<script setup lang="ts">
import { useAppColorMode, useAppThemeColor } from '@/composables/useColorMode';
import GaSetting from '@/layouts/setting/index.vue';
import type { CardConfigContext } from 'element-plus';
import zhCn from 'element-plus/es/locale/lang/zh-cn';
import { reactive } from "vue";


const card = reactive<CardConfigContext>({
    shadow: 'never',
})
useAppColorMode()
useAppThemeColor()

</script>

<template>
  <el-config-provider :locale="zhCn" :card="card">
    <router-view></router-view>
    <ga-setting></ga-setting>
  </el-config-provider>
</template>

<style scoped>

</style>
