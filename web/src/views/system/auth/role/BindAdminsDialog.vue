<script setup lang="ts">
import { showError } from '@/apis/request'
import type { AdminRow } from '@/apis/system/auth/admin'
import * as adminApi from '@/apis/system/auth/admin'
import type { RoleRow } from '@/apis/system/auth/role'
import * as roleApi from '@/apis/system/auth/role'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaStatusTag from '@/components/ga-status-tag/index.vue'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { ADMIN_STATUS_MAP, OAUTH_PROVIDER_OPTIONS } from '@/views/system/auth/admin/constants'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, reactive, ref } from 'vue'

const emit = defineEmits<{ saved: [] }>()

const visible = ref(false)
const saving = ref(false)
const roleId = ref(0)
const roleName = ref('')

/** advanced 高级筛选条件 */
const advanced = reactive({
    username: '',
    nickname: '',
    status: undefined as number | undefined,
    oauth_provider: undefined as string | undefined,
})

const keyword = ref('')

/** queryParams 列表查询条件；exclude_role_id 只列出尚未拥有该角色的管理员 */
const queryParams = computed(() => ({
    keyword: keyword.value.trim() || undefined,
    username: advanced.username.trim() || undefined,
    nickname: advanced.nickname.trim() || undefined,
    status: advanced.status === 0 || advanced.status === 1 ? advanced.status : undefined,
    oauth_provider: advanced.oauth_provider || undefined,
    exclude_role_id: roleId.value > 0 ? roleId.value : undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<AdminRow>(
    (params) => adminApi.fetchList(params),
    { params: queryParams, immediate: false },
)

/** selected 跨页保留的勾选管理员 id */
const selectedIds = ref<number[]>([])

/** tableKey 每次打开重建表格，清空 reserve-selection 残留勾选 */
const tableKey = ref(0)

const open = async (row: RoleRow) => {
    roleId.value = row.id
    roleName.value = row.name
    keyword.value = ''
    advanced.username = ''
    advanced.nickname = ''
    advanced.status = undefined
    advanced.oauth_provider = undefined
    selectedIds.value = []
    page.value = 1
    tableKey.value += 1
    visible.value = true
    await load()
}

defineExpose({ open })

/** onSelectionChange 记录当前勾选（reserve-selection 已保留其他页的勾选） */
const onSelectionChange = (rows: AdminRow[]) => {
    selectedIds.value = rows.map((r) => r.id)
}

const resetAdvanced = () => {
    advanced.username = ''
    advanced.nickname = ''
    advanced.status = undefined
    advanced.oauth_provider = undefined
}

/** avatarInitial 无头像时的文字 */
const avatarInitial = (row: AdminRow): string => {
    const raw = (row.nickname || row.username || '?').trim()
    return raw ? raw.slice(0, 1).toUpperCase() : '?'
}

const oauthProviderLabel = (code: string): string => (code === 'feishu' ? '飞书' : code)

const submit = async () => {
    if (selectedIds.value.length === 0) {
        ElMessage.warning('请先勾选管理员')
        return
    }
    try {
        await ElMessageBox.confirm(
            `确定把角色「${roleName.value}」追加绑定到 ${selectedIds.value.length} 个管理员？已拥有该角色的将自动跳过。`,
            '确认绑定',
            { type: 'warning' },
        )
    } catch {
        return
    }
    saving.value = true
    try {
        const res = await roleApi.assignAdmins(roleId.value, selectedIds.value)
        ElMessage.success(
            `新增 ${res.added} 人，跳过 ${res.skipped} 人（已拥有该角色）；目标用户刷新页面或重新登录后生效`,
        )
        visible.value = false
        emit('saved')
    } catch (e: unknown) {
        showError(e, '绑定失败')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <el-dialog
        v-model="visible"
        draggable
        :title="`绑定用户 - ${roleName}`"
        width="90%"
        top="5vh"
        destroy-on-close
        append-to-body
        lock-scroll
        :close-on-click-modal="false"
    >
        <div class="mb-2 text-xs leading-5 text-ga-text-secondary">
            只追加该角色，不会解除管理员已有的其他角色；列表中已排除当前拥有该角色的人。
        </div>
        <ga-search-bar
            v-model:keyword="keyword"
            :loading="loading"
            keyword-placeholder="用户名 / 昵称"
            @refresh="load"
            @search="search"
            @reset="resetAdvanced"
        >
            <template #advanced>
                <el-form-item label="用户名">
                    <el-input
                        v-model.trim="advanced.username"
                        clearable
                        placeholder="模糊匹配"
                        class="!w-40"
                    />
                </el-form-item>
                <el-form-item label="昵称">
                    <el-input
                        v-model.trim="advanced.nickname"
                        clearable
                        placeholder="模糊匹配"
                        class="!w-40"
                    />
                </el-form-item>
                <el-form-item label="三方登录">
                    <el-select v-model="advanced.oauth_provider" clearable placeholder="全部" class="!w-28">
                        <el-option
                            v-for="item in OAUTH_PROVIDER_OPTIONS"
                            :key="item.value"
                            :value="item.value"
                            :label="item.label"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="advanced.status" clearable placeholder="全部" class="!w-28">
                        <el-option
                            v-for="(item, value) in ADMIN_STATUS_MAP"
                            :key="value"
                            :value="Number(value)"
                            :label="item.label"
                        />
                    </el-select>
                </el-form-item>
            </template>
        </ga-search-bar>
        <el-table
            :key="tableKey"
            v-loading="loading"
            :data="list"
            row-key="id"
            border
            stripe
            highlight-current-row
            height="52vh"
            empty-text="没有可绑定的管理员"
            @selection-change="onSelectionChange"
        >
            <el-table-column type="selection" reserve-selection width="46" />
            <el-table-column prop="id" label="ID" width="72" />
            <el-table-column label="头像" width="72" align="center">
                <template #default="{ row }">
                    <el-avatar :size="28" :src="row.avatar || undefined">
                        {{ avatarInitial(row) }}
                    </el-avatar>
                </template>
            </el-table-column>
            <el-table-column prop="username" label="用户名" min-width="120" />
            <el-table-column prop="nickname" label="昵称" min-width="120" show-overflow-tooltip />
            <el-table-column label="角色" min-width="200">
                <template #default="{ row }">
                    <template v-if="row.roles?.length">
                        <el-tag v-for="r in row.roles" :key="r.id" round class="mr-1 mb-1">{{ r.name }}</el-tag>
                    </template>
                    <span v-else class="text-ga-text-placeholder">—</span>
                </template>
            </el-table-column>
            <el-table-column label="三方登录" width="110">
                <template #default="{ row }">
                    <template v-if="row.oauth_providers?.length">
                        <el-tag v-for="p in row.oauth_providers" :key="p" size="small" round class="mr-1">
                            {{ oauthProviderLabel(p) }}
                        </el-tag>
                    </template>
                    <span v-else class="text-ga-text-placeholder">—</span>
                </template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="88">
                <template #default="{ row }">
                    <ga-status-tag :value="row.status" :map="ADMIN_STATUS_MAP" />
                </template>
            </el-table-column>
        </el-table>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
            <span class="text-sm text-ga-text-secondary">已选 {{ selectedIds.length }} 人</span>
            <el-pagination
                v-model:current-page="page"
                v-model:page-size="limit"
                :total="total"
                :page-sizes="[10, 20, 50]"
                layout="total, sizes, prev, pager, next"
                background
                @current-change="load"
                @size-change="load"
            />
        </div>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button
                type="primary"
                :loading="saving"
                :disabled="selectedIds.length === 0"
                @click="submit"
            >绑定 {{ selectedIds.length }} 人</el-button>
        </template>
    </el-dialog>
</template>
