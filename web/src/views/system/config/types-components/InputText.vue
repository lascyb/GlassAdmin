<script setup lang="ts">
import { ElInput } from 'element-plus'

const props = defineProps<{
    modelValue: string
    options: string | null
    disabled?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
    change: [value: string]
}>()

const onChange = (val: string) => {
    emit('update:modelValue', val)
    emit('change', val)
}
</script>

<template>
    <el-input
        :model-value="modelValue"
        placeholder="请输入"
        clearable
        :disabled="disabled"
        @update:model-value="onChange"
    />
</template>
