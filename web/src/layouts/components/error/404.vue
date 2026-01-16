<script setup lang="ts">

import {useRoute} from "vue-router";

const route = useRoute()
</script>

<template>
<div>404{{ route.meta?.error}}</div>
</template>

<style scoped>

</style>