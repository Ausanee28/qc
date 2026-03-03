<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex">
        <!-- Left Panel — Branding -->
        <div class="hidden lg:flex lg:w-[45%] bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
            <!-- Subtle grid pattern -->
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            <!-- Glow effects -->
            <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-1/4 right-1/4 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex flex-col justify-between p-12 w-full">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm border border-white/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <span class="text-white/90 font-semibold text-sm">QC Lab Tracking</span>
                </div>

                <!-- Center content -->
                <div>
                    <h1 class="text-4xl font-bold text-white leading-tight mb-4">Quality Control<br />Management System</h1>
                    <p class="text-slate-400 text-base leading-relaxed max-w-sm">Track inspections, manage equipment testing, and generate quality certificates — all in one place.</p>
                    <div class="flex items-center gap-6 mt-8">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white">7</p>
                            <p class="text-xs text-slate-500 mt-0.5">Modules</p>
                        </div>
                        <div class="w-px h-8 bg-slate-700"></div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white">24/7</p>
                            <p class="text-xs text-slate-500 mt-0.5">Access</p>
                        </div>
                        <div class="w-px h-8 bg-slate-700"></div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white">PDF</p>
                            <p class="text-xs text-slate-500 mt-0.5">Reports</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <p class="text-xs text-slate-600">&copy; {{ new Date().getFullYear() }} QC Lab — Quality Control System</p>
            </div>
        </div>

        <!-- Right Panel — Form -->
        <div class="flex-1 flex items-center justify-center bg-white px-6">
            <div class="w-full max-w-sm">
                <!-- Mobile logo -->
                <div class="lg:hidden flex items-center gap-3 mb-10">
                    <div class="w-9 h-9 rounded-lg bg-slate-900 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <span class="text-gray-900 font-semibold">QC Lab Tracking</span>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
