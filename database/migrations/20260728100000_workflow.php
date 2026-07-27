<?php

use think\migration\Migrator;

/**
 * 工作流引擎 — 7张核心表
 *
 * workflow_definition  流程定义（设计时）
 * workflow_node        流程节点（设计时）
 * workflow_instance    流程实例（运行时）
 * workflow_task        审批任务 / 待办池（运行时）
 * workflow_sign        会签记录（运行时）
 * workflow_log         操作日志（审计）
 * workflow_bind        业务模块绑定
 */
class Workflow extends Migrator
{
    public function up(): void
    {
        // ── 1. 流程定义表 ──────────────────────────────────────────
        $def = $this->table('workflow_definition', [
            'engine'       => 'InnoDB',
            'comment'      => '工作流定义表',
            'collation'    => 'utf8mb4_unicode_ci',
        ]);
        $def->addColumn('name', 'string', ['limit' => 100, 'comment' => '流程名称'])
            ->addColumn('code', 'string', ['limit' => 50, 'comment' => '流程编码'])
            ->addColumn('description', 'string', ['limit' => 500, 'default' => '', 'comment' => '流程描述'])
            ->addColumn('graph', 'text', ['null' => true, 'comment' => '设计器JSON（nodes+edges）'])
            ->addColumn('version', 'integer', ['signed' => false, 'default' => 1, 'comment' => '版本号'])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'draft', 'comment' => '状态:draft|published|disabled'])
            ->addColumn('admin_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '创建人ID'])
            ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'biginteger', ['signed' => false, 'null' => true, 'comment' => '更新时间'])
            ->addIndex(['code'], ['unique' => true])
            ->addIndex(['status'])
            ->create();

        // ── 2. 流程节点表 ──────────────────────────────────────────
        $node = $this->table('workflow_node', [
            'engine'       => 'InnoDB',
            'comment'      => '工作流节点定义表',
            'collation'    => 'utf8mb4_unicode_ci',
        ]);
        $node->addColumn('definition_id', 'integer', ['signed' => false, 'comment' => '流程定义ID'])
            ->addColumn('node_key', 'string', ['limit' => 50, 'comment' => '节点key（对应graph node id）'])
            ->addColumn('name', 'string', ['limit' => 100, 'comment' => '节点名称'])
            ->addColumn('node_type', 'string', ['limit' => 20, 'default' => 'task', 'comment' => '类型:start|end|task|condition|fork|join'])
            ->addColumn('approver_type', 'string', ['limit' => 20, 'default' => 'assignee', 'comment' => '审批人规则:assignee|role|dept|initiator|dept_leader'])
            ->addColumn('approver_ids', 'string', ['limit' => 500, 'default' => '', 'comment' => '审批人/角色/部门ID（逗号分隔）'])
            ->addColumn('approver_names', 'string', ['limit' => 500, 'default' => '', 'comment' => '审批人名称（冗余）'])
            ->addColumn('perform_type', 'string', ['limit' => 10, 'default' => 'ANY', 'comment' => '审批方式:ANY=或签|ALL=会签'])
            ->addColumn('next_node_keys', 'string', ['limit' => 500, 'default' => '', 'comment' => '下游节点key（逗号分隔）'])
            ->addColumn('condition_expr', 'text', ['null' => true, 'comment' => '条件表达式JSON（condition节点）'])
            ->addColumn('form_fields', 'text', ['null' => true, 'comment' => '节点可编辑表单字段配置JSON'])
            ->addColumn('allow_back', 'boolean', ['default' => 0, 'comment' => '是否允许退回'])
            ->addColumn('allow_transfer', 'boolean', ['default' => 0, 'comment' => '是否允许转办'])
            ->addColumn('sort', 'integer', ['signed' => false, 'default' => 0, 'comment' => '排序'])
            ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('update_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addIndex(['definition_id'])
            ->addIndex(['definition_id', 'node_key'])
            ->create();

        // ── 3. 流程实例表 ──────────────────────────────────────────
        $inst = $this->table('workflow_instance', [
            'engine'       => 'InnoDB',
            'comment'      => '工作流实例表',
            'collation'    => 'utf8mb4_unicode_ci',
        ]);
        $inst->addColumn('definition_id', 'integer', ['signed' => false, 'comment' => '流程定义ID'])
            ->addColumn('business_type', 'string', ['limit' => 50, 'default' => '', 'comment' => '业务模块编码'])
            ->addColumn('business_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '业务数据主键ID'])
            ->addColumn('title', 'string', ['limit' => 255, 'default' => '', 'comment' => '实例标题'])
            ->addColumn('initiator_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '发起人admin_id'])
            ->addColumn('current_node_key', 'string', ['limit' => 50, 'default' => '', 'comment' => '当前节点key'])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'running', 'comment' => '状态:running|approved|rejected|cancelled|timeout'])
            ->addColumn('form_data', 'text', ['null' => true, 'comment' => '表单数据快照JSON'])
            ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('update_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addIndex(['business_type', 'business_id'])
            ->addIndex(['initiator_id', 'status'])
            ->addIndex(['definition_id'])
            ->create();

        // ── 4. 审批任务表（待办池）────────────────────────────────
        $task = $this->table('workflow_task', [
            'engine'       => 'InnoDB',
            'comment'      => '工作流审批任务表（待办池）',
            'collation'    => 'utf8mb4_unicode_ci',
        ]);
        $task->addColumn('instance_id', 'biginteger', ['signed' => false, 'comment' => '实例ID'])
            ->addColumn('definition_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '流程定义ID'])
            ->addColumn('node_key', 'string', ['limit' => 50, 'default' => '', 'comment' => '节点key'])
            ->addColumn('node_name', 'string', ['limit' => 100, 'default' => '', 'comment' => '节点名称（冗余）'])
            ->addColumn('assignee_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '被分配审批人admin_id'])
            ->addColumn('assignee_name', 'string', ['limit' => 50, 'default' => '', 'comment' => '审批人名称（冗余）'])
            ->addColumn('approver_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '实际审批人ID'])
            ->addColumn('approver_name', 'string', ['limit' => 50, 'default' => '', 'comment' => '实际审批人名称'])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'pending', 'comment' => '状态:pending|approved|rejected|transferred|cancelled'])
            ->addColumn('comment', 'string', ['limit' => 500, 'default' => '', 'comment' => '审批意见'])
            ->addColumn('is_cc', 'boolean', ['default' => 0, 'comment' => '是否抄送任务'])
            ->addColumn('is_read', 'boolean', ['default' => 0, 'comment' => '是否已读'])
            ->addColumn('batch_no', 'integer', ['signed' => false, 'default' => 0, 'comment' => '会签批次号'])
            ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('update_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addIndex(['assignee_id', 'status'])
            ->addIndex(['instance_id', 'status'])
            ->addIndex(['instance_id', 'node_key', 'batch_no'])
            ->create();

        // ── 5. 会签记录表 ──────────────────────────────────────────
        $sign = $this->table('workflow_sign', [
            'engine'       => 'InnoDB',
            'comment'      => '工作流会签记录表',
            'collation'    => 'utf8mb4_unicode_ci',
        ]);
        $sign->addColumn('task_id', 'biginteger', ['signed' => false, 'comment' => '关联task ID'])
            ->addColumn('instance_id', 'biginteger', ['signed' => false, 'default' => 0, 'comment' => '实例ID'])
            ->addColumn('signer_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '会签人admin_id'])
            ->addColumn('signer_name', 'string', ['limit' => 50, 'default' => '', 'comment' => '会签人名称'])
            ->addColumn('result', 'string', ['limit' => 20, 'default' => '', 'comment' => '结果:agree|disagree'])
            ->addColumn('comment', 'string', ['limit' => 500, 'default' => '', 'comment' => '意见'])
            ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addIndex(['task_id'])
            ->addIndex(['instance_id'])
            ->create();

        // ── 6. 操作日志表 ──────────────────────────────────────────
        $log = $this->table('workflow_log', [
            'engine'       => 'InnoDB',
            'comment'      => '工作流操作日志表（追加写入）',
            'collation'    => 'utf8mb4_unicode_ci',
        ]);
        $log->addColumn('instance_id', 'biginteger', ['signed' => false, 'default' => 0, 'comment' => '实例ID'])
            ->addColumn('node_key', 'string', ['limit' => 50, 'default' => '', 'comment' => '节点key'])
            ->addColumn('operator_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '操作人admin_id'])
            ->addColumn('operator_name', 'string', ['limit' => 50, 'default' => '', 'comment' => '操作人名称'])
            ->addColumn('action', 'string', ['limit' => 20, 'default' => '', 'comment' => '动作:start|approve|reject|back|transfer|cc|cancel'])
            ->addColumn('comment', 'string', ['limit' => 500, 'default' => '', 'comment' => '操作意见'])
            ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addIndex(['instance_id'])
            ->create();

        // ── 7. 业务模块绑定表 ──────────────────────────────────────
        $bind = $this->table('workflow_bind', [
            'engine'       => 'InnoDB',
            'comment'      => '工作流业务模块绑定表',
            'collation'    => 'utf8mb4_unicode_ci',
        ]);
        $bind->addColumn('module_code', 'string', ['limit' => 50, 'comment' => '业务编码'])
            ->addColumn('module_name', 'string', ['limit' => 100, 'default' => '', 'comment' => '业务名称'])
            ->addColumn('definition_id', 'integer', ['signed' => false, 'default' => 0, 'comment' => '绑定的流程定义ID'])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'enabled', 'comment' => '状态:enabled|disabled'])
            ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('update_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addIndex(['module_code'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('workflow_bind')->drop()->save();
        $this->table('workflow_log')->drop()->save();
        $this->table('workflow_sign')->drop()->save();
        $this->table('workflow_task')->drop()->save();
        $this->table('workflow_instance')->drop()->save();
        $this->table('workflow_node')->drop()->save();
        $this->table('workflow_definition')->drop()->save();
    }
}
