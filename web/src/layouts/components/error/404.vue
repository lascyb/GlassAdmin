<script setup lang="ts">

import {useRoute} from "vue-router";

const route = useRoute()
</script>

<template>
<div>404 not fount
<pre>
  {{ JSON.stringify(route,undefined, 2) }}
</pre>
</div>
</template>

<style scoped>

</style>