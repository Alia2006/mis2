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
                        :label="t('mis.employee.ID')"
                        type="string"
                        v-model="baTable.form.items!.ID"
                        prop="ID"
                        :placeholder="t('Please input field', { field: t('mis.employee.ID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.员工域账户')"
                        type="string"
                        v-model="baTable.form.items!.员工域账户"
                        prop="员工域账户"
                        :placeholder="t('Please input field', { field: t('mis.employee.员工域账户') })"
                    />
                    <FormItem
                        :label="t('mis.employee.DingTalkID')"
                        type="string"
                        v-model="baTable.form.items!.DingTalkID"
                        prop="DingTalkID"
                        :placeholder="t('Please input field', { field: t('mis.employee.DingTalkID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.员工姓名')"
                        type="string"
                        v-model="baTable.form.items!.员工姓名"
                        prop="员工姓名"
                        :placeholder="t('Please input field', { field: t('mis.employee.员工姓名') })"
                    />
                    <FormItem
                        :label="t('mis.employee.Name')"
                        type="string"
                        v-model="baTable.form.items!.Name"
                        prop="Name"
                        :placeholder="t('Please input field', { field: t('mis.employee.Name') })"
                    />
                    <FormItem
                        :label="t('mis.employee.Default_Project_ID')"
                        type="remoteSelect"
                        v-model="baTable.form.items!.Default_Project_ID"
                        prop="Default_Project_ID"
                        :input-attr="{ pk: 'id', field: 'name', remoteUrl: '' }"
                        :placeholder="t('Please select field', { field: t('mis.employee.Default_Project_ID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.性别')"
                        type="string"
                        v-model="baTable.form.items!.性别"
                        prop="性别"
                        :placeholder="t('Please input field', { field: t('mis.employee.性别') })"
                    />
                    <FormItem
                        :label="t('mis.employee.民族')"
                        type="number"
                        v-model="baTable.form.items!.民族"
                        prop="民族"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.民族') })"
                    />
                    <FormItem
                        :label="t('mis.employee.身份')"
                        type="number"
                        v-model="baTable.form.items!.身份"
                        prop="身份"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.身份') })"
                    />
                    <FormItem
                        :label="t('mis.employee.入职日期')"
                        type="datetime"
                        v-model="baTable.form.items!.入职日期"
                        prop="入职日期"
                        :placeholder="t('Please select field', { field: t('mis.employee.入职日期') })"
                    />
                    <FormItem
                        :label="t('mis.employee.离职日期')"
                        type="datetime"
                        v-model="baTable.form.items!.离职日期"
                        prop="离职日期"
                        :placeholder="t('Please select field', { field: t('mis.employee.离职日期') })"
                    />
                    <FormItem
                        :label="t('mis.employee.事业部ID')"
                        type="number"
                        v-model="baTable.form.items!.事业部ID"
                        prop="事业部ID"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.事业部ID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.部门ID')"
                        type="number"
                        v-model="baTable.form.items!.部门ID"
                        prop="部门ID"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.部门ID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.项目组ID')"
                        type="number"
                        v-model="baTable.form.items!.项目组ID"
                        prop="项目组ID"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.项目组ID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.出生日期')"
                        type="datetime"
                        v-model="baTable.form.items!.出生日期"
                        prop="出生日期"
                        :placeholder="t('Please select field', { field: t('mis.employee.出生日期') })"
                    />
                    <FormItem
                        :label="t('mis.employee.手机号')"
                        type="string"
                        v-model="baTable.form.items!.手机号"
                        prop="手机号"
                        :placeholder="t('Please input field', { field: t('mis.employee.手机号') })"
                    />
                    <FormItem
                        :label="t('mis.employee.紧急联系人')"
                        type="string"
                        v-model="baTable.form.items!.紧急联系人"
                        prop="紧急联系人"
                        :placeholder="t('Please input field', { field: t('mis.employee.紧急联系人') })"
                    />
                    <FormItem
                        :label="t('mis.employee.紧急人联系电话')"
                        type="string"
                        v-model="baTable.form.items!.紧急人联系电话"
                        prop="紧急人联系电话"
                        :placeholder="t('Please input field', { field: t('mis.employee.紧急人联系电话') })"
                    />
                    <FormItem
                        :label="t('mis.employee.分机号')"
                        type="string"
                        v-model="baTable.form.items!.分机号"
                        prop="分机号"
                        :placeholder="t('Please input field', { field: t('mis.employee.分机号') })"
                    />
                    <FormItem
                        :label="t('mis.employee.籍贯省份ID')"
                        type="number"
                        v-model="baTable.form.items!.籍贯省份ID"
                        prop="籍贯省份ID"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.籍贯省份ID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.籍贯市区ID')"
                        type="number"
                        v-model="baTable.form.items!.籍贯市区ID"
                        prop="籍贯市区ID"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.籍贯市区ID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.现家庭住址')"
                        type="string"
                        v-model="baTable.form.items!.现家庭住址"
                        prop="现家庭住址"
                        :placeholder="t('Please input field', { field: t('mis.employee.现家庭住址') })"
                    />
                    <FormItem
                        :label="t('mis.employee.个人简介')"
                        type="string"
                        v-model="baTable.form.items!.个人简介"
                        prop="个人简介"
                        :placeholder="t('Please input field', { field: t('mis.employee.个人简介') })"
                    />
                    <FormItem
                        :label="t('mis.employee.头像')"
                        type="string"
                        v-model="baTable.form.items!.头像"
                        prop="头像"
                        :placeholder="t('Please input field', { field: t('mis.employee.头像') })"
                    />
                    <FormItem
                        :label="t('mis.employee.Email')"
                        type="string"
                        v-model="baTable.form.items!.Email"
                        prop="Email"
                        :placeholder="t('Please input field', { field: t('mis.employee.Email') })"
                    />
                    <FormItem
                        :label="t('mis.employee.腾讯微信号码')"
                        type="string"
                        v-model="baTable.form.items!.腾讯微信号码"
                        prop="腾讯微信号码"
                        :placeholder="t('Please input field', { field: t('mis.employee.腾讯微信号码') })"
                    />
                    <FormItem
                        :label="t('mis.employee.试用期最终评价表')"
                        type="string"
                        v-model="baTable.form.items!.试用期最终评价表"
                        prop="试用期最终评价表"
                        :placeholder="t('Please input field', { field: t('mis.employee.试用期最终评价表') })"
                    />
                    <FormItem
                        :label="t('mis.employee.试用期过程评价')"
                        type="string"
                        v-model="baTable.form.items!.试用期过程评价"
                        prop="试用期过程评价"
                        :placeholder="t('Please input field', { field: t('mis.employee.试用期过程评价') })"
                    />
                    <FormItem
                        :label="t('mis.employee.身份证号码')"
                        type="string"
                        v-model="baTable.form.items!.身份证号码"
                        prop="身份证号码"
                        :placeholder="t('Please input field', { field: t('mis.employee.身份证号码') })"
                    />
                    <FormItem
                        :label="t('mis.employee.身份证有效期起始日期')"
                        type="datetime"
                        v-model="baTable.form.items!.身份证有效期起始日期"
                        prop="身份证有效期起始日期"
                        :placeholder="t('Please select field', { field: t('mis.employee.身份证有效期起始日期') })"
                    />
                    <FormItem
                        :label="t('mis.employee.身份证有效期')"
                        type="string"
                        v-model="baTable.form.items!.身份证有效期"
                        prop="身份证有效期"
                        :placeholder="t('Please input field', { field: t('mis.employee.身份证有效期') })"
                    />
                    <FormItem
                        :label="t('mis.employee.护照有效期起始日期')"
                        type="datetime"
                        v-model="baTable.form.items!.护照有效期起始日期"
                        prop="护照有效期起始日期"
                        :placeholder="t('Please select field', { field: t('mis.employee.护照有效期起始日期') })"
                    />
                    <FormItem
                        :label="t('mis.employee.护照有效期')"
                        type="string"
                        v-model="baTable.form.items!.护照有效期"
                        prop="护照有效期"
                        :placeholder="t('Please input field', { field: t('mis.employee.护照有效期') })"
                    />
                    <FormItem
                        :label="t('mis.employee.护照号码')"
                        type="string"
                        v-model="baTable.form.items!.护照号码"
                        prop="护照号码"
                        :placeholder="t('Please input field', { field: t('mis.employee.护照号码') })"
                    />
                    <FormItem
                        :label="t('mis.employee.是否有护照')"
                        type="number"
                        v-model="baTable.form.items!.是否有护照"
                        prop="是否有护照"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.是否有护照') })"
                    />
                    <FormItem
                        :label="t('mis.employee.户口所在地市区ID')"
                        type="number"
                        v-model="baTable.form.items!.户口所在地市区ID"
                        prop="户口所在地市区ID"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.户口所在地市区ID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.户口所在地区')"
                        type="string"
                        v-model="baTable.form.items!.户口所在地区"
                        prop="户口所在地区"
                        :placeholder="t('Please input field', { field: t('mis.employee.户口所在地区') })"
                    />
                    <FormItem
                        :label="t('mis.employee.户口所在地街道')"
                        type="string"
                        v-model="baTable.form.items!.户口所在地街道"
                        prop="户口所在地街道"
                        :placeholder="t('Please input field', { field: t('mis.employee.户口所在地街道') })"
                    />
                    <FormItem
                        :label="t('mis.employee.户口性质')"
                        type="string"
                        v-model="baTable.form.items!.户口性质"
                        prop="户口性质"
                        :placeholder="t('Please input field', { field: t('mis.employee.户口性质') })"
                    />
                    <FormItem
                        :label="t('mis.employee.个人特长')"
                        type="string"
                        v-model="baTable.form.items!.个人特长"
                        prop="个人特长"
                        :placeholder="t('Please input field', { field: t('mis.employee.个人特长') })"
                    />
                    <FormItem
                        :label="t('mis.employee.车牌号')"
                        type="string"
                        v-model="baTable.form.items!.车牌号"
                        prop="车牌号"
                        :placeholder="t('Please input field', { field: t('mis.employee.车牌号') })"
                    />
                    <FormItem
                        :label="t('mis.employee.工牌号码')"
                        type="string"
                        v-model="baTable.form.items!.工牌号码"
                        prop="工牌号码"
                        :placeholder="t('Please input field', { field: t('mis.employee.工牌号码') })"
                    />
                    <FormItem
                        :label="t('mis.employee.是否已婚')"
                        type="number"
                        v-model="baTable.form.items!.是否已婚"
                        prop="是否已婚"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.是否已婚') })"
                    />
                    <FormItem
                        :label="t('mis.employee.前工作单位')"
                        type="string"
                        v-model="baTable.form.items!.前工作单位"
                        prop="前工作单位"
                        :placeholder="t('Please input field', { field: t('mis.employee.前工作单位') })"
                    />
                    <FormItem
                        :label="t('mis.employee.首次参加工作时间')"
                        type="datetime"
                        v-model="baTable.form.items!.首次参加工作时间"
                        prop="首次参加工作时间"
                        :placeholder="t('Please select field', { field: t('mis.employee.首次参加工作时间') })"
                    />
                    <FormItem
                        :label="t('mis.employee.首次参加社保时间')"
                        type="datetime"
                        v-model="baTable.form.items!.首次参加社保时间"
                        prop="首次参加社保时间"
                        :placeholder="t('Please select field', { field: t('mis.employee.首次参加社保时间') })"
                    />
                    <FormItem
                        :label="t('mis.employee.社保所在地')"
                        type="string"
                        v-model="baTable.form.items!.社保所在地"
                        prop="社保所在地"
                        :placeholder="t('Please input field', { field: t('mis.employee.社保所在地') })"
                    />
                    <FormItem
                        :label="t('mis.employee.公积金所在地')"
                        type="string"
                        v-model="baTable.form.items!.公积金所在地"
                        prop="公积金所在地"
                        :placeholder="t('Please input field', { field: t('mis.employee.公积金所在地') })"
                    />
                    <FormItem
                        :label="t('mis.employee.是否需要办理公积金')"
                        type="number"
                        v-model="baTable.form.items!.是否需要办理公积金"
                        prop="是否需要办理公积金"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.是否需要办理公积金') })"
                    />
                    <FormItem
                        :label="t('mis.employee.工资卡号')"
                        type="string"
                        v-model="baTable.form.items!.工资卡号"
                        prop="工资卡号"
                        :placeholder="t('Please input field', { field: t('mis.employee.工资卡号') })"
                    />
                    <FormItem
                        :label="t('mis.employee.身份证正面图片')"
                        type="string"
                        v-model="baTable.form.items!.身份证正面图片"
                        prop="身份证正面图片"
                        :placeholder="t('Please input field', { field: t('mis.employee.身份证正面图片') })"
                    />
                    <FormItem
                        :label="t('mis.employee.身份证反面图片')"
                        type="string"
                        v-model="baTable.form.items!.身份证反面图片"
                        prop="身份证反面图片"
                        :placeholder="t('Please input field', { field: t('mis.employee.身份证反面图片') })"
                    />
                    <FormItem
                        :label="t('mis.employee.IRDNumber')"
                        type="number"
                        v-model="baTable.form.items!.IRDNumber"
                        prop="IRDNumber"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.IRDNumber') })"
                    />
                    <FormItem
                        :label="t('mis.employee.Position')"
                        type="string"
                        v-model="baTable.form.items!.Position"
                        prop="Position"
                        :placeholder="t('Please input field', { field: t('mis.employee.Position') })"
                    />
                    <FormItem
                        :label="t('mis.employee.HourlyRateGross')"
                        type="number"
                        v-model="baTable.form.items!.HourlyRateGross"
                        prop="HourlyRateGross"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.HourlyRateGross') })"
                    />
                    <FormItem
                        :label="t('mis.employee.VisaNumber')"
                        type="number"
                        v-model="baTable.form.items!.VisaNumber"
                        prop="VisaNumber"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('mis.employee.VisaNumber') })"
                    />
                    <FormItem
                        :label="t('mis.employee.VisaExpireDate')"
                        type="datetime"
                        v-model="baTable.form.items!.VisaExpireDate"
                        prop="VisaExpireDate"
                        :placeholder="t('Please select field', { field: t('mis.employee.VisaExpireDate') })"
                    />
                    <FormItem
                        :label="t('mis.employee.VisaStartDate')"
                        type="datetime"
                        v-model="baTable.form.items!.VisaStartDate"
                        prop="VisaStartDate"
                        :placeholder="t('Please select field', { field: t('mis.employee.VisaStartDate') })"
                    />
                    <FormItem
                        :label="t('mis.employee.Visa_Type')"
                        type="string"
                        v-model="baTable.form.items!.Visa_Type"
                        prop="Visa_Type"
                        :placeholder="t('Please input field', { field: t('mis.employee.Visa_Type') })"
                    />
                    <FormItem
                        :label="t('mis.employee.SitesafetyCardId')"
                        type="string"
                        v-model="baTable.form.items!.SitesafetyCardId"
                        prop="SitesafetyCardId"
                        :placeholder="t('Please input field', { field: t('mis.employee.SitesafetyCardId') })"
                    />
                    <FormItem
                        :label="t('mis.employee.CourseDate')"
                        type="datetime"
                        v-model="baTable.form.items!.CourseDate"
                        prop="CourseDate"
                        :placeholder="t('Please select field', { field: t('mis.employee.CourseDate') })"
                    />
                    <FormItem
                        :label="t('mis.employee.CourseName')"
                        type="string"
                        v-model="baTable.form.items!.CourseName"
                        prop="CourseName"
                        :placeholder="t('Please input field', { field: t('mis.employee.CourseName') })"
                    />
                    <FormItem
                        :label="t('mis.employee.SiteSafetyCardExpireDate')"
                        type="datetime"
                        v-model="baTable.form.items!.SiteSafetyCardExpireDate"
                        prop="SiteSafetyCardExpireDate"
                        :placeholder="t('Please select field', { field: t('mis.employee.SiteSafetyCardExpireDate') })"
                    />
                    <FormItem
                        :label="t('mis.employee.SiteSafetyCardPic')"
                        type="string"
                        v-model="baTable.form.items!.SiteSafetyCardPic"
                        prop="SiteSafetyCardPic"
                        :placeholder="t('Please input field', { field: t('mis.employee.SiteSafetyCardPic') })"
                    />
                    <FormItem
                        :label="t('mis.employee.DriverLicenceID')"
                        type="string"
                        v-model="baTable.form.items!.DriverLicenceID"
                        prop="DriverLicenceID"
                        :placeholder="t('Please input field', { field: t('mis.employee.DriverLicenceID') })"
                    />
                    <FormItem
                        :label="t('mis.employee.Conditions')"
                        type="string"
                        v-model="baTable.form.items!.Conditions"
                        prop="Conditions"
                        :placeholder="t('Please input field', { field: t('mis.employee.Conditions') })"
                    />
                    <FormItem
                        :label="t('mis.employee.Class')"
                        type="string"
                        v-model="baTable.form.items!.Class"
                        prop="Class"
                        :placeholder="t('Please input field', { field: t('mis.employee.Class') })"
                    />
                    <FormItem
                        :label="t('mis.employee.Issued')"
                        type="datetime"
                        v-model="baTable.form.items!.Issued"
                        prop="Issued"
                        :placeholder="t('Please select field', { field: t('mis.employee.Issued') })"
                    />
                    <FormItem
                        :label="t('mis.employee.Expires')"
                        type="datetime"
                        v-model="baTable.form.items!.Expires"
                        prop="Expires"
                        :placeholder="t('Please select field', { field: t('mis.employee.Expires') })"
                    />
                    <FormItem
                        :label="t('mis.employee.DriverLicencePicFront')"
                        type="string"
                        v-model="baTable.form.items!.DriverLicencePicFront"
                        prop="DriverLicencePicFront"
                        :placeholder="t('Please input field', { field: t('mis.employee.DriverLicencePicFront') })"
                    />
                    <FormItem
                        :label="t('mis.employee.DriverLicencePicBack')"
                        type="string"
                        v-model="baTable.form.items!.DriverLicencePicBack"
                        prop="DriverLicencePicBack"
                        :placeholder="t('Please input field', { field: t('mis.employee.DriverLicencePicBack') })"
                    />
                    <FormItem
                        :label="t('mis.employee.FGC_Creator')"
                        type="string"
                        v-model="baTable.form.items!.FGC_Creator"
                        prop="FGC_Creator"
                        :placeholder="t('Please input field', { field: t('mis.employee.FGC_Creator') })"
                    />
                    <FormItem
                        :label="t('mis.employee.FGC_CreateDate')"
                        type="datetime"
                        v-model="baTable.form.items!.FGC_CreateDate"
                        prop="FGC_CreateDate"
                        :placeholder="t('Please select field', { field: t('mis.employee.FGC_CreateDate') })"
                    />
                    <FormItem
                        :label="t('mis.employee.FGC_LastModifier')"
                        type="string"
                        v-model="baTable.form.items!.FGC_LastModifier"
                        prop="FGC_LastModifier"
                        :placeholder="t('Please input field', { field: t('mis.employee.FGC_LastModifier') })"
                    />
                    <FormItem
                        :label="t('mis.employee.FGC_LastModifyDate')"
                        type="datetime"
                        v-model="baTable.form.items!.FGC_LastModifyDate"
                        prop="FGC_LastModifyDate"
                        :placeholder="t('Please select field', { field: t('mis.employee.FGC_LastModifyDate') })"
                    />
                    <FormItem
                        :label="t('mis.employee.FGC_Rowversion')"
                        type="datetime"
                        v-model="baTable.form.items!.FGC_Rowversion"
                        prop="FGC_Rowversion"
                        :placeholder="t('Please select field', { field: t('mis.employee.FGC_Rowversion') })"
                    />
                    <FormItem
                        :label="t('mis.employee.FGC_UpdateHelp')"
                        type="string"
                        v-model="baTable.form.items!.FGC_UpdateHelp"
                        prop="FGC_UpdateHelp"
                        :placeholder="t('Please input field', { field: t('mis.employee.FGC_UpdateHelp') })"
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
    民族: [buildValidatorData({ name: 'number', title: t('mis.employee.民族') })],
    身份: [buildValidatorData({ name: 'number', title: t('mis.employee.身份') })],
    入职日期: [buildValidatorData({ name: 'date', title: t('mis.employee.入职日期') })],
    离职日期: [buildValidatorData({ name: 'date', title: t('mis.employee.离职日期') })],
    事业部ID: [buildValidatorData({ name: 'number', title: t('mis.employee.事业部ID') })],
    部门ID: [buildValidatorData({ name: 'number', title: t('mis.employee.部门ID') })],
    项目组ID: [buildValidatorData({ name: 'number', title: t('mis.employee.项目组ID') })],
    出生日期: [buildValidatorData({ name: 'date', title: t('mis.employee.出生日期') })],
    籍贯省份ID: [buildValidatorData({ name: 'number', title: t('mis.employee.籍贯省份ID') })],
    籍贯市区ID: [buildValidatorData({ name: 'number', title: t('mis.employee.籍贯市区ID') })],
    身份证有效期起始日期: [buildValidatorData({ name: 'date', title: t('mis.employee.身份证有效期起始日期') })],
    护照有效期起始日期: [buildValidatorData({ name: 'date', title: t('mis.employee.护照有效期起始日期') })],
    是否有护照: [buildValidatorData({ name: 'number', title: t('mis.employee.是否有护照') })],
    户口所在地市区ID: [buildValidatorData({ name: 'number', title: t('mis.employee.户口所在地市区ID') })],
    是否已婚: [buildValidatorData({ name: 'number', title: t('mis.employee.是否已婚') })],
    首次参加工作时间: [buildValidatorData({ name: 'date', title: t('mis.employee.首次参加工作时间') })],
    首次参加社保时间: [buildValidatorData({ name: 'date', title: t('mis.employee.首次参加社保时间') })],
    是否需要办理公积金: [buildValidatorData({ name: 'number', title: t('mis.employee.是否需要办理公积金') })],
    IRDNumber: [buildValidatorData({ name: 'number', title: t('mis.employee.IRDNumber') })],
    HourlyRateGross: [buildValidatorData({ name: 'number', title: t('mis.employee.HourlyRateGross') })],
    VisaNumber: [buildValidatorData({ name: 'number', title: t('mis.employee.VisaNumber') })],
    VisaExpireDate: [buildValidatorData({ name: 'date', title: t('mis.employee.VisaExpireDate') })],
    VisaStartDate: [buildValidatorData({ name: 'date', title: t('mis.employee.VisaStartDate') })],
    CourseDate: [buildValidatorData({ name: 'date', title: t('mis.employee.CourseDate') })],
    SiteSafetyCardExpireDate: [buildValidatorData({ name: 'date', title: t('mis.employee.SiteSafetyCardExpireDate') })],
    Issued: [buildValidatorData({ name: 'date', title: t('mis.employee.Issued') })],
    Expires: [buildValidatorData({ name: 'date', title: t('mis.employee.Expires') })],
    FGC_CreateDate: [buildValidatorData({ name: 'date', title: t('mis.employee.FGC_CreateDate') })],
    FGC_LastModifyDate: [buildValidatorData({ name: 'date', title: t('mis.employee.FGC_LastModifyDate') })],
    FGC_Rowversion: [buildValidatorData({ name: 'date', title: t('mis.employee.FGC_Rowversion') })],
})
</script>

<style scoped lang="scss"></style>
