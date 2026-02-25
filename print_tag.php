<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT TH.transaction_id, TH.dmc, TH.line, TH.receive_date, TH.status,
           EU.external_name AS sender, IU.name AS receiver, E.equipment_name
    FROM Transaction_Header TH
    JOIN External_Users EU ON TH.external_id = EU.external_id
    JOIN Internal_Users IU ON TH.internal_id = IU.user_id
    JOIN Equipments E ON TH.equipment_id = E.equipment_id
    WHERE TH.transaction_id = :id
");
$stmt->execute([':id' => $id]);
$job = $stmt->fetch();
if (!$job) { echo '<h1>Job not found</h1>'; exit; }
$qrData = $job['dmc'] ?: ('JOB-' . $job['transaction_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Tag #<?= $job['transaction_id'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        @media print {
            body { background: white !important; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .tag-card { box-shadow: none !important; border: 2px solid #000 !important; }
            .tag-card * { color: #000 !important; }
        }
    </style>
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center p-8">
    <div class="no-print fixed top-6 left-6 flex gap-3 z-50">
        <a href="receive_job.php" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm font-medium transition-all border border-slate-700">Back</a>
        <button onclick="window.print()" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-500/25 transition-all cursor-pointer">Print Tag</button>
    </div>
    <div class="tag-card bg-white rounded-2xl shadow-2xl shadow-black/30 w-[400px] overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 text-center">
            <h1 class="text-white text-lg font-extrabold tracking-wide">QC LAB TRACKING</h1>
            <p class="text-indigo-200 text-xs font-medium mt-0.5">Quality Control Inspection Tag</p>
        </div>
        <div class="px-6 py-5">
            <div class="flex justify-center mb-5"><div id="qrcode" class="p-3 bg-white border-2 border-slate-200 rounded-xl"></div></div>
            <div class="text-center mb-4">
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-1">DMC Code</p>
                <p class="text-2xl font-extrabold text-slate-800 font-mono tracking-wider"><?= htmlspecialchars($job['dmc'] ?: 'N/A') ?></p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="bg-slate-50 rounded-lg px-3 py-2.5"><p class="text-[10px] text-slate-400 font-bold uppercase">Job ID</p><p class="text-slate-800 font-bold font-mono">#<?= $job['transaction_id'] ?></p></div>
                <div class="bg-slate-50 rounded-lg px-3 py-2.5"><p class="text-[10px] text-slate-400 font-bold uppercase">Line</p><p class="text-slate-800 font-bold"><?= htmlspecialchars($job['line'] ?: '-') ?></p></div>
                <div class="bg-slate-50 rounded-lg px-3 py-2.5"><p class="text-[10px] text-slate-400 font-bold uppercase">Equipment</p><p class="text-slate-800 font-bold text-xs"><?= htmlspecialchars($job['equipment_name']) ?></p></div>
                <div class="bg-slate-50 rounded-lg px-3 py-2.5"><p class="text-[10px] text-slate-400 font-bold uppercase">Received</p><p class="text-slate-800 font-bold text-xs"><?= date('d/m/Y H:i', strtotime($job['receive_date'])) ?></p></div>
                <div class="bg-slate-50 rounded-lg px-3 py-2.5 col-span-2"><p class="text-[10px] text-slate-400 font-bold uppercase">Sender / Receiver</p><p class="text-slate-800 font-bold text-xs"><?= htmlspecialchars($job['sender']) ?> / <?= htmlspecialchars($job['receiver']) ?></p></div>
            </div>
        </div>
        <div class="bg-slate-50 px-6 py-3 flex items-center justify-between border-t border-slate-200">
            <span class="text-[10px] text-slate-400 font-semibold uppercase">Status: <?= htmlspecialchars($job['status']) ?></span>
            <span class="text-[10px] text-slate-400 font-mono"><?= date('d/m/Y H:i') ?></span>
        </div>
    </div>
    <script>new QRCode(document.getElementById('qrcode'), { text: '<?= addslashes($qrData) ?>', width: 150, height: 150, colorDark: '#1e293b', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.H });</script>
</body>
</html>