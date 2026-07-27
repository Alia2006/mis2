<?php

namespace app\admin\model\workflow;

use think\Model;

/**
 * 工作流定义模型
 *
 * @property int    $id
 * @property string $name         流程名称
 * @property string $code         流程编码
 * @property string $description  描述
 * @property array  $graph        设计器JSON
 * @property int    $version      版本号
 * @property string $status       状态:draft|published|disabled
 * @property int    $admin_id     创建人ID
 */
class Definition extends Model
{
    protected $name = 'workflow_definition';

    protected $autoWriteTimestamp = true;

    protected $json = ['graph'];

    protected $jsonAssoc = true;

    /**
     * 关联节点
     */
    public function nodes()
    {
        return $this->hasMany(Node::class, 'definition_id');
    }

    /**
     * 关联实例
     */
    public function instances()
    {
        return $this->hasMany(Instance::class, 'definition_id');
    }

    /**
     * 关联绑定
     */
    public function binds()
    {
        return $this->hasMany(Bind::class, 'definition_id');
    }

    /**
     * 状态文案
     */
    public function getStatusTextAttr($value, $row): string
    {
        return match ($row['status'] ?? '') {
            'draft'     => '草稿',
            'published' => '已发布',
            'disabled'  => '已停用',
            default     => $row['status'] ?? '',
        };
    }
}
