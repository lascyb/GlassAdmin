/** WorkbenchShortcut 快捷入口项 */
export type WorkbenchShortcut = {
    id: number
    name: string
    path_full: string
    icon?: string | null
    /** view | link | iframe 等 */
    type?: string
}

/** WorkbenchRecent 最近访问项（本地持久化） */
export type WorkbenchRecent = {
    id: number
    name: string
    path_full: string
    icon?: string | null
    type?: string
    /** 访问时间 Unix 秒 */
    visited_at: number
}

/** WorkbenchTodo 待办/通知项（预留） */
export type WorkbenchTodo = {
    id: string
    title: string
    summary?: string
    level?: 'pending' | 'info' | 'warning'
    created_at?: string
}

/**
 * formatRelativeTime 将 Unix 秒格式化为相对时间文案
 */
export function formatRelativeTime(unixSec: number, nowSec = Math.floor(Date.now() / 1000)): string {
    const diff = Math.max(0, nowSec - unixSec)
    if (diff < 60) {
        return '刚刚'
    }
    if (diff < 3600) {
        return `${Math.floor(diff / 60)} 分钟前`
    }
    if (diff < 86400) {
        return `${Math.floor(diff / 3600)} 小时前`
    }
    const d = new Date(unixSec * 1000)
    const pad = (n: number) => String(n).padStart(2, '0')
    if (diff < 86400 * 2) {
        return `昨天 ${pad(d.getHours())}:${pad(d.getMinutes())}`
    }
    return `${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}
