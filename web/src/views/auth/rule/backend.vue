<script setup lang="ts">
import * as ruleApi from '@/apis/auth/rule'
import type { RuleRow } from '@/apis/auth/rule'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { onMounted, reactive, ref, shallowRef } from 'vue'

const treeData = shallowRef<RuleRow[]>([])
const loading = ref(false)

const dialog = reactive<{
    visible: boolean
    title: string
    saving: boolean
    form: {
        id?: number
        scope: 'backend'
        type: 'menu' | 'api'
        pid: number
        name: string
        path: string
        method: string
        sort: number
        status: number
    }
}>({
    visible: false,
    title: '',
    saving: false,
    form: {
        scope: 'backend',
        type: 'menu',
        pid: 0,
        name: '',
        path: '',
        method: 'GET',
        sort: 0,
        status: 1,
    },
})

const load = async () => {
    loading.value = true
    try {
        treeData.value = await ruleApi.fetchTree('backend')
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载失败')
    } finally {
        loading.value = false
    }
}

onMounted(load)

const openAdd = (pid: number, type: 'menu' | 'api') => {
    dialog.title = pid === 0 ? `新增${type === 'menu' ? '目录' : '接口'}` : `在节点下新增${type === 'menu' ? '目录' : '接口'}`
    Object.assign(dialog.form, {
        id: undefined,
        scope: 'backend' as const,
        type,
        pid,
        name: '',
        path: type === 'api' ? '' : '',
        method: type === 'api' ? 'GET' : '',
        sort: 0,
        status: 1,
    })
    dialog.visible = true
}

const openEdit = (row: RuleRow) => {
    dialog.title = '编辑'
    Object.assign(dialog.form, {
        id: row.id,
        scope: 'backend',
        type: row.type as 'menu' | 'api',
        pid: row.pid,
        name: row.name,
        path: row.path ?? '',
        method: row.method ?? 'GET',
        sort: row.sort,
        status: row.status,
    })
    dialog.visible = true
}

const submit = async () => {
    if (!dialog.form.name.trim()) {
        ElMessage.warning('请输入名称')
        return
    }
    if (dialog.form.type === 'api' && !dialog.form.path.trim()) {
        ElMessage.warning('接口须填写 path')
        return
    }
    dialog.saving = true
    try {
        const payload = {
            scope: 'backend',
            type: dialog.form.type,
            pid: dialog.form.pid,
            name: dialog.form.name.trim(),
            path: dialog.form.path.trim() || null,
            method: dialog.form.type === 'api' ? dialog.form.method.trim() || null : null,
            sort: dialog.form.sort,
            status: dialog.form.status,
        }
        if (dialog.form.id) {
            await ruleApi.updateRule(dialog.form.id, payload)
            ElMessage.success('已保存')
        } else {
            await ruleApi.saveRule(payload)
            ElMessage.success('已创建')
        }
        dialog.visible = false
        await load()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '保存失败')
    } finally {
        dialog.saving = false
    }
}

const remove = async (row: RuleRow) => {
    try {
        await ElMessageBox.confirm(`确定删除「${row.name}」？`, '确认', { type: 'warning' })
    } catch {
        return
    }
    try {
        await ruleApi.deleteRule(row.id)
        ElMessage.success('已删除')
        await load()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '删除失败')
    }
}
</script>

<template>
    <div v-loading="loading" class="min-h-[360px]">
        <div class="mb-3 flex flex-wrap gap-2">
            <el-button type="primary" :icon="Plus"  @click="openAdd(0, 'menu')">新增根目录</el-button>
            <el-button type="success" :icon="Plus"  @click="openAdd(0, 'api')">新增根接口</el-button>
            <el-button  @click="load">刷新</el-button>
        </div>
        <el-tree
            :data="treeData"
            node-key="id"
            default-expand-all
            :props="{ children: 'children', label: 'name' }"
        >
            <template #default="{ data }">
                <span class="flex flex-1 flex-wrap items-center gap-2 py-1 pr-2">
                    <el-tag  :type="data.type === 'api' ? 'warning' : 'info'">{{ data.type }}</el-tag>
                    <span>{{ data.name }}</span>
                    <span v-if="data.type === 'api' && data.path" class="text-xs text-gray-500"
                        >{{ data.method }} {{ data.path }}</span
                    >
                    <span class="ml-auto flex gap-1">
                        <el-button  text type="primary" :icon="Plus" @click.stop="openAdd(data.id, 'menu')"
                            >子目录</el-button
                        >
                        <el-button  text type="success" :icon="Plus" @click.stop="openAdd(data.id, 'api')"
                            >子接口</el-button
                        >
                        <el-button  text type="primary" :icon="Edit" @click.stop="openEdit(data)"
                            >编辑</el-button
                        >
                        <el-button  text type="danger" :icon="Delete" @click.stop="remove(data)"
                            >删除</el-button
                        >
                    </span>
                </span>
            </template>
        </el-tree>

        <el-dialog v-model="dialog.visible" :title="dialog.title" width="520px" destroy-on-close>
            <el-form label-width="88px" >
                <el-form-item label="类型">
                    <el-tag>{{ dialog.form.type }}</el-tag>
                </el-form-item>
                <el-form-item label="名称" required>
                    <el-input v-model.trim="dialog.form.name" maxlength="100" show-word-limit />
                </el-form-item>
                <template v-if="dialog.form.type === 'api'">
                    <el-form-item label="path" required>
                        <el-input v-model.trim="dialog.form.path" placeholder="如 auth/rule/tree" />
                    </el-form-item>
                    <el-form-item label="method">
                        <el-select v-model="dialog.form.method" class="w-full">
                            <el-option label="GET" value="GET" />
                            <el-option label="POST" value="POST" />
                            <el-option label="PUT" value="PUT" />
                            <el-option label="DELETE" value="DELETE" />
                            <el-option label="*" value="*" />
                        </el-select>
                    </el-form-item>
                </template>
                <el-form-item label="排序">
                    <el-input-number v-model="dialog.form.sort" :step="1" class="w-full" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-radio-group v-model="dialog.form.status">
                        <el-radio :value="1">启用</el-radio>
                        <el-radio :value="0">禁用</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialog.visible = false">取消</el-button>
                <el-button type="primary" :loading="dialog.saving" @click="submit">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>
