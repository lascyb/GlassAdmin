<script setup lang="ts">
import { computed } from 'vue'
import { ElInputNumber } from 'element-plus'

const props = defineProps<{
    modelValue: string
    options: string | null
    disabled?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
    change: [value: string]
}>()

const innerValue = computed({
    get: () => {
        const n = Number(props.modelValue)
        return Number.isNaN(n) ? 0 : n
    },
    set: (val: number | undefined) => {
        const str = String(val ?? 0)
        emit('update:modelValue', str)
        emit('change', str)
    },
})
</script>

<template>
    <el-input-number v-model="innerValue" :disabled="disabled" :min="0" />
</template>
