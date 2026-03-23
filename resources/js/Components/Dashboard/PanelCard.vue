<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <section class="rounded-3xl border border-orange-500/16 bg-[linear-gradient(180deg,rgba(21,16,13,0.96),rgba(16,12,10,0.94))] shadow-[0_18px_40px_rgba(0,0,0,0.24)] backdrop-blur">
        <header class="flex items-center justify-between gap-4 border-b border-white/8 px-5 py-4 sm:px-6">
            <div>
                <h3 class="text-lg font-semibold tracking-tight text-stone-100">{{ title }}</h3>
                <p v-if="subtitle" class="mt-1 text-sm text-stone-400">{{ subtitle }}</p>
            </div>
            <slot name="actions" />
        </header>
        <div class="px-5 py-4 sm:px-6">
            <slot />
        </div>
    </section>
</template>
