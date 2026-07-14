<script setup lang="ts">
import { Document, DocumentCopy, Plus } from '@element-plus/icons-vue'

const emit = defineEmits<{
    history: []
    table: []
    empty: []
}>()
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 px-4">
        <div class="text-base font-medium mb-2">CRUD 生成</div>
        <div class="text-xs text-gray-500 mb-10">选择配置来源，进入可视化参数构建</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full max-w-3xl">
            <el-card shadow="hover" class="cursor-pointer !border" @click="emit('history')">
                <div class="flex flex-col items-center py-6 gap-3">
                    <el-icon :size="36" color="var(--el-color-primary)"><DocumentCopy /></el-icon>
                    <div class="text-sm font-medium">从历史记录</div>
                    <div class="text-xs text-gray-500 text-center">选择已生成方案并继续编辑</div>
                </div>
            </el-card>
            <el-card shadow="hover" class="cursor-pointer !border" @click="emit('table')">
                <div class="flex flex-col items-center py-6 gap-3">
                    <el-icon :size="36" color="var(--el-color-primary)"><Document /></el-icon>
                    <div class="text-sm font-medium">从数据库表</div>
                    <div class="text-xs text-gray-500 text-center">反推表结构为配置草稿</div>
                </div>
            </el-card>
            <el-card shadow="hover" class="cursor-pointer !border" @click="emit('empty')">
                <div class="flex flex-col items-center py-6 gap-3">
                    <el-icon :size="36" color="var(--el-color-primary)"><Plus /></el-icon>
                    <div class="text-sm font-medium">从零开始</div>
                    <div class="text-xs text-gray-500 text-center">空配置（含 id 主键）</div>
                </div>
            </el-card>
        </div>
    </div>
</template>
