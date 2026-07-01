<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 清理旧架构遗留的 admin_rule 数据：
 *
 * 1. 删除 dynamic/Table/* 按钮权限（新架构下 Table 控制器使用 noNeedPermission，
 *    访问控制由菜单可见性决定，不再需要共享按钮权限）
 *
 * 2. dynamic 目录仅保留：表格配置菜单 + Config/* 权限按钮
 */
class DynamicTableMenuCleanup extends Migrator
{
    public function up(): void
    {
        // 删除 dynamic/Table/* 共享按钮权限
        Db::name('admin_rule')->where('name', 'like', 'dynamic/Table/%')->delete();
    }

    public function down(): void
    {
        // 无需恢复（旧数据已废弃）
    }
}
