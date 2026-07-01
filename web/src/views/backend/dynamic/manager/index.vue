<template>
    <div class="default-main ba-table-box">
        <el-alert type="info" :closable="false" class="ba-table-alert">
            {{ t('dynamic.manager.tip') }}
        </el-alert>

        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('dynamic.manager.quick Search Fields') })"
        />

        <Table ref="tableRef"></Table>

        <!-- 配置设计器弹窗 -->
        <DesignDialog v-model="designVisible" :edit-id="editId" @saved="onSaved" />
    </div>
</template>

<script setup lang="ts">
import { onMounted, provide, reactive, ref, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElNotification } from 'element-plus'
import { baTableApi } from '/@/api/common'
import { defaultOptButtons } from '/@/components/table'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import baTableClass from '/@/utils/baTable'
import DesignDialog from './design.vue'

defineOptions({
    name: 'dynamic/Config',
})

const { t } = useI18n()
const tableRef = useTemplateRef('tableRef')
const designVisible = ref(false)
const editId = ref(0)

const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])

// 覆写 edit 按钮：打开设计器而非默认弹窗
optButtons[0].click = (row: any) => {
    editId.value = row.id
    designVisible.value = true
}

const baTable = new baTableClass(
    new baTableApi('/admin/dynamic.Config/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: 'ID', prop: 'id', align: 'center', width: 70, operator: '=', sortable: 'custom' },
            {
                label: t('dynamic.manager.name'),
                prop: 'name',
                align: 'center',
                operator: 'LIKE',
                sortable: false,
                width: 120,
            },
            {
                label: t('dynamic.manager.title'),
                prop: 'title',
                align: 'center',
                operator: 'LIKE',
                sortable: false,
            },
            {
                label: t('dynamic.manager.db_table'),
                prop: 'db_table',
                align: 'center',
                operator: 'LIKE',
                sortable: false,
            },
            {
                label: t('dynamic.manager.pk'),
                prop: 'pk',
                align: 'center',
                width: 80,
                operator: false,
            },
            {
                label: t('dynamic.manager.status'),
                prop: 'status',
                align: 'center',
                width: 90,
                render: 'tag',
                operator: 'eq',
                replaceValue: { enabled: t('dynamic.manager.enabled'), disabled: t('dynamic.manager.disabled') },
                custom: { enabled: 'success', disabled: 'danger' },
            },
            {
                label: t('dynamic.manager.create_time'),
                prop: 'create_time',
                align: 'center',
                render: 'datetime',
                operator: 'RANGE',
                sortable: 'custom',
                width: 160,
                comSearchRender: 'datetime',
                timeFormat: 'yyyy-mm-dd hh:MM:ss',
            },
            { label: t('Operate'), align: 'center', width: 130, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [undefined],
        defaultOrder: { prop: 'id', order: 'desc' },
    },
    {
        defaultItems: {
            status: 'enabled',
            pk: 'id',
            header_buttons: ['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay'],
            row_buttons: ['edit', 'delete'],
        },
    }
)

provide('baTable', baTable)

// 覆写 add 操作：打开设计器
baTable.before = {
    onTableHeaderAction: ({ event }) => {
        if (event === 'add') {
            editId.value = 0
            designVisible.value = true
            return false
        }
    },
}

const onSaved = () => {
    designVisible.value = false
    baTable.onTableHeaderAction('refresh', {})
    // 提示用户：菜单规则已更新，需刷新页面生效
    ElNotification({
        title: t('dynamic.manager.menu_synced_title'),
        message: t('dynamic.manager.menu_synced_msg'),
        type: 'success',
        duration: 6000,
    })
}

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getData()
})
</script>

<style scoped lang="scss"></style>
