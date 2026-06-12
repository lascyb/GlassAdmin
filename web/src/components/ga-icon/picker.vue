<script setup lang="ts">
import { getElIcon, listElIconNames } from '@components/ga-icon/elRegistry'
import { getLocalIconUrl, listLocalIconStems } from '@components/ga-icon/localIcons'
import { parseGaIconString } from '@components/ga-icon/parse'
import GaIcon from '@components/ga-icon/index.vue'
import { computed, ref, watch } from 'vue'

const model = defineModel<string | null>({ default: null })

const open = ref(false)
const keyword = ref('')
const tab = ref<'el' | 'url' | 'local'>('el')
const urlBody = ref('')

const allNames = listElIconNames()
const localStems = computed(() => listLocalIconStems())

const filteredNames = computed(() => {
    const k = keyword.value.trim().toLowerCase()
    if (!k) return allNames
    return allNames.filter((n) => n.toLowerCase().includes(k))
})

const filteredLocalStems = computed(() => {
    const k = keyword.value.trim().toLowerCase()
    if (!k) return localStems.value
    return localStems.value.filter((n) => n.toLowerCase().includes(k))
})

watch(
    () => model.value,
    (v) => {
        const p = parseGaIconString(v)
        if (p.source === 'url') {
            tab.value = 'url'
            urlBody.value = p.href
        } else if (p.source === 'local') {
            tab.value = 'local'
            urlBody.value = ''
        } else {
            tab.value = 'el'
            urlBody.value = ''
        }
    },
    { immediate: true },
)

const selectEl = (name: string) => {
    model.value = `el:${name}`
    open.value = false
}

const selectLocal = (stem: string) => {
    model.value = `local:${stem}`
    open.value = false
}

const applyUrl = () => {
    const u = urlBody.value.trim()
    model.value = u ? `url:${u}` : null
    open.value = false
}

const clearIcon = () => {
    model.value = null
    open.value = false
}
</script>

<template>
    <div class="icon-picker w-full min-w-0 max-w-70">
        <el-popover v-model:visible="open" placement="right" :width="400" trigger="click" teleported>
            <template #reference>
                <el-button class="icon-picker__btn box-border! flex! h-auto! w-full! min-w-0! max-w-full! items-center! justify-start! gap-2! overflow-hidden! px-2!">
                    <span class="flex min-w-0 w-full max-w-full flex-1 items-center gap-2 overflow-hidden">
                        <span class="shrink-0"><GaIcon :icon="model" :fallback="true" /></span>
                        <span
                            class="block min-w-0 flex-1 truncate text-left text-xs text-gray-500"
                            :title="model || undefined"
                            >{{ model || '未设置' }}</span
                        >
                    </span>
                </el-button>
            </template>
            <el-tabs v-model="tab">
                <el-tab-pane label="Element Plus" name="el">
                    <el-input v-model="keyword" clearable placeholder="搜索图标名" class="mb-2" />
                    <el-scrollbar max-height="280px">
                        <div class="grid grid-cols-6 gap-1 p-1">
                            <el-button
                                v-for="name in filteredNames"
                                :key="name"
                                size="large"
                                class="h-9! min-w-0! p-0!"
                                :type="model === `el:${name}` ? 'primary' : 'default'"
                                :title="name"
                                @click="selectEl(name)"
                                :icon="getElIcon(name)"
                            >
                            </el-button>
                        </div>
                    </el-scrollbar>
                </el-tab-pane>
                <el-tab-pane label="本地图标" name="local">
                    <el-input v-model="keyword" clearable placeholder="搜索路径或文件名（不含 .svg）" class="mb-2" />
                    <el-scrollbar max-height="280px">
                        <div v-if="!filteredLocalStems.length" class="py-3 text-center text-xs text-gray-400">
                            将 svg 放入 src/assets/icons 后重新构建即可列出
                        </div>
                        <div v-else class="grid grid-cols-6 gap-1 p-1">
                            <el-button
                                v-for="stem in filteredLocalStems"
                                :key="stem"
                                size="large"
                                class="h-9! min-w-0! p-0!"
                                :type="model === `local:${stem}` ? 'primary' : 'default'"
                                :title="stem"
                                @click="selectLocal(stem)"
                            >
                                <img
                                    class="h-5 w-5 object-contain"
                                    :src="getLocalIconUrl(stem)"
                                    alt=""
                                />
                            </el-button>
                        </div>
                    </el-scrollbar>
                </el-tab-pane>
                <el-tab-pane label="图片 URL" name="url">
                    <el-input
                        v-model="urlBody"
                        type="textarea"
                        :rows="3"
                        placeholder="支持 http(s) 或站点内相对路径，保存为 url: 前缀"
                    />
                    <div class="mt-2 flex justify-end gap-2">
                        <el-button size="small" @click="open = false">取消</el-button>
                        <el-button type="primary" size="small" @click="applyUrl">确定</el-button>
                    </div>
                </el-tab-pane>
            </el-tabs>
            <div class="mt-2 flex justify-end border-t border-gray-100 pt-2">
                <el-button size="small" text type="danger" @click="clearIcon">清除图标</el-button>
            </div>
        </el-popover>
    </div>
</template>

<style scoped>
/* el-button 内层包裹插槽的 span 默认不收缩，子级 truncate 无效 */
.icon-picker :deep(.el-button) {
    width: 100%;
    min-width: 0;
}
.icon-picker :deep(.el-button > span) {
    display: flex;
    min-width: 0;
    max-width: 100%;
    flex: 1 1 0%;
    overflow: hidden;
}
.el-button+.el-button{
    margin-left: 0;
}
</style>
