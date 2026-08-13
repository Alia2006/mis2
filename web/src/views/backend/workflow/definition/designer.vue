<template>
  <div class="dingflow-design" style="position: relative; border-radius: 0 0 8px 8px; overflow: auto">
    <!-- 缩放控制 -->
    <div class="wf-zoom">
      <el-button :icon="ZoomOut" size="small" circle :disabled="nowVal == 50" @click="zoomSize(1)" />
      <span>{{ nowVal }}%</span>
      <el-button :icon="ZoomIn" size="small" circle :disabled="nowVal == 300" @click="zoomSize(2)" />
    </div>

    <!-- 工具栏 -->
    <div style="position: absolute; top: 10px; left: 20px; z-index: 10; display: flex; gap: 10px">
      <el-button type="success" :icon="Check" @click="onSave" :loading="saving">保存</el-button>
      <el-button :icon="RefreshLeft" @click="loadGraph">重新加载</el-button>
    </div>

    <div class="box-scale" :style="`transform: scale(${nowVal / 100})`">
      <nodeWrap v-model:nodeConfig="nodeConfig" v-model:flowPermission="flowPermission" />
      <div class="end-node">
        <div class="end-node-circle"></div>
        <div class="end-node-text">流程结束</div>
      </div>
    </div>

    <!-- 抽屉和弹窗 -->
    <errorDialog v-model:visible="tipVisible" :list="tipList" />
    <promoterDrawer />
    <approverDrawer :directorMaxLevel="directorMaxLevel" />
    <copyerDrawer />
    <conditionDrawer />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { ZoomIn, ZoomOut, Check, RefreshLeft } from '@element-plus/icons-vue'
import { saveGraph } from '/@/api/backend/workflow/definition'
import createAxios from '/@/utils/axios'
import { useWorkflowStore } from './designer/store'

import nodeWrap from './designer/nodeWrap.vue'
import errorDialog from './designer/errorDialog.vue'
import promoterDrawer from './designer/promoterDrawer.vue'
import approverDrawer from './designer/approverDrawer.vue'
import copyerDrawer from './designer/copyerDrawer.vue'
import conditionDrawer from './designer/conditionDrawer.vue'
import './designer/workflow.css'

import $func from './designer/helpers'

const props = defineProps<{ definitionId: number }>()
const emit = defineEmits<{ (e: 'saved'): void }>()

const store = useWorkflowStore()
const { setIsTried } = store

let tipList = ref<any[]>([])
let tipVisible = ref(false)
let nowVal = ref(100)
let nodeConfig = ref<any>({})
let flowPermission = ref<any[]>([])
let directorMaxLevel = ref(4)
let saving = ref(false)

/* ─── 默认发起人节点 ─── */
const defaultNodeConfig = () => ({
    nodeName: '发起人',
    type: 0,
    nodeUserList: [],
    childNode: null,
})

/* ─── 加载已保存的设计 ─── */
const loadGraph = async () => {
    if (!props.definitionId) {
        nodeConfig.value = defaultNodeConfig()
        return
    }
    try {
        const res = await createAxios({
            url: '/admin/workflow.Definition/edit',
            method: 'get',
            params: { id: props.definitionId },
        })
        const row = res.data?.data?.row || res.data?.row
        if (row?.graph && Array.isArray(row.graph.nodes) && row.graph.nodes.length > 0) {
            // 有已保存的图 → 转回树结构
            nodeConfig.value = graphToTree(row.graph)
            flowPermission.value = row.flowPermission || []
        } else {
            nodeConfig.value = defaultNodeConfig()
        }
    } catch {
        nodeConfig.value = defaultNodeConfig()
    }
}

/* ─── 树 → 图 JSON（后端 syncNodes 需要的格式） ─── */
function treeToGraph(root: any): { nodes: any[]; edges: any[] } {
    const nodes: any[] = []
    const edges: any[] = []
    let nodeCounter = 0

    const genId = () => 'node_' + Date.now() + '_' + (++nodeCounter)

    // 起始节点
    const startId = 'node_start'
    nodes.push({ id: startId, type: 'workflow-start', text: { value: '开始' }, properties: {} })

    // 如果根节点是发起人(type=0)，从其 childNode 开始处理
    let chain = root.type === 0 ? root.childNode : root
    let prevId = startId

    const processChain = (node: any) => {
        if (!node) {
            // 连接到结束
            const endId = 'node_end'
            if (!nodes.find((n) => n.id === endId)) {
                nodes.push({ id: endId, type: 'workflow-end', text: { value: '结束' }, properties: {} })
            }
            edges.push({ sourceNodeId: prevId, targetNodeId: endId, properties: {} })
            return
        }

        if (node.type === 1) {
            // 审批节点
            const id = genId()
            const idsStr = (node.nodeUserList || []).map((u: any) => u.targetId).join(',')
            const namesStr = (node.nodeUserList || []).map((u: any) => u.name).join(',')
            const approverType = node.settype === 1 ? 'assignee'
                : node.settype === 2 ? 'dept_leader'
                : node.settype === 5 ? 'initiator'
                : node.settype === 4 ? (node.selectRange === 3 ? 'role' : 'assignee')
                : 'assignee'
            const performType = node.examineMode === 2 ? 'ALL' : 'ANY'

            nodes.push({
                id, type: 'workflow-task', text: { value: node.nodeName || '审批人' },
                properties: {
                    name: node.nodeName || '审批人',
                    approver_type: approverType,
                    approver_ids: idsStr,
                    approver_names: namesStr,
                    perform_type: performType,
                    allow_back: 0,
                    allow_transfer: 1,
                },
            })
            edges.push({ sourceNodeId: prevId, targetNodeId: id, properties: {} })
            prevId = id
            processChain(node.childNode)

        } else if (node.type === 2) {
            // 抄送节点 → 跳过（后续版本支持），直接处理下一个
            processChain(node.childNode)

        } else if (node.type === 4) {
            // 条件分支
            const condId = genId()
            const conditions: any[] = []
            ;(node.conditionNodes || []).forEach((cond: any) => {
                const exprStr = (cond.conditionList || []).map((c: any) => {
                    if (c.columnType === 'Double') {
                        const ops = ['', '<', '>', '<=', '=', '>=']
                        return `${c.showName} ${ops[parseInt(c.optType)] || '>'} ${c.zdy1}`
                    }
                    return '1'
                }).join(' && ')

                conditions.push({ node_key: cond.nodeName, expr: exprStr || '1' })
            })

            nodes.push({
                id: condId, type: 'workflow-condition', text: { value: node.nodeName || '条件分支' },
                properties: {},
            })
            nodes[nodes.length - 1].properties = {}
            // 存储条件到边的 properties
            edges.push({ sourceNodeId: prevId, targetNodeId: condId, properties: {} })

            // 每个分支
            ;(node.conditionNodes || []).forEach((cond: any, idx: number) => {
                const branchId = genId()
                const exprStr = conditions[idx]?.expr || '1'
                nodes.push({
                    id: branchId, type: 'workflow-task', text: { value: cond.nodeName || '条件' + (idx + 1) },
                    properties: {
                        name: cond.nodeName || '条件' + (idx + 1),
                        approver_type: 'assignee',
                        approver_ids: '',
                        approver_names: '',
                        perform_type: 'ANY',
                        allow_back: 0,
                        allow_transfer: 1,
                    },
                })
                edges.push({ sourceNodeId: condId, targetNodeId: branchId, properties: { expr: exprStr } })

                // 递归处理分支内的链
                const savedPrev = prevId
                prevId = branchId
                processChain(cond.childNode)
                prevId = savedPrev
            })

            // 分支汇合后继续主链
            prevId = condId
            processChain(node.childNode)

        } else {
            processChain(node.childNode)
        }
    }

    processChain(chain)

    // 确保有结束节点
    if (!nodes.find((n) => n.id === 'node_end')) {
        nodes.push({ id: 'node_end', type: 'workflow-end', text: { value: '结束' }, properties: {} })
        edges.push({ sourceNodeId: prevId, targetNodeId: 'node_end', properties: {} })
    }

    return { nodes, edges }
}

/* ─── 图 → 树（加载已保存的设计器数据时用） ─── */
function graphToTree(graph: any): any {
    const nodes: any[] = graph.nodes || []
    const edges: any[] = graph.edges || []

    // 构建邻接表
    const nextMap: Record<string, string[]> = {}
    const edgeProps: Record<string, Record<string, string>> = {}
    edges.forEach((e: any) => {
        const s = e.sourceNodeId, t = e.targetNodeId
        if (!nextMap[s]) nextMap[s] = []
        nextMap[s].push(t)
        edgeProps[s + '->' + t] = e.properties || {}
    })

    const nodeMap: Record<string, any> = {}
    nodes.forEach((n: any) => { nodeMap[n.id] = n })

    // 找起始节点
    const startNode = nodes.find((n: any) => (n.type || '').includes('start'))
    if (!startNode) return defaultNodeConfig()

    // 递归构建链
    const buildChain = (nodeId: string): any => {
        const raw = nodeMap[nodeId]
        if (!raw) return null
        const type = raw.type || ''

        if (type.includes('end')) return null
        if (type.includes('task')) {
            const p = raw.properties || {}
            return {
                nodeName: p.name || raw.text?.value || '审批人',
                error: false,
                type: 1,
                settype: p.approver_type === 'dept_leader' ? 2 : p.approver_type === 'initiator' ? 5 : 1,
                selectMode: 0, selectRange: 0, directorLevel: 1,
                examineMode: p.perform_type === 'ALL' ? 2 : 1,
                noHanderAction: 1, examineEndDirectorLevel: 0,
                nodeUserList: (p.approver_ids || '').split(',').filter(Boolean).map((id: string, idx: number) => ({
                    type: 1, targetId: parseInt(id), name: (p.approver_names || '').split(',')[idx] || '',
                })),
                childNode: nextMap[nodeId]?.[0] ? buildChain(nextMap[nodeId][0]) : null,
            }
        }
        if (type.includes('condition')) {
            const branches = nextMap[nodeId] || []
            return {
                nodeName: raw.text?.value || '条件分支',
                type: 4,
                childNode: null,
                conditionNodes: branches.map((bid: string, idx: number) => ({
                    nodeName: nodeMap[bid]?.text?.value || '条件' + (idx + 1),
                    error: false,
                    type: 3,
                    priorityLevel: idx + 1,
                    conditionList: [],
                    nodeUserList: [],
                    childNode: nextMap[bid]?.[0] ? buildChain(nextMap[bid][0]) : null,
                })),
            }
        }
        // 其他中间节点 → 递归
        return nextMap[nodeId]?.[0] ? buildChain(nextMap[nodeId][0]) : null
    }

    // 从 start 的下一个节点开始
    const startNext = nextMap[startNode.id]?.[0]
    const childChain = startNext ? buildChain(startNext) : null

    return {
        nodeName: '发起人',
        type: 0,
        nodeUserList: [],
        childNode: childChain,
    }
}

/* ─── 错误校验 ─── */
const reErr = ({ childNode }: any) => {
    if (childNode) {
        let { type, error, nodeName, conditionNodes } = childNode
        if (type == 1 || type == 2) {
            if (error) tipList.value.push({ name: nodeName, type: ['', '审核人', '抄送人'][type] })
            reErr(childNode)
        } else if (type == 4) {
            reErr(childNode)
            for (let i = 0; i < (conditionNodes || []).length; i++) {
                if (conditionNodes[i].error) tipList.value.push({ name: conditionNodes[i].nodeName, type: '条件' })
                reErr(conditionNodes[i])
            }
        }
    }
}

/* ─── 保存 ─── */
const onSave = async () => {
    setIsTried(true)
    tipList.value = []
    reErr(nodeConfig.value)
    if (tipList.value.length != 0) {
        tipVisible.value = true
        return
    }

    saving.value = true
    try {
        const graph = treeToGraph(nodeConfig.value)
        await saveGraph({
            id: props.definitionId,
            graph,
            flowPermission: flowPermission.value,
        })
        ElMessage.success('保存成功')
        emit('saved')
    } catch (e: any) {
        ElMessage.error(e.message || '保存失败')
    } finally {
        saving.value = false
    }
}

/* ─── 缩放 ─── */
const zoomSize = (type: number) => {
    if (type == 1) {
        if (nowVal.value == 50) return
        nowVal.value -= 10
    } else {
        if (nowVal.value == 300) return
        nowVal.value += 10
    }
}

onMounted(() => {
    loadGraph()
})
</script>

<style scoped>
.wf-zoom {
    position: absolute;
    right: 40px;
    top: 15px;
    z-index: 10;
}
</style>
