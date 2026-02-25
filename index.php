<?php
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Overview of lab activity and status metrics';
require_once 'db.php';
require_once 'header.php';

// Core Metrics
$todayCount   = $pdo->query("SELECT COUNT(*) FROM Transaction_Header WHERE DATE(receive_date) = CURDATE()")->fetchColumn();
$monthCount   = $pdo->query("SELECT COUNT(*) FROM Transaction_Header WHERE YEAR(receive_date)=YEAR(CURDATE()) AND MONTH(receive_date)=MONTH(CURDATE())")->fetchColumn();
$okCount      = $pdo->query("SELECT COUNT(*) FROM Transaction_Detail WHERE judgement='OK'")->fetchColumn();
$ngCount      = $pdo->query("SELECT COUNT(*) FROM Transaction_Detail WHERE judgement='NG'")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM Transaction_Header WHERE status='Pending'")->fetchColumn();

// Today OK/NG
$todayOK = $pdo->query("SELECT COUNT(*) FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id=TH.transaction_id WHERE DATE(TH.receive_date)=CURDATE() AND TD.judgement='OK'")->fetchColumn();
$todayNG = $pdo->query("SELECT COUNT(*) FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id=TH.transaction_id WHERE DATE(TH.receive_date)=CURDATE() AND TD.judgement='NG'")->fetchColumn();

// Weekly trend
$weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $ok = $pdo->prepare("SELECT COUNT(*) FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id=TH.transaction_id WHERE DATE(TH.receive_date)=:d AND TD.judgement='OK'"); $ok->execute([':d'=>$d]);
    $ng = $pdo->prepare("SELECT COUNT(*) FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id=TH.transaction_id WHERE DATE(TH.receive_date)=:d AND TD.judgement='NG'"); $ng->execute([':d'=>$d]);
    $weeklyData[] = ['label' => date('D d', strtotime("-{$i} days")), 'ok' => (int)$ok->fetchColumn(), 'ng' => (int)$ng->fetchColumn()];
}

// Monthly trend
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $ok = $pdo->prepare("SELECT COUNT(*) FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id=TH.transaction_id WHERE DATE_FORMAT(TH.receive_date,'%Y-%m')=:m AND TD.judgement='OK'"); $ok->execute([':m'=>$m]);
    $ng = $pdo->prepare("SELECT COUNT(*) FROM Transaction_Detail TD JOIN Transaction_Header TH ON TD.transaction_id=TH.transaction_id WHERE DATE_FORMAT(TH.receive_date,'%Y-%m')=:m AND TD.judgement='NG'"); $ng->execute([':m'=>$m]);
    $monthlyData[] = ['label' => date('M', strtotime("-{$i} months")), 'ok' => (int)$ok->fetchColumn(), 'ng' => (int)$ng->fetchColumn()];
}

// Equipment Ranking
$equipRank = $pdo->query("SELECT E.equipment_name, COUNT(*) AS cnt FROM Transaction_Header TH JOIN Equipments E ON TH.equipment_id=E.equipment_id GROUP BY E.equipment_name ORDER BY cnt DESC LIMIT 5")->fetchAll();
$maxEquip = !empty($equipRank) ? $equipRank[0]['cnt'] : 1;

// Inspector Performance (no avg)
$inspPerf = $pdo->query("SELECT IU.name, COUNT(*) AS total, SUM(CASE WHEN TD.judgement='OK' THEN 1 ELSE 0 END) AS ok_cnt, SUM(CASE WHEN TD.judgement='NG' THEN 1 ELSE 0 END) AS ng_cnt FROM Transaction_Detail TD JOIN Internal_Users IU ON TD.internal_id=IU.user_id GROUP BY IU.name ORDER BY total DESC LIMIT 5")->fetchAll();

// Recent Activities
$recentItems = $pdo->query("SELECT TH.transaction_id, TH.dmc, TH.line, TH.receive_date, TH.status, EU.external_name AS sender, IU.name AS receiver, E.equipment_name FROM Transaction_Header TH JOIN External_Users EU ON TH.external_id=EU.external_id JOIN Internal_Users IU ON TH.internal_id=IU.user_id JOIN Equipments E ON TH.equipment_id=E.equipment_id ORDER BY TH.receive_date DESC LIMIT 10")->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<!-- Greeting -->
<div class="mb-8 anim-fade-up">
    <h2 class="text-2xl font-extrabold text-white">
        <?php date_default_timezone_set('Asia/Bangkok'); $h=(int)date('G'); if($h<12) echo 'Good Morning'; elseif($h<17) echo 'Good Afternoon'; else echo 'Good Evening'; ?>, <span class="text-gradient"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span> 
    </h2>
    <p class="text-slate-500 mt-1 font-medium">Here's what's happening in your lab today.</p>
</div>

<!-- Metric Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 tilt-card shimmer-border hover:border-indigo-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-1">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Today</span>
            <div class="w-9 h-9 rounded-xl bg-indigo-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
        </div>
        <p class="text-3xl font-extrabold text-gradient-blue metric-value font-mono anim-count delay-2"><?= $todayCount ?></p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Items received today</p>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 tilt-card shimmer-border hover:border-purple-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-2">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">This Month</span>
            <div class="w-9 h-9 rounded-xl bg-purple-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
        </div>
        <p class="text-3xl font-extrabold text-gradient metric-value font-mono anim-count delay-2"><?= $monthCount ?></p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Items this month</p>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 tilt-card shimmer-border hover:border-emerald-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-3">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">OK</span>
            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        </div>
        <p class="text-3xl font-extrabold text-gradient-emerald metric-value font-mono anim-count delay-3"><?= $okCount ?></p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Passed judgements</p>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 tilt-card shimmer-border hover:border-red-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">NG</span>
            <div class="w-9 h-9 rounded-xl bg-red-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        </div>
        <p class="text-3xl font-extrabold text-gradient-red metric-value font-mono anim-count delay-3"><?= $ngCount ?></p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Failed judgements</p>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 tilt-card shimmer-border hover:border-amber-500/30 transition-all duration-300 hover-lift hover-glow anim-fade-up delay-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending</span>
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        </div>
        <p class="text-3xl font-extrabold text-gradient-amber metric-value font-mono anim-count delay-4"><?= $pendingCount ?></p>
        <p class="text-xs text-slate-500 mt-1 font-medium">In the lab now</p>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 tilt-card shimmer-border anim-fade-up delay-3 hover-lift">
        <h3 class="text-sm font-bold text-white mb-0.5">Today's Results</h3>
        <p class="text-xs text-slate-500 mb-4 font-medium"><?= date('d M Y') ?>  <?= $todayOK + $todayNG ?> tests</p>
        <div class="flex items-center justify-center" style="height: 200px;">
            <?php if ($todayOK == 0 && $todayNG == 0): ?>
                <div class="text-center"><p class="text-slate-600 text-sm">No test results today</p></div>
            <?php else: ?>
                <canvas id="donutChart" style="cursor:pointer;"></canvas>
            <?php endif; ?>
        </div>
        <div class="flex justify-center gap-6 mt-4">
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-400"></span><span class="text-xs text-slate-400 font-medium">OK: <strong class="text-emerald-400"><?= $todayOK ?></strong></span></div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-400"></span><span class="text-xs text-slate-400 font-medium">NG: <strong class="text-red-400"><?= $todayNG ?></strong></span></div>
        </div>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 tilt-card shimmer-border anim-fade-up delay-4 hover-lift">
        <h3 class="text-sm font-bold text-white mb-0.5">Weekly Trend</h3>
        <p class="text-xs text-slate-500 mb-4 font-medium">Last 7 days OK vs NG</p>
        <div style="height: 230px;"><canvas id="weeklyChart" style="cursor:pointer;"></canvas></div>
    </div>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 tilt-card shimmer-border anim-fade-up delay-5 hover-lift">
        <h3 class="text-sm font-bold text-white mb-0.5">Monthly Trend</h3>
        <p class="text-xs text-slate-500 mb-4 font-medium">Last 6 months overview</p>
        <div style="height: 230px;"><canvas id="monthlyChart" style="cursor:pointer;"></canvas></div>
    </div>
</div>

<!-- Analytics -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 tilt-card shimmer-border anim-fade-up delay-4 hover-lift">
        <div class="flex items-center gap-2 mb-5">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <h3 class="text-sm font-bold text-white">Equipment Usage Ranking</h3>
        </div>
        <?php if (empty($equipRank)): ?>
            <p class="text-slate-600 text-sm text-center py-4">No data yet</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($equipRank as $i => $eq):
                    $pct = round(($eq['cnt'] / $maxEquip) * 100);
                    $colors = ['from-indigo-500 to-purple-500', 'from-blue-500 to-cyan-500', 'from-emerald-500 to-teal-500', 'from-amber-500 to-orange-500', 'from-pink-500 to-rose-500'];
                ?>
                    <div class="anim-fade-up" style="animation-delay: <?= 0.5 + ($i * 0.1) ?>s">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm text-slate-300 font-medium"><?= htmlspecialchars($eq['equipment_name']) ?></span>
                            <span class="text-xs font-bold text-slate-400 font-mono"><?= $eq['cnt'] ?> jobs</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2"><div class="bg-gradient-to-r <?= $colors[$i % 5] ?> h-2 rounded-full progress-bar" style="width: <?= $pct ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Inspector Performance (no avg) -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 tilt-card shimmer-border anim-fade-up delay-5 hover-lift">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <h3 class="text-sm font-bold text-white">Inspector Performance</h3>
            </div>
            <a href="performance.php" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors font-medium">View Details </a>
        </div>
        <?php if (empty($inspPerf)): ?>
            <p class="text-slate-600 text-sm text-center py-4">No data yet</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($inspPerf as $i => $ip): ?>
                    <div class="flex items-center gap-4 p-3 rounded-xl bg-slate-800/30 hover:bg-slate-800/50 transition-all duration-300 anim-fade-up" style="animation-delay: <?= 0.5 + ($i * 0.1) ?>s">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0"><?= strtoupper(substr($ip['name'], 0, 1)) ?></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($ip['name']) ?></p>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-xs font-medium text-slate-500 font-mono"><?= $ip['total'] ?> tests</span>
                                <span class="text-xs font-medium text-emerald-400 font-mono"><?= $ip['ok_cnt'] ?> OK</span>
                                <span class="text-xs font-medium text-red-400 font-mono"><?= $ip['ng_cnt'] ?> NG</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Activities -->
<div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden anim-fade-up delay-6">
    <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
        <h3 class="text-base font-bold text-white">Recent Activities</h3>
        <span class="text-xs text-slate-500 font-medium">Last 10 transactions</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-800">
                    <th class="px-6 py-3">ID</th><th class="px-6 py-3">Line</th><th class="px-6 py-3">DMC</th><th class="px-6 py-3">Equipment</th><th class="px-6 py-3">Sender</th><th class="px-6 py-3">Receiver</th><th class="px-6 py-3">Received</th><th class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                <?php if (empty($recentItems)): ?>
                    <tr><td colspan="8" class="px-6 py-10 text-center text-slate-600">No transactions yet. <a href="receive_job.php" class="text-indigo-400 hover:underline font-medium">Receive a job</a> to get started.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentItems as $i => $item): ?>
                        <tr class="hover:bg-slate-800/30 transition-all duration-200 table-row-anim" style="animation-delay: <?= 0.7 + ($i * 0.06) ?>s">
                            <td class="px-6 py-3 font-mono text-xs text-indigo-400 font-bold">#<?= $item['transaction_id'] ?></td>
                            <td class="px-6 py-3 text-white font-medium"><?= htmlspecialchars($item['line'] ?? '') ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($item['dmc'] ?? '') ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($item['equipment_name']) ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($item['sender']) ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($item['receiver']) ?></td>
                            <td class="px-6 py-3 text-slate-400 text-xs font-mono"><span class="relative-time" data-time="<?= date('c', strtotime($item['receive_date'])) ?>"><?= date('d M Y H:i', strtotime($item['receive_date'])) ?></span></td>
                            <td class="px-6 py-3">
                                <?php $sc = match($item['status']) { 'Pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20', 'Completed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', default => 'bg-slate-500/10 text-slate-400 border-slate-500/20' }; ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $sc ?>"><?= htmlspecialchars($item['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    Chart.defaults.font.family = 'Outfit, sans-serif';
    Chart.defaults.font.weight = '500';
    Chart.defaults.color = '#94a3b8';
    const tooltipStyle = { backgroundColor: 'rgba(15,23,42,0.95)', borderColor: 'rgba(99,102,241,0.2)', borderWidth: 1, cornerRadius: 12, padding: 14, titleFont: { weight: '700', size: 13 }, bodyFont: { size: 12 } };

    <?php if ($todayOK > 0 || $todayNG > 0): ?>
    const donutChart = new Chart(document.getElementById('donutChart'), {
        type: 'doughnut',
        data: { labels: ['OK','NG'], datasets: [{ data: [<?=$todayOK?>,<?=$todayNG?>], backgroundColor: ['rgba(52,211,153,0.85)','rgba(248,113,113,0.85)'], borderColor: ['rgba(52,211,153,1)','rgba(248,113,113,1)'], borderWidth: 2, hoverOffset: 10, spacing: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false }, tooltip: tooltipStyle }, animation: { animateRotate: true, duration: 1200, easing: 'easeOutQuart' }, onClick: (e, els) => { if(els.length){ const label = ['OK','NG'][els[0].index]; window.location.href='report.php?date_from='+new Date().toISOString().slice(0,10)+'&date_to='+new Date().toISOString().slice(0,10)+'&judgement='+label; } } }
    });
    <?php endif; ?>

    const weeklyChart = new Chart(document.getElementById('weeklyChart'), {
        type: 'bar',
        data: { labels: [<?=implode(',',array_map(fn($d)=>"'{$d['label']}'", $weeklyData))?>], datasets: [
            { label: 'OK', data: [<?=implode(',',array_column($weeklyData,'ok'))?>], backgroundColor: 'rgba(52,211,153,0.75)', borderColor: 'rgba(52,211,153,1)', borderWidth: 1, borderRadius: 8, borderSkipped: false },
            { label: 'NG', data: [<?=implode(',',array_column($weeklyData,'ng'))?>], backgroundColor: 'rgba(248,113,113,0.75)', borderColor: 'rgba(248,113,113,1)', borderWidth: 1, borderRadius: 8, borderSkipped: false }
        ]},
        options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { color: 'rgba(51,65,85,0.3)' }, ticks: { font: { size: 10, weight: '600' } } }, y: { beginAtZero: true, grid: { color: 'rgba(51,65,85,0.3)' }, ticks: { stepSize: 1, font: { size: 10 } } } }, plugins: { legend: { labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, weight: '600' } } }, tooltip: tooltipStyle }, animation: { duration: 1000, easing: 'easeOutQuart' }, onClick: (e, els) => { if(els.length){ const ds = weeklyChart.data.datasets[els[0].datasetIndex]; const label = ds.label; window.location.href='report.php?judgement='+label; } } }
    });

    const monthlyChart = new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: { labels: [<?=implode(',',array_map(fn($d)=>"'{$d['label']}'", $monthlyData))?>], datasets: [
            { label: 'OK', data: [<?=implode(',',array_column($monthlyData,'ok'))?>], backgroundColor: 'rgba(129,140,248,0.75)', borderColor: 'rgba(129,140,248,1)', borderWidth: 1, borderRadius: 8, borderSkipped: false },
            { label: 'NG', data: [<?=implode(',',array_column($monthlyData,'ng'))?>], backgroundColor: 'rgba(251,146,60,0.75)', borderColor: 'rgba(251,146,60,1)', borderWidth: 1, borderRadius: 8, borderSkipped: false }
        ]},
        options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { color: 'rgba(51,65,85,0.3)' }, ticks: { font: { size: 10, weight: '600' } } }, y: { beginAtZero: true, grid: { color: 'rgba(51,65,85,0.3)' }, ticks: { stepSize: 1, font: { size: 10 } } } }, plugins: { legend: { labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, weight: '600' } } }, tooltip: tooltipStyle }, animation: { duration: 1200, easing: 'easeOutQuart' }, onClick: (e, els) => { if(els.length){ const ds = monthlyChart.data.datasets[els[0].datasetIndex]; const label = ds.label; window.location.href='report.php?judgement='+label; } } }
    });

    // Relative Time for Recent Activities
    (function() {
        function timeAgo(dateStr) {
            const now = new Date();
            const past = new Date(dateStr);
            const diffMs = now - past;
            const diffSec = Math.floor(diffMs / 1000);
            const diffMin = Math.floor(diffSec / 60);
            const diffHr = Math.floor(diffMin / 60);
            const diffDay = Math.floor(diffHr / 24);

            if (diffSec < 60) return 'just now';
            if (diffMin < 60) return diffMin + ' min' + (diffMin > 1 ? 's' : '') + ' ago';
            if (diffHr < 24) return diffHr + ' hr' + (diffHr > 1 ? 's' : '') + ' ago';
            if (diffDay < 7) return diffDay + ' day' + (diffDay > 1 ? 's' : '') + ' ago';
            return past.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
        }

        document.querySelectorAll('.relative-time').forEach(el => {
            const t = el.getAttribute('data-time');
            if (t) {
                el.textContent = timeAgo(t);
                el.title = new Date(t).toLocaleString('th-TH');
            }
        });
    })();
</script>
<?php require_once 'footer.php'; ?>

