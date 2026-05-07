<script setup lang="ts">
import {useLayoutSetting} from "@/pinia/layout/setting.ts";
import {ref} from "vue";
import {View} from "@element-plus/icons-vue";

const setting = useLayoutSetting()
const opacity = ref(1)
const opacityHide = () => {
  opacity.value = 0
}

const opacityShow = () => {
  opacity.value = 1
}
</script>

<template>
  <el-drawer v-model="setting.layout.showSetting" direction="rtl" title="个人偏好" :style="{'opacity': opacity}">
    <el-radio-group v-model="setting.layout.container">
      <el-radio style="opacity: 1" value="Default"> Default
        <el-icon @mouseenter="opacityHide" @mouseleave="opacityShow">
          <View/>
        </el-icon>
      </el-radio>
      <el-radio style="opacity: 1" value="Double"> Double
        <el-icon @mouseenter="opacityHide" @mouseleave="opacityShow">
          <View/>
        </el-icon>
      </el-radio>
    </el-radio-group>
  </el-drawer>
</template>

<style scoped>

</style>