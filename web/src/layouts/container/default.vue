<script setup lang="ts">
import GaMain from '../router-view/main.vue'
import GaHeader from '../components/header/default.vue'
import GaAside from '../components/aside/default.vue'
</script>

<template>
    <el-container class="ga-shell h-screen" direction="vertical">
        <ga-header />
        <el-container class="min-h-0 flex-1">
            <ga-aside />
            <ga-main />
        </el-container>
    </el-container>
</template>

<style scoped></style>
