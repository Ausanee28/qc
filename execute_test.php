<?php
$pageTitle    = 'Execute Test';
$pageSubtitle = 'Record test results for a pending job';
require_once 'includes/db.php';
require_once 'includes/header.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = (int)($_POST['transaction_id'] ?? 0);
    $method_id      = (int)($_POST['method_id'] ?? 0);
    $internal_id    = (int)($_POST['internal_id'] ?? 0);
    $start_time     = trim($_POST['start_time'] ?? '');
    $end_time       = trim($_POST['end_time'] ?? '');
    $judgement       = trim($_POST['judgement'] ?? '');
    $remark         = trim($_POST['remark'] ?? '');

    if ($transaction_id <= 0 || $method_id <= 0 || $internal_id <= 0 || $judgement === '') {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO Transaction_Detail (transaction_id, method_id, internal_id, start_time, end_time, judgement, remark) VALUES (:tid, :mid, :iid, :st, :et, :jdg, :rem)");
            $stmt->execute([':tid' => $transaction_id, ':mid' => $method_id, ':iid' => $internal_id, ':st' => $start_time ?: null, ':et' => $end_time ?: null, ':jdg' => $judgement, ':rem' => $remark]);
            $stmtUpdate = $pdo->prepare("UPDATE Transaction_Header SET status = 'Completed', return_date = NOW() WHERE transaction_id = :tid");
            $stmtUpdate->execute([':tid' => $transaction_id]);
            $pdo->commit();
            $success = "Test result recorded and Job #{$transaction_id} marked as Completed!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$pendingJobs = $pdo->query("SELECT TH.transaction_id, TH.dmc, TH.line, E.equipment_name FROM Transaction_Header TH JOIN Equipments E ON TH.equipment_id = E.equipment_id WHERE TH.status = 'Pending' ORDER BY TH.receive_date DESC")->fetchAll();
$methods   = $pdo->query("SELECT method_id, method_name FROM Test_Methods ORDER BY method_name")->fetchAll();
$internals = $pdo->query("SELECT user_id, name FROM Internal_Users ORDER BY name")->fetchAll();
?>

<div class="max-w-2xl mx-auto">
    <?php if ($success): ?>
        <div class="mb-6 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 px-5 py-4 rounded-xl text-sm anim-scale-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-6 flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-300 px-5 py-4 rounded-xl text-sm anim-scale-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 md:p-8 anim-fade-up delay-1">
        <?php if (empty($pendingJobs) && !$success): ?>
            <div class="text-center py-10 anim-fade-in">
                <p class="text-slate-400 mb-4">No pending jobs available for testing.</p>
                <a href="receive_job.php" class="btn-press inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-all duration-300">Receive a Job First</a>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-6">
                <div class="anim-fade-up delay-2">
                    <label for="transaction_id" class="block text-sm font-medium text-slate-300 mb-2">Pending Job <span class="text-red-400">*</span></label>
                    <select id="transaction_id" name="transaction_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 appearance-none hover:border-slate-600">
                        <option value="">-- Select a Pending Job --</option>
                        <?php foreach ($pendingJobs as $job): ?>
                            <option value="<?= $job['transaction_id'] ?>">#<?= $job['transaction_id'] ?> - <?= htmlspecialchars($job['equipment_name']) ?> | DMC: <?= htmlspecialchars($job['dmc'] ?: 'N/A') ?> | Line: <?= htmlspecialchars($job['line'] ?: 'N/A') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="anim-fade-up delay-2">
                        <label for="method_id" class="block text-sm font-medium text-slate-300 mb-2">Test Method <span class="text-red-400">*</span></label>
                        <select id="method_id" name="method_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 appearance-none hover:border-slate-600">
                            <option value="">-- Select Method --</option>
                            <?php foreach ($methods as $m): ?>
                                <option value="<?= $m['method_id'] ?>"><?= htmlspecialchars($m['method_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="anim-fade-up delay-3">
                        <label for="internal_id" class="block text-sm font-medium text-slate-300 mb-2">Inspector <span class="text-red-400">*</span></label>
                        <select id="internal_id" name="internal_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 appearance-none hover:border-slate-600">
                            <option value="">-- Select Inspector --</option>
                            <?php foreach ($internals as $int): ?>
                                <option value="<?= $int['user_id'] ?>"><?= htmlspecialchars($int['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="anim-fade-up delay-3">
                        <label for="start_time" class="block text-sm font-medium text-slate-300 mb-2">Start Time</label>
                        <input type="datetime-local" id="start_time" name="start_time" class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 hover:border-slate-600" style="color-scheme: dark;">
                        <button type="button" onclick="document.getElementById('start_time').value=new Date(Date.now()-new Date().getTimezoneOffset()*60000).toISOString().slice(0,16)" class="btn-press mt-1.5 text-xs px-3 py-1 bg-indigo-600/20 text-indigo-400 rounded-lg hover:bg-indigo-600/30 transition-all duration-300">Now</button>
                    </div>
                    <div class="anim-fade-up delay-4">
                        <label for="end_time" class="block text-sm font-medium text-slate-300 mb-2">End Time</label>
                        <input type="datetime-local" id="end_time" name="end_time" class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 hover:border-slate-600" style="color-scheme: dark;">
                        <button type="button" onclick="document.getElementById('end_time').value=new Date(Date.now()-new Date().getTimezoneOffset()*60000).toISOString().slice(0,16)" class="btn-press mt-1.5 text-xs px-3 py-1 bg-indigo-600/20 text-indigo-400 rounded-lg hover:bg-indigo-600/30 transition-all duration-300">Now</button>
                    </div>
                </div>
                <div class="anim-fade-up delay-4">
                    <label for="judgement" class="block text-sm font-medium text-slate-300 mb-2">Judgement <span class="text-red-400">*</span></label>
                    <select id="judgement" name="judgement" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 appearance-none hover:border-slate-600">
                        <option value="">-- Select Judgement --</option>
                        <option value="OK">OK (Pass)</option>
                        <option value="NG">NG (Fail)</option>
                    </select>
                </div>
                <div class="anim-fade-up delay-5">
                    <label for="remark" class="block text-sm font-medium text-slate-300 mb-2">Remark</label>
                    <input type="text" id="remark" name="remark" class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 hover:border-slate-600" placeholder="Optional notes about this test">
                </div>
                <div class="flex items-center gap-4 pt-2 anim-fade-up delay-5">
                    <button type="submit" class="btn-press px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-300 transform hover:-translate-y-0.5">
                        <span class="flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Submit Test Result</span>
                    </button>
                    <a href="index.php" class="px-6 py-3 text-slate-400 hover:text-white transition-all duration-300 text-sm">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
