<script setup lang="ts">
import {defineAsyncComponent, onMounted, ref} from "vue";
import GaSetting from "./setting.vue";
import {useLayoutSetting} from "@/pinia/layout/setting";
import {useLayoutMenus} from "@/pinia/layout/menus.ts";
import {index} from "@/apis";

defineOptions({
  components: {
    Default: defineAsyncComponent(() => import("./container/default.vue")),
    Double: defineAsyncComponent(() => import("./container/double.vue"))
  }
})

const setting = useLayoutSetting()
const layoutMenus = useLayoutMenus()
const loading = ref<boolean>(false);
onMounted(async () => {
  loading.value = true;
  await index().then((res: any) => {
    layoutMenus.init(res.menus)
  })
  loading.value = false

})

</script>

<template>
  <div>
    <component v-loading="loading" element-loading-text="页面加载中..." :is="setting.layout.container"></component>
    <ga-setting></ga-setting>
  </div>
</template>

<style scoped>

</style>