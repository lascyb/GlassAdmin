<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { computed } from 'vue'
import GaLogo from '../logo.vue'
import GaMenus from '../menus/index.vue'

const layoutMenus = useLayoutMenus()
const layoutSetting = useLayoutSetting()

/** asideStyle 侧栏宽度：与 el-menu collapse 同步 */
const asideStyle = computed(() => ({
    width: layoutMenus.collapse
        ? 'calc(var(--el-menu-icon-width) + var(--el-menu-base-level-padding) * 2)'
        : `${layoutSetting.layout.asideWidth}px`,
    transition: 'width var(--ga-duration-slow) var(--ga-ease)',
}))

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
            class="flex items-center justify-center text-xs text-gray-400"
            :style="{ height: menuHeight }"
        >
            暂无子菜单
        </div>
    </el-aside>
</template>

<style scoped></style>
