<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const showMobileMenu = ref(false);

const globalSearch = ref('');
const handleGlobalSearch = () => {
    if (globalSearch.value.trim()) {
        router.get(route('report.index'), { dmc: globalSearch.value.trim() });
        globalSearch.value = ''; // clear after search
    }
};

const groupedNav = [
    {
        label: 'Analytics',
        items: [
            { name: 'Dashboard', route: 'dashboard', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' }
        ]
    },
    {
        label: 'Workflow',
        items: [
            { name: 'Receive Job', route: 'receive-job.create', icon: 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z' },
            { name: 'Execute Test', route: 'execute-test.create', icon: 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' }
        ]
    },
    {
        label: 'Documents',
        items: [
            { name: 'Certificates', route: 'certificates.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            { name: 'Report', route: 'report.index', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' }
        ]
    },
    {
        label: 'Analysis',
        items: [
            { name: 'Performance', route: 'performance.index', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' }
        ]
    },
    {
        label: 'Master Data',
        items: [
            { name: 'Departments', route: 'master-data.departments.index', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
            { name: 'Equipment', route: 'master-data.equipments.index', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
            { name: 'Test Methods', route: 'master-data.test-methods.index', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
            { name: 'Users', route: 'master-data.users.index', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
            { name: 'External Users', route: 'master-data.external-users.index', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' }
        ]
    }
];

const currentDate = computed(() => {
    const now = new Date();
    return now.toLocaleDateString('en-GB', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
});
</script>

<template>
    <div class="flex h-screen w-full overflow-hidden bg-gray-50">
        <!-- SIDEBAR -->
        <aside class="w-[260px] bg-zinc-950 flex flex-col shrink-0 h-screen overflow-y-auto">
            <div class="p-5 flex items-center gap-3">
                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center text-gray-900 font-bold text-base shadow-sm">Q</div>
                <div>
                    <div class="text-base font-bold text-white leading-tight">QC Lab</div>
                    <div class="text-[11px] text-zinc-400 font-medium">Quality Control</div>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto">
                <template v-for="(group, gIdx) in groupedNav" :key="gIdx">
                    <div class="px-4" :class="[gIdx === 0 ? 'mt-2 mb-5' : 'mt-6 mb-5']">
                        <div class="px-3 text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-2">{{ group.label }}</div>
                        <div class="space-y-1">
                            <Link
                                v-for="item in group.items"
                                :key="item.route"
                                :href="route(item.route)"
                                prefetch
                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium cursor-pointer transition-colors duration-150 decoration-none"
                                :class="[
                                    route().current(item.route) 
                                    ? 'bg-white text-gray-900 shadow-sm' 
                                    : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'
                                ]"
                            >
                                <!-- Hardcode icons based on route name directly to perfectly match redesign -->
                                <svg class="w-5 h-5 shrink-0 mb-1" v-if="item.name === 'Dashboard'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <svg class="w-5 h-5 shrink-0 mb-1" v-else-if="item.name === 'Receive Job'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg class="w-5 h-5 shrink-0 mb-1" v-else-if="item.name === 'Execute Test'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <svg class="w-5 h-5 shrink-0 mb-1" v-else-if="item.name === 'Certificates'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <svg class="w-5 h-5 shrink-0 mb-1" v-else-if="item.name === 'Report'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <svg class="w-5 h-5 shrink-0 mb-1" v-else-if="item.name === 'Performance'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg class="w-5 h-5 shrink-0 mb-1" v-else fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                </svg>
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Mobile Menu Toggle Button (visible on mobile only) -->
            <div class="md:hidden p-4 border-t border-zinc-800 mt-auto">
                <button @click="showMobileMenu = true" class="w-full flex justify-center py-2 text-zinc-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="flex-1 flex flex-col min-w-0 bg-gray-50">
            <header class="h-16 bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8 flex items-center justify-between z-10 antialiased shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
                
                <!-- LEFT: Mobile Menu Toggle & Title Area -->
                <div class="flex items-center gap-3">
                    <button @click="showMobileMenu = true" class="md:hidden text-gray-500 hover:text-gray-900 focus:outline-none p-1.5 rounded-md hover:bg-gray-100 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="hidden sm:flex items-center text-[13px] font-medium tracking-tight">
                        <span class="text-gray-500 hover:text-gray-900 transition-colors cursor-pointer">QC Lab</span>
                        <svg class="w-4 h-4 text-gray-300 mx-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <span class="text-gray-900 font-semibold"><slot name="title">Workspace</slot></span>
                    </div>
                </div>

                <!-- RIGHT: Search, Date, Profile, Logout -->
                <div class="flex items-center gap-4 lg:gap-6">
                    
                    <!-- Search Input (Linear style) -->
                    <div class="relative group hidden md:block">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400 group-focus-within:text-gray-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            v-model="globalSearch" 
                            @keyup.enter="handleGlobalSearch" 
                            class="block w-[260px] lg:w-[320px] pl-9 pr-12 py-1.5 border border-gray-200 rounded-md leading-5 bg-gray-50/50 text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-gray-900 focus:border-gray-900 sm:text-[13px] transition-all focus:shadow-sm"
                            placeholder="Search DMC code..." 
                            type="text" 
                            autocomplete="off"
                        />
                        <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                            <span class="text-gray-400 text-[10px] font-mono font-medium border border-gray-200 rounded px-1.5 py-0.5 bg-white shadow-[0_1px_1px_rgba(0,0,0,0.04)]">⌘K</span>
                        </div>
                    </div>

                    <!-- Separator -->
                    <div class="hidden lg:block h-5 w-px bg-gray-200"></div>

                    <!-- User Actions Row -->
                    <div class="flex items-center gap-3 sm:gap-4">
                        <span class="text-[12px] text-gray-500 font-medium hidden xl:block tracking-wide bg-gray-50 px-2 py-1 rounded-md border border-gray-100">{{ currentDate }}</span>
                        
                        <!-- User Identity -->
                        <div class="flex items-center gap-3">
                            <div class="flex-col items-end hidden sm:flex leading-tight">
                                <span class="text-[13px] font-bold text-gray-900 tracking-tight">{{ $page.props.auth.user.name }}</span>
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5">{{ $page.props.auth.user.role === 'admin' ? 'Admin' : 'QC Tech' }}</span>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-gray-900 to-gray-700 text-white flex items-center justify-center text-[11px] font-bold ring-2 ring-white shadow-[0_2px_4px_rgba(0,0,0,0.1)] cursor-pointer hover:opacity-90 transition-opacity">
                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </div>
                        </div>

                        <!-- Logout Icon Button -->
                        <Link :href="route('logout')" method="post" as="button" class="text-gray-400 hover:text-gray-900 p-1.5 rounded-lg hover:bg-gray-100 transition-colors ml-1" title="Log out">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </Link>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto w-full p-6 lg:p-8">
                <div class="max-w-7xl mx-auto">
                    <slot />
                </div>
            </div>
        </main>

        <!-- Mobile Menu Overlay -->
        <div v-if="showMobileMenu" class="md:hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm" @click="showMobileMenu = false">
            <div class="w-[240px] h-full bg-white flex flex-col pt-4" @click.stop>
                <div class="flex items-center justify-between px-6 mb-6">
                     <div class="flex items-center gap-3">
                         <div class="w-8 h-8 bg-zinc-900 rounded-lg flex items-center justify-center text-white font-bold text-base shadow-sm">Q</div>
                         <h2 class="font-bold text-gray-900" style="font-size:16px;font-weight:700">QC Lab</h2>
                     </div>
                     <button @click="showMobileMenu = false" class="text-gray-400 hover:text-gray-900">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                     </button>
                </div>
                
                <div class="flex-1 overflow-y-auto px-4">
                     <template v-for="(group, gIdx) in groupedNav" :key="gIdx">
                         <div class="mb-6">
                             <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-3">{{ group.label }}</div>
                             <Link
                                 v-for="item in group.items"
                                 :key="item.route"
                                 :href="route(item.route)"
                                 prefetch
                                 @click="showMobileMenu = false"
                                 :class="['flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors mb-1', route().current(item.route) ? 'bg-[#EFF6FF] text-[#4F46E5] font-semibold' : 'text-gray-600 hover:bg-gray-50']"
                             >
                                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                 </svg>
                                 {{ item.name }}
                             </Link>
                         </div>
                     </template>
                </div>
            </div>
        </div>
    </div>
</template>
