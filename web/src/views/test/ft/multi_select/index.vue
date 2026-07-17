<script setup lang="ts">
import * as entityApi from '@/apis/test/ft/multi_select'
import EditView from '@/views/test/ft/multi_select/edit.vue'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import type { MultiSelectRow } from '@/apis/test/ft/multi_select'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive } from 'vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { EXTRA_OPTIONS } from '@/views/test/ft/multi_select/constants'

const advanced = reactive({
    extra: '',
})

const queryParams = computed(() => ({
    extra: advanced.extra.trim() || undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<MultiSelectRow>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: number }>({})

const clickEdit = (id: number) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: MultiSelectRow) => {
    void confirm(`确定删除「${row.id}」？`, () => entityApi.delete(row.id))
}
const onSaved = () => {
    editController.show = false
    void load()
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.extra = ''
}

const extraLabel = (value: string) => EXTRA_OPTIONS.find((o) => o.value === value)?.label ?? value
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
                :show-keyword="false"
                :loading="loading"
                keyword-placeholder="搜索"
                @refresh="load"
                @search="search"
                @reset="resetAdvanced"
            >
                <template #advanced>
                    <el-form-item label="附加项">
                        <el-input v-model.trim="advanced.extra" clearable placeholder="模糊匹配" class="!w-40" />
                    </el-form-item>

                </template>

                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

        <el-table-column prop="id" label="ID" width="80" align="center" />

        <el-table-column label="附加项" min-width="160">
            <template #default="{ row }">
                <template v-if="row.extra?.length">
                    <el-tag v-for="v in row.extra" :key="v" round class="mr-1 mb-1">{{ extraLabel(v) }}</el-tag>
                </template>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>


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
