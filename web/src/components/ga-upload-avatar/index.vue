<script setup lang="ts">
import * as accountApi from '@/apis/account'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { Loading, Plus } from '@element-plus/icons-vue'
import type { UploadRequestOptions } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, ref } from 'vue'

const avatarPath = defineModel<string | null>('modelValue', { default: null })

const props = withDefaults(
    defineProps<{
        /** size 头像尺寸 px */
        size?: number
        /** initial 无图时显示文字（昵称/用户名首字符） */
        initial?: string
        /** disabled 禁用上传 */
        disabled?: boolean
        /** tip 底部提示文案 */
        tip?: string
        /** httpRequest 自定义上传，默认 account.Profile/uploadAvatar */
        httpRequest?: (opt: UploadRequestOptions) => Promise<void>
    }>(),
    {
        size: 96,
        initial: '?',
        disabled: false,
        tip: '支持 JPG、PNG、GIF、WebP，最大 2MB',
    },
)

const emit = defineEmits<{ success: [avatar: string] }>()

const uploading = ref(false)

const avatarDisplayUrl = computed(() => resolveStorageUrl(avatarPath.value))

/** onAvatarImgError 图片无效时回退插槽文字 */
const onAvatarImgError = (): boolean => true

/** beforeAvatarUpload 类型与大小校验 */
const beforeAvatarUpload = (rawFile: File) => {
    const okType = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(rawFile.type)
    if (!okType) {
        ElMessage.error('仅支持 JPG、PNG、GIF、WebP')
        return false
    }
    const max = 2 * 1024 * 1024
    if (rawFile.size > max) {
        ElMessage.error('图片不能超过 2MB')
        return false
    }
    return true
}

/** defaultUpload 默认走 account 头像上传 */
const defaultUpload = async (opt: UploadRequestOptions) => {
    uploading.value = true
    try {
        const fd = new FormData()
        fd.append('file', opt.file as File)
        const res = await accountApi.uploadProfileAvatar(fd)
        avatarPath.value = res.avatar
        emit('success', res.avatar)
        ElMessage.success('头像已更新')
        opt.onSuccess(res)
    } catch (e) {
        const err = e instanceof Error ? e : new Error(String(e))
        ElMessage.error(err.message)
        opt.onError(err as Parameters<UploadRequestOptions['onError']>[0])
    } finally {
        uploading.value = false
    }
}

const onRequest = (opt: UploadRequestOptions) => {
    const fn = props.httpRequest ?? defaultUpload
    return fn(opt)
}

const avatarInitial = computed(() => {
    const s = (props.initial || '?').trim()
    return s ? s.slice(0, 1).toUpperCase() : '?'
})
</script>

<template>
    <el-upload
        class="avatar-uploader"
        :class="{ 'is-disabled': disabled || uploading }"
        :show-file-list="false"
        accept="image/jpeg,image/png,image/gif,image/webp"
        :before-upload="beforeAvatarUpload"
        :http-request="onRequest"
        :disabled="disabled || uploading"
    >
        <div class="avatar-uploader__trigger">
            <el-avatar
                :size="size"
                shape="circle"
                fit="cover"
                :src="avatarDisplayUrl || undefined"
                @error="onAvatarImgError"
            >
                {{ avatarInitial }}
            </el-avatar>
            <div class="avatar-uploader__overlay" :class="{ 'is-busy': uploading }">
                <el-icon v-if="uploading" class="is-loading avatar-uploader__icon-lg">
                    <Loading />
                </el-icon>
                <template v-else>
                    <el-icon class="avatar-uploader__icon-lg"><Plus /></el-icon>
                    <span class="avatar-uploader__hint">点击更换</span>
                </template>
            </div>
        </div>
        <template #tip>
            <p v-if="tip" class="avatar-uploader__tip">{{ tip }}</p>
        </template>
    </el-upload>
</template>

<style scoped>
.avatar-uploader :deep(.el-upload) {
    border: 1px dashed var(--el-border-color);
    border-radius: 50%;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--el-transition-duration-fast);
}

.avatar-uploader :deep(.el-upload:hover) {
    border-color: var(--el-color-primary);
}

.avatar-uploader.is-disabled :deep(.el-upload) {
    cursor: not-allowed;
    opacity: 0.75;
}

.avatar-uploader__trigger {
    position: relative;
    line-height: 0;
}

.avatar-uploader__overlay {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    color: #fff;
    font-size: 12px;
    background: rgba(0, 0, 0, 0.45);
    opacity: 0;
    transition: opacity var(--el-transition-duration-fast);
    pointer-events: none;
}

.avatar-uploader:not(.is-disabled):hover .avatar-uploader__overlay:not(.is-busy) {
    opacity: 1;
}

.avatar-uploader__overlay.is-busy {
    opacity: 1;
}

.avatar-uploader__icon-lg {
    font-size: 22px;
}

.avatar-uploader__hint {
    line-height: 1;
}

.avatar-uploader__tip {
    margin: 8px 0 0;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}
</style>
