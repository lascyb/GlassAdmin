<script setup lang="ts">
import {onMounted, ref, watch} from "vue";
import {User} from "@element-plus/icons-vue"
import GaLogo from "../logo.vue";
import GaMenus from "../menus/index.vue";
import type {Menu} from "../menus/types.ts";
import {useLayoutMenus} from "@/pinia/layout/menus.ts";
import {useRoute, useRouter} from "vue-router";
import {useCssVar} from "@vueuse/core";

const router = useRouter()
const route = useRoute()

const layoutMenus = useLayoutMenus()

const menus = ref<Menu[]>([]);
const aside_with = useCssVar("--el-aside-width")

watch(() => layoutMenus.collapse, (newVal) => {
  menus.value = []
  if (newVal) {
    unMountModule()
    aside_with.value = "calc(var(--el-menu-icon-width) + var(--el-menu-base-level-padding)*2)";
  } else {
    aside_with.value = "300px"
    mountModule()
  }
})

const changeModule = (val: number) => {
  let menu = layoutMenus.menus.find((item) => item.id == val)
  if (menu) {
    if (menu?.children && menu.children.length > 0) {
      menus.value = menu.children
    } else {
      menus.value = []
      router.push(menu.path_full)
    }
  } else {
    menus.value = []
  }
}
const mountModule = () => {
  const find = layoutMenus.find(route.path) //模块自动选择
  if (find) {
    layoutMenus.module = find.menus[0]?.id
    if (layoutMenus.module) {
      changeModule(layoutMenus.module)
    }
  }
}
const unMountModule = () => {
  menus.value = layoutMenus.menus
}
onMounted(() => {
  mountModule()
})
</script>

<template>
  <el-aside>
    <ga-logo></ga-logo>
    <div style="display: flex;align-items: flex-start;height: calc(100vh - 60px);">
      <div style="height: 100%;background: var(--el-fill-color-light);" v-if="!layoutMenus.collapse">
        <el-segmented v-model="layoutMenus.module" :options="layoutMenus.menus" size="small" direction="vertical"
                      :block="false"
                      :props="{value:'id',label:'name'}" @change="changeModule">
          <template #default="scope">
            <div>
              <el-icon size="20">
                <component :is="scope.item?.icon??User"/>
              </el-icon>
              <div>{{ scope.item.name }}</div>
            </div>
          </template>
        </el-segmented>
      </div>
      <ga-menus v-if="menus && menus.length>0" :menus="menus" style="height: calc(100vh - 60px);flex: 1"></ga-menus>
    </div>
  </el-aside>
</template>

<style scoped>

</style>