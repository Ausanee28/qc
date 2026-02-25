<?php
$pageTitle    = 'Receive Job';
$pageSubtitle = 'Record a new incoming inspection job';
require_once 'db.php';
require_once 'header.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $external_id  = (int)($_POST['external_id'] ?? 0);
    $internal_id  = (int)($_POST['internal_id'] ?? 0);
    $equipment_id = (int)($_POST['equipment_id'] ?? 0);
    $dmc          = trim($_POST['dmc'] ?? '');
    $line         = trim($_POST['line'] ?? '');

    if ($external_id <= 0 || $internal_id <= 0 || $equipment_id <= 0) {
        $error = 'Please select Sender, Receiver, and Equipment.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Transaction_Header (external_id, internal_id, equipment_id, dmc, line, receive_date, status) VALUES (:ext, :int, :eq, :dmc, :line, NOW(), 'Pending')");
            $stmt->execute([':ext' => $external_id, ':int' => $internal_id, ':eq' => $equipment_id, ':dmc' => $dmc, ':line' => $line]);
            $newId = $pdo->lastInsertId();
            $success = "Job #{$newId} created successfully!"; $newJobId = $newId;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$externals  = $pdo->query("SELECT external_id, external_name FROM External_Users ORDER BY external_name")->fetchAll();
$internals  = $pdo->query("SELECT user_id, name FROM Internal_Users ORDER BY name")->fetchAll();
$equipments = $pdo->query("SELECT equipment_id, equipment_name FROM Equipments ORDER BY equipment_name")->fetchAll();
?>

<div class="max-w-2xl mx-auto">
    <?php if ($success): ?>
        <div class="mb-6 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 px-5 py-4 rounded-xl text-sm anim-scale-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?= htmlspecialchars($success) ?>
            <?php if (isset($newJobId)): ?>
                <a href="print_tag.php?id=<?= $newJobId ?>" target="_blank" class="ml-auto flex-shrink-0 px-4 py-1.5 bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/30 rounded-lg text-emerald-300 text-xs font-bold transition-all">
                    ??? Print Tag
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-6 flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-300 px-5 py-4 rounded-xl text-sm anim-scale-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 md:p-8 anim-fade-up delay-1">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="anim-fade-up delay-2">
                    <label for="external_id" class="block text-sm font-medium text-slate-300 mb-2">Sender (External)</label>
                    <select id="external_id" name="external_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 appearance-none hover:border-slate-600">
                        <option value="">-- Select Sender --</option>
                        <?php foreach ($externals as $ext): ?>
                            <option value="<?= $ext['external_id'] ?>"><?= htmlspecialchars($ext['external_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="anim-fade-up delay-3">
                    <label for="internal_id" class="block text-sm font-medium text-slate-300 mb-2">Receiver (Internal)</label>
                    <select id="internal_id" name="internal_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 appearance-none hover:border-slate-600">
                        <option value="">-- Select Receiver --</option>
                        <?php foreach ($internals as $int): ?>
                            <option value="<?= $int['user_id'] ?>"><?= htmlspecialchars($int['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="anim-fade-up delay-3">
                <label for="equipment_id" class="block text-sm font-medium text-slate-300 mb-2">Equipment</label>
                <select id="equipment_id" name="equipment_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 appearance-none hover:border-slate-600">
                    <option value="">-- Select Equipment --</option>
                    <?php foreach ($equipments as $eq): ?>
                        <option value="<?= $eq['equipment_id'] ?>"><?= htmlspecialchars($eq['equipment_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="anim-fade-up delay-4">
                    <label for="dmc" class="block text-sm font-medium text-slate-300 mb-2">DMC Code</label>
                    <input type="text" id="dmc" name="dmc" class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 hover:border-slate-600" placeholder="Enter DMC code">
                </div>
                <div class="anim-fade-up delay-5">
                    <label for="line" class="block text-sm font-medium text-slate-300 mb-2">Line</label>
                    <select id="line" name="line" class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-300 appearance-none hover:border-slate-600"><option value="">-- Select Line --</option><option value="Line 1">Line 1</option><option value="Line 2">Line 2</option><option value="Line 3">Line 3</option><option value="Line 4">Line 4</option><option value="Line 5">Line 5</option><option value="Line 6">Line 6</option><option value="Line 7">Line 7</option><option value="Line 8">Line 8</option><option value="Line 9">Line 9</option><option value="Line 10">Line 10</option></select>
                </div>
            </div>
            <div class="flex items-center gap-4 pt-2 anim-fade-up delay-5">
                <button type="submit" class="btn-press px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all duration-300 transform hover:-translate-y-0.5">
                    <span class="flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Receive Job</span>
                </button>
                <a href="index.php" class="px-6 py-3 text-slate-400 hover:text-white transition-all duration-300 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require_once 'footer.php'; ?>
