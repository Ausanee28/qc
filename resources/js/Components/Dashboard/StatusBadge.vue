<script setup>
import { cva } from 'class-variance-authority';
import { computed } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps({
    label: { type: String, required: true },
    tone: {
        type: String,
        default: 'neutral',
    },
});

const badgeTone = cva('inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold', {
    variants: {
        tone: {
            neutral: 'border border-white/10 bg-white/5 text-stone-200',
            success: 'border border-orange-500/20 bg-orange-500/12 text-orange-100',
            danger: 'border border-white/10 bg-stone-800 text-stone-100',
            warning: 'border border-orange-500/18 bg-orange-500/10 text-orange-200',
            info: 'border border-orange-500/16 bg-orange-500/10 text-orange-100',
        },
    },
});

const badgeClass = computed(() => twMerge(badgeTone({ tone: props.tone })));
</script>

<template>
    <span :class="badgeClass">{{ label }}</span>
</template>
