<script setup lang="ts">
import { showError } from '@/apis/request'
import * as adminApi from '@/apis/system/auth/admin'
import type { RoleRow } from '@/apis/system/auth/role'
import * as roleApi from '@/apis/system/auth/role'
import GaPasswordInput from '@/components/ga-password-input/index.vue'
import { useSystemConfig } from '@/pinia/system/config'
import { LOGIN_METHOD_OPTIONS, loginMethodLabel } from '@/views/system/auth/admin/constants'
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref, watch } from 'vue'

const props = defineProps<{
    id?: number
}>()

const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const systemConfig = useSystemConfig()

const form = reactive<{
    id?: number
    username: string
    password: string
    nickname: string
    role_ids: number[]
    status: number
    follow_default: boolean
    login_methods: string[]
}>({
    id: undefined,
    username: '',
    password: '',
    nickname: '',
    role_ids: [],
    status: 1,
    follow_default: true,
    login_methods: [],
})

const loading = ref(false)
const saving = ref(false)
const title = ref('添加管理员')

/** roleTree 角色树（供 el-tree-select） */
const roleTree = ref<RoleRow[]>([])

/** defaultLoginMethods 系统默认登录方式 */
const defaultLoginMethods = computed(() =>
    systemConfig.getStringArray('admin_default_login_methods', ['password', 'feishu']),
)

/** isEdit 编辑已有账号（可开关飞书）；新建无法绑定飞书用户 */
const isEdit = computed(() => !!form.id)

/** methodOptions 新建仅密码；编辑可选密码/飞书 */
const methodOptions = computed(() =>
    isEdit.value
        ? LOGIN_METHOD_OPTIONS
        : LOGIN_METHOD_OPTIONS.filter((item) => item.value === 'password'),
)

/** effectiveLoginMethods 即将生效的集合 */
const effectiveLoginMethods = computed(() =>
    form.follow_default ? defaultLoginMethods.value : form.login_methods,
)

/** passwordRequired 仅当生效含密码登录时，新建须填密码；否则随机占位 */
const passwordRequired = computed(
    () => !isEdit.value && effectiveLoginMethods.value.includes('password'),
)

const defaultMethodsHint = computed(() => {
    const labels = defaultLoginMethods.value.map(loginMethodLabel)
    return labels.length > 0 ? labels.join('、') : '（未配置，将无法登录）'
})

const loadRoles = async () => {
    roleTree.value = await roleApi.fetchTree()
}

const loadDetail = async () => {
    await systemConfig.load()
    if (props?.id) {
        title.value = '编辑管理员'
        loading.value = true
        try {
            const d = await adminApi.fetchRead(props.id)
            const follow = d.login_methods == null
            Object.assign(form, {
                id: d.id,
                username: d.username,
                password: '',
                nickname: d.nickname ?? '',
                role_ids: d.role_ids ?? d.roles?.map((r) => r.id) ?? [],
                status: d.status,
                follow_default: follow,
                login_methods: follow
                    ? [...defaultLoginMethods.value]
                    : [...(d.login_methods ?? [])],
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
            follow_default: true,
            // 自定义预填：默认含密码则勾选，否则空（占位）
            login_methods: defaultLoginMethods.value.includes('password') ? ['password'] : [],
        })
    }
}

watch(
    () => form.follow_default,
    (follow) => {
        if (follow) {
            form.login_methods = [...defaultLoginMethods.value]
        } else if (!form.id) {
            form.login_methods = defaultLoginMethods.value.includes('password') ? ['password'] : []
        }
    },
)

onMounted(async () => {
    await loadRoles()
    await loadDetail()
})

const submit = async () => {
    if (!form.username.trim()) {
        ElMessage.warning('请输入用户名')
        return
    }
    // 编辑自定义须至少一种；新建允许不勾选密码（占位账号）
    if (isEdit.value && !form.follow_default && form.login_methods.length === 0) {
        ElMessage.warning('请至少选择一种登录方式')
        return
    }
    if (passwordRequired.value && !form.password) {
        ElMessage.warning('请设置登录密码')
        return
    }
    if (form.role_ids.length === 0) {
        ElMessage.warning('请至少选择一个角色')
        return
    }
    // 新建无飞书绑定；自定义去掉 feishu
    let login_methods: string[] | null = form.follow_default ? null : [...form.login_methods]
    if (!form.id && login_methods) {
        login_methods = login_methods.filter((m) => m !== 'feishu')
    }
    saving.value = true
    try {
        if (form.id) {
            const body: {
                nickname: string
                role_ids: number[]
                status: number
                login_methods: string[] | null
                password?: string
            } = {
                nickname: form.nickname.trim(),
                role_ids: form.role_ids,
                status: form.status,
                login_methods,
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
                login_methods,
                password: form.password || undefined,
            })
        }
        ElMessage.success('已保存')
        emit('saved')
        show.value = false
    } catch (e: unknown) {
        showError(e, '保存失败')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <el-dialog v-model="show" draggable  :title="title" width="520px" destroy-on-close lock-scroll top="8vh" :closeOnClickModal="false">
        <el-skeleton :loading="loading" animated :rows="5">
            <el-form :model="form" label-position="right" label-width="108px">
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
                <el-form-item label="登录方式" required>
                    <div class="w-full">
                        <el-switch
                            v-model="form.follow_default"
                            inline-prompt
                            active-text="跟随默认"
                            inactive-text="自定义"
                        />
                        <div v-if="form.follow_default" class="mt-2 text-sm text-ga-text-placeholder">
                            系统默认：{{ defaultMethodsHint }}
                        </div>
                        <el-checkbox-group v-else v-model="form.login_methods" class="mt-2 block">
                            <el-checkbox
                                v-for="item in methodOptions"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </el-checkbox>
                        </el-checkbox-group>
                        <div
                            v-if="!isEdit"
                            class="mt-2 text-sm text-ga-text-placeholder"
                        >
                            飞书用户可由授权登录自动创建；未开通密码登录时将生成随机密码（占位账号）。
                        </div>
                    </div>
                </el-form-item>
                <el-form-item :label="isEdit ? '新密码' : '密码'" :required="passwordRequired">
                    <ga-password-input
                        v-model="form.password"
                        :placeholder="
                            isEdit
                                ? '留空表示不修改'
                                : passwordRequired
                                  ? '至少 6 位'
                                  : '未开通密码登录，将自动生成占位密码'
                        "
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
