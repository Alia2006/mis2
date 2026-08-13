<template>
    <el-drawer :append-to-body="true" title="发起人" v-model="visible" class="set_promoter" :show-close="false" :size="550" :before-close="savePromoter">
        <div class="demo-drawer__content">
            <div class="promoter_content" style="padding: 0 20px">
                <p style="padding: 18px 0; font-size: 14px; color: #000">{{ $func.arrToStr(flowPermission) || '所有人' }}</p>
                <el-button type="primary" style="margin-bottom: 20px" @click="addPromoter">添加/修改发起人</el-button>
            </div>
            <div class="demo-drawer__footer clear" style="padding: 20px">
                <el-button type="primary" @click="savePromoter">确 定</el-button>
                <el-button @click="closeDrawer">取 消</el-button>
            </div>
            <employeesDialog
                :isDepartment="true"
                v-model:visible="promoterVisible"
                :data="checkedList"
                @change="surePromoter"
            />
        </div>
    </el-drawer>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import $func from './helpers'
import { useWorkflowStore } from './store'
import employeesDialog from './employeesDialog.vue'

let flowPermission = ref<any[]>([])
let promoterVisible = ref(false)
let checkedList = ref<any[]>([])

let store = useWorkflowStore()
let { setPromoter, setFlowPermission } = store
let promoterDrawer = computed(() => store.promoterDrawer)
let flowPermission1 = computed(() => store.flowPermission1)

let visible = computed({
    get: () => promoterDrawer.value,
    set: () => closeDrawer(),
})

watch(flowPermission1, (val: any) => {
    flowPermission.value = val.value || []
})

const addPromoter = () => {
    checkedList.value = flowPermission.value
    promoterVisible.value = true
}
const surePromoter = (data: any[]) => {
    flowPermission.value = data
    promoterVisible.value = false
}
const savePromoter = () => {
    setFlowPermission({ value: flowPermission.value, flag: true, id: flowPermission1.value.id })
    closeDrawer()
}
const closeDrawer = () => setPromoter(false)
</script>
