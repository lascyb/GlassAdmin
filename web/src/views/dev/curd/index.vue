<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useDevCurd } from '@/pinia/dev/curd'
import BuildPage from './BuildPage.vue'
import EntryPage from './EntryPage.vue'
import HistoryDialog from './components/HistoryDialog.vue'
import TableDialog from './components/TableDialog.vue'

const store = useDevCurd()
const { step, loading, historyVisible, tableVisible } = storeToRefs(store)
</script>

<template>
    <el-card v-loading="loading" class="!border-0" style="min-height: max(calc(100vh - 100px), 400px)">
        <EntryPage
            v-if="step === 'entry'"
            @history="historyVisible = true"
            @table="tableVisible = true"
            @empty="store.goEmpty()"
        />
        <BuildPage v-else />

        <HistoryDialog v-model="historyVisible" @select="store.loadFromHistory" />
        <TableDialog v-model="tableVisible" @select="store.loadFromTable" />
    </el-card>
</template>
