<script setup lang="ts">
import { menusForDisplay } from '@/layouts/components/menus/menusForDisplay'
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { type PropType, computed } from 'vue'
import { useRoute } from 'vue-router'
import MenuTree from './MenuTree.vue'
import type { Menu } from './types.ts'

const layoutMenus = useLayoutMenus()
const layoutSetting = useLayoutSetting()

const props = defineProps({
    menus: {
        type: Array as PropType<Menu[]>,
        default: () => [],
    },
    /** mode 侧栏纵向 / 顶栏横向 */
    mode: {
        type: String as PropType<'vertical' | 'horizontal'>,
        default: 'vertical',
    },
})

const route = useRoute()
const displayMenus = computed(() => menusForDisplay(props.menus))
const isHorizontal = computed(() => props.mode === 'horizontal')
</script>

<template>
    <el-menu
        :class="isHorizontal ? 'ga-top-menus' : 'ga-aside-menus'"
        :mode="mode"
        :default-active="route.path"
        router
        :collapse="isHorizontal ? false : layoutMenus.collapse"
        :unique-opened="layoutSetting.layout.menuAccordion"
        :ellipsis="false"
    >
        <MenuTree :menus="displayMenus" />
    </el-menu>
</template>

<style scoped>
.ga-aside-menus {
    box-sizing: border-box;
    /* 覆盖 EP / 旧 overflow:auto：收起 width 过渡时不要闪横向条；条目多时仍纵向滚动 */
    overflow-x: hidden;
    overflow-y: auto;
}

.ga-top-menus {
    box-sizing: border-box;
    height: 100%;
    border-bottom: none;
    background: transparent;
    /* EP 横菜单默认高度常为 60px，顶栏含 1px 底边后内容区更矮，固定 60 会溢出出竖条 */
    --el-menu-horizontal-height: 100%;
}

.ga-top-menus :deep(.el-menu-item),
.ga-top-menus :deep(.el-sub-menu__title) {
    display: flex;
    align-items: center;
    height: 100%;
    line-height: 1.25;
    border-bottom: none !important;
}

.ga-top-menus :deep(.el-sub-menu) {
    height: 100%;
}
</style>
