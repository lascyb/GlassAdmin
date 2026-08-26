<script setup lang="ts">
import type {
    GenerateAuthPlan,
    GenerateFilePlan,
    GenerateSchemaPlan,
} from '@/apis/dev/curd'
import { useDevCurd } from '@/pinia/dev/curd'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'

const store = useDevCurd()
const { resultVisible, result, resultError } = storeToRefs(store)

const resultJson = computed(() => (result.value ? JSON.stringify(result.value, null, 2) : ''))

type TagType = 'success' | 'warning' | 'info'
type StatusView = { label: string; type: TagType }

/** fileStatusView 文件计划状态展示 */
const fileStatusView = (status: GenerateFilePlan['status']): StatusView | null => {
    switch (status) {
        case 'written':
            return { label: '已写入', type: 'success' }
        case 'skipped':
            return { label: '跳过', type: 'warning' }
        default:
            return null
    }
}

/** schemaStatusView 建表计划状态展示 */
const schemaStatusView = (status: GenerateSchemaPlan['status']): StatusView => {
    switch (status) {
        case 'created':
            return { label: '已创建', type: 'success' }
        case 'altered':
            return { label: '已同步字段', type: 'success' }
        case 'will_alter':
            return { label: '将同步字段', type: 'warning' }
        case 'unchanged':
            return { label: '无变更', type: 'info' }
        case 'skipped':
        case 'exists':
            return { label: '跳过', type: 'warning' }
        default:
            return { label: '将创建', type: 'info' }
    }
}

/** authStatusView 权限计划状态展示 */
const authStatusView = (status: GenerateAuthPlan['status']): StatusView | null => {
    switch (status) {
        case 'created':
            return { label: '新建', type: 'success' }
        case 'updated':
            return { label: '更新', type: 'success' }
        case 'reused':
            return { label: '复用', type: 'info' }
        default:
            return null
    }
}

const fileRows = computed(() =>
    (result.value?.files ?? []).map((f) => ({
        ...f,
        statusView: fileStatusView(f.status),
    })),
)

const schemaRows = computed(() =>
    (result.value?.schema ?? []).map((s) => ({
        ...s,
        statusView: schemaStatusView(s.status),
    })),
)

const authRows = computed(() =>
    (result.value?.auth ?? []).map((a) => ({
        ...a,
        statusView: authStatusView(a.status),
    })),
)
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
                <el-descriptions-item label="数据库连接">
                    {{ result.connection || '默认连接' }}
                </el-descriptions-item>
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

            <div class="mt-4 text-xs font-medium">计划文件（{{ fileRows.length }}）</div>
            <ul class="mt-1 text-xs text-gray-600 max-h-40 overflow-auto space-y-1">
                <li v-for="f in fileRows" :key="f.path" class="flex items-center gap-2 flex-wrap">
                    <span>{{ f.path }} ({{ f.bytes }})</span>
                    <el-tag v-if="f.statusView" size="small" :type="f.statusView.type" effect="plain">
                        {{ f.statusView.label }}
                    </el-tag>
                </li>
            </ul>

            <div class="mt-3 text-xs font-medium">建表计划（{{ schemaRows.length }}）</div>
            <ul class="mt-1 text-xs text-gray-600 max-h-52 overflow-auto space-y-2">
                <li v-for="s in schemaRows" :key="s.table" class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span>{{ s.table }}</span>
                        <el-tag size="small" :type="s.statusView.type" effect="plain">
                            {{ s.statusView.label }}
                        </el-tag>
                    </div>
                    <pre
                        v-if="s.sql"
                        class="whitespace-pre-wrap break-all rounded bg-gray-50 px-2 py-1 text-[11px] text-gray-500"
                    >{{ s.sql }}</pre>
                </li>
            </ul>

            <div v-if="authRows.length" class="mt-3 text-xs font-medium">
                权限计划（{{ authRows.length }}）
            </div>
            <ul v-if="authRows.length" class="mt-1 text-xs text-gray-600 max-h-40 overflow-auto space-y-1">
                <li v-for="a in authRows" :key="a.name" class="flex items-center gap-2 flex-wrap">
                    <span>{{ a.name }}</span>
                    <el-tag v-if="a.statusView" size="small" :type="a.statusView.type" effect="plain">
                        {{ a.statusView.label }}
                    </el-tag>
                </li>
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
