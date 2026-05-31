<script setup lang="ts">
import type { RuleRow } from '@/apis/auth/rule'
import * as ruleApi from '@/apis/auth/rule'
import IconPicker from '@/components/icon-picker/index.vue'
import { ElMessage } from 'element-plus'
import { computed, reactive, ref } from 'vue'

const props = defineProps<{ scope: 'backend' | 'frontend' }>()

const emit = defineEmits<{ success: [] }>()

/** RuleEditForm 与 auth_rule 表字段对齐的编辑表单（前后端 scope 共用） */
type RuleEditForm = {
    id?: number
    scope: 'backend' | 'frontend'
    type: string
    pid: number
    name: string
    path: string
    method: string
    component: string
    icon: string | null
    sort: number
    status: number
}

const visible = ref(false)
const title = ref('')
const saving = ref(false)

/** 父级树数据 */
const treeData = ref<RuleRow[]>([])

/** 递归移除当前节点及其子节点，防止选自己为父级造成循环 */
function removeNodeAndChildren(nodes: RuleRow[], targetId: number): RuleRow[] {
    const result: RuleRow[] = []
    for (const node of nodes) {
        if (node.id === targetId) continue
        const children = node.children?.length ? removeNodeAndChildren(node.children, targetId) : []
        result.push({ ...node, children })
    }
    return result
}

const parentTreeData = computed(() => {
    if (!form.id) return treeData.value
    return removeNodeAndChildren(treeData.value, form.id)
})

const form = reactive<RuleEditForm>({
    scope: props.scope,
    type: 'menu',
    pid: 0,
    name: '',
    path: '',
    method: 'GET',
    component: '',
    icon: null,
    sort: 0,
    status: 1,
})

/**
 * ruleEditResetForAdd 新增时重置表单
 */
function ruleEditResetForAdd(pid: number, type: string) {
    form.id = undefined
    form.scope = props.scope
    form.type = type
    form.pid = pid
    form.name = ''
    form.path = ''
    form.method = props.scope === 'backend' && type === 'api' ? 'GET' : ''
    form.component = props.scope === 'frontend' && type === 'view' ? '' : ''
    form.icon = null
    form.sort = 0
    form.status = 1
}

/**
 * ruleEditAddTitle 新增弹窗标题
 */
function ruleEditAddTitle(pid: number, type: string) {
    if (props.scope === 'backend') {
        const label = type === 'menu' ? '目录' : '接口'
        return pid === 0 ? `新增${label}` : `在节点下新增${label}`
    }
    return `新增${type}`
}

/**
 * loadTree 加载当前 scope 的规则树（供父级选择）
 */
async function loadTree() {
    try {
        treeData.value = await ruleApi.fetchTree(props.scope)
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载规则树失败')
    }
}

/**
 * openAdd 打开新增（type：backend 为 menu|api，frontend 为 menu|view|btn）
 */
function openAdd(pid: number, type: string) {
    title.value = ruleEditAddTitle(pid, type)
    ruleEditResetForAdd(pid, type)
    loadTree()
    visible.value = true
}

/**
 * ruleEditMapRow 行数据写入表单
 */
function ruleEditMapRow(row: RuleRow) {
    form.id = row.id
    form.scope = props.scope
    form.type = row.type
    form.pid = row.pid
    form.name = row.name
    form.path = row.path ?? ''
    form.method = row.method ?? 'GET'
    form.component = row.component ?? ''
    form.icon = row.icon ?? null
    form.sort = row.sort
    form.status = row.status
}

/**
 * openEdit 打开编辑
 */
function openEdit(row: RuleRow) {
    title.value = '编辑'
    ruleEditMapRow(row)
    loadTree()
    visible.value = true
}

/**
 * ruleEditValidate 提交前校验
 */
function ruleEditValidate(): string | void {
    if (!form.name.trim()) return '请输入名称'
    if (props.scope === 'backend' && form.type === 'api' && !form.path.trim()) return '接口须填写 path'
}

/**
 * ruleEditSubmit 保存
 */
async function ruleEditSubmit() {
    const hint = ruleEditValidate()
    if (hint) {
        ElMessage.warning(hint)
        return
    }
    saving.value = true
    try {
        let payload: Record<string, unknown>
        if (props.scope === 'backend') {
            payload = {
                scope: 'backend',
                type: form.type,
                pid: form.pid,
                name: form.name.trim(),
                path: form.path.trim() || null,
                method: form.type === 'api' ? form.method.trim() || null : null,
                sort: form.sort,
                status: form.status,
            }
        } else {
            payload = {
                scope: 'frontend',
                type: form.type,
                pid: form.pid,
                name: form.name.trim(),
                path: form.path.trim() || null,
                method: null,
                component: form.type === 'view' ? form.component.trim() || null : null,
                icon: form.icon?.trim() ? form.icon.trim() : null,
                sort: form.sort,
                status: form.status,
            }
        }
        if (form.id) {
            await ruleApi.updateRule(form.id, payload)
            ElMessage.success('已保存')
        } else {
            await ruleApi.saveRule(payload)
            ElMessage.success('已创建')
        }
        visible.value = false
        emit('success')
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '保存失败')
    } finally {
        saving.value = false
    }
}

defineExpose({ openAdd, openEdit })
</script>

<template>
    <el-dialog draggable v-model="visible" :title="title" width="520px" destroy-on-close>
        <el-form label-width="88px">
            <el-form-item label="类型">
                <el-tag>{{ form.type }}</el-tag>
            </el-form-item>
            <el-form-item label="父级">
                <el-tree-select
                    v-model="form.pid"
                    class="w-full"
                    :data="parentTreeData"
                    :props="{ value: 'id', label: 'name', children: 'children' }"
                    node-key="id"
                    placeholder="留空为顶级"
                    clearable
                    filterable
                    check-strictly
                    default-expand-all
                    :render-after-expand="false"
                />
            </el-form-item>
            <el-form-item label="名称" required>
                <el-input v-model.trim="form.name" maxlength="100" show-word-limit />
            </el-form-item>

            <template v-if="scope === 'backend'">
                <template v-if="form.type === 'api'">
                    <el-form-item label="path" required>
                        <el-input v-model.trim="form.path" placeholder="如 auth/rule/tree" />
                    </el-form-item>
                    <el-form-item label="method">
                        <el-select v-model="form.method" class="w-full">
                            <el-option label="GET" value="GET" />
                            <el-option label="POST" value="POST" />
                            <el-option label="PUT" value="PUT" />
                            <el-option label="DELETE" value="DELETE" />
                            <el-option label="PATCH" value="PATCH" />
                            <el-option label="*" value="*" />
                        </el-select>
                    </el-form-item>
                </template>
            </template>

            <template v-else>
                <el-form-item label="path">
                    <el-input v-model.trim="form.path" placeholder="路由 path 片段" />
                </el-form-item>
                <el-form-item v-if="form.type === 'view'" label="component">
                    <el-input v-model.trim="form.component" placeholder="如 auth/rule/index.vue" />
                </el-form-item>
                <el-form-item label="图标">
                    <IconPicker v-model="form.icon" />
                </el-form-item>
            </template>

            <el-form-item label="排序">
                <el-input-number v-model="form.sort" :step="1" class="w-full" />
            </el-form-item>
            <el-form-item label="状态">
                <el-radio-group v-model="form.status">
                    <el-radio :value="1">启用</el-radio>
                    <el-radio :value="0">禁用</el-radio>
                </el-radio-group>
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="ruleEditSubmit">保存</el-button>
        </template>
    </el-dialog>
</template>
