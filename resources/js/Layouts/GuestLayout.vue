<template>
    <div class="theme-guest relative flex min-h-dvh w-full items-center justify-center overflow-hidden bg-[#090909] px-4 py-6 sm:px-6">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,#090909_0%,#160f0b_48%,#090909_100%)]"></div>
            <div class="absolute left-[-12%] top-[-9%] h-80 w-80 rounded-full bg-orange-400/12 blur-3xl"></div>
            <div class="absolute right-[-10%] top-[18%] h-96 w-96 rounded-full bg-orange-700/12 blur-3xl"></div>
            <div class="absolute bottom-[-18%] left-1/2 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-amber-300/5 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.08]" style="background-image: linear-gradient(rgba(251,146,60,0.16) 1px, transparent 1px), linear-gradient(90deg, rgba(251,146,60,0.16) 1px, transparent 1px); background-size: 34px 34px;"></div>
        </div>

        <div class="relative z-10 w-full max-w-xl">
            <div class="rounded-[34px] border border-orange-500/20 bg-black/45 p-2 shadow-[0_35px_80px_rgba(0,0,0,0.55)] backdrop-blur-xl sm:p-3">
                <div class="rounded-[28px] bg-[linear-gradient(180deg,rgba(20,16,13,0.98),rgba(12,10,9,0.96))] p-4 sm:p-6">
                    <slot />
                </div>
            </div>
            <div class="mt-4 text-center text-xs text-stone-300/70">
                QC Lab Management System &copy; {{ new Date().getFullYear() }}
            </div>
        </div>
    </div>
</template>
