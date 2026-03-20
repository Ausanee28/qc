<script setup>
import { cva } from 'class-variance-authority';
import { computed } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    meta: { type: String, default: '' },
    tone: {
        type: String,
        default: 'blue',
    },
});

const cardTone = cva('relative overflow-hidden rounded-3xl border p-5 shadow-[0_14px_34px_rgba(15,23,42,0.08)] dark:shadow-none', {
    variants: {
        tone: {
            blue: 'border-sky-200/70 bg-gradient-to-br from-sky-100 via-sky-50 to-white dark:border-sky-900/60 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-900 dark:to-sky-950/60',
            green: 'border-emerald-200/70 bg-gradient-to-br from-emerald-100 via-emerald-50 to-white dark:border-emerald-900/60 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-900 dark:to-emerald-950/60',
            red: 'border-rose-200/70 bg-gradient-to-br from-rose-100 via-rose-50 to-white dark:border-rose-900/60 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-900 dark:to-rose-950/60',
            yellow: 'border-amber-200/70 bg-gradient-to-br from-amber-100 via-yellow-50 to-white dark:border-amber-900/60 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-900 dark:to-amber-950/60',
            orange: 'border-orange-200/70 bg-gradient-to-br from-orange-100 via-orange-50 to-white dark:border-orange-900/60 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-900 dark:to-orange-950/60',
        },
    },
});

const valueTone = cva('mt-3 text-5xl font-semibold leading-none tracking-tight', {
    variants: {
        tone: {
            blue: 'text-sky-900 dark:text-sky-200',
            green: 'text-emerald-900 dark:text-emerald-200',
            red: 'text-rose-900 dark:text-rose-200',
            yellow: 'text-amber-900 dark:text-amber-200',
            orange: 'text-orange-900 dark:text-orange-200',
        },
    },
});

const metaTone = cva('mt-2 text-sm font-medium', {
    variants: {
        tone: {
            blue: 'text-sky-700 dark:text-sky-300',
            green: 'text-emerald-700 dark:text-emerald-300',
            red: 'text-rose-700 dark:text-rose-300',
            yellow: 'text-amber-700 dark:text-amber-300',
            orange: 'text-orange-700 dark:text-orange-300',
        },
    },
});

const cardClass = computed(() => twMerge(cardTone({ tone: props.tone })));
const valueClass = computed(() => twMerge(valueTone({ tone: props.tone })));
const metaClass = computed(() => twMerge(metaTone({ tone: props.tone })));
</script>

<template>
    <article :class="cardClass">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.78),transparent_38%)] dark:bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.06),transparent_40%)]"></div>
        <p class="relative text-sm font-semibold text-slate-800 dark:text-slate-200">{{ title }}</p>
        <p :class="valueClass">{{ value }}</p>
        <p v-if="meta" :class="metaClass">{{ meta }}</p>
    </article>
</template>
