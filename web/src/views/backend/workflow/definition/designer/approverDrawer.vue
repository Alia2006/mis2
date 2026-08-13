<template>
    <el-drawer :append-to-body="true" title="审批人设置" v-model="visible" class="set_promoter" :show-close="false" :size="550" :before-close="saveApprover">
        <div class="demo-drawer__content">
            <div class="drawer_content" style="padding: 0 20px">
                <div class="approver_content" style="padding-bottom: 10px; border-bottom: 1px solid #f2f2f2">
                    <el-radio-group v-model="approverConfig.settype" @change="changeType">
                        <el-radio v-for="({ value, label }) in setTypes" :key="value" :value="value">{{ label }}</el-radio>
                    </el-radio-group>
                    <el-button type="primary" @click="addApprover" v-if="approverConfig.settype == 1" style="margin-bottom: 20px">添加/修改成员</el-button>
                    <p class="selected_list" v-if="approverConfig.settype == 1">
                        <span v-for="(item, index) in approverConfig.nodeUserList" :key="index">{{ item.name }}
                            <el-icon style="margin-left: 5px; cursor: pointer" @click="$func.removeEle(approverConfig.nodeUserList, item, 'targetId')"><Close /></el-icon>
                        </span>
                        <a v-if="approverConfig.nodeUserList.length != 0" style="cursor: pointer; color: #3296fa" @click="approverConfig.nodeUserList = []">清除</a>
                    </p>
                </div>
                <div class="approver_manager" v-if="approverConfig.settype == 2" style="padding: 20px 0 0">
                    <p><span>发起人的：</span>
                        <el-select v-model="approverConfig.directorLevel" style="width: 200px">
                            <el-option v-for="item in directorMaxLevel" :key="item" :label="item == 1 ? '直接主管' : '第' + item + '级主管'" :value="item" />
                        </el-select>
                    </p>
                    <p style="margin: 10px 0; font-size: 12px; color: #f8642d">找不到主管时，由上级主管代审批</p>
                </div>
                <div style="padding: 28px 20px" v-if="approverConfig.settype == 5">
                    <p>该审批节点设置"发起人自己"后，审批人默认为发起人</p>
                </div>
                <div v-show="approverConfig.settype == 4" style="padding: 20px 0 0">
                    <el-radio-group v-model="approverConfig.selectMode" style="width: 100%">
                        <el-radio v-for="({ value, label }) in selectModes" :key="value" :value="value">{{ label }}</el-radio>
                    </el-radio-group>
                    <h3 style="margin: 5px 0 20px; font-size: 14px">选择范围</h3>
                    <el-radio-group v-model="approverConfig.selectRange" style="width: 100%" @change="changeRange">
                        <el-radio v-for="({ value, label }) in selectRanges" :key="value" :value="value">{{ label }}</el-radio>
                    </el-radio-group>
                    <template v-if="approverConfig.selectRange == 2 || approverConfig.selectRange == 3">
                        <el-button type="primary" @click="addApprover" v-if="approverConfig.selectRange == 2">添加/修改成员</el-button>
                        <el-button type="primary" @click="addRoleApprover" v-else>添加/修改角色</el-button>
                        <p class="selected_list">
                            <span v-for="(item, index) in approverConfig.nodeUserList" :key="index">{{ item.name }}
                                <el-icon style="margin-left: 5px; cursor: pointer" @click="$func.removeEle(approverConfig.nodeUserList, item, 'targetId')"><Close /></el-icon>
                            </span>
                        </p>
                    </template>
                </div>
                <div class="approver_manager" v-if="approverConfig.settype == 7" style="padding: 20px 0 0">
                    <p>审批终点</p>
                    <p style="padding-bottom: 20px"><span>发起人的：</span>
                        <el-select v-model="approverConfig.examineEndDirectorLevel" style="width: 200px">
                            <el-option v-for="item in directorMaxLevel" :key="item" :label="item == 1 ? '最高' : '第' + item + '层级主管'" :value="item" />
                        </el-select>
                    </p>
                </div>
                <div style="padding: 20px 0" v-if="(approverConfig.settype == 1 && approverConfig.nodeUserList.length > 1) || approverConfig.settype == 2 || (approverConfig.settype == 4 && approverConfig.selectMode == 2)">
                    <p style="margin-bottom: 14px">多人审批时采用的审批方式</p>
                    <el-radio-group v-model="approverConfig.examineMode">
                        <el-radio :value="1">依次审批</el-radio>
                        <el-radio :value="2" v-if="approverConfig.settype != 2">会签(须所有审批人同意)</el-radio>
                    </el-radio-group>
                </div>
                <div style="padding: 20px 0" v-if="approverConfig.settype == 2 || approverConfig.settype == 7">
                    <p style="margin-bottom: 14px">审批人为空时</p>
                    <el-radio-group v-model="approverConfig.noHanderAction">
                        <el-radio :value="1">自动审批通过/不允许发起</el-radio>
                        <el-radio :value="2">转交给审核管理员</el-radio>
                    </el-radio-group>
                </div>
            </div>
            <div style="padding: 20px; text-align: right">
                <el-button type="primary" @click="saveApprover">确 定</el-button>
                <el-button @click="closeDrawer">取 消</el-button>
            </div>
            <employeesDialog v-model:visible="approverVisible" :data="checkedList" @change="sureApprover" />
            <roleDialog v-model:visible="approverRoleVisible" :data="checkedRoleList" @change="sureRoleApprover" />
        </div>
    </el-drawer>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Close } from '@element-plus/icons-vue'
import $func from './helpers'
import { setTypes, selectModes, selectRanges } from './const'
import { useWorkflowStore } from './store'
import employeesDialog from './employeesDialog.vue'
import roleDialog from './roleDialog.vue'

const props = defineProps({ directorMaxLevel: { type: Number, default: 4 } })

let approverConfig = ref<any>({})
let approverVisible = ref(false)
let approverRoleVisible = ref(false)
let checkedRoleList = ref<any[]>([])
let checkedList = ref<any[]>([])
let store = useWorkflowStore()
let { setApproverConfig, setApprover } = store
let approverConfig1 = computed(() => store.approverConfig1)
let approverDrawer = computed(() => store.approverDrawer)
let visible = computed({ get: () => approverDrawer.value, set: () => closeDrawer() })

watch(approverConfig1, (val: any) => { approverConfig.value = val.value })

const changeRange = () => { approverConfig.value.nodeUserList = [] }
const changeType = (val: any) => {
    approverConfig.value.nodeUserList = []
    approverConfig.value.examineMode = 1
    approverConfig.value.noHanderAction = 2
    if (val == 2) approverConfig.value.directorLevel = 1
    else if (val == 4) { approverConfig.value.selectMode = 1; approverConfig.value.selectRange = 1 }
    else if (val == 7) approverConfig.value.examineEndDirectorLevel = 1
}
const addApprover = () => { approverVisible.value = true; checkedList.value = approverConfig.value.nodeUserList }
const addRoleApprover = () => { approverRoleVisible.value = true; checkedRoleList.value = approverConfig.value.nodeUserList }
const sureApprover = (data: any[]) => { approverConfig.value.nodeUserList = data; approverVisible.value = false }
const sureRoleApprover = (data: any[]) => { approverConfig.value.nodeUserList = data; approverRoleVisible.value = false }
const saveApprover = () => {
    approverConfig.value.error = !$func.setApproverStr(approverConfig.value)
    setApproverConfig({ value: approverConfig.value, flag: true, id: approverConfig1.value.id })
    closeDrawer()
}
const closeDrawer = () => setApprover(false)
</script>
