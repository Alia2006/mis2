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
                    <!-- 数据库连接：下拉选择 -->
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
                    <!-- 数据表名：可搜索 + 可新建 -->
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
                            <el-input v-model="form.pk" placeholder="id" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="4">
                        <el-form-item :label="t('dynamic.designer.sort_field')">
                            <el-input v-model="form.default_sort_field" placeholder="id" />
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
                                    :key="field.prop"
                                    :label="field.label['zh-cn'] || field.label.en || field.prop"
                                    :value="field.prop"
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
                <!-- 多语言切换 + 操作按钮 -->
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
                                    <span>{{ field.title }}</span>
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
                                    <span>{{ field.title }}</span>
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
                                    <span>{{ field.title }}</span>
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
                                    v-model="field.prop"
                                    size="small"
                                    class="field-name-input"
                                    @pointerdown.stop
                                />
                            </div>
                            <div class="design-field-comment">
                                <span class="field-label">{{ t('dynamic.designer.f_label') }}：</span>
                                <el-input
                                    v-model="field.label[designerLang]"
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
                                    v-model="form.fields[activateField]._designType"
                                    style="width: 100%"
                                    @change="onDesignTypeChange($event, activateField)"
                                >
                                    <el-option-group
                                        v-for="group in [
                                            { label: 'Common', types: ['pk','spk','switch','textarea','weigh','timestamp','remoteSelect'] },
                                            { label: 'Base', types: ['string','number','float','image','file','radio','checkbox','select','editor','password','date','time','datetime','year'] },
                                            { label: 'Senior', types: ['array','city','icon','color','images','files','selects','remoteSelects'] },
                                        ]"
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
                                <el-input v-model="form.fields[activateField].label['zh-cn']" placeholder="中文名称" />
                            </el-form-item>
                            <el-form-item label="English">
                                <el-input v-model="form.fields[activateField].label.en" placeholder="English label" />
                            </el-form-item>

                            <!-- 表格列属性 -->
                            <el-divider content-position="left">{{ t('dynamic.designer.f_table_props') }}</el-divider>
                            <el-form-item :label="t('dynamic.designer.f_show')">
                                <el-switch v-model="form.fields[activateField].column_show" />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_width')">
                                <el-input-number v-model="form.fields[activateField].column_width" :controls="false" style="width: 100%" />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_align')">
                                <el-select v-model="form.fields[activateField].column_align" style="width: 100%">
                                    <el-option label="left" value="left" />
                                    <el-option label="center" value="center" />
                                    <el-option label="right" value="right" />
                                </el-select>
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_render')">
                                <el-select v-model="form.fields[activateField].column_render" clearable placeholder="none" style="width: 100%">
                                    <el-option v-for="r in renderOptions" :key="r" :label="r" :value="r" />
                                </el-select>
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_operator')">
                                <el-select v-model="form.fields[activateField].column_operator" style="width: 100%">
                                    <el-option v-for="op in operatorOptions" :key="op" :label="op" :value="op" />
                                </el-select>
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_sortable')">
                                <el-select v-model="form.fields[activateField].column_sortable" style="width: 100%">
                                    <el-option label="false" value="false" />
                                    <el-option label="custom" value="custom" />
                                </el-select>
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_comSearch')">
                                <el-select v-model="form.fields[activateField].column_com_search_render" clearable placeholder="auto" style="width: 100%">
                                    <el-option v-for="r in comSearchRenderOptions" :key="r" :label="r" :value="r" />
                                </el-select>
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_timeformat')">
                                <el-input v-model="form.fields[activateField].column_time_format" placeholder="yyyy-mm-dd hh:MM:ss" />
                            </el-form-item>

                            <!-- 字典/替换值 -->
                            <el-form-item :label="t('dynamic.designer.f_replacevalue')">
                                <el-input
                                    type="textarea"
                                    :rows="2"
                                    :model-value="form.fields[activateField]._replaceValueStr"
                                    @update:model-value="onDictInput(form.fields[activateField], $event)"
                                    :placeholder='`{"0":"Disabled","1":"Enabled"}`'
                                />
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_custom')">
                                <el-input
                                    type="textarea"
                                    :rows="2"
                                    v-model="form.fields[activateField].column_custom"
                                    placeholder='{"0":"danger","1":"success"}'
                                />
                            </el-form-item>

                            <!-- 表单属性 -->
                            <el-divider content-position="left">{{ t('dynamic.designer.f_form_props') }}</el-divider>
                            <el-form-item :label="t('dynamic.designer.f_formtype')">
                                <el-select v-model="form.fields[activateField].form_type" style="width: 100%">
                                    <el-option v-for="ft in formTypeOptions" :key="ft" :label="ft" :value="ft" />
                                </el-select>
                            </el-form-item>
                            <el-form-item :label="t('dynamic.designer.f_validators')">
                                <el-select
                                    v-model="form.fields[activateField].form_validators"
                                    multiple
                                    style="width: 100%"
                                >
                                    <el-option label="required" value="required" />
                                    <el-option label="number" value="number" />
                                    <el-option label="date" value="date" />
                                    <el-option label="email" value="email" />
                                    <el-option label="float" value="float" />
                                    <el-option label="phone" value="phone" />
                                    <el-option label="password" value="password" />
                                </el-select>
                            </el-form-item>
                        </el-form>
                    </div>
                </el-col>
            </el-row>
        </div>

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
import createAxios from '/@/utils/axios'
import { addDynamicConfig, editDynamicConfig, getDynamicConfigDetail, getDbTableFields, getMenuTree } from '/@/api/backend/dynamic'
import { fieldTemplates, designTypes, createFieldFromTemplate, type FieldTemplate } from '../fieldTypes'
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

watch(() => props.modelValue, (v) => {
    show.value = v
    if (v) initForm()
})
watch(show, (v) => emit('update:modelValue', v))

const headerButtonOptions = ['refresh', 'add', 'edit', 'delete', 'unfold', 'comSearch', 'quickSearch', 'columnDisplay']
const renderOptions = ['tag', 'tags', 'switch', 'datetime', 'image', 'images', 'icon', 'color', 'url']
const operatorOptions = ['false', 'eq', 'ne', 'gt', 'egt', 'lt', 'elt', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'RANGE', 'NOT RANGE', 'NULL', 'NOT NULL', 'FIND_IN_SET']
const comSearchRenderOptions = ['string', 'select', 'remoteSelect', 'time', 'date', 'datetime']
const formTypeOptions = ['string', 'number', 'textarea', 'switch', 'datetime', 'date', 'time', 'year', 'select', 'selects', 'radio', 'checkbox', 'remoteSelect', 'remoteSelects', 'image', 'images', 'file', 'files', 'editor', 'password', 'array', 'city', 'icon', 'color']

const menuTree = ref<any[]>([])
const connectionOptions = ref<any[]>([])
const tableOptions = ref<any[]>([])

/**
 * 将原始值规范化为多语言对象
 */
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

/**
 * 加载数据库连接列表
 */
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

/**
 * 加载数据表列表
 */
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

const onTableChange = () => {
    // 选择表后可手动触发导入
}

const loadMenuTree = async () => {
    try {
        const res = await getMenuTree()
        menuTree.value = res.data?.tree ?? res.data?.data?.tree ?? []
    } catch {
        menuTree.value = []
    }
}

interface FieldRow {
    prop: string
    label: Record<string, string>
    column_show: boolean
    column_width: number | null
    column_align: string
    column_render: string | null
    column_operator: string
    column_sortable: string
    column_com_search_render: string | null
    column_replace_value: any
    _replaceValueStr: string
    column_custom: any
    column_time_format: string | null
    column_operator_placeholder: string | null
    form_type: string
    form_validators: string[]
    form_input_attr: any
    _designType: string
}

const defaultField = (): FieldRow => ({
    prop: '',
    label: { 'zh-cn': '', en: '' },
    column_show: true,
    column_width: null,
    column_align: 'center',
    column_render: null,
    column_operator: 'eq',
    column_sortable: 'false',
    column_com_search_render: null,
    column_replace_value: null,
    _replaceValueStr: '',
    column_custom: null,
    column_time_format: null,
    column_operator_placeholder: null,
    form_type: 'string',
    form_validators: [],
    form_input_attr: {},
    _designType: 'string',
})

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

const initForm = async () => {
    activateField.value = -1
    Object.assign(form, {
        id: 0, name: '', title: { 'zh-cn': '', en: '' }, db_table: '', db_connection: '',
        pk: 'id', default_sort_field: '', default_sort_order: 'desc',
        quick_search_fields: [], header_buttons: ['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay'],
        row_buttons: ['edit', 'delete'], default_items: null, remark: { 'zh-cn': '', en: '' }, status: 'enabled',
        menu_pid: 0,
        fields: [],
    })
    designerLang.value = 'zh-cn'

    // 并行加载下拉数据
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
                    const rv = f.column_replace_value
                    const rvStr = rv ? (typeof rv === 'string' ? rv : JSON.stringify(rv)) : ''
                    return {
                        ...defaultField(),
                        prop: f.prop,
                        label: normalizeLangValue(f.label),
                        column_show: !!f.column_show,
                        column_width: f.column_width,
                        column_render: f.column_render,
                        column_operator: f.column_operator || 'eq',
                        column_sortable: f.column_sortable || 'false',
                        column_com_search_render: f.column_com_search_render,
                        column_replace_value: rv,
                        _replaceValueStr: rvStr,
                        column_custom: f.column_custom,
                        column_time_format: f.column_time_format,
                        column_operator_placeholder: f.column_operator_placeholder,
                        form_type: f.form_type || 'string',
                        form_validators: f.form_validators || [],
                        form_input_attr: f.form_input_attr || {},
                        _designType: f.form_type || 'string',
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

/**
 * 从左侧面板添加字段
 */
const onAddFieldFromTemplate = (template: FieldTemplate) => {
    const newField = { ...defaultField(), ...createFieldFromTemplate(template) } as FieldRow
    form.fields.push(newField)
    activateField.value = form.fields.length - 1
}

/**
 * 删除字段
 */
const onDelField = (index: number) => {
    form.fields.splice(index, 1)
    if (activateField.value >= form.fields.length) {
        activateField.value = form.fields.length - 1
    }
}

/**
 * 设计类型变更时，更新默认属性
 */
const onDesignTypeChange = (newType: string, index: number) => {
    const dt = designTypes[newType]
    if (!dt) return
    const field = form.fields[index]

    // 更新表格列属性
    if (dt.table) {
        for (const [key, val] of Object.entries(dt.table)) {
            const propName = key === 'width' ? 'column_width' : key === 'render' ? 'column_render' :
                key === 'operator' ? 'column_operator' : key === 'sortable' ? 'column_sortable' :
                key === 'comSearchRender' ? 'column_com_search_render' : key === 'timeFormat' ? 'column_time_format' : null
            if (propName) {
                (field as any)[propName] = (val as any).value
            }
        }
    }

    // 更新表单类型
    field.form_type = newType
}

const onDictInput = (row: FieldRow, val: string) => {
    row._replaceValueStr = val
    try {
        row.column_replace_value = val ? JSON.parse(val) : null
    } catch {
        // JSON 还不完整
    }
}

/**
 * 从数据库表导入字段
 */
const onImportFields = async () => {
    if (!form.db_table) {
        ElMessage.warning(t('dynamic.designer.need_db_table'))
        return
    }
    importing.value = true
    try {
        const res = await getDbTableFields(form.db_table, form.db_connection)
        const fields = res.data?.fields ?? res.data?.data?.fields ?? []
        const existingProps = new Set(form.fields.map((f) => f.prop))
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

                const template = {
                    title: f.name,
                    name: f.name,
                    designType,
                    formType: designType === 'pk' ? 'string' : designType,
                }
                const row = { ...createFieldFromTemplate(template) } as FieldRow
                row.label = { 'zh-cn': f.comment || f.name, en: '' }
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

const onSave = async () => {
    const hasTitle = form.title['zh-cn'] || form.title['en']
    if (!form.name || !hasTitle || !form.db_table) {
        ElMessage.warning(t('dynamic.designer.required_fields'))
        return
    }

    saving.value = true
    try {
        const payload = { ...form }
        payload.quick_search_fields = form.quick_search_fields

        const cleanFields = form.fields.map(({ _replaceValueStr, _designType, ...rest }) => rest)
        payload.fields = cleanFields

        if (props.editId) {
            payload.id = props.editId
            await editDynamicConfig(payload)
        } else {
            await addDynamicConfig(payload)
        }
        ElMessage.success(t('axios.Operation successful'))
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
/* 左栏：字段类型面板 */
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
/* 中栏：字段设计区 */
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
/* 右栏：属性编辑器 */
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
</style>
