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

const greeting = () => {
    const h = new Date().getHours();
    if (h < 12) return 'Good Morning';
    if (h < 17) return 'Good Afternoon';
    return 'Good Evening';
};

const metricCards = [
    { label: 'Today', value: props.metrics.todayCount, color: 'text-blue-600', bg: 'bg-blue-50', desc: 'Items received' },
    { label: 'This Month', value: props.metrics.monthCount, color: 'text-indigo-600', bg: 'bg-indigo-50', desc: 'Monthly total' },
    { label: 'OK', value: props.metrics.okCount, color: 'text-emerald-600', bg: 'bg-emerald-50', desc: 'Passed' },
    { label: 'NG', value: props.metrics.ngCount, color: 'text-red-600', bg: 'bg-red-50', desc: 'Failed' },
    { label: 'Pending', value: props.metrics.pendingCount, color: 'text-amber-600', bg: 'bg-amber-50', desc: 'In lab' },
];

const weeklyChartData = {
    labels: props.weeklyData.map(d => d.label),
    datasets: [
        { label: 'OK', data: props.weeklyData.map(d => d.ok), backgroundColor: '#059669', borderRadius: 4, maxBarThickness: 20 },
        { label: 'NG', data: props.weeklyData.map(d => d.ng), backgroundColor: '#DC2626', borderRadius: 4, maxBarThickness: 20 },
    ]
};

const monthlyChartData = {
    labels: props.monthlyData.map(d => d.label),
    datasets: [
        { label: 'OK', data: props.monthlyData.map(d => d.ok), backgroundColor: '#059669', borderRadius: 4, maxBarThickness: 20 },
        { label: 'NG', data: props.monthlyData.map(d => d.ng), backgroundColor: '#DC2626', borderRadius: 4, maxBarThickness: 20 },
    ]
};

const totalOkNg = props.metrics.okCount + props.metrics.ngCount;
const overallDonutData = {
    labels: ['OK', 'NG'],
    datasets: [{
        data: totalOkNg > 0 ? [props.metrics.okCount, props.metrics.ngCount] : [1, 0],
        backgroundColor: ['#059669', '#DC2626'],
        borderWidth: 0,
        hoverOffset: 4,
    }]
};

const todayTotal = props.metrics.todayOK + props.metrics.todayNG;
const todayDonutData = {
    labels: ['OK', 'NG'],
    datasets: [{
        data: todayTotal > 0 ? [props.metrics.todayOK, props.metrics.todayNG] : [1, 0],
        backgroundColor: ['#2563EB', '#F59E0B'],
        borderWidth: 0,
        hoverOffset: 4,
    }]
};

const donutOptions = {
    responsive: true, maintainAspectRatio: false, cutout: '70%',
    plugins: { legend: { position: 'bottom', labels: { color: '#6B7280', font: { size: 11, family: 'Inter' }, padding: 12 } } },
};

const chartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#6B7280', font: { size: 11, family: 'Inter' } } } },
    scales: {
        x: { ticks: { color: '#9CA3AF', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
        y: { ticks: { color: '#9CA3AF', font: { size: 11 } }, grid: { color: '#F3F4F6' } },
    }
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #title>Dashboard</template>

        <!-- Greeting -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900">
                {{ greeting() }}, <span class="text-blue-600">{{ $page.props.auth.user.name }}</span>
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">Here's what's happening in your lab today.</p>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
            <div v-for="card in metricCards" :key="card.label" class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-500 font-medium">{{ card.label }}</span>
                    <span :class="[card.bg, 'w-6 h-6 rounded-md flex items-center justify-center']">
                        <span :class="[card.color, 'text-[10px] font-bold']">{{ card.value > 99 ? '99+' : '' }}</span>
                    </span>
                </div>
                <p :class="[card.color, 'text-2xl font-bold']">{{ card.value }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ card.desc }}</p>
            </div>
        </div>

        <!-- Donut Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-0.5">Overall Results</h3>
                <p class="text-xs text-gray-400 mb-3">All-time pass rate</p>
                <div style="height: 180px;" class="flex justify-center"><Doughnut :data="overallDonutData" :options="donutOptions" /></div>
                <div class="flex justify-center gap-6 mt-3">
                    <div class="text-center"><p class="text-lg font-bold text-emerald-600">{{ metrics.okCount }}</p><p class="text-[10px] text-gray-400">OK</p></div>
                    <div class="text-center"><p class="text-lg font-bold text-red-600">{{ metrics.ngCount }}</p><p class="text-[10px] text-gray-400">NG</p></div>
                    <div class="text-center"><p class="text-lg font-bold text-gray-900">{{ totalOkNg > 0 ? Math.round(metrics.okCount / totalOkNg * 100) : 0 }}%</p><p class="text-[10px] text-gray-400">Pass Rate</p></div>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-0.5">Today's Results</h3>
                <p class="text-xs text-gray-400 mb-3">Today's breakdown</p>
                <div style="height: 180px;" class="flex justify-center"><Doughnut :data="todayDonutData" :options="donutOptions" /></div>
                <div class="flex justify-center gap-6 mt-3">
                    <div class="text-center"><p class="text-lg font-bold text-blue-600">{{ metrics.todayOK }}</p><p class="text-[10px] text-gray-400">OK</p></div>
                    <div class="text-center"><p class="text-lg font-bold text-amber-600">{{ metrics.todayNG }}</p><p class="text-[10px] text-gray-400">NG</p></div>
                    <div class="text-center"><p class="text-lg font-bold text-gray-900">{{ todayTotal > 0 ? Math.round(metrics.todayOK / todayTotal * 100) : 0 }}%</p><p class="text-[10px] text-gray-400">Pass Rate</p></div>
                </div>
            </div>
        </div>

        <!-- Bar Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-0.5">Weekly Trend</h3>
                <p class="text-xs text-gray-400 mb-3">Last 7 days</p>
                <div style="height: 180px;"><Bar :data="weeklyChartData" :options="chartOptions" /></div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-0.5">Monthly Overview</h3>
                <p class="text-xs text-gray-400 mb-3">Last 6 months</p>
                <div style="height: 180px;"><Bar :data="monthlyChartData" :options="chartOptions" /></div>
            </div>
        </div>

        <!-- Equipment Ranking -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Top Equipment</h3>
            <div class="space-y-2.5">
                <div v-for="(eq, i) in equipRank" :key="i" class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-gray-400 w-5 text-right">{{ i + 1 }}</span>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-700 font-medium">{{ eq.name }}</span>
                            <span class="text-xs text-gray-400 font-mono">{{ eq.count }}</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full" :style="{ width: (equipRank.length ? (eq.count / equipRank[0].count * 100) : 0) + '%' }"></div>
                        </div>
                    </div>
                </div>
                <p v-if="!equipRank.length" class="text-sm text-gray-400 text-center py-4">No data yet.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
