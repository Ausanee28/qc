<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { getEcho } from '@/lib/realtime';

const chartFontFamily = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";

const showPrimaryCharts = ref(false);
const showHeavySections = ref(false);
const showTrendArchive = ref(false);
const trendArchiveSentinel = ref(null);
let realtimeRefreshTimer = null;
let dashboardEcho = null;
let realtimeBootTimer = null;
let trendArchiveFallbackTimer = null;
let trendArchiveObserver = null;
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
    equipRank: { type: Array, default: () => [] },
    failByEquip: { type: Array, default: () => [] },
    inspectorEff: { type: Array, default: () => [] },
    recentActivities: { type: Array, default: () => [] },
    inspectorData: { type: Array, default: () => [] },
});

const selectedPeriod = ref(props.currentPeriod);
const isLoading = ref(false);

const dashboardPayloadKeys = [
    'currentPeriod',
    'metrics',
    'weeklyData',
    'dailyData',
    'monthlyData',
    'equipRank',
    'failByEquip',
    'inspectorEff',
    'recentActivities',
    'inspectorData',
];

const periodOptions = [
    { value: 'today', label: 'Today' },
    { value: 'month', label: 'This Month' },
    { value: 'week', label: 'Last 7 Days' },
    { value: '30days', label: 'Last 30 Days' },
    { value: 'quarter', label: 'This Quarter' },
];

const periodLabels = {
    today: 'Today',
    month: 'This Month',
    week: 'Last 7 Days',
    '30days': 'Last 30 Days',
    quarter: 'This Quarter',
};

watch(selectedPeriod, (val) => {
    isLoading.value = true;
    router.get(route('dashboard'), { period: val }, {
        only: dashboardPayloadKeys,
        replace: true,
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
});

const reloadDashboard = () => {
    if (isLoading.value) {
        return;
    }

    isLoading.value = true;
    router.reload({
        only: dashboardPayloadKeys,
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const scheduleRealtimeReload = () => {
    if (realtimeRefreshTimer !== null) {
        return;
    }

    realtimeRefreshTimer = window.setTimeout(() => {
        realtimeRefreshTimer = null;
        reloadDashboard();
    }, 250);
};

onMounted(() => {
    const revealPrimaryCharts = () => {
        showPrimaryCharts.value = true;
    };

    const revealHeavySections = () => {
        showHeavySections.value = true;
    };

    const revealTrendArchive = () => {
        showTrendArchive.value = true;

        if (trendArchiveFallbackTimer !== null) {
            window.clearTimeout(trendArchiveFallbackTimer);
            trendArchiveFallbackTimer = null;
        }

        if (trendArchiveObserver) {
            trendArchiveObserver.disconnect();
            trendArchiveObserver = null;
        }
    };

    const setupTrendArchiveReveal = () => {
        if (showTrendArchive.value) {
            return;
        }

        const compactViewport = window.matchMedia('(max-width: 767px)').matches;

        if (typeof window.IntersectionObserver === 'function' && trendArchiveSentinel.value) {
            trendArchiveObserver = new window.IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) {
                    return;
                }

                revealTrendArchive();
            }, {
                rootMargin: compactViewport ? '320px 0px' : '220px 0px',
                threshold: 0.01,
            });

            trendArchiveObserver.observe(trendArchiveSentinel.value);
        }

        trendArchiveFallbackTimer = window.setTimeout(revealTrendArchive, compactViewport ? 2600 : 1900);
    };

    const bootRealtime = async () => {
        dashboardEcho = await getEcho();

        if (!dashboardEcho) {
            return;
        }

        dashboardEcho.private('dashboard.global').listen('.dashboard.updated', scheduleRealtimeReload);
    };

    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(() => revealPrimaryCharts(), { timeout: 500 });
        window.requestIdleCallback(() => revealHeavySections(), { timeout: 1000 });
        window.requestIdleCallback(() => setupTrendArchiveReveal(), { timeout: 1400 });
        window.requestIdleCallback(() => bootRealtime(), { timeout: 1800 });
    } else {
        window.setTimeout(revealPrimaryCharts, 150);
        window.setTimeout(revealHeavySections, 500);
        window.setTimeout(setupTrendArchiveReveal, 900);
        realtimeBootTimer = window.setTimeout(() => {
            realtimeBootTimer = null;
            bootRealtime();
        }, 900);
    }
});

onBeforeUnmount(() => {
    if (realtimeRefreshTimer !== null) {
        window.clearTimeout(realtimeRefreshTimer);
    }

    if (realtimeBootTimer !== null) {
        window.clearTimeout(realtimeBootTimer);
        realtimeBootTimer = null;
    }

    if (trendArchiveFallbackTimer !== null) {
        window.clearTimeout(trendArchiveFallbackTimer);
        trendArchiveFallbackTimer = null;
    }

    if (trendArchiveObserver) {
        trendArchiveObserver.disconnect();
        trendArchiveObserver = null;
    }

    if (dashboardEcho) {
        dashboardEcho.leave('dashboard.global');
        dashboardEcho = null;
    }
});

const formatNumber = (value) => Number(value || 0).toLocaleString();
const formatPercent = (value) => `${Number(value || 0).toFixed(1)}%`;

const totalTests = computed(() => Number(props.metrics.totalTests || 0));
const yieldPct = computed(() => Number(props.metrics.yieldRate || 0));
const defectPct = computed(() => Number(props.metrics.defectRate || 0));
const todayTotal = computed(() => Number(props.metrics.todayOK || 0) + Number(props.metrics.todayNG || 0));
const todayYield = computed(() => todayTotal.value > 0 ? Number(((props.metrics.todayOK / todayTotal.value) * 100).toFixed(1)) : 0);
const monthlyTotalOK = computed(() => props.monthlyData.reduce((sum, month) => sum + Number(month.ok || 0), 0));
const monthlyTotalNG = computed(() => props.monthlyData.reduce((sum, month) => sum + Number(month.ng || 0), 0));
const selectedPeriodLabel = computed(() => periodLabels[selectedPeriod.value] || 'This Month');
const leadEquipment = computed(() => props.equipRank?.[0] ?? null);
const leadFailure = computed(() => props.failByEquip?.[0] ?? null);
const leadInspector = computed(() => props.inspectorData?.[0] ?? null);

const healthSummary = computed(() => {
    if (!totalTests.value) {
        return {
            title: 'Waiting for inspection data',
            text: 'Once results are recorded, the dashboard will surface quality and workload signals here.',
            tone: 'ink',
        };
    }

    if (yieldPct.value >= 95 && Number(props.metrics.pendingCount || 0) <= 5) {
        return {
            title: 'Quality is stable and queue pressure is low',
            text: 'Yield is above target while pending work remains manageable for the team.',
            tone: 'orange',
        };
    }

    if (yieldPct.value >= 92) {
        return {
            title: 'Operations look healthy with a few areas to watch',
            text: 'Keep an eye on the queue and on any equipment causing NG clusters.',
            tone: 'amber',
        };
    }

    return {
        title: 'Quality needs attention this period',
            text: 'Yield has slipped below target, so failure drivers and workload distribution deserve a closer look.',
            tone: 'ember',
    };
});

const overviewStats = computed(() => ([
    { label: 'Total inspections', value: formatNumber(totalTests.value), note: `${formatNumber(props.metrics.todayCount)} logged today`, tone: 'ink' },
    { label: 'Pass yield', value: formatPercent(yieldPct.value), note: `${formatNumber(props.metrics.okCount)} OK records`, tone: 'orange' },
    { label: 'Defect rate', value: formatPercent(defectPct.value), note: `${formatNumber(props.metrics.ngCount)} NG records`, tone: 'ember' },
    { label: 'Pending jobs', value: formatNumber(props.metrics.pendingCount), note: `${props.metrics.avgTestTime || 0} min avg test time`, tone: 'amber' },
]));
const topInspectors = computed(() => (props.inspectorData || []).slice(0, 5));
const recentActivityPreview = computed(() => props.recentActivities.slice(0, 5));
const signalCards = computed(() => ([
    {
        label: 'Top equipment load',
        value: leadEquipment.value ? leadEquipment.value.name : 'No data yet',
        note: leadEquipment.value ? `${formatNumber(leadEquipment.value.count)} tests` : 'Waiting for ranked usage data',
    },
    {
        label: 'Failure hotspot',
        value: leadFailure.value ? leadFailure.value.name : 'No failures recorded',
        note: leadFailure.value ? `${formatNumber(leadFailure.value.count)} NG results` : 'No NG trend available for this period',
    },
    {
        label: 'Lead inspector',
        value: leadInspector.value ? leadInspector.value.name : 'No ranking available',
        note: leadInspector.value ? `${formatPercent(leadInspector.value.yield)} yield across ${formatNumber(leadInspector.value.total)} tests` : 'Inspector leaderboard will appear once records are available',
    },
]));
const heroChecklist = computed(() => ([
    {
        label: 'Queue pressure',
        value: Number(props.metrics.pendingCount || 0) <= 5 ? 'In range' : 'Needs watch',
        note: `${formatNumber(props.metrics.pendingCount)} jobs still open`,
    },
    {
        label: 'Today cadence',
        value: formatNumber(todayTotal.value),
        note: `${formatPercent(todayYield.value)} yield for today's output`,
    },
    {
        label: 'Window focus',
        value: selectedPeriodLabel.value,
        note: `${props.metrics.avgTestTime || 0} min average test time`,
    },
]));

const monthlyHighlights = computed(() => {
    const withData = props.monthlyData.filter((month) => Number(month.total || 0) > 0);

    if (!withData.length) {
        return [
            { label: 'Best month', value: 'No data', note: 'Monthly trend cards will populate once records exist.' },
            { label: 'Worst month', value: 'No data', note: 'No monthly comparison available yet.' },
            { label: 'Average yield', value: '0.0%', note: 'The six-month average updates automatically.' },
        ];
    }

    const bestMonth = withData.reduce((best, month) => (Number(month.yield) > Number(best.yield) ? month : best), withData[0]);
    const worstMonth = withData.reduce((worst, month) => (Number(month.yield) < Number(worst.yield) ? month : worst), withData[0]);
    const averageYield = (withData.reduce((sum, month) => sum + Number(month.yield || 0), 0) / withData.length).toFixed(1);

    return [
        { label: 'Best month', value: bestMonth.label, note: `${formatPercent(bestMonth.yield)} yield` },
        { label: 'Worst month', value: worstMonth.label, note: `${formatPercent(worstMonth.yield)} yield` },
        { label: 'Average yield', value: `${averageYield}%`, note: 'Across the last six reported months' },
    ];
});

const dailyLineData = computed(() => ({
    labels: props.dailyData.map((day) => day.label),
    datasets: [
        { label: 'OK', data: props.dailyData.map((day) => day.ok), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.16)', fill: true, pointBackgroundColor: '#f59e0b', pointBorderColor: '#ffffff', pointBorderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
        { label: 'NG', data: props.dailyData.map((day) => day.ng), borderColor: '#fb923c', backgroundColor: 'rgba(251,146,60,0.06)', fill: false, pointBackgroundColor: '#fb923c', pointBorderColor: '#ffffff', pointBorderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
    ],
}));

const weeklyChartData = computed(() => ({
    labels: props.weeklyData.map((day) => day.label),
    datasets: [
        { label: 'OK', data: props.weeklyData.map((day) => day.ok), backgroundColor: '#f59e0b', borderRadius: 10, borderSkipped: false, maxBarThickness: 24 },
        { label: 'NG', data: props.weeklyData.map((day) => day.ng), backgroundColor: '#9a3412', borderRadius: 10, borderSkipped: false, maxBarThickness: 24 },
    ],
}));

const equipUsageData = computed(() => ({
    labels: props.equipRank?.length ? props.equipRank.map((item) => item.name) : ['No data'],
    datasets: [{ label: 'Tests', data: props.equipRank?.length ? props.equipRank.map((item) => item.count) : [0], backgroundColor: ['#f59e0b', '#fb923c', '#fbbf24', '#78350f', '#292524'], borderRadius: 10, borderSkipped: false }],
}));

const failDoughnutData = computed(() => ({
    labels: props.failByEquip?.length ? props.failByEquip.map((item) => item.name) : ['No data'],
    datasets: [{ data: props.failByEquip?.length ? props.failByEquip.map((item) => item.count) : [1], backgroundColor: ['#f97316', '#f59e0b', '#fb923c', '#c2410c', '#292524'], borderWidth: 0, hoverOffset: 6 }],
}));

const inspectorEffData = computed(() => ({
    labels: props.inspectorEff?.length ? props.inspectorEff.map((item) => item.name) : ['No data'],
    datasets: [{ label: 'Avg (min)', data: props.inspectorEff?.length ? props.inspectorEff.map((item) => item.avgMinutes) : [0], backgroundColor: ['#f59e0b', '#fb923c', '#fbbf24', '#a16207', '#292524'], borderRadius: 10, borderSkipped: false }],
}));

const monthlyLineData = computed(() => ({
    labels: props.monthlyData.map((month) => month.label),
    datasets: [
        { label: 'OK', data: props.monthlyData.map((month) => month.ok), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.16)', fill: true, pointBackgroundColor: '#f59e0b', pointBorderColor: '#ffffff', pointBorderWidth: 2 },
        { label: 'NG', data: props.monthlyData.map((month) => month.ng), borderColor: '#fb923c', backgroundColor: 'transparent', fill: false, pointBackgroundColor: '#fb923c', pointBorderColor: '#ffffff', pointBorderWidth: 2 },
    ],
}));

const sharedCartesianScale = {
    x: { ticks: { color: '#a8a29e', font: { size: 11, family: chartFontFamily } }, grid: { display: false }, border: { display: false } },
    y: { beginAtZero: true, ticks: { color: '#78716c', font: { size: 10, family: chartFontFamily } }, grid: { color: 'rgba(245,158,11,0.12)' }, border: { display: false } },
};

const tooltipOpts = { backgroundColor: '#120c08', padding: 12, titleFont: { family: chartFontFamily, size: 12, weight: '700' }, bodyFont: { family: chartFontFamily, size: 11 } };

const barOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { usePointStyle: true, pointStyle: 'circle', color: '#d6d3d1', font: { family: chartFontFamily, size: 11, weight: '600' }, padding: 18 } }, tooltip: tooltipOpts }, scales: sharedCartesianScale };
const horizontalBarOpts = { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tooltipOpts }, scales: { x: sharedCartesianScale.y, y: { ticks: { color: '#e7e5e4', font: { size: 11, family: chartFontFamily, weight: '600' } }, grid: { display: false }, border: { display: false } } } };
const doughnutOpts = { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', color: '#d6d3d1', font: { family: chartFontFamily, size: 10, weight: '600' }, padding: 16 } }, tooltip: tooltipOpts } };
const lineOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tooltipOpts }, scales: sharedCartesianScale, elements: { line: { tension: 0.36, borderWidth: 2.5 }, point: { radius: 0, hoverRadius: 5 } }, interaction: { mode: 'index', intersect: false } };
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #title>Dashboard</template>

        <div class="space-y-8">
            <section class="dash-hero reveal-section p-6 sm:p-8">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_340px]">
                    <div class="space-y-6">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-3xl">
                                <div class="dash-kicker">Operations Dashboard</div>
                                <h1 class="dash-heading mt-2">Quality control at a glance</h1>
                                <p class="mt-3 text-sm leading-7 text-stone-300/80 sm:text-base">
                                    A cleaner snapshot of throughput, yield, queue pressure, and team performance for {{ selectedPeriodLabel.toLowerCase() }}.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div class="inline-flex items-center gap-3 rounded-full border border-white/10 bg-black/35 px-4 py-2 text-sm font-semibold text-stone-100 backdrop-blur">
                                    <span class="h-2.5 w-2.5 rounded-full bg-orange-400 shadow-[0_0_0_6px_rgba(251,146,60,0.18)]"></span>
                                    {{ isLoading ? 'Refreshing data' : 'Live overview' }}
                                </div>
                                <label class="inline-flex items-center gap-3 rounded-full border border-white/10 bg-black/35 px-4 py-2 text-sm font-semibold text-stone-100 backdrop-blur">
                                    <span class="text-stone-400">Window</span>
                                    <select v-model="selectedPeriod" class="bg-transparent text-stone-100 outline-none">
                                        <option v-for="option in periodOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                    </select>
                                </label>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <article v-for="stat in overviewStats" :key="stat.label" class="dash-stat rounded-[22px] border border-white/10 bg-[#18120e]/88 p-5 shadow-[0_12px_30px_rgba(0,0,0,0.25)]" :data-tone="stat.tone">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">{{ stat.label }}</div>
                                <div class="mt-3 text-3xl font-semibold tracking-tight text-stone-50">{{ stat.value }}</div>
                                <div class="mt-2 text-sm leading-6 text-stone-400">{{ stat.note }}</div>
                            </article>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(280px,0.8fr)]">
                            <article class="rounded-[24px] border border-white/10 p-6 text-white shadow-[0_20px_50px_rgba(15,23,42,0.18)]" :class="{
                                'bg-[linear-gradient(160deg,#0a0a0a,#1c1917)]': healthSummary.tone === 'ink',
                                'bg-[linear-gradient(160deg,#1c0f05,#9a3412)]': healthSummary.tone === 'orange',
                                'bg-[linear-gradient(160deg,#20120a,#b45309)]': healthSummary.tone === 'amber',
                                'bg-[linear-gradient(160deg,#130d08,#7c2d12)]': healthSummary.tone === 'ember'
                            }">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/60">Operational pulse</div>
                                <h2 class="mt-3 text-2xl font-semibold tracking-tight">{{ healthSummary.title }}</h2>
                                <p class="mt-3 text-sm leading-7 text-white/72">{{ healthSummary.text }}</p>
                                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                                        <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/55">Today</div>
                                        <div class="mt-2 text-2xl font-semibold tracking-tight">{{ formatNumber(todayTotal) }}</div>
                                        <div class="mt-2 text-sm text-white/70">inspections completed</div>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                                        <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/55">Yield</div>
                                        <div class="mt-2 text-2xl font-semibold tracking-tight">{{ formatPercent(todayYield) }}</div>
                                        <div class="mt-2 text-sm text-white/70">for today's output</div>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                                        <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/55">Tests / Job</div>
                                        <div class="mt-2 text-2xl font-semibold tracking-tight">{{ props.metrics.testsPerJob || 0 }}</div>
                                        <div class="mt-2 text-sm text-white/70">average workload depth</div>
                                    </div>
                                </div>
                            </article>

                            <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                                <div class="dash-kicker">Launchpad</div>
                                <div class="mt-3">
                                    <h2 class="text-2xl font-semibold tracking-tight text-stone-50">Start with the queue and today's pulse</h2>
                                    <p class="mt-3 text-sm leading-7 text-stone-300/80">
                                        The first view stays focused on throughput, queue health, and the current reporting window. Deeper drivers load below once the page settles.
                                    </p>
                                </div>
                                <div class="mt-5 space-y-4">
                                    <div v-for="item in heroChecklist" :key="item.label" class="rounded-2xl border border-white/10 bg-black/25 p-4">
                                        <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">{{ item.label }}</div>
                                        <div class="mt-2 text-base font-semibold tracking-tight text-stone-50">{{ item.value }}</div>
                                        <div class="mt-2 text-sm leading-6 text-stone-400">{{ item.note }}</div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                    <aside class="space-y-4">
                        <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                            <div class="dash-kicker">Period snapshot</div>
                            <div class="mt-4 flex items-end justify-between gap-4">
                                <div>
                                    <div class="text-sm font-medium text-stone-400">{{ selectedPeriodLabel }}</div>
                                    <div class="mt-1 text-4xl font-semibold tracking-tight text-stone-50">{{ formatNumber(totalTests) }}</div>
                                </div>
                                <div class="rounded-full border border-orange-500/20 bg-orange-500/10 px-3 py-1 text-xs font-semibold text-orange-200">{{ formatPercent(yieldPct) }} yield</div>
                            </div>
                            <div class="mt-5 space-y-3">
                                <div>
                                    <div class="mb-2 flex items-center justify-between text-xs font-semibold text-stone-400">
                                        <span>Quality mix</span>
                                        <span>{{ formatPercent(defectPct) }} defect</span>
                                    </div>
                                    <div class="flex h-3 overflow-hidden rounded-full bg-white/10">
                                        <div class="bg-gradient-to-r from-orange-500 to-amber-300" :style="{ width: `${yieldPct}%` }"></div>
                                        <div class="bg-gradient-to-r from-[#5b2d12] to-[#9a3412]" :style="{ width: `${defectPct}%` }"></div>
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-white/10 bg-black/25 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">Pass</div><div class="mt-2 text-xl font-semibold tracking-tight text-stone-50">{{ formatNumber(props.metrics.okCount) }}</div></div>
                                    <div class="rounded-2xl border border-white/10 bg-black/25 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">Fail</div><div class="mt-2 text-xl font-semibold tracking-tight text-stone-50">{{ formatNumber(props.metrics.ngCount) }}</div></div>
                                    <div class="rounded-2xl border border-white/10 bg-black/25 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">Pending</div><div class="mt-2 text-xl font-semibold tracking-tight text-stone-50">{{ formatNumber(props.metrics.pendingCount) }}</div></div>
                                    <div class="rounded-2xl border border-white/10 bg-black/25 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">Avg time</div><div class="mt-2 text-xl font-semibold tracking-tight text-stone-50">{{ props.metrics.avgTestTime || 0 }} min</div></div>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[24px] border border-orange-500/15 bg-[linear-gradient(160deg,#090909,#22140a)] p-6 text-white shadow-[0_24px_80px_rgba(0,0,0,0.32)]">
                            <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-300/70">Quick actions</div>
                            <div class="mt-4 space-y-3">
                                <Link :href="route('receive-job.create')" class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:-translate-y-0.5 hover:bg-white/10">
                                    <div><div class="text-base font-semibold tracking-tight">Receive new job</div><div class="mt-1 text-sm text-white/70">Open intake and register incoming work.</div></div>
                                    <span class="text-white/60">></span>
                                </Link>
                                <Link :href="route('execute-test.create')" class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:-translate-y-0.5 hover:bg-white/10">
                                    <div><div class="text-base font-semibold tracking-tight">Record test result</div><div class="mt-1 text-sm text-white/70">Jump straight to active inspections.</div></div>
                                    <span class="text-white/60">></span>
                                </Link>
                                <Link :href="route('report.index')" class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:-translate-y-0.5 hover:bg-white/10">
                                    <div><div class="text-base font-semibold tracking-tight">Open reporting</div><div class="mt-1 text-sm text-white/70">Review history and export filtered reports.</div></div>
                                    <span class="text-white/60">></span>
                                </Link>
                            </div>
                        </article>
                    </aside>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.95fr)] reveal-section">
                <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <div class="dash-kicker">Quality overview</div>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Weekly pass vs fail movement</h2>
                            <p class="mt-3 text-sm leading-7 text-stone-300/80">A fast read on how the lab has performed across the last seven days.</p>
                        </div>
                        <div class="rounded-full border border-white/10 bg-black/25 px-3 py-1 text-xs font-semibold text-orange-200">{{ selectedPeriodLabel }}</div>
                    </div>
                    <div class="mt-6 h-[320px]">
                        <BarChart v-if="showPrimaryCharts" :data="weeklyChartData" :options="barOpts" />
                        <div v-else class="dash-skeleton h-full"></div>
                    </div>
                </article>

                <div class="grid gap-6">
                    <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                        <div class="dash-kicker">Result split</div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-orange-500/20 bg-orange-500/10 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-orange-200/80">OK records</div><div class="mt-2 text-2xl font-semibold tracking-tight text-orange-100">{{ formatNumber(props.metrics.okCount) }}</div><div class="mt-2 text-sm text-orange-100/70">{{ formatPercent(yieldPct) }} of total results</div></div>
                            <div class="rounded-2xl border border-white/10 bg-black/25 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">NG records</div><div class="mt-2 text-2xl font-semibold tracking-tight text-stone-100">{{ formatNumber(props.metrics.ngCount) }}</div><div class="mt-2 text-sm text-stone-400">{{ formatPercent(defectPct) }} of total results</div></div>
                        </div>
                    </article>

                    <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                        <div class="dash-kicker">Today</div>
                        <div class="mt-3 flex items-end justify-between gap-4">
                            <div><div class="text-4xl font-semibold tracking-tight text-stone-50">{{ formatNumber(todayTotal) }}</div><div class="mt-1 text-sm text-stone-400">completed inspections</div></div>
                            <div class="text-right"><div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Yield</div><div class="mt-1 text-2xl font-semibold text-orange-300">{{ formatPercent(todayYield) }}</div></div>
                        </div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-black/25 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">OK today</div><div class="mt-2 text-xl font-semibold tracking-tight text-stone-50">{{ formatNumber(props.metrics.todayOK) }}</div></div>
                            <div class="rounded-2xl border border-white/10 bg-black/25 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">NG today</div><div class="mt-2 text-xl font-semibold tracking-tight text-stone-50">{{ formatNumber(props.metrics.todayNG) }}</div></div>
                        </div>
                    </article>
                </div>
            </section>

            <template v-if="showHeavySections">
                <div ref="trendArchiveSentinel" class="h-px w-full"></div>

                <section class="space-y-4 reveal-section">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div><div class="dash-kicker">Performance drivers</div><h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">What is shaping the lab right now</h2></div>
                        <p class="max-w-2xl text-sm leading-7 text-stone-300/80 lg:text-right">Equipment usage, failure concentration, and inspector efficiency for {{ selectedPeriodLabel.toLowerCase() }}.</p>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-3">
                        <article v-for="item in signalCards" :key="item.label" class="dash-panel rounded-[22px] border border-white/10 bg-[#16110d]/92 p-5 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                            <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">{{ item.label }}</div>
                            <div class="mt-3 text-lg font-semibold tracking-tight text-stone-50">{{ item.value }}</div>
                            <div class="mt-2 text-sm leading-6 text-stone-400">{{ item.note }}</div>
                        </article>
                    </div>
                    <div class="grid gap-6 xl:grid-cols-3">
                        <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]"><div class="text-xl font-semibold tracking-tight text-stone-50">Top equipment used</div><p class="mt-2 text-sm leading-7 text-stone-300/80">Usage volume by equipment across the active reporting window.</p><div class="mt-6 h-[260px]"><BarChart :data="equipUsageData" :options="horizontalBarOpts" /></div></article>
                        <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]"><div class="text-xl font-semibold tracking-tight text-stone-50">Failure by equipment</div><p class="mt-2 text-sm leading-7 text-stone-300/80">Where NG results are clustering most often.</p><div class="mt-6 h-[260px]"><DoughnutChart :data="failDoughnutData" :options="doughnutOpts" /></div></article>
                        <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]"><div class="text-xl font-semibold tracking-tight text-stone-50">Inspector efficiency</div><p class="mt-2 text-sm leading-7 text-stone-300/80">Average test duration by inspector in minutes.</p><div class="mt-6 h-[260px]"><BarChart :data="inspectorEffData" :options="horizontalBarOpts" /></div></article>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] reveal-section">
                    <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                        <div class="flex items-start justify-between gap-3"><div><div class="dash-kicker">Team activity</div><h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Inspector leaderboard</h2><p class="mt-3 text-sm leading-7 text-stone-300/80">A quick comparison of throughput and pass rate by inspector.</p></div><div class="rounded-full border border-white/10 bg-black/25 px-3 py-1 text-xs font-semibold text-orange-200">Top 5</div></div>
                        <div class="mt-5 space-y-3">
                            <div v-for="(inspector, index) in topInspectors" :key="`${inspector.name}-${index}`" class="flex items-center gap-4 rounded-2xl border border-white/10 bg-black/25 p-4">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-orange-500/15 text-sm font-bold text-orange-200">{{ index + 1 }}</div>
                                <div class="min-w-0 flex-1"><div class="text-base font-semibold tracking-tight text-stone-50">{{ inspector.name }}</div><div class="mt-1 text-sm text-stone-400">{{ formatNumber(inspector.total) }} tests, {{ formatNumber(inspector.ok) }} OK, {{ formatNumber(inspector.ng) }} NG</div></div>
                                <div class="text-right text-xl font-semibold tracking-tight text-orange-300">{{ formatPercent(inspector.yield) }}</div>
                            </div>
                            <div v-if="!inspectorData || !inspectorData.length" class="rounded-2xl border border-dashed border-white/10 bg-black/20 px-4 py-8 text-center text-sm text-stone-400">Inspector ranking will appear once enough results are recorded.</div>
                        </div>
                    </article>

                    <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                        <div class="flex items-start justify-between gap-3"><div><div class="dash-kicker">Recent activity</div><h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Latest inspection outcomes</h2></div><div class="rounded-full border border-white/10 bg-black/25 px-3 py-1 text-xs font-semibold text-orange-200">{{ selectedPeriodLabel }}</div></div>
                        <div class="mt-5 overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead><tr class="border-b border-white/10"><th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">ID</th><th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">DMC</th><th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">Detail</th><th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">Status</th></tr></thead>
                                <tbody>
                                    <tr v-for="activity in recentActivityPreview" :key="activity.id" class="border-b border-white/5">
                                        <td class="px-3 py-4 font-mono text-sm font-semibold text-stone-50">#{{ activity.id }}</td>
                                        <td class="px-3 py-4 text-sm text-stone-200">{{ activity.dmcCode || '-' }}</td>
                                        <td class="px-3 py-4 text-sm text-stone-300">{{ activity.detail || '-' }}</td>
                                        <td class="px-3 py-4 text-sm"><span :class="activity.result === 'OK' ? 'bg-orange-500/15 text-orange-200' : 'bg-stone-100/10 text-stone-200'" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold">{{ activity.result }}</span></td>
                                    </tr>
                                    <tr v-if="!recentActivities.length"><td colspan="4" class="px-3 py-8 text-center text-sm text-stone-400">No recent activity for this period.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </section>

                <template v-if="showTrendArchive">
                    <section class="space-y-4 reveal-section">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                            <div><div class="dash-kicker">Trend archive</div><h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Daily and monthly quality context</h2></div>
                            <p class="max-w-2xl text-sm leading-7 text-stone-300/80 lg:text-right">Pair the current pulse with broader trends to see whether recent movement is a short-term issue or part of a longer shift.</p>
                        </div>
                        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.95fr)]">
                            <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                                <div class="text-xl font-semibold tracking-tight text-stone-50">Monthly pass vs fail trend</div>
                                <p class="mt-2 text-sm leading-7 text-stone-300/80">A six-month view of pass/fail movement for the lab.</p>
                                <div class="mt-6 h-[320px]"><LineChart :data="monthlyLineData" :options="lineOpts" /></div>
                                <div class="mt-6 grid gap-3 md:grid-cols-3">
                                    <div v-for="item in monthlyHighlights" :key="item.label" class="rounded-2xl border border-white/10 bg-black/25 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">{{ item.label }}</div><div class="mt-2 text-xl font-semibold tracking-tight text-stone-50">{{ item.value }}</div><div class="mt-2 text-sm text-stone-400">{{ item.note }}</div></div>
                                </div>
                            </article>
                            <div class="grid gap-6">
                                <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]"><div class="text-xl font-semibold tracking-tight text-stone-50">Daily trend</div><p class="mt-2 text-sm leading-7 text-stone-300/80">Current-month day-by-day inspection movement.</p><div class="mt-6 h-[220px]"><LineChart :data="dailyLineData" :options="lineOpts" /></div></article>
                                <article class="dash-panel rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]"><div class="text-xl font-semibold tracking-tight text-stone-50">Six-month totals</div><div class="mt-4 grid gap-3 sm:grid-cols-2"><div class="rounded-2xl border border-orange-500/20 bg-orange-500/10 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-orange-200/80">Total OK</div><div class="mt-2 text-2xl font-semibold tracking-tight text-orange-100">{{ formatNumber(monthlyTotalOK) }}</div><div class="mt-2 text-sm text-orange-100/70">Across reported months</div></div><div class="rounded-2xl border border-white/10 bg-black/25 p-4"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">Total NG</div><div class="mt-2 text-2xl font-semibold tracking-tight text-stone-100">{{ formatNumber(monthlyTotalNG) }}</div><div class="mt-2 text-sm text-stone-400">Across reported months</div></div></div></article>
                            </div>
                        </div>
                    </section>
                </template>
                <template v-else>
                    <section class="dash-panel reveal-section rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                        <div class="dash-kicker">Trend archive</div>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Trend archive loads when you reach it</h2>
                        <p class="mt-3 text-sm leading-7 text-stone-300/80">Monthly and daily history stay deferred until this part of the dashboard approaches the viewport, which keeps the first paint lighter on both mobile and desktop.</p>
                        <div class="mt-5 grid gap-3 sm:grid-cols-3"><div class="dash-skeleton"></div><div class="dash-skeleton"></div><div class="dash-skeleton"></div></div>
                    </section>
                </template>
            </template>

            <section v-else class="dash-panel reveal-section rounded-[24px] border border-white/10 bg-[#16110d]/92 p-6 shadow-[0_18px_40px_rgba(0,0,0,0.28)]">
                <div class="dash-kicker">Loading</div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Detailed sections are on the way</h2>
                <p class="mt-3 text-sm leading-7 text-stone-300/80">Heavier charts load after the core dashboard so the first view stays responsive.</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-3"><div class="dash-skeleton"></div><div class="dash-skeleton"></div><div class="dash-skeleton"></div></div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.dash-hero {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(251, 146, 60, 0.14);
    border-radius: 32px;
    background:
        radial-gradient(circle at top left, rgba(251, 146, 60, 0.18), transparent 30%),
        radial-gradient(circle at bottom right, rgba(234, 88, 12, 0.12), transparent 28%),
        linear-gradient(145deg, rgba(10, 10, 10, 0.98), rgba(28, 18, 12, 0.96));
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.32);
}

.dash-kicker {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #fb923c;
}

.dash-heading {
    font-size: clamp(2rem, 3vw, 3.3rem);
    font-weight: 650;
    line-height: 0.98;
    letter-spacing: -0.045em;
    color: #fff7ed;
}

.dash-stat {
    position: relative;
    overflow: hidden;
}

.dash-stat::after {
    content: '';
    position: absolute;
    left: 20px;
    right: 20px;
    bottom: 16px;
    height: 3px;
    border-radius: 9999px;
}

.dash-stat[data-tone='orange']::after { background: linear-gradient(90deg, #f59e0b, transparent); }
.dash-stat[data-tone='ember']::after { background: linear-gradient(90deg, #ea580c, transparent); }
.dash-stat[data-tone='amber']::after { background: linear-gradient(90deg, #d97706, transparent); }
.dash-stat[data-tone='ink']::after { background: linear-gradient(90deg, #fef3c7, transparent); }

.reveal-section {
    animation: dash-rise 420ms ease-out both;
}

.dash-skeleton {
    height: 110px;
    border-radius: 18px;
    background: linear-gradient(90deg, rgba(41, 37, 36, 0.9), rgba(68, 64, 60, 0.95), rgba(41, 37, 36, 0.9));
    background-size: 200% 100%;
    animation: dash-shimmer 1.6s linear infinite;
}

@keyframes dash-rise {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes dash-shimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

@media (max-width: 767px) {
    .dash-hero {
        border-radius: 24px;
    }
}
</style>
