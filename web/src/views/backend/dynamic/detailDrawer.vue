<template>
    <el-drawer
        v-model="showDrawer"
        :title="drawerTitle"
        direction="btt"
        size="65%"
        destroy-on-close
        @closed="onClosed"
    >
        <div v-if="configReady && detailConfig" class="detail-drawer-content">
            <el-alert
                v-if="detailConfig.remark"
                class="ba-table-alert"
                :title="detailConfig.remark"
                type="info"
                show-icon
            />
            <TableHeader
                :buttons="detailConfig.headerButtons"
                :quick-search-placeholder="t('Quick search placeholder', { fields: detailConfig.quickSearchPlaceholder })"
            />
            <Table ref="detailTableRef" />
            <PopupForm :fields="detailConfig.formFields ?? []" />
        </div>
        <div v-else class="detail-loading">
            <el-icon class="is-loading" :size="24"><Loading /></el-icon>
            <span style="margin-left: 8px">{{ t('dynamic.detail.loading') }}</span>
        </div>
    </el-drawer>
</template>

<script setup lang="ts">
import { ref, computed, watch, provide, inject, useTemplateRef, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { Loading } from '@element-plus/icons-vue'
import PopupForm from './popupForm.vue'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import { defaultOptButtons } from '/@/components/table'
import baTableClass from '/@/utils/baTable'
import { baTableApi } from '/@/api/common'
import createAxios from '/@/utils/axios'
import { getDynamicConfigById } from '/@/api/backend/dynamic'
import type { DynamicTableConfig, DetailTableConfig } from './types'

defineOptions({
    name: 'dynamic/detailDrawer',
})

const { t } = useI18n()

/* ─── 读取父表 baTable（用于 extend 状态通信） ─── */
const parentBaTable = inject('baTable') as baTableClass

/* ─── 详情表 baTable（提供给子组件） ─── */
const detailBaTable = new baTableClass(new baTableApi('/admin/dynamic.Table/'), {
    pk: 'id',
    column: [],
    dblClickNotEditColumn: [undefined],
})
provide('baTable', detailBaTable)

const detailTableRef = useTemplateRef('detailTableRef')
const configReady = ref(false)
const loading = ref(false)
const detailConfig = ref<DynamicTableConfig>()

/* ─── Drawer 显示状态（与 parentBaTable.table.extend 双向绑定） ─── */
const showDrawer = computed({
    get: () => !!parentBaTable.table.extend?.showDetail,
    set: (val: boolean) => {
        if (parentBaTable.table.extend) {
            parentBaTable.table.extend.showDetail = val
        }
    },
})

const drawerTitle = computed(() => {
    if (!detailConfig.value) return t('dynamic.detail.title')
    return `${detailConfig.value.title} - ${t('dynamic.detail.title')}`
})

/**
 * 自定义 API 类：附加 table、detail_filter_key、detail_filter_value 参数
 */
class DetailTableApi extends baTableApi {
    private fixedParams: Record<string, any>

    constructor(controllerUrl: string, fixedParams: Record<string, any>) {
        super(controllerUrl)
        this.fixedParams = fixedParams
    }

    index(filter: any = {}) {
        return createAxios({
            url: this.actionUrl.get('index'),
            method: 'get',
            params: { ...this.fixedParams, ...filter },
        })
    }

    edit(params: anyObj) {
        return createAxios({
            url: this.actionUrl.get('edit'),
            method: 'get',
            params: { ...this.fixedParams, ...params },
        })
    }

    del(ids: string[]) {
        return createAxios({
            url: this.actionUrl.get('del'),
            method: 'DELETE',
            data: { ...this.fixedParams, ids },
        })
    }

    add(data: anyObj) {
        return createAxios({
            url: this.actionUrl.get('add'),
            method: 'post',
            data: { ...this.fixedParams, ...data },
        })
    }

    postData(action: string, data: anyObj) {
        return createAxios(
            {
                url: this.actionUrl.has(action) ? this.actionUrl.get(action) : this.controllerUrl + action,
                method: 'post',
                data: { ...this.fixedParams, ...data },
            },
            { showSuccessMessage: true }
        )
    }

    sortable(data: anyObj) {
        return createAxios({
            url: this.actionUrl.get('sortable'),
            method: 'post',
            data: { ...this.fixedParams, ...data },
        })
    }
}

/**
 * 加载详情表配置并初始化 baTable
 */
const loadDetail = async (detail: DetailTableConfig, rowId: string | number) => {
    loading.value = true
    configReady.value = false

    try {
        const res = await getDynamicConfigById(detail.tableId)
        const cfg = (res.data?.data ?? res.data) as DynamicTableConfig
        detailConfig.value = cfg

        // 固定参数：详情表名 + 外键过滤
        const fixedParams: Record<string, any> = {
            table: cfg.name,
            detail_filter_key: detail.foreignKey,
            detail_filter_value: rowId,
        }

        // 构造列
        const selectionColumn = { type: 'selection', align: 'center', operator: false }
        // 排除 'detail' 按钮（防止递归嵌套）
        const safeRowButtons = (cfg.rowButtonNames || []).filter((n) => n !== 'detail')
        const operateColumn = {
            label: t('Operate'),
            align: 'center',
            width: 130,
            fixed: 'right',
            render: 'buttons',
            buttons: defaultOptButtons(safeRowButtons),
            operator: false,
        }

        // 直接更新 baTable 实例属性
        detailBaTable.api = new DetailTableApi(cfg.controllerUrl, fixedParams)
        detailBaTable.table.pk = cfg.pk
        detailBaTable.table.column = [selectionColumn, ...cfg.columns, operateColumn] as any
        detailBaTable.table.defaultOrder = cfg.defaultOrder
        detailBaTable.table.dblClickNotEditColumn = cfg.dblClickNotEditColumn || [undefined]
        // 新增时预填外键值
        detailBaTable.form.defaultItems = { ...(cfg.defaultItems || {}), [detail.foreignKey]: rowId }

        configReady.value = true
        loading.value = false

        await nextTick()
        detailBaTable.table.ref = detailTableRef.value
        detailBaTable.comSearch.fieldData.clear()
        Object.keys(detailBaTable.comSearch.form).forEach((k) => delete detailBaTable.comSearch.form[k])
        detailBaTable.table.routePath = `/dynamic/detail/${cfg.name}`
        detailBaTable.mount()
        detailBaTable.getData()
    } catch (err) {
        console.error('Failed to load detail table config:', err)
        loading.value = false
    }
}

/**
 * Drawer 打开时：读取 extend 上的 detail 配置，加载详情表
 */
watch(showDrawer, async (val) => {
    if (val) {
        const ext = parentBaTable.table.extend
        const detail: DetailTableConfig | undefined = ext?.detailConfig
        const rowId: string | number | undefined = ext?.detailRowId
        if (detail && rowId !== undefined) {
            await loadDetail(detail, rowId)
        }
    }
})

const onClosed = () => {
    configReady.value = false
    detailConfig.value = undefined
    detailBaTable.table.column = []
}
</script>

<style scoped lang="scss">
.detail-drawer-content {
    padding: 0 4px;
}
.detail-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
    color: var(--el-text-color-secondary);
}
</style>
