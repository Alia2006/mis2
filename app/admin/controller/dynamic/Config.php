<?php

namespace app\admin\controller\dynamic;

use app\common\controller\Backend;
use app\admin\model\dynamic\TableConfig;
use app\admin\model\AdminRule;
use app\admin\library\crud\Helper;
use ba\TableManager;
use think\facade\Db;
use think\facade\Lang;
use Throwable;

/**
 * 动态表格配置管理
 *
 * 方法说明：
 *   index()         → 管理页面分页列表（继承 Backend trait 标准行为）
 *   edit()/add()/del() → 配置的增删改（fields 作为 JSON 字段随配置一起保存）
 *   getConfig()     → 前端动态页面获取配置 JSON（按 name）
 *   getTableFields() → 设计器导入数据库字段
 */
class Config extends Backend
{
    protected object $model;
    protected array $noNeedPermission = ['getConfig', 'getConfigById', 'getTableList', 'getTableFields', 'getMenuTree'];

    /**
     * 表级多语言字段（存储 JSON: {"zh-cn":"...","en":"..."}）
     */
    protected array $configLangFields = ['title', 'remark'];

    /**
     * 字段级多语言字段
     */
    protected array $fieldLangFields = ['label', 'column_operator_placeholder'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new TableConfig();
    }

    /**
     * 解析多语言字段值
     * - 普通字符串 → 原样返回（向后兼容）
     * - JSON 对象  → 按当前请求语言返回对应值，找不到则回退到第一个可用语言
     */
    protected function resolveLangValue(mixed $value): string
    {
        if (!$value) return '';

        // 非字符串直接转字符串返回
        if (!is_string($value)) return (string)$value;

        $trimmed = trim($value);
        if (!str_starts_with($trimmed, '{')) return $value;

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) return $value;

        // 获取当前请求语言
        $lang = Lang::getLangSet() ?: 'zh-cn';

        // 精确匹配
        if (isset($decoded[$lang]) && $decoded[$lang] !== '') return $decoded[$lang];

        // 模糊匹配（zh-cn 匹配 zh，en-us 匹配 en）
        $short = explode('-', $lang)[0];
        foreach ($decoded as $key => $val) {
            if (str_starts_with($key, $short) && $val !== '') return $val;
        }

        // 回退：第一个非空值
        foreach ($decoded as $val) {
            if ($val !== '') return $val;
        }

        return '';
    }

    /**
     * 确保 JSON 字段返回数组/对象
     * 兼容迁移数据中的双重编码 + 多层 HTML 实体编码
     */
    protected function decodeJsonField(mixed $value): mixed
    {
        if (!$value) return null;
        if (is_array($value)) return $value;
        if (is_string($value)) {
            // 循环反转 HTML 实体（旧数据可能经过多次 htmlspecialchars 过滤）
            $cleaned = $value;
            for ($i = 0; $i < 3; $i++) {
                $decoded = htmlspecialchars_decode($cleaned, ENT_QUOTES);
                if ($decoded === $cleaned) break;
                $cleaned = $decoded;
            }
            $trimmed = trim($cleaned);
            if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                $decoded = json_decode($cleaned, true);
                if (is_array($decoded)) return $decoded;
            }
        }
        return $value;
    }

    /**
     * 解析多语言标题为菜单显示用的单语言字符串
     */
    protected function resolveMenuTitle(mixed $value): string
    {
        $resolved = $this->resolveLangValue($value);
        // 如果解析失败或为空，返回 name 作为后备
        return $resolved;
    }

    // ─── admin_rule 同步方法 ───────────────────────────────────

    /**
     * 按钮权限名 → 中文标题映射
     */
    protected array $buttonTitles = [
        'index'    => '查看',
        'add'      => '添加',
        'edit'     => '编辑',
        'del'      => '删除',
        'sortable' => '排序',
    ];

    /**
     * 根据表格配置计算需要的按钮权限列表
     * @return string[] 权限名（不含 menuName 前缀）
     */
    protected function getNeededButtons(TableConfig $config): array
    {
        $headerBtns = $config->header_buttons ?: [];
        $rowBtns    = $config->row_buttons ?: [];
        $allBtns    = array_unique(array_merge($headerBtns, $rowBtns));

        $buttons = ['index']; // 始终需要

        if (in_array('add', $allBtns))    $buttons[] = 'add';
        if (in_array('edit', $allBtns))   $buttons[] = 'edit';
        if (in_array('delete', $allBtns)) $buttons[] = 'del';

        // 检查是否有可排序字段（从 JSON fields 数组读取）
        $fields = $config->fields ?: [];
        $hasSortable = false;
        foreach ($fields as $field) {
            // 新格式：render.column_sortable；旧格式：column_sortable
            $sortable = isset($field['schema'])
                ? ($field['render']['column_sortable'] ?? '')
                : ($field['column_sortable'] ?? '');
            if ($sortable === 'custom') {
                $hasSortable = true;
                break;
            }
        }
        if ($hasSortable) $buttons[] = 'sortable';

        return $buttons;
    }

    /**
     * 同步菜单的子按钮权限（增删改）
     */
    protected function syncRuleButtons(int $menuId, string $menuName, array $neededButtons, int $status): void
    {
        // 已存在的按钮
        $existing = AdminRule::where('pid', $menuId)
            ->where('type', 'button')
            ->column('name');
        $existingSet = array_flip($existing);

        // 需要的完整权限名
        $neededNames = array_map(fn($b) => $menuName . '/' . $b, $neededButtons);
        $neededSet   = array_flip($neededNames);

        // 删除多余的
        $toRemove = array_diff($existing, $neededNames);
        if ($toRemove) {
            AdminRule::where('name', 'in', array_values($toRemove))->delete();
        }

        // 新增缺失的
        $now = time();
        foreach ($neededButtons as $btn) {
            $btnName = $menuName . '/' . $btn;
            if (!isset($existingSet[$btnName])) {
                AdminRule::insert([
                    'pid'         => $menuId,
                    'type'        => 'button',
                    'title'       => $this->buttonTitles[$btn] ?? $btn,
                    'name'        => $btnName,
                    'path'        => '',
                    'icon'        => '',
                    'menu_type'   => null,
                    'url'         => '',
                    'component'   => '',
                    'keepalive'   => 0,
                    'extend'      => 'none',
                    'remark'      => '',
                    'weigh'       => 0,
                    'status'      => $status,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
            }
        }

        // 更新现有按钮状态
        AdminRule::where('pid', $menuId)
            ->where('type', 'button')
            ->update(['status' => $status, 'update_time' => $now]);
    }

    protected function syncAdminRuleCreate(TableConfig $config): void
    {
        $menuName = 'dynamic/' . $config->name;

        // 已存在则补全按钮
        if ($existing = AdminRule::where('name', $menuName)->find()) {
            $status = $config->status === 'enabled' ? 1 : 0;
            $needed = $this->getNeededButtons($config);
            $this->syncRuleButtons($existing->id, $menuName, $needed, $status);
            return;
        }

        // 父级菜单
        $pid = (int)($config->menu_pid ?: 0);
        $title = $this->resolveMenuTitle($config->getData('title'));
        if (!$title) $title = $config->name;
        $status = $config->status === 'enabled' ? 1 : 0;
        $now = time();

        // 1. 创建菜单
        $menuId = AdminRule::insertGetId([
            'pid'         => $pid,
            'type'        => 'menu',
            'title'       => $title,
            'name'        => $menuName,
            'path'        => $menuName,
            'icon'        => 'fa fa-list',
            'menu_type'   => 'tab',
            'url'         => '',
            'component'   => '/src/views/backend/dynamic/index.vue',
            'keepalive'   => 1,
            'extend'      => 'none',
            'remark'      => '',
            'weigh'       => 0,
            'status'      => $status,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        // 2. 创建子按钮权限（根据配置动态生成）
        $needed = $this->getNeededButtons($config);
        $this->syncRuleButtons($menuId, $menuName, $needed, $status);
    }

    /**
     * 更新动态表格对应的菜单规则（标题、状态、pid、按钮权限）
     */
    protected function syncAdminRuleUpdate(TableConfig $config): void
    {
        $menuName = 'dynamic/' . $config->name;
        $title    = $this->resolveMenuTitle($config->getData('title'));
        if (!$title) $title = $config->name;
        $newPid   = (int)($config->menu_pid ?: 0);
        $status   = $config->status === 'enabled' ? 1 : 0;

        $rule = AdminRule::where('name', $menuName)->find();

        if ($rule) {
            $rule->title  = $title;
            $rule->status = $status;
            $rule->pid    = $newPid;
            $rule->save();

            // 同步按钮权限
            $needed = $this->getNeededButtons($config);
            $this->syncRuleButtons($rule->id, $menuName, $needed, $status);
        } else {
            // 不存在则创建（含按钮）
            $this->syncAdminRuleCreate($config);
        }
    }

    /**
     * 删除动态表格对应的菜单规则（含子节点）
     */
    protected function syncAdminRuleDelete(string $configName): void
    {
        $menuName = 'dynamic/' . $configName;
        $rule     = AdminRule::where('name', $menuName)->find();

        if ($rule) {
            // 删除子节点
            AdminRule::where('pid', $rule->id)->delete();
            $rule->delete();
        }
    }

    // ─── admin_rule 同步方法 END ───────────────────────────────

    /**
     * 前端动态页面获取表格配置
     * GET /admin/dynamic.Config/getConfig?name=employee
     * 返回前端需要的完整 DynamicTableConfig JSON
     */
    public function getConfig(): void
    {
        $name = $this->request->param('name');
        if (!$name) {
            $this->error('参数 name 不能为空');
        }

        $config = TableConfig::where('name', $name)->where('status', 'enabled')->find();
        if (!$config) {
            $this->error('动态表格配置不存在：' . $name);
        }

        $this->success('', [
            'data' => $this->buildFrontendConfig($config),
        ]);
    }

    /**
     * 按配置 ID 获取前端表格配置（详情抽屉用）
     * GET /admin/dynamic.Config/getConfigById?id=5
     */
    public function getConfigById(): void
    {
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数 id 不能为空');
        }

        $config = TableConfig::find($id);
        if (!$config || $config->status !== 'enabled') {
            $this->error('动态表格配置不存在或已禁用');
        }

        $this->success('', [
            'data' => $this->buildFrontendConfig($config),
        ]);
    }

    /**
     * 获取已启用的动态表列表（设计器选择详情表用）
     * GET /admin/dynamic.Config/getTableList
     */
    public function getTableList(): void
    {
        $list = TableConfig::where('status', 'enabled')
            ->field(['id', 'name', 'title', 'pk'])
            ->order('id', 'desc')
            ->select()
            ->toArray();

        // 解析多语言标题
        foreach ($list as &$item) {
            $item['title'] = $this->resolveLangValue($item['title'] ?? '');
        }

        $this->success('', ['list' => $list]);
    }

    /**
     * 将数据库配置记录转换为前端 DynamicTableConfig 结构
     * 兼容新格式 {schema, render, remote} 和旧扁平格式
     */
    protected function buildFrontendConfig(TableConfig $config): array
    {
        $fields = $config->fields ?: [];

        // ─── 构建列定义 ───
        $columns = [];
        foreach ($fields as $field) {
            // 格式检测 + 数据提取
            if (isset($field['schema'])) {
                // 新结构格式
                $render = $field['render'] ?? [];
                if (empty($render['column_show'] ?? true)) continue;

                $prop      = $field['schema']['prop'] ?? '';
                $label     = $this->resolveLangValue($render['label'] ?? '');
                $formType  = $render['form_type'] ?? '';
                $colAlign  = $render['column_align'] ?? 'center';
                $colWidth  = $render['column_width'] ?? null;
                $colRender = $render['column_render'] ?? null;
                $colOp     = $render['column_operator'] ?? null;
                $colSort   = $render['column_sortable'] ?? null;
                $colComSR  = $render['column_com_search_render'] ?? null;
                $colRV     = $render['column_replace_value'] ?? null;
                $colCustom = $render['column_custom'] ?? null;
                $colTF     = $render['column_time_format'] ?? null;
                $colOpPH   = $render['column_operator_placeholder'] ?? null;
            } else {
                // 旧扁平格式
                if (empty($field['column_show'])) continue;

                $prop      = $field['prop'] ?? '';
                $label     = $this->resolveLangValue($field['label'] ?? '');
                $formType  = $field['form_type'] ?? '';
                $colAlign  = $field['column_align'] ?? 'center';
                $colWidth  = $field['column_width'] ?? null;
                $colRender = $field['column_render'] ?? null;
                $colOp     = $field['column_operator'] ?? null;
                $colSort   = $field['column_sortable'] ?? null;
                $colComSR  = $field['column_com_search_render'] ?? null;
                $colRV     = $field['column_replace_value'] ?? null;
                $colCustom = $field['column_custom'] ?? null;
                $colTF     = $field['column_time_format'] ?? null;
                $colOpPH   = $field['column_operator_placeholder'] ?? null;
                $render    = $field;
            }

            $isRemoteSelect = in_array($formType, ['remoteSelect', 'remoteSelects']);

            $col = [
                'prop'  => $isRemoteSelect ? $prop . '__text' : $prop,
                'label' => $label,
                'align' => $colAlign ?: 'center',
            ];
            if (!empty($colWidth)) $col['width'] = $colWidth;
            if (!empty($colRender)) $col['render'] = $colRender;
            if (!empty($colOp)) {
                $col['operator'] = ($colOp === 'false') ? false : $colOp;
            }
            // remoteSelect 列禁止排序
            if (!$isRemoteSelect && !empty($colSort) && $colSort !== 'false') {
                $col['sortable'] = $colSort;
            }
            if (!$isRemoteSelect && !empty($colComSR)) {
                $col['comSearchRender'] = $colComSR;
            }
            $rv = $this->decodeJsonField($colRV);
            if ($rv) $col['replaceValue'] = $rv;
            $custom = $this->decodeJsonField($colCustom);
            if ($custom) $col['custom'] = $custom;
            if (!empty($colTF)) $col['timeFormat'] = $colTF;
            if (!empty($colOpPH)) {
                $col['operatorPlaceholder'] = $this->resolveLangValue($colOpPH);
            }

            // Virtual field properties (computed / remoteExpand)
            if (isset($field['schema'])) {
                $schemaType = $field['schema']['type'] ?? '';
                if ($schemaType === 'virtual') {
                    $designType = $render['design_type'] ?? '';
                    if ($designType === 'computed') {
                        // Computed column: include template for frontend evaluation
                        $col['template'] = $render['template'] ?? '';
                        $col['render'] = 'computed';
                    } elseif ($designType === 'remoteExpand') {
                        // RemoteExpand: prop becomes {parent}__{remote_field}
                        $parentField = $render['parent_field'] ?? '';
                        $remoteField = $render['remote_field'] ?? '';
                        if ($parentField && $remoteField) {
                            $col['prop'] = $parentField . '__' . $remoteField;
                        }
                        // Respect user-selected render (e.g. currency, tag, datetime);
                        // only default to 'none' if no render was explicitly set
                        if (empty($col['render'])) {
                            $col['render'] = 'none';
                        }
                    }
                }
            }

            $columns[] = $col;
        }

        // ─── 构建表单字段 ───
        $formFields = [];
        foreach ($fields as $field) {
            // Skip virtual fields (computed/remoteExpand) — display only, no form input
            if (isset($field['schema']) && ($field['schema']['type'] ?? '') === 'virtual') {
                continue;
            }
            // 格式检测 + 数据提取
            if (isset($field['schema'])) {
                $schema  = $field['schema'];
                $render  = $field['render'] ?? [];
                $remote  = $field['remote'] ?? [];
                $prop    = $schema['prop'] ?? '';
                $label   = $this->resolveLangValue($render['label'] ?? '');
                $fType   = $render['form_type'] ?? 'string';
                $fVal    = $this->decodeJsonField($render['form_validators'] ?? null);
                $fAttr   = $this->decodeJsonField($render['form_input_attr'] ?? null);
                $rTable  = $remote['table'] ?? '';
                $rPk     = $remote['pk'] ?? 'id';
                $rLabel  = $remote['label'] ?? 'name';
            } else {
                $prop    = $field['prop'] ?? '';
                $label   = $this->resolveLangValue($field['label'] ?? '');
                $fType   = $field['form_type'] ?? 'string';
                $fVal    = $this->decodeJsonField($field['form_validators'] ?? null);
                $fAttr   = $this->decodeJsonField($field['form_input_attr'] ?? null);
                $rTable  = $fAttr['remote_table'] ?? '';
                $rPk     = $fAttr['remote_pk'] ?? 'id';
                $rLabel  = $fAttr['remote_label'] ?? 'name';
            }

            if (!is_array($fAttr)) $fAttr = [];

            $ff = [
                'prop'  => $prop,
                'label' => $label,
                'type'  => $fType ?: 'string',
            ];
            if ($fVal) {
                // remoteSelect 值为字符串 PK，移除类型验证器
                if (in_array($fType, ['remoteSelect', 'remoteSelects'])) {
                    $fVal = array_values(array_filter($fVal, function ($v) {
                        return !in_array($v, ['number', 'integer', 'float', 'date', 'url', 'email']);
                    }));
                }
                if ($fVal) $ff['validators'] = $fVal;
            }

            // remoteSelect 字段注入下拉组件所需属性
            if (in_array($ff['type'], ['remoteSelect', 'remoteSelects'])) {
                $fAttr['remoteUrl'] = '/admin/dynamic.Table/index';
                $fAttr['pk']        = $rPk;
                $fAttr['field']     = $rLabel;
                $fAttr['params']    = [
                    'table' => $config->name,
                    'field' => $prop,
                ];
            }

            // 移除不需要传给前端的内部属性
            unset($fAttr['remote_table'], $fAttr['remote_pk'], $fAttr['remote_label']);

            if ($fAttr) $ff['inputAttr'] = $fAttr;
            $ff['placeholder'] = '请输入' . $label;
            if (in_array($ff['type'], ['datetime', 'date', 'time', 'year', 'remoteSelect', 'remoteSelects', 'select', 'selects'])) {
                $ff['placeholder'] = '请选择' . $label;
            }

            $formFields[] = $ff;
        }

        // ─── 构建表级配置 ───
        $headerButtons = $config->header_buttons ?: ['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay'];
        $rowButtons    = $config->row_buttons ?: ['edit', 'delete'];

        // 默认排序
        $defaultOrder = null;
        if ($config->default_sort_field) {
            $defaultOrder = [
                'prop'  => $config->default_sort_field,
                'order' => $config->default_sort_order ?: 'desc',
            ];
        }

        // 快速搜索占位
        $quickSearchFields = $config->quick_search_fields ?: [];
        $quickSearchPlaceholder = '';
        if (!empty($quickSearchFields)) {
            $quickSearchPlaceholder = implode(', ', $quickSearchFields);
        }

        // 构建 dblClickNotEditColumn：switch 类型字段双击直接切换，不打开编辑弹窗
        $dblClickNotEditColumn = [null];
        foreach ($fields as $field) {
            // 格式检测
            if (isset($field['schema'])) {
                $render = $field['render'] ?? [];
                if (($render['column_render'] ?? '') === 'switch') {
                    $dblClickNotEditColumn[] = $field['schema']['prop'] ?? '';
                }
            } else {
                if (($field['column_render'] ?? '') === 'switch') {
                    $dblClickNotEditColumn[] = $field['prop'] ?? '';
                }
            }
        }

        // ─── 构建详情表配置 ───
        $detail = null;
        if (!empty($config->detail_table_id)) {
            $detailConfig = TableConfig::where('id', $config->detail_table_id)
                ->where('status', 'enabled')
                ->find();
            if ($detailConfig) {
                $detail = [
                    'tableId'    => (int)$config->detail_table_id,
                    'tableName'  => $detailConfig->name,
                    'title'      => $this->resolveLangValue($detailConfig->getData('title')),
                    'foreignKey' => $config->detail_foreign_key ?: $config->pk,
                    'pk'         => $detailConfig->pk ?: 'id',
                ];
            }
        }

        return [
            'name'                   => $config->name,
            'title'                  => $this->resolveLangValue($config->getData('title')),
            'controllerUrl'          => '/admin/dynamic.Table/',
            'controllerParams'       => ['table' => $config->name],
            'pk'                     => $config->pk,
            'remark'                 => $this->resolveLangValue($config->getData('remark')),
            'quickSearchPlaceholder' => $quickSearchPlaceholder,
            'defaultOrder'           => $defaultOrder,
            'headerButtons'          => $headerButtons,
            'rowButtonNames'         => $rowButtons,
            'defaultItems'           => $config->default_items ?: [],
            'dblClickNotEditColumn'  => $dblClickNotEditColumn,
            'columns'                => $columns,
            'formFields'             => $formFields,
            'detail'                 => $detail,
        ];
    }

    /**
     * 新增配置
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            unset($data['designChange']);
            $fields = $this->prepareFields($data['fields'] ?? []);
            $data['fields'] = $fields;

            // JSON 字段编码（表级多语言）
            foreach ($this->configLangFields as $lf) {
                if (isset($data[$lf]) && is_array($data[$lf])) {
                    $data[$lf] = json_encode($data[$lf], JSON_UNESCAPED_UNICODE);
                }
            }
            $data['header_buttons'] = json_encode($data['header_buttons'] ?? ['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']);
            $data['row_buttons']    = json_encode($data['row_buttons'] ?? ['edit', 'delete']);
            if (isset($data['default_items'])) {
                $data['default_items'] = json_encode($data['default_items']);
            }
            if (isset($data['quick_search_fields']) && is_array($data['quick_search_fields'])) {
                $data['quick_search_fields'] = implode(',', $data['quick_search_fields']);
            }

            $this->model->startTrans();
            try {
                $config = $this->model->create($data);
                $this->syncAdminRuleCreate($config);
                $this->model->commit();

                // 同步数据库表结构（DDL 在 commit 后执行，不可回滚）
                try {
                    $this->syncTableSchema($data, []);
                } catch (Throwable $schemaErr) {
                    $this->success('添加成功，但数据表同步失败：' . $schemaErr->getMessage());
                    return;
                }

                $this->success('添加成功');
            } catch (\think\exception\HttpResponseException $e) {
                throw $e;
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
        }
        $this->error('参数错误');
    }

    /**
     * 编辑配置
     */
    public function edit(): void
    {
        $id = $this->request->param('id');
        $row = $this->model->find($id);
        if (!$row) {
            $this->error('记录不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $designChange = $data['designChange'] ?? [];
            unset($data['designChange']);
            $data['fields'] = $this->prepareFields($data['fields'] ?? []);

            // JSON 字段编码（表级多语言）
            foreach ($this->configLangFields as $lf) {
                if (isset($data[$lf]) && is_array($data[$lf])) {
                    $data[$lf] = json_encode($data[$lf], JSON_UNESCAPED_UNICODE);
                }
            }
            if (isset($data['header_buttons'])) {
                $data['header_buttons'] = json_encode($data['header_buttons']);
            }
            if (isset($data['row_buttons'])) {
                $data['row_buttons'] = json_encode($data['row_buttons']);
            }
            if (isset($data['default_items'])) {
                $data['default_items'] = json_encode($data['default_items']);
            }
            if (isset($data['quick_search_fields']) && is_array($data['quick_search_fields'])) {
                $data['quick_search_fields'] = implode(',', $data['quick_search_fields']);
            }

            $this->model->startTrans();
            try {
                $row->save($data);
                $this->syncAdminRuleUpdate($row);
                $this->model->commit();

                // 同步数据库表结构（DDL 在 commit 后执行，不可回滚）
                if (!empty($designChange)) {
                    try {
                        $this->syncTableSchema($data, $designChange);
                    } catch (Throwable $schemaErr) {
                        $this->success('保存成功，但数据表结构同步失败：' . $schemaErr->getMessage());
                        return;
                    }
                }

                $this->success('保存成功');
            } catch (\think\exception\HttpResponseException $e) {
                throw $e;
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
        }

        // GET 请求返回详情（规范化 fields 子属性后返回）
        $rowData = $row->toArray();
        if (!empty($rowData['fields']) && is_array($rowData['fields'])) {
            $rowData['fields'] = array_map(fn($f) => $this->normalizeField($f), $rowData['fields']);
        }
        $this->success('', ['row' => $rowData]);
    }

    /**
     * 删除配置
     */
    public function del(): void
    {
        $ids = $this->request->param('ids/a', []);
        if (empty($ids)) {
            $ids = [$this->request->param('id')];
        }

        // 先查出 name 列表，用于清理 admin_rule
        $configs = $this->model->where('id', 'in', $ids)->column('name');

        $this->model->startTrans();
        try {
            $this->model->destroy($ids);
            foreach ($configs as $name) {
                $this->syncAdminRuleDelete($name);
            }
            $this->model->commit();
            $this->success('删除成功');
        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 准备 fields 数据：编码字段级多语言和 JSON 属性
     * 返回数组，由模型 $json 自动编码为 JSON 字符串写入数据库
     * 兼容新格式 {schema, render, remote} 和旧扁平格式
     */
    protected function prepareFields(array $fields): array
    {
        $cleaned = [];
        $sort = 0;
        foreach ($fields as $field) {
            // 确保字段是数组
            if (!is_array($field)) continue;

            // 规范化子属性：解码 HTML 实体、JSON 字符串还原为数组
            $field = $this->normalizeField($field);

            $field['sort'] = $sort++;

            if (isset($field['schema'])) {
                // ─── 新结构格式：多语言 + JSON 在 render 内部 ───
                $render = $field['render'] ?? [];
                foreach ($this->fieldLangFields as $lf) {
                    if (isset($render[$lf]) && is_array($render[$lf])) {
                        $render[$lf] = json_encode($render[$lf], JSON_UNESCAPED_UNICODE);
                    }
                }
                // column_show 转布尔
                $render['column_show'] = !empty($render['column_show'] ?? true);
                $field['render'] = $render;
            } else {
                // ─── 旧扁平格式 ───
                foreach ($this->fieldLangFields as $lf) {
                    if (isset($field[$lf]) && is_array($field[$lf])) {
                        $field[$lf] = json_encode($field[$lf], JSON_UNESCAPED_UNICODE);
                    }
                }
                $field['column_show'] = !empty($field['column_show']);
            }

            $cleaned[] = $field;
        }
        return $cleaned;
    }

    /**
     * 规范化单个 field 的子属性：
     * - 解码多层 HTML 实体（旧数据经多次 htmlspecialchars 过滤）
     * - 将 JSON 字符串还原为数组/对象
     * 兼容新格式 {schema, render, remote} 和旧扁平格式
     */
    protected function normalizeField(array $field): array
    {
        // 需要确保为数组/对象的 JSON 子字段
        $jsonKeys = ['form_validators', 'column_replace_value', 'column_custom', 'form_input_attr'];

        if (isset($field['schema'])) {
            // ─── 新结构格式：JSON 字段在 render 内部 ───
            if (isset($field['render']) && is_array($field['render'])) {
                foreach ($jsonKeys as $key) {
                    if (isset($field['render'][$key])) {
                        $field['render'][$key] = $this->decodeJsonField($field['render'][$key]);
                    }
                }
                // 多语言字段：解码 HTML 实体
                foreach ($this->fieldLangFields as $lf) {
                    if (isset($field['render'][$lf]) && is_string($field['render'][$lf])) {
                        $cleaned = $field['render'][$lf];
                        for ($i = 0; $i < 3; $i++) {
                            $d = htmlspecialchars_decode($cleaned, ENT_QUOTES);
                            if ($d === $cleaned) break;
                            $cleaned = $d;
                        }
                        $field['render'][$lf] = $cleaned;
                    }
                }
            }
        } else {
            // ─── 旧扁平格式 ───
            foreach ($jsonKeys as $key) {
                if (isset($field[$key])) {
                    $field[$key] = $this->decodeJsonField($field[$key]);
                }
            }
            // 多语言字段：解码 HTML 实体
            foreach ($this->fieldLangFields as $lf) {
                if (isset($field[$lf]) && is_string($field[$lf])) {
                    $cleaned = $field[$lf];
                    for ($i = 0; $i < 3; $i++) {
                        $d = htmlspecialchars_decode($cleaned, ENT_QUOTES);
                        if ($d === $cleaned) break;
                        $cleaned = $d;
                    }
                    $field[$lf] = $cleaned;
                }
            }
        }

        return $field;
    }

    // ─── DB Schema Sync (Phase 2) ──────────────────────────────

    /**
     * 构建 SQL dataType 字符串（如 varchar(255), decimal(10,2), int(10)）
     */
    protected function buildDataType(array $schema): string
    {
        $type      = $schema['type'] ?? 'varchar';
        $length    = $schema['length'] ?? 0;
        $precision = $schema['precision'] ?? 0;

        $typesWithPrecision = ['decimal', 'float', 'double'];
        $typesWithLength    = ['varchar', 'char', 'int', 'bigint', 'tinyint', 'smallint', 'mediumint'];

        if (in_array($type, $typesWithPrecision) && $length) {
            return "$type($length,$precision)";
        }
        if (in_array($type, $typesWithLength) && $length) {
            return "$type($length)";
        }
        return $type;
    }

    /**
     * 将动态字段（新 {schema, render, remote} 格式）转换为 CRUD Helper 字段格式
     * CRUD Helper 期望的字段键：name, type, dataType, length, precision, null, default, defaultType, primaryKey, unsigned, autoIncrement, comment
     */
    protected function buildCrudFields(array $dynamicFields): array
    {
        $result = [];
        foreach ($dynamicFields as $field) {
            if (isset($field['schema'])) {
                // 新结构格式
                $schema = $field['schema'];
                // Virtual fields (computed/remoteExpand) are display-only, skip DDL
                if (($schema['type'] ?? '') === 'virtual') {
                    continue;
                }
                $result[] = [
                    'name'          => $schema['prop'] ?? '',
                    'type'          => $schema['type'] ?? 'varchar',
                    'dataType'      => $this->buildDataType($schema),
                    'length'        => $schema['length'] ?? 255,
                    'precision'     => $schema['precision'] ?? 0,
                    'null'          => $schema['nullable'] ?? true,
                    'default'       => $schema['default'] ?? '',
                    'defaultType'   => $schema['defaultType'] ?? 'NULL',
                    'primaryKey'    => $schema['primary'] ?? false,
                    'unsigned'      => $schema['unsigned'] ?? false,
                    'autoIncrement' => $schema['autoIncrement'] ?? false,
                    'comment'       => $schema['comment'] ?? '',
                ];
            } else {
                // 旧扁平格式 — 从 form_input_attr 等推断 schema 属性
                $designType = $field['design_type'] ?? 'string';
                // Virtual fields are display-only, skip DDL
                if (in_array($designType, ['computed', 'remoteExpand'])) {
                    continue;
                }
                $typeMap = [
                    'pk' => 'int', 'spk' => 'bigint', 'weigh' => 'int',
                    'string' => 'varchar', 'password' => 'varchar', 'textarea' => 'varchar',
                    'number' => 'int', 'float' => 'decimal', 'switch' => 'tinyint',
                    'datetime' => 'bigint', 'timestamp' => 'bigint', 'date' => 'date',
                    'time' => 'time', 'year' => 'year',
                    'remoteSelect' => 'int', 'remoteSelects' => 'varchar',
                    'image' => 'varchar', 'images' => 'varchar', 'file' => 'varchar', 'files' => 'varchar',
                    'editor' => 'text', 'city' => 'varchar', 'icon' => 'varchar', 'color' => 'varchar',
                    'select' => 'enum', 'radio' => 'enum', 'checkbox' => 'set',
                    'array' => 'varchar',
                ];
                $type = $typeMap[$designType] ?? 'varchar';
                $result[] = [
                    'name'          => $field['prop'] ?? '',
                    'type'          => $type,
                    'dataType'      => $type,
                    'length'        => ($type === 'varchar') ? 255 : (($type === 'bigint') ? 20 : 10),
                    'precision'     => ($type === 'decimal') ? 2 : 0,
                    'null'          => !($field['prop'] ?? '' === 'id'),
                    'default'       => '',
                    'defaultType'   => 'NULL',
                    'primaryKey'    => in_array($designType, ['pk', 'spk']),
                    'unsigned'      => in_array($designType, ['pk', 'spk', 'weigh', 'datetime', 'timestamp']),
                    'autoIncrement' => $designType === 'pk',
                    'comment'       => '',
                ];
            }
        }
        return $result;
    }

    /**
     * 同步数据库表结构
     * - 新增配置时：如果表不存在，创建表
     * - 编辑配置时：根据 designChange 修改表结构
     * 委托给 Helper::handleTableDesign() 处理
     *
     * @param array $configData 配置数据（含 db_table, db_connection, fields, title）
     * @param array $designChange 字段变更记录
     */
    protected function syncTableSchema(array $configData, array $designChange = []): void
    {
        $tableName = $configData['db_table'] ?? '';
        if (!$tableName) return;

        $connection = $configData['db_connection'] ?? '';
        $comment    = $this->resolveLangValue($configData['title'] ?? '');
        if (!$comment) $comment = $tableName;

        // 构建 CRUD Helper 格式的字段数组（buildCrudFields 已自动跳过 virtual 字段）
        $crudFields = $this->buildCrudFields($configData['fields'] ?? []);

        // 过滤掉 virtual 字段相关的 designChange，防止触发虚假 DDL 操作
        $virtualProps = $this->getVirtualFieldProps($configData['fields'] ?? []);
        if ($virtualProps) {
            $designChange = array_values(array_filter($designChange, function ($change) use ($virtualProps) {
                $newName = $change['newName'] ?? '';
                $oldName = $change['oldName'] ?? '';
                return !in_array($newName, $virtualProps) && !in_array($oldName, $virtualProps);
            }));
        }

        // 委托给 CRUD Helper::handleTableDesign()
        $tableData = [
            'name'               => $tableName,
            'comment'            => $comment,
            'designChange'       => $designChange,
            'databaseConnection' => $connection ?: null,
        ];

        Helper::handleTableDesign($tableData, $crudFields);
    }

    /**
     * 获取所有 virtual 字段的 prop 名称列表
     * Virtual 字段（computed / remoteExpand）仅为前端展示，不参与 DDL
     *
     * @return string[]
     */
    protected function getVirtualFieldProps(array $fields): array
    {
        $props = [];
        foreach ($fields as $field) {
            if (isset($field['schema'])) {
                if (($field['schema']['type'] ?? '') === 'virtual') {
                    $props[] = $field['schema']['prop'] ?? '';
                }
            } else {
                if (in_array($field['design_type'] ?? '', ['computed', 'remoteExpand'])) {
                    $props[] = $field['prop'] ?? '';
                }
            }
        }
        return array_filter($props, fn($p) => $p !== '');
    }

    /**
     * 获取数据库表的字段信息（设计器用）
     * GET /admin/dynamic.Config/getTableFields?table=xxx&connection=xxx
     */
    public function getTableFields(): void
    {
        $table      = $this->request->param('table');
        $connection = $this->request->param('connection', '');

        if (!$table) {
            $this->error('参数 table 不能为空');
        }

        try {
            $conn   = Db::connect($connection ?: null);
            $prefix = $conn->getConfig('prefix') ?: '';
            $fullTable = $prefix . $table;

            $columns = $conn->getFields($fullTable);

            $result = [];
            foreach ($columns as $name => $column) {
                $result[] = [
                    'name'      => $name,
                    'type'      => $column['type'] ?? '',
                    'notnull'   => $column['notnull'] ?? false,
                    'default'   => $column['default'] ?? null,
                    'primary'   => ($column['primary'] ?? false) ? true : false,
                    'autoinc'   => ($column['autoinc'] ?? false) ? true : false,
                    'comment'   => $column['comment'] ?? '',
                ];
            }

            $this->success('', ['fields' => $result]);
        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->error('获取字段失败：' . $e->getMessage());
        }
    }

    /**
     * 获取菜单树（设计器选择父级菜单用）
     * GET /admin/dynamic.Config/getMenuTree
     * 返回 menu_dir 和 menu 类型的规则树
     */
    public function getMenuTree(): void
    {
        $rules = AdminRule::where('type', 'in', ['menu_dir', 'menu'])
            ->where('status', 1)
            ->field(['id', 'pid', 'title', 'name', 'type'])
            ->order('weigh', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        // 构建树
        $tree = $this->buildMenuTree($rules, 0);

        $this->success('', ['tree' => $tree]);
    }

    /**
     * 递归构建菜单树
     */
    protected function buildMenuTree(array $rules, int $pid): array
    {
        $tree = [];
        foreach ($rules as $rule) {
            if ((int)$rule['pid'] === $pid) {
                $children = $this->buildMenuTree($rules, (int)$rule['id']);
                $tree[] = [
                    'id'       => $rule['id'],
                    'label'    => $rule['title'],
                    'name'     => $rule['name'],
                    'type'     => $rule['type'],
                    'children' => $children ?: null,
                ];
            }
        }
        return $tree;
    }
}
