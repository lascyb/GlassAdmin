<script setup lang="ts">
import type { AttachmentRow } from '@/apis/system/attachment'
import * as attachmentApi from '@/apis/system/attachment'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaPage from '@/components/ga-page/index.vue'
import GaTable from '@/components/ga-table/index.vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { formatUnixTime } from '@/utils/formatUnixTime'
import { Delete, View } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'
import DetailDialog from './DetailDialog.vue'

const keyword = ref('')

const advanced = reactive({
    scene: '',
    mime: '',
})

const queryParams = computed(() => ({
    keyword: keyword.value.trim() || undefined,
    scene: advanced.scene.trim() || undefined,
    mime: advanced.mime.trim() || undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<AttachmentRow>(
    (params) => attachmentApi.fetchList(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const detailController = reactive<{
    show: boolean
    id: number
    seed: AttachmentRow | null
}>({
    show: false,
    id: 0,
    seed: null,
})

/** formatBytes 字节可读化 */
const formatBytes = (n: number | null | undefined) => {
    if (n == null || Number.isNaN(n)) return '—'
    if (n < 1024) return `${n} B`
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
    if (n < 1024 * 1024 * 1024) return `${(n / 1024 / 1024).toFixed(2)} MB`
    return `${(n / 1024 / 1024 / 1024).toFixed(2)} GB`
}

/** isImageMime 是否图片 MIME */
const isImageMime = (mime?: string | null) => !!mime && mime.startsWith('image/')

/** adminLabel 上传人展示 */
const adminLabel = (row: AttachmentRow) => {
    const u = row.admin
    if (!u) return row.admin_id ? String(row.admin_id) : '—'
    return u.nickname?.trim() || u.username || String(u.id)
}

/** resetAdvanced 重置高级筛选 */
const resetAdvanced = () => {
    advanced.scene = ''
    advanced.mime = ''
}

/** openDetail 打开详情弹窗 */
const openDetail = (row: AttachmentRow) => {
    detailController.id = row.id
    detailController.seed = row
    detailController.show = true
}

/** clickDel 删除附件 */
const clickDel = (row: AttachmentRow) => {
    void confirm(`确定删除附件「${row.name}」？将同时删除磁盘文件。`, () =>
        attachmentApi.deleteAttachment(row.id),
    )
}
</script>

<template>
    <ga-page padding="sm" min-height="max(calc(100vh - 100px), 400px)">
    <ga-table
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
                keyword-placeholder="文件名 / 路径"
                @refresh="load"
                @search="search"
                @reset="resetAdvanced"
            >
                <template #advanced>
                    <el-form-item label="接口">
                        <el-input
                            v-model.trim="advanced.scene"
                            clearable
                            placeholder="如 account.Profile/uploadAvatar"
                            class="!w-64"
                        />
                    </el-form-item>
                    <el-form-item label="MIME">
                        <el-input
                            v-model.trim="advanced.mime"
                            clearable
                            placeholder="如 image/"
                            class="!w-36"
                        />
                    </el-form-item>
                </template>
            </ga-search-bar>
        </template>

        <el-table-column label="预览" width="80" align="center">
            <template #default="{ row }">
                <el-image
                    v-if="isImageMime(row.mime) && row.url"
                    :src="row.url"
                    fit="cover"
                    class="h-10 w-10 rounded"
                    :preview-src-list="[row.url]"
                    preview-teleported
                />
                <span v-else class="text-xs text-gray-400">{{ row.ext || '文件' }}</span>
            </template>
        </el-table-column>
        <el-table-column prop="name" label="文件名" min-width="160" show-overflow-tooltip />
        <el-table-column prop="path" label="路径" min-width="200" show-overflow-tooltip />
        <el-table-column prop="scene" label="接口" min-width="180" show-overflow-tooltip />
        <el-table-column prop="mime" label="MIME" min-width="120" show-overflow-tooltip />
        <el-table-column label="大小" width="100">
            <template #default="{ row }">{{ formatBytes(row.size) }}</template>
        </el-table-column>
        <el-table-column label="文件" width="100">
            <template #default="{ row }">
                <el-tag :type="row.file_deleted === 1 ? 'info' : 'success'" size="small">
                    {{ row.file_deleted === 1 ? '已删文件' : '在盘' }}
                </el-tag>
            </template>
        </el-table-column>
        <el-table-column label="上传人" width="120" show-overflow-tooltip>
            <template #default="{ row }">{{ adminLabel(row) }}</template>
        </el-table-column>
        <el-table-column label="创建时间" width="170">
            <template #default="{ row }">{{ formatUnixTime(row.create_time) }}</template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" width="140">
            <template #default="{ row }">
                <el-button title="详情" plain type="primary" :icon="View" @click="openDetail(row)" />
                <el-button
                    v-auth="'del'"
                    title="删除"
                    plain
                    type="danger"
                    :icon="Delete"
                    @click="clickDel(row)"
                />
            </template>
        </el-table-column>

        <template #footer>
            <DetailDialog
                v-if="detailController.show"
                v-model="detailController.show"
                :id="detailController.id"
                :seed="detailController.seed"
            />
        </template>
    </ga-table>
    </ga-page>
</template>
