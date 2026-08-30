<script setup lang="ts">
import { index } from '@/apis'
import { useLayoutMenus } from '@/pinia/layout/menus'
import {
    ASIDE_WIDTH_DEFAULT,
    ASIDE_WIDTH_DOUBLE_MIN,
    ASIDE_WIDTH_MAX,
    ASIDE_WIDTH_MIN,
    type LayoutContainer,
    useLayoutSetting,
} from '@/pinia/layout/setting.ts'
import { router } from '@/router'
import { ElMessage } from 'element-plus'
import { ref } from 'vue'
import LayoutPicker from './LayoutPicker.vue'
import ThemeColorPicker from './ThemeColorPicker.vue'

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

/** onContainerChange 切换菜单样式 */
const onContainerChange = (value: LayoutContainer) => {
    setting.setContainer(value)
}
</script>

<template>
    <el-drawer
        v-model="setting.layout.showSetting"
        class="ga-pref-drawer"
        direction="rtl"
        size="400px"
        title="个人偏好"
    >
        <div class="pref-panel" v-loading="reloading">
            <section class="pref-block">
                <div class="pref-label">外观</div>
                <el-radio-group v-model="setting.layout.colorMode" class="pref-radio">
                    <el-radio-button value="light">浅色</el-radio-button>
                    <el-radio-button value="dark">深色</el-radio-button>
                    <el-radio-button value="system">跟随系统</el-radio-button>
                </el-radio-group>
                <p class="pref-hint">未手动选择时默认跟随操作系统的浅色 / 深色。</p>
                <div class="pref-label pref-label--sub">主题色</div>
                <ThemeColorPicker
                    :model-value="setting.layout.themeColor"
                    @update:model-value="setting.setThemeColor"
                />
                <p class="pref-hint">按钮、侧栏高亮与品牌色会即时跟随；与浅色 / 深色互不影响。</p>
            </section>

            <section class="pref-block">
                <div class="pref-label">菜单样式</div>
                <LayoutPicker
                    :model-value="setting.layout.container"
                    @update:model-value="onContainerChange"
                />
            </section>

            <section class="pref-block">
                <div class="pref-label">左侧菜单宽度</div>
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
                <p class="pref-hint">
                    展开态侧栏宽度（{{ ASIDE_WIDTH_MIN }}–{{ ASIDE_WIDTH_MAX }}px）；收起态不受影响。切换到左侧双层时会自动加宽至至少
                    {{ ASIDE_WIDTH_DOUBLE_MIN }}px，以保证子菜单区宽度。
                </p>
            </section>

            <section class="pref-block">
                <div class="pref-switch-row">
                    <div class="pref-switch-copy">
                        <div class="pref-label">左侧菜单手风琴</div>
                        <p class="pref-hint">开启后同一时刻只展开一个子菜单目录。</p>
                    </div>
                    <el-switch v-model="setting.layout.menuAccordion" />
                </div>
            </section>

            <section class="pref-block">
                <div class="pref-switch-row">
                    <div class="pref-switch-copy">
                        <div class="pref-label">展示全部可发现菜单</div>
                        <p class="pref-hint">
                            开启后侧栏会显示标记为「可发现」但尚未授权的菜单，点击进入说明页。
                        </p>
                    </div>
                    <el-switch
                        :model-value="setting.layout.showAllMenus"
                        @change="onShowAllMenusChange"
                    />
                </div>
            </section>
        </div>
    </el-drawer>
</template>

<style scoped>
.pref-panel {
    display: flex;
    flex-direction: column;
}

.pref-block + .pref-block {
    margin-top: var(--ga-space-5);
    padding-top: var(--ga-space-5);
    border-top: 1px solid var(--ga-color-border-lighter);
}

.pref-label {
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
    color: var(--ga-color-text);
}

.pref-label--sub {
    margin-top: var(--ga-space-4);
}

.pref-hint {
    margin: 0;
    width: 100%;
    font-size: 12px;
    line-height: 1.5;
    text-align: left;
    color: var(--ga-color-text-secondary);
}

.pref-block > .pref-label + * {
    margin-top: var(--ga-space-2);
}

.pref-block > * + .pref-hint {
    margin-top: var(--ga-space-2);
}

.pref-radio {
    display: flex;
    width: 100%;
}

.pref-radio :deep(.el-radio-button) {
    flex: 1;
}

.pref-radio :deep(.el-radio-button__inner) {
    width: 100%;
    padding-inline: var(--ga-space-2);
}

.pref-switch-row {
    display: flex;
    align-items: flex-start;
    gap: var(--ga-space-4);
    width: 100%;
}

.pref-switch-copy {
    display: flex;
    flex: 1;
    min-width: 0;
    flex-direction: column;
    gap: var(--ga-space-1);
}

.pref-switch-row :deep(.el-switch) {
    flex-shrink: 0;
    margin-top: 2px;
}

.aside-width-row {
    display: flex;
    align-items: center;
    gap: var(--ga-space-2);
    width: 100%;
}

.aside-width-slider {
    flex: 1;
    min-width: 0;
}
</style>

<style>
/* Drawer 传送到 body：外壳样式用独立类，保持实色，不加玻璃 */
.ga-pref-drawer.el-drawer {
    background: var(--ga-color-overlay);
}

.ga-pref-drawer .el-drawer__header {
    margin-bottom: 0;
    padding: var(--ga-space-4) var(--ga-space-5);
    border-bottom: 1px solid var(--ga-color-border-lighter);
    color: var(--ga-color-text);
}

.ga-pref-drawer .el-drawer__body {
    padding: var(--ga-space-5);
    background: var(--ga-color-overlay);
}
</style>
