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
        class="app-btn app-btn--secondary inline-flex items-center px-4 py-2 text-xs font-semibold uppercase tracking-widest shadow-sm transition duration-150 ease-in-out focus:outline-none disabled:opacity-25"
    >
        <slot />
    </button>
</template>
