<script setup lang="ts">
import { LIST_RENDER_TYPE_LABEL, type ListRenderType } from '@/crud/types'
import { useDevCurd } from '@/pinia/dev/curd'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { detectControlIntent } from '../../fieldIntent'
import { applyListRenderGuess, fillPivotDefaults } from '../../helpers'
import InlineOptionsEditor from '../InlineOptionsEditor.vue'
import SearchConfigCard from './SearchConfigCard.vue'

const store = useDevCurd()
const { config, selectedField: field } = storeToRefs(store)

/** hasList 是否显示列表列 */
const hasList = computed({
    get: () => !!field.value?.list,
    set: (v: boolean) => {
        if (!field.value) return
        if (v) {
            field.value.list = field.value.list ?? { render: 'text', minWidth: 120 }
        } else {
            delete field.value.list
        }
    },
})

/** onListRenderChange 切换列表渲染并推测填充搜索/表单 */
const onListRenderChange = (render: ListRenderType) => {
    if (!field.value) return
    if (!field.value.list) field.value.list = { render, minWidth: 120 }
    else field.value.list.render = render
    applyListRenderGuess(field.value, render)
}

/** intent 当前字段类型 */
const intent = computed(() => (field.value ? detectControlIntent(field.value) : 'text'))

/** showEnum 枚举配套区 */
const showEnum = computed(() => intent.value === 'status' || intent.value === 'select')

/** showRemote 远程数据源 */
const showRemote = computed(() => intent.value === 'remote' || intent.value === 'remote_multi')

/** showNumber 数字 min/max */
const showNumber = computed(() => intent.value === 'number' || intent.value === 'amount')

/** showPivot 多对多中间表 */
const showPivot = computed(() => intent.value === 'remote_multi')

/** formCfg 表单对象（启用时） */
const formCfg = computed(() => {
    const f = field.value?.form
    if (!f || f === false) return null
    return f
})

/** onFillPivot 中间表空时自动填充 */
const onFillPivot = () => {
    if (field.value) fillPivotDefaults(field.value, config.value)
}
</script>

<template>
    <el-card v-if="field" shadow="never" class="!border">
        <template #header>
            <span class="text-sm font-medium">列表相关</span>
        </template>
        <div class="space-y-4">
            <!-- 列表渲染 -->
            <section class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-medium text-gray-500">列表渲染</span>
                    <el-switch v-model="hasList" active-text="显示列" size="small" />
                </div>
                <el-form v-if="field.list" label-width="80px" size="small">
                    <el-form-item label="列渲染" required>
                        <el-select
                            :model-value="field.list.render ?? 'text'"
                            class="!w-52 max-w-full"
                            @update:model-value="onListRenderChange"
                        >
                            <el-option v-for="(label, value) in LIST_RENDER_TYPE_LABEL" :key="value" :label="label" :value="value"/>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="列宽">
                        <el-input
                            class="!w-40 max-w-full"
                            :model-value="field.list.width == null ? '' : String(field.list.width)"
                            placeholder="列宽"
                            @update:model-value="
                                (v: string) => {
                                    if (!field?.list) return
                                    if (v === '') delete field.list.width
                                    else field.list.width = /^\d+$/.test(v) ? Number(v) : v
                                }
                            "
                        />
                    </el-form-item>
                    <el-form-item label="最小列宽">
                        <el-input
                            class="!w-40 max-w-full"
                            :model-value="field.list.minWidth == null ? '' : String(field.list.minWidth)"
                            placeholder="最小列宽"
                            @update:model-value="
                                (v: string) => {
                                    if (!field?.list) return
                                    if (v === '') delete field.list.minWidth
                                    else field.list.minWidth = /^\d+$/.test(v) ? Number(v) : v
                                }
                            "
                        />
                    </el-form-item>
                    <el-form-item label="关联展示">
                        <el-input
                            v-model="field.list.relationProp"
                            class="!w-52 max-w-full"
                            placeholder="如 category.name"
                        />
                    </el-form-item>
                    <el-form-item label="状态映射">
                        <el-input
                            v-model="field.list.statusMapKey"
                            class="!w-52 max-w-full"
                            placeholder="STATUS_MAP"
                        />
                    </el-form-item>
                    <el-form-item label="选项键">
                        <el-input
                            v-model="field.list.optionsKey"
                            class="!w-52 max-w-full"
                            placeholder="静态选项键"
                        />
                    </el-form-item>
                </el-form>
            </section>

            <!-- 配套 -->
            <section v-if="showEnum || showRemote || showNumber || showPivot" class="space-y-2">
                <div class="text-xs font-medium text-gray-500">配套</div>

                <InlineOptionsEditor v-if="showEnum" />

                <el-form v-if="showRemote && formCfg" label-width="80px" size="small">
                    <el-form-item label="数据源" required>
                        <el-input
                            v-model="formCfg.optionsKey"
                            class="!w-72 max-w-full"
                            placeholder="如 example.Category/list"
                        />
                        <div class="text-xs text-gray-400 mt-1">格式：模块.实体/list，供 ga-select-remote</div>
                    </el-form-item>
                </el-form>

                <el-form v-if="showNumber && formCfg" label-width="80px" size="small">
                    <el-form-item label="最小值">
                        <el-input-number v-model="formCfg.numberMin" controls-position="right" class="!w-36" />
                    </el-form-item>
                    <el-form-item label="最大值">
                        <el-input-number v-model="formCfg.numberMax" controls-position="right" class="!w-36" />
                    </el-form-item>
                    <el-form-item v-if="intent === 'amount'" label="步进">
                        <el-input-number v-model="formCfg.numberStep" controls-position="right" class="!w-36" />
                    </el-form-item>
                </el-form>

                <el-form v-if="showPivot && field.relation" label-width="80px" size="small">
                    <el-form-item label="中间表">
                        <div class="flex flex-wrap items-center gap-2">
                            <el-input
                                v-model="field.relation.pivotTable"
                                class="!w-56 max-w-full"
                                placeholder="多对多中间表"
                            />
                            <el-button size="small" @click="onFillPivot">自动填充</el-button>
                        </div>
                    </el-form-item>
                    <el-form-item label="本表外键">
                        <el-input v-model="field.relation.foreignKey" class="!w-56 max-w-full" />
                    </el-form-item>
                    <el-form-item label="关联外键">
                        <el-input v-model="field.relation.relatedKey" class="!w-56 max-w-full" />
                    </el-form-item>
                </el-form>
            </section>

            <!-- 搜索 -->
            <SearchConfigCard />
        </div>
    </el-card>
</template>
