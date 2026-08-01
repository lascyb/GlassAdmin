<script setup lang="ts">
defineEmits<{ 'edit-remote': [] }>()
</script>

<template>
    <el-form size="small">
        <el-form-item label="远程">
            <el-button size="small" type="primary" link @click="$emit('edit-remote')">编辑远程</el-button>
        </el-form-item>
    </el-form>
</template>
