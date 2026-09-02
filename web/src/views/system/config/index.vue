<script setup lang="ts">
import { showError } from '@/apis/request'
import type { SystemConfigRow } from '@/apis/system/config'
import * as systemApi from '@/apis/system/config'
import { Plus, Edit, Delete } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { onMounted, reactive, ref, computed } from 'vue'
import ConfigEdit from './edit.vue'

const typeLabels: Record<string, string> = {
    input: '单行文本',
    textarea: '多行文本',
    select: '下拉单选',
    multi_select: '下拉多选',
    switch: '开关',
    number: '数值',
    color: '颜色选择',
}

/** CONFIG_GROUP_SORT_KEY 分组排序配置键（值为分号分隔的分组名） */
const CONFIG_GROUP_SORT_KEY = 'config_group_sort'

const configs = ref<SystemConfigRow[]>([])
const groups = ref<string[]>([])
const activeGroup = ref('')
const loading = ref(false)

const editController = reactive<{
    show: boolean
    id?: number
}>({ show: false })

const groupConfigs = computed(() => {
    const map: Record<string, SystemConfigRow[]> = {}
    for (const c of configs.value) {
        const arr = map[c.group] ?? []
        arr.push(c)
        map[c.group] = arr
    }
    for (const g of Object.keys(map)) {
        map[g]!.sort((a, b) => a.sort - b.sort)
    }
    return map
})

/**
 * sortGroups 按 config_group_sort 排序分组；未出现在排序配置中的分组排到后面
 */
const sortGroups = (names: string[], sortValue?: string | null): string[] => {
    const order = (sortValue ?? '')
        .split(/[;；]/)
        .map((s) => s.trim())
        .filter(Boolean)
    const rank = new Map(order.map((g, i) => [g, i]))
    return [...names].sort((a, b) => {
        const ia = rank.has(a) ? rank.get(a)! : Number.MAX_SAFE_INTEGER
        const ib = rank.has(b) ? rank.get(b)! : Number.MAX_SAFE_INTEGER
        if (ia !== ib) return ia - ib
        return a.localeCompare(b, 'zh-CN')
    })
}

const load = async () => {
    loading.value = true
    try {
        const res = await systemApi.fetchList({ page: 1, limit: 999 })
        configs.value = res.list
        const sortRow = res.list.find((c) => c.key === CONFIG_GROUP_SORT_KEY)
        const set = new Set(res.list.map((c) => c.group))
        groups.value = sortGroups(Array.from(set), String(sortRow?.value ?? ''))
        if (groups.value.length && !groups.value.includes(activeGroup.value)) {
            activeGroup.value = groups.value[0]!
        }
    } catch (e: unknown) {
        showError(e, '加载失败')
    } finally {
        loading.value = false
    }
}

onMounted(load)

const clickEdit = (id: number) => {
    Object.assign(editController, { show: true, id })
}

const clickAdd = () => {
    Object.assign(editController, { show: true, id: undefined })
}

const clickDel = async (row: SystemConfigRow) => {
    try {
        await ElMessageBox.confirm(`确定删除配置「${row.name}」？`, '确认', { type: 'warning' })
    } catch {
        return
    }
    try {
        await systemApi.deleteConfig(row.id)
        ElMessage.success('已删除')
        await load()
    } catch (e: unknown) {
        showError(e, '删除失败')
    }
}

const onSaved = () => {
    editController.show = false
    load()
}

const displayValue = (row: SystemConfigRow): string => {
    const v = row.value
    if (v === '' || v === null || v === undefined) return '—'
    if (row.type === 'switch') return v === true ? '开启' : '关闭'
    if (row.type === 'multi_select') {
        if (Array.isArray(v)) {
            const text = v.map(String).join('、')
            return text || '—'
        }
        return '—'
    }
    const text = String(v)
    return text.length > 50 ? text.slice(0, 50) + '…' : text
}
</script>

<template>
    <div class="p-3">
    <el-card v-loading="loading" style="min-height: max(calc(100vh - 100px), 400px)">
        <div class="flex items-center justify-between p-4 pb-2">
            <div class="text-base font-medium">系统配置</div>
            <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增配置</el-button>
        </div>

        <el-tabs v-model="activeGroup" class="px-4">
            <el-tab-pane v-for="g in groups" :key="g" :label="g" :name="g">
                <el-table :data="groupConfigs[g]" border stripe size="small">
                    <el-table-column prop="key" label="键名" min-width="140" show-overflow-tooltip />
                    <el-table-column prop="name" label="名称" min-width="120" show-overflow-tooltip />
                    <el-table-column label="类型" width="100">
                        <template #default="{ row }">
                            {{ typeLabels[row.type] || row.type }}
                        </template>
                    </el-table-column>
                    <el-table-column label="值" min-width="160" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ displayValue(row) }}
                        </template>
                    </el-table-column>
                    <el-table-column prop="sort" label="排序" width="72" />
                    <el-table-column prop="status" label="状态" width="100">
                        <template #default="{ row }">
                            <el-tag
                                :type="row.status === 1 ? 'success' : row.status === 2 ? 'warning' : 'info'"
                                size="small"
                            >
                                {{ row.status === 1 ? '启用' : row.status === 2 ? '系统内置' : '禁用' }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" fixed="right" width="160">
                        <template #default="{ row }">
                            <el-button v-auth="'edit'" text type="primary" size="small" :icon="Edit" @click="clickEdit(row.id)">编辑</el-button>
                            <el-button v-if="row.status !== 2" v-auth="'del'" text type="danger" size="small" :icon="Delete" @click="clickDel(row)">删除</el-button>
                        </template>
                    </el-table-column>
                </el-table>
                <el-empty v-if="!groupConfigs[g]?.length" description="暂无配置" />
            </el-tab-pane>
            <el-empty v-if="!groups.length" description="暂无配置分组" />
        </el-tabs>

        <config-edit v-if="editController.show" v-model="editController.show" :id="editController.id" :groups="groups" @saved="onSaved" />
    </el-card>
    </div>
</template>
