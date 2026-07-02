<?php

namespace app\admin\controller\dynamic;

use app\common\controller\Backend;
use app\admin\model\dynamic\TableConfig;
use app\admin\model\dynamic\DynamicModel;
use think\exception\HttpResponseException;
use Throwable;

/**
 * 动态表格数据控制器
 *
 * 根据 table 参数动态绑定模型，提供通用 CRUD
 * 自动处理 remoteSelect 字段的关联标签（查询后在 PHP 中批量补充）
 */
class Table extends Backend
{
    protected object $model;
    protected ?TableConfig $config = null;

    /**
     * 动态表共用同一控制器，权限由菜单可见性控制
     */
    protected array $noNeedPermission = ['index', 'add', 'edit', 'del'];

    public function initialize(): void
    {
        parent::initialize();

        $tableName = $this->request->param('table');
        if (!$tableName) {
            $this->error('参数 table 不能为空');
        }

        $this->config = TableConfig::where('name', $tableName)->where('status', 'enabled')->find();
        if (!$this->config) {
            $this->error('动态表格配置不存在：' . $tableName);
        }

        $this->model = new DynamicModel();
        $this->model->setDynamicTable(
            $this->config->db_table,
            $this->config->pk ?: 'id',
            $this->config->db_connection ?: ''
        );

        if ($this->config->default_sort_field) {
            $this->defaultSortField = $this->config->default_sort_field . ',' . ($this->config->default_sort_order ?: 'desc');
        }

        if ($this->config->quick_search_fields) {
            $this->quickSearchField = $this->config->quick_search_fields;
        }

        $this->modelValidate = false;
    }

    /**
     * 获取 remoteSelect 字段的关联配置
     * @return array [{prop, remote_table, remote_pk, remote_label}]
     */
    protected function getRemoteSelectJoins(): array
    {
        $joins = [];
        $fields = $this->config->fields ?: [];
        foreach ($fields as $field) {
            $formType = $field['form_type'] ?? '';
            if (!in_array($formType, ['remoteSelect', 'remoteSelects'])) continue;

            $inputAttr = $field['form_input_attr'];
            if (is_string($inputAttr)) {
                $inputAttr = json_decode($inputAttr, true) ?: [];
            }
            if (!is_array($inputAttr)) $inputAttr = [];

            if (empty($inputAttr['remote_table'])) continue;

            $joins[] = [
                'prop'         => $field['prop'],
                'remote_table'  => $inputAttr['remote_table'],
                'remote_pk'     => $inputAttr['remote_pk'] ?? 'id',
                'remote_label'  => $inputAttr['remote_label'] ?? 'name',
            ];
        }
        return $joins;
    }

    /**
     * 列表查询 — 标准查询后，在 PHP 中批量补充 remoteSelect 关联标签
     * 关联表字段以 {prop}__text 返回给前端
     */
    public function index(): void
    {
        // select=true 时走 remoteSelect 下拉数据
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();

        $res = $this->model
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $list = $res->items();

        // 批量补充 remoteSelect 关联标签（避免 JOIN 的字段名转换问题）
        $remoteJoins = $this->getRemoteSelectJoins();
        if ($remoteJoins && $list) {
            // 转为纯数组，确保可自由添加 __text 字段
            $list = array_map(fn($item) => is_array($item) ? $item : (method_exists($item, 'toArray') ? $item->toArray() : (array) $item), $list);

            $connection = $this->config->db_connection ?: '';
            foreach ($remoteJoins as $rj) {
                $prop   = $rj['prop'];
                $ids    = array_map(fn($row) => $row[$prop] ?? null, $list);
                $ids    = array_unique(array_filter($ids, fn($v) => $v !== null && $v !== ''));
                if (empty($ids)) continue;

                $remoteModel = new DynamicModel();
                $remoteModel->setDynamicTable($rj['remote_table'], $rj['remote_pk'], $connection);

                $labels = $remoteModel->db()
                    ->where($rj['remote_pk'], 'in', $ids)
                    ->column($rj['remote_label'], $rj['remote_pk']);

                foreach ($list as &$row) {
                    $val = $row[$prop] ?? null;
                    $row[$prop . '__text'] = $labels[$val] ?? '';
                }
                unset($row);
            }
        }

        $this->success('', [
            'list'   => $list,
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * remoteSelect 下拉数据接口
     *
     * 前端 remoteSelect 组件发送：
     *   GET index?table=xxx&select=true&field=user_id&quickSearch=John&page=1&limit=10&initKey=id&initValue=5
     *
     * 本方法读取 field 对应的关联配置，查询关联表返回 {pk, label} 数据
     */
    public function select(): void
    {
        $fieldProp = $this->request->param('field');
        if (!$fieldProp) {
            // 无 field 参数 — 返回空（兼容默认 select 行为）
            $this->success('', ['list' => [], 'total' => 0]);
            return;
        }

        // 在字段配置中找到对应字段
        $fieldConfig = null;
        foreach ($this->config->fields ?: [] as $f) {
            if (($f['prop'] ?? '') === $fieldProp) {
                $fieldConfig = $f;
                break;
            }
        }
        if (!$fieldConfig) {
            $this->success('', ['list' => [], 'total' => 0]);
            return;
        }

        $inputAttr = $fieldConfig['form_input_attr'];
        if (is_string($inputAttr)) {
            $inputAttr = json_decode($inputAttr, true) ?: [];
        }
        if (!is_array($inputAttr)) $inputAttr = [];

        if (empty($inputAttr['remote_table'])) {
            $this->success('', ['list' => [], 'total' => 0]);
            return;
        }

        $remoteTable  = $inputAttr['remote_table'];
        $remotePk     = $inputAttr['remote_pk'] ?? 'id';
        $remoteLabel  = $inputAttr['remote_label'] ?? 'name';
        $connection   = $this->config->db_connection ?: '';

        $remoteModel = new DynamicModel();
        $remoteModel->setDynamicTable($remoteTable, $remotePk, $connection);

        $query = $remoteModel->db();

        // 关键词搜索（按 label 字段模糊匹配）
        $keyword = $this->request->param('quickSearch', '');
        if ($keyword) {
            $query->whereLike($remoteLabel, "%$keyword%");
        }

        // initValue 预选（编辑时确保当前值在选项中）
        $initValue = $this->request->param('initValue');
        if ($initValue !== null && $initValue !== '') {
            // 如果有 initValue，优先返回该行（即使没有关键词也保证返回）
            $query->whereOr($remotePk, $initValue);
        }

        $page  = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);

        $total = $remoteModel->db()
            ->when($keyword, fn($q) => $q->whereLike($remoteLabel, "%$keyword%"))
            ->when($initValue !== null && $initValue !== '', fn($q) => $q->whereOr($remotePk, $initValue))
            ->count();

        $list = $query->field([$remotePk, $remoteLabel])
            ->page($page, $limit)
            ->select()
            ->toArray();

        $this->success('', [
            'list'  => $list,
            'total' => $total,
        ]);
    }
}
