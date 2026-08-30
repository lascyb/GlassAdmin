<script setup lang="ts">
import { THEME_COLOR_DEFAULT, THEME_COLOR_PRESETS } from '@/theme'
import { computed } from 'vue'

const model = defineModel<string>({ required: true })

const presetColors = THEME_COLOR_PRESETS.map((p) => p.color)

const current = computed(() => model.value.toLowerCase())

const isPreset = (color: string) => current.value === color.toLowerCase()
</script>

<template>
    <div class="theme-color-picker">
        <div class="theme-color-swatches" role="list">
            <button
                v-for="item in THEME_COLOR_PRESETS"
                :key="item.id"
                type="button"
                role="listitem"
                class="theme-color-swatch"
                :class="{ 'is-active': isPreset(item.color) }"
                :style="{ background: item.color }"
                :title="item.label"
                :aria-label="item.label"
                :aria-pressed="isPreset(item.color)"
                @click="model = item.color"
            />
        </div>
        <el-color-picker
            :model-value="model"
            :predefine="presetColors"
            @update:model-value="(v: string | null) => { if (v) model = v }"
        />
        <el-button link type="primary" @click="model = THEME_COLOR_DEFAULT">重置</el-button>
    </div>
</template>

<style scoped>
.theme-color-picker {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--ga-space-2);
}

.theme-color-swatches {
    display: flex;
    flex-wrap: wrap;
    gap: var(--ga-space-2);
}

.theme-color-swatch {
    width: 22px;
    height: 22px;
    padding: 0;
    border: 2px solid var(--ga-color-border-lighter);
    border-radius: 50%;
    cursor: pointer;
    transition:
        border-color var(--ga-duration) var(--ga-ease),
        box-shadow var(--ga-duration) var(--ga-ease);
}

.theme-color-swatch:hover {
    border-color: var(--el-color-primary-light-5);
}

.theme-color-swatch.is-active {
    border-color: var(--el-color-primary);
    box-shadow: 0 0 0 1px var(--el-color-primary);
}
</style>
