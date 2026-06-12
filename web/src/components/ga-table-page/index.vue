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
        /** minHeight 最小高度 */
        minHeight?: string
        /** border 表格 border */
        border?: boolean
        /** stripe 斑马纹 */
        stripe?: boolean
        /** bare 不包裹 el-card（嵌入 ga-page 等已有容器时使用） */
        bare?: boolean
    }>(),
    {
        loading: false,
        list: () => [],
        total: 0,
        emptyText: '暂无数据',
        pageSizes: () => [10, 20, 50],
        showPagination: true,
        minHeight: 'max(calc(100vh - 100px), 400px)',
        border: true,
        stripe: true,
        bare: false,
    },
)

const emit = defineEmits<{ load: [] }>()

const onPageChange = () => emit('load')
const onSizeChange = () => emit('load')
</script>

<template>
    <component :is="bare ? 'div' : 'el-card'" :style="bare ? undefined : { minHeight }" :body-style="bare ? undefined : 'padding:0'">
        <slot name="toolbar" />
        <el-table
            v-loading="loading"
            :data="list"
            :border="border"
            :stripe="stripe"
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
    </component>
</template>
