<script setup lang="ts">
import * as entityApi from '@/apis/test/ft/radio'
import EditView from '@/views/test/ft/radio/edit.vue'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaStatusTag from '@/components/ga-status-tag/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import type { RadioRow } from '@/apis/test/ft/radio'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { STATUS_MAP } from '@/views/test/ft/radio/constants'

const advanced = reactive({
    status: undefined as number | undefined,
})

const queryParams = computed(() => ({
    status: [1, 0].includes(Number(advanced.status)) ? advanced.status : undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<RadioRow>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: number }>({})

const clickEdit = (id: number) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: RadioRow) => {
    void confirm(`确定删除「${row.id}」？`, () => entityApi.delete(row.id))
}
const onSaved = () => {
    editController.show = false
    void load()
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.status = undefined
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
                    <el-form-item label="状态">
                        <el-select v-model="advanced.status" clearable placeholder="全部" class="!w-28">
                            <el-option
                                v-for="o in STATUS_MAP"
                                :key="o.value"
                                :value="Number(o.value)"
                                :label="o.label"
                            />
                        </el-select>
                    </el-form-item>

                </template>

                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

        <el-table-column prop="id" label="ID" width="80" align="center" />

        <el-table-column label="状态" width="88">
            <template #default="{ row }">
                <ga-status-tag :value="row.status" :options="STATUS_MAP" />
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
