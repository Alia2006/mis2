<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <TableHeader
            v-if="configReady"
            :buttons="config!.headerButtons"
            :quick-search-placeholder="t('Quick search placeholder', { fields: config!.quickSearchPlaceholder })"
        />

        <Table ref="tableRef"></Table>

        <PopupForm :fields="config?.formFields ?? []" />

        <!-- 详情抽屉（从下往上滑出，显示关联详情表） -->
        <DetailDrawer v-if="hasDetail" />

        <!-- 工作流审批进度弹窗 -->
        <WorkflowDialog v-if="hasWorkflow" ref="workflowDialogRef" />
    </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted, onActivated, useTemplateRef, nextTick, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PopupForm from './popupForm.vue'
import DetailDrawer from './detailDrawer.vue'
import WorkflowDialog from './workflowDialog.vue'
import { baTableApi } from '/@/api/common'
import createAxios from '/@/utils/axios'
import { defaultOptButtons } from '/@/components/table'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import baTableClass from '/@/utils/baTable'
import { getDynamicConfig, startWorkflow } from '/@/api/backend/dynamic'
import type { DynamicTableConfig } from './types'
import { ElMessageBox } from 'element-plus'

defineOptions({
    name: 'dynamic/index',
})

const { t } = useI18n()
const route = useRoute()
const tableRef = useTemplateRef('tableRef')

const configReady = ref(false)
const config = ref<DynamicTableConfig>()
const hasDetail = computed(() => !!config.value?.detail)
const hasWorkflow = computed(() => !!config.value?.workflow)
const workflowDialogRef = ref()

/**
 * 自定义 API 类：在标准 baTableApi 基础上自动附加 table 参数
 */
class DynamicTableApi extends baTableApi {
    private tableParam: Record<string, any>

    constructor(controllerUrl: string, tableParam: Record<string, any>) {
        super(controllerUrl)
        this.tableParam = tableParam
    }

    index(filter: any = {}) {
        return createAxios({
            url: this.actionUrl.get('index'),
            method: 'get',
            params: { ...this.tableParam, ...filter },
        })
    }

    edit(params: anyObj) {
        return createAxios({
            url: this.actionUrl.get('edit'),
            method: 'get',
            params: { ...this.tableParam, ...params },
        })
    }

    del(ids: string[]) {
        return createAxios({
            url: this.actionUrl.get('del'),
            method: 'DELETE',
            data: { ...this.tableParam, ids },
        })
    }

    add(data: anyObj) {
        return createAxios({
            url: this.actionUrl.get('add'),
            method: 'post',
            data: { ...this.tableParam, ...data },
        })
    }

    postData(action: string, data: anyObj) {
        return createAxios(
            {
                url: this.actionUrl.has(action) ? this.actionUrl.get(action) : this.controllerUrl + action,
                method: 'post',
                data: { ...this.tableParam, ...data },
            },
            {
                showSuccessMessage: true,
            }
        )
    }

    sortable(data: anyObj) {
        return createAxios({
            url: this.actionUrl.get('sortable'),
            method: 'post',
            data: { ...this.tableParam, ...data },
        })
    }
}

// 从路由路径提取表名
const getTableName = (): string => {
    if (route.params.tableName) return route.params.tableName as string
    const segments = route.path.split('/').filter(Boolean)
    return segments[segments.length - 1] || ''
}

// 创建占位 baTable（provide 必须在 setup 同步调用）
const baTable = new baTableClass(new baTableApi('/admin/dynamic.Table/'), {
    pk: 'id',
    column: [],
    dblClickNotEditColumn: [undefined],
})
provide('baTable', baTable)

/**
 * 加载远程配置并更新 baTable
 */
const loadConfig = async () => {
    const tableName = getTableName()
    if (!tableName) return

    configReady.value = false

    try {
        const res = await getDynamicConfig(tableName)
        const cfg = (res.data?.data ?? res.data) as DynamicTableConfig
        config.value = cfg

        // 构造列
        const selectionColumn = { type: 'selection', align: 'center', operator: false }

        // 行操作按钮（排除 detail，detail 由配置驱动单独注入）
        const rowBtnNames = (cfg.rowButtonNames || []).filter((n) => n !== 'detail')
        const optBtns = defaultOptButtons(rowBtnNames)

        // 详情按钮：配置了详情表时自动添加
        if (cfg.detail) {
            optBtns.unshift({
                render: 'tipButton',
                name: 'detail',
                title: t('dynamic.detail.btn_title'),
                text: '',
                type: 'info',
                icon: 'fa fa-list',
                class: 'table-row-detail',
                disabledTip: false,
                click: (row: TableRow) => {
                    baTable.table.extend = baTable.table.extend || {}
                    baTable.table.extend.showDetail = true
                    baTable.table.extend.detailRowId = row[cfg.pk!]
                    baTable.table.extend.detailConfig = cfg.detail!
                },
            })
        }

        // 工作流按钮：配置了工作流绑定时自动添加
        if (cfg.workflow) {
            const moduleCode = cfg.workflow.moduleCode
            const tableName = cfg.name

            // 审批进度按钮
            optBtns.unshift({
                render: 'tipButton',
                name: 'workflowProgress',
                title: t('dynamic.workflow.btn_progress'),
                text: '',
                type: 'primary',
                icon: 'fa fa-sitemap',
                class: 'table-row-workflow',
                disabledTip: false,
                click: (row: TableRow) => {
                    const wf = (row as any).__workflow
                    if (!wf) {
                        // 无审批记录 → 直接发起
                        onStartWorkflow(row, tableName, moduleCode)
                    } else {
                        // 有审批记录 → 打开详情
                        workflowDialogRef.value?.open(tableName, row[cfg.pk!], moduleCode)
                    }
                },
            })

            // 提交审批按钮
            optBtns.unshift({
                render: 'tipButton',
                name: 'workflowStart',
                title: t('dynamic.workflow.btn_start'),
                text: '',
                type: 'success',
                icon: 'fa fa-paper-plane',
                class: 'table-row-workflow-start',
                disabledTip: false,
                click: (row: TableRow) => {
                    onStartWorkflow(row, tableName, moduleCode)
                },
            })
        }

        const operateColumn = {
            label: t('Operate'),
            align: 'center',
            width: optBtns.length > 2 ? 170 : 130,
            fixed: 'right',
            render: 'buttons',
            buttons: optBtns,
            operator: false,
        }

        // 直接更新 baTable 实例属性（保持 provide 引用和响应性）
        baTable.api = new DynamicTableApi(cfg.controllerUrl, cfg.controllerParams || {})
        baTable.table.pk = cfg.pk
        baTable.table.column = [selectionColumn, ...cfg.columns, operateColumn] as any

        // 工作流状态列：配置了工作流时在操作列前插入
        if (cfg.workflow) {
            const wfColumn = {
                label: t('dynamic.workflow.col_status'),
                prop: '__workflow.status',
                align: 'center',
                width: 100,
                render: 'workflowStatus' as any,
                operator: false,
                sortable: false,
            }
            // 插入到操作列之前
            const cols = baTable.table.column as any[]
            cols.splice(cols.length - 1, 0, wfColumn)
        }

        baTable.table.defaultOrder = cfg.defaultOrder
        baTable.table.dblClickNotEditColumn = cfg.dblClickNotEditColumn || [undefined]
        baTable.form.defaultItems = cfg.defaultItems || {}

        configReady.value = true

        await nextTick()
        baTable.table.ref = tableRef.value
        // 重新初始化公共搜索（列已更新）
        baTable.comSearch.fieldData.clear()
        Object.keys(baTable.comSearch.form).forEach((k) => delete baTable.comSearch.form[k])
        // mount() 在 await 之后调用，useRoute() 可能失效，预先设置 routePath
        baTable.table.routePath = route.fullPath
        baTable.mount()
        // mount() 内部可能无法读取 route.query，此处补充处理
        if (baTable.table.acceptQuery && Object.keys(route.query).length > 0) {
            baTable.setComSearchData(route.query)
            baTable.setFilterSearchData(baTable.getComSearchData(), 'merge')
        }
        baTable.getData()
    } catch (err) {
        console.error('Failed to load dynamic table config:', err)
    }
}

/**
 * 发起审批
 */
const onStartWorkflow = async (row: TableRow, tableName: string, moduleCode: string) => {
    const existing = (row as any).__workflow
    if (existing && existing.status === 'running') {
        // 已有进行中流程 → 打开详情
        workflowDialogRef.value?.open(tableName, row[config.value!.pk!], moduleCode)
        return
    }
    if (existing && existing.status === 'approved') {
        return
    }

    try {
        await ElMessageBox.confirm(
            t('dynamic.workflow.confirm_start'),
            t('dynamic.workflow.btn_start'),
            { type: 'info' }
        )
        await startWorkflow(tableName, row[config.value!.pk!])
        baTable.getData()
    } catch {
        // 用户取消
    }
}

// 同组件不同表切换时重新加载
watch(
    () => route.fullPath,
    (newPath, oldPath) => {
        if (newPath !== oldPath && route.path.includes('/dynamic/')) {
            loadConfig()
        }
    }
)

onMounted(() => {
    loadConfig()
})

// keep-alive 重新激活时：如果配置已加载，刷新数据
onActivated(() => {
    if (configReady.value && baTable.table.ref) {
        baTable.getData()
    }
})
</script>

<style scoped lang="scss"></style>
