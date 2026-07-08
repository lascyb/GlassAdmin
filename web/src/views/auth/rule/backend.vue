<script setup lang="ts">
import type { RuleRow } from '@/apis/auth/rule'
import RuleEdit from '@/views/auth/rule/edit.vue'
import { useRuleTreeBase } from '@/views/auth/rule/useRuleTreePage'
import {Delete, Edit, Plus, Refresh} from '@element-plus/icons-vue'
import {  onMounted, useTemplateRef } from 'vue'

const { treeData, loading, load, remove,top_ids } = useRuleTreeBase('backend')
const ruleEditRef = useTemplateRef<InstanceType<typeof RuleEdit>>('ruleEditRef')


const openAdd = (pid: number, parentType?: string) => ruleEditRef.value?.openAdd(pid, parentType)
const openEdit = (row: RuleRow) => ruleEditRef.value?.openEdit(row)

onMounted(load)
</script>

<template>
    <div v-loading="loading" class="min-h-[360px]">
        <div class="mb-3 flex flex-wrap justify-end gap-2">
            <el-button v-auth="'add'" plain :icon="Plus" @click="openAdd(0)">新增</el-button>
            <el-button plain :icon="Refresh" @click="load">刷新</el-button>
        </div>
        <el-tree
            :data="treeData"
            node-key="id"
            :default-expanded-keys="top_ids"
            :props="{ children: 'children', label: 'name' }"
        >
            <template #default="{ data }">
                <span class="flex flex-1 flex-wrap items-center gap-2 py-1 pr-2">
                    <el-tag :type="data.type === 'api' ? 'warning' : 'info'">{{ data.type }}</el-tag>
                    <span>{{ data.name }}</span>
                    <span v-if="data.type === 'api' && data.path" class="text-xs text-gray-500"
                        >{{ data.method }} {{ data.path }}</span
                    >
                    <span class="ml-auto flex gap-1">
                        <el-button v-auth="'add'" text type="primary" :icon="Plus" @click.stop="openAdd(data.id, data.type)"
                            >新增子节点</el-button
                        >
                        <el-button v-auth="'edit'" text type="primary" :icon="Edit" @click.stop="openEdit(data)">编辑</el-button>
                        <el-button v-auth="'del'" text type="danger" :icon="Delete" @click.stop="remove(data)">删除</el-button>
                    </span>
                </span>
            </template>
        </el-tree>

        <rule-edit ref="ruleEditRef" scope="backend" @success="load" />
    </div>
</template>
