<?php

namespace app\admin\model\dynamic;

use think\Model;
use think\facade\Db;

/**
 * 动态数据模型 — 运行时绑定任意数据表
 *
 * 用法：
 *   $model = new DynamicModel();
 *   $model->setDynamicTable('员工信息表', 'ID', '');
 */
class DynamicModel extends Model
{
    /** 表名（不含前缀时 ThinkPHP 自动处理） */
    protected $name = '';

    /** 主键 */
    protected $pk = 'id';

    /** 不自动写入时间戳 */
    protected $autoWriteTimestamp = false;

    /**
     * 运行时设置目标表
     *
     * 使用 $this->table 而非 $this->name，避免 ThinkPHP 的 Str::snake() 转换
     * 把 'Cost_Type_Table' 变成 'cost__type__table'
     */
    public function setDynamicTable(string $tableName, string $pk = 'id', string $connection = ''): void
    {
        $this->table = $tableName;
        $this->pk    = $pk;

        if ($connection) {
            $this->connection = $connection;
        }
    }

    /**
     * 创建新的模型实例（覆盖父类）
     *
     * ThinkPHP 的 Model::newInstance() 只复制 connection 和 suffix，
     * 不复制 table 和 pk。导致 find()/select() 返回的实例丢失动态表名，
     * 回退到 Str::snake(className) = 'dynamic_model'，触发 SQL 错误。
     *
     * 此方法确保所有子实例都继承动态表名和主键。
     */
    public function newInstance(array $data = [], $where = null, array $options = []): Model
    {
        $model = parent::newInstance($data, $where, $options);
        $model->table = $this->table;
        $model->pk    = $this->pk;
        return $model;
    }

    /**
     * 获取表完整名称（含前缀），供 Db::name 等使用
     */
    public function getFullTableName(): string
    {
        $prefix = $this->db()->getConnection()->getConfig('prefix') ?: '';
        return $prefix . $this->table;
    }
}
