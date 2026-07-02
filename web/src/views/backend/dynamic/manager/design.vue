<template>
    <el-dialog
        v-model="show"
        :title="editId ? t('dynamic.designer.edit_title') : t('dynamic.designer.add_title')"
        width="95%"
        top="2vh"
        class="dynamic-designer-dialog"
        :close-on-click-modal="false"
        destroy-on-close
    >
        <div v-loading="loading" class="dynamic-designer">
            <!-- ═══ 表格配置区 ═══ -->
            <div class="config-bar">
                <el-row :gutter="16">
                    <el-col :span="4">
                        <el-form-item :label="t('dynamic.designer.name')" required>
                            <el-input v-model="form.name" :placeholder="t('dynamic.designer.name_ph')" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="5">
                        <el-form-item :label="t('dynamic.designer.title')" required>
                            <el-input v-model="form.title[designerLang]" :placeholder="t('dynamic.designer.title_ph')" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="4">
                        <el-form-item :label="t('dynamic.designer.db_connection')">
                            <el-select
                                v-model="form.db_connection"
                                filterable
                                clearable
                                :placeholder="t('dynamic.designer.db_connection_ph')"
                                style="width: 100%"
                                @change="onConnectionChange"
                            >
                                <el-option
                                    v-for="item in connectionOptions"
                                    :key="item.key"
                                    :label="item.key"
                                    :value="item.key"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="5">
                        <el-form-item :label="t('dynamic.designer.db_table')" required>
                            <el-select
                                v-model="form.db_table"
                                filterable
                                allow-create
                                default-first-option
                                clearable
                                :placeholder="t('dynamic.designer.db_table_ph')"
                                style="width: 100%"
                                @change="onTableChange"
                            >
                                <el-option
                                    v-for="item in tableOptions"
                                    :key="item.table"
                                    :label="item.comment ? `${item.table} (${item.comment})` : item.table"
                                    :value="item.table"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="6">
                        <el-form-item :label="t('dynamic.designer.menu_pid')">
                            <el-tree-select
                                v-model="form.menu_pid"
                                :data="menuTree"
                                :props="{ label: 'label', value: 'id', children: 'children' }"
                                :render-after-expand="false"
                                check-strictly
                                clearable
                                :placeholder="t('dynamic.designer.menu_pid_ph')"
                                style="width: 100%"
                            />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="16">
                    <el-col :span="3">
                        <el-form-item label="PK">
                            <el-select
                                v-model="form.pk"
                                filterable
                                clearable
                                placeholder="id"
                                style="width: 100%"
                            >
                                <el-option
                                    v-for="f in form.fields"
                                    :key="f.schema.prop"
                                    :label="f.render.label[designerLang] || f.schema.prop"
                                    :value="f.schema.prop"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="4">
                        <el-form-item :label="t('dynamic.designer.sort_field')">
                            <el-select
                                v-model="form.default_sort_field"
                                filterable
                                clearable
                                :placeholder="t('dynamic.designer.sort_field_ph', 'id')"
                                style="width: 100%"
                            >
                                <el-option
                                    v-for="f in form.fields"
                                    :key="f.schema.prop"
                                    :label="f.render.label[designerLang] || f.schema.prop"
                                    :value="f.schema.prop"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="3">
                        <el-form-item :label="t('dynamic.designer.sort_order')">
                            <el-select v-model="form.default_sort_order">
                                <el-option label="desc" value="desc" />
                                <el-option label="asc" value="asc" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="4">
                        <el-form-item :label="t('dynamic.designer.quick_search')">
                            <el-select
                                v-model="form.quick_search_fields"
                                multiple
                                collapse-tags
                                filterable
                                clearable
                                :placeholder="t('dynamic.designer.quick_search_ph')"
                                style="width: 100%"
                            >
                                <el-option
                                    v-for="field in form.fields"
                                    :key="field.schema.prop"
                                    :label="field.render.label['zh-cn'] || field.render.label.en || field.schema.prop"
                                    :value="field.schema.prop"
                                />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="4">
                        <el-form-item :label="t('dynamic.designer.status')">
                            <el-select v-model="form.status">
                                <el-option :label="t('dynamic.manager.enabled')" value="enabled" />
                                <el-option :label="t('dynamic.manager.disabled')" value="disabled" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="3">
                        <el-form-item :label="t('dynamic.designer.header_buttons')">
                            <el-select v-model="form.header_buttons" multiple collapse-tags style="width: 100%">
                                <el-option v-for="btn in headerButtonOptions" :key="btn" :label="btn" :value="btn" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="3">
                        <el-form-item :label="t('dynamic.designer.row_buttons')">
                            <el-select v-model="form.row_buttons" multiple collapse-tags style="width: 100%">
                                <el-option label="edit" value="edit" />
                                <el-option label="delete" value="delete" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <div class="designer-toolbar">
                    <el-radio-group v-model="designerLang" size="small">
                        <el-radio-button value="zh-cn">中文</el-radio-button>
                        <el-radio-button value="en">English</el-radio-button>
                    </el-radio-group>
                    <div class="toolbar-right">
                        <el-button size="small" :loading="importing" @click="onImportFields">
                            <Icon name="fa fa-download" size="12" class="mr-2" />
                            {{ t('dynamic.designer.import_fields') }}
                        </el-button>
                    </div>
                </div>
            </div>

            <!-- ═══ 三栏字段设计区 ═══ -->
            <el-row :gutter="12" class="fields-designer">
                <!-- ── 左栏：字段类型面板 ── -->
                <el-col :span="5">
                    <div class="field-palette ba-scroll-style">
                        <el-collapse v-model="paletteActiveNames">
                            <el-collapse-item :title="t('dynamic.designer.common_fields')" name="common">
                                <div
                                    v-for="(field, idx) in fieldTemplates.common"
                                    :key="'common-' + idx"
                                    class="palette-item"
                                    @click="onAddFieldFromTemplate(field)"
                                >
                                    <Icon name="fa fa-plus" size="10" color="#909399" class="mr-2" />
                                    <span>{{ designTypes[field.designType]?.name ?? field.name }}</span>
                                </div>
                            </el-collapse-item>
                            <el-collapse-item :title="t('dynamic.designer.base_fields')" name="base">
                                <div
                                    v-for="(field, idx) in fieldTemplates.base"
                                    :key="'base-' + idx"
                                    class="palette-item"
                                    @click="onAddFieldFromTemplate(field)"
                                >
                                    <Icon name="fa fa-plus" size="10" color="#909399" class="mr-2" />
                                    <span>{{ designTypes[field.designType]?.name ?? field.name }}</span>
                                </div>
                            </el-collapse-item>
                            <el-collapse-item :title="t('dynamic.designer.senior_fields')" name="senior">
                                <div
                                    v-for="(field, idx) in fieldTemplates.senior"
                                    :key="'senior-' + idx"
                                    class="palette-item"
                                    @click="onAddFieldFromTemplate(field)"
                                >
                                    <Icon name="fa fa-plus" size="10" color="#909399" class="mr-2" />
                                    <span>{{ designTypes[field.designType]?.name ?? field.name }}</span>
                                </div>
                            </el-collapse-item>
                        </el-collapse>
                    </div>
                </el-col>

                <!-- ── 中栏：字段列表 ── -->
                <el-col :span="9">
                    <div class="design-window ba-scroll-style">
                        <div
                            v-for="(field, index) in form.fields"
                            :key="index"
                            :class="['design-field-box', { activate: activateField === index }]"
                            @click="activateField = index"
                        >
                            <div class="design-field-name">
                                <span class="field-label">{{ t('dynamic.designer.f_prop') }}：</span>
                                <el-input
                                    v-model="field.schema.prop"
                                    size="small"
                                    class="field-name-input"
                                    @pointerdown.stop
                                    @change="onFieldRename(index)"
                                />
                            </div>
                            <div class="design-field-comment">
                                <span class="field-label">{{ t('dynamic.designer.f_label') }}：</span>
                                <el-input
                                    v-model="field.render.label[designerLang]"
                                    size="small"
                                    class="field-comment-input"
                                    @pointerdown.stop
                                />
                            </div>
                            <div class="design-field-actions">
                                <el-button
                                    size="small"
                                    type="danger"
                                    circle
                                    @click.stop="onDelField(index)"
                                >
                                    <Icon name="fa fa-trash" size="12" color="#fff" />
                                </el-button>
                            </div>
                        </div>
                        <div v-if="!form.fields.length" class="design-field-empty">
                            {{ t('dynamic.designer.drag_tip') }}
                        </div>
                    </div>
                </el-col>

                <!-- ── 右栏：属性编辑器 ── -->
                <el-col :span="10">
                    <div class="field-config ba-scroll-style">
                        <div v-if="activateField === -1" class="design-field-empty">
                            {{ t('dynamic.designer.select_field_tip') }}
                        </div>
                        <el-form v-else label-position="top" :key="'field-' + activateField">
                            <!-- 字段类型 -->
                            <el-divider content-position="left">{{ t('dynamic.designer.f_type') }}</el-divider>
                            <el-form-item :label="t('dynamic.designer.f_designtype')">
                                <el-select
                                    v-model="form.fields[activateField].render.design_type"
                                    style="width: 100%"
                                    @change="onDesignTypeChange($event, activateField)"
                                >
                                    <el-option-group
                                        v-for="group in designTypeGroups"
                                        :key="group.label"
                                        :label="group.label"
                                    >
                                        <el-option
                                            v-for="dt in group.types"
                                            :key="dt"
                                            :label="designTypes[dt]?.name ?? dt"
                                            :value="dt"
                                        />
                                    </el-option-group>
                                </el-select>
                            </el-form-item>

                            <!-- 多语言标签 -->
                            <el-divider content-position="left">{{ t('dynamic.designer.f_label_section') }}</el-divider>
                            <el-form-item label="中文">
                                <el-input v-model="form.fields[activateField].render.label['zh-cn']" placeholder="中文名称" />
                            </el-form-item>
                            <el-form-item label="English">
                                <el-input v-model="form.fields[activateField].render.label.en" placeholder="English label" />
                            </el-form-item>

                            <!-- 数据库 Schema 属性 -->
                            <el-divider content-position="left">{{ t('dynamic.designer.f_schema_props') }}</el-divider>
                            <el-form-item :label="t('dynamic.designer.f_db_type')">
                                <el-select
                                    v-model="form.fields[activateField].schema.type"
                                    filterable
                                    allow-create
                                    style="width: 100%"
                                >
                                    <el-option v-for="dt in dbTypeOptions" :key="dt" :label="dt" :value="dt" />
                                </el-select>
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_db_length')">
                                <el-input-number
                                    v-model="form.fields[activateField].schema.length"
                                    :min="0"
                                    controls-position="right"
                                    style="width: 100%"
                                />
                            </el-form-item>
                            <el-form-item
                                v-if="['decimal', 'float', 'double'].includes(form.fields[activateField].schema.type)"
                                :label="t('dynamic.designer.f_db_precision')"
                            >
                                <el-input-number
                                    v-model="form.fields[activateField].schema.precision"
                                    :min="0"
                                    controls-position="right"
                                    style="width: 100%"
                                />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_db_nullable')">
                                <el-switch v-model="form.fields[activateField].schema.nullable" />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_db_default_type')">
                                <el-select v-model="form.fields[activateField].schema.defaultType" style="width: 100%">
                                    <el-option
                                        v-for="(label, key) in defaultTypeOptions"
                                        :key="key"
                                        :label="label"
                                        :value="key"
                                    />
                                </el-select>
                            </el-form-item>
                            <el-form-item
                                v-if="form.fields[activateField].schema.defaultType === 'INPUT'"
                                :label="t('dynamic.designer.f_db_default')"
                            >
                                <el-input v-model="form.fields[activateField].schema.default" />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_db_unsigned')">
                                <el-switch v-model="form.fields[activateField].schema.unsigned" />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_db_auto_increment')">
                                <el-switch v-model="form.fields[activateField].schema.autoIncrement" />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_db_comment')">
                                <el-input v-model="form.fields[activateField].schema.comment" />
                            </el-form-item>

                            <!-- 表格列属性 -->
                            <el-divider content-position="left">{{ t('dynamic.designer.f_table_props') }}</el-divider>
                            <el-form-item :label="t('dynamic.designer.f_show')">
                                <el-switch v-model="form.fields[activateField].render.column_show" />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_align')">
                                <el-select v-model="form.fields[activateField].render.column_align" style="width: 100%">
                                    <el-option label="left" value="left" />
                                    <el-option label="center" value="center" />
                                    <el-option label="right" value="right" />
                                </el-select>
                            </el-form-item>
                            <template v-if="hasTableProps(activateField)">
                                <template v-for="(item, key) in form.fields[activateField].table" :key="'tbl-' + key">
                                    <FormItem
                                        :label="t(tablePropLabelKeys[key] || key)"
                                        :type="item.type"
                                        v-model="form.fields[activateField].table[key].value"
                                        :placeholder="item.placeholder ?? ''"
                                        :input-attr="{
                                            content: item.options ?? {},
                                            ...(item.attr ?? {}),
                                        }"
                                    />
                                </template>
                            </template>
                            <el-form-item :label="t('dynamic.designer.f_replacevalue')">
                                <el-input
                                    type="textarea"
                                    :rows="2"
                                    :model-value="form.fields[activateField].render._replaceValueStr"
                                    @update:model-value="onDictInput(form.fields[activateField], $event)"
                                    :placeholder='`{"0":"Disabled","1":"Enabled"}`'
                                />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_custom')">
                                <el-input
                                    type="textarea"
                                    :rows="2"
                                    v-model="form.fields[activateField].render.column_custom"
                                    placeholder='{"0":"danger","1":"success"}'
                                />
                            </el-form-item>

                            <!-- 表单属性 -->
                            <template v-if="hasFormProps(activateField)">
                                <el-divider content-position="left">{{ t('dynamic.designer.f_form_props') }}</el-divider>
                                <template v-for="(item, key) in form.fields[activateField].form" :key="'frm-' + key">
                                    <FormItem
                                        v-if="item.type !== 'hidden'"
                                        :label="t(formPropLabelKeys[key] || key)"
                                        :type="item.type"
                                        v-model="form.fields[activateField].form[key].value"
                                        :placeholder="item.placeholder ?? ''"
                                        :input-attr="{
                                            content: item.options ?? {},
                                            ...(item.attr ?? {}),
                                        }"
                                    />
                                </template>
                            </template>

                            <!-- remoteSelect 关联配置 -->
                            <template v-if="['remoteSelect', 'remoteSelects'].includes(form.fields[activateField].render.design_type)">
                                <el-divider content-position="left">{{ t('dynamic.designer.remote_assoc_title') }}</el-divider>
                                <el-form-item :label="t('dynamic.designer.f_remote_table')">
                                    <el-select
                                        v-model="form.fields[activateField].remote.table"
                                        filterable
                                        clearable
                                        placeholder=""
                                        style="width: 100%"
                                        @change="onRemoteTableChange(activateField)"
                                    >
                                        <el-option
                                            v-for="item in tableOptions"
                                            :key="item.table"
                                            :label="item.comment ? `${item.table} (${item.comment})` : item.table"
                                            :value="item.table"
                                        />
                                    </el-select>
                                </el-form-item>
                                <el-form-item :label="t('dynamic.designer.f_remote_pk')">
                                    <el-select
                                        v-model="form.fields[activateField].remote.pk"
                                        filterable
                                        clearable
                                        placeholder="id"
                                        style="width: 100%"
                                    >
                                        <el-option
                                            v-for="f in (remoteFieldOptions[activateField] || [])"
                                            :key="f.name"
                                            :label="`${f.name} (${f.type || ''})`"
                                            :value="f.name"
                                        />
                                    </el-select>
                                </el-form-item>
                                <el-form-item :label="t('dynamic.designer.f_remote_label')">
                                    <el-select
                                        v-model="form.fields[activateField].remote.label"
                                        filterable
                                        clearable
                                        placeholder="name"
                                        style="width: 100%"
                                    >
                                        <el-option
                                            v-for="f in (remoteFieldOptions[activateField] || [])"
                                            :key="f.name"
                                            :label="`${f.name} (${f.type || ''})`"
                                            :value="f.name"
                                        />
                                    </el-select>
                                </el-form-item>
                                <el-form-item :label="t('dynamic.designer.f_remote_key_type')">
                                    <el-radio-group v-model="form.fields[activateField].remote.key_type">
                                        <el-radio-button value="number">Number</el-radio-button>
                                        <el-radio-button value="string">String</el-radio-button>
                                    </el-radio-group>
                                </el-form-item>
                            </template>

                            <!-- 表单类型 -->
                            <el-form-item :label="t('dynamic.designer.f_formtype')">
                                <el-select v-model="form.fields[activateField].render.form_type" style="width: 100%">
                                    <el-option v-for="ft in formTypeOptions" :key="ft" :label="ft" :value="ft" />
                                </el-select>
                            </el-form-item>
                        </el-form>
                    </div>
                </el-col>
            </el-row>
        </div>

        <!-- ═══ 表结构变更确认弹窗 ═══ -->
        <el-dialog
            v-model="showSchemaSync"
            :title="t('dynamic.designer.schema_sync_title')"
            width="500px"
            append-to-body
            :close-on-click-modal="false"
        >
            <div v-if="designChange.length === 0" class="design-field-empty">
                {{ t('dynamic.designer.schema_sync_none') }}
            </div>
            <template v-else>
                <p>{{ t('dynamic.designer.schema_sync_desc') }}</p>
                <el-scrollbar max-height="300px">
                    <div v-for="(item, idx) in designChange" :key="idx" class="schema-change-item">
                        <el-checkbox v-model="item.sync" :label="getChangeDescription(item)" size="small" />
                    </div>
                </el-scrollbar>
                <el-alert
                    :title="t('dynamic.designer.schema_sync_warning')"
                    type="warning"
                    :closable="false"
                    show-icon
                    style="margin-top: 12px"
                />
            </template>
            <template #footer>
                <el-button @click="showSchemaSync = false">{{ t('Cancel') }}</el-button>
                <el-button type="primary" :loading="saving" @click="doSave">
                    {{ t('Save') }}
                </el-button>
            </template>
        </el-dialog>

        <template #footer>
            <div class="dialog-footer">
                <el-button @click="show = false">{{ t('Cancel') }}</el-button>
                <el-button type="primary" :loading="saving" @click="onSave">
                    {{ t('Save') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { ref, watch, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import Icon from '/@/components/icon/index.vue'
import FormItem from '/@/components/formItem/index.vue'
import createAxios from '/@/utils/axios'
import { addDynamicConfig, editDynamicConfig, getDynamicConfigDetail, getDbTableFields, getMenuTree } from '/@/api/backend/dynamic'
import { index as adminIndex } from '/@/api/backend'
import { handleAdminRoute } from '/@/utils/router'
import {
    fieldTemplates,
    designTypes,
    tablePropKeyMap,
    formPropKeyMap,
    tablePropLabelKeys,
    formPropLabelKeys,
    createFieldFromTemplate,
    schemaDefaults,
    dbTypeOptions,
    defaultTypeOptions,
    type FieldTemplate,
} from '../fieldTypes'
import { getDatabaseConnectionListUrl, getTableListUrl } from '/@/api/common'

defineOptions({
    name: 'dynamic/designer',
})

const props = defineProps<{
    modelValue: boolean
    editId: number
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', val: boolean): void
    (e: 'saved'): void
}>()

const { t } = useI18n()
const show = ref(props.modelValue)
const loading = ref(false)
const saving = ref(false)
const importing = ref(false)
const designerLang = ref<'zh-cn' | 'en'>('zh-cn')
const activateField = ref(-1)
const paletteActiveNames = ref(['common', 'base'])
const showSchemaSync = ref(false)

watch(() => props.modelValue, (v) => {
    show.value = v
    if (v) initForm()
})
watch(show, (v) => emit('update:modelValue', v))

const headerButtonOptions = ['refresh', 'add', 'edit', 'delete', 'unfold', 'comSearch', 'quickSearch', 'columnDisplay']
const formTypeOptions = ['string', 'number', 'textarea', 'switch', 'datetime', 'date', 'time', 'year', 'select', 'selects', 'radio', 'checkbox', 'remoteSelect', 'remoteSelects', 'image', 'images', 'file', 'files', 'editor', 'password', 'array', 'city', 'icon', 'color']

const designTypeGroups = [
    { label: 'Common', types: ['pk', 'spk', 'switch', 'textarea', 'weigh', 'timestamp', 'remoteSelect'] },
    { label: 'Base', types: ['string', 'number', 'float', 'image', 'file', 'radio', 'checkbox', 'select', 'editor', 'password', 'date', 'time', 'datetime', 'year'] },
    { label: 'Senior', types: ['array', 'city', 'icon', 'color', 'images', 'files', 'selects', 'remoteSelects'] },
]

const menuTree = ref<any[]>([])
const connectionOptions = ref<any[]>([])
const tableOptions = ref<any[]>([])
const remoteFieldOptions = ref<Record<number, any[]>>({})

/* ═══ Design Change Tracking (Phase 3) ═══ */

type DesignChangeType = 'add-field' | 'del-field' | 'change-field-name' | 'change-field-attr'

interface DesignChange {
    type: DesignChangeType
    oldName: string
    newName: string
    sync: boolean
}

const designChange = ref<DesignChange[]>([])

/**
 * Log a design change with CRUD-style dedup logic
 * Adapted from web/src/views/backend/crud/design.vue logTableDesignChange()
 */
const logTableDesignChange = (data: DesignChange) => {
    // Only track when editing existing configs
    if (!props.editId) return
    let push = true

    if (data.type === 'change-field-name') {
        // Update references in existing changes
        for (const key in designChange.value) {
            // Attr change tracking follows rename
            if (designChange.value[key].type === 'change-field-attr' && data.oldName === designChange.value[key].oldName) {
                designChange.value[key].oldName = data.newName
            }
            // Newly added field renamed
            if (designChange.value[key].type === 'add-field' && designChange.value[key].newName === data.oldName) {
                designChange.value[key].newName = data.newName
                push = false
                break
            }
            // Field renamed again
            if (designChange.value[key].type === 'change-field-name' && designChange.value[key].newName === data.oldName) {
                data.oldName = designChange.value[key].oldName
                designChange.value[key] = data
                // Undo rename
                if (designChange.value[key].newName === designChange.value[key].oldName) {
                    designChange.value.splice(Number(key), 1)
                }
                push = false
                break
            }
        }
    } else if (data.type === 'del-field') {
        let wasAdded = false
        // Added then deleted → cancel out
        designChange.value = designChange.value.filter((item) => {
            wasAdded = item.type === 'add-field' && item.newName === data.oldName
            const attr = item.type === 'change-field-attr' && item.oldName === data.oldName
            return !wasAdded && !attr
        })
        // Rename then deleted
        designChange.value = designChange.value.filter((item) => {
            const name = item.type === 'change-field-name' && item.newName === data.oldName
            if (name) data.oldName = item.oldName
            return !name
        })
        if (wasAdded) push = false
        // Dedup duplicate deletes
        for (const key in designChange.value) {
            if (designChange.value[key].type === 'del-field' && designChange.value[key].oldName === data.oldName) {
                push = false
                break
            }
        }
    } else if (data.type === 'change-field-attr') {
        // Skip attr change for newly-added fields
        for (const key in designChange.value) {
            if (designChange.value[key].type === 'add-field' && designChange.value[key].newName === data.oldName) {
                push = false
                break
            }
            // Dedup: only one attr change per field
            if (designChange.value[key].type === 'change-field-attr' && designChange.value[key].oldName === data.oldName) {
                push = false
                break
            }
        }
    } else if (data.type === 'add-field') {
        // Field was deleted then re-added
        for (const key in designChange.value) {
            if (designChange.value[key].type === 'del-field' && designChange.value[key].oldName === data.newName) {
                designChange.value.splice(Number(key), 1)
                push = false
                break
            }
        }
    }

    data.sync = true
    if (push) designChange.value.push(data)
}

const getChangeDescription = (item: DesignChange): string => {
    switch (item.type) {
        case 'add-field':
            return t('dynamic.designer.schema_change_add') + ' ' + item.newName
        case 'del-field':
            return t('dynamic.designer.schema_change_del') + ' ' + item.oldName
        case 'change-field-name':
            return t('dynamic.designer.schema_change_rename') + ' ' + item.oldName + ' => ' + item.newName
        case 'change-field-attr':
            return t('dynamic.designer.schema_change_attr') + ' ' + item.oldName
        default:
            return 'Unknown'
    }
}

/**
 * Called when user renames a field (schema.prop changes)
 */
const onFieldRename = (index: number) => {
    const field = form.fields[index]
    if (!field) return
    // Tracking handled via @change event since we need old name
    // This is called after the name has already changed in v-model
    // For proper rename tracking, we store the original name on field load
    const originalName = field._originalProp || ''
    const currentName = field.schema.prop
    if (originalName && originalName !== currentName) {
        logTableDesignChange({
            type: 'change-field-name',
            oldName: originalName,
            newName: currentName,
            sync: true,
        })
        field._originalProp = currentName
    }
}

/* ═══ Remote Select helpers ═══ */

const onRemoteTableChange = async (idx: number) => {
    const field = form.fields[idx]
    if (!field) return

    field.remote.pk = ''
    field.remote.label = ''

    if (!field.remote.table) {
        delete remoteFieldOptions.value[idx]
        return
    }

    try {
        const res = await getDbTableFields(field.remote.table, form.db_connection)
        const fields = res.data?.fields ?? res.data?.data?.fields ?? []
        remoteFieldOptions.value[idx] = fields

        const pkField = fields.find((f: any) => f.primary)
        if (pkField) field.remote.pk = pkField.name
        else if (fields.length) field.remote.pk = fields[0].name

        const labelField = fields.find((f: any) => {
            const ty = (f.type || '').toLowerCase()
            return !f.primary && (ty.includes('varchar') || ty.includes('text') || ty.includes('char'))
        })
        if (labelField) field.remote.label = labelField.name
        else if (fields.length > 1) field.remote.label = fields[1].name
        else if (fields.length) field.remote.label = fields[0].name
    } catch {
        remoteFieldOptions.value[idx] = []
    }
}

const ensureRemoteFieldOptions = async (idx: number) => {
    const field = form.fields[idx]
    if (!field || !['remoteSelect', 'remoteSelects'].includes(field.render.design_type)) return
    if (!field.remote.table) return
    if (remoteFieldOptions.value[idx]) return

    try {
        const res = await getDbTableFields(field.remote.table, form.db_connection)
        remoteFieldOptions.value[idx] = res.data?.fields ?? res.data?.data?.fields ?? []
    } catch {
        remoteFieldOptions.value[idx] = []
    }
}

watch(activateField, (idx) => {
    if (idx !== -1) ensureRemoteFieldOptions(idx)
})

/* ═══ Utilities ═══ */

const normalizeLangValue = (raw: any): Record<string, string> => {
    const empty = { 'zh-cn': '', en: '' }
    if (!raw) return { ...empty }
    if (typeof raw === 'string') {
        const trimmed = raw.trim()
        if (trimmed.startsWith('{')) {
            try {
                const obj = JSON.parse(trimmed)
                return { 'zh-cn': obj['zh-cn'] || '', en: obj['en'] || '' }
            } catch { /* fall through */ }
        }
        return { 'zh-cn': raw, en: '' }
    }
    if (typeof raw === 'object') {
        return { 'zh-cn': raw['zh-cn'] || '', en: raw['en'] || '' }
    }
    return { ...empty }
}

const loadConnectionOptions = async () => {
    try {
        const res = await createAxios({
            url: getDatabaseConnectionListUrl,
            method: 'get',
        })
        connectionOptions.value = res.data?.list ?? res.data ?? []
    } catch {
        connectionOptions.value = []
    }
}

const loadTableOptions = async () => {
    try {
        const res = await createAxios({
            url: getTableListUrl,
            method: 'get',
            params: {
                connection: form.db_connection || '',
                samePrefix: 0,
            },
        })
        tableOptions.value = res.data?.list ?? res.data ?? []
    } catch {
        tableOptions.value = []
    }
}

const onConnectionChange = () => {
    form.db_table = ''
    loadTableOptions()
}

const onTableChange = () => {}

const loadMenuTree = async () => {
    try {
        const res = await getMenuTree()
        menuTree.value = res.data?.tree ?? res.data?.data?.tree ?? []
    } catch {
        menuTree.value = []
    }
}

/* ═══ Field Model ═══ */

interface PropBagItem {
    type: string
    value: any
    options?: Record<string, any>
    placeholder?: string
    attr?: Record<string, any>
}

interface FieldRow {
    schema: {
        prop: string
        type: string
        length: number
        precision: number
        nullable: boolean
        default: any
        defaultType: string
        primary: boolean
        unsigned: boolean
        autoIncrement: boolean
        comment: string
    }
    render: {
        label: Record<string, string>
        design_type: string
        column_show: boolean
        column_align: string
        column_width: number | null
        column_render: string | null
        column_sortable: string
        column_operator: string
        column_com_search_render: string | null
        column_operator_placeholder: string | null
        column_time_format: string | null
        column_replace_value: any
        column_custom: string | null
        _replaceValueStr: string
        form_type: string
        form_validators: string[]
        form_input_attr: Record<string, any>
    }
    remote: {
        table: string
        pk: string
        label: string
        key_type: 'string' | 'number'
    }
    table: Record<string, PropBagItem>
    form: Record<string, PropBagItem>
    _originalProp?: string  // for rename tracking
}

/* ═══ Property Bag Builders ═══ */

const buildTableBag = (designType: string, renderData: any): Record<string, PropBagItem> => {
    const dt = designTypes[designType]
    if (!dt?.table) return {}
    const bag: Record<string, PropBagItem> = {}
    for (const [key, def] of Object.entries(dt.table)) {
        const fieldName = tablePropKeyMap[key]
        const dbVal = fieldName ? renderData[fieldName] : undefined
        bag[key] = {
            ...(def as any),
            value: dbVal ?? (def as any).value,
        }
    }
    return bag
}

const buildFormBag = (designType: string, renderData: any): Record<string, PropBagItem> => {
    const dt = designTypes[designType]
    if (!dt?.form) return {}
    const bag: Record<string, PropBagItem> = {}
    const inputAttr = renderData.form_input_attr || {}
    for (const [key, def] of Object.entries(dt.form)) {
        let dbVal: any
        if (key === 'validator') {
            dbVal = renderData.form_validators
        } else {
            const subKey = formPropKeyMap[key]
            if (subKey && !subKey.startsWith('__')) {
                dbVal = inputAttr[subKey]
            }
        }
        bag[key] = {
            ...(def as any),
            value: dbVal ?? (def as any).value,
        }
    }
    return bag
}

/**
 * Flatten field back to DB JSON format (schema/render/remote structure)
 */
const flattenField = (field: FieldRow) => {
    // Flatten table bag → render columns
    for (const [key, item] of Object.entries(field.table)) {
        const fieldName = tablePropKeyMap[key]
        if (fieldName) {
            (field.render as any)[fieldName] = item.value
        }
    }
    // Flatten form bag → form_validators + form_input_attr
    for (const [key, item] of Object.entries(field.form)) {
        if (key === 'validator') {
            field.render.form_validators = item.value
        } else {
            const subKey = formPropKeyMap[key]
            if (subKey && !subKey.startsWith('__')) {
                field.render.form_input_attr[subKey] = item.value
            }
        }
    }

    return {
        schema: { ...field.schema },
        render: {
            label: field.render.label,
            design_type: field.render.design_type,
            column_show: field.render.column_show,
            column_align: field.render.column_align,
            column_width: field.render.column_width,
            column_render: field.render.column_render,
            column_sortable: field.render.column_sortable,
            column_operator: field.render.column_operator,
            column_com_search_render: field.render.column_com_search_render,
            column_operator_placeholder: field.render.column_operator_placeholder,
            column_time_format: field.render.column_time_format,
            column_replace_value: field.render.column_replace_value,
            column_custom: field.render.column_custom,
            form_type: field.render.form_type,
            form_validators: field.render.form_validators,
            form_input_attr: field.render.form_input_attr,
        },
        remote: { ...field.remote },
    }
}

const hasTableProps = (idx: number): boolean => {
    const field = form.fields[idx]
    if (!field?.table) return false
    return Object.keys(field.table).length > 0
}

const hasFormProps = (idx: number): boolean => {
    const field = form.fields[idx]
    if (!field?.form) return false
    return Object.keys(field.form).length > 0
}

/**
 * Infer design_type from form_type for backward compat
 */
const inferDesignType = (formType: string, prop: string): string => {
    if (designTypes[formType]) return formType
    if (prop === 'id') return 'pk'
    if (prop === 'weigh') return 'weigh'
    if (prop === 'status') return 'switch'
    if (prop === 'create_time' || prop === 'update_time') return 'timestamp'
    return 'string'
}

const form = reactive({
    id: 0,
    name: '',
    title: { 'zh-cn': '', en: '' } as Record<string, string>,
    db_table: '',
    db_connection: '',
    pk: 'id',
    default_sort_field: '',
    default_sort_order: 'desc' as 'desc' | 'asc',
    quick_search_fields: [] as string[],
    header_buttons: ['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay'] as string[],
    row_buttons: ['edit', 'delete'] as string[],
    default_items: null as any,
    remark: { 'zh-cn': '', en: '' } as Record<string, string>,
    status: 'enabled',
    menu_pid: 0,
    fields: [] as FieldRow[],
})

/* ═══ initForm — with backward compat for old flat format ═══ */

const initForm = async () => {
    activateField.value = -1
    designChange.value = []
    Object.assign(form, {
        id: 0, name: '', title: { 'zh-cn': '', en: '' }, db_table: '', db_connection: '',
        pk: 'id', default_sort_field: '', default_sort_order: 'desc',
        quick_search_fields: [], header_buttons: ['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay'],
        row_buttons: ['edit', 'delete'], default_items: null, remark: { 'zh-cn': '', en: '' }, status: 'enabled',
        menu_pid: 0,
        fields: [],
    })
    designerLang.value = 'zh-cn'

    loadMenuTree()
    loadConnectionOptions()
    loadTableOptions()

    if (props.editId) {
        loading.value = true
        try {
            const res = await getDynamicConfigDetail(props.editId)
            const row = res.data?.row ?? res.data
            form.id = row.id
            form.name = row.name
            form.title = normalizeLangValue(row.title)
            form.db_table = row.db_table
            form.db_connection = row.db_connection || ''
            form.pk = row.pk || 'id'
            form.default_sort_field = row.default_sort_field || ''
            form.default_sort_order = row.default_sort_order || 'desc'
            form.remark = normalizeLangValue(row.remark)
            form.status = row.status || 'enabled'
            form.menu_pid = row.menu_pid || 0
            form.header_buttons = row.header_buttons || form.header_buttons
            form.row_buttons = row.row_buttons || form.row_buttons
            form.default_items = row.default_items

            const qsf = row.quick_search_fields
            form.quick_search_fields = Array.isArray(qsf) ? qsf : (qsf ? String(qsf).split(',').map((s: string) => s.trim()).filter(Boolean) : [])

            if (row.fields && Array.isArray(row.fields)) {
                form.fields = row.fields.map((f: any) => {
                    // ─── Backward compat: detect old vs new format ───
                    if (f.schema) {
                        // New structured format
                        const dt = f.render.design_type || 'string'
                        const rv = f.render.column_replace_value
                        const rvStr = rv ? (typeof rv === 'string' ? rv : JSON.stringify(rv)) : ''
                        let ia = f.render.form_input_attr
                        if (typeof ia === 'string') { try { ia = JSON.parse(ia || '{}') } catch { ia = {} } }
                        if (!ia) ia = {}

                        const fieldRow: FieldRow = {
                            schema: {
                                prop: f.schema.prop || '',
                                type: f.schema.type || 'varchar',
                                length: f.schema.length ?? 255,
                                precision: f.schema.precision ?? 0,
                                nullable: f.schema.nullable ?? true,
                                default: f.schema.default ?? '',
                                defaultType: f.schema.defaultType ?? 'NULL',
                                primary: f.schema.primary ?? false,
                                unsigned: f.schema.unsigned ?? false,
                                autoIncrement: f.schema.autoIncrement ?? false,
                                comment: f.schema.comment ?? '',
                            },
                            render: {
                                label: normalizeLangValue(f.render.label),
                                design_type: dt,
                                column_show: f.render.column_show !== undefined ? !!f.render.column_show : true,
                                column_align: f.render.column_align || 'center',
                                column_width: f.render.column_width ?? null,
                                column_render: f.render.column_render ?? null,
                                column_sortable: f.render.column_sortable ?? 'false',
                                column_operator: f.render.column_operator ?? 'eq',
                                column_com_search_render: f.render.column_com_search_render ?? null,
                                column_operator_placeholder: f.render.column_operator_placeholder ?? null,
                                column_time_format: f.render.column_time_format ?? null,
                                column_replace_value: rv,
                                _replaceValueStr: rvStr,
                                column_custom: f.render.column_custom ?? null,
                                form_type: f.render.form_type || dt,
                                form_validators: f.render.form_validators || [],
                                form_input_attr: ia,
                            },
                            remote: {
                                table: f.remote?.table || ia.remote_table || '',
                                pk: f.remote?.pk || ia.remote_pk || 'id',
                                label: f.remote?.label || ia.remote_label || 'name',
                                key_type: f.remote?.key_type || 'number',
                            },
                            table: {},
                            form: {},
                            _originalProp: f.schema.prop || '',
                        }
                        // Build bags from render data
                        fieldRow.table = buildTableBag(dt, {
                            column_render: fieldRow.render.column_render,
                            column_operator: fieldRow.render.column_operator,
                            column_sortable: fieldRow.render.column_sortable,
                            column_com_search_render: fieldRow.render.column_com_search_render,
                            column_operator_placeholder: fieldRow.render.column_operator_placeholder,
                            column_width: fieldRow.render.column_width,
                            column_time_format: fieldRow.render.column_time_format,
                        })
                        fieldRow.form = buildFormBag(dt, {
                            form_validators: fieldRow.render.form_validators,
                            form_input_attr: fieldRow.render.form_input_attr,
                        })
                        return fieldRow
                    } else {
                        // ─── Old flat format → migrate ───
                        const dt = f.design_type || inferDesignType(f.form_type || 'string', f.prop)
                        const rv = f.column_replace_value
                        const rvStr = rv ? (typeof rv === 'string' ? rv : JSON.stringify(rv)) : ''
                        let ia = (typeof f.form_input_attr === 'string' ? JSON.parse(f.form_input_attr || '{}') : f.form_input_attr) || {}
                        const sd = schemaDefaults[dt] || schemaDefaults['string']

                        const fieldRow: FieldRow = {
                            schema: {
                                prop: f.prop,
                                type: sd.type,
                                length: sd.length,
                                precision: sd.precision,
                                nullable: sd.nullable,
                                default: sd.default,
                                defaultType: sd.defaultType,
                                primary: sd.primary,
                                unsigned: sd.unsigned,
                                autoIncrement: sd.autoIncrement,
                                comment: '',
                            },
                            render: {
                                label: normalizeLangValue(f.label),
                                design_type: dt,
                                column_show: f.column_show !== undefined ? !!f.column_show : true,
                                column_align: f.column_align || 'center',
                                column_width: f.column_width ?? null,
                                column_render: f.column_render ?? null,
                                column_sortable: f.column_sortable ?? 'false',
                                column_operator: f.column_operator ?? 'eq',
                                column_com_search_render: f.column_com_search_render ?? null,
                                column_operator_placeholder: f.column_operator_placeholder ?? null,
                                column_time_format: f.column_time_format ?? null,
                                column_replace_value: rv,
                                _replaceValueStr: rvStr,
                                column_custom: f.column_custom ?? null,
                                form_type: f.form_type || dt,
                                form_validators: f.form_validators || [],
                                form_input_attr: ia,
                            },
                            remote: {
                                table: ia.remote_table || '',
                                pk: ia.remote_pk || 'id',
                                label: ia.remote_label || 'name',
                                key_type: 'number',
                            },
                            table: {},
                            form: {},
                            _originalProp: f.prop,
                        }
                        fieldRow.table = buildTableBag(dt, {
                            column_render: fieldRow.render.column_render,
                            column_operator: fieldRow.render.column_operator,
                            column_sortable: fieldRow.render.column_sortable,
                            column_com_search_render: fieldRow.render.column_com_search_render,
                            column_operator_placeholder: fieldRow.render.column_operator_placeholder,
                            column_width: fieldRow.render.column_width,
                            column_time_format: fieldRow.render.column_time_format,
                        })
                        fieldRow.form = buildFormBag(dt, {
                            form_validators: fieldRow.render.form_validators,
                            form_input_attr: fieldRow.render.form_input_attr,
                        })
                        return fieldRow
                    }
                })
            }
        } catch (err) {
            console.error(err)
        } finally {
            loading.value = false
        }
    }
}

/* ═══ Field Operations ═══ */

const onAddFieldFromTemplate = (template: FieldTemplate) => {
    const base = createFieldFromTemplate(template)
    const newField: FieldRow = {
        schema: base.schema,
        render: {
            ...base.render,
            _replaceValueStr: '',
        },
        remote: base.remote,
        table: buildTableBag(template.designType, {
            column_render: base.render.column_render,
            column_operator: base.render.column_operator,
            column_sortable: base.render.column_sortable,
            column_com_search_render: base.render.column_com_search_render,
            column_operator_placeholder: base.render.column_operator_placeholder,
            column_width: base.render.column_width,
            column_time_format: base.render.column_time_format,
        }),
        form: buildFormBag(template.designType, {
            form_validators: base.render.form_validators,
            form_input_attr: base.render.form_input_attr,
        }),
        _originalProp: base.schema.prop,
    }
    form.fields.push(newField)
    activateField.value = form.fields.length - 1

    logTableDesignChange({
        type: 'add-field',
        oldName: '',
        newName: base.schema.prop,
        sync: true,
    })
}

const onDelField = (index: number) => {
    const field = form.fields[index]
    if (field) {
        const name = field.schema.prop
        logTableDesignChange({
            type: 'del-field',
            oldName: name,
            newName: '',
            sync: true,
        })
    }
    form.fields.splice(index, 1)
    if (activateField.value >= form.fields.length) {
        activateField.value = form.fields.length - 1
    }
}

const onDesignTypeChange = (newType: string, index: number) => {
    const dt = designTypes[newType]
    if (!dt) return
    const field = form.fields[index]

    // Preserve user-modified values
    const oldTableValues: Record<string, any> = {}
    for (const [key, item] of Object.entries(field.table || {})) {
        oldTableValues[key] = item.value
    }
    const oldFormValues: Record<string, any> = {}
    for (const [key, item] of Object.entries(field.form || {})) {
        oldFormValues[key] = item.value
    }

    // Rebuild bags
    field.table = buildTableBag(newType, {
        column_render: oldTableValues.render,
        column_operator: oldTableValues.operator,
        column_sortable: oldTableValues.sortable,
        column_com_search_render: oldTableValues.comSearchRender,
        column_operator_placeholder: oldTableValues.comSearchInputAttr,
        column_width: oldTableValues.width,
        column_time_format: oldTableValues.timeFormat,
    })
    field.form = buildFormBag(newType, {
        form_validators: oldFormValues.validator,
        form_input_attr: {
            step: oldFormValues.step,
            rows: oldFormValues.rows,
            'select-multi': oldFormValues['select-multi'],
            'image-multi': oldFormValues['image-multi'],
            'file-multi': oldFormValues['file-multi'],
        },
    })

    // Update schema defaults for new type
    const sd = schemaDefaults[newType]
    if (sd) {
        field.schema.type = sd.type
        field.schema.length = sd.length
        field.schema.precision = sd.precision
        field.schema.nullable = sd.nullable
        field.schema.defaultType = sd.defaultType
        field.schema.unsigned = sd.unsigned
        field.schema.autoIncrement = sd.autoIncrement
        field.schema.primary = sd.primary
    }

    // Sync form_type
    field.render.form_type = newType
    field.render.design_type = newType

    // Track as attr change (schema changed)
    logTableDesignChange({
        type: 'change-field-attr',
        oldName: field.schema.prop,
        newName: '',
        sync: true,
    })
}

const onDictInput = (row: FieldRow, val: string) => {
    row.render._replaceValueStr = val
    try {
        row.render.column_replace_value = val ? JSON.parse(val) : null
    } catch {
        // JSON incomplete
    }
}

/* ═══ Import Fields from DB ═══ */

const onImportFields = async () => {
    if (!form.db_table) {
        ElMessage.warning(t('dynamic.designer.need_db_table'))
        return
    }
    importing.value = true
    try {
        const res = await getDbTableFields(form.db_table, form.db_connection)
        const fields = res.data?.fields ?? res.data?.data?.fields ?? []
        const existingProps = new Set(form.fields.map((f) => f.schema.prop))
        for (const f of fields) {
            if (!existingProps.has(f.name)) {
                const dbType = (f.type || '').toLowerCase()
                let designType = 'string'
                if (f.primary) {
                    designType = 'pk'
                } else if (dbType.includes('int')) {
                    designType = 'number'
                } else if (dbType.includes('datetime') || dbType.includes('timestamp')) {
                    designType = 'datetime'
                } else if (dbType.includes('date')) {
                    designType = 'date'
                } else if (dbType.includes('text')) {
                    designType = 'textarea'
                } else if (dbType.includes('decimal') || dbType.includes('float') || dbType.includes('double')) {
                    designType = 'float'
                }

                const template: FieldTemplate = {
                    title: f.name,
                    name: f.name,
                    designType,
                    formType: designType === 'pk' ? 'string' : designType,
                }
                const base = createFieldFromTemplate(template)
                // Override schema with actual DB values
                base.schema.comment = f.comment || ''
                base.schema.nullable = !(f.notnull === false)

                const row: FieldRow = {
                    schema: base.schema,
                    render: {
                        ...base.render,
                        label: { 'zh-cn': f.comment || f.name, en: '' },
                        _replaceValueStr: '',
                    },
                    remote: base.remote,
                    table: buildTableBag(designType, {
                        column_render: base.render.column_render,
                        column_operator: base.render.column_operator,
                        column_sortable: base.render.column_sortable,
                        column_com_search_render: base.render.column_com_search_render,
                        column_operator_placeholder: base.render.column_operator_placeholder,
                        column_width: base.render.column_width,
                        column_time_format: base.render.column_time_format,
                    }),
                    form: buildFormBag(designType, {
                        form_validators: base.render.form_validators,
                        form_input_attr: base.render.form_input_attr,
                    }),
                    _originalProp: f.name,
                }
                form.fields.push(row)
            }
        }
        ElMessage.success(t('dynamic.designer.imported', { count: fields.length }))
    } catch (err) {
        ElMessage.error(t('dynamic.designer.import_failed'))
        console.error(err)
    } finally {
        importing.value = false
    }
}

/* ═══ Save (with schema sync confirmation) ═══ */

const onSave = async () => {
    const hasTitle = form.title['zh-cn'] || form.title['en']
    if (!form.name || !hasTitle || !form.db_table) {
        ElMessage.warning(t('dynamic.designer.required_fields'))
        return
    }

    // If editing existing config and has design changes → show confirmation
    if (props.editId && designChange.value.length > 0) {
        showSchemaSync.value = true
    } else {
        // No structural changes or new config → save directly
        await doSave()
    }
}

const doSave = async () => {
    showSchemaSync.value = false
    saving.value = true
    try {
        // Flatten fields to schema/render/remote format
        const cleanFields = form.fields.map((f) => {
            const flat = flattenField(f)
            return flat
        })

        // Build designChange payload (only synced items)
        const syncChanges = designChange.value
            .filter((d) => d.sync)
            .map((d) => ({
                type: d.type,
                oldName: d.oldName,
                newName: d.newName,
                sync: true,
            }))

        const payload = { ...form, fields: cleanFields, designChange: syncChanges }
        // Remove frontend-only fields
        const cleanPayload: any = { ...payload }
        delete cleanPayload.default_items
        if (form.default_items !== null) cleanPayload.default_items = form.default_items

        if (props.editId) {
            cleanPayload.id = props.editId
            await editDynamicConfig(cleanPayload)
        } else {
            await addDynamicConfig(cleanPayload)
        }
        ElMessage.success(t('axios.Operation successful'))

        // Refresh admin menu routes
        try {
            const res = await adminIndex()
            if (res.data?.menus) {
                handleAdminRoute(res.data.menus)
            }
        } catch (e) {
            console.error('Menu refresh failed:', e)
        }

        // Clear design changes after successful save
        designChange.value = []

        emit('saved')
    } catch (err) {
        console.error(err)
    } finally {
        saving.value = false
    }
}
</script>

<style lang="scss">
.dynamic-designer-dialog {
    .el-dialog__body {
        padding: 10px 20px;
    }
}
.dynamic-designer {
    .config-bar {
        margin-bottom: 12px;
        .el-form-item {
            margin-bottom: 8px;
        }
    }
    .designer-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
        .toolbar-right {
            display: flex;
            gap: 8px;
        }
    }
}
.fields-designer {
    border-top: 1px solid var(--el-border-color-lighter);
    padding-top: 12px;

    .ba-scroll-style {
        max-height: calc(75vh - 280px);
        overflow-y: auto;
    }
}
.field-palette {
    .el-collapse-item__header {
        font-size: 13px;
        font-weight: 600;
    }
    .palette-item {
        display: flex;
        align-items: center;
        padding: 6px 8px;
        cursor: pointer;
        border-radius: 4px;
        font-size: 13px;
        transition: background 0.2s;
        &:hover {
            background: var(--el-color-primary-light-9);
        }
    }
}
.design-window {
    min-height: 300px;
    padding: 8px;
    .design-field-box {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        margin-bottom: 6px;
        border: 1px solid var(--el-border-color-lighter);
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
        &:hover {
            border-color: var(--el-color-primary-light-5);
        }
        &.activate {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
        }
    }
    .design-field-name,
    .design-field-comment {
        display: flex;
        align-items: center;
        flex: 1;
        .field-label {
            font-size: 12px;
            color: var(--el-text-color-secondary);
            white-space: nowrap;
            margin-right: 4px;
        }
    }
    .design-field-actions {
        flex-shrink: 0;
    }
    .design-field-empty {
        text-align: center;
        padding: 60px 0;
        color: var(--el-text-color-placeholder);
        font-size: 14px;
    }
}
.field-config {
    min-height: 300px;
    padding: 8px 16px;
    .el-divider__text {
        font-size: 13px;
        font-weight: 600;
    }
    .el-form-item {
        margin-bottom: 12px;
    }
}
.schema-change-item {
    padding: 4px 0;
}
</style>
