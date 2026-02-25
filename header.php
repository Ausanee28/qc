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
    <style>
        body { font-family: 'Outfit', sans-serif; overflow-x: hidden; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        /*  Gradient Text  */
        .text-gradient { background: linear-gradient(135deg, #818cf8, #c084fc, #f472b6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .text-gradient-emerald { background: linear-gradient(135deg, #34d399, #2dd4bf); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .text-gradient-amber { background: linear-gradient(135deg, #fbbf24, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .text-gradient-red { background: linear-gradient(135deg, #f87171, #ef4444); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .text-gradient-blue { background: linear-gradient(135deg, #60a5fa, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        /*  Keyframes  */
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
        @keyframes borderGlow { 0%, 100% { border-color: rgba(99,102,241,0.15); } 50% { border-color: rgba(99,102,241,0.4); } }
        @keyframes rippleAnim { to { transform: scale(4); opacity: 0; } }

        /*  No page-load entrance animations  */
        .anim-fade-up, .anim-fade-in, .anim-slide-left, .anim-slide-right, .anim-scale-in, .anim-count, .table-row-anim, .page-content { animation: none; }
        .anim-float { animation: float 3s ease-in-out infinite; }
        .delay-1, .delay-2, .delay-3, .delay-4, .delay-5, .delay-6 { animation-delay: 0s; }

        /*  3D Tilt Card with Mouse Border Glow  */
        .tilt-card {
            position: relative;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            transform-style: preserve-3d; perspective: 800px;
            will-change: transform;
            overflow: hidden;
        }
        
        
        
        
        .tilt-card:hover {
            box-shadow: 0 20px 60px -15px rgba(99,102,241,0.12);
        }
        

        /*  Sidebar  */
        .sidebar-link { transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1); position: relative; overflow: hidden; }
        .sidebar-link::before {
            content: ''; position: absolute; left: 0; top: 0; width: 3px; height: 100%;
            background: linear-gradient(180deg, #6366f1, #a855f7); transform: scaleY(0);
            transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1); border-radius: 0 4px 4px 0;
        }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(99,102,241,0.12); color: #a5b4fc; }
        .sidebar-link.active::before, .sidebar-link:hover::before { transform: scaleY(1); }
        .sidebar-link.active { animation: borderGlow 2s ease-in-out infinite; }

        /*  Hover Effects  */
        .hover-lift { transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s ease; }
        .hover-lift:hover { transform: translateY(-4px); box-shadow: 0 12px 40px -8px rgba(0,0,0,0.4); }
        .hover-glow:hover { box-shadow: 0 0 20px rgba(99,102,241,0.15); }
        .magnetic { transition: transform 0.2s cubic-bezier(0.22, 1, 0.36, 1); }
        .metric-value { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .metric-value:hover { transform: scale(1.08); }
        .btn-press { transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1); }
        .btn-press:active { transform: scale(0.96); }

        /*  Ripple on Click  */
        .ripple-container { position: relative; overflow: hidden; }
        .ripple-container .ripple {
            position: absolute; border-radius: 50%; background: rgba(99,102,241,0.2);
            transform: scale(0); animation: rippleAnim 0.6s linear; pointer-events: none;
        }

        /*  Progress bar  */
        .progress-bar { transition: width 1.5s cubic-bezier(0.22, 1, 0.36, 1); }

        /*  Scrollbar  */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.3); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(99,102,241,0.5); }

        /*  Table row glow  */
        tbody tr { transition: all 0.2s ease; }
        tbody tr:hover { background: rgba(99,102,241,0.04) !important; }
        tbody tr:hover td:first-child { color: #a5b4fc !important; }

        /*  Particles  */
        .particle {
            position: fixed; pointer-events: none; border-radius: 50%;
            background: rgba(99,102,241,0.08); z-index: -1;
        }

        /* ===== Light Mode ===== */
        body.light-mode { background: #f1f5f9 !important; color: #1e293b !important; }
        body.light-mode .bg-slate-950 { background: #f1f5f9 !important; }
        body.light-mode .bg-slate-950\/80 { background: rgba(241,245,249,0.9) !important; }
        body.light-mode .bg-slate-900\/80 { background: rgba(255,255,255,0.95) !important; }
        body.light-mode .bg-slate-900\/60 { background: rgba(255,255,255,0.8) !important; }
        body.light-mode .bg-slate-800\/60 { background: rgba(226,232,240,0.6) !important; }
        body.light-mode .bg-slate-800\/30 { background: rgba(226,232,240,0.3) !important; }
        body.light-mode .border-slate-800 { border-color: #e2e8f0 !important; }
        body.light-mode .border-slate-700 { border-color: #cbd5e1 !important; }
        body.light-mode .text-slate-200 { color: #334155 !important; }
        body.light-mode .text-slate-300 { color: #475569 !important; }
        body.light-mode .text-slate-400 { color: #64748b !important; }
        body.light-mode .text-slate-500 { color: #64748b !important; }
        body.light-mode .text-white { color: #0f172a !important; }
        body.light-mode .text-gradient { background: linear-gradient(135deg, #6366f1, #a855f7, #ec4899); -webkit-background-clip: text; }
        body.light-mode .sidebar-link:hover, body.light-mode .sidebar-link.active { background: rgba(99,102,241,0.08) !important; color: #6366f1 !important; }
        body.light-mode tbody tr:hover { background: rgba(99,102,241,0.06) !important; }
        body.light-mode .particle { background: rgba(99,102,241,0.04) !important; }
        body.light-mode ::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.2); }
        body.light-mode .divide-slate-800\/60 > :not(:last-child) { border-color: #e2e8f0 !important; }

        /* Light Mode — Forms, Inputs, Select, Tables */
        body.light-mode select,
        body.light-mode input[type="text"],
        body.light-mode input[type="password"],
        body.light-mode input[type="date"],
        body.light-mode input[type="number"],
        body.light-mode input[type="datetime-local"],
        body.light-mode input[type="time"],
        body.light-mode input[type="email"],
        body.light-mode textarea {
            background: #ffffff !important;
            color: #1e293b !important;
            border-color: #cbd5e1 !important;
        }
        body.light-mode select option { background: #ffffff; color: #1e293b; }
        body.light-mode input::placeholder { color: #94a3b8 !important; }
        body.light-mode input[type="date"] { color-scheme: light !important; }
        body.light-mode input[type="datetime-local"] { color-scheme: light !important; }
        body.light-mode input, body.light-mode select, body.light-mode textarea { background-color: #ffffff !important; color: #1e293b !important; border-color: #cbd5e1 !important; }
        body.light-mode label { color: #334155 !important; }
        body.light-mode .text-red-400, body.light-mode .text-red-500 { color: #ef4444 !important; }
        body.light-mode .bg-gradient-to-br { filter: none; }
        body.light-mode .bg-slate-800 { background: #e2e8f0 !important; }
        body.light-mode .bg-slate-900\/40 { background: rgba(241,245,249,0.6) !important; }
        body.light-mode .bg-indigo-600, body.light-mode .bg-gradient-to-r { filter: brightness(1.05); }
        body.light-mode table thead tr { background: #f1f5f9 !important; }
        body.light-mode table thead th { color: #475569 !important; }
        body.light-mode table tbody td { color: #334155 !important; }
        body.light-mode .rounded-2xl { border-color: #e2e8f0 !important; }
        body.light-mode .font-mono { color: #475569 !important; }
        body.light-mode .bg-emerald-500\/10 { background: rgba(52,211,153,0.1) !important; }
        body.light-mode .bg-red-500\/10 { background: rgba(248,113,113,0.1) !important; }
        body.light-mode .bg-amber-500\/10 { background: rgba(251,191,36,0.1) !important; }
        body.light-mode .bg-indigo-500\/10 { background: rgba(99,102,241,0.1) !important; }
        body.light-mode .bg-purple-500\/10 { background: rgba(168,85,247,0.1) !important; }
        body.light-mode .shadow-lg { box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important; }
        body.light-mode .tilt-card:hover { box-shadow: 0 12px 40px -10px rgba(99,102,241,0.15) !important; }

        /* Theme toggle button */
        .theme-toggle { cursor: pointer; transition: all 0.3s ease; }
        .theme-toggle:hover { transform: scale(1.1) rotate(15deg); }
    </style>
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
    <main class="flex-1 ml-64">
        <header class="sticky top-0 z-20 bg-slate-950/80 backdrop-blur-lg border-b border-slate-800 px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-gradient"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
                    <p class="text-sm text-slate-500 mt-0.5 font-medium"><?= htmlspecialchars($pageSubtitle ?? '') ?></p>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle -->
                    <button id="themeToggle" class="theme-toggle p-2 rounded-xl bg-slate-800/60 border border-slate-700 hover:border-slate-600 transition-all duration-300" title="Toggle Light/Dark Mode">
                        <svg id="themeIconDark" class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg id="themeIconLight" class="w-5 h-5 text-indigo-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    <div class="relative" id="dmcSearchWrap">
                        <input type="text" id="dmcSearch" placeholder="Search DMC..." class="w-56 pl-9 pr-4 py-2 bg-slate-800/60 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 hover:border-slate-600">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <div id="dmcSpinner" class="absolute right-3 top-1/2 -translate-y-1/2 hidden">
                            <svg class="w-4 h-4 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </div>
                    </div>
                    <div class="text-sm text-slate-500 font-medium whitespace-nowrap"><?= date('l, d M Y') ?></div>
                </div>
            </div>
        </header>
        <div class="p-8 page-content">

    <script>
    //  Mouse-follow Border Glow on Cards 
    document.addEventListener('mousemove', e => {
        document.querySelectorAll('.tilt-card').forEach(card => {
            const r = card.getBoundingClientRect();
            const x = e.clientX - r.left;
            const y = e.clientY - r.top;
            card.style.setProperty('--mouse-x', x + 'px');
            card.style.setProperty('--mouse-y', y + 'px');
            // 3D tilt
            const inRange = e.clientX >= r.left - 40 && e.clientX <= r.right + 40 && e.clientY >= r.top - 40 && e.clientY <= r.bottom + 40;
            if (inRange) {
                const rx = ((e.clientX - r.left) / r.width - 0.5) * 5;
                const ry = ((e.clientY - r.top) / r.height - 0.5) * 5;
                card.style.transform = `perspective(800px) rotateY(${rx}deg) rotateX(${-ry}deg) scale(1.01)`;
            } else {
                card.style.transform = 'perspective(800px) rotateY(0) rotateX(0) scale(1)';
            }
        });
    });

    //  Magnetic Hover for Buttons 
    document.querySelectorAll('.magnetic').forEach(el => {
        el.addEventListener('mousemove', e => {
            const r = el.getBoundingClientRect();
            const x = (e.clientX - r.left - r.width / 2) * 0.3;
            const y = (e.clientY - r.top - r.height / 2) * 0.3;
            el.style.transform = `translate(${x}px, ${y}px)`;
        });
        el.addEventListener('mouseleave', () => { el.style.transform = 'translate(0, 0)'; });
    });

    //  Ripple on Click 
    document.querySelectorAll('.ripple-container').forEach(el => {
        el.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const r = this.getBoundingClientRect();
            const size = Math.max(r.width, r.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - r.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - r.top - size / 2) + 'px';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });

    //  Floating Particles 
    (function() {
        const c = document.getElementById('particles');
        for (let i = 0; i < 6; i++) {
            const p = document.createElement('div');
            p.classList.add('particle');
            const s = Math.random() * 4 + 2;
            p.style.width = p.style.height = s + 'px';
            p.style.left = Math.random() * 100 + '%';
            p.style.top = Math.random() * 100 + '%';
            p.style.opacity = Math.random() * 0.5 + 0.1;
            p.style.animation = `float ${Math.random() * 4 + 4}s ease-in-out infinite`;
            p.style.animationDelay = Math.random() * 3 + 's';
            c.appendChild(p);
        }
    })();

    //  DMC Quick Search 
    (function() {
        const input = document.getElementById('dmcSearch');
        const spinner = document.getElementById('dmcSpinner');
        if (!input) return;
        input.addEventListener('keydown', async function(e) {
            if (e.key !== 'Enter') return;
            const dmc = this.value.trim();
            if (!dmc) { Swal.fire({icon:'warning',title:'Please enter DMC',text:'Enter DMC number to search',background:'#0f172a',color:'#e2e8f0',confirmButtonColor:'#6366f1'}); return; }
            spinner.classList.remove('hidden');
            try {
                const res = await fetch('search_dmc.php?dmc=' + encodeURIComponent(dmc));
                const json = await res.json();
                spinner.classList.add('hidden');
                if (!json.success) { Swal.fire({icon:'info',title:'Not Found',text:json.message||'No DMC found',background:'#0f172a',color:'#e2e8f0',confirmButtonColor:'#6366f1'}); return; }
                let html = '<div style="max-height:400px;overflow-y:auto;"><table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">';
                html += '<thead><tr style="border-bottom:2px solid #334155;"><th style="padding:8px 10px;color:#94a3b8;">Date</th><th style="padding:8px 10px;color:#94a3b8;">Inspector</th><th style="padding:8px 10px;color:#94a3b8;">Method</th><th style="padding:8px 10px;color:#94a3b8;">Result</th></tr></thead><tbody>';
                json.data.forEach(row => {
                    const date = row.receive_date ? new Date(row.receive_date).toLocaleDateString('th-TH',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-';
                    const badge = row.judgement==='OK' ? '<span style="background:rgba(52,211,153,0.15);color:#34d399;padding:2px 10px;border-radius:20px;font-weight:600;font-size:12px;">OK</span>' : row.judgement==='NG' ? '<span style="background:rgba(248,113,113,0.15);color:#f87171;padding:2px 10px;border-radius:20px;font-weight:600;font-size:12px;">NG</span>' : '<span style="color:#94a3b8;">Pending</span>';
                    html += '<tr style="border-bottom:1px solid #1e293b;"><td style="padding:8px 10px;color:#cbd5e1;white-space:nowrap;">'+date+'</td><td style="padding:8px 10px;color:#e2e8f0;">'+(row.inspector_name||'-')+'</td><td style="padding:8px 10px;color:#94a3b8;">'+(row.method_name||'-')+'</td><td style="padding:8px 10px;">'+badge+'</td></tr>';
                });
                html += '</tbody></table></div>';
                Swal.fire({title:'<span style="color:#a5b4fc;">DMC: '+dmc+'</span>',html:html,width:640,background:'#0f172a',color:'#e2e8f0',showCloseButton:true,showConfirmButton:false,customClass:{popup:'rounded-2xl border border-slate-800'}});
            } catch(err) { spinner.classList.add('hidden'); Swal.fire({icon:'error',title:'Error',text:'Search failed',background:'#0f172a',color:'#e2e8f0',confirmButtonColor:'#6366f1'}); }
        });
    })();

    //  Light/Dark Mode Toggle 
    (function() {
        const toggle = document.getElementById('themeToggle');
        const iconDark = document.getElementById('themeIconDark');
        const iconLight = document.getElementById('themeIconLight');
        if (!toggle) return;
        
        // Load saved theme
        if (localStorage.getItem('qc-theme') === 'light') {
            document.body.classList.add('light-mode');
            iconDark.classList.add('hidden');
            iconLight.classList.remove('hidden');
        }
        
        toggle.addEventListener('click', function() {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');
            localStorage.setItem('qc-theme', isLight ? 'light' : 'dark');
            iconDark.classList.toggle('hidden', isLight);
            iconLight.classList.toggle('hidden', !isLight);
        });
    })();
    </script>

