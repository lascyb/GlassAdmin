<script setup lang="ts">
import { useDevCurdField } from '@/pinia/dev/curdField'
import type { ListRenderType, SearchRenderType } from '@/views/dev/curd/types'
import { storeToRefs } from 'pinia'
import { computed, ref, watch } from 'vue'
import {
    LIST_COLUMN_RENDER_GROUPS,
    LIST_COLUMN_RENDER_OPTIONS,
} from '../../constants/list-render'
import {
    SEARCH_CONTROL_GROUPS,
    SEARCH_CONTROL_NEED_ENUM,
    SEARCH_CONTROL_NEED_MATCH,
    SEARCH_CONTROL_NEED_REMOTE,
    SEARCH_CONTROL_OPTIONS,
} from '../../constants/search-render'
import { isRemoteField, listRenderNeedsEnum } from '../../fieldExt'
import { inferSearchRender } from '../../helpers'
import InlineOptionsEditor from '../InlineOptionsEditor.vue'
import RemoteConfigDialog from '../RemoteConfigDialog.vue'

const fieldStore = useDevCurdField()
const { selectedField: field, hasList } = storeToRefs(fieldStore)

const renderValueSet = new Set(LIST_COLUMN_RENDER_OPTIONS.map((o) => o.value))
const controlValueSet = new Set(SEARCH_CONTROL_OPTIONS.map((o) => o.value))
const searchEnumNeedSet = new Set<string>(SEARCH_CONTROL_NEED_ENUM)
const searchRemoteNeedSet = new Set<string>(SEARCH_CONTROL_NEED_REMOTE)
const matchControlSet = new Set<string>(SEARCH_CONTROL_NEED_MATCH)

const enumVisible = ref(false)
const remoteVisible = ref(false)

/** keywordDisabled 远程字段不允许关键词搜索 */
const keywordDisabled = computed(() => isRemoteField(field.value))

/** clearRemoteKeyword 远程字段强制关闭关键词搜索 */
const clearRemoteKeyword = () => {
    const f = field.value
    if (!f || !isRemoteField(f) || f.search.keyword !== true) return
    const advanced = f.search.advanced === true
    fieldStore.syncSearchFlags(false, advanced)
}

watch(field, clearRemoteKeyword, { immediate: true, deep: true })

/** ensureSearchMatch 需要匹配方式时默认 fuzzy */
const ensureSearchMatch = (render: string) => {
    if (!field.value || !matchControlSet.has(render)) return
    if (!field.value.search.match) field.value.search.match = 'fuzzy'
}

/** clearSearchMatch 清除匹配方式 */
const clearSearchMatch = () => {
    if (field.value?.search.match) delete field.value.search.match
}

/** listRenderDisplay 列渲染（仅展示契约内选项） */
const listRenderDisplay = computed((): string | undefined => {
    const render = field.value?.list.render
    return render && renderValueSet.has(render) ? render : undefined
})

/** searchAdvanced 是否启用高级搜索 */
const searchAdvanced = computed(() => field.value?.search.advanced === true)

/** searchControlDisplay 高级搜索控件（schema.render 或推断值） */
const searchControlDisplay = computed((): string | undefined => {
    if (!field.value || !searchAdvanced.value) return undefined
    const sr = field.value.search.render ?? inferSearchRender(field.value)
    return sr && controlValueSet.has(sr) ? sr : undefined
})

const showSearchMatch = computed(
    () => searchAdvanced.value && !!searchControlDisplay.value && matchControlSet.has(searchControlDisplay.value),
)

/** searchMatch 文本匹配方式 {fuzzy:模糊,exact:精确} */
const searchMatch = computed({
    get: (): 'fuzzy' | 'exact' => field.value?.search.match ?? 'fuzzy',
    set: (v: 'fuzzy' | 'exact') => {
        if (field.value) field.value.search.match = v
    },
})

/** showListEnumEdit 当前列渲染是否需要编辑枚举（远程 tag/tags 不需要） */
const showListEnumEdit = computed(
    () => !!listRenderDisplay.value && listRenderNeedsEnum(field.value, listRenderDisplay.value),
)

/** showSearchEnumEdit 当前搜索控件是否需要编辑枚举 */
const showSearchEnumEdit = computed(
    () => !!searchControlDisplay.value && searchEnumNeedSet.has(searchControlDisplay.value),
)

/** showSearchRemoteEdit 当前搜索控件是否需要编辑远程 */
const showSearchRemoteEdit = computed(
    () => !!searchControlDisplay.value && searchRemoteNeedSet.has(searchControlDisplay.value),
)

/** onListRenderChange 写回 list.render */
const onListRenderChange = (v: string | undefined) => {
    if (!v || !renderValueSet.has(v)) return
    fieldStore.setListRender(v as ListRenderType)
}

/** onKeywordChange 切换关键词搜索 */
const onKeywordChange = (enabled: boolean) => {
    if (!field.value || isRemoteField(field.value)) return
    const advanced = field.value.search.advanced === true
    if (!enabled && !advanced) {
        fieldStore.disableSearch()
        clearSearchMatch()
        return
    }
    fieldStore.syncSearchFlags(enabled, advanced)
}

/** onAdvancedChange 切换高级搜索 */
const onAdvancedChange = (enabled: boolean) => {
    if (!field.value) return
    const keyword = isRemoteField(field.value) ? false : field.value.search.keyword === true
    if (!enabled && !keyword) {
        fieldStore.disableSearch()
        fieldStore.setSearchRender('')
        clearSearchMatch()
        return
    }
    fieldStore.syncSearchFlags(keyword, enabled)
    if (enabled) {
        const render = field.value.search.render ?? inferSearchRender(field.value)
        if (controlValueSet.has(render)) {
            fieldStore.setSearchRender(render as SearchRenderType)
            ensureSearchMatch(render)
        }
        return
    }
    fieldStore.setSearchRender('')
    clearSearchMatch()
}

/** onSearchControlChange 写回 search.render */
const onSearchControlChange = (v: string | undefined) => {
    if (!v) {
        fieldStore.setSearchRender('')
        clearSearchMatch()
        return
    }
    if (!controlValueSet.has(v) || !field.value) return
    const keyword = isRemoteField(field.value) ? false : field.value.search.keyword === true
    fieldStore.syncSearchFlags(keyword, true)
    fieldStore.setSearchRender(v as SearchRenderType)
    if (matchControlSet.has(v)) ensureSearchMatch(v)
    else clearSearchMatch()
}
</script>

<template>
    <el-card v-if="field" shadow="never">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span class="text-sm font-medium shrink-0">列表渲染 / 搜索配置</span>
                <el-switch v-model="hasList" active-text="显示列" inactive-text="不显示列" />
            </div>
        </template>

        <div class="space-y-4">
            <section class="space-y-2">
                <el-form size="small">
                    <el-form-item label="列渲染" required>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <el-select
                                :model-value="listRenderDisplay"
                                class="!w-20"
                                clearable
                                placeholder="列渲染"
                                popper-class="field-type-select-popper"
                                @update:model-value="onListRenderChange"
                            >
                                <el-option-group
                                    v-for="g in LIST_COLUMN_RENDER_GROUPS"
                                    :key="g.label"
                                    :label="g.label"
                                >
                                    <el-option
                                        v-for="o in g.options"
                                        :key="o.value"
                                        :label="o.label"
                                        :value="o.value"
                                    />
                                </el-option-group>
                            </el-select>
                            <el-button
                                v-if="showListEnumEdit"
                                size="small"
                                type="primary"
                                link
                                @click="enumVisible = true"
                            >
                                编辑枚举
                            </el-button>
                        </div>
                    </el-form-item>
                    <el-form-item label="搜索配置">
                        <el-checkbox
                                :model-value="!keywordDisabled && field.search.keyword === true"
                                :disabled="keywordDisabled"
                                @update:model-value="onKeywordChange"
                            >
                                启用关键词搜索
                        </el-checkbox>
                        <el-checkbox
                            :model-value="field.search.advanced === true"
                            @update:model-value="onAdvancedChange"
                        >
                                启用高级搜索
                        </el-checkbox>
                    </el-form-item>
                    <el-form-item v-if="searchAdvanced"  label="搜索控件" >
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <el-select
                                :model-value="searchControlDisplay"
                                class="!w-25 max-w-full"
                                clearable
                                placeholder="搜索控件"
                                popper-class="field-type-select-popper"
                                @update:model-value="onSearchControlChange"
                            >
                                <el-option-group
                                    v-for="g in SEARCH_CONTROL_GROUPS"
                                    :key="g.label"
                                    :label="g.label"
                                >
                                    <el-option
                                        v-for="o in g.options"
                                        :key="o.value"
                                        :label="o.label"
                                        :value="o.value"
                                    />
                                </el-option-group>
                            </el-select>
                            <el-button
                                v-if="showSearchEnumEdit"
                                size="small"
                                type="primary"
                                link
                                @click="enumVisible = true"
                            >
                                编辑枚举
                            </el-button>
                            <el-button
                                v-if="showSearchRemoteEdit"
                                size="small"
                                type="primary"
                                link
                                @click="remoteVisible = true"
                            >
                                编辑远程
                            </el-button>
                            <el-radio-group v-if="showSearchMatch" v-model="searchMatch" size="small">
                                <el-radio value="fuzzy">模糊搜索</el-radio>
                                <el-radio value="exact">精确搜索</el-radio>
                            </el-radio-group>
                        </div>
                    </el-form-item>
                </el-form>
            </section>
        </div>

        <el-dialog
            v-model="enumVisible"
            title="编辑枚举选项"
            width="560px"
            append-to-body
            destroy-on-close
        >
            <InlineOptionsEditor />
        </el-dialog>

        <RemoteConfigDialog v-model="remoteVisible" />
    </el-card>
</template>
