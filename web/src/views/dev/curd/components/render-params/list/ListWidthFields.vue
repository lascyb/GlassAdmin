<script setup lang="ts">
import { useDevCurdField } from '@/pinia/dev/curdField'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'

const { selectedField: field } = storeToRefs(useDevCurdField())

/** toNum 列宽配置转 number */
const toNum = (v: number | string | undefined): number | undefined => {
    if (v === undefined || v === '') return undefined
    const n = typeof v === 'number' ? v : Number(v)
    return Number.isFinite(n) ? n : undefined
}

/** width 列宽 */
const width = computed({
    get: (): number | undefined => toNum(field.value?.list.width),
    set: (n: number | undefined) => {
        if (!field.value) return
        if (n === undefined || n === null) delete field.value.list.width
        else field.value.list.width = n
    },
})

/** minWidth 最小列宽 */
const minWidth = computed({
    get: (): number | undefined => toNum(field.value?.list.minWidth),
    set: (n: number | undefined) => {
        if (!field.value) return
        if (n === undefined || n === null) delete field.value.list.minWidth
        else field.value.list.minWidth = n
    },
})
</script>

<template>
    <el-form v-if="field" size="small">
        <el-form-item label="列宽">
            <el-input-number
                v-model="width"
                :min="0"
                controls-position="right"
                class="!w-36"
                placeholder="width"
            />
        </el-form-item>
        <el-form-item label="最小列宽">
            <el-input-number
                v-model="minWidth"
                :min="0"
                controls-position="right"
                class="!w-36"
                placeholder="minWidth"
            />
        </el-form-item>
    </el-form>
</template>
