<script setup lang="ts">
import { flattenNavigableMenus } from '@/layouts/components/menus/menusForDisplay'
import { useLayoutMenus } from '@/pinia/layout/menus'
import GaIcon from '@components/ga-icon/index.vue'
import { Search } from '@element-plus/icons-vue'
import { computed, nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const visible = defineModel<boolean>({ default: false })

const layoutMenus = useLayoutMenus()
const router = useRouter()

const keyword = ref('')
const inputRef = ref<{ focus?: () => void } | null>(null)

const hits = computed(() => flattenNavigableMenus(layoutMenus.menus))

const filtered = computed(() => {
    const q = keyword.value.trim().toLowerCase()
    if (!q) {
        return hits.value.slice(0, 50)
    }
    return hits.value
        .filter((hit) => {
            const name = hit.menu.name.toLowerCase()
            const path = hit.menu.path_full.toLowerCase()
            const trail = hit.trail.join(' / ').toLowerCase()
            return name.includes(q) || path.includes(q) || trail.includes(q)
        })
        .slice(0, 50)
})

watch(visible, async (open) => {
    if (!open) {
        keyword.value = ''
        return
    }
    await nextTick()
    inputRef.value?.focus?.()
})

const trailText = (trail: string[]) => (trail.length ? trail.join(' / ') : '')

const onSelect = (path: string) => {
    visible.value = false
    void router.push(path)
}
</script>

<template>
    <el-dialog
        v-model="visible"
        title="菜单搜索"
        width="480px"
        append-to-body
        destroy-on-close
        class="ga-menu-search-dialog"
    >
        <el-input
            ref="inputRef"
            v-model="keyword"
            clearable
            placeholder="按名称、路径或上级目录搜索"
            :prefix-icon="Search"
        />
        <el-scrollbar max-height="360px" class="ga-menu-search-list">
            <button
                v-for="hit in filtered"
                :key="hit.menu.id"
                type="button"
                class="ga-menu-search-item"
                @click="onSelect(hit.menu.path_full)"
            >
                <GaIcon :icon="hit.menu.icon" :url-size="18" class="ga-menu-search-item__icon" />
                <div class="ga-menu-search-item__text">
                    <div class="ga-menu-search-item__name">{{ hit.menu.name }}</div>
                    <div v-if="trailText(hit.trail)" class="ga-menu-search-item__trail">
                        {{ trailText(hit.trail) }}
                    </div>
                </div>
            </button>
            <div v-if="filtered.length === 0" class="ga-menu-search-empty">无匹配菜单</div>
        </el-scrollbar>
    </el-dialog>
</template>

<style scoped>
.ga-menu-search-list {
    margin-top: 12px;
}

.ga-menu-search-item {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 0;
    border-radius: var(--ga-radius-sm, 6px);
    background: transparent;
    color: inherit;
    text-align: left;
    cursor: pointer;
}

.ga-menu-search-item:hover {
    background: color-mix(in srgb, var(--ga-color-fill) 70%, transparent);
}

.ga-menu-search-item__icon,
.ga-menu-search-item :deep(.el-icon) {
    flex-shrink: 0;
    font-size: 18px;
}

.ga-menu-search-item__text {
    min-width: 0;
    flex: 1;
}

.ga-menu-search-item__name {
    font-size: 14px;
    line-height: 1.3;
}

.ga-menu-search-item__trail {
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 12px;
    color: var(--ga-color-text-secondary, var(--el-text-color-secondary));
}

.ga-menu-search-empty {
    padding: 24px 12px;
    text-align: center;
    font-size: 13px;
    color: var(--ga-color-text-secondary, var(--el-text-color-secondary));
}
</style>
