<script setup lang="ts">
import { Refresh, Search } from '@element-plus/icons-vue';
import { ref } from 'vue';

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

const emit = defineEmits<{ refresh: []; search: []; reset: [] }>()

const expanded = ref(false)

const onSearch = () => emit('search')

const onRefresh = () => emit('refresh')

/** onReset 清空关键词并通知页面重置高级条件 */
const onReset = () => {
    keyword.value = ''
    emit('reset')
    emit('search')
}
</script>

<template>
    <div class="ga-search-bar w-full">
        <el-form
            v-if="expanded && $slots.advanced"
            inline
            class="ga-search-bar__advanced flex flex-wrap p-2"
            @submit.prevent="onSearch"
        >
            <slot name="advanced" />
            <el-form-item class="!mr-0">
                <el-button type="primary" native-type="submit">查询</el-button>
                <el-button @click="onReset">重置</el-button>
            </el-form-item>
        </el-form>
        <el-divider v-if="expanded && $slots.advanced" class="!my-0" />
        <div class="ga-search-bar__main flex flex-wrap items-center justify-end gap-2 p-2">
            <el-button :disabled="loading" :icon="Refresh" @click="onRefresh" />
            <el-input
                v-model="keyword"
                :placeholder="keywordPlaceholder"
                clearable
                class="max-w-xs"
                @keyup.enter="onSearch"
            />
            <el-button type="primary" @click="onSearch">查询</el-button>
            <el-button
                v-if="showToggle && $slots.advanced"
                :icon="Search"
                @click="expanded = !expanded"
            />
            <slot name="actions" />
        </div>
    </div>
</template>
