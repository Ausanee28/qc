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
const formatDecimal = (value) => Number(value || 0).toFixed(1);

const totalTests = computed(() => Number(props.metrics.totalTests || 0));
const yieldPct = computed(() => Number(props.metrics.yieldRate || 0));
const defectPct = computed(() => Number(props.metrics.defectRate || 0));
const todayTotal = computed(() => Number(props.metrics.todayOK || 0) + Number(props.metrics.todayNG || 0));
const todayYield = computed(() => todayTotal.value > 0 ? Number(((props.metrics.todayOK / todayTotal.value) * 100).toFixed(1)) : 0);
const monthlyTotalOK = computed(() => props.monthlyData.reduce((sum, month) => sum + Number(month.ok || 0), 0));
const monthlyTotalNG = computed(() => props.monthlyData.reduce((sum, month) => sum + Number(month.ng || 0), 0));
const selectedPeriodLabel = computed(() => periodLabels[selectedPeriod.value] || 'This Month');
const periodJobs = computed(() => Number(props.metrics.periodJobs || 0));
const leadEquipment = computed(() => props.equipRank?.[0] ?? null);
const leadFailure = computed(() => props.failByEquip?.[0] ?? null);
const leadInspector = computed(() => props.inspectorData?.[0] ?? null);
const pendingJobs = computed(() => Number(props.metrics.pendingCount || 0));

const healthSummary = computed(() => {
    if (!totalTests.value) {
        return {
            title: 'Waiting for inspection data',
            text: 'Record the first jobs to unlock quality and workload signals.',
            tone: 'ink',
        };
    }

    if (yieldPct.value >= 95 && Number(props.metrics.pendingCount || 0) <= 5) {
        return {
            title: 'Quality is stable and queue pressure is low',
            text: 'Yield is above target and the queue is still manageable.',
            tone: 'orange',
        };
    }

    if (yieldPct.value >= 92) {
        return {
            title: 'Operations look healthy with a few areas to watch',
            text: 'Watch the queue and any equipment causing NG clusters.',
            tone: 'amber',
        };
    }

    return {
        title: 'Quality needs attention this period',
            text: 'Yield is below target. Review failure drivers and workload balance.',
            tone: 'ember',
    };
});

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
const heroStatusMetrics = computed(() => ([
    { label: 'Pass yield', value: formatPercent(yieldPct.value), note: `${formatNumber(props.metrics.okCount)} OK` },
    { label: 'Defect rate', value: formatPercent(defectPct.value), note: `${formatNumber(props.metrics.ngCount)} NG` },
    { label: 'Pending jobs', value: formatNumber(pendingJobs.value), note: `${formatNumber(periodJobs.value)} jobs in window` },
]));
const heroSummaryCards = computed(() => ([
    {
        label: 'Jobs received',
        value: formatNumber(periodJobs.value),
        note: `${selectedPeriodLabel.value} incoming jobs`,
    },
    {
        label: 'Inspections run',
        value: formatNumber(totalTests.value),
        note: `${formatDecimal(props.metrics.testsPerJob)} tests per job`,
    },
    {
        label: 'Average test time',
        value: `${formatNumber(props.metrics.avgTestTime)} min`,
        note: 'Average time per completed inspection',
    },
    {
        label: 'Today completed',
        value: formatNumber(todayTotal.value),
        note: `${formatPercent(todayYield.value)} yield today`,
    },
]));
const snapshotMetrics = computed(() => ([
    { label: 'Jobs received', value: formatNumber(periodJobs.value), note: `${selectedPeriodLabel.value} intake volume` },
    { label: 'Tests per job', value: formatDecimal(props.metrics.testsPerJob), note: 'Average inspection density per job' },
    { label: 'Pending jobs', value: formatNumber(pendingJobs.value), note: 'Jobs still waiting to close' },
    { label: 'Avg test time', value: `${formatNumber(props.metrics.avgTestTime)} min`, note: 'Average time spent per inspection' },
]));
const weeklyOkTotal = computed(() => props.weeklyData.reduce((sum, day) => sum + Number(day.ok || 0), 0));
const weeklyNgTotal = computed(() => props.weeklyData.reduce((sum, day) => sum + Number(day.ng || 0), 0));
const weeklyTotal = computed(() => weeklyOkTotal.value + weeklyNgTotal.value);
const weeklyYield = computed(() => weeklyTotal.value > 0 ? Number(((weeklyOkTotal.value / weeklyTotal.value) * 100).toFixed(1)) : 0);
const movementSummary = computed(() => ([
    { label: '7-day total', value: formatNumber(weeklyTotal.value), note: `${formatNumber(weeklyOkTotal.value)} OK / ${formatNumber(weeklyNgTotal.value)} NG` },
    { label: '7-day yield', value: formatPercent(weeklyYield.value), note: 'How clean the recent flow has been' },
    { label: 'Open queue', value: formatNumber(pendingJobs.value), note: 'Jobs currently waiting to close' },
]));
const weeklyRows = computed(() => props.weeklyData.map((day) => {
    const ok = Number(day.ok || 0);
    const ng = Number(day.ng || 0);
    const total = ok + ng;
    const yieldRate = total > 0 ? Number(((ok / total) * 100).toFixed(1)) : 0;

    return {
        label: day.label,
        ok,
        ng,
        total,
        yieldRate,
    };
}));
const movementHighlights = computed(() => {
    const activeDays = weeklyRows.value.filter((day) => day.total > 0);

    if (!activeDays.length) {
        return [
            { label: 'Strongest day', value: 'No activity yet', note: 'The busiest day appears once tests are recorded.' },
            { label: 'Highest NG day', value: 'No failures yet', note: 'Use this spot to catch the roughest day quickly.' },
            { label: 'Flow signal', value: pendingJobs.value ? 'Queue waiting' : 'Queue clear', note: `${formatNumber(pendingJobs.value)} open jobs need follow-up.` },
        ];
    }

    const strongestDay = activeDays.reduce((best, day) => (day.total > best.total ? day : best), activeDays[0]);
    const highestNgDay = activeDays.reduce((worst, day) => (day.ng > worst.ng ? day : worst), activeDays[0]);
    const latestDay = activeDays[activeDays.length - 1];

    return [
        {
            label: 'Strongest day',
            value: strongestDay.label,
            note: `${formatNumber(strongestDay.total)} inspections moved through the lab.`,
        },
        {
            label: 'Highest NG day',
            value: highestNgDay.ng > 0 ? highestNgDay.label : 'No NG spike',
            note: highestNgDay.ng > 0
                ? `${formatNumber(highestNgDay.ng)} NG records were logged on that day.`
                : 'The week has not produced a visible fail cluster.',
        },
        {
            label: 'Latest flow signal',
            value: `${formatPercent(latestDay.yieldRate)} yield`,
            note: `${latestDay.label} closed with ${formatNumber(latestDay.total)} inspections.`,
        },
    ];
});
const attentionItems = computed(() => {
    if (!totalTests.value) {
        return [
            {
                title: 'Dashboard readiness',
                value: 'No inspections yet',
                detail: 'Record incoming jobs and results to unlock live signals.',
                tone: 'ink',
            },
            {
                title: 'Open queue',
                value: formatNumber(pendingJobs.value),
                detail: 'Jobs waiting to close right now.',
                tone: pendingJobs.value ? 'amber' : 'ink',
            },
            {
                title: 'Next step',
                value: 'Start intake or testing',
                detail: 'Use the quick actions to build today\'s dashboard view.',
                tone: 'orange',
            },
        ];
    }

    const items = [
        pendingJobs.value <= 5
            ? {
                title: 'Queue status',
                value: 'In control',
                detail: `${formatNumber(pendingJobs.value)} jobs remain open.`,
                tone: 'orange',
            }
            : {
                title: 'Queue status',
                value: 'Needs review',
                detail: `${formatNumber(pendingJobs.value)} jobs remain open.`,
                tone: 'amber',
            },
        yieldPct.value >= 95
            ? {
                title: 'Quality signal',
                value: 'Above target',
                detail: `${formatPercent(yieldPct.value)} yield in ${selectedPeriodLabel.value.toLowerCase()}.`,
                tone: 'orange',
            }
            : {
                title: 'Quality signal',
                value: 'Needs attention',
                detail: `${formatPercent(defectPct.value)} defect rate in ${selectedPeriodLabel.value.toLowerCase()}.`,
                tone: 'ember',
            },
    ];

    if (leadFailure.value) {
        items.push({
            title: 'Primary focus',
            value: leadFailure.value.name,
            detail: `${leadFailure.value.name}: ${formatNumber(leadFailure.value.count)} NG results.`,
            tone: 'ember',
        });
    } else if (leadEquipment.value) {
        items.push({
            title: 'Primary focus',
            value: leadEquipment.value.name,
            detail: `${leadEquipment.value.name}: ${formatNumber(leadEquipment.value.count)} tests.`,
            tone: 'ink',
        });
    } else {
        items.push({
            title: 'Primary focus',
            value: 'Signals still building',
            detail: 'This card will surface the main risk once more results arrive.',
            tone: 'ink',
        });
    }

    return items;
});

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

        <div class="dashboard-shell space-y-6">
            <section class="dash-hero reveal-section p-5 sm:p-7 lg:p-8">
                <div class="dash-hero__glow dash-hero__glow--one"></div>
                <div class="dash-hero__glow dash-hero__glow--two"></div>

                <div class="relative z-10">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div class="min-w-0 max-w-3xl">
                            <div class="dash-kicker">Operations Dashboard</div>
                            <h1 class="dash-heading mt-3">Lab performance in one view</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-300/80 sm:text-base">
                                A clean read of queue pressure, quality outcome, and team workload for {{ selectedPeriodLabel.toLowerCase() }}.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="dash-chip">
                                <span class="dash-chip__dot"></span>
                                {{ isLoading ? 'Refreshing data' : 'Live overview' }}
                            </div>
                            <label class="dash-chip dash-chip--select">
                                <span class="text-stone-400">Window</span>
                                <select v-model="selectedPeriod" class="dash-select">
                                    <option v-for="option in periodOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1.12fr)_minmax(300px,0.88fr)]">
                        <article class="hero-brief" :data-tone="healthSummary.tone">
                            <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/55">Executive summary</div>
                            <h2 class="mt-4 max-w-2xl text-[clamp(2rem,3vw,3.35rem)] font-semibold leading-[1.02] tracking-tight text-white">{{ healthSummary.title }}</h2>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-white/78">{{ healthSummary.text }}</p>

                            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                <div v-for="item in heroStatusMetrics" :key="item.label" class="metric-glass">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/55">{{ item.label }}</div>
                                    <div class="mt-4 text-[clamp(1.9rem,2.5vw,2.8rem)] font-semibold leading-none tracking-tight text-white">{{ item.value }}</div>
                                    <div class="mt-3 text-sm leading-5 text-white/72">{{ item.note }}</div>
                                </div>
                            </div>
                        </article>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <article v-for="card in heroSummaryCards" :key="card.label" class="surface-card summary-tile p-5">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">{{ card.label }}</div>
                                <div class="mt-4 text-3xl font-semibold tracking-tight text-stone-50">{{ card.value }}</div>
                                <div class="mt-3 text-sm leading-6 text-stone-400">{{ card.note }}</div>
                            </article>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 lg:grid-cols-3">
                        <article v-for="item in attentionItems" :key="item.title" class="attention-card" :data-tone="item.tone">
                            <div class="text-[11px] font-bold uppercase tracking-[0.16em]">{{ item.title }}</div>
                            <div class="mt-3 text-xl font-semibold tracking-tight">{{ item.value }}</div>
                            <div class="mt-3 text-sm leading-6">{{ item.detail }}</div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.95fr)] reveal-section">
                <article class="surface-card surface-card--deep self-start p-5 sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <div class="dash-kicker">Movement</div>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">7-day flow</h2>
                            <p class="mt-3 text-sm leading-7 text-stone-300/80">See throughput, yield movement, and the days that deserve follow-up.</p>
                        </div>
                        <div class="mini-badge">{{ selectedPeriodLabel }}</div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-3">
                        <div v-for="item in movementSummary" :key="item.label" class="surface-inset">
                            <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">{{ item.label }}</div>
                            <div class="mt-3 text-2xl font-semibold tracking-tight text-stone-50">{{ item.value }}</div>
                            <div class="mt-2 text-sm leading-6 text-stone-400">{{ item.note }}</div>
                        </div>
                    </div>

                    <div class="mt-6 h-[280px] rounded-[24px] border border-white/5 bg-black/20 p-3">
                        <BarChart v-if="showPrimaryCharts" :data="weeklyChartData" :options="barOpts" />
                        <div v-else class="dash-skeleton h-full"></div>
                    </div>

                    <div class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
                        <div class="grid gap-3">
                            <div v-for="item in movementHighlights" :key="item.label" class="surface-inset">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">{{ item.label }}</div>
                                <div class="mt-3 text-lg font-semibold tracking-tight text-stone-50">{{ item.value }}</div>
                                <div class="mt-2 text-sm leading-6 text-stone-400">{{ item.note }}</div>
                            </div>
                        </div>

                        <div class="surface-inset">
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">Daily breakdown</div>
                                    <div class="mt-2 text-lg font-semibold tracking-tight text-stone-50">Contribution by day</div>
                                </div>
                                <div class="text-xs font-semibold text-stone-500">7 days</div>
                            </div>
                            <div class="mt-4 space-y-2">
                                <div v-for="day in weeklyRows" :key="day.label" class="day-row">
                                    <div class="text-sm font-semibold text-stone-200">{{ day.label }}</div>
                                    <div>
                                        <div class="quality-bar quality-bar--thin">
                                            <div class="quality-bar__ok" :style="{ width: day.total ? `${day.yieldRate}%` : '0%' }"></div>
                                            <div class="quality-bar__ng" :style="{ width: day.total ? `${100 - day.yieldRate}%` : '0%' }"></div>
                                        </div>
                                        <div class="mt-1 flex items-center justify-between text-[11px] text-stone-500">
                                            <span>{{ formatNumber(day.ok) }} OK / {{ formatNumber(day.ng) }} NG</span>
                                            <span>{{ formatNumber(day.total) }} total</span>
                                        </div>
                                    </div>
                                    <div class="text-right text-sm font-semibold text-orange-200">{{ formatPercent(day.yieldRate) }}</div>
                                </div>
                                <div v-if="!weeklyRows.length" class="rounded-2xl border border-dashed border-white/10 bg-black/20 px-4 py-6 text-center text-sm text-stone-400">
                                    Weekly movement detail will appear once inspection activity is recorded.
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="surface-card self-start p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="dash-kicker">Action board</div>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Snapshot and next actions</h2>
                            <p class="mt-3 text-sm leading-7 text-stone-300/80">Keep the current window in view while moving straight into the next workflow step.</p>
                        </div>
                        <div class="mini-badge">{{ selectedPeriodLabel }}</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <Link :href="route('receive-job.create')" prefetch="click" cache-for="45s" view-transition class="quick-link">
                            <div><div class="text-base font-semibold tracking-tight text-stone-50">Receive new job</div><div class="mt-1 text-sm text-stone-400">Open intake and register incoming work.</div></div>
                            <span class="text-lg text-white/50">></span>
                        </Link>
                        <Link :href="route('execute-test.create')" prefetch="click" cache-for="45s" view-transition class="quick-link">
                            <div><div class="text-base font-semibold tracking-tight text-stone-50">Record test result</div><div class="mt-1 text-sm text-stone-400">Jump straight to active inspections.</div></div>
                            <span class="text-lg text-white/50">></span>
                        </Link>
                        <Link :href="route('report.index')" prefetch="click" cache-for="45s" view-transition class="quick-link">
                            <div><div class="text-base font-semibold tracking-tight text-stone-50">Open reporting</div><div class="mt-1 text-sm text-stone-400">Review history and export filtered reports.</div></div>
                            <span class="text-lg text-white/50">></span>
                        </Link>
                    </div>

                    <div class="mt-5 pulse-card">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-orange-200/75">Today pulse</div>
                                <div class="mt-3 text-4xl font-semibold tracking-tight text-white">{{ formatNumber(todayTotal) }}</div>
                                <div class="mt-2 text-sm text-white/65">completed inspections</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-orange-200/75">Yield</div>
                                <div class="mt-3 text-3xl font-semibold tracking-tight text-orange-100">{{ formatPercent(todayYield) }}</div>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="metric-chip">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/55">OK today</div>
                                <div class="mt-2 text-xl font-semibold tracking-tight text-white">{{ formatNumber(props.metrics.todayOK) }}</div>
                            </div>
                            <div class="metric-chip">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/55">NG today</div>
                                <div class="mt-2 text-xl font-semibold tracking-tight text-white">{{ formatNumber(props.metrics.todayNG) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 surface-inset">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">Window snapshot</div>
                                <div class="mt-2 text-3xl font-semibold tracking-tight text-stone-50">{{ formatNumber(totalTests) }}</div>
                                <div class="mt-2 text-sm text-stone-400">{{ formatNumber(periodJobs) }} jobs received in {{ selectedPeriodLabel.toLowerCase() }}</div>
                            </div>
                            <div class="mini-badge mini-badge--accent">{{ formatPercent(yieldPct) }} yield</div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div>
                                <div class="mb-2 flex items-center justify-between text-xs font-semibold text-stone-400">
                                    <span>Quality mix</span>
                                    <span>{{ formatPercent(defectPct) }} defect</span>
                                </div>
                                <div class="quality-bar">
                                    <div class="quality-bar__ok" :style="{ width: `${yieldPct}%` }"></div>
                                    <div class="quality-bar__ng" :style="{ width: `${defectPct}%` }"></div>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div v-for="item in snapshotMetrics" :key="item.label" class="metric-chip metric-chip--dark">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">{{ item.label }}</div>
                                    <div class="mt-2 text-xl font-semibold tracking-tight text-stone-50">{{ item.value }}</div>
                                    <div class="mt-2 text-sm leading-6 text-stone-400">{{ item.note }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <template v-if="showHeavySections">
                <div ref="trendArchiveSentinel" class="h-px w-full"></div>

                <section class="space-y-4 reveal-section">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div><div class="dash-kicker">Performance Drivers</div><h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Charts that explain the story</h2></div>
                        <p class="max-w-2xl text-sm leading-7 text-stone-300/80 lg:text-right">Equipment load, failure concentration, and inspector pace for {{ selectedPeriodLabel.toLowerCase() }}.</p>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-3">
                        <article v-for="item in signalCards" :key="item.label" class="surface-card p-5">
                            <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">{{ item.label }}</div>
                            <div class="mt-3 text-lg font-semibold tracking-tight text-stone-50">{{ item.value }}</div>
                            <div class="mt-2 text-sm leading-6 text-stone-400">{{ item.note }}</div>
                        </article>
                    </div>
                    <div class="grid gap-6 xl:grid-cols-3">
                        <article class="surface-card p-5 sm:p-6"><div class="text-xl font-semibold tracking-tight text-stone-50">Equipment load</div><p class="mt-2 text-sm leading-7 text-stone-300/80">Most-used equipment in the selected window.</p><div class="mt-6 h-[260px]"><BarChart :data="equipUsageData" :options="horizontalBarOpts" /></div></article>
                        <article class="surface-card p-5 sm:p-6"><div class="text-xl font-semibold tracking-tight text-stone-50">NG hotspot</div><p class="mt-2 text-sm leading-7 text-stone-300/80">Where failed inspections are clustering most often.</p><div class="mt-6 h-[260px]"><DoughnutChart :data="failDoughnutData" :options="doughnutOpts" /></div></article>
                        <article class="surface-card p-5 sm:p-6"><div class="text-xl font-semibold tracking-tight text-stone-50">Inspector pace</div><p class="mt-2 text-sm leading-7 text-stone-300/80">Average inspection duration by inspector in minutes.</p><div class="mt-6 h-[260px]"><BarChart :data="inspectorEffData" :options="horizontalBarOpts" /></div></article>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[minmax(0,0.96fr)_minmax(0,1.04fr)] reveal-section">
                    <article class="surface-card p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3"><div><div class="dash-kicker">Team Activity</div><h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Inspector leaderboard</h2><p class="mt-3 text-sm leading-7 text-stone-300/80">Compare throughput and pass rate across the team.</p></div><div class="mini-badge">Top 5</div></div>
                        <div class="mt-5 space-y-3">
                            <div v-for="(inspector, index) in topInspectors" :key="`${inspector.name}-${index}`" class="leaderboard-row">
                                <div class="leaderboard-row__rank">{{ index + 1 }}</div>
                                <div class="min-w-0 flex-1"><div class="text-base font-semibold tracking-tight text-stone-50">{{ inspector.name }}</div><div class="mt-1 text-sm text-stone-400">{{ formatNumber(inspector.total) }} tests, {{ formatNumber(inspector.ok) }} OK, {{ formatNumber(inspector.ng) }} NG</div></div>
                                <div class="text-right"><div class="text-xl font-semibold tracking-tight text-orange-200">{{ formatPercent(inspector.yield) }}</div><div class="text-xs text-stone-500">pass yield</div></div>
                            </div>
                            <div v-if="!inspectorData || !inspectorData.length" class="rounded-2xl border border-dashed border-white/10 bg-black/20 px-4 py-8 text-center text-sm text-stone-400">Inspector ranking will appear once enough results are recorded.</div>
                        </div>
                    </article>

                    <article class="surface-card p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3"><div><div class="dash-kicker">Recent Activity</div><h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Latest closed jobs</h2><p class="mt-3 text-sm leading-7 text-stone-300/80">The newest completed jobs with result mix and close date.</p></div><div class="mini-badge">{{ selectedPeriodLabel }}</div></div>
                        <div class="mt-5 overflow-x-auto">
                            <table class="dash-table w-full border-collapse">
                                <thead><tr class="border-b border-white/10"><th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">Job</th><th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">Detail</th><th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">Result mix</th><th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-stone-500">Closed</th></tr></thead>
                                <tbody>
                                    <tr v-for="activity in recentActivityPreview" :key="activity.id" class="dash-table__row align-top">
                                        <td class="px-3 py-4">
                                            <div class="font-mono text-sm font-semibold text-stone-50">#{{ activity.id }}</div>
                                            <div class="mt-1 text-xs text-stone-500">{{ activity.dmcCode || '-' }}</div>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-stone-300">{{ activity.detail || '-' }}</td>
                                        <td class="px-3 py-4 text-sm">
                                            <div class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="activity.result === 'OK' ? 'bg-orange-500/15 text-orange-200' : 'bg-stone-100/10 text-stone-200'">{{ activity.result }}</div>
                                            <div class="mt-2 text-xs text-stone-400">{{ formatNumber(activity.ok) }} OK / {{ formatNumber(activity.ng) }} NG</div>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-stone-400">{{ activity.date }}</td>
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
                            <div><div class="dash-kicker">Trend Archive</div><h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Daily and monthly trends</h2></div>
                            <p class="max-w-2xl text-sm leading-7 text-stone-300/80 lg:text-right">Use the broader trend to confirm whether the latest movement is isolated or persistent.</p>
                        </div>
                        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.95fr)]">
                            <article class="surface-card p-5 sm:p-6">
                                <div class="text-xl font-semibold tracking-tight text-stone-50">Monthly pass vs fail trend</div>
                                <p class="mt-2 text-sm leading-7 text-stone-300/80">Six months of pass and fail movement.</p>
                                <div class="mt-6 h-[320px]"><LineChart :data="monthlyLineData" :options="lineOpts" /></div>
                                <div class="mt-6 grid gap-3 md:grid-cols-3">
                                    <div v-for="item in monthlyHighlights" :key="item.label" class="surface-inset"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">{{ item.label }}</div><div class="mt-2 text-xl font-semibold tracking-tight text-stone-50">{{ item.value }}</div><div class="mt-2 text-sm text-stone-400">{{ item.note }}</div></div>
                                </div>
                            </article>
                            <div class="grid gap-6">
                                <article class="surface-card p-5 sm:p-6"><div class="text-xl font-semibold tracking-tight text-stone-50">Daily trend</div><p class="mt-2 text-sm leading-7 text-stone-300/80">Day-by-day inspection movement this month.</p><div class="mt-6 h-[220px]"><LineChart :data="dailyLineData" :options="lineOpts" /></div></article>
                                <article class="surface-card p-5 sm:p-6"><div class="text-xl font-semibold tracking-tight text-stone-50">Six-month totals</div><div class="mt-4 grid gap-3 sm:grid-cols-2"><div class="metric-chip metric-chip--accent"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-orange-200/80">Total OK</div><div class="mt-2 text-2xl font-semibold tracking-tight text-orange-100">{{ formatNumber(monthlyTotalOK) }}</div><div class="mt-2 text-sm text-orange-100/70">Across reported months</div></div><div class="metric-chip metric-chip--dark"><div class="text-[11px] font-bold uppercase tracking-[0.16em] text-stone-500">Total NG</div><div class="mt-2 text-2xl font-semibold tracking-tight text-stone-100">{{ formatNumber(monthlyTotalNG) }}</div><div class="mt-2 text-sm text-stone-400">Across reported months</div></div></div></article>
                            </div>
                        </div>
                    </section>
                </template>
                <template v-else>
                    <section class="surface-card reveal-section p-5 sm:p-6">
                        <div class="dash-kicker">Trend Archive</div>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Trend archive loads on approach</h2>
                        <p class="mt-3 text-sm leading-7 text-stone-300/80">Daily and monthly history stay deferred until this area is near the viewport to keep the first load responsive.</p>
                        <div class="mt-5 grid gap-3 sm:grid-cols-3"><div class="dash-skeleton"></div><div class="dash-skeleton"></div><div class="dash-skeleton"></div></div>
                    </section>
                </template>
            </template>

            <section v-else class="surface-card reveal-section p-5 sm:p-6">
                <div class="dash-kicker">Loading</div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-stone-50">Detailed sections are on the way</h2>
                <p class="mt-3 text-sm leading-7 text-stone-300/80">Heavier charts load after the main summary so the first view stays responsive.</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-3"><div class="dash-skeleton"></div><div class="dash-skeleton"></div><div class="dash-skeleton"></div></div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.dashboard-shell {
    position: relative;
}

.dashboard-shell::before {
    content: '';
    position: fixed;
    inset: auto 0 0 0;
    height: 60vh;
    pointer-events: none;
    background:
        radial-gradient(circle at 15% 20%, rgba(251, 146, 60, 0.08), transparent 26%),
        radial-gradient(circle at 85% 0%, rgba(120, 53, 15, 0.12), transparent 24%);
    z-index: 0;
}

.dash-hero {
    position: relative;
    overflow: hidden;
    isolation: isolate;
    border: 1px solid rgba(251, 146, 60, 0.16);
    border-radius: 36px;
    background:
        linear-gradient(135deg, rgba(10, 10, 10, 0.98), rgba(27, 20, 16, 0.96) 58%, rgba(38, 21, 10, 0.95)),
        linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0));
    box-shadow: 0 34px 90px rgba(0, 0, 0, 0.34);
}

.dash-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(115deg, rgba(255, 255, 255, 0.05), transparent 26%),
        repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.03) 0 1px, transparent 1px 110px);
    opacity: 0.4;
    pointer-events: none;
}

.dash-hero__glow {
    position: absolute;
    border-radius: 9999px;
    filter: blur(24px);
    opacity: 0.7;
    pointer-events: none;
}

.dash-hero__glow--one {
    top: -30px;
    left: -20px;
    width: 260px;
    height: 260px;
    background: rgba(251, 146, 60, 0.16);
}

.dash-hero__glow--two {
    right: -40px;
    bottom: -60px;
    width: 300px;
    height: 300px;
    background: rgba(194, 65, 12, 0.18);
}

.surface-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 28px;
    background: linear-gradient(180deg, rgba(24, 18, 14, 0.95), rgba(14, 11, 9, 0.96));
    box-shadow: 0 22px 50px rgba(0, 0, 0, 0.26);
}

.surface-card::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(circle at top right, rgba(251, 146, 60, 0.12), transparent 36%);
}

.surface-card--deep {
    background: linear-gradient(180deg, rgba(18, 13, 11, 0.97), rgba(10, 8, 7, 0.97));
}

.surface-inset {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(0, 0, 0, 0.18));
    padding: 1rem;
}

.hero-brief {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    padding: 1.5rem;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.hero-brief::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.08), transparent 36%);
    pointer-events: none;
}

.hero-brief[data-tone='ink'] {
    background: linear-gradient(160deg, rgba(16, 13, 11, 0.95), rgba(31, 24, 20, 0.95));
}

.hero-brief[data-tone='orange'] {
    background: linear-gradient(160deg, rgba(45, 23, 10, 0.96), rgba(154, 52, 18, 0.88));
}

.hero-brief[data-tone='amber'] {
    background: linear-gradient(160deg, rgba(44, 26, 11, 0.96), rgba(180, 83, 9, 0.86));
}

.hero-brief[data-tone='ember'] {
    background: linear-gradient(160deg, rgba(31, 16, 10, 0.96), rgba(124, 45, 18, 0.88));
}

.dash-kicker {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #fb923c;
}

.dash-heading {
    font-size: clamp(2.3rem, 3.5vw, 4rem);
    font-weight: 650;
    line-height: 0.96;
    letter-spacing: -0.05em;
    color: #fff7ed;
}

.dash-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 9999px;
    background: rgba(0, 0, 0, 0.28);
    padding: 0.72rem 1rem;
    font-size: 0.92rem;
    font-weight: 600;
    color: #f5f5f4;
    backdrop-filter: blur(14px);
}

.dash-chip--select {
    gap: 0.65rem;
}

.dash-chip__dot {
    width: 0.65rem;
    height: 0.65rem;
    border-radius: 9999px;
    background: #fb923c;
    box-shadow: 0 0 0 6px rgba(251, 146, 60, 0.16);
}

.dash-select {
    min-width: 0;
    border: 0;
    background: transparent;
    color: #fafaf9;
    font-weight: 600;
    outline: none;
}

.dash-select option {
    color: #111827;
}

.metric-glass {
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 22px;
    background: rgba(0, 0, 0, 0.18);
    padding: 1rem 1.1rem;
    backdrop-filter: blur(14px);
}

.summary-tile {
    min-height: 160px;
}

.attention-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 22px;
    padding: 1.2rem 1.25rem;
    color: #d6d3d1;
}

.attention-card::after {
    content: '';
    position: absolute;
    left: 1.25rem;
    right: 1.25rem;
    bottom: 0.95rem;
    height: 2px;
    border-radius: 9999px;
}

.attention-card[data-tone='orange'] {
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.12), rgba(120, 53, 15, 0.16));
    color: #fff7ed;
}

.attention-card[data-tone='orange']::after {
    background: linear-gradient(90deg, #f59e0b, transparent);
}

.attention-card[data-tone='amber'] {
    background: linear-gradient(180deg, rgba(217, 119, 6, 0.14), rgba(120, 53, 15, 0.12));
    color: #fff7ed;
}

.attention-card[data-tone='amber']::after {
    background: linear-gradient(90deg, #d97706, transparent);
}

.attention-card[data-tone='ember'] {
    background: linear-gradient(180deg, rgba(154, 52, 18, 0.18), rgba(91, 45, 18, 0.12));
    color: #fff7ed;
}

.attention-card[data-tone='ember']::after {
    background: linear-gradient(90deg, #ea580c, transparent);
}

.attention-card[data-tone='ink'] {
    background: rgba(0, 0, 0, 0.24);
    color: #e7e5e4;
}

.attention-card[data-tone='ink']::after {
    background: linear-gradient(90deg, #fef3c7, transparent);
}

.pulse-card {
    border: 1px solid rgba(251, 146, 60, 0.18);
    border-radius: 24px;
    background: linear-gradient(160deg, rgba(14, 10, 8, 0.98), rgba(34, 20, 10, 0.94));
    padding: 1.25rem;
}

.metric-chip {
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.05);
    padding: 0.95rem;
}

.metric-chip--dark {
    background: rgba(0, 0, 0, 0.24);
}

.metric-chip--accent {
    border-color: rgba(251, 146, 60, 0.2);
    background: rgba(251, 146, 60, 0.1);
}

.mini-badge {
    display: inline-flex;
    align-items: center;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 9999px;
    background: rgba(0, 0, 0, 0.2);
    padding: 0.45rem 0.8rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #fdba74;
}

.mini-badge--accent {
    border-color: rgba(251, 146, 60, 0.2);
    background: rgba(251, 146, 60, 0.12);
}

.quality-bar {
    display: flex;
    height: 0.75rem;
    overflow: hidden;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.08);
}

.quality-bar--thin {
    height: 0.52rem;
}

.quality-bar__ok {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.quality-bar__ng {
    background: linear-gradient(90deg, #7c2d12, #ea580c);
}

.day-row {
    display: grid;
    grid-template-columns: 88px minmax(0, 1fr) 64px;
    align-items: center;
    gap: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.03);
    padding: 0.75rem;
}

.quick-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.04);
    padding: 1rem 1.1rem;
    transition: transform 180ms ease, border-color 180ms ease, background 180ms ease;
}

.quick-link:hover {
    transform: translateY(-1px);
    border-color: rgba(251, 146, 60, 0.22);
    background: rgba(255, 255, 255, 0.07);
}

.leaderboard-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 22px;
    background: rgba(0, 0, 0, 0.2);
    padding: 1rem;
}

.leaderboard-row__rank {
    display: flex;
    width: 2.5rem;
    height: 2.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: rgba(251, 146, 60, 0.14);
    color: #fdba74;
    font-weight: 700;
}

.dash-table__row {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.dash-table__row:hover {
    background: rgba(255, 255, 255, 0.02);
}

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
    .dash-hero,
    .surface-card {
        border-radius: 24px;
    }

    .hero-brief,
    .surface-inset,
    .pulse-card,
    .quick-link,
    .attention-card,
    .leaderboard-row {
        border-radius: 20px;
    }

    .day-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 1279px) {
    .summary-tile {
        min-height: auto;
    }
}
</style>
