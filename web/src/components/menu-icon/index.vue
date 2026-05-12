<script setup lang="ts">
import { getEpIcon } from '@/utils/menuIcon/epRegistry';
import { parseMenuIconString } from '@/utils/menuIcon/parse';
import { StarFilled } from '@element-plus/icons-vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        /** icon 存储串：ep:Odometer、url:https://… */
        icon?: string | null
        /** 无图标时是否显示默认 StarFilled */
        fallback?: boolean
        /** url 图标边长（px），与侧栏 el-icon 对齐；不传则用菜单 CSS 变量 */
        urlSize?: number
    }>(),
    { fallback: true },
)

const parsed = computed(() => parseMenuIconString(props.icon))

const epComp = computed(() => {
    const p = parsed.value
    if (p.source === 'ep') {
        return getEpIcon(p.epName) ?? (props.fallback ? StarFilled : undefined)
    }
    if (p.source === 'none' && props.fallback) {
        return StarFilled
    }
    return undefined
})

const url = computed(() => (parsed.value.source === 'url' ? parsed.value.href : ''))

/** urlImgStyle url 图标固定为方形，避免大图撑开菜单 */
const urlImgStyle = computed(() => {
    const px = props.urlSize
    if (px != null && px > 0) {
        const n = `${px}px`
        return { width: n, height: n, maxWidth: n, maxHeight: n }
    }
    return {
        width: 'var(--el-menu-icon-width, 18px)',
        height: 'var(--el-menu-icon-width, 18px)',
        maxWidth: 'var(--el-menu-icon-width, 18px)',
        maxHeight: 'var(--el-menu-icon-width, 18px)',
    }
})
</script>

<template>
    <el-icon v-if="epComp" class="menu-icon-ep">
        <component :is="epComp" />
    </el-icon>
    <span v-else-if="url" class="menu-icon-url-wrap">
        <img class="menu-icon-url" :src="url" alt="" :style="urlImgStyle" />
    </span>
</template>

<style scoped>
.menu-icon-url-wrap {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
    line-height: 1;
}

.menu-icon-url {
    display: block;
    object-fit: contain;
    box-sizing: border-box;
}
</style>
