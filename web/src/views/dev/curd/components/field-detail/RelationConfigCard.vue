<script setup lang="ts">
import type { CrudFieldRelationConfig, RelationType } from '@/crud/types'
import { useDevCurd } from '@/pinia/dev/curd'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { RELATION_TYPE_OPTIONS } from '../../helpers'

const store = useDevCurd()
const { selectedField: field } = storeToRefs(store)

/** hasRelation 是否配置关联 */
const hasRelation = computed({
    get: () => !!field.value?.relation,
    set: (v: boolean) => {
        if (!field.value) return
        if (v) {
            field.value.relation = field.value.relation ?? { type: 'belongs_to' }
        } else {
            delete field.value.relation
        }
    },
})

/** ensureRelation 保证 relation 对象 */
const ensureRelation = (): CrudFieldRelationConfig | null => {
    if (!field.value || !hasRelation.value) return null
    if (!field.value.relation) field.value.relation = { type: 'belongs_to' }
    return field.value.relation
}

/** onRelationTypeChange 切换关联类型 */
const onRelationTypeChange = (type: RelationType) => {
    const r = ensureRelation()
    if (r) r.type = type
}
</script>

<template>
    <el-card v-if="field" shadow="never" class="!border">
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-medium">关联</span>
                <el-switch v-model="hasRelation" active-text="启用关联" />
            </div>
        </template>
        <el-form v-if="field.relation" label-width="80px" size="small">
            <el-form-item label="关联类型">
                <el-select
                    :model-value="field.relation.type"
                    class="!w-52 max-w-full"
                    @update:model-value="onRelationTypeChange"
                >
                    <el-option
                        v-for="o in RELATION_TYPE_OPTIONS"
                        :key="o.value"
                        :label="o.label"
                        :value="o.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="中间表">
                <el-input
                    v-model="field.relation.pivotTable"
                    class="!w-52 max-w-full"
                    placeholder="多对多中间表"
                />
            </el-form-item>
            <el-form-item label="本表外键">
                <el-input
                    v-model="field.relation.foreignKey"
                    class="!w-52 max-w-full"
                    placeholder="指向主实体"
                />
            </el-form-item>
            <el-form-item label="关联外键">
                <el-input
                    v-model="field.relation.relatedKey"
                    class="!w-52 max-w-full"
                    placeholder="指向关联实体"
                />
            </el-form-item>
        </el-form>
    </el-card>
</template>
