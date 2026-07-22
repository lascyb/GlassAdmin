<script setup lang="ts">
import type { RulePayload, RuleRow } from '@/apis/auth/rule'
import * as ruleApi from '@/apis/auth/rule'
import { views } from '@/router/views'
import BindBackendApiPanel from '@/views/auth/rule/BindBackendApiPanel.vue'
import Picker from '@components/ga-icon/picker.vue'
import { ElMessage } from 'element-plus'
import { computed, reactive, ref, watch } from 'vue'

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
const backendRuleIds = ref<number[]>([])

/** treeData 父级树数据 */
const treeData = ref<RuleRow[]>([])

/** showBackendApiPanel 是否展示关联后端 API 区域 */
const showBackendApiPanel = computed(
    () => props.scope === 'frontend' && (form.type === 'view' || form.type === 'btn'),
)

/** dialogWidth 弹窗宽度：含接口勾选时加宽 */
const dialogWidth = computed(() => (showBackendApiPanel.value ? '720px' : '520px'))

/** routeOnly 前端规则是否仅作为路由（method=route） */
const routeOnly = computed({
    get: () => form.method === 'route',
    set: (v: boolean) => {
        form.method = v ? 'route' : ''
    },
})

/**
 * toViewComponentValue glob key 转为 auth_rule.component（相对 views/）
 */
function toViewComponentValue(globKey: string): string {
    return globKey.replace(/^\/src\/views\//, '').replace(/^@\/views\//, '')
}

/** viewComponentOptions 可选页面组件（来自 import.meta.glob） */
const viewComponentOptions = computed(() => {
    const map = new Map<string, string>()
    for (const key of Object.keys(views)) {
        const value = toViewComponentValue(key)
        if (value) map.set(value, value)
    }
    const current = form.component.trim()
    if (current) map.set(current, current)
    return [...map.values()]
        .sort((a, b) => a.localeCompare(b))
        .map((value) => ({ label: value, value }))
})

/** 递归移除当前节点及其子节点，防止选自己为父级造成循环 */
function removeNodeAndChildren(nodes: RuleRow[], targetId: number): RuleRow[] {
    const result: RuleRow[] = []
    for (const node of nodes) {
        if (node.id === targetId) continue
        const children = node.children?.length ? removeNodeAndChildren(node.children, targetId) : []
        result.push({...node, children})
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
 * inferDefaultType 根据父节点类型推断新建默认 type
 */
function inferDefaultType(pid: number, parentType?: string): string {
    if (props.scope === 'backend') {
        if (pid === 0) return 'menu'
        return 'api'
    }
    if (pid === 0) return 'menu'
    if (parentType === 'menu') return 'view'
    if (parentType === 'view') return 'btn'
    return 'btn'
}

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
    backendRuleIds.value = []
}

/**
 * ruleEditAddTitle 新增弹窗标题
 */
function ruleEditAddTitle(pid: number): string {
    if (props.scope === 'backend') {
        return pid === 0 ? '新增规则' : '新增子节点'
    }
    return pid === 0 ? '新增规则' : '新增子节点'
}

/**
 * loadTree 加载当前 scope 的规则树（供父级选择）
 */
async function loadTree() {
    try {
        treeData.value = await ruleApi.fetchTree(props.scope)
        treeData.value.unshift({pid: 0, scope: "", type: "", sort: 0, status: 1, id: 0, name: "作为顶级节点"})
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载规则树失败')
    }
}

/**
 * openAdd 打开新增
 */
function openAdd(pid: number, parentType?: string) {
    const type = inferDefaultType(pid, parentType)
    title.value = ruleEditAddTitle(pid)
    ruleEditResetForAdd(pid, type)
    void loadTree()
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
    form.method = row.method ?? (props.scope === 'backend' ? 'GET' : '')
    form.component = row.component ?? ''
    form.icon = row.icon ?? null
    form.sort = row.sort
    form.status = row.status
}

/**
 * openEdit 打开编辑
 */
async function openEdit(row: RuleRow) {
    title.value = '编辑'
    ruleEditMapRow(row)
    backendRuleIds.value = []
    if (props.scope === 'frontend' && (row.type === 'view' || row.type === 'btn')) {
        try {
            const detail = await ruleApi.fetchRead(row.id)
            backendRuleIds.value = detail.backend_rule_ids ?? []
        } catch (e: unknown) {
            ElMessage.error(e instanceof Error ? e.message : '加载规则详情失败')
            return
        }
    }
    void loadTree()
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
 * ruleEditSubmit 保存（frontend view/btn 时 backend_rule_ids 随 save/update 一并提交）
 */
async function ruleEditSubmit() {
    const hint = ruleEditValidate()
    if (hint) {
        ElMessage.warning(hint)
        return
    }
    saving.value = true
    try {
        let payload: RulePayload
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
                method: form.method === 'route' ? 'route' : null,
                component: form.type === 'view' ? form.component.trim() || null : null,
                icon: form.icon?.trim() ? form.icon.trim() : null,
                sort: form.sort,
                status: form.status,
            }
            if (form.type === 'view' || form.type === 'btn') {
                payload.backend_rule_ids = backendRuleIds.value
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

watch(
    () => form.type,
    (type) => {
        if (props.scope === 'frontend' && type === 'menu') {
            backendRuleIds.value = []
        }
        if (props.scope === 'frontend' && type === 'btn' && form.method === 'route') {
            form.method = ''
        }
    },
)

defineExpose({openAdd, openEdit})
</script>

<template>
    <el-dialog draggable v-model="visible" :title="title" :width="dialogWidth" destroy-on-close :closeOnClickModal="false">
        <el-form label-width="88px">
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
            <el-form-item label="类型">
                <el-radio-group v-if="scope === 'frontend'" v-model="form.type">
                    <el-radio value="menu">目录</el-radio>
                    <el-radio value="view">页面</el-radio>
                    <el-radio value="btn">按钮</el-radio>
                </el-radio-group>
                <el-radio-group v-else v-model="form.type">
                    <el-radio value="menu">目录</el-radio>
                    <el-radio value="api">接口</el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item label="名称" required>
                <el-input v-model.trim="form.name" maxlength="100" show-word-limit/>
            </el-form-item>

            <template v-if="scope === 'backend'">
                <template v-if="form.type === 'api'">
                    <el-form-item label="method">
                        <el-radio-group v-model="form.method">
                            <el-radio-button value="GET">GET</el-radio-button>
                            <el-radio-button value="POST">POST</el-radio-button>
                            <el-radio-button value="PUT">PUT</el-radio-button>
                            <el-radio-button value="DELETE">DELETE</el-radio-button>
                            <el-radio-button value="PATCH">PATCH</el-radio-button>
                            <el-radio-button value="*">*</el-radio-button>
                        </el-radio-group>
                    </el-form-item>
                    <el-form-item label="path" required>
                        <el-input v-model.trim="form.path" placeholder="如 auth.Rule/tree"/>
                    </el-form-item>
                </template>
            </template>

            <template v-else>
                <el-form-item label="path">
                    <el-input v-model.trim="form.path" placeholder="路由 path 片段"/>
                </el-form-item>
                <el-form-item v-if="form.type === 'view'" label="component">
                    <el-select
                        v-model="form.component"
                        class="w-full"
                        filterable
                        allow-create
                        clearable
                        default-first-option
                        placeholder="选择或输入，如 auth/rule/index.vue"
                    >
                        <el-option
                            v-for="item in viewComponentOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item v-if="form.type !== 'btn'" label="图标">
                    <Picker v-model="form.icon"/>
                </el-form-item>
            </template>

            <el-form-item v-if="showBackendApiPanel" label="关联接口">
                <BindBackendApiPanel v-model="backendRuleIds"/>
            </el-form-item>

            <el-form-item label="排序">
                <el-input-number v-model="form.sort" :step="1" class="w-full"/>
            </el-form-item>
            <el-form-item label="状态">
                <el-radio-group v-model="form.status">
                    <el-radio :value="1">启用</el-radio>
                    <el-radio :value="0">禁用</el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item v-if="scope === 'frontend' && (form.type === 'menu' || form.type === 'view')" label="仅作为路由">
                <el-checkbox v-model="routeOnly">是（不入侧栏菜单）</el-checkbox>
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="ruleEditSubmit">保存</el-button>
        </template>
    </el-dialog>
</template>
