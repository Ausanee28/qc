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
            neutral: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
            success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            danger: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
            warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            info: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
        },
    },
});

const badgeClass = computed(() => twMerge(badgeTone({ tone: props.tone })));
</script>

<template>
    <span :class="badgeClass">{{ label }}</span>
</template>
