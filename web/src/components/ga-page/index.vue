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

const paddingClass: Record<string, string> = {
    none: 'p-0',
    sm: 'p-2',
    md: 'p-4',
    lg: 'p-6',
}
</script>

<template>
    <div :class="paddingClass[padding]" :style="minHeight ? { minHeight } : undefined">
        <el-card :shadow="shadow" :body-style="padding === 'none' ? 'padding:0' : undefined">
            <template v-if="title || $slots.header" #header>
                <slot name="header">{{ title }}</slot>
            </template>
            <slot />
        </el-card>
    </div>
</template>
