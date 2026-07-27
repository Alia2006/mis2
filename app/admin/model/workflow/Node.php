<?php

namespace app\admin\model\workflow;

use think\Model;

/**
 * 工作流节点模型
 *
 * @property int    $id
 * @property int    $definition_id  流程定义ID
 * @property string $node_key       节点key
 * @property string $name           节点名称
 * @property string $node_type      类型:start|end|task|condition|fork|join
 * @property string $approver_type  审批人规则
 * @property string $approver_ids   审批人ID（逗号分隔）
 * @property string $approver_names 审批人名称
 * @property string $perform_type   审批方式:ANY|ALL
 * @property string $next_node_keys 下游节点key
 * @property array  $condition_expr 条件表达式
 * @property array  $form_fields    表单字段配置
 * @property bool   $allow_back     允许退回
 * @property bool   $allow_transfer 允许转办
 */
class Node extends Model
{
    protected $name = 'workflow_node';

    protected $autoWriteTimestamp = true;

    protected $json = ['condition_expr', 'form_fields'];

    protected $jsonAssoc = true;

    /**
     * 关联定义
     */
    public function definition()
    {
        return $this->belongsTo(Definition::class, 'definition_id');
    }
}
