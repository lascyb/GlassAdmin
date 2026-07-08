<script setup lang="ts" generic="T extends { id: number; children?: T[] }">
import AddChildSvg from '@/assets/icons/add_child.svg';
import { Delete, Edit } from '@element-plus/icons-vue';

withDefaults(
    defineProps<{
        /** data 树形表格数据 */
        data?: T[]
        /** loading 加载态 */
        loading?: boolean
        /** rowKey 行主键字段 */
        rowKey?: string
        /** defaultExpandAll 默认展开全部 */
        defaultExpandAll?: boolean
        /** showAddChild 是否显示添加子节点按钮 */
        showAddChild?: boolean
        /** actionWidth 操作列宽度 */
        actionWidth?: string | number
    }>(),
    {
        data: () => [],
        loading: false,
        rowKey: 'id',
        defaultExpandAll: true,
        showAddChild: true,
        actionWidth: 190,
    },
)

const emit = defineEmits<{
    edit: [row: T]
    delete: [row: T]
    addChild: [row: T]
}>()
</script>

<template>
    <el-table
        v-loading="loading"
        :data="data"
        :row-key="rowKey"
        :default-expand-all="defaultExpandAll"
        stripe
        highlight-current-row
        :tree-props="{ children: 'children' }"
    >
        <slot />
        <el-table-column label="操作" fixed="right" :width="actionWidth">
            <template #default="{ row }">
                <slot name="actions" :row="row">
                    <el-button v-auth="'edit'" title="编辑" plain type="primary" :icon="Edit" @click="emit('edit', row)" />
                    <el-button
                        v-if="showAddChild"
                        v-auth="'add'"
                        title="添加子节点"
                        plain
                        type="warning"
                        :icon="AddChildSvg"
                        @click="emit('addChild', row)"
                    />
                    <el-button v-auth="'del'" title="删除" plain type="danger" :icon="Delete" @click="emit('delete', row)" />
                </slot>
            </template>
        </el-table-column>
    </el-table>
</template>
