<?php

use think\migration\Migrator;

/**
 * 工作流通知表（站内信）
 *
 * 由 WorkflowEngine 触发的事件监听器写入，记录待办/审批结果/抄送等通知，
 * 同时监听器会尝试通过邮件发送（依赖系统邮件配置）。
 */
class WorkflowNotification extends Migrator
{
    public function up(): void
    {
        $table = $this->table('workflow_notification', [
            'engine'    => 'InnoDB',
            'comment'   => '工作流通知表（站内信）',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
        $table->addColumn('admin_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '接收人 admin_id'])
            ->addColumn('type', 'string', ['limit' => 30, 'default' => '', 'comment' => '类型:task_assigned|instance_completed|instance_rejected|instance_cancelled|task_cc'])
            ->addColumn('title', 'string', ['limit' => 200, 'default' => '', 'comment' => '通知标题'])
            ->addColumn('content', 'text', ['null' => true, 'comment' => '通知内容'])
            ->addColumn('business_type', 'string', ['limit' => 60, 'default' => '', 'comment' => '业务模块编码'])
            ->addColumn('instance_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '流程实例ID'])
            ->addColumn('task_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '任务ID'])
            ->addColumn('is_read', 'boolean', ['default' => 0, 'comment' => '是否已读'])
            ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true, 'comment' => '创建时间'])
            ->addIndex(['admin_id'])
            ->addIndex(['is_read'])
            ->addIndex(['instance_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('workflow_notification')->drop()->save();
    }
}
