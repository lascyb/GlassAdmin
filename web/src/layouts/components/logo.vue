<script setup lang="ts">
import {useRouter} from "vue-router";
import {useLayoutMenus} from "@/pinia/layout/menus";

const layoutMenus = useLayoutMenus()
const router = useRouter()
const toHome = () => {
  layoutMenus.module = undefined
  router.push('/')
}
</script>

<template>
  <div style="height: 60px;width: 100%;border-right: 1px solid var(--el-menu-border-color);box-sizing: border-box"
       @click="toHome">
    GlassAdmin
  </div>
</template>

<style scoped>

</style>