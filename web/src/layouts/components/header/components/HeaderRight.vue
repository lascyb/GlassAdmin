<script setup lang="ts">
import { useAdmin } from "@/pinia/admin";
import { useMainViewRefresh } from "@/pinia/layout/mainViewRefresh";
import { useLayoutSetting } from "@/pinia/layout/setting.ts";
import { Refresh, Tools } from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";

const setting = useLayoutSetting()
const admin = useAdmin()
const route = useRoute()
const router = useRouter()
const mainRefresh = useMainViewRefresh()
const activeName = ref("system")

const refreshPage = () => {
    mainRefresh.bumpForPath(route.fullPath)
}

/**
 * logout 退出登录：清空 token 与用户信息并跳转登录页
 */
const logout = async () => {
    try {
        await ElMessageBox.confirm('确定退出当前账号？', '退出登录', {type: 'warning'})
    } catch {
        return
    }
    admin.clear()
    await router.replace({name: 'login'})
}
</script>

<template>
  <div class="navs">
    <el-tooltip content="刷新当前页" placement="bottom">
      <el-icon size="20" @click="refreshPage">
        <Refresh/>
      </el-icon>
    </el-tooltip>
    <div class="flex items-center" style="padding-top:6px;">
      <el-popover
          :title="admin.data?.nickname || admin.data?.username || '账号'"
          placement="bottom-end"
          width="300"
      >
        <template #reference>
          <el-avatar class="mr-1 mb-0"/>
        </template>
        <template #default>
          <el-tabs v-model="activeName">
            <el-tab-pane label="系统消息" name="system">系统消息</el-tab-pane>
            <el-tab-pane label="好友消息" name="friend">好友消息</el-tab-pane>
          </el-tabs>
          <el-divider class="!my-2"/>
          <div class="flex justify-end">
            <el-button type="danger" link @click="logout">退出登录</el-button>
          </div>
        </template>
      </el-popover>
    </div>
    <el-icon @click="setting.showSetting()" size="20">
      <Tools/>
    </el-icon>
  </div>
</template>

<style scoped>
.navs {
  display: flex;
  align-items: center;

  > * {
    padding: 0 5px;
    height: 100%;
    min-width: 40px;
    cursor: pointer;

    &:hover {
      background: #F0F2F5;
    }
  }
}
</style>