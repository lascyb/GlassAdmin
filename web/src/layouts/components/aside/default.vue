<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { computed } from 'vue'
import GaLogo from '../logo.vue'
import GaMenus from '../menus/index.vue'

const layoutMenus = useLayoutMenus()
const layoutSetting = useLayoutSetting()

/** asideStyle 侧栏宽度：与 el-menu collapse 同步，避免 scoped 内 --el-aside-width 覆盖 useCssVar 写入的根变量 */
const asideStyle = computed(() => ({
    width: layoutMenus.collapse
        ? 'calc(var(--el-menu-icon-width) + var(--el-menu-base-level-padding) * 2)'
        : `${layoutSetting.layout.asideWidth}px`,
    transition: 'width var(--ga-duration-slow) var(--ga-ease)',
}))
</script>

<template>
    <el-aside :style="asideStyle">
        <ga-logo></ga-logo>
        <ga-menus :menus="layoutMenus.menus" style="height: calc(100vh - 80px)"></ga-menus>
    </el-aside>
</template>

<style scoped></style>
