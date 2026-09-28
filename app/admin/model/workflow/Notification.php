<?php

namespace app\admin\model\workflow;

use think\Model;

/**
 * 工作流通知模型（站内信）
 *
 * @property int    $id
 * @property int    $admin_id     接收人 admin_id
 * @property string $type         类型:task_assigned|instance_completed|instance_rejected|instance_cancelled|task_cc
 * @property string $title        通知标题
 * @property string $content      通知内容
 * @property string $business_type 业务模块编码
 * @property int    $instance_id  流程实例ID
 * @property int    $task_id      任务ID
 * @property int    $is_read      是否已读
 */
class Notification extends Model
{
    protected $name = 'workflow_notification';

    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    protected $type = [
        'is_read' => 'integer',
    ];
}
