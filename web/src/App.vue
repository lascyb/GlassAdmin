<script setup lang="ts">
import zhCn from 'element-plus/es/locale/lang/zh-cn'
import GaSetting from "@/layouts/setting.vue";
</script>

<template>
  <el-config-provider :locale="zhCn">
    <router-view></router-view>
    <ga-setting></ga-setting>
  </el-config-provider>
</template>

<style scoped>

</style>
