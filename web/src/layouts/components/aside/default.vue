<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { computed } from 'vue'
import AsideFooter from './AsideFooter.vue'
import GaMenus from '../menus/index.vue'

const layoutMenus = useLayoutMenus()
const layoutSetting = useLayoutSetting()

/** asideStyle 侧栏宽度：收起态含玻璃 1px 边，与 el-menu--collapse 内容宽对齐 */
const asideStyle = computed(() => ({
    width: layoutMenus.collapse
        ? 'var(--ga-aside-collapse-width)'
        : `${layoutSetting.layout.asideWidth}px`,
    transition: 'width var(--ga-duration-slow) var(--ga-ease)',
}))
</script>

<template>
    <el-aside class="ga-aside-nav flex h-full flex-col" :style="asideStyle">
        <ga-menus :menus="layoutMenus.menus" class="min-h-0 flex-1" />
        <aside-footer />
    </el-aside>
</template>

<style scoped></style>
