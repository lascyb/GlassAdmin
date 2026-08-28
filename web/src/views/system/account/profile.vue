<script setup lang="ts">
import type { OperationLogRow } from '@/apis/account.ts'
import * as accountApi from '@/apis/account.ts'
import { usePaginatedList } from '@/composables/usePaginatedList.ts'
import { useAdmin } from '@/pinia/admin'
import { resolveStorageUrl } from '@/utils/storageUrl'
import GaPasswordInput from '@components/ga-password-input/index.vue'
import GaPage from '@components/ga-page/index.vue'
import GaTable from '@components/ga-table/index.vue'
import GaUploadImage from '@components/ga-upload/image.vue'
import { Loading, Plus } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'

const admin = useAdmin()

const loading = ref(false)
const saving = ref(false)
const pwdDialogVisible = ref(false)
const pwdSaving = ref(false)
const avatarPath = ref<string | null>(null)
const form = reactive({
    username: '',
    nickname: '',
})
const pwdForm = reactive({
    password: '',
})

/** avatarInitial 无图或加载失败时头像内文字 */
const avatarInitial = computed(() => {
    const s = (form.nickname || form.username || '?').trim()
    return s ? s.slice(0, 1).toUpperCase() : '?'
})

const {
    list: logList,
    total: logTotal,
    page: logPage,
    limit: logLimit,
    loading: logLoading,
    load: loadLogs,
} = usePaginatedList<OperationLogRow>(
    (params) => accountApi.fetchMyOperationLogs(params),
    { defaultLimit: 10, showError: false },
)

const load = async () => {
    loading.value = true
    try {
        const d = await accountApi.fetchProfile()
        form.username = d.username
        form.nickname = d.nickname ?? ''
        avatarPath.value = d.avatar ?? null
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    void load()
    void loadLogs()
})

const onAvatarSuccess = (avatar: string) => {
    if (admin.data) {
        admin.data.avatar = avatar
    }
}

/** uploadAvatar 上传头像并返回存储路径 */
const uploadAvatar = async (file: File) => {
    const fd = new FormData()
    fd.append('file', file)
    const res = await accountApi.uploadProfileAvatar(fd)
    return res.avatar
}

/** avatarDisplayUrl 头像展示 URL */
const avatarDisplayUrl = computed(() => resolveStorageUrl(avatarPath.value))

/** onAvatarImgError 图片无效时回退插槽文字 */
const onAvatarImgError = (): boolean => true

const openPwdDialog = () => {
    pwdForm.password = ''
    pwdDialogVisible.value = true
}

const submit = async () => {
    saving.value = true
    try {
        const nickname = form.nickname.trim()
        await accountApi.updateProfile({ nickname })
        ElMessage.success('保存成功')
        if (admin.data) {
            admin.data.nickname = nickname || admin.data.username
        }
        await load()
        void loadLogs()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '保存失败')
    } finally {
        saving.value = false
    }
}

/** submitPassword 提交修改密码 */
const submitPassword = async () => {
    if (!pwdForm.password) {
        ElMessage.warning('请输入新密码')
        return
    }
    pwdSaving.value = true
    try {
        await accountApi.changePassword({ password: pwdForm.password.trim() })
        ElMessage.success('密码已修改')
        pwdDialogVisible.value = false
        pwdForm.password = ''
        void loadLogs()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '修改失败')
    } finally {
        pwdSaving.value = false
    }
}
</script>

<template>
    <div class="p-4">
        <el-row :gutter="16">
            <el-col :xs="24" :md="10" :lg="8">
                <el-card v-loading="loading" shadow="never">
                    <template #header>个人信息</template>
                    <el-form label-width="88px" @submit.prevent>
                        <el-form-item label="头像">
                            <ga-upload-image
                                v-model="avatarPath"
                                accept="image/jpeg,image/png,image/gif,image/webp"
                                :max-size="2 * 1024 * 1024"
                                tip="支持 JPG、PNG、GIF、WebP，最大 2MB"
                                max-size-hint-url="https://lascyb.github.io/tools/src/%E5%9B%BE%E7%89%87%E5%8E%8B%E7%BC%A9%E5%B7%A5%E5%85%B7.html"
                                :http-request="uploadAvatar"
                                @success="onAvatarSuccess"
                            >
                                <template #trigger="{ uploading }">
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
                                        <div
                                            class="avatar-uploader__overlay"
                                            :class="{ 'is-busy': uploading }"
                                        >
                                            <el-icon
                                                v-if="uploading"
                                                class="is-loading avatar-uploader__icon-lg"
                                            >
                                                <Loading />
                                            </el-icon>
                                            <template v-else>
                                                <el-icon class="avatar-uploader__icon-lg">
                                                    <Plus />
                                                </el-icon>
                                                <span class="avatar-uploader__hint">点击更换</span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </ga-upload-image>
                        </el-form-item>
                        <el-form-item label="用户名">
                            <el-input v-model="form.username" disabled />
                        </el-form-item>
                        <el-form-item label="昵称">
                            <el-input
                                v-model="form.nickname"
                                maxlength="64"
                                show-word-limit
                                placeholder="显示名称"
                            />
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
                            <el-button v-auth="'changePassword'" @click="openPwdDialog">修改密码</el-button>
                        </el-form-item>
                    </el-form>
                </el-card>
            </el-col>

            <el-col :xs="24" :md="14" :lg="16" class="mt-4 md:mt-0">
                <ga-page shadow="never" padding="none" min-height="480px" title="我的操作日志">
                    <ga-table
                        v-model:page="logPage"
                        v-model:limit="logLimit"
                        :loading="logLoading"
                        :list="logList"
                        :total="logTotal"
                        :border="false"
                        :page-sizes="[10, 20]"
                        @load="loadLogs"
                    >
                        <el-table-column prop="create_time" label="时间" width="180" />
                        <el-table-column prop="summary" label="说明" min-width="200" show-overflow-tooltip />
                        <el-table-column prop="ip" label="IP" width="140" />
                    </ga-table>
                </ga-page>
            </el-col>
        </el-row>

        <el-dialog
            v-model="pwdDialogVisible"
            title="修改密码"
            width="420px"
            destroy-on-close
            :close-on-click-modal="false"
            draggable
        >
            <el-form label-width="72px" @submit.prevent>
                <el-form-item label="新密码" required>
                    <ga-password-input
                        v-model="pwdForm.password"
                        placeholder="请输入新密码"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="pwdDialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="pwdSaving" @click="submitPassword">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<style scoped>
.avatar-uploader__trigger {
    position: relative;
    line-height: 0;
    border: 1px dashed var(--el-border-color);
    border-radius: 50%;
    overflow: hidden;
    transition: border-color var(--el-transition-duration-fast);
}

:deep(.ga-upload:not(.is-disabled):hover) .avatar-uploader__trigger {
    border-color: var(--el-color-primary);
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
    color: var(--ga-color-on-primary);
    font-size: 12px;
    background: rgba(0, 0, 0, 0.45);
    opacity: 0;
    transition: opacity var(--el-transition-duration-fast);
    pointer-events: none;
}

:deep(.ga-upload:not(.is-disabled):hover) .avatar-uploader__overlay:not(.is-busy) {
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
</style>
