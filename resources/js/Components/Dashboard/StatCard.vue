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

const cardTone = cva('relative overflow-hidden rounded-3xl border p-5 shadow-[0_18px_34px_rgba(0,0,0,0.24)]', {
    variants: {
        tone: {
            blue: 'border-orange-500/18 bg-gradient-to-br from-[#22160f] via-[#18110d] to-[#120d0a]',
            green: 'border-orange-500/16 bg-gradient-to-br from-[#28170f] via-[#1b140f] to-[#120d0a]',
            red: 'border-white/10 bg-gradient-to-br from-[#1f1917] via-[#151110] to-[#0f0b09]',
            yellow: 'border-orange-500/18 bg-gradient-to-br from-[#2a1a10] via-[#1b140f] to-[#120d0a]',
            orange: 'border-orange-500/22 bg-gradient-to-br from-[#311a0f] via-[#20140f] to-[#120d0a]',
        },
    },
});

const valueTone = cva('mt-3 text-5xl font-semibold leading-none tracking-tight', {
    variants: {
        tone: {
            blue: 'text-orange-100',
            green: 'text-orange-200',
            red: 'text-stone-100',
            yellow: 'text-orange-100',
            orange: 'text-orange-200',
        },
    },
});

const metaTone = cva('mt-2 text-sm font-medium', {
    variants: {
        tone: {
            blue: 'text-stone-400',
            green: 'text-stone-400',
            red: 'text-stone-400',
            yellow: 'text-stone-400',
            orange: 'text-orange-200/80',
        },
    },
});

const cardClass = computed(() => twMerge(cardTone({ tone: props.tone })));
const valueClass = computed(() => twMerge(valueTone({ tone: props.tone })));
const metaClass = computed(() => twMerge(metaTone({ tone: props.tone })));
</script>

<template>
    <article :class="cardClass">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(251,146,60,0.18),transparent_42%)]"></div>
        <p class="relative text-sm font-semibold text-stone-300">{{ title }}</p>
        <p :class="valueClass">{{ value }}</p>
        <p v-if="meta" :class="metaClass">{{ meta }}</p>
    </article>
</template>
