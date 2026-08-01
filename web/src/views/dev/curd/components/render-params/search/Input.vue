<script setup lang="ts">
import { useDevCurdField } from '@/pinia/dev/curdField'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'

const { selectedField: field } = storeToRefs(useDevCurdField())

/** searchMatch 文本匹配方式 {fuzzy:模糊,exact:精确} */
const searchMatch = computed({
    get: (): 'fuzzy' | 'exact' => field.value?.search.match ?? 'fuzzy',
    set: (v: 'fuzzy' | 'exact') => {
        if (field.value) field.value.search.match = v
    },
})
</script>

<template>
    <el-form v-if="field" size="small">
        <el-form-item label="匹配">
            <el-radio-group v-model="searchMatch" size="small">
                <el-radio value="fuzzy">模糊搜索</el-radio>
                <el-radio value="exact">精确搜索</el-radio>
            </el-radio-group>
        </el-form-item>
    </el-form>
</template>
