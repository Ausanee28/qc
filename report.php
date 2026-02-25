<?php
$pageTitle    = 'Report';
$pageSubtitle = 'View and export completed test results';
require_once 'includes/db.php';
require_once 'includes/header.php';

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$judgement = $_GET['judgement']   ?? '';

$sql = "SELECT TH.transaction_id, TH.line, TH.receive_date, EU.external_name, TH.dmc, E.equipment_name, TM.method_name, IU.name AS inspector_name, TD.start_time, TD.end_time, TD.judgement, TD.remark
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

<style>
input[type="date"] { color-scheme: dark; cursor: pointer; }
input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
.report-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 11px; }
.report-table th { padding: 10px 8px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 1px solid #1e293b; background: rgba(15,23,42,0.4); }
.report-table td { padding: 8px 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.report-table tbody tr:hover { background: rgba(30,41,59,0.3); }
.report-table tbody tr { border-bottom: 1px solid rgba(30,41,59,0.6); }
</style>

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
    <table class="report-table">
        <colgroup>
            <col style="width:4%">
            <col style="width:11%">
            <col style="width:8%">
            <col style="width:6%">
            <col style="width:8%">
            <col style="width:10%">
            <col style="width:8%">
            <col style="width:11%">
            <col style="width:11%">
            <col style="width:5%">
            <col style="width:12%">
            <col style="width:6%">
        </colgroup>
        <thead>
            <tr>
                <th>Line</th>
                <th>Date</th>
                <th>Sender</th>
                <th>DMC</th>
                <th>Detail</th>
                <th>Process</th>
                <th>Inspector</th>
                <th>Start</th>
                <th>End</th>
                <th>Result</th>
                <th>Remark</th>
                <th style="text-align:center">PDF</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="12" style="text-align:center;padding:40px 8px;color:#475569;font-size:13px;">No test results found for the selected date range.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $i => $row): ?>
                    <tr class="table-row-anim" style="animation-delay: <?= 0.4 + ($i * 0.06) ?>s">
                        <td class="text-white"><?= htmlspecialchars($row['line'] ?? '') ?></td>
                        <td class="text-slate-400"><?= $row['receive_date'] ? date('d/m/Y H:i', strtotime($row['receive_date'])) : '' ?></td>
                        <td class="text-slate-300"><?= htmlspecialchars($row['external_name']) ?></td>
                        <td class="text-slate-300"><?= htmlspecialchars($row['dmc'] ?? '') ?></td>
                        <td class="text-slate-300"><?= htmlspecialchars($row['equipment_name']) ?></td>
                        <td class="text-slate-300"><?= htmlspecialchars($row['method_name']) ?></td>
                        <td class="text-slate-300"><?= htmlspecialchars($row['inspector_name']) ?></td>
                        <td class="text-slate-400"><?= $row['start_time'] ? date('d/m/Y H:i', strtotime($row['start_time'])) : '' ?></td>
                        <td class="text-slate-400"><?= $row['end_time'] ? date('d/m/Y H:i', strtotime($row['end_time'])) : '' ?></td>
                        <td>
                            <?php if ($row['judgement'] === 'OK'): ?>
                                <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:10px;font-weight:600;background:rgba(16,185,129,0.1);color:#34d399;border:1px solid rgba(16,185,129,0.2);">OK</span>
                            <?php elseif ($row['judgement'] === 'NG'): ?>
                                <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:10px;font-weight:600;background:rgba(239,68,68,0.1);color:#f87171;border:1px solid rgba(239,68,68,0.2);">NG</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-slate-400"><?= htmlspecialchars($row['remark'] ?? '') ?></td>
                        <td style="text-align:center">
                            <a href="generate_pdf.php?id=<?= $row['transaction_id'] ?? '' ?>" target="_blank" style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.2);border-radius:8px;color:#818cf8;font-size:10px;font-weight:700;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='rgba(99,102,241,0.2)'" onmouseout="this.style.background='rgba(99,102,241,0.1)'">
                                <svg style="width:12px;height:12px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                PDF
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once 'includes/footer.php'; ?>