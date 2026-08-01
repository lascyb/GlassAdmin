<script setup lang="ts">
import { useDevCurdField } from '@/pinia/dev/curdField'
import { storeToRefs } from 'pinia'

const { formCfg } = storeToRefs(useDevCurdField())
</script>

<template>
    <el-form v-if="formCfg" size="small">
        <el-form-item label="最大长度">
            <el-input-number
                v-model="formCfg.maxLength"
                :min="0"
                controls-position="right"
                class="!w-36"
            />
        </el-form-item>
    </el-form>
</template>
