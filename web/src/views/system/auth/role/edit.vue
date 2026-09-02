<script setup lang="ts">
import { showError } from '@/apis/request'
import type { RoleRow } from '@/apis/system/auth/role'
import * as roleApi from '@/apis/system/auth/role'
import type { RuleRow } from '@/apis/system/auth/rule'
import * as ruleApi from '@/apis/system/auth/rule'
import { ruleTypeLabel, ruleTypeTagType } from '@/views/system/auth/rule/constants'
import GaIcon from "@components/ga-icon/index.vue"
import { ElMessage } from 'element-plus'
import { computed, nextTick, onMounted, reactive, ref } from 'vue'

/**
 * expandedKeysForCheckedPaths 仅展开通向已选规则的路径上的分支节点（不含纯叶子）
 */
function expandedKeysForCheckedPaths(nodes: RuleRow[], checked: Set<number>): number[] {
  const expanded = new Set<number>()
  const visit = (list: RuleRow[], ancestors: number[]): boolean => {
    let hit = false
    for (const n of list) {
      const children = n.children ?? []
      if (!children.length) {
        if (checked.has(n.id)) {
          ancestors.forEach((a) => expanded.add(a))
          hit = true
        }
      } else {
        const childHit = visit(children, [...ancestors, n.id])
        const selfHit = checked.has(n.id)
        if (selfHit || childHit) {
          ancestors.forEach((a) => expanded.add(a))
          if (children.length && (childHit || selfHit)) expanded.add(n.id)
          hit = true
        }
      }
    }
    return hit
  }
  visit(nodes, [])
  return [...expanded]
}

const props = defineProps<{
  id?: number
  pid?: number
}>()

const show = defineModel<boolean>('modelValue', {required: true})
const emit = defineEmits<{ saved: [] }>()

const form = reactive<{
  id?: number
  pid?: number
  code: string
  name: string
  desc: string
  sort: number
  status: number
}>({
  id: undefined,
  pid: undefined,
  code: '',
  name: '',
  desc: '',
  sort: 0,
  status: 1,
})

const loading = ref(false)
const saving = ref(false)
const title = ref('添加')

/** roleTree 角色树（供 el-tree-select） */
const roleTree = ref<RoleRow[]>([])

const permTree = ref<RuleRow[]>([])
const permTreeRef = ref()
/** permDefaultExpandedKeys 有已选 rule_ids 时由 syncPermTreeExpandForLoadedRules 写入，与 default-expand-all 互斥 */
const permDefaultExpandedKeys = ref<number[]>([])
/** permDefaultExpandAll 无已选 rule_ids 时为 true，使用 el-tree 的 default-expand-all */
const permDefaultExpandAll = ref(false)
/** permTreePanelKey 变更以强制树重挂载，使 default-expand-all / default-expanded-keys 生效 */
const permTreePanelKey = ref(0)

/**
 * syncPermTreeExpandForLoadedRules 仅在规则树与角色数据已就绪后调用：无 rule_ids 则 default-expand-all；有则仅展开通向已选项的路径，其余默认闭合
 */
function syncPermTreeExpandForLoadedRules(ruleIds: number[]) {
  const tree = permTree.value
  if (!tree.length) {
    permDefaultExpandedKeys.value = []
    permDefaultExpandAll.value = false
    return
  }
  if (ruleIds.length === 0) {
    permDefaultExpandAll.value = true
    permDefaultExpandedKeys.value = []
  } else {
    permDefaultExpandAll.value = false
    permDefaultExpandedKeys.value = expandedKeysForCheckedPaths(tree, new Set(ruleIds))
  }
}

/** parentRoleTreeData 编辑时禁用当前节点，避免选自身为上级 */
const parentRoleTreeData = computed((): RoleRow[] => {
  const walk = (rows: RoleRow[]): RoleRow[] =>
      rows.map((r) => ({
        ...r,
        disabled: form.id !== undefined && r.id === form.id,
        children: r.children?.length ? walk(r.children) : undefined,
      }))
  return walk(roleTree.value)
})

const loadMeta = async () => {
  roleTree.value = await roleApi.fetchTree()
  roleTree.value.unshift({id: 0, pid: 0, code: "", name: "作为顶级角色", sort: 0, status: 1})
  permTree.value = await ruleApi.fetchTree('frontend')
}

const loadDetail = async () => {
  if (props?.id) {
    title.value = '编辑'
    loading.value = true
    let ruleIds: number[] = []
    try {
      const d = await roleApi.fetchRead(props.id)
      ruleIds = d.rule_ids ?? []
      Object.assign(form, {
        id: d.id,
        pid: d.pid,
        code: d.code,
        name: d.name,
        desc: d.desc ?? '',
        sort: d.sort,
        status: d.status,
      })
    } finally {
      // 详情已返回且 permTree 已在 loadMeta 中就绪：仅在此处按 rule_ids 决定展开策略
      syncPermTreeExpandForLoadedRules(ruleIds)
      permTreePanelKey.value++
      loading.value = false
    }
    // el-skeleton 在 loading 为 true 时不渲染默认插槽，树未挂载时 setCheckedKeys 无效，须在骨架结束后执行
    await nextTick()
    permTreeRef.value?.setCheckedKeys(ruleIds, false)
  } else if (props?.pid) {
    title.value = '添加子角色'
    Object.assign(form, {
      id: undefined,
      pid: props.pid,
      code: '',
      name: '',
      desc: '',
      sort: 0,
      status: 1,
    })
    // 无已保存 rule_ids，视为空：全部展开
    syncPermTreeExpandForLoadedRules([])
    permTreePanelKey.value++
    await nextTick()
    permTreeRef.value?.setCheckedKeys([], false)
  } else {
    title.value = '添加角色'
    Object.assign(form, {
      id: undefined,
      pid: undefined,
      code: '',
      name: '',
      desc: '',
      sort: 0,
      status: 1,
    })
    syncPermTreeExpandForLoadedRules([])
    permTreePanelKey.value++
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
        code: form.code.trim(),
        name: form.name.trim(),
        desc: form.desc.trim(),
        sort: form.sort,
        status: form.status,
      })
    } else {
      const r = await roleApi.saveRole({
        pid: form.pid ?? 0,
        code: form.code.trim(),
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
    showError(e, '保存失败')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <el-dialog v-model="show" draggable :title="title" destroy-on-close lock-scroll top="8vh" width="640px" :closeOnClickModal="false">
    <el-scrollbar class="pr-10" max-height="70vh">
      <el-skeleton :loading="loading" animated :rows="6">
        <el-form :model="form" label-position="right" label-width="96px">
          <el-form-item label="上级角色">
            <el-tree-select
                v-model="form.pid"
                class="w-full"
                :data="parentRoleTreeData"
                :props="{ value: 'id', label: 'name', children: 'children' }"
                node-key="id"
                placeholder="留空为顶级角色（无权限继承）"
                clearable
                filterable
                check-strictly
                default-expand-all
                :render-after-expand="false"
            />
          </el-form-item>
          <el-form-item label="角色识别码">
            <el-input v-model.trim="form.code" placeholder="请输入角色识别码" clearable show-word-limit maxlength="50"/>
          </el-form-item>
            <el-form-item label="角色名" required>
            <el-input v-model.trim="form.name" placeholder="请输入角色名" clearable show-word-limit maxlength="50"/>
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
            <el-input-number v-model="form.sort" :step="1" class="w-full"/>
          </el-form-item>
          <el-form-item label="状态">
            <el-radio-group v-model="form.status">
              <el-radio :value="1">启用</el-radio>
              <el-radio :value="0">禁用</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="前端权限">
            <el-tree
                :key="permTreePanelKey"
                ref="permTreeRef"
                :data="permTree"
                node-key="id"
                show-checkbox
                :check-strictly="true"
                :default-expand-all="permDefaultExpandAll"
                :default-expanded-keys="permDefaultExpandAll ? undefined : permDefaultExpandedKeys"
                :props="{ children: 'children', label: 'name' }"
            >
              <template #default="{ data }">
                            <span class="flex flex-1 flex-wrap items-center gap-2 py-1 pr-2">
                                <el-tag size="small" :type="ruleTypeTagType(data.type)">{{ ruleTypeLabel(data.type) }}</el-tag>
                                <GaIcon v-if="data.icon" :icon="data.icon" class="shrink-0"/>
                                <span>{{ data.name }}</span>
                                <span v-if="data.path" class="text-xs text-ga-text-secondary">{{ data.path }}</span>
                            </span>
              </template>
            </el-tree>
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
