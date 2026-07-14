<script setup lang="ts">
import type { CrudFieldSearchConfig, SearchMode, SearchRenderType } from '@/crud/types'
import { useDevCurd } from '@/pinia/dev/curd'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { SEARCH_MODE_OPTIONS, SEARCH_RENDER_OPTIONS, defaultSearchRenderHint } from '../../helpers'

const store = useDevCurd()
const { selectedField: field } = storeToRefs(store)

/** hasSearch 是否参与搜索 */
const hasSearch = computed({
    get: () => !!field.value?.search,
    set: (v: boolean) => {
        if (!field.value) return
        if (v) {
            field.value.search = field.value.search ?? { mode: 'keyword' }
        } else {
            delete field.value.search
        }
    },
})

/** ensureSearch 保证 search 对象 */
const ensureSearch = (): CrudFieldSearchConfig | null => {
    if (!field.value || !hasSearch.value) return null
    if (!field.value.search) field.value.search = { mode: 'keyword' }
    return field.value.search
}

/** onSearchModeChange 切换搜索方式 */
const onSearchModeChange = (mode: SearchMode) => {
    const s = ensureSearch()
    if (s) s.mode = mode
}

/** onSearchRenderChange 切换搜索控件 */
const onSearchRenderChange = (render: SearchRenderType | '') => {
    const s = ensureSearch()
    if (!s) return
    if (!render) delete s.render
    else s.render = render
}
</script>

<template>
    <section v-if="field" class="space-y-2">
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-medium text-gray-500">搜索</span>
            <el-switch v-model="hasSearch" active-text="参与搜索" size="small" />
        </div>
        <el-form v-if="field.search" label-width="80px" size="small">
            <el-form-item label="搜索方式" required>
                <el-select
                    :model-value="field.search.mode"
                    class="!w-52 max-w-full"
                    @update:model-value="onSearchModeChange"
                >
                    <el-option
                        v-for="o in SEARCH_MODE_OPTIONS"
                        :key="o.value"
                        :label="o.label"
                        :value="o.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="搜索控件">
                <el-select
                    :model-value="field.search.render ?? ''"
                    clearable
                    placeholder="可选，默认从表单推断"
                    class="!w-52 max-w-full"
                    @update:model-value="onSearchRenderChange"
                >
                    <el-option
                        v-for="o in SEARCH_RENDER_OPTIONS"
                        :key="o.value"
                        :label="o.label"
                        :value="o.value"
                    />
                </el-select>
                <div class="text-xs text-gray-400 mt-1">默认推断：{{ defaultSearchRenderHint(field) }}</div>
            </el-form-item>
            <el-form-item label="占位符">
                <el-input v-model="field.search.placeholder" class="!w-52 max-w-full" />
            </el-form-item>
            <el-form-item label="选项键">
                <el-input v-model="field.search.optionsKey" class="!w-52 max-w-full" />
            </el-form-item>
            <el-form-item label="状态映射">
                <el-input v-model="field.search.statusMapKey" class="!w-52 max-w-full" />
            </el-form-item>
        </el-form>
    </section>
</template>
