<script setup lang="ts">
import type {PropType} from "vue";
import type {Menu, Extend} from "./types.ts";


const props = defineProps({
  menus: {
    type: Array as PropType<Menu[]>,
    default: () => []
  },
  extend: {
    type: Object as PropType<Extend>,
    default: () => ({
      level: 1
    })
  }
})
const click = (index: string, indexPath: string[], active: boolean) => {
  console.log(index, indexPath, active)
}
</script>

<template>
  <template v-for="menu in props.menus">
    <template v-if="menu.children && menu.children.length > 0">
      <el-sub-menu :index="menu.path" :key="menu.id">
        <template #title>
          <span>{{ menu.name }}</span>
        </template>
        <MenuTree :extend="{ ...props.extend, level: props.extend.level + 1 }" :menus="menu.children"/>
      </el-sub-menu>
    </template>
    <template v-else>
      <el-menu-item :index="menu.path" :key="menu.id" @click="click">
        <span>{{ menu.name }}</span>
      </el-menu-item>
    </template>
  </template>
</template>

<style scoped>

</style>