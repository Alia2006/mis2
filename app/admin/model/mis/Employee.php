<?php

namespace app\admin\model\mis;

use think\Model;

/**
 * Employee
 */
class Employee extends Model
{
    // 表主键
    protected $pk = 'ID';

    // 表名
    protected $name = '员工信息表';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;


    public function getHourlyRateGrossAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }
}