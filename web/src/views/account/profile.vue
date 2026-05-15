<script setup lang="ts">
import * as accountApi from '@/apis/account'
import { useAdmin } from '@/pinia/admin'
import { useSecurityRsa } from '@/pinia/security/rsa'
import { resolveStorageUrl } from '@/utils/storageUrl'
import { Loading, Plus } from '@element-plus/icons-vue'
import type { UploadRequestOptions } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'

const admin = useAdmin()
const rsa = useSecurityRsa()

const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const avatarPath = ref<string | null>(null)
const form = reactive({
    username: '',
    nickname: '',
    password: '',
})

const avatarDisplayUrl = computed(() => resolveStorageUrl(avatarPath.value))

/** avatarInitial 无图或加载失败时头像内文字（取昵称或用户名首字符） */
const avatarInitial = computed(() => {
    const s = (form.nickname || form.username || '?').trim()
    return s ? s.slice(0, 1).toUpperCase() : '?'
})

/** onAvatarImgError 图片地址无效时回退为插槽文字（与 Avatar 文档 error 回调一致） */
const onAvatarImgError = (): boolean => true

const load = async () => {
    loading.value = true
    try {
        const d = await accountApi.fetchProfile()
        form.username = d.username
        form.nickname = d.nickname ?? ''
        form.password = ''
        avatarPath.value = d.avatar ?? null
    } finally {
        loading.value = false
    }
}

onMounted(load)

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

const onAvatarRequest = async (opt: UploadRequestOptions) => {
    uploading.value = true
    try {
        const fd = new FormData()
        fd.append('file', opt.file as File)
        const res = await accountApi.uploadProfileAvatar(fd)
        avatarPath.value = res.avatar
        if (admin.data) {
            admin.data.avatar = res.avatar
        }
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

const submit = async () => {
    saving.value = true
    try {
        const body: { nickname: string; password?: string } = {
            nickname: form.nickname.trim(),
        }
        let headers: { 'X-RSA-Serial': string } | undefined
        if (form.password) {
            const enc = await rsa.encrypt(form.password)
            if (!enc.serial || !enc.texts[0]) {
                ElMessage.error('获取加密公钥失败')
                return
            }
            body.password = enc.texts[0]
            headers = { 'X-RSA-Serial': enc.serial }
        }
        await accountApi.updateProfile(body, headers)
        ElMessage.success('保存成功')
        form.password = ''
        if (admin.data) {
            admin.data.nickname = body.nickname || admin.data.username
        }
        await load()
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <div v-loading="loading" class="max-w-xl p-4">
        <el-card shadow="never">
            <template #header>个人信息</template>
            <el-form label-width="88px" @submit.prevent>
                <el-form-item label="头像">
                    <el-upload
                        class="avatar-uploader"
                        :show-file-list="false"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        :before-upload="beforeAvatarUpload"
                        :http-request="onAvatarRequest"
                        :disabled="uploading"
                    >
                        <div class="avatar-uploader__trigger">
                            <el-avatar
                                :size="96"
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
                            <p class="avatar-uploader__tip">支持 JPG、PNG、GIF、WebP，最大 2MB</p>
                        </template>
                    </el-upload>
                </el-form-item>
                <el-form-item label="用户名">
                    <el-input v-model="form.username" disabled />
                </el-form-item>
                <el-form-item label="昵称">
                    <el-input v-model="form.nickname" maxlength="64" show-word-limit placeholder="显示名称" />
                </el-form-item>
                <el-form-item label="新密码">
                    <el-input
                        v-model="form.password"
                        type="password"
                        show-password
                        autocomplete="new-password"
                        placeholder="不修改请留空"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
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
