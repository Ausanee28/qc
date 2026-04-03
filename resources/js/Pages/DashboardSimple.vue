<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Bar, Doughnut, Line } from '@/lib/dashboard-charts';

const dashboardReloadOnly = ['currentPeriod', 'metrics', 'weeklyData', 'dailyData', 'monthlyData', 'inspectorData', 'flash'];

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
    inspectorData: { type: Array, default: () => [] },
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
const currentTheme = ref('dark');
let themeObserver = null;
const currentPeriodLabel = computed(() => periodLabels[props.currentPeriod] || 'This Month');
const dashboardInvalidateTags = ['dashboard', 'workflow', 'performance', 'report', 'certificates'];

const syncTheme = () => {
    if (typeof document === 'undefined') {
        return;
    }

    currentTheme.value = document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
};

const isLightTheme = computed(() => currentTheme.value === 'light');

watch(() => props.currentPeriod, (v) => { selectedPeriod.value = v; });
watch(selectedPeriod, (v, prev) => {
    if (!v || v === prev) return;
    isChangingPeriod.value = true;
    router.get(route('dashboard'), { period: v }, {
        only: dashboardReloadOnly,
        cacheTags: dashboardInvalidateTags,
        replace: true, preserveState: true, preserveScroll: true,
        onFinish: () => { isChangingPeriod.value = false; },
    });
});

onMounted(() => {
    syncTheme();

    if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
        themeObserver = new MutationObserver(syncTheme);
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }
});

onUnmounted(() => {
    themeObserver?.disconnect();
    themeObserver = null;
});

const fmt = (v) => Number(v || 0).toLocaleString();
const pct = (v) => `${Number(v || 0).toFixed(1)}%`;
const chartTheme = computed(() => (
    isLightTheme.value
        ? {
            axis: '#1f2937',
            legend: '#111827',
            strong: '#101828',
            grid: 'rgba(15,23,42,0.18)',
            tooltipBg: 'rgba(255,255,255,1)',
            tooltipTitle: '#101828',
            tooltipBody: '#1f2937',
            tooltipBorder: 'rgba(15,23,42,0.2)',
            ok: '#1d4ed8',
            okFill: 'rgba(29,78,216,0.22)',
            ng: '#e11d48',
            ngFill: 'rgba(225,29,72,0.16)',
            bar: 'rgba(29,78,216,0.38)',
            axisRight: '#1e40af',
        }
        : {
            axis: '#a8a29e',
            legend: '#e7e5e4',
            strong: '#fafaf9',
            grid: 'rgba(255,255,255,0.06)',
            tooltipBg: 'rgba(10,10,10,0.95)',
            tooltipTitle: '#fafaf9',
            tooltipBody: '#f5f5f4',
            tooltipBorder: 'rgba(251,146,60,0.2)',
            ok: '#22c55e',
            okFill: 'rgba(34,197,94,0.18)',
            ng: '#ef4444',
            ngFill: 'rgba(239,68,68,0.10)',
            bar: 'rgba(16,185,129,0.32)',
            axisRight: '#4ade80',
        }
));

/* โ”€โ”€ KPI cards โ”€โ”€ */
const kpiCards = computed(() => ([
    { label: 'OK %', value: pct(props.metrics.yieldRate), accent: true },
    { label: 'NG %', value: pct(props.metrics.defectRate), accent: false, danger: true },
    { label: 'Jobs', value: fmt(props.metrics.periodJobs) },
    { label: 'Total Tests', value: fmt(props.metrics.totalTests) },
    { label: 'Avg Time', value: `${fmt(props.metrics.avgTestTime)} min`, icon: true },
    { label: 'Pending', value: fmt(props.metrics.pendingCount) },
]));

/* โ”€โ”€ Quality doughnut โ”€โ”€ */
const qualityChartData = computed(() => ({
    labels: ['OK', 'NG'],
    datasets: [{
        data: [Number(props.metrics.okCount || 0), Number(props.metrics.ngCount || 0)],
        backgroundColor: [chartTheme.value.ok, chartTheme.value.ng],
        borderWidth: 0, hoverOffset: 6,
    }],
}));

const doughnutOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false, cutout: '72%',
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: chartTheme.value.tooltipBg,
            titleColor: chartTheme.value.tooltipTitle,
            bodyColor: chartTheme.value.tooltipBody,
            borderColor: chartTheme.value.tooltipBorder,
            borderWidth: 1,
            callbacks: {
                label: (ctx) => {
                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    const p = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : '0.0';
                    return `${ctx.label}: ${ctx.parsed.toLocaleString()} (${p}%)`;
                },
            },
        },
    },
}));

/* โ”€โ”€ Shared chart config โ”€โ”€ */
const tooltipStyle = computed(() => ({
    backgroundColor: chartTheme.value.tooltipBg,
    titleColor: chartTheme.value.tooltipTitle,
    bodyColor: chartTheme.value.tooltipBody,
    borderColor: chartTheme.value.tooltipBorder,
    borderWidth: 1,
}));

const lineOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { labels: { color: chartTheme.value.legend, usePointStyle: true, padding: 14 } },
        tooltip: tooltipStyle.value,
    },
    scales: {
        x: { ticks: { color: chartTheme.value.axis }, grid: { display: false }, border: { display: false } },
        y: { beginAtZero: true, ticks: { color: chartTheme.value.axis }, grid: { color: chartTheme.value.grid }, border: { display: false } },
    },
    elements: { line: { tension: 0.35, borderWidth: 2.5 }, point: { radius: 2, hoverRadius: 5 } },
}));

const barOpts = computed(() => ({
    ...lineOpts.value,
    elements: undefined,
    plugins: { ...lineOpts.value.plugins, legend: { display: false } },
}));

/* โ”€โ”€ Daily trend โ”€โ”€ */
const dailyRows = computed(() => {
    const src = props.dailyData?.length ? props.dailyData : props.weeklyData;
    return src.map((d) => ({ label: d.label, ok: Number(d.ok || 0), ng: Number(d.ng || 0) }));
});

const dailyTrendData = computed(() => ({
    labels: dailyRows.value.map((d) => d.label),
    datasets: [
        { label: 'OK', data: dailyRows.value.map((d) => d.ok), borderColor: chartTheme.value.ok, backgroundColor: chartTheme.value.okFill, fill: true, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 },
        { label: 'NG', data: dailyRows.value.map((d) => d.ng), borderColor: chartTheme.value.ng, backgroundColor: chartTheme.value.ngFill, fill: true, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 },
    ],
}));

/* โ”€โ”€ Monthly trend โ”€โ”€ */
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
        { type: 'bar', label: 'Total Tests', data: monthlySeries.value.map((m) => m.total), backgroundColor: chartTheme.value.bar, borderRadius: 8, yAxisID: 'y' },
        { type: 'line', label: 'OK %', data: monthlySeries.value.map((m) => m.yield), borderColor: chartTheme.value.ok, backgroundColor: chartTheme.value.ok, tension: 0.3, pointRadius: 4, pointHoverRadius: 6, yAxisID: 'y1' },
        { type: 'line', label: 'NG %', data: monthlySeries.value.map((m) => m.ngRate), borderColor: chartTheme.value.ng, backgroundColor: chartTheme.value.ng, tension: 0.3, pointRadius: 4, pointHoverRadius: 6, yAxisID: 'y1' },
    ],
}));

const monthlyMixedOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { labels: { color: chartTheme.value.legend, usePointStyle: true, padding: 14 } },
        tooltip: {
            ...tooltipStyle.value,
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
        x: { ticks: { color: chartTheme.value.axis }, grid: { display: false }, border: { display: false } },
        y: { beginAtZero: true, ticks: { color: chartTheme.value.axis }, grid: { color: chartTheme.value.grid }, border: { display: false } },
        y1: { beginAtZero: true, position: 'right', min: 0, max: 100, ticks: { color: chartTheme.value.axisRight, callback: (v) => `${v}%` }, grid: { display: false }, border: { display: false } },
    },
}));

/* โ”€โ”€ Weekly bar โ”€โ”€ */
const weeklyBarData = computed(() => ({
    labels: (props.weeklyData || []).map((d) => d.label),
    datasets: [
        { label: 'OK', data: (props.weeklyData || []).map((d) => Number(d.ok || 0)), backgroundColor: chartTheme.value.ok, borderRadius: 6 },
        { label: 'NG', data: (props.weeklyData || []).map((d) => Number(d.ng || 0)), backgroundColor: chartTheme.value.ng, borderRadius: 6 },
    ],
}));

const weeklyBarOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: chartTheme.value.legend, usePointStyle: true, pointStyle: 'circle', padding: 12 } }, tooltip: tooltipStyle.value },
    scales: {
        x: { stacked: true, ticks: { color: chartTheme.value.axis }, grid: { display: false }, border: { display: false } },
        y: { stacked: true, beginAtZero: true, ticks: { color: chartTheme.value.axis }, grid: { color: chartTheme.value.grid }, border: { display: false } },
    },
}));

/* โ”€โ”€ Forecast โ”€โ”€ */
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
const topInspectors = computed(() => (props.inspectorData || []).slice(0, 5));
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #title>Dashboard</template>

        <div class="db" :class="{ 'db--loading': isChangingPeriod }">
            <!-- โ•โ•โ• HEADER โ•โ•โ• -->
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

            <!-- โ•โ•โ• KPI STRIP โ•โ•โ• -->
            <section class="kpi-strip">
                <article v-for="card in kpiCards" :key="card.label" class="kpi" :class="{ 'kpi--accent': card.accent, 'kpi--danger': card.danger }">
                    <div class="kpi__label">{{ card.label }}</div>
                    <div class="kpi__value">{{ card.value }}</div>
                </article>
            </section>

            <!-- โ•โ•โ• ROW: Quality Doughnut + Daily Trend โ•โ•โ• -->
            <section class="chart-row">
                <article class="card card--doughnut">
                    <div class="card__head">OK / NG Ratio</div>
                    <div class="doughnut-wrap">
                        <Doughnut :data="qualityChartData" :options="doughnutOpts" />
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
                    <div class="chart-area"><Line :data="dailyTrendData" :options="lineOpts" /></div>
                </article>
            </section>

            <!-- โ•โ•โ• ROW: Monthly Trend + Forecast & Weekly โ•โ•โ• -->
            <section class="chart-row chart-row--bottom">
                <article class="card card--chart card--monthly">
                    <div class="card__head">Monthly Tests, OK % and NG %</div>
                    <div class="chart-area chart-area--tall"><Bar :data="monthlyTrendData" :options="monthlyMixedOpts" /></div>

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
                        <div class="chart-area chart-area--short"><Bar :data="weeklyBarData" :options="weeklyBarOpts" /></div>
                    </article>
                </div>
            </section>

            <!-- โ•โ•โ• ROW: Inspector Leaderboard โ•โ•โ• -->
            <section class="card card--leaderboard">
                <div class="card__head">Inspector Leaderboard</div>
                <div v-if="topInspectors.length" class="lb-list">
                        <div v-for="(ins, idx) in topInspectors" :key="ins.name" class="lb-row">
                            <div class="lb-rank">{{ idx + 1 }}</div>
                            <div class="lb-info">
                                <div class="lb-name">{{ ins.name }}</div>
                                <div class="lb-meta">{{ fmt(ins.total) }} tests ยท {{ fmt(ins.ok) }} OK ยท {{ fmt(ins.ng) }} NG</div>
                            </div>
                            <div class="lb-yield">
                                <div class="lb-yield__value">{{ pct(ins.yield) }}</div>
                                <div class="lb-yield__label">OK %</div>
                            </div>
                            <div class="lb-bar">
                                <div class="lb-bar__fill" :style="{ width: `${ins.yield}%` }"></div>
                            </div>
                        </div>
                </div>
                <div v-else class="lb-empty">No inspector data for this period.</div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* โ”€โ”€ Base โ”€โ”€ */
.db {
    display: grid;
    gap: 1.25rem;
    transition: opacity 160ms ease;
}
.db--loading { opacity: 0.96; pointer-events: auto; }

/* โ”€โ”€ Header โ”€โ”€ */
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

/* โ”€โ”€ KPI Strip โ”€โ”€ */
.kpi-strip {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(6, 1fr);
}
.kpi {
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(20,16,13,0.95), rgba(12,10,9,0.97));
    padding: 1.1rem 1.25rem;
    transition: border-color 200ms, transform 200ms;
}
.kpi:hover { border-color: rgba(251,146,60,0.2); transform: translateY(-2px); }
.kpi--accent { border-color: rgba(34,197,94,0.3); background: linear-gradient(135deg, rgba(14,42,26,0.78), rgba(9,22,15,0.95)); }
.kpi__label {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.55);
}
.kpi--accent .kpi__label { color: #86efac; }
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

/* โ”€โ”€ Cards โ”€โ”€ */
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

.card,
.kpi {
    position: relative;
}

/* โ”€โ”€ Chart rows โ”€โ”€ */
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

/* โ”€โ”€ Doughnut โ”€โ”€ */
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
.dot--ok { background: #22c55e; }
.dot--ng { background: #ef4444; }
.dot--today { background: #8b5cf6; }

/* โ”€โ”€ Chart areas โ”€โ”€ */
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

/* โ”€โ”€ Forecast โ”€โ”€ */
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

/* โ”€โ”€ Shimmer โ”€โ”€ */
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

/* โ”€โ”€ Leaderboard โ”€โ”€ */
.card--leaderboard { padding: 1.25rem; }
.lb-list { display: grid; gap: 0.5rem; }
.lb-row {
    display: grid;
    grid-template-columns: 2.2rem 1fr auto 120px;
    gap: 0.85rem;
    align-items: center;
    padding: 0.75rem 0.85rem;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.02);
    transition: border-color 180ms, background 180ms;
}
.lb-row:hover {
    border-color: rgba(251,146,60,0.18);
    background: rgba(255,255,255,0.04);
}
.lb-rank {
    width: 2.2rem;
    height: 2.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: rgba(251,146,60,0.12);
    font-size: 0.85rem;
    font-weight: 700;
    color: #fdba74;
}
.lb-row:nth-child(1) .lb-rank { background: rgba(245,158,11,0.25); color: #f59e0b; }
.lb-row:nth-child(2) .lb-rank { background: rgba(251,146,60,0.18); color: #fb923c; }
.lb-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #f5f5f4;
}
.lb-meta {
    margin-top: 0.15rem;
    font-size: 0.78rem;
    color: #a8a29e;
}
.lb-yield { text-align: right; }
.lb-yield__value {
    font-size: 1.15rem;
    font-weight: 700;
    color: #4ade80;
    line-height: 1;
}
.lb-yield__label {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.4);
    margin-top: 0.15rem;
}
.lb-bar {
    height: 6px;
    border-radius: 999px;
    background: rgba(255,255,255,0.06);
    overflow: hidden;
}
.lb-bar__fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #22c55e, #16a34a);
    transition: width 400ms ease;
}
.lb-empty {
    text-align: center;
    padding: 2rem 1rem;
    color: #78716c;
    font-size: 0.9rem;
}

/* โ”€โ”€ Responsive โ”€โ”€ */
@media (max-width: 1023px) {
    .chart-row, .chart-row--bottom { grid-template-columns: 1fr; }
    .kpi-strip { grid-template-columns: repeat(3, 1fr); }
    .lb-row { grid-template-columns: 2.2rem 1fr auto; }
    .lb-bar { display: none; }
}

:global(.theme-shell[data-theme='light'] .db-header__title),
:global(.theme-shell[data-theme='light'] .card__head),
:global(.theme-shell[data-theme='light'] .kpi__value),
:global(.theme-shell[data-theme='light'] .monthly-insight__value),
:global(.theme-shell[data-theme='light'] .fc__value),
:global(.theme-shell[data-theme='light'] .lb-name) {
    color: #0f172a;
    text-shadow: none;
}

:global(.theme-shell[data-theme='light'] .db) {
    gap: 1.1rem;
}

:global(.theme-shell[data-theme='light'] .db-header) {
    padding: 0.15rem 0.1rem 0.35rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.1);
}

:global(.theme-shell[data-theme='light'] .db-badge) {
    background: rgba(219, 234, 254, 0.98);
    border: 1px solid rgba(29, 78, 216, 0.18);
    color: #1e40af;
    box-shadow: 0 10px 22px rgba(29, 78, 216, 0.08);
}

:global(.theme-shell[data-theme='light'] .db-badge__dot) {
    background: #1d4ed8;
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.16);
}

:global(.theme-shell[data-theme='light'] .db-period select),
:global(.theme-shell[data-theme='light'] .kpi),
:global(.theme-shell[data-theme='light'] .kpi--accent),
:global(.theme-shell[data-theme='light'] .kpi--danger),
:global(.theme-shell[data-theme='light'] .card),
:global(.theme-shell[data-theme='light'] .monthly-insight),
:global(.theme-shell[data-theme='light'] .fc),
:global(.theme-shell[data-theme='light'] .lb-row) {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.995), rgba(243, 248, 255, 0.98)) !important;
    border-color: rgba(15, 23, 42, 0.24) !important;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.1) !important;
}

:global(.theme-shell[data-theme='light'] .kpi::before),
:global(.theme-shell[data-theme='light'] .card::before) {
    content: none;
}

:global(.theme-shell[data-theme='light'] .kpi:hover),
:global(.theme-shell[data-theme='light'] .card:hover),
:global(.theme-shell[data-theme='light'] .lb-row:hover) {
    border-color: rgba(29, 78, 216, 0.42) !important;
    background: linear-gradient(180deg, rgba(255, 255, 255, 1), rgba(231, 240, 252, 0.99)) !important;
    box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12) !important;
}

:global(.theme-shell[data-theme='light'] .db-period select) {
    color: #0f172a;
    border-color: rgba(15, 23, 42, 0.2) !important;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
    background: #ffffff !important;
}

:global(.theme-shell[data-theme='light'] .kpi__label),
:global(.theme-shell[data-theme='light'] .monthly-insight__label),
:global(.theme-shell[data-theme='light'] .fc__label),
:global(.theme-shell[data-theme='light'] .lb-yield__label) {
    color: #334155;
}

:global(.theme-shell[data-theme='light'] .monthly-insight__note),
:global(.theme-shell[data-theme='light'] .fc__sub),
:global(.theme-shell[data-theme='light'] .lb-meta),
:global(.theme-shell[data-theme='light'] .lb-empty) {
    color: #334155;
}

:global(.theme-shell[data-theme='light'] .doughnut-legend) {
    border-top-color: rgba(15, 23, 42, 0.16);
}

:global(.theme-shell[data-theme='light'] .doughnut-legend__item),
:global(.theme-shell[data-theme='light'] .doughnut-center__ok span),
:global(.theme-shell[data-theme='light'] .doughnut-center__ng span) {
    color: #334155;
}

:global(.theme-shell[data-theme='light'] .doughnut-legend__item strong),
:global(.theme-shell[data-theme='light'] .doughnut-center__ok strong),
:global(.theme-shell[data-theme='light'] .doughnut-center__ng strong) {
    color: #0f172a;
}

:global(.theme-shell[data-theme='light'] .lb-bar) {
    background: rgba(148, 163, 184, 0.28);
}

:global(.theme-shell[data-theme='light'] .kpi--accent .kpi__label),
:global(.theme-shell[data-theme='light'] .fc--accent .fc__label) {
    color: #1e40af;
}

:global(.theme-shell[data-theme='light'] .kpi--accent) {
    border-color: rgba(29, 78, 216, 0.42) !important;
    background: linear-gradient(135deg, rgba(219, 234, 254, 0.98), rgba(239, 246, 255, 0.98)) !important;
}

:global(.theme-shell[data-theme='light'] .kpi--danger) {
    border-color: rgba(225, 29, 72, 0.4) !important;
    background: linear-gradient(135deg, rgba(255, 241, 242, 0.98), rgba(255, 248, 250, 0.98)) !important;
}

:global(.theme-shell[data-theme='light'] .fc--accent) {
    border-color: rgba(29, 78, 216, 0.24) !important;
    background: linear-gradient(180deg, rgba(219, 234, 254, 0.94), rgba(239, 246, 255, 0.98)) !important;
}

:global(.theme-shell[data-theme='light'] .lb-rank) {
    background: rgba(219, 234, 254, 0.95);
    color: #1d4ed8;
}

:global(.theme-shell[data-theme='light'] .lb-row:nth-child(1) .lb-rank) {
    background: rgba(191, 219, 254, 0.98);
    color: #1e3a8a;
}

:global(.theme-shell[data-theme='light'] .lb-row:nth-child(2) .lb-rank) {
    background: rgba(219, 234, 254, 0.98);
    color: #1d4ed8;
}

:global(.theme-shell[data-theme='light'] .lb-yield__value) {
    color: #1e40af;
}

:global(.theme-shell[data-theme='light'] .lb-bar__fill) {
    background: linear-gradient(90deg, #1d4ed8, #60a5fa);
}

:global(.theme-shell[data-theme='light'] .card__head) {
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.01em;
}

:global(.theme-shell[data-theme='light'] .kpi__value) {
    font-size: 2rem;
}

:global(.theme-shell[data-theme='light'] .doughnut-wrap),
:global(.theme-shell[data-theme='light'] .chart-area) {
    border-radius: 16px;
}

:global(.theme-shell[data-theme='light'] .dot--ok) {
    background: #1d4ed8;
}

:global(.theme-shell[data-theme='light'] .dot--ng) {
    background: #e11d48;
}

:global(.theme-shell[data-theme='light'] .dot--today) {
    background: #8b5cf6;
}

:global(.theme-shell[data-theme='light'] .kpi--danger .kpi__label),
:global(.theme-shell[data-theme='light'] .kpi--danger .kpi__value),
:global(.theme-shell[data-theme='light'] .doughnut-center__ng strong) {
    color: #be123c;
}

@media (max-width: 639px) {
    .kpi-strip { grid-template-columns: repeat(2, 1fr); }
    .forecast-grid { grid-template-columns: 1fr; }
    .monthly-insights { grid-template-columns: 1fr; }
    .lb-row { grid-template-columns: 2rem 1fr; gap: 0.5rem; }
    .lb-yield { display: none; }
}
</style>
