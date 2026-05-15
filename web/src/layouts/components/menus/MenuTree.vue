<script setup lang="ts">
import { router } from "@/router";
import GaIcon from "@components/ga-icon/index.vue";
import type { PropType } from "vue";
import type { Extend, Menu } from "./types.ts";

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
          <GaIcon :icon="menu.icon" />
          <span>{{ menu.name }}</span>
        </template>
        <MenuTree :extend="{ ...props.extend, level: props.extend.level + 1 }" :menus="menu.children"/>
      </el-sub-menu>
    </template>
    <template v-else-if="menu.type == 'page' || menu.type == 'view' || menu.type == 'route' || menu.type == 'link' || menu.type == 'iframe'">
      <el-menu-item :index="menu.path_full" :key="menu.id"
                    @click="(index: string, indexPath: string[], active: boolean)=>click(index, indexPath, active,menu)">
        <GaIcon :icon="menu.icon" />
        <span>{{ menu.name }}</span>
      </el-menu-item>
    </template>
  </template>
</template>

<style scoped>

</style>