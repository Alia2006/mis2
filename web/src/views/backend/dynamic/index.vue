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
    </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted, onActivated, useTemplateRef, nextTick, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PopupForm from './popupForm.vue'
import { baTableApi } from '/@/api/common'
import createAxios from '/@/utils/axios'
import { defaultOptButtons } from '/@/components/table'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import baTableClass from '/@/utils/baTable'
import { getDynamicConfig } from '/@/api/backend/dynamic'
import type { DynamicTableConfig } from './types'

defineOptions({
    name: 'dynamic/index',
})

const { t } = useI18n()
const route = useRoute()
const tableRef = useTemplateRef('tableRef')

const configReady = ref(false)
const config = ref<DynamicTableConfig>()

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
        const operateColumn = {
            label: t('Operate'),
            align: 'center',
            width: 130,
            render: 'buttons',
            buttons: defaultOptButtons(cfg.rowButtonNames),
            operator: false,
        }

        // 直接更新 baTable 实例属性（保持 provide 引用和响应性）
        baTable.api = new DynamicTableApi(cfg.controllerUrl, cfg.controllerParams || {})
        baTable.table.pk = cfg.pk
        baTable.table.column = [selectionColumn, ...cfg.columns, operateColumn] as any
        baTable.table.defaultOrder = cfg.defaultOrder
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
