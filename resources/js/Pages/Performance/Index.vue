<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';

const showCharts = ref(false);
const BarChart = defineAsyncComponent(() => import('@/lib/performance-charts').then((module) => module.Bar));

const props = defineProps({ inspectors: Array, details: Array });

const inspectorRows = computed(() => props.inspectors ?? []);
const detailRows = computed(() => props.details ?? []);

onMounted(() => {
    const revealCharts = () => {
        showCharts.value = true;
    };

    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(revealCharts, { timeout: 500 });
        return;
    }

    window.setTimeout(revealCharts, 150);
});

const fmt = (sec) => {
    if (!sec || sec < 0) return '—';
    const h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
    return h > 0 ? `${h}h ${m}m` : m > 0 ? `${m}m ${s}s` : `${s}s`;
};

const fmtMin = (sec) => {
    if (!sec || sec < 0) return '—';
    return Math.round(sec / 60) + 'm';
};

// Charts matching redesign-preview.html exactly
const perfOpts = {
    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
    scales: {
        x: {
            beginAtZero: true,
            grid: { color: 'rgba(255,255,255,0.08)' },
            border: { color: 'rgba(255,255,255,0.1)' },
            ticks: { color: '#a8a29e', font: { size: 10 }, callback: v => v + 'm' }
        },
        y: {
            grid: { display: false },
            border: { display: false },
            ticks: { color: '#e7e5e4', font: { size: 12, weight: '600' } }
        }
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15,15,15,0.96)',
            borderColor: 'rgba(251,146,60,0.22)',
            borderWidth: 1,
            titleColor: '#fafaf9',
            bodyColor: '#d6d3d1',
            displayColors: false
        }
    }
};

const avgChartData = () => ({
    labels: props.inspectors.map(i => i.name),
    datasets: [{
        data: props.inspectors.map(i => Math.round((i.avg_sec || 0) / 60)),
        backgroundColor: props.inspectors.map((_, idx) => `rgba(245,158,11,${0.7 - idx * 0.15})`),
        borderRadius: 6, borderSkipped: false
    }]
});

const fastChartData = () => ({
    labels: props.inspectors.map(i => i.name),
    datasets: [{
        data: props.inspectors.map(i => Math.round((i.min_sec || 0) / 60)),
        backgroundColor: props.inspectors.map((_, idx) => `rgba(251,146,60,${0.68 - idx * 0.12})`),
        borderRadius: 6, borderSkipped: false
    }]
});

const slowChartData = () => ({
    labels: props.inspectors.map(i => i.name),
    datasets: [{
        data: props.inspectors.map(i => Math.round((i.max_sec || 0) / 60)),
        backgroundColor: props.inspectors.map((_, idx) => `rgba(120,53,15,${0.76 - idx * 0.12})`),
        borderRadius: 6, borderSkipped: false
    }]
});

const fmtDt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '-';
</script>

<template>
    <Head title="Performance" />
    <AuthenticatedLayout>
        <template #title>Performance</template>

        <div class="pg-header">
            <div>
                <h1 class="pg-title">Performance</h1>
                <p class="pg-sub">Inspector test duration analysis (end time − start time)</p>
            </div>
        </div>

        <div v-if="!inspectorRows.length" style="padding:40px;text-align:center;color:#a8a29e;font-size:13px;background:rgba(18,18,18,0.92);border-radius:16px;border:1px solid rgba(255,255,255,0.08)">
            No test data yet.
        </div>

        <template v-else>
            <!-- Inspector Cards Grid -->
            <div class="perf-grid">
                <div v-for="insp in inspectorRows" :key="insp.id" class="card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                        <div class="avatar">{{ insp.name.charAt(0) }}</div>
                        <div>
                            <div style="font-size:14px;font-weight:600">{{ insp.name }}</div>
                            <div style="font-size:11px;color:#78716c">{{ insp.total_tests }} tests total</div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:12px">
                        <div class="stat-mini">
                            <div class="val" style="color:#fb923c">{{ fmtMin(insp.avg_sec) }}</div>
                            <div class="lbl">Average</div>
                        </div>
                        <div class="stat-mini" style="background:rgba(251,146,60,0.08);border-color:rgba(251,146,60,0.14)">
                            <div class="val" style="color:#fdba74">{{ fmtMin(insp.min_sec) }}</div>
                            <div class="lbl">Fastest</div>
                        </div>
                        <div class="stat-mini" style="background:rgba(68,64,60,0.55);border-color:rgba(255,255,255,0.08)">
                            <div class="val" style="color:#e7e5e4">{{ fmtMin(insp.max_sec) }}</div>
                            <div class="lbl">Slowest</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px">
                        <div style="flex:1;background:rgba(255,255,255,0.08);border-radius:999px;height:6px;overflow:hidden">
                            <div :style="{ background:'linear-gradient(90deg,#fb923c,#ea580c)', height:'6px', borderRadius:'999px', width: (insp.total_tests > 0 ? Math.round(insp.ok_cnt / insp.total_tests * 100) : 0) + '%' }"></div>
                        </div>
                        <span style="font-size:10px;color:#fdba74;font-weight:600">{{ insp.ok_cnt }} OK</span>
                        <span style="font-size:10px;color:#d6d3d1;font-weight:600">{{ insp.ng_cnt }} NG</span>
                    </div>
                </div>
            </div>

            <!-- Performance Charts Row (3 columns) -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px">
                <div class="card">
                    <div class="card-title" style="color:#fb923c">Average Duration</div>
                    <div class="card-desc">Mean time per test (lower = faster)</div>
                    <div style="height:180px">
                        <BarChart v-if="showCharts" :data="avgChartData()" :options="perfOpts" />
                        <div v-else class="perf-skeleton"></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-title" style="color:#fdba74">Fastest Time</div>
                    <div class="card-desc">Best (shortest) test time recorded</div>
                    <div style="height:180px">
                        <BarChart v-if="showCharts" :data="fastChartData()" :options="perfOpts" />
                        <div v-else class="perf-skeleton"></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-title" style="color:#e7e5e4">Slowest Time</div>
                    <div class="card-desc">Worst (longest) test time recorded</div>
                    <div style="height:180px">
                        <BarChart v-if="showCharts" :data="slowChartData()" :options="perfOpts" />
                        <div v-else class="perf-skeleton"></div>
                    </div>
                </div>
            </div>

            <!-- Test Duration History Table -->
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <div class="card-title" style="margin:0">Test Duration History</div>
                    <span style="font-size:11px;color:#78716c">Last 50 tests</span>
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
                            <tr v-for="d in detailRows" :key="d.detail_id">
                                <td style="font-family:monospace;color:#fb923c;font-weight:700">#{{ d.detail_id }}</td>
                                <td>{{ d.inspector }}</td>
                                <td>{{ d.dmc || '—' }}</td>
                                <td>{{ d.detail }}</td>
                                <td style="font-weight:700;color:#fb923c">{{ fmt(d.duration_sec) }}</td>
                                <td>
                                    <span v-if="d.judgement === 'OK'" class="pill pill-g">OK</span>
                                    <span v-else-if="d.judgement === 'NG'" class="pill pill-r">NG</span>
                                    <span v-else class="pill pill-y">{{ d.judgement }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </AuthenticatedLayout>
</template>

<style scoped>
.perf-skeleton {
    height: 100%;
    border-radius: 18px;
    background: linear-gradient(90deg, rgba(41, 37, 36, 0.9), rgba(68, 64, 60, 0.95), rgba(41, 37, 36, 0.9));
    background-size: 200% 100%;
    animation: perf-shimmer 1.6s linear infinite;
}

@keyframes perf-shimmer {
    from {
        background-position: 200% 0;
    }

    to {
        background-position: -200% 0;
    }
}
</style>
