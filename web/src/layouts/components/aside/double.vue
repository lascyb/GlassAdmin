<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import GaLogo from '../logo.vue'
import GaMenus from '../menus/index.vue'
import type { Menu } from '../menus/types.ts'
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import { useLayoutSetting, ASIDE_MODULE_RAIL_WIDTH } from '@/pinia/layout/setting.ts'
import { menusForDisplay } from '@/layouts/components/menus/menusForDisplay'
import { useRoute, useRouter } from 'vue-router'
import GaIcon from "@components/ga-icon/index.vue";

const router = useRouter()
const route = useRoute()

const layoutMenus = useLayoutMenus()
const layoutSetting = useLayoutSetting()

const menus = ref<Menu[]>([])
const moduleOptions = computed(() => menusForDisplay(layoutMenus.menus))

/** asideStyle 侧栏宽度与收起态同步（收起态含玻璃 1px 边） */
const asideStyle = computed(() => ({
    width: layoutMenus.collapse
        ? 'var(--ga-aside-collapse-width)'
        : `${layoutSetting.layout.asideWidth}px`,
    transition: 'width var(--ga-duration-slow) var(--ga-ease)',
    '--ga-aside-module-rail-width': `${ASIDE_MODULE_RAIL_WIDTH}px`,
}))

watch(
    () => layoutMenus.collapse,
    (newVal) => {
        menus.value = []
        if (newVal) {
            unMountModule()
        } else {
            mountModule()
        }
    },
)

const changeModule = (val: number) => {
    const menu = layoutMenus.menus.find((item) => item.id == val)
    if (menu) {
        if (menu?.children && menu.children.length > 0) {
            menus.value = menu.children
        } else {
            menus.value = []
            router.push(menu.path_full)
        }
    } else {
        menus.value = []
    }
}
const mountModule = () => {
    const find = layoutMenus.find(route.path)
    if (find) {
        layoutMenus.module = find.menus[0]?.id
        if (layoutMenus.module) {
            changeModule(layoutMenus.module)
        }
    }
}
const unMountModule = () => {
    menus.value = layoutMenus.menus
}
onMounted(() => {
    mountModule()
})
</script>

<template>
    <el-aside :style="asideStyle">
        <ga-logo></ga-logo>
        <div class="ga-double-body">
            <div class="ga-double-modules" v-if="!layoutMenus.collapse">
                <el-segmented
                    v-model="layoutMenus.module"
                    class="ga-double-segmented"
                    :options="moduleOptions"
                    size="small"
                    direction="vertical"
                    :block="false"
                    :props="{ value: 'id', label: 'name' }"
                    @change="changeModule"
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
                v-if="menus && menus.length > 0"
                :menus="menus"
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
