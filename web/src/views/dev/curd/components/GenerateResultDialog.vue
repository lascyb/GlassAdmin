<script setup lang="ts">
import { useDevCurd } from '@/pinia/dev/curd'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'

const store = useDevCurd()
const { resultVisible, result, resultError } = storeToRefs(store)

const resultJson = computed(() => (result.value ? JSON.stringify(result.value, null, 2) : ''))
</script>

<template>
    <el-dialog v-model="resultVisible" title="生成结果" width="720px" destroy-on-close>
        <template v-if="resultError">
            <el-alert type="error" :title="resultError" show-icon :closable="false" />
        </template>
        <template v-else-if="result">
            <el-descriptions :column="2" size="small" border>
                <el-descriptions-item label="模式">
                    {{ result.dryRun ? '仅预览' : '已执行' }}
                </el-descriptions-item>
                <el-descriptions-item label="实体">
                    {{ result.module ? `${result.module}.${result.entity}` : result.entity }}
                </el-descriptions-item>
                <el-descriptions-item label="数据表">{{ result.table }}</el-descriptions-item>
                <el-descriptions-item label="写盘策略">
                    {{ result.writeMode === 'overwrite' ? '覆盖' : '跳过已存在' }}
                </el-descriptions-item>
                <el-descriptions-item label="写入权限">
                    {{ result.generateAuth ? '是' : '否' }}
                </el-descriptions-item>
                <el-descriptions-item v-if="result.historyId" label="历史编号">
                    {{ result.historyId }}
                </el-descriptions-item>
                <el-descriptions-item v-if="result.historyError" label="历史错误" :span="2">
                    <span class="text-amber-700">{{ result.historyError }}</span>
                </el-descriptions-item>
            </el-descriptions>

            <div class="mt-4 text-xs font-medium">计划文件（{{ result.files.length }}）</div>
            <ul class="mt-1 text-xs text-gray-600 max-h-28 overflow-auto space-y-1">
                <li v-for="f in result.files" :key="f.path">{{ f.path }} ({{ f.bytes }})</li>
            </ul>

            <div v-if="result.written.length" class="mt-3 text-xs font-medium text-green-700">
                已写入（{{ result.written.length }}）
            </div>
            <ul v-if="result.written.length" class="mt-1 text-xs text-green-700 max-h-24 overflow-auto space-y-1">
                <li v-for="p in result.written" :key="p">{{ p }}</li>
            </ul>

            <div v-if="result.skipped.length" class="mt-3 text-xs font-medium text-amber-700">
                跳过（{{ result.skipped.length }}）
            </div>
            <ul v-if="result.skipped.length" class="mt-1 text-xs text-amber-700 max-h-24 overflow-auto space-y-1">
                <li v-for="p in result.skipped" :key="p">{{ p }}</li>
            </ul>

            <div class="mt-3 text-xs font-medium">建表计划（{{ result.schema.length }}）</div>
            <ul class="mt-1 text-xs text-gray-600 max-h-28 overflow-auto space-y-1">
                <li v-for="s in result.schema" :key="s.table">
                    {{ s.table }} — {{ s.exists ? '已存在→跳过' : '将创建' }}
                </li>
            </ul>

            <div v-if="result.tablesCreated?.length" class="mt-3 text-xs font-medium text-green-700">
                已建表（{{ result.tablesCreated.length }}）
            </div>
            <ul v-if="result.tablesCreated?.length" class="mt-1 text-xs text-green-700 space-y-1">
                <li v-for="t in result.tablesCreated" :key="t">{{ t }}</li>
            </ul>

            <div v-if="result.auth.length" class="mt-3 text-xs font-medium">
                权限计划（{{ result.auth.length }}）
            </div>
            <ul v-if="result.auth.length" class="mt-1 text-xs text-gray-600 max-h-28 overflow-auto space-y-1">
                <li v-for="a in result.auth" :key="a">{{ a }}</li>
            </ul>

            <el-collapse v-if="resultJson" class="mt-3">
                <el-collapse-item title="原始 JSON" name="raw">
                    <pre class="text-xs whitespace-pre-wrap break-all m-0">{{ resultJson }}</pre>
                </el-collapse-item>
            </el-collapse>
        </template>
        <el-empty v-else description="无结果" :image-size="64" />
        <template #footer>
            <el-button type="primary" @click="resultVisible = false">关闭</el-button>
        </template>
    </el-dialog>
</template>
