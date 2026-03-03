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
        backgroundColor: ['#F59E0B', '#059669', '#DC2626'],
        borderRadius: 4,
        maxBarThickness: 24,
    }]
});

const miniChartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { color: '#9CA3AF', font: { size: 10, family: 'Inter' } }, grid: { display: false } },
        y: { ticks: { color: '#9CA3AF', font: { size: 10 } }, grid: { color: '#F3F4F6' }, title: { display: true, text: 'min', color: '#9CA3AF', font: { size: 9 } } },
    }
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '-';
</script>

<template>
    <Head title="Performance" />
    <AuthenticatedLayout>
        <template #title>Performance</template>

        <div class="mb-5">
            <h2 class="text-lg font-semibold text-gray-900">Inspector Performance</h2>
            <p class="text-sm text-gray-500 mt-0.5">Test duration analysis per inspector.</p>
        </div>

        <div v-if="!inspectors.length" class="bg-white border border-gray-200 rounded-lg p-12 text-center">
            <p class="text-gray-400 text-sm">No test data yet.</p>
        </div>

        <template v-else>
            <!-- Inspector Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
                <div v-for="insp in inspectors" :key="insp.id" class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-xs">
                                {{ insp.name.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-gray-900 font-semibold text-sm">{{ insp.name }}</p>
                                <p class="text-[11px] text-gray-400 font-mono">{{ insp.total_tests }} tests</p>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ insp.ok_cnt }} OK</span>
                            <span class="text-[10px] font-semibold text-red-700 bg-red-50 px-1.5 py-0.5 rounded border border-red-200">{{ insp.ng_cnt }} NG</span>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="bg-gray-50 rounded-md p-2 text-center">
                            <p class="text-sm font-bold text-amber-600 font-mono">{{ fmtDuration(insp.avg_sec) }}</p>
                            <p class="text-[9px] text-gray-400">Average</p>
                        </div>
                        <div class="bg-gray-50 rounded-md p-2 text-center">
                            <p class="text-sm font-bold text-emerald-600 font-mono">{{ fmtDuration(insp.min_sec) }}</p>
                            <p class="text-[9px] text-gray-400">Fastest</p>
                        </div>
                        <div class="bg-gray-50 rounded-md p-2 text-center">
                            <p class="text-sm font-bold text-red-600 font-mono">{{ fmtDuration(insp.max_sec) }}</p>
                            <p class="text-[9px] text-gray-400">Slowest</p>
                        </div>
                    </div>

                    <!-- Mini chart -->
                    <div style="height: 110px;">
                        <Bar :data="makeChartData(insp)" :options="miniChartOptions" />
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-900">Test Duration History</h3>
                    <span class="text-xs text-gray-400">Last 50</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Inspector</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Equipment</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">DMC</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Start</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">End</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in details" :key="d.detail_id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-2 text-gray-400 font-mono text-xs">#{{ d.detail_id }}</td>
                                <td class="px-4 py-2 text-gray-800 text-xs">{{ d.inspector }}</td>
                                <td class="px-4 py-2 text-gray-600 text-xs">{{ d.equipment_name }}</td>
                                <td class="px-4 py-2 text-gray-600 font-mono text-xs">{{ d.dmc || '-' }}</td>
                                <td class="px-4 py-2 text-gray-500 text-xs">{{ formatDate(d.start_time) }}</td>
                                <td class="px-4 py-2 text-gray-500 text-xs">{{ formatDate(d.end_time) }}</td>
                                <td class="px-4 py-2 text-blue-600 font-mono font-bold text-xs">{{ fmtDuration(d.duration_sec) }}</td>
                                <td class="px-4 py-2">
                                    <span :class="d.judgement === 'OK' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'" class="px-2 py-0.5 rounded-full text-[10px] font-semibold border">
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
