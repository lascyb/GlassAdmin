<script setup lang="ts">

</script>

<template>
  <el-main>
    <el-scrollbar height="calc(100vh - 100px)">
      <router-view v-slot="{ Component }">
        <transition>
          <keep-alive>
            <component :is="Component"/>
          </keep-alive>
        </transition>
      </router-view>
    </el-scrollbar>
  </el-main>
</template>

<style scoped>
.el-main {
  background: var(--el-fill-color-light);
}
</style>