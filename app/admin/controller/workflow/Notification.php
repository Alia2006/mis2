<?php

namespace app\admin\controller\workflow;

use app\common\controller\Backend;

/**
 * 工作流通知（站内信）
 *
 * 提供给登录管理员查询自己的通知、未读计数、标记已读。
 */
class Notification extends Backend
{
    /**
     * @var object
     * @phpstan-var \app\admin\model\workflow\Notification
     */
    protected object $model;

    // 通知接口属于用户个人数据，无需额外权限节点
    protected array $noNeedPermission = ['index', 'unreadCount', 'markRead'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new Notification();
    }

    /**
     * 我的消息列表
     */
    public function index(): void
    {
        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->where('admin_id', $this->auth->id)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 未读数量
     */
    public function unreadCount(): void
    {
        $count = $this->model
            ->where('admin_id', $this->auth->id)
            ->where('is_read', 0)
            ->count();

        $this->success('', ['count' => $count]);
    }

    /**
     * 标记已读
     * POST id=0 或不传 → 全部已读；传 id → 单条已读
     */
    public function markRead(): void
    {
        $id = $this->request->post('id/d', 0);

        $query = $this->model->where('admin_id', $this->auth->id);
        if ($id > 0) {
            $query->where('id', $id);
        }
        $query->update(['is_read' => 1]);

        $this->success('操作成功');
    }
}
