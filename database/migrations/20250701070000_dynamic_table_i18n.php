<?php

use think\migration\Migrator;

/**
 * 多语言字段改造
 *
 * 将用户可见的文本字段从 varchar 改为 text，以存储 JSON 多语言格式：
 * {"zh-cn":"员工姓名","en":"Employee Name"}
 *
 * 受影响字段：
 *   dynamic_table_config.title        → text
 *   dynamic_table_config.remark       → text
 *   dynamic_table_field.label          → text
 *   dynamic_table_field.column_operator_placeholder → text
 */
class DynamicTableI18n extends Migrator
{
    public function up(): void
    {
        $this->table('dynamic_table_config')
            ->changeColumn('title', 'text')
            ->changeColumn('remark', 'text')
            ->update();

        $this->table('dynamic_table_field')
            ->changeColumn('label', 'text')
            ->changeColumn('column_operator_placeholder', 'text')
            ->update();
    }

    public function down(): void
    {
        $this->table('dynamic_table_config')
            ->changeColumn('title', 'string', ['limit' => 100])
            ->changeColumn('remark', 'string', ['limit' => 255, 'default' => ''])
            ->update();

        $this->table('dynamic_table_field')
            ->changeColumn('label', 'string', ['limit' => 100])
            ->changeColumn('column_operator_placeholder', 'string', ['limit' => 100, 'null' => true])
            ->update();
    }
}
