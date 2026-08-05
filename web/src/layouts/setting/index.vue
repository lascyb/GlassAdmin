<script setup lang="ts">
import { index } from '@/apis'
import { useLayoutMenus } from '@/pinia/layout/menus'
import { useLayoutSetting } from '@/pinia/layout/setting.ts'
import { router } from '@/router'
import { ElMessage } from 'element-plus'
import { ref } from 'vue'
import LayoutPicker from './LayoutPicker.vue'

const setting = useLayoutSetting()
const layoutMenus = useLayoutMenus()
const reloading = ref(false)

/** onShowAllMenusChange 切换后重新拉取菜单 */
const onShowAllMenusChange = async (val: string | number | boolean) => {
    setting.layout.showAllMenus = !!val
    reloading.value = true
    try {
        const res = await index(setting.layout.showAllMenus)
        const menus = res.menus ?? []
        layoutMenus.init(menus)
        void router.replace(router.currentRoute.value.fullPath)
        ElMessage.success(setting.layout.showAllMenus ? '已展示全部可发现菜单' : '已仅展示已授权菜单')
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '菜单刷新失败')
    } finally {
        reloading.value = false
    }
}
</script>

<template>
    <el-drawer v-model="setting.layout.showSetting" direction="rtl" title="个人偏好">
        <el-form v-loading="reloading" label-position="top">
            <el-form-item label="菜单样式">
                <LayoutPicker v-model="setting.layout.container" />
            </el-form-item>
            <el-form-item label="展示全部可发现菜单">
                <el-switch
                    :model-value="setting.layout.showAllMenus"
                    @change="onShowAllMenusChange"
                />
                <div class="mt-1 text-xs text-gray-400">
                    开启后侧栏会显示标记为「可发现」但尚未授权的菜单，点击进入说明页。
                </div>
            </el-form-item>
        </el-form>
    </el-drawer>
</template>

<style scoped></style>
