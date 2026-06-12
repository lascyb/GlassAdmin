<script setup lang="ts">
import { getElIcon } from '@components/ga-icon/elRegistry';
import { getLocalIconUrl } from '@components/ga-icon/localIcons';
import { parseGaIconString } from '@components/ga-icon/parse';
import { StarFilled } from '@element-plus/icons-vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        /** icon 存储串：el:、url:、local:（见 parse） */
        icon?: string | null
        /** 无图标时是否显示默认 StarFilled */
        fallback?: boolean
        /** url 图标边长（px）；不传则宽高由样式回退（与常见 el-icon 占位一致） */
        urlSize?: number
    }>(),
    { fallback: true },
)

const parsed = computed(() => parseGaIconString(props.icon))

const elComp = computed(() => {
    const p = parsed.value
    if (p.source === 'el') {
        return getElIcon(p.elName) ?? (props.fallback ? StarFilled : undefined)
    }
    if (p.source === 'local') {
        const src = getLocalIconUrl(p.stem)
        if (!src && props.fallback) return StarFilled
        return undefined
    }
    if (p.source === 'none' && props.fallback) {
        return StarFilled
    }
    return undefined
})

const url = computed(() => (parsed.value.source === 'url' ? parsed.value.href : ''))

/** localImgUrl local: 前缀且存在对应 svg 时的 url */
const localImgUrl = computed(() => {
    if (parsed.value.source !== 'local') return ''
    return getLocalIconUrl(parsed.value.stem) ?? ''
})

/** urlImgStyle url 图标固定为方形，限制边长避免撑破布局 */
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
    <el-icon v-if="elComp" class="ga-icon-el">
        <component :is="elComp" />
    </el-icon>
    <span v-else-if="localImgUrl" class="ga-icon-url-wrap">
        <img class="ga-icon-url" :src="localImgUrl" alt="" :style="urlImgStyle" />
    </span>
    <span v-else-if="url" class="ga-icon-url-wrap">
        <img class="ga-icon-url" :src="url" alt="" :style="urlImgStyle" />
    </span>
</template>

<style scoped>
.ga-icon-url-wrap {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
    line-height: 1;
}

.ga-icon-url {
    display: block;
    object-fit: contain;
    box-sizing: border-box;
}
</style>
