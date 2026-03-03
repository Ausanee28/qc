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
    <div class="min-h-screen bg-[#f8f9fb]">
        <!-- Sidebar Desktop -->
        <aside class="fixed inset-y-0 left-0 z-50 w-[220px] bg-white border-r border-gray-200/80 hidden lg:flex lg:flex-col">
            <!-- Logo -->
            <div class="flex items-center gap-2.5 px-5 h-[60px] border-b border-gray-100">
                <div class="w-8 h-8 rounded-lg bg-gray-900 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <div class="leading-tight">
                    <h1 class="text-gray-900 font-bold text-[13px]">QC Lab</h1>
                    <p class="text-gray-400 text-[10px]">Quality Control</p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <p class="px-3 mb-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Menu</p>
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        route().current(item.route)
                            ? 'bg-gray-900 text-white shadow-sm'
                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900',
                        'flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-150'
                    ]"
                >
                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <!-- Sidebar footer -->
            <div class="px-4 py-3 border-t border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center text-white font-bold text-[11px]">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[12px] font-semibold text-gray-900 truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-[10px] text-gray-400">{{ $page.props.auth.user.role || 'User' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="lg:pl-[220px]">
            <!-- Top Bar -->
            <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-gray-200/60">
                <div class="flex items-center justify-between px-6 h-[60px]">
                    <!-- Mobile menu button -->
                    <button @click="showMobileMenu = !showMobileMenu" class="lg:hidden text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="flex-1 lg:flex-none">
                        <h2 class="text-gray-900 font-semibold text-[15px]" v-if="$slots.title"><slot name="title" /></h2>
                    </div>

                    <div class="flex items-center gap-3">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 transition-colors px-2 py-1 rounded-lg hover:bg-gray-50">
                                    <span class="hidden sm:block text-[13px] font-medium">{{ $page.props.auth.user.name }}</span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
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
            <div v-if="showMobileMenu" class="lg:hidden fixed inset-0 z-50 bg-black/20 backdrop-blur-sm" @click="showMobileMenu = false">
                <div class="w-[220px] h-full bg-white border-r border-gray-200 p-3 space-y-0.5 shadow-xl" @click.stop>
                    <div class="flex items-center gap-2.5 px-3 py-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-gray-900 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <span class="text-gray-900 font-bold text-sm">QC Lab</span>
                    </div>
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        @click="showMobileMenu = false"
                        :class="[
                            route().current(item.route) ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900',
                            'flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-all'
                        ]"
                    >
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
