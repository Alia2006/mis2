<template>
   <el-dialog title="选择角色" v-model="visibleDialog" :width="600" append-to-body class="promoter_person">
      <div class="person_body clear">
          <div class="person_tree l">
              <input type="text" placeholder="搜索角色" v-model="searchVal" @input="getDebounceData($event, 2)" />
              <selectBox :list="list" />
          </div>
          <selectResult :total="total" @del="delList" :list="resList" />
      </div>
      <template #footer>
          <el-button @click="closeDialog">取 消</el-button>
          <el-button type="primary" @click="saveDialog">确 定</el-button>
      </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { computed, watch, ref } from 'vue'
import selectBox from './selectBox.vue'
import selectResult from './selectResult.vue'
import { roles, getDebounceData, getRoleList, searchVal } from './dialogCommon'
import $func from './helpers'

const props = defineProps({
    visible: { type: Boolean, default: false },
    data: { type: Array as () => any[], default: () => [] },
})
const emits = defineEmits(['update:visible', 'change'])

let checkedRoleList = ref<any[]>([])

let list = computed(() => [{
    type: 'role',
    not: true,
    data: roles.value,
    isActiveItem: (item: any) => $func.toggleClass(checkedRoleList.value, item, 'roleId'),
    change: (item: any) => { checkedRoleList.value = [item] },
}])

let resList = computed(() => [{
    type: 'role',
    data: checkedRoleList.value,
    cancel: (item: any) => $func.removeEle(checkedRoleList.value, item, 'roleId'),
}])

let visibleDialog = computed({ get: () => props.visible, set: () => closeDialog() })

watch(() => props.visible, (val) => {
    if (val) {
        getRoleList()
        searchVal.value = ''
        checkedRoleList.value = props.data.map(({ name, targetId }: any) => ({ roleName: name, roleId: targetId }))
    }
})

let total = computed(() => checkedRoleList.value.length)

const saveDialog = () => {
    let checkedList = checkedRoleList.value.map((item: any) => ({ type: 2, targetId: item.roleId, name: item.roleName }))
    emits('change', checkedList)
}
const delList = () => { checkedRoleList.value = [] }
const closeDialog = () => emits('update:visible', false)
</script>

<style scoped>
@import './dialog.css';
</style>
