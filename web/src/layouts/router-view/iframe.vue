<script setup lang="ts">
import type { IframeResolveCtx } from '@/embed/iframeAuth'
import { lookupIframeAuthProvider } from '@/embed/iframeAuth'
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh'
import { resolveKeepAliveBaseKey } from '@/utils/pageKey'
import { Loading } from '@element-plus/icons-vue'
import { computed, onActivated, onDeactivated, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

/** 跨实例合并同一 target 的并发 resolve */
const inflight = new Map<string, Promise<void>>()

const route = useRoute()
const mainRefresh = useMainViewRefresh()

const loading = ref(false)
const error = ref('')
const iframeSrc = ref('')
const iframeEl = ref<HTMLIFrameElement | null>(null)
let loadSeq = 0
let lastReauthAt = 0
let activatedOnce = false
const REAUTH_COOLDOWN_MS = 15000

/** embedMethod iframe 鉴权策略 ID（menusToRoutes 写入 meta.method；空 = 直链或 matchTarget） */
const embedMethod = computed(() => String(route.meta.method ?? '').trim())
/** targetOrUrl component：策略为相对路径，直链为完整 URL */
const targetOrUrl = computed(() => String(route.meta.url ?? '').trim())
const keepAliveBaseKey = computed(() => resolveKeepAliveBaseKey(route))

/** currentProvider 当前路由对应的鉴权策略 */
const currentProvider = computed(() =>
    lookupIframeAuthProvider(embedMethod.value, targetOrUrl.value),
)

/** buildResolveCtx 构造策略上下文 */
const buildResolveCtx = (force?: boolean): IframeResolveCtx => ({
    force,
    routePath: route.fullPath,
    cacheKey: keepAliveBaseKey.value,
    refreshNonce: mainRefresh.nonceFor(keepAliveBaseKey.value),
    currentSrc: iframeSrc.value,
})

/** persistCurrentSrc 交给策略改写缓存用 src */
const persistCurrentSrc = (): void => {
    const raw = targetOrUrl.value
    const provider = currentProvider.value
    if (!raw || !provider?.persistSrc) return
    iframeSrc.value = provider.persistSrc(raw, iframeSrc.value)
}

/** resolveSrc 直链或交给策略解析 */
const resolveSrc = async (opts?: { force?: boolean }): Promise<void> => {
    const raw = targetOrUrl.value
    if (!raw) {
        iframeSrc.value = ''
        error.value = '未配置内嵌地址'
        return
    }

    const method = embedMethod.value
    const provider = lookupIframeAuthProvider(method, raw)
    if (method !== '' && !provider) {
        iframeSrc.value = ''
        error.value = `未注册 iframe 鉴权策略：${method}`
        return
    }
    if (!provider) {
        iframeSrc.value = raw
        return
    }

    const inflightKey = `${provider.id}|${raw}|${opts?.force ? 'force' : ''}`
    const existing = inflight.get(inflightKey)
    if (existing) {
        return existing
    }

    const run = async (): Promise<void> => {
        const seq = ++loadSeq
        error.value = ''
        loading.value = true
        try {
            const next = await provider.resolveSrc(raw, buildResolveCtx(opts?.force))
            if (seq !== loadSeq) return
            if (next !== iframeSrc.value) {
                iframeSrc.value = next
            }
        } catch (e: unknown) {
            if (seq !== loadSeq) return
            iframeSrc.value = ''
            error.value = e instanceof Error ? e.message : '解析内嵌地址失败'
        } finally {
            if (seq === loadSeq) {
                loading.value = false
            }
        }
    }

    const p = run().finally(() => {
        if (inflight.get(inflightKey) === p) {
            inflight.delete(inflightKey)
        }
    })
    inflight.set(inflightKey, p)
    return p
}

/** applyTarget 优先壳内 navigate，否则全量 resolveSrc */
const applyTarget = async (nextTarget: string): Promise<void> => {
    const raw = nextTarget.trim()
    if (!raw) {
        iframeSrc.value = ''
        error.value = '未配置内嵌地址'
        return
    }

    const seq = ++loadSeq
    const method = embedMethod.value
    const provider = lookupIframeAuthProvider(method, raw)

    if (iframeSrc.value && iframeEl.value && provider?.canNavigateInShell?.({
        iframe: iframeEl.value,
        target: raw,
    })) {
        const navigated = provider.navigateInShell?.({
            iframe: iframeEl.value,
            target: raw,
        })
        if (navigated) {
            return
        }
    }

    if (seq !== loadSeq) {
        return
    }

    await resolveSrc()
}

/** onEmbedMessage 来自当前 iframe 的消息交给策略 */
const onEmbedMessage = (event: MessageEvent) => {
    if (iframeEl.value?.contentWindow !== event.source) return
    const provider = currentProvider.value
    if (!provider) return
    const kind = provider.interpretMessage?.(event)
    if (!kind) return

    if (kind === 'ready') {
        loading.value = false
        provider.onReady?.(buildResolveCtx())
        return
    }

    if (loading.value) return
    const now = Date.now()
    if (now - lastReauthAt < REAUTH_COOLDOWN_MS) return
    lastReauthAt = now
    provider.onReauth?.(buildResolveCtx())
    void resolveSrc({ force: true })
}

watch(targetOrUrl, (next) => {
    void applyTarget(next)
})

onMounted(() => {
    void applyTarget(targetOrUrl.value)
})

onActivated(() => {
    window.addEventListener('message', onEmbedMessage)
    const isRestore = activatedOnce
    activatedOnce = true
    if (!isRestore) return

    persistCurrentSrc()
    const ctx = buildResolveCtx()
    if (currentProvider.value?.shouldReload?.(ctx)) {
        void resolveSrc({ force: true })
        return
    }
    void applyTarget(targetOrUrl.value)
})

onDeactivated(() => {
    window.removeEventListener('message', onEmbedMessage)
    persistCurrentSrc()
})

onUnmounted(() => {
    window.removeEventListener('message', onEmbedMessage)
    loadSeq += 1
})
</script>

<template>
    <div class="iframe-wrap">
        <iframe
            v-if="iframeSrc"
            ref="iframeEl"
            :src="iframeSrc"
            class="iframe-frame"
            frameborder="0"
            allow="fullscreen"
        />
        <div v-if="loading" class="iframe-state iframe-overlay">
            <el-icon class="is-loading" :size="28"><Loading /></el-icon>
            <span>正在进入内嵌页面…</span>
        </div>
        <el-empty v-if="error && !iframeSrc" :description="error">
            <el-button type="primary" @click="resolveSrc({ force: true })">重试</el-button>
        </el-empty>
        <el-empty v-else-if="!iframeSrc && !loading" description="未配置内嵌地址" />
    </div>
</template>

<style scoped>
/* 空白壳：无外边距，高度与 main.vue 滚动视口一致 */
.iframe-wrap {
    position: relative;
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

.iframe-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    background: var(--el-fill-color-light);
}
</style>
