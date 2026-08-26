<script setup lang="ts">
import * as curdApi from '@/apis/dev/curd'
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'

type TableRow = { name: string }

const visible = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ select: [table: string, connection: string] }>()

const loading = ref(false)
const tables = ref<string[]>([])
const connections = ref<curdApi.DbConnectionItem[]>([])
const connection = ref('')
const keyword = ref('')
const selected = ref('')

const filtered = computed<TableRow[]>(() => {
    const kw = keyword.value.trim().toLowerCase()
    const list = kw ? tables.value.filter((t) => t.toLowerCase().includes(kw)) : tables.value
    return list.map((name) => ({ name }))
})

/** connectionHint 当前连接的库信息 */
const connectionHint = computed(() => {
    const item = connections.value.find((c) => c.name === connection.value)
    if (!item) return ''
    const database = item.database || '未配置库名'
    return item.prefix ? `${database}（前缀 ${item.prefix}）` : database
})

/** loadConnections 加载可选连接，默认选中默认连接 */
const loadConnections = async () => {
    try {
        const res = await curdApi.listConnections()
        connections.value = res.connections
        connection.value = res.default
    } catch (e: unknown) {
        connections.value = []
        connection.value = ''
        ElMessage.error(e instanceof Error ? e.message : '加载数据库连接失败')
    }
}

/** load 加载表列表 */
const load = async () => {
    loading.value = true
    try {
        tables.value = await curdApi.listTables(connection.value)
    } catch (e: unknown) {
        tables.value = []
        ElMessage.error(e instanceof Error ? e.message : '加载表列表失败')
    } finally {
        loading.value = false
    }
}

/** onConnectionChange 切换连接后重新拉表 */
const onConnectionChange = () => {
    selected.value = ''
    void load()
}

watch(visible, async (v) => {
    if (v) {
        keyword.value = ''
        selected.value = ''
        await loadConnections()
        void load()
    }
})

/** onRowClick 单击选中 */
const onRowClick = (row: TableRow) => {
    selected.value = row.name
}

/** onConfirm 确认选择 */
const onConfirm = () => {
    if (!selected.value) {
        ElMessage.warning('请选择数据表')
        return
    }
    emit('select', selected.value, connection.value)
    visible.value = false
}

/** onDblClick 双击直接确认 */
const onDblClick = (row: TableRow) => {
    selected.value = row.name
    onConfirm()
}
</script>

<template>
    <el-dialog v-model="visible" title="选择数据库表" width="520px" destroy-on-close>
        <el-form label-width="100px" class="mb-2">
            <el-form-item label="数据库连接">
                <el-select v-model="connection" style="width: 100%" @change="onConnectionChange">
                    <el-option
                        v-for="item in connections"
                        :key="item.name"
                        :value="item.name"
                        :label="item.name"
                    >
                        <span>{{ item.name }}</span>
                        <el-tag v-if="item.default" size="small" type="success" class="ml-2">默认</el-tag>
                        <span class="ml-2 text-xs text-gray-400">{{ item.database }}</span>
                    </el-option>
                </el-select>
                <el-text v-if="connectionHint" type="info" size="small">{{ connectionHint }}</el-text>
            </el-form-item>
        </el-form>
        <el-input v-model="keyword" placeholder="筛选表名" clearable class="mb-3" />
        <el-table
            v-loading="loading"
            :data="filtered"
            size="small"
            empty-text="无可用表"
            max-height="360"
            highlight-current-row
            @row-click="onRowClick"
            @row-dblclick="onDblClick"
        >
            <el-table-column prop="name" label="表名" min-width="200" />
        </el-table>
        <div v-if="selected" class="mt-2 text-xs text-gray-500">已选：{{ selected }}</div>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :disabled="!selected" @click="onConfirm">确定</el-button>
        </template>
    </el-dialog>
</template>
