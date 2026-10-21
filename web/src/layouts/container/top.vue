<script setup lang="ts">
import GaHeader from '../components/header/top.vue'
import GaMain from '../router-view/main.vue'
</script>

<template>
    <el-container class="ga-shell h-screen" direction="vertical">
        <ga-header />
        <ga-main />
    </el-container>
</template>

<style scoped></style>
