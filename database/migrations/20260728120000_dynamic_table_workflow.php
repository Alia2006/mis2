<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 动态表格：新增工作流绑定字段
 *
 * workflow_module_code — 绑定的工作流模块编码（对应 workflow_bind.module_code）
 *                       为空表示该动态表不启用工作流审批
 */
class DynamicTableWorkflow extends Migrator
{
    public function up(): void
    {
        $columns = Db::query("SHOW COLUMNS FROM `dynamic_table_config` LIKE 'workflow_module_code'");
        if (empty($columns)) {
            Db::execute("ALTER TABLE `dynamic_table_config` ADD COLUMN `workflow_module_code` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '工作流模块编码(绑定workflow_bind)' AFTER `detail_foreign_key`");
        }
    }

    public function down(): void
    {
        $columns = Db::query("SHOW COLUMNS FROM `dynamic_table_config` LIKE 'workflow_module_code'");
        if (!empty($columns)) {
            Db::execute("ALTER TABLE `dynamic_table_config` DROP COLUMN `workflow_module_code`");
        }
    }
}
