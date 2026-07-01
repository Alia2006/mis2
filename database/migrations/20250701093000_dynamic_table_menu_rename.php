<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 修正菜单规则 name：dynamic/manager → dynamic/Config
 *
 * 前端 auth() 根据「当前路由路径 + 按钮名」构造权限节点，
 * 菜单 name 必须与控制器路径一致才能匹配。
 * Config 控制器的 controllerPath 为 dynamic/Config，
 * 因此菜单 name 也必须是 dynamic/Config。
 */
class DynamicTableMenuRename extends Migrator
{
    public function up(): void
    {
        Db::name('admin_rule')
            ->where('name', 'dynamic/manager')
            ->update([
                'name' => 'dynamic/Config',
                'path' => 'dynamic/Config',
            ]);
    }

    public function down(): void
    {
        Db::name('admin_rule')
            ->where('name', 'dynamic/Config')
            ->where('component', '/src/views/backend/dynamic/manager/index.vue')
            ->update([
                'name' => 'dynamic/manager',
                'path' => 'dynamic/manager',
            ]);
    }
}
