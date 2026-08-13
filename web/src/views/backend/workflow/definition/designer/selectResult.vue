<template>
  <div class="select-result l">
    <p class="clear">已选（{{ total }}）
        <a @click="emits('del')">清空</a>
    </p>
    <ul>
        <template v-for="({ type, data, cancel }) in list" :key="type">
          <template v-if="type === 'role'">
            <li v-for="item in data" :key="item.roleId">
                <i class="fa fa-user-circle" />
                <span>{{ item.roleName }}</span>
                <i class="fa fa-times cancel-icon" @click="cancel(item)" />
            </li>
          </template>
          <template v-if="type === 'department'">
            <li v-for="item in data" :key="item.id">
                <i class="fa fa-folder" />
                <span>{{ item.departmentName }}</span>
                <i class="fa fa-times cancel-icon" @click="cancel(item)" />
            </li>
          </template>
          <template v-if="type === 'employee'">
            <li v-for="item in data" :key="item.id">
                <i class="fa fa-user" />
                <span>{{ item.employeeName }}</span>
                <i class="fa fa-times cancel-icon" @click="cancel(item)" />
            </li>
          </template>
        </template>
    </ul>
  </div>
</template>

<script setup lang="ts">
defineProps({
  total: { type: Number, default: 0 },
  list: { type: Array as () => any[], default: () => [] },
})
const emits = defineEmits(['del'])
</script>
