<script setup lang="ts">
import { useDevCurd } from '@/pinia/dev/curd'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import {
    FIELD_CONTROL_OPTIONS,
    FIELD_TEMPLATE_OPTIONS,
    applyControlIntent,
    buildFieldFromTemplate,
    detectControlIntent,
    type FieldControlIntent,
    type FieldTemplateId,
} from '../fieldIntent'
import { fillPivotDefaults } from '../helpers'
import ColumnConfigCard from './field-detail/ColumnConfigCard.vue'
import FormConfigCard from './field-detail/FormConfigCard.vue'
import ListConfigCard from './field-detail/ListConfigCard.vue'
import RelationConfigCard from './field-detail/RelationConfigCard.vue'

const store = useDevCurd()
const { config, selectedField: field, selectedIndex } = storeToRefs(store)

/** intent 当前字段类型 */
const intent = computed({
    get: (): FieldControlIntent => (field.value ? detectControlIntent(field.value) : 'text'),
    set: (v: FieldControlIntent) => {
        if (!field.value) return
        applyControlIntent(field.value, v, config.value)
    },
})

/** applyTemplate 套用模板到当前字段（覆盖五层） */
const applyTemplate = (id: FieldTemplateId) => {
    const idx = selectedIndex.value
    if (idx < 0 || idx >= config.value.fields.length) return
    config.value.fields[idx] = buildFieldFromTemplate(id, idx, config.value)
}

/** onPropBlur 远程多选改 prop 后刷新中间表默认（仅空值时） */
const onPropBlur = () => {
    if (field.value && detectControlIntent(field.value) === 'remote_multi') {
        fillPivotDefaults(field.value, config.value)
    }
}
</script>

<template>
    <el-card shadow="never" class="!border h-full flex flex-col min-h-0">
        <el-empty v-if="!field" description="请选择左侧字段" :image-size="72" />
        <div v-else class="overflow-y-auto min-h-0 flex-1 space-y-3 pr-1">
            <!-- 身份 -->
            <el-card shadow="never" class="!border">
                <template #header>
                    <span class="text-sm font-medium">身份</span>
                </template>
                <el-form label-width="80px" size="small">
                    <el-form-item label="字段名" required>
                        <el-input v-model="field.prop" class="!w-56 max-w-full" placeholder="prop" @blur="onPropBlur" />
                    </el-form-item>
                    <el-form-item label="中文标签" required>
                        <el-input v-model="field.label" class="!w-56 max-w-full" placeholder="label" />
                    </el-form-item>
                    <el-form-item label="套用模板">
                        <el-dropdown trigger="click" @command="(id: FieldTemplateId) => applyTemplate(id)">
                            <el-button size="small">覆盖为模板配置</el-button>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item
                                        v-for="t in FIELD_TEMPLATE_OPTIONS"
                                        :key="t.id"
                                        :command="t.id"
                                    >
                                        {{ t.advanced ? `${t.label}（高级）` : t.label }}
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </el-form-item>
                </el-form>
            </el-card>

            <!-- 字段类型 -->
            <el-card shadow="never" class="!border">
                <template #header>
                    <span class="text-sm font-medium">字段类型</span>
                </template>
                <el-radio-group v-model="intent" class="flex flex-wrap gap-y-2">
                    <el-radio-button
                        v-for="o in FIELD_CONTROL_OPTIONS"
                        :key="o.value"
                        :value="o.value"
                    >
                        {{ o.label }}
                    </el-radio-button>
                </el-radio-group>
                <div class="text-xs text-gray-400 mt-2">切换字段类型将按业务语义重算列 / 搜 / 表 / 库列 / 关联默认</div>
            </el-card>
            <el-row :gutter="8">
             <el-col :span="24">
                 <ColumnConfigCard />
             </el-col>
            </el-row>
            <ListConfigCard />
            <FormConfigCard />

            <RelationConfigCard />
        </div>
    </el-card>
</template>
