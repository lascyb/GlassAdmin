<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus'
import { Expand, Fold, Search } from '@element-plus/icons-vue'
import { ref } from 'vue'
import MenuSearchDialog from './MenuSearchDialog.vue'

const layoutMenus = useLayoutMenus()
const searchOpen = ref(false)

const toggleCollapse = () => {
    layoutMenus.collapse = !layoutMenus.collapse
}
</script>

<template>
    <div class="ga-aside-footer" :class="{ 'is-collapsed': layoutMenus.collapse }">
        <el-button
            v-if="!layoutMenus.collapse"
            plain
            text
            type="primary"
            class="ga-aside-footer__btn"
            title="菜单搜索"
            aria-label="菜单搜索"
            @click="searchOpen = true"
        >
            <el-icon size="18">
                <Search />
            </el-icon>
        </el-button>
        <el-button
            plain
            text
            type="primary"
            class="ga-aside-footer__btn ga-aside-footer__btn--fold"
            :title="layoutMenus.collapse ? '展开侧栏' : '收起侧栏'"
            @click="toggleCollapse"
        >
            <el-icon size="20">
                <Expand v-if="layoutMenus.collapse" />
                <Fold v-else />
            </el-icon>
        </el-button>
        <menu-search-dialog v-model="searchOpen" />
    </div>
</template>

<style scoped>
.ga-aside-footer {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
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

.ga-aside-footer.is-collapsed .ga-aside-footer__btn--fold {
    width: 100%;
    height: 100%;
}
</style>
