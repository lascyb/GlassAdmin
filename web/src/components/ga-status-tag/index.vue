<script setup lang="ts">
import type { EnumOption, StatusMap } from '@/composables/types'
import { computed } from 'vue'

const props = defineProps<{
    /** value 状态值 */
    value: string | number | null | undefined
    /** map 状态映射 {value: {label, type}}（手写页） */
    map?: StatusMap
    /** options 选项数组（生成页 constants） */
    options?: EnumOption[]
    /** fallback 未命中时的展示文案 */
    fallback?: string
}>()

const item = computed(() => {
    if (props.value === null || props.value === undefined) return undefined
    if (props.map) return props.map[props.value]
    if (props.options) return props.options.find((o) => o.value == props.value)
    return undefined
})

const label = computed(() => item.value?.label ?? props.fallback ?? String(props.value ?? '—'))
const tagType = computed(() => item.value?.type ?? 'info')
</script>

<template>
    <el-tag :type="tagType">{{ label }}</el-tag>
</template>
