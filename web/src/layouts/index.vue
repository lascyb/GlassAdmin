<script setup lang="ts">
import { index } from "@/apis";
import type { Menu } from "@/layouts/components/menus/types";
import { useLayoutMenus } from "@/pinia/layout/menus.ts";
import { useLayoutSetting } from "@/pinia/layout/setting";
import { useSystemConfig } from "@/pinia/system/config";
import { router } from "@/router";
import { ElMessage } from "element-plus";
import { defineAsyncComponent, onMounted, ref, } from "vue";
import GaLoading from "./components/loading.vue";

defineOptions({
  components: {
    Default: defineAsyncComponent(() => import("./container/default.vue")),
    Double: defineAsyncComponent(() => import("./container/double.vue")),
    Module: defineAsyncComponent(() => import("./container/module.vue")),
  }
})
const setting = useLayoutSetting()
const layoutMenus = useLayoutMenus()
const loading = ref<boolean>(true);
onMounted(() => {
  void useSystemConfig().ensureLoaded()
  index().then((res: { menus?: Menu[] }) => {
    const menus = res.menus ?? []
    layoutMenus.init(menus)
    if (menus.length === 0) {
      void router.replace({ name: 'account-profile' })
    } else {
      // 只用 fullPath 触发重新匹配；传入 useRoute() 整对象会带上 addRoute 前的旧 matched，易导致后续跳转白屏
      void router.replace(router.currentRoute.value.fullPath)
    }
    loading.value = false
  }).catch((e: unknown) => {
    ElMessage.error(e instanceof Error ? e.message : '菜单加载失败')
    loading.value = false
  })
})

</script>

<template>
  <Transition name="page-fade" mode="out-in">
    <ga-loading v-if="loading" key="loading"></ga-loading>
    <component v-else key="container" :is="setting.layout.container"></component>
  </Transition>
</template>