<?php

namespace app\admin\controller\workflow;

use app\common\controller\Backend;
use app\admin\model\workflow\Bind as BindModel;

class Bind extends Backend
{
    /**
     * @var object
     * @phpstan-var BindModel
     */
    protected object $model;

    protected array|string $preExcludeFields = ['create_time', 'update_time'];

    protected string|array $quickSearchField = ['module_code', 'module_name'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new BindModel();
    }

    /**
     * 查看时追加流程定义名称
     */
    public function index(): void
    {
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->withJoin(['definition'])
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }
}
