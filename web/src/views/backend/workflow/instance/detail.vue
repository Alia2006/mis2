<template>
    <div class="default-main">
        <el-page-header @back="goBack" title="返回" content="流程实例详情" class="mb16" />
        <div v-loading="loading">
            <template v-if="data">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="实例标题">{{ data.instance.title }}</el-descriptions-item>
                    <el-descriptions-item label="业务类型">{{ data.instance.business_type }}</el-descriptions-item>
                    <el-descriptions-item label="发起人">{{ data.instance.initiator_name }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="statusType(data.instance.status)">{{ statusLabel(data.instance.status) }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="创建时间">{{ fmt(data.instance.create_time) }}</el-descriptions-item>
                    <el-descriptions-item label="更新时间">{{ fmt(data.instance.update_time) }}</el-descriptions-item>
                </el-descriptions>

                <el-card class="mt16" header="审批历史">
                    <el-timeline>
                        <el-timeline-item v-for="log in data.logs" :key="log.id" :timestamp="fmt(log.create_time)" placement="top">
                            <strong>{{ log.operator_name }}</strong> {{ actionLabel(log.action) }}
                            <span v-if="log.comment" style="color: var(--el-text-color-secondary)"> - {{ log.comment }}</span>
                        </el-timeline-item>
                    </el-timeline>
                </el-card>

                <el-card class="mt16" header="任务列表">
                    <el-table :data="data.tasks" size="small">
                        <el-table-column prop="node_name" label="节点" width="150" />
                        <el-table-column prop="assignee_name" label="审批人" width="120" />
                        <el-table-column prop="status" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag size="small" :type="taskType(row.status)">{{ taskLabel(row.status) }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="approver_name" label="实际审批人" width="120" />
                        <el-table-column prop="comment" label="意见" show-overflow-tooltip />
                    </el-table>
                </el-card>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { detail as getDetail } from '/@/api/backend/workflow/instance'

defineOptions({
    name: 'workflow/instance/detail',
})

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const data = ref<any>(null)

const statusType = (s: string) => ({ running: 'primary', approved: 'success', rejected: 'danger', cancelled: 'info', timeout: 'warning' }[s] || 'info')
const statusLabel = (s: string) => ({ running: '审批中', approved: '已通过', rejected: '已驳回', cancelled: '已撤回', timeout: '已超时' }[s] || s)
const actionLabel = (a: string) => ({ start: '发起流程', approve: '审批通过', reject: '驳回', back: '退回', transfer: '转办', cancel: '撤回' }[a] || a)
const taskType = (s: string) => ({ pending: 'warning', approved: 'success', rejected: 'danger', transferred: 'info', cancelled: 'info' }[s] || 'info')
const taskLabel = (s: string) => ({ pending: '待审批', approved: '已通过', rejected: '已驳回', transferred: '已转办', cancelled: '已取消' }[s] || s)
const fmt = (ts: number) => ts ? new Date(ts * 1000).toLocaleString('zh-CN', { hour12: false }) : '-'

const goBack = () => router.back()

onMounted(async () => {
    const id = Number(route.query.id || 0)
    if (!id) return
    loading.value = true
    try {
        const res = await getDetail(id)
        data.value = res.data
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.mb16 { margin-bottom: 16px; }
.mt16 { margin-top: 16px; }
</style>
