<script setup lang="ts">
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh';

const mainRefresh = useMainViewRefresh()
</script>

<template>
  <!-- 壳只负责视口滚动；边距/卡片样式由页面或 GaPage / ga-table-page 自行定义 -->
  <el-main class="ga-main">
    <el-scrollbar height="var(--ga-main-view-height)" noresize>
      <router-view v-slot="{ Component, route }">
        <transition name="ga-page" mode="out-in">
          <keep-alive>
            <component
              v-if="Component"
              :is="Component"
              :key="`${route.fullPath}:${mainRefresh.nonceFor(route.fullPath)}`"
            />
          </keep-alive>
        </transition>
      </router-view>
    </el-scrollbar>
  </el-main>
</template>

<style scoped>
.ga-main {
  /* 顶栏约 60px + 余量；供 el-scrollbar / iframe 共用 */
  --ga-main-view-height: calc(100vh - 70px);
  padding: 0;
  background: var(--el-fill-color-light);
}
</style>
