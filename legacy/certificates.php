<?php
$pageTitle    = 'Certificates';
$pageSubtitle = 'Generate and download QC test certificates as PDF';
require_once 'includes/db.php';
require_once 'includes/header.php';

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

$sql = "SELECT TH.transaction_id, TH.dmc, TH.line, TH.receive_date, TH.status,
               EU.external_name AS sender, E.equipment_name,
               COUNT(TD.detail_id) AS test_count,
               SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) AS ok_count,
               SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) AS ng_count
        FROM Transaction_Header TH
        JOIN External_Users EU ON TH.external_id = EU.external_id
        JOIN Equipments E ON TH.equipment_id = E.equipment_id
        LEFT JOIN Transaction_Detail TD ON TH.transaction_id = TD.transaction_id
        WHERE DATE(TH.receive_date) BETWEEN :df AND :dt
        GROUP BY TH.transaction_id
        ORDER BY TH.receive_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':df' => $dateFrom, ':dt' => $dateTo]);
$jobs = $stmt->fetchAll();
?>
<style>
.cert-card{transition:all .3s cubic-bezier(.22,1,.36,1)}.cert-card:hover{transform:translateY(-2px);box-shadow:0 12px 40px -10px rgba(99,102,241,.15);border-color:rgba(99,102,241,.3)}
.pdf-btn{transition:all .3s ease}.pdf-btn:hover{transform:scale(1.05);box-shadow:0 8px 25px -5px rgba(139,92,246,.4)}
.s-ok{background:rgba(16,185,129,.1);color:#34d399;border:1px solid rgba(16,185,129,.2)}.s-ng{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2)}.s-pen{background:rgba(251,191,36,.1);color:#fbbf24;border:1px solid rgba(251,191,36,.2)}
input[type="date"]{color-scheme:dark;cursor:pointer}input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(1);cursor:pointer}
</style>

<div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 mb-6">
    <form method="GET" action="certificates.php" class="flex flex-wrap items-end gap-4">
        <div><label for="date_from" class="block text-xs font-medium text-slate-400 mb-1.5">From</label>
        <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 hover:border-slate-500"></div>
        <div><label for="date_to" class="block text-xs font-medium text-slate-400 mb-1.5">To</label>
        <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 hover:border-slate-500"></div>
        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-all duration-300 flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg> Filter
        </button>
    </form>
</div>

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-slate-500">Found <span class="text-white font-semibold"><?= count($jobs) ?></span> job(s) from <span class="text-slate-300"><?= date('d M Y', strtotime($dateFrom)) ?></span> to <span class="text-slate-300"><?= date('d M Y', strtotime($dateTo)) ?></span></p>
</div>

<?php if (empty($jobs)): ?>
<div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center">
    <svg class="w-16 h-16 text-slate-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <p class="text-slate-500 text-sm">No jobs found for the selected date range.</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($jobs as $job): ?>
    <?php $allOk=$job['test_count']>0&&$job['ng_count']==0; $hasNg=$job['ng_count']>0; ?>
    <div class="cert-card bg-slate-900/60 border border-slate-800 rounded-2xl p-5 flex flex-col">
        <div class="flex items-start justify-between mb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-mono text-slate-500">QC-<?= str_pad($job['transaction_id'],5,'0',STR_PAD_LEFT) ?></span>
                    <?php if($allOk): ?><span class="s-ok text-[10px] font-bold px-2 py-0.5 rounded-full">OK</span>
                    <?php elseif($hasNg): ?><span class="s-ng text-[10px] font-bold px-2 py-0.5 rounded-full">NG</span>
                    <?php else: ?><span class="s-pen text-[10px] font-bold px-2 py-0.5 rounded-full">Pending</span><?php endif; ?>
                </div>
                <h3 class="text-base font-bold text-white"><?= htmlspecialchars($job['dmc'] ?: 'No DMC') ?></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500/20 to-purple-600/20 border border-violet-500/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
        </div>
        <div class="space-y-2 mb-4 flex-1">
            <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Line</span><span class="text-slate-300 font-medium"><?= htmlspecialchars($job['line'] ?: '-') ?></span></div>
            <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Sender</span><span class="text-slate-300 font-medium"><?= htmlspecialchars($job['sender']) ?></span></div>
            <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Equipment</span><span class="text-slate-300 font-medium"><?= htmlspecialchars($job['equipment_name']) ?></span></div>
            <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Date</span><span class="text-slate-300 font-medium"><?= date('d/m/Y H:i', strtotime($job['receive_date'])) ?></span></div>
            <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Tests</span><span class="text-slate-300 font-medium"><?= $job['test_count'] ?> test(s) — <span class="text-emerald-400"><?= $job['ok_count'] ?> OK</span><?php if($job['ng_count']>0): ?>, <span class="text-red-400"><?= $job['ng_count'] ?> NG</span><?php endif; ?></span></div>
        </div>
        <a href="generate_pdf.php?id=<?= $job['transaction_id'] ?>" target="_blank" class="pdf-btn w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white text-sm font-bold rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Download PDF Certificate
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>