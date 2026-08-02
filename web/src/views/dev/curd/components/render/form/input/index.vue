<script setup lang="ts">
import {computed, onMounted} from "vue";
import {defaultValue, type FormInput} from "@/views/dev/curd/components/render/form/input/index.ts";
import {useCurdField} from "@/pinia/dev/field.ts";
const store = useCurdField()

// 生成一个类型安全的 Ref，同时保持响应式
const form = computed<FormInput>({
  get: () => store.form as FormInput,
  set: (val:any) => store.form = val
})

onMounted(() => {
  store.form = { ...defaultValue, ...store.form } as FormInput;
})
</script>

<template>
  <el-form v-if="store.form" size="small">
    <el-form-item label="最大长度">
      <el-input-number
          v-model="form.maxLength"
          :min="0"
          controls-position="right"
          class="w-36!"
      />
    </el-form-item>
    <el-form-item label="最小长度">
      <el-input-number
          v-model="form.minLength"
          :min="0"
          controls-position="right"
          class="w-36!"
      />
    </el-form-item>
    <el-form-item label="是否显示统计字数" v-if="form.type == 'text' || form.type == 'textarea' ">
      <el-switch
          v-model="form.showWordLimit"
          controls-position="right"
          class="w-36!"
      />
    </el-form-item>
    <el-form-item label="输入框占位文本">
      <el-input v-model="form.placeholder"></el-input>
    </el-form-item>
  </el-form>
</template>
