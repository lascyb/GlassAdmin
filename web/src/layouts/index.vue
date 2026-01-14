<script setup lang="ts">
import {defineAsyncComponent} from "vue";
import GaSetting from "./setting.vue";
import {useLayoutSetting} from "../pinia/layout/setting.ts";

defineOptions({
  components: {
    Default:defineAsyncComponent(() => import("./container/default.vue")),
    Double: defineAsyncComponent(() => import("./container/double.vue"))
  }
})
const setting = useLayoutSetting()

</script>

<template>
  <div>
    <component :is="setting.layout.container"></component>
    <ga-setting></ga-setting>
  </div>
</template>

<style scoped>

</style>