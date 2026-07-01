<?php

namespace app\admin\model\dynamic;

use think\Model;

/**
 * 动态表格配置模型
 */
class TableConfig extends Model
{
    // 表名
    protected $name = 'dynamic_table_config';

    // 表主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // JSON 字段
    protected $json = ['default_items'];

    // 字段关联
    public function fields()
    {
        return $this->hasMany(TableField::class, 'table_id', 'id')->order('sort', 'asc');
    }

    /**
     * 获取器：header_buttons 自动 JSON 解码
     */
    public function getHeaderButtonsAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 获取器：row_buttons 自动 JSON 解码
     */
    public function getRowButtonsAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 获取器：quick_search_fields 转数组
     */
    public function getQuickSearchFieldsAttr($value): array
    {
        if (!$value) return [];
        return array_filter(explode(',', $value));
    }
}
