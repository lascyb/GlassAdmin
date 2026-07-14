<script setup lang="ts">
import * as entityApi from '@/apis/demo/alltype'
import EditView from '@/views/demo/alltype/edit.vue'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaSelectRemote from '@/components/ga-select-remote/index.vue'
import GaStatusTag from '@/components/ga-status-tag/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import type { AllTypeRow } from '@/apis/demo/alltype'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'
import { formatUnixTime } from '@/utils/formatUnixTime'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { STATUS_MAP, LEVEL_MAP, EXTRA_OPTIONS } from '@/views/demo/alltype/constants'

const keyword = ref('')

const advanced = reactive({
    code: '',
    category_id: undefined as number | undefined,
    tag_ids: [] as number[],
    amount_min: undefined as number | undefined,
    amount_max: undefined as number | undefined,
    is_top: undefined as number | undefined,
    status: undefined as number | undefined,
    level: undefined as number | undefined,
    publish_time: null as [number, number] | null,
    expire_date: null as [string, string] | null,
})

const queryParams = computed(() => ({
    keyword: keyword.value.trim() || undefined,
    code: advanced.code.trim() || undefined,
    category_id: advanced.category_id,
    tag_ids: advanced.tag_ids.length > 0 ? advanced.tag_ids : undefined,
    amount_min: advanced.amount_min,
    amount_max: advanced.amount_max,
    is_top: advanced.is_top === 0 || advanced.is_top === 1 ? advanced.is_top : undefined,
    status: [1, 0].includes(Number(advanced.status)) ? advanced.status : undefined,
    level: [1, 2, 3].includes(Number(advanced.level)) ? advanced.level : undefined,
    publish_time_start: advanced.publish_time?.[0] || undefined,
    publish_time_end: advanced.publish_time?.[1] || undefined,
    expire_date_start: advanced.expire_date?.[0] || undefined,
    expire_date_end: advanced.expire_date?.[1] || undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<AllTypeRow>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: number }>({})

const clickEdit = (id: number) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: AllTypeRow) => {
    void confirm(`确定删除「${row.title}」？`, () => entityApi.delete(row.id))
}
const onSaved = () => {
    editController.show = false
    void load()
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.code = ''
    advanced.category_id = undefined
    advanced.tag_ids = []
    advanced.amount_min = undefined
    advanced.amount_max = undefined
    advanced.is_top = undefined
    advanced.status = undefined
    advanced.level = undefined
    advanced.publish_time = null
    advanced.expire_date = null
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
                keyword-placeholder="标题 / 备注"
                @refresh="load"
                @search="search"
                @reset="resetAdvanced"
            >
                <template #advanced>
                    <el-form-item label="编码">
                        <el-input v-model.trim="advanced.code" clearable placeholder="模糊匹配" class="!w-40" />
                    </el-form-item>

                    <el-form-item label="分类">
                        <ga-select-remote
                            v-model="advanced.category_id"
                            :remote-url="'example.Category/list'"
                            :props="{ value: 'id', label: 'name' }"
                            clearable
                            placeholder="搜索分类"
                            class="!w-40"
                        />
                    </el-form-item>

                    <el-form-item label="标签">
                        <ga-select-remote
                            v-model="advanced.tag_ids"
                            :remote-url="'example.Tag/list'"
                            :props="{ value: 'id', label: 'name' }"
                            multiple
                            collapse-tags
                            clearable
                            placeholder="搜索标签"
                            class="!w-48"
                        />
                    </el-form-item>

                    <el-form-item label="金额">
                        <div class="flex items-center gap-1">
                            <el-input-number v-model="advanced.amount_min" controls-position="right" class="!w-28" placeholder="最小" />
                            <span class="text-gray-400">~</span>
                            <el-input-number v-model="advanced.amount_max" controls-position="right" class="!w-28" placeholder="最大" />
                        </div>
                    </el-form-item>
                    <el-form-item label="置顶">
                        <el-select v-model="advanced.is_top" clearable placeholder="全部" class="!w-28">
                            <el-option :value="1" label="是" />
                            <el-option :value="0" label="否" />
                        </el-select>
                    </el-form-item>

                    <el-form-item label="状态">
                        <el-select v-model="advanced.status" clearable placeholder="全部" class="!w-28">
                            <el-option
                                v-for="(item, value) in STATUS_MAP"
                                :key="value"
                                :value="Number(value)"
                                :label="item.label"
                            />
                        </el-select>
                    </el-form-item>

                    <el-form-item label="等级">
                        <el-select v-model="advanced.level" clearable placeholder="全部" class="!w-28">
                            <el-option
                                v-for="(item, value) in LEVEL_MAP"
                                :key="value"
                                :value="Number(value)"
                                :label="item.label"
                            />
                        </el-select>
                    </el-form-item>

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

                    <el-form-item label="过期日期">
                        <el-date-picker
                            v-model="advanced.expire_date"
                            type="daterange"
                            value-format="YYYY-MM-DD"
                            start-placeholder="开始"
                            end-placeholder="结束"
                            class="!w-56"
                        />
                    </el-form-item>

                </template>

                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

        <el-table-column prop="id" label="ID" width="72" />

        <el-table-column prop="title" label="标题" min-width="140" show-overflow-tooltip />

        <el-table-column prop="code" label="编码" min-width="120" show-overflow-tooltip />

        <el-table-column label="分类" width="110">
            <template #default="{ row }">
                <el-tag v-if="row.category?.name" round>{{ row.category.name }}</el-tag>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>

        <el-table-column label="标签" min-width="160">
            <template #default="{ row }">
                <template v-if="row.tags?.length">
                    <el-tag v-for="t in row.tags" :key="t.id" round class="mr-1 mb-1">{{ t.name }}</el-tag>
                </template>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>

        <el-table-column prop="amount" label="金额" width="100" align="center" />

        <el-table-column prop="quantity" label="数量" width="80" align="center" />

        <el-table-column label="置顶" width="72" align="center">
            <template #default="{ row }">
                <el-tag :type="row.is_top ? 'warning' : 'info'" round>{{ row.is_top ? '是' : '否' }}</el-tag>
            </template>
        </el-table-column>

        <el-table-column label="状态" width="88">
            <template #default="{ row }">
                <ga-status-tag :value="row.status" :map="STATUS_MAP" />
            </template>
        </el-table-column>

        <el-table-column label="等级" width="88">
            <template #default="{ row }">
                <ga-status-tag :value="row.level" :map="LEVEL_MAP" />
            </template>
        </el-table-column>

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

        <el-table-column label="发布时间" width="170">
            <template #default="{ row }">{{ formatUnixTime(row.publish_time) }}</template>
        </el-table-column>

        <el-table-column prop="expire_date" label="过期日期" width="120" show-overflow-tooltip />

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
            <template #default="{ row }">{{ formatUnixTime(row.create_time) }}</template>
        </el-table-column>

        <el-table-column label="更新时间">
            <template #default="{ row }">{{ formatUnixTime(row.update_time) }}</template>
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
