<script setup lang="ts">
import type { RuleRow } from '@/apis/auth/rule'
import * as ruleApi from '@/apis/auth/rule'
import IconPicker from '@/components/icon-picker/index.vue'
import MenuIcon from '@/components/menu-icon/index.vue'
import BindBackendApiDialog from '@/views/auth/rule/BindBackendApiDialog.vue'
import { Delete, Edit, Link, Plus } from '@element-plus/icons-vue'
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
        scope: 'frontend'
        type: 'menu' | 'view' | 'btn'
        pid: number
        name: string
        path: string
        component: string
        icon: string | null
        sort: number
        status: number
    }
}>({
    visible: false,
    title: '',
    saving: false,
    form: {
        scope: 'frontend',
        type: 'menu',
        pid: 0,
        name: '',
        path: '',
        component: '',
        icon: null,
        sort: 0,
        status: 1,
    },
})

const bindDlg = reactive<{
    visible: boolean
    row?: RuleRow
}>({
    visible: false,
})

const load = async () => {
    loading.value = true
    try {
        treeData.value = await ruleApi.fetchTree('frontend')
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载失败')
    } finally {
        loading.value = false
    }
}

onMounted(load)

const openAdd = (pid: number, type: 'menu' | 'view' | 'btn') => {
    dialog.title = `新增${type}`
    Object.assign(dialog.form, {
        id: undefined,
        scope: 'frontend' as const,
        type,
        pid,
        name: '',
        path: '',
        component: type === 'view' ? '' : '',
        icon: null,
        sort: 0,
        status: 1,
    })
    dialog.visible = true
}

const openEdit = (row: RuleRow) => {
    dialog.title = '编辑'
    Object.assign(dialog.form, {
        id: row.id,
        scope: 'frontend',
        type: row.type as 'menu' | 'view' | 'btn',
        pid: row.pid,
        name: row.name,
        path: row.path ?? '',
        component: row.component ?? '',
        icon: row.icon ?? null,
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
    dialog.saving = true
    try {
        const payload = {
            scope: 'frontend',
            type: dialog.form.type,
            pid: dialog.form.pid,
            name: dialog.form.name.trim(),
            path: dialog.form.path.trim() || null,
            method: null,
            component:
                dialog.form.type === 'view' ? (dialog.form.component.trim() || null) : null,
            icon: dialog.form.icon?.trim() ? dialog.form.icon.trim() : null,
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

const openBind = (row: RuleRow) => {
    bindDlg.row = row
    bindDlg.visible = true
}
</script>

<template>
    <div v-loading="loading" class="min-h-[360px]">
        <div class="mb-3 flex flex-wrap gap-2">
            <el-button type="primary" :icon="Plus"  @click="openAdd(0, 'menu')">新增根目录</el-button>
            <el-button type="success" :icon="Plus"  @click="openAdd(0, 'view')">新增根页面</el-button>
            <el-button  @click="load">刷新</el-button>
        </div>
        <el-tree :data="treeData" node-key="id" default-expand-all :props="{ children: 'children', label: 'name' }">
            <template #default="{ data }">
                <span class="flex flex-1 flex-wrap items-center gap-2 py-1 pr-2">
                    <el-tag>{{ data.type }}</el-tag>
                    <MenuIcon v-if="data.icon" :icon="data.icon" class="shrink-0" />
                    <span>{{ data.name }}</span>
                    <span v-if="data.path" class="text-xs text-gray-500">{{ data.path }}</span>
                    <span class="ml-auto flex flex-wrap gap-1">
                        <el-button  text type="primary" :icon="Plus" @click.stop="openAdd(data.id, 'menu')"
                            >子目录</el-button
                        >
                        <el-button  text type="success" :icon="Plus" @click.stop="openAdd(data.id, 'view')"
                            >子页面</el-button
                        >
                        <el-button  text type="warning" :icon="Plus" @click.stop="openAdd(data.id, 'btn')"
                            >子按钮</el-button
                        >
                        <el-button
                            v-if="data.type === 'view' || data.type === 'btn'"

                            text
                            type="info"
                            :icon="Link"
                            @click.stop="openBind(data)"
                            >配置接口</el-button
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
                <el-form-item label="path">
                    <el-input v-model.trim="dialog.form.path" placeholder="路由 path 片段" />
                </el-form-item>
                <el-form-item v-if="dialog.form.type === 'view'" label="component">
                    <el-input v-model.trim="dialog.form.component" placeholder="如 auth/rule/index.vue" />
                </el-form-item>
                <el-form-item label="图标">
                    <IconPicker v-model="dialog.form.icon" />
                </el-form-item>
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

        <bind-backend-api-dialog
            v-model="bindDlg.visible"
            :frontend-rule-id="bindDlg.row?.id"
            :title-name="bindDlg.row?.name"
            @success="load"
        />
    </div>
</template>
