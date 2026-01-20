<script setup lang="ts">
import {useLayoutSetting} from "@/pinia/layout/setting.ts";
import GaSetting from "./setting.vue";
import {Tools} from "@element-plus/icons-vue";
import {ref} from "vue";

const setting = useLayoutSetting()
const activeName = ref("system")
</script>

<template>
  <div class="navs">


    <div class="flex items-center" style="padding-top:6px;">
      <el-popover
          title="Title"
          content="Left Top prompts info"
          placement="bottom-end"
          width="300"
      >
        <template #reference>
          <el-badge :value="12">
            <el-avatar class="mr-1 mb-0"/>
          </el-badge>
        </template>
        <template #default>
          <el-tabs v-model="activeName">
            <el-tab-pane label="系统消息" name="system">系统消息</el-tab-pane>
            <el-tab-pane label="好友消息" name="friend">好友消息</el-tab-pane>
          </el-tabs>
        </template>
      </el-popover>
    </div>
    <el-icon @click="setting.showSetting()" size="20">
      <Tools/>
    </el-icon>
    <ga-setting></ga-setting>
  </div>
</template>

<style scoped>
.navs {
  display: flex;
  align-items: center;

  > * {
    padding: 0 5px;
    height: 100%;
    min-width: 40px;
    cursor: pointer;

    &:hover {
      background: #F0F2F5;
    }
  }
}
</style>