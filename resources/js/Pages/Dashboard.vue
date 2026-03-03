<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

const props = defineProps({
    metrics: Object,
    weeklyData: Array,
    monthlyData: Array,
    equipRank: Array,
});

const totalTests = props.metrics.okCount + props.metrics.ngCount;
const passRate = totalTests > 0 ? Math.round(props.metrics.okCount / totalTests * 100) : 0;
const todayTotal = props.metrics.todayOK + props.metrics.todayNG;
const todayRate = todayTotal > 0 ? Math.round(props.metrics.todayOK / todayTotal * 100) : 0;

// Chart configs
const donutData = {
    labels: ['OK', 'NG'],
    datasets: [{
        data: totalTests > 0 ? [props.metrics.okCount, props.metrics.ngCount] : [1, 0],
        backgroundColor: ['#10B981', '#EF4444'],
        borderWidth: 0, hoverOffset: 4,
    }]
};
const donutOpts = {
    responsive: true, maintainAspectRatio: false, cutout: '75%',
    plugins: { legend: { display: false }, tooltip: { bodyFont: { family: 'Inter' } } },
};

const weeklyChartData = {
    labels: props.weeklyData.map(d => d.label),
    datasets: [
        { label: 'OK', data: props.weeklyData.map(d => d.ok), backgroundColor: '#10B981', borderRadius: 3, maxBarThickness: 16 },
        { label: 'NG', data: props.weeklyData.map(d => d.ng), backgroundColor: '#EF4444', borderRadius: 3, maxBarThickness: 16 },
    ]
};
const monthlyChartData = {
    labels: props.monthlyData.map(d => d.label),
    datasets: [
        { label: 'OK', data: props.monthlyData.map(d => d.ok), backgroundColor: '#10B981', borderRadius: 3, maxBarThickness: 16 },
        { label: 'NG', data: props.monthlyData.map(d => d.ng), backgroundColor: '#EF4444', borderRadius: 3, maxBarThickness: 16 },
    ]
};
const barOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', align: 'end', labels: { color: '#9CA3AF', font: { size: 11, family: 'Inter' }, boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle' } } },
    scales: {
        x: { ticks: { color: '#9CA3AF', font: { size: 10 } }, grid: { display: false } },
        y: { ticks: { color: '#D1D5DB', font: { size: 10 }, stepSize: 1 }, grid: { color: '#F3F4F6', drawBorder: false }, border: { display: false } },
    }
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #title>Dashboard</template>

        <!-- Top Row: Stats + Donut -->
        <div class="grid grid-cols-12 gap-4 mb-5">
            <!-- KPI Cards -->
            <div class="col-span-12 lg:col-span-8">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <!-- Today -->
                    <div class="bg-white border border-gray-200/80 rounded-xl p-4 relative overflow-hidden group hover:shadow-md hover:border-gray-300 transition-all duration-200">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-blue-50 rounded-full opacity-60 group-hover:scale-110 transition-transform"></div>
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Today</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ metrics.todayCount }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">Items received</p>
                    </div>
                    <!-- Month -->
                    <div class="bg-white border border-gray-200/80 rounded-xl p-4 relative overflow-hidden group hover:shadow-md hover:border-gray-300 transition-all duration-200">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-indigo-50 rounded-full opacity-60 group-hover:scale-110 transition-transform"></div>
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">This Month</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ metrics.monthCount }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">Monthly total</p>
                    </div>
                    <!-- OK -->
                    <div class="bg-white border border-gray-200/80 rounded-xl p-4 relative overflow-hidden group hover:shadow-md hover:border-gray-300 transition-all duration-200">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-emerald-50 rounded-full opacity-60 group-hover:scale-110 transition-transform"></div>
                        <p class="text-[11px] font-medium text-emerald-500 uppercase tracking-wide">Passed</p>
                        <p class="text-3xl font-bold text-emerald-600 mt-1">{{ metrics.okCount }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">OK results</p>
                    </div>
                    <!-- NG -->
                    <div class="bg-white border border-gray-200/80 rounded-xl p-4 relative overflow-hidden group hover:shadow-md hover:border-gray-300 transition-all duration-200">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-red-50 rounded-full opacity-60 group-hover:scale-110 transition-transform"></div>
                        <p class="text-[11px] font-medium text-red-500 uppercase tracking-wide">Failed</p>
                        <p class="text-3xl font-bold text-red-600 mt-1">{{ metrics.ngCount }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">NG results</p>
                    </div>
                </div>

                <!-- Pending banner -->
                <div class="mt-3 bg-amber-50 border border-amber-200/60 rounded-xl px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-amber-800">{{ metrics.pendingCount }} pending in lab</p>
                            <p class="text-[11px] text-amber-600/80">Awaiting test execution</p>
                        </div>
                    </div>
                    <a :href="route('execute-test.create')" class="text-xs font-semibold text-amber-700 bg-amber-100 hover:bg-amber-200 px-3 py-1.5 rounded-lg transition-colors">Execute →</a>
                </div>
            </div>

            <!-- Pass Rate Donut -->
            <div class="col-span-12 lg:col-span-4">
                <div class="bg-white border border-gray-200/80 rounded-xl p-5 h-full flex flex-col items-center justify-center hover:shadow-md transition-shadow">
                    <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide mb-3">Overall Pass Rate</p>
                    <div class="relative" style="width: 140px; height: 140px;">
                        <Doughnut :data="donutData" :options="donutOpts" />
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold text-gray-900">{{ passRate }}%</span>
                            <span class="text-[10px] text-gray-400">of {{ totalTests }}</span>
                        </div>
                    </div>
                    <div class="flex gap-5 mt-4">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                            <span class="text-xs text-gray-500">{{ metrics.okCount }} OK</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                            <span class="text-xs text-gray-500">{{ metrics.ngCount }} NG</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
            <div class="bg-white border border-gray-200/80 rounded-xl p-5 hover:shadow-md transition-shadow">
                <h3 class="text-sm font-semibold text-gray-900">Weekly Trend</h3>
                <p class="text-[11px] text-gray-400 mb-3">Last 7 days OK vs NG</p>
                <div style="height: 200px;"><Bar :data="weeklyChartData" :options="barOpts" /></div>
            </div>
            <div class="bg-white border border-gray-200/80 rounded-xl p-5 hover:shadow-md transition-shadow">
                <h3 class="text-sm font-semibold text-gray-900">Monthly Overview</h3>
                <p class="text-[11px] text-gray-400 mb-3">Last 6 months</p>
                <div style="height: 200px;"><Bar :data="monthlyChartData" :options="barOpts" /></div>
            </div>
        </div>

        <!-- Bottom Row: Equipment + Today summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Equipment Ranking -->
            <div class="lg:col-span-2 bg-white border border-gray-200/80 rounded-xl p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Top Equipment</h3>
                        <p class="text-[11px] text-gray-400">Most tested items</p>
                    </div>
                    <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-1 rounded-md font-medium">Top 5</span>
                </div>
                <div class="space-y-3">
                    <div v-for="(eq, i) in equipRank" :key="i" class="flex items-center gap-3">
                        <div :class="[
                            i === 0 ? 'bg-amber-100 text-amber-700' : i === 1 ? 'bg-gray-100 text-gray-500' : i === 2 ? 'bg-orange-50 text-orange-500' : 'bg-gray-50 text-gray-400',
                            'w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-bold'
                        ]">{{ i + 1 }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-700 font-medium truncate">{{ eq.name }}</span>
                                <span class="text-xs text-gray-400 font-mono ml-2">{{ eq.count }} tests</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="i === 0 ? 'bg-gray-900' : i === 1 ? 'bg-gray-600' : 'bg-gray-400'"
                                    :style="{ width: (equipRank.length ? (eq.count / equipRank[0].count * 100) : 0) + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    <p v-if="!equipRank.length" class="text-sm text-gray-400 text-center py-6">No equipment data yet.</p>
                </div>
            </div>

            <!-- Today's Quick Stats -->
            <div class="bg-white border border-gray-200/80 rounded-xl p-5 hover:shadow-md transition-shadow">
                <h3 class="text-sm font-semibold text-gray-900 mb-1">Today's Snapshot</h3>
                <p class="text-[11px] text-gray-400 mb-4">{{ new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</p>

                <div class="space-y-3">
                    <div class="bg-blue-50 rounded-lg p-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-blue-700">{{ metrics.todayCount }}</p>
                            <p class="text-[10px] text-blue-500">Received today</p>
                        </div>
                    </div>

                    <div class="bg-emerald-50 rounded-lg p-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-emerald-700">{{ metrics.todayOK }}</p>
                            <p class="text-[10px] text-emerald-500">Passed (OK)</p>
                        </div>
                    </div>

                    <div class="bg-red-50 rounded-lg p-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-red-700">{{ metrics.todayNG }}</p>
                            <p class="text-[10px] text-red-500">Failed (NG)</p>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-3 flex items-center justify-between">
                        <span class="text-sm text-gray-500">Today's pass rate</span>
                        <span class="text-lg font-bold" :class="todayRate >= 80 ? 'text-emerald-600' : todayRate >= 50 ? 'text-amber-600' : 'text-red-600'">{{ todayRate }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
