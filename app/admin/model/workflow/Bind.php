<?php

namespace app\admin\model\workflow;

use think\Model;

/**
 * 工作流业务模块绑定模型
 *
 * @property int    $id
 * @property string $module_code   业务编码
 * @property string $module_name   业务名称
 * @property int    $definition_id 绑定的流程定义ID
 * @property string $status        状态:enabled|disabled
 */
class Bind extends Model
{
    protected $name = 'workflow_bind';

    protected $autoWriteTimestamp = true;

    /**
     * 关联定义
     */
    public function definition()
    {
        return $this->belongsTo(Definition::class, 'definition_id');
    }
}
