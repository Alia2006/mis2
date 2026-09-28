<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use think\facade\Db;
use app\admin\library\WorkflowEngine;
use app\admin\model\workflow\Notification;
use app\admin\model\workflow\Sign;

/**
 * 工作流引擎集成测试
 *
 * 覆盖：
 *  - 发起 → 审批通过 → 完成（或签 ANY）
 *  - 驳回（记录 disagree 签批 + 通知发起人）
 *  - 会签 ALL：需全部审批人通过，逐人写入 agree 记录
 *  - 事件监听器写入站内信通知（G1）
 *  - 引擎写入 workflow_sign 逐人签批记录（G2）
 *
 * 测试在 .env 配置的数据库上运行，结束后清理自建数据，不污染业务数据。
 */
class WorkflowEngineTest extends TestCase
{
    private array $created = [];

    protected function tearDown(): void
    {
        $this->cleanup();
    }

    private function adminIds(): array
    {
        // 引擎 createTasks 仅对 status='enable' 的审批人创建任务，故此处只取启用账户
        $ids = Db::name('admin')->where('status', 'enable')->order('id')->limit(2)->column('id');
        if (empty($ids)) {
            $this->markTestSkipped('没有可用的（已启用）admin 账户，无法运行工作流集成测试');
        }
        return $ids;
    }

    /**
     * 创建一个临时启用的管理员，用于构造会签（多审批人）场景；测试结束自动清理。
     */
    private function createTempAdmin(): int
    {
        $uname = 'twf' . uniqid();
        $id = Db::name('admin')->insertGetId([
            'username'    => $uname,
            'nickname'    => $uname,
            'avatar'      => '',
            'email'       => $uname . '@example.com',
            'mobile'      => '',
            'password'    => md5('test' . 'salt'),
            'salt'        => 'salt',
            'motto'       => '',
            'status'      => 'enable',
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $this->created['admin'][] = $id;
        return $id;
    }

    private function buildWorkflow(string $moduleCode, string $performType, array $approverIds): void
    {
        $definitionId = Db::name('workflow_definition')->insertGetId([
            'name'         => '测试流程_' . $moduleCode,
            'code'         => $moduleCode,
            'status'       => 'published',
            'version'      => 1,
            'admin_id'     => $approverIds[0],
            'create_time'  => time(),
            'update_time'  => time(),
        ]);
        $this->created['definition'][] = $definitionId;

        Db::name('workflow_node')->insert([
            'definition_id'  => $definitionId,
            'node_key'       => 'start1',
            'name'           => '开始',
            'node_type'      => 'start',
            'next_node_keys' => 'task1',
            'create_time'    => time(),
            'update_time'    => time(),
        ]);
        Db::name('workflow_node')->insert([
            'definition_id'   => $definitionId,
            'node_key'        => 'task1',
            'name'            => '审批',
            'node_type'       => 'task',
            'approver_type'   => 'assignee',
            'approver_ids'    => implode(',', $approverIds),
            'perform_type'    => $performType,
            'next_node_keys'  => 'end1',
            'allow_back'      => 1,
            'allow_transfer'  => 1,
            'create_time'     => time(),
            'update_time'     => time(),
        ]);
        Db::name('workflow_node')->insert([
            'definition_id'  => $definitionId,
            'node_key'       => 'end1',
            'name'           => '结束',
            'node_type'      => 'end',
            'next_node_keys' => '',
            'create_time'    => time(),
            'update_time'    => time(),
        ]);

        Db::name('workflow_bind')->insert([
            'module_code'   => $moduleCode,
            'module_name'   => '测试',
            'definition_id' => $definitionId,
            'status'        => 'enabled',
            'create_time'   => time(),
            'update_time'   => time(),
        ]);
        $this->created['bind'][] = $moduleCode;
    }

    private function cleanup(): void
    {
        if (!empty($this->created['instance'])) {
            Db::name('workflow_task')->whereIn('instance_id', $this->created['instance'])->delete();
            Db::name('workflow_log')->whereIn('instance_id', $this->created['instance'])->delete();
            Db::name('workflow_sign')->whereIn('instance_id', $this->created['instance'])->delete();
            Db::name('workflow_notification')->whereIn('instance_id', $this->created['instance'])->delete();
            Db::name('workflow_instance')->whereIn('id', $this->created['instance'])->delete();
        }
        if (!empty($this->created['definition'])) {
            Db::name('workflow_node')->whereIn('definition_id', $this->created['definition'])->delete();
            Db::name('workflow_definition')->whereIn('id', $this->created['definition'])->delete();
        }
        if (!empty($this->created['bind'])) {
            Db::name('workflow_bind')->whereIn('module_code', $this->created['bind'])->delete();
        }
        if (!empty($this->created['admin'])) {
            Db::name('admin')->whereIn('id', $this->created['admin'])->delete();
        }
    }

    public function testStartApproveCompletesAndNotifies(): void
    {
        [$adminId] = $this->adminIds();
        $moduleCode = 'test_wf_any_' . uniqid();
        $this->buildWorkflow($moduleCode, 'ANY', [$adminId]);

        $instanceId = WorkflowEngine::instance()->start($moduleCode, 1, ['title' => '测试'], $adminId, '测试实例');
        $this->created['instance'][] = $instanceId;

        $task = Db::name('workflow_task')->where('instance_id', $instanceId)->where('status', 'pending')->find();
        $this->assertNotNull($task, '应创建待办任务');

        // G1: 分配通知已写入
        $notified = Notification::where('admin_id', $adminId)
            ->where('type', 'task_assigned')
            ->where('instance_id', $instanceId)
            ->find();
        $this->assertNotNull($notified, '应写入待办分配通知（G1）');

        WorkflowEngine::instance()->approve($task['id'], $adminId, '同意');

        $instance = Db::name('workflow_instance')->where('id', $instanceId)->find();
        $this->assertEquals('approved', $instance['status']);

        // G2: 签批记录写入
        $sign = Sign::where('instance_id', $instanceId)->where('result', 'agree')->find();
        $this->assertNotNull($sign, '应写入会签记录 agree（G2）');

        // G1: 发起人收到完成通知
        $done = Notification::where('admin_id', $adminId)
            ->where('type', 'instance_completed')
            ->where('instance_id', $instanceId)
            ->find();
        $this->assertNotNull($done, '发起人应收到完成通知（G1）');
    }

    public function testRejectRecordsSignAndNotifies(): void
    {
        [$adminId] = $this->adminIds();
        $moduleCode = 'test_wf_reject_' . uniqid();
        $this->buildWorkflow($moduleCode, 'ANY', [$adminId]);

        $instanceId = WorkflowEngine::instance()->start($moduleCode, 1, [], $adminId, '驳回测试');
        $this->created['instance'][] = $instanceId;

        $task = Db::name('workflow_task')->where('instance_id', $instanceId)->where('status', 'pending')->find();
        WorkflowEngine::instance()->reject($task['id'], $adminId, '不同意');

        $instance = Db::name('workflow_instance')->where('id', $instanceId)->find();
        $this->assertEquals('rejected', $instance['status']);

        $sign = Sign::where('instance_id', $instanceId)->where('result', 'disagree')->find();
        $this->assertNotNull($sign, '驳回应写入会签记录 disagree（G2）');

        $rej = Notification::where('admin_id', $adminId)
            ->where('type', 'instance_rejected')
            ->where('instance_id', $instanceId)
            ->find();
        $this->assertNotNull($rej, '发起人应收到驳回通知（G1）');
    }

    public function testCountersignAllRequiresAllApprovers(): void
    {
        $ids = $this->adminIds();
        // 会签需要两个「不同」的审批人（引擎对审批人去重），不足则创建临时启用账户
        while (count($ids) < 2) {
            $ids[] = $this->createTempAdmin();
        }
        $approverIds = [$ids[0], $ids[1]];
        $moduleCode = 'test_wf_all_' . uniqid();
        $this->buildWorkflow($moduleCode, 'ALL', $approverIds);

        $instanceId = WorkflowEngine::instance()->start($moduleCode, 1, [], $ids[0], '会签测试');
        $this->created['instance'][] = $instanceId;

        $tasks = Db::name('workflow_task')->where('instance_id', $instanceId)->where('status', 'pending')->select()->toArray();
        $this->assertCount(2, $tasks, '会签应创建两个待办');

        // 第一个人通过 → 仍在运行
        WorkflowEngine::instance()->approve($tasks[0]['id'], $ids[0], '同意1');
        $instance = Db::name('workflow_instance')->where('id', $instanceId)->find();
        $this->assertEquals('running', $instance['status'], '会签未完成时应保持 running');

        // 第二个人通过 → 完成
        WorkflowEngine::instance()->approve($tasks[1]['id'], $ids[1], '同意2');
        $instance = Db::name('workflow_instance')->where('id', $instanceId)->find();
        $this->assertEquals('approved', $instance['status']);

        $signCount = Sign::where('instance_id', $instanceId)->where('result', 'agree')->count();
        $this->assertEquals(2, $signCount, '会签两人应各写一条 agree 记录（G2）');
    }
}
