<template>
    <span v-if="formatted">{{ formatted }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { TableColumnCtx } from 'element-plus'
import { getCellValue } from '/@/components/table/index'

interface Props {
    row: TableRow
    field: TableColumn
    column: TableColumnCtx<TableRow>
    index: number
}

const props = defineProps<Props>()

const rawValue = getCellValue(props.row, props.field, props.column, props.index)

const formatted = computed(() => {
    if (rawValue === null || rawValue === undefined || rawValue === '') return ''
    const num = Number(rawValue)
    if (isNaN(num)) return String(rawValue)
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num)
})
</script>
