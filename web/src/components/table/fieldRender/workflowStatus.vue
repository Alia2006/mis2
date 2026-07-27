<template>
    <div>
        <el-tag v-if="wf" :type="tagType" size="small" effect="light">
            {{ label }}
        </el-tag>
        <span v-else class="wf-none">—</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { TableColumnCtx } from 'element-plus'

interface Props {
    row: TableRow
    field: TableColumn
    column: TableColumnCtx<TableRow>
    index: number
}

const props = defineProps<Props>()
const { t } = useI18n()

/** row.__workflow 由后端 index() 附加 */
const wf = computed(() => (props.row as any).__workflow)

const labelMap: Record<string, string> = {
    running: 'dynamic.workflow.status_running',
    approved: 'dynamic.workflow.status_approved',
    rejected: 'dynamic.workflow.status_rejected',
    cancelled: 'dynamic.workflow.status_cancelled',
}

const typeMap: Record<string, '' | 'success' | 'warning' | 'danger' | 'info'> = {
    running: 'warning',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'info',
}

const label = computed(() => {
    const s = wf.value?.status
    return s ? t(labelMap[s] || s) : '—'
})

const tagType = computed(() => {
    const s = wf.value?.status
    return s ? (typeMap[s] ?? '') : ''
})
</script>

<style scoped>
.wf-none {
    color: var(--el-text-color-placeholder);
}
</style>
