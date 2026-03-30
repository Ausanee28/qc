<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Deferred, Head, router } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, ref, watch } from 'vue';

const BarChart = defineAsyncComponent(() => import('@/lib/dashboard-charts').then((module) => module.Bar));
const LineChart = defineAsyncComponent(() => import('@/lib/dashboard-charts').then((module) => module.Line));
const DoughnutChart = defineAsyncComponent(() => import('@/lib/dashboard-charts').then((module) => module.Doughnut));

const props = defineProps({
    currentPeriod: { type: String, default: 'month' },
    metrics: {
        type: Object,
        default: () => ({
            todayCount: 0,
            monthCount: 0,
            periodJobs: 0,
            okCount: 0,
            ngCount: 0,
            pendingCount: 0,
            todayOK: 0,
            todayNG: 0,
            yieldRate: 0,
            defectRate: 0,
            avgTestTime: 0,
            totalTests: 0,
            testsPerJob: 0,
        }),
    },
    weeklyData: { type: Array, default: () => [] },
    dailyData: { type: Array, default: () => [] },
    monthlyData: { type: Array, default: () => [] },
});

const periodOptions = [
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'Last 7 Days' },
    { value: 'month', label: 'This Month' },
    { value: '30days', label: 'Last 30 Days' },
    { value: 'quarter', label: 'This Quarter' },
];

const periodLabels = {
    today: 'Today',
    week: 'Last 7 Days',
    month: 'This Month',
    '30days': 'Last 30 Days',
    quarter: 'This Quarter',
};

const selectedPeriod = ref(props.currentPeriod);
const isChangingPeriod = ref(false);
const metrics = computed(() => props.metrics);
const currentPeriodLabel = computed(() => periodLabels[props.currentPeriod] || 'This Month');

watch(() => props.currentPeriod, (value) => {
    selectedPeriod.value = value;
});

watch(selectedPeriod, (value, previous) => {
    if (!value || value === previous) {
        return;
    }

    isChangingPeriod.value = true;
    router.get(route('dashboard'), { period: value }, {
        replace: true,
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isChangingPeriod.value = false;
        },
    });
});

const formatNumber = (value) => Number(value || 0).toLocaleString();
const formatPercent = (value) => `${Number(value || 0).toFixed(1)}%`;
const formatMinutes = (value) => `${Number(value || 0).toFixed(0)} min`;

const summaryCards = computed(() => ([
    {
        label: 'Jobs In Period',
        value: formatNumber(props.metrics.periodJobs),
        note: `${formatNumber(props.metrics.pendingCount)} pending`,
        tone: 'amber',
    },
    {
        label: 'Total Tests',
        value: formatNumber(props.metrics.totalTests),
        note: `${formatNumber(props.metrics.testsPerJob)} tests / job`,
        tone: 'orange',
    },
    {
        label: 'Yield',
        value: formatPercent(props.metrics.yieldRate),
        note: `${formatPercent(props.metrics.defectRate)} NG`,
        tone: 'stone',
    },
    {
        label: 'Avg Test Time',
        value: formatMinutes(props.metrics.avgTestTime),
        note: 'Per completed test',
        tone: 'ink',
    },
]));

const qualityChartData = computed(() => ({
    labels: ['OK', 'NG'],
    datasets: [{
        data: [Number(props.metrics.okCount || 0), Number(props.metrics.ngCount || 0)],
        backgroundColor: ['#f59e0b', '#9a3412'],
        borderWidth: 0,
        hoverOffset: 4,
        cutout: '72%',
    }],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: '#d6d3d1',
                padding: 18,
                usePointStyle: true,
            },
        },
        tooltip: {
            backgroundColor: 'rgba(12,12,12,0.95)',
            borderColor: 'rgba(251,146,60,0.2)',
            borderWidth: 1,
            titleColor: '#fafaf9',
            bodyColor: '#e7e5e4',
        },
    },
};

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            labels: {
                color: '#d6d3d1',
                usePointStyle: true,
                padding: 16,
            },
        },
        tooltip: {
            backgroundColor: 'rgba(12,12,12,0.95)',
            borderColor: 'rgba(251,146,60,0.2)',
            borderWidth: 1,
            titleColor: '#fafaf9',
            bodyColor: '#e7e5e4',
        },
    },
    scales: {
        x: {
            ticks: { color: '#a8a29e' },
            grid: { display: false },
            border: { display: false },
        },
        y: {
            beginAtZero: true,
            ticks: { color: '#a8a29e' },
            grid: { color: 'rgba(255,255,255,0.06)' },
            border: { display: false },
        },
    },
};

const barOptions = {
    ...lineOptions,
    plugins: {
        ...lineOptions.plugins,
        legend: { display: false },
    },
};

const dailySeries = computed(() => {
    const rows = (props.dailyData?.length ? props.dailyData : props.weeklyData).map((item) => {
        const ok = Number(item.ok || 0);
        const ng = Number(item.ng || 0);
        return {
            label: item.label,
            ok,
            ng,
            total: ok + ng,
        };
    });

    return {
        rows,
        total: rows.reduce((sum, row) => sum + row.total, 0),
        peak: rows.reduce((best, row) => (row.total > best.total ? row : best), { label: '-', total: 0 }),
    };
});

const dailyTrendData = computed(() => ({
    labels: dailySeries.value.rows.map((item) => item.label),
    datasets: [
        {
            label: 'OK',
            data: dailySeries.value.rows.map((item) => item.ok),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245,158,11,0.18)',
            fill: true,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 4,
        },
        {
            label: 'NG',
            data: dailySeries.value.rows.map((item) => item.ng),
            borderColor: '#9a3412',
            backgroundColor: 'rgba(154,52,18,0.14)',
            fill: true,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 4,
        },
    ],
}));

const monthlySeries = computed(() => {
    const rows = (props.monthlyData || []).map((item) => ({
        label: item.fullLabel || item.label,
        shortLabel: item.label,
        total: Number(item.total || 0),
        yield: Number(item.yield || 0),
        ok: Number(item.ok || 0),
        ng: Number(item.ng || 0),
    }));

    const bestMonth = rows.reduce((best, row) => (row.yield > best.yield ? row : best), { label: '-', yield: 0, total: 0 });
    const weakestMonth = rows.reduce((worst, row) => {
        if (worst.label === '-') {
            return row;
        }

        return row.yield < worst.yield ? row : worst;
    }, { label: '-', yield: 0, total: 0 });

    return { rows, bestMonth, weakestMonth };
});

const monthlyTrendData = computed(() => ({
    labels: monthlySeries.value.rows.map((item) => item.shortLabel),
    datasets: [
        {
            type: 'bar',
            label: 'Total tests',
            data: monthlySeries.value.rows.map((item) => item.total),
            backgroundColor: 'rgba(245,158,11,0.28)',
            borderRadius: 10,
            yAxisID: 'y',
        },
        {
            type: 'line',
            label: 'Yield %',
            data: monthlySeries.value.rows.map((item) => item.yield),
            borderColor: '#fb923c',
            backgroundColor: '#fb923c',
            tension: 0.32,
            pointRadius: 3,
            pointHoverRadius: 5,
            yAxisID: 'y1',
        },
    ],
}));

const monthlyMixedOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: lineOptions.plugins,
    scales: {
        x: lineOptions.scales.x,
        y: {
            beginAtZero: true,
            ticks: { color: '#a8a29e' },
            grid: { color: 'rgba(255,255,255,0.06)' },
            border: { display: false },
        },
        y1: {
            beginAtZero: true,
            position: 'right',
            min: 0,
            max: 100,
            ticks: {
                color: '#fb923c',
                callback: (value) => `${value}%`,
            },
            grid: { display: false },
            border: { display: false },
        },
    },
};

const regressionForecast = (series, clamp = null) => {
    if (series.length === 0) {
        return 0;
    }

    if (series.length === 1) {
        return series[0];
    }

    const n = series.length;
    const meanX = (n - 1) / 2;
    const meanY = series.reduce((sum, value) => sum + value, 0) / n;

    let numerator = 0;
    let denominator = 0;

    series.forEach((value, index) => {
        numerator += (index - meanX) * (value - meanY);
        denominator += (index - meanX) ** 2;
    });

    const slope = denominator === 0 ? 0 : numerator / denominator;
    const intercept = meanY - (slope * meanX);
    let projection = intercept + (slope * n);

    if (clamp) {
        projection = Math.min(clamp.max, Math.max(clamp.min, projection));
    }

    return projection;
};

const forecastSummary = computed(() => {
    const totals = monthlySeries.value.rows.map((item) => item.total);
    const yields = monthlySeries.value.rows.map((item) => item.yield);
    const nextTotal = Math.max(0, Math.round(regressionForecast(totals)));
    const nextYield = regressionForecast(yields, { min: 0, max: 100 });
    const latest = monthlySeries.value.rows.at(-1);
    const delta = latest ? Number((nextYield - latest.yield).toFixed(1)) : 0;

    return {
        nextTotal,
        nextYield: Number(nextYield.toFixed(1)),
        delta,
        latestLabel: latest?.label || '-',
    };
});

const archiveHighlights = computed(() => ([
    {
        label: 'Best Month',
        value: monthlySeries.value.bestMonth.label,
        note: `${formatPercent(monthlySeries.value.bestMonth.yield)} yield`,
    },
    {
        label: 'Weakest Month',
        value: monthlySeries.value.weakestMonth.label,
        note: `${formatPercent(monthlySeries.value.weakestMonth.yield)} yield`,
    },
    {
        label: 'Forecast',
        value: `${formatPercent(forecastSummary.value.nextYield)}`,
        note: `${forecastSummary.value.delta >= 0 ? '+' : ''}${forecastSummary.value.delta.toFixed(1)} pts vs ${forecastSummary.value.latestLabel}`,
    },
]));

const historyBars = computed(() => ({
    labels: (props.weeklyData || []).map((item) => item.label),
    datasets: [{
        data: (props.weeklyData || []).map((item) => Number(item.ok || 0) + Number(item.ng || 0)),
        backgroundColor: '#fb923c',
        borderRadius: 10,
    }],
}));
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #title>Dashboard</template>

        <div class="dashboard-clean space-y-6">
            <section class="hero-panel">
                <div class="hero-panel__copy">
                    <div class="dashboard-kicker">QC Dashboard</div>
                    <h1 class="hero-panel__title">Summary first, trend second, decisions faster.</h1>
                    <p class="hero-panel__text">
                        Focus on the signals that matter most: total workload, OK/NG quality, daily and monthly trend, plus a simple forward-looking forecast.
                    </p>
                </div>

                <div class="hero-panel__controls">
                    <label class="hero-select">
                        <span>Period</span>
                        <select v-model="selectedPeriod" :disabled="isChangingPeriod">
                            <option v-for="option in periodOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>
                    <div class="hero-badge">
                        <span class="hero-badge__dot"></span>
                        {{ currentPeriodLabel }}
                    </div>
                </div>
            </section>

            <section class="summary-grid">
                <article v-for="card in summaryCards" :key="card.label" class="summary-card" :data-tone="card.tone">
                    <div class="summary-card__label">{{ card.label }}</div>
                    <div class="summary-card__value">{{ card.value }}</div>
                    <div class="summary-card__note">{{ card.note }}</div>
                </article>
            </section>

            <section class="dashboard-main">
                <article class="surface-panel surface-panel--quality">
                    <div class="panel-head">
                        <div>
                            <div class="dashboard-kicker">Quality Summary</div>
                            <h2 class="panel-title">OK / NG overview</h2>
                        </div>
                    </div>

                    <div class="quality-layout">
                        <div class="quality-chart">
                            <DoughnutChart :data="qualityChartData" :options="doughnutOptions" />
                            <div class="quality-chart__center">
                                <strong>{{ formatPercent(metrics.yieldRate) }}</strong>
                                <span>Yield</span>
                            </div>
                        </div>

                        <div class="quality-stats">
                            <div class="quality-stat">
                                <span>OK</span>
                                <strong>{{ formatNumber(metrics.okCount) }}</strong>
                            </div>
                            <div class="quality-stat">
                                <span>NG</span>
                                <strong>{{ formatNumber(metrics.ngCount) }}</strong>
                            </div>
                            <div class="quality-stat">
                                <span>Pending</span>
                                <strong>{{ formatNumber(metrics.pendingCount) }}</strong>
                            </div>
                            <div class="quality-stat">
                                <span>Today</span>
                                <strong>{{ formatNumber(metrics.todayOK + metrics.todayNG) }}</strong>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="surface-panel">
                    <div class="panel-head">
                        <div>
                            <div class="dashboard-kicker">Daily Trend</div>
                            <h2 class="panel-title">Daily OK / NG movement</h2>
                        </div>
                        <div class="panel-note">Peak: {{ dailySeries.peak.label }} ({{ formatNumber(dailySeries.peak.total) }})</div>
                    </div>

                    <Deferred data="dailyData">
                        <template #fallback>
                            <div class="chart-shell"></div>
                        </template>

                        <div class="chart-wrap">
                            <LineChart :data="dailyTrendData" :options="lineOptions" />
                        </div>
                    </Deferred>
                </article>
            </section>

            <section class="dashboard-secondary">
                <article class="surface-panel">
                    <div class="panel-head">
                        <div>
                            <div class="dashboard-kicker">Monthly History</div>
                            <h2 class="panel-title">ย้อนหลังรายเดือน + yield trend</h2>
                        </div>
                    </div>

                    <Deferred data="monthlyData">
                        <template #fallback>
                            <div class="chart-shell chart-shell--tall"></div>
                        </template>

                        <div class="chart-wrap chart-wrap--tall">
                            <BarChart :data="monthlyTrendData" :options="monthlyMixedOptions" />
                        </div>
                    </Deferred>
                </article>

                <article class="surface-panel surface-panel--stack">
                    <div class="panel-head">
                        <div>
                            <div class="dashboard-kicker">Forecast</div>
                            <h2 class="panel-title">Simple next-step view</h2>
                        </div>
                    </div>

                    <div class="forecast-card">
                        <span>Projected next month tests</span>
                        <strong>{{ formatNumber(forecastSummary.nextTotal) }}</strong>
                    </div>

                    <div class="forecast-card forecast-card--accent">
                        <span>Projected next month yield</span>
                        <strong>{{ formatPercent(forecastSummary.nextYield) }}</strong>
                        <small>{{ forecastSummary.delta >= 0 ? '+' : '' }}{{ forecastSummary.delta.toFixed(1) }} pts vs {{ forecastSummary.latestLabel }}</small>
                    </div>

                    <div class="history-highlights">
                        <div v-for="item in archiveHighlights" :key="item.label" class="history-highlight">
                            <span>{{ item.label }}</span>
                            <strong>{{ item.value }}</strong>
                            <small>{{ item.note }}</small>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dashboard-foot">
                <article class="surface-panel">
                    <div class="panel-head">
                        <div>
                            <div class="dashboard-kicker">Weekly Snapshot</div>
                            <h2 class="panel-title">Recent 7-day volume</h2>
                        </div>
                        <div class="panel-note">{{ formatNumber(dailySeries.total) }} total checks</div>
                    </div>

                    <Deferred data="weeklyData">
                        <template #fallback>
                            <div class="chart-shell chart-shell--short"></div>
                        </template>

                        <div class="chart-wrap chart-wrap--short">
                            <BarChart :data="historyBars" :options="barOptions" />
                        </div>
                    </Deferred>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.dashboard-clean {
    position: relative;
}

.hero-panel,
.surface-panel,
.summary-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: linear-gradient(180deg, rgba(17, 13, 11, 0.96), rgba(10, 9, 8, 0.98));
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.24);
}

.hero-panel {
    display: grid;
    gap: 1.5rem;
    border-radius: 32px;
    padding: 1.6rem;
    background:
        radial-gradient(circle at top right, rgba(251, 146, 60, 0.16), transparent 26%),
        linear-gradient(135deg, rgba(15, 12, 11, 0.98), rgba(28, 18, 13, 0.96));
}

.hero-panel__title {
    margin-top: 0.35rem;
    font-size: clamp(2rem, 3vw, 3.1rem);
    line-height: 0.95;
    letter-spacing: -0.05em;
    color: #fff7ed;
}

.hero-panel__text {
    margin-top: 0.9rem;
    max-width: 56rem;
    color: rgba(231, 229, 228, 0.82);
    line-height: 1.8;
}

.hero-panel__controls {
    display: flex;
    flex-wrap: wrap;
    gap: 0.9rem;
    align-items: flex-end;
}

.hero-select,
.hero-badge {
    display: inline-flex;
    flex-direction: column;
    gap: 0.35rem;
    border-radius: 18px;
    padding: 0.85rem 1rem;
    background: rgba(0, 0, 0, 0.22);
}

.hero-select span,
.hero-badge {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #fdba74;
}

.hero-select select {
    border: 0;
    background: transparent;
    color: #fafaf9;
    font-size: 0.95rem;
    font-weight: 600;
    outline: none;
}

.hero-badge {
    align-items: flex-start;
    justify-content: center;
    min-width: 170px;
    color: #f5f5f4;
}

.hero-badge__dot {
    width: 0.6rem;
    height: 0.6rem;
    border-radius: 999px;
    background: #fb923c;
    box-shadow: 0 0 0 6px rgba(251, 146, 60, 0.14);
}

.dashboard-kicker {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #fb923c;
}

.summary-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.summary-card {
    border-radius: 24px;
    padding: 1.15rem;
}

.summary-card[data-tone='amber'] {
    background: linear-gradient(180deg, rgba(68, 36, 14, 0.96), rgba(24, 15, 11, 0.98));
}

.summary-card[data-tone='orange'] {
    background: linear-gradient(180deg, rgba(58, 28, 12, 0.96), rgba(24, 15, 11, 0.98));
}

.summary-card__label {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.52);
}

.summary-card__value {
    margin-top: 0.65rem;
    font-size: 2rem;
    font-weight: 650;
    letter-spacing: -0.04em;
    color: #fff7ed;
}

.summary-card__note {
    margin-top: 0.55rem;
    color: #d6d3d1;
    font-size: 0.92rem;
}

.dashboard-main,
.dashboard-secondary {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1.1fr 1fr;
}

.dashboard-foot {
    display: grid;
    gap: 1rem;
}

.surface-panel {
    border-radius: 28px;
    padding: 1.35rem;
}

.surface-panel--quality {
    background:
        radial-gradient(circle at top right, rgba(251, 146, 60, 0.14), transparent 22%),
        linear-gradient(180deg, rgba(19, 15, 13, 0.98), rgba(10, 9, 8, 0.98));
}

.surface-panel--stack {
    display: grid;
    gap: 0.9rem;
    align-content: start;
}

.panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.panel-title {
    margin-top: 0.35rem;
    font-size: 1.4rem;
    font-weight: 600;
    letter-spacing: -0.03em;
    color: #fff7ed;
}

.panel-note {
    color: #d6d3d1;
    font-size: 0.88rem;
}

.quality-layout {
    display: grid;
    gap: 1rem;
    grid-template-columns: minmax(220px, 0.92fr) minmax(0, 1fr);
    align-items: center;
}

.quality-chart {
    position: relative;
    height: 280px;
}

.quality-chart__center {
    position: absolute;
    inset: 50% auto auto 50%;
    transform: translate(-50%, -50%);
    display: grid;
    gap: 0.25rem;
    text-align: center;
    pointer-events: none;
}

.quality-chart__center strong {
    font-size: 2rem;
    color: #fff7ed;
}

.quality-chart__center span {
    color: rgba(231, 229, 228, 0.72);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.quality-stats {
    display: grid;
    gap: 0.9rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.quality-stat,
.forecast-card,
.history-highlight {
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    padding: 1rem;
}

.quality-stat span,
.forecast-card span,
.history-highlight span {
    display: block;
    color: rgba(231, 229, 228, 0.62);
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.quality-stat strong,
.forecast-card strong,
.history-highlight strong {
    display: block;
    margin-top: 0.55rem;
    color: #fff7ed;
    font-size: 1.5rem;
    font-weight: 650;
}

.forecast-card small,
.history-highlight small {
    display: block;
    margin-top: 0.45rem;
    color: #d6d3d1;
}

.forecast-card--accent {
    border-color: rgba(251, 146, 60, 0.22);
    background: rgba(251, 146, 60, 0.09);
}

.history-highlights {
    display: grid;
    gap: 0.8rem;
}

.chart-wrap {
    height: 320px;
}

.chart-wrap--tall {
    height: 360px;
}

.chart-wrap--short {
    height: 220px;
}

.chart-shell {
    height: 320px;
    border-radius: 22px;
    background: linear-gradient(90deg, rgba(41, 37, 36, 0.9), rgba(68, 64, 60, 0.95), rgba(41, 37, 36, 0.9));
    background-size: 200% 100%;
    animation: dashboard-shimmer 1.6s linear infinite;
}

.chart-shell--tall {
    height: 360px;
}

.chart-shell--short {
    height: 220px;
}

@keyframes dashboard-shimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

@media (max-width: 1023px) {
    .summary-grid,
    .dashboard-main,
    .dashboard-secondary {
        grid-template-columns: 1fr;
    }

    .quality-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767px) {
    .hero-panel,
    .surface-panel,
    .summary-card {
        border-radius: 22px;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .quality-stats {
        grid-template-columns: 1fr;
    }
}
</style>
