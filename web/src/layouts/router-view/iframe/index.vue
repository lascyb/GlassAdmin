<script setup lang="ts">
import { Loading } from '@element-plus/icons-vue'

defineProps<{
    loading?: boolean
    error?: string
    hasSrc?: boolean
}>()

const emit = defineEmits<{
    retry: []
}>()
</script>

<template>
    <div class="iframe-wrap">
        <slot />
        <div v-if="loading" class="iframe-state iframe-overlay">
            <el-icon class="is-loading" :size="28"><Loading /></el-icon>
            <span>正在进入内嵌页面…</span>
        </div>
        <el-empty v-if="error && !hasSrc" :description="error">
            <el-button type="primary" @click="emit('retry')">重试</el-button>
        </el-empty>
        <el-empty v-else-if="!hasSrc && !loading" description="未配置内嵌地址" />
    </div>
</template>

<style scoped>
.iframe-wrap {
    position: relative;
    box-sizing: border-box;
    width: 100%;
    height: var(--ga-main-view-height);
    min-height: 320px;
    margin: 0;
    padding: 0;
    overflow: hidden;
    background: var(--el-fill-color-light);
}

.iframe-wrap :deep(iframe) {
    display: block;
    width: 100%;
    height: 100%;
    border: 0;
}

.iframe-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    height: 100%;
    color: var(--el-text-color-secondary);
    font-size: 14px;
}

.iframe-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    background: var(--el-fill-color-light);
}
</style>
