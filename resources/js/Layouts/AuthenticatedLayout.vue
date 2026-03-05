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
    }
];

const currentDate = computed(() => {
    const now = new Date();
    return now.toLocaleDateString('en-GB', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
});
</script>

<template>
    <div style="display:flex;height:100vh;width:100%;overflow:hidden">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sb-header">
                <div class="logo">Q</div>
                <div>
                    <div style="font-size:16px;font-weight:700;color:#111827">QC Lab</div>
                    <div style="font-size:11px;color:#9CA3AF">Quality Control</div>
                </div>
            </div>
            
            <div style="flex:1;overflow-y:auto">
                <template v-for="(group, gIdx) in groupedNav" :key="gIdx">
                    <div class="nav-grp" :style="gIdx === 0 ? 'margin-top:8px' : ''">
                        <div class="nav-label">{{ group.label }}</div>
                        <Link
                            v-for="item in group.items"
                            :key="item.route"
                            :href="route(item.route)"
                            prefetch
                            :class="['nav-item', route().current(item.route) ? 'active' : '']"
                        >
                            <!-- Hardcode icons based on route name directly to perfectly match redesign -->
                            <svg v-if="item.name === 'Dashboard'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <svg v-else-if="item.name === 'Receive Job'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="item.name === 'Execute Test'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <svg v-else-if="item.name === 'Certificates'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <svg v-else-if="item.name === 'Report'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <svg v-else-if="item.name === 'Performance'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </Link>
                    </div>
                </template>
            </div>
            
            <!-- Mobile Menu Toggle Button -->
            <div class="md:hidden p-4 border-t border-gray-100 mt-auto">
                <button @click="showMobileMenu = true" class="w-full flex justify-center py-2 text-gray-500 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="main">
            <header class="topbar">
                <div class="search-box">
                    <svg width="14" height="14" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input v-model="globalSearch" @keyup.enter="handleGlobalSearch" placeholder="Search DMC code..." />
                </div>
                <div style="display:flex;align-items:center;gap:16px">
                    <span style="font-size:12px;color:#9CA3AF">{{ currentDate }}</span>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">
                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <span style="font-size:13px;font-weight:600;color:#374151">{{ $page.props.auth.user.name }}</span>
                    </div>
                    <Link :href="route('logout')" method="post" as="button" style="font-size:12px;color:#9CA3AF;background:none;border:none;cursor:pointer;font-family:Inter,sans-serif">Log out</Link>
                </div>
            </header>

            <div class="content">
                <div class="container">
                    <slot />
                </div>
            </div>
        </main>

        <!-- Mobile Menu Overlay -->
        <div v-if="showMobileMenu" class="md:hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm" @click="showMobileMenu = false">
            <div class="w-[240px] h-full bg-white flex flex-col pt-4" @click.stop>
                <div class="flex items-center justify-between px-6 mb-6">
                     <div class="flex items-center gap-3">
                         <div class="logo">Q</div>
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
