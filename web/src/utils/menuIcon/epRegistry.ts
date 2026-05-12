import * as Raw from '@element-plus/icons-vue'
import type { Component } from 'vue'

/** epIconMap 组件名 → 图标组件，与前缀 ep:Name 对应 */
export const epIconMap: Record<string, Component> = {}
for (const key of Object.keys(Raw)) {
    if (!/^[A-Z]/.test(key)) {
        continue
    }
    const v = (Raw as Record<string, unknown>)[key]
    if (v === null || v === undefined) {
        continue
    }
    if (typeof v !== 'object' && typeof v !== 'function') {
        continue
    }
    epIconMap[key] = v as Component
}

/**
 * listEpIconNames 返回全部 Element Plus 图标组件名（排序）
 */
export function listEpIconNames(): string[] {
    return Object.keys(epIconMap).sort((a, b) => a.localeCompare(b))
}

/**
 * getEpIcon 按组件名取图标组件
 */
export function getEpIcon(name: string): Component | undefined {
    return epIconMap[name]
}
