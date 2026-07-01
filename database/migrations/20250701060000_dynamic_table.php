<?php

use think\migration\Migrator;

class DynamicTable extends Migrator
{
    public function up(): void
    {
        // 动态表格配置表
        $table = $this->table('dynamic_table_config', [
            'engine'       => 'InnoDB',
            'comment'      => '动态表格配置',
            'collation'    => 'utf8mb4_general_ci',
        ]);
        $table->addColumn('name', 'string', ['limit' => 50, 'comment' => '表标识，如 employee'])
            ->addColumn('title', 'string', ['limit' => 100, 'comment' => '显示名'])
            ->addColumn('db_table', 'string', ['limit' => 100, 'comment' => '真实数据库表名'])
            ->addColumn('db_connection', 'string', ['limit' => 50, 'default' => '', 'comment' => '数据库连接，空为默认'])
            ->addColumn('pk', 'string', ['limit' => 30, 'default' => 'id', 'comment' => '主键字段'])
            ->addColumn('quick_search_fields', 'string', ['limit' => 255, 'default' => '', 'comment' => '快速搜索字段，逗号分隔'])
            ->addColumn('default_sort_field', 'string', ['limit' => 30, 'default' => '', 'comment' => '默认排序字段'])
            ->addColumn('default_sort_order', 'string', ['limit' => 4, 'default' => 'desc', 'comment' => '默认排序方向'])
            ->addColumn('header_buttons', 'string', ['limit' => 255, 'default' => '["refresh","add","edit","delete","comSearch","quickSearch","columnDisplay"]', 'comment' => '表头按钮 JSON'])
            ->addColumn('row_buttons', 'string', ['limit' => 100, 'default' => '["edit","delete"]', 'comment' => '行按钮 JSON'])
            ->addColumn('default_items', 'text', ['null' => true, 'comment' => '新增默认值 JSON'])
            ->addColumn('remark', 'string', ['limit' => 255, 'default' => '', 'comment' => '备注'])
            ->addColumn('status', 'string', ['limit' => 10, 'default' => 'enabled', 'comment' => '状态:enabled|disabled'])
            ->addColumn('create_time', 'datetime', ['null' => true])
            ->addColumn('update_time', 'datetime', ['null' => true])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // 动态表格字段配置表
        $field = $this->table('dynamic_table_field', [
            'engine'       => 'InnoDB',
            'comment'      => '动态表格字段配置',
            'collation'    => 'utf8mb4_general_ci',
        ]);
        $field->addColumn('table_id', 'integer', ['comment' => '关联 dynamic_table_config.id'])
            ->addColumn('prop', 'string', ['limit' => 50, 'comment' => '字段名'])
            ->addColumn('label', 'string', ['limit' => 100, 'comment' => '显示名'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])

            // 列属性
            ->addColumn('column_show', 'boolean', ['default' => 1, 'comment' => '是否显示列'])
            ->addColumn('column_width', 'integer', ['null' => true, 'comment' => '列宽'])
            ->addColumn('column_align', 'string', ['limit' => 10, 'default' => 'center', 'comment' => '对齐'])
            ->addColumn('column_render', 'string', ['limit' => 20, 'null' => true, 'comment' => '渲染器: tag|switch|datetime|image|...'])
            ->addColumn('column_operator', 'string', ['limit' => 20, 'default' => 'eq', 'comment' => '搜索操作符'])
            ->addColumn('column_sortable', 'string', ['limit' => 10, 'default' => 'false', 'comment' => '排序:false|custom'])
            ->addColumn('column_com_search_render', 'string', ['limit' => 20, 'null' => true, 'comment' => '公共搜索渲染'])
            ->addColumn('column_replace_value', 'text', ['null' => true, 'comment' => '字典 JSON'])
            ->addColumn('column_custom', 'text', ['null' => true, 'comment' => '自定义渲染属性 JSON'])
            ->addColumn('column_time_format', 'string', ['limit' => 30, 'null' => true, 'comment' => '时间格式'])
            ->addColumn('column_operator_placeholder', 'string', ['limit' => 100, 'null' => true, 'comment' => '搜索框 placeholder'])

            // 表单属性
            ->addColumn('form_type', 'string', ['limit' => 20, 'default' => 'string', 'comment' => '表单输入类型'])
            ->addColumn('form_validators', 'string', ['limit' => 255, 'null' => true, 'comment' => '校验器 JSON 数组'])
            ->addColumn('form_input_attr', 'text', ['null' => true, 'comment' => '输入属性 JSON'])

            ->addIndex(['table_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('dynamic_table_field')->drop()->save();
        $this->table('dynamic_table_config')->drop()->save();
    }
}
