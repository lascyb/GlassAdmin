<script setup lang="ts">
import * as curdApi from '@/apis/dev/curd'
import { useDevCurd } from '@/pinia/dev/curd'
import { useDevCurdGlobal } from '@/pinia/dev/curdGlobal'
import GaPicker from '@components/ga-icon/picker.vue'
import { ArrowDown, ArrowUp, Back, Delete, Plus } from '@element-plus/icons-vue'
import { storeToRefs } from 'pinia'
import { computed, onMounted, ref } from 'vue'

const curd = useDevCurd()
const global = useDevCurdGlobal()
const {
    table,
    connection,
    menus,
    paths,
    generateAuth,
    force,
    noAuth,
    saveHistory,
    leafTitle,
} = storeToRefs(global)

const collapsed = ref(true)
const connections = ref<curdApi.DbConnectionItem[]>([])
const defaultConnection = ref('')

/** defaultLabel 默认连接选项文案 */
const defaultLabel = computed(() =>
    defaultConnection.value ? `默认（${defaultConnection.value}）` : '默认连接',
)

onMounted(async () => {
    try {
        const res = await curdApi.listConnections()
        connections.value = res.connections
        defaultConnection.value = res.default
    } catch {
        connections.value = []
    }
})
</script>

<template>
    <el-card shadow="never" class="!border">
        <el-row justify="space-between" align="middle" :gutter="12">
            <el-col :xs="24" :sm="16">
                <el-space wrap :size="8" alignment="center">
                    <el-text>数据表名</el-text>
                    <el-input
                        :model-value="table"
                        style="width: 200px"
                        placeholder="如 countries 或 demo_item"
                        @update:model-value="global.onTableInput"
                    />
                    <el-text>数据库连接</el-text>
                    <el-select v-model="connection" style="width: 160px" @change="global.syncMeta">
                        <el-option :value="''" :label="defaultLabel" />
                        <el-option
                            v-for="item in connections"
                            :key="item.name"
                            :value="item.name"
                            :label="item.name"
                        />
                    </el-select>
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
                    <el-button :icon="Back" @click="curd.goBack()">返回入口</el-button>
                    <el-button type="primary" plain @click="curd.runGenerate(true)">预览</el-button>
                    <el-button type="primary" @click="curd.runGenerate(false)">生成</el-button>
                </el-space>
            </el-col>
        </el-row>
        <el-row v-show="!collapsed" :gutter="24" class="mt-3">
            <el-col :xs="24" :md="12">
                <el-divider content-position="left">代码与写盘</el-divider>
                <el-form label-width="120px" style="width: 500px">
                    <el-text type="info" size="small" class="mb-2" style="display: block; padding-left: 120px">
                        前缀固定，仅编辑相对路径
                    </el-text>
                    <el-form-item label="控制器">
                        <el-input v-model="paths.controller" @change="global.syncMeta">
                            <template #prepend>app/admin/controller/</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="模型">
                        <el-input v-model="paths.model" @change="global.syncMeta">
                            <template #prepend>app/admin/model/</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="验证器">
                        <el-input v-model="paths.validate" @change="global.syncMeta">
                            <template #prepend>app/admin/validate/</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="前端 API">
                        <el-input v-model="paths.apis" @change="global.syncMeta">
                            <template #prepend>web/src/apis/</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="前端视图目录">
                        <el-input v-model="paths.viewsDir" @change="global.syncMeta">
                            <template #prepend>web/src/views/</template>
                        </el-input>
                    </el-form-item>
                    <el-form-item label="可选操作">
                        <el-checkbox v-model="force">强制覆盖已有文件</el-checkbox>
                        <el-checkbox v-model="saveHistory">写入生成历史</el-checkbox>
                    </el-form-item>
                </el-form>
            </el-col>
            <el-col :xs="24" :md="12">
                <el-divider content-position="left">权限与菜单</el-divider>
                <el-form>
                    <el-form-item label-position="top">
                        <div class="mb-2">
                            <el-button size="small" :icon="Plus" @click="global.addMenuLevel">
                                添加菜单规则
                            </el-button>
                        </div>
                        <el-table :data="menus" border size="small" style="width: 100%">
                            <el-table-column label="级别" width="64" align="center">
                                <template #default="{ $index }">
                                    {{ global.levelLabel($index) }}
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
                                    <el-input
                                        v-model="row.path"
                                        size="small"
                                        @change="global.onMenusChange"
                                    />
                                </template>
                            </el-table-column>
                            <el-table-column label="title" min-width="90">
                                <template #default="{ row }">
                                    <el-input
                                        v-model="row.title"
                                        size="small"
                                        @change="global.onMenusChange"
                                    />
                                </template>
                            </el-table-column>
                            <el-table-column label="图标" width="60" align="center">
                                <template #default="{ row }">
                                    <GaPicker
                                        v-model="row.icon"
                                        hide-name
                                        @update:model-value="global.onMenusChange"
                                    />
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
                                        @change="global.onMenusChange"
                                    />
                                </template>
                            </el-table-column>
                            <el-table-column label="启用" width="64" align="center">
                                <template #default="{ row }">
                                    <el-switch v-model="row.enabled" @change="global.onMenusChange" />
                                </template>
                            </el-table-column>
                            <el-table-column label="" width="48" align="center">
                                <template #default="{ $index }">
                                    <el-button
                                        v-if="$index < menus.length - 1"
                                        link
                                        type="danger"
                                        :icon="Delete"
                                        @click="global.removeMenuAt($index)"
                                    />
                                </template>
                            </el-table-column>
                        </el-table>
                        <el-text type="info" size="small" class="mt-1" style="display: block">
                            末行为页面；关闭启用后本级不写入，下级升一级
                        </el-text>
                    </el-form-item>
                    <el-form-item label="可选操作">
                        <el-checkbox v-model="generateAuth" @change="global.syncMeta">
                            生成权限规则
                        </el-checkbox>
                        <el-checkbox v-model="noAuth">不写入权限/菜单</el-checkbox>
                    </el-form-item>
                </el-form>
            </el-col>
        </el-row>
    </el-card>
</template>
