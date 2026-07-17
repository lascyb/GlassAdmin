<script setup lang="ts">
import * as entityApi from '@/apis/test/ft/remote_multi_select'
import EditView from '@/views/test/ft/remote_multi_select/edit.vue'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaSelectRemote from '@/components/ga-select-remote/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import type { RemoteMultiSelectRow } from '@/apis/test/ft/remote_multi_select'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'

const advanced = reactive({
    tag_ids: [] as number[],
})

const queryParams = computed(() => ({
    tag_ids: advanced.tag_ids.length > 0 ? advanced.tag_ids : undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<RemoteMultiSelectRow>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: number }>({})

const clickEdit = (id: number) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: RemoteMultiSelectRow) => {
    void confirm(`确定删除「${row.id}」？`, () => entityApi.delete(row.id))
}
const onSaved = () => {
    editController.show = false
    void load()
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.tag_ids = []
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
                :show-keyword="false"
                :loading="loading"
                keyword-placeholder="搜索"
                @refresh="load"
                @search="search"
                @reset="resetAdvanced"
            >
                <template #advanced>
                    <el-form-item label="标签">
                        <ga-select-remote
                            v-model="advanced.tag_ids"
                            :remote-url="'test.ft.Text/list'"
                            :props="{ value: 'id', label: 'name' }"
                            multiple
                            collapse-tags
                            clearable
                            placeholder="搜索标签"
                            class="!w-48"
                        />
                    </el-form-item>

                </template>

                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

        <el-table-column prop="id" label="ID" width="80" align="center" />

        <el-table-column label="标签" min-width="160">
            <template #default="{ row }">
                <template v-if="row.tags?.length">
                    <el-tag v-for="t in row.tags" :key="t.id" round class="mr-1 mb-1">{{ t.name }}</el-tag>
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
