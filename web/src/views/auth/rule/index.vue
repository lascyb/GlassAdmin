<script setup lang="ts">
import Backend from './backend.vue'
import Frontend from './frontend.vue'
</script>

<template>
  <el-card style="min-height: max(calc(100vh - 100px), 400px)">
    <el-tabs>
      <el-tab-pane label="前端页面规则 (menu / view / btn)">
        <frontend/>
      </el-tab-pane>
      <el-tab-pane label="后端接口规则 (menu / api)">
        <backend/>
      </el-tab-pane>
    </el-tabs>
  </el-card>
</template>
