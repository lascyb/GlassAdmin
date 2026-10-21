<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import GaAside from '../components/aside/module.vue'
import GaHeader from '../components/header/module.vue'
import GaMain from '../router-view/main.vue'

const layoutMenus = useLayoutMenus()
</script>

<template>
    <el-container class="ga-shell h-screen" direction="vertical">
        <ga-header />
        <el-container class="min-h-0 flex-1">
            <ga-aside v-if="layoutMenus.subMenus.length > 0" />
            <ga-main />
        </el-container>
    </el-container>
</template>

<style scoped></style>
