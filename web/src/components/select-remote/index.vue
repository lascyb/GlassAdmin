<script setup lang="ts">
const model = defineModel()
</script>

<template>
  <el-select v-model="model"  clearable filterable remote-method=""
             :value-on-clear="undefined">
    <el-option label="超级管理员" :value="1"></el-option>
    <template #footer>
      <el-pagination :total="100"></el-pagination>
    </template>
  </el-select>
</template>

<style scoped>

</style>