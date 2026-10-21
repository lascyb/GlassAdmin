<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus'
import { Expand, Fold } from '@element-plus/icons-vue'

const layoutMenus = useLayoutMenus()

const toggleCollapse = () => {
    layoutMenus.collapse = !layoutMenus.collapse
}
</script>

<template>
    <div class="ga-aside-footer" :class="{ 'is-collapsed': layoutMenus.collapse }">
        <el-button
            plain
            text
            type="primary"
            class="ga-aside-footer__btn"
            :title="layoutMenus.collapse ? '展开侧栏' : '收起侧栏'"
            @click="toggleCollapse"
        >
            <el-icon size="20">
                <Expand v-if="layoutMenus.collapse" />
                <Fold v-else />
            </el-icon>
        </el-button>
    </div>
</template>

<style scoped>
.ga-aside-footer {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    box-sizing: border-box;
    height: 44px;
    padding: 0 4px;
    border-top: 1px solid var(--ga-glass-border);
}

.ga-aside-footer.is-collapsed {
    justify-content: center;
    padding: 0;
}

.ga-aside-footer__btn {
    flex-shrink: 0;
    margin: 0;
}

.ga-aside-footer.is-collapsed .ga-aside-footer__btn {
    width: 100%;
    height: 100%;
}
</style>
