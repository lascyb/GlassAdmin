<script setup lang="ts">
import { showError } from '@/apis/request'
import type { RulePayload, RuleRow } from '@/apis/system/auth/rule'
import * as ruleApi from '@/apis/system/auth/rule'
import { hasIframeMethod, listIframeMethods } from '@/layouts/router-view/iframe/iframeMethods'
import { views } from '@/router/views'
import { normalizeViewComponent } from '@/utils/viewComponent'
import BindBackendApiPanel from '@/views/system/auth/rule/BindBackendApiPanel.vue'
import Picker from '@components/ga-icon/picker.vue'
import { ElMessage } from 'element-plus'
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps<{ scope: 'backend' | 'frontend' }>()

const emit = defineEmits<{ success: [] }>()

/** {0:授权可见,1:展示全部时可发现,2:不入侧栏（仅目录）} */
const VISIBILITY_SIDEBAR = 0
const VISIBILITY_CATALOG = 1
const VISIBILITY_HIDDEN = 2

/** VIEW_TYPES 需要 component 的页面类型 */
const VIEW_TYPES = ['view', 'route']

/** RuleEditForm 与 system_auth_rule 表字段对齐的编辑表单（前后端 scope 共用） */
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
    visibility: number
    keep_alive: number
    description: string
}

/** showVisibilityFields 侧栏可见性相关字段；route 恒不入侧栏故不展示 */
const showVisibilityFields = computed(
    () =>
        props.scope === 'frontend' &&
        (form.type === 'menu' || form.type === 'view' || form.type === 'link' || form.type === 'iframe'),
)

/** allowHidden 仅目录可设为不入侧栏（页面用 route 类型） */
const allowHidden = computed(() => form.type === 'menu')

/** showKeepAliveField 仅前端页面规则可配缓存 */
const showKeepAliveField = computed(
    () => props.scope === 'frontend' && VIEW_TYPES.includes(form.type),
)

const visible = ref(false)
const title = ref('')
const saving = ref(false)
const backendRuleIds = ref<number[]>([])

/** treeData 父级树数据 */
const treeData = ref<RuleRow[]>([])

/** showBackendApiPanel 是否展示关联后端 API 区域 */
const showBackendApiPanel = computed(
    () => props.scope === 'frontend' && (VIEW_TYPES.includes(form.type) || form.type === 'btn'),
)

/** dialogWidth 弹窗宽度：含接口勾选时加宽 */
const dialogWidth = computed(() => (showBackendApiPanel.value ? '700px' : '700px'))

/** iframeMethods 已落地的 iframe 鉴权目录 */
const iframeMethods = listIframeMethods()

/** viewComponentOptions 可选页面组件（来自 import.meta.glob） */
const viewComponentOptions = computed(() => {
    const map = new Map<string, string>()
    for (const key of Object.keys(views)) {
        const value = normalizeViewComponent(key)
        if (value) map.set(value, value)
    }
    const current = normalizeViewComponent(form.component.trim()) ?? form.component.trim()
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
    visibility: VISIBILITY_SIDEBAR,
    keep_alive: 1,
    description: '',
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
    form.component = ''
    form.icon = null
    form.sort = 0
    form.status = 1
    form.visibility = VISIBILITY_SIDEBAR
    form.keep_alive = 1
    form.description = ''
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
        showError(e, '加载规则树失败')
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
    form.visibility = row.visibility ?? VISIBILITY_SIDEBAR
    form.keep_alive = row.keep_alive === 0 ? 0 : 1
    form.description = row.description ?? ''
}

/**
 * openEdit 打开编辑
 */
async function openEdit(row: RuleRow) {
    title.value = '编辑'
    ruleEditMapRow(row)
    backendRuleIds.value = []
    if (props.scope === 'frontend' && (VIEW_TYPES.includes(row.type) || row.type === 'btn')) {
        try {
            const detail = await ruleApi.fetchRead(row.id)
            backendRuleIds.value = detail.backend_rule_ids ?? []
        } catch (e: unknown) {
            showError(e, '加载规则详情失败')
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
    if (props.scope === 'frontend' && (form.type === 'link' || form.type === 'iframe')) {
        if (!form.path.trim()) return '请填写路由 path'
        if (!form.component.trim()) return form.type === 'link' ? '请填写外链地址' : '请填写内嵌地址'
        if (form.type === 'iframe') {
            const method = form.method.trim()
            if (method !== '' && !hasIframeMethod(method)) {
                return '未注册的 iframe 鉴权方式'
            }
        }
    }
}

/**
 * ruleEditSubmit 保存（frontend view/route/btn 时 backend_rule_ids 随 save/update 一并提交）
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
                method:
                    form.type === 'iframe' && form.method.trim() !== ''
                        ? form.method.trim()
                        : null,
                component:
                    VIEW_TYPES.includes(form.type) || form.type === 'link' || form.type === 'iframe'
                        ? form.component.trim() || null
                        : null,
                icon: form.icon?.trim() ? form.icon.trim() : null,
                sort: form.sort,
                status: form.status,
                visibility: showVisibilityFields.value ? form.visibility : VISIBILITY_SIDEBAR,
                keep_alive: showKeepAliveField.value ? form.keep_alive : 0,
                description: showVisibilityFields.value
                    ? form.description.trim() || null
                    : null,
            }
            if (VIEW_TYPES.includes(form.type) || form.type === 'btn') {
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
        showError(e, '保存失败')
    } finally {
        saving.value = false
    }
}

watch(
    () => form.type,
    (type, prev) => {
        if (props.scope !== 'frontend') return
        if (type === 'menu' || type === 'link' || type === 'iframe') {
            backendRuleIds.value = []
        }
        if (type === 'iframe' && form.method && !hasIframeMethod(form.method)) {
            form.method = ''
        }
        if (type !== 'iframe') {
            form.method = ''
        }
        if (!allowHidden.value && form.visibility === VISIBILITY_HIDDEN) {
            form.visibility = VISIBILITY_SIDEBAR
        }
        // 在页面组件路径与 URL 之间切换时清空 component，避免脏数据
        if (
            prev &&
            prev !== type &&
            ((VIEW_TYPES.includes(prev) && (type === 'link' || type === 'iframe')) ||
                ((prev === 'link' || prev === 'iframe') && VIEW_TYPES.includes(type)) ||
                ((prev === 'link' || prev === 'iframe') && (type === 'menu' || type === 'btn')))
        ) {
            form.component = ''
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
                <template v-if="scope === 'frontend'">
                    <el-radio-group v-model="form.type">
                        <el-radio value="menu">目录</el-radio>
                        <el-radio value="view">页面</el-radio>
                        <el-radio value="route">路由</el-radio>
                        <el-radio value="btn">按钮</el-radio>
                        <el-radio value="link">外链</el-radio>
                        <el-radio value="iframe">内嵌</el-radio>
                    </el-radio-group>
                    <div v-if="form.type === 'route'" class="mt-1 text-xs text-[var(--el-text-color-secondary)]">
                        路由：与页面相同，但不出现在侧栏（详情页、编辑页等）
                    </div>
                </template>
                <template v-else>
                    <el-radio-group v-model="form.type">
                        <el-radio value="menu">目录</el-radio>
                        <el-radio value="api">接口</el-radio>
                    </el-radio-group>
                </template>
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
                        <el-input v-model.trim="form.path" placeholder="如 system.auth.Rule/tree"/>
                    </el-form-item>
                </template>
            </template>

            <template v-else>
                <el-form-item
                    label="path"
                    :required="form.type === 'link' || form.type === 'iframe'"
                >
                    <el-input
                        v-model.trim="form.path"
                        :placeholder="
                            form.type === 'link' || form.type === 'iframe'
                                ? '侧栏路由 path，如 /docs/help'
                                : form.type === 'btn'
                                  ? '权限标识 path'
                                  : '路由 path 片段'
                        "
                    />
                </el-form-item>
                <el-form-item v-if="VIEW_TYPES.includes(form.type)" label="component">
                    <el-select
                        v-model="form.component"
                        class="w-full"
                        filterable
                        allow-create
                        clearable
                        default-first-option
                        placeholder="选择或输入，如 system/auth/rule/index.vue"
                    >
                        <el-option
                            v-for="item in viewComponentOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item v-if="form.type === 'link'" label="外链地址" required>
                    <el-input
                        v-model.trim="form.component"
                        placeholder="如 https://example.com"
                    />
                </el-form-item>
                <el-form-item v-if="form.type === 'iframe'" label="内嵌地址" required>
                    <el-input
                        v-model.trim="form.component"
                        placeholder="业务路径或完整 URL"
                    />
                </el-form-item>
                <el-form-item v-if="form.type === 'iframe'" label="鉴权方式">
                    <div class="flex w-full flex-col items-start">
                        <el-radio-group v-model="form.method">
                            <el-radio value="">直链</el-radio>
                            <el-radio
                                v-for="id in iframeMethods"
                                :key="id"
                                :value="id"
                            >{{ id }}</el-radio>
                        </el-radio-group>
                        <div class="mt-1 text-xs text-[var(--el-text-color-secondary)]">
                            直链：component 填完整 URL；选择鉴权方式时填该系统约定的目标路径
                        </div>
                    </div>
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
            <el-form-item v-if="showKeepAliveField" label="页面缓存">
                <div class="flex w-full flex-col items-start">
                    <el-switch
                        v-model="form.keep_alive"
                        :active-value="1"
                        :inactive-value="0"
                    />
                    <div class="mt-1 text-xs text-[var(--el-text-color-secondary)]">
                        开启后离开再进入仍保留筛选、滚动等状态；关闭则每次重新挂载。
                    </div>
                </div>
            </el-form-item>
            <el-form-item v-if="showVisibilityFields" label="可见性">
                <div class="flex w-full flex-col items-start">
                    <el-radio-group v-model="form.visibility">
                        <el-radio :value="VISIBILITY_SIDEBAR">授权可见</el-radio>
                        <el-radio :value="VISIBILITY_CATALOG">可发现</el-radio>
                        <el-radio v-if="allowHidden" :value="VISIBILITY_HIDDEN">不入侧栏</el-radio>
                    </el-radio-group>
                    <div class="mt-1 text-xs text-[var(--el-text-color-secondary)]">
                        授权可见：授权后在侧栏显示；可发现：未授权时也能在「展示全部」中出现<template v-if="allowHidden">；不入侧栏：隐藏本目录，子节点提升到同级</template>
                    </div>
                </div>
            </el-form-item>
            <el-form-item v-if="showVisibilityFields" label="描述">
                <el-input
                    v-model="form.description"
                    type="textarea"
                    :rows="3"
                    maxlength="500"
                    show-word-limit
                    placeholder="无权限页展示的功能说明"
                />
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="ruleEditSubmit">保存</el-button>
        </template>
    </el-dialog>
</template>
