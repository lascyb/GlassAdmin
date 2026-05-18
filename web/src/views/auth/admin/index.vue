<script setup lang="ts">
import type { AdminRow } from '@/apis/auth/admin'
import * as adminApi from '@/apis/auth/admin'
import EditView from '@/views/auth/admin/edit.vue'
import { Delete, Edit, Plus, Refresh, Search } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'

const list = ref<AdminRow[]>([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const keyword = ref('')
const loading = ref(false)

const editController = reactive<{
    show?: boolean
    id?: number
}>({})

const load = async () => {
    loading.value = true
    try {
        const res = await adminApi.fetchList({
            page: page.value, limit: limit.value, keyword: keyword.value.trim() || undefined,
        })
        list.value = res.list
        total.value = res.total
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载失败')
    } finally {
        loading.value = false
    }
}

onMounted(load)

const onSearch = () => {
    page.value = 1
    load()
}

const clickEdit = (id: number) => {
    Object.assign(editController, {show: true, id})
}

const clickAdd = () => {
    Object.assign(editController, {show: true, id: undefined})
}

const clickDel = async (row: AdminRow) => {
    try {
        await ElMessageBox.confirm(`确定删除管理员「${row.username}」？`, '确认', {type: 'warning'})
    } catch {
        return
    }
    try {
        await adminApi.deleteAdmin(row.id)
        ElMessage.success('已删除')
        await load()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '删除失败')
    }
}

const onSaved = () => {
    editController.show = false
    load()
}
const search = ref(true)
</script>

<template>
    <el-card style="min-height: max(calc(100vh - 100px), 400px)" body-style="padding:0;">
        <el-form inline class="p-2" v-if="search">
            <el-form-item label="用户名">
                <el-input></el-input>
            </el-form-item>
            <el-form-item label="昵称">
                <el-input></el-input>
            </el-form-item>
            <el-form-item label="状态">
                <el-select placeholder="请选择状态">
                    <el-option value="1" label="正常"></el-option>
                    <el-option value="0" label="禁用"></el-option>
                </el-select>
            </el-form-item>
        </el-form>
        <el-divider></el-divider>
        <div class=" flex flex-wrap items-center gap-2 p-2">
            <el-button :disabled="loading" @click="load" :icon="Refresh"></el-button>
            <el-input v-model="keyword" placeholder="用户名 / 昵称" clearable class="max-w-xs" @keyup.enter="onSearch"/>
            <el-button type="primary" @click="onSearch">查询</el-button>
            <el-button type="primary" :icon="Plus" @click="clickAdd">新增管理员</el-button>
            <el-button :icon="Search" @click="()=>{search = !search}"></el-button>
        </div>
        <el-table v-loading="loading" :data="list" border stripe highlight-current-row>
            <el-table-column prop="id" label="ID" width="72"/>
            <el-table-column prop="username" label="用户名" min-width="120"/>
            <el-table-column prop="nickname" label="昵称" min-width="120" show-overflow-tooltip/>
            <el-table-column label="角色" min-width="120" show-overflow-tooltip>
                <template #default="{ row }">
                    {{ row.role?.name ?? '—' }}
                </template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="88">
                <template #default="{ row }">
                    <el-tag :type="row.status === 1 ? 'success' : 'info'">{{
                            row.status === 1 ? '正常' : '禁用'
                        }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column prop="create_time" label="创建时间" width="170"/>
            <el-table-column label="操作" fixed="right" width="140">
                <template #default="{ row }">
                    <el-button title="编辑" plain type="primary" :icon="Edit" @click="clickEdit(row.id)"/>
                    <el-button title="删除" plain type="danger" :icon="Delete" @click="clickDel(row)"/>
                </template>
            </el-table-column>
        </el-table>
        <div class="mt-3 flex justify-end">
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
        <edit-view v-if="editController.show" v-model="editController.show" :id="editController.id" @saved="onSaved"/>
    </el-card>
</template>
