<script setup lang="ts">
import GaIcon from '@/components/ga-icon/index.vue'
import { Clock, Delete } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'

import { formatRelativeTime, type WorkbenchRecent } from './types'

defineProps<{
    items: WorkbenchRecent[]
}>()

const emit = defineEmits<{
    clear: []
}>()

const router = useRouter()

const open = (item: WorkbenchRecent) => {
    if (item.type === 'link') {
        window.open(item.path_full, '_blank')
        return
    }
    void router.push(item.path_full)
}
</script>

<template>
    <el-card shadow="never" class="wb-card h-full">
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <el-icon class="text-gray-500"><Clock /></el-icon>
                    <span class="text-[15px] font-medium text-gray-800">最近访问</span>
                </div>
                <el-button
                    v-if="items.length"
                    text
                    type="info"
                    :icon="Delete"
                    @click="emit('clear')"
                >
                    清空
                </el-button>
            </div>
        </template>

        <ul v-if="items.length" class="recent-list">
            <li v-for="item in items" :key="`${item.id}-${item.path_full}`">
                <button type="button" class="recent-row" @click="open(item)">
                    <span class="recent-icon">
                        <GaIcon :icon="item.icon" :url-size="16" class="text-[16px]" />
                    </span>
                    <span class="recent-name">{{ item.name }}</span>
                    <span class="recent-time">{{ formatRelativeTime(item.visited_at) }}</span>
                </button>
            </li>
        </ul>

        <el-empty v-else description="暂无访问记录" :image-size="64" class="py-6" />
    </el-card>
</template>

<style scoped>
.wb-card {
    border: 1px solid var(--el-border-color-lighter);
}

.recent-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.recent-row {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 10px;
    padding: 10px 8px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    cursor: pointer;
    transition: background var(--ga-duration) var(--ga-ease);
}

.recent-row:hover {
    background: var(--el-fill-color-light);
}

.recent-icon {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: var(--el-fill-color);
    color: var(--el-text-color-regular);
}

.recent-name {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-align: left;
    font-size: 13px;
    color: var(--el-text-color-primary);
}

.recent-time {
    flex-shrink: 0;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}
</style>
