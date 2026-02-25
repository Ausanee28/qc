<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
require_once 'includes/db.php';

$success = '';
$error   = '';
$step    = 'verify'; // verify -> reset

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';

    if ($action === 'verify') {
        $user_name   = trim($_POST['user_name'] ?? '');
        $employee_id = trim($_POST['employee_id'] ?? '');

        if ($user_name === '' || $employee_id === '') {
            $error = 'Please enter both username and employee ID.';
        } else {
            $stmt = $pdo->prepare('SELECT user_id, name FROM Internal_Users WHERE user_name = :u AND employee_id = :e LIMIT 1');
            $stmt->execute([':u' => $user_name, ':e' => $employee_id]);
            $user = $stmt->fetch();
            if ($user) {
                $step = 'reset';
                $_SESSION['reset_user_id']   = $user['user_id'];
                $_SESSION['reset_user_name'] = $user_name;
                $_SESSION['reset_name']      = $user['name'];
            } else {
                $error = 'No account found with that username and employee ID combination.';
            }
        }
    } elseif ($action === 'reset') {
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $reset_user_id    = $_SESSION['reset_user_id'] ?? 0;

        if ($reset_user_id <= 0) {
            $error = 'Session expired. Please start over.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters.';
            $step = 'reset';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
            $step = 'reset';
        } else {
            try {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE Internal_Users SET user_password = :p WHERE user_id = :id');
                $stmt->execute([':p' => $hash, ':id' => $reset_user_id]);
                $success = 'Password reset successfully! You can now sign in with your new password.';
                unset($_SESSION['reset_user_id'], $_SESSION['reset_user_name'], $_SESSION['reset_name']);
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
                $step = 'reset';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - QC Lab Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255,255,255,0.06); backdrop-filter: blur(24px); border: 1px solid rgba(255,255,255,0.08); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.85); } to { opacity: 1; transform: scale(1); } }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        @keyframes orb1 { 0%,100% { transform: translate(0,0) scale(1); } 33% { transform: translate(40px,-30px) scale(1.1); } 66% { transform: translate(-20px,20px) scale(0.95); } }
        @keyframes orb2 { 0%,100% { transform: translate(0,0) scale(1); } 33% { transform: translate(-30px,40px) scale(0.9); } 66% { transform: translate(30px,-20px) scale(1.1); } }
        @keyframes inputGlow { 0%,100% { box-shadow: 0 0 0 0 rgba(99,102,241,0); } 50% { box-shadow: 0 0 0 4px rgba(99,102,241,0.1); } }
        .logo-anim { animation: scaleIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        .title-anim { animation: fadeInUp 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.2s both; }
        .card-anim { animation: fadeInUp 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.3s both; }
        .field-1 { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.45s both; }
        .field-2 { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.55s both; }
        .btn-anim { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.65s both; }
        .hint-anim { animation: fadeIn 0.5s ease 0.8s both; }
        .orb-1 { animation: orb1 8s ease-in-out infinite; }
        .orb-2 { animation: orb2 10s ease-in-out infinite; }
        .bg-shift { background-size: 300% 300%; animation: gradientShift 8s ease infinite; }
        .btn-press { transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1); }
        .btn-press:active { transform: scale(0.96); }
        .input-focus:focus { animation: inputGlow 1.5s ease infinite; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 bg-shift relative overflow-hidden">
    <div class="absolute top-[-10%] left-[-5%] w-96 h-96 bg-amber-500/15 rounded-full blur-3xl orb-1"></div>
    <div class="absolute bottom-[-10%] right-[-5%] w-96 h-96 bg-orange-500/15 rounded-full blur-3xl orb-2"></div>

    <div class="relative z-10 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <div class="logo-anim inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-lg shadow-amber-500/30 mb-4" style="animation: scaleIn 0.8s cubic-bezier(0.34,1.56,0.64,1) both, float 3s ease-in-out 1s infinite;">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </div>
            <h1 class="title-anim text-2xl font-bold text-white tracking-tight">Reset Password</h1>
            <p class="title-anim text-amber-300/70 text-sm mt-1" style="animation-delay:0.3s">
                <?= $step === 'reset' ? 'Set your new password' : 'Verify your identity first' ?>
            </p>
        </div>
        <div class="glass rounded-2xl p-8 shadow-2xl card-anim">
            <?php if ($success): ?>
                <div class="mb-6 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 px-4 py-3 rounded-xl text-sm" style="animation: fadeInUp 0.4s ease both;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= htmlspecialchars($success) ?>
                </div>
                <div class="text-center">
                    <a href="login.php" class="btn-press inline-block py-3 px-8 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold rounded-xl shadow-lg transition-all duration-300 transform hover:-translate-y-0.5">Go to Sign In</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="mb-6 flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-300 px-4 py-3 rounded-xl text-sm" style="animation: fadeInUp 0.4s ease both;">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($step === 'verify'): ?>
                    <!-- Step 1: Verify identity -->
                    <div class="flex items-center gap-3 mb-6 p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-sm text-amber-300/80">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Enter your username and employee ID to verify your identity.
                    </div>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="verify">
                        <div class="field-1">
                            <label for="user_name" class="block text-sm font-medium text-amber-200 mb-2">Username</label>
                            <input type="text" id="user_name" name="user_name" required autofocus value="<?= htmlspecialchars($_POST['user_name'] ?? '') ?>" class="input-focus w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-amber-300/30 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-300" placeholder="Enter your username">
                        </div>
                        <div class="field-2">
                            <label for="employee_id" class="block text-sm font-medium text-amber-200 mb-2">Employee ID</label>
                            <input type="text" id="employee_id" name="employee_id" required value="<?= htmlspecialchars($_POST['employee_id'] ?? '') ?>" class="input-focus w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-amber-300/30 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-300" placeholder="e.g. EMP001">
                        </div>
                        <button type="submit" class="btn-anim btn-press w-full py-3 px-4 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-semibold rounded-xl shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 transition-all duration-300 transform hover:-translate-y-0.5">Verify Identity</button>
                    </form>
                <?php else: ?>
                    <!-- Step 2: Set new password -->
                    <div class="flex items-center gap-3 mb-6 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-sm text-emerald-300/80">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Verified as <strong class="text-emerald-300"><?= htmlspecialchars($_SESSION['reset_name'] ?? '') ?></strong>. Set your new password below.
                    </div>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="reset">
                        <div class="field-1">
                            <label for="new_password" class="block text-sm font-medium text-amber-200 mb-2">New Password</label>
                            <input type="password" id="new_password" name="new_password" required class="input-focus w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-amber-300/30 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-300" placeholder="Min 6 characters">
                        </div>
                        <div class="field-2">
                            <label for="confirm_password" class="block text-sm font-medium text-amber-200 mb-2">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required class="input-focus w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-amber-300/30 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-300" placeholder="Re-enter new password">
                        </div>
                        <button type="submit" class="btn-anim btn-press w-full py-3 px-4 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-semibold rounded-xl shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 transition-all duration-300 transform hover:-translate-y-0.5">Reset Password</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <p class="hint-anim text-center text-amber-300/40 text-sm mt-6">Remember your password? <a href="login.php" class="text-amber-400 hover:text-amber-300 transition-colors underline">Sign In</a></p>
        </div>
    </div>
</body>
</html>
