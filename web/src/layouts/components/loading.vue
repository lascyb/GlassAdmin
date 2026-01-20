<script setup lang="ts">
import {Loading} from '@element-plus/icons-vue'

defineProps({
  text: {
    type: String,
    default: "页面加载中..."
  }
})
</script>

<template>
  <div class="ga-loading">
    <div class="ga-loading-content">
      <div class="ga-loading-spinner">
        <el-icon class="is-loading" :size="48">
          <Loading/>
        </el-icon>
      </div>
      <div class="ga-loading-text">{{ text }}</div>
    </div>
  </div>
</template>

<style scoped>
.ga-loading {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--el-bg-color);
  z-index: 2000;
}

.ga-loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.ga-loading-spinner {
  color: var(--el-color-primary);
}

.ga-loading-text {
  color: var(--el-text-color-regular);
  font-size: 14px;
  font-weight: 400;
}

.is-loading {
  animation: rotating 2s linear infinite;
}

@keyframes rotating {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
