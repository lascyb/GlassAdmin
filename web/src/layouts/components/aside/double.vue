<script setup lang="ts">
import {onMounted, ref} from "vue";
import {User} from "@element-plus/icons-vue"
import GaLogo from "../logo.vue";
import GaMenus from "../menus/index.vue";
import type {Menu} from "../menus/types.ts";
import {useLayoutMenus} from "../../../pinia/layout/menus.ts";
import {useRoute, useRouter} from "vue-router";
import {findMenuByPath} from "../../../utils/menu/findMenuByPath.ts";

const router = useRouter()
const route = useRoute()

const layoutMenus = useLayoutMenus()

const menu = ref<Menu>();

const changeModule = (val: number = 0) => {
  menu.value = layoutMenus.menus.find((item) => item.id == val)
  if (menu.value && val != 0) {
    if (!(menu.value?.children && menu.value.children.length > 0)) {
      router.push(menu.value.path)
    }
  }
}

onMounted(() => {

  const find = findMenuByPath(layoutMenus.menus, route.path) //模块自动选择
  if (find) {
    let path = find.breadcrumb.shift()
    layoutMenus.module = layoutMenus.menus.find((item) => item.path == path)?.id
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