<?php
session_start();

// If user is already logged in (and this isn't a fresh login POST), redirect
if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header('Location: index.php'); 
    exit; 
}

require_once 'includes/db.php';
$error = '';
$loginSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, user_name, user_password, name, role FROM Internal_Users WHERE user_name = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['user_password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['name']      = $user['name'];
            $_SESSION['role']      = $user['role'];
            $loginSuccess = true;
        } else { 
            $error = 'Invalid username or password.'; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QC Lab Tracking</title>
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
        .field-anim-1 { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.45s both; }
        .field-anim-2 { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.55s both; }
        .btn-anim { animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.65s both; }
        .links-anim { animation: fadeIn 0.5s ease 0.8s both; }
        .orb-1 { animation: orb1 8s ease-in-out infinite; }
        .orb-2 { animation: orb2 10s ease-in-out infinite; }
        .bg-shift { background-size: 300% 300%; animation: gradientShift 8s ease infinite; }
        .btn-press { transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1); }
        .btn-press:active { transform: scale(0.96); }
        .input-focus:focus { animation: inputGlow 1.5s ease infinite; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 bg-shift relative overflow-hidden">
    <div class="absolute top-[-10%] left-[-5%] w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl orb-1"></div>
    <div class="absolute bottom-[-10%] right-[-5%] w-96 h-96 bg-purple-500/20 rounded-full blur-3xl orb-2"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-500/8 rounded-full blur-3xl"></div>

    <div class="relative z-10 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <div class="logo-anim inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/30 mb-4 hover:shadow-indigo-500/50 transition-shadow duration-500" style="animation: scaleIn 0.8s cubic-bezier(0.34,1.56,0.64,1) both, float 3s ease-in-out 1s infinite;">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <h1 class="title-anim text-2xl font-bold text-white tracking-tight">QC Lab Tracking</h1>
            <p class="title-anim text-indigo-300/70 text-sm mt-1" style="animation-delay:0.3s">Quality Control Laboratory System</p>
        </div>
        <div class="glass rounded-2xl p-8 shadow-2xl card-anim">
            <?php if ($error): ?>
                <div class="mb-6 flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-300 px-4 py-3 rounded-xl text-sm" style="animation: fadeInUp 0.4s ease both;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-5">
                <div class="field-anim-1">
                    <label for="username" class="block text-sm font-medium text-indigo-200 mb-2">Username</label>
                    <input type="text" id="username" name="username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="input-focus w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-indigo-300/30 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300" placeholder="Enter your username">
                </div>
                <div class="field-anim-2">
                    <label for="password" class="block text-sm font-medium text-indigo-200 mb-2">Password</label>
                    <input type="password" id="password" name="password" required class="input-focus w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-indigo-300/30 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300" placeholder="Enter your password">
                </div>
                <div class="field-anim-2 flex justify-end">
                    <a href="forgot_password.php" class="text-xs text-indigo-400/70 hover:text-indigo-300 transition-colors">Forgot password?</a>
                </div>
                <button type="submit" class="btn-anim btn-press w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all duration-300 transform hover:-translate-y-0.5">Sign In</button>
            </form>
            <div class="links-anim mt-6 pt-5 border-t border-white/5 text-center">
                <p class="text-indigo-300/40 text-sm">Don't have an account? <a href="register.php" class="text-indigo-400 hover:text-indigo-300 transition-colors underline">Sign Up</a></p>
            </div>
        </div>
    </div>

    <!-- Typewriter Success Overlay -->
    <?php if (isset($loginSuccess) && $loginSuccess): ?>
    <div id="successOverlay" class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center transition-opacity duration-500" style="z-index: 9999;">
        <div class="logo-anim inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/30 mb-8" style="animation: scaleIn 0.8s cubic-bezier(0.34,1.56,0.64,1) both;">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="flex items-center justify-center h-16">
            <h1 id="typewriterText" class="text-3xl md:text-5xl font-bold text-white tracking-tight"></h1>
            <span class="text-3xl md:text-5xl text-purple-400" id="typewriterCursor" style="animation: blink-caret .75s step-end infinite;">|</span>
        </div>
        <p id="loadingText" class="text-indigo-400 mt-6 text-sm font-medium opacity-0 transition-opacity duration-500">Preparing your lab environment...</p>
    </div>
    
    <style>
        @keyframes blink-caret { 50% { opacity: 0; } }
    </style>

    <script>
        const overlay = document.getElementById('successOverlay');
        const textToType = 'Welcome, <?= addslashes(htmlspecialchars($_SESSION['name'] ?? 'User')) ?>';
        const typewriterElement = document.getElementById('typewriterText');
        const loadingText = document.getElementById('loadingText');
        const cursor = document.getElementById('typewriterCursor');
        let index = 0;
        
        function typeWriter() {
            if (index < textToType.length) {
                typewriterElement.innerHTML += textToType.charAt(index);
                index++;
                setTimeout(typeWriter, 50 + Math.random() * 50); // random typing speed
            } else {
                cursor.style.display = 'none'; // hide cursor when done
                loadingText.style.opacity = '1';
                
                // Keep the success screen visible for 1.5s, then redirect
                setTimeout(() => {
                    overlay.style.opacity = '0';
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 500);
                }, 1500);
            }
        }
        
        // Wait briefly for CSS paint before starting to type
        setTimeout(typeWriter, 500); 
    </script>
    <?php endif; ?>

</body>
</html>
