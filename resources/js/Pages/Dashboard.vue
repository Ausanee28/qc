<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bar, Line, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend, Filler, ArcElement } from 'chart.js';
import { computed, ref, watch } from 'vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend, Filler, ArcElement);

const props = defineProps({
    currentPeriod: { type: String, default: 'month' },
    metrics: Object,
    weeklyData: Array,
    dailyData: Array,
    monthlyData: Array,
    equipRank: Array,
    failByEquip: Array,
    inspectorEff: Array,
    recentActivities: Array,
    inspectorData: Array,
});

const selectedPeriod = ref(props.currentPeriod);
const isLoading = ref(false);

const periodLabels = {
    today: 'Today',
    month: 'This Month',
    week: 'Last 7 Days',
    '30days': 'Last 30 Days',
    quarter: 'This Quarter',
};

watch(selectedPeriod, (val) => {
    isLoading.value = true;
    router.get(route('dashboard'), { period: val }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { isLoading.value = false; },
    });
});

const totalTests = computed(() => props.metrics.totalTests || 0);
const yieldPct = computed(() => props.metrics.yieldRate || 0);
const defectPct = computed(() => props.metrics.defectRate || 0);
const monthlyTotalOK = computed(() => props.monthlyData.reduce((s, m) => s + m.ok, 0));
const monthlyTotalNG = computed(() => props.monthlyData.reduce((s, m) => s + m.ng, 0));

// Daily trend chart
const dailyLineData = computed(() => ({
    labels: props.dailyData.map(d => d.label),
    datasets: [
        { label: 'OK', data: props.dailyData.map(d => d.ok), borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, pointBackgroundColor: '#10B981', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 3 },
        { label: 'NG', data: props.dailyData.map(d => d.ng), borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.05)', fill: false, pointBackgroundColor: '#EF4444', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 3 }
    ]
}));
const dailyTotalOK = computed(() => props.dailyData.reduce((s, d) => s + d.ok, 0));
const dailyTotalNG = computed(() => props.dailyData.reduce((s, d) => s + d.ng, 0));
const dailyTotal = computed(() => dailyTotalOK.value + dailyTotalNG.value);
const dailyYield = computed(() => dailyTotal.value > 0 ? (dailyTotalOK.value / dailyTotal.value * 100).toFixed(1) : 0);

// --- Chart Configs ---
const barOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { usePointStyle: true, pointStyle: 'circle', font: { family: 'Inter', size: 11, weight: '500' } } } },
    scales: {
        x: { ticks: { font: { size: 11, family: 'Inter' } }, grid: { display: false } },
        y: { beginAtZero: true, ticks: { font: { size: 10, family: 'Inter' } }, grid: { color: '#F3F4F6' } }
    }
};

const weeklyChartData = computed(() => ({
    labels: props.weeklyData.map(d => d.label),
    datasets: [
        { label: 'OK', data: props.weeklyData.map(d => d.ok), backgroundColor: 'rgba(16,185,129,.75)', borderRadius: 4, borderSkipped: false },
        { label: 'NG', data: props.weeklyData.map(d => d.ng), backgroundColor: 'rgba(239,68,68,.75)', borderRadius: 4, borderSkipped: false }
    ]
}));

const equipBarOpts = {
    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { beginAtZero: true, ticks: { font: { size: 10, family: 'Inter' }, color: '#9CA3AF' }, grid: { color: '#F3F4F6' } },
        y: { ticks: { font: { size: 11, family: 'Inter', weight: '500' }, color: '#374151' }, grid: { display: false } }
    }
};

const equipUsageData = computed(() => ({
    labels: props.equipRank?.map(d => d.name) || ['No data'],
    datasets: [{
        label: 'Tests',
        data: props.equipRank?.map(d => d.count) || [0],
        backgroundColor: ['#4F46E5', '#8B5CF6', '#06B6D4', '#F59E0B', '#10B981'],
        borderRadius: 6, borderSkipped: false
    }]
}));

const doughnutOpts = {
    responsive: true, maintainAspectRatio: false, animation: false, cutout: '60%',
    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 10 }, usePointStyle: true, pointStyle: 'circle', padding: 8 } } }
};

const failDoughnutData = computed(() => ({
    labels: props.failByEquip?.map(d => d.name) || ['No Data'],
    datasets: [{
        data: props.failByEquip?.map(d => d.count) || [1],
        backgroundColor: ['#EF4444', '#F59E0B', '#4F46E5', '#8B5CF6', '#06B6D4'],
        borderWidth: 0, hoverOffset: 0
    }]
}));

const inspectorEffData = computed(() => ({
    labels: props.inspectorEff?.map(d => d.name) || ['No Data'],
    datasets: [{
        label: 'Avg (min)',
        data: props.inspectorEff?.map(d => d.avgMinutes) || [0],
        backgroundColor: ['rgba(16,185,129,.7)', 'rgba(79,70,229,.7)', 'rgba(245,158,11,.7)', '#F472B6', '#60A5FA'],
        borderRadius: 6, borderSkipped: false
    }]
}));

const dualLineOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { font: { size: 10, family: 'Inter' }, color: '#6B7280' }, grid: { display: false } },
        y: { ticks: { font: { size: 10, family: 'Inter' }, color: '#9CA3AF', stepSize: 50 }, border: { display: false }, grid: { color: '#F3F4F6' }, beginAtZero: true }
    },
    elements: { line: { tension: 0.4, borderWidth: 2 }, point: { radius: 0, hoverRadius: 6 } },
    interaction: { mode: 'index', intersect: false }
};

const dualLineData = computed(() => ({
    labels: props.monthlyData.map(d => d.label),
    datasets: [
        { label: 'OK', data: props.monthlyData.map(d => d.ok), borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, pointBackgroundColor: '#10B981', pointBorderColor: '#fff', pointBorderWidth: 2 },
        { label: 'NG', data: props.monthlyData.map(d => d.ng), borderColor: '#EF4444', backgroundColor: 'transparent', fill: false, pointBackgroundColor: '#EF4444', pointBorderColor: '#fff', pointBorderWidth: 2 }
    ]
}));

// Helpers
const yieldColor = (val) => val >= 95 ? '#059669' : val >= 93 ? '#D97706' : '#DC2626';
const ngPctColor = (val) => val <= 5 ? '#059669' : val <= 7 ? '#D97706' : '#DC2626';
const momDisplay = (val) => {
    if (val === null || val === undefined) return { text: '—', color: '#9CA3AF' };
    if (val > 0) return { text: `▲ ${val}%`, color: '#059669' };
    if (val < 0) return { text: `▼ ${Math.abs(val)}%`, color: '#DC2626' };
    return { text: '—', color: '#9CA3AF' };
};

const bestMonth = computed(() => props.monthlyData.length ? props.monthlyData.reduce((a, b) => (b.yield > a.yield ? b : a)) : null);
const worstMonth = computed(() => props.monthlyData.length ? props.monthlyData.filter(m => m.total > 0).reduce((a, b) => (b.yield < a.yield ? b : a), props.monthlyData[0]) : null);
const avgYield = computed(() => {
    const withData = props.monthlyData.filter(m => m.total > 0);
    return withData.length ? (withData.reduce((s, m) => s + m.yield, 0) / withData.length).toFixed(1) : 0;
});
const okGrowth = computed(() => {
    const withData = props.monthlyData.filter(m => m.ok > 0);
    if (withData.length < 2) return null;
    const first = withData[0].ok, last = withData[withData.length - 1].ok;
    return first > 0 ? ((last - first) / first * 100).toFixed(1) : null;
});
const worstNGMonth = computed(() => {
    const withData = props.monthlyData.filter(m => m.ng > 0);
    return withData.length ? withData.reduce((a, b) => b.ng > a.ng ? b : a) : null;
});

const medals = ['🥇', '🥈', '🥉'];
const medalColors = ['#D97706', '#9CA3AF', '#CD7F32'];
const avatarBgs = ['#E0E7FF', '#DCFCE7', '#FEF3C7', '#FCE7F3', '#DBEAFE'];
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #title>Dashboard</template>
        
        <div class="pg-header">
            <div>
                <h1 class="pg-title">Dashboard</h1>
                <p class="pg-sub">Overview of lab activity, quality metrics, and operational insights</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <div v-if="isLoading" style="font-size:12px;color:#6B7280;display:flex;align-items:center;gap:4px">
                    <svg style="width:14px;height:14px;animation:spin .7s linear infinite" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" opacity="0.8"></path></svg>
                    Loading...
                </div>
                <select v-model="selectedPeriod" class="form-inp" style="padding:6px 10px;width:140px;font-size:12px">
                    <option value="today">Today</option>
                    <option value="month">This Month</option>
                    <option value="week">Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="quarter">This Quarter</option>
                </select>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SECTION 1: KPI Cards                            -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="metric-grid">
            <div class="card kpi-card">
                <div class="kpi-icon" style="background:#EFF6FF">📊</div>
                <div class="kpi-label">Total Volume</div>
                <div class="kpi-number">{{ metrics.totalTests.toLocaleString() }}</div>
                <div class="kpi-change" style="background:#ECFDF5;color:#059669">↑ {{ metrics.todayCount }} new today</div>
            </div>
            <div class="card kpi-card">
                <div class="kpi-icon" style="background:#ECFDF5">✅</div>
                <div class="kpi-label">OK (Pass)</div>
                <div class="kpi-number" style="color:#059669">{{ metrics.okCount.toLocaleString() }}</div>
                <div class="kpi-change" style="background:#ECFDF5;color:#059669">{{ metrics.yieldRate }}% yield rate</div>
            </div>
            <div class="card kpi-card">
                <div class="kpi-icon" style="background:#FEF2F2">❌</div>
                <div class="kpi-label">NG (Fail)</div>
                <div class="kpi-number" style="color:#DC2626">{{ metrics.ngCount.toLocaleString() }}</div>
                <div class="kpi-change" style="background:#FEF2F2;color:#DC2626">{{ metrics.defectRate }}% defect rate</div>
            </div>
            <div class="card kpi-card">
                <div class="kpi-icon" style="background:#FFFBEB">⏱️</div>
                <div class="kpi-label">Avg Test Time</div>
                <div class="kpi-number" style="color:#D97706">{{ metrics.avgTestTime }}m</div>
                <div class="kpi-change" style="background:#F3F4F6;color:#6B7280">{{ metrics.testsPerJob }} tests/job</div>
            </div>
            <div class="card kpi-card">
                <div class="kpi-icon" style="background:#FEF2F2">⚠️</div>
                <div class="kpi-label">Pending Jobs</div>
                <div class="kpi-number" style="color:#DC2626">{{ metrics.pendingCount }}</div>
                <div class="kpi-change" style="background:#FEF2F2;color:#DC2626">Awaiting test</div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SECTION 2: Quality Trend + Period Summary       -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="section-divider">
            <span class="text">Quality Overview</span>
            <div class="line"></div>
        </div>

        <div class="chart-row chart-row-2">
            <div class="card">
                <div class="card-title">Weekly Quality Trend (Pass vs Fail)</div>
                <div class="card-desc">Daily OK and NG judgements over the last 7 days</div>
                <div style="height:280px"><Bar :data="weeklyChartData" :options="barOpts" /></div>
            </div>
            <div class="card">
                <div class="card-title">{{ periodLabels[selectedPeriod] }} Summary</div>
                <div class="card-desc">Inspection results for the selected period</div>
                <div style="text-align:center;padding:20px 0 16px">
                    <div style="font-size:48px;font-weight:700;color:#111827;line-height:1">{{ totalTests }}</div>
                    <div style="font-size:12px;color:#9CA3AF;margin-top:4px">total inspections</div>
                </div>
                <!-- Yield bar -->
                <div style="display:flex;height:28px;border-radius:8px;overflow:hidden;margin-bottom:16px;background:#F3F4F6">
                    <div v-show="totalTests > 0" :style="{ width: yieldPct + '%', background: '#10B981', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'12px', fontWeight:'700', minWidth: yieldPct > 0 ? '40px' : '0' }">{{ yieldPct }}%</div>
                    <div v-show="totalTests > 0 && defectPct > 0" :style="{ width: defectPct + '%', background: '#EF4444', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'12px', fontWeight:'700', minWidth: defectPct > 0 ? '40px' : '0' }">{{ defectPct }}%</div>
                </div>
                <!-- OK / NG numbers -->
                <div style="display:flex;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:12px;height:12px;border-radius:3px;background:#10B981"></div>
                        <div>
                            <div style="font-size:18px;font-weight:700;color:#059669">{{ metrics.okCount }}</div>
                            <div style="font-size:11px;color:#6B7280">OK (Pass)</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:12px;height:12px;border-radius:3px;background:#EF4444"></div>
                        <div style="text-align:right">
                            <div style="font-size:18px;font-weight:700;color:#DC2626">{{ metrics.ngCount }}</div>
                            <div style="font-size:11px;color:#6B7280">NG (Fail)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SECTION 3: Equipment & Inspector Analysis       -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="section-divider"><span class="text">Equipment & Inspector Analysis</span>
            <div class="line"></div>
        </div>

        <div class="chart-row chart-row-3">
            <div class="card">
                <div class="card-title">Top Equipment Used</div>
                <div class="card-desc">Most frequently used equipment — {{ periodLabels[selectedPeriod] }}</div>
                <div style="height:240px"><Bar :data="equipUsageData" :options="equipBarOpts" /></div>
            </div>
            <div class="card">
                <div class="card-title">Failure by Equipment</div>
                <div class="card-desc">Which equipment generates the most NG — {{ periodLabels[selectedPeriod] }}</div>
                <div style="height:240px;display:flex;justify-content:center"><Doughnut :data="failDoughnutData" :options="doughnutOpts" /></div>
            </div>
            <div class="card">
                <div class="card-title">Inspector Efficiency</div>
                <div class="card-desc">Average test duration per inspector (minutes)</div>
                <div style="height:240px"><Bar :data="inspectorEffData" :options="equipBarOpts" /></div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SECTION 4: Leaderboard + Recent Activities      -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="section-divider"><span class="text">Team & Recent Activity</span>
            <div class="line"></div>
        </div>

        <div class="chart-row chart-row-2">
            <div class="card">
                <div class="card-title">Inspector Leaderboard</div>
                <div class="card-desc">Pass rate and volume — {{ periodLabels[selectedPeriod] }}</div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div v-for="(insp, idx) in (inspectorData || []).slice(0, 5)" :key="idx" style="display:flex;align-items:center;gap:10px;padding:10px;background:#F9FAFB;border-radius:8px">
                        <div v-if="idx < 3" style="font-size:16px;font-weight:700;width:24px" :style="{ color: medalColors[idx] }">{{ medals[idx] }}</div>
                        <div v-else style="font-size:13px;font-weight:700;width:24px;text-align:center;color:#9CA3AF">{{ idx + 1 }}</div>
                        <div class="avatar" style="width:30px;height:30px;font-size:11px" :style="{ background: avatarBgs[idx % 5] }">{{ insp.name.charAt(0) }}</div>
                        <div style="flex:1">
                            <div style="font-size:13px;font-weight:600">{{ insp.name }}</div>
                            <div style="font-size:11px;color:#6B7280">{{ insp.total }} tests · <span style="color:#059669">{{ insp.ok }} OK</span><span v-if="insp.ng > 0"> · <span style="color:#DC2626">{{ insp.ng }} NG</span></span></div>
                        </div>
                        <div style="font-size:12px;font-weight:700" :style="{ color: insp.yield >= 95 ? '#059669' : insp.yield >= 90 ? '#D97706' : '#DC2626' }">{{ insp.yield }}%</div>
                    </div>
                    <div v-if="!inspectorData || !inspectorData.length" style="padding:20px;text-align:center;color:#9CA3AF;font-size:12px">
                        No inspector data for this period
                    </div>
                </div>
            </div>
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <div class="card-title" style="margin:0">Recent Activities</div><span style="font-size:11px;color:#9CA3AF">{{ periodLabels[selectedPeriod] }}</span>
                </div>
                <div class="tbl">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>DMC</th>
                                <th>Detail</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="act in recentActivities.slice(0,5)" :key="act.id">
                                <td style="font-family:monospace;color:#4F46E5;font-weight:700">#{{ act.id }}</td>
                                <td style="font-weight:600">{{ act.dmcCode }}</td>
                                <td>{{ act.detail }}</td>
                                <td><span :class="['pill', act.result === 'OK' ? 'pill-g' : 'pill-r']">{{ act.result }}</span></td>
                            </tr>
                            <tr v-if="!recentActivities.length">
                                <td colspan="4" style="text-align: center; color: #9CA3B8">No activities for this period</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SECTION 5: Daily Analysis (Current month)       -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="section-divider"><span class="text">Daily Analysis (Current Month)</span>
            <div class="line"></div>
        </div>

        <div class="chart-row" style="grid-template-columns:2fr 1fr">
            <div class="card">
                <div class="card-title">📅 OK vs NG — Daily Trend</div>
                <div class="card-desc">Day-by-day inspection results for the current month</div>
                <div style="height:280px"><Line :data="dailyLineData" :options="dualLineOpts" /></div>
            </div>
            <div class="card">
                <div class="card-title">📋 Today Summary</div>
                <div style="text-align:center;padding:12px 0 8px">
                    <div style="font-size:42px;font-weight:700;color:#111;line-height:1">{{ metrics.todayOK + metrics.todayNG }}</div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:4px">inspections today</div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
                    <div style="text-align:center;background:#F0FDF4;border-radius:6px;padding:10px">
                        <div style="font-size:22px;font-weight:700;color:#059669">{{ metrics.todayOK }}</div>
                        <div style="font-size:10px;color:#6B7280;font-weight:600">OK</div>
                    </div>
                    <div style="text-align:center;background:#FEF2F2;border-radius:6px;padding:10px">
                        <div style="font-size:22px;font-weight:700;color:#DC2626">{{ metrics.todayNG }}</div>
                        <div style="font-size:10px;color:#6B7280;font-weight:600">NG</div>
                    </div>
                </div>
                <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:12px;margin-bottom:10px">
                    <div style="font-size:11px;color:#6B7280;margin-bottom:4px">Today Yield</div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="flex:1;height:10px;border-radius:5px;overflow:hidden;background:#F3F4F6">
                            <div :style="{ width: ((metrics.todayOK + metrics.todayNG) > 0 ? (metrics.todayOK / (metrics.todayOK + metrics.todayNG) * 100) : 0) + '%', height: '100%', background: '#10B981', borderRadius: '5px', transition: 'width 0.3s' }"></div>
                        </div>
                        <div style="font-size:14px;font-weight:700;color:#059669">{{ (metrics.todayOK + metrics.todayNG) > 0 ? ((metrics.todayOK / (metrics.todayOK + metrics.todayNG)) * 100).toFixed(1) : 0 }}%</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:11px">
                    <div style="background:#F9FAFB;border-radius:6px;padding:8px;text-align:center">
                        <div style="font-size:16px;font-weight:700;color:#D97706">{{ metrics.avgTestTime }}m</div>
                        <div style="color:#6B7280">Avg Test Time</div>
                    </div>
                    <div style="background:#F9FAFB;border-radius:6px;padding:8px;text-align:center">
                        <div style="font-size:16px;font-weight:700;color:#7C3AED">{{ metrics.testsPerJob }}</div>
                        <div style="color:#6B7280">Tests/Job</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SECTION 6: Monthly Analysis (Long-term trends)  -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="section-divider"><span class="text">Monthly Analysis</span>
            <div class="line"></div>
        </div>

        <div class="chart-row" style="grid-template-columns:2fr 1fr">
            <div class="card">
                <div class="card-title">📊 OK vs NG — Monthly Trend</div>
                <div class="card-desc">6-month comparison of passed (green) and failed (red) inspections</div>
                <div style="height:300px"><Line :data="dualLineData" :options="dualLineOpts" /></div>
            </div>
            <div class="card">
                <div class="card-title">📋 Monthly Summary</div>
                <div class="tbl" style="margin-bottom:12px">
                    <table style="font-size:11px">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th style="color:#059669">OK</th>
                                <th style="color:#DC2626">NG</th>
                                <th>Yield</th>
                                <th style="color:#DC2626">NG%</th>
                                <th>MoM</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(m, i) in monthlyData" :key="i" :style="i === monthlyData.length - 1 ? 'background:#F0FDF4' : ''">
                                <td :style="{ fontWeight: i === monthlyData.length - 1 ? '700' : '600' }">{{ m.label }}</td>
                                <td :style="{ color: '#059669', fontWeight: i === monthlyData.length - 1 ? '700' : 'normal' }">{{ m.ok }}</td>
                                <td :style="{ color: m.ng >= 15 ? '#DC2626' : 'inherit', fontWeight: m.ng >= 15 || i === monthlyData.length - 1 ? '700' : 'normal' }">{{ m.ng }}</td>
                                <td :style="{ color: yieldColor(m.yield), fontWeight: '600' }">{{ m.yield }}%</td>
                                <td :style="{ color: ngPctColor(m.ngPercent), fontWeight: i === monthlyData.length - 1 ? '700' : 'normal' }">{{ m.ngPercent }}%</td>
                                <td :style="{ color: momDisplay(m.mom).color, fontWeight: '600' }">{{ momDisplay(m.mom).text }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
                    <div style="text-align:center;background:#F0FDF4;border-radius:6px;padding:8px">
                        <div style="font-size:16px;font-weight:700;color:#059669">{{ monthlyTotalOK.toLocaleString() }}</div>
                        <div style="font-size:9px;color:#6B7280;font-weight:600">TOTAL OK</div>
                    </div>
                    <div style="text-align:center;background:#FEF2F2;border-radius:6px;padding:8px">
                        <div style="font-size:16px;font-weight:700;color:#DC2626">{{ monthlyTotalNG.toLocaleString() }}</div>
                        <div style="font-size:9px;color:#6B7280;font-weight:600">TOTAL NG</div>
                    </div>
                </div>
                <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:10px;font-size:10px;color:#374151;line-height:1.7">
                    <div><b style="color:#059669">📈</b> OK growth <b :style="{ color: okGrowth > 0 ? '#059669' : '#DC2626' }">{{ okGrowth !== null ? (okGrowth > 0 ? '↑' : '↓') + Math.abs(okGrowth) + '%' : '—' }}</b> · Best: <b>{{ bestMonth?.label || '—' }}</b> ({{ bestMonth?.yield || 0 }}%)</div>
                    <div><b style="color:#DC2626">⚠️</b> NG spike <b style="color:#DC2626">{{ worstNGMonth?.label || '—' }} ({{ worstNGMonth?.ng || 0 }})</b> · Worst: <b>{{ worstMonth?.label || '—' }}</b> ({{ worstMonth?.yield || 0 }}%)</div>
                    <div><b style="color:#4F46E5">🎯</b> Avg yield <b>{{ avgYield }}%</b> · Target <b>≥ 95%</b></div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
