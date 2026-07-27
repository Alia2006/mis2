<?php

namespace app\admin\model\workflow;

use think\Model;

/**
 * 工作流操作日志模型（追加写入）
 *
 * @property int    $id
 * @property int    $instance_id    实例ID
 * @property string $node_key       节点key
 * @property int    $operator_id    操作人ID
 * @property string $operator_name  操作人名称
 * @property string $action         动作:start|approve|reject|back|transfer|cc|cancel
 * @property string $comment        操作意见
 */
class Log extends Model
{
    protected $name = 'workflow_log';

    /**
     * 仅写入创建时间
     */
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    /**
     * 关联实例
     */
    public function instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    /**
     * 动作文案
     */
    public function getActionTextAttr($value, $row): string
    {
        return match ($row['action'] ?? '') {
            'start'    => '发起',
            'approve'  => '通过',
            'reject'   => '驳回',
            'back'     => '退回',
            'transfer' => '转办',
            'cc'       => '抄送',
            'cancel'   => '撤回',
            default    => $row['action'] ?? '',
        };
    }
}
