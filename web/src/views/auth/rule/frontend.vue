<script setup lang="ts">
import type { RuleRow } from '@/apis/auth/rule'
import { ruleTypeLabel, ruleTypeTagType } from '@/views/auth/rule/constants'
import RuleEdit from '@/views/auth/rule/edit.vue'
import { useRuleTreeBase } from '@/views/auth/rule/useRuleTreePage'
import GaIcon from '@components/ga-icon/index.vue'
import { Close, Delete, Edit, Plus, Refresh } from '@element-plus/icons-vue'
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
    onNodeExpand,
} = useRuleTreeBase('frontend')
const ruleEditRef = useTemplateRef<InstanceType<typeof RuleEdit>>('ruleEditRef')

const openAdd = (pid: number, parentType?: string) => ruleEditRef.value?.openAdd(pid, parentType)
const openEdit = (row: RuleRow) => ruleEditRef.value?.openEdit(row)

onMounted(load)
</script>

<template>
    <div v-loading="loading" class="min-h-90">
        <div class="mb-3 flex flex-wrap justify-end gap-2">
            <template v-if="!batchMode">
                <el-button v-auth="'add'" :icon="Plus" plain @click="openAdd(0)">新增</el-button>
                <el-button v-auth="'del'" plain type="danger" :icon="Delete" @click="startBatchDelete">
                    批量删除
                </el-button>
                <el-button plain :icon="Refresh" @click="load">刷新</el-button>
            </template>
            <template v-else>
                <el-button type="danger" :icon="Delete" @click="confirmBatchDelete">确认删除</el-button>
                <el-button plain :icon="Close" @click="cancelBatchDelete">取消</el-button>
            </template>
        </div>
        <el-tree
            ref="treeRef"
            :data="treeData"
            node-key="id"
            :default-expanded-keys="top_ids"
            :props="{ children: 'children', label: 'name' }"
            :show-checkbox="batchMode"
            @node-expand="onNodeExpand"
        >
            <template #default="{ data }">
                <span class="flex flex-1 flex-wrap items-center gap-2 py-1 pr-2">
                    <el-tag :type="ruleTypeTagType(data.type)">{{ ruleTypeLabel(data.type) }}</el-tag>
                    <GaIcon v-if="data.icon" :icon="data.icon" class="shrink-0" />
                    <span>{{ data.name }}</span>
                    <span v-if="data.path" class="text-xs text-gray-500">{{ data.path }}</span>
                    <el-tag v-if="data.component" type="info">{{ data.component }}</el-tag>
                    <el-button-group v-if="!batchMode" class="ml-auto flex flex-wrap gap-1">
                        <el-button v-auth="'add'" text type="primary" :icon="Plus" @click.stop="openAdd(data.id, data.type)">子节点</el-button>
                        <el-button v-auth="'edit'" text type="primary" :icon="Edit" @click.stop="openEdit(data)">编辑</el-button>
                        <el-button v-auth="'del'" text type="danger" :icon="Delete" @click.stop="remove(data)">删除</el-button>
                    </el-button-group>
                </span>
            </template>
        </el-tree>

        <rule-edit ref="ruleEditRef" scope="frontend" @success="load" />
    </div>
</template>
