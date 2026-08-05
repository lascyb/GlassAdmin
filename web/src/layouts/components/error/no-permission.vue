<script setup lang="ts">
import { fetchMenuDescribe, type MenuDescribe } from '@/apis'
import GaIcon from '@/components/ga-icon/index.vue'
import { Lock } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const loading = ref(false)
const detail = ref<MenuDescribe | null>(null)

const ruleId = computed(() => Number(route.meta.ruleId ?? 0))
const title = computed(() => String(route.meta.title ?? detail.value?.name ?? '无权限'))

const load = async () => {
    const id = ruleId.value
    if (!id) {
        detail.value = null
        return
    }
    loading.value = true
    try {
        detail.value = await fetchMenuDescribe(id)
    } catch (e: unknown) {
        detail.value = null
        ElMessage.error(e instanceof Error ? e.message : '加载说明失败')
    } finally {
        loading.value = false
    }
}

const onApply = () => {
    ElMessage.info('请联系管理员为您分配该菜单权限')
}

onMounted(() => {
    void load()
})

watch(ruleId, () => {
    void load()
})
</script>

<template>
    <div v-loading="loading" class="no-perm">
        <el-result>
            <template #icon>
                <el-icon :size="56" class="text-[var(--el-color-warning)]"><Lock /></el-icon>
            </template>
            <template #title>
                <span>{{ title }}</span>
            </template>
            <template #sub-title>
                <p class="m-0 text-sm text-gray-500">您当前没有该菜单的访问权限</p>
            </template>
            <template #extra>
                <div class="no-perm-body">
                    <div v-if="detail?.icon || detail?.path" class="mb-3 flex flex-wrap items-center justify-center gap-2 text-sm text-gray-500">
                        <GaIcon v-if="detail?.icon" :icon="detail.icon" class="shrink-0" />
                        <span v-if="detail?.path" class="font-mono text-xs">{{ detail.path }}</span>
                    </div>
                    <div class="desc">
                        <template v-if="detail?.description">
                            {{ detail.description }}
                        </template>
                        <template v-else>
                            暂无功能说明。如需使用，请向管理员申请开通本菜单权限。
                        </template>
                    </div>
                    <el-button type="primary" class="mt-4" @click="onApply">申请权限</el-button>
                </div>
            </template>
        </el-result>
    </div>
</template>

<style scoped>
.no-perm {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 160px);
    padding: 24px;
}

.no-perm-body {
    max-width: 520px;
    text-align: center;
}

.desc {
    margin: 0;
    padding: 12px 16px;
    border-radius: 8px;
    background: var(--el-fill-color-light);
    color: var(--el-text-color-regular);
    font-size: 14px;
    line-height: 1.7;
    text-align: left;
    white-space: pre-wrap;
}
</style>
