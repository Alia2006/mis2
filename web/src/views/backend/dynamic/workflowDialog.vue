<template>
    <el-dialog
        v-model="show"
        :title="t('dynamic.workflow.title')"
        width="700px"
        :close-on-click-modal="false"
        destroy-on-close
    >
        <div v-loading="loading">
            <template v-if="instance">
                <!-- 实例概要 -->
                <el-descriptions :column="2" border size="small" class="mb-4">
                    <el-descriptions-item :label="t('dynamic.workflow.instance_title')">
                        {{ instance.title }}
                    </el-descriptions-item>
                    <el-descriptions-item :label="t('dynamic.workflow.status')">
                        <el-tag :type="statusTagType(instance.status)" size="small">
                            {{ statusLabel(instance.status) }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item :label="t('dynamic.workflow.initiator')">
                        {{ instance.initiator_name }}
                    </el-descriptions-item>
                    <el-descriptions-item :label="t('dynamic.workflow.current_node')">
                        {{ instance.current_node_key }}
                    </el-descriptions-item>
                </el-descriptions>

                <!-- 审批任务列表 -->
                <el-table :data="tasks" size="small" border class="mb-4">
                    <el-table-column prop="node_name" :label="t('dynamic.workflow.node')" width="140" />
                    <el-table-column prop="assignee_name" :label="t('dynamic.workflow.assignee')" width="120" />
                    <el-table-column prop="status" :label="t('dynamic.workflow.task_status')" width="90">
                        <template #default="{ row }">
                            <el-tag :type="taskStatusTagType(row.status)" size="small">
                                {{ taskStatusLabel(row.status) }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column prop="comment" :label="t('dynamic.workflow.comment')" show-overflow-tooltip />
                    <el-table-column prop="update_time" :label="t('dynamic.workflow.time')" width="160" />
                </el-table>

                <!-- 操作时间线 -->
                <el-timeline>
                    <el-timeline-item
                        v-for="log in logs"
                        :key="log.id"
                        :type="logIconType(log.action)"
                        :timestamp="log.create_time"
                        placement="top"
                    >
                        <div class="timeline-content">
                            <span class="timeline-action">{{ actionLabel(log.action) }}</span>
                            <span class="timeline-operator">{{ log.operator_name }}</span>
                            <span class="timeline-comment" v-if="log.comment">: {{ log.comment }}</span>
                        </div>
                    </el-timeline-item>
                </el-timeline>

                <!-- 撤回按钮（发起人 + 进行中） -->
                <div v-if="canCancel" class="dialog-footer-tip">
                    <el-popconfirm
                        :title="t('dynamic.workflow.confirm_cancel')"
                        @confirm="onCancel"
                        width="240"
                    >
                        <template #reference>
                            <el-button type="danger" plain size="small">
                                {{ t('dynamic.workflow.cancel') }}
                            </el-button>
                        </template>
                    </el-popconfirm>
                </div>
            </template>
            <el-empty v-else :description="t('dynamic.workflow.no_instance')" />
        </div>

        <template #footer>
            <el-button @click="show = false">{{ t('Close') }}</el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUserInfo } from '/@/stores/userInfo'
import { getWorkflowDetail, cancelWorkflow } from '/@/api/backend/dynamic'

defineOptions({
    name: 'dynamic/workflowDialog',
})

const { t } = useI18n()
const userInfo = useUserInfo()

const show = ref(false)
const loading = ref(false)
const tableName = ref('')
const rowId = ref<number | string>(0)
const moduleCode = ref('')

const instance = ref<any>(null)
const tasks = ref<any[]>([])
const logs = ref<any[]>([])

/** 发起人可撤回 */
const canCancel = computed(() => {
    if (!instance.value || instance.value.status !== 'running') return false
    return instance.value.initiator_id === userInfo.userInfo.id
})

/** 打开弹窗 */
const open = (table: string, id: number | string, code: string) => {
    tableName.value = table
    rowId.value = id
    moduleCode.value = code
    show.value = true
    loadData()
}

const loadData = async () => {
    loading.value = true
    try {
        const res = await getWorkflowDetail(tableName.value, rowId.value)
        const data = res.data?.data ?? res.data
        instance.value = data.instance
        tasks.value = data.tasks || []
        logs.value = data.logs || []
    } catch (err) {
        console.error('Failed to load workflow detail:', err)
    } finally {
        loading.value = false
    }
}

const onCancel = async () => {
    try {
        await cancelWorkflow(tableName.value, rowId.value)
        await loadData()
    } catch (err) {
        console.error('Failed to cancel workflow:', err)
    }
}

/* ─── 标签/颜色映射 ─── */

const statusLabel = (s: string) => ({
    running: t('dynamic.workflow.status_running'),
    approved: t('dynamic.workflow.status_approved'),
    rejected: t('dynamic.workflow.status_rejected'),
    cancelled: t('dynamic.workflow.status_cancelled'),
}[s] || s)

const statusTagType = (s: string): '' | 'success' | 'warning' | 'danger' | 'info' => ({
    running: 'warning',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'info',
}[s] as any || '')

const taskStatusLabel = (s: string) => ({
    pending: t('dynamic.workflow.task_pending'),
    approved: t('dynamic.workflow.task_approved'),
    rejected: t('dynamic.workflow.task_rejected'),
    cancelled: t('dynamic.workflow.task_cancelled'),
    transferred: t('dynamic.workflow.task_transferred'),
}[s] || s)

const taskStatusTagType = (s: string): '' | 'success' | 'warning' | 'danger' | 'info' => ({
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'info',
    transferred: 'info',
}[s] as any || '')

const actionLabel = (a: string) => ({
    start: t('dynamic.workflow.action_start'),
    approve: t('dynamic.workflow.action_approve'),
    reject: t('dynamic.workflow.action_reject'),
    back: t('dynamic.workflow.action_back'),
    transfer: t('dynamic.workflow.action_transfer'),
    cancel: t('dynamic.workflow.action_cancel'),
}[a] || a)

const logIconType = (a: string): '' | 'primary' | 'success' | 'warning' | 'danger' => ({
    start: 'primary',
    approve: 'success',
    reject: 'danger',
    back: 'warning',
    transfer: 'primary',
    cancel: 'danger',
}[a] as any || '')

defineExpose({ open })
</script>

<style scoped lang="scss">
.timeline-content {
    font-size: 13px;
    .timeline-action {
        font-weight: 600;
        margin-right: 6px;
    }
    .timeline-operator {
        color: var(--el-text-color-secondary);
    }
    .timeline-comment {
        color: var(--el-text-color-regular);
    }
}
.dialog-footer-tip {
    margin-top: 12px;
    text-align: right;
}
.mb-4 {
    margin-bottom: 16px;
}
</style>
