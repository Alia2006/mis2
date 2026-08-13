<template>
    <el-drawer :append-to-body="true" title="抄送人设置" v-model="visible" class="set_copyer" :show-close="false" :size="550" :before-close="saveCopyer">
        <div class="demo-drawer__content">
            <div style="padding: 20px 20px 0">
                <el-button type="primary" @click="addCopyer" style="margin-bottom: 20px">添加成员</el-button>
                <p class="selected_list">
                    <span v-for="(item, index) in copyerConfig.nodeUserList" :key="index">{{ item.name }}
                        <el-icon style="margin-left: 5px; cursor: pointer" @click="$func.removeEle(copyerConfig.nodeUserList, item, 'targetId')"><Close /></el-icon>
                    </span>
                    <a v-if="copyerConfig.nodeUserList && copyerConfig.nodeUserList.length != 0" style="cursor: pointer; color: #3296fa" @click="copyerConfig.nodeUserList = []">清除</a>
                </p>
                <el-checkbox-group v-model="ccSelfSelectFlag" style="margin-bottom: 20px">
                    <el-checkbox :value="1">允许发起人自选抄送人</el-checkbox>
                </el-checkbox-group>
            </div>
            <div style="padding: 20px; text-align: right">
                <el-button type="primary" @click="saveCopyer">确 定</el-button>
                <el-button @click="closeDrawer">取 消</el-button>
            </div>
            <employeesRoleDialog v-model:visible="copyerVisible" :data="checkedList" @change="sureCopyer" />
        </div>
    </el-drawer>
</template>

<script setup lang="ts">
import { Close } from '@element-plus/icons-vue'
import $func from './helpers'
import { useWorkflowStore } from './store'
import { ref, watch, computed } from 'vue'
import employeesRoleDialog from './employeesRoleDialog.vue'

let copyerConfig = ref<any>({})
let ccSelfSelectFlag = ref<any[]>([])
let copyerVisible = ref(false)
let checkedList = ref<any[]>([])
let store = useWorkflowStore()
let { setCopyerConfig, setCopyer } = store
let copyerDrawer = computed(() => store.copyerDrawer)
let copyerConfig1 = computed(() => store.copyerConfig1)
let visible = computed({ get: () => copyerDrawer.value, set: () => closeDrawer() })

watch(copyerConfig1, (val: any) => {
    copyerConfig.value = val.value
    ccSelfSelectFlag.value = copyerConfig.value.ccSelfSelectFlag == 0 ? [] : [copyerConfig.value.ccSelfSelectFlag]
})

const addCopyer = () => { copyerVisible.value = true; checkedList.value = copyerConfig.value.nodeUserList }
const sureCopyer = (data: any[]) => { copyerConfig.value.nodeUserList = data; copyerVisible.value = false }
const saveCopyer = () => {
    copyerConfig.value.ccSelfSelectFlag = ccSelfSelectFlag.value.length == 0 ? 0 : 1
    copyerConfig.value.error = !$func.copyerStr(copyerConfig.value)
    setCopyerConfig({ value: copyerConfig.value, flag: true, id: copyerConfig1.value.id })
    closeDrawer()
}
const closeDrawer = () => setCopyer(false)
</script>
