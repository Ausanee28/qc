<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Bar, Line, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend, Filler, ArcElement } from 'chart.js';
import { computed } from 'vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend, Filler, ArcElement);

const props = defineProps({
    metrics: Object,
    weeklyData: Array,
    monthlyData: Array,
    equipRank: Array,
    failByEquip: Array,
    inspectorEff: Array,
    recentActivities: Array,
    inspectorData: Array,
});

const totalTests = computed(() => props.metrics.totalTests || (props.metrics.okCount + props.metrics.ngCount));
const todayTotal = computed(() => props.metrics.todayOK + props.metrics.todayNG);
const todayRate = computed(() => todayTotal.value > 0 ? Math.round(props.metrics.todayOK / todayTotal.value * 100) : 0);
const monthlyTotalOK = computed(() => props.monthlyData.reduce((s, m) => s + m.ok, 0));
const monthlyTotalNG = computed(() => props.monthlyData.reduce((s, m) => s + m.ng, 0));

// Chart config constants matching preview script
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

const singleLineOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { font: { size: 10, family: 'Inter' }, color: '#6B7280' }, grid: { display: false } },
        y: { ticks: { font: { size: 10, family: 'Inter' }, color: '#9CA3AF' }, border: { display: false }, grid: { color: '#F3F4F6', drawBorder: false }, beginAtZero: true }
    },
    elements: { line: { tension: 0.4 }, point: { radius: 0, hoverRadius: 6 } },
    interaction: { mode: 'nearest', intersect: false }
};

const monthlyOKData = computed(() => ({
    labels: props.monthlyData.map(d => d.label),
    datasets: [{ data: props.monthlyData.map(d => d.ok), borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.1)', fill: true, borderWidth: 2, pointBackgroundColor: '#059669', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 4 }]
}));

const monthlyNGData = computed(() => ({
    labels: props.monthlyData.map(d => d.label),
    datasets: [{ data: props.monthlyData.map(d => d.ng), borderColor: '#DC2626', backgroundColor: 'rgba(220,38,38,0.1)', fill: true, borderWidth: 2, pointBackgroundColor: '#DC2626', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 4 }]
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
    labels: props.equipRank?.map(d => d.name) || ['Caliper 150mm', 'Micrometer 25mm', 'Gauge Block', 'Height Gauge', 'Ring Gauge'],
    datasets: [{
        label: 'Jobs',
        data: props.equipRank?.map(d => d.count) || [120, 95, 72, 45, 30],
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
const worstMonth = computed(() => props.monthlyData.length ? props.monthlyData.filter(m => m.total > 0).reduce((a, b) => (b.yield < a.yield ? b : a)) : null);
const avgYield = computed(() => {
    const withData = props.monthlyData.filter(m => m.total > 0);
    return withData.length ? (withData.reduce((s, m) => s + m.yield, 0) / withData.length).toFixed(1) : 0;
});

// OK growth: compare first month with data to last month with data
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
            <div style="display:flex;gap:8px">
                <select class="form-inp" style="padding:6px 10px;width:140px;font-size:12px">
                    <option>Today</option>
                    <option selected>This Month</option>
                    <option>Last 7 Days</option>
                    <option>Last 30 Days</option>
                    <option>This Quarter</option>
                </select>
            </div>
        </div>

        <!-- ROW 1: Primary KPIs -->
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
                <div class="kpi-label">Avg Test Time (MTTE)</div>
                <div class="kpi-number" style="color:#D97706">{{ metrics.avgTestTime }}m</div>
                <div class="kpi-change" style="background:#F3F4F6;color:#6B7280">Per inspection</div>
            </div>
            <div class="card kpi-card">
                <div class="kpi-icon" style="background:#FEF2F2">⚠️</div>
                <div class="kpi-label">Pending Jobs</div>
                <div class="kpi-number" style="color:#DC2626">{{ metrics.pendingCount }}</div>
                <div class="kpi-change" style="background:#FEF2F2;color:#DC2626">Awaiting test</div>
            </div>
        </div>

        <!-- SECTION: Quality Overview -->
        <div class="section-divider">
            <span class="text">Quality Overview</span>
            <div class="line"></div>
        </div>

        <!-- ROW 2: Trend + Donut -->
        <div class="chart-row chart-row-2">
            <div class="card">
                <div class="card-title">Weekly Quality Trend (Pass vs Fail)</div>
                <div class="card-desc">Daily count of OK and NG judgements over the last 7 days — helps identify quality anomalies</div>
                <div style="height:280px"><Bar :data="weeklyChartData" :options="barOpts" /></div>
            </div>
            <div class="card">
                <div class="card-title">Today's Test Results</div>
                <div class="card-desc">Breakdown of today's completed inspections</div>
                <div style="text-align:center;padding:20px 0 16px">
                    <div style="font-size:48px;font-weight:700;color:#111827;line-height:1">{{ todayTotal }}</div>
                    <div style="font-size:12px;color:#9CA3AF;margin-top:4px">tests completed today</div>
                </div>
                <!-- Simple stacked bar -->
                <div style="display:flex;height:28px;border-radius:8px;overflow:hidden;margin-bottom:16px;background:#F3F4F6">
                    <div v-show="todayTotal > 0" :style="{ width: todayRate + '%', background: '#10B981', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'12px', fontWeight:'700' }">{{ todayRate }}%</div>
                    <div v-show="todayTotal > 0 && todayRate < 100" :style="{ width: (100 - todayRate) + '%', background: '#EF4444', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'12px', fontWeight:'700' }">{{ 100 - todayRate }}%</div>
                </div>
                <!-- Numbers -->
                <div style="display:flex;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:12px;height:12px;border-radius:3px;background:#10B981"></div>
                        <div>
                            <div style="font-size:18px;font-weight:700;color:#059669">{{ metrics.todayOK }}</div>
                            <div style="font-size:11px;color:#6B7280">OK (Pass)</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:12px;height:12px;border-radius:3px;background:#EF4444"></div>
                        <div style="text-align:right">
                            <div style="font-size:18px;font-weight:700;color:#DC2626">{{ metrics.todayNG }}</div>
                            <div style="font-size:11px;color:#6B7280">NG (Fail)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 4: Calculated Metrics Summary -->
        <div class="card" style="margin-bottom:20px">
            <div class="card-title">Calculated Metrics Summary</div>
            <div class="card-desc">Key statistical values derived from Transaction_Header & Transaction_Detail</div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
                <div class="stat-mini">
                    <div class="val" style="color:#4F46E5">{{ metrics.totalTests.toLocaleString() }}</div>
                    <div class="lbl">Total Transactions</div>
                </div>
                <div class="stat-mini">
                    <div class="val" style="color:#059669">{{ metrics.okCount.toLocaleString() }}</div>
                    <div class="lbl">Total Pass (OK)</div>
                </div>
                <div class="stat-mini">
                    <div class="val" style="color:#DC2626">{{ metrics.ngCount.toLocaleString() }}</div>
                    <div class="lbl">Total Fail (NG)</div>
                </div>
                <div class="stat-mini">
                    <div class="val" style="color:#D97706">{{ metrics.pendingCount.toLocaleString() }}</div>
                    <div class="lbl">Pending in Lab</div>
                </div>
                <div class="stat-mini">
                    <div class="val" style="color:#059669">{{ metrics.yieldRate }}%</div>
                    <div class="lbl">Yield Rate</div>
                </div>
                <div class="stat-mini">
                    <div class="val" style="color:#DC2626">{{ metrics.defectRate }}%</div>
                    <div class="lbl">Defect Rate</div>
                </div>
                <div class="stat-mini">
                    <div class="val" style="color:#D97706">{{ metrics.avgTestTime }}m</div>
                    <div class="lbl">MTTE (Avg Test Time)</div>
                </div>
                <div class="stat-mini">
                    <div class="val" style="color:#7C3AED">{{ metrics.testsPerJob }}</div>
                    <div class="lbl">Tests per Job (Avg)</div>
                </div>
            </div>
        </div>

        <!-- SECTION: Equipment & Inspector Analysis -->
        <div class="section-divider"><span class="text">Equipment & Inspector Analysis</span>
            <div class="line"></div>
        </div>

        <!-- ROW 4: Equipment Charts -->
        <div class="chart-row chart-row-3">
            <div class="card">
                <div class="card-title">Top Inspected Items</div>
                <div class="card-desc">Most frequently inspected items</div>
                <div style="height:240px"><Bar :data="equipUsageData" :options="equipBarOpts" /></div>
            </div>
            <div class="card">
                <div class="card-title">Failure by Item Detail</div>
                <div class="card-desc">Which items generate the most NG results</div>
                <div style="height:240px;display:flex;justify-content:center"><Doughnut :data="failDoughnutData" :options="doughnutOpts" /></div>
            </div>
            <div class="card">
                <div class="card-title">Inspector Efficiency</div>
                <div class="card-desc">Average test duration per inspector (minutes)</div>
                <div style="height:240px"><Bar :data="inspectorEffData" :options="equipBarOpts" /></div>
            </div>
        </div>

        <!-- SECTION: Recent Activity -->
        <div class="section-divider"><span class="text">Recent Activity</span>
            <div class="line"></div>
        </div>

        <!-- ROW 5: Inspector Leaderboard + Recent Table -->
        <div class="chart-row chart-row-2">
            <div class="card">
                <div class="card-title">Inspector Leaderboard</div>
                <div class="card-desc">Pass rate and volume comparison across inspectors</div>
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
                        No inspector data yet
                    </div>
                </div>
            </div>
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <div class="card-title" style="margin:0">Recent Activities</div><span style="font-size:11px;color:#9CA3AF">Last 5 transactions</span>
                </div>
                <div class="tbl">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>DMC</th>
                                <th>Detail</th>
                                <th>Status</th>
                                <th>Tag</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="act in recentActivities.slice(0,5)" :key="act.id">
                                <td style="font-family:monospace;color:#4F46E5;font-weight:700">#{{ act.id }}</td>
                                <td style="font-weight:600">{{ act.dmcCode }}</td>
                                <td>{{ act.detail }}</td>
                                <td><span :class="['pill', act.result === 'OK' ? 'pill-g' : 'pill-r']">{{ act.result }}</span></td>
                                <td><button class="btn-outline" style="padding:2px 6px;font-size:10px">🖨️</button></td>
                            </tr>
                            <tr v-if="!recentActivities.length">
                                <td colspan="5" style="text-align: center; color: #9CA3B8">No recent transactions found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Monthly OK Volume + Monthly NG Volume (Individual Trends) -->
        <div class="chart-row" style="grid-template-columns:1fr 1fr">
            <div class="card">
                <div class="card-title" style="color:#059669">Monthly OK Volume (Pass)</div>
                <div class="card-desc">6-month trend of passed inspections</div>
                <div style="height:220px"><Line :data="monthlyOKData" :options="singleLineOpts" /></div>
            </div>
            <div class="card">
                <div class="card-title" style="color:#DC2626">Monthly NG Volume (Fail)</div>
                <div class="card-desc">6-month trend of failed inspections</div>
                <div style="height:220px"><Line :data="monthlyNGData" :options="singleLineOpts" /></div>
            </div>
        </div>

        <!-- SECTION: Monthly Analysis -->
        <div class="section-divider"><span class="text">Monthly Analysis</span>
            <div class="line"></div>
        </div>

        <!-- OK vs NG Dual Line Chart + Monthly Data Table -->
        <div class="chart-row" style="grid-template-columns:2fr 1fr">
            <div class="card">
                <div class="card-title">📊 OK vs NG — Monthly Trend</div>
                <div class="card-desc">Dual-line comparison showing passed (green) and failed (red) inspections over 6 months</div>
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
