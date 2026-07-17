<script setup lang="ts">
import { useDevCurdField } from '@/pinia/dev/curdField'
import { storeToRefs } from 'pinia'
import ColumnConfigCard from './field-detail/ColumnConfigCard.vue'
import FormConfigCard from './field-detail/FormConfigCard.vue'
import ListConfigCard from './field-detail/ListConfigCard.vue'

const { selectedField: field } = storeToRefs(useDevCurdField())
</script>

<template>
    <el-card shadow="never" class="h-full flex flex-col min-h-0">
        <el-empty v-if="!field" description="请选择左侧字段" :image-size="72" />
        <el-row :gutter="8">
            <el-col :span="24" class="mb-2"><ColumnConfigCard /></el-col>
            <el-col :span="12"><FormConfigCard /></el-col>
            <el-col :span="12"><ListConfigCard /></el-col>
        </el-row>
    </el-card>
</template>
