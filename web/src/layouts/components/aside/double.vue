<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import { ASIDE_MODULE_RAIL_WIDTH, useLayoutSetting } from '@/pinia/layout/setting.ts'
import GaIcon from "@components/ga-icon/index.vue"
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import GaLogo from '../logo.vue'
import GaMenus from '../menus/index.vue'

const route = useRoute()

const layoutMenus = useLayoutMenus()
const layoutSetting = useLayoutSetting()

/** asideMenus 展开用当前模块子菜单；收起展示完整树 */
const asideMenus = computed(() =>
    layoutMenus.collapse ? layoutMenus.menus : layoutMenus.subMenus,
)

/** asideStyle 侧栏宽度与收起态同步（收起态含玻璃 1px 边） */
const asideStyle = computed(() => ({
    width: layoutMenus.collapse
        ? 'var(--ga-aside-collapse-width)'
        : `${layoutSetting.layout.asideWidth}px`,
    transition: 'width var(--ga-duration-slow) var(--ga-ease)',
    '--ga-aside-module-rail-width': `${ASIDE_MODULE_RAIL_WIDTH}px`,
}))

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
    <el-aside :style="asideStyle">
        <ga-logo></ga-logo>
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
                            <GaIcon :icon="scope.item?.icon" />
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
    </el-aside>
</template>

<style scoped>
.ga-double-body {
    display: flex;
    align-items: flex-start;
    height: calc(100vh - 60px);
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

.ga-double-segment__label {
    width: 100%;
    overflow: hidden;
    word-break: break-all;
}

.ga-double-menus {
    flex: 1;
    min-width: 0;
    height: calc(100vh - 60px);
}
</style>
