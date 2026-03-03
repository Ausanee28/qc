<?php
$pageTitle    = 'Performance';
$pageSubtitle = 'Inspector test duration analysis (end time - start time)';
require_once 'includes/db.php';
require_once 'includes/header.php';

try {
    // All inspectors summary
$inspectors = $pdo->query("
    SELECT 
        IU.user_id,
        IU.name,
        COUNT(*) AS total_tests,
        SUM(CASE WHEN TD.judgement='OK' THEN 1 ELSE 0 END) AS ok_cnt,
        SUM(CASE WHEN TD.judgement='NG' THEN 1 ELSE 0 END) AS ng_cnt,
        ROUND(AVG(CASE WHEN TD.start_time IS NOT NULL AND TD.end_time IS NOT NULL AND TD.end_time > TD.start_time 
            THEN TIMESTAMPDIFF(SECOND, TD.start_time, TD.end_time) END)) AS avg_sec,
        MIN(CASE WHEN TD.start_time IS NOT NULL AND TD.end_time IS NOT NULL AND TD.end_time > TD.start_time 
            THEN TIMESTAMPDIFF(SECOND, TD.start_time, TD.end_time) END) AS min_sec,
        MAX(CASE WHEN TD.start_time IS NOT NULL AND TD.end_time IS NOT NULL AND TD.end_time > TD.start_time 
            THEN TIMESTAMPDIFF(SECOND, TD.start_time, TD.end_time) END) AS max_sec
    FROM Transaction_Detail TD
    JOIN Internal_Users IU ON TD.internal_id = IU.user_id
    GROUP BY IU.user_id, IU.name
    ORDER BY total_tests DESC
")->fetchAll();

// All detailed test records with duration
$details = $pdo->query("
    SELECT 
        TD.detail_id,
        IU.name AS inspector,
        TH.dmc,
        TH.line,
        E.equipment_name,
        TD.judgement,
        TD.start_time,
        TD.end_time,
        CASE WHEN TD.start_time IS NOT NULL AND TD.end_time IS NOT NULL AND TD.end_time > TD.start_time
            THEN TIMESTAMPDIFF(SECOND, TD.start_time, TD.end_time) ELSE NULL END AS duration_sec
    FROM Transaction_Detail TD
    JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id
    JOIN Internal_Users IU ON TD.internal_id = IU.user_id
    JOIN Equipments E ON TH.equipment_id = E.equipment_id
    WHERE TD.start_time IS NOT NULL AND TD.end_time IS NOT NULL
    ORDER BY TD.end_time DESC
    LIMIT 50
")->fetchAll();

} catch (PDOException $e) {
    $dbError = "Failed to load performance data: " . $e->getMessage();
    $inspectors = [];
    $details = [];
}

// Helper: format seconds to readable
function fmtDuration($sec) {
    if ($sec === null) return '-';
    if ($sec < 60) return $sec . 's';
    $m = floor($sec / 60);
    $s = $sec % 60;
    if ($m < 60) return $m . 'm ' . $s . 's';
    $h = floor($m / 60);
    $m = $m % 60;
    return $h . 'h ' . $m . 'm';
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<!-- Page Header -->
<?php if (isset($dbError) && $dbError): ?>
    <div class="mb-6 flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-300 px-5 py-4 rounded-xl text-sm blur-in">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= htmlspecialchars($dbError) ?>
    </div>
<?php endif; ?>
<div class="mb-8 anim-fade-up">
    <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/20">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <h2 class="text-xl font-extrabold text-white">Test Duration Analysis</h2>
            <p class="text-sm text-slate-500 font-medium">How long each inspector takes per test (end time  start time)</p>
        </div>
    </div>
</div>

<!-- Inspector Summary Cards -->
<?php if (empty($inspectors)): ?>
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center anim-fade-up">
        <svg class="w-12 h-12 text-slate-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <p class="text-slate-500 font-medium">No test data yet.</p>
        <p class="text-slate-600 text-sm mt-1">Execute some tests to see duration analysis here.</p>
    </div>
<?php else: ?>

<!-- Summary Cards per Inspector -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
    <?php foreach ($inspectors as $i => $insp): ?>
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-indigo-500/30 transition-all duration-300 hover-lift anim-fade-up" style="animation-delay: <?= 0.1 + ($i * 0.1) ?>s">
            <!-- Inspector Header -->
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-indigo-500/20">
                    <?= strtoupper(substr($insp['name'], 0, 1)) ?>
                </div>
                <div>
                    <p class="text-sm font-bold text-white"><?= htmlspecialchars($insp['name']) ?></p>
                    <p class="text-xs text-slate-500 font-medium font-mono"><?= $insp['total_tests'] ?> tests total</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="bg-slate-800/40 rounded-xl p-3 text-center">
                    <p class="text-lg font-extrabold text-gradient-amber font-mono"><?= fmtDuration($insp['avg_sec']) ?></p>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium">Average</p>
                </div>
                <div class="bg-slate-800/40 rounded-xl p-3 text-center">
                    <p class="text-lg font-extrabold text-gradient-emerald font-mono"><?= fmtDuration($insp['min_sec']) ?></p>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium">Fastest</p>
                </div>
                <div class="bg-slate-800/40 rounded-xl p-3 text-center">
                    <p class="text-lg font-extrabold text-gradient-red font-mono"><?= fmtDuration($insp['max_sec']) ?></p>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium">Slowest</p>
                </div>
            </div>

            <!-- OK / NG bar -->
            <div class="flex items-center gap-2">
                <div class="flex-1 bg-slate-800 rounded-full h-2 overflow-hidden">
                    <?php $okPct = $insp['total_tests'] > 0 ? round(($insp['ok_cnt'] / $insp['total_tests']) * 100) : 0; ?>
                    <div class="bg-gradient-to-r from-emerald-500 to-emerald-400 h-2 rounded-full" style="width: <?= $okPct ?>%"></div>
                </div>
                <span class="text-xs font-medium text-emerald-400 font-mono"><?= $insp['ok_cnt'] ?> OK</span>
                <span class="text-xs font-medium text-red-400 font-mono"><?= $insp['ng_cnt'] ?> NG</span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Bar Chart: Avg Duration per Inspector -->
<div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 mb-8 anim-fade-up delay-3 hover-lift">
    <h3 class="text-sm font-bold text-white mb-0.5">Average Test Duration by Inspector</h3>
    <p class="text-xs text-slate-500 mb-4 font-medium">Comparing average time spent per test across inspectors</p>
    <div style="height: 260px;"><canvas id="avgDurationChart"></canvas></div>
</div>

<!-- Detailed Test History -->
<div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden anim-fade-up delay-4">
    <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
        <h3 class="text-base font-bold text-white">Test Duration History</h3>
        <span class="text-xs text-slate-500 font-medium">Last 50 completed tests</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-800">
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">Inspector</th>
                    <th class="px-6 py-3">Line</th>
                    <th class="px-6 py-3">DMC</th>
                    <th class="px-6 py-3">Equipment</th>
                    <th class="px-6 py-3">Start</th>
                    <th class="px-6 py-3">End</th>
                    <th class="px-6 py-3">Duration</th>
                    <th class="px-6 py-3">Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                <?php if (empty($details)): ?>
                    <tr><td colspan="9" class="px-6 py-10 text-center text-slate-600">No completed tests with duration data.</td></tr>
                <?php else: ?>
                    <?php foreach ($details as $i => $d): ?>
                        <tr class="hover:bg-slate-800/30 transition-all duration-200 table-row-anim" style="animation-delay: <?= 0.5 + ($i * 0.04) ?>s">
                            <td class="px-6 py-3 font-mono text-xs text-indigo-400 font-bold">#<?= $d['detail_id'] ?></td>
                            <td class="px-6 py-3 text-white font-medium"><?= htmlspecialchars($d['inspector']) ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($d['line'] ?? '-') ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($d['dmc'] ?? '-') ?></td>
                            <td class="px-6 py-3 text-slate-300"><?= htmlspecialchars($d['equipment_name']) ?></td>
                            <td class="px-6 py-3 text-slate-400 text-xs font-mono"><?= $d['start_time'] ? date('H:i:s', strtotime($d['start_time'])) : '-' ?></td>
                            <td class="px-6 py-3 text-slate-400 text-xs font-mono"><?= $d['end_time'] ? date('H:i:s', strtotime($d['end_time'])) : '-' ?></td>
                            <td class="px-6 py-3">
                                <span class="font-mono text-sm font-bold text-gradient-amber"><?= fmtDuration($d['duration_sec']) ?></span>
                            </td>
                            <td class="px-6 py-3">
                                <?php if ($d['judgement'] === 'OK'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">OK</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border bg-red-500/10 text-red-400 border-red-500/20">NG</span>
                                <?php endif; ?>
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

    <?php
    $chartLabels = [];
    $chartAvg = [];
    $chartMin = [];
    $chartMax = [];
    $barColors = ['rgba(129,140,248,0.75)', 'rgba(52,211,153,0.75)', 'rgba(251,146,60,0.75)', 'rgba(248,113,113,0.75)', 'rgba(168,85,247,0.75)', 'rgba(56,189,248,0.75)', 'rgba(251,191,36,0.75)'];
    $borderColors = ['rgba(129,140,248,1)', 'rgba(52,211,153,1)', 'rgba(251,146,60,1)', 'rgba(248,113,113,1)', 'rgba(168,85,247,1)', 'rgba(56,189,248,1)', 'rgba(251,191,36,1)'];
    foreach ($inspectors as $idx => $ins) {
        $chartLabels[] = $ins['name'];
        $chartAvg[] = $ins['avg_sec'] !== null ? round($ins['avg_sec'] / 60, 1) : 0;
        $chartMin[] = $ins['min_sec'] !== null ? round($ins['min_sec'] / 60, 1) : 0;
        $chartMax[] = $ins['max_sec'] !== null ? round($ins['max_sec'] / 60, 1) : 0;
    }
    ?>

    new Chart(document.getElementById('avgDurationChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                {
                    label: 'Avg (min)',
                    data: <?= json_encode($chartAvg) ?>,
                    backgroundColor: <?= json_encode(array_slice($barColors, 0, count($chartLabels))) ?>,
                    borderColor: <?= json_encode(array_slice($borderColors, 0, count($chartLabels))) ?>,
                    borderWidth: 1, borderRadius: 10, borderSkipped: false
                },
                {
                    label: 'Fastest (min)',
                    data: <?= json_encode($chartMin) ?>,
                    backgroundColor: 'rgba(52,211,153,0.4)',
                    borderColor: 'rgba(52,211,153,1)',
                    borderWidth: 1, borderRadius: 10, borderSkipped: false
                },
                {
                    label: 'Slowest (min)',
                    data: <?= json_encode($chartMax) ?>,
                    backgroundColor: 'rgba(248,113,113,0.4)',
                    borderColor: 'rgba(248,113,113,1)',
                    borderWidth: 1, borderRadius: 10, borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { color: 'rgba(51,65,85,0.3)' }, ticks: { font: { size: 11, weight: '600' } } },
                y: { beginAtZero: true, grid: { color: 'rgba(51,65,85,0.3)' }, ticks: { font: { size: 10 }, callback: v => v + ' min' } }
            },
            plugins: {
                legend: { labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, weight: '600' } } },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,0.95)', borderColor: 'rgba(99,102,241,0.2)', borderWidth: 1,
                    cornerRadius: 12, padding: 14,
                    callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y + ' min' }
                }
            },
            animation: { duration: 1200, easing: 'easeOutQuart' }
        }
    });
</script>

<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>

