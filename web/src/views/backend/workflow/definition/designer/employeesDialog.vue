<template>
   <el-dialog title="选择成员" v-model="visibleDialog" :width="600" append-to-body class="promoter_person">
      <div class="person_body clear">
          <div class="person_tree l">
              <input type="text" placeholder="搜索成员" v-model="searchVal" @input="getDebounceData($event)" />
              <p class="ellipsis tree_nav" v-if="!searchVal">
                  <span @click="getDepartmentList(0)" class="ellipsis">全部</span>
                  <span v-for="(item, index) in departments.titleDepartments" class="ellipsis" :key="index + 'a'" @click="getDepartmentList(item.id)">{{ item.departmentName }}</span>
              </p>
              <selectBox :list="list" />
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
import { departments, getDebounceData, getDepartmentList, searchVal } from './dialogCommon'
import $func from './helpers'

const props = defineProps({
    visible: { type: Boolean, default: false },
    data: { type: Array as () => any[], default: () => [] },
    isDepartment: { type: Boolean, default: false },
})
const emits = defineEmits(['update:visible', 'change'])

let checkedDepartmentList = ref<any[]>([])
let checkedEmployessList = ref<any[]>([])
let visibleDialog = computed({
    get: () => props.visible,
    set: () => closeDialog(),
})

let list = computed(() => [
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
])

let resList = computed(() => {
    let data: any[] = [{
        type: 'employee',
        data: checkedEmployessList.value,
        cancel: (item: any) => $func.removeEle(checkedEmployessList.value, item),
    }]
    if (props.isDepartment) {
        data.unshift({
            type: 'department',
            data: checkedDepartmentList.value,
            cancel: (item: any) => $func.removeEle(checkedDepartmentList.value, item),
        })
    }
    return data
})

let total = computed(() => checkedDepartmentList.value.length + checkedEmployessList.value.length)

watch(() => props.visible, (val) => {
    if (val) {
        getDepartmentList()
        searchVal.value = ''
        checkedEmployessList.value = props.data.filter((item: any) => item.type === 1).map(({ name, targetId }: any) => ({ employeeName: name, id: targetId }))
        checkedDepartmentList.value = props.data.filter((item: any) => item.type === 3).map(({ name, targetId }: any) => ({ departmentName: name, id: targetId }))
    }
})

const closeDialog = () => emits('update:visible', false)

const saveDialog = () => {
    let checkedList = [...checkedDepartmentList.value, ...checkedEmployessList.value].map((item: any) => ({
        type: item.employeeName ? 1 : 3,
        targetId: item.id,
        name: item.employeeName || item.departmentName,
    }))
    emits('change', checkedList)
}

const delList = () => { checkedDepartmentList.value = []; checkedEmployessList.value = [] }
</script>

<style scoped>
@import './dialog.css';
</style>
