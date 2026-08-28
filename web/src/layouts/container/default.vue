<script setup lang="ts">
import GaMain from "../router-view/main.vue"
import GaHeader from "../components/header/default.vue"
import GaAside from "../components/aside/default.vue"

</script>

<template>
  <el-container class="ga-shell">
    <ga-aside></ga-aside>
    <el-container direction="vertical">
      <ga-header></ga-header>
      <ga-main></ga-main>
    </el-container>
  </el-container>
</template>

<style scoped>

</style>