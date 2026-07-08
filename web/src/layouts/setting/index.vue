<script setup lang="ts">
import { useLayoutSetting } from '@/pinia/layout/setting.ts';
import LayoutPicker from './LayoutPicker.vue';

const setting = useLayoutSetting()
</script>

<template>
    <el-drawer v-model="setting.layout.showSetting" direction="rtl" title="个人偏好">
        <el-form>
            <el-form-item label="菜单样式">
                <LayoutPicker v-model="setting.layout.container" />
            </el-form-item>
        </el-form>
    </el-drawer>
</template>

<style scoped></style>
