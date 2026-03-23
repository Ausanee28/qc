<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-xl border border-orange-500/20 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-stone-100 shadow-sm transition duration-150 ease-in-out hover:bg-orange-500/10 focus:outline-none focus:ring-2 focus:ring-orange-300/20 focus:ring-offset-2 focus:ring-offset-[#120d0a] disabled:opacity-25"
    >
        <slot />
    </button>
</template>
