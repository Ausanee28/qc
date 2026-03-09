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
    <div :class="[
        'rounded-xl p-5 border relative overflow-hidden group hover:-translate-y-0.5 transition-all duration-300',
        trendType === 'positive' ? 'kpi-positive' : trendType === 'negative' ? 'kpi-negative' : 'kpi-neutral'
    ]" style="background-color: var(--color-bg-surface); border-color: var(--color-border-subtle); box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        
        <!-- Glow Left Border Accent -->
        <div class="absolute left-0 top-0 bottom-0 w-1 transition-all duration-300" 
             :style="{ 
                 backgroundColor: trendType === 'positive' ? 'var(--color-accent-teal)' : trendType === 'negative' ? 'var(--color-accent-coral)' : 'var(--color-border-strong)',
                 boxShadow: trendType === 'positive' ? '2px 0 12px var(--color-accent-teal-opacity)' : trendType === 'negative' ? '2px 0 12px var(--color-accent-coral-opacity)' : 'none'
             }">
        </div>

        <div class="flex items-start justify-between pl-2">
            <div class="flex flex-col gap-1 w-full">
                <div class="flex justify-between w-full">
                    <!-- Tech Label -->
                    <h3 class="text-[10px] font-bold tracking-[0.2em] uppercase" style="color: var(--color-text-muted);">
                        {{ title }}
                    </h3>
                    
                    <!-- Muted Icon -->
                    <div style="color: var(--color-border-strong);">
                        <slot name="icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </slot>
                    </div>
                </div>
                
                <!-- Display Value (Monospace style, tech bold) -->
                <div class="mt-2 text-3xl font-mono tracking-tighter" style="color: var(--color-text-bright); font-weight: 600;">
                    {{ value }}
                </div>
            </div>
        </div>

        <!-- Metric Footer -->
        <div v-if="trendValue || trendLabel" class="mt-4 pl-2 flex items-center gap-2">
            <!-- Badge -->
            <span v-if="trendValue" :class="[
                'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider',
                trendType === 'positive' ? 'bg-[#00d4aa20] text-[#00d4aa]' :
                trendType === 'negative' ? 'bg-[#ff575720] text-[#ff5757]' :
                'bg-[rgba(255,255,255,0.05)] text-[#8b949e]'
            ]">
                <svg v-if="trendType !== 'neutral'" :class="['w-3 h-3 mr-1']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="trendType === 'negative' ? 'M19 14l-7 7m0 0l-7-7m7 7V3' : 'M5 10l7-7m0 0l7 7m-7-7v18'" />
                </svg>
                {{ trendValue }}
            </span>
            
            <span v-if="trendLabel" class="text-[11px] font-medium tracking-wide" style="color: var(--color-text-muted);">
                {{ trendLabel }}
            </span>
        </div>
        
        <!-- Subtle Grid Overflow Texture -->
        <div class="absolute inset-0 pointer-events-none opacity-5 group-hover:opacity-10 transition-opacity" style="background-image: radial-gradient(var(--color-text-bright) 1px, transparent 1px); background-size: 16px 16px; mix-blend-mode: overlay;"></div>
    </div>
</template>

<style scoped>
/* Scoped overrides if needed, rely mostly on inherited root vars from layout */
.kpi-positive:hover { box-shadow: 0 8px 30px rgba(0, 212, 170, 0.1); border-color: rgba(0, 212, 170, 0.2); }
.kpi-negative:hover { box-shadow: 0 8px 30px rgba(255, 87, 87, 0.1); border-color: rgba(255, 87, 87, 0.2); }
.kpi-neutral:hover { box-shadow: 0 8px 30px rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.15); }
</style>
