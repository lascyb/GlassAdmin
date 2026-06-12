<script setup lang="ts">
import * as roleApi from '@/apis/auth/role'
import type {RoleRow} from '@/apis/auth/role'
import GaTreeTable from '@/components/ga-tree-table/index.vue'
import {useTreeCrud} from '@/composables/useTreeCrud'
import EditView from '@/views/auth/role/edit.vue'
import {onMounted} from 'vue'

const {treeData, loading, editController, load, remove, openEdit, openAdd, openAddChild, onSaved} =
    useTreeCrud<RoleRow>(
        () => roleApi.fetchTree(),
        (id) => roleApi.deleteRole(id),
        {
          getDeleteMessage: () => '确定删除该角色？',
          deleteSuccessMsg: '已删除',
        })

onMounted(load)

const onEdit = (row: RoleRow) => openEdit(row.id)
const onAddChild = (row: RoleRow) => openAddChild(row.id)
</script>

<template>
  <el-card style="min-height: max(calc(100vh - 100px), 400px)">
    <div class="mb-3">
      <el-button type="primary" @click="openAdd">新增角色</el-button>
      <el-button @click="load">刷新</el-button>
    </div>
    <ga-tree-table
        :data="treeData"
        :loading="loading"
        @edit="onEdit"
        @add-child="onAddChild"
        @delete="remove"
        border
    >
      <el-table-column label="ID" prop="id" width="80"/>
      <el-table-column label="名称" prop="name"/>
      <el-table-column label="描述" prop="desc" show-overflow-tooltip/>
    </ga-tree-table>
    <edit-view
        v-if="editController.show"
        v-model="editController.show"
        :id="editController.id"
        :pid="editController.pid"
        @saved="onSaved"
    />
  </el-card>
</template>
