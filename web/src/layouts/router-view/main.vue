<script setup lang="ts">
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh'

const mainRefresh = useMainViewRefresh()
</script>

<template>
  <el-main>
    <el-scrollbar height="calc(100vh - 70px)" class="pr-[16px]" noresize>
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
.el-main {
  background: var(--el-fill-color-light);
  padding-right: 4px;
  padding-bottom: 5px;
  padding-top: 5px;
}
</style>