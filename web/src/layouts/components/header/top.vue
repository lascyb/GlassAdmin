<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import GaLogo from '../logo.vue'
import GaMenus from '../menus/index.vue'
import HeaderRight from './components/HeaderRight.vue'

const layoutMenus = useLayoutMenus()
</script>

<template>
    <el-header class="top-header flex items-stretch p-0">
        <ga-logo />
        <div class="top-header__menus min-w-0 flex-1">
            <ga-menus :menus="layoutMenus.menus" mode="horizontal" />
        </div>
        <header-right />
    </el-header>
</template>

<style scoped>
.top-header__menus {
    height: 100%;
    /* 仅横向滚动；勿用 overflow-x:auto 单独写，否则 y 会变 auto，1px 溢出就出竖条 */
    overflow-x: auto;
    overflow-y: hidden;
}

.top-header__menus :deep(.ga-top-menus) {
    display: flex;
    width: max-content;
    min-width: 100%;
    height: 100%;
}
</style>
