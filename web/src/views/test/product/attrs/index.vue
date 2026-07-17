<script setup lang="ts">
import * as entityApi from '@/apis/test/product/attrs'
import EditView from '@/views/test/product/attrs/edit.vue'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import type { AttrsRow } from '@/apis/test/product/attrs'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'

const keyword = ref('')

const advanced = reactive({
    key: '',
    value: '',
})

const queryParams = computed(() => ({
    keyword: keyword.value.trim() || undefined,
    key: advanced.key.trim() || undefined,
    value: advanced.value.trim() || undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<AttrsRow>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: string }>({})

const clickEdit = (id: string) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: AttrsRow) => {
    void confirm(`确定删除「${row.key}」？`, () => entityApi.delete(row.key))
}
const onSaved = () => {
    editController.show = false
    void load()
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.key = ''
    advanced.value = ''
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
                keyword-placeholder="Key / Value"
                @refresh="load"
                @search="search"
                @reset="resetAdvanced"
            >
                <template #advanced>
                    <el-form-item label="Key">
                        <el-input v-model.trim="advanced.key" clearable placeholder="模糊匹配" class="!w-40" />
                    </el-form-item>

                    <el-form-item label="Value">
                        <el-input v-model.trim="advanced.value" clearable placeholder="模糊匹配" class="!w-40" />
                    </el-form-item>

                </template>

                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

        <el-table-column prop="key" label="Key" min-width="120" show-overflow-tooltip />

        <el-table-column prop="value" label="Value" min-width="120" show-overflow-tooltip />


        <el-table-column label="操作" fixed="right" width="140">
            <template #default="{ row }">
                <el-button v-auth="'edit'" title="编辑" plain type="primary" :icon="Edit" @click="clickEdit(row.key)" />
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
