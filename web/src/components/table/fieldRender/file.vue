<template>
    <div>
        <template v-if="isArray(cellValue)">
            <template v-for="(ext, idx) in cellValue" :key="idx">
                <el-tag
                    v-if="ext"
                    class="m-4"
                    :type="getTagType(ext, field.custom)"
                    :effect="field.effect ?? 'light'"
                    :size="field.size ?? 'default'"
                >
                    {{ ext }}
                </el-tag>
            </template>
        </template>
        <template v-else>
            <el-tag
                v-if="cellValue"
                :type="getTagType(cellValue, field.custom)"
                :effect="field.effect ?? 'light'"
                :size="field.size ?? 'default'"
            >
                {{ cellValue }}
            </el-tag>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { TableColumnCtx, TagProps } from 'element-plus'
import { isArray, isEmpty } from 'lodash-es'
import { getCellValue } from '/@/components/table/index'

interface Props {
    row: TableRow
    field: TableColumn
    column: TableColumnCtx<TableRow>
    index: number
}

const props = defineProps<Props>()

const rawValue = getCellValue(props.row, props.field, props.column, props.index)

/**
 * Extract uppercase file extension from a path/URL.
 * e.g. "/uploads/doc.pdf" -> "PDF", "archive.tar.gz" -> "GZ"
 */
const getExt = (val: any): string => {
    if (isArray(val) || val === null || val === undefined || val === '') return ''
    const str = String(val)
    // Handle paths with query strings: /file.pdf?token=xxx
    const cleanPath = str.split('?')[0].split('#')[0]
    const lastDot = cleanPath.lastIndexOf('.')
    const lastSlash = Math.max(cleanPath.lastIndexOf('/'), cleanPath.lastIndexOf('\\'))
    if (lastDot <= lastSlash || lastDot === -1) return ''
    return cleanPath.slice(lastDot + 1).toUpperCase()
}

/**
 * Normalise the cell value into either a string (single file) or array (multiple files).
 */
const cellValue = computed<string | string[]>(() => {
    if (rawValue === null || rawValue === undefined || rawValue === '') return ''
    if (isArray(rawValue)) {
        return rawValue.map(getExt).filter(Boolean)
    }
    if (typeof rawValue === 'string') {
        // Try parse JSON array
        const trimmed = rawValue.trim()
        if (trimmed.startsWith('[')) {
            try {
                const parsed = JSON.parse(trimmed)
                if (isArray(parsed)) return parsed.map(getExt).filter(Boolean)
            } catch {
                // not JSON, treat as single file path
            }
        }
    }
    return getExt(rawValue)
})

const getTagType = (value: string, custom: any): TagProps['type'] => {
    return !isEmpty(custom) && custom[value] ? custom[value] : 'info'
}
</script>

<style scoped lang="scss">
.m-4 {
    margin: 4px;
}
</style>
