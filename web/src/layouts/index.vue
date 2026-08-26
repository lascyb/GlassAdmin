<script setup lang="ts">
import { index } from "@/apis";
import { useLayoutMenus } from "@/pinia/layout/menus.ts";
import { useLayoutSetting } from "@/pinia/layout/setting";
import { useSystemConfig } from "@/pinia/system/config";
import { router } from "@/router";
import { applyDocumentTitle } from "@/router/documentTitle";
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
  void (async () => {
    const systemConfig = useSystemConfig()
    await systemConfig.load()
    setting.applySystemDefaults()
    try {
      const res = await index(setting.layout.showAllMenus)
      const menus = res.menus ?? []
      layoutMenus.init(menus)
      // 登录后默认落在工作台首页；无侧栏菜单时也不再跳个人中心
      const route = router.currentRoute.value
      if (menus.length === 0 || route.path === '/' || route.name === 'root-default') {
        await router.replace({ name: 'root-default' })
      } else {
        try {
          await router.replace(route.fullPath)
        } catch {
          // 重复导航时仍需用最新 site_title + 菜单 meta 刷标题
        }
      }
      applyDocumentTitle(router.currentRoute.value)
    } catch (e: unknown) {
      ElMessage.error(e instanceof Error ? e.message : '菜单加载失败')
      applyDocumentTitle(router.currentRoute.value)
    } finally {
      loading.value = false
    }
  })()
})

</script>

<template>
  <Transition name="page-fade" mode="out-in">
    <ga-loading v-if="loading" key="loading"></ga-loading>
    <component v-else key="container" :is="setting.layout.container"></component>
  </Transition>
</template>
