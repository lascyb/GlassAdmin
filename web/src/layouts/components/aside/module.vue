<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { computed } from 'vue'
import GaLogo from '../logo.vue'
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

/** 顶栏 logo 60px（含 1px 底边，border-box） */
const menuHeight = 'calc(100vh - 60px)'
</script>

<template>
    <el-aside :style="asideStyle">
        <ga-logo />
        <ga-menus
            v-if="layoutMenus.subMenus.length > 0"
            :menus="layoutMenus.subMenus"
            :style="{ height: menuHeight }"
        />
        <div
            v-else
            class="flex items-center justify-center text-xs text-ga-text-secondary"
            :style="{ height: menuHeight }"
        >
            暂无子菜单
        </div>
    </el-aside>
</template>

<style scoped></style>
