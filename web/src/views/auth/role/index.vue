<script setup lang="ts">

import {reactive, ref, shallowRef} from "vue";
import {Delete, Edit} from "@element-plus/icons-vue";
import AddChildSvg from "@/assets/icons/add_child.svg"
import {ElMessage} from "element-plus";
import EditView from "@/views/auth/role/edit.vue";

type Role = {
  id: number
  name: string
  desc: string
  children?: Array<Role>
}

const data = shallowRef<Array<Role>>([
  {
    id: 1,
    name: "超级管理员",
    desc: "描述1",
  },
  {
    id: 2,
    name: "系统管理员",
    desc: "描述2",
  },
  {
    id: 3,
    name: "亚马逊运营组",
    desc: "描述3",
    children: [
      {
        id: 4,
        name: "亚马逊运营经理",
        desc: "描述4",
      }
    ]
  }
]);
const editController = reactive<{
  show?: boolean
  id?: number
  pid?: number
}>({})

const clickEdit = (id: number) => {
  Object.assign(editController, {
    show: true,
    pid: undefined,
    id: id,
  })
  ElMessage.success({message: `clickEdit:id:${id}`})
}
const clickDel = (id: number) => {
  ElMessage.success({message: `clickDel:id:${id}`})
}
const clickAddChild = (pid: number) => {
  console.log(editController.show)
  Object.assign(editController, {
    show: true,
    pid: pid,
    id: undefined,
  })

  ElMessage.success({message: `clickAddChild:id:${pid}`})
}

</script>

<template>
  <div>
    <el-table :data="data" size="small" border row-key="id" default-expand-all stripe highlight-current-row
              current-row-key="id">
      <el-table-column label="ID" prop="id"></el-table-column>
      <el-table-column label="名称" prop="name"></el-table-column>
      <el-table-column label="描述" prop="desc"></el-table-column>
      <el-table-column label="操作">
        <template #default="scope">
          <el-button title="编辑" size="small" plain type="primary" :icon="Edit"
                     @click="clickEdit(scope.row.id)"></el-button>
          <el-button title="添加子角色" size="small" plain type="warning" :icon="AddChildSvg"
                     @click="clickAddChild(scope.row.id)"></el-button>
          <el-button title="删除" size="small" plain type="danger" :icon="Delete"
                     @click="clickDel(scope.row.id)"></el-button>
        </template>
      </el-table-column>
    </el-table>
    <edit-view v-if="editController.show" v-model="editController.show"
               :id="editController.id"
               :pid="editController.pid" @close="(_)=>{
                 console.log(_)
               }"></edit-view>
  </div>
</template>

<style scoped>

</style>