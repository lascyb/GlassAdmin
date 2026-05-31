<script setup lang="ts">
import { computed, watch } from 'vue'
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

const parseValue = (v: string): string[] => {
    if (!v) return []
    try {
        const parsed = JSON.parse(v)
        return Array.isArray(parsed) ? parsed : String(v).split(',').filter(Boolean)
    } catch {
        return String(v).split(',').filter(Boolean)
    }
}

const innerValue = computed<string[]>({
    get: () => parseValue(props.modelValue),
    set: (val: string[]) => {
        const str = JSON.stringify(val)
        emit('update:modelValue', str)
        emit('change', str)
    },
})
</script>

<template>
    <el-select
        v-model="innerValue"
        multiple
        placeholder="请选择"
        class="w-full"
        :disabled="disabled"
    >
        <el-option v-for="opt in optionList" :key="opt" :label="opt" :value="opt" />
    </el-select>
</template>
