<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <!-- 自定义按钮请使用插槽，甚至公共搜索也可以使用具名插槽渲染，参见文档 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('mis.employee.quick Search Fields') })"
        ></TableHeader>

        <!-- 表格 -->
        <!-- 表格列有多种自定义渲染方式，比如自定义组件、具名插槽等，参见文档 -->
        <!-- 要使用 el-table 组件原有的属性，直接加在 Table 标签上即可 -->
        <Table ref="tableRef"></Table>

        <!-- 表单 -->
        <PopupForm />
    </div>
</template>

<script setup lang="ts">
import { onMounted, provide, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import PopupForm from './popupForm.vue'
import { baTableApi } from '/@/api/common'
import { defaultOptButtons } from '/@/components/table'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import baTableClass from '/@/utils/baTable'

defineOptions({
    name: 'mis/employee',
})

const { t } = useI18n()
const tableRef = useTemplateRef('tableRef')
const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])

/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/mis.Employee/'),
    {
        pk: 'ID',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('mis.employee.ID'), prop: 'ID', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            {
                label: t('mis.employee.员工域账户'),
                prop: '员工域账户',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.DingTalkID'),
                prop: 'DingTalkID',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.员工姓名'),
                prop: '员工姓名',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.Name'),
                prop: 'Name',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.Default_Project_ID'),
                prop: 'Default_Project_ID',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                render: 'tags',
                operator: 'LIKE',
                comSearchRender: 'string',
            },
            {
                label: t('mis.employee.性别'),
                prop: '性别',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            { label: t('mis.employee.民族'), prop: '民族', align: 'center', sortable: false, operator: 'RANGE' },
            { label: t('mis.employee.身份'), prop: '身份', align: 'center', sortable: false, operator: 'RANGE' },
            {
                label: t('mis.employee.入职日期'),
                prop: '入职日期',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.离职日期'),
                prop: '离职日期',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            { label: t('mis.employee.事业部ID'), prop: '事业部ID', align: 'center', sortable: false, operator: 'RANGE' },
            { label: t('mis.employee.部门ID'), prop: '部门ID', align: 'center', sortable: false, operator: 'RANGE' },
            { label: t('mis.employee.项目组ID'), prop: '项目组ID', align: 'center', sortable: false, operator: 'RANGE' },
            {
                label: t('mis.employee.出生日期'),
                prop: '出生日期',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.手机号'),
                prop: '手机号',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.紧急联系人'),
                prop: '紧急联系人',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.紧急人联系电话'),
                prop: '紧急人联系电话',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.分机号'),
                prop: '分机号',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            { label: t('mis.employee.籍贯省份ID'), prop: '籍贯省份ID', align: 'center', sortable: false, operator: 'RANGE' },
            { label: t('mis.employee.籍贯市区ID'), prop: '籍贯市区ID', align: 'center', sortable: false, operator: 'RANGE' },
            {
                label: t('mis.employee.现家庭住址'),
                prop: '现家庭住址',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.个人简介'),
                prop: '个人简介',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.头像'),
                prop: '头像',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.Email'),
                prop: 'Email',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.腾讯微信号码'),
                prop: '腾讯微信号码',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.试用期最终评价表'),
                prop: '试用期最终评价表',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.试用期过程评价'),
                prop: '试用期过程评价',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.身份证号码'),
                prop: '身份证号码',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.身份证有效期起始日期'),
                prop: '身份证有效期起始日期',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.身份证有效期'),
                prop: '身份证有效期',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.护照有效期起始日期'),
                prop: '护照有效期起始日期',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.护照有效期'),
                prop: '护照有效期',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.护照号码'),
                prop: '护照号码',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            { label: t('mis.employee.是否有护照'), prop: '是否有护照', align: 'center', sortable: false, operator: 'RANGE' },
            { label: t('mis.employee.户口所在地市区ID'), prop: '户口所在地市区ID', align: 'center', sortable: false, operator: 'RANGE' },
            {
                label: t('mis.employee.户口所在地区'),
                prop: '户口所在地区',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.户口所在地街道'),
                prop: '户口所在地街道',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.户口性质'),
                prop: '户口性质',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.个人特长'),
                prop: '个人特长',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.车牌号'),
                prop: '车牌号',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.工牌号码'),
                prop: '工牌号码',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            { label: t('mis.employee.是否已婚'), prop: '是否已婚', align: 'center', sortable: false, operator: 'RANGE' },
            {
                label: t('mis.employee.前工作单位'),
                prop: '前工作单位',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.首次参加工作时间'),
                prop: '首次参加工作时间',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.首次参加社保时间'),
                prop: '首次参加社保时间',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.社保所在地'),
                prop: '社保所在地',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.公积金所在地'),
                prop: '公积金所在地',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            { label: t('mis.employee.是否需要办理公积金'), prop: '是否需要办理公积金', align: 'center', sortable: false, operator: 'RANGE' },
            {
                label: t('mis.employee.工资卡号'),
                prop: '工资卡号',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.身份证正面图片'),
                prop: '身份证正面图片',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.身份证反面图片'),
                prop: '身份证反面图片',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            { label: t('mis.employee.IRDNumber'), prop: 'IRDNumber', align: 'center', sortable: false, operator: 'RANGE' },
            {
                label: t('mis.employee.Position'),
                prop: 'Position',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            { label: t('mis.employee.HourlyRateGross'), prop: 'HourlyRateGross', align: 'center', sortable: false, operator: 'RANGE' },
            { label: t('mis.employee.VisaNumber'), prop: 'VisaNumber', align: 'center', sortable: false, operator: 'RANGE' },
            {
                label: t('mis.employee.VisaExpireDate'),
                prop: 'VisaExpireDate',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.VisaStartDate'),
                prop: 'VisaStartDate',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.Visa_Type'),
                prop: 'Visa_Type',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.SitesafetyCardId'),
                prop: 'SitesafetyCardId',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.CourseDate'),
                prop: 'CourseDate',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.CourseName'),
                prop: 'CourseName',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.SiteSafetyCardExpireDate'),
                prop: 'SiteSafetyCardExpireDate',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.SiteSafetyCardPic'),
                prop: 'SiteSafetyCardPic',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.DriverLicenceID'),
                prop: 'DriverLicenceID',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.Conditions'),
                prop: 'Conditions',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.Class'),
                prop: 'Class',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.Issued'),
                prop: 'Issued',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.Expires'),
                prop: 'Expires',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.DriverLicencePicFront'),
                prop: 'DriverLicencePicFront',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.DriverLicencePicBack'),
                prop: 'DriverLicencePicBack',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.FGC_Creator'),
                prop: 'FGC_Creator',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.FGC_CreateDate'),
                prop: 'FGC_CreateDate',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.FGC_LastModifier'),
                prop: 'FGC_LastModifier',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            {
                label: t('mis.employee.FGC_LastModifyDate'),
                prop: 'FGC_LastModifyDate',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.FGC_Rowversion'),
                prop: 'FGC_Rowversion',
                align: 'center',
                operator: 'RANGE',
                comSearchRender: 'datetime',
                sortable: 'custom',
                width: 160,
            },
            {
                label: t('mis.employee.FGC_UpdateHelp'),
                prop: 'FGC_UpdateHelp',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                sortable: false,
                operator: 'LIKE',
            },
            { label: t('Operate'), align: 'center', width: 100, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [undefined],
        defaultOrder: { prop: 'ID', order: 'desc' },
    },
    {
        defaultItems: { FGC_Rowversion: 'CURRENT_TIMESTAMP' },
    }
)

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getData()?.then(() => {
        baTable.initSort()
        baTable.dragSort()
    })
})
</script>

<style scoped lang="scss"></style>
