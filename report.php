<?php
$pageTitle    = 'Report';
$pageSubtitle = 'View and export completed test results';
require_once 'db.php';
require_once 'header.php';

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$judgement = $_GET['judgement']   ?? '';

$sql = "SELECT TH.line, TH.receive_date, EU.external_name, TH.dmc, E.equipment_name, TM.method_name, IU.name AS inspector_name, TD.start_time, TD.end_time, TD.judgement, TD.remark
    FROM Transaction_Detail TD
    JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id
    JOIN External_Users EU ON TH.external_id = EU.external_id
    JOIN Equipments E ON TH.equipment_id = E.equipment_id
    JOIN Test_Methods TM ON TD.method_id = TM.method_id
    JOIN Internal_Users IU ON TD.internal_id = IU.user_id
    WHERE DATE(TH.receive_date) BETWEEN :df AND :dt";

$params = [':df' => $dateFrom, ':dt' => $dateTo];

if ($judgement === 'OK' || $judgement === 'NG') {
    $sql .= " AND TD.judgement = :jg";
    $params[':jg'] = $judgement;
}

$sql .= " ORDER BY TH.receive_date DESC, TD.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();
?>

<style>input[type="date"] { color-scheme: dark; cursor: pointer; } input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }</style>

<div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 mb-6 anim-fade-up delay-1">
    <form method="GET" action="report.php" class="flex flex-wrap items-end gap-4">
        <div class="anim-slide-left delay-1">
            <label for="date_from" class="block text-xs font-medium text-slate-400 mb-1.5">From</label>
            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 hover:border-slate-500">
        </div>
        <div class="anim-slide-left delay-2">
            <label for="date_to" class="block text-xs font-medium text-slate-400 mb-1.5">To</label>
            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 hover:border-slate-500">
        </div>
        <button type="submit" class="btn-press px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-all duration-300 flex items-center gap-2 cursor-pointer anim-slide-left delay-3 hover:shadow-lg hover:shadow-indigo-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg> Filter
        </button>
        <a href="export.php?date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="btn-press px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-sm font-medium rounded-xl shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition-all duration-300 flex items-center gap-2 anim-slide-left delay-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Export to Excel
        </a>
    </form>
</div>

<div class="flex items-center justify-between mb-4 anim-fade-in delay-2">
    <p class="text-sm text-slate-500">Showing <span class="text-white font-semibold"><?= count($results) ?></span> result(s) from <span class="text-slate-300"><?= date('d M Y', strtotime($dateFrom)) ?></span> to <span class="text-slate-300"><?= date('d M Y', strtotime($dateTo)) ?></span></p>
</div>

<div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden anim-fade-up delay-3">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-800 bg-slate-900/40">
                    <th class="px-5 py-3">Line</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Sender</th><th class="px-5 py-3">DMC</th><th class="px-5 py-3">Detail</th><th class="px-5 py-3">Inspection Process</th><th class="px-5 py-3">Inspector</th><th class="px-5 py-3">Start</th><th class="px-5 py-3">End</th><th class="px-5 py-3">Judgement</th><th class="px-5 py-3">Remark</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                <?php if (empty($results)): ?>
                    <tr><td colspan="11" class="px-5 py-10 text-center text-slate-600 anim-fade-in">No test results found for the selected date range.</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $i => $row): ?>
                        <tr class="hover:bg-slate-800/30 transition-all duration-200 table-row-anim" style="animation-delay: <?= 0.4 + ($i * 0.06) ?>s">
                            <td class="px-5 py-3 text-white"><?= htmlspecialchars($row['line'] ?? '') ?></td>
                            <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap"><?= $row['receive_date'] ? date('d/m/Y H:i', strtotime($row['receive_date'])) : '' ?></td>
                            <td class="px-5 py-3 text-slate-300"><?= htmlspecialchars($row['external_name']) ?></td>
                            <td class="px-5 py-3 text-slate-300"><?= htmlspecialchars($row['dmc'] ?? '') ?></td>
                            <td class="px-5 py-3 text-slate-300"><?= htmlspecialchars($row['equipment_name']) ?></td>
                            <td class="px-5 py-3 text-slate-300"><?= htmlspecialchars($row['method_name']) ?></td>
                            <td class="px-5 py-3 text-slate-300"><?= htmlspecialchars($row['inspector_name']) ?></td>
                            <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap"><?= $row['start_time'] ? date('d/m/Y H:i', strtotime($row['start_time'])) : '' ?></td>
                            <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap"><?= $row['end_time'] ? date('d/m/Y H:i', strtotime($row['end_time'])) : '' ?></td>
                            <td class="px-5 py-3">
                                <?php if ($row['judgement'] === 'OK'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">OK</span>
                                <?php elseif ($row['judgement'] === 'NG'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">NG</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-slate-400 max-w-[200px] truncate"><?= htmlspecialchars($row['remark'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'footer.php'; ?>
