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

const makeChart = (insp) => ({
    labels: ['Avg', 'Min', 'Max'],
    datasets: [{
        data: [Math.round((insp.avg_sec||0)/60), Math.round((insp.min_sec||0)/60), Math.round((insp.max_sec||0)/60)],
        backgroundColor: ['#F59E0B', '#10B981', '#EF4444'],
        borderRadius: 4, maxBarThickness: 20,
    }]
});

const chartOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { color: '#9CA3AF', font: { size: 10 } }, grid: { display: false } },
        y: { ticks: { color: '#D1D5DB', font: { size: 10 } }, grid: { color: '#F3F4F6' }, border: { display: false } },
    }
};

const fmtDt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '-';
</script>

<template>
    <Head title="Performance" />
    <AuthenticatedLayout>
        <template #title>Performance</template>

        <div class="mb-6 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Inspector Performance</h2>
                <p class="text-sm text-gray-500">Test duration analysis per inspector</p>
            </div>
        </div>

        <div v-if="!inspectors.length" class="bg-white border border-gray-200/80 rounded-2xl p-12 text-center">
            <p class="text-sm text-gray-400">No test data yet.</p>
        </div>

        <template v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
                <div v-for="insp in inspectors" :key="insp.id" class="bg-white border border-gray-200/80 rounded-2xl p-5 hover:shadow-md hover:border-gray-300 transition-all group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center text-white font-bold text-xs">{{ insp.name.charAt(0) }}</div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ insp.name }}</p>
                                <p class="text-[11px] text-gray-400 font-mono">{{ insp.total_tests }} tests</p>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ insp.ok_cnt }}</span>
                            <span class="text-[10px] font-semibold text-red-700 bg-red-50 px-1.5 py-0.5 rounded border border-red-200">{{ insp.ng_cnt }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="bg-amber-50/60 rounded-lg p-2 text-center">
                            <p class="text-sm font-bold text-amber-600 font-mono">{{ fmt(insp.avg_sec) }}</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Average</p>
                        </div>
                        <div class="bg-emerald-50/60 rounded-lg p-2 text-center">
                            <p class="text-sm font-bold text-emerald-600 font-mono">{{ fmt(insp.min_sec) }}</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Fastest</p>
                        </div>
                        <div class="bg-red-50/60 rounded-lg p-2 text-center">
                            <p class="text-sm font-bold text-red-600 font-mono">{{ fmt(insp.max_sec) }}</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Slowest</p>
                        </div>
                    </div>

                    <div style="height: 100px;"><Bar :data="makeChart(insp)" :options="chartOpts" /></div>
                </div>
            </div>

            <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50/80">
                    <h3 class="text-sm font-semibold text-gray-900">Duration History</h3>
                    <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-1 rounded-md font-medium">Last 50</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Inspector</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Equipment</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Start</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">End</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Duration</th>
                                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Result</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="d in details" :key="d.detail_id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-2.5 text-gray-400 font-mono text-xs">#{{ d.detail_id }}</td>
                                <td class="px-4 py-2.5 text-gray-800 text-xs">{{ d.inspector }}</td>
                                <td class="px-4 py-2.5 text-gray-600 text-xs">{{ d.equipment_name }}</td>
                                <td class="px-4 py-2.5 text-gray-400 text-xs">{{ fmtDt(d.start_time) }}</td>
                                <td class="px-4 py-2.5 text-gray-400 text-xs">{{ fmtDt(d.end_time) }}</td>
                                <td class="px-4 py-2.5 text-gray-900 font-mono font-bold text-xs">{{ fmt(d.duration_sec) }}</td>
                                <td class="px-4 py-2.5">
                                    <span :class="d.judgement === 'OK' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-semibold border">
                                        <div :class="d.judgement === 'OK' ? 'bg-emerald-500' : 'bg-red-500'" class="w-1.5 h-1.5 rounded-full"></div>
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
