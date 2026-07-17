<script setup lang="ts">
import { useDevCurdField } from '@/pinia/dev/curdField'
import type { RelationType } from '@/views/dev/curd/types'
import { storeToRefs } from 'pinia'

const fieldStore = useDevCurdField()
const { selectedField: field, hasRelation } = storeToRefs(fieldStore)
</script>

<template>
    <el-card v-if="field" shadow="never">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-medium">关联</span>
                <el-switch v-model="hasRelation" active-text="启用关联" />
            </div>
        </template>
        <el-form v-if="hasRelation" label-width="80px" size="small">
            <el-form-item label="关联类型">
                <el-select
                    :model-value="field.relation.type"
                    class="!w-52 max-w-full"
                    @update:model-value="(v: RelationType) => fieldStore.setRelationType(v)"
                >
                    <el-option label="一对一" value="belongs_to" />
                    <el-option label="多对多" value="belongs_to_many" />
                </el-select>
            </el-form-item>
            <el-form-item v-if="field.relation.type === 'belongs_to_many'" label="中间表">
                <el-input
                    v-model="field.relation.pivotTable"
                    class="!w-52 max-w-full"
                    placeholder="多对多中间表"
                />
            </el-form-item>
            <el-form-item v-if="field.relation.type === 'belongs_to_many'" label="本表外键">
                <el-input
                    v-model="field.relation.foreignKey"
                    class="!w-52 max-w-full"
                    placeholder="指向主实体"
                />
            </el-form-item>
            <el-form-item v-if="field.relation.type === 'belongs_to_many'" label="关联外键">
                <el-input
                    v-model="field.relation.relatedKey"
                    class="!w-52 max-w-full"
                    placeholder="指向关联实体"
                />
            </el-form-item>
        </el-form>
    </el-card>
</template>
