<script setup lang="ts">
import * as accountApi from '@/apis/account'
import type { OperationLogRow } from '@/apis/account'
import GaPage from '@/components/ga-page/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'

const { list, total, page, limit, loading, load } = usePaginatedList<OperationLogRow>(
    (params) => accountApi.fetchOperationLogs(params),
    { showError: false },
)
</script>

<template>
    <ga-page title="操作日志" padding="md">
        <ga-table-page
            v-model:page="page"
            v-model:limit="limit"
            bare
            :loading="loading"
            :list="list"
            :total="total"
            :border="false"
            min-height=""
            :page-sizes="[20]"
            @load="load"
        >
            <el-table-column prop="create_time" label="时间" width="180" />
            <el-table-column prop="action" label="动作" width="140" />
            <el-table-column prop="summary" label="说明" min-width="160" />
            <el-table-column prop="ip" label="IP" width="140" />
        </ga-table-page>
    </ga-page>
</template>
