<script setup lang="ts">
import type {PropType} from "vue";
import type {Menu, Extend} from "./types.ts";
import {router} from "@/router";


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
const click = (index: string, indexPath: string[], active: boolean, menu: Menu) => {
  console.log(index, indexPath, active, menu)
  console.log(router.getRoutes())
}
</script>

<template>
  <template v-for="menu in props.menus">
    <template v-if="menu.type == 'menu' && menu.children && menu.children.length > 0">
      <el-sub-menu :index="menu.path_full" :key="menu.id">
        <template #title>
          <span>{{ menu.name }}</span>
        </template>
        <MenuTree :extend="{ ...props.extend, level: props.extend.level + 1 }" :menus="menu.children"/>
      </el-sub-menu>
    </template>
    <template v-else-if="menu.type=='page' || menu.type=='link' || menu.type == 'iframe'">
      <el-menu-item :index="menu.path_full" :key="menu.id"
                    @click="(index: string, indexPath: string[], active: boolean)=>click(index, indexPath, active,menu)">
        <span>{{ menu.name }}</span>
      </el-menu-item>
    </template>
  </template>
</template>

<style scoped>

</style>