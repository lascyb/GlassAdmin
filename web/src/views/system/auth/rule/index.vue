<script setup lang="ts">
import Backend from './backend.vue'
import Frontend from './frontend.vue'
</script>

<template>
  <div class="p-3">
  <el-card style="min-height: max(calc(100vh - 100px), 400px);">
    <el-tabs style="min-width: 900px" type="border-card">
      <el-tab-pane label="前端页面规则">
        <frontend/>
      </el-tab-pane>
      <el-tab-pane label="后端接口规则">
        <backend/>
      </el-tab-pane>
    </el-tabs>
  </el-card>
  </div>
</template>
