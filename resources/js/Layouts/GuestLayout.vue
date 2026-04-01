<template>
    <div class="theme-guest relative flex min-h-dvh w-full items-center justify-center overflow-hidden bg-[#090909] px-4 py-6 sm:px-6">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,#090909_0%,#160f0b_48%,#090909_100%)]"></div>
            <div class="guest-glow guest-glow--one absolute left-[-12%] top-[-9%] h-80 w-80 rounded-full bg-orange-400/12 blur-3xl"></div>
            <div class="guest-glow guest-glow--two absolute right-[-10%] top-[18%] h-96 w-96 rounded-full bg-orange-700/12 blur-3xl"></div>
            <div class="guest-glow guest-glow--three absolute bottom-[-18%] left-1/2 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-amber-300/5 blur-3xl"></div>
            <div class="guest-grid absolute inset-0 opacity-[0.08]" style="background-image: linear-gradient(rgba(251,146,60,0.16) 1px, transparent 1px), linear-gradient(90deg, rgba(251,146,60,0.16) 1px, transparent 1px); background-size: 34px 34px;"></div>
        </div>

        <div class="guest-shell relative z-10 w-full max-w-xl">
            <div class="guest-frame rounded-[34px] border border-orange-500/20 bg-black/45 p-2 shadow-[0_35px_80px_rgba(0,0,0,0.55)] backdrop-blur-xl sm:p-3">
                <div class="guest-panel rounded-[28px] bg-[linear-gradient(180deg,rgba(20,16,13,0.98),rgba(12,10,9,0.96))] p-4 sm:p-6">
                    <slot />
                </div>
            </div>
            <div class="guest-footer mt-4 text-center text-xs text-stone-300/70">
                QC Lab Management System &copy; {{ new Date().getFullYear() }}
            </div>
        </div>
    </div>
</template>

<style scoped>
.guest-shell {
    animation: guest-rise 720ms cubic-bezier(0.2, 0.7, 0.2, 1) both;
}

:global(html[data-theme='light']) .theme-guest {
    background:
        radial-gradient(circle at top left, rgba(251, 146, 60, 0.08), transparent 18%),
        radial-gradient(circle at 82% 12%, rgba(120, 53, 15, 0.05), transparent 16%),
        linear-gradient(180deg, #fcfcfa 0%, #f6f5f1 52%, #f1efea 100%) !important;
}

:global(html[data-theme='light']) .theme-guest .guest-glow--one {
    background: rgba(251, 146, 60, 0.12) !important;
}

:global(html[data-theme='light']) .theme-guest .guest-glow--two {
    background: rgba(194, 65, 12, 0.08) !important;
}

:global(html[data-theme='light']) .theme-guest .guest-glow--three {
    background: rgba(217, 119, 6, 0.05) !important;
}

:global(html[data-theme='light']) .theme-guest .guest-grid {
    opacity: 0.05 !important;
    background-image:
        linear-gradient(rgba(68, 64, 60, 0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(68, 64, 60, 0.08) 1px, transparent 1px) !important;
}

.guest-frame {
    position: relative;
    overflow: hidden;
}

.guest-frame::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(115deg, rgba(255, 255, 255, 0.06), transparent 24%, transparent 76%, rgba(251, 146, 60, 0.08));
    opacity: 0.7;
    pointer-events: none;
}

.guest-panel {
    position: relative;
    overflow: hidden;
}

.guest-panel::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(circle at top right, rgba(251, 146, 60, 0.08), transparent 34%);
}

.guest-footer {
    animation: guest-fade 920ms ease both;
    animation-delay: 180ms;
}

.guest-glow {
    animation: guest-float 14s ease-in-out infinite;
}

.guest-glow--two {
    animation-delay: -5s;
}

.guest-glow--three {
    animation-delay: -9s;
}

.guest-grid {
    animation: guest-grid-drift 18s linear infinite;
}

@keyframes guest-rise {
    from { opacity: 0; transform: translateY(18px) scale(0.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes guest-fade {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes guest-float {
    0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
    50% { transform: translate3d(0, 16px, 0) scale(1.04); }
}

@keyframes guest-grid-drift {
    from { transform: translate3d(0, 0, 0); }
    to { transform: translate3d(12px, 12px, 0); }
}

@media (prefers-reduced-motion: reduce) {
    .guest-shell,
    .guest-footer,
    .guest-glow,
    .guest-grid {
        animation: none !important;
    }
}
</style>
