<template>
    <!-- 动态表单弹窗：v-for 渲染所有字段，完全数据驱动 -->
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                {{ baTable.form.operate ? t(baTable.form.operate) : '' }}
            </div>
        </template>
        <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
            <div
                class="ba-operate-form"
                :class="'ba-' + baTable.form.operate + '-form'"
                :style="config.layout.shrink ? '' : 'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
            >
                <el-form
                    v-if="!baTable.form.loading"
                    ref="formRef"
                    @submit.prevent=""
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    :label-position="config.layout.shrink ? 'top' : 'right'"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                >
                    <FormItem
                        v-for="field in fields"
                        :key="field.prop"
                        :label="field.label"
                        :type="field.type"
                        v-model="baTable.form.items![field.prop]"
                        :prop="field.prop"
                        :placeholder="field.placeholder"
                        :input-attr="field.inputAttr || {}"
                    />
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm()">{{ t('Cancel') }}</el-button>
                <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
                    {{ baTable.form.operateIds && baTable.form.operateIds.length > 1 ? t('Save and edit next item') : t('Save') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import type { FormInstance, FormItemRule } from 'element-plus'
import { inject, reactive, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import FormItem from '/@/components/formItem/index.vue'
import { useConfig } from '/@/stores/config'
import type baTableClass from '/@/utils/baTable'
import { buildValidatorData } from '/@/utils/validate'
import type { DynamicFormField } from './types'

defineOptions({
    name: 'dynamic/popupForm',
})

const props = defineProps<{
    fields: DynamicFormField[]
}>()

const config = useConfig()
const formRef = useTemplateRef<FormInstance>('formRef')
const baTable = inject('baTable') as baTableClass
const { t } = useI18n()

/**
 * 根据字段配置的 validators 数组自动生成 el-form 校验规则
 */
const rules = reactive<Partial<Record<string, FormItemRule[]>>>({})

const rebuildRules = () => {
    // 清空旧规则
    Object.keys(rules).forEach((k) => delete rules[k])

    for (const field of props.fields) {
        if (field.validators && field.validators.length > 0) {
            const isRemoteSelect = ['remoteSelect', 'remoteSelects'].includes(field.type)
            rules[field.prop] = field.validators.map((name) =>
                buildValidatorData({ name, title: field.label, trigger: isRemoteSelect ? 'change' : 'blur' })
            )
        }
    }
}

// 字段变化时重建规则
watch(
    () => props.fields,
    () => rebuildRules(),
    { immediate: true, deep: true }
)
</script>

<style scoped lang="scss"></style>
