<script setup lang="ts">
import type { StatusMap } from '@/composables/types'
import { computed } from 'vue'

const props = defineProps<{
    /** value 状态值 */
    value: string | number | null | undefined
    /** map 状态映射 {value: {label, type}} */
    map: StatusMap
    /** fallback 未命中 map 时的展示文案 */
    fallback?: string
}>()

const item = computed(() => {
    if (props.value === null || props.value === undefined) return undefined
    return props.map[props.value]
})

const label = computed(() => item.value?.label ?? props.fallback ?? String(props.value ?? '—'))
const tagType = computed(() => item.value?.type ?? 'info')
</script>

<template>
    <el-tag :type="tagType">{{ label }}</el-tag>
</template>
