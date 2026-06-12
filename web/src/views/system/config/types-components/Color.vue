<script setup lang="ts">
import { ElColorPicker } from 'element-plus'

const props = defineProps<{
    modelValue: string
    options: string | null
    disabled?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
    change: [value: string]
}>()

const onChange = (val: string | null) => {
    if (val === null) return
    emit('update:modelValue', val)
    emit('change', val)
}
</script>

<template>
    <el-color-picker
        :model-value="modelValue"
        :disabled="disabled"
        @update:model-value="onChange"
    />
</template>
