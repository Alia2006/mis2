<template>
    <div class="add-node-btn-box">
        <div class="add-node-btn">
            <el-popover placement="right-start" v-model:visible="visible" width="auto">
                <div class="add-node-popover-body">
                    <a class="add-node-popover-item approver" @click="addType(1)">
                        <div class="item-wrapper"><i class="fa fa-user"></i></div>
                        <p>审批人</p>
                    </a>
                    <a class="add-node-popover-item notifier" @click="addType(2)">
                        <div class="item-wrapper"><i class="fa fa-envelope"></i></div>
                        <p>抄送人</p>
                    </a>
                    <a class="add-node-popover-item condition" @click="addType(4)">
                        <div class="item-wrapper"><i class="fa fa-code-branch"></i></div>
                        <p>条件分支</p>
                    </a>
                </div>
                <template #reference>
                    <button class="btn" type="button"><i class="fa fa-plus"></i></button>
                </template>
            </el-popover>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps({
    childNodeP: { type: Object as () => any, default: () => ({}) },
})
const emits = defineEmits(['update:childNodeP'])
let visible = ref(false)

const addType = (type: number) => {
    visible.value = false
    if (type != 4) {
        let data: any
        if (type == 1) {
            data = {
                nodeName: '审核人',
                error: true,
                type: 1,
                settype: 1,
                selectMode: 0,
                selectRange: 0,
                directorLevel: 1,
                examineMode: 1,
                noHanderAction: 1,
                examineEndDirectorLevel: 0,
                childNode: props.childNodeP,
                nodeUserList: [],
            }
        } else {
            data = {
                nodeName: '抄送人',
                type: 2,
                ccSelfSelectFlag: 1,
                childNode: props.childNodeP,
                nodeUserList: [],
            }
        }
        emits('update:childNodeP', data)
    } else {
        emits('update:childNodeP', {
            nodeName: '路由',
            type: 4,
            childNode: null,
            conditionNodes: [
                {
                    nodeName: '条件1',
                    error: true,
                    type: 3,
                    priorityLevel: 1,
                    conditionList: [],
                    nodeUserList: [],
                    childNode: props.childNodeP,
                },
                {
                    nodeName: '条件2',
                    type: 3,
                    priorityLevel: 2,
                    conditionList: [],
                    nodeUserList: [],
                    childNode: null,
                },
            ],
        })
    }
}
</script>
