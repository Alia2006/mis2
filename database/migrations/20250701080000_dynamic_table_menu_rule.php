<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 动态表格模块 — 初始化菜单和权限规则
 *
 * 结构（参照 CRUD 模块，表格配置为独立顶级菜单）：
 *   dynamic/Config            (menu)     配置管理页（顶级菜单）
 *   ├── dynamic/Config/index   (button)   查看
 *   ├── dynamic/Config/add     (button)   添加
 *   ├── dynamic/Config/edit    (button)   编辑
 *   └── dynamic/Config/del     (button)   删除
 *
 * 每个动态表的菜单由 Config 控制器在保存配置时自动创建，
 * 父级菜单位置由 dynamic_table_config.menu_pid 决定。
 * Table 控制器使用 noNeedPermission，访问控制由菜单可见性决定。
 */
class DynamicTableMenuRule extends Migrator
{
    public function up(): void
    {
        // 检查是否已存在
        if (Db::name('admin_rule')->where('name', 'dynamic/Config')->find()) {
            return;
        }

        $now = time();

        // 1. 配置管理菜单（顶级菜单，无父级目录）
        $managerId = Db::name('admin_rule')->insertGetId([
            'pid'        => 0,
            'type'       => 'menu',
            'title'      => '表格配置',
            'name'       => 'dynamic/Config',
            'path'       => 'dynamic/Config',
            'icon'       => 'fa fa-table',
            'menu_type'  => 'tab',
            'url'        => '',
            'component'  => '/src/views/backend/dynamic/manager/index.vue',
            'keepalive'  => 1,
            'extend'     => 'none',
            'remark'     => '低代码动态表格配置',
            'weigh'      => 0,
            'status'     => 1,
            'create_time'=> $now,
            'update_time'=> $now,
        ]);

        // 2. 管理器按钮权限（对应 Config 控制器）
        $configButtons = [
            ['title' => '查看', 'name' => 'dynamic/Config/index'],
            ['title' => '添加', 'name' => 'dynamic/Config/add'],
            ['title' => '编辑', 'name' => 'dynamic/Config/edit'],
            ['title' => '删除', 'name' => 'dynamic/Config/del'],
        ];
        foreach ($configButtons as $btn) {
            Db::name('admin_rule')->insert([
                'pid'        => $managerId,
                'type'       => 'button',
                'title'      => $btn['title'],
                'name'       => $btn['name'],
                'path'       => '',
                'icon'       => '',
                'menu_type'  => null,
                'url'        => '',
                'component'  => '',
                'keepalive'  => 0,
                'extend'     => 'none',
                'remark'     => '',
                'weigh'      => 0,
                'status'     => 1,
                'create_time'=> $now,
                'update_time'=> $now,
            ]);
        }
    }

    public function down(): void
    {
        Db::name('admin_rule')->where('name', 'dynamic/Config')->delete();
        Db::name('admin_rule')->where('name', 'like', 'dynamic/Config/%')->delete();
    }
}
