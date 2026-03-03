<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({ inspectors: Array, details: Array });

const fmtDuration = (sec) => {
    if (!sec || sec < 0) return '-';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    if (h > 0) return `${h}h ${m}m`;
    if (m > 0) return `${m}m ${s}s`;
    return `${s}s`;
};

const makeChartData = (insp) => ({
    labels: ['Avg', 'Fast', 'Slow'],
    datasets: [{
        data: [
            Math.round((insp.avg_sec || 0) / 60),
            Math.round((insp.min_sec || 0) / 60),
            Math.round((insp.max_sec || 0) / 60),
        ],
        backgroundColor: ['rgba(251, 191, 36, 0.85)', 'rgba(16, 185, 129, 0.85)', 'rgba(239, 68, 68, 0.85)'],
        borderRadius: 4,
        maxBarThickness: 22,
    }]
});

const miniChartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { enabled: true } },
    scales: {
        x: { ticks: { color: '#94a3b8', font: { size: 9 } }, grid: { display: false } },
        y: { ticks: { color: '#64748b', font: { size: 9 } }, grid: { color: 'rgba(51,65,85,0.2)' }, title: { display: true, text: 'min', color: '#64748b', font: { size: 9 } } },
    }
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '-';
</script>

<template>
    <Head title="Performance" />
    <AuthenticatedLayout>
        <template #title>Performance</template>

        <div v-if="!inspectors.length" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center">
            <p class="text-slate-500">No test data yet. Execute some tests to see performance here.</p>
        </div>

        <template v-else>
            <!-- Inspector Cards with integrated mini charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
                <div v-for="insp in inspectors" :key="insp.id" class="bg-slate-900/60 border border-slate-800 rounded-xl p-4 hover:border-indigo-500/30 transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs">
                                {{ insp.name.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-white font-bold text-sm">{{ insp.name }}</p>
                                <p class="text-[10px] text-slate-500 font-mono">{{ insp.total_tests }} tests</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded">{{ insp.ok_cnt }} OK</span>
                            <span class="text-[10px] font-bold text-red-400 bg-red-500/10 px-1.5 py-0.5 rounded">{{ insp.ng_cnt }} NG</span>
                        </div>
                    </div>

                    <!-- Stats row -->
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="bg-slate-800/50 rounded-lg p-2 text-center">
                            <p class="text-sm font-extrabold text-amber-400 font-mono leading-tight">{{ fmtDuration(insp.avg_sec) }}</p>
                            <p class="text-[9px] text-slate-500">Average</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-lg p-2 text-center">
                            <p class="text-sm font-extrabold text-emerald-400 font-mono leading-tight">{{ fmtDuration(insp.min_sec) }}</p>
                            <p class="text-[9px] text-slate-500">Fastest</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-lg p-2 text-center">
                            <p class="text-sm font-extrabold text-red-400 font-mono leading-tight">{{ fmtDuration(insp.max_sec) }}</p>
                            <p class="text-[9px] text-slate-500">Slowest</p>
                        </div>
                    </div>

                    <!-- Mini chart per inspector -->
                    <div style="height: 120px;">
                        <Bar :data="makeChartData(insp)" :options="miniChartOptions" />
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white">Test Duration History</h3>
                    <span class="text-xs text-slate-500">Last 50</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-800">
                                <th class="px-4 py-2">ID</th>
                                <th class="px-4 py-2">Inspector</th>
                                <th class="px-4 py-2">Equipment</th>
                                <th class="px-4 py-2">DMC</th>
                                <th class="px-4 py-2">Start</th>
                                <th class="px-4 py-2">End</th>
                                <th class="px-4 py-2">Duration</th>
                                <th class="px-4 py-2">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in details" :key="d.detail_id" class="border-b border-slate-800/60 hover:bg-slate-800/20 transition">
                                <td class="px-4 py-2 text-slate-400 font-mono text-xs">#{{ d.detail_id }}</td>
                                <td class="px-4 py-2 text-white text-xs">{{ d.inspector }}</td>
                                <td class="px-4 py-2 text-slate-300 text-xs">{{ d.equipment_name }}</td>
                                <td class="px-4 py-2 text-slate-300 font-mono text-xs">{{ d.dmc || '-' }}</td>
                                <td class="px-4 py-2 text-slate-400 text-xs">{{ formatDate(d.start_time) }}</td>
                                <td class="px-4 py-2 text-slate-400 text-xs">{{ formatDate(d.end_time) }}</td>
                                <td class="px-4 py-2 text-indigo-400 font-mono font-bold text-xs">{{ fmtDuration(d.duration_sec) }}</td>
                                <td class="px-4 py-2">
                                    <span :class="d.judgement === 'OK' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'" class="px-2 py-0.5 rounded-full text-[10px] font-bold border">
                                        {{ d.judgement }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </AuthenticatedLayout>
</template>
