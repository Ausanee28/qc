<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({ inspectors: Array, details: Array });

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
        x: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 10 }, callback: v => v + 'm' } },
        y: { grid: { display: false }, ticks: { font: { size: 12, weight: '600' } } }
    },
    plugins: { legend: { display: false } }
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
        backgroundColor: props.inspectors.map((_, idx) => `rgba(16,185,129,${0.7 - idx * 0.15})`),
        borderRadius: 6, borderSkipped: false
    }]
});

const slowChartData = () => ({
    labels: props.inspectors.map(i => i.name),
    datasets: [{
        data: props.inspectors.map(i => Math.round((i.max_sec || 0) / 60)),
        backgroundColor: props.inspectors.map((_, idx) => `rgba(239,68,68,${0.7 - idx * 0.15})`),
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

        <div v-if="!inspectors.length" style="padding:40px;text-align:center;color:#9CA3AF;font-size:13px;background:#fff;border-radius:8px;border:1px solid #E5E7EB">
            No test data yet.
        </div>

        <template v-else>
            <!-- Inspector Cards Grid -->
            <div class="perf-grid">
                <div v-for="insp in inspectors" :key="insp.id" class="card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                        <div class="avatar">{{ insp.name.charAt(0) }}</div>
                        <div>
                            <div style="font-size:14px;font-weight:600">{{ insp.name }}</div>
                            <div style="font-size:11px;color:#9CA3AF">{{ insp.total_tests }} tests total</div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:12px">
                        <div class="stat-mini">
                            <div class="val" style="color:#D97706">{{ fmtMin(insp.avg_sec) }}</div>
                            <div class="lbl">Average</div>
                        </div>
                        <div class="stat-mini" style="background:#ECFDF5">
                            <div class="val" style="color:#059669">{{ fmtMin(insp.min_sec) }}</div>
                            <div class="lbl">Fastest</div>
                        </div>
                        <div class="stat-mini" style="background:#FEF2F2">
                            <div class="val" style="color:#DC2626">{{ fmtMin(insp.max_sec) }}</div>
                            <div class="lbl">Slowest</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px">
                        <div style="flex:1;background:#E5E7EB;border-radius:4px;height:5px">
                            <div :style="{ background:'#10B981', height:'5px', borderRadius:'4px', width: (insp.total_tests > 0 ? Math.round(insp.ok_cnt / insp.total_tests * 100) : 0) + '%' }"></div>
                        </div>
                        <span style="font-size:10px;color:#059669;font-weight:600">{{ insp.ok_cnt }} OK</span>
                        <span style="font-size:10px;color:#DC2626;font-weight:600">{{ insp.ng_cnt }} NG</span>
                    </div>
                </div>
            </div>

            <!-- Performance Charts Row (3 columns) -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px">
                <div class="card">
                    <div class="card-title" style="color:#D97706">⏱️ Average Duration</div>
                    <div class="card-desc">Mean time per test (lower = faster)</div>
                    <div style="height:180px"><Bar :data="avgChartData()" :options="perfOpts" /></div>
                </div>
                <div class="card">
                    <div class="card-title" style="color:#059669">⚡ Fastest Time</div>
                    <div class="card-desc">Best (shortest) test time recorded</div>
                    <div style="height:180px"><Bar :data="fastChartData()" :options="perfOpts" /></div>
                </div>
                <div class="card">
                    <div class="card-title" style="color:#DC2626">🐢 Slowest Time</div>
                    <div class="card-desc">Worst (longest) test time recorded</div>
                    <div style="height:180px"><Bar :data="slowChartData()" :options="perfOpts" /></div>
                </div>
            </div>

            <!-- Test Duration History Table -->
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <div class="card-title" style="margin:0">Test Duration History</div>
                    <span style="font-size:11px;color:#9CA3AF">Last 50 tests</span>
                </div>
                <div class="tbl">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Inspector</th>
                                <th>DMC</th>
                                <th>Equipment</th>
                                <th>Duration</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in details" :key="d.detail_id">
                                <td style="font-family:monospace;color:#4F46E5;font-weight:700">#{{ d.detail_id }}</td>
                                <td>{{ d.inspector }}</td>
                                <td>{{ d.dmc || '—' }}</td>
                                <td>{{ d.equipment_name }}</td>
                                <td style="font-weight:700;color:#D97706">{{ fmt(d.duration_sec) }}</td>
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
