<?php

namespace app\admin\controller\workflow;

use Throwable;
use think\facade\Db;
use app\common\controller\Backend;
use app\admin\model\workflow\Definition as DefinitionModel;
use app\admin\model\workflow\Node as NodeModel;
use app\admin\library\WorkflowEngine;

class Definition extends Backend
{
    /**
     * @var object
     * @phpstan-var DefinitionModel
     */
    protected object $model;

    protected array|string $preExcludeFields = ['graph', 'version', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['name', 'code'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new DefinitionModel();
    }

    /**
     * 保存设计器
     * POST { id, graph: {nodes, edges} }
     */
    public function saveGraph(): void
    {
        $id = $this->request->post('id/d', 0);
        $graph = $this->request->post('graph', []);

        $row = DefinitionModel::find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        if ($row->status === 'published') {
            $this->error('已发布的流程不允许修改，请新建版本');
        }

        Db::startTrans();
        try {
            // 保存设计器JSON
            $row->save(['graph' => $graph]);

            // 清除旧节点，重新解析
            NodeModel::where('definition_id', $id)->delete();
            $this->syncNodes($id, $graph);

            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('保存成功');
    }

    /**
     * 发布流程
     */
    public function publish(): void
    {
        $id = $this->request->post('id/d', 0);
        $row = DefinitionModel::find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        // 校验完整性
        $nodes = NodeModel::where('definition_id', $id)->select();
        if ($nodes->isEmpty()) {
            $this->error('请先设计流程节点');
        }

        $hasStart = $nodes->where('node_type', 'start')->count() > 0;
        $hasEnd = $nodes->where('node_type', 'end')->count() > 0;
        if (!$hasStart) {
            $this->error('流程缺少开始节点');
        }
        if (!$hasEnd) {
            $this->error('流程缺少结束节点');
        }

        // 校验 task 节点配置了审批人
        foreach ($nodes as $node) {
            if ($node->node_type === 'task') {
                if ($node->approver_type !== 'initiator' && $node->approver_type !== 'dept_leader' && empty($node->approver_ids)) {
                    $this->error('节点 [' . $node->name . '] 未配置审批人');
                }
            }
        }

        $row->save(['status' => 'published']);
        $this->success('发布成功');
    }

    /**
     * 复制流程定义
     */
    public function copy(): void
    {
        $id = $this->request->post('id/d', 0);
        $row = DefinitionModel::find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        Db::startTrans();
        try {
            $newDef = DefinitionModel::create([
                'name'        => $row->name . ' (副本)',
                'code'        => $row->code . '_copy_' . time(),
                'description' => $row->description,
                'graph'       => $row->graph,
                'status'      => 'draft',
                'admin_id'    => $this->auth->id,
            ]);

            // 复制节点
            $nodes = NodeModel::where('definition_id', $id)->select();
            foreach ($nodes as $node) {
                $nodeArr = $node->toArray();
                unset($nodeArr['id'], $nodeArr['create_time'], $nodeArr['update_time']);
                $nodeArr['definition_id'] = $newDef->id;
                NodeModel::create($nodeArr);
            }

            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('复制成功');
    }

    /**
     * 获取管理员列表（设计器选择审批人/抄送人）
     * 返回 id, nickname, username, group_ids（所属组ID数组）
     */
    public function getAdmins(): void
    {
        $groupId = $this->request->param('group_id', 0);

        // 查全部管理员
        $list = Db::name('admin')
            ->where('status', 'enable')
            ->field('id, nickname, username')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        if (empty($list)) {
            $this->success('', ['list' => []]);
            return;
        }

        // 查管理员-组关联
        $accessList = Db::name('admin_group_access')->select()->toArray();
        $adminGroups = [];
        foreach ($accessList as $access) {
            $uid = $access['uid'];
            if (!isset($adminGroups[$uid])) $adminGroups[$uid] = [];
            $adminGroups[$uid][] = (int)$access['group_id'];
        }

        // 附加 group_ids
        foreach ($list as &$item) {
            $item['group_ids'] = $adminGroups[$item['id']] ?? [];
        }
        unset($item);

        // 按组过滤
        if ($groupId) {
            $list = array_filter($list, fn($a) => in_array((int)$groupId, $a['group_ids']));
            $list = array_values($list);
        }

        $this->success('', ['list' => $list]);
    }

    /**
     * 获取角色组列表（设计器选择角色/部门）
     */
    public function getGroups(): void
    {
        $list = Db::name('admin_group')
            ->where('status', 1)
            ->field('id, name, pid')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $this->success('', ['list' => $list]);
    }

    /**
     * 从设计器JSON同步节点到 workflow_node 表
     * 兼容新版树状设计器（treeToGraph）输出的 graph 格式
     */
    private function syncNodes(int $definitionId, array $graph): void
    {
        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];

        // 构建邻接表
        $nextMap = [];
        foreach ($edges as $edge) {
            $source = $edge['sourceNodeId'] ?? '';
            $target = $edge['targetNodeId'] ?? '';
            if ($source && $target) {
                $nextMap[$source][] = $target;
            }
        }

        // 条件边的表达式（source → [{node_key, expr}]）
        $conditionMap = [];
        foreach ($edges as $edge) {
            $source = $edge['sourceNodeId'] ?? '';
            $target = $edge['targetNodeId'] ?? '';
            $expr = $edge['properties']['expr'] ?? '';
            if ($source && $target && $expr) {
                $conditionMap[$source][] = ['node_key' => $target, 'expr' => $expr];
            }
        }

        $sort = 0;
        foreach ($nodes as $node) {
            $key = $node['id'] ?? '';
            $type = $node['type'] ?? 'task';
            $properties = $node['properties'] ?? [];

            // 映射节点类型
            $nodeType = match (true) {
                str_contains($type, 'start') => 'start',
                str_contains($type, 'end') => 'end',
                str_contains($type, 'condition') || str_contains($type, 'decision') || str_contains($type, 'gateway') => 'condition',
                str_contains($type, 'cc') || str_contains($type, 'copy') => 'cc',
                str_contains($type, 'fork') => 'fork',
                str_contains($type, 'join') => 'join',
                default => 'task',
            };

            $nextKeys = implode(',', $nextMap[$key] ?? []);

            // CC 节点存储抄送人信息
            $ccUserIds = '';
            $ccUserNames = '';
            if ($nodeType === 'cc') {
                $ccUserIds = $properties['cc_ids'] ?? ($properties['approver_ids'] ?? '');
                $ccUserNames = $properties['cc_names'] ?? ($properties['approver_names'] ?? '');
            }

            NodeModel::create([
                'definition_id'   => $definitionId,
                'node_key'        => $key,
                'name'            => $node['text']['value'] ?? ($properties['name'] ?? $key),
                'node_type'       => $nodeType,
                'approver_type'   => $nodeType === 'cc' ? 'assignee' : ($properties['approver_type'] ?? 'assignee'),
                'approver_ids'    => $nodeType === 'cc' ? $ccUserIds : ($properties['approver_ids'] ?? ''),
                'approver_names'  => $nodeType === 'cc' ? $ccUserNames : ($properties['approver_names'] ?? ''),
                'perform_type'    => $properties['perform_type'] ?? 'ANY',
                'next_node_keys'  => $nextKeys,
                'condition_expr'  => $conditionMap[$key] ?? null,
                'form_fields'     => $properties['form_fields'] ?? null,
                'allow_back'      => $properties['allow_back'] ?? 0,
                'allow_transfer'  => $properties['allow_transfer'] ?? 1,
                'sort'            => $sort++,
            ]);
        }
    }
}
