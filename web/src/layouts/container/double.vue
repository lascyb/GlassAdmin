<script setup lang="ts">
import GaMain from "../router-view/main.vue"
import GaAside from "../components/aside/double.vue"
import GaHeader from "../components/header/double.vue"

</script>

<template>
  <el-container>
    <ga-aside></ga-aside>
    <el-container direction="vertical">
      <ga-header></ga-header>
      <ga-main></ga-main>
    </el-container>
  </el-container>
</template>

<style scoped>

</style>