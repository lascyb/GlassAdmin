<script setup lang="ts">
import type { CrudMenuLevel } from '@/crud/types'
import { useDevCurd } from '@/pinia/dev/curd'
import GaPicker from '@components/ga-icon/picker.vue'
import { ArrowDown, ArrowUp, Back, Delete, Plus } from '@element-plus/icons-vue'
import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { PATH_PREFIXES, guessMetaFromTable, leafView, syncDerivedFields } from '../helpers'

const store = useDevCurd()
const { config, force, noAuth, saveHistory } = storeToRefs(store)

const collapsed = ref(true)

const menus = computed({
    get: (): CrudMenuLevel[] => {
        if (!config.value.menus) config.value.menus = []
        return config.value.menus
    },
    set: (v: CrudMenuLevel[]) => {
        config.value.menus = v
        syncDerivedFields(config.value)
    },
})

const leafTitle = computed({
    get: () => leafView(config.value.menus)?.title ?? '',
    set: (v: string) => {
        const view = leafView(config.value.menus)
        if (view) {
            view.title = v
            syncDerivedFields(config.value)
        }
    },
})

/** levelLabel 级别文案 */
const levelLabel = (index: number): string => {
    const names = ['一级', '二级', '三级', '四级', '五级', '六级', '七级', '八级']
    return names[index] ?? `${index + 1}级`
}

/** onMenusChange menus 编辑后回写派生字段 */
const onMenusChange = () => {
    const list = config.value.menus
    if (list.length) {
        const last = list[list.length - 1]
        if (last) last.type = 'view'
        for (let i = 0; i < list.length - 1; i++) {
            const row = list[i]
            if (row) row.type = 'menu'
        }
    }
    syncDerivedFields(config.value)
}

/** addMenuLevel 在页面之前插入一级 menu */
const addMenuLevel = () => {
    const list = [...config.value.menus]
    const idx = Math.max(0, list.length - 1)
    list.splice(idx, 0, {
        type: 'menu',
        path: `menu${idx + 1}`,
        title: `菜单${idx + 1}`,
        enabled: true,
    })
    if (list[0] && !list[0].icon) {
        list[0].icon = 'el:Document'
        list[0].sort = list[0].sort ?? 0
    }
    config.value.menus = list
    onMenusChange()
}

/** removeMenuAt 删除非页面行 */
const removeMenuAt = (index: number) => {
    const list = config.value.menus
    if (index < 0 || index >= list.length - 1) return
    list.splice(index, 1)
    onMenusChange()
}

/** onTableInput 表名变更时覆盖 menus + paths */
const onTableInput = (value: string) => {
    config.value.table = value
    const meta = guessMetaFromTable(value.trim())
    config.value.menus = meta.menus
    config.value.paths = meta.paths
    syncDerivedFields(config.value)
}
</script>

<template>
    <el-card shadow="never" class="!border">
        <el-row justify="space-between" align="middle" :gutter="12">
            <el-col :xs="24" :sm="16">
                <el-space wrap :size="8" alignment="center">
                    <el-text>数据表名</el-text>
                    <el-input
                        :model-value="config.table"
                        style="width: 200px"
                        placeholder="如 countries 或 demo_item"
                        @update:model-value="onTableInput"
                    />
                    <el-text>页面名称</el-text>
                    <el-input
                        v-model="leafTitle"
                        style="width: 200px"
                        placeholder="末级页面标题"
                    />
                    <el-button link type="primary" @click="collapsed = !collapsed">
                        <el-icon class="mr-1">
                            <ArrowUp v-if="!collapsed" />
                            <ArrowDown v-else />
                        </el-icon>
                        {{ collapsed ? '更多配置' : '收起' }}
                    </el-button>
                </el-space>
            </el-col>
            <el-col :xs="24" :sm="8" style="text-align: right">
                <el-space :size="8">
                    <el-button :icon="Back" @click="store.goBack()">返回入口</el-button>
                    <el-button type="primary" plain @click="store.runGenerate(true)">预览</el-button>
                    <el-button type="primary" @click="store.runGenerate(false)">生成</el-button>
                </el-space>
            </el-col>
        </el-row>

        <el-row v-show="!collapsed" :gutter="24" class="mt-3">
            <el-col :xs="24" :md="12">
                <el-form label-width="120px">
                    <el-divider content-position="left">代码与写盘</el-divider>
                    <el-text type="info" size="small" class="mb-2" style="display: block; padding-left: 120px">
                        前缀固定，仅编辑相对路径
                    </el-text>
                    <el-form-item label="控制器">
                        <el-input v-model="config.paths.controller" style="width: 100%; max-width: 420px">
                            <template #prepend>{{ PATH_PREFIXES.controller }}</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="模型">
                        <el-input v-model="config.paths.model" style="width: 100%; max-width: 420px">
                            <template #prepend>{{ PATH_PREFIXES.model }}</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="验证器">
                        <el-input v-model="config.paths.validate" style="width: 100%; max-width: 420px">
                            <template #prepend>{{ PATH_PREFIXES.validate }}</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="前端 API">
                        <el-input v-model="config.paths.apis" style="width: 100%; max-width: 420px">
                            <template #prepend>{{ PATH_PREFIXES.apis }}</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="前端视图目录">
                        <el-input v-model="config.paths.viewsDir" style="width: 100%; max-width: 420px">
                            <template #prepend>{{ PATH_PREFIXES.viewsDir }}</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="可选操作">
                        <el-checkbox v-model="force">强制覆盖已有文件</el-checkbox>
                        <el-checkbox v-model="saveHistory">写入生成历史</el-checkbox>
                    </el-form-item>
                </el-form>
            </el-col>
            <el-col :xs="24" :md="12">
                <el-form label-width="120px">
                    <el-divider content-position="left">权限与菜单</el-divider>
                    <el-form-item label="菜单层级" label-position="top">
                        <div class="mb-2">
                            <el-button size="small" :icon="Plus" @click="addMenuLevel">添加菜单级</el-button>
                        </div>
                        <el-table :data="menus" border size="small" style="width: 100%">
                            <el-table-column label="级别" width="64" align="center">
                                <template #default="{ $index }">
                                    {{ levelLabel($index) }}
                                </template>
                            </el-table-column>
                            <el-table-column label="类型" width="88" align="center">
                                <template #default="{ row }">
                                    <el-tag size="small" :type="row.type === 'view' ? 'success' : 'info'">
                                        {{ row.type === 'view' ? '页面' : '菜单' }}
                                    </el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column label="path" min-width="90">
                                <template #default="{ row }">
                                    <el-input v-model="row.path" size="small" @change="onMenusChange" />
                                </template>
                            </el-table-column>
                            <el-table-column label="title" min-width="90">
                                <template #default="{ row }">
                                    <el-input v-model="row.title" size="small" @change="onMenusChange" />
                                </template>
                            </el-table-column>
                            <el-table-column label="图标" width="60" align="center">
                                <template #default="{ row }">
                                    <GaPicker v-model="row.icon" hide-name @update:model-value="onMenusChange" />
                                </template>
                            </el-table-column>
                            <el-table-column label="排序" width="100">
                                <template #default="{ row }">
                                    <el-input-number
                                        v-model="row.sort"
                                        :min="0"
                                        :max="9999"
                                        size="small"
                                        controls-position="right"
                                        style="width: 88px"
                                        @change="onMenusChange"
                                    />
                                </template>
                            </el-table-column>
                            <el-table-column label="启用" width="64" align="center">
                                <template #default="{ row }">
                                    <el-switch v-model="row.enabled" @change="onMenusChange" />
                                </template>
                            </el-table-column>
                            <el-table-column label="" width="48" align="center">
                                <template #default="{ $index }">
                                    <el-button
                                        v-if="$index < menus.length - 1"
                                        link
                                        type="danger"
                                        :icon="Delete"
                                        @click="removeMenuAt($index)"
                                    />
                                </template>
                            </el-table-column>
                        </el-table>
                        <el-text type="info" size="small" class="mt-1" style="display: block">
                            末行为页面；关闭启用后本级不写入，下级升一级
                        </el-text>
                    </el-form-item>
                    <el-form-item label="可选操作">
                        <el-checkbox
                            :model-value="config.generateAuth !== false"
                            @update:model-value="(v: boolean) => (config.generateAuth = v)"
                        >
                            生成权限规则
                        </el-checkbox>
                        <el-checkbox v-model="noAuth">不写入权限/菜单</el-checkbox>
                    </el-form-item>
                </el-form>
            </el-col>
        </el-row>
    </el-card>
</template>
