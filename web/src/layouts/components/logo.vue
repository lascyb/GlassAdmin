<script setup lang="ts">
import { useLayoutMenus } from '@/pinia/layout/menus'
import { useSystemConfig } from '@/pinia/system/config'
import { useRouter } from 'vue-router'

const layoutMenus = useLayoutMenus()
const systemConfig = useSystemConfig()
const router = useRouter()

const toHome = () => {
    layoutMenus.module = undefined
    router.push('/')
}
</script>

<template>
    <div
        class="ga-logo-cluster flex h-full shrink-0 cursor-pointer items-center px-4 text-sm font-medium"
        @click="toHome"
    >
        <span class="truncate">{{ systemConfig.siteTitle }}</span>
    </div>
</template>

<style scoped>
.ga-logo-cluster {
    max-width: 12rem;
}
</style>
