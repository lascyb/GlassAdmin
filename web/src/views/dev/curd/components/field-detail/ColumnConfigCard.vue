<script setup lang="ts">
import { useDevCurd } from '@/pinia/dev/curd'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { FIELD_TYPES, ensureColumn } from '../../helpers'

const store = useDevCurd()
const { selectedField: field } = storeToRefs(store)

/** hasColumn 是否有物理列 */
const hasColumn = computed({
    get: () => !!field.value?.column,
    set: (v: boolean) => {
        if (!field.value) return
        if (v) {
            ensureColumn(field.value)
        } else {
            delete field.value.column
        }
    },
})

/** columnRows el-table 单行数据源（当前列） */
const columnRows = computed(() => (field.value?.column ? [field.value.column] : []))

/** onDefaultChange 默认值解析写回（空/null/数字/字符串） */
const onDefaultChange = (v: string) => {
    if (!field.value?.column) return
    if (v === '') delete field.value.column.default
    else if (v === 'null') field.value.column.default = null
    else if (/^-?\d+(\.\d+)?$/.test(v)) field.value.column.default = Number(v)
    else field.value.column.default = v
}
</script>

<template>
    <el-card v-if="field" shadow="never" class="!border">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-medium">数据库列</span>
                <el-switch v-model="hasColumn" active-text="有物理列" inactive-text="无列（如多对多）" />
            </div>
        </template>
        <el-form inline label-position="top">
            <el-form-item label="列类型" >
                <el-select v-model="field.column.type" size="small" style="width:100px">
                    <el-option v-for="t in FIELD_TYPES" :key="t" :label="t" :value="t" />
                </el-select>
            </el-form-item>
        </el-form>
        <el-table v-if="field.column" :data="columnRows" size="small" border class="w-full">
            <el-table-column label="列类型" min-width="120">
                <template #default="{ row }">
                    <el-select v-model="row.type" size="small" class="w-full">
                        <el-option v-for="t in FIELD_TYPES" :key="t" :label="t" :value="t" />
                    </el-select>
                </template>
            </el-table-column>
            <el-table-column label="长度" width="96">
                <template #default="{ row }">
                    <el-input-number v-model="row.length" :min="0" :controls="false" size="small" class="w-full" />
                </template>
            </el-table-column>
            <el-table-column label="总位数" width="96">
                <template #default="{ row }">
                    <el-input-number v-model="row.precision" :min="0" :controls="false" size="small" class="w-full" />
                </template>
            </el-table-column>
            <el-table-column label="小数位" width="96">
                <template #default="{ row }">
                    <el-input-number v-model="row.scale" :min="0" :controls="false" size="small" class="w-full" />
                </template>
            </el-table-column>
            <el-table-column label="默认值" min-width="150">
                <template #default="{ row }">
                    <el-input
                        size="small"
                        :model-value="row.default == null ? '' : String(row.default)"
                        placeholder="空=不设；null=DEFAULT NULL"
                        @update:model-value="onDefaultChange"
                    />
                </template>
            </el-table-column>
            <el-table-column label="列注释" min-width="150">
                <template #default="{ row }">
                    <el-input v-model="row.comment" size="small" placeholder="列注释" />
                </template>
            </el-table-column>
        </el-table>
        <div class="flex flex-wrap gap-x-3 gap-y-1">
            <el-checkbox v-model="field.unsigned" size="small">无符号</el-checkbox>
            <el-checkbox v-model="field.nullable" size="small">允许空</el-checkbox>
            <el-checkbox v-model="field.primary" size="small">主键</el-checkbox>
            <el-checkbox v-model="field.autoIncrement" size="small">自增</el-checkbox>
            <el-checkbox v-model="field.unique" size="small">唯一</el-checkbox>
        </div>
    </el-card>
</template>
