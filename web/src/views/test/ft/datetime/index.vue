<script setup lang="ts">
import * as entityApi from '@/apis/test/ft/datetime'
import EditView from '@/views/test/ft/datetime/edit.vue'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import type { DatetimeRow } from '@/apis/test/ft/datetime'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive } from 'vue'
import { formatUnixTime } from '@/utils/formatUnixTime'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'

const advanced = reactive({
    publish_time: null as [number, number] | null,
})

const queryParams = computed(() => ({
    publish_time_start: advanced.publish_time?.[0] || undefined,
    publish_time_end: advanced.publish_time?.[1] || undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<DatetimeRow>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: number }>({})

const clickEdit = (id: number) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: DatetimeRow) => {
    void confirm(`确定删除「${row.id}」？`, () => entityApi.delete(row.id))
}
const onSaved = () => {
    editController.show = false
    void load()
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.publish_time = null
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
                    <el-form-item label="发布时间">
                        <el-date-picker
                            v-model="advanced.publish_time"
                            type="datetimerange"
                            value-format="X"
                            start-placeholder="开始"
                            end-placeholder="结束"
                            class="!w-72"
                        />
                    </el-form-item>

                </template>

                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

        <el-table-column prop="id" label="ID" width="80" align="center" />

        <el-table-column label="发布时间" width="170">
            <template #default="{ row }">{{ formatUnixTime(row.publish_time) }}</template>
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
