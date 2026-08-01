<script setup lang="ts">
import type { AttachmentRow } from '@/apis/system/attachment'
import * as attachmentApi from '@/apis/system/attachment'
import { formatUnixTime } from '@/utils/formatUnixTime'
import { CopyDocument, Link } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref } from 'vue'

const props = defineProps<{
    /** id 附件 id */
    id: number
    /** seed 列表行，打开时先展示，再拉详情覆盖 */
    seed?: AttachmentRow | null
}>()

const show = defineModel<boolean>('modelValue', { required: true })

const loading = ref(false)
const detail = ref<AttachmentRow | null>(props.seed ?? null)

/** isImage 是否可预览图片 */
const isImage = computed(
    () => !!detail.value?.mime?.startsWith('image/') && !!detail.value?.url,
)

/** formatBytes 字节可读化 */
const formatBytes = (n: number | null | undefined) => {
    if (n == null || Number.isNaN(n)) return '—'
    if (n < 1024) return `${n} B`
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
    if (n < 1024 * 1024 * 1024) return `${(n / 1024 / 1024).toFixed(2)} MB`
    return `${(n / 1024 / 1024 / 1024).toFixed(2)} GB`
}

/** adminLabel 上传人展示 */
const adminLabel = (row: AttachmentRow) => {
    const u = row.admin
    if (!u) return row.admin_id ? String(row.admin_id) : '—'
    return u.nickname?.trim() || u.username || String(u.id)
}

/** copyText 复制文本到剪贴板 */
const copyText = async (text: string | null | undefined) => {
    const t = (text ?? '').trim()
    if (!t) {
        ElMessage.warning('无可复制内容')
        return
    }
    try {
        await navigator.clipboard.writeText(t)
        ElMessage.success('已复制')
    } catch {
        ElMessage.error('复制失败')
    }
}

/** openUrl 新窗口打开附件 URL */
const openUrl = () => {
    const url = detail.value?.url
    if (!url) return
    window.open(url, '_blank', 'noopener,noreferrer')
}

/** loadDetail 拉取详情 */
const loadDetail = async () => {
    loading.value = true
    try {
        detail.value = await attachmentApi.fetchRead(props.id)
    } catch {
        /* 沿用 seed */
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    void loadDetail()
})
</script>

<template>
    <el-dialog
        v-model="show"
        title="附件详情"
        width="720px"
        destroy-on-close
        append-to-body
    >
        <div v-loading="loading" class="min-h-40">
            <template v-if="detail">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div
                        v-if="isImage"
                        class="flex shrink-0 items-center justify-center rounded border border-gray-100 bg-gray-50 p-3 sm:w-56"
                    >
                        <el-image
                            :src="detail.url!"
                            fit="contain"
                            class="max-h-52 w-full"
                            :preview-src-list="[detail.url!]"
                            preview-teleported
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <el-descriptions :column="2" border size="small">
                            <el-descriptions-item width="60" label="ID">{{ detail.id }}</el-descriptions-item>
                            <el-descriptions-item label="文件">
                                <el-tag
                                    :type="detail.file_deleted === 1 ? 'info' : 'success'"
                                    size="small"
                                >
                                    {{ detail.file_deleted === 1 ? '物理文件已删除' : '在盘' }}
                                </el-tag>
                            </el-descriptions-item>
                            <el-descriptions-item label="文件名" :span="2">
                                {{ detail.name }}
                            </el-descriptions-item>
                            <el-descriptions-item label="路径" :span="2">
                                <div class="flex items-start gap-1">
                                    <span class="min-w-0 flex-1 break-all">{{ detail.path }}</span>
                                    <el-button
                                        link
                                        type="primary"
                                        :icon="CopyDocument"
                                        title="复制路径"
                                        @click="copyText(detail.path)"
                                    />
                                </div>
                            </el-descriptions-item>
                            <el-descriptions-item label="磁盘">{{ detail.disk }}</el-descriptions-item>
                            <el-descriptions-item label="大小">
                                {{ formatBytes(detail.size) }}
                            </el-descriptions-item>
                            <el-descriptions-item label="MIME">
                                {{ detail.mime || '—' }}
                            </el-descriptions-item>
                            <el-descriptions-item label="扩展名">
                                {{ detail.ext || '—' }}
                            </el-descriptions-item>
                            <el-descriptions-item label="接口" :span="2">
                                {{ detail.scene }}
                            </el-descriptions-item>
                            <el-descriptions-item label="SHA1" :span="2">
                                <div class="flex items-start gap-1">
                                    <span class="min-w-0 flex-1 break-all font-mono text-xs">
                                        {{ detail.sha1 || '—' }}
                                    </span>
                                    <el-button
                                        v-if="detail.sha1"
                                        link
                                        type="primary"
                                        :icon="CopyDocument"
                                        title="复制 SHA1"
                                        @click="copyText(detail.sha1)"
                                    />
                                </div>
                            </el-descriptions-item>
                            <el-descriptions-item label="上传人">
                                <el-tag>{{ adminLabel(detail) }}</el-tag>
                            </el-descriptions-item>
                            <el-descriptions-item label="创建时间">
                                {{ formatUnixTime(detail.create_time) }}
                            </el-descriptions-item>
                            <el-descriptions-item label="URL" :span="2">
                                <div v-if="detail.url" class="flex items-start gap-1">
                                    <a
                                        :href="detail.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-primary min-w-0 flex-1 break-all"
                                    >
                                        {{ detail.url }}
                                    </a>
                                    <el-button
                                        link
                                        type="primary"
                                        :icon="CopyDocument"
                                        title="复制 URL"
                                        @click="copyText(detail.url)"
                                    />
                                </div>
                                <span v-else>—</span>
                            </el-descriptions-item>
                        </el-descriptions>
                    </div>
                </div>
            </template>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <el-button
                    v-if="detail?.url"
                    type="primary"
                    plain
                    :icon="Link"
                    @click="openUrl"
                >
                    新窗口打开
                </el-button>
                <el-button @click="show = false">关闭</el-button>
            </div>
        </template>
    </el-dialog>
</template>
