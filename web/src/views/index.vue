<script setup lang="ts">

import {views} from "@/router";
import {ref} from "vue";

// 将文件路径转换为相对路径格式（去掉 /src/views/ 前缀和 .vue 后缀）


const options = ref(Object.keys(views).map((item: string) => {
  return {
    label: item.replace(/^\/src/, '@'),
    value: item
  };
}));
const file = ref("");

</script>

<template>
  <div>
    <el-alert>index</el-alert>
    {{ file }}
    <el-select v-model="file" clearable>
      <el-option v-for="item in options" :value="item.value" :label="item.label"></el-option>
    </el-select>
  </div>
</template>

<style scoped>

</style>