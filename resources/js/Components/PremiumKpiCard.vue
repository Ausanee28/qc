<script setup>
defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    trendValue: { type: String, default: null },
    trendLabel: { type: String, default: null },
    trendType: { type: String, default: 'neutral' }, // 'positive', 'negative', 'neutral'
});
</script>

<template>
    <div class="bg-white rounded-2xl p-6 ring-1 ring-zinc-900/5 shadow-[0_1px_2px_rgba(0,0,0,0.04)] hover:shadow-md hover:shadow-zinc-200/50 transition-all duration-300 relative overflow-hidden group">
        
        <!-- Subtle Top Border Highlight (Linear inspiration) -->
        <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-zinc-200 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

        <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
                <!-- Meta Label -->
                <h3 class="text-[11px] font-medium tracking-widest text-zinc-400 uppercase">{{ title }}</h3>
                
                <!-- Primary Value -->
                <div class="mt-1 flex items-baseline gap-2 text-3xl font-semibold tracking-tight text-zinc-900">
                    {{ value }}
                </div>
            </div>

            <!-- Sleek Icon Container -->
            <div class="text-zinc-400 group-hover:text-zinc-600 transition-colors duration-300">
                <slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </slot>
            </div>
        </div>

        <!-- Trend / Context Footer -->
        <div v-if="trendValue || trendLabel" class="mt-4 flex items-center gap-2">
            <!-- Trend Badge -->
            <span v-if="trendValue" :class="[
                'inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium ring-1 ring-inset',
                trendType === 'positive' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/10' :
                trendType === 'negative' ? 'bg-rose-50 text-rose-700 ring-rose-600/10' :
                'bg-zinc-50 text-zinc-600 ring-zinc-500/10'
            ]">
                <svg v-if="trendType !== 'neutral'" :class="['w-3 h-3 mr-0.5', trendType === 'negative' ? 'rotate-180 text-rose-500' : 'text-emerald-500']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                {{ trendValue }}
            </span>
            
            <!-- Secondary Context -->
            <span v-if="trendLabel" class="text-xs text-zinc-400 font-medium tracking-tight">
                {{ trendLabel }}
            </span>
        </div>
    </div>
</template>
