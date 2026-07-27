<?php

namespace app\admin\controller\workflow;

use Throwable;
use app\common\controller\Backend;
use app\admin\model\workflow\Instance as InstanceModel;
use app\admin\model\workflow\Task as TaskModel;
use app\admin\model\workflow\Log as LogModel;
use app\admin\library\WorkflowEngine;

class Instance extends Backend
{
    /**
     * @var object
     * @phpstan-var InstanceModel
     */
    protected object $model;

    protected string|array $quickSearchField = ['title', 'business_type'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new InstanceModel();
    }

    /**
     * 实例详情（含任务+日志）
     */
    public function detail(): void
    {
        $id = $this->request->param('id/d', 0);
        $instance = InstanceModel::find($id);
        if (!$instance) {
            $this->error(__('Record not found'));
        }

        $tasks = TaskModel::where('instance_id', $id)
            ->order('create_time', 'asc')
            ->select();

        $logs = LogModel::where('instance_id', $id)
            ->order('create_time', 'asc')
            ->select();

        $this->success('', [
            'instance' => $instance,
            'tasks'    => $tasks,
            'logs'     => $logs,
        ]);
    }

    /**
     * 撤回流程
     */
    public function cancel(): void
    {
        $id = $this->request->post('id/d', 0);
        $comment = $this->request->post('comment', '');

        try {
            WorkflowEngine::instance()->cancel($id, $this->auth->id, $comment);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        $this->success('撤回成功');
    }
}
