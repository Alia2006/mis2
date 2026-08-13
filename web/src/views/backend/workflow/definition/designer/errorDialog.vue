<template>
  <el-dialog title="提示" v-model="visibleDialog" :width="520">
    <div style="display: flex; align-items: flex-start; gap: 16px">
      <el-icon :size="22" color="#f00"><CircleCloseFilled /></el-icon>
      <div>
        <span style="color: rgba(0,0,0,.85); font-weight: 500; font-size: 16px; display: block">当前无法发布</span>
        <div style="margin-top: 8px; font-size: 14px; color: rgba(0,0,0,.65)">
          <div>
            <p class="error-modal-desc">以下内容不完善，需进行修改</p>
            <div class="error-modal-list">
              <div class="error-modal-item" v-for="(item, index) in list" :key="index">
                <div class="error-modal-item-label">流程设计</div>
                <div class="error-modal-item-content">{{ item.name }} 未选择{{ item.type }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <template #footer>
      <el-button @click="visibleDialog = false">我知道了</el-button>
      <el-button type="primary" @click="visibleDialog = false">前往修改</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { CircleCloseFilled } from '@element-plus/icons-vue'

const props = defineProps({
    list: { type: Array as () => any[], default: () => [] },
    visible: { type: Boolean, default: false },
})
const emits = defineEmits(['update:visible'])

const visibleDialog = computed({
    get: () => props.visible,
    set: (val: boolean) => emits('update:visible', val),
})
</script>
