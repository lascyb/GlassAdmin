<script setup lang="ts">
import * as curdApi from '@/apis/dev/curd'
import { ElMessage } from 'element-plus'
import { computed, ref, watch } from 'vue'

type TableRow = { name: string }

const visible = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ select: [table: string] }>()

const loading = ref(false)
const tables = ref<string[]>([])
const keyword = ref('')
const selected = ref('')

const filtered = computed<TableRow[]>(() => {
    const kw = keyword.value.trim().toLowerCase()
    const list = kw ? tables.value.filter((t) => t.toLowerCase().includes(kw)) : tables.value
    return list.map((name) => ({ name }))
})

/** load 加载表列表 */
const load = async () => {
    loading.value = true
    try {
        tables.value = await curdApi.listTables()
    } catch (e: unknown) {
        tables.value = []
        ElMessage.error(e instanceof Error ? e.message : '加载表列表失败')
    } finally {
        loading.value = false
    }
}

watch(visible, (v) => {
    if (v) {
        keyword.value = ''
        selected.value = ''
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
    emit('select', selected.value)
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
