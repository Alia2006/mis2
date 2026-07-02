/**
 * 动态表格设计器 — 字段类型定义
 *
 * 完全参照 CRUD 的 designTypes / fieldItem 模式：
 * 每个类型定义 table（表格列属性）和 form（表单属性），
 * 右侧面板通过遍历这两个对象动态渲染，而非硬编码。
 *
 * i18n 键全部使用 dynamic.designer.* 或全局 utils.* 命名空间，
 * 确保在动态表格路由下语言包已加载。
 */

import { i18n } from '/@/lang/index'
import { validatorType } from '/@/utils/validate'

const t = i18n.global.t

/* ─── 基础属性模板 ─── */

const tableBaseAttr = {
    render: {
        type: 'select',
        value: 'none',
        options: {
            none: t('None'),
            icon: 'Icon',
            switch: t('utils.switch'),
            image: t('utils.image'),
            images: t('utils.multi image'),
            tag: 'Tag',
            tags: 'Tags',
            url: 'URL',
            datetime: t('utils.time date'),
            color: t('utils.color'),
        },
    },
    operator: {
        type: 'select',
        value: 'eq',
        options: {
            false: t('dynamic.designer.op_false'),
            eq: 'eq =',
            ne: 'ne !=',
            gt: 'gt >',
            egt: 'egt >=',
            lt: 'lt <',
            elt: 'elt <=',
            LIKE: 'LIKE',
            'NOT LIKE': 'NOT LIKE',
            IN: 'IN',
            'NOT IN': 'NOT IN',
            RANGE: 'RANGE',
            'NOT RANGE': 'NOT RANGE',
            NULL: 'NULL',
            'NOT NULL': 'NOT NULL',
            FIND_IN_SET: 'FIND_IN_SET',
        },
    },
    comSearchRender: {
        type: 'select',
        value: 'string',
        options: {
            string: t('utils.string'),
            select: t('utils.select'),
            remoteSelect: t('utils.remote select'),
            time: t('utils.time') + t('utils.choice'),
            date: t('utils.date') + t('utils.choice'),
            datetime: t('utils.time date') + t('utils.choice'),
        },
    },
    comSearchInputAttr: {
        type: 'textarea',
        value: '',
        placeholder: t('dynamic.designer.ph_comSearchAttr'),
        attr: { rows: 3 },
    },
    sortable: {
        type: 'select',
        value: 'false',
        options: {
            false: t('Disable'),
            custom: t('Enable'),
        },
    },
} as const

const formBaseAttr = {
    validator: {
        type: 'selects',
        value: [] as string[],
        options: validatorType,
    },
    validatorMsg: {
        type: 'textarea',
        value: '',
        placeholder: t('dynamic.designer.ph_validatorMsg'),
        attr: { rows: 2 },
    },
}

const getTableAttr = (type: keyof typeof tableBaseAttr, val: string) => ({
    ...tableBaseAttr[type],
    value: val,
})

const getFormAttr = (type: keyof typeof formBaseAttr, val: string[]) => ({
    ...formBaseAttr[type],
    value: val,
})

/* ─── 通用 width 定义（所有类型都可调整列宽） ─── */
const widthAttr = (val: number | null = null) => ({ type: 'number' as const, value: val })

/* ─── 属性 key 到字段模型的映射 ─── */

export const tablePropKeyMap: Record<string, string> = {
    render:              'column_render',
    operator:            'column_operator',
    sortable:            'column_sortable',
    comSearchRender:     'column_com_search_render',
    comSearchInputAttr:  'column_operator_placeholder',
    width:               'column_width',
    timeFormat:          'column_time_format',
}

export const formPropKeyMap: Record<string, string> = {
    validator:       '__validators',
    validatorMsg:    'validatorMsg',
    step:            'step',
    rows:            'rows',
    'select-multi':  'select-multi',
    'image-multi':   'image-multi',
    'file-multi':    'file-multi',
    // remoteSelect association props (stored in form_input_attr, rendered in dedicated UI)
    'remote-table':  'remote_table',
    'remote-pk':     'remote_pk',
    'remote-label':  'remote_label',
}

/**
 * table 属性 i18n key（存 key，在模板中用 t() 翻译）
 */
export const tablePropLabelKeys: Record<string, string> = {
    render:             'dynamic.designer.f_render',
    operator:           'dynamic.designer.f_operator',
    sortable:           'dynamic.designer.f_sortable',
    comSearchRender:    'dynamic.designer.f_comSearch',
    comSearchInputAttr: 'dynamic.designer.f_comSearchInputAttr',
    width:              'dynamic.designer.f_width',
    timeFormat:         'dynamic.designer.f_timeformat',
}

/**
 * form 属性 i18n key（存 key，在模板中用 t() 翻译）
 */
export const formPropLabelKeys: Record<string, string> = {
    validator:      'dynamic.designer.f_validators',
    validatorMsg:   'dynamic.designer.f_validatorMsg',
    step:           'dynamic.designer.f_step',
    rows:           'dynamic.designer.f_rows',
    'select-multi': 'dynamic.designer.f_select_multi',
    'image-multi':  'dynamic.designer.f_image_multi',
    'file-multi':   'dynamic.designer.f_file_multi',
    'remote-table': 'dynamic.designer.f_remote_table',
    'remote-pk':    'dynamic.designer.f_remote_pk',
    'remote-label': 'dynamic.designer.f_remote_label',
}

/* ─── 设计器字段类型定义 ─── */

export const designTypes: Record<string, {
    name: string
    table: Record<string, any>
    form: Record<string, any>
}> = {
    pk: {
        name: t('dynamic.designer.type_pk'),
        table: {
            width: widthAttr(70),
            operator: getTableAttr('operator', 'RANGE'),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: {},
    },
    spk: {
        name: t('dynamic.designer.type_spk'),
        table: {
            width: widthAttr(180),
            operator: getTableAttr('operator', 'RANGE'),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: {},
    },
    weigh: {
        name: t('dynamic.designer.type_weigh'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'RANGE'),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: { ...formBaseAttr },
    },
    timestamp: {
        name: t('dynamic.designer.type_timestamp'),
        table: {
            render: getTableAttr('render', 'datetime'),
            operator: getTableAttr('operator', 'RANGE'),
            comSearchRender: getTableAttr('comSearchRender', 'datetime'),
            comSearchInputAttr: getTableAttr('comSearchInputAttr', ''),
            sortable: getTableAttr('sortable', 'custom'),
            width: widthAttr(160),
            timeFormat: { type: 'string', value: 'yyyy-mm-dd hh:MM:ss' },
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['date']),
        },
    },
    string: {
        name: t('utils.string'),
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'none'),
            sortable: getTableAttr('sortable', 'false'),
            operator: getTableAttr('operator', 'LIKE'),
        },
        form: { ...formBaseAttr },
    },
    password: {
        name: t('utils.password'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'false'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['password']),
        },
    },
    number: {
        name: t('utils.number'),
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'none'),
            sortable: getTableAttr('sortable', 'false'),
            operator: getTableAttr('operator', 'RANGE'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['number']),
            step: { type: 'number', value: 1 },
        },
    },
    float: {
        name: t('utils.float'),
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'none'),
            sortable: getTableAttr('sortable', 'false'),
            operator: getTableAttr('operator', 'RANGE'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['float']),
            step: { type: 'number', value: 1 },
        },
    },
    radio: {
        name: t('utils.radio'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'eq'),
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'tag'),
        },
        form: { ...formBaseAttr },
    },
    checkbox: {
        name: t('utils.checkbox'),
        table: {
            width: widthAttr(),
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'tags'),
            operator: getTableAttr('operator', 'FIND_IN_SET'),
        },
        form: { ...formBaseAttr },
    },
    switch: {
        name: t('utils.switch'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'eq'),
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'switch'),
        },
        form: { ...formBaseAttr },
    },
    textarea: {
        name: t('utils.textarea'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'false'),
        },
        form: {
            ...formBaseAttr,
            rows: { type: 'number', value: 3 },
        },
    },
    array: {
        name: t('utils.array'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    datetime: {
        name: t('utils.time date') + t('utils.choice'),
        table: {
            operator: getTableAttr('operator', 'RANGE'),
            comSearchRender: getTableAttr('comSearchRender', 'datetime'),
            comSearchInputAttr: getTableAttr('comSearchInputAttr', ''),
            sortable: getTableAttr('sortable', 'custom'),
            width: widthAttr(160),
            render: getTableAttr('render', 'datetime'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['date']),
        },
    },
    year: {
        name: t('utils.year') + t('utils.choice'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'RANGE'),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['date']),
        },
    },
    date: {
        name: t('utils.date') + t('utils.choice'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'RANGE'),
            comSearchRender: getTableAttr('comSearchRender', 'date'),
            comSearchInputAttr: getTableAttr('comSearchInputAttr', ''),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['date']),
        },
    },
    time: {
        name: t('utils.time') + t('utils.choice'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'RANGE'),
            comSearchRender: getTableAttr('comSearchRender', 'time'),
            comSearchInputAttr: getTableAttr('comSearchInputAttr', ''),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: { ...formBaseAttr },
    },
    select: {
        name: t('utils.select'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'eq'),
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'tag'),
        },
        form: {
            ...formBaseAttr,
            'select-multi': { type: 'switch', value: false },
        },
    },
    selects: {
        name: t('utils.select') + '(' + t('dynamic.designer.label_multi') + ')',
        table: {
            width: widthAttr(),
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'tags'),
            operator: getTableAttr('operator', 'FIND_IN_SET'),
        },
        form: {
            ...formBaseAttr,
            'select-multi': { type: 'switch', value: true },
        },
    },
    remoteSelect: {
        name: t('utils.remote select') + t('utils.choice'),
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'tags'),
            operator: getTableAttr('operator', 'LIKE'),
            comSearchRender: getTableAttr('comSearchRender', 'string'),
            comSearchInputAttr: getTableAttr('comSearchInputAttr', ''),
        },
        form: {
            ...formBaseAttr,
            'select-multi': { type: 'switch', value: false },
        },
    },
    remoteSelects: {
        name: t('utils.remote select') + t('utils.choice') + '(' + t('dynamic.designer.label_multi') + ')',
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'tags'),
            operator: getTableAttr('operator', 'LIKE'),
            comSearchRender: getTableAttr('comSearchRender', 'remoteSelect'),
            comSearchInputAttr: getTableAttr('comSearchInputAttr', ''),
        },
        form: {
            ...formBaseAttr,
            'select-multi': { type: 'switch', value: true },
        },
    },
    editor: {
        name: t('utils.rich Text'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'false'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['editorRequired']),
        },
    },
    city: {
        name: t('utils.city select'),
        table: {
            width: widthAttr(),
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    image: {
        name: t('utils.image') + t('dynamic.designer.label_upload'),
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'image'),
            operator: getTableAttr('operator', 'false'),
        },
        form: {
            ...formBaseAttr,
            'image-multi': { type: 'switch', value: false },
        },
    },
    images: {
        name: t('utils.image') + t('dynamic.designer.label_upload') + '(' + t('dynamic.designer.label_multi') + ')',
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'images'),
            operator: getTableAttr('operator', 'false'),
        },
        form: {
            ...formBaseAttr,
            'image-multi': { type: 'switch', value: true },
        },
    },
    file: {
        name: t('utils.file') + t('dynamic.designer.label_upload'),
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'none'),
            operator: getTableAttr('operator', 'false'),
        },
        form: {
            ...formBaseAttr,
            'file-multi': { type: 'switch', value: false },
        },
    },
    files: {
        name: t('utils.file') + t('dynamic.designer.label_upload') + '(' + t('dynamic.designer.label_multi') + ')',
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'none'),
            operator: getTableAttr('operator', 'false'),
        },
        form: {
            ...formBaseAttr,
            'file-multi': { type: 'switch', value: true },
        },
    },
    icon: {
        name: t('utils.icon select'),
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'icon'),
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    color: {
        name: t('utils.color picker'),
        table: {
            width: widthAttr(),
            render: getTableAttr('render', 'color'),
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
}

/* ─── 字段面板分类（title 自动从 designTypes.name 获取） ─── */

export interface FieldTemplate {
    name: string
    designType: string
    formType: string
}

export const fieldTemplates: {
    common: FieldTemplate[]
    base: FieldTemplate[]
    senior: FieldTemplate[]
} = {
    common: [
        { name: 'id', designType: 'pk', formType: 'string' },
        { name: 'id', designType: 'spk', formType: 'string' },
        { name: 'status', designType: 'switch', formType: 'switch' },
        { name: 'remark', designType: 'textarea', formType: 'textarea' },
        { name: 'weigh', designType: 'weigh', formType: 'number' },
        { name: 'update_time', designType: 'timestamp', formType: 'datetime' },
        { name: 'create_time', designType: 'timestamp', formType: 'datetime' },
        { name: 'remote_select', designType: 'remoteSelect', formType: 'remoteSelect' },
    ],
    base: [
        { name: 'string', designType: 'string', formType: 'string' },
        { name: 'number', designType: 'number', formType: 'number' },
        { name: 'image', designType: 'image', formType: 'image' },
        { name: 'file', designType: 'file', formType: 'file' },
        { name: 'radio', designType: 'radio', formType: 'radio' },
        { name: 'checkbox', designType: 'checkbox', formType: 'checkbox' },
        { name: 'select', designType: 'select', formType: 'select' },
        { name: 'switch', designType: 'switch', formType: 'switch' },
        { name: 'editor', designType: 'editor', formType: 'editor' },
        { name: 'textarea', designType: 'textarea', formType: 'textarea' },
        { name: 'password', designType: 'password', formType: 'password' },
        { name: 'date', designType: 'date', formType: 'date' },
        { name: 'time', designType: 'time', formType: 'time' },
        { name: 'datetime', designType: 'datetime', formType: 'datetime' },
        { name: 'year', designType: 'year', formType: 'year' },
        { name: 'timestamp', designType: 'timestamp', formType: 'datetime' },
    ],
    senior: [
        { name: 'array', designType: 'array', formType: 'array' },
        { name: 'city', designType: 'city', formType: 'city' },
        { name: 'icon', designType: 'icon', formType: 'icon' },
        { name: 'color', designType: 'color', formType: 'color' },
        { name: 'images', designType: 'images', formType: 'images' },
        { name: 'files', designType: 'files', formType: 'files' },
        { name: 'selects', designType: 'selects', formType: 'selects' },
        { name: 'remote_selects', designType: 'remoteSelects', formType: 'remoteSelects' },
    ],
}

/* ─── 根据 designType 创建字段默认值 ─── */

export const createFieldFromTemplate = (template: FieldTemplate) => {
    const dt = designTypes[template.designType]
    const tableProps: Record<string, any> = {}
    const formProps: Record<string, any> = {}

    if (dt?.table) {
        for (const [key, val] of Object.entries(dt.table)) {
            tableProps[key] = (val as any).value
        }
    }
    if (dt?.form) {
        for (const [key, val] of Object.entries(dt.form)) {
            formProps[key] = (val as any).value
        }
    }

    const inputAttr: Record<string, any> = {}
    if (formProps['select-multi'] !== undefined) inputAttr['select-multi'] = formProps['select-multi']
    if (formProps['image-multi'] !== undefined) inputAttr['image-multi'] = formProps['image-multi']
    if (formProps['file-multi'] !== undefined) inputAttr['file-multi'] = formProps['file-multi']
    if (formProps.step !== undefined) inputAttr.step = formProps.step
    if (formProps.rows !== undefined) inputAttr.rows = formProps.rows

    return {
        prop: template.name,
        label: { 'zh-cn': '', en: '' } as Record<string, string>,
        column_show: template.designType !== 'pk' && template.designType !== 'spk',
        column_width: tableProps.width ?? null,
        column_align: 'center',
        column_render: tableProps.render ?? null,
        column_operator: tableProps.operator ?? 'eq',
        column_sortable: tableProps.sortable ?? 'false',
        column_com_search_render: tableProps.comSearchRender ?? null,
        column_replace_value: null,
        column_custom: null,
        column_time_format: tableProps.timeFormat ?? null,
        column_operator_placeholder: tableProps.comSearchInputAttr ?? null,
        form_type: template.formType,
        form_validators: formProps.validator ?? [],
        form_input_attr: inputAttr,
        design_type: template.designType,
    }
}
