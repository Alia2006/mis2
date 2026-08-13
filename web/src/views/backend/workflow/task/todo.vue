<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <TableHeader
            :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('workflow.task.node_name') })"
        />

        <Table />

        <!-- 审批弹窗 -->
        <el-dialog v-model="approvalVisible" title="审批" width="500px" :close-on-click-modal="false">
            <div v-loading="approvalLoading">
                <el-descriptions :column="1" border class="mb16">
                    <el-descriptions-item label="实例标题">{{ currentTask?.instance?.title || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="当前节点">{{ currentTask?.node_name || '-' }}</el-descriptions-item>
                </el-descriptions>
                <el-form>
                    <el-form-item label="审批意见">
                        <el-input v-model="approvalComment" type="textarea" :rows="3" placeholder="请输入审批意见" />
                    </el-form-item>
                </el-form>
            </div>
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="approvalVisible = false">取消</el-button>
                    <el-button v-if="currentTask?.allow_back" @click="doBack" :loading="actionLoading">退回</el-button>
                    <el-button type="danger" @click="doReject" :loading="actionLoading">驳回</el-button>
                    <el-button type="primary" @click="doApprove" :loading="actionLoading">通过</el-button>
                </div>
            </template>
        </el-dialog>

        <!-- 转办弹窗 -->
        <el-dialog v-model="transferVisible" title="转办" width="500px" :close-on-click-modal="false">
            <el-form>
                <el-form-item label="转办给">
                    <el-select v-model="transferToId" filterable placeholder="请选择转办人" style="width: 100%">
                        <el-option v-for="a in transferAdmins" :key="a.id" :label="a.nickname" :value="a.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="转办说明">
                    <el-input v-model="transferComment" type="textarea" :rows="2" placeholder="请输入转办说明" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="transferVisible = false">取消</el-button>
                <el-button type="primary" @click="doTransfer" :loading="actionLoading">确定转办</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import baTableClass from '/@/utils/baTable'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { baTableApi } from '/@/api/common'
import { approve, reject, back, transfer } from '/@/api/backend/workflow/task'
import { getAdmins } from '/@/api/backend/workflow/definition'

defineOptions({
    name: 'workflow/task',
})

const { t } = useI18n()

// 审批弹窗状态
const approvalVisible = ref(false)
const approvalLoading = ref(false)
const approvalComment = ref('')
const currentTask = ref<any>(null)
const actionLoading = ref(false)

// 转办弹窗状态
const transferVisible = ref(false)
const transferToId = ref(0)
const transferComment = ref('')
const transferAdmins = ref<any[]>([])

const optButtons: any[] = [
    {
        render: 'tipButton',
        name: 'approve',
        title: '审批',
        text: '',
        type: 'primary',
        icon: 'fa fa-check',
        class: 'table-row-approve',
        disabledTip: false,
        click: ({ row }: { row: any }) => {
            currentTask.value = row
            approvalComment.value = ''
            approvalVisible.value = true
        },
    },
    {
        render: 'tipButton',
        name: 'transfer',
        title: '转办',
        text: '',
        type: 'info',
        icon: 'fa fa-share',
        class: 'table-row-transfer',
        disabledTip: false,
        click: async ({ row }: { row: any }) => {
            currentTask.value = row
            transferToId.value = 0
            transferComment.value = ''
            transferVisible.value = true
        },
    },
]

const taskApi = new baTableApi('/admin/workflow.Task/')
taskApi.actionUrl.set('index', '/admin/workflow.Task/myTodo')

const baTable = new baTableClass(
    taskApi,
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('Id'), prop: 'id', align: 'center', operator: '=', width: 70 },
            { label: '实例标题', prop: 'instance_title', align: 'center', operator: 'LIKE', width: 150 },
            { label: t('workflow.task.node_name'), prop: 'node_name', align: 'center', operator: 'LIKE' },
            { label: t('workflow.task.assignee'), prop: 'assignee_name', align: 'center', operator: false },
            { label: t('Create time'), prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            {
                label: t('Operate'),
                align: 'center',
                width: '130',
                render: 'buttons',
                buttons: optButtons,
                operator: false,
            },
        ],
    },
    {}
)

provide('baTable', baTable)

const doApprove = async () => {
    actionLoading.value = true
    try {
        await approve(currentTask.value.id, approvalComment.value)
        approvalVisible.value = false
        baTable.onTableHeaderAction('refresh', {})
    } catch (e: any) {
        ElMessage.error(e.message || '操作失败')
    } finally {
        actionLoading.value = false
    }
}

const doReject = async () => {
    actionLoading.value = true
    try {
        await reject(currentTask.value.id, approvalComment.value)
        approvalVisible.value = false
        baTable.onTableHeaderAction('refresh', {})
    } catch (e: any) {
        ElMessage.error(e.message || '操作失败')
    } finally {
        actionLoading.value = false
    }
}

const doBack = async () => {
    actionLoading.value = true
    try {
        await back(currentTask.value.id, approvalComment.value)
        approvalVisible.value = false
        baTable.onTableHeaderAction('refresh', {})
    } catch (e: any) {
        ElMessage.error(e.message || '操作失败')
    } finally {
        actionLoading.value = false
    }
}

const doTransfer = async () => {
    if (!transferToId.value) {
        ElMessage.warning('请选择转办人')
        return
    }
    actionLoading.value = true
    try {
        await transfer(currentTask.value.id, transferToId.value, transferComment.value)
        transferVisible.value = false
        baTable.onTableHeaderAction('refresh', {})
    } catch (e: any) {
        ElMessage.error(e.message || '操作失败')
    } finally {
        actionLoading.value = false
    }
}

onMounted(async () => {
    try {
        const res = await getAdmins()
        transferAdmins.value = res.data.list || []
    } catch (e) {
        // ignore
    }
})

baTable.mount()
baTable.getData()
</script>

<style scoped lang="scss">
.mb16 {
    margin-bottom: 16px;
}
.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
</style>
