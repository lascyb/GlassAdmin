import * as accountApi from '@/apis/account'
import type { NotificationRow } from '@/apis/account'
import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * useAccountNotifications Header 消息区与「消息通知」页共用：未读数、简要列表
 */
export const useAccountNotifications = defineStore('account_notifications', () => {
    const unreadTotal = ref(0)
    const briefSystem = ref<NotificationRow[]>([])
    const briefFriend = ref<NotificationRow[]>([])
    const loadingBrief = ref(false)

    /**
     * refreshUnreadBadge 拉未读总数（不按 category 过滤）
     */
    const refreshUnreadBadge = async () => {
        const res = await accountApi.fetchNotifications({ page: 1, limit: 1 })
        unreadTotal.value = res.unread_count
    }

    /**
     * fetchHeaderList 按分类拉简要列表（Header 弹层）
     */
    const fetchHeaderList = async (category: 'system' | 'friend', limit = 12) => {
        loadingBrief.value = true
        try {
            const res = await accountApi.fetchNotifications({ page: 1, limit, category })
            if (category === 'system') {
                briefSystem.value = res.list
            } else {
                briefFriend.value = res.list
            }
            await refreshUnreadBadge()
        } finally {
            loadingBrief.value = false
        }
    }

    return {
        unreadTotal,
        briefSystem,
        briefFriend,
        loadingBrief,
        refreshUnreadBadge,
        fetchHeaderList,
    }
})
