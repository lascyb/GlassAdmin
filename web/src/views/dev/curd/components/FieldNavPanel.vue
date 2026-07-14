<script setup lang="ts">
import type { CrudFieldSchema } from '@/crud/types'
import { useDevCurd } from '@/pinia/dev/curd'
import { Delete, Plus, Rank } from '@element-plus/icons-vue'
import { storeToRefs } from 'pinia'
import Sortable from 'sortablejs'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import {
    FIELD_CONTROL_OPTIONS,
    FIELD_TEMPLATE_OPTIONS,
    applyControlIntent,
    detectControlIntent,
    type FieldControlIntent,
    type FieldTemplateId,
} from '../fieldIntent'

const store = useDevCurd()
const { config, selectedIndex } = storeToRefs(store)

const listRef = ref<HTMLElement | null>(null)
let sortable: Sortable | null = null

/** initSortable 初始化拖拽 */
const initSortable = () => {
    sortable?.destroy()
    sortable = null
    if (!listRef.value) return
    sortable = Sortable.create(listRef.value, {
        animation: 150,
        handle: '.drag-handle',
        draggable: '.field-item',
        onEnd: (evt) => {
            const { oldIndex, newIndex } = evt
            if (oldIndex == null || newIndex == null || oldIndex === newIndex) return
            store.reorderFields(oldIndex, newIndex)
        },
    })
}

onMounted(() => {
    void nextTick(initSortable)
})

watch(
    () => config.value.fields.length,
    () => void nextTick(initSortable),
)

onBeforeUnmount(() => {
    sortable?.destroy()
    sortable = null
})

/** addByTemplate 按模板添加字段 */
const addByTemplate = (id: FieldTemplateId) => {
    store.addField(id)
}

/** onIntentChange 切换字段类型并按业务语义重算五层默认 */
const onIntentChange = (field: CrudFieldSchema, v: FieldControlIntent) => {
    applyControlIntent(field, v, config.value)
}

/** toggleList 列能力增删 */
const toggleList = (field: CrudFieldSchema) => {
    if (field.list) delete field.list
    else field.list = { render: 'text', minWidth: 120 }
}

/** toggleSearch 搜能力增删 */
const toggleSearch = (field: CrudFieldSchema) => {
    if (field.search) delete field.search
    else field.search = { mode: 'keyword' }
}

/** toggleForm 表能力增删 */
const toggleForm = (field: CrudFieldSchema) => {
    if (field.form !== false && field.form != null) field.form = false
    else field.form = { render: 'input' }
}
</script>

<template>
    <el-card shadow="never" class="!border h-full flex flex-col min-h-0">
        <div class="flex items-center justify-between mb-2 gap-2">
            <div class="text-sm font-medium shrink-0">字段</div>
            <el-dropdown trigger="click" @command="addByTemplate">
                <el-button size="small" type="primary" :icon="Plus">添加</el-button>
                <template #dropdown>
                    <el-dropdown-menu>
                        <el-dropdown-item
                            v-for="t in FIELD_TEMPLATE_OPTIONS.filter((x) => !x.advanced)"
                            :key="t.id"
                            :command="t.id"
                        >
                            {{ t.label }}
                        </el-dropdown-item>
                        <el-dropdown-item divided :command="'blank'">空白字段（高级）</el-dropdown-item>
                    </el-dropdown-menu>
                </template>
            </el-dropdown>
        </div>

        <div ref="listRef" class="flex-1 overflow-auto min-h-[280px] max-h-[calc(100vh-360px)] space-y-1">
            <div
                v-for="(field, index) in config.fields"
                :key="index"
                class="field-item px-2 py-2 rounded border cursor-pointer space-y-1.5"
                :class="index === selectedIndex? 'border-primary bg-blue-50/50' :'border-transparent hover:bg-gray-50'"
                @click="selectedIndex = index"
            >
                <span class="drag-handle cursor-grab text-gray-400 shrink-0" @click.stop>
                        <el-icon><Rank /></el-icon>
                </span>
                <el-form inline label-position="left">

                </el-form>
                <div class="flex items-center gap-3 min-w-0">
                    <el-input
                        v-model="field.label"
                        size="small"
                        placeholder="中文标签"
                        class="flex-1 min-w-0"
                        @click.stop
                    />
                    <el-input
                        v-model="field.prop"
                        size="small"
                        placeholder="prop"
                        class="flex-1 min-w-0"
                        @click.stop
                    />
                    <el-select
                        :model-value="detectControlIntent(field)"
                        size="small"
                        placeholder="字段类型"
                        class="w-24 shrink-0 flex-1"
                        @click.stop
                        @change="(v: FieldControlIntent) => onIntentChange(field, v)"
                    >
                        <el-option
                            v-for="o in FIELD_CONTROL_OPTIONS"
                            :key="o.value"
                            :label="o.label"
                            :value="o.value"
                        />
                    </el-select>
                    <el-button
                        size="small"
                        type="danger"
                        link
                        :icon="Delete"
                        :disabled="config.fields.length <= 1"
                        @click.stop="store.removeFieldAt(index)"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 pl-6">
                    <div class="flex items-center gap-1 shrink-0" @click.stop>
                        <el-check-tag :checked="!!field.list" @change="() => toggleList(field)">列表</el-check-tag>
                        <el-check-tag :checked="!!field.search" @change="() => toggleSearch(field)">搜索</el-check-tag>
                        <el-check-tag
                            :checked="field.form !== false && field.form != null"
                            @change="() => toggleForm(field)"
                        >
                            表单
                        </el-check-tag>
                    </div>
                </div>
            </div>
        </div>
    </el-card>
</template>
