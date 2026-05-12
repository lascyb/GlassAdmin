<script setup lang="ts">
</script>

<template>
  <el-main>
    <el-scrollbar height="calc(100vh - 70px)" class="pr-[16px]" noresize>
      <router-view v-slot="{ Component, route }">
        <transition name="ga-page" mode="out-in">
          <keep-alive>
            <component :is="Component" :key="route.fullPath" />
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