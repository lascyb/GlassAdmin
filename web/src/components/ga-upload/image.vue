<script setup lang="ts">
import { showError } from '@/apis/request'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { Plus } from '@element-plus/icons-vue'
import type { UploadRequestOptions } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, h, ref, useSlots } from 'vue'

const path = defineModel<string | null>('modelValue', { default: null })

const props = withDefaults(
    defineProps<{
        /** accept 文件类型，传给 el-upload */
        accept?: string
        /** maxSize 最大字节数，0 不限制 */
        maxSize?: number
        /** tip 底部提示 */
        tip?: string
        /** disabled 禁用 */
        disabled?: boolean
        /** maxSizeHintUrl 超限时推荐的压缩工具链接 */
        maxSizeHintUrl?: string
        /** httpRequest 上传并返回存储路径（相对 path）；onProgress 为 0–100 */
        httpRequest: (file: File, onProgress?: (percent: number) => void) => Promise<string>
    }>(),
    {
        accept: 'image/jpeg,image/png,image/gif,image/webp',
        maxSize: 2 * 1024 * 1024,
        tip: '',
        disabled: false,
        maxSizeHintUrl: '',
    },
)

const emit = defineEmits<{ success: [path: string] }>()

const slots = useSlots()
const uploading = ref(false)
const percent = ref(0)

const displayUrl = computed(() => resolveStorageUrl(path.value))

/** beforeUpload 类型与大小校验 */
const beforeUpload = (rawFile: File) => {
    if (props.accept) {
        const allowed = props.accept.split(',').map((s) => s.trim()).filter(Boolean)
        const ok = allowed.some((a) => {
            if (a.endsWith('/*')) {
                return rawFile.type.startsWith(a.slice(0, -1))
            }
            return rawFile.type === a
        })
        if (!ok) {
            ElMessage.error('不支持的文件类型')
            return false
        }
    }
    if (props.maxSize > 0 && rawFile.size > props.maxSize) {
        const mb = props.maxSize / 1024 / 1024
        const base = mb >= 1 ? `文件不能超过 ${mb}MB` : `文件不能超过 ${props.maxSize} 字节`
        if (props.maxSizeHintUrl) {
            ElMessage({
                type: 'error',
                duration: 8000,
                message: h('span', null, [
                    `${base}，推荐去 `,
                    h(
                        'a',
                        {
                            href: props.maxSizeHintUrl,
                            target: '_blank',
                            rel: 'noopener noreferrer',
                            style: {
                                color: 'var(--el-color-primary)',
                                textDecoration: 'underline',
                            },
                            onClick: (e: MouseEvent) => e.stopPropagation(),
                        },
                        '图片压缩工具',
                    ),
                    ' 压缩后再上传',
                ]),
            })
        } else {
            ElMessage.error(base)
        }
        return false
    }
    return true
}

/** onRequest 适配 el-upload http-request */
const onRequest = async (opt: UploadRequestOptions) => {
    uploading.value = true
    percent.value = 0
    try {
        const next = await props.httpRequest(opt.file as File, (p) => {
            percent.value = p
        })
        path.value = next
        emit('success', next)
        ElMessage.success('上传成功')
        opt.onSuccess?.(next)
    } catch (e) {
        showError(e, '上传失败')
        const err = e instanceof Error ? e : new Error(String(e))
        opt.onError?.(err as Parameters<NonNullable<UploadRequestOptions['onError']>>[0])
    } finally {
        uploading.value = false
        percent.value = 0
    }
}
</script>

<template>
    <el-upload
        class="ga-upload"
        :class="{ 'is-disabled': disabled || uploading }"
        :show-file-list="false"
        :accept="accept"
        :before-upload="beforeUpload"
        :http-request="onRequest"
        :disabled="disabled || uploading"
    >
        <slot
            name="trigger"
            :url="displayUrl"
            :uploading="uploading"
            :percent="percent"
            :disabled="disabled || uploading"
        >
            <div class="ga-upload__default">
                <img v-if="displayUrl" :src="displayUrl" class="ga-upload__preview" alt="" />
                <el-icon v-else-if="!uploading" class="ga-upload__plus"><Plus /></el-icon>
                <div v-if="uploading" class="ga-upload__mask">{{ percent }}%</div>
            </div>
        </slot>
        <template v-if="tip || slots.tip" #tip>
            <slot name="tip">
                <p v-if="tip" class="ga-upload__tip">{{ tip }}</p>
            </slot>
        </template>
    </el-upload>
</template>

<style scoped>
.ga-upload :deep(.el-upload) {
    cursor: pointer;
}

.ga-upload.is-disabled :deep(.el-upload) {
    cursor: not-allowed;
    opacity: 0.75;
}

.ga-upload__default {
    position: relative;
    width: 96px;
    height: 96px;
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: border-color var(--el-transition-duration-fast);
}

.ga-upload:not(.is-disabled):hover .ga-upload__default {
    border-color: var(--el-color-primary);
}

.ga-upload__preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ga-upload__plus {
    font-size: 24px;
    color: var(--el-text-color-secondary);
}

.ga-upload__mask {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.45);
    color: #fff;
    font-size: 14px;
    font-variant-numeric: tabular-nums;
}

.ga-upload__tip {
    margin: 8px 0 0;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}
</style>
