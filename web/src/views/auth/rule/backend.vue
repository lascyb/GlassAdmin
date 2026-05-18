<script setup lang="ts">
import type { RuleRow } from '@/apis/auth/rule'
import RuleEdit from '@/views/auth/rule/edit.vue'
import { useRuleTreeBase } from '@/views/auth/rule/useRuleTreePage'
import { Delete, Edit, Plus } from '@element-plus/icons-vue'
import {  onMounted, useTemplateRef } from 'vue'

const { treeData, loading, load, remove,top_ids } = useRuleTreeBase('backend')
const ruleEditRef = useTemplateRef<InstanceType<typeof RuleEdit>>('ruleEditRef')


const openAdd = (pid: number, type: 'menu' | 'api') => ruleEditRef.value?.openAdd(pid, type)
const openEdit = (row: RuleRow) => ruleEditRef.value?.openEdit(row)

onMounted(load)
</script>

<template>
    <div v-loading="loading" class="min-h-[360px]">
        <div class="mb-3 flex flex-wrap gap-2">
            <el-button type="primary" :icon="Plus" @click="openAdd(0, 'menu')">新增根目录</el-button>
            <el-button type="success" :icon="Plus" @click="openAdd(0, 'api')">新增根接口</el-button>
            <el-button @click="load">刷新</el-button>
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
                        <el-button text type="primary" :icon="Plus" @click.stop="openAdd(data.id, 'menu')"
                            >子目录</el-button
                        >
                        <el-button text type="success" :icon="Plus" @click.stop="openAdd(data.id, 'api')"
                            >子接口</el-button
                        >
                        <el-button text type="primary" :icon="Edit" @click.stop="openEdit(data)">编辑</el-button>
                        <el-button text type="danger" :icon="Delete" @click.stop="remove(data)">删除</el-button>
                    </span>
                </span>
            </template>
        </el-tree>

        <rule-edit ref="ruleEditRef" scope="backend" @success="load" />
    </div>
</template>
