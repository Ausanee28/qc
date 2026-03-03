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
    { label: 'Today', value: props.metrics.todayCount, color: 'from-blue-500 to-cyan-500', desc: 'Items received today' },
    { label: 'This Month', value: props.metrics.monthCount, color: 'from-indigo-500 to-purple-500', desc: 'Items this month' },
    { label: 'OK', value: props.metrics.okCount, color: 'from-emerald-500 to-green-500', desc: 'Passed judgements' },
    { label: 'NG', value: props.metrics.ngCount, color: 'from-red-500 to-rose-500', desc: 'Failed judgements' },
    { label: 'Pending', value: props.metrics.pendingCount, color: 'from-amber-500 to-orange-500', desc: 'In the lab now' },
];

const weeklyChartData = {
    labels: props.weeklyData.map(d => d.label),
    datasets: [
        { label: 'OK', data: props.weeklyData.map(d => d.ok), backgroundColor: 'rgba(16, 185, 129, 0.8)', borderRadius: 6 },
        { label: 'NG', data: props.weeklyData.map(d => d.ng), backgroundColor: 'rgba(239, 68, 68, 0.8)', borderRadius: 6 },
    ]
};

const monthlyChartData = {
    labels: props.monthlyData.map(d => d.label),
    datasets: [
        { label: 'OK', data: props.monthlyData.map(d => d.ok), backgroundColor: 'rgba(16, 185, 129, 0.8)', borderRadius: 6 },
        { label: 'NG', data: props.monthlyData.map(d => d.ng), backgroundColor: 'rgba(239, 68, 68, 0.8)', borderRadius: 6 },
    ]
};

// Doughnut charts
const totalOkNg = props.metrics.okCount + props.metrics.ngCount;
const overallDonutData = {
    labels: ['OK', 'NG'],
    datasets: [{
        data: totalOkNg > 0 ? [props.metrics.okCount, props.metrics.ngCount] : [1, 0],
        backgroundColor: ['rgba(16, 185, 129, 0.9)', 'rgba(239, 68, 68, 0.9)'],
        borderColor: ['rgba(16, 185, 129, 1)', 'rgba(239, 68, 68, 1)'],
        borderWidth: 2,
        hoverOffset: 6,
    }]
};

const todayTotal = props.metrics.todayOK + props.metrics.todayNG;
const todayDonutData = {
    labels: ['OK', 'NG'],
    datasets: [{
        data: todayTotal > 0 ? [props.metrics.todayOK, props.metrics.todayNG] : [1, 0],
        backgroundColor: ['rgba(99, 102, 241, 0.9)', 'rgba(251, 146, 60, 0.9)'],
        borderColor: ['rgba(99, 102, 241, 1)', 'rgba(251, 146, 60, 1)'],
        borderWidth: 2,
        hoverOffset: 6,
    }]
};

const donutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 }, padding: 16 } },
    }
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#94a3b8', font: { size: 11 } } } },
    scales: {
        x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(51,65,85,0.3)' } },
        y: { ticks: { color: '#64748b' }, grid: { color: 'rgba(51,65,85,0.3)' } },
    }
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #title>Dashboard</template>

        <!-- Greeting -->
        <div class="mb-8">
            <h2 class="text-2xl font-extrabold text-white">
                {{ greeting() }}, <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">{{ $page.props.auth.user.name }}</span>
            </h2>
            <p class="text-slate-500 mt-1 font-medium">Here's what's happening in your lab today.</p>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
            <div v-for="card in metricCards" :key="card.label" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition-all duration-300">
                <p class="text-xs text-slate-500 font-medium mb-1">{{ card.label }}</p>
                <p :class="'text-3xl font-extrabold bg-gradient-to-r ' + card.color + ' bg-clip-text text-transparent'">{{ card.value }}</p>
                <p class="text-xs text-slate-600 mt-1">{{ card.desc }}</p>
            </div>
        </div>

        <!-- Donut Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                <h3 class="text-sm font-bold text-white mb-1">Overall OK vs NG</h3>
                <p class="text-xs text-slate-500 mb-4">All-time judgement ratio</p>
                <div style="height: 220px;" class="flex items-center justify-center">
                    <Doughnut :data="overallDonutData" :options="donutOptions" />
                </div>
                <div class="flex justify-center gap-6 mt-4">
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-emerald-400">{{ metrics.okCount }}</p>
                        <p class="text-xs text-slate-500">OK</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-red-400">{{ metrics.ngCount }}</p>
                        <p class="text-xs text-slate-500">NG</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-white">{{ totalOkNg > 0 ? Math.round(metrics.okCount / totalOkNg * 100) : 0 }}%</p>
                        <p class="text-xs text-slate-500">Pass Rate</p>
                    </div>
                </div>
            </div>
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                <h3 class="text-sm font-bold text-white mb-1">Today's Results</h3>
                <p class="text-xs text-slate-500 mb-4">Today's OK vs NG breakdown</p>
                <div style="height: 220px;" class="flex items-center justify-center">
                    <Doughnut :data="todayDonutData" :options="donutOptions" />
                </div>
                <div class="flex justify-center gap-6 mt-4">
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-indigo-400">{{ metrics.todayOK }}</p>
                        <p class="text-xs text-slate-500">OK Today</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-orange-400">{{ metrics.todayNG }}</p>
                        <p class="text-xs text-slate-500">NG Today</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-white">{{ todayTotal > 0 ? Math.round(metrics.todayOK / todayTotal * 100) : 0 }}%</p>
                        <p class="text-xs text-slate-500">Pass Rate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bar Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                <h3 class="text-sm font-bold text-white mb-1">Weekly Trend</h3>
                <p class="text-xs text-slate-500 mb-4">Last 7 days OK vs NG</p>
                <div style="height: 180px;"><Bar :data="weeklyChartData" :options="chartOptions" /></div>
            </div>
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
                <h3 class="text-sm font-bold text-white mb-1">Monthly Overview</h3>
                <p class="text-xs text-slate-500 mb-4">Last 6 months overview</p>
                <div style="height: 180px;"><Bar :data="monthlyChartData" :options="chartOptions" /></div>
            </div>
        </div>

        <!-- Equipment Ranking -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4">Top Equipment</h3>
            <div class="space-y-3">
                <div v-for="(eq, i) in equipRank" :key="i" class="flex items-center gap-4">
                    <span class="text-xs font-bold text-slate-500 w-6">{{ i + 1 }}</span>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-white font-medium">{{ eq.name }}</span>
                            <span class="text-xs text-slate-400 font-mono">{{ eq.count }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full" :style="{ width: (equipRank.length ? (eq.count / equipRank[0].count * 100) : 0) + '%' }"></div>
                        </div>
                    </div>
                </div>
                <p v-if="!equipRank.length" class="text-sm text-slate-500 text-center py-4">No data yet.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
