<?php

namespace app\admin\model\workflow;

use think\Model;

/**
 * 工作流会签记录模型
 *
 * @property int    $id
 * @property int    $task_id      任务ID
 * @property int    $instance_id  实例ID
 * @property int    $signer_id    会签人ID
 * @property string $signer_name  会签人名称
 * @property string $result       结果:agree|disagree
 * @property string $comment      意见
 */
class Sign extends Model
{
    protected $name = 'workflow_sign';

    /**
     * 仅写入创建时间
     */
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
