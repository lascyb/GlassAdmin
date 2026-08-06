<script setup lang="ts">
import GaIcon from '@/components/ga-icon/index.vue'
import type { Menu } from '@/layouts/components/menus/types'
import { menusForDisplay } from '@/layouts/components/menus/menusForDisplay'
import { useLayoutMenus } from '@/pinia/layout/menus'
import { Rank, RefreshLeft } from '@element-plus/icons-vue'
import { ElMessage, type ElTree } from 'element-plus'
import Sortable from 'sortablejs'
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'

const SHORTCUT_TYPES = new Set(['view', 'page', 'link', 'iframe'])
const VISIBILITY_HIDDEN = 2
const MAX_ITEMS = 16

const props = defineProps<{
    modelValue: boolean
    /** 当前已展示的快捷 rule id（用于预勾） */
    selectedIds: number[]
}>()

const emit = defineEmits<{
    'update:modelValue': [boolean]
    save: [ruleIds: number[]]
    restoreDefault: []
}>()

const layoutMenus = useLayoutMenus()
const treeRef = ref<InstanceType<typeof ElTree>>()
const selectedListRef = ref<HTMLElement | null>(null)
let sortable: Sortable | null = null

const open = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v),
})

type TreeNode = Menu & { disabled?: boolean }

/** markTree：仅 view/page/link/iframe 叶子可勾选 */
const markTree = (menus: Menu[]): TreeNode[] => {
    return menus.map((m) => {
        const children = m.children?.length ? markTree(m.children) : undefined
        const isLeafShortcut =
            !children?.length && SHORTCUT_TYPES.has(m.type) && m.visibility !== VISIBILITY_HIDDEN
        return {
            ...m,
            children,
            disabled: !isLeafShortcut,
        }
    })
}

const treeData = computed(() => markTree(menusForDisplay(layoutMenus.menus)))

const flatById = computed(() => {
    const map = new Map<number, Menu>()
    const walk = (list: Menu[]) => {
        for (const m of list) {
            if (m.children?.length) {
                walk(m.children)
            } else if (SHORTCUT_TYPES.has(m.type) && m.visibility !== VISIBILITY_HIDDEN) {
                map.set(m.id, m)
            }
        }
    }
    walk(menusForDisplay(layoutMenus.menus))
    return map
})

const checkedIds = ref<number[]>([])

const selectedMenus = computed(() => {
    return checkedIds.value
        .map((id) => flatById.value.get(id))
        .filter((m): m is Menu => !!m)
})

const syncTreeCheck = async () => {
    await nextTick()
    treeRef.value?.setCheckedKeys(checkedIds.value)
}

const onCheck = () => {
    const keys = (treeRef.value?.getCheckedKeys(false) ?? []) as Array<string | number>
    const leafIds = keys.map((k) => Number(k)).filter((id) => flatById.value.has(id))
    const prev = checkedIds.value.filter((id) => leafIds.includes(id))
    const added = leafIds.filter((id) => !prev.includes(id))
    let next = [...prev, ...added]
    if (next.length > MAX_ITEMS) {
        next = next.slice(0, MAX_ITEMS)
        ElMessage.warning(`最多选择 ${MAX_ITEMS} 个快捷入口`)
    }
    checkedIds.value = next
    void syncTreeCheck()
}

const removeOne = (id: number) => {
    checkedIds.value = checkedIds.value.filter((x) => x !== id)
    void syncTreeCheck()
}

const initSortable = () => {
    sortable?.destroy()
    sortable = null
    if (!selectedListRef.value) {
        return
    }
    sortable = Sortable.create(selectedListRef.value, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: (evt) => {
            const { oldIndex, newIndex } = evt
            if (oldIndex == null || newIndex == null || oldIndex === newIndex) {
                return
            }
            const list = [...checkedIds.value]
            const [moved] = list.splice(oldIndex, 1)
            if (moved == null) {
                return
            }
            list.splice(newIndex, 0, moved)
            checkedIds.value = list
        },
    })
}

watch(
    () => props.modelValue,
    async (v) => {
        if (!v) {
            sortable?.destroy()
            sortable = null
            return
        }
        checkedIds.value = [...props.selectedIds].filter((id) => flatById.value.has(id))
        await syncTreeCheck()
        await nextTick(initSortable)
    },
)

watch(selectedListRef, () => {
    if (props.modelValue) {
        void nextTick(initSortable)
    }
})

onBeforeUnmount(() => {
    sortable?.destroy()
    sortable = null
})

const onSave = () => {
    emit('save', [...checkedIds.value])
}

const onRestore = () => {
    emit('restoreDefault')
}
</script>

<template>
    <el-drawer v-model="open" title="自定义快捷入口" size="520px" destroy-on-close>
        <div class="flex h-full flex-col gap-3">
            <el-alert
                type="info"
                :closable="false"
                show-icon
                title="勾选常用页面，拖拽右侧调整顺序；保存后按账号同步到服务器。"
            />
            <div class="grid min-h-0 flex-1 grid-cols-1 gap-3 md:grid-cols-2">
                <div class="flex min-h-0 flex-col overflow-hidden rounded-lg border border-gray-100">
                    <div class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600">菜单</div>
                    <div class="min-h-0 flex-1 overflow-auto p-2">
                        <el-tree
                            ref="treeRef"
                            :data="treeData"
                            node-key="id"
                            show-checkbox
                            default-expand-all
                            :check-strictly="true"
                            :props="{ children: 'children', label: 'name', disabled: 'disabled' }"
                            @check="onCheck"
                        >
                            <template #default="{ data }">
                                <span class="flex items-center gap-1.5 text-sm">
                                    <GaIcon
                                        v-if="data.icon"
                                        :icon="data.icon"
                                        :url-size="14"
                                        class="shrink-0 text-[14px]"
                                    />
                                    <span>{{ data.name }}</span>
                                </span>
                            </template>
                        </el-tree>
                    </div>
                </div>
                <div class="flex min-h-0 flex-col overflow-hidden rounded-lg border border-gray-100">
                    <div class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600">
                        已选（{{ selectedMenus.length }}/{{ MAX_ITEMS }}）
                    </div>
                    <ul
                        v-if="selectedMenus.length"
                        ref="selectedListRef"
                        class="selected-list min-h-0 flex-1 overflow-auto p-2"
                    >
                        <li
                            v-for="m in selectedMenus"
                            :key="m.id"
                            class="selected-item"
                            :data-id="m.id"
                        >
                            <el-icon class="drag-handle cursor-grab text-gray-400"><Rank /></el-icon>
                            <GaIcon :icon="m.icon" :url-size="16" class="shrink-0 text-[16px]" />
                            <span class="min-w-0 flex-1 truncate text-sm">{{ m.name }}</span>
                            <el-button text type="danger" size="small" @click="removeOne(m.id)">
                                移除
                            </el-button>
                        </li>
                    </ul>
                    <el-empty v-else description="尚未选择" :image-size="56" class="py-8" />
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3">
                <el-button :icon="RefreshLeft" @click="onRestore">恢复默认</el-button>
                <div class="flex gap-2">
                    <el-button @click="open = false">取消</el-button>
                    <el-button type="primary" @click="onSave">保存</el-button>
                </div>
            </div>
        </div>
    </el-drawer>
</template>

<style scoped>
.selected-list {
    margin: 0;
    padding: 8px;
    list-style: none;
}

.selected-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    margin-bottom: 4px;
    border-radius: 6px;
    background: var(--el-fill-color-blank);
    border: 1px solid var(--el-border-color-extra-light);
}

.drag-handle {
    flex-shrink: 0;
}
</style>
