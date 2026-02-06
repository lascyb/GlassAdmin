<script setup lang="ts">
import GaLogo from "../logo.vue";
import {useLayoutMenus} from "@/pinia/layout/menus.ts";
import GaMenus from "../menus/index.vue";
import {useCssVar} from "@vueuse/core";
import {watch} from "vue";
const layoutMenus = useLayoutMenus()
const store = useLayoutMenus()
const aside_with = useCssVar("--el-aside-width")

watch(() => layoutMenus.collapse, (newVal) => {
  if (newVal) {
    aside_with.value = "calc(var(--el-menu-icon-width) + var(--el-menu-base-level-padding)*2)";
  } else {
    aside_with.value = "200px"
  }
})


</script>

<template>
  <el-aside>
    <ga-logo></ga-logo>
    <ga-menus :menus="store.menus" style="height: calc(100vh - 60px)"></ga-menus>
  </el-aside>
</template>

<style scoped>
.el-aside {
  --el-aside-width:200px;
}
</style>