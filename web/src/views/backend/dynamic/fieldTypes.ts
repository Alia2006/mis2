/**
 * 动态表格设计器 — 字段类型定义
 *
 * 参照 CRUD 的 designTypes / fieldItem 模式，
 * 但仅保留「表格列属性」和「表单属性」，不需要 DB schema 信息。
 */

/**
 * 表格列属性模板
 */
const tableBaseAttr = {
    render: {
        type: 'select',
        value: 'none',
        options: {
            none: 'None',
            icon: 'Icon',
            switch: 'Switch',
            image: 'Image',
            images: 'Images',
            tag: 'Tag',
            tags: 'Tags',
            url: 'URL',
            datetime: 'DateTime',
            color: 'Color',
        },
    },
    operator: {
        type: 'select',
        value: 'eq',
        options: {
            false: 'Disable Search',
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
            string: 'String',
            select: 'Select',
            remoteSelect: 'Remote Select',
            time: 'Time',
            date: 'Date',
            datetime: 'DateTime',
        },
    },
    sortable: {
        type: 'select',
        value: 'false',
        options: {
            false: 'Disable',
            custom: 'Enable',
        },
    },
} as const

/**
 * 表单属性模板
 */
const formBaseAttr = {
    validator: {
        type: 'selects',
        value: [] as string[],
        options: ['required', 'number', 'date', 'email', 'float', 'phone', 'password'],
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

/**
 * 设计器字段类型定义
 * 每个类型定义了添加该类型字段时的默认表格列属性和表单属性
 */
export const designTypes: Record<string, {
    name: string
    table: Record<string, any>
    form: Record<string, any>
}> = {
    pk: {
        name: 'Primary Key',
        table: {
            width: { type: 'number', value: 70 },
            operator: getTableAttr('operator', 'RANGE'),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: {},
    },
    spk: {
        name: 'Primary Key (Snowflake)',
        table: {
            width: { type: 'number', value: 180 },
            operator: getTableAttr('operator', 'RANGE'),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: {},
    },
    string: {
        name: 'String',
        table: {
            render: getTableAttr('render', 'none'),
            sortable: getTableAttr('sortable', 'false'),
            operator: getTableAttr('operator', 'LIKE'),
        },
        form: { ...formBaseAttr },
    },
    number: {
        name: 'Number',
        table: {
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
        name: 'Float',
        table: {
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
    switch: {
        name: 'Switch',
        table: {
            operator: getTableAttr('operator', 'eq'),
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'switch'),
        },
        form: { ...formBaseAttr },
    },
    radio: {
        name: 'Radio',
        table: {
            operator: getTableAttr('operator', 'eq'),
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'tag'),
        },
        form: { ...formBaseAttr },
    },
    checkbox: {
        name: 'Checkbox',
        table: {
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'tags'),
            operator: getTableAttr('operator', 'FIND_IN_SET'),
        },
        form: { ...formBaseAttr },
    },
    select: {
        name: 'Select',
        table: {
            operator: getTableAttr('operator', 'eq'),
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'tag'),
        },
        form: { ...formBaseAttr },
    },
    selects: {
        name: 'Select (Multi)',
        table: {
            sortable: getTableAttr('sortable', 'false'),
            render: getTableAttr('render', 'tags'),
            operator: getTableAttr('operator', 'FIND_IN_SET'),
        },
        form: { ...formBaseAttr },
    },
    textarea: {
        name: 'Textarea',
        table: {
            operator: getTableAttr('operator', 'false'),
        },
        form: {
            ...formBaseAttr,
            rows: { type: 'number', value: 3 },
        },
    },
    password: {
        name: 'Password',
        table: {
            operator: getTableAttr('operator', 'false'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['password']),
        },
    },
    datetime: {
        name: 'DateTime',
        table: {
            operator: getTableAttr('operator', 'RANGE'),
            comSearchRender: getTableAttr('comSearchRender', 'datetime'),
            sortable: getTableAttr('sortable', 'custom'),
            width: { type: 'number', value: 160 },
            render: getTableAttr('render', 'datetime'),
            timeFormat: { type: 'string', value: 'yyyy-mm-dd hh:MM:ss' },
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['date']),
        },
    },
    date: {
        name: 'Date',
        table: {
            operator: getTableAttr('operator', 'RANGE'),
            comSearchRender: getTableAttr('comSearchRender', 'date'),
            sortable: getTableAttr('sortable', 'custom'),
            render: getTableAttr('render', 'datetime'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['date']),
        },
    },
    time: {
        name: 'Time',
        table: {
            operator: getTableAttr('operator', 'RANGE'),
            comSearchRender: getTableAttr('comSearchRender', 'time'),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: { ...formBaseAttr },
    },
    year: {
        name: 'Year',
        table: {
            operator: getTableAttr('operator', 'RANGE'),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['date']),
        },
    },
    timestamp: {
        name: 'Timestamp',
        table: {
            operator: getTableAttr('operator', 'RANGE'),
            comSearchRender: getTableAttr('comSearchRender', 'datetime'),
            sortable: getTableAttr('sortable', 'custom'),
            width: { type: 'number', value: 160 },
            render: getTableAttr('render', 'datetime'),
            timeFormat: { type: 'string', value: 'yyyy-mm-dd hh:MM:ss' },
        },
        form: {
            ...formBaseAttr,
            validator: getFormAttr('validator', ['date']),
        },
    },
    image: {
        name: 'Image',
        table: {
            render: getTableAttr('render', 'image'),
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    images: {
        name: 'Images',
        table: {
            render: getTableAttr('render', 'images'),
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    file: {
        name: 'File',
        table: {
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    files: {
        name: 'Files',
        table: {
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    editor: {
        name: 'Rich Text',
        table: {
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    array: {
        name: 'Array',
        table: {
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    city: {
        name: 'City Select',
        table: {
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    icon: {
        name: 'Icon',
        table: {
            render: getTableAttr('render', 'icon'),
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    color: {
        name: 'Color',
        table: {
            render: getTableAttr('render', 'color'),
            operator: getTableAttr('operator', 'false'),
        },
        form: { ...formBaseAttr },
    },
    weigh: {
        name: 'Weight (Drag Sort)',
        table: {
            operator: getTableAttr('operator', 'RANGE'),
            sortable: getTableAttr('sortable', 'custom'),
        },
        form: { ...formBaseAttr },
    },
    remoteSelect: {
        name: 'Remote Select',
        table: {
            render: getTableAttr('render', 'tags'),
            operator: getTableAttr('operator', 'LIKE'),
            comSearchRender: getTableAttr('comSearchRender', 'string'),
        },
        form: { ...formBaseAttr },
    },
    remoteSelects: {
        name: 'Remote Select (Multi)',
        table: {
            render: getTableAttr('render', 'tags'),
            operator: getTableAttr('operator', 'LIKE'),
            comSearchRender: getTableAttr('comSearchRender', 'string'),
        },
        form: { ...formBaseAttr },
    },
}

/**
 * 字段面板分类（参照 CRUD fieldItem）
 * 点击左侧字段类型可快速添加字段
 */
export interface FieldTemplate {
    title: string
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
        { title: 'Primary Key', name: 'id', designType: 'pk', formType: 'string' },
        { title: 'Primary Key (Snowflake)', name: 'id', designType: 'spk', formType: 'string' },
        { title: 'Status', name: 'status', designType: 'switch', formType: 'switch' },
        { title: 'Remark', name: 'remark', designType: 'textarea', formType: 'textarea' },
        { title: 'Weight (Drag Sort)', name: 'weigh', designType: 'weigh', formType: 'number' },
        { title: 'Update Time', name: 'update_time', designType: 'timestamp', formType: 'datetime' },
        { title: 'Create Time', name: 'create_time', designType: 'timestamp', formType: 'datetime' },
        { title: 'Remote Select (Association)', name: 'remote_select', designType: 'remoteSelect', formType: 'remoteSelect' },
    ],
    base: [
        { title: 'String', name: 'string', designType: 'string', formType: 'string' },
        { title: 'Number', name: 'number', designType: 'number', formType: 'number' },
        { title: 'Image', name: 'image', designType: 'image', formType: 'image' },
        { title: 'File', name: 'file', designType: 'file', formType: 'file' },
        { title: 'Radio', name: 'radio', designType: 'radio', formType: 'radio' },
        { title: 'Checkbox', name: 'checkbox', designType: 'checkbox', formType: 'checkbox' },
        { title: 'Select', name: 'select', designType: 'select', formType: 'select' },
        { title: 'Switch', name: 'switch', designType: 'switch', formType: 'switch' },
        { title: 'Rich Text', name: 'editor', designType: 'editor', formType: 'editor' },
        { title: 'Textarea', name: 'textarea', designType: 'textarea', formType: 'textarea' },
        { title: 'Password', name: 'password', designType: 'password', formType: 'password' },
        { title: 'Date', name: 'date', designType: 'date', formType: 'date' },
        { title: 'Time', name: 'time', designType: 'time', formType: 'time' },
        { title: 'DateTime', name: 'datetime', designType: 'datetime', formType: 'datetime' },
        { title: 'Year', name: 'year', designType: 'year', formType: 'year' },
        { title: 'Timestamp', name: 'timestamp', designType: 'timestamp', formType: 'datetime' },
    ],
    senior: [
        { title: 'Array', name: 'array', designType: 'array', formType: 'array' },
        { title: 'City Select', name: 'city', designType: 'city', formType: 'city' },
        { title: 'Icon', name: 'icon', designType: 'icon', formType: 'icon' },
        { title: 'Color', name: 'color', designType: 'color', formType: 'color' },
        { title: 'Images', name: 'images', designType: 'images', formType: 'images' },
        { title: 'Files', name: 'files', designType: 'files', formType: 'files' },
        { title: 'Select (Multi)', name: 'selects', designType: 'selects', formType: 'selects' },
        { title: 'Remote Select (Multi)', name: 'remote_selects', designType: 'remoteSelects', formType: 'remoteSelects' },
    ],
}

/**
 * 根据 designType 创建字段默认值
 */
export const createFieldFromTemplate = (template: FieldTemplate) => {
    const dt = designTypes[template.designType]
    const tableProps: Record<string, any> = {}
    const formProps: Record<string, any> = {}

    // 提取表格列属性默认值
    if (dt?.table) {
        for (const [key, val] of Object.entries(dt.table)) {
            tableProps[key] = (val as any).value
        }
    }

    // 提取表单属性默认值
    if (dt?.form) {
        for (const [key, val] of Object.entries(dt.form)) {
            formProps[key] = (val as any).value
        }
    }

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
        column_operator_placeholder: null,
        form_type: template.formType,
        form_validators: formProps.validator ?? [],
        form_input_attr: {},
        _designType: template.designType,
    }
}
