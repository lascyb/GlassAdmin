<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

/** src 内嵌目标地址（menusToRoutes 写入 meta.url） */
const src = computed(() => String(route.meta.url ?? '').trim())
</script>

<template>
    <div class="iframe-wrap">
        <iframe v-if="src" class="iframe-frame" :src="src" frameborder="0" />
        <el-empty v-else description="未配置内嵌地址" />
    </div>
</template>

<style scoped>
.iframe-wrap {
    box-sizing: border-box;
    width: 100%;
    height: calc(100vh - 120px);
    min-height: 320px;
    padding: 0;
    overflow: hidden;
    background: var(--el-bg-color);
}

.iframe-frame {
    display: block;
    width: 100%;
    height: 100%;
    border: 0;
}
</style>
