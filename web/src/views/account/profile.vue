<script setup lang="ts">
import * as accountApi from '@/apis/account'
import { useAdmin } from '@/pinia/admin'
import GaPasswordInput from '@/components/ga-password-input/index.vue'
import GaUploadAvatar from '@/components/ga-upload-avatar/index.vue'
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref, useTemplateRef } from 'vue'

const admin = useAdmin()
const passwordRef = useTemplateRef<InstanceType<typeof GaPasswordInput>>('passwordRef')

const loading = ref(false)
const saving = ref(false)
const avatarPath = ref<string | null>(null)
const form = reactive({
    username: '',
    nickname: '',
    password: '',
})

/** avatarInitial 无图或加载失败时头像内文字 */
const avatarInitial = computed(() => {
    const s = (form.nickname || form.username || '?').trim()
    return s ? s.slice(0, 1).toUpperCase() : '?'
})

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

const onAvatarSuccess = (avatar: string) => {
    if (admin.data) {
        admin.data.avatar = avatar
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
            const enc = await passwordRef.value!.getEncryptedPayload()
            if (enc.password) body.password = enc.password
            headers = enc.headers
        }
        await accountApi.updateProfile(body, headers)
        ElMessage.success('保存成功')
        form.password = ''
        if (admin.data) {
            admin.data.nickname = body.nickname || admin.data.username
        }
        await load()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '保存失败')
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
                    <ga-upload-avatar v-model="avatarPath" :initial="avatarInitial" @success="onAvatarSuccess" />
                </el-form-item>
                <el-form-item label="用户名">
                    <el-input v-model="form.username" disabled />
                </el-form-item>
                <el-form-item label="昵称">
                    <el-input v-model="form.nickname" maxlength="64" show-word-limit placeholder="显示名称" />
                </el-form-item>
                <el-form-item label="新密码">
                    <ga-password-input ref="passwordRef" v-model="form.password" placeholder="不修改请留空" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>
