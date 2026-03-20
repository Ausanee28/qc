<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <section class="rounded-3xl border border-slate-200/70 bg-white/80 shadow-[0_18px_40px_rgba(15,23,42,0.08)] backdrop-blur dark:border-slate-800/80 dark:bg-slate-900/70 dark:shadow-none">
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:px-6">
            <div>
                <h3 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ title }}</h3>
                <p v-if="subtitle" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
            </div>
            <slot name="actions" />
        </header>
        <div class="px-5 py-4 sm:px-6">
            <slot />
        </div>
    </section>
</template>
