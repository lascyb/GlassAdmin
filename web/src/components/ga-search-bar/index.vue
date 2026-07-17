<script setup lang="ts">
import {Refresh, Search} from '@element-plus/icons-vue';
import {ref} from 'vue';

const keyword = defineModel<string>('keyword', {default: ''})

withDefaults(
    defineProps<{
        /** loading 刷新按钮禁用态 */
        loading?: boolean
        /** keywordPlaceholder 关键词占位 */
        keywordPlaceholder?: string
        /** showKeyword 是否显示关键词输入框 */
        showKeyword?: boolean
        /** showToggle 是否显示展开/收起高级搜索 */
        showToggle?: boolean
    }>(),
    {
        loading: false,
        keywordPlaceholder: '关键词',
        showKeyword: true,
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
            class="ga-search-bar__advanced flex flex-wrap "
            @submit.prevent="onSearch"
        >
            <slot name="advanced"/>
            <el-form-item class="!mr-0">
                <el-button type="primary" native-type="submit">查询</el-button>
                <el-button @click="onReset">重置</el-button>
            </el-form-item>
        </el-form>
        <el-divider v-if="expanded && $slots.advanced" class="!my-0"/>
        <div class="ga-search-bar__main flex items-center gap-2 py-2">
            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                <slot name="actions"/>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <el-button :disabled="loading" :icon="Refresh" @click="onRefresh"/>
                <el-input
                    v-if="showKeyword"
                    v-model="keyword"
                    :placeholder="keywordPlaceholder"
                    clearable
                    class="!w-100"
                    @keyup.enter="onSearch"
                />
                <el-button type="primary" @click="onSearch">查询</el-button>
                <el-button type="primary" @click="expanded = !expanded" plain v-if="showToggle && $slots.advanced"
                           :icon="Search"/>
            </div>
        </div>
    </div>
</template>
