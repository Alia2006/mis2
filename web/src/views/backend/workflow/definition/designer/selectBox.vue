<template>
  <ul class="select-box">
    <template v-for="(elem, i) in list" :key="i">
      <template v-if="elem.type === 'role'">
        <li v-for="item in elem.data" :key="item.roleId"
          class="check_box"
          :class="{ active: elem.isActiveItem && elem.isActiveItem(item), not: elem.not }"
          @click="elem.change(item)">
          <a :title="item.description">
            <i class="fa fa-user-circle role-icon" />{{ item.roleName }}
          </a>
        </li>
      </template>
      <template v-if="elem.type === 'department'">
        <li v-for="item in elem.data" :key="item.id" class="check_box" :class="{ not: !elem.isDepartment }">
          <a v-if="elem.isDepartment"
            :class="elem.isActive(item) && 'active'"
            @click="elem.change(item)">
              <i class="fa fa-folder dept-icon" />{{ item.departmentName }}</a>
          <a v-else><i class="fa fa-folder dept-icon" />{{ item.departmentName }}</a>
          <i @click="elem.next(item)">下级</i>
        </li>
      </template>
      <template v-if="elem.type === 'employee'">
        <li v-for="item in elem.data" :key="item.id" class="check_box">
            <a :class="elem.isActive(item) && 'active'"
              @click="elem.change(item)"
              :title="item.departmentNames">
              <i class="fa fa-user people-icon" />{{ item.employeeName }}
            </a>
        </li>
      </template>
    </template>
  </ul>
</template>

<script setup lang="ts">
defineProps({
  list: { type: Array as () => any[], default: () => [] },
})
</script>
