<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('workflow.bind.module_code') })"
        />

        <Table />
        <PopupForm />
    </div>
</template>

<script setup lang="ts">
import { provide } from 'vue'
import { useI18n } from 'vue-i18n'
import baTableClass from '/@/utils/baTable'
import PopupForm from './popupForm.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'

defineOptions({
    name: 'workflow/bind',
})

const { t } = useI18n()

const optButtons = defaultOptButtons(['edit', 'delete'])

const baTable = new baTableClass(
    new baTableApi('/admin/workflow.Bind/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('Id'), prop: 'id', align: 'center', operator: '=', width: 70 },
            { label: t('workflow.bind.module_code'), prop: 'module_code', align: 'center', operator: 'LIKE', operatorPlaceholder: t('Fuzzy query') },
            { label: t('workflow.bind.module_name'), prop: 'module_name', align: 'center', operator: 'LIKE' },
            {
                label: t('workflow.bind.status'),
                prop: 'status',
                align: 'center',
                render: 'tag',
                width: 100,
                custom: { enabled: 'success', disabled: 'danger' },
                replaceValue: {
                    enabled: t('workflow.bind.status enabled'),
                    disabled: t('workflow.bind.status disabled'),
                },
            },
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
        dblClickNotEditColumn: [undefined, 'status'],
    },
    {
        defaultItems: {
            status: 'enabled',
        },
    }
)

provide('baTable', baTable)

baTable.mount()
baTable.getData()
</script>

<style scoped lang="scss"></style>
