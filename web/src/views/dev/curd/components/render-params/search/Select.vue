<script setup lang="ts">
defineEmits<{ 'edit-enum': [] }>()
</script>

<template>
    <el-form size="small">
        <el-form-item label="枚举">
            <el-button size="small" type="primary" link @click="$emit('edit-enum')">编辑枚举</el-button>
        </el-form-item>
    </el-form>
</template>
