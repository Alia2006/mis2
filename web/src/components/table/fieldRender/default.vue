<template>
    <div>{{ displayValue }}</div>
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

const cellValue = getCellValue(props.row, props.field, props.column, props.index)

const displayValue = computed(() => {
    const val = cellValue
    if (val === null || val === undefined || val === '') return ''
    // 字典替换
    if (props.field.replaceValue && props.field.replaceValue[val] !== undefined) {
        return props.field.replaceValue[val]
    }
    // 对象/数组转为 JSON 字符串，其余转为字符串
    if (typeof val === 'object') return JSON.stringify(val)
    return String(val)
})
</script>
