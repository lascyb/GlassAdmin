<script setup lang="ts">
import {useRouter} from "vue-router";
import {useLayoutMenus} from "@/pinia/layout/menus";
import {Expand, Fold} from "@element-plus/icons-vue";

const layoutMenus = useLayoutMenus()
const router = useRouter()
const toHome = () => {
  layoutMenus.module = undefined
  router.push('/')
}

const collapse = () => {
  layoutMenus.collapse = !layoutMenus.collapse
}
</script>

<template>
  <div style="height: 60px;border-right: 1px solid var(--el-menu-border-color);box-sizing: border-box"
       class="flex items-center justify-between">
    <div v-if="!layoutMenus.collapse" @click="toHome" class="flex items-center justify-center flex-1">GlassAdmin</div>
    <el-button :style="{'height': '100%','width': layoutMenus.collapse?'100%':'50px'}"
               @click="collapse" plain text type="primary">
      <el-icon size="26" >
        <Expand v-if="layoutMenus.collapse"/>
        <Fold v-else/>
      </el-icon>
    </el-button>
  </div>
</template>

<style scoped></style>