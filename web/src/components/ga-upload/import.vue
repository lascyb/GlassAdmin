<script setup lang="ts">
import { post, type AxiosProgressEvent } from '@/apis/request'
import { UploadFilled } from '@element-plus/icons-vue'
import type { UploadFile, UploadInstance, UploadRawFile } from 'element-plus'
import { ElMessage, genFileId } from 'element-plus'
import { computed, ref, watch } from 'vue'

const visible = defineModel<boolean>({ default: false })

const props = withDefaults(
    defineProps<{
        /** title 对话框标题 */
        title?: string
        /** templateUrl 导入模板下载地址，空则不展示 */
        templateUrl?: string
        /** accept 允许的扩展名或 MIME，逗号分隔 */
        accept?: string
        /** maxSize 最大字节数，0 不限制；默认 10MB，与 AttachmentUploadOptions::default 对齐 */
        maxSize?: number
        /** tip 底部提示，空则按 accept/maxSize 生成 */
        tip?: string
        /** url 业务导入接口路径，如 ass.batch.Range/import */
        url?: string
        /** httpRequest 自定义提交；提供时优先于 url；onProgress 为 0–100 */
        httpRequest?: (file: File, onProgress?: (percent: number) => void) => Promise<unknown>
        /** data 一并写入 FormData 的额外字段（仅 url 模式） */
        data?: Record<string, string | number>
        /** closeOnSuccess 成功后关闭对话框 */
        closeOnSuccess?: boolean
        /** successMsg 无接口结果文案时的兜底提示 */
        successMsg?: string
    }>(),
    {
        title: '文件导入',
        templateUrl: '',
        accept: '.xlsx,.xls',
        maxSize: 10 * 1024 * 1024,
        tip: '',
        closeOnSuccess: true,
        successMsg: '导入成功',
    },
)

const emit = defineEmits<{
    success: [data: unknown]
}>()

const uploadRef = ref<UploadInstance>()
const rawFile = ref<File | null>(null)
const submitting = ref(false)
const percent = ref(0)
const done = ref(false)

const tipText = computed(() => {
    if (props.tip) return props.tip
    const acceptLabel = props.accept
        .split(',')
        .map((s) => s.trim().replace(/^\./, ''))
        .filter(Boolean)
        .join('、')
    if (props.maxSize <= 0) {
        return `支持 ${acceptLabel}`
    }
    const mb = props.maxSize / 1024 / 1024
    const size = mb >= 1 ? `${mb}MB` : `${props.maxSize} 字节`
    return `支持 ${acceptLabel}，不超过 ${size}`
})

/** showError 导入类错误不自动关闭 */
const showError = (message: string) => {
    ElMessage({ type: 'error', message, duration: 0, showClose: true })
}

/** successText 优先用接口 data.msg / created / skipped */
const successText = (result: unknown): string => {
    if (result && typeof result === 'object') {
        const row = result as Record<string, unknown>
        if (typeof row.msg === 'string' && row.msg.trim() !== '') {
            return row.msg
        }
        const created = row.created ?? row.count
        const skipped = row.skipped
        if (typeof created === 'number') {
            if (typeof skipped === 'number') {
                return `导入成功：新增 ${created}，跳过 ${skipped}`
            }
            return `导入成功：${created} 条`
        }
    }
    return props.successMsg
}

/** reset 关闭时清空已选文件与状态 */
const reset = () => {
    rawFile.value = null
    done.value = false
    submitting.value = false
    percent.value = 0
    uploadRef.value?.clearFiles()
}

watch(visible, (open) => {
    if (!open) reset()
})

/** fileAllowed 按 accept 校验扩展名或 MIME */
const fileAllowed = (file: File): boolean => {
    const items = props.accept
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)
    if (items.length === 0) return true
    const name = file.name.toLowerCase()
    const type = file.type
    return items.some((a) => {
        const token = a.toLowerCase()
        if (token.startsWith('.')) return name.endsWith(token)
        if (token.endsWith('/*')) return type.startsWith(token.slice(0, -1))
        return type === token
    })
}

/** sizeErrorText 超限提示 */
const sizeErrorText = (): string => {
    const mb = props.maxSize / 1024 / 1024
    return mb >= 1 ? `文件不能超过 ${mb}MB` : `文件不能超过 ${props.maxSize} 字节`
}

/** takeFile 校验并记录当前文件 */
const takeFile = (file: File | undefined) => {
    done.value = false
    if (!file) {
        rawFile.value = null
        return
    }
    if (!fileAllowed(file)) {
        showError('不支持的文件类型')
        uploadRef.value?.clearFiles()
        rawFile.value = null
        return
    }
    if (props.maxSize > 0 && file.size > props.maxSize) {
        showError(sizeErrorText())
        uploadRef.value?.clearFiles()
        rawFile.value = null
        return
    }
    rawFile.value = file
}

/** onChange 选择或替换文件 */
const onChange = (file: UploadFile) => {
    takeFile(file.raw)
}

/** onRemove 移除已选文件 */
const onRemove = () => {
    rawFile.value = null
    done.value = false
}

/** onExceed 超出数量时替换为新文件 */
const onExceed = (files: File[]) => {
    const next = files[0]
    if (!next) return
    const raw = next as UploadRawFile
    raw.uid = genFileId()
    uploadRef.value?.clearFiles()
    uploadRef.value?.handleStart(raw)
}

/** onUploadProgress axios 上传进度 0–100 */
const onUploadProgress = (e: AxiosProgressEvent) => {
    if (!e.total) {
        return
    }
    percent.value = Math.min(100, Math.round((e.loaded / e.total) * 100))
}

/** submit 提交导入；已成功过则用同一文件再导一次 */
const submit = async () => {
    if (!rawFile.value) {
        ElMessage.warning('请先选择文件')
        return
    }
    submitting.value = true
    percent.value = 0
    try {
        let result: unknown
        if (props.httpRequest) {
            result = await props.httpRequest(rawFile.value, (p) => {
                percent.value = p
            })
        } else if (props.url) {
            const fd = new FormData()
            fd.append('file', rawFile.value)
            for (const [k, v] of Object.entries(props.data ?? {})) {
                fd.append(k, String(v))
            }
            result = await post(props.url, fd, { silent: true, onUploadProgress })
        } else {
            showError('未配置 url 或 httpRequest')
            return
        }
        done.value = true
        emit('success', result)
        ElMessage.success(successText(result))
        if (props.closeOnSuccess) {
            visible.value = false
        }
    } catch (e: unknown) {
        showError(e instanceof Error ? e.message : '导入失败')
    } finally {
        submitting.value = false
        percent.value = 0
    }
}
</script>

<template>
    <el-dialog
        v-model="visible"
        :title="title"
        width="520px"
        draggable
        destroy-on-close
        lock-scroll
        append-to-body
        :close-on-click-modal="false"
    >
        <div class="flex flex-col gap-3">
            <el-upload
                ref="uploadRef"
                drag
                :auto-upload="false"
                :limit="1"
                :accept="accept"
                :disabled="submitting"
                :on-change="onChange"
                :on-remove="onRemove"
                :on-exceed="onExceed"
            >
                <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
                <div class="el-upload__text">将文件拖到此处，或 <em>点击选择</em></div>
                <template #tip>
                    <div class="el-upload__tip">{{ tipText }}</div>
                </template>
            </el-upload>
            <el-progress v-if="submitting" :percentage="percent" :stroke-width="6" />
            <el-link
                v-if="templateUrl"
                type="primary"
                :href="templateUrl"
                download
                target="_blank"
                rel="noopener noreferrer"
            >
                下载导入模板
            </el-link>
            <el-button class="w-full" type="primary" :disabled="submitting || !rawFile" :loading="submitting" @click="submit">
                {{ submitting ? `${percent}%` : done ? '重新导入' : '确定导入' }}
            </el-button>
        </div>
    </el-dialog>
</template>
