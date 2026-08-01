<script setup lang="ts">
import type { AttachmentRow } from '@/apis/system/attachment'
import * as attachmentApi from '@/apis/system/attachment'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { formatUnixTime } from '@/utils/formatUnixTime'
import { Delete, View } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'

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

const detailVisible = ref(false)
const detail = ref<AttachmentRow | null>(null)

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

/** openDetail 打开详情 */
const openDetail = async (row: AttachmentRow) => {
    try {
        detail.value = await attachmentApi.fetchRead(row.id)
        detailVisible.value = true
    } catch {
        detail.value = row
        detailVisible.value = true
    }
}

/** clickDel 删除附件 */
const clickDel = (row: AttachmentRow) => {
    void confirm(`确定删除附件「${row.name}」？将同时删除磁盘文件。`, () =>
        attachmentApi.deleteAttachment(row.id),
    )
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
            <el-dialog v-model="detailVisible" title="附件详情" width="560px" destroy-on-close>
                <template v-if="detail">
                    <div v-if="isImageMime(detail.mime) && detail.url" class="mb-4 flex justify-center">
                        <el-image :src="detail.url" fit="contain" class="max-h-48" />
                    </div>
                    <el-descriptions :column="1" border>
                        <el-descriptions-item label="ID">{{ detail.id }}</el-descriptions-item>
                        <el-descriptions-item label="文件名">{{ detail.name }}</el-descriptions-item>
                        <el-descriptions-item label="路径">{{ detail.path }}</el-descriptions-item>
                        <el-descriptions-item label="磁盘">{{ detail.disk }}</el-descriptions-item>
                        <el-descriptions-item label="接口">{{ detail.scene }}</el-descriptions-item>
                        <el-descriptions-item label="MIME">{{ detail.mime || '—' }}</el-descriptions-item>
                        <el-descriptions-item label="扩展名">{{ detail.ext || '—' }}</el-descriptions-item>
                        <el-descriptions-item label="大小">{{ formatBytes(detail.size) }}</el-descriptions-item>
                        <el-descriptions-item label="SHA1">{{ detail.sha1 || '—' }}</el-descriptions-item>
                        <el-descriptions-item label="文件">
                            {{ detail.file_deleted === 1 ? '物理文件已删除' : '在盘' }}
                        </el-descriptions-item>
                        <el-descriptions-item label="上传人">{{ adminLabel(detail) }}</el-descriptions-item>
                        <el-descriptions-item label="URL">
                            <a
                                v-if="detail.url"
                                :href="detail.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary"
                            >
                                {{ detail.url }}
                            </a>
                            <span v-else>—</span>
                        </el-descriptions-item>
                        <el-descriptions-item label="创建时间">
                            {{ formatUnixTime(detail.create_time) }}
                        </el-descriptions-item>
                    </el-descriptions>
                </template>
            </el-dialog>
        </template>
    </ga-table-page>
</template>
