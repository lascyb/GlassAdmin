<script setup lang="ts">
import { Link } from '@element-plus/icons-vue'
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

/** url 外链目标地址（menusToRoutes 写入 meta.url） */
const url = computed(() => String(route.meta.url ?? '').trim())
const title = computed(() => String(route.meta.name ?? '外链'))

onMounted(() => {
    if (url.value) {
        window.open(url.value, '_blank', 'noopener,noreferrer')
    }
})
</script>

<template>
    <div class="link-wrap">
        <el-result v-if="url" icon="success" :title="title" sub-title="已尝试在新窗口打开外链">
            <template #icon>
                <el-icon :size="48" class="text-[var(--el-color-primary)]"><Link /></el-icon>
            </template>
            <template #extra>
                <el-button type="primary" tag="a" :href="url" target="_blank" rel="noopener noreferrer">
                    再次打开
                </el-button>
                <div class="mt-3 max-w-xl break-all text-left text-xs text-gray-400">{{ url }}</div>
            </template>
        </el-result>
        <el-empty v-else description="未配置外链地址" />
    </div>
</template>

<style scoped>
.link-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 160px);
    padding: 24px;
}
</style>
