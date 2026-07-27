<?php

namespace app\admin\model\workflow;

use think\Model;

/**
 * 工作流审批任务模型（待办池）
 *
 * @property int    $id
 * @property int    $instance_id   实例ID
 * @property int    $definition_id 定义ID
 * @property string $node_key      节点key
 * @property string $node_name     节点名称
 * @property int    $assignee_id   被分配人ID
 * @property string $assignee_name 被分配人名称
 * @property int    $approver_id   实际审批人ID
 * @property string $approver_name 实际审批人名称
 * @property string $status        状态:pending|approved|rejected|transferred|cancelled
 * @property string $comment       审批意见
 * @property bool   $is_cc         抄送任务
 * @property bool   $is_read       已读
 * @property int    $batch_no      会签批次号
 */
class Task extends Model
{
    protected $name = 'workflow_task';

    protected $autoWriteTimestamp = true;

    /**
     * 关联实例
     */
    public function instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    /**
     * 关联会签记录
     */
    public function signs()
    {
        return $this->hasMany(Sign::class, 'task_id');
    }

    /**
     * 状态文案
     */
    public function getStatusTextAttr($value, $row): string
    {
        return match ($row['status'] ?? '') {
            'pending'     => '待审批',
            'approved'    => '已通过',
            'rejected'    => '已驳回',
            'transferred' => '已转办',
            'cancelled'   => '已取消',
            default       => $row['status'] ?? '',
        };
    }
}
