<?php

namespace app\admin\model\mis;

use think\Model;

/**
 * Projecttypetable
 */
class Projecttypetable extends Model
{
    // 表主键
    protected $pk = 'ID';

    // 表名
    protected $table = 'Project_type_Table';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

}
