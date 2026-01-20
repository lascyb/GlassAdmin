<script setup lang="ts">
import {useRoute} from "vue-router";

const route = useRoute()
</script>

<template>
  <div>
    {{ route.path }}
  </div>
</template>

<style scoped>

</style>