<script setup lang="ts">
import {onMounted, ref} from "vue";
import {User} from "@element-plus/icons-vue"
import GaLogo from "../logo.vue";
import GaMenus from "../menus/index.vue";
import type {Menu} from "../menus/types.ts";
import {useLayoutMenus} from "@/pinia/layout/menus.ts";
import {useRoute, useRouter} from "vue-router";

const router = useRouter()
const route = useRoute()

const layoutMenus = useLayoutMenus()

const menu = ref<Menu>();

const changeModule = (val: number = 0) => {
  menu.value = layoutMenus.menus.find((item) => item.id == layoutMenus.module)
  if (menu.value && val != 0) {
    if (!(menu.value?.children && menu.value.children.length > 0)) {
      router.push(menu.value.path_full)
    }
  }
}

onMounted(() => {
  // 此时 menus 已经初始化完成（因为父组件使用 v-if 控制了渲染时机）
  console.log("route.path", route.path)
  const find = layoutMenus.find(route.path) //模块自动选择
  console.log("find", find?.menus)
  if (find) {
    console.log(find.menus[0])
    layoutMenus.module = find.menus[0]?.id

    if (layoutMenus.module) {
      changeModule(0)
    }
  }
})
</script>

<template>
  <el-aside>
    <ga-logo></ga-logo>
    <div style="display: flex;align-items: flex-start;height: calc(100vh - 60px);">
      <div style="height: 100%;background: var(--el-fill-color-light);">
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
      <ga-menus v-if="menu?.children && menu?.children.length>0"
                :menus="menu.children"
                style="flex: 1;height: calc(100vh - 60px)"></ga-menus>
    </div>
  </el-aside>
</template>

<style scoped>

</style>