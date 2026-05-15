<script setup lang="ts">
import * as accountApi from '@/apis/account'
import type { OperationLogRow } from '@/apis/account'
import { onMounted, ref } from 'vue'

const loading = ref(false)
const list = ref<OperationLogRow[]>([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)

const load = async () => {
    loading.value = true
    try {
        const res = await accountApi.fetchOperationLogs({
            page: page.value,
            limit: limit.value,
        })
        list.value = res.list
        total.value = res.total
    } finally {
        loading.value = false
    }
}

onMounted(load)

const onPageChange = (p: number) => {
    page.value = p
    void load()
}
</script>

<template>
    <div class="p-4">
        <el-card shadow="never">
            <template #header>操作日志</template>
            <el-table v-loading="loading" :data="list" stripe empty-text="暂无记录">
                <el-table-column prop="create_time" label="时间" width="180" />
                <el-table-column prop="action" label="动作" width="140" />
                <el-table-column prop="summary" label="说明" min-width="160" />
                <el-table-column prop="ip" label="IP" width="140" />
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
