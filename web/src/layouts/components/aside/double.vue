<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import { ASIDE_MODULE_RAIL_WIDTH, useLayoutSetting } from '@/pinia/layout/setting.ts'
import GaIcon from '@components/ga-icon/index.vue'
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import GaMenus from '../menus/index.vue'
import AsideFooter from './AsideFooter.vue'

const route = useRoute()

const layoutMenus = useLayoutMenus()
const layoutSetting = useLayoutSetting()

/** asideMenus 展开用当前模块子菜单；收起展示完整树 */
const asideMenus = computed(() =>
    layoutMenus.collapse ? layoutMenus.menus : layoutMenus.subMenus,
)

const hasSubMenus = computed(() => layoutMenus.subMenus.length > 0)

/** asideStyle 侧栏宽度与收起态同步；展开且无子菜单时仅模块轨宽 */
const asideStyle = computed(() => {
    let width: string
    if (layoutMenus.collapse) {
        width = 'var(--ga-aside-collapse-width)'
    } else if (!hasSubMenus.value) {
        width = `${ASIDE_MODULE_RAIL_WIDTH}px`
    } else {
        width = `${layoutSetting.layout.asideWidth}px`
    }
    return {
        width,
        transition: 'width var(--ga-duration-slow) var(--ga-ease)',
        '--ga-aside-module-rail-width': `${ASIDE_MODULE_RAIL_WIDTH}px`,
    }
})

const onModuleChange = (moduleId: number) => {
    layoutMenus.setActiveModule(moduleId, { navigate: true })
}

watch(
    () => route.path,
    (path) => {
        layoutMenus.syncModuleFromPath(path)
    },
    { immediate: true },
)
</script>

<template>
    <el-aside class="ga-aside-nav flex h-full flex-col" :style="asideStyle">
        <div class="ga-double-body">
            <div class="ga-double-modules" v-if="!layoutMenus.collapse">
                <el-segmented
                    v-model="layoutMenus.module"
                    class="ga-double-segmented"
                    :options="layoutMenus.moduleMenus"
                    size="small"
                    direction="vertical"
                    :block="false"
                    :props="{ value: 'id', label: 'name' }"
                    @change="onModuleChange"
                >
                    <template #default="scope">
                        <div class="ga-double-segment">
                            <GaIcon :icon="scope.item?.icon" :url-size="22" class="ga-double-segment__icon" />
                            <div class="ga-double-segment__label">{{ scope.item.name }}</div>
                        </div>
                    </template>
                </el-segmented>
            </div>
            <ga-menus
                v-if="asideMenus && asideMenus.length > 0"
                :menus="asideMenus"
                class="ga-double-menus"
            />
        </div>
        <aside-footer />
    </el-aside>
</template>

<style scoped>
.ga-double-body {
    display: flex;
    align-items: flex-start;
    flex: 1;
    min-height: 0;
    min-width: 0;
}

.ga-double-modules {
    flex: 0 0 var(--ga-aside-module-rail-width);
    width: var(--ga-aside-module-rail-width);
    min-width: 0;
    height: 100%;
    overflow-x: hidden;
    overflow-y: auto;
    background: color-mix(in srgb, var(--ga-color-fill-light) 42%, transparent);
    border-right: 1px solid var(--ga-glass-border);
}

/* 无子菜单时仅模块轨，右边线走 aside 外框，避免双线 */
.ga-double-modules:only-child {
    border-right: none;
}

.ga-double-segmented {
    --el-border-radius-base: 0;
    width: 100%;
    background: transparent;
}

.ga-double-segment {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    width: 100%;
    padding: 8px 4px;
    font-size: 12px;
    line-height: 1.25;
    text-align: center;
}

.ga-double-segment__icon,
.ga-double-segment :deep(.el-icon) {
    font-size: 22px;
}

.ga-double-segment__label {
    width: 100%;
    overflow: hidden;
    word-break: break-all;
}

.ga-double-menus {
    flex: 1;
    min-width: 0;
    height: 100%;
}
</style>
