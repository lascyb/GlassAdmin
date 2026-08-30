<script setup lang="ts">
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh';
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const mainRefresh = useMainViewRefresh()
const route = useRoute()

/** pageKey 按 fullPath + 刷新 nonce，缓存页与非缓存页共用 */
const pageKey = computed(
    () => `${route.fullPath}:${mainRefresh.nonceFor(route.fullPath)}`,
)

/** keepAlive 缺省 true，与规则默认 keep_alive=1 对齐 */
const keepAlive = computed(() => route.meta.keepAlive !== false)
</script>

<template>
  <!-- 壳只负责视口滚动；边距/卡片样式由页面或 GaPage / ga-table 自行定义 -->
  <el-main class="ga-main">
    <el-scrollbar height="var(--ga-main-view-height)" noresize>
      <!-- relative：离开页绝对定位时仍锚在主视口内，交叉淡入避免 out-in 空帧 -->
      <div class="ga-main-view">
        <router-view v-slot="{ Component }">
          <transition name="ga-page">
            <keep-alive>
              <component
                v-if="Component && keepAlive"
                :is="Component"
                :key="pageKey"
              />
            </keep-alive>
          </transition>
          <transition name="ga-page">
            <component
              v-if="Component && !keepAlive"
              :is="Component"
              :key="pageKey"
            />
          </transition>
        </router-view>
      </div>
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

.ga-main-view {
  position: relative;
  min-height: 100%;
}
</style>
