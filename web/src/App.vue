<script setup lang="ts">
import GaSetting from '@/layouts/setting/index.vue';
import zhCn from 'element-plus/es/locale/lang/zh-cn';
</script>

<template>
  <el-config-provider :locale="zhCn">
    <router-view></router-view>
    <ga-setting></ga-setting>
  </el-config-provider>
</template>

<style scoped>

</style>
