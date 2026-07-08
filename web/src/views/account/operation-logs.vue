<script setup lang="ts">
import type { OperationLogRow } from '@/apis/account'
import * as accountApi from '@/apis/account'
import GaTablePage from '@/components/ga-table-page/index.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'

const { list, total, page, limit, loading, load } = usePaginatedList<OperationLogRow>(
    (params) => accountApi.fetchOperationLogs(params),
    { showError: false },
)
</script>

<template>
    <ga-table-page
        v-model:page="page"
        v-model:limit="limit"
        :loading="loading"
        :list="list"
        :total="total"
        :border="false"
        :page-sizes="[20]"
        @load="load"
    >
        <template #header>操作日志</template>
        <el-table-column prop="create_time" label="时间" width="180" />
        <el-table-column prop="action" label="动作" min-width="140" />
        <el-table-column prop="summary" label="说明" min-width="160" />
        <el-table-column prop="ip" label="IP" width="140" />
    </ga-table-page>
</template>
