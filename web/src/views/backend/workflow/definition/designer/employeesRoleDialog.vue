<template>
  <el-dialog title="选择成员" v-model="visibleDialog" :width="600" append-to-body class="promoter_person">
      <div class="person_body clear">
          <div class="person_tree l">
              <input type="text" placeholder="搜索成员" v-model="searchVal" @input="getDebounceData($event, activeName)" />
              <el-tabs v-model="activeName" @tab-change="handleClick">
                  <el-tab-pane label="组织架构" name="1" />
                  <el-tab-pane label="角色列表" name="2" />
              </el-tabs>
              <p class="ellipsis tree_nav" v-if="activeName === '1' && !searchVal">
                  <span @click="getDepartmentList(0)" class="ellipsis">全部</span>
                  <span v-for="(item, index) in departments.titleDepartments" class="ellipsis" :key="index + 'a'" @click="getDepartmentList(item.id)">{{ item.departmentName }}</span>
              </p>
              <selectBox :list="list" style="height: 360px" />
          </div>
          <selectResult :total="total" @del="delList" :list="resList" />
      </div>
      <template #footer>
          <el-button @click="$emit('update:visible', false)">取 消</el-button>
          <el-button type="primary" @click="saveDialog">确 定</el-button>
      </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { computed, watch, ref } from 'vue'
import selectBox from './selectBox.vue'
import selectResult from './selectResult.vue'
import { departments, roles, getDebounceData, getRoleList, getDepartmentList, searchVal } from './dialogCommon'
import $func from './helpers'

const props = defineProps({
    visible: { type: Boolean, default: false },
    data: { type: Array as () => any[], default: () => [] },
    isDepartment: { type: Boolean, default: false },
})
const emits = defineEmits(['update:visible', 'change'])

let checkedRoleList = ref<any[]>([])
let checkedEmployessList = ref<any[]>([])
let checkedDepartmentList = ref<any[]>([])
let activeName = ref('1')

let visibleDialog = computed({ get: () => props.visible, set: () => closeDialog() })

let list = computed(() => {
    if (activeName.value === '2') {
        return [{
            type: 'role',
            not: false,
            data: roles.value,
            isActiveItem: (item: any) => $func.toggleClass(checkedRoleList.value, item, 'roleId'),
            change: (item: any) => $func.toChecked(checkedRoleList.value, item, 'roleId'),
        }]
    } else {
        return [
            {
                isDepartment: props.isDepartment,
                type: 'department',
                data: departments.value.childDepartments,
                isActive: (item: any) => $func.toggleClass(checkedDepartmentList.value, item),
                change: (item: any) => $func.toChecked(checkedDepartmentList.value, item),
                next: (item: any) => getDepartmentList(item.id),
            },
            {
                type: 'employee',
                data: departments.value.employees,
                isActive: (item: any) => $func.toggleClass(checkedEmployessList.value, item),
                change: (item: any) => $func.toChecked(checkedEmployessList.value, item),
            },
        ]
    }
})

let resList = computed(() => {
    let data: any[] = [
        { type: 'role', data: checkedRoleList.value, cancel: (item: any) => $func.removeEle(checkedRoleList.value, item, 'roleId') },
        { type: 'employee', data: checkedEmployessList.value, cancel: (item: any) => $func.removeEle(checkedEmployessList.value, item) },
    ]
    if (props.isDepartment) {
        data.splice(1, 0, { type: 'department', data: checkedDepartmentList.value, cancel: (item: any) => $func.removeEle(checkedDepartmentList.value, item) })
    }
    return data
})

let total = computed(() => checkedEmployessList.value.length + checkedRoleList.value.length + checkedDepartmentList.value.length)

watch(() => props.visible, (val) => {
    if (val) {
        activeName.value = '1'
        getDepartmentList()
        searchVal.value = ''
        checkedEmployessList.value = props.data.filter((item: any) => item.type === 1).map(({ name, targetId }: any) => ({ employeeName: name, id: targetId }))
        checkedRoleList.value = props.data.filter((item: any) => item.type === 2).map(({ name, targetId }: any) => ({ roleName: name, roleId: targetId }))
        checkedDepartmentList.value = props.data.filter((item: any) => item.type === 3).map(({ name, targetId }: any) => ({ departmentName: name, id: targetId }))
    }
})

const handleClick = () => {
    searchVal.value = ''
    if (activeName.value === '1') getDepartmentList()
    else getRoleList()
}

const saveDialog = () => {
    let checkedList = [...checkedRoleList.value, ...checkedEmployessList.value, ...checkedDepartmentList.value].map((item: any) => ({
        type: item.employeeName ? 1 : item.roleName ? 2 : 3,
        targetId: item.id || item.roleId,
        name: item.employeeName || item.roleName || item.departmentName,
    }))
    emits('change', checkedList)
}

const delList = () => { checkedEmployessList.value = []; checkedRoleList.value = []; checkedDepartmentList.value = [] }
const closeDialog = () => emits('update:visible', false)
</script>

<style scoped>
@import './dialog.css';
</style>
