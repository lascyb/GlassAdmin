<script setup lang="ts">
import type { DataRow } from '@/apis/example/data'
import * as dataApi from '@/apis/example/data'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaStatusTag from '@/components/ga-status-tag/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { formatUnixTime } from '@/utils/formatUnixTime'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { DATA_LEVEL_MAP, DATA_STATUS_MAP, EXTRA_OPTIONS } from '@/views/example/data/constants'
import EditView from '@/views/example/data/edit.vue'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'

const keyword = ref('')

const advanced = reactive({
    code: '',
    category_id: undefined as number | undefined,
    status: undefined as number | undefined,
    level: undefined as number | undefined,
    is_top: undefined as number | undefined,
})

const queryParams = computed(() => ({
    keyword: keyword.value.trim() || undefined,
    code: advanced.code.trim() || undefined,
    category_id: advanced.category_id,
    status: advanced.status === 0 || advanced.status === 1 ? advanced.status : undefined,
    level: [1, 2, 3].includes(Number(advanced.level)) ? advanced.level : undefined,
    is_top: advanced.is_top === 0 || advanced.is_top === 1 ? advanced.is_top : undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<DataRow>(
    (params) => dataApi.fetchList(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{
    show?: boolean
    id?: number
}>({})

const categories = ref<Array<{ id: number; name: string }>>([])

const loadOptions = async () => {
    const res = await dataApi.fetchOptions()
    categories.value = res.categories
}

void loadOptions()

const clickEdit = (id: number) => {
    Object.assign(editController, { show: true, id })
}

const clickAdd = () => {
    Object.assign(editController, { show: true, id: undefined })
}

const clickDel = (row: DataRow) => {
    void confirm(`确定删除「${row.title}」？`, () => dataApi.deleteData(row.id))
}

const onSaved = () => {
    editController.show = false
    void load()
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.code = ''
    advanced.category_id = undefined
    advanced.status = undefined
    advanced.level = undefined
    advanced.is_top = undefined
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
                v-model:keyword="keyword"
                :loading="loading"
                keyword-placeholder="标题 / 编码 / 备注"
                @refresh="load"
                @search="search"
                @reset="resetAdvanced"
            >
                <template #advanced>
                    <el-form-item label="编码">
                        <el-input v-model.trim="advanced.code" clearable placeholder="模糊匹配" class="!w-40" />
                    </el-form-item>
                    <el-form-item label="分类">
                        <el-select v-model="advanced.category_id" clearable placeholder="全部" class="!w-32">
                            <el-option v-for="c in categories" :key="c.id" :value="c.id" :label="c.name" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="状态">
                        <el-select v-model="advanced.status" clearable placeholder="全部" class="!w-28">
                            <el-option
                                v-for="(item, value) in DATA_STATUS_MAP"
                                :key="value"
                                :value="Number(value)"
                                :label="item.label"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="等级">
                        <el-select v-model="advanced.level" clearable placeholder="全部" class="!w-28">
                            <el-option
                                v-for="(item, value) in DATA_LEVEL_MAP"
                                :key="value"
                                :value="Number(value)"
                                :label="item.label"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="置顶">
                        <el-select v-model="advanced.is_top" clearable placeholder="全部" class="!w-28">
                            <el-option :value="1" label="是" />
                            <el-option :value="0" label="否" />
                        </el-select>
                    </el-form-item>
                </template>
                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

        <!-- pk / string -->
        <el-table-column prop="id" label="ID" width="72" />
        <el-table-column prop="title" label="标题" min-width="140" show-overflow-tooltip />
        <el-table-column prop="code" label="编码" min-width="120" show-overflow-tooltip />

        <!-- belongs_to -->
        <el-table-column label="分类" width="110">
            <template #default="{ row }">
                <el-tag v-if="row.category?.name" round>{{ row.category.name }}</el-tag>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>

        <!-- belongs_to_many -->
        <el-table-column label="标签" min-width="160">
            <template #default="{ row }">
                <template v-if="row.tags?.length">
                    <el-tag v-for="t in row.tags" :key="t.id" round class="mr-1 mb-1">{{ t.name }}</el-tag>
                </template>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>

        <!-- decimal / int -->
        <el-table-column prop="amount" label="金额" width="100" />
        <el-table-column prop="quantity" label="数量" width="80" align="center" />

        <!-- bool -->
        <el-table-column label="置顶" width="72" align="center">
            <template #default="{ row }">
                <el-tag :type="row.is_top ? 'warning' : 'info'" round>{{ row.is_top ? '是' : '否' }}</el-tag>
            </template>
        </el-table-column>

        <!-- enum / status -->
        <el-table-column label="状态" width="88">
            <template #default="{ row }">
                <ga-status-tag :value="row.status" :map="DATA_STATUS_MAP" />
            </template>
        </el-table-column>
        <el-table-column label="等级" width="88">
            <template #default="{ row }">
                <ga-status-tag :value="row.level" :map="DATA_LEVEL_MAP" />
            </template>
        </el-table-column>

        <!-- color -->
        <el-table-column label="颜色" width="72" align="center">
            <template #default="{ row }">
                <span
                    v-if="row.color"
                    class="inline-block h-5 w-5 rounded border border-[var(--el-border-color)]"
                    :style="{ background: row.color }"
                />
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>

        <!-- image -->
        <el-table-column label="封面" width="88" align="center">
            <template #default="{ row }">
                <el-image
                    v-if="row.cover"
                    :src="resolveStorageUrl(row.cover)"
                    fit="cover"
                    class="h-10 w-10 rounded"
                    :preview-src-list="[resolveStorageUrl(row.cover)]"
                    preview-teleported
                />
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>

        <!-- timestamp / date -->
        <el-table-column label="发布时间" width="170">
            <template #default="{ row }">
                {{ formatUnixTime(row.publish_time) }}
            </template>
        </el-table-column>
        <el-table-column prop="expire_date" label="过期日期" width="120" />

        <!-- json -->
        <el-table-column label="扩展项" min-width="140">
            <template #default="{ row }">
                <template v-if="row.extra?.length">
                    <el-tag v-for="v in row.extra" :key="v" round class="mr-1 mb-1">{{ extraLabel(v) }}</el-tag>
                </template>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>

        <el-table-column prop="remark" label="备注" min-width="140" show-overflow-tooltip />
        <el-table-column prop="sort" label="排序" width="72" align="center" />
        <el-table-column label="创建时间" width="170">
            <template #default="{ row }">
                {{ formatUnixTime(row.create_time) }}
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
