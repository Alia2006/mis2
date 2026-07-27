<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 工作流模块 — 初始化菜单和权限规则
 *
 * 结构：
 *   workflow                     (menu_dir)  工作流
 *   ├── workflow/definition       (menu)     流程定义
 *   │   ├── workflow/definition/index    (button)
 *   │   ├── workflow/definition/add      (button)
 *   │   ├── workflow/definition/edit     (button)
 *   │   ├── workflow/definition/del      (button)
 *   │   └── workflow/definition/designer (menu, 侧栏隐藏)
 *   ├── workflow/task             (menu)     我的待办
 *   │   ├── workflow/task/index           (button)
 *   │   ├── workflow/task/approve         (button)
 *   │   ├── workflow/task/reject          (button)
 *   │   └── workflow/task/transfer        (button)
 *   ├── workflow/instance         (menu)     流程实例
 *   │   └── workflow/instance/index       (button)
 *   └── workflow/bind             (menu)     模块绑定
 *       ├── workflow/bind/index           (button)
 *       ├── workflow/bind/add             (button)
 *       ├── workflow/bind/edit            (button)
 *       └── workflow/bind/del             (button)
 */
class WorkflowMenuRule extends Migrator
{
    public function up(): void
    {
        if (Db::name('admin_rule')->where('name', 'workflow')->find()) {
            return;
        }

        $now = time();

        // ── 顶级目录 ──────────────────────────────────────────
        $dirId = Db::name('admin_rule')->insertGetId([
            'pid'         => 0,
            'type'        => 'menu_dir',
            'title'       => '工作流',
            'name'        => 'workflow',
            'path'        => 'workflow',
            'icon'        => 'fa fa-sitemap',
            'menu_type'   => 'tab',
            'url'         => '',
            'component'   => '',
            'keepalive'   => 1,
            'extend'      => 'none',
            'remark'      => '审批工作流引擎',
            'weigh'       => 0,
            'status'      => 1,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        // ── 流程定义 ──────────────────────────────────────────
        $defId = $this->addMenu($dirId, '流程定义', 'workflow/definition', '/src/views/backend/workflow/definition/index.vue', 'fa fa-project-diagram', $now);
        $this->addButtons($defId, 'workflow/definition', ['index', 'add', 'edit', 'del'], $now);

        // 设计器（隐藏菜单，通过 extend=add_rules_only 不在侧栏显示）
        $this->addMenu($defId, '流程设计器', 'workflow/definition/designer', '/src/views/backend/workflow/definition/designer.vue', '', $now, 'add_rules_only');

        // ── 我的待办 ──────────────────────────────────────────
        $taskId = $this->addMenu($dirId, '我的待办', 'workflow/task', '/src/views/backend/workflow/task/todo.vue', 'fa fa-inbox', $now);
        $this->addButtons($taskId, 'workflow/task', ['index', 'approve', 'reject', 'transfer'], $now);

        // ── 流程实例 ──────────────────────────────────────────
        $instId = $this->addMenu($dirId, '流程实例', 'workflow/instance', '/src/views/backend/workflow/instance/index.vue', 'fa fa-list-alt', $now);
        $this->addButtons($instId, 'workflow/instance', ['index'], $now);

        // 实例详情（隐藏）
        $this->addMenu($instId, '实例详情', 'workflow/instance/detail', '/src/views/backend/workflow/instance/detail.vue', '', $now, 'add_rules_only');

        // ── 模块绑定 ──────────────────────────────────────────
        $bindId = $this->addMenu($dirId, '模块绑定', 'workflow/bind', '/src/views/backend/workflow/bind/index.vue', 'fa fa-link', $now);
        $this->addButtons($bindId, 'workflow/bind', ['index', 'add', 'edit', 'del'], $now);
    }

    public function down(): void
    {
        Db::name('admin_rule')->where('name', 'workflow')->delete();
        Db::name('admin_rule')->where('name', 'like', 'workflow/%')->delete();
    }

    /**
     * 添加菜单项
     */
    private function addMenu(int $pid, string $title, string $name, string $component, string $icon, int $now, string $extend = 'none'): int
    {
        return Db::name('admin_rule')->insertGetId([
            'pid'         => $pid,
            'type'        => 'menu',
            'title'       => $title,
            'name'        => $name,
            'path'        => $name,
            'icon'        => $icon,
            'menu_type'   => 'tab',
            'url'         => '',
            'component'   => $component,
            'keepalive'   => 1,
            'extend'      => $extend,
            'remark'      => '',
            'weigh'       => 0,
            'status'      => 1,
            'create_time' => $now,
            'update_time' => $now,
        ]);
    }

    /**
     * 批量添加按钮权限
     */
    private function addButtons(int $pid, string $prefix, array $actions, int $now): void
    {
        $titles = [
            'index'    => '查看',
            'add'      => '添加',
            'edit'     => '编辑',
            'del'      => '删除',
            'approve'  => '审批通过',
            'reject'   => '驳回',
            'transfer' => '转办',
        ];

        foreach ($actions as $action) {
            Db::name('admin_rule')->insert([
                'pid'         => $pid,
                'type'        => 'button',
                'title'       => $titles[$action] ?? $action,
                'name'        => $prefix . '/' . $action,
                'path'        => '',
                'icon'        => '',
                'menu_type'   => null,
                'url'         => '',
                'component'   => '',
                'keepalive'   => 0,
                'extend'      => 'none',
                'remark'      => '',
                'weigh'       => 0,
                'status'      => 1,
                'create_time' => $now,
                'update_time' => $now,
            ]);
        }
    }
}
