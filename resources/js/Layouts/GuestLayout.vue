<template>
    <div class="relative flex min-h-dvh w-full items-center justify-center overflow-hidden bg-zinc-950 px-4 py-6 sm:px-6">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,#09090b_0%,#18181b_48%,#09090b_100%)]"></div>
            <div class="absolute left-[-12%] top-[-9%] h-80 w-80 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute right-[-10%] top-[18%] h-96 w-96 rounded-full bg-zinc-300/10 blur-3xl"></div>
            <div class="absolute bottom-[-18%] left-1/2 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.10]" style="background-image: linear-gradient(rgba(255,255,255,0.12) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.12) 1px, transparent 1px); background-size: 34px 34px;"></div>
        </div>

        <div class="relative z-10 w-full max-w-xl">
            <div class="rounded-[34px] border border-white/20 bg-white/85 p-2 shadow-[0_35px_80px_rgba(0,0,0,0.55)] backdrop-blur-xl sm:p-3">
                <div class="rounded-[28px] bg-[linear-gradient(180deg,rgba(255,255,255,0.96),rgba(244,244,245,0.96))] p-4 sm:p-6">
                    <slot />
                </div>
            </div>
            <div class="mt-4 text-center text-xs text-zinc-200/80">
                QC Lab Management System &copy; {{ new Date().getFullYear() }}
            </div>
        </div>
    </div>
</template>
