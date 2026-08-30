<script setup lang="ts">
withDefaults(
    defineProps<{
        /** title 页面标题 */
        title?: string
        /** shadow el-card shadow */
        shadow?: 'always' | 'hover' | 'never'
        /** padding 内边距变体 */
        padding?: 'none' | 'sm' | 'md' | 'lg'
        /** minHeight 最小高度 */
        minHeight?: string
    }>(),
    {
        shadow: 'never',
        padding: 'md',
        minHeight: '',
    },
)

const paddingVar: Record<string, string> = {
    none: '0',
    sm: 'var(--ga-space-2)',
    md: 'var(--ga-space-4)',
    lg: 'var(--ga-space-6)',
}
</script>

<template>
    <div
        class="ga-page"
        :class="{ 'ga-page--stretch': !!minHeight }"
        :style="{
            padding: paddingVar[padding],
            minHeight: minHeight || undefined,
        }"
    >
        <el-card :shadow="shadow" :body-style="padding === 'none' ? 'padding:0' : undefined">
            <template v-if="title || $slots.header" #header>
                <slot name="header">{{ title }}</slot>
            </template>
            <slot />
        </el-card>
    </div>
</template>

<style scoped>
.ga-page--stretch {
    display: flex;
    flex-direction: column;
}

.ga-page--stretch :deep(.el-card) {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.ga-page--stretch :deep(.el-card__body) {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
</style>
