<script setup lang="ts">
import type { DashboardOverview } from '@/apis/dashboard'
import * as dashboardApi from '@/apis/dashboard'
import GaPage from '@/components/ga-page/index.vue'
import { useWindowSize } from '@vueuse/core'
import * as echarts from 'echarts'
import { ElMessage } from 'element-plus'
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const loading = ref(false)
const trendDays = ref(7)
const data = ref<DashboardOverview | null>(null)

const opsTrendRef = ref<HTMLElement>()
const activeTrendRef = ref<HTMLElement>()

let opsChart: echarts.ECharts | null = null
let activeChart: echarts.ECharts | null = null

const kpiCards = computed(() => {
    const k = data.value?.kpi
    if (!k) return []
    return [
        { label: '管理员', value: k.admin_total, sub: `启用 ${k.admin_enabled} / 禁用 ${k.admin_disabled}` },
        { label: '角色', value: k.role_count, sub: `共 ${k.role_count} 个` },
        {
            label: '权限规则',
            value: k.frontend_rule_count + k.backend_api_count,
            sub: `菜单 ${k.frontend_rule_count} / API ${k.backend_api_count}`,
        },
        { label: '今日活跃', value: k.today_active_users, sub: '去重人数' },
        {
            label: '今日操作',
            value: k.today_ops,
            sub: `写操作 ${k.today_write_ops} / 登录 ${k.today_login_count}`,
        },
    ]
})

/** formatTrendDates 趋势横轴日期（MM-DD） */
const formatTrendDates = (dates: string[]) => dates.map((d) => d.slice(5))

/** renderCharts 渲染 ECharts 趋势图 */
const renderCharts = () => {
    const trend = data.value?.trend
    if (!trend) return

    const dates = formatTrendDates(trend.dates)
    const axisLabel = { color: '#909399', fontSize: 11 }
    const splitLine = { lineStyle: { color: '#ebeef5', type: 'dashed' as const } }

    if (opsTrendRef.value) {
        opsChart ??= echarts.init(opsTrendRef.value)
        opsChart.setOption(
            {
                tooltip: { trigger: 'axis' },
                legend: { top: 0, right: 0, itemWidth: 10, itemHeight: 10, textStyle: { fontSize: 12 } },
                grid: { left: 8, right: 8, top: 32, bottom: 4, containLabel: true },
                xAxis: { type: 'category', data: dates, axisLabel, axisLine: { lineStyle: { color: '#dcdfe6' } } },
                yAxis: { type: 'value', minInterval: 1, axisLabel, splitLine },
                series: [
                    { name: '写操作', type: 'bar', barMaxWidth: 16, itemStyle: { color: '#60a5fa' }, data: trend.write_ops },
                    { name: '登录', type: 'bar', barMaxWidth: 16, itemStyle: { color: '#4ade80' }, data: trend.logins },
                ],
            },
            true,
        )
    }

    if (activeTrendRef.value) {
        activeChart ??= echarts.init(activeTrendRef.value)
        activeChart.setOption(
            {
                tooltip: { trigger: 'axis' },
                grid: { left: 8, right: 8, top: 16, bottom: 4, containLabel: true },
                xAxis: { type: 'category', data: dates, axisLabel, axisLine: { lineStyle: { color: '#dcdfe6' } } },
                yAxis: { type: 'value', minInterval: 1, axisLabel, splitLine },
                series: [
                    {
                        name: '活跃人数',
                        type: 'bar',
                        barMaxWidth: 20,
                        itemStyle: { color: '#fb923c', borderRadius: [3, 3, 0, 0] },
                        data: trend.active_users,
                    },
                ],
            },
            true,
        )
    }
}

/** resizeCharts 窗口尺寸变化时重绘图表 */
const resizeCharts = () => {
    opsChart?.resize()
    activeChart?.resize()
}

const load = async () => {
    loading.value = true
    try {
        data.value = await dashboardApi.fetchUsageOverview(trendDays.value)
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '加载失败')
    } finally {
        loading.value = false
    }
}

const { width } = useWindowSize()
watch(width, resizeCharts)

watch(
    () => data.value?.trend,
    () => {
        void nextTick(renderCharts)
    },
)

onMounted(() => {
    void load()
})

onUnmounted(() => {
    opsChart?.dispose()
    activeChart?.dispose()
    opsChart = null
    activeChart = null
})
</script>

<template>
    <ga-page padding="md" min-height="calc(100vh - 120px)">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span>系统使用情况</span>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-normal text-gray-500">趋势范围</span>
                    <el-radio-group v-model="trendDays" @change="load">
                        <el-radio-button :value="7">近 7 日</el-radio-button>
                        <el-radio-button :value="30">近 30 日</el-radio-button>
                    </el-radio-group>
                    <el-button @click="load">刷新</el-button>
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
                    <div class="text-lg font-semibold leading-none">{{ item.value }}</div>
                    <div class="truncate text-[11px] leading-none text-gray-500">{{ item.sub }}</div>
                </div>
            </div>

            <el-row v-if="data" :gutter="12">
                <el-col :xs="24" :lg="12" class="mb-3">
                    <el-card shadow="never" header="操作量趋势">
                        <div ref="opsTrendRef" class="h-52 w-full" />
                    </el-card>
                </el-col>
                <el-col :xs="24" :lg="12" class="mb-3">
                    <el-card shadow="never" header="活跃人数趋势">
                        <div ref="activeTrendRef" class="h-52 w-full" />
                    </el-card>
                </el-col>
            </el-row>

            <el-row v-if="data" :gutter="12">
                <el-col :xs="24" :lg="12" class="mb-3">
                    <el-card shadow="never" header="活跃管理员 Top 5">
                        <el-table :data="data.top_admins" size="small" empty-text="暂无数据">
                            <el-table-column type="index" label="#" width="48" />
                            <el-table-column prop="name" label="管理员" min-width="120" />
                            <el-table-column prop="count" label="操作次数" width="100" align="right" />
                        </el-table>
                    </el-card>
                </el-col>
                <el-col :xs="24" :lg="12" class="mb-3">
                    <el-card shadow="never" header="高频写操作 Top 10">
                        <el-table :data="data.top_actions" size="small" empty-text="暂无数据">
                            <el-table-column type="index" label="#" width="48" />
                            <el-table-column prop="action" label="动作" min-width="180" show-overflow-tooltip />
                            <el-table-column prop="count" label="次数" width="80" align="right" />
                        </el-table>
                    </el-card>
                </el-col>
            </el-row>

            <el-row v-if="data" :gutter="12">
                <el-col :xs="24" :lg="12" class="mb-3">
                    <el-card shadow="never" header="管理员角色分布">
                        <el-table :data="data.role_distribution" size="small" empty-text="暂无数据">
                            <el-table-column prop="role_name" label="角色" min-width="140" />
                            <el-table-column prop="count" label="人数" width="80" align="right" />
                        </el-table>
                    </el-card>
                </el-col>
                <el-col :xs="24" :lg="12" class="mb-3">
                    <el-card shadow="never" :header="`模块操作分布（近 ${trendDays} 日）`">
                        <el-table :data="data.module_distribution" size="small" empty-text="暂无数据">
                            <el-table-column prop="module" label="模块" min-width="140" />
                            <el-table-column prop="count" label="写操作次数" width="110" align="right" />
                        </el-table>
                    </el-card>
                </el-col>
            </el-row>

            <el-card v-if="data" shadow="never" header="最近操作日志">
                <el-table :data="data.recent_logs" size="small" empty-text="暂无数据">
                    <el-table-column prop="create_time" label="时间" width="170" />
                    <el-table-column prop="admin_name" label="操作人" width="120" />
                    <el-table-column prop="action" label="动作" min-width="160" show-overflow-tooltip />
                    <el-table-column prop="summary" label="说明" min-width="200" show-overflow-tooltip />
                    <el-table-column prop="ip" label="IP" width="130" />
                </el-table>
            </el-card>
        </div>
    </ga-page>
</template>
