<script setup lang="ts">
import { computed } from 'vue'
import { ElSelect, ElOption } from 'element-plus'

const props = defineProps<{
    modelValue: string
    options: string | null
    disabled?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
    change: [value: string]
}>()

const optionList = computed<string[]>(() => {
    if (!props.options) return []
    try {
        const parsed = JSON.parse(props.options)
        return Array.isArray(parsed) ? parsed : []
    } catch {
        return []
    }
})

const onChange = (val: string) => {
    emit('update:modelValue', val)
    emit('change', val)
}
</script>

<template>
    <el-select
        :model-value="modelValue"
        placeholder="请选择"
        clearable
        class="w-full"
        :disabled="disabled"
        @update:model-value="onChange"
    >
        <el-option v-for="opt in optionList" :key="opt" :label="opt" :value="opt" />
    </el-select>
</template>
