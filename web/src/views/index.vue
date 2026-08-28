<script setup lang="ts">
import * as workbenchApi from '@/apis/system/workbench'
import { useAdmin } from '@/pinia/admin'
import { useWorkbenchRecent } from '@/pinia/system/workbenchRecent'
import RecentPanel from '@/views/system/workbench/RecentPanel.vue'
import ShortcutCustomizeDrawer from '@/views/system/workbench/ShortcutCustomizeDrawer.vue'
import ShortcutPanel from '@/views/system/workbench/ShortcutPanel.vue'
import TodoPanel from '@/views/system/workbench/TodoPanel.vue'
import type { WorkbenchShortcut } from '@/views/system/workbench/types'
import { User } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { storeToRefs } from 'pinia'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const admin = useAdmin()
const recentStore = useWorkbenchRecent()
const { items: recents } = storeToRefs(recentStore)

const shortcuts = ref<WorkbenchShortcut[]>([])
const loadingShortcuts = ref(false)
const customizeOpen = ref(false)
const saving = ref(false)

const displayName = computed(() => {
    const u = admin.data
    return u?.nickname || u?.username || '管理员'
})

const greeting = computed(() => {
    const h = new Date().getHours()
    if (h < 6) return '夜深了'
    if (h < 12) return '上午好'
    if (h < 14) return '中午好'
    if (h < 18) return '下午好'
    return '晚上好'
})

const selectedIds = computed(() => shortcuts.value.map((s) => s.id))

const mapList = (list: workbenchApi.WorkbenchShortcutItem[]): WorkbenchShortcut[] => {
    return list.map((item) => ({
        id: item.id,
        name: item.name,
        path_full: item.path,
        icon: item.icon,
        type: item.type,
    }))
}

const loadShortcuts = async () => {
    loadingShortcuts.value = true
    try {
        const res = await workbenchApi.fetchShortcuts()
        shortcuts.value = mapList(res.list ?? [])
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '快捷入口加载失败')
    } finally {
        loadingShortcuts.value = false
    }
}

const onCustomize = () => {
    customizeOpen.value = true
}

const onClearRecent = () => {
    recentStore.clear()
    ElMessage.success('已清空最近访问')
}

const onSaveShortcuts = async (ruleIds: number[]) => {
    saving.value = true
    try {
        await workbenchApi.saveShortcuts(ruleIds)
        customizeOpen.value = false
        ElMessage.success('快捷入口已保存')
        await loadShortcuts()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '保存失败')
    } finally {
        saving.value = false
    }
}

const onRestoreDefault = async () => {
    saving.value = true
    try {
        await workbenchApi.saveShortcuts([])
        customizeOpen.value = false
        ElMessage.success('已恢复默认快捷入口')
        await loadShortcuts()
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '操作失败')
    } finally {
        saving.value = false
    }
}

/** goProfile 进入个人中心 */
const goProfile = () => {
    void router.push({ name: 'account-profile' })
}

onMounted(() => {
    if (admin.data?.id) {
        recentStore.ensureAdmin(admin.data.id)
    }
    void loadShortcuts()
})
</script>

<template>
    <div class="workbench p-4" style="min-height: calc(100vh - 120px)">
        <div class="welcome mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="m-0 text-xl font-semibold tracking-tight text-ga-text">
                {{ greeting }}，{{ displayName }}
            </h1>
            <el-button type="primary" plain @click="goProfile">
                <el-icon class="mr-1"><User /></el-icon>
                个人中心
            </el-button>
        </div>

        <div v-loading="loadingShortcuts || saving" class="flex flex-col gap-3">
            <ShortcutPanel :items="shortcuts" @customize="onCustomize" />

            <el-row :gutter="12">
                <el-col :xs="24" :md="12" class="mb-3 md:mb-0">
                    <RecentPanel :items="recents" @clear="onClearRecent" />
                </el-col>
                <el-col :xs="24" :md="12">
                    <TodoPanel :items="[]" />
                </el-col>
            </el-row>
        </div>

        <ShortcutCustomizeDrawer
            v-model="customizeOpen"
            :selected-ids="selectedIds"
            @save="onSaveShortcuts"
            @restore-default="onRestoreDefault"
        />
    </div>
</template>

<style scoped>
.welcome h1 {
    line-height: 1.35;
}
</style>
