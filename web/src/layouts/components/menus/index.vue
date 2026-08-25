<script setup lang="ts">
import { menusForDisplay } from '@/layouts/components/menus/menusForDisplay';
import { useLayoutMenus } from "@/pinia/layout/menus.ts";
import { useLayoutSetting } from '@/pinia/layout/setting.ts';
import type { MenuItemClicked } from "element-plus";
import { type PropType, computed } from "vue";
import { type NavigationFailure, useRoute } from "vue-router";
import MenuTree from "./MenuTree.vue";
import type { Menu } from "./types.ts";

const layoutMenus = useLayoutMenus()
const layoutSetting = useLayoutSetting()

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
  <el-menu
    class="ga-aside-menus"
    @select="select"
    :default-active="route.path"
    router
    :collapse="layoutMenus.collapse"
    :unique-opened="layoutSetting.layout.menuAccordion"
  >
    <MenuTree :menus="displayMenus"></MenuTree>
  </el-menu>
</template>

<style scoped>
.ga-aside-menus {
  box-sizing: border-box;
  overflow: auto;
}
</style>
