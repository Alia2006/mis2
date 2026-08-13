<template>
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
        :destroy-on-close="true"
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
                    ref="formRef"
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    :label-position="config.layout.shrink ? 'top' : 'right'"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                    v-if="!baTable.form.loading"
                >
                    <FormItem
                        :label="t('workflow.bind.module_code')"
                        v-model="baTable.form.items!.module_code"
                        type="string"
                        prop="module_code"
                        :placeholder="t('Please input field', { field: t('workflow.bind.module_code') })"
                    />
                    <FormItem
                        :label="t('workflow.bind.module_name')"
                        v-model="baTable.form.items!.module_name"
                        type="string"
                        prop="module_name"
                        :placeholder="t('Please input field', { field: t('workflow.bind.module_name') })"
                    />
                    <el-form-item :label="t('workflow.bind.definition_id')">
                        <el-select
                            v-model="baTable.form.items!.definition_id"
                            filterable
                            :placeholder="t('Please select field', { field: t('workflow.bind.definition_id') })"
                            style="width: 100%"
                        >
                            <el-option
                                v-for="d in definitions"
                                :key="d.id"
                                :label="d.name + ' (' + d.code + ')'"
                                :value="d.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item :label="t('workflow.bind.status')">
                        <el-radio-group v-model="baTable.form.items!.status">
                            <el-radio value="enabled" border>{{ t('workflow.bind.status enabled') }}</el-radio>
                            <el-radio value="disabled" border>{{ t('workflow.bind.status disabled') }}</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm('')">{{ t('Cancel') }}</el-button>
                <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
                    {{ t('Save') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive, ref, inject, useTemplateRef, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import type baTableClass from '/@/utils/baTable'
import { buildValidatorData } from '/@/utils/validate'
import type { FormItemRule } from 'element-plus'
import FormItem from '/@/components/formItem/index.vue'
import { useConfig } from '/@/stores/config'
import { getWorkflowDefinitions } from '/@/api/backend/dynamic'

const config = useConfig()
const formRef = useTemplateRef('formRef')
const baTable = inject('baTable') as baTableClass

const { t } = useI18n()

const definitions = ref<{ id: number; name: string; code: string }[]>([])

onMounted(async () => {
    try {
        const res = await getWorkflowDefinitions()
        definitions.value = res.data?.data?.list || res.data?.list || []
    } catch (e) {
        // ignore
    }
})

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    module_code: [buildValidatorData({ name: 'required', title: t('workflow.bind.module_code') })],
    module_name: [buildValidatorData({ name: 'required', title: t('workflow.bind.module_name') })],
})
</script>

<style scoped lang="scss"></style>
