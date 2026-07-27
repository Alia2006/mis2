<template>
    <div class="workflow-designer">
        <!-- 顶部工具栏 -->
        <div class="designer-toolbar">
            <el-button-group>
                <el-button type="primary" :icon="Plus" @click="addNode('task')">添加审批节点</el-button>
                <el-button :icon="Plus" @click="addNode('condition')">添加条件分支</el-button>
            </el-button-group>
            <el-button-group>
                <el-button type="success" :icon="Check" @click="onSave" :loading="saving">保存</el-button>
                <el-button type="warning" :icon="RefreshLeft" @click="loadGraph">重新加载</el-button>
            </el-button-group>
        </div>

        <!-- 主体：左侧节点列表 + 右侧属性面板 -->
        <div class="designer-body">
            <!-- 节点列表 -->
            <div class="node-list">
                <div class="node-list-header">流程节点（拖拽排序）</div>
                <div class="node-items">
                    <!-- 开始节点（固定） -->
                    <div class="node-item node-start">
                        <el-icon><Promotion /></el-icon>
                        <span>开始</span>
                    </div>
                    <el-icon class="flow-arrow"><ArrowDown /></el-icon>

                    <!-- 可编辑节点 -->
                    <div
                        v-for="(node, idx) in nodes"
                        :key="node.id"
                        class="node-wrapper"
                    >
                        <div
                            class="node-item"
                            :class="{
                                'node-task': node.type === 'task',
                                'node-condition': node.type === 'condition',
                                active: selectedNodeId === node.id,
                            }"
                            @click="selectNode(node)"
                        >
                            <div class="node-info">
                                <el-icon v-if="node.type === 'task'"><User /></el-icon>
                                <el-icon v-else><Switch /></el-icon>
                                <span class="node-label">{{ node.text || node.name || ('节点' + (idx + 1)) }}</span>
                            </div>
                            <el-icon class="node-delete" @click.stop="removeNode(idx)"><Close /></el-icon>
                        </div>
                        <el-icon class="flow-arrow"><ArrowDown /></el-icon>
                    </div>

                    <!-- 结束节点（固定） -->
                    <div class="node-item node-end">
                        <el-icon><CircleCheck /></el-icon>
                        <span>结束</span>
                    </div>

                    <div v-if="nodes.length === 0" class="empty-tip">
                        <el-text type="info">请添加审批节点</el-text>
                    </div>
                </div>
            </div>

            <!-- 属性面板 -->
            <div class="node-properties">
                <div class="properties-header">节点属性</div>
                <div class="properties-body" v-if="selectedNode">
                    <el-form label-width="100px" label-position="right">
                        <el-form-item label="节点名称">
                            <el-input v-model="selectedNode.name" placeholder="请输入节点名称" />
                        </el-form-item>

                        <template v-if="selectedNode.type === 'task'">
                            <el-form-item label="审批方式">
                                <el-radio-group v-model="selectedNode.perform_type">
                                    <el-radio value="ANY">或签（任一同意）</el-radio>
                                    <el-radio value="ALL">会签（全员同意）</el-radio>
                                </el-radio-group>
                            </el-form-item>

                            <el-form-item label="审批人规则">
                                <el-select v-model="selectedNode.approver_type" placeholder="请选择">
                                    <el-option label="指定人员" value="assignee" />
                                    <el-option label="指定角色" value="role" />
                                    <el-option label="指定部门" value="dept" />
                                    <el-option label="发起人" value="initiator" />
                                    <el-option label="部门主管" value="dept_leader" />
                                </el-select>
                            </el-form-item>

                            <el-form-item
                                label="选择人员"
                                v-if="selectedNode.approver_type === 'assignee'"
                            >
                                <el-select
                                    v-model="selectedNode.approver_ids_arr"
                                    multiple
                                    filterable
                                    placeholder="请选择审批人"
                                >
                                    <el-option
                                        v-for="a in admins"
                                        :key="a.id"
                                        :label="a.nickname"
                                        :value="a.id"
                                    />
                                </el-select>
                            </el-form-item>

                            <el-form-item
                                label="选择角色"
                                v-if="selectedNode.approver_type === 'role' || selectedNode.approver_type === 'dept'"
                            >
                                <el-select
                                    v-model="selectedNode.approver_ids_arr"
                                    multiple
                                    filterable
                                    placeholder="请选择"
                                >
                                    <el-option
                                        v-for="g in groups"
                                        :key="g.id"
                                        :label="g.name"
                                        :value="g.id"
                                    />
                                </el-select>
                            </el-form-item>

                            <el-form-item label="允许退回">
                                <el-switch v-model="selectedNode.allow_back" />
                            </el-form-item>
                            <el-form-item label="允许转办">
                                <el-switch v-model="selectedNode.allow_transfer" />
                            </el-form-item>
                        </template>

                        <template v-if="selectedNode.type === 'condition'">
                            <el-form-item label="节点名称">
                                <el-input v-model="selectedNode.name" placeholder="如：金额判断" />
                            </el-form-item>
                            <el-alert type="info" :closable="false">
                                条件分支节点根据表单字段值自动路由。请在连线中配置条件表达式。
                                暂时按顺序流转，后续版本支持可视化条件配置。
                            </el-alert>
                        </template>
                    </el-form>
                </div>
                <div class="properties-body" v-else>
                    <el-empty description="请选择左侧节点编辑属性" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Check, RefreshLeft, Close, ArrowDown, Promotion, CircleCheck, User, Switch } from '@element-plus/icons-vue'
import { saveGraph } from '/@/api/backend/workflow/definition'
import { getAdmins, getGroups } from '/@/api/backend/workflow/definition'

const props = defineProps<{ definitionId: number }>()
const emit = defineEmits<{ (e: 'saved'): void }>()

interface DesignerNode {
    id: string
    type: 'task' | 'condition'
    name: string
    text?: string
    approver_type: string
    approver_ids: string
    approver_ids_arr: number[]
    approver_names: string
    perform_type: string
    allow_back: boolean
    allow_transfer: boolean
}

const nodes = ref<DesignerNode[]>([])
const selectedNodeId = ref<string>('')
const saving = ref(false)
const admins = ref<any[]>([])
const groups = ref<any[]>([])

const selectedNode = computed(() => {
    return nodes.value.find((n) => n.id === selectedNodeId.value) || null
})

let nodeCounter = 0

const genId = () => {
    nodeCounter++
    return 'node_' + Date.now() + '_' + nodeCounter
}

const addNode = (type: 'task' | 'condition') => {
    const node: DesignerNode = reactive({
        id: genId(),
        type,
        name: type === 'task' ? '审批节点' : '条件分支',
        approver_type: 'assignee',
        approver_ids: '',
        approver_ids_arr: [],
        approver_names: '',
        perform_type: 'ANY',
        allow_back: false,
        allow_transfer: true,
    })
    nodes.value.push(node)
    selectedNodeId.value = node.id
}

const removeNode = (idx: number) => {
    const removed = nodes.value[idx]
    nodes.value.splice(idx, 1)
    if (selectedNodeId.value === removed.id) {
        selectedNodeId.value = ''
    }
}

const selectNode = (node: DesignerNode) => {
    selectedNodeId.value = node.id
}

const loadGraph = async () => {
    // 从父组件传入的数据加载（暂时空实现，后续从API加载已有图）
    // 加载管理员和角色
    try {
        const [adminRes, groupRes] = await Promise.all([getAdmins(), getGroups()])
        admins.value = adminRes.data.list || []
        groups.value = groupRes.data.list || []
    } catch (e) {
        // ignore
    }
}

const onSave = async () => {
    saving.value = true
    try {
        // 构建 graph JSON
        const startId = 'node_start'
        const endId = 'node_end'
        const graphNodes: any[] = [
            { id: startId, type: 'workflow-start', text: { value: '开始' }, properties: {} },
        ]
        const graphEdges: any[] = []

        let prevId = startId
        for (const node of nodes.value) {
            const idsStr = (node.approver_ids_arr || []).join(',')
            const namesStr = (node.approver_ids_arr || [])
                .map((id) => {
                    if (node.approver_type === 'assignee') {
                        return admins.value.find((a) => a.id === id)?.nickname || ''
                    }
                    return groups.value.find((g) => g.id === id)?.name || ''
                })
                .join(',')

            graphNodes.push({
                id: node.id,
                type: node.type === 'task' ? 'workflow-task' : 'workflow-condition',
                text: { value: node.name },
                properties: {
                    name: node.name,
                    approver_type: node.approver_type,
                    approver_ids: idsStr,
                    approver_names: namesStr,
                    perform_type: node.perform_type,
                    allow_back: node.allow_back ? 1 : 0,
                    allow_transfer: node.allow_transfer ? 1 : 0,
                },
            })
            graphEdges.push({ sourceNodeId: prevId, targetNodeId: node.id, properties: {} })
            prevId = node.id
        }
        graphNodes.push({ id: endId, type: 'workflow-end', text: { value: '结束' }, properties: {} })
        graphEdges.push({ sourceNodeId: prevId, targetNodeId: endId, properties: {} })

        await saveGraph({
            id: props.definitionId,
            graph: { nodes: graphNodes, edges: graphEdges },
        })
        emit('saved')
    } catch (e: any) {
        ElMessage.error(e.message || '保存失败')
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    loadGraph()
})
</script>

<style scoped lang="scss">
.workflow-designer {
    height: calc(100vh - 120px);
    display: flex;
    flex-direction: column;
}

.designer-toolbar {
    display: flex;
    justify-content: space-between;
    padding: 0 0 12px;
}

.designer-body {
    flex: 1;
    display: flex;
    gap: 16px;
    overflow: hidden;
}

.node-list {
    width: 300px;
    border: 1px solid var(--el-border-color);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.node-list-header {
    padding: 12px;
    font-weight: 600;
    background: var(--el-fill-color-light);
    border-bottom: 1px solid var(--el-border-color);
}

.node-items {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.node-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.node-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    min-height: 48px;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s;

    .node-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .node-label {
        font-size: 14px;
    }

    .node-delete {
        opacity: 0;
        transition: opacity 0.2s;
        color: var(--el-color-danger);
    }

    &:hover .node-delete {
        opacity: 1;
    }

    &.active {
        border-color: var(--el-color-primary);
    }

    &.node-start,
    &.node-end {
        cursor: default;
        justify-content: center;
        gap: 8px;
    }

    &.node-start {
        background: var(--el-color-success-light-9);
        color: var(--el-color-success);
    }

    &.node-end {
        background: var(--el-color-info-light-9);
        color: var(--el-color-info);
    }

    &.node-task {
        background: var(--el-color-primary-light-9);
    }

    &.node-condition {
        background: var(--el-color-warning-light-9);
    }
}

.flow-arrow {
    margin: 8px 0;
    color: var(--el-text-color-secondary);
}

.empty-tip {
    margin-top: 20px;
}

.node-properties {
    flex: 1;
    border: 1px solid var(--el-border-color);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.properties-header {
    padding: 12px;
    font-weight: 600;
    background: var(--el-fill-color-light);
    border-bottom: 1px solid var(--el-border-color);
}

.properties-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}
</style>
