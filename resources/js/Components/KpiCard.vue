<script setup>
defineProps({
    title: String,
    value: [String, Number],
    subtext: String,
    trend: { type: String, default: 'neutral' }, // 'up', 'down', 'neutral'
    iconClass: { type: String, default: 'text-indigo-600 bg-indigo-50' }
});
</script>

<template>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-5 hover:shadow-md transition-shadow duration-200">
        <!-- Icon Container -->
        <div :class="['w-14 h-14 rounded-xl flex items-center justify-center shrink-0', iconClass]">
            <slot name="icon">
                <!-- Default fallback icon -->
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </slot>
        </div>
        
        <!-- Data Context -->
        <div>
            <!-- Label -->
            <h3 class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1 drop-shadow-sm">{{ title }}</h3>
            <!-- Value -->
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ value }}</span>
                <!-- Subtext/Trend -->
                <span v-if="subtext" :class="[
                    'text-sm font-semibold',
                    trend === 'up' ? 'text-emerald-600' : 
                    trend === 'down' ? 'text-rose-600' : 'text-slate-500' // Handle undefined
                ]">
                    {{ subtext }}
                </span>
            </div>
        </div>
    </div>
</template>
