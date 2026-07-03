<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 动态表格：新增详情表配置字段
 *
 * detail_table_id    — 关联的详情动态表配置 ID（dynamic_table_config.id）
 * detail_foreign_key — 详情表中用于过滤的外键字段名
 */
class DynamicTableDetail extends Migrator
{
    public function up(): void
    {
        // 检查字段是否已存在
        $columns = Db::query("SHOW COLUMNS FROM `dynamic_table_config` LIKE 'detail_table_id'");
        if (empty($columns)) {
            Db::execute("ALTER TABLE `dynamic_table_config` ADD COLUMN `detail_table_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '详情关联的动态表配置ID' AFTER `row_buttons`");
        }

        $columns = Db::query("SHOW COLUMNS FROM `dynamic_table_config` LIKE 'detail_foreign_key'");
        if (empty($columns)) {
            Db::execute("ALTER TABLE `dynamic_table_config` ADD COLUMN `detail_foreign_key` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '详情表过滤的外键字段名' AFTER `detail_table_id`");
        }
    }

    public function down(): void
    {
        $columns = Db::query("SHOW COLUMNS FROM `dynamic_table_config` LIKE 'detail_table_id'");
        if (!empty($columns)) {
            Db::execute("ALTER TABLE `dynamic_table_config` DROP COLUMN `detail_table_id`");
        }

        $columns = Db::query("SHOW COLUMNS FROM `dynamic_table_config` LIKE 'detail_foreign_key'");
        if (!empty($columns)) {
            Db::execute("ALTER TABLE `dynamic_table_config` DROP COLUMN `detail_foreign_key`");
        }
    }
}
