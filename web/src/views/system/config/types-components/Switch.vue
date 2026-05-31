<script setup lang="ts">
import { computed } from 'vue'
import { ElSwitch } from 'element-plus'

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
    get: () => props.modelValue === '1',
    set: (val: boolean) => {
        const str = val ? '1' : '0'
        emit('update:modelValue', str)
        emit('change', str)
    },
})
</script>

<template>
    <el-switch v-model="innerValue" :disabled="disabled" />
</template>
