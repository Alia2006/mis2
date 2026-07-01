<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 移除 dynamic 父级目录，表格配置改为独立顶级菜单（参照 CRUD 模块）
 *
 * 之前：dynamic (menu_dir) → dynamic/Config (menu)
 * 现在：dynamic/Config (menu, pid=0) ← 顶级菜单
 */
class DynamicTableMenuFlatten extends Migrator
{
    public function up(): void
    {
        // 1. 将 dynamic/Config 提升为顶级菜单
        Db::name('admin_rule')->where('name', 'dynamic/Config')->update(['pid' => 0]);

        // 2. 删除 dynamic 目录（Config/* 按钮的 pid 指向 manager，不受影响）
        $dirId = Db::name('admin_rule')->where('name', 'dynamic')->value('id');
        if ($dirId) {
            // 安全检查：确保没有子节点仍挂在 dynamic 目录下
            $orphanCount = Db::name('admin_rule')->where('pid', $dirId)->count();
            if ($orphanCount == 0) {
                Db::name('admin_rule')->where('id', $dirId)->delete();
            }
        }
    }

    public function down(): void
    {
        // 无需恢复
    }
}
