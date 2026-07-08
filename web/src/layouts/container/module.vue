<script setup lang="ts">
import GaAside from '../components/aside/module.vue';
import GaHeader from '../components/header/module.vue';
import GaMain from '../router-view/main.vue';
</script>



<template>
    <el-container class="h-screen">
        <ga-aside />
        <el-container direction="vertical">
            <ga-header />
            <ga-main />
        </el-container>
    </el-container>
</template>



<style scoped></style>

