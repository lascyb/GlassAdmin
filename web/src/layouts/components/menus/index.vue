<script setup lang="ts">
import { useLayoutMenus } from "@/pinia/layout/menus.ts";
import { menusForDisplay } from "@/utils/menu/menusForDisplay";
import type { MenuItemClicked } from "element-plus";
import { type PropType, computed } from "vue";
import { type NavigationFailure, useRoute } from "vue-router";
import MenuTree from "./MenuTree.vue";
import type { Menu } from "./types.ts";

const layoutMenus = useLayoutMenus()

const props = defineProps({
  menus: {
    type: Array as PropType<Menu[]>,
    default: () => []
  },
})
const select = (index: string, indexPath: string[], item: MenuItemClicked, routerResult?: Promise<void | NavigationFailure>) => {
  console.log(index, indexPath, item, routerResult)
}

const route = useRoute()
const displayMenus = computed(() => menusForDisplay(props.menus))

</script>

<template>
  <el-menu @select="select" :default-active="route.path" router :collapse="layoutMenus.collapse">
    <MenuTree :menus="displayMenus"></MenuTree>
  </el-menu>
</template>

<style scoped>

</style>
