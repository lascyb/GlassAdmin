<script setup lang="ts">
import * as adminApi from '@/apis/auth/admin'
import type { RoleRow } from '@/apis/auth/role'
import * as roleApi from '@/apis/auth/role'
import GaDialogForm from '@/components/ga-dialog-form/index.vue'
import GaPasswordInput from '@/components/ga-password-input/index.vue'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref, useTemplateRef } from 'vue'

const props = defineProps<{
    id?: number
}>()

const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const passwordRef = useTemplateRef<InstanceType<typeof GaPasswordInput>>('passwordRef')

const form = reactive<{
    id?: number
    username: string
    password: string
    nickname: string
    role_id: number | undefined
    status: number
}>({
    id: undefined,
    username: '',
    password: '',
    nickname: '',
    role_id: undefined,
    status: 1,
})

const loading = ref(false)
const saving = ref(false)
const title = ref('添加管理员')

/** roleTree 角色树（供 el-tree-select） */
const roleTree = ref<RoleRow[]>([])

const loadRoles = async () => {
    roleTree.value = await roleApi.fetchTree()
}

const loadDetail = async () => {
    if (props?.id) {
        title.value = '编辑管理员'
        loading.value = true
        try {
            const d = await adminApi.fetchRead(props.id)
            Object.assign(form, {
                id: d.id,
                username: d.username,
                password: '',
                nickname: d.nickname ?? '',
                role_id: d.role_id ?? undefined,
                status: d.status,
            })
        } finally {
            loading.value = false
        }
    } else {
        title.value = '添加管理员'
        Object.assign(form, {
            id: undefined,
            username: '',
            password: '',
            nickname: '',
            role_id: undefined,
            status: 1,
        })
    }
}

onMounted(async () => {
    await loadRoles()
    await loadDetail()
})

const submit = async () => {
    if (!form.username.trim()) {
        ElMessage.warning('请输入用户名')
        return
    }
    if (!form.id && !form.password) {
        ElMessage.warning('请设置登录密码')
        return
    }
    if (form.role_id === undefined || form.role_id <= 0) {
        ElMessage.warning('请选择角色')
        return
    }
    saving.value = true
    try {
        if (form.id) {
            const body: {
                nickname: string
                role_id: number
                status: number
                password?: string
            } = {
                nickname: form.nickname.trim(),
                role_id: form.role_id,
                status: form.status,
            }
            if (form.password) {
                const enc = await passwordRef.value!.getEncryptedPayload()
                if (enc.password) body.password = enc.password
                await adminApi.updateAdmin(form.id, body, enc.headers)
            } else {
                await adminApi.updateAdmin(form.id, body)
            }
        } else {
            const enc = await passwordRef.value!.getEncryptedPayload()
            if (!enc.password || !enc.headers) {
                ElMessage.error('请设置登录密码')
                return
            }
            await adminApi.saveAdmin(
                {
                    username: form.username.trim(),
                    nickname: form.nickname.trim(),
                    role_id: form.role_id,
                    status: form.status,
                    password: enc.password,
                },
                enc.headers,
            )
        }
        ElMessage.success('已保存')
        emit('saved')
        show.value = false
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '保存失败')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <ga-dialog-form v-model="show" :title="title" :loading="loading" :saving="saving" @confirm="submit">
        <el-form :model="form" label-position="right" label-width="96px">
            <el-form-item label="用户名" required>
                <el-input
                    v-model.trim="form.username"
                    placeholder="3~64 位字母、数字或下划线"
                    :disabled="!!form.id"
                    maxlength="64"
                    show-word-limit
                    clearable
                />
            </el-form-item>
            <el-form-item :label="form.id ? '新密码' : '密码'" :required="!form.id">
                <ga-password-input
                    ref="passwordRef"
                    v-model="form.password"
                    :placeholder="form.id ? '留空表示不修改' : '至少 6 位'"
                />
            </el-form-item>
            <el-form-item label="昵称">
                <el-input v-model.trim="form.nickname" placeholder="可选" maxlength="100" show-word-limit clearable />
            </el-form-item>
            <el-form-item label="角色" required>
                <el-tree-select
                    v-model="form.role_id"
                    class="w-full"
                    :data="roleTree"
                    :props="{ value: 'id', label: 'name', children: 'children' }"
                    node-key="id"
                    placeholder="请选择角色"
                    clearable
                    filterable
                    check-strictly
                    default-expand-all
                    :render-after-expand="false"
                />
            </el-form-item>
            <el-form-item label="状态">
                <el-radio-group v-model="form.status">
                    <el-radio :value="1">正常</el-radio>
                    <el-radio :value="0">禁用</el-radio>
                </el-radio-group>
            </el-form-item>
        </el-form>
    </ga-dialog-form>
</template>
