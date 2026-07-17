<script setup lang="ts">
import { useDevCurd } from '@/pinia/dev/curd'
import FieldWorkspace from './components/FieldWorkspace.vue'
import GenerateResultDialog from './components/GenerateResultDialog.vue'
import GlobalConfigPanel from './components/GlobalConfigPanel.vue'

const store = useDevCurd()
</script>

<template>
    <el-scrollbar>
        <div v-loading="store.loading" class="flex flex-col gap-3 min-h-[max(calc(100vh-140px),400px)]">
            <GlobalConfigPanel />
            <FieldWorkspace />
            <GenerateResultDialog />
        </div>
    </el-scrollbar>
</template>
