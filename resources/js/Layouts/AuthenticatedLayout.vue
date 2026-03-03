<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const showMobileMenu = ref(false);

const navItems = [
    { name: 'Dashboard', route: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1' },
    { name: 'Receive Job', route: 'receive-job.create', icon: 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z' },
    { name: 'Execute Test', route: 'execute-test.create', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
    { name: 'Report', route: 'report.index', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'Certificates', route: 'certificates.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'Performance', route: 'performance.index', icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' },
];
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
        <!-- Sidebar Desktop -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900/80 backdrop-blur-xl border-r border-slate-800 hidden lg:block">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-800">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-white font-bold text-sm">QC Lab Tracking</h1>
                    <p class="text-slate-500 text-xs">Quality Control System</p>
                </div>
            </div>

            <nav class="px-3 py-4 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        route().current(item.route)
                            ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20'
                            : 'text-slate-400 border-transparent hover:bg-slate-800/50 hover:text-white',
                        'flex items-center gap-3 px-4 py-2.5 rounded-xl border text-sm font-medium transition-all duration-200'
                    ]"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="lg:pl-64">
            <!-- Top Bar -->
            <header class="sticky top-0 z-40 bg-slate-900/60 backdrop-blur-xl border-b border-slate-800">
                <div class="flex items-center justify-between px-6 py-3">
                    <!-- Mobile menu button -->
                    <button @click="showMobileMenu = !showMobileMenu" class="lg:hidden text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="flex-1 lg:flex-none">
                        <h2 class="text-white font-bold text-lg" v-if="$slots.title"><slot name="title" /></h2>
                    </div>

                    <div class="flex items-center gap-4">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center gap-2 text-sm text-slate-300 hover:text-white transition">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs">
                                        {{ $page.props.auth.user.name.charAt(0) }}
                                    </div>
                                    <span class="hidden sm:block">{{ $page.props.auth.user.name }}</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Mobile Menu -->
            <div v-if="showMobileMenu" class="lg:hidden fixed inset-0 z-50 bg-slate-950/80" @click="showMobileMenu = false">
                <div class="w-64 h-full bg-slate-900 border-r border-slate-800 p-4 space-y-1" @click.stop>
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        @click="showMobileMenu = false"
                        :class="[
                            route().current(item.route) ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white',
                            'flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all'
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon" />
                        </svg>
                        {{ item.name }}
                    </Link>
                </div>
            </div>

            <!-- Page Content -->
            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
