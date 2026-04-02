<script setup>
import Modal from '@/Components/Modal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'md',
    },
});

defineEmits(['close']);
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" @close="$emit('close')">
        <div class="relative p-6" :class="maxWidth === '2xl' ? 'max-h-[90vh] overflow-y-auto' : ''">
            <div
                class="absolute inset-x-0 top-0 h-1 rounded-t-2xl"
                style="background: linear-gradient(90deg, var(--color-accent-main), var(--color-accent-strong));"
            ></div>
            <div class="mt-2">
                <h2 class="text-xl font-bold text-stone-50">{{ title }}</h2>
            </div>
            <div class="mt-6">
                <slot />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <slot name="actions" />
            </div>
        </div>
    </Modal>
</template>
