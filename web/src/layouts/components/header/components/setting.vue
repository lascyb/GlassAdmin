<script setup lang="ts">
import {useLayoutSetting} from "@/pinia/layout/setting.ts";
import {useLayout} from "@/pinia/layout";
import {nextTick} from "vue";

const setting = useLayoutSetting()
const layout = useLayout()

const change = ()=>{
  layout.loading = true
  nextTick(()=>{
    setTimeout(()=>{
      layout.loading = false
    },5000)
  })
}
</script>

<template>
  <el-drawer v-model="setting.layout.showSetting" direction="rtl">
    <el-radio-group v-model="setting.layout.container" @change="change">
      <el-radio value="Default">Default</el-radio>
      <el-radio value="Double">Double</el-radio>
    </el-radio-group>
  </el-drawer>
</template>

<style scoped>

</style>