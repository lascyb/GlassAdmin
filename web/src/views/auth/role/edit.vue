<script setup lang="ts">
import * as roleApi from '@/apis/auth/role'
import type { RoleRow } from '@/apis/auth/role'
import * as ruleApi from '@/apis/auth/rule'
import type { RuleRow } from '@/apis/auth/rule'
import { EditPen } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { nextTick, onMounted, reactive, ref } from 'vue'

const props = defineProps<{
    id?: number
    pid?: number
}>()

const show = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ saved: [] }>()

const form = reactive<{
    id?: number
    pid?: number
    name: string
    desc: string
    sort: number
    status: number
}>({
    id: undefined,
    pid: undefined,
    name: '',
    desc: '',
    sort: 0,
    status: 1,
})

const loading = ref(false)
const saving = ref(false)
const title = ref('添加')

const flatRoles = ref<{ id: number; label: string }[]>([])

const permTree = ref<RuleRow[]>([])
const permTreeRef = ref()

const flattenRoles = (rows: RoleRow[], prefix = ''): { id: number; label: string }[] => {
    const out: { id: number; label: string }[] = []
    for (const r of rows) {
        out.push({ id: r.id, label: prefix + r.name })
        if (r.children?.length) {
            out.push(...flattenRoles(r.children, prefix + '　'))
        }
    }
    return out
}

const loadMeta = async () => {
    const tree = await roleApi.fetchTree()
    flatRoles.value = flattenRoles(tree)
    permTree.value = await ruleApi.fetchTree('frontend')
}

const loadDetail = async () => {
    if (props?.id) {
        title.value = '编辑'
        loading.value = true
        try {
            const d = await roleApi.fetchRead(props.id)
            Object.assign(form, {
                id: d.id,
                pid: d.pid,
                name: d.name,
                desc: d.desc ?? '',
                sort: d.sort,
                status: d.status,
            })
            await nextTick()
            permTreeRef.value?.setCheckedKeys(d.rule_ids ?? [], false)
        } finally {
            loading.value = false
        }
    } else if (props?.pid) {
        title.value = '添加子角色'
        Object.assign(form, {
            id: undefined,
            pid: props.pid,
            name: '',
            desc: '',
            sort: 0,
            status: 1,
        })
        await nextTick()
        permTreeRef.value?.setCheckedKeys([], false)
    } else {
        title.value = '添加角色'
        Object.assign(form, {
            id: undefined,
            pid: undefined,
            name: '',
            desc: '',
            sort: 0,
            status: 1,
        })
        await nextTick()
        permTreeRef.value?.setCheckedKeys([], false)
    }
}

onMounted(async () => {
    await loadMeta()
    await loadDetail()
})

const collectRuleIds = (): number[] => {
    const tree = permTreeRef.value
    if (!tree) return []
    const a = (tree.getCheckedKeys(false) as number[]) ?? []
    const b = (tree.getHalfCheckedKeys() as number[]) ?? []
    return [...new Set([...a, ...b])]
}

const submit = async () => {
    if (!form.name.trim()) {
        ElMessage.warning('请输入角色名')
        return
    }
    saving.value = true
    try {
        let roleId = form.id
        if (roleId) {
            await roleApi.updateRole(roleId, {
                pid: form.pid ?? 0,
                name: form.name.trim(),
                desc: form.desc.trim(),
                sort: form.sort,
                status: form.status,
            })
        } else {
            const r = await roleApi.saveRole({
                pid: form.pid ?? 0,
                name: form.name.trim(),
                desc: form.desc.trim(),
                sort: form.sort,
                status: form.status,
            })
            roleId = r.id
        }
        await roleApi.assignRules(roleId, collectRuleIds())
        ElMessage.success('已保存')
        emit('saved')
        show.value = false
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '保存失败')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <el-dialog v-model="show" draggable :title="title" destroy-on-close lock-scroll top="8vh" width="640px">
        <el-scrollbar class="pr-2" max-height="70vh">
            <el-skeleton :loading="loading" animated :rows="6" :throttle="{ leading: 300, trailing: 300, initVal: true }">
                <el-form :model="form" label-position="right" label-width="96px">
                    <el-form-item label="上级角色">
                        <el-select
                            v-model="form.pid"
                            clearable
                            filterable
                            placeholder="留空为顶级角色（树形仅展示，无权限继承）"
                            class="w-full"
                        >
                            <template #prefix>
                                <el-icon><EditPen /></el-icon>
                            </template>
                            <el-option
                                v-for="opt in flatRoles"
                                :key="opt.id"
                                :label="opt.label"
                                :value="opt.id"
                                :disabled="opt.id === form.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="角色名" required>
                        <el-input v-model.trim="form.name" placeholder="请输入角色名" clearable show-word-limit maxlength="50" />
                    </el-form-item>
                    <el-form-item label="描述">
                        <el-input
                            v-model.trim="form.desc"
                            type="textarea"
                            placeholder="请输入角色描述"
                            :autosize="{ minRows: 2, maxRows: 8 }"
                            show-word-limit
                            maxlength="200"
                        />
                    </el-form-item>
                    <el-form-item label="排序">
                        <el-input-number v-model="form.sort" :step="1" class="w-full" />
                    </el-form-item>
                    <el-form-item label="状态">
                        <el-radio-group v-model="form.status">
                            <el-radio :value="1">启用</el-radio>
                            <el-radio :value="0">禁用</el-radio>
                        </el-radio-group>
                    </el-form-item>
                    <el-form-item label="前端权限">
                        <el-tree
                            ref="permTreeRef"
                            :data="permTree"
                            node-key="id"
                            show-checkbox
                            default-expand-all
                            :props="{ children: 'children', label: 'name' }"
                        />
                    </el-form-item>
                </el-form>
            </el-skeleton>
        </el-scrollbar>
        <template #footer>
            <el-button @click="show = false">取消</el-button>
            <el-button type="primary" :loading="saving" @click="submit">保存</el-button>
        </template>
    </el-dialog>
</template>
