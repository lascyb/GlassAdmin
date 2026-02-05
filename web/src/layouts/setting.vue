<script setup lang="ts">
import {useLayoutSetting} from "@/pinia/layout/setting.ts";

const setting = useLayoutSetting()

</script>

<template>
  <el-drawer v-model="setting.layout.showSetting" direction="rtl" title="个人偏好">
    <el-radio-group v-model="setting.layout.container">
      <el-radio value="Default">Default</el-radio>
      <el-radio value="Double">Double</el-radio>
    </el-radio-group>
  </el-drawer>
</template>

<style scoped>

</style>