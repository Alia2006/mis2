<template>
    <span>{{ result }}</span>
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

/**
 * Evaluate a computed template against the row data.
 *
 * Template syntax: `{fieldName}` is replaced with row values.
 * After replacement, the expression is evaluated:
 *  1. If it's valid arithmetic/JS → compute the result (e.g., `{price} * {qty}` → 500)
 *  2. If it's string concatenation → return the interpolated string (e.g., `{first} {last}` → "John Doe")
 *
 * @example "{price} * {quantity}"   → 500 (when price=100, qty=5)
 * @example "{first_name} {last}"    → "John Doe"
 * @example "{a} > 100 ? 'High' : 'Low'" → "High" or "Low"
 */
const evalTemplate = (template: string, row: TableRow): string => {
    if (!template) return ''

    let hasNumeric = false
    let hasString = false

    // Replace {field} placeholders with row values
    let expr = template.replace(/\{(\w+)\}/g, (match, fieldName: string) => {
        const val = (row as any)[fieldName]
        if (val === null || val === undefined) return '""'
        const strVal = String(val)
        if (/^-?\d+(\.\d+)?$/.test(strVal)) {
            hasNumeric = true
            return strVal
        }
        hasString = true
        return `"${strVal.replace(/"/g, '\\"')}"`
    })

    // Pure string concatenation (no numeric operations) → just return the string
    if (hasString && !hasNumeric) {
        try {
            const result = Function(`"use strict"; return (${expr})`)()
            return String(result ?? '')
        } catch {
            // Not valid JS (e.g., plain text without operators), strip quotes and return
            return expr.replace(/\\?"/g, '')
        }
    }

    // Has numeric or mixed → try JS evaluation
    try {
        const result = Function(`"use strict"; return (${expr})`)()
        if (result === null || result === undefined) return ''
        // Round to avoid floating point artifacts (e.g., 0.1 + 0.2 → 0.30000000000000004)
        if (typeof result === 'number') {
            return String(Math.round(result * 1e10) / 1e10)
        }
        return String(result)
    } catch {
        // Fall back to string replacement
        return expr.replace(/\\?"/g, '')
    }
}

const result = computed(() => {
    const template = (props.field as any).template
    if (!template) return ''
    return evalTemplate(template, props.row)
})
</script>
