<script setup lang="ts">
import {computed, onMounted} from "vue";
import {defaultValue,type FormNumber} from "@/views/dev/curd/components/render/form/number/index.ts";
import {useCurdField} from "@/pinia/dev/field.ts";

const store = useCurdField()

// 生成一个类型安全的 Ref，同时保持响应式
const form = computed<FormNumber>({
  get: () => store.form as FormNumber,
  set: (val:any) => store.form = val
})

onMounted(() => {
  store.form = { ...defaultValue, ...store.form } as FormNumber;
})
</script>

<template>
  <el-form v-if="form" size="small">
    <el-form-item label="最小值">
      <el-input-number v-model="form.min" :max="Number.MAX_SAFE_INTEGER" :min="Number.MIN_SAFE_INTEGER"
                       controls-position="right" class="w-36!"/>
    </el-form-item>
    <el-form-item label="最大值">
      <el-input-number v-model="form.max" :max="Number.MAX_SAFE_INTEGER" :min="Number.MIN_SAFE_INTEGER"
                       controls-position="right" class="w-36!"/>
    </el-form-item>
    <el-form-item label="精度">
      <el-input-number v-model="form.precision" step-strictly :step="1" controls-position="right" class="w-36!"/>
    </el-form-item>
    <el-form-item label="步进">
      <el-input-number v-model="form.step" controls-position="right" class="w-36!"/>
    </el-form-item>
  </el-form>
</template>
