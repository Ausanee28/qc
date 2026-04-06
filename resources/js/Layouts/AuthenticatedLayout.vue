<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useTheme, initializeThemePreference } from '@/composables/useTheme';

const navGroupsConfig = [
    {
        label: 'Analytics',
        items: [
            { name: 'Dashboard', route: 'dashboard', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
        ],
    },
    {
        label: 'Workflow',
        items: [
            { name: 'Receive Job', route: 'receive-job.create', icon: 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z' },
            { name: 'Execute Test', route: 'execute-test.create', icon: 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
        ],
    },
    {
        label: 'Documents',
        items: [
            { name: 'Report', route: 'report.index', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
        ],
    },
    {
        label: 'Analysis',
        items: [
            { name: 'Performance', route: 'performance.index', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
        ],
    },
    {
        label: 'Master Data',
        items: [
            { name: 'Departments', route: 'master-data.departments.index', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', adminOnly: true },
            { name: 'Equipment', route: 'master-data.equipments.index', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', adminOnly: true },
            { name: 'Test Methods', route: 'master-data.test-methods.index', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', adminOnly: true },
            { name: 'Users', route: 'master-data.users.index', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', adminOnly: true },
            { name: 'External Users', route: 'master-data.external-users.index', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', adminOnly: true },
        ],
    },
];

const dateFormatter = new Intl.DateTimeFormat('en-GB', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
});

const showMobileMenu = ref(false);
const page = usePage();
const { currentTheme, isLightTheme, toggleTheme } = useTheme();

const globalSearch = ref('');
const handleGlobalSearch = () => {
    if (globalSearch.value.trim()) {
        router.get(route('report.index'), { dmc: globalSearch.value.trim() });
        globalSearch.value = ''; // clear after search
    }
};

const groupedNav = computed(() => {
    const isAdmin = page.props.auth?.user?.role === 'admin';
    return navGroupsConfig
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => !item.adminOnly || isAdmin),
        }))
        .filter((group) => group.items.length > 0);
});

const user = computed(() => page.props.auth?.user ?? { name: '', role: '' });
const currentDate = dateFormatter.format(new Date());
const isActiveRoute = (routeName) => route().current(routeName);
const desktopNavClass = (routeName) => (
    isActiveRoute(routeName)
        ? (isLightTheme.value
            ? 'shell-nav-active border border-[rgba(29,78,216,0.34)] bg-[linear-gradient(135deg,#1D4ED8,#1E40AF)] text-white shadow-[0_18px_34px_rgba(29,78,216,0.24)]'
            : 'border border-orange-500/20 bg-[linear-gradient(135deg,rgba(251,146,60,0.2),rgba(249,115,22,0.1))] text-orange-100 shadow-[0_14px_28px_rgba(0,0,0,0.2)]')
        : (isLightTheme.value
            ? 'text-slate-700 hover:bg-[rgba(219,234,254,0.85)] hover:text-[#1E3A8A]'
            : 'text-stone-300 hover:bg-white/5 hover:text-orange-100')
);
const mobileNavClass = (routeName) => (
    isActiveRoute(routeName)
        ? (isLightTheme.value
            ? 'shell-nav-active bg-[linear-gradient(135deg,#1D4ED8,#1E40AF)] text-white font-semibold border border-[rgba(29,78,216,0.34)] shadow-[0_12px_28px_rgba(29,78,216,0.22)]'
            : 'bg-orange-500/15 text-orange-100 font-semibold border border-orange-500/20')
        : (isLightTheme.value
            ? 'text-slate-700 hover:bg-[rgba(219,234,254,0.96)]'
            : 'text-stone-300 hover:bg-white/5')
);
const brandBadgeClass = computed(() => (
    isLightTheme.value
        ? 'bg-[linear-gradient(135deg,#1D4ED8,#1E3A8A)] text-white shadow-[0_12px_24px_rgba(29,78,216,0.22)]'
        : 'bg-[linear-gradient(135deg,#fb923c,#ea580c)] text-[#140d08] shadow-[0_10px_24px_rgba(249,115,22,0.25)]'
));
const shellRootClass = computed(() => (
    isLightTheme.value
        ? 'theme-shell shell-frame flex h-screen w-full overflow-hidden bg-[linear-gradient(180deg,#eef4fb,#f8fbff_24%,#f3f7fc_100%)] text-slate-900'
        : 'theme-shell shell-frame flex h-screen w-full overflow-hidden bg-[#090909] text-stone-100'
));
const sidebarClass = computed(() => (
    isLightTheme.value
        ? 'shell-sidebar hidden w-[264px] flex-col shrink-0 h-screen border-r border-[rgba(15,23,42,0.12)] bg-[linear-gradient(180deg,#fdfefe,#f4f8fe_58%,#eaf1f9)] lg:flex shadow-[inset_-1px_0_0_rgba(255,255,255,0.8)]'
        : 'shell-sidebar hidden w-[264px] flex-col shrink-0 h-screen border-r border-white/10 bg-[linear-gradient(180deg,#0a0a0a,#18110d_58%,#0b0b0b)] lg:flex'
));
const shellHeaderClass = computed(() => (
    isLightTheme.value
        ? 'shell-header h-16 bg-[linear-gradient(180deg,rgba(255,255,255,0.97),rgba(247,250,252,0.94))] px-4 sm:px-6 lg:px-8 flex items-center justify-between z-10 antialiased border-b border-[rgba(15,23,42,0.08)] shadow-[0_8px_24px_rgba(15,23,42,0.05)]'
        : 'shell-header h-16 bg-[#0c0c0c]/92 px-4 sm:px-6 lg:px-8 flex items-center justify-between z-10 antialiased'
));
const workspaceFrameClass = computed(() => (
    isLightTheme.value
        ? 'rounded-2xl border border-[rgba(15,23,42,0.12)] bg-[linear-gradient(180deg,rgba(255,255,255,0.99),rgba(243,247,252,0.98))] p-4 shadow-[0_24px_48px_rgba(15,23,42,0.08)] lg:p-5'
        : 'rounded-2xl border border-white/10 bg-[linear-gradient(180deg,rgba(10,10,10,0.96),rgba(7,7,7,0.98))] p-4 shadow-[0_18px_40px_rgba(0,0,0,0.32)] lg:p-5'
));
const mobileOverlayClass = computed(() => (
    isLightTheme.value
        ? 'lg:hidden fixed inset-0 z-50 bg-[rgba(15,23,42,0.16)] backdrop-blur-sm'
        : 'lg:hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm'
));
const mobilePanelClass = computed(() => (
    isLightTheme.value
        ? 'shell-mobile-panel w-[260px] h-full bg-[linear-gradient(180deg,#ffffff,#f4f8fc)] border-r border-[rgba(15,23,42,0.08)] flex flex-col pt-4'
        : 'shell-mobile-panel w-[260px] h-full bg-[linear-gradient(180deg,#0b0b0b,#18110d)] border-r border-white/10 flex flex-col pt-4'
));
const searchIconClass = computed(() => (
    isLightTheme.value
        ? 'h-4 w-4 transition-colors text-slate-500 group-focus-within:text-[#1D4ED8]'
        : 'h-4 w-4 transition-colors text-stone-500 group-focus-within:text-orange-200'
));
const searchInputClass = computed(() => (
    isLightTheme.value
        ? 'shell-search-input block w-[240px] lg:w-[300px] pl-9 pr-3 py-2 border border-[rgba(15,23,42,0.12)] rounded-xl leading-5 bg-[rgba(255,255,255,0.98)] text-slate-900 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#1D4ED8]/12 focus:border-[#1D4ED8]/36 sm:text-[13px] transition-all shadow-[0_6px_18px_rgba(15,23,42,0.04)]'
        : 'shell-search-input block w-[240px] lg:w-[300px] pl-9 pr-3 py-2 border border-white/10 rounded-xl leading-5 bg-white/5 text-stone-100 placeholder-stone-500 focus:outline-none focus:bg-black/30 focus:ring-1 focus:ring-orange-400/30 focus:border-orange-400/30 sm:text-[13px] transition-all'
));
const dateChipClass = computed(() => (
    isLightTheme.value
        ? 'shell-date-chip text-[12px] font-medium hidden xl:block tracking-wide bg-[rgba(255,255,255,0.98)] px-2 py-1 rounded-md border border-[rgba(15,23,42,0.12)] text-slate-600 shadow-[0_6px_18px_rgba(15,23,42,0.04)]'
        : 'shell-date-chip text-[12px] font-medium hidden xl:block tracking-wide bg-white/5 px-2 py-1 rounded-md border border-white/10 text-stone-400'
));
const crumbBrandClass = computed(() => (isLightTheme.value ? 'transition-colors cursor-pointer text-slate-600 hover:text-[#1D4ED8]' : 'transition-colors cursor-pointer text-stone-500 hover:text-orange-200'));
const crumbDividerClass = computed(() => (isLightTheme.value ? 'w-4 h-4 text-slate-400 mx-1.5' : 'w-4 h-4 text-stone-700 mx-1.5'));
const crumbTitleClass = computed(() => (isLightTheme.value ? 'font-semibold text-slate-900' : 'font-semibold text-stone-100'));
const userNameClass = computed(() => (isLightTheme.value ? 'text-[13px] font-bold tracking-tight text-slate-900' : 'text-[13px] font-bold tracking-tight text-stone-100'));
const userRoleClass = computed(() => (isLightTheme.value ? 'text-[10px] font-bold uppercase tracking-widest mt-0.5 text-[#1D4ED8]/75' : 'text-[10px] font-bold uppercase tracking-widest mt-0.5 text-orange-200/70'));
const userAvatarClass = computed(() => (
    isLightTheme.value
        ? 'w-9 h-9 rounded-full flex items-center justify-center text-[11px] font-bold cursor-pointer hover:opacity-90 transition-opacity bg-[linear-gradient(135deg,#1D4ED8,#1E3A8A)] text-white shadow-[0_10px_22px_rgba(29,78,216,0.18)]'
        : 'w-9 h-9 rounded-full flex items-center justify-center text-[11px] font-bold cursor-pointer hover:opacity-90 transition-opacity bg-[linear-gradient(135deg,#fb923c,#c2410c)] text-[#120d08] shadow-[0_8px_20px_rgba(249,115,22,0.28)]'
));
const iconButtonClass = computed(() => (
    isLightTheme.value
        ? 'p-1.5 rounded-lg hover:bg-[rgba(219,234,254,0.9)] transition-colors text-slate-500 hover:text-[#1D4ED8]'
        : 'p-1.5 rounded-lg hover:bg-white/5 transition-colors text-stone-500 hover:text-orange-200'
));
const sidebarLabelClass = computed(() => (isLightTheme.value ? 'px-2.5 text-[10px] font-semibold text-slate-500 uppercase tracking-[0.16em] mb-1.5' : 'px-2.5 text-[10px] font-semibold text-stone-500 uppercase tracking-[0.16em] mb-1.5'));
const brandTitleClass = computed(() => (isLightTheme.value ? 'text-[19px] font-bold leading-tight text-slate-900' : 'text-[19px] font-bold leading-tight text-white'));
const brandSubtitleClass = computed(() => (isLightTheme.value ? 'text-[10px] font-medium uppercase tracking-[0.16em] text-[#1D4ED8]/70' : 'text-[10px] font-medium uppercase tracking-[0.16em] text-orange-200/70'));
const themeToggleClass = computed(() => (
    isLightTheme.value
        ? 'shell-theme-toggle inline-flex h-9 items-center gap-2 rounded-xl border border-[rgba(15,23,42,0.12)] bg-[rgba(255,255,255,0.98)] px-3 text-[12px] font-semibold text-slate-600 shadow-[0_10px_24px_rgba(15,23,42,0.06)] transition hover:border-[rgba(29,78,216,0.22)] hover:bg-[rgba(219,234,254,0.82)] hover:text-[#1D4ED8]'
        : 'shell-theme-toggle inline-flex h-9 items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 text-[12px] font-semibold text-stone-300 transition hover:border-orange-500/20 hover:bg-white/8 hover:text-orange-100'
));
const navCacheFor = (routeName) => (
    routeName === 'receive-job.create' || routeName === 'execute-test.create'
        ? ['2m', '20m']
        : routeName === 'dashboard' || routeName === 'report.index' || routeName === 'performance.index'
            ? ['1m', '15m']
            : ['45s', '8m']
);
const navCacheTags = (routeName) => (
    routeName === 'dashboard'
        ? ['dashboard']
        : routeName === 'receive-job.create'
            ? ['workflow', 'receive-job']
            : routeName === 'execute-test.create'
                ? ['workflow', 'execute-test', 'performance']
                : routeName === 'report.index'
                    ? ['report']
                    : routeName === 'performance.index'
                            ? ['performance']
                            : routeName === 'master-data.departments.index'
                                ? ['master-data', 'master-data:departments']
                                : routeName === 'master-data.equipments.index'
                                    ? ['master-data', 'master-data:equipments']
                                    : routeName === 'master-data.test-methods.index'
                                        ? ['master-data', 'master-data:test-methods']
                                        : routeName === 'master-data.users.index'
                                            ? ['master-data', 'master-data:users']
                                            : routeName === 'master-data.external-users.index'
                                                ? ['master-data', 'master-data:external-users']
                            : ['nav']
);
const userInitial = computed(() => user.value.name.charAt(0).toUpperCase());
const userRoleLabel = computed(() => (user.value.role === 'admin' ? 'Admin' : 'QC Tech'));
const navPrefetchTimers = [];
const navPrefetchIdleHandles = [];
const prefetchedNavRoutes = new Set();
const workflowNavRoutes = computed(() => {
    const availableRoutes = new Set(groupedNav.value.flatMap((group) => group.items.map((item) => item.route)));

    return ['receive-job.create', 'execute-test.create']
        .filter((routeName) => availableRoutes.has(routeName) && !isActiveRoute(routeName));
});
const secondaryNavRoutes = computed(() => {
    const priority = [
        'dashboard',
        'report.index',
        'performance.index',
    ];

    const availableRoutes = new Set(groupedNav.value.flatMap((group) => group.items.map((item) => item.route)));

    return priority.filter((routeName) => availableRoutes.has(routeName) && !isActiveRoute(routeName));
});
const allNavRoutes = computed(() => (
    groupedNav.value
        .flatMap((group) => group.items.map((item) => item.route))
        .filter((routeName) => !isActiveRoute(routeName))
));

const clearNavPrefetchSchedule = () => {
    while (navPrefetchTimers.length) {
        window.clearTimeout(navPrefetchTimers.pop());
    }

    while (navPrefetchIdleHandles.length) {
        const handle = navPrefetchIdleHandles.pop();

        if (typeof window.cancelIdleCallback === 'function') {
            window.cancelIdleCallback(handle);
        } else {
            window.clearTimeout(handle);
        }
    }
};

const scheduleIdlePrefetch = (callback, delay = 0) => {
    if (typeof window.requestIdleCallback === 'function') {
        const handle = window.requestIdleCallback(callback, { timeout: 900 + delay });
        navPrefetchIdleHandles.push(handle);
        return;
    }

    const handle = window.setTimeout(callback, 180 + delay);
    navPrefetchIdleHandles.push(handle);
};

const prefetchNavRoutes = (routeNames, delayStep = 120) => {
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

    if (connection?.saveData) {
        return;
    }

    routeNames.forEach((routeName, index) => {
        const timer = window.setTimeout(() => {
            prefetchedNavRoutes.add(routeName);
            router.prefetch(route(routeName), {}, {
                cacheFor: navCacheFor(routeName),
                cacheTags: navCacheTags(routeName),
            });
        }, index * delayStep);

        navPrefetchTimers.push(timer);
    });
};

const warmNavRoute = (routeName) => {
    if (!routeName || isActiveRoute(routeName) || prefetchedNavRoutes.has(routeName)) {
        return;
    }

    prefetchedNavRoutes.add(routeName);
    router.prefetch(route(routeName), {}, {
        cacheFor: navCacheFor(routeName),
        cacheTags: navCacheTags(routeName),
    });
};

onMounted(() => {
    initializeThemePreference();
    prefetchNavRoutes(workflowNavRoutes.value, 80);
    scheduleIdlePrefetch(() => prefetchNavRoutes(secondaryNavRoutes.value), 180);
    scheduleIdlePrefetch(() => prefetchNavRoutes(allNavRoutes.value, 110), 420);
});

onUnmounted(() => {
    clearNavPrefetchSchedule();
});
</script>

<template>
    <div :data-theme="currentTheme" :class="shellRootClass">
        <!-- SIDEBAR -->
        <aside :class="sidebarClass">
            <div class="h-16 shrink-0 border-b px-4 flex items-center gap-2.5" :class="isLightTheme ? 'border-[rgba(15,23,42,0.08)]' : 'border-white/10'">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-sm" :class="brandBadgeClass">Q</div>
                <div>
                    <div :class="brandTitleClass">QC Lab</div>
                    <div :class="brandSubtitleClass">Quality Control</div>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-hidden px-1.5" scroll-region>
                <template v-for="(group, gIdx) in groupedNav" :key="group.label">
                    <div class="px-3.5" :class="[gIdx === 0 ? 'mt-2.5 mb-3.5' : 'mt-3.5 mb-3.5']">
                        <div :class="sidebarLabelClass">{{ group.label }}</div>
                        <div class="space-y-0">
                            <Link
                                v-for="item in group.items"
                                :key="item.route"
                                :href="route(item.route)"
                                preserve-scroll
                                prefetch="hover"
                                :cache-for="navCacheFor(item.route)"
                                :view-transition="false"
                                @mousedown="warmNavRoute(item.route)"
                                @focus="warmNavRoute(item.route)"
                                @mouseenter="warmNavRoute(item.route)"
                                class="flex w-full items-center gap-2.5 px-2.5 py-1.5 rounded-xl text-[13px] font-medium cursor-pointer transition-colors duration-150 decoration-none text-left"
                                :class="desktopNavClass(item.route)"
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                </svg>
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Mobile Menu Toggle Button (visible on mobile only) -->
            <div class="md:hidden p-4 border-t mt-auto" :class="isLightTheme ? 'border-[rgba(15,23,42,0.08)]' : 'border-white/10'">
                <button @click="showMobileMenu = true" class="w-full flex justify-center py-2" :class="isLightTheme ? 'text-slate-400 hover:text-[#1D4ED8]' : 'text-stone-500 hover:text-orange-200'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="flex-1 flex flex-col min-w-0 bg-transparent">
            <header :class="shellHeaderClass">
                
                <!-- LEFT: Mobile Menu Toggle & Title Area -->
                <div class="flex items-center gap-3">
                    <button @click="showMobileMenu = true" class="lg:hidden focus:outline-none p-1.5 rounded-md transition-colors" :class="isLightTheme ? 'hover:bg-[rgba(219,234,254,0.7)] text-slate-400 hover:text-[#1D4ED8]' : 'hover:bg-white/5 text-stone-400 hover:text-orange-200'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="hidden sm:flex items-center text-[13px] font-medium tracking-tight">
                        <span :class="crumbBrandClass">QC Lab</span>
                        <svg :class="crumbDividerClass" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <span :class="crumbTitleClass"><slot name="title">Workspace</slot></span>
                    </div>
                </div>

                <!-- RIGHT: Search, Date, Profile, Logout -->
                <div class="flex items-center gap-4 lg:gap-6">
                    
                    <!-- Search Input (Linear style) -->
                    <div class="relative group hidden md:block">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg :class="searchIconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            v-model="globalSearch" 
                            @keyup.enter="handleGlobalSearch" 
                            :class="searchInputClass"
                            placeholder="Search DMC code..." 
                            type="text" 
                            autocomplete="off"
                        />
                    </div>

                    <!-- User Actions Row -->
                    <div class="flex items-center gap-3 sm:gap-4">
                        <button type="button" @click="toggleTheme" :class="themeToggleClass">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path v-if="isLightTheme" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2.5m0 13V21m9-9h-2.5M5.5 12H3m15.364 6.364l-1.768-1.768M7.404 7.404 5.636 5.636m12.728 0-1.768 1.768M7.404 16.596l-1.768 1.768M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ isLightTheme ? 'Dark' : 'Light' }}</span>
                        </button>
                        <span :class="dateChipClass">{{ currentDate }}</span>
                        
                        <!-- User Identity -->
                        <div class="flex items-center gap-3">
                            <div class="flex-col items-end hidden sm:flex leading-tight">
                                <span :class="userNameClass">{{ user.name }}</span>
                                <span :class="userRoleClass">{{ userRoleLabel }}</span>
                            </div>
                            <div :class="userAvatarClass">
                                {{ userInitial }}
                            </div>
                        </div>

                        <!-- Logout Icon Button -->
                        <Link :href="route('logout')" method="post" as="button" :class="[iconButtonClass, 'ml-1']" title="Log out">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </Link>
                    </div>
                </div>
            </header>

            <div class="shell-scroll-region flex-1 overflow-y-auto w-full px-6 pb-6 pt-4 lg:px-8 lg:pb-8 lg:pt-5">
                <div class="max-w-7xl mx-auto">
                    <div :class="workspaceFrameClass">
                        <slot />
                    </div>
                </div>
            </div>
        </main>

        <!-- Mobile Menu Overlay -->
        <div v-if="showMobileMenu" :class="mobileOverlayClass" @click="showMobileMenu = false">
            <div :class="mobilePanelClass" @click.stop>
                <div class="flex items-center justify-between px-6 mb-6">
                     <div class="flex items-center gap-3">
                         <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-base shadow-sm" :class="brandBadgeClass">Q</div>
                         <h2 class="font-bold" :class="isLightTheme ? 'text-slate-900' : 'text-white'" style="font-size:16px;font-weight:700">QC Lab</h2>
                     </div>
                     <button @click="showMobileMenu = false" :class="isLightTheme ? 'text-slate-400 hover:text-[#1D4ED8]' : 'text-stone-500 hover:text-orange-200'">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                     </button>
                </div>
                
                <div class="flex-1 overflow-y-auto px-4">
                     <template v-for="group in groupedNav" :key="group.label">
                         <div class="mb-6">
                             <div class="text-[10px] font-bold uppercase tracking-wider mb-2 px-3" :class="isLightTheme ? 'text-slate-400' : 'text-stone-500'">{{ group.label }}</div>
                             <Link
                                 v-for="item in group.items"
                                 :key="item.route"
                                 :href="route(item.route)"
                                 preserve-scroll
                                 prefetch="hover"
                                 :cache-for="navCacheFor(item.route)"
                                 :view-transition="false"
                                 @mousedown="warmNavRoute(item.route)"
                                 @focus="warmNavRoute(item.route)"
                                 @mouseenter="warmNavRoute(item.route)"
                                 @click="showMobileMenu = false"
                                 :class="['flex w-full items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors mb-1 text-left', mobileNavClass(item.route)]"
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
