<?php

namespace app\admin\controller\dynamic;

use app\common\controller\Backend;
use app\admin\model\dynamic\TableConfig;
use app\admin\model\dynamic\DynamicModel;
use Throwable;

/**
 * 动态表格数据控制器
 *
 * 根据 table 参数动态绑定模型，提供通用 CRUD
 * GET  /admin/dynamic.Table/index?table=employee  → 列表
 * POST /admin/dynamic.Table/add?table=employee    → 新增
 * GET  /admin/dynamic.Table/edit?table=employee&id=1 → 编辑详情
 * POST /admin/dynamic.Table/edit?table=employee&id=1 → 保存编辑
 * DEL  /admin/dynamic.Table/del?table=employee&ids=1,2 → 删除
 */
class Table extends Backend
{
    protected object $model;
    /**
     * 动态表共用同一控制器，权限由菜单可见性控制（能看到菜单即可操作）
     */
    protected array $noNeedPermission = ['index', 'add', 'edit', 'del'];

    public function initialize(): void
    {
        parent::initialize();

        $tableName = $this->request->param('table');
        if (!$tableName) {
            $this->error('参数 table 不能为空');
        }

        // 读取动态表格配置
        $config = TableConfig::where('name', $tableName)->where('status', 'enabled')->find();
        if (!$config) {
            $this->error('动态表格配置不存在：' . $tableName);
        }

        // 动态创建模型实例
        $this->model = new DynamicModel();
        $this->model->setDynamicTable(
            $config->db_table,
            $config->pk ?: 'id',
            $config->db_connection ?: ''
        );

        // 设置默认排序
        if ($config->default_sort_field) {
            $this->defaultSortField = $config->default_sort_field . ',' . ($config->default_sort_order ?: 'desc');
        }

        // 设置快速搜索字段
        if ($config->quick_search_fields) {
            $this->quickSearchField = $config->quick_search_fields;
        }

        // 关闭模型验证（动态表无对应 validate 类）
        $this->modelValidate = false;
    }
}
