<script setup lang="ts">
import FieldNavPanel from './FieldNavPanel.vue'
import FieldWorkbench from './FieldWorkbench.vue'
</script>

<template>
    <el-row :gutter="10" class="flex-1 min-h-0">
        <el-col :span="10" class="min-h-0">
            <FieldNavPanel />
        </el-col>
        <el-col :span="14" class="min-h-0">
            <FieldWorkbench />
        </el-col>
    </el-row>
</template>
