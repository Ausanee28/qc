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
                <div class="text-sm text-slate-500 font-medium"><?= date('l, d M Y') ?></div>
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
    </script>

