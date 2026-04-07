<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, onMounted, onUnmounted, ref, watch } from 'vue';
import { getEcho } from '@/lib/realtime';

const dashboardReloadOnly = ['currentPeriod', 'metrics', 'weeklyData', 'fourWeekData', 'dailyData', 'monthlyData', 'inspectorData', 'flash'];

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
    fourWeekData: { type: Array, default: () => [] },
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
const chartsReady = ref(false);
const realtimeMode = ref('connecting');
const lastRealtimeSyncAt = ref(null);
const lastRealtimeEventAt = ref(null);
let themeObserver = null;
let dashboardEcho = null;
let realtimeRefreshTimer = null;
let realtimeBootTimer = null;
let chartsBootTimer = null;
let dashboardPollTimer = null;
let echoConnection = null;
let echoConnectionStateHandler = null;
let usePollingFallback = true;
const realtimeRefreshDelayMs = 250;
const dashboardSyncIntervalActiveMs = 30000;
const dashboardSyncIntervalHiddenMs = 90000;
const currentPeriodLabel = computed(() => periodLabels[props.currentPeriod] || 'This Month');
const dashboardInvalidateTags = ['dashboard', 'workflow', 'performance', 'report', 'certificates'];
const Line = defineAsyncComponent({
    loader: () => import('@/lib/dashboard-charts').then((mod) => mod.Line),
    suspensible: false,
});
const Bar = defineAsyncComponent({
    loader: () => import('@/lib/dashboard-charts').then((mod) => mod.Bar),
    suspensible: false,
});
const Doughnut = defineAsyncComponent({
    loader: () => import('@/lib/dashboard-charts').then((mod) => mod.Doughnut),
    suspensible: false,
});

const syncTheme = () => {
    if (typeof document === 'undefined') {
        return;
    }

    currentTheme.value = document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
};

const isLightTheme = computed(() => currentTheme.value === 'light');

const formatRealtimeClock = (value) => {
    if (!value) {
        return '';
    }

    const dateValue = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(dateValue.getTime())) {
        return '';
    }

    return dateValue.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const realtimeStatusLabel = computed(() => {
    if (realtimeMode.value === 'live') {
        return 'Live';
    }

    if (realtimeMode.value === 'polling') {
        return 'Auto Sync';
    }

    return 'Connecting';
});

const realtimeStatusNote = computed(() => {
    if (realtimeMode.value === 'connecting') {
        return 'starting...';
    }

    const reference = lastRealtimeEventAt.value || lastRealtimeSyncAt.value;
    const formatted = formatRealtimeClock(reference);
    return formatted ? `updated ${formatted}` : '';
});

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

const reloadDashboardRealtime = () => {
    if (isChangingPeriod.value) {
        return;
    }

    isChangingPeriod.value = true;
    router.reload({
        only: dashboardReloadOnly,
        cacheTags: dashboardInvalidateTags,
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isChangingPeriod.value = false;
            lastRealtimeSyncAt.value = new Date().toISOString();
        },
    });
};

const scheduleRealtimeReload = () => {
    if (typeof document !== 'undefined' && document.visibilityState !== 'visible') {
        return;
    }

    if (realtimeRefreshTimer !== null) {
        return;
    }

    realtimeRefreshTimer = window.setTimeout(() => {
        realtimeRefreshTimer = null;
        reloadDashboardRealtime();
    }, realtimeRefreshDelayMs);
};

const stopDashboardPolling = () => {
    if (dashboardPollTimer === null) {
        return;
    }

    window.clearInterval(dashboardPollTimer);
    dashboardPollTimer = null;
};

const startDashboardPolling = (intervalMs) => {
    if (dashboardPollTimer !== null) {
        window.clearInterval(dashboardPollTimer);
    }

    dashboardPollTimer = window.setInterval(() => {
        if (document.hidden) {
            return;
        }

        reloadDashboardRealtime();
    }, intervalMs);
};

const enablePollingFallback = () => {
    if (usePollingFallback) {
        return;
    }

    usePollingFallback = true;
    realtimeMode.value = 'polling';
    startDashboardPolling(document.hidden ? dashboardSyncIntervalHiddenMs : dashboardSyncIntervalActiveMs);
};

const disablePollingFallback = () => {
    if (!usePollingFallback) {
        return;
    }

    usePollingFallback = false;
    stopDashboardPolling();
};

const handlePageFocus = () => {
    if (!usePollingFallback) {
        return;
    }

    reloadDashboardRealtime();
};

const handleVisibilityChange = () => {
    if (!usePollingFallback) {
        return;
    }

    if (!document.hidden) {
        startDashboardPolling(dashboardSyncIntervalActiveMs);
        reloadDashboardRealtime();
        return;
    }

    startDashboardPolling(dashboardSyncIntervalHiddenMs);
};

onMounted(() => {
    syncTheme();
    lastRealtimeSyncAt.value = new Date().toISOString();
    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(() => {
            chartsReady.value = true;
        }, { timeout: 1200 });
    } else {
        chartsBootTimer = window.setTimeout(() => {
            chartsBootTimer = null;
            chartsReady.value = true;
        }, 320);
    }

    if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
        themeObserver = new MutationObserver(syncTheme);
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }

    window.addEventListener('focus', handlePageFocus);
    document.addEventListener('visibilitychange', handleVisibilityChange);

    const bootRealtime = async () => {
        realtimeMode.value = 'connecting';
        dashboardEcho = await getEcho();

        if (!dashboardEcho) {
            realtimeMode.value = 'polling';
            startDashboardPolling(document.hidden ? dashboardSyncIntervalHiddenMs : dashboardSyncIntervalActiveMs);
            return;
        }

        disablePollingFallback();
        realtimeMode.value = 'live';

        const dashboardChannel = dashboardEcho.private('dashboard.global');

        dashboardChannel.listen('.dashboard.updated', (eventPayload) => {
            if (eventPayload?.updatedAt) {
                lastRealtimeEventAt.value = eventPayload.updatedAt;
            } else {
                lastRealtimeEventAt.value = new Date().toISOString();
            }

            scheduleRealtimeReload();
        });

        if (typeof dashboardChannel.error === 'function') {
            dashboardChannel.error(() => {
                enablePollingFallback();
            });
        }

        const nextConnection = dashboardEcho?.connector?.pusher?.connection ?? null;
        if (nextConnection && typeof nextConnection.bind === 'function') {
            echoConnection = nextConnection;
            echoConnectionStateHandler = ({ current }) => {
                const state = String(current || '').toLowerCase();

                if (state === 'connected') {
                    realtimeMode.value = 'live';
                    disablePollingFallback();
                    scheduleRealtimeReload();
                    return;
                }

                if (state === 'connecting' || state === 'initialized') {
                    realtimeMode.value = 'connecting';
                    return;
                }

                enablePollingFallback();
            };

            echoConnection.bind('state_change', echoConnectionStateHandler);
        }
    };

    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(() => {
            void bootRealtime();
        }, { timeout: 1400 });
    } else {
        realtimeBootTimer = window.setTimeout(() => {
            realtimeBootTimer = null;
            void bootRealtime();
        }, 900);
    }
});

onUnmounted(() => {
    if (chartsBootTimer !== null) {
        window.clearTimeout(chartsBootTimer);
        chartsBootTimer = null;
    }

    if (realtimeRefreshTimer !== null) {
        window.clearTimeout(realtimeRefreshTimer);
        realtimeRefreshTimer = null;
    }

    if (realtimeBootTimer !== null) {
        window.clearTimeout(realtimeBootTimer);
        realtimeBootTimer = null;
    }

    stopDashboardPolling();

    window.removeEventListener('focus', handlePageFocus);
    document.removeEventListener('visibilitychange', handleVisibilityChange);

    if (dashboardEcho) {
        dashboardEcho.leave('dashboard.global');
        dashboardEcho = null;
    }

    if (echoConnection && echoConnectionStateHandler && typeof echoConnection.unbind === 'function') {
        echoConnection.unbind('state_change', echoConnectionStateHandler);
    }
    echoConnection = null;
    echoConnectionStateHandler = null;

    themeObserver?.disconnect();
    themeObserver = null;
});

const fmt = (v) => Number(v || 0).toLocaleString();
const pct = (v) => `${Number(v || 0).toFixed(1)}%`;
const monthShortNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const formatReadableDateLabel = (rawLabel) => {
    const text = String(rawLabel ?? '').trim();
    if (!text) return '';

    const withWeekday = text.match(/^(?:[A-Za-z]{3,9})\s+(\d{1,2})\/(\d{1,2})(?:\/\d{2,4})?$/);
    if (withWeekday) {
        const day = Number(withWeekday[1]);
        const monthIndex = Number(withWeekday[2]) - 1;
        return `${day} ${monthShortNames[monthIndex] || withWeekday[2]}`;
    }

    const shortDate = text.match(/^(\d{1,2})\/(\d{1,2})(?:\/\d{2,4})?$/);
    if (shortDate) {
        const day = Number(shortDate[1]);
        const monthIndex = Number(shortDate[2]) - 1;
        return `${day} ${monthShortNames[monthIndex] || shortDate[2]}`;
    }

    const isoDate = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (isoDate) {
        const day = Number(isoDate[3]);
        const monthIndex = Number(isoDate[2]) - 1;
        return `${day} ${monthShortNames[monthIndex] || isoDate[2]}`;
    }

    return text;
};
const chartTheme = computed(() => (
    isLightTheme.value
        ? {
            axis: '#1f2937',
            legend: '#111827',
            strong: '#101828',
            grid: 'rgba(15,23,42,0.18)',
            gridSoft: 'rgba(15,23,42,0.12)',
            tooltipBg: 'rgba(255,255,255,1)',
            tooltipTitle: '#101828',
            tooltipBody: '#1f2937',
            tooltipBorder: 'rgba(15,23,42,0.2)',
            ok: '#16a34a',
            okFill: 'rgba(22,163,74,0.2)',
            ng: '#e11d48',
            ngFill: 'rgba(225,29,72,0.16)',
            bar: 'rgba(148,163,184,0.38)',
            barBorder: 'rgba(100,116,139,0.46)',
            axisRight: '#15803d',
        }
        : {
            axis: '#a8a29e',
            legend: '#e7e5e4',
            strong: '#fafaf9',
            grid: 'rgba(255,255,255,0.06)',
            gridSoft: 'rgba(255,255,255,0.1)',
            tooltipBg: 'rgba(10,10,10,0.95)',
            tooltipTitle: '#fafaf9',
            tooltipBody: '#f5f5f4',
            tooltipBorder: 'rgba(251,146,60,0.2)',
            ok: '#22c55e',
            okFill: 'rgba(34,197,94,0.18)',
            ng: '#ef4444',
            ngFill: 'rgba(239,68,68,0.10)',
            bar: 'rgba(16,185,129,0.32)',
            barBorder: 'rgba(34,197,94,0.42)',
            axisRight: '#4ade80',
        }
));

/* โ”€โ”€ KPI cards โ”€โ”€ */
const kpiCards = computed(() => ([
    { label: 'OK %', value: pct(props.metrics.yieldRate), accent: true },
    { label: 'NG %', value: pct(props.metrics.defectRate), accent: false, danger: true },
    { label: 'Jobs', value: fmt(props.metrics.periodJobs) },
    { label: 'Total Tests', value: fmt(props.metrics.totalTests) },
    { label: 'Today Tests', value: fmt(props.metrics.todayCount), icon: true },
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
        x: { ticks: { color: chartTheme.value.axis, maxRotation: 0, minRotation: 0, autoSkip: true, maxTicksLimit: 7 }, grid: { display: false }, border: { display: false } },
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
    return src.map((d) => ({ label: formatReadableDateLabel(d.label), ok: Number(d.ok || 0), ng: Number(d.ng || 0) }));
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
        {
            label: 'OK',
            data: monthlySeries.value.map((m) => m.yield),
            borderColor: chartTheme.value.ok,
            backgroundColor: chartTheme.value.okFill,
            fill: true,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 5,
        },
        {
            label: 'NG',
            data: monthlySeries.value.map((m) => m.ngRate),
            borderColor: chartTheme.value.ng,
            backgroundColor: chartTheme.value.ngFill,
            fill: true,
            tension: 0.35,
            pointRadius: 2,
            pointHoverRadius: 5,
        },
    ],
}));

const monthlyTrendOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { labels: { color: chartTheme.value.legend, usePointStyle: true, padding: 14 } },
        tooltip: {
            ...tooltipStyle.value,
            callbacks: {
                title: (items) => {
                    const idx = items?.[0]?.dataIndex ?? -1;
                    return monthlySeries.value[idx]?.fullLabel || items?.[0]?.label || '';
                },
                label: (ctx) => `${ctx.dataset.label}: ${Number(ctx.parsed.y || 0).toFixed(1)}%`,
            },
        },
    },
    scales: {
        x: { ticks: { color: chartTheme.value.axis, maxRotation: 0, minRotation: 0 }, grid: { display: false }, border: { display: false } },
        y: {
            beginAtZero: true,
            min: 0,
            max: 100,
            ticks: { color: chartTheme.value.axisRight, stepSize: 10, maxTicksLimit: 6, callback: (v) => `${v}%` },
            grid: { color: chartTheme.value.gridSoft },
            border: { display: false },
        },
    },
}));

/* โ”€โ”€ Weekly bar โ”€โ”€ */
const hasFourWeekData = computed(() => Array.isArray(props.fourWeekData) && props.fourWeekData.length > 0);
const weeklyCardTitle = computed(() => (hiddenWeekCount.value > 0 ? 'Recent Active Weeks OK / NG' : hasFourWeekData.value ? 'Last 4 Weeks OK / NG' : 'Weekly OK / NG'));

const fourWeekSeries = computed(() => {
    if (hasFourWeekData.value) {
        return props.fourWeekData;
    }

    if (Array.isArray(props.weeklyData) && props.weeklyData.length > 0) {
        return props.weeklyData.map((day) => ({
            label: formatReadableDateLabel(day.label),
            ok: Number(day.ok || 0),
            ng: Number(day.ng || 0),
        }));
    }

    return [{ label: 'No data', ok: 0, ng: 0 }];
});

const visibleWeeklySeries = computed(() => {
    if (!hasFourWeekData.value) {
        return fourWeekSeries.value;
    }

    const activeWeeks = fourWeekSeries.value.filter((week) => (Number(week.ok || 0) + Number(week.ng || 0)) > 0);
    return activeWeeks.length ? activeWeeks : fourWeekSeries.value;
});

const hiddenWeekCount = computed(() => {
    if (!hasFourWeekData.value) {
        return 0;
    }

    return Math.max(0, fourWeekSeries.value.length - visibleWeeklySeries.value.length);
});

const weeklyCardNote = computed(() => (hiddenWeekCount.value > 0 ? `${hiddenWeekCount.value} week${hiddenWeekCount.value > 1 ? 's' : ''} had no tests` : ''));

const weeklyBarData = computed(() => ({
    labels: visibleWeeklySeries.value.map((d) => d.label),
    datasets: [
        {
            label: 'OK',
            data: visibleWeeklySeries.value.map((d) => Number(d.ok || 0)),
            backgroundColor: chartTheme.value.ok,
            borderRadius: 6,
            maxBarThickness: 28,
            barPercentage: 0.7,
            categoryPercentage: 0.7,
        },
        {
            label: 'NG',
            data: visibleWeeklySeries.value.map((d) => Number(d.ng || 0)),
            backgroundColor: chartTheme.value.ng,
            borderRadius: 6,
            maxBarThickness: 28,
            barPercentage: 0.7,
            categoryPercentage: 0.7,
        },
    ],
}));

const weeklyBarOpts = computed(() => ({
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: chartTheme.value.legend, usePointStyle: true, pointStyle: 'circle', padding: 12 } }, tooltip: tooltipStyle.value },
    scales: {
        x: { stacked: hasFourWeekData.value, ticks: { color: chartTheme.value.axis, maxRotation: 0, minRotation: 0, autoSkip: true, maxTicksLimit: 7 }, grid: { display: false }, border: { display: false } },
        y: { stacked: hasFourWeekData.value, beginAtZero: true, ticks: { color: chartTheme.value.axis, precision: 0 }, grid: { color: chartTheme.value.grid }, border: { display: false } },
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
            label: 'OK Change',
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
                    <div class="db-header__status">
                        <div class="db-badge">
                            <span class="db-badge__dot"></span>
                            {{ currentPeriodLabel }}
                        </div>
                        <div class="db-status" :data-mode="realtimeMode">
                            <span class="db-status__dot"></span>
                            <span>{{ realtimeStatusLabel }}</span>
                            <span v-if="realtimeStatusNote" class="db-status__note">{{ realtimeStatusNote }}</span>
                        </div>
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
                        <Doughnut v-if="chartsReady" :data="qualityChartData" :options="doughnutOpts" />
                        <div v-else class="shimmer shimmer--chart"></div>
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
                    <div class="chart-area">
                        <Line v-if="chartsReady" :data="dailyTrendData" :options="lineOpts" />
                        <div v-else class="shimmer shimmer--chart"></div>
                    </div>
                </article>
            </section>

            <!-- โ•โ•โ• ROW: Monthly Trend + Forecast & Weekly โ•โ•โ• -->
            <section class="chart-row chart-row--bottom">
                <article class="card card--chart card--monthly">
                    <div class="card__head">Monthly OK / NG Trend</div>
                    <div class="chart-area chart-area--tall">
                        <Line v-if="chartsReady" :data="monthlyTrendData" :options="monthlyTrendOpts" />
                        <div v-else class="shimmer shimmer--chart"></div>
                    </div>

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
                        <div class="card__head">{{ weeklyCardTitle }}</div>
                        <div v-if="weeklyCardNote" class="card__note">{{ weeklyCardNote }}</div>
                        <div class="chart-area chart-area--short">
                            <Bar v-if="chartsReady" :data="weeklyBarData" :options="weeklyBarOpts" />
                            <div v-else class="shimmer shimmer--chart"></div>
                        </div>
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
                                <div class="lb-meta">{{ fmt(ins.total) }} tests | {{ fmt(ins.ok) }} OK | {{ fmt(ins.ng) }} NG</div>
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
.db-header__status {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
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
.db-status {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.14);
    background: rgba(255,255,255,0.02);
    font-size: 0.78rem;
    font-weight: 600;
    color: #e7e5e4;
}
.db-status__dot {
    width: 0.46rem;
    height: 0.46rem;
    border-radius: 50%;
    background: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245,158,11,0.2);
    flex-shrink: 0;
}
.db-status__note {
    color: #a8a29e;
    font-size: 0.72rem;
    font-weight: 500;
}
.db-status[data-mode='live'] {
    border-color: rgba(34,197,94,0.38);
    background: rgba(22,163,74,0.12);
    color: #bbf7d0;
}
.db-status[data-mode='live'] .db-status__dot {
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34,197,94,0.22);
}
.db-status[data-mode='polling'] {
    border-color: rgba(59,130,246,0.38);
    background: rgba(37,99,235,0.12);
    color: #bfdbfe;
}
.db-status[data-mode='polling'] .db-status__dot {
    background: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59,130,246,0.22);
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
.card__note {
    margin-top: -0.45rem;
    margin-bottom: 0.6rem;
    font-size: 0.76rem;
    color: #a8a29e;
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
.shimmer--chart { height: 100%; border-radius: 14px; }
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

:global(.theme-shell[data-theme='light'] .db-status) {
    border-color: rgba(15, 23, 42, 0.16);
    background: rgba(255, 255, 255, 0.98);
    color: #1f2937;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
}

:global(.theme-shell[data-theme='light'] .db-status__note) {
    color: #64748b;
}

:global(.theme-shell[data-theme='light'] .db-status[data-mode='live']) {
    border-color: rgba(22, 163, 74, 0.34);
    background: rgba(240, 253, 244, 0.98);
    color: #166534;
}

:global(.theme-shell[data-theme='light'] .db-status[data-mode='polling']) {
    border-color: rgba(37, 99, 235, 0.3);
    background: rgba(239, 246, 255, 0.98);
    color: #1e40af;
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
:global(.theme-shell[data-theme='light'] .card__note),
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
    border-width: 2px !important;
    border-color: rgba(34, 197, 94, 0.52) !important;
    background: linear-gradient(135deg, rgba(240, 253, 244, 0.98), rgba(236, 253, 245, 0.98)) !important;
    box-shadow:
        inset 0 0 0 1px rgba(34, 197, 94, 0.12),
        0 0 0 3px rgba(34, 197, 94, 0.14),
        0 14px 28px rgba(22, 163, 74, 0.16) !important;
}

:global(.theme-shell[data-theme='light'] .kpi--danger) {
    border-width: 2px !important;
    border-color: rgba(239, 68, 68, 0.52) !important;
    background: linear-gradient(135deg, rgba(255, 241, 242, 0.98), rgba(255, 248, 250, 0.98)) !important;
    box-shadow:
        inset 0 0 0 1px rgba(239, 68, 68, 0.12),
        0 0 0 3px rgba(239, 68, 68, 0.14),
        0 14px 28px rgba(220, 38, 38, 0.14) !important;
}

:global(.theme-shell[data-theme='light'] .kpi--accent:hover) {
    border-color: rgba(22, 163, 74, 0.7) !important;
    background: linear-gradient(135deg, rgba(236, 253, 245, 1), rgba(220, 252, 231, 0.98)) !important;
}

:global(.theme-shell[data-theme='light'] .kpi--danger:hover) {
    border-color: rgba(220, 38, 38, 0.68) !important;
    background: linear-gradient(135deg, rgba(255, 228, 230, 0.98), rgba(255, 241, 242, 0.98)) !important;
}

:global(.theme-shell[data-theme='light'] .kpi--accent .kpi__label) {
    color: #15803d;
}

:global(.theme-shell[data-theme='light'] .fc--accent) {
    border-color: rgba(29, 78, 216, 0.24) !important;
    background: linear-gradient(180deg, rgba(219, 234, 254, 0.94), rgba(239, 246, 255, 0.98)) !important;
}

:global(.theme-shell[data-theme='dark'] .fc--accent) {
    border-color: rgba(255, 255, 255, 0.07) !important;
    background: rgba(255, 255, 255, 0.03) !important;
    box-shadow: none !important;
}

:global(.theme-shell[data-theme='dark'] .fc--accent .fc__label) {
    color: rgba(255, 255, 255, 0.5) !important;
}

:global(.theme-shell[data-theme='dark'] .card--forecast:hover) {
    border-color: rgba(255, 255, 255, 0.08) !important;
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
    color: #15803d;
}

:global(.theme-shell[data-theme='light'] .lb-bar__fill) {
    background: linear-gradient(90deg, #16a34a, #4ade80);
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
    background: #16a34a;
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
