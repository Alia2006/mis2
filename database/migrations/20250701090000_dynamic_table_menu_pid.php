<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 1. dynamic_table_config 增加 menu_pid 字段（指定表格菜单挂在哪个父级菜单下）
 * 2. 修正 admin_rule 中 dynamic 目录：仅保留管理页和权限按钮，
 *    动态表的菜单不再挂在此目录下，而由 menu_pid 决定位置。
 */
class DynamicTableMenuPid extends Migrator
{
    public function up(): void
    {
        // 1. 增加 menu_pid 字段
        $table = $this->table('dynamic_table_config');
        if (!$table->hasColumn('menu_pid')) {
            $table->addColumn('menu_pid', 'integer', [
                'default'  => 0,
                'signed'   => false,
                'comment'  => '菜单父级 admin_rule.id，0=顶级菜单',
                'after'    => 'status',
            ])->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('dynamic_table_config');
        if ($table->hasColumn('menu_pid')) {
            $table->removeColumn('menu_pid')->update();
        }
    }
}
