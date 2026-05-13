<script setup lang="ts">
import type { RuleRow } from '@/apis/auth/rule'
import MenuIcon from '@/components/menu-icon/index.vue'
import BindBackendApiDialog from '@/views/auth/rule/BindBackendApiDialog.vue'
import RuleEdit from '@/views/auth/rule/edit.vue'
import { useRuleTreeBase } from '@/views/auth/rule/useRuleTreePage'
import { Delete, Edit, Link, Plus } from '@element-plus/icons-vue'
import { onMounted, reactive, useTemplateRef } from 'vue'

const { treeData, loading, load, remove } = useRuleTreeBase('frontend')
const ruleEditRef = useTemplateRef<InstanceType<typeof RuleEdit>>('ruleEditRef')

const openAdd = (pid: number, type: 'menu' | 'view' | 'btn') => ruleEditRef.value?.openAdd(pid, type)
const openEdit = (row: RuleRow) => ruleEditRef.value?.openEdit(row)

const bindDlg = reactive<{
    visible: boolean
    row?: RuleRow
}>({
    visible: false,
})

onMounted(load)

const openBind = (row: RuleRow) => {
    bindDlg.row = row
    bindDlg.visible = true
}
</script>

<template>
    <div v-loading="loading" class="min-h-[360px]">
        <div class="mb-3 flex flex-wrap gap-2">
            <el-button type="primary" :icon="Plus" @click="openAdd(0, 'menu')">新增根目录</el-button>
            <el-button type="success" :icon="Plus" @click="openAdd(0, 'view')">新增根页面</el-button>
            <el-button @click="load">刷新</el-button>
        </div>
        <el-tree :data="treeData" node-key="id" default-expand-all :props="{ children: 'children', label: 'name' }">
            <template #default="{ data }">
                <span class="flex flex-1 flex-wrap items-center gap-2 py-1 pr-2">
                    <el-tag>{{ data.type }}</el-tag>
                    <MenuIcon v-if="data.icon" :icon="data.icon" class="shrink-0" />
                    <span>{{ data.name }}</span>
                    <span v-if="data.path" class="text-xs text-gray-500">{{ data.path }}</span>
                    <el-button-group class="ml-auto flex flex-wrap gap-1">
                          <el-button
                              v-if="data.type === 'view' || data.type === 'btn'"
                              text
                              type="info"
                              :icon="Link"
                              @click.stop="openBind(data)"
                          >配置接口</el-button>
                        <el-button text type="primary" :icon="Plus" @click.stop="openAdd(data.id, 'menu')">子目录</el-button>
                        <el-button text type="success" :icon="Plus" @click.stop="openAdd(data.id, 'view')">子页面</el-button>
                        <el-button text type="warning" :icon="Plus" @click.stop="openAdd(data.id, 'btn')">子按钮</el-button>
                        <el-button text type="primary" :icon="Edit" @click.stop="openEdit(data)">编辑</el-button>
                        <el-button text type="danger" :icon="Delete" @click.stop="remove(data)">删除</el-button>
                    </el-button-group>
                </span>
            </template>
        </el-tree>

        <rule-edit ref="ruleEditRef" scope="frontend" @success="load" />

        <bind-backend-api-dialog
            v-model="bindDlg.visible"
            :frontend-rule-id="bindDlg.row?.id"
            :title-name="bindDlg.row?.name"
            @success="load"
        />
    </div>
</template>
