/**
 * iframe 鉴权策略注册表
 * 业务方在 embed/auth/*.ts 导出 IframeAuthProvider（default 或 provider）
 */

/** IframeAuthProvider iframe 鉴权提供方 */
export type IframeAuthProvider = {
    /** 策略 ID，写入 auth_rule.method */
    id: string
    /** 菜单编辑展示名 */
    label: string
    /** 内嵌地址输入框占位 */
    targetPlaceholder: string
    /** 将菜单 component（target）解析为最终 iframe src */
    resolveSrc: (target: string) => Promise<string>
    /**
     * acceptMessage 是否由当前策略处理该 message（如壳页请求重签票）
     * 返回 true 表示已消费
     */
    acceptMessage?: (event: MessageEvent) => boolean
}

type AuthModule = {
    default?: IframeAuthProvider
    provider?: IframeAuthProvider
}

const modules = import.meta.glob<AuthModule>('./auth/*.ts', { eager: true })

const providersById = new Map<string, IframeAuthProvider>()

for (const mod of Object.values(modules)) {
    const p = mod.default ?? mod.provider
    if (!p || typeof p.id !== 'string' || p.id.trim() === '') {
        continue
    }
    const id = p.id.trim()
    if (providersById.has(id)) {
        console.warn(`[iframeAuth] duplicate provider id: ${id}`)
        continue
    }
    providersById.set(id, { ...p, id })
}

/**
 * listIframeAuthProviders 已注册策略（按 id 排序）
 */
export const listIframeAuthProviders = (): IframeAuthProvider[] =>
    [...providersById.values()].sort((a, b) => a.id.localeCompare(b.id))

/**
 * getIframeAuthProvider 按策略 ID 取提供方；空 ID 表示直链
 */
export const getIframeAuthProvider = (method: string): IframeAuthProvider | undefined => {
    const id = method.trim()
    if (id === '') {
        return undefined
    }
    return providersById.get(id)
}
