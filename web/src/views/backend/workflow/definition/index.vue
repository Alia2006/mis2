<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('workflow.definition.name') + '/' + t('workflow.definition.code') })"
        />

        <Table />

        <PopupForm />

        <!-- 设计器入口按钮在行内 -->
        <el-dialog v-model="designerVisible" fullscreen :title="t('workflow.definition.designer')" :destroy-on-close="true" @close="onDesignerClose">
            <Designer v-if="designerVisible" :definition-id="designerId" @saved="onDesignerSaved" />
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, provide } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessageBox, ElMessage } from 'element-plus'
import baTableClass from '/@/utils/baTable'
import PopupForm from './popupForm.vue'
import Designer from './designer.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'
import { publish as publishApi, copyDefinition } from '/@/api/backend/workflow/definition'

defineOptions({
    name: 'workflow/definition',
})

const { t } = useI18n()

const designerVisible = ref(false)
const designerId = ref(0)

// 自定义行按钮
const optButtons = defaultOptButtons(['edit', 'delete'])

// 扩展按钮：设计、发布、复制
const designerBtn = {
    render: 'tipButton',
    name: 'designer',
    title: t('workflow.definition.designer'),
    text: '',
    type: 'primary',
    icon: 'fa fa-project-diagram',
    class: 'table-row-designer',
    disabledTip: false,
    click: ({ row }: { row: any }) => {
        designerId.value = row.id
        designerVisible.value = true
    },
}
const publishBtn = {
    render: 'tipButton',
    name: 'publish',
    title: t('workflow.definition.publish'),
    text: '',
    type: 'success',
    icon: 'fa fa-upload',
    class: 'table-row-publish',
    disabledTip: false,
    click: ({ row }: { row: any }) => {
        ElMessageBox.confirm(t('workflow.definition.publish confirm'), t('Warning'), { type: 'warning' })
            .then(async () => {
                await publishApi(row.id)
                baTable.onTableHeaderAction('refresh', {})
            })
            .catch(() => {})
    },
}
const copyBtn = {
    render: 'tipButton',
    name: 'copy',
    title: t('workflow.definition.copy'),
    text: '',
    type: 'info',
    icon: 'fa fa-copy',
    class: 'table-row-copy',
    disabledTip: false,
    click: ({ row }: { row: any }) => {
        ElMessageBox.confirm(t('workflow.definition.copy confirm'), t('Warning'), { type: 'warning' })
            .then(async () => {
                await copyDefinition(row.id)
                baTable.onTableHeaderAction('refresh', {})
            })
            .catch(() => {})
    },
}

const baTable = new baTableClass(
    new baTableApi('/admin/workflow.Definition/'),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('Id'), prop: 'id', align: 'center', operator: '=', width: 70 },
            { label: t('workflow.definition.name'), prop: 'name', align: 'center', operator: 'LIKE', operatorPlaceholder: t('Fuzzy query') },
            { label: t('workflow.definition.code'), prop: 'code', align: 'center', operator: 'LIKE', operatorPlaceholder: t('Fuzzy query') },
            { label: t('workflow.definition.description'), prop: 'description', align: 'center', operator: 'LIKE' },
            { label: t('workflow.definition.version'), prop: 'version', align: 'center', operator: false, width: 80 },
            {
                label: t('workflow.definition.status'),
                prop: 'status',
                align: 'center',
                render: 'tag',
                width: 100,
                custom: { draft: 'info', published: 'success', disabled: 'danger' },
                replaceValue: {
                    draft: t('workflow.definition.status draft'),
                    published: t('workflow.definition.status published'),
                    disabled: t('workflow.definition.status disabled'),
                },
            },
            { label: t('Create time'), prop: 'create_time', align: 'center', render: 'datetime', sortable: 'custom', operator: 'RANGE', width: 160 },
            {
                label: t('Operate'),
                align: 'center',
                width: '180',
                render: 'buttons',
                buttons: [designerBtn, publishBtn, copyBtn, ...optButtons],
                operator: false,
            },
        ],
        dblClickNotEditColumn: [undefined, 'status'],
    },
    {
        defaultItems: {
            status: 'draft',
            version: 1,
        },
    }
)

provide('baTable', baTable)

const onDesignerClose = () => {
    designerId.value = 0
}

const onDesignerSaved = () => {
    baTable.onTableHeaderAction('refresh', {})
}

baTable.mount()
baTable.getData()
</script>

<style scoped lang="scss"></style>
