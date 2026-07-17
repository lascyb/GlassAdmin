<script setup lang="ts">
import * as entityApi from '@/apis/test/product/products'
import EditView from '@/views/test/product/products/edit.vue'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaSelectRemote from '@/components/ga-select-remote/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import type { ProductsRow } from '@/apis/test/product/products'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'

const keyword = ref('')

const advanced = reactive({
    id_min: undefined as number | undefined,
    id_max: undefined as number | undefined,
    name: '',
    category_id: undefined as number | undefined,
})

const queryParams = computed(() => ({
    keyword: keyword.value.trim() || undefined,
    id_min: advanced.id_min,
    id_max: advanced.id_max,
    name: advanced.name.trim() || undefined,
    category_id: advanced.category_id,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<ProductsRow>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: number }>({})

const clickEdit = (id: number) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: ProductsRow) => {
    void confirm(`确定删除「${row.id}」？`, () => entityApi.delete(row.id))
}
const onSaved = () => {
    editController.show = false
    void load()
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.id_min = undefined
    advanced.id_max = undefined
    advanced.name = ''
    advanced.category_id = undefined
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
                keyword-placeholder="ID / 标题"
                @refresh="load"
                @search="search"
                @reset="resetAdvanced"
            >
                <template #advanced>
                    <el-form-item label="ID">
                        <div class="flex items-center gap-1">
                            <el-input-number v-model="advanced.id_min" controls-position="right" class="!w-28" placeholder="最小" />
                            <span class="text-gray-400">~</span>
                            <el-input-number v-model="advanced.id_max" controls-position="right" class="!w-28" placeholder="最大" />
                        </div>
                    </el-form-item>
                    <el-form-item label="标题">
                        <el-input v-model.trim="advanced.name" clearable placeholder="模糊匹配" class="!w-40" />
                    </el-form-item>

                    <el-form-item label="类型">
                        <ga-select-remote
                            v-model="advanced.category_id"
                            :remote-url="'test.product.Category/list'"
                            :props="{ value: 'id', label: 'name' }"
                            clearable
                            placeholder="搜索类型"
                            class="!w-40"
                        />
                    </el-form-item>

                </template>

                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

        <el-table-column prop="id" label="ID" width="72" />

        <el-table-column prop="name" label="标题" min-width="120" show-overflow-tooltip />

        <el-table-column label="类型" width="110" min-width="120">
            <template #default="{ row }">
                <el-tag v-if="row.category?.name" round>{{ row.category.name }}</el-tag>
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
