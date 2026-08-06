<script setup lang="ts">
import { index } from '@/apis'
import { useLayoutMenus } from '@/pinia/layout/menus'
import {
    ASIDE_WIDTH_DEFAULT,
    ASIDE_WIDTH_MAX,
    ASIDE_WIDTH_MIN,
    useLayoutSetting,
} from '@/pinia/layout/setting.ts'
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

/** onAsideWidthChange 调整侧栏宽度 */
const onAsideWidthChange = (val: number | number[]) => {
    const n = Array.isArray(val) ? val[0] : val
    setting.setAsideWidth(typeof n === 'number' ? n : ASIDE_WIDTH_DEFAULT)
}

/** resetAsideWidth 恢复系统默认宽度 */
const resetAsideWidth = () => {
    setting.setAsideWidth(ASIDE_WIDTH_DEFAULT)
}
</script>

<template>
    <el-drawer v-model="setting.layout.showSetting" direction="rtl" title="个人偏好">
        <el-form v-loading="reloading" label-position="top">
            <el-form-item label="菜单样式">
                <LayoutPicker v-model="setting.layout.container" />
            </el-form-item>
            <el-form-item label="左侧菜单宽度">
                <div class="aside-width-row">
                    <el-slider
                        class="aside-width-slider"
                        :model-value="setting.layout.asideWidth"
                        :min="ASIDE_WIDTH_MIN"
                        :max="ASIDE_WIDTH_MAX"
                        :step="10"
                        show-input
                        :show-input-controls="true"
                        @update:model-value="onAsideWidthChange"
                    />
                    <el-button link type="primary" @click="resetAsideWidth">重置</el-button>
                </div>
                <div class="mt-1 text-xs text-gray-400">
                    展开态侧栏宽度（{{ ASIDE_WIDTH_MIN }}–{{ ASIDE_WIDTH_MAX }}px）；收起态不受影响。
                </div>
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

<style scoped>
.aside-width-row {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}
.aside-width-slider {
    flex: 1;
    min-width: 0;
}
</style>
