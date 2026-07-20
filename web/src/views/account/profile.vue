<script setup lang="ts">
import type { OperationLogRow } from '@/apis/account'
import * as accountApi from '@/apis/account'
import GaPasswordInput from '@/components/ga-password-input/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import GaUploadAvatar from '@/components/ga-upload-avatar/index.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { useAdmin } from '@/pinia/admin'
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
                            <ga-upload-avatar
                                v-model="avatarPath"
                                :initial="avatarInitial"
                                @success="onAvatarSuccess"
                            />
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
                <ga-table-page shadow="never"
                    v-model:page="logPage"
                    v-model:limit="logLimit"
                    :loading="logLoading"
                    :list="logList"
                    :total="logTotal"
                    :border="false"
                    :page-sizes="[10, 20]"
                    min-height="480px"
                    @load="loadLogs"
                >
                    <template #header>我的操作日志</template>
                    <el-table-column prop="create_time" label="时间" width="180" />
                    <el-table-column prop="summary" label="说明" min-width="200" show-overflow-tooltip />
                    <el-table-column prop="ip" label="IP" width="140" />
                </ga-table-page>
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
