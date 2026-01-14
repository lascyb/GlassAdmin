<script setup lang="ts">
import GaLogo from "../logo.vue";
import {useLayoutMenus} from "../../../pinia/layout/menus.ts";
import GaMenus from "../menus/index.vue";

const store = useLayoutMenus()
</script>

<template>
  <el-aside>
    <ga-logo></ga-logo>
    <ga-menus :menus="store.menus" style="height: calc(100vh - 60px)"></ga-menus>
  </el-aside>
</template>

<style scoped>

</style>