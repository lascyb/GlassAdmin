<script setup lang="ts">
import type { ServerOverview } from '@/apis/dashboard'
import * as dashboardApi from '@/apis/dashboard'
import GaPage from '@/components/ga-page/index.vue'
import { useWindowSize } from '@vueuse/core'
import * as echarts from 'echarts'
import { ElMessage } from 'element-plus'
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const REFRESH_MS = 5000
const HISTORY_MAX = 60

const loading = ref(false)
const autoRefresh = ref(true)
const includeHgfs = ref(false)
const data = ref<ServerOverview | null>(null)

type HistoryPoint = { t: string; cpu: number | null; mem: number | null; disk: number | null }
const history = ref<HistoryPoint[]>([])

const cpuChartRef = ref<HTMLElement>()
const memChartRef = ref<HTMLElement>()
const diskChartRef = ref<HTMLElement>()

let cpuChart: echarts.ECharts | null = null
let memChart: echarts.ECharts | null = null
let diskChart: echarts.ECharts | null = null
let timer: ReturnType<typeof setInterval> | null = null

/** formatBytes 字节可读化 */
const formatBytes = (n: number | null | undefined) => {
    if (n == null || n < 0) return '-'
    if (n === 0) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    const i = Math.min(Math.floor(Math.log(n) / Math.log(1024)), units.length - 1)
    return `${(n / 1024 ** i).toFixed(i === 0 ? 0 : 1)} ${units[i]}`
}

/** formatUptime 运行时长可读化 */
const formatUptime = (sec: number | null | undefined) => {
    if (sec == null || sec < 0) return '-'
    const d = Math.floor(sec / 86400)
    const h = Math.floor((sec % 86400) / 3600)
    const m = Math.floor((sec % 3600) / 60)
    const parts: string[] = []
    if (d > 0) parts.push(`${d} 天`)
    if (h > 0 || d > 0) parts.push(`${h} 小时`)
    parts.push(`${m} 分`)
    return parts.join(' ')
}

/** formatPct 百分比展示 */
const formatPct = (n: number | null | undefined) => (n == null ? '-' : `${n.toFixed(1)}%`)

/** pctColor 占用率颜色 */
const pctColor = (n: number | null | undefined) => {
    if (n == null) return '#909399'
    if (n >= 90) return '#f56c6c'
    if (n >= 70) return '#e6a23c'
    return '#67c23a'
}

const primaryDisk = computed(() => data.value?.disks?.[0] ?? null)

const kpiCards = computed(() => {
    const d = data.value
    if (!d) return []
    const disk = primaryDisk.value
    return [
        {
            label: 'CPU',
            value: formatPct(d.cpu.usage_percent),
            sub: `${d.cpu.cores} 核` + (d.cpu.load_1 != null ? ` · load ${d.cpu.load_1}` : ''),
            pct: d.cpu.usage_percent,
        },
        {
            label: '内存',
            value: formatPct(d.memory.usage_percent),
            sub: `${formatBytes(d.memory.used)} / ${formatBytes(d.memory.total)}`,
            pct: d.memory.usage_percent,
        },
        {
            label: '磁盘',
            value: formatPct(disk?.usage_percent ?? null),
            sub: disk ? `${disk.mount} · ${formatBytes(disk.used)} / ${formatBytes(disk.total)}` : '-',
            pct: disk?.usage_percent ?? null,
        },
        {
            label: '运行时长',
            value: formatUptime(d.runtime.uptime_seconds),
            sub: d.runtime.hostname || d.runtime.uname,
            pct: null,
        },
        {
            label: 'PHP 内存',
            value: formatBytes(d.runtime.php_memory),
            sub: `峰值 ${formatBytes(d.runtime.php_memory_peak)} · 限 ${formatBytes(d.runtime.php_memory_limit)}`,
            pct: null,
        },
    ]
})

/** pushHistory 追加实时曲线点 */
const pushHistory = (snap: ServerOverview) => {
    const now = new Date(snap.timestamp * 1000)
    const t = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`
    history.value = [
        ...history.value,
        {
            t,
            cpu: snap.cpu.usage_percent,
            mem: snap.memory.usage_percent,
            disk: snap.disks[0]?.usage_percent ?? null,
        },
    ].slice(-HISTORY_MAX)
}

/** lineOption 折线图配置 */
const lineOption = (name: string, color: string, labels: string[], values: (number | null)[]) => {
    const axisLabel = { color: '#909399', fontSize: 11 }
    const splitLine = { lineStyle: { color: '#ebeef5', type: 'dashed' as const } }
    return {
        tooltip: { trigger: 'axis', valueFormatter: (v: number | null) => (v == null ? '-' : `${v}%`) },
        grid: { left: 8, right: 12, top: 28, bottom: 4, containLabel: true },
        xAxis: { type: 'category', data: labels, boundaryGap: false, axisLabel, axisLine: { lineStyle: { color: '#dcdfe6' } } },
        yAxis: { type: 'value', min: 0, max: 100, axisLabel: { ...axisLabel, formatter: '{value}%' }, splitLine },
        series: [
            {
                name,
                type: 'line',
                smooth: true,
                showSymbol: false,
                lineStyle: { width: 2, color },
                areaStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: color + '55' },
                        { offset: 1, color: color + '08' },
                    ]),
                },
                data: values,
            },
        ],
    }
}

/** renderCharts 渲染实时曲线 */
const renderCharts = () => {
    const labels = history.value.map((p) => p.t)
    if (cpuChartRef.value) {
        cpuChart ??= echarts.init(cpuChartRef.value)
        cpuChart.setOption(lineOption('CPU', '#60a5fa', labels, history.value.map((p) => p.cpu)), true)
    }
    if (memChartRef.value) {
        memChart ??= echarts.init(memChartRef.value)
        memChart.setOption(lineOption('内存', '#4ade80', labels, history.value.map((p) => p.mem)), true)
    }
    if (diskChartRef.value) {
        diskChart ??= echarts.init(diskChartRef.value)
        diskChart.setOption(lineOption('磁盘', '#fb923c', labels, history.value.map((p) => p.disk)), true)
    }
}

const resizeCharts = () => {
    cpuChart?.resize()
    memChart?.resize()
    diskChart?.resize()
}

const load = async (silent = false) => {
    if (!silent) loading.value = true
    try {
        const snap = await dashboardApi.fetchServerOverview(includeHgfs.value)
        data.value = snap
        pushHistory(snap)
        await nextTick(renderCharts)
    } catch (e: unknown) {
        if (!silent) ElMessage.error(e instanceof Error ? e.message : '加载失败')
    } finally {
        if (!silent) loading.value = false
    }
}

/** startTimer 启动自动刷新 */
const startTimer = () => {
    stopTimer()
    if (!autoRefresh.value) return
    timer = setInterval(() => {
        void load(true)
    }, REFRESH_MS)
}

const stopTimer = () => {
    if (timer != null) {
        clearInterval(timer)
        timer = null
    }
}

const { width } = useWindowSize()
watch(width, resizeCharts)
watch(autoRefresh, startTimer)
watch(includeHgfs, () => {
    void load(true)
})

onMounted(() => {
    void load().then(startTimer)
})

onUnmounted(() => {
    stopTimer()
    cpuChart?.dispose()
    memChart?.dispose()
    diskChart?.dispose()
    cpuChart = null
    memChart = null
    diskChart = null
})
</script>

<template>
    <ga-page padding="md" min-height="calc(100vh - 120px)">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span>服务器性能</span>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-normal text-gray-500">自动刷新（{{ REFRESH_MS / 1000 }}s）</span>
                    <el-switch v-model="autoRefresh" />
                    <el-button @click="load()">刷新</el-button>
                </div>
            </div>
        </template>
        <div v-loading="loading" class="space-y-4">
            <div class="mb-2 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                <div
                    v-for="item in kpiCards"
                    :key="item.label"
                    class="flex h-[90px] flex-col justify-between rounded border border-gray-100 bg-white px-3 py-2"
                >
                    <div class="text-xs text-gray-800">{{ item.label }}</div>
                    <div class="text-lg font-semibold leading-none" :style="{ color: pctColor(item.pct) }">
                        {{ item.value }}
                    </div>
                    <div class="truncate text-[11px] leading-none text-gray-500">{{ item.sub }}</div>
                </div>
            </div>

            <el-row v-if="data" :gutter="12">
                <el-col :xs="24" :md="8" class="mb-3">
                    <el-card shadow="never" header="CPU 占用">
                        <div class="flex flex-col items-center">
                            <el-progress
                                type="dashboard"
                                :percentage="data.cpu.usage_percent ?? 0"
                                :color="pctColor(data.cpu.usage_percent)"
                                :format="() => formatPct(data!.cpu.usage_percent)"
                            />
                            <div class="mt-2 space-y-1 text-center text-xs text-gray-500">
                                <div>核数 {{ data.cpu.cores }}</div>
                                <div v-if="data.cpu.load_1 != null">
                                    Load {{ data.cpu.load_1 }} / {{ data.cpu.load_5 }} / {{ data.cpu.load_15 }}
                                </div>
                            </div>
                        </div>
                    </el-card>
                </el-col>
                <el-col :xs="24" :md="8" class="mb-3">
                    <el-card shadow="never" header="内存占用">
                        <div class="flex flex-col items-center">
                            <el-progress
                                type="dashboard"
                                :percentage="data.memory.usage_percent ?? 0"
                                :color="pctColor(data.memory.usage_percent)"
                                :format="() => formatPct(data!.memory.usage_percent)"
                            />
                            <div class="mt-2 space-y-1 text-center text-xs text-gray-500">
                                <div>{{ formatBytes(data.memory.used) }} / {{ formatBytes(data.memory.total) }}</div>
                                <div>可用 {{ formatBytes(data.memory.available) }}</div>
                            </div>
                        </div>
                    </el-card>
                </el-col>
                <el-col :xs="24" :md="8" class="mb-3">
                    <el-card shadow="never" header="主分区占用">
                        <div class="flex flex-col items-center">
                            <el-progress
                                type="dashboard"
                                :percentage="primaryDisk?.usage_percent ?? 0"
                                :color="pctColor(primaryDisk?.usage_percent)"
                                :format="() => formatPct(primaryDisk?.usage_percent ?? null)"
                            />
                            <div class="mt-2 space-y-1 text-center text-xs text-gray-500">
                                <div>{{ primaryDisk?.mount ?? '-' }}</div>
                                <div v-if="primaryDisk">
                                    {{ formatBytes(primaryDisk.used) }} / {{ formatBytes(primaryDisk.total) }}
                                </div>
                            </div>
                        </div>
                    </el-card>
                </el-col>
            </el-row>

            <el-row v-if="history.length" :gutter="12">
                <el-col :xs="24" :lg="8" class="mb-3">
                    <el-card shadow="never" header="CPU 实时曲线">
                        <div ref="cpuChartRef" class="h-48 w-full" />
                    </el-card>
                </el-col>
                <el-col :xs="24" :lg="8" class="mb-3">
                    <el-card shadow="never" header="内存实时曲线">
                        <div ref="memChartRef" class="h-48 w-full" />
                    </el-card>
                </el-col>
                <el-col :xs="24" :lg="8" class="mb-3">
                    <el-card shadow="never" header="磁盘实时曲线">
                        <div ref="diskChartRef" class="h-48 w-full" />
                    </el-card>
                </el-col>
            </el-row>

            <el-row v-if="data" :gutter="12">
                <el-col :xs="24" :lg="14" class="mb-3">
                    <el-card shadow="never">
                        <template #header>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span>磁盘分区</span>
                                <div class="flex items-center gap-2 text-sm font-normal text-gray-500">
                                    <span>含 HGFS</span>
                                    <el-switch v-model="includeHgfs" />
                                </div>
                            </div>
                        </template>
                        <el-table :data="data.disks" size="small" empty-text="暂无数据">
                            <el-table-column prop="mount" label="挂载点" min-width="120" />
                            <el-table-column label="已用" width="100" align="right">
                                <template #default="{ row }">{{ formatBytes(row.used) }}</template>
                            </el-table-column>
                            <el-table-column label="总量" width="100" align="right">
                                <template #default="{ row }">{{ formatBytes(row.total) }}</template>
                            </el-table-column>
                            <el-table-column label="占用" min-width="160">
                                <template #default="{ row }">
                                    <el-progress
                                        :percentage="row.usage_percent ?? 0"
                                        :color="pctColor(row.usage_percent)"
                                        :stroke-width="10"
                                    />
                                </template>
                            </el-table-column>
                        </el-table>
                    </el-card>
                </el-col>
                <el-col :xs="24" :lg="10" class="mb-3">
                    <el-card shadow="never" header="运行环境">
                        <el-descriptions v-if="data" :column="1" size="small" border>
                            <el-descriptions-item label="主机名">{{ data.runtime.hostname || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="系统">{{ data.runtime.uname }}</el-descriptions-item>
                            <el-descriptions-item label="PHP">{{ data.runtime.php_version }} ({{ data.runtime.sapi }})</el-descriptions-item>
                            <el-descriptions-item label="Web 服务">{{ data.runtime.server_software || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="运行时长">{{ formatUptime(data.runtime.uptime_seconds) }}</el-descriptions-item>
                        </el-descriptions>
                    </el-card>
                </el-col>
            </el-row>
        </div>
    </ga-page>
</template>
