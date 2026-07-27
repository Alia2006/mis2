<?php

namespace app\admin\controller\workflow;

use Throwable;
use app\common\controller\Backend;
use app\admin\model\workflow\Task as TaskModel;
use app\admin\model\workflow\Instance as InstanceModel;
use app\admin\library\WorkflowEngine;

class Task extends Backend
{
    /**
     * @var object
     * @phpstan-var TaskModel
     */
    protected object $model;

    protected array $noNeedPermission = ['myTodo', 'myDone', 'myInitiated'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new TaskModel();
    }

    /**
     * 我的待办
     */
    public function myTodo(): void
    {
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->where('assignee_id', $this->auth->id)
            ->where('status', 'pending')
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 我的已办
     */
    public function myDone(): void
    {
        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->where('approver_id', $this->auth->id)
            ->whereIn('status', ['approved', 'rejected', 'transferred'])
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 我发起的
     */
    public function myInitiated(): void
    {
        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = (new InstanceModel())
            ->where('initiator_id', $this->auth->id)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 审批通过
     */
    public function approve(): void
    {
        $taskId = $this->request->post('id/d', 0);
        $comment = $this->request->post('comment', '');

        try {
            WorkflowEngine::instance()->approve($taskId, $this->auth->id, $comment);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $this->success('审批通过');
    }

    /**
     * 驳回
     */
    public function reject(): void
    {
        $taskId = $this->request->post('id/d', 0);
        $comment = $this->request->post('comment', '');

        try {
            WorkflowEngine::instance()->reject($taskId, $this->auth->id, $comment);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $this->success('已驳回');
    }

    /**
     * 退回上一步
     */
    public function back(): void
    {
        $taskId = $this->request->post('id/d', 0);
        $comment = $this->request->post('comment', '');

        try {
            WorkflowEngine::instance()->back($taskId, $this->auth->id, $comment);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $this->success('已退回');
    }

    /**
     * 转办
     */
    public function transfer(): void
    {
        $taskId = $this->request->post('id/d', 0);
        $toAdminId = $this->request->post('to_admin_id/d', 0);
        $comment = $this->request->post('comment', '');

        if (!$toAdminId) {
            $this->error('请选择转办人');
        }

        try {
            WorkflowEngine::instance()->transfer($taskId, $this->auth->id, $toAdminId, $comment);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $this->success('转办成功');
    }
}
