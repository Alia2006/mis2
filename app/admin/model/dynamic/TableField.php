<?php

namespace app\admin\model\dynamic;

use think\Model;

/**
 * 动态表格字段配置模型
 */
class TableField extends Model
{
    // 表名
    protected $name = 'dynamic_table_field';

    // 表主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = false;

    /**
     * 获取器：column_replace_value JSON 解码
     */
    public function getColumnReplaceValueAttr($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    /**
     * 获取器：column_custom JSON 解码
     */
    public function getColumnCustomAttr($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    /**
     * 获取器：form_validators JSON 解码
     */
    public function getFormValidatorsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 获取器：form_input_attr JSON 解码
     */
    public function getFormInputAttrAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 获取器：column_show 转布尔
     */
    public function getColumnShowAttr($value): bool
    {
        return (bool)$value;
    }
}
