<script setup lang="ts">
const page = defineModel<number>('page', { default: 1 })
const limit = defineModel<number>('limit', { default: 20 })

withDefaults(
    defineProps<{
        /** loading 表格 loading */
        loading?: boolean
        /** list 表格数据 */
        list?: unknown[]
        /** total 总条数 */
        total?: number
        /** emptyText 空态文案 */
        emptyText?: string
        /** pageSizes 每页条数选项 */
        pageSizes?: number[]
        /** showPagination 是否显示分页 */
        showPagination?: boolean
        /** border 表格 border */
        border?: boolean
        /** stripe 斑马纹 */
        stripe?: boolean
        /** size 表格 size */
        size?: 'default' | 'small' | 'large'
    }>(),
    {
        loading: false,
        list: () => [],
        total: 0,
        emptyText: '暂无数据',
        pageSizes: () => [10, 20, 50],
        showPagination: true,
        border: true,
        stripe: true,
        size: 'default',
    },
)

const emit = defineEmits<{ load: [] }>()

const onPageChange = () => emit('load')
const onSizeChange = () => emit('load')
</script>

<template>
    <div class="ga-table">
        <slot name="toolbar" />
        <el-table
            v-loading="loading"
            :data="list"
            :border="border"
            :stripe="stripe"
            :size="size"
            highlight-current-row
            :empty-text="emptyText"
        >
            <slot />
        </el-table>
        <div v-if="showPagination" class="mt-3 flex justify-end p-2">
            <el-pagination
                v-model:current-page="page"
                v-model:page-size="limit"
                :total="total"
                :page-sizes="pageSizes"
                layout="total, sizes, prev, pager, next"
                background
                @current-change="onPageChange"
                @size-change="onSizeChange"
            />
        </div>
        <slot name="footer" />
    </div>
</template>
