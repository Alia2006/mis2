<?php

namespace app\admin\model\workflow;

use think\Model;
use think\facade\Db;

/**
 * 工作流实例模型
 *
 * @property int    $id
 * @property int    $definition_id     流程定义ID
 * @property string $business_type     业务模块编码
 * @property int    $business_id       业务数据ID
 * @property string $title             实例标题
 * @property int    $initiator_id      发起人ID
 * @property string $current_node_key  当前节点key
 * @property string $status            状态:running|approved|rejected|cancelled|timeout
 * @property array  $form_data         表单数据快照
 */
class Instance extends Model
{
    protected $name = 'workflow_instance';

    protected $autoWriteTimestamp = true;

    protected $json = ['form_data'];

    protected $jsonAssoc = true;

    /**
     * 追加属性
     */
    protected $append = [
        'initiator_name',
        'status_text',
    ];

    /**
     * 关联定义
     */
    public function definition()
    {
        return $this->belongsTo(Definition::class, 'definition_id');
    }

    /**
     * 关联任务
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'instance_id');
    }

    /**
     * 关联日志
     */
    public function logs()
    {
        return $this->hasMany(Log::class, 'instance_id');
    }

    /**
     * 发起人名称
     */
    public function getInitiatorNameAttr($value, $row): string
    {
        if (empty($row['initiator_id'])) {
            return '';
        }
        return Db::name('admin')->where('id', $row['initiator_id'])->value('nickname', '');
    }

    /**
     * 状态文案
     */
    public function getStatusTextAttr($value, $row): string
    {
        return match ($row['status'] ?? '') {
            'running'   => '审批中',
            'approved'  => '已通过',
            'rejected'  => '已驳回',
            'cancelled' => '已撤回',
            'timeout'   => '已超时',
            default     => $row['status'] ?? '',
        };
    }
}
