<?php
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Overview of lab activity and status metrics';
require_once 'db.php';
require_once 'header.php';

//  Metric Queries 
$stmtToday = $pdo->query("SELECT COUNT(*) AS cnt FROM Transaction_Header WHERE DATE(receive_date) = CURDATE()");
$todayCount = $stmtToday->fetch()['cnt'];
$stmtMonth = $pdo->query("SELECT COUNT(*) AS cnt FROM Transaction_Header WHERE YEAR(receive_date) = YEAR(CURDATE()) AND MONTH(receive_date) = MONTH(CURDATE())");
$monthCount = $stmtMonth->fetch()['cnt'];
$stmtOK = $pdo->query("SELECT COUNT(*) AS cnt FROM Transaction_Detail WHERE judgement = 'OK'");
$okCount = $stmtOK->fetch()['cnt'];
$stmtNG = $pdo->query("SELECT COUNT(*) AS cnt FROM Transaction_Detail WHERE judgement = 'NG'");
$ngCount = $stmtNG->fetch()['cnt'];
$stmtPending = $pdo->query("SELECT COUNT(*) AS cnt FROM Transaction_Header WHERE status = 'Pending'");
$pendingCount = $stmtPending->fetch()['cnt'];

//  Today's OK/NG 
$stmtTodayOK = $pdo->query("SELECT COUNT(*) AS cnt FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id WHERE DATE(TH.receive_date) = CURDATE() AND TD.judgement = 'OK'");
$todayOK = $stmtTodayOK->fetch()['cnt'];
$stmtTodayNG = $pdo->query("SELECT COUNT(*) AS cnt FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id WHERE DATE(TH.receive_date) = CURDATE() AND TD.judgement = 'NG'");
$todayNG = $stmtTodayNG->fetch()['cnt'];

//  Weekly trend (last 7 days) 
$weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $label = date('d M', strtotime("-{$i} days"));
    $stOK = $pdo->prepare("SELECT COUNT(*) AS cnt FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id WHERE DATE(TH.receive_date) = :d AND TD.judgement = 'OK'");
    $stOK->execute([':d' => $date]);
    $ok = $stOK->fetch()['cnt'];
    $stNG = $pdo->prepare("SELECT COUNT(*) AS cnt FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id WHERE DATE(TH.receive_date) = :d AND TD.judgement = 'NG'");
    $stNG->execute([':d' => $date]);
    $ng = $stNG->fetch()['cnt'];
    $weeklyData[] = ['label' => $label, 'ok' => (int)$ok, 'ng' => (int)$ng];
}

//  Monthly trend (last 6 months) 
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $label = date('M Y', strtotime("-{$i} months"));
    $stOK = $pdo->prepare("SELECT COUNT(*) AS cnt FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id WHERE DATE_FORMAT(TH.receive_date, '%Y-%m') = :m AND TD.judgement = 'OK'");
    $stOK->execute([':m' => $m]);
    $ok = $stOK->fetch()['cnt'];
    $stNG = $pdo->prepare("SELECT COUNT(*) AS cnt FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id WHERE DATE_FORMAT(TH.receive_date, '%Y-%m') = :m AND TD.judgement = 'NG'");
    $stNG->execute([':m' => $m]);
    $ng = $stNG->fetch()['cnt'];
    $monthlyData[] = ['label' => $label, 'ok' => (int)$ok, 'ng' => (int)$ng];
}

//  Recent Activities 
$stmtRecent = $pdo->query("
    SELECT TH.transaction_id, TH.dmc, TH.line, TH.receive_date, TH.status,
           EU.external_name AS sender, IU.name AS receiver, E.equipment_name
    FROM Transaction_Header TH
    JOIN External_Users EU ON TH.external_id = EU.external_id
    JOIN Internal_Users IU ON TH.internal_id = IU.user_id
    JOIN Equipments E ON TH.equipment_id = E.equipment_id
    ORDER BY TH.receive_date DESC LIMIT 10
");
$recentItems = $stmtRecent->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<!-- Metric Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-indigo-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-1">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today</span>
            <div class="w-9 h-9 rounded-xl bg-indigo-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
        </div>
        <p class="text-3xl font-bold text-white metric-value"><?= $todayCount ?></p>
        <p class="text-xs text-slate-500 mt-1">Items received today</p>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-blue-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-2">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">This Month</span>
            <div class="w-9 h-9 rounded-xl bg-blue-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
        </div>
        <p class="text-3xl font-bold text-white metric-value"><?= $monthCount ?></p>
        <p class="text-xs text-slate-500 mt-1">Items this month</p>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-emerald-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-3">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">OK</span>
            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        </div>
        <p class="text-3xl font-bold text-emerald-400 metric-value"><?= $okCount ?></p>
        <p class="text-xs text-slate-500 mt-1">Passed judgements</p>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-red-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">NG</span>
            <div class="w-9 h-9 rounded-xl bg-red-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        </div>
        <p class="text-3xl font-bold text-red-400 metric-value"><?= $ngCount ?></p>
        <p class="text-xs text-slate-500 mt-1">Failed judgements</p>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-amber-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending</span>
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        </div>
        <p class="text-3xl font-bold text-amber-400 metric-value"><?= $pendingCount ?></p>
        <p class="text-xs text-slate-500 mt-1">In the lab now</p>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
    <!-- Donut Chart: Today's OK vs NG -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 anim-fade-up delay-3 hover-lift">
        <h3 class="text-sm font-semibold text-white mb-1">Today's Results</h3>
        <p class="text-xs text-slate-500 mb-4"><?= date('d M Y') ?></p>
        <div class="flex items-center justify-center" style="height: 220px;">
            <?php if ($todayOK == 0 && $todayNG == 0): ?>
                <p class="text-slate-600 text-sm">No test results today</p>
            <?php else: ?>
                <canvas id="donutChart"></canvas>
            <?php endif; ?>
        </div>
        <div class="flex justify-center gap-6 mt-4">
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-400"></span><span class="text-xs text-slate-400">OK: <?= $todayOK ?></span></div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-400"></span><span class="text-xs text-slate-400">NG: <?= $todayNG ?></span></div>
        </div>
    </div>

    <!-- Bar Chart: Weekly Trend -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 anim-fade-up delay-4 hover-lift">
        <h3 class="text-sm font-semibold text-white mb-1">Weekly Trend</h3>
        <p class="text-xs text-slate-500 mb-4">Last 7 days OK vs NG</p>
        <div style="height: 250px;">
            <canvas id="weeklyChart"></canvas>
        </div>
    </div>

    <!-- Bar Chart: Monthly Trend -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 anim-fade-up delay-5 hover-lift">
        <h3 class="text-sm font-semibold text-white mb-1">Monthly Trend</h3>
        <p class="text-xs text-slate-500 mb-4">Last 6 months OK vs NG</p>
        <div style="height: 250px;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activities Table -->
<div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden anim-fade-up delay-6">
    <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
        <h3 class="text-base font-semibold text-white">Recent Activities</h3>
        <span class="text-xs text-slate-500">Last 10 transactions</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-800">
                    <th class="px-6 py-3">ID</th><th class="px-6 py-3">Line</th><th class="px-6 py-3">DMC</th><th class="px-6 py-3">Equipment</th><th class="px-6 py-3">Sender</th><th class="px-6 py-3">Receiver</th><th class="px-6 py-3">Received</th><th class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                <?php if (empty($recentItems)): ?>
                    <tr><td colspan="8" class="px-6 py-10 text-center text-slate-600">No transactions yet. Start by <a href="receive_job.php" class="text-indigo-400 hover:underline">receiving a job</a>.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentItems as $i => $item): ?>
                        <tr class="hover:bg-slate-800/30 transition-all duration-200 table-row-anim" style="animation-delay: <?= 0.7 + ($i * 0.06) ?>s">
                            <td class="px-6 py-3 font-mono text-xs text-slate-400">#<?= $item['transaction_id'] ?></td>
                            <td class="px-6 py-3 text-white"><?= htmlspecialchars($item['line'] ?? '') ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($item['dmc'] ?? '') ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($item['equipment_name']) ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($item['sender']) ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($item['receiver']) ?></td>
                            <td class="px-6 py-3 text-slate-400 text-xs"><?= date('d M Y H:i', strtotime($item['receive_date'])) ?></td>
                            <td class="px-6 py-3">
                                <?php $sc = match($item['status']) { 'Pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20', 'Completed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', default => 'bg-slate-500/10 text-slate-400 border-slate-500/20' }; ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?= $sc ?>"><?= htmlspecialchars($item['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js Scripts -->
<script>
    const chartFont = { family: 'Inter, sans-serif' };
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = '#94a3b8';

    //  Donut Chart: Today's OK vs NG 
    <?php if ($todayOK > 0 || $todayNG > 0): ?>
    new Chart(document.getElementById('donutChart'), {
        type: 'doughnut',
        data: {
            labels: ['OK', 'NG'],
            datasets: [{
                data: [<?= $todayOK ?>, <?= $todayNG ?>],
                backgroundColor: ['rgba(52, 211, 153, 0.8)', 'rgba(248, 113, 113, 0.8)'],
                borderColor: ['rgba(52, 211, 153, 1)', 'rgba(248, 113, 113, 1)'],
                borderWidth: 2,
                hoverOffset: 8,
                spacing: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    borderColor: 'rgba(100, 116, 139, 0.3)',
                    borderWidth: 1,
                    cornerRadius: 12,
                    padding: 12,
                    titleFont: { ...chartFont, weight: '600' },
                    bodyFont: chartFont,
                }
            },
            animation: { animateRotate: true, duration: 1200, easing: 'easeOutQuart' }
        }
    });
    <?php endif; ?>

    //  Bar Chart: Weekly Trend 
    new Chart(document.getElementById('weeklyChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($d) { return "'" . $d['label'] . "'"; }, $weeklyData)); ?>],
            datasets: [
                {
                    label: 'OK',
                    data: [<?php echo implode(',', array_column($weeklyData, 'ok')); ?>],
                    backgroundColor: 'rgba(52, 211, 153, 0.7)',
                    borderColor: 'rgba(52, 211, 153, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'NG',
                    data: [<?php echo implode(',', array_column($weeklyData, 'ng')); ?>],
                    backgroundColor: 'rgba(248, 113, 113, 0.7)',
                    borderColor: 'rgba(248, 113, 113, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { color: 'rgba(51, 65, 85, 0.3)' }, ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: 'rgba(51, 65, 85, 0.3)' }, ticks: { stepSize: 1, font: { size: 10 } } }
            },
            plugins: {
                legend: { labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } },
                tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', borderColor: 'rgba(100, 116, 139, 0.3)', borderWidth: 1, cornerRadius: 12, padding: 12 }
            },
            animation: { duration: 1000, easing: 'easeOutQuart' }
        }
    });

    //  Bar Chart: Monthly Trend 
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($d) { return "'" . $d['label'] . "'"; }, $monthlyData)); ?>],
            datasets: [
                {
                    label: 'OK',
                    data: [<?php echo implode(',', array_column($monthlyData, 'ok')); ?>],
                    backgroundColor: 'rgba(52, 211, 153, 0.7)',
                    borderColor: 'rgba(52, 211, 153, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'NG',
                    data: [<?php echo implode(',', array_column($monthlyData, 'ng')); ?>],
                    backgroundColor: 'rgba(248, 113, 113, 0.7)',
                    borderColor: 'rgba(248, 113, 113, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { color: 'rgba(51, 65, 85, 0.3)' }, ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: 'rgba(51, 65, 85, 0.3)' }, ticks: { stepSize: 1, font: { size: 10 } } }
            },
            plugins: {
                legend: { labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } },
                tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', borderColor: 'rgba(100, 116, 139, 0.3)', borderWidth: 1, cornerRadius: 12, padding: 12 }
            },
            animation: { duration: 1200, easing: 'easeOutQuart' }
        }
    });
</script>

<?php require_once 'footer.php'; ?>
