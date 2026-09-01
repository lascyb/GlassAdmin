<script setup lang="ts">
import { useIframeEmbed } from '@/layouts/router-view/iframe/useIframeEmbed'
import { Loading } from '@element-plus/icons-vue'
import { useTemplateRef } from 'vue'

const iframeEl = useTemplateRef<HTMLIFrameElement>('iframeEl')
const { loading, error, iframeSrc, resolveSrc, onIframeLoad } = useIframeEmbed(iframeEl)
</script>

<template>
    <div class="iframe-wrap">
        <iframe
            v-if="iframeSrc"
            ref="iframeEl"
            :src="iframeSrc"
            class="iframe-frame"
            frameborder="0"
            allow="fullscreen; unload *"
            @load="onIframeLoad"
        />
        <div v-if="loading" class="iframe-state iframe-overlay">
            <el-icon class="is-loading" :size="28"><Loading /></el-icon>
            <span>正在进入内嵌页面…</span>
        </div>
        <el-empty v-if="error && !iframeSrc" :description="error">
            <el-button type="primary" @click="resolveSrc({ force: true })">重试</el-button>
        </el-empty>
        <el-empty v-else-if="!iframeSrc && !loading" description="未配置内嵌地址" />
    </div>
</template>

<style scoped>
/* 空白壳：无外边距，高度与 main.vue 滚动视口一致 */
.iframe-wrap {
    position: relative;
    box-sizing: border-box;
    width: 100%;
    height: var(--ga-main-view-height);
    min-height: 320px;
    margin: 0;
    padding: 0;
    overflow: hidden;
    background: var(--el-fill-color-light);
}

.iframe-frame {
    display: block;
    width: 100%;
    height: 100%;
    border: 0;
}

.iframe-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    height: 100%;
    color: var(--el-text-color-secondary);
    font-size: 14px;
}

.iframe-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    background: var(--el-fill-color-light);
}
</style>
