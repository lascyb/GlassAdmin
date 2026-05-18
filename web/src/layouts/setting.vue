<script setup lang="ts">
import { useLayoutSetting } from "@/pinia/layout/setting.ts";

const setting = useLayoutSetting()

</script>

<template>
    <el-drawer v-model="setting.layout.showSetting" direction="rtl" title="个人偏好">
        <el-form>
            <el-form-item label="菜单样式">
                <el-radio-group v-model="setting.layout.container">
                    <el-radio-button style="opacity: 1" value="Default">左侧单层</el-radio-button>
                    <el-radio-button style="opacity: 1" value="Double">左侧双层</el-radio-button>
                </el-radio-group>
            </el-form-item>
        </el-form>
    </el-drawer>
</template>

<style scoped>

</style>