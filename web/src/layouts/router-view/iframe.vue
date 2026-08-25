<script setup lang="ts">
import { getIframeAuthProvider } from '@/embed/iframeAuth'
import { Loading } from '@element-plus/icons-vue'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const loading = ref(false)
const error = ref('')
const src = ref('')
let loadSeq = 0
let lastReauthAt = 0
const REAUTH_COOLDOWN_MS = 15000

/** embedMethod iframe 鉴权策略 ID（menusToRoutes 写入 meta.method；空 = 直链） */
const embedMethod = computed(() => String(route.meta.method ?? '').trim())
/** targetOrUrl component：策略为相对路径，直链为完整 URL */
const targetOrUrl = computed(() => String(route.meta.url ?? '').trim())

/** resolveSrc 按鉴权策略解析 iframe src */
const resolveSrc = async (): Promise<void> => {
    const seq = ++loadSeq
    error.value = ''
    const raw = targetOrUrl.value
    if (!raw) {
        src.value = ''
        error.value = '未配置内嵌地址'
        return
    }

    const method = embedMethod.value
    if (method === '') {
        src.value = raw
        return
    }

    const provider = getIframeAuthProvider(method)
    if (!provider) {
        src.value = ''
        error.value = `未注册 iframe 鉴权策略：${method}`
        return
    }

    loading.value = true
    src.value = ''
    try {
        const next = await provider.resolveSrc(raw)
        if (seq !== loadSeq) return
        src.value = next
    } catch (e: unknown) {
        if (seq !== loadSeq) return
        src.value = ''
        error.value = e instanceof Error ? e.message : '解析内嵌地址失败'
    } finally {
        if (seq === loadSeq) {
            loading.value = false
        }
    }
}

/** onEmbedMessage 交给当前策略处理（如壳页会话失效后重授权） */
const onEmbedMessage = (event: MessageEvent) => {
    const method = embedMethod.value
    if (method === '') return
    const provider = getIframeAuthProvider(method)
    if (!provider?.acceptMessage?.(event)) return
    if (loading.value) return
    const now = Date.now()
    if (now - lastReauthAt < REAUTH_COOLDOWN_MS) return
    lastReauthAt = now
    void resolveSrc()
}

watch(
    () => [route.fullPath, embedMethod.value, targetOrUrl.value] as const,
    () => {
        void resolveSrc()
    },
    { immediate: true },
)

onMounted(() => {
    window.addEventListener('message', onEmbedMessage)
})

onUnmounted(() => {
    window.removeEventListener('message', onEmbedMessage)
    loadSeq += 1
})
</script>

<template>
    <div class="iframe-wrap">
        <div v-if="loading" class="iframe-state">
            <el-icon class="is-loading" :size="28"><Loading /></el-icon>
            <span>正在进入内嵌页面…</span>
        </div>
        <el-empty v-else-if="error" :description="error">
            <el-button type="primary" @click="resolveSrc">重试</el-button>
        </el-empty>
        <iframe
            v-else-if="src"
            :key="src"
            class="iframe-frame"
            :src="src"
            frameborder="0"
            allow="fullscreen"
        />
        <el-empty v-else description="未配置内嵌地址" />
    </div>
</template>

<style scoped>
/* 空白壳：无外边距，高度与 main.vue 滚动视口一致 */
.iframe-wrap {
    box-sizing: border-box;
    width: 100%;
    height: var(--ga-main-view-height);
    min-height: 320px;
    margin: 0;
    padding: 0;
    overflow: hidden;
    background: var(--el-fill-color-light);
}

.iframe-frame {
    display: block;
    width: 100%;
    height: 100%;
    border: 0;
}

.iframe-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    height: 100%;
    color: var(--el-text-color-secondary);
    font-size: 14px;
}
</style>
