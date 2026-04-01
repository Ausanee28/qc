<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Bar } from '@/lib/performance-charts';

const props = defineProps({ inspectors: Array, details: Array });

const inspectorRows = computed(() => props.inspectors ?? []);
const detailRows = computed(() => props.details ?? []);
const currentTheme = ref('dark');

const syncTheme = () => {
    if (typeof document === 'undefined') {
        return;
    }

    currentTheme.value = document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
};

let themeObserver = null;

const fmt = (sec) => {
    if (!sec || sec < 0) return '-';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;

    return h > 0 ? `${h}h ${m}m` : m > 0 ? `${m}m ${s}s` : `${s}s`;
};

const fmtMin = (sec) => {
    if (!sec || sec < 0) return '-';

    return `${Math.round(sec / 60)}m`;
};

const perfOpts = computed(() => {
    const isLight = currentTheme.value === 'light';

    return {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                beginAtZero: true,
                grid: { color: isLight ? 'rgba(120,53,15,0.10)' : 'rgba(255,255,255,0.08)' },
                border: { color: isLight ? 'rgba(120,53,15,0.12)' : 'rgba(255,255,255,0.1)' },
                ticks: { color: isLight ? '#1c1917' : '#a8a29e', font: { size: 10 }, callback: (v) => `${v}m` },
            },
            y: {
                grid: { display: false },
                border: { display: false },
                ticks: { color: isLight ? '#1c1917' : '#e7e5e4', font: { size: 12, weight: '600' } },
            },
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: isLight ? 'rgba(255,250,245,0.98)' : 'rgba(15,15,15,0.96)',
                borderColor: isLight ? 'rgba(234,88,12,0.18)' : 'rgba(251,146,60,0.22)',
                borderWidth: 1,
                titleColor: isLight ? '#1c1917' : '#fafaf9',
                bodyColor: isLight ? '#1c1917' : '#d6d3d1',
                displayColors: false,
            },
        },
    };
});

const avgChartData = computed(() => ({
    labels: inspectorRows.value.map((inspector) => inspector.name),
    datasets: [{
        data: inspectorRows.value.map((inspector) => Math.round((inspector.avg_sec || 0) / 60)),
        backgroundColor: inspectorRows.value.map((_, index) => currentTheme.value === 'light'
            ? `rgba(245,158,11,${0.86 - index * 0.12})`
            : `rgba(245,158,11,${0.7 - index * 0.15})`),
        borderRadius: 8,
        borderSkipped: false,
    }],
}));

const fastChartData = computed(() => ({
    labels: inspectorRows.value.map((inspector) => inspector.name),
    datasets: [{
        data: inspectorRows.value.map((inspector) => Math.round((inspector.min_sec || 0) / 60)),
        backgroundColor: inspectorRows.value.map((_, index) => currentTheme.value === 'light'
            ? `rgba(251,146,60,${0.78 - index * 0.1})`
            : `rgba(251,146,60,${0.68 - index * 0.12})`),
        borderRadius: 8,
        borderSkipped: false,
    }],
}));

const slowChartData = computed(() => ({
    labels: inspectorRows.value.map((inspector) => inspector.name),
    datasets: [{
        data: inspectorRows.value.map((inspector) => Math.round((inspector.max_sec || 0) / 60)),
        backgroundColor: inspectorRows.value.map((_, index) => currentTheme.value === 'light'
            ? `rgba(146,96,61,${0.88 - index * 0.08})`
            : `rgba(120,53,15,${0.76 - index * 0.12})`),
        borderRadius: 8,
        borderSkipped: false,
    }],
}));

onMounted(() => {
    syncTheme();

    if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
        themeObserver = new MutationObserver(syncTheme);
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }
});

onUnmounted(() => {
    themeObserver?.disconnect();
});
</script>

<template>
    <Head title="Performance" />
    <AuthenticatedLayout>
        <template #title>Performance</template>

        <div class="pg-header performance-page__header">
            <div>
                <h1 class="pg-title">Performance</h1>
                <p class="pg-sub">Inspector test duration analysis (end time - start time)</p>
            </div>
        </div>

        <div v-if="!inspectorRows.length" class="performance-empty">
            No test data yet.
        </div>

        <div v-else class="performance-page">
            <div class="perf-grid performance-page__top-grid">
                <article v-for="insp in inspectorRows" :key="insp.id" class="card performance-card">
                    <div class="performance-card__head">
                        <div class="performance-card__avatar">{{ insp.name.charAt(0) }}</div>
                        <div class="performance-card__identity">
                            <div class="performance-card__name">{{ insp.name }}</div>
                            <div class="performance-card__meta">{{ insp.total_tests }} tests total</div>
                        </div>
                    </div>

                    <div class="performance-card__stats">
                        <div class="stat-mini performance-stat performance-stat--average">
                            <div class="val performance-stat__value">{{ fmtMin(insp.avg_sec) }}</div>
                            <div class="lbl">Average</div>
                        </div>
                        <div class="stat-mini performance-stat performance-stat--fastest">
                            <div class="val performance-stat__value">{{ fmtMin(insp.min_sec) }}</div>
                            <div class="lbl">Fastest</div>
                        </div>
                        <div class="stat-mini performance-stat performance-stat--slowest">
                            <div class="val performance-stat__value">{{ fmtMin(insp.max_sec) }}</div>
                            <div class="lbl">Slowest</div>
                        </div>
                    </div>

                    <div class="performance-card__progress-row">
                        <div class="performance-card__progress-track">
                            <div
                                class="performance-card__progress-fill"
                                :style="{ width: (insp.total_tests > 0 ? Math.round((insp.ok_cnt / insp.total_tests) * 100) : 0) + '%' }"
                            ></div>
                        </div>
                        <span class="performance-card__result performance-card__result--ok">{{ insp.ok_cnt }} OK</span>
                        <span class="performance-card__result performance-card__result--ng">{{ insp.ng_cnt }} NG</span>
                    </div>
                </article>
            </div>

            <div class="performance-chart-grid">
                <section class="card performance-panel">
                    <div class="card-title performance-panel__title performance-panel__title--average">Average Duration</div>
                    <div class="card-desc">Mean time per test (lower = faster)</div>
                    <div class="performance-panel__chart">
                        <Bar :data="avgChartData" :options="perfOpts" />
                    </div>
                </section>

                <section class="card performance-panel">
                    <div class="card-title performance-panel__title performance-panel__title--fastest">Fastest Time</div>
                    <div class="card-desc">Best (shortest) test time recorded</div>
                    <div class="performance-panel__chart">
                        <Bar :data="fastChartData" :options="perfOpts" />
                    </div>
                </section>

                <section class="card performance-panel">
                    <div class="card-title performance-panel__title performance-panel__title--slowest">Slowest Time</div>
                    <div class="card-desc">Worst (longest) test time recorded</div>
                    <div class="performance-panel__chart">
                        <Bar :data="slowChartData" :options="perfOpts" />
                    </div>
                </section>
            </div>

            <section class="card performance-history">
                <div class="performance-history__head">
                    <div class="card-title performance-history__title">Test Duration History</div>
                    <span class="performance-history__hint">Last 50 tests</span>
                </div>

                <div class="tbl">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Inspector</th>
                                <th>DMC</th>
                                <th>Detail</th>
                                <th>Duration</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="detail in detailRows" :key="detail.detail_id">
                                <td class="performance-history__id">#{{ detail.detail_id }}</td>
                                <td>{{ detail.inspector }}</td>
                                <td>{{ detail.dmc || '-' }}</td>
                                <td>{{ detail.detail }}</td>
                                <td class="performance-history__duration">{{ fmt(detail.duration_sec) }}</td>
                                <td>
                                    <span v-if="detail.judgement === 'OK'" class="pill pill-g">OK</span>
                                    <span v-else-if="detail.judgement === 'NG'" class="pill pill-r">NG</span>
                                    <span v-else class="pill pill-y">{{ detail.judgement }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.performance-page {
    --perf-card-bg: linear-gradient(180deg, rgba(20, 16, 13, 0.95), rgba(12, 10, 9, 0.97));
    --perf-card-border: rgba(251, 146, 60, 0.12);
    --perf-card-shadow: 0 18px 40px rgba(0, 0, 0, 0.24);
    --perf-subtle-bg: rgba(255, 255, 255, 0.04);
    --perf-subtle-border: rgba(255, 255, 255, 0.08);
    --perf-soft-text: #a8a29e;
    --perf-strong-text: #f5f5f4;
    --perf-avatar-bg: linear-gradient(135deg, rgba(251, 146, 60, 0.16), rgba(194, 65, 12, 0.18));
    --perf-average-bg: linear-gradient(180deg, rgba(35, 28, 24, 0.92), rgba(26, 21, 18, 0.96));
    --perf-fastest-bg: linear-gradient(180deg, rgba(56, 33, 17, 0.72), rgba(28, 18, 12, 0.78));
    --perf-slowest-bg: linear-gradient(180deg, rgba(68, 64, 60, 0.55), rgba(41, 37, 36, 0.72));
    --perf-progress-track: rgba(255, 255, 255, 0.08);
    --perf-ok-text: #fdba74;
    --perf-ng-text: #d6d3d1;
    display: grid;
    gap: 1.35rem;
}

.performance-empty {
    padding: 40px;
    text-align: center;
    color: #a8a29e;
    font-size: 13px;
    background: rgba(18, 18, 18, 0.92);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.performance-page__top-grid {
    margin-bottom: 0;
}

.performance-card,
.performance-panel,
.performance-history {
    background: var(--perf-card-bg);
    border-color: var(--perf-card-border);
    box-shadow: var(--perf-card-shadow);
    padding: 1.2rem;
}

.performance-card__head,
.performance-history__head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.performance-history__head {
    justify-content: space-between;
}

.performance-card__avatar {
    width: 2.65rem;
    height: 2.65rem;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--perf-avatar-bg);
    color: #fb923c;
    font-size: 1rem;
    font-weight: 700;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
}

.performance-card__identity {
    display: grid;
    gap: 0.15rem;
}

.performance-card__name,
.performance-history__title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--perf-strong-text);
}

.performance-card__meta,
.performance-history__hint {
    font-size: 0.78rem;
    color: var(--perf-soft-text);
}

.performance-card__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.65rem;
    margin-bottom: 1rem;
}

.performance-stat {
    border-radius: 14px;
    border: 1px solid var(--perf-subtle-border);
    background: var(--perf-subtle-bg);
}

.performance-stat--average {
    background: var(--perf-average-bg);
}

.performance-stat--fastest {
    background: var(--perf-fastest-bg);
    border-color: rgba(251, 146, 60, 0.16);
}

.performance-stat--slowest {
    background: var(--perf-slowest-bg);
}

.performance-stat__value {
    color: #fb923c;
}

.performance-stat--fastest .performance-stat__value {
    color: #fdba74;
}

.performance-stat--slowest .performance-stat__value {
    color: #f5f5f4;
}

.performance-card__progress-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.performance-card__progress-track {
    flex: 1;
    height: 0.45rem;
    border-radius: 999px;
    background: var(--perf-progress-track);
    overflow: hidden;
}

.performance-card__progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #fb923c, #ea580c);
}

.performance-card__result {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.performance-card__result--ok {
    color: var(--perf-ok-text);
}

.performance-card__result--ng {
    color: var(--perf-ng-text);
}

.performance-chart-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.performance-panel__title {
    margin-bottom: 0.25rem;
}

.performance-panel__title--average {
    color: #fb923c;
}

.performance-panel__title--fastest {
    color: #fdba74;
}

.performance-panel__title--slowest {
    color: #f5f5f4;
}

.performance-panel__chart {
    height: 180px;
}

.performance-history__id {
    font-family: monospace;
    color: #fb923c;
    font-weight: 700;
}

.performance-history__duration {
    font-weight: 700;
    color: #fb923c;
}

:global(.theme-shell[data-theme='light']) .performance-page {
    --perf-card-bg: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 247, 243, 0.96));
    --perf-card-border: rgba(68, 64, 60, 0.12);
    --perf-card-shadow: 0 22px 40px rgba(24, 24, 27, 0.06);
    --perf-subtle-bg: rgba(255, 255, 255, 0.96);
    --perf-subtle-border: rgba(68, 64, 60, 0.12);
    --perf-soft-text: #57534e;
    --perf-strong-text: #18181b;
    --perf-avatar-bg: linear-gradient(135deg, rgba(251, 146, 60, 0.18), rgba(255, 237, 213, 0.98));
    --perf-average-bg: linear-gradient(180deg, rgba(41, 37, 36, 0.96), rgba(28, 25, 23, 0.98));
    --perf-fastest-bg: linear-gradient(180deg, rgba(120, 53, 15, 0.78), rgba(87, 39, 11, 0.86));
    --perf-slowest-bg: linear-gradient(180deg, rgba(161, 161, 170, 0.92), rgba(113, 113, 122, 0.96));
    --perf-progress-track: rgba(68, 64, 60, 0.12);
    --perf-ok-text: #c2410c;
    --perf-ng-text: #78716c;
}

:global(.theme-shell[data-theme='light']) .performance-empty {
    color: #18181b;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 247, 243, 0.96));
    border-color: rgba(68, 64, 60, 0.12);
    box-shadow: 0 18px 40px rgba(24, 24, 27, 0.06);
}

:global(.theme-shell[data-theme='light']) .performance-stat__value,
:global(.theme-shell[data-theme='light']) .performance-history__id,
:global(.theme-shell[data-theme='light']) .performance-history__duration {
    color: #ea580c;
}

:global(.theme-shell[data-theme='light']) .performance-card__meta,
:global(.theme-shell[data-theme='light']) .performance-history__hint,
:global(.theme-shell[data-theme='light']) .performance-stat .lbl {
    color: #57534e;
}

:global(.theme-shell[data-theme='light']) .performance-stat--average .performance-stat__value,
:global(.theme-shell[data-theme='light']) .performance-stat--fastest .performance-stat__value {
    color: #fb923c;
}

:global(.theme-shell[data-theme='light']) .performance-stat--slowest .performance-stat__value,
:global(.theme-shell[data-theme='light']) .performance-stat--average .lbl,
:global(.theme-shell[data-theme='light']) .performance-stat--fastest .lbl,
:global(.theme-shell[data-theme='light']) .performance-stat--slowest .lbl {
    color: #fff7ed;
}

:global(.theme-shell[data-theme='light']) .performance-panel__title--fastest {
    color: #c2410c;
}

:global(.theme-shell[data-theme='light']) .performance-panel__title--slowest {
    color: #7c4a2a;
}

@media (max-width: 1023px) {
    .performance-chart-grid {
        grid-template-columns: 1fr;
    }
}
</style>
