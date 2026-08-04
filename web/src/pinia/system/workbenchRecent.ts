import type { Menu } from '@/layouts/components/menus/types'
import type { WorkbenchRecent } from '@/views/system/workbench/types'
import { defineStore } from 'pinia'
import { ref } from 'vue'

const MAX_ITEMS = 10

/**
 * useWorkbenchRecent 最近访问（localStorage 持久化，按 adminId 隔离）
 */
export const useWorkbenchRecent = defineStore(
    'workbench_recent',
    () => {
        const adminId = ref<number | null>(null)
        const items = ref<WorkbenchRecent[]>([])

        /** ensureAdmin 切换账号时清空 */
        const ensureAdmin = (id: number) => {
            if (adminId.value === id) {
                return
            }
            adminId.value = id
            items.value = []
        }

        /**
         * touch 记录一次访问（同 path 置顶去重）
         */
        const touch = (currentAdminId: number, menu: Menu) => {
            ensureAdmin(currentAdminId)
            const path = menu.path_full || menu.path
            if (!path) {
                return
            }
            const next: WorkbenchRecent = {
                id: menu.id,
                name: menu.name,
                path_full: path,
                icon: menu.icon ?? null,
                type: menu.type,
                visited_at: Math.floor(Date.now() / 1000),
            }
            const rest = items.value.filter((x) => x.path_full !== path)
            items.value = [next, ...rest].slice(0, MAX_ITEMS)
        }

        const clear = () => {
            items.value = []
        }

        return {
            adminId,
            items,
            ensureAdmin,
            touch,
            clear,
        }
    },
    {
        persist: {
            pick: ['adminId', 'items'],
        },
    },
)
