<script setup lang="ts">
import type { AdminRow } from '@/apis/auth/admin'
import * as adminApi from '@/apis/auth/admin'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaStatusTag from '@/components/ga-status-tag/index.vue'
import GaTablePage from '@/components/ga-table-page/index.vue'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { formatUnixTime } from '@/utils/formatUnixTime'
import { ADMIN_STATUS_MAP } from '@/views/auth/admin/constants'
import EditView from '@/views/auth/admin/edit.vue'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { computed, reactive, ref } from 'vue'

const keyword = ref('')

const advanced = reactive({
    username: '',
    nickname: '',
    status: undefined as number | undefined,
})

const queryParams = computed(() => ({
    keyword: keyword.value.trim() || undefined,
    username: advanced.username.trim() || undefined,
    nickname: advanced.nickname.trim() || undefined,
    status: advanced.status === 0 || advanced.status === 1 ? advanced.status : undefined,
}))

const { list, total, page, limit, loading, load, search } = usePaginatedList<AdminRow>(
    (params) => adminApi.fetchList(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{
    show?: boolean
    id?: number
}>({})

const clickEdit = (id: number) => {
    Object.assign(editController, { show: true, id })
}

const clickAdd = () => {
    Object.assign(editController, { show: true, id: undefined })
}

const clickDel = (row: AdminRow) => {
    void confirm(`确定删除管理员「${row.username}」？`, () => adminApi.deleteAdmin(row.id))
}

const onSaved = () => {
    editController.show = false
    void load()
}

/** oauthProviderLabel 三方登录展示文案 */
const oauthProviderLabel = (code: string): string => {
    if (code === 'feishu') return '飞书'
    return code
}

/** avatarInitial 无头像时的文字 */
const avatarInitial = (row: AdminRow): string => {
    const raw = (row.nickname || row.username || '?').trim()
    return raw ? raw.slice(0, 1).toUpperCase() : '?'
}

/** resetAdvanced 重置高级筛选条件 */
const resetAdvanced = () => {
    advanced.username = ''
    advanced.nickname = ''
    advanced.status = undefined
}
</script>

<template>
    <ga-table-page
        v-model:page="page"
        v-model:limit="limit"
        :loading="loading"
        :list="list"
        :total="total"
        @load="load"
    >
        <template #toolbar>
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
                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增管理员</el-button>
                </template>
            </ga-search-bar>
        </template>
        <el-table-column prop="id" label="ID" width="72" />
        <el-table-column label="头像" width="72" align="center">
            <template #default="{ row }">
                <el-avatar :size="36" :src="row.avatar || undefined">
                    {{ avatarInitial(row) }}
                </el-avatar>
            </template>
        </el-table-column>
        <el-table-column prop="username" label="用户名" min-width="120" />
        <el-table-column prop="nickname" label="昵称" min-width="120" show-overflow-tooltip />
        <el-table-column label="三方登录" width="120">
            <template #default="{ row }">
                <template v-if="row.oauth_providers?.length">
                    <el-tag
                        v-for="p in row.oauth_providers"
                        :key="p"
                        size="small"
                        round
                        class="mr-1"
                    >{{ oauthProviderLabel(p) }}</el-tag>
                </template>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>
        <el-table-column label="角色" min-width="200">
            <template #default="{ row }">
                <template v-if="row.roles?.length">
                    <el-tag v-for="r in row.roles" :key="r.id"  round class="mr-1 mb-1">{{ r.name }}</el-tag>
                </template>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>
        <el-table-column label="上次登录" width="170">
            <template #default="{ row }">
                {{ formatUnixTime(row.last_login_time) }}
            </template>
        </el-table-column>
        <el-table-column label="创建时间" width="170">
            <template #default="{ row }">{{ formatUnixTime(row.create_time) }}</template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="88">
            <template #default="{ row }">
                <ga-status-tag :value="row.status" :map="ADMIN_STATUS_MAP" />
            </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" width="140">
            <template #default="{ row }">
                <el-button v-auth="'edit'" title="编辑" plain type="primary" :icon="Edit" @click="clickEdit(row.id)" />
                <el-button v-auth="'del'" title="删除" plain type="danger" :icon="Delete" @click="clickDel(row)" />
            </template>
        </el-table-column>
        <template #footer>
            <edit-view
                v-if="editController.show"
                v-model="editController.show"
                :id="editController.id"
                @saved="onSaved"
            />
        </template>
    </ga-table-page>
</template>
