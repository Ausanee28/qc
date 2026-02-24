<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'QC Lab Tracking') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        /*  Gradient Text  */
        .text-gradient {
            background: linear-gradient(135deg, #818cf8, #c084fc, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .text-gradient-emerald {
            background: linear-gradient(135deg, #34d399, #2dd4bf);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .text-gradient-amber {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .text-gradient-red {
            background: linear-gradient(135deg, #f87171, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .text-gradient-blue {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /*  Animation Keyframes  */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideInLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        @keyframes pulse-soft { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
        @keyframes countUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

        .anim-fade-up     { animation: none; }
        .anim-fade-in     { animation: none; }
        .anim-slide-left  { animation: none; }
        .anim-slide-right { animation: none; }
        .anim-scale-in    { animation: none; }
        .anim-float       { animation: float 3s ease-in-out infinite; }
        .anim-count       { animation: none; }

        .delay-1 { animation-delay: 0s; } .delay-2 { animation-delay: 0s; } .delay-3 { animation-delay: 0s; }
        .delay-4 { animation-delay: 0s; } .delay-5 { animation-delay: 0s; } .delay-6 { animation-delay: 0s; }

        /*  Hover Effects  */
        .hover-lift { transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s ease; }
        .hover-lift:hover { transform: translateY(-4px); box-shadow: 0 12px 40px -8px rgba(0,0,0,0.4); }
        .hover-glow:hover { box-shadow: 0 0 20px rgba(99, 102, 241, 0.15); }

        /*  Sidebar  */
        .sidebar-link { transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1); position: relative; overflow: hidden; }
        .sidebar-link::before { content: ''; position: absolute; left: 0; top: 0; width: 3px; height: 100%; background: linear-gradient(180deg, #6366f1, #a855f7); transform: scaleY(0); transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1); border-radius: 0 4px 4px 0; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(99,102,241,0.12); color: #a5b4fc; }
        .sidebar-link.active::before, .sidebar-link:hover::before { transform: scaleY(1); }

        .table-row-anim { animation: none; }
        .page-content { animation: none; }
        .metric-value { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .metric-value:hover { transform: scale(1.08); }
        .btn-press { transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1); }
        .btn-press:active { transform: scale(0.96); }

        /*  Card gradient borders  */
        .card-glow-indigo { border-image: linear-gradient(135deg, rgba(99,102,241,0.3), transparent) 1; }
        .card-glow-emerald { border-image: linear-gradient(135deg, rgba(52,211,153,0.3), transparent) 1; }

        /*  Progress bar  */
        .progress-bar { transition: width 1.5s cubic-bezier(0.22, 1, 0.36, 1); }

        /*  Scrollbar  */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.3); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(99,102,241,0.5); }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen flex">
    <aside class="w-64 bg-slate-900/80 border-r border-slate-800 flex flex-col fixed inset-y-0 left-0 z-30 anim-slide-left">
        <div class="px-6 py-5 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 anim-float">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-gradient leading-tight">QC Lab Tracking</h1>
                    <p class="text-xs text-slate-500 font-medium">Quality Control</p>
                </div>
            </div>
        </div>
        <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
            <a href="index.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
            </a>
            <a href="receive_job.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'receive_job.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Receive Job
            </a>
            <a href="execute_test.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'execute_test.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Execute Test
            </a>
            <a href="report.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'report.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Report
            </a>
        </nav>
        <div class="px-4 py-4 border-t border-slate-800 anim-fade-in delay-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-emerald-500/20">
                    <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></p>
                    <p class="text-xs text-slate-500 truncate font-medium"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></p>
                </div>
                <a href="logout.php" class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-red-400 transition-all duration-300" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </aside>
    <main class="flex-1 ml-64">
        <header class="sticky top-0 z-20 bg-slate-950/80 backdrop-blur-lg border-b border-slate-800 px-8 py-4 anim-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-gradient"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
                    <p class="text-sm text-slate-500 mt-0.5 font-medium"><?= htmlspecialchars($pageSubtitle ?? '') ?></p>
                </div>
                <div class="text-sm text-slate-500 font-medium"><?= date('l, d M Y') ?></div>
            </div>
        </header>
        <div class="p-8 page-content">

