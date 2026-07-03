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
     * 兼容新格式 {schema, render, remote} 和旧扁平格式
     * @return array [{prop, remote_table, remote_pk, remote_label}]
     */
    protected function getRemoteSelectJoins(): array
    {
        $joins = [];
        $fields = $this->config->fields ?: [];
        foreach ($fields as $field) {
            // 格式检测
            if (isset($field['schema'])) {
                // 新结构格式
                $formType = $field['render']['form_type'] ?? '';
                if (!in_array($formType, ['remoteSelect', 'remoteSelects'])) continue;

                $remote = $field['remote'] ?? [];
                if (empty($remote['table'])) continue;

                $joins[] = [
                    'prop'          => $field['schema']['prop'] ?? '',
                    'remote_table'  => $remote['table'],
                    'remote_pk'     => $remote['pk'] ?? 'id',
                    'remote_label'  => $remote['label'] ?? 'name',
                ];
            } else {
                // 旧扁平格式
                $formType = $field['form_type'] ?? '';
                if (!in_array($formType, ['remoteSelect', 'remoteSelects'])) continue;

                $inputAttr = $field['form_input_attr'];
                if (is_string($inputAttr)) {
                    $inputAttr = json_decode($inputAttr, true) ?: [];
                }
                if (!is_array($inputAttr)) $inputAttr = [];

                if (empty($inputAttr['remote_table'])) continue;

                $joins[] = [
                    'prop'          => $field['prop'],
                    'remote_table'  => $inputAttr['remote_table'],
                    'remote_pk'     => $inputAttr['remote_pk'] ?? 'id',
                    'remote_label'  => $inputAttr['remote_label'] ?? 'name',
                ];
            }
        }
        return $joins;
    }

    /**
     * 获取 remoteExpand 虚拟字段配置
     * 这些字段引用某个 remoteSelect 父字段，从关联表中额外获取指定的字段值
     * @return array [{prop, parent_field, remote_field}]
     */
    protected function getRemoteExpandColumns(): array
    {
        $columns = [];
        $fields = $this->config->fields ?: [];
        foreach ($fields as $field) {
            if (isset($field['schema'])) {
                if (($field['schema']['type'] ?? '') === 'virtual') {
                    $render = $field['render'] ?? [];
                    if (($render['design_type'] ?? '') === 'remoteExpand') {
                        $pf = $render['parent_field'] ?? '';
                        $rf = $render['remote_field'] ?? '';
                        if ($pf && $rf) {
                            $columns[] = [
                                'prop'          => $field['schema']['prop'] ?? '',
                                'parent_field'  => $pf,
                                'remote_field'  => $rf,
                            ];
                        }
                    }
                }
            }
        }
        return $columns;
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

        // 详情表预过滤：detail_filter_key=外键字段, detail_filter_value=父表主键值
        $detailFilterKey   = $this->request->param('detail_filter_key');
        $detailFilterValue = $this->request->param('detail_filter_value');

        $res = $this->model
            ->alias($alias)
            ->where($where)
            ->when($detailFilterKey && $detailFilterValue !== null, function ($query) use ($detailFilterKey, $detailFilterValue) {
                $query->where($detailFilterKey, $detailFilterValue);
            })
            ->order($order)
            ->paginate($limit);

        $list = $res->items();

        // 批量补充 remoteSelect 关联标签（避免 JOIN 的字段名转换问题）
        $remoteJoins = $this->getRemoteSelectJoins();
        $expandCols  = $this->getRemoteExpandColumns();
        if (($remoteJoins || $expandCols) && $list) {
            // 转为纯数组，确保可自由添加 __text / __field 字段
            $list = array_map(fn($item) => is_array($item) ? $item : (method_exists($item, 'toArray') ? $item->toArray() : (array) $item), $list);

            $connection = $this->config->db_connection ?: '';

            // ── 1. remoteSelect 标签补充（{prop}__text）──
            // 构建 prop → joinConfig 的映射，供 remoteExpand 复用
            $joinsByProp = [];
            foreach ($remoteJoins as $rj) {
                $joinsByProp[$rj['prop']] = $rj;
            }

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

            // ── 2. remoteExpand 字段补充（{parent}__{remote_field}）──
            if ($expandCols) {
                // 按 parent_field 分组，减少重复查询
                $byParent = [];
                foreach ($expandCols as $ec) {
                    $pf = $ec['parent_field'];
                    if (!isset($byParent[$pf])) $byParent[$pf] = [];
                    $byParent[$pf][] = $ec['remote_field'];
                }

                foreach ($byParent as $parentField => $remoteFields) {
                    if (!isset($joinsByProp[$parentField])) continue;
                    $rj = $joinsByProp[$parentField];

                    $ids = array_map(fn($row) => $row[$parentField] ?? null, $list);
                    $ids = array_unique(array_filter($ids, fn($v) => $v !== null && $v !== ''));
                    if (empty($ids)) continue;

                    $remoteModel = new DynamicModel();
                    $remoteModel->setDynamicTable($rj['remote_table'], $rj['remote_pk'], $connection);

                    // 一次查询获取所有需要的远程字段
                    $selectFields = array_unique(array_merge([$rj['remote_pk']], $remoteFields));
                    $remoteRows = $remoteModel->db()
                        ->where($rj['remote_pk'], 'in', $ids)
                        ->field($selectFields)
                        ->select()
                        ->toArray();

                    // 构建查找表: pk → {field: value}
                    $lookup = [];
                    foreach ($remoteRows as $rr) {
                        $pk = $rr[$rj['remote_pk']] ?? null;
                        if ($pk !== null) $lookup[$pk] = $rr;
                    }

                    foreach ($list as &$row) {
                        $val = $row[$parentField] ?? null;
                        if ($val !== null && isset($lookup[$val])) {
                            foreach ($remoteFields as $rf) {
                                $row[$parentField . '__' . $rf] = $lookup[$val][$rf] ?? '';
                            }
                        }
                    }
                    unset($row);
                }
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

        // 在字段配置中找到对应字段（兼容新旧格式）
        $fieldConfig = null;
        foreach ($this->config->fields ?: [] as $f) {
            $prop = isset($f['schema']) ? ($f['schema']['prop'] ?? '') : ($f['prop'] ?? '');
            if ($prop === $fieldProp) {
                $fieldConfig = $f;
                break;
            }
        }
        if (!$fieldConfig) {
            $this->success('', ['list' => [], 'total' => 0]);
            return;
        }

        // 提取 remote 配置（兼容新旧格式）
        if (isset($fieldConfig['schema'])) {
            // 新结构格式
            $remote = $fieldConfig['remote'] ?? [];
            $remoteTable  = $remote['table'] ?? '';
            $remotePk     = $remote['pk'] ?? 'id';
            $remoteLabel  = $remote['label'] ?? 'name';
        } else {
            // 旧扁平格式
            $inputAttr = $fieldConfig['form_input_attr'];
            if (is_string($inputAttr)) {
                $inputAttr = json_decode($inputAttr, true) ?: [];
            }
            if (!is_array($inputAttr)) $inputAttr = [];

            $remoteTable  = $inputAttr['remote_table'] ?? '';
            $remotePk     = $inputAttr['remote_pk'] ?? 'id';
            $remoteLabel  = $inputAttr['remote_label'] ?? 'name';
        }
        
        if (empty($remoteTable)) {
            $this->success('', ['list' => [], 'total' => 0]);
            return;
        }

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
