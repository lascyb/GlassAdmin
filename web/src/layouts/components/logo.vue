<script setup lang="ts">
import { useLayoutMenus } from "@/pinia/layout/menus"
import { useSystemConfig } from "@/pinia/system/config"
import { Expand, Fold } from "@element-plus/icons-vue"
import { computed } from "vue"
import { useRouter } from "vue-router"

const layoutMenus = useLayoutMenus()
const systemConfig = useSystemConfig()
const router = useRouter()

const toHome = () => {
    layoutMenus.module = undefined
    router.push('/')
}

const collapse = () => {
    layoutMenus.collapse = !layoutMenus.collapse
}

/** btnStyle 折叠按钮宽度过渡 */
const btnStyle = computed(() => ({
    height: '100%',
    width: layoutMenus.collapse ? '100%' : '50px',
    transition: 'width var(--ga-duration-slow) var(--ga-ease)',
}))
</script>

<template>
    <div
        id="ga-tour-logo"
        style="height: 60px; box-sizing: border-box"
        class="ga-logo-bar flex items-center justify-between"
    >
        <div v-if="!layoutMenus.collapse"
             class="flex flex-1 cursor-pointer items-center justify-center transition-opacity duration-200"
             @click="toHome">
            {{ systemConfig.siteTitle }}
        </div>
        <el-button :style="btnStyle" plain text type="primary" @click="collapse">
            <el-icon size="22">
                <Expand v-if="layoutMenus.collapse"/>
                <Fold v-else/>
            </el-icon>
        </el-button>
    </div>
</template>

<style scoped>
.ga-logo-bar {
    border-bottom: 1px solid var(--ga-glass-border);
    transition: border-color var(--ga-duration) var(--ga-ease);
}
</style>
