<template>
    <el-drawer :append-to-body="true" title="条件设置" v-model="visible" class="condition_copyer" :show-close="false" :size="550" :before-close="saveCondition">
        <div class="demo-drawer__content">
            <div style="padding: 20px 20px 0">
                <p style="margin: 10px 0; padding: 10px 17px; line-height: 24px; background: rgba(241,249,255,1); border: 1px solid rgba(64,163,247,1); color: #46a6fe; border-radius: 4px">当审批单同时满足以下条件时进入此流程</p>
                <ul style="max-height: 300px; overflow-y: auto; margin-bottom: 20px">
                    <li v-for="(item, index) in conditionConfig.conditionList" :key="index" style="margin-bottom: 15px">
                        <span style="display: inline-block; width: 80px; text-align: right; margin-right: 8px; line-height: 32px">{{ item.type == 1 ? '发起人' : item.showName }}：</span>
                        <div v-if="item.type == 1" style="display: inline-block; width: 300px">
                            <p :class="{ selected_list: conditionConfig.nodeUserList.length > 0 }" style="padding: 4px 10px; border: 1px solid #d9d9d9; border-radius: 4px; min-height: 32px; cursor: text" @click.self="addConditionRole">
                                <span v-for="(item1, index1) in conditionConfig.nodeUserList" :key="index1" style="margin-right: 6px">{{ item1.name }}
                                    <el-icon style="cursor: pointer" @click="$func.removeEle(conditionConfig.nodeUserList, item1, 'targetId')"><Close /></el-icon>
                                </span>
                                <input v-if="conditionConfig.nodeUserList.length == 0" type="text" placeholder="请选择具体人员/角色/部门" @click="addConditionRole" style="border: none; outline: none; width: 100%" />
                            </p>
                        </div>
                        <div v-else style="display: inline-block; width: 300px">
                            <p>
                                <el-select v-model="item.optType" style="width: 120px" @change="changeOptType(item)">
                                    <el-option v-for="({ value, label }) in optTypes" :key="value" :value="value" :label="label" />
                                </el-select>
                                <el-input v-if="item.optType != '6'" v-model="item.zdy1" :placeholder="'请输入' + item.showName" style="width: 170px; margin-left: 10px" type="number" />
                            </p>
                            <p v-if="item.optType == '6'" style="margin-top: 10px">
                                <el-input v-model="item.zdy1" style="width: 75px; margin-right: 10px" type="number" />
                                <el-select v-model="item.opt1" style="width: 60px"><el-option v-for="({ value, label }) in opt1s" :key="value" :value="value" :label="label" /></el-select>
                                <span style="display: inline-block; width: 60px; text-align: center">{{ item.showName }}</span>
                                <el-select v-model="item.opt2" style="width: 60px; margin-left: 10px"><el-option v-for="({ value, label }) in opt1s" :key="value" :value="value" :label="label" /></el-select>
                                <el-input v-model="item.zdy2" style="width: 75px" type="number" />
                            </p>
                        </div>
                        <a v-if="item.type == 1 || item.type == 2" style="cursor: pointer; color: #f56c6c; margin-left: 10px" @click="$func.removeEle(conditionConfig.conditionList, item, 'columnId')">删除</a>
                    </li>
                </ul>
                <el-button type="primary" @click="addCondition" style="margin-bottom: 20px">添加条件</el-button>
            </div>
            <employeesRoleDialog v-model:visible="conditionRoleVisible" :data="checkedList" @change="sureConditionRole" :isDepartment="true" />
            <div style="padding: 20px; text-align: right">
                <el-button type="primary" @click="saveCondition">确 定</el-button>
                <el-button @click="closeDrawer">取 消</el-button>
            </div>
        </div>
    </el-drawer>
</template>

<script setup lang="ts">
import { Close } from '@element-plus/icons-vue'
import { ref, watch, computed } from 'vue'
import $func from './helpers'
import { optTypes, opt1s } from './const'
import { useWorkflowStore } from './store'
import employeesRoleDialog from './employeesRoleDialog.vue'

let conditionsConfig = ref<any>({ conditionNodes: [] })
let conditionConfig = ref<any>({})
let PriorityLevel = ref(0)
let checkedList = ref<any[]>([])
let conditionRoleVisible = ref(false)

let store = useWorkflowStore()
let { setCondition, setConditionsConfig } = store
let conditionsConfig1 = computed(() => store.conditionsConfig1)
let conditionDrawer = computed(() => store.conditionDrawer)
let visible = computed({ get: () => conditionDrawer.value, set: () => closeDrawer() })

watch(conditionsConfig1, (val: any) => {
    conditionsConfig.value = val.value
    PriorityLevel.value = val.priorityLevel
    conditionConfig.value = val.priorityLevel
        ? conditionsConfig.value.conditionNodes[val.priorityLevel - 1]
        : { nodeUserList: [], conditionList: [] }
})

const changeOptType = (item: any) => {
    if (item.optType == '1') { item.zdy1 = 2 } else { item.zdy1 = 1; item.zdy2 = 2 }
}
const addConditionRole = () => { conditionRoleVisible.value = true; checkedList.value = conditionConfig.value.nodeUserList }
const sureConditionRole = (data: any[]) => { conditionConfig.value.nodeUserList = data; conditionRoleVisible.value = false }
const addCondition = () => {
    conditionConfig.value.conditionList.push({
        showType: 3, columnId: Date.now(), type: 2, showName: '自定义条件',
        optType: '1', zdy1: '2', opt1: '<', zdy2: '', opt2: '<', columnType: 'Double',
    })
}
const saveCondition = () => {
    closeDrawer()
    var a = conditionsConfig.value.conditionNodes.splice(PriorityLevel.value - 1, 1)
    conditionsConfig.value.conditionNodes.splice(conditionConfig.value.priorityLevel - 1, 0, a[0])
    conditionsConfig.value.conditionNodes.map((item: any, index: number) => { item.priorityLevel = index + 1 })
    for (let i = 0; i < conditionsConfig.value.conditionNodes.length; i++) {
        conditionsConfig.value.conditionNodes[i].error =
            $func.conditionStr(conditionsConfig.value, i) == '请设置条件' && i != conditionsConfig.value.conditionNodes.length - 1
    }
    setConditionsConfig({ value: conditionsConfig.value, flag: true, id: conditionsConfig1.value.id })
}
const closeDrawer = () => setCondition(false)
</script>
