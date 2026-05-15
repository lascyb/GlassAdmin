<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import GaLogo from '../logo.vue'
import GaMenus from '../menus/index.vue'
import type { Menu } from '../menus/types.ts'
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import { useRoute, useRouter } from 'vue-router'
import GaIcon from "@components/ga-icon/index.vue";

const router = useRouter()
const route = useRoute()

const layoutMenus = useLayoutMenus()

const menus = ref<Menu[]>([])

/** asideStyle 侧栏宽度与收起态同步（避免 scoped 内 --el-aside-width 与 useCssVar 目标不一致） */
const asideStyle = computed(() => ({
    width: layoutMenus.collapse
        ? 'calc(var(--el-menu-icon-width) + var(--el-menu-base-level-padding) * 2)'
        : '250px',
    transition: 'width var(--ga-duration-slow) var(--ga-ease)',
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
        <div style="display: flex; align-items: flex-start; height: calc(100vh - 60px)">
            <div style="height: 100%; background: var(--el-fill-color-light)" v-if="!layoutMenus.collapse">
                <el-segmented
                    v-model="layoutMenus.module"
                    :options="layoutMenus.menus"
                    size="small"
                    direction="vertical"
                    :block="false"
                    :props="{ value: 'id', label: 'name' }"
                    @change="changeModule"
                >
                    <template #default="scope">
                        <div>
                          <GaIcon :icon="scope.item?.icon" />
                          <div >{{ scope.item.name }}</div>
                        </div>
                    </template>
                </el-segmented>
            </div>
            <ga-menus v-if="menus && menus.length > 0" :menus="menus" style="height: calc(100vh - 60px); flex: 1"></ga-menus>
        </div>
    </el-aside>
</template>

<style scoped></style>
