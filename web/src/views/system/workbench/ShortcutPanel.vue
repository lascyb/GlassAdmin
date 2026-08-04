<script setup lang="ts">
import GaIcon from '@components/ga-icon/index.vue'
import { Plus, Setting } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'

import type { WorkbenchShortcut } from './types.ts'

defineProps<{
    items: WorkbenchShortcut[]
}>()

const emit = defineEmits<{
    customize: []
}>()

const router = useRouter()

const open = (item: WorkbenchShortcut) => {
    // link / iframe 均走内部路由，由 link.vue / iframe.vue 统一渲染
    void router.push(item.path_full)
}
</script>

<template>
    <el-card shadow="never" class="wb-card">
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-[15px] font-medium text-gray-800">快捷入口</span>
                    <span class="text-xs font-normal text-gray-400">点击进入 · 可自定义</span>
                </div>
                <el-button text type="primary" :icon="Setting" @click="emit('customize')">自定义</el-button>
            </div>
        </template>

        <div v-if="items.length" class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
            <button
                v-for="item in items"
                :key="item.id"
                type="button"
                class="shortcut-item group"
                @click="open(item)"
            >
                <span class="shortcut-icon">
                    <GaIcon :icon="item.icon" :url-size="22" class="text-[22px] text-[var(--el-color-primary)]" />
                </span>
                <span class="shortcut-name">{{ item.name }}</span>
            </button>
        </div>

        <div v-else class="flex flex-col items-center justify-center gap-3 py-10 text-gray-400">
            <el-empty description="还没有快捷入口" :image-size="72">
                <el-button type="primary" :icon="Plus" @click="emit('customize')">添加入口</el-button>
            </el-empty>
        </div>
    </el-card>
</template>

<style scoped>
.wb-card {
    border: 1px solid var(--el-border-color-lighter);
}

.shortcut-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 14px 8px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    cursor: pointer;
    transition:
        background var(--ga-duration) var(--ga-ease),
        border-color var(--ga-duration) var(--ga-ease),
        transform var(--ga-duration) var(--ga-ease);
}

.shortcut-item:hover {
    background: var(--el-fill-color-blank);
    border-color: var(--el-border-color-lighter);
    transform: translateY(-1px);
}

.shortcut-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: var(--el-color-primary-light-9);
    transition: background var(--ga-duration) var(--ga-ease);
}

.shortcut-item:hover .shortcut-icon {
    background: var(--el-color-primary-light-8);
}

.shortcut-name {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 12px;
    line-height: 1.2;
    color: var(--el-text-color-regular);
}
</style>
