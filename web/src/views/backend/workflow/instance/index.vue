<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <TableHeader
            :buttons="['refresh', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('workflow.instance.title') })"
        />

        <Table />

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="流程详情" width="800px" :destroy-on-close="true">
            <div v-loading="detailLoading">
                <template v-if="detailData">
                    <el-descriptions :column="2" border>
                        <el-descriptions-item label="实例标题">{{ detailData.instance.title }}</el-descriptions-item>
                        <el-descriptions-item label="业务类型">{{ detailData.instance.business_type }}</el-descriptions-item>
                        <el-descriptions-item label="发起人">{{ detailData.instance.initiator_name }}</el-descriptions-item>
                        <el-descriptions-item label="状态">
                            <el-tag :type="statusTagType(detailData.instance.status)">
                                {{ statusText(detailData.instance.status) }}
                            </el-tag>
                        </el-descriptions-item>
                        <el-descriptions-item label="创建时间">{{ formatTime(detailData.instance.create_time) }}</el-descriptions-item>
                        <el-descriptions-item label="更新时间">{{ formatTime(detailData.instance.update_time) }}</el-descriptions-item>
                    </el-descriptions>

                    <!-- 审批历史 -->
                    <h3 style="margin: 20px 0 12px">审批历史</h3>
                    <el-timeline>
                        <el-timeline-item
                            v-for="log in detailData.logs"
                            :key="log.id"
                            :timestamp="formatTime(log.create_time)"
                            :type="actionTimelineType(log.action)"
                            placement="top"
                        >
                            <el-card>
                                <p><strong>{{ log.operator_name }}</strong> - {{ actionText(log.action) }}</p>
                                <p v-if="log.comment" style="color: var(--el-text-color-secondary)">{{ log.comment }}</p>
                            </el-card>
                        </el-timeline-item>
                    </el-timeline>

                    <!-- 任务列表 -->
                    <h3 style="margin: 20px 0 12px">任务列表</h3>
                    <el-table :data="detailData.tasks" size="small">
                        <el-table-column prop="node_name" label="节点" width="150" />
                        <el-table-column prop="assignee_name" label="审批人" width="120" />
                        <el-table-column prop="status" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="taskStatusType(row.status)" size="small">{{ taskStatusText(row.status) }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="approver_name" label="实际审批人" width="120" />
                        <el-table-column prop="comment" label="意见" show-overflow-tooltip />
                        <el-table-column prop="create_time" label="创建时间" width="160">
                            <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
                        </el-table-column>
                    </el-table>
                </template>
            </div>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, provide } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import baTableClass from '/@/utils/baTable'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { baTableApi } from '/@/api/common'
import { detail as getDetail, cancel as cancelInstance } from '/@/api/backend/workflow/instance'

defineOptions({
    name: 'workflow/instance',
})

const { t } = useI18n()

const detailVisible = ref(false)
const detailLoading = ref(false)
const detailData = ref<any>(null)

const optButtons: any[] = [
    {
        render: 'tipButton',
        name: 'detail',
        title: '详情',
        text: '',
        type: 'primary',
        icon: 'fa fa-eye',
        class: 'table-row-detail',
        disabledTip: false,
        click: async (row: any) => {
            detailLoading.value = true
            detailVisible.value = true
            try {
                const res = await getDetail(row.id)
                detailData.value = res.data?.data || res.data
            } catch (e: any) {
                ElMessage.error(e.message || '加载失败')
            } finally {
                detailLoading.value = false
            }
        },
    },
    {
        render: 'tipButton',
        name: 'cancel',
        title: '撤回',
        text: '',
        type: 'warning',
        icon: 'fa fa-undo',
        class: 'table-row-cancel',
        disabledTip: false,
        display: (row: any) => row.status === 'running',
        click: (row: any) => {
            ElMessageBox.prompt('请输入撤回原因', '撤回流程', { type: 'warning' })
                .then(async ({ value }) => {
                    await cancelInstance(row.id, value || '')
                    baTable.onTableHeaderAction('refresh', {})
                })
                .catch(() => {})
        },
    },
]

const baTable = new baTableClass(
    new baTableApi('/admin/workflow.Instance/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('Id'), prop: 'id', align: 'center', operator: '=', width: 70 },
            { label: t('workflow.instance.title'), prop: 'title', align: 'center', operator: 'LIKE', operatorPlaceholder: t('Fuzzy query') },
            { label: t('workflow.instance.business_type'), prop: 'business_type', align: 'center', operator: '=' },
            { label: t('workflow.instance.initiator'), prop: 'initiator_name', align: 'center', operator: false },
            {
                label: t('workflow.instance.status'),
                prop: 'status',
                align: 'center',
                render: 'tag',
                width: 100,
                custom: { running: 'primary', approved: 'success', rejected: 'danger', cancelled: 'info', timeout: 'warning' },
                replaceValue: {
                    running: '审批中',
                    approved: '已通过',
                    rejected: '已驳回',
                    cancelled: '已撤回',
                    timeout: '已超时',
                },
            },
            { label: t('Create time'), prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            {
                label: t('Operate'),
                align: 'center',
                width: '180',
                render: 'buttons',
                buttons: optButtons,
                operator: false,
            },
        ],
    },
    {}
)

provide('baTable', baTable)

const statusTagType = (status: string) => {
    const map: Record<string, string> = {
        running: 'primary',
        approved: 'success',
        rejected: 'danger',
        cancelled: 'info',
        timeout: 'warning',
    }
    return map[status] || 'info'
}

const statusText = (status: string) => {
    const map: Record<string, string> = {
        running: '审批中',
        approved: '已通过',
        rejected: '已驳回',
        cancelled: '已撤回',
        timeout: '已超时',
    }
    return map[status] || status
}

const actionTimelineType = (action: string) => {
    const map: Record<string, string> = {
        start: 'primary',
        approve: 'success',
        reject: 'danger',
        back: 'warning',
        transfer: 'info',
        cancel: 'info',
    }
    return map[action] || 'info'
}

const actionText = (action: string) => {
    const map: Record<string, string> = {
        start: '发起流程',
        approve: '审批通过',
        reject: '驳回',
        back: '退回',
        transfer: '转办',
        cc: '抄送',
        cancel: '撤回',
    }
    return map[action] || action
}

const taskStatusType = (status: string) => {
    const map: Record<string, string> = {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        transferred: 'info',
        cancelled: 'info',
    }
    return map[status] || 'info'
}

const taskStatusText = (status: string) => {
    const map: Record<string, string> = {
        pending: '待审批',
        approved: '已通过',
        rejected: '已驳回',
        transferred: '已转办',
        cancelled: '已取消',
    }
    return map[status] || status
}

const formatTime = (ts: number) => {
    if (!ts) return '-'
    const d = new Date(ts * 1000)
    return d.toLocaleString('zh-CN', { hour12: false })
}

baTable.mount()
baTable.getData()
</script>

<style scoped lang="scss"></style>
