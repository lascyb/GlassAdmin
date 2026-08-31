<script setup lang="ts">
import type { RuleRow } from '@/apis/system/auth/rule'
import AssignFrontendsDialog from '@/views/system/auth/rule/AssignFrontendsDialog.vue'
import RuleEdit from '@/views/system/auth/rule/edit.vue'
import { useRuleTreeBase } from '@/views/system/auth/rule/useRuleTreePage'
import { Close, Connection, Delete, Edit, Plus, Rank, Refresh } from '@element-plus/icons-vue'
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
} = useRuleTreeBase('backend')
const ruleEditRef = useTemplateRef<InstanceType<typeof RuleEdit>>('ruleEditRef')
const assignFrontendsRef = useTemplateRef<InstanceType<typeof AssignFrontendsDialog>>('assignFrontendsRef')

const openAdd = (pid: number, parentType?: string) => ruleEditRef.value?.openAdd(pid, parentType)
const openEdit = (row: RuleRow) => ruleEditRef.value?.openEdit(row)
const openAssignFrontends = (row: RuleRow) => assignFrontendsRef.value?.open(row)

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
                    <el-tag :type="data.type === 'api' ? 'warning' : 'info'">{{ data.type }}</el-tag>
                    <span>{{ data.name }}</span>
                    <span v-if="data.type === 'api' && data.path" class="text-xs text-ga-text-secondary"
                        >{{ data.method }} {{ data.path }}</span
                    >
                    <el-button-group v-if="!batchMode && !sortMode" class="ml-auto flex flex-wrap gap-1">
                        <el-button
                            v-if="data.type === 'api'"
                            v-auth="'assignFrontends'"
                            text
                            type="primary"
                            :icon="Connection"
                            @click.stop="openAssignFrontends(data)"
                        >
                            分配前端
                        </el-button>
                        <el-button
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

        <rule-edit ref="ruleEditRef" scope="backend" @success="load" />
        <assign-frontends-dialog ref="assignFrontendsRef" />
    </div>
</template>
