<script setup lang="ts">
import { menusForDisplay } from '@/layouts/components/menus/menusForDisplay';
import { useLayoutMenus } from "@/pinia/layout/menus.ts";
import { useLayoutSetting } from '@/pinia/layout/setting.ts';
import { type PropType, computed } from "vue";
import { useRoute } from "vue-router";
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

const route = useRoute()
const displayMenus = computed(() => menusForDisplay(props.menus))

</script>

<template>
  <el-menu
    class="ga-aside-menus"
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
  /* 覆盖 EP / 旧 overflow:auto：收起 width 过渡时不要闪横向条；条目多时仍纵向滚动 */
  overflow-x: hidden;
  overflow-y: auto;
}
</style>
