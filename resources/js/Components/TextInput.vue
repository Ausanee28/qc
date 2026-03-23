<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border border-white/10 bg-black/25 text-stone-100 shadow-sm placeholder:text-stone-500 focus:border-orange-400/40 focus:ring-orange-300/25"
        v-model="model"
        ref="input"
    />
</template>
