import * as Raw from '@element-plus/icons-vue'
import type { Component } from 'vue'

/** elIconMap 组件名 → 图标组件，与存储串前缀 el:Name 对应 */
export const elIconMap: Record<string, Component> = {}
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
    elIconMap[key] = v as Component
}

/**
 * listElIconNames 返回全部 Element Plus 图标组件名（排序）
 */
export function listElIconNames(): string[] {
    return Object.keys(elIconMap).sort((a, b) => a.localeCompare(b))
}

/**
 * getElIcon 按组件名取图标组件
 */
export function getElIcon(name: string): Component | undefined {
    return elIconMap[name]
}
