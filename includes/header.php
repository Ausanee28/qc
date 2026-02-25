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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen flex">
    <div id="particles"></div>

    <aside class="w-64 bg-slate-900/80 border-r border-slate-800 flex flex-col fixed inset-y-0 left-0 z-30">
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
            <a href="index.php" class="sidebar-link ripple-container flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
            </a>
            <a href="receive_job.php" class="sidebar-link ripple-container flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'receive_job.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Receive Job
            </a>
            <a href="execute_test.php" class="sidebar-link ripple-container flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'execute_test.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Execute Test
            </a>
            <a href="certificates.php" class="sidebar-link ripple-container flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'certificates.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Certificates
            </a>
            <a href="report.php" class="sidebar-link ripple-container flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'report.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Report
            </a>
            <a href="performance.php" class="sidebar-link ripple-container flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 font-medium <?= $currentPage === 'performance.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Performance
            </a>
        </nav>
        <div class="px-4 py-4 border-t border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-emerald-500/20">
                    <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></p>
                    <p class="text-xs text-slate-500 truncate font-medium"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></p>
                </div>
                <a href="logout.php" class="magnetic p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-red-400 transition-all duration-300" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </aside>
    <main class="flex-1 ml-64 max-w-[calc(100vw-16rem)]">
        <header class="sticky top-0 z-20 bg-slate-950/80 backdrop-blur-lg border-b border-slate-800 px-6 py-4">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl font-extrabold text-gradient truncate"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
                    <p class="text-sm text-slate-500 mt-0.5 font-medium truncate"><?= htmlspecialchars($pageSubtitle ?? '') ?></p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="text-xs text-slate-500 font-medium"><?= date('d M Y') ?></span>
                    <button id="themeToggle" class="theme-toggle p-2 rounded-xl bg-slate-800/60 border border-slate-700 hover:border-slate-600 transition-all duration-300" title="Toggle Light/Dark Mode">
                        <svg id="themeIconDark" class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg id="themeIconLight" class="w-5 h-5 text-indigo-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    <div class="relative" id="dmcSearchWrap">
                        <input type="text" id="dmcSearch" placeholder="DMC..." class="w-24 pl-7 pr-2 py-2 bg-slate-800/60 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300">
                        <svg class="absolute left-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <div id="dmcSpinner" class="absolute right-2 top-1/2 -translate-y-1/2 hidden">
                            <svg class="w-3 h-3 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <div class="p-8 page-content">

    <script src="assets/js/app.js"></script>
