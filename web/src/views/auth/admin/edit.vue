<script setup lang="ts">
import * as adminApi from '@/apis/auth/admin'
import type { RoleRow } from '@/apis/auth/role'
import * as roleApi from '@/apis/auth/role'
import GaPasswordInput from '@/components/ga-password-input/index.vue'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'

const props = defineProps<{
    id?: number
}>()

const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive<{
    id?: number
    username: string
    password: string
    nickname: string
    role_ids: number[]
    status: number
}>({
    id: undefined,
    username: '',
    password: '',
    nickname: '',
    role_ids: [],
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
                role_ids: d.role_ids ?? d.roles?.map((r) => r.id) ?? [],
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
            role_ids: [],
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
    if (form.role_ids.length === 0) {
        ElMessage.warning('请至少选择一个角色')
        return
    }
    saving.value = true
    try {
        if (form.id) {
            const body: {
                nickname: string
                role_ids: number[]
                status: number
                password?: string
            } = {
                nickname: form.nickname.trim(),
                role_ids: form.role_ids,
                status: form.status,
            }
            if (form.password) {
                body.password = form.password
            }
            await adminApi.updateAdmin(form.id, body)
        } else {
            await adminApi.saveAdmin({
                username: form.username.trim(),
                nickname: form.nickname.trim(),
                role_ids: form.role_ids,
                status: form.status,
                password: form.password,
            })
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
    <el-dialog v-model="show" draggable  :title="title" width="520px" destroy-on-close lock-scroll top="8vh" :closeOnClickModal="false">
        <el-skeleton :loading="loading" animated :rows="5">
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
                        v-model="form.password"
                        :placeholder="form.id ? '留空表示不修改' : '至少 6 位'"
                    />
                </el-form-item>
                <el-form-item label="昵称">
                    <el-input v-model.trim="form.nickname" placeholder="可选" maxlength="100" show-word-limit clearable />
                </el-form-item>
                <el-form-item label="角色" required>
                    <el-tree-select
                        v-model="form.role_ids"
                        class="w-full"
                        :data="roleTree"
                        :props="{ value: 'id', label: 'name', children: 'children' }"
                        node-key="id"
                        placeholder="请选择角色"
                        multiple
                        collapse-tags
                        collapse-tags-tooltip
                        :max-collapse-tags="10"
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
        </el-skeleton>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
