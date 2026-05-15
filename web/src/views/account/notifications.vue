<script setup lang="ts">
import * as accountApi from '@/apis/account'
import type { NotificationRow } from '@/apis/account'
import { useAccountNotifications } from '@/pinia/account/notifications'
import { ElMessage } from 'element-plus'
import { onMounted, ref } from 'vue'

const notifyStore = useAccountNotifications()

const loading = ref(false)
const list = ref<NotificationRow[]>([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const category = ref<'system' | 'friend'>('system')

const load = async () => {
    loading.value = true
    try {
        const res = await accountApi.fetchNotifications({
            page: page.value,
            limit: limit.value,
            category: category.value,
        })
        list.value = res.list
        total.value = res.total
    } finally {
        loading.value = false
    }
}

onMounted(load)

const refreshAll = async () => {
    await load()
    await notifyStore.refreshUnreadBadge()
}

const onCategoryChange = () => {
    page.value = 1
    void load()
}

const onPageChange = (p: number) => {
    page.value = p
    void load()
}

const markRead = async (row: NotificationRow) => {
    if (row.read_at) return
    await accountApi.markNotificationsRead({ ids: [row.id] })
    ElMessage.success('已标记已读')
    await refreshAll()
}

const markAll = async () => {
    await accountApi.markNotificationsRead({ all: true })
    ElMessage.success('已全部标记已读')
    await refreshAll()
}
</script>

<template>
    <div class="p-4">
        <el-card shadow="never">
            <template #header>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>消息通知</span>
                    <el-button size="small" type="primary" link @click="markAll">全部已读</el-button>
                </div>
            </template>
            <el-radio-group v-model="category" class="mb-3" @change="onCategoryChange">
                <el-radio-button label="system">系统消息</el-radio-button>
                <el-radio-button label="friend">好友消息</el-radio-button>
            </el-radio-group>
            <el-table v-loading="loading" :data="list" stripe empty-text="暂无消息">
                <el-table-column prop="create_time" label="时间" width="170" />
                <el-table-column prop="title" label="标题" min-width="140" />
                <el-table-column prop="content" label="内容" min-width="200" show-overflow-tooltip />
                <el-table-column label="状态" width="90">
                    <template #default="{ row }">
                        <el-tag :type="row.read_at ? 'info' : 'warning'" size="small">
                            {{ row.read_at ? '已读' : '未读' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-if="!row.read_at"
                            type="primary"
                            link
                            size="small"
                            @click="markRead(row)"
                        >
                            已读
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="mt-4 flex justify-end">
                <el-pagination
                    :current-page="page"
                    :page-size="limit"
                    :total="total"
                    layout="total, prev, pager, next"
                    @current-change="onPageChange"
                />
            </div>
        </el-card>
    </div>
</template>
