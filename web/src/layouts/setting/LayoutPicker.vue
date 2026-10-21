<script setup lang="ts">
type LayoutContainer = 'Default' | 'Double' | 'Module' | 'Top'

const model = defineModel<LayoutContainer>({ required: true })

const layoutOptions: { value: LayoutContainer; label: string }[] = [
    { value: 'Default', label: '左侧单层' },
    { value: 'Double', label: '左侧双层' },
    { value: 'Module', label: '顶侧双栏' },
    { value: 'Top', label: '顶栏菜单' },
]

const selectLayout = (value: LayoutContainer) => {
    model.value = value
}
</script>

<template>
    <div class="layout-picker">
        <button
            v-for="item in layoutOptions"
            :key="item.value"
            type="button"
            class="layout-picker__item"
            :class="{ 'is-active': model === item.value }"
            :aria-label="item.label"
            :aria-pressed="model === item.value"
            @click="selectLayout(item.value)"
        >
            <div class="layout-preview" :class="`layout-preview--${item.value.toLowerCase()}`">
                <div
                    class="layout-preview__header"
                    :class="{
                        'layout-preview__header--module': item.value === 'Module',
                        'layout-preview__header--top': item.value === 'Top',
                    }"
                >
                    <template v-if="item.value === 'Module' || item.value === 'Top'">
                        <span />
                        <span />
                        <span />
                    </template>
                </div>
                <div class="layout-preview__body">
                    <template v-if="item.value === 'Default'">
                        <div class="layout-preview__aside" />
                        <div class="layout-preview__main" />
                    </template>
                    <template v-else-if="item.value === 'Double'">
                        <div class="layout-preview__aside layout-preview__aside--double">
                            <div class="layout-preview__module-col" />
                            <div class="layout-preview__sub-col" />
                        </div>
                        <div class="layout-preview__main" />
                    </template>
                    <template v-else-if="item.value === 'Module'">
                        <div class="layout-preview__aside" />
                        <div class="layout-preview__main" />
                    </template>
                    <template v-else>
                        <div class="layout-preview__main" />
                    </template>
                </div>
            </div>
            <span class="layout-picker__caption">{{ item.label }}</span>
        </button>
    </div>
</template>

<style scoped>
.layout-picker {
    display: flex;
    flex-wrap: wrap;
    gap: var(--ga-space-3);
    width: 100%;
}

.layout-picker__item {
    display: flex;
    flex: 1 1 calc(50% - var(--ga-space-3) / 2);
    min-width: 0;
    flex-direction: column;
    align-items: center;
    gap: var(--ga-space-2);
    padding: var(--ga-space-2);
    border: 2px solid var(--ga-color-border);
    border-radius: var(--ga-radius-md);
    background: var(--ga-color-bg);
    cursor: pointer;
    transition:
        border-color var(--ga-duration) var(--ga-ease),
        box-shadow var(--ga-duration) var(--ga-ease);
}

.layout-picker__item:hover {
    border-color: var(--el-color-primary-light-5);
}

.layout-picker__item.is-active {
    border-color: var(--el-color-primary);
    box-shadow: 0 0 0 1px var(--el-color-primary);
}

.layout-picker__caption {
    font-size: 12px;
    line-height: 1.4;
    text-align: center;
    color: var(--ga-color-text-regular);
}

.layout-picker__item.is-active .layout-picker__caption {
    font-weight: 500;
    color: var(--el-color-primary);
}

.layout-preview {
    display: flex;
    flex-direction: column;
    width: 100%;
    aspect-ratio: 3 / 2;
    overflow: hidden;
    border-radius: var(--ga-radius-sm);
    border: 1px solid var(--ga-color-border-lighter);
    background: var(--ga-color-fill-blank);
}

.layout-preview__header {
    flex: 0 0 10px;
    background: var(--ga-color-fill);
    border-bottom: 1px solid var(--ga-color-border-lighter);
}

.layout-preview__header--module,
.layout-preview__header--top {
    display: flex;
    align-items: center;
    gap: 2px;
    padding: 0 3px;
}

.layout-preview__header--module span,
.layout-preview__header--top span {
    flex: 1;
    height: 4px;
    border-radius: 1px;
    background: var(--el-color-primary-light-5);
}

.layout-preview__header--module span:first-child,
.layout-preview__header--top span:first-child {
    background: var(--el-color-primary);
}

.layout-preview__body {
    display: flex;
    flex: 1;
    min-height: 0;
}

.layout-preview__aside {
    flex: 0 0 22px;
    background: var(--el-color-primary-light-7);
    border-right: 1px solid var(--ga-color-border-lighter);
}

.layout-preview__aside--double {
    display: flex;
    flex: 0 0 30px;
}

.layout-preview__module-col {
    flex: 0 0 12px;
    background: var(--el-color-primary-light-5);
    border-right: 1px solid var(--ga-color-border-lighter);
}

.layout-preview__sub-col {
    flex: 1;
    background: var(--el-color-primary-light-7);
}

.layout-preview__main {
    flex: 1;
    min-width: 0;
    background: var(--ga-color-fill-light);
}
</style>
