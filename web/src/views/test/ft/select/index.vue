<script setup lang="ts">
import * as entityApi from '@/apis/test/ft/select'
import EditView from '@/views/test/ft/select/edit.vue'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaStatusTag from '@/components/ga-status-tag/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import type { SelectRow } from '@/apis/test/ft/select'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { LEVEL_MAP } from '@/views/test/ft/select/constants'

const advanced = reactive({
    level: undefined as number | undefined,
})

const queryParams = computed(() => ({
    level: [1, 2, 3].includes(Number(advanced.level)) ? advanced.level : undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<SelectRow>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: number }>({})

const clickEdit = (id: number) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: SelectRow) => {
    void confirm(`确定删除「${row.id}」？`, () => entityApi.delete(row.id))
}
const onSaved = () => {
    editController.show = false
    void load()
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.level = undefined
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
                    <el-form-item label="等级">
                        <el-select v-model="advanced.level" clearable placeholder="全部" class="!w-28">
                            <el-option
                                v-for="o in LEVEL_MAP"
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

        <el-table-column label="等级" width="88">
            <template #default="{ row }">
                <ga-status-tag :value="row.level" :options="LEVEL_MAP" />
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
