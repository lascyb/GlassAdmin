<script setup lang="ts">
import * as roleApi from '@/apis/auth/role'
import type { RoleRow } from '@/apis/auth/role'
import { Delete, Edit } from '@element-plus/icons-vue'
import AddChildSvg from '@/assets/icons/add_child.svg'
import EditView from '@/views/auth/role/edit.vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { onMounted, reactive, shallowRef } from 'vue'

const data = shallowRef<RoleRow[]>([])
const editController = reactive<{
    show?: boolean
    id?: number
    pid?: number
}>({})

const load = async () => {
    try {
        data.value = await roleApi.fetchTree()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载失败')
    }
}

onMounted(load)

const clickEdit = (id: number) => {
    Object.assign(editController, {
        show: true,
        pid: undefined,
        id: id,
    })
}
const clickDel = async (id: number) => {
    try {
        await ElMessageBox.confirm('确定删除该角色？', '确认', { type: 'warning' })
    } catch {
        return
    }
    try {
        await roleApi.deleteRole(id)
        ElMessage.success('已删除')
        await load()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '删除失败')
    }
}
const clickAddChild = (pid: number) => {
    Object.assign(editController, {
        show: true,
        pid: pid,
        id: undefined,
    })
}

const onSaved = () => {
    editController.show = false
    load()
}
</script>

<template>
    <el-card style="min-height: max(calc(100vh - 100px), 400px)">
        <div class="mb-3">
            <el-button type="primary"  @click="Object.assign(editController, { show: true, id: undefined, pid: undefined })"
                >新增角色</el-button
            >
            <el-button  @click="load">刷新</el-button>
        </div>
        <el-table
            :data="data"

            border
            row-key="id"
            default-expand-all
            stripe
            highlight-current-row
            current-row-key="id"
            :tree-props="{ children: 'children' }"
        >
            <el-table-column label="ID" prop="id" width="80" />
            <el-table-column label="名称" prop="name" min-width="140" />
            <el-table-column label="描述" prop="desc" min-width="180" show-overflow-tooltip />
            <el-table-column label="操作" align="right" width="190px">
                <template #default="scope">
                    <el-button title="编辑"  plain type="primary" :icon="Edit" @click="clickEdit(scope.row.id)" />
                    <el-button
                        title="添加子角色"

                        plain
                        type="warning"
                        :icon="AddChildSvg"
                        @click="clickAddChild(scope.row.id)"
                    />
                    <el-button title="删除"  plain type="danger" :icon="Delete" @click="clickDel(scope.row.id)" />
                </template>
            </el-table-column>
        </el-table>
        <edit-view
            v-if="editController.show"
            v-model="editController.show"
            :id="editController.id"
            :pid="editController.pid"
            @saved="onSaved"
        />
    </el-card>
</template>
