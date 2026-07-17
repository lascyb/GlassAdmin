<script setup lang="ts">
import * as entityApi from '@/apis/test/ft/uuid'
import EditView from '@/views/test/ft/uuid/edit.vue'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import type { UuidRow } from '@/apis/test/ft/uuid'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'

const keyword = ref('')

const queryParams = computed(() => ({
    keyword: keyword.value.trim() || undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<UuidRow>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: number }>({})

const clickEdit = (id: number) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: UuidRow) => {
    void confirm(`确定删除「${row.id}」？`, () => entityApi.delete(row.id))
}
const onSaved = () => {
    editController.show = false
    void load()
}
</script>

<template>
    <ga-table-page
        v-model:page="page"
        v-model:limit="limit"
        :loading="loading"
        :list="list"
        :total="total"
        @load="load"
    >
        <template #toolbar>
            <ga-search-bar
                v-model:keyword="keyword"
                :loading="loading"
                keyword-placeholder="UUID"
                @refresh="load"
                @search="search"
            >
                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

        <el-table-column prop="id" label="ID" width="80" align="center" />

        <el-table-column prop="uuid" label="UUID" min-width="280" show-overflow-tooltip />


        <el-table-column label="操作" fixed="right" width="140">
            <template #default="{ row }">
                <el-button v-auth="'edit'" title="编辑" plain type="primary" :icon="Edit" @click="clickEdit(row.id)" />
                <el-button v-auth="'del'" title="删除" plain type="danger" :icon="Delete" @click="clickDel(row)" />
            </template>
        </el-table-column>

        <template #footer>
            <edit-view
                v-if="editController.show"
                v-model="editController.show"
                :id="editController.id"
                @saved="onSaved"
            />
        </template>
    </ga-table-page>
</template>
