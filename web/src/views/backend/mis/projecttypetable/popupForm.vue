<template>
    <!-- 对话框表单 -->
    <!-- 建议使用 Prettier 格式化代码 -->
    <!-- el-form 内可以混用 el-form-item、FormItem、ba-input 等输入组件 -->
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
                        :label="t('mis.projecttypetable.ID')"
                        type="string"
                        v-model="baTable.form.items!.ID"
                        prop="ID"
                        :placeholder="t('Please input field', { field: t('mis.projecttypetable.ID') })"
                    />
                    <FormItem
                        :label="t('mis.projecttypetable.Type_Name')"
                        type="string"
                        v-model="baTable.form.items!.Type_Name"
                        prop="Type_Name"
                        :placeholder="t('Please input field', { field: t('mis.projecttypetable.Type_Name') })"
                    />
                    <FormItem
                        :label="t('mis.projecttypetable.FGC_Creator')"
                        type="string"
                        v-model="baTable.form.items!.FGC_Creator"
                        prop="FGC_Creator"
                        :placeholder="t('Please input field', { field: t('mis.projecttypetable.FGC_Creator') })"
                    />
                    <FormItem
                        :label="t('mis.projecttypetable.FGC_CreateDate')"
                        type="datetime"
                        v-model="baTable.form.items!.FGC_CreateDate"
                        prop="FGC_CreateDate"
                        :placeholder="t('Please select field', { field: t('mis.projecttypetable.FGC_CreateDate') })"
                    />
                    <FormItem
                        :label="t('mis.projecttypetable.FGC_LastModifier')"
                        type="string"
                        v-model="baTable.form.items!.FGC_LastModifier"
                        prop="FGC_LastModifier"
                        :placeholder="t('Please input field', { field: t('mis.projecttypetable.FGC_LastModifier') })"
                    />
                    <FormItem
                        :label="t('mis.projecttypetable.FGC_LastModifyDate')"
                        type="datetime"
                        v-model="baTable.form.items!.FGC_LastModifyDate"
                        prop="FGC_LastModifyDate"
                        :placeholder="t('Please select field', { field: t('mis.projecttypetable.FGC_LastModifyDate') })"
                    />
                    <FormItem
                        :label="t('mis.projecttypetable.FGC_Rowversion')"
                        type="datetime"
                        v-model="baTable.form.items!.FGC_Rowversion"
                        prop="FGC_Rowversion"
                        :placeholder="t('Please select field', { field: t('mis.projecttypetable.FGC_Rowversion') })"
                    />
                    <FormItem
                        :label="t('mis.projecttypetable.FGC_UpdateHelp')"
                        type="string"
                        v-model="baTable.form.items!.FGC_UpdateHelp"
                        prop="FGC_UpdateHelp"
                        :placeholder="t('Please input field', { field: t('mis.projecttypetable.FGC_UpdateHelp') })"
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
import type { FormItemRule } from 'element-plus'
import { inject, reactive, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import FormItem from '/@/components/formItem/index.vue'
import { useConfig } from '/@/stores/config'
import type baTableClass from '/@/utils/baTable'
import { buildValidatorData } from '/@/utils/validate'

const config = useConfig()
const formRef = useTemplateRef('formRef')
const baTable = inject('baTable') as baTableClass

const { t } = useI18n()

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    FGC_CreateDate: [buildValidatorData({ name: 'date', title: t('mis.projecttypetable.FGC_CreateDate') })],
    FGC_LastModifyDate: [buildValidatorData({ name: 'date', title: t('mis.projecttypetable.FGC_LastModifyDate') })],
    FGC_Rowversion: [buildValidatorData({ name: 'date', title: t('mis.projecttypetable.FGC_Rowversion') })],
})
</script>

<style scoped lang="scss"></style>
