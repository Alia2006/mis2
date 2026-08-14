<?php

namespace app\admin\library;

use Throwable;
use think\facade\Db;
use think\facade\Event;
use app\admin\model\workflow\Definition;
use app\admin\model\workflow\Node;
use app\admin\model\workflow\Instance;
use app\admin\model\workflow\Task;
use app\admin\model\workflow\Sign;
use app\admin\model\workflow\Log;
use app\admin\model\workflow\Bind;

/**
 * 工作流引擎核心
 *
 * 负责流程发起、流转、驳回、退回、转办、撤回等运行时逻辑。
 * 引擎通过 ThinkPHP Event 触发副作用（通知、业务回调），自身不处理通知发送。
 */
class WorkflowEngine
{
    /**
     * 单例
     */
    private static ?WorkflowEngine $instance = null;

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 发起流程
     *
     * @param string $moduleCode  业务模块编码
     * @param int    $businessId  业务数据主键
     * @param array  $formData    表单数据
     * @param int    $initiatorId 发起人admin_id
     * @param string $title       实例标题（可选）
     * @return int 实例ID
     * @throws Throwable
     */
    public function start(string $moduleCode, int $businessId, array $formData, int $initiatorId, string $title = ''): int
    {
        // 查找绑定的已发布流程
        $bind = Bind::where('module_code', $moduleCode)->where('status', 'enabled')->find();
        if (!$bind) {
            throw new \RuntimeException('未找到业务模块 [' . $moduleCode . '] 绑定的流程');
        }

        $definition = Definition::where('id', $bind->definition_id)
            ->where('status', 'published')
            ->find();
        if (!$definition) {
            throw new \RuntimeException('绑定的流程未发布或不存在');
        }

        // 查找 start 节点
        $startNode = Node::where('definition_id', $definition->id)
            ->where('node_type', 'start')
            ->find();
        if (!$startNode) {
            throw new \RuntimeException('流程缺少开始节点');
        }

        // 查找第一个 task 节点
        $firstTaskNode = $this->getNextTaskNode($definition->id, $startNode->node_key);
        if (!$firstTaskNode) {
            throw new \RuntimeException('流程缺少审批节点');
        }

        // 发起人名称
        $initiatorName = Db::name('admin')->where('id', $initiatorId)->value('nickname', '');

        // 默认标题
        if (!$title) {
            $title = $definition->name . '-' . $initiatorName . '-' . date('Y-m-d H:i');
        }

        Db::startTrans();
        try {
            // 创建实例
            $instance = Instance::create([
                'definition_id'    => $definition->id,
                'business_type'    => $moduleCode,
                'business_id'      => $businessId,
                'title'            => $title,
                'initiator_id'     => $initiatorId,
                'current_node_key' => $firstTaskNode->node_key,
                'status'           => 'running',
                'form_data'        => $formData,
            ]);

            // 创建第一个任务
            $this->createTasks($instance->id, $definition->id, $firstTaskNode, $initiatorId, $formData);

            // 写日志
            $this->writeLog($instance->id, $startNode->node_key, $initiatorId, $initiatorName, 'start', '发起流程');

            Db::commit();

            // 触发事件
            Event::trigger('workflow.instance.started', $instance);

            return (int)$instance->id;
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 审批通过
     *
     * @param int    $taskId     任务ID
     * @param int    $approverId 审批人ID
     * @param string $comment    审批意见
     * @throws Throwable
     */
    public function approve(int $taskId, int $approverId, string $comment = ''): void
    {
        $task = Task::where('id', $taskId)->where('assignee_id', $approverId)->where('status', 'pending')->find();
        if (!$task) {
            throw new \RuntimeException('任务不存在或无权操作');
        }

        $instance = Instance::find($task->instance_id);
        if (!$instance || $instance->status !== 'running') {
            throw new \RuntimeException('流程已结束');
        }

        $approverName = Db::name('admin')->where('id', $approverId)->value('nickname', '');

        Db::startTrans();
        try {
            // 标记当前任务完成
            $task->save([
                'status'        => 'approved',
                'approver_id'   => $approverId,
                'approver_name' => $approverName,
                'comment'       => $comment,
            ]);

            // 写日志
            $this->writeLog($instance->id, $task->node_key, $approverId, $approverName, 'approve', $comment);

            // 获取当前节点
            $node = Node::where('definition_id', $instance->definition_id)
                ->where('node_key', $task->node_key)
                ->find();

            if (!$node) {
                throw new \RuntimeException('节点不存在');
            }

            // 会签检查：若 perform_type=ALL，需同批次所有任务完成
            if ($node->perform_type === 'ALL' && $task->batch_no > 0) {
                $pendingCount = Task::where('instance_id', $instance->id)
                    ->where('node_key', $task->node_key)
                    ->where('batch_no', $task->batch_no)
                    ->where('status', 'pending')
                    ->count();
                if ($pendingCount > 0) {
                    // 还有未完成会签任务，等待
                    Db::commit();
                    return;
                }
            }

            // 或签检查：若 perform_type=ANY，取消同批次其他待办
            if ($node->perform_type === 'ANY' && $task->batch_no > 0) {
                Task::where('instance_id', $instance->id)
                    ->where('node_key', $task->node_key)
                    ->where('batch_no', $task->batch_no)
                    ->where('status', 'pending')
                    ->update([
                        'status'        => 'cancelled',
                        'approver_id'   => $approverId,
                        'approver_name' => $approverName,
                        'comment'       => '已被其他审批人处理',
                    ]);
            }

            // 流转到下一节点
            $this->advance($instance, $node, $approverId, $approverName);

            Db::commit();

            // 触发事件
            Event::trigger('workflow.task.approved', $task);
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 驳回（直接结束流程）
     */
    public function reject(int $taskId, int $approverId, string $comment = ''): void
    {
        $task = Task::where('id', $taskId)->where('assignee_id', $approverId)->where('status', 'pending')->find();
        if (!$task) {
            throw new \RuntimeException('任务不存在或无权操作');
        }

        $instance = Instance::find($task->instance_id);
        if (!$instance || $instance->status !== 'running') {
            throw new \RuntimeException('流程已结束');
        }

        $approverName = Db::name('admin')->where('id', $approverId)->value('nickname', '');

        Db::startTrans();
        try {
            // 标记当前任务
            $task->save([
                'status'        => 'rejected',
                'approver_id'   => $approverId,
                'approver_name' => $approverName,
                'comment'       => $comment,
            ]);

            // 取消同批次其他待办
            Task::where('instance_id', $instance->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'comment' => '流程已被驳回']);

            // 更新实例状态
            $instance->save(['status' => 'rejected']);

            // 写日志
            $this->writeLog($instance->id, $task->node_key, $approverId, $approverName, 'reject', $comment);

            Db::commit();

            Event::trigger('workflow.instance.rejected', $instance);
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 退回上一步
     */
    public function back(int $taskId, int $approverId, string $comment = ''): void
    {
        $task = Task::where('id', $taskId)->where('assignee_id', $approverId)->where('status', 'pending')->find();
        if (!$task) {
            throw new \RuntimeException('任务不存在或无权操作');
        }

        $instance = Instance::find($task->instance_id);
        if (!$instance || $instance->status !== 'running') {
            throw new \RuntimeException('流程已结束');
        }

        // 检查当前节点是否允许退回
        $node = Node::where('definition_id', $instance->definition_id)
            ->where('node_key', $task->node_key)
            ->find();
        if (!$node || !$node->allow_back) {
            throw new \RuntimeException('当前节点不允许退回');
        }

        $approverName = Db::name('admin')->where('id', $approverId)->value('nickname', '');

        Db::startTrans();
        try {
            // 标记当前任务
            $task->save([
                'status'        => 'rejected',
                'approver_id'   => $approverId,
                'approver_name' => $approverName,
                'comment'       => '退回: ' . $comment,
            ]);

            // 写日志
            $this->writeLog($instance->id, $task->node_key, $approverId, $approverName, 'back', $comment);

            // 找到上一个已完成的节点
            $lastCompleted = Task::where('instance_id', $instance->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->order('update_time', 'desc')
                ->find();

            if ($lastCompleted) {
                $prevNode = Node::where('definition_id', $instance->definition_id)
                    ->where('node_key', $lastCompleted->node_key)
                    ->find();
                if ($prevNode) {
                    // 在上一节点重新创建任务
                    $this->createTasks($instance->id, $instance->definition_id, $prevNode, $instance->initiator_id, $instance->form_data ?? []);
                    $instance->save(['current_node_key' => $prevNode->node_key]);
                }
            } else {
                // 没有上一节点，退回给发起人重新提交
                $instance->save(['status' => 'running', 'current_node_key' => 'start']);
            }

            Db::commit();
            Event::trigger('workflow.task.backed', $task);
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 转办
     */
    public function transfer(int $taskId, int $fromAdminId, int $toAdminId, string $comment = ''): void
    {
        $task = Task::where('id', $taskId)->where('assignee_id', $fromAdminId)->where('status', 'pending')->find();
        if (!$task) {
            throw new \RuntimeException('任务不存在或无权操作');
        }

        $toName = Db::name('admin')->where('id', $toAdminId)->value('nickname', '');
        if (!$toName) {
            throw new \RuntimeException('目标用户不存在');
        }

        $fromName = Db::name('admin')->where('id', $fromAdminId)->value('nickname', '');

        Db::startTrans();
        try {
            // 原任务标记转办
            $task->save([
                'status'        => 'transferred',
                'approver_id'   => $fromAdminId,
                'approver_name' => $fromName,
                'comment'       => '转办给 ' . $toName . ': ' . $comment,
            ]);

            // 新建任务给目标人
            Task::create([
                'instance_id'   => $task->instance_id,
                'definition_id' => $task->definition_id,
                'node_key'      => $task->node_key,
                'node_name'     => $task->node_name,
                'assignee_id'   => $toAdminId,
                'assignee_name' => $toName,
                'status'        => 'pending',
                'batch_no'      => $task->batch_no,
            ]);

            // 写日志
            $this->writeLog($task->instance_id, $task->node_key, $fromAdminId, $fromName, 'transfer', '转办给 ' . $toName);

            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 撤回（发起人撤回进行中的流程）
     */
    public function cancel(int $instanceId, int $initiatorId, string $comment = ''): void
    {
        $instance = Instance::find($instanceId);
        if (!$instance) {
            throw new \RuntimeException('流程实例不存在');
        }
        if ($instance->initiator_id != $initiatorId) {
            throw new \RuntimeException('只有发起人可以撤回');
        }
        if ($instance->status !== 'running') {
            throw new \RuntimeException('流程已结束，无法撤回');
        }

        $initiatorName = Db::name('admin')->where('id', $initiatorId)->value('nickname', '');

        Db::startTrans();
        try {
            // 取消所有待办
            Task::where('instance_id', $instanceId)->where('status', 'pending')->update([
                'status'  => 'cancelled',
                'comment' => '发起人撤回流程',
            ]);

            $instance->save(['status' => 'cancelled']);

            $this->writeLog($instanceId, $instance->current_node_key, $initiatorId, $initiatorName, 'cancel', $comment);

            Db::commit();
            Event::trigger('workflow.instance.cancelled', $instance);
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  核心流转逻辑
    // ─────────────────────────────────────────────────────────────

    /**
     * 流转：从当前节点找到下一节点并创建任务
     */
    private function advance(Instance $instance, Node $currentNode, int $approverId, string $approverName): void
    {
        $nextNode = $this->resolveNextNode($instance->definition_id, $currentNode, $instance->form_data ?? []);

        if (!$nextNode) {
            throw new \RuntimeException('无法确定下一节点');
        }

        // 到达 end 节点 → 流程结束
        if ($nextNode->node_type === 'end') {
            $instance->save(['status' => 'approved', 'current_node_key' => $nextNode->node_key]);
            $this->writeLog($instance->id, $nextNode->node_key, $approverId, $approverName, 'approve', '流程完成');
            Event::trigger('workflow.instance.completed', $instance);
            return;
        }

        // 到达 fork/join 等中间节点 → 继续向下找 task 节点
        if (in_array($nextNode->node_type, ['condition', 'fork', 'join'])) {
            $taskNode = $this->getNextTaskNode($instance->definition_id, $nextNode->node_key, $instance->form_data ?? []);
            if (!$taskNode) {
                // 可能是 end 节点
                $endCheck = $this->resolveNextNode($instance->definition_id, $nextNode, $instance->form_data ?? []);
                if ($endCheck && $endCheck->node_type === 'end') {
                    $instance->save(['status' => 'approved', 'current_node_key' => $endCheck->node_key]);
                    $this->writeLog($instance->id, $endCheck->node_key, $approverId, $approverName, 'approve', '流程完成');
                    Event::trigger('workflow.instance.completed', $instance);
                    return;
                }
                throw new \RuntimeException('无法确定下一审批节点');
            }
            $nextNode = $taskNode;
        }

        // 抄送节点 → 发通知后继续流转（不创建审批任务）
        if ($nextNode->node_type === 'cc') {
            $ccIds = array_filter(explode(',', $nextNode->approver_ids));
            if (!empty($ccIds)) {
                Event::trigger('workflow.task.cc', [
                    'instance_id' => $instance->id,
                    'node_name'   => $nextNode->name,
                    'cc_ids'      => $ccIds,
                    'cc_names'    => $nextNode->approver_names,
                ]);
            }
            $this->writeLog($instance->id, $nextNode->node_key, $approverId, $approverName, 'cc', '抄送给：' . $nextNode->approver_names);

            // 跳过 CC 节点，继续流转
            $this->advance($instance, $nextNode, $approverId, $approverName);
            return;
        }

        // 创建下一节点的任务
        $this->createTasks($instance->id, $instance->definition_id, $nextNode, $instance->initiator_id, $instance->form_data ?? []);
        $instance->save(['current_node_key' => $nextNode->node_key]);
    }

    /**
     * 解析下一节点
     *
     * 对于 condition 节点，评估条件表达式选择分支
     */
    private function resolveNextNode(int $definitionId, Node $currentNode, array $formData): ?Node
    {
        $nextKeys = array_filter(explode(',', $currentNode->next_node_keys));
        if (empty($nextKeys)) {
            return null;
        }

        // 单一出边 → 直接取
        if (count($nextKeys) === 1) {
            return Node::where('definition_id', $definitionId)->where('node_key', $nextKeys[0])->find();
        }

        // 多出边 → 条件分支评估
        // condition_expr 格式: [{"node_key":"node_x","expr":"amount > 10000"}, {"node_key":"node_y","expr":"1"}]
        $conditions = $currentNode->condition_expr ?? [];
        if (is_string($conditions)) {
            $conditions = json_decode($conditions, true) ?: [];
        }

        foreach ($conditions as $cond) {
            $expr = $cond['expr'] ?? '1';
            $targetKey = $cond['node_key'] ?? '';
            if (!$targetKey) continue;

            // 无条件分支（默认路由）
            if ($expr === '1' || $expr === 'true') {
                return Node::where('definition_id', $definitionId)->where('node_key', $targetKey)->find();
            }

            // 评估条件表达式
            // 支持简单比较：field operator value (如 amount > 10000)
            if ($this->evalCondition($expr, $formData)) {
                return Node::where('definition_id', $definitionId)->where('node_key', $targetKey)->find();
            }
        }

        // 兜底：取第一个
        return Node::where('definition_id', $definitionId)->where('node_key', $nextKeys[0])->find();
    }

    /**
     * 获取下一个 task 节点（跳过 start/condition/cc/fork/join 等中间节点）
     * 遇到 cc 节点自动处理抄送并继续流转
     */
    private function getNextTaskNode(int $definitionId, string $fromNodeKey, array $formData = []): ?Node
    {
        $node = Node::where('definition_id', $definitionId)->where('node_key', $fromNodeKey)->find();
        if (!$node) return null;

        if ($node->node_type === 'task' || $node->node_type === 'end') {
            return $node;
        }

        // start/condition/cc/fork/join → 递归找下一节点
        $next = $this->resolveNextNode($definitionId, $node, $formData);
        if (!$next) return null;

        if ($next->node_type === 'task' || $next->node_type === 'end') {
            return $next;
        }

        return $this->getNextTaskNode($definitionId, $next->node_key, $formData);
    }

    /**
     * 创建审批任务（待办）
     */
    private function createTasks(int $instanceId, int $definitionId, Node $node, int $initiatorId, array $formData): void
    {
        $approverIds = $this->resolveApprovers($node, $initiatorId);
        if (empty($approverIds)) {
            throw new \RuntimeException('节点 [' . $node->name . '] 无法解析审批人');
        }

        // 批次号（用于会签/或签分组）— 随机9位数，确保在 INT UNSIGNED 范围内
        $batchNo = mt_rand(100000000, 999999999);

        // 查询审批人名称
        $admins = Db::name('admin')
            ->whereIn('id', $approverIds)
            ->where('status', 'enable')
            ->column('nickname', 'id');

        foreach ($approverIds as $adminId) {
            if (!isset($admins[$adminId])) continue;

            Task::create([
                'instance_id'   => $instanceId,
                'definition_id' => $definitionId,
                'node_key'      => $node->node_key,
                'node_name'     => $node->name,
                'assignee_id'   => $adminId,
                'assignee_name' => $admins[$adminId],
                'status'        => 'pending',
                'batch_no'      => $batchNo,
            ]);
        }

        // 触发任务分配事件（可推送通知）
        Event::trigger('workflow.task.assigned', [
            'instance_id' => $instanceId,
            'node_name'   => $node->name,
            'approver_ids' => $approverIds,
        ]);
    }

    /**
     * 解析审批人
     *
     * 规则:
     *   assignee     → 指定人员
     *   role         → 指定角色下的管理员
     *   dept         → 指定部门下的管理员
     *   initiator    → 发起人
     *   dept_leader  → 发起人所在部门主管
     */
    private function resolveApprovers(Node $node, int $initiatorId): array
    {
        $ids = [];

        switch ($node->approver_type) {
            case 'assignee':
                $ids = array_filter(explode(',', $node->approver_ids));
                break;

            case 'initiator':
                $ids = [$initiatorId];
                break;

            case 'role':
            case 'dept':
                // 通过 admin_group_access 查找角色/部门下的人员
                $groupIds = array_filter(explode(',', $node->approver_ids));
                if (!empty($groupIds)) {
                    $ids = Db::name('admin_group_access')
                        ->whereIn('group_id', $groupIds)
                        ->column('uid');
                }
                break;

            case 'dept_leader':
                // 查找发起人所在组的上级组的主管
                $groupIds = Db::name('admin_group_access')
                    ->where('uid', $initiatorId)
                    ->column('group_id');
                if (!empty($groupIds)) {
                    // 查找这些组的父组
                    $parentIds = Db::name('admin_group')
                        ->whereIn('id', $groupIds)
                        ->where('pid', '>', 0)
                        ->column('pid');
                    if (!empty($parentIds)) {
                        $ids = Db::name('admin_group_access')
                            ->whereIn('group_id', $parentIds)
                            ->column('uid');
                    }
                }
                break;

            default:
                $ids = array_filter(explode(',', $node->approver_ids));
                break;
        }

        return array_unique(array_map('intval', $ids));
    }

    /**
     * 评估条件表达式
     *
     * 支持格式: "field operator value"
     * 例如: "amount > 10000", "type == 'purchase'"
     */
    private function evalCondition(string $expr, array $formData): bool
    {
        // 安全性: 仅允许简单比较表达式
        if (!preg_match('/^(\w+)\s*(==|!=|>=|<=|>|<)\s*(.+)$/', trim($expr), $m)) {
            return $expr === '1' || $expr === 'true';
        }

        $field = $m[1];
        $operator = $m[2];
        $value = trim($m[3], "'\" ");

        $fieldValue = $formData[$field] ?? null;
        if ($fieldValue === null) return false;

        // 数值比较
        if (is_numeric($fieldValue) && is_numeric($value)) {
            $fieldValue = (float)$fieldValue;
            $value = (float)$value;
        }

        return match ($operator) {
            '=='  => (string)$fieldValue === (string)$value,
            '!='  => (string)$fieldValue !== (string)$value,
            '>'   => $fieldValue > $value,
            '<'   => $fieldValue < $value,
            '>='  => $fieldValue >= $value,
            '<='  => $fieldValue <= $value,
            default => false,
        };
    }

    /**
     * 写操作日志
     */
    private function writeLog(int $instanceId, string $nodeKey, int $operatorId, string $operatorName, string $action, string $comment = ''): void
    {
        Log::create([
            'instance_id'   => $instanceId,
            'node_key'      => $nodeKey,
            'operator_id'   => $operatorId,
            'operator_name' => $operatorName,
            'action'        => $action,
            'comment'       => $comment,
        ]);
    }
}
