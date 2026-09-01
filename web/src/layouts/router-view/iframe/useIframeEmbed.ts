import type { Ref } from 'vue'
import type { IframeResolveCtx } from '@/layouts/router-view/iframe/iframeAuth'
import { lookupIframeAuthProvider } from '@/layouts/router-view/iframe/iframeAuth'
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh'
import { resolveKeepAliveBaseKey } from '@/utils/pageKey'
import {
    computed,
    onActivated,
    onDeactivated,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue'
import { useRoute } from 'vue-router'

/** 跨实例合并同一 target 的并发 resolve */
const inflight = new Map<string, Promise<void>>()

const REAUTH_COOLDOWN_MS = 15000

/** useIframeEmbed 内嵌页壳层：解析 src、壳内 navigate、postMessage 分发（平台逻辑在 auth/*.ts） */
export const useIframeEmbed = (iframeEl: Ref<HTMLIFrameElement | null>) => {
    const route = useRoute()
    const mainRefresh = useMainViewRefresh()

    const loading = ref(false)
    const error = ref('')
    const iframeSrc = ref('')
    let loadSeq = 0
    let lastReauthAt = 0
    let activatedOnce = false

    const embedMethod = computed(() => String(route.meta.method ?? '').trim())
    const targetOrUrl = computed(() => String(route.meta.url ?? '').trim())
    const keepAliveBaseKey = computed(() => resolveKeepAliveBaseKey(route))

    const currentProvider = computed(() =>
        lookupIframeAuthProvider(embedMethod.value, targetOrUrl.value),
    )

    const buildResolveCtx = (force?: boolean): IframeResolveCtx => ({
        force,
        routePath: route.fullPath,
        cacheKey: keepAliveBaseKey.value,
        refreshNonce: mainRefresh.nonceFor(keepAliveBaseKey.value),
        currentSrc: iframeSrc.value,
    })

    const persistCurrentSrc = (): void => {
        const raw = targetOrUrl.value
        const provider = currentProvider.value
        if (!raw || !provider?.persistSrc) return
        iframeSrc.value = provider.persistSrc(raw, iframeSrc.value)
    }

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

        if (
            iframeSrc.value &&
            iframeEl.value &&
            provider?.canNavigateInShell?.({
                iframe: iframeEl.value,
                target: raw,
            })
        ) {
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

    return {
        loading,
        error,
        iframeSrc,
        resolveSrc,
    }
}
