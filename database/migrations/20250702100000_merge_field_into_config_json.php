<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 将 dynamic_table_field 表合并为 dynamic_table_config.fields JSON 字段
 */
class MergeFieldIntoConfigJson extends Migrator
{
    public function up(): void
    {
        // 1. 添加 fields JSON 列
        $this->table('dynamic_table_config')
            ->addColumn('fields', 'text', ['null' => true, 'comment' => '字段配置JSON'])
            ->update();

        // 2. 迁移数据：将 dynamic_table_field 的行组装为 JSON 写入 dynamic_table_config.fields
        $configs = Db::table('dynamic_table_config')->column('id');
        foreach ($configs as $configId) {
            $fields = Db::table('dynamic_table_field')
                ->where('table_id', $configId)
                ->order('sort', 'asc')
                ->field([
                    'prop', 'label', 'column_show', 'column_width', 'column_align',
                    'column_render', 'column_operator', 'column_sortable',
                    'column_com_search_render', 'column_replace_value', 'column_custom',
                    'column_time_format', 'column_operator_placeholder',
                    'form_type', 'form_validators', 'form_input_attr'
                ])
                ->select()
                ->toArray();

            if ($fields) {
                Db::table('dynamic_table_config')
                    ->where('id', $configId)
                    ->update(['fields' => json_encode($fields, JSON_UNESCAPED_UNICODE)]);
            }
        }

        // 3. 删除旧表
        $this->table('dynamic_table_field')            ->drop();
    }

    public function down(): void
    {
        // 恢复 dynamic_table_field 表
        $table = $this->table('dynamic_table_field');
        $table->addColumn('table_id', 'integer', ['comment' => '配置ID'])
            ->addColumn('prop', 'string', ['limit' => 50, 'comment' => '字段名'])
            ->addColumn('label', 'string', ['limit' => 255, 'null' => true, 'comment' => '显示名(多语言JSON)'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addColumn('column_show', 'boolean', ['default' => 1, 'comment' => '是否显示'])
            ->addColumn('column_width', 'integer', ['null' => true, 'comment' => '列宽'])
            ->addColumn('column_align', 'string', ['limit' => 10, 'default' => 'center'])
            ->addColumn('column_render', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('column_operator', 'string', ['limit' => 20, 'default' => 'eq'])
            ->addColumn('column_sortable', 'string', ['limit' => 10, 'default' => 'false'])
            ->addColumn('column_com_search_render', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('column_replace_value', 'text', ['null' => true])
            ->addColumn('column_custom', 'text', ['null' => true])
            ->addColumn('column_time_format', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('column_operator_placeholder', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('form_type', 'string', ['limit' => 30, 'default' => 'string'])
            ->addColumn('form_validators', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('form_input_attr', 'text', ['null' => true])
            ->addIndex(['table_id'])
            ->create();

        // 从 JSON 恢复数据
        $configs = Db::table('dynamic_table_config')->field(['id', 'fields'])->select();
        foreach ($configs as $config) {
            if (empty($config['fields'])) continue;
            $fields = json_decode($config['fields'], true);
            if (!is_array($fields)) continue;
            foreach ($fields as $sort => $field) {
                $field['table_id'] = $config['id'];
                $field['sort'] = $sort;
                Db::table('dynamic_table_field')->insert($field);
            }
        }

        // 删除 fields 列
        $this->table('dynamic_table_config')->removeColumn('fields')->update();
    }
}
