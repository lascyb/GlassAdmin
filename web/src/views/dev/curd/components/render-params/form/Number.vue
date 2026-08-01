<script setup lang="ts">
import { useDevCurdField } from '@/pinia/dev/curdField'
import { storeToRefs } from 'pinia'

const { formCfg } = storeToRefs(useDevCurdField())
</script>

<template>
    <el-form v-if="formCfg" size="small">
        <el-form-item label="最小值">
            <el-input-number v-model="formCfg.numberMin" controls-position="right" class="!w-36" />
        </el-form-item>
        <el-form-item label="最大值">
            <el-input-number v-model="formCfg.numberMax" controls-position="right" class="!w-36" />
        </el-form-item>
        <el-form-item label="步进">
            <el-input-number v-model="formCfg.numberStep" controls-position="right" class="!w-36" />
        </el-form-item>
    </el-form>
</template>
