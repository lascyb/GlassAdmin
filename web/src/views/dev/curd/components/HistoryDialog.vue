<script setup lang="ts">
import * as curdApi from '@/apis/dev/curd'
import type { HistoryRow } from '@/apis/dev/curd'
import { ElMessage } from 'element-plus'
import { ref, watch } from 'vue'

const visible = defineModel<boolean>('modelValue', { required: true })
const emit = defineEmits<{ select: [id: number] }>()

const loading = ref(false)
const list = ref<HistoryRow[]>([])
const total = ref(0)
const page = ref(1)

/** load 加载历史分页 */
const load = async () => {
    loading.value = true
    try {
        const res = await curdApi.listHistory({ page: page.value, limit: 10 })
        list.value = res.list
        total.value = res.total
    } catch (e: unknown) {
        list.value = []
        ElMessage.error(e instanceof Error ? e.message : '加载历史失败')
    } finally {
        loading.value = false
    }
}

watch(visible, (v) => {
    if (v) {
        page.value = 1
        void load()
    }
})

/** onSelect 选中历史 */
const onSelect = (id: number) => {
    emit('select', id)
    visible.value = false
}
</script>

<template>
    <el-dialog v-model="visible" title="选择历史记录" width="640px" destroy-on-close>
        <el-table v-loading="loading" :data="list" size="small" empty-text="暂无历史" max-height="400">
            <el-table-column prop="id" label="ID" width="72" />
            <el-table-column prop="name" label="方案" min-width="160" show-overflow-tooltip />
            <el-table-column prop="create_time" label="时间" width="170" />
            <el-table-column label="操作" width="88" fixed="right">
                <template #default="{ row }">
                    <el-button link type="primary" @click="onSelect(row.id)">选择</el-button>
                </template>
            </el-table-column>
        </el-table>
        <div class="flex justify-end mt-3" v-if="total > 10">
            <el-pagination
                v-model:current-page="page"
                layout="prev, pager, next"
                :total="total"
                :page-size="10"
                small
                @current-change="load"
            />
        </div>
    </el-dialog>
</template>
