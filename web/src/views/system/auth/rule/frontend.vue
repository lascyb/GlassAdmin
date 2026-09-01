<script setup lang="ts">
import type { RuleRow } from '@/apis/system/auth/rule'
import AssignRolesDialog from '@/views/system/auth/rule/AssignRolesDialog.vue'
import { ruleTypeLabel, ruleTypeTagType } from '@/views/system/auth/rule/constants'
import RuleEdit from '@/views/system/auth/rule/edit.vue'
import { useRuleTreeBase } from '@/views/system/auth/rule/useRuleTreePage'
import GaIcon from '@components/ga-icon/index.vue'
import { Close, Delete, Edit, Plus, Rank, Refresh, User } from '@element-plus/icons-vue'
import { onMounted, useTemplateRef } from 'vue'

const {
    treeData,
    loading,
    load,
    remove,
    top_ids,
    batchMode,
    startBatchDelete,
    cancelBatchDelete,
    confirmBatchDelete,
    sortMode,
    startSort,
    stopSort,
    allowSortDrop,
    onSortDrop,
    onNodeExpand,
    expandAll,
    collapseAll,
} = useRuleTreeBase('frontend')
const ruleEditRef = useTemplateRef<InstanceType<typeof RuleEdit>>('ruleEditRef')
const assignRolesRef = useTemplateRef<InstanceType<typeof AssignRolesDialog>>('assignRolesRef')

const openAdd = (pid: number, parentType?: string) => ruleEditRef.value?.openAdd(pid, parentType)
const openEdit = (row: RuleRow) => ruleEditRef.value?.openEdit(row)
const openAssignRoles = (row: RuleRow) => assignRolesRef.value?.open(row)

onMounted(load)
</script>

<template>
    <div v-loading="loading" class="min-h-90">
        <div class="mb-3 flex flex-wrap items-center justify-end gap-2">
            <span v-if="sortMode" class="mr-auto text-sm text-ga-text-secondary">拖动即保存，可调整顺序或挂到其他节点下</span>
            <template v-if="!batchMode && !sortMode">
                <el-button v-auth="'add'" :icon="Plus" plain @click="openAdd(0)">新增</el-button>
                <el-button v-auth="'del'" plain type="danger" :icon="Delete" @click="startBatchDelete">
                    批量删除
                </el-button>
                <el-button v-auth="'edit'" plain :icon="Rank" @click="startSort">拖动排序</el-button>
                <el-button plain @click="expandAll">全部展开</el-button>
                <el-button plain @click="collapseAll">全部收起</el-button>
                <el-button plain :icon="Refresh" @click="load">刷新</el-button>
            </template>
            <template v-else-if="batchMode">
                <el-button type="danger" :icon="Delete" @click="confirmBatchDelete">确认删除</el-button>
                <el-button plain :icon="Close" @click="cancelBatchDelete">取消</el-button>
            </template>
            <template v-else>
                <el-button plain :icon="Close" @click="stopSort">完成</el-button>
            </template>
        </div>
        <el-tree
            ref="treeRef"
            :data="treeData"
            node-key="id"
            :default-expanded-keys="top_ids"
            :props="{ children: 'children', label: 'name' }"
            :show-checkbox="batchMode"
            :draggable="sortMode"
            :allow-drop="allowSortDrop"
            :expand-on-click-node="!sortMode"
            @node-drop="onSortDrop"
            @node-expand="onNodeExpand"
        >
            <template #default="{ data }">
                <span class="flex flex-1 flex-wrap items-center gap-2 py-1 pr-2" :class="{ 'cursor-move': sortMode }">
                    <el-icon v-if="sortMode" class="text-ga-text-secondary"><Rank /></el-icon>
                    <el-tag :type="ruleTypeTagType(data.type)">{{ ruleTypeLabel(data.type) }}</el-tag>
                    <GaIcon v-if="data.icon" :icon="data.icon" class="shrink-0" />
                    <span :class="{ 'text-ga-text-secondary': data.status === 0 }">{{ data.name }}</span>
                    <el-tag v-if="data.status === 0" size="small" type="info" effect="plain">禁用</el-tag>
                    <span v-if="data.path" class="text-xs text-ga-text-secondary">{{ data.path }}</span>
                    <el-tag v-if="data.component" type="info" size="small">{{ data.component }}</el-tag>
                    <template
                        v-if="
                            data.type === 'menu' ||
                            data.type === 'view' ||
                            data.type === 'link' ||
                            data.type === 'iframe'
                        "
                    >
                        <el-tag v-if="data.visibility === 1" type="success" effect="plain">可发现</el-tag>
                        <el-tag v-else-if="data.visibility === 2" type="warning" effect="plain" size="small">
                            不入侧栏
                        </el-tag>
                        <el-tag v-else type="info" effect="plain" size="small">授权可见</el-tag>
                    </template>
                    <el-button-group v-if="!batchMode && !sortMode" class="ml-auto flex flex-wrap gap-1">
                        <el-button
                            v-auth="'assignRoles'"
                            text
                            type="primary"
                            :icon="User"
                            @click.stop="openAssignRoles(data)"
                        >
                            分配角色
                        </el-button>
                        <el-button
                            v-if="data.type !== 'link' && data.type !== 'iframe' && data.type !== 'btn'"
                            v-auth="'add'"
                            text
                            type="primary"
                            :icon="Plus"
                            @click.stop="openAdd(data.id, data.type)"
                        >
                            子节点
                        </el-button>
                        <el-button v-auth="'edit'" text type="primary" :icon="Edit" @click.stop="openEdit(data)">
                            编辑
                        </el-button>
                        <el-button v-auth="'del'" text type="danger" :icon="Delete" @click.stop="remove(data)">
                            删除
                        </el-button>
                    </el-button-group>
                </span>
            </template>
        </el-tree>

        <rule-edit ref="ruleEditRef" scope="frontend" @success="load" />
        <assign-roles-dialog ref="assignRolesRef" />
    </div>
</template>
