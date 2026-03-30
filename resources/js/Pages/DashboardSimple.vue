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
            todayCount: 0, monthCount: 0, periodJobs: 0,
            okCount: 0, ngCount: 0, pendingCount: 0,
            todayOK: 0, todayNG: 0, yieldRate: 0, defectRate: 0,
            avgTestTime: 0, totalTests: 0, testsPerJob: 0,
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
    today: 'Today', week: 'Last 7 Days', month: 'This Month',
    '30days': 'Last 30 Days', quarter: 'This Quarter',
};

const selectedPeriod = ref(props.currentPeriod);
const isChangingPeriod = ref(false);
const currentPeriodLabel = computed(() => periodLabels[props.currentPeriod] || 'This Month');

watch(() => props.currentPeriod, (v) => { selectedPeriod.value = v; });
watch(selectedPeriod, (v, prev) => {
    if (!v || v === prev) return;
    isChangingPeriod.value = true;
    router.get(route('dashboard'), { period: v }, {
        replace: true, preserveState: true, preserveScroll: true,
        onFinish: () => { isChangingPeriod.value = false; },
    });
});

const fmt = (v) => Number(v || 0).toLocaleString();
const pct = (v) => `${Number(v || 0).toFixed(1)}%`;

/* ── KPI cards ── */
const kpiCards = computed(() => ([
    { label: 'OK %', value: pct(props.metrics.yieldRate), accent: true },
    { label: 'NG %', value: pct(props.metrics.defectRate), accent: false, danger: true },
    { label: 'Jobs', value: fmt(props.metrics.periodJobs) },
    { label: 'Total Tests', value: fmt(props.metrics.totalTests) },
    { label: 'Pending', value: fmt(props.metrics.pendingCount) },
]));

/* ── Quality doughnut ── */
const qualityChartData = computed(() => ({
    labels: ['OK', 'NG'],
    datasets: [{
        data: [Number(props.metrics.okCount || 0), Number(props.metrics.ngCount || 0)],
        backgroundColor: ['#f59e0b', '#ef4444'],
        borderWidth: 0, hoverOffset: 6,
    }],
}));

const doughnutOpts = {
    responsive: true, maintainAspectRatio: false, cutout: '72%',
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(10,10,10,0.95)', titleColor: '#fafaf9', bodyColor: '#f5f5f4',
            borderColor: 'rgba(251,146,60,0.2)', borderWidth: 1,
            callbacks: {
                label: (ctx) => {
                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    const p = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : '0.0';
                    return `${ctx.label}: ${ctx.parsed.toLocaleString()} (${p}%)`;
                },
            },
        },
    },
};

/* ── Shared chart config ── */
const axisColor = '#a8a29e';
const gridColor = 'rgba(255,255,255,0.06)';
const tooltipStyle = {
    backgroundColor: 'rgba(10,10,10,0.95)', titleColor: '#fafaf9', bodyColor: '#f5f5f4',
    borderColor: 'rgba(251,146,60,0.2)', borderWidth: 1,
};

const lineOpts = {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { labels: { color: '#e7e5e4', usePointStyle: true, padding: 14 } },
        tooltip: tooltipStyle,
    },
    scales: {
        x: { ticks: { color: axisColor }, grid: { display: false }, border: { display: false } },
        y: { beginAtZero: true, ticks: { color: axisColor }, grid: { color: gridColor }, border: { display: false } },
    },
    elements: { line: { tension: 0.35, borderWidth: 2.5 }, point: { radius: 2, hoverRadius: 5 } },
};

const barOpts = {
    ...lineOpts,
    elements: undefined,
    plugins: { ...lineOpts.plugins, legend: { display: false } },
};

/* ── Daily trend ── */
const dailyRows = computed(() => {
    const src = props.dailyData?.length ? props.dailyData : props.weeklyData;
    return src.map((d) => ({ label: d.label, ok: Number(d.ok || 0), ng: Number(d.ng || 0) }));
});

const dailyTrendData = computed(() => ({
    labels: dailyRows.value.map((d) => d.label),
    datasets: [
        { label: 'OK', data: dailyRows.value.map((d) => d.ok), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.15)', fill: true, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 },
        { label: 'NG', data: dailyRows.value.map((d) => d.ng), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.10)', fill: true, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 },
    ],
}));

/* ── Monthly trend ── */
const monthlySeries = computed(() => {
    return (props.monthlyData || []).map((m) => ({
        label: m.label, fullLabel: m.fullLabel || m.label,
        total: Number(m.total || 0), yield: Number(m.yield || 0),
        ok: Number(m.ok || 0), ng: Number(m.ng || 0),
        ngRate: Number(m.total || 0) > 0 ? (Number(m.ng || 0) / Number(m.total || 0)) * 100 : 0,
    }));
});

const monthlyTrendData = computed(() => ({
    labels: monthlySeries.value.map((m) => m.label),
    datasets: [
        { type: 'bar', label: 'Total Tests', data: monthlySeries.value.map((m) => m.total), backgroundColor: 'rgba(245,158,11,0.3)', borderRadius: 8, yAxisID: 'y' },
        { type: 'line', label: 'OK %', data: monthlySeries.value.map((m) => m.yield), borderColor: '#fb923c', backgroundColor: '#fb923c', tension: 0.3, pointRadius: 4, pointHoverRadius: 6, yAxisID: 'y1' },
        { type: 'line', label: 'NG %', data: monthlySeries.value.map((m) => m.ngRate), borderColor: '#ef4444', backgroundColor: '#ef4444', tension: 0.3, pointRadius: 4, pointHoverRadius: 6, borderDash: [6, 5], yAxisID: 'y1' },
    ],
}));

const monthlyMixedOpts = {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { labels: { color: '#e7e5e4', usePointStyle: true, padding: 14 } },
        tooltip: {
            ...tooltipStyle,
            callbacks: {
                label: (ctx) => {
                    const value = Number(ctx.parsed.y || 0);
                    return ctx.dataset.yAxisID === 'y1'
                        ? `${ctx.dataset.label}: ${value.toFixed(1)}%`
                        : `${ctx.dataset.label}: ${value.toLocaleString()}`;
                },
            },
        },
    },
    scales: {
        x: { ticks: { color: axisColor }, grid: { display: false }, border: { display: false } },
        y: { beginAtZero: true, ticks: { color: axisColor }, grid: { color: gridColor }, border: { display: false } },
        y1: { beginAtZero: true, position: 'right', min: 0, max: 100, ticks: { color: '#fb923c', callback: (v) => `${v}%` }, grid: { display: false }, border: { display: false } },
    },
};

/* ── Weekly bar ── */
const weeklyBarData = computed(() => ({
    labels: (props.weeklyData || []).map((d) => d.label),
    datasets: [
        { label: 'OK', data: (props.weeklyData || []).map((d) => Number(d.ok || 0)), backgroundColor: '#f59e0b', borderRadius: 6 },
        { label: 'NG', data: (props.weeklyData || []).map((d) => Number(d.ng || 0)), backgroundColor: '#ef4444', borderRadius: 6 },
    ],
}));

const weeklyBarOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#e7e5e4', usePointStyle: true, pointStyle: 'circle', padding: 12 } }, tooltip: tooltipStyle },
    scales: {
        x: { stacked: true, ticks: { color: axisColor }, grid: { display: false }, border: { display: false } },
        y: { stacked: true, beginAtZero: true, ticks: { color: axisColor }, grid: { color: gridColor }, border: { display: false } },
    },
};

/* ── Forecast ── */
const regressionForecast = (series, clamp = null) => {
    if (!series.length) return 0;
    if (series.length === 1) return series[0];
    const n = series.length;
    const mx = (n - 1) / 2;
    const my = series.reduce((s, v) => s + v, 0) / n;
    let num = 0, den = 0;
    series.forEach((v, i) => { num += (i - mx) * (v - my); den += (i - mx) ** 2; });
    const slope = den === 0 ? 0 : num / den;
    let proj = my - (slope * mx) + (slope * n);
    if (clamp) proj = Math.min(clamp.max, Math.max(clamp.min, proj));
    return proj;
};

const forecast = computed(() => {
    const totals = monthlySeries.value.map((m) => m.total);
    const yields = monthlySeries.value.map((m) => m.yield);
    const nextTotal = Math.max(0, Math.round(regressionForecast(totals)));
    const nextYield = Number(regressionForecast(yields, { min: 0, max: 100 }).toFixed(1));
    const latest = monthlySeries.value.at(-1);
    const delta = latest ? Number((nextYield - latest.yield).toFixed(1)) : 0;
    const best = monthlySeries.value.reduce((b, m) => m.yield > b.yield ? m : b, { label: '-', yield: 0 });
    const worst = monthlySeries.value.reduce((w, m) => {
        if (w.label === '-') return m;
        return m.yield < w.yield ? m : w;
    }, { label: '-', yield: 0 });
    return { nextTotal, nextYield, delta, latestLabel: latest?.label || '-', best, worst };
});

const monthlyInsights = computed(() => {
    const latest = monthlySeries.value.at(-1) || { label: '-', total: 0, yield: 0, ngRate: 0 };
    const previous = monthlySeries.value.at(-2);
    const totalHistory = monthlySeries.value.reduce((sum, month) => sum + month.total, 0);
    const delta = previous ? latest.yield - previous.yield : 0;

    return [
        {
            label: 'Latest Month',
            value: latest.label,
            note: previous ? `vs ${previous.label}` : 'current period edge',
        },
        {
            label: 'Latest Volume',
            value: fmt(latest.total),
            note: `${fmt(totalHistory)} total in view`,
        },
        {
            label: 'Latest Split',
            value: `${pct(latest.yield)} / ${pct(latest.ngRate)}`,
            note: 'OK % / NG %',
        },
        {
            label: 'Yield Change',
            value: `${delta >= 0 ? '+' : ''}${delta.toFixed(1)} pts`,
            note: previous ? `compared with ${previous.label}` : 'no prior month',
        },
    ];
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #title>Dashboard</template>

        <div class="db" :class="{ 'db--loading': isChangingPeriod }">
            <!-- ═══ HEADER ═══ -->
            <header class="db-header">
                <div class="db-header__left">
                    <h1 class="db-header__title">QC Dashboard</h1>
                    <div class="db-badge">
                        <span class="db-badge__dot"></span>
                        {{ currentPeriodLabel }}
                    </div>
                </div>
                <label class="db-period">
                    <select v-model="selectedPeriod" :disabled="isChangingPeriod">
                        <option v-for="o in periodOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                </label>
            </header>

            <!-- ═══ KPI STRIP ═══ -->
            <section class="kpi-strip">
                <article v-for="card in kpiCards" :key="card.label" class="kpi" :class="{ 'kpi--accent': card.accent, 'kpi--danger': card.danger }">
                    <div class="kpi__label">{{ card.label }}</div>
                    <div class="kpi__value">{{ card.value }}</div>
                </article>
            </section>

            <!-- ═══ ROW: Quality Doughnut + Daily Trend ═══ -->
            <section class="chart-row">
                <article class="card card--doughnut">
                    <div class="card__head">OK / NG Ratio</div>
                    <div class="doughnut-wrap">
                        <DoughnutChart :data="qualityChartData" :options="doughnutOpts" />
                        <div class="doughnut-center">
                            <div class="doughnut-center__ok"><strong>{{ pct(metrics.yieldRate) }}</strong> <span>OK</span></div>
                            <div class="doughnut-center__ng"><strong>{{ pct(metrics.defectRate) }}</strong> <span>NG</span></div>
                        </div>
                    </div>
                    <div class="doughnut-legend">
                        <div class="doughnut-legend__item">
                            <span class="dot dot--ok"></span>
                            <span>OK</span>
                            <strong>{{ fmt(metrics.okCount) }}</strong>
                        </div>
                        <div class="doughnut-legend__item">
                            <span class="dot dot--ng"></span>
                            <span>NG</span>
                            <strong>{{ fmt(metrics.ngCount) }}</strong>
                        </div>
                        <div class="doughnut-legend__item">
                            <span class="dot dot--today"></span>
                            <span>Today</span>
                            <strong>{{ fmt(metrics.todayOK + metrics.todayNG) }}</strong>
                        </div>
                    </div>
                </article>

                <article class="card card--chart">
                    <div class="card__head">Daily OK / NG Trend</div>
                    <Deferred data="dailyData">
                        <template #fallback><div class="shimmer"></div></template>
                        <div class="chart-area"><LineChart :data="dailyTrendData" :options="lineOpts" /></div>
                    </Deferred>
                </article>
            </section>

            <!-- ═══ ROW: Monthly Trend + Forecast & Weekly ═══ -->
            <section class="chart-row chart-row--bottom">
                <article class="card card--chart card--monthly">
                    <div class="card__head">Monthly Tests, OK % and NG %</div>
                    <Deferred data="monthlyData">
                        <template #fallback><div class="shimmer shimmer--tall"></div></template>
                        <div class="chart-area chart-area--tall"><BarChart :data="monthlyTrendData" :options="monthlyMixedOpts" /></div>
                    </Deferred>

                    <div class="monthly-insights">
                        <div v-for="item in monthlyInsights" :key="item.label" class="monthly-insight">
                            <div class="monthly-insight__label">{{ item.label }}</div>
                            <div class="monthly-insight__value">{{ item.value }}</div>
                            <div class="monthly-insight__note">{{ item.note }}</div>
                        </div>
                    </div>
                </article>

                <div class="side-stack">
                    <article class="card card--forecast">
                        <div class="card__head">Forecast</div>
                        <div class="forecast-grid">
                            <div class="fc">
                                <div class="fc__label">Next Month Tests</div>
                                <div class="fc__value">{{ fmt(forecast.nextTotal) }}</div>
                            </div>
                            <div class="fc fc--accent">
                                <div class="fc__label">Next Month OK %</div>
                                <div class="fc__value">{{ pct(forecast.nextYield) }}</div>
                                <div class="fc__delta" :class="forecast.delta >= 0 ? 'fc__delta--up' : 'fc__delta--down'">
                                    {{ forecast.delta >= 0 ? '+' : '' }}{{ forecast.delta.toFixed(1) }} pts
                                </div>
                            </div>
                            <div class="fc">
                                <div class="fc__label">Best Month</div>
                                <div class="fc__value">{{ forecast.best.label }}</div>
                                <div class="fc__sub">{{ pct(forecast.best.yield) }}</div>
                            </div>
                            <div class="fc">
                                <div class="fc__label">Worst Month</div>
                                <div class="fc__value">{{ forecast.worst.label }}</div>
                                <div class="fc__sub">{{ pct(forecast.worst.yield) }}</div>
                            </div>
                        </div>
                    </article>

                    <article class="card card--chart card--compact">
                        <div class="card__head">Weekly OK / NG</div>
                        <Deferred data="weeklyData">
                            <template #fallback><div class="shimmer shimmer--short"></div></template>
                            <div class="chart-area chart-area--short"><BarChart :data="weeklyBarData" :options="weeklyBarOpts" /></div>
                        </Deferred>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* ── Base ── */
.db {
    display: grid;
    gap: 1.25rem;
    transition: opacity 160ms ease;
}
.db--loading { opacity: 0.6; pointer-events: none; }

/* ── Header ── */
.db-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.db-header__left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.db-header__title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff7ed;
    letter-spacing: -0.03em;
}
.db-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    background: rgba(251,146,60,0.12);
    font-size: 0.82rem;
    font-weight: 600;
    color: #fdba74;
}
.db-badge__dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: #fb923c;
    box-shadow: 0 0 0 4px rgba(251,146,60,0.18);
}
.db-period select {
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    background: rgba(0,0,0,0.3);
    color: #fafaf9;
    font-size: 0.9rem;
    font-weight: 600;
    padding: 0.55rem 1rem;
    outline: none;
    cursor: pointer;
    transition: border-color 160ms;
}
.db-period select:hover { border-color: rgba(251,146,60,0.35); }
.db-period select option { background: #1c1917; color: #fafaf9; }

/* ── KPI Strip ── */
.kpi-strip {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(5, 1fr);
}
.kpi {
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(20,16,13,0.95), rgba(12,10,9,0.97));
    padding: 1.1rem 1.25rem;
    transition: border-color 200ms, transform 200ms;
}
.kpi:hover { border-color: rgba(251,146,60,0.2); transform: translateY(-2px); }
.kpi--accent { border-color: rgba(245,158,11,0.25); background: linear-gradient(135deg, rgba(60,30,10,0.7), rgba(20,14,10,0.95)); }
.kpi__label {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.55);
}
.kpi--accent .kpi__label { color: #fdba74; }
.kpi--danger { border-color: rgba(239,68,68,0.25); background: linear-gradient(135deg, rgba(60,15,15,0.7), rgba(20,10,10,0.95)); }
.kpi--danger .kpi__label { color: #fca5a5; }
.kpi--danger .kpi__value { color: #fecaca; }
.kpi__value {
    margin-top: 0.4rem;
    font-size: 1.85rem;
    font-weight: 700;
    letter-spacing: -0.04em;
    color: #fff7ed;
    line-height: 1.1;
}

/* ── Cards ── */
.card {
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px;
    background: linear-gradient(180deg, rgba(20,16,13,0.95), rgba(12,10,9,0.97));
    padding: 1.25rem;
    overflow: hidden;
    transition: border-color 200ms;
}
.card:hover { border-color: rgba(251,146,60,0.18); }
.card__head {
    font-size: 1rem;
    font-weight: 700;
    color: #e7e5e4;
    margin-bottom: 1rem;
}

/* ── Chart rows ── */
.chart-row {
    display: grid;
    gap: 1rem;
    grid-template-columns: minmax(280px, 0.42fr) minmax(0, 1fr);
    align-items: stretch;
}
.chart-row--bottom {
    grid-template-columns: minmax(0, 1fr) minmax(300px, 0.48fr);
    align-items: start;
}
.side-stack {
    display: grid;
    gap: 1rem;
    align-content: start;
}

/* ── Doughnut ── */
.card--doughnut { display: flex; flex-direction: column; }
.doughnut-wrap {
    position: relative;
    flex: 1;
    min-height: 200px;
    max-height: 280px;
}
.doughnut-center {
    position: absolute;
    inset: 50% auto auto 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    pointer-events: none;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.doughnut-center__ok strong, .doughnut-center__ng strong {
    font-weight: 700;
    color: #fff7ed;
    line-height: 1;
}
.doughnut-center__ok strong { font-size: 1.65rem; }
.doughnut-center__ng strong { font-size: 1.1rem; color: #fca5a5; }
.doughnut-center__ok span, .doughnut-center__ng span {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(231,229,228,0.55);
    margin-left: 0.2rem;
}
.doughnut-legend {
    display: flex;
    gap: 1.25rem;
    justify-content: center;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(255,255,255,0.06);
}
.doughnut-legend__item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    color: #a8a29e;
}
.doughnut-legend__item strong { color: #e7e5e4; font-size: 0.95rem; }
.dot { width: 0.55rem; height: 0.55rem; border-radius: 50%; flex-shrink: 0; }
.dot--ok { background: #f59e0b; }
.dot--ng { background: #ef4444; }
.dot--today { background: #8b5cf6; }

/* ── Chart areas ── */
.chart-area { height: 280px; }
.chart-area--tall { height: 320px; }
.chart-area--short { height: 200px; }
.card--compact { padding: 1rem; }
.card--compact .card__head { margin-bottom: 0.65rem; }
.card--monthly {
    display: grid;
    gap: 1rem;
}

.monthly-insights {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    padding-top: 0.9rem;
    border-top: 1px solid rgba(255,255,255,0.06);
}

.monthly-insight {
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.07);
    background: rgba(255,255,255,0.03);
    padding: 0.85rem;
}

.monthly-insight__label {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
}

.monthly-insight__value {
    margin-top: 0.35rem;
    font-size: 1.2rem;
    font-weight: 700;
    color: #fff7ed;
    line-height: 1.15;
}

.monthly-insight__note {
    margin-top: 0.2rem;
    font-size: 0.8rem;
    color: #a8a29e;
}

/* ── Forecast ── */
.card--forecast { padding: 1.1rem; }
.forecast-grid {
    display: grid;
    gap: 0.6rem;
    grid-template-columns: 1fr 1fr;
}
.fc {
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.07);
    background: rgba(255,255,255,0.03);
    padding: 0.85rem;
}
.fc--accent {
    border-color: rgba(251,146,60,0.2);
    background: rgba(251,146,60,0.06);
}
.fc__label {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
}
.fc--accent .fc__label { color: #fdba74; }
.fc__value {
    margin-top: 0.35rem;
    font-size: 1.35rem;
    font-weight: 700;
    color: #fff7ed;
    line-height: 1.15;
}
.fc__delta {
    margin-top: 0.2rem;
    font-size: 0.78rem;
    font-weight: 600;
}
.fc__delta--up { color: #22c55e; }
.fc__delta--down { color: #ef4444; }
.fc__sub {
    margin-top: 0.2rem;
    font-size: 0.78rem;
    color: #a8a29e;
}

/* ── Shimmer ── */
.shimmer {
    height: 280px;
    border-radius: 16px;
    background: linear-gradient(90deg, rgba(41,37,36,0.9), rgba(68,64,60,0.95), rgba(41,37,36,0.9));
    background-size: 200% 100%;
    animation: shimmer 1.6s linear infinite;
}
.shimmer--tall { height: 320px; }
.shimmer--short { height: 200px; }

@keyframes shimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

/* ── Responsive ── */
@media (max-width: 1023px) {
    .chart-row, .chart-row--bottom { grid-template-columns: 1fr; }
    .kpi-strip { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 639px) {
    .kpi-strip { grid-template-columns: 1fr; }
    .forecast-grid { grid-template-columns: 1fr; }
    .monthly-insights { grid-template-columns: 1fr; }
}
</style>
