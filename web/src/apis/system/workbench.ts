import { getData, postData } from '@/apis/http'

/** WorkbenchShortcutItem 快捷入口列表项 */
export type WorkbenchShortcutItem = {
    id: number
    name: string
    path: string
    icon?: string | null
    type: string
    sort: number
}

/** WorkbenchShortcutListRes list 响应 */
export type WorkbenchShortcutListRes = {
    list: WorkbenchShortcutItem[]
    source: 'custom' | 'default'
}

/**
 * fetchShortcuts 当前管理员快捷入口（未设置时后端返回默认前 8）
 */
export const fetchShortcuts = async () => {
    return getData<WorkbenchShortcutListRes>('system.workbench.Shortcut/list')
}

/**
 * saveShortcuts 全量保存；空数组恢复默认
 */
export const saveShortcuts = async (ruleIds: number[]) => {
    return postData<true>('system.workbench.Shortcut/save', { rule_ids: ruleIds })
}
