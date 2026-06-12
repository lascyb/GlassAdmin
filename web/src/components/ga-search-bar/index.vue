<script setup lang="ts">
import { Refresh, Search } from '@element-plus/icons-vue'
import { ref } from 'vue'

const keyword = defineModel<string>('keyword', { default: '' })

withDefaults(
    defineProps<{
        /** loading 刷新按钮禁用态 */
        loading?: boolean
        /** keywordPlaceholder 关键词占位 */
        keywordPlaceholder?: string
        /** showToggle 是否显示展开/收起高级搜索 */
        showToggle?: boolean
    }>(),
    {
        loading: false,
        keywordPlaceholder: '关键词',
        showToggle: true,
    },
)

const emit = defineEmits<{ refresh: []; search: [] }>()

const expanded = ref(false)

const onSearch = () => emit('search')
const onRefresh = () => emit('refresh')
</script>

<template>
    <div>
        <el-form v-if="expanded && $slots.advanced" inline class="p-2">
            <slot name="advanced" />
        </el-form>
        <el-divider v-if="expanded && $slots.advanced" class="!my-0" />
        <div class="flex flex-wrap items-center gap-2 p-2">
            <el-button :disabled="loading" :icon="Refresh" @click="onRefresh" />
            <el-input
                v-model="keyword"
                :placeholder="keywordPlaceholder"
                clearable
                class="max-w-xs"
                @keyup.enter="onSearch"
            />
            <el-button type="primary" @click="onSearch">查询</el-button>
            <slot name="actions" />
            <el-button
                v-if="showToggle && $slots.advanced"
                :icon="Search"
                @click="expanded = !expanded"
            />
        </div>
    </div>
</template>
