<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
require_once 'includes/db.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name   = trim($_POST['user_name'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';
    $employee_id = trim($_POST['employee_id'] ?? '');
    $name        = trim($_POST['name'] ?? '');
    $role        = 'inspector'; // default role for new users

    // Validation
    if ($user_name === '' || $password === '' || $name === '' || $employee_id === '') {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($user_name) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare('SELECT user_id FROM Internal_Users WHERE user_name = :u LIMIT 1');
        $stmt->execute([':u' => $user_name]);
        if ($stmt->fetch()) {
            $error = 'Username already exists. Please choose another.';
        } else {
            // Check if employee_id already exists
            $stmt2 = $pdo->prepare('SELECT user_id FROM Internal_Users WHERE employee_id = :e LIMIT 1');
            $stmt2->execute([':e' => $employee_id]);
            if ($stmt2->fetch()) {
                $error = 'Employee ID already registered.';
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO Internal_Users (user_name, user_password, employee_id, name, role) VALUES (:u, :p, :e, :n, :r)');
                    $stmt->execute([':u' => $user_name, ':p' => $hash, ':e' => $employee_id, ':n' => $name, ':r' => $role]);
                    $success = 'Account created successfully! You can now sign in.';
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
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
    <title>Sign Up - QC Lab Tracking</title>
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
        .field-1 { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.4s both; }
        .field-2 { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.48s both; }
        .field-3 { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.56s both; }
        .field-4 { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.64s both; }
        .field-5 { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.72s both; }
        .btn-anim { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.8s both; }
        .hint-anim { animation: fadeIn 0.5s ease 0.9s both; }
        .orb-1 { animation: orb1 8s ease-in-out infinite; }
        .orb-2 { animation: orb2 10s ease-in-out infinite; }
        .bg-shift { background-size: 300% 300%; animation: gradientShift 8s ease infinite; }
        .btn-press { transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1); }
        .btn-press:active { transform: scale(0.96); }
        .input-focus:focus { animation: inputGlow 1.5s ease infinite; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 bg-shift relative overflow-hidden">
    <div class="absolute top-[-10%] left-[-5%] w-96 h-96 bg-emerald-500/15 rounded-full blur-3xl orb-1"></div>
    <div class="absolute bottom-[-10%] right-[-5%] w-96 h-96 bg-purple-500/15 rounded-full blur-3xl orb-2"></div>

    <div class="relative z-10 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <div class="logo-anim inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/30 mb-4" style="animation: scaleIn 0.8s cubic-bezier(0.34,1.56,0.64,1) both, float 3s ease-in-out 1s infinite;">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <h1 class="title-anim text-2xl font-bold text-white tracking-tight">Create Account</h1>
            <p class="title-anim text-emerald-300/70 text-sm mt-1" style="animation-delay:0.3s">Join QC Lab Tracking System</p>
        </div>
        <div class="glass rounded-2xl p-8 shadow-2xl card-anim">
            <?php if ($success): ?>
                <div class="mb-6 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 px-4 py-3 rounded-xl text-sm" style="animation: fadeInUp 0.4s ease both;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="mb-6 flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-300 px-4 py-3 rounded-xl text-sm" style="animation: fadeInUp 0.4s ease both;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="field-1">
                        <label for="name" class="block text-sm font-medium text-emerald-200 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" class="input-focus w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-emerald-300/30 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-300" placeholder="John Doe">
                    </div>
                    <div class="field-2">
                        <label for="employee_id" class="block text-sm font-medium text-emerald-200 mb-1.5">Employee ID <span class="text-red-400">*</span></label>
                        <input type="text" id="employee_id" name="employee_id" required value="<?= htmlspecialchars($_POST['employee_id'] ?? '') ?>" class="input-focus w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-emerald-300/30 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-300" placeholder="EMP004">
                    </div>
                </div>
                <div class="field-3">
                    <label for="user_name" class="block text-sm font-medium text-emerald-200 mb-1.5">Username <span class="text-red-400">*</span></label>
                    <input type="text" id="user_name" name="user_name" required value="<?= htmlspecialchars($_POST['user_name'] ?? '') ?>" class="input-focus w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-emerald-300/30 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-300" placeholder="Choose a username (min 3 chars)">
                </div>
                <div class="field-4">
                    <label for="password" class="block text-sm font-medium text-emerald-200 mb-1.5">Password <span class="text-red-400">*</span></label>
                    <input type="password" id="password" name="password" required class="input-focus w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-emerald-300/30 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-300" placeholder="Min 6 characters">
                </div>
                <div class="field-5">
                    <label for="confirm_password" class="block text-sm font-medium text-emerald-200 mb-1.5">Confirm Password <span class="text-red-400">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required class="input-focus w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-emerald-300/30 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-300" placeholder="Re-enter password">
                </div>
                <button type="submit" class="btn-anim btn-press w-full py-3 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-300 transform hover:-translate-y-0.5">Create Account</button>
            </form>
            <p class="hint-anim text-center text-emerald-300/40 text-sm mt-6">Already have an account? <a href="login.php" class="text-emerald-400 hover:text-emerald-300 transition-colors underline">Sign In</a></p>
        </div>
    </div>
</body>
</html>
