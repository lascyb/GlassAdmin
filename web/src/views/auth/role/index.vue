<script setup lang="ts">
import type { RoleRow } from '@/apis/auth/role'
import * as roleApi from '@/apis/auth/role'
import GaSearchBar from '@/components/ga-search-bar/index.vue'
import GaTreeTable from '@/components/ga-tree-table/index.vue'
import { useTreeCrud } from '@/composables/useTreeCrud'
import AdminIndex from '@/views/auth/admin/index.vue'
import EditView from '@/views/auth/role/edit.vue'
import { Plus, User } from '@element-plus/icons-vue'
import { computed, onMounted, reactive, ref } from 'vue'

const { treeData, loading, editController, load, remove, openEdit, openAdd, openAddChild, onSaved } =
    useTreeCrud<RoleRow>(
        () => roleApi.fetchTree(),
        (id) => roleApi.deleteRole(id),
        {
            getDeleteMessage: () => '确定删除该角色？',
            deleteSuccessMsg: '已删除',
        },
    )

const keyword = ref('')
const advanced = reactive({
    code: '',
    name: '',
    status: undefined as number | undefined,
})

/** applied 点击查询后生效的条件 */
const applied = reactive({
    keyword: '',
    code: '',
    name: '',
    status: undefined as number | undefined,
})

const usersDialog = reactive<{
    show: boolean
    roleId?: number
    roleName: string
}>({
    show: false,
    roleId: undefined,
    roleName: '',
})

/** roleMatches 当前节点是否命中筛选（不含子树） */
const roleMatches = (row: RoleRow): boolean => {
    const kw = applied.keyword.trim().toLowerCase()
    if (kw) {
        const hay = `${row.code} ${row.name} ${row.desc ?? ''}`.toLowerCase()
        if (!hay.includes(kw)) {
            return false
        }
    }
    const code = applied.code.trim().toLowerCase()
    if (code && !row.code.toLowerCase().includes(code)) {
        return false
    }
    const name = applied.name.trim().toLowerCase()
    if (name && !row.name.toLowerCase().includes(name)) {
        return false
    }
    if (applied.status === 0 || applied.status === 1) {
        if (row.status !== applied.status) {
            return false
        }
    }
    return true
}

/** filterRoleTree 保留命中节点及其祖先，便于树表展示 */
const filterRoleTree = (nodes: RoleRow[]): RoleRow[] => {
    const out: RoleRow[] = []
    for (const node of nodes) {
        const children = node.children?.length ? filterRoleTree(node.children) : []
        if (roleMatches(node) || children.length > 0) {
            out.push({
                ...node,
                children: children.length > 0 ? children : node.children?.length ? [] : undefined,
            })
        }
    }
    return out
}

const hasFilter = computed(() => {
    return (
        applied.keyword.trim() !== '' ||
        applied.code.trim() !== '' ||
        applied.name.trim() !== '' ||
        applied.status === 0 ||
        applied.status === 1
    )
})

const displayTree = computed(() => {
    if (!hasFilter.value) {
        return treeData.value
    }
    return filterRoleTree(treeData.value)
})

const onSearch = () => {
    applied.keyword = keyword.value
    applied.code = advanced.code
    applied.name = advanced.name
    applied.status = advanced.status
}

const resetAdvanced = () => {
    advanced.code = ''
    advanced.name = ''
    advanced.status = undefined
    applied.code = ''
    applied.name = ''
    applied.status = undefined
    applied.keyword = ''
}

onMounted(load)

const onEdit = (row: RoleRow) => openEdit(row.id)
const onAddChild = (row: RoleRow) => openAddChild(row.id)

const openUsers = (row: RoleRow) => {
    usersDialog.roleId = row.id
    usersDialog.roleName = row.name
    usersDialog.show = true
}
</script>

<template>
    <div class="p-3">
        <el-card style="min-height: max(calc(100vh - 100px), 400px)">
            <ga-search-bar
                v-model:keyword="keyword"
                :loading="loading"
                keyword-placeholder="识别码 / 名称 / 描述"
                @refresh="load"
                @search="onSearch"
                @reset="resetAdvanced"
            >
                <template #advanced>
                    <el-form-item label="识别码">
                        <el-input
                            v-model.trim="advanced.code"
                            clearable
                            placeholder="模糊匹配"
                            class="!w-40"
                        />
                    </el-form-item>
                    <el-form-item label="名称">
                        <el-input
                            v-model.trim="advanced.name"
                            clearable
                            placeholder="模糊匹配"
                            class="!w-40"
                        />
                    </el-form-item>
                    <el-form-item label="状态">
                        <el-select v-model="advanced.status" clearable placeholder="全部" class="!w-28">
                            <el-option :value="1" label="启用" />
                            <el-option :value="0" label="禁用" />
                        </el-select>
                    </el-form-item>
                </template>
                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="openAdd">新增角色</el-button>
                </template>
            </ga-search-bar>
            <ga-tree-table
                :data="displayTree"
                :loading="loading"
                :action-width="260"
                @edit="onEdit"
                @add-child="onAddChild"
                @delete="remove"
                size="small"
                border
            >
                <template #prepend-actions="{ row }">
                    <el-button
                        title="查看用户"
                        plain
                        type="success"
                        :icon="User"
                        @click="openUsers(row)"
                    />
                </template>
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="识别码" prop="code" />
                <el-table-column label="名称" prop="name" />
                <el-table-column label="描述" prop="desc" show-overflow-tooltip />
            </ga-tree-table>
            <edit-view
                v-if="editController.show"
                v-model="editController.show"
                :id="editController.id"
                :pid="editController.pid"
                @saved="onSaved"
            />
            <el-dialog
                v-model="usersDialog.show"
                :title="`角色「${usersDialog.roleName}」的用户`"
                width="90%"
                top="5vh"
                destroy-on-close
                append-to-body
            >
                <AdminIndex
                    v-if="usersDialog.show && usersDialog.roleId"
                    :locked-role-id="usersDialog.roleId"
                    embedded
                />
            </el-dialog>
        </el-card>
    </div>
</template>
