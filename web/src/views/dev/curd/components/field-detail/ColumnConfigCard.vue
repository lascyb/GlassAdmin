<script setup lang="ts">
import { useDevCurdField } from '@/pinia/dev/curdField'
import { storeToRefs } from 'pinia'
import { FIELD_TYPES } from '../../helpers'

const fieldStore = useDevCurdField()
const { selectedField: field, hasColumn } = storeToRefs(fieldStore)
</script>

<template>
    <el-card v-if="field" shadow="never">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-medium">数据库列</span>
                <el-switch v-model="hasColumn" active-text="有物理列" inactive-text="无列（如多对多）" />
            </div>
        </template>

        <el-form v-if="hasColumn" label-width="80px" size="small">
            <el-form-item label="列定义">
                <table class="col-table">
                    <thead>
                    <tr>
                        <th>列类型</th>
                        <th>长度</th>
                        <th>总位数</th>
                        <th>小数位</th>
                        <th>允许空</th>
                        <th>主键</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>
                            <el-select
                                v-model="field.column.type"
                                filterable
                                allow-create
                                default-first-option
                                placeholder="类型"
                                style="width: 120px"
                            >
                                <el-option v-for="t in FIELD_TYPES" :key="t" :label="t" :value="t" />
                            </el-select>
                        </td>
                        <td>
                            <el-input-number v-model="field.column.length" :min="0" :controls="false" />
                        </td>
                        <td>
                            <el-input-number v-model="field.column.precision" :min="0" :controls="false" />
                        </td>
                        <td>
                            <el-input-number v-model="field.column.scale" :min="0" :controls="false" />
                        </td>
                        <td>
                            <el-checkbox v-model="field.column.nullable" size="default"/>
                        </td>
                        <td>
                            <el-checkbox v-model="field.column.primary" size="default"/>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </el-form-item>
            <el-form-item label="约束">
                <div class="flex flex-wrap gap-x-3 gap-y-1">
                    <el-checkbox v-model="field.column.autoIncrement">自动递增</el-checkbox>
                    <el-checkbox v-model="field.column.unsigned">无符号</el-checkbox>
                    <el-checkbox v-model="field.column.unique">唯一</el-checkbox>
                </div>
            </el-form-item>
            <el-form-item label="默认值">
                <el-input size="small"
                    :model-value="field.column.default == null ? '' : String(field.column.default)"
                    placeholder="空=不设；null=DEFAULT NULL"
                    @update:model-value="(v: string) => fieldStore.setColumnDefault(v)"
                />
            </el-form-item>
            <el-form-item label="列注释">
                <el-input type="textarea" size="small" v-model="field.column.comment" placeholder="列注释" />
            </el-form-item>
        </el-form>
    </el-card>
</template>

<style scoped>
.col-table {
    width: 100%;
    border-collapse: collapse;
}
.col-table th,
.col-table td {
    border: 1px solid var(--el-border-color);
    padding: 6px 8px;
}
.col-table th {
    background: var(--el-fill-color-light);
    text-align: left;
}
</style>
