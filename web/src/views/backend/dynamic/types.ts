import type { HeaderOptButton } from '/@/types/table'
import type { OperatorStr, TableRenderer } from '/@/types/table'

/**
 * 动态表格完整配置（后端 API 返回、前端消费）
 */
export interface DynamicTableConfig {
    /** 表标识，如 'employee' */
    name: string
    /** 显示名，如 '员工信息' */
    title: string
    /** 数据API地址，如 '/admin/dynamic.Table/' */
    controllerUrl: string
    /** 附加请求参数，如 { table: 'employee' } */
    controllerParams?: Record<string, any>
    /** 主键字段 */
    pk: string
    /** 表格备注 */
    remark?: string
    /** 快速搜索占位提示 */
    quickSearchPlaceholder?: string
    /** 默认排序 */
    defaultOrder?: { prop: string; order: 'desc' | 'asc' }
    /** 表头按钮 */
    headerButtons: HeaderOptButton[]
    /** 行操作按钮名称，如 ['edit', 'delete'] */
    rowButtonNames: string[]
    /** 新增默认值 */
    defaultItems?: Record<string, any>
    /** 双击不打开编辑弹窗的列（如 switch 列） */
    dblClickNotEditColumn?: (string | undefined | null)[]

    /** 列定义 */
    columns: DynamicColumn[]
    /** 表单字段定义 */
    formFields: DynamicFormField[]

    /** 详情表配置（配置了详情表时非 null） */
    detail?: DetailTableConfig | null
}

/**
 * 详情表配置
 */
export interface DetailTableConfig {
    /** 关联的动态表配置 ID */
    tableId: number
    /** 详情表标识名 */
    tableName: string
    /** 详情表标题 */
    title: string
    /** 详情表中用于过滤的外键字段名 */
    foreignKey: string
    /** 详情表的主键 */
    pk: string
}

/**
 * 单列配置 — 直接映射 TableColumn 接口的子集
 */
export interface DynamicColumn {
    prop: string
    label: string
    width?: number
    align?: 'center' | 'left' | 'right'
    /** 单元格渲染器 */
    render?: TableRenderer
    /** 搜索操作符，false 禁用搜索 */
    operator?: OperatorStr | false
    /** 是否可排序 */
    sortable?: boolean | 'custom'
    /** 公共搜索渲染方式 */
    comSearchRender?: 'string' | 'remoteSelect' | 'select' | 'time' | 'date' | 'datetime'
    /** 是否显示此列 */
    show?: boolean
    /** 字典替换数据 { value: 'label', ... } */
    replaceValue?: Record<string, any>
    /** tag 自定义颜色 { value: 'success', ... } */
    custom?: Record<string, any>
    /** 日期时间格式 */
    timeFormat?: string
    /** 搜索框 placeholder */
    operatorPlaceholder?: string
    /** computed 列的模板表达式，如 "{price} * {quantity}" */
    template?: string
}

/**
 * 单表单字段配置 — 直接映射 FormItem 组件 props
 */
export interface DynamicFormField {
    prop: string
    label: string
    /** 输入类型：string | number | datetime | textarea | switch | remoteSelect | ... */
    type: string
    placeholder?: string
    /** 校验器名称数组，如 ['required', 'number', 'date'] */
    validators?: string[]
    /** FormItem 的 inputAttr */
    inputAttr?: Record<string, any>
}

/**
 * 后端字段配置原始记录（dynamic_table_config.fields JSON 数组元素）
 */
export interface TableFieldRecord {
    id: number
    table_id: number
    prop: string
    label: string
    sort: number

    // 列属性
    column_show: number
    column_width: number | null
    column_align: string
    column_render: string | null
    column_operator: string
    column_sortable: string
    column_com_search_render: string | null
    column_replace_value: string | null
    column_custom: string | null
    column_time_format: string | null
    column_operator_placeholder: string | null

    // 表单属性
    form_type: string
    form_validators: string | null
    form_input_attr: string | null
}

/**
 * 后端表配置原始记录（dynamic_table_config 表行）
 */
export interface TableConfigRecord {
    id: number
    name: string
    title: string
    db_table: string
    db_connection: string | null
    pk: string
    quick_search_fields: string | null
    default_sort_field: string | null
    default_sort_order: string
    header_buttons: string
    row_buttons: string
    default_items: string | null
    remark: string | null
    status: string
    fields?: TableFieldRecord[]
}
