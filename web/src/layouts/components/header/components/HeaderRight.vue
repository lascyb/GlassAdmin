<script setup lang="ts">
import { useAccountNotifications } from '@/pinia/account/notifications'
import { useAdmin } from '@/pinia/admin'
import { useMainViewRefresh } from '@/pinia/layout/mainViewRefresh'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { Refresh, Tools } from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const setting = useLayoutSetting()
const admin = useAdmin()
const route = useRoute()
const router = useRouter()
const mainRefresh = useMainViewRefresh()
const notifyStore = useAccountNotifications()
const activeName = ref<'system' | 'friend'>('system')
const popoverVisible = ref(false)

const headerAvatarSrc = computed(() => resolveStorageUrl(admin.data?.avatar))

const refreshPage = () => {
    mainRefresh.bumpForPath(route.fullPath)
}

watch(activeName, () => {
    if (popoverVisible.value) {
        void notifyStore.fetchHeaderList(activeName.value)
    }
})

const onPopoverShow = () => {
    void notifyStore.fetchHeaderList(activeName.value)
}

onMounted(() => {
    void notifyStore.refreshUnreadBadge()
})

const goNotificationsPage = async () => {
    popoverVisible.value = false
    await router.push('/account/notifications')
}

/**
 * logout 退出登录：清空 token 与用户信息并跳转登录页
 */
const logout = async () => {
    try {
        await ElMessageBox.confirm('确定退出当前账号？', '退出登录', { type: 'warning' })
    } catch {
        return
    }
    admin.clear()
    await router.replace({ name: 'login' })
}
</script>

<template>
    <div class="navs">
        <el-tooltip content="刷新当前页" placement="bottom">
            <el-icon size="20" @click="refreshPage">
                <Refresh />
            </el-icon>
        </el-tooltip>
        <div class="flex items-center" style="padding-top: 6px">
            <el-popover
                v-model:visible="popoverVisible"
                :title="admin.data?.nickname || admin.data?.username || '账号'"
                placement="bottom-end"
                width="320"
                trigger="click"
                @show="onPopoverShow"
            >
                <template #reference>
                    <span class="inline-flex cursor-pointer items-center">
                        <el-badge :value="notifyStore.unreadTotal" :hidden="notifyStore.unreadTotal <= 0" :max="99">
                            <el-avatar class="mr-1 mb-0" :src="headerAvatarSrc || undefined" />
                        </el-badge>
                    </span>
                </template>
                <template #default>
                    <el-tabs v-model="activeName">
                        <el-tab-pane label="系统消息" name="system">
                            <el-scrollbar max-height="220px">
                                <div v-if="notifyStore.loadingBrief" class="py-4 text-center text-xs text-gray-400">
                                    加载中…
                                </div>
                                <div
                                    v-else-if="!notifyStore.briefSystem.length"
                                    class="py-4 text-center text-xs text-gray-400"
                                >
                                    暂无系统消息
                                </div>
                                <ul v-else class="m-0 list-none space-y-2 p-0">
                                    <li
                                        v-for="n in notifyStore.briefSystem"
                                        :key="n.id"
                                        class="rounded border border-gray-100 px-2 py-1.5 text-xs"
                                    >
                                        <div class="font-medium text-gray-800">{{ n.title }}</div>
                                        <div class="mt-0.5 line-clamp-2 text-gray-500">{{ n.content }}</div>
                                        <div class="mt-1 text-[11px] text-gray-400">{{ n.create_time }}</div>
                                    </li>
                                </ul>
                            </el-scrollbar>
                        </el-tab-pane>
                        <el-tab-pane label="好友消息" name="friend">
                            <el-scrollbar max-height="220px">
                                <div v-if="notifyStore.loadingBrief" class="py-4 text-center text-xs text-gray-400">
                                    加载中…
                                </div>
                                <div
                                    v-else-if="!notifyStore.briefFriend.length"
                                    class="py-4 text-center text-xs text-gray-400"
                                >
                                    暂无好友消息
                                </div>
                                <ul v-else class="m-0 list-none space-y-2 p-0">
                                    <li
                                        v-for="n in notifyStore.briefFriend"
                                        :key="n.id"
                                        class="rounded border border-gray-100 px-2 py-1.5 text-xs"
                                    >
                                        <div class="font-medium text-gray-800">{{ n.title }}</div>
                                        <div class="mt-0.5 line-clamp-2 text-gray-500">{{ n.content }}</div>
                                        <div class="mt-1 text-[11px] text-gray-400">{{ n.create_time }}</div>
                                    </li>
                                </ul>
                            </el-scrollbar>
                        </el-tab-pane>
                    </el-tabs>
                    <div class="mt-2 flex justify-between gap-2">
                        <el-button type="primary" link size="small" @click="goNotificationsPage">查看全部</el-button>
                        <el-button type="danger" link size="small" @click="logout">退出登录</el-button>
                    </div>
                </template>
            </el-popover>
        </div>
        <el-icon size="20" @click="setting.showSetting()">
            <Tools />
        </el-icon>
    </div>
</template>

<style scoped>
.navs {
    display: flex;
    align-items: center;

    > * {
        padding: 0 5px;
        height: 100%;
        min-width: 40px;
        cursor: pointer;

        &:hover {
            background: #f0f2f5;
        }
    }
}
</style>
