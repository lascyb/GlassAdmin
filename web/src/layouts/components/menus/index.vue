<script setup lang="ts">
import MenuTree from "./MenuTree.vue";
import {type PropType} from "vue";
import type {Menu} from "./types.ts";
import type {MenuItemClicked} from "element-plus";
import {type NavigationFailure, useRoute} from "vue-router";
import {useLayoutMenus} from "@/pinia/layout/menus.ts";

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

</script>

<template>
  <el-menu @select="select" :default-active="route.path" router :collapse="layoutMenus.collapse">
    <MenuTree :menus="props.menus"></MenuTree>
  </el-menu>
</template>

<style scoped>

</style>