<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus.ts'
import GaIcon from '@components/ga-icon/index.vue'
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import HeaderRight from './components/HeaderRight.vue'

const layoutMenus = useLayoutMenus()
const route = useRoute()

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
    <el-header class="module-header flex items-stretch p-0">
        <div class="module-bar flex min-w-0 flex-1 items-stretch overflow-x-auto px-3">
            <el-segmented
                v-model="layoutMenus.module"
                class="module-segmented"
                :options="layoutMenus.moduleMenus"
                size="default"
                :props="{ value: 'id', label: 'name' }"
                @change="onModuleChange"
            >
                <template #default="scope">
                    <div class="module-segment">
                        <GaIcon :icon="scope.item?.icon" />
                        <span>{{ scope.item.name }}</span>
                    </div>
                </template>
            </el-segmented>
        </div>
        <header-right />
    </el-header>
</template>

<style scoped>
.module-bar {
    height: 100%;
}

.module-segmented {
    height: 100%;
}

.module-bar :deep(.el-segmented) {
    --el-border-radius-base: 0;
    height: 100%;
    background: transparent;
}

.module-bar :deep(.el-segmented__group) {
    height: 100%;
    align-items: stretch;
}

.module-bar :deep(.el-segmented__item) {
    display: flex;
    align-items: center;
}

.module-segment {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 100%;
    padding: 0 8px;
    white-space: nowrap;
}
</style>
