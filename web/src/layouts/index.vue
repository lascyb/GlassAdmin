<script setup lang="ts">
import {defineAsyncComponent, onMounted, ref,} from "vue";
import GaLoading from "./components/loading.vue";
import {useLayoutSetting} from "@/pinia/layout/setting";
import {useLayoutMenus} from "@/pinia/layout/menus.ts";
import {index} from "@/apis";
import {useRoute} from "vue-router";
import {router} from "@/router";

defineOptions({
  components: {
    Default: defineAsyncComponent(() => import("./container/default.vue")),
    Double: defineAsyncComponent(() => import("./container/double.vue"))
  }
})
const setting = useLayoutSetting()
const layoutMenus = useLayoutMenus()
const loading = ref<boolean>(true);
onMounted(() => {
  index().then((res: any) => {
    // 等待路由添加完成
    if (res.menus) {
      layoutMenus.init(res.menus)
      router.replace(useRoute()); // 使用 replace 避免在历史记录中添加新条目，让 Vue Router 使用新添加的路由重新匹配
    }
    loading.value = false
  })
})

</script>

<template>
  <Transition name="page-fade" mode="out-in" :duration="100">
    <ga-loading v-if="loading" key="loading"></ga-loading>
    <component v-else key="container" :is="setting.layout.container"></component>
  </Transition>
</template>

<style scoped>
/* 页面切换效果 */
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.1s ease, transform 0.1s ease;
}

.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}

.page-fade-enter-to,
.page-fade-leave-from {
  opacity: 1;
}
</style>