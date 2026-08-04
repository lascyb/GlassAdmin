<script setup lang="ts">
import { Bell, DocumentChecked } from '@element-plus/icons-vue'

import type { WorkbenchTodo } from './types.ts'

defineProps<{
    items: WorkbenchTodo[]
}>()

const levelType = (level?: WorkbenchTodo['level']) => {
    if (level === 'warning') return 'warning'
    if (level === 'info') return 'info'
    return 'primary'
}
</script>

<template>
    <el-card shadow="never" class="wb-card h-full">
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <el-icon class="text-gray-500"><Bell /></el-icon>
                    <span class="text-[15px] font-medium text-gray-800">待办事项</span>
                    <el-tag v-if="items.length" size="small" type="danger" effect="plain" round>
                        {{ items.length }}
                    </el-tag>
                </div>
                <span class="text-xs text-gray-400">审批 / 通知预留</span>
            </div>
        </template>

        <ul v-if="items.length" class="todo-list">
            <li v-for="item in items" :key="item.id" class="todo-row">
                <el-tag :type="levelType(item.level)" size="small" effect="light" class="shrink-0">
                    {{ item.level === 'warning' ? '紧急' : item.level === 'info' ? '通知' : '待办' }}
                </el-tag>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-[13px] text-gray-800">{{ item.title }}</div>
                    <div v-if="item.summary" class="mt-0.5 truncate text-xs text-gray-400">
                        {{ item.summary }}
                    </div>
                </div>
                <span v-if="item.created_at" class="shrink-0 text-xs text-gray-400">{{ item.created_at }}</span>
            </li>
        </ul>

        <div v-else class="todo-placeholder">
            <div class="todo-placeholder-icon">
                <el-icon :size="28"><DocumentChecked /></el-icon>
            </div>
            <p class="todo-placeholder-title">暂无待办</p>
            <p class="todo-placeholder-desc">
                此处预留审批、站内通知等消息入口，后续接入流程后将在此汇总待处理事项。
            </p>
        </div>
    </el-card>
</template>

<style scoped>
.wb-card {
    border: 1px solid var(--el-border-color-lighter);
}

.todo-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.todo-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 4px;
    border-bottom: 1px solid var(--el-border-color-extra-light);
}

.todo-row:last-child {
    border-bottom: 0;
}

.todo-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    padding: 16px 20px;
    text-align: center;
}

.todo-placeholder-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    margin-bottom: 12px;
    border-radius: 14px;
    background: var(--el-fill-color-light);
    color: var(--el-text-color-secondary);
}

.todo-placeholder-title {
    margin: 0 0 6px;
    font-size: 14px;
    font-weight: 500;
    color: var(--el-text-color-regular);
}

.todo-placeholder-desc {
    margin: 0;
    max-width: 280px;
    font-size: 12px;
    line-height: 1.6;
    color: var(--el-text-color-secondary);
}
</style>
