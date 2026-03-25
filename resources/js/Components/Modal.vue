<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);
let hideTimer = null;

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        e.preventDefault();

        if (props.show) {
            close();
        }
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (hideTimer !== null) {
            window.clearTimeout(hideTimer);
            hideTimer = null;
        }

        if (isOpen) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;
            document.addEventListener('keydown', closeOnEscape);

            dialog.value?.showModal();
        } else {
            document.body.style.overflow = '';
            document.removeEventListener('keydown', closeOnEscape);

            hideTimer = window.setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;
                hideTimer = null;
            }, 200);
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    if (hideTimer !== null) {
        window.clearTimeout(hideTimer);
        hideTimer = null;
    }

    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <dialog
        class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
        ref="dialog"
    >
        <div
            class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
            scroll-region
        >
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 transform transition-all"
                    @click="close"
                >
                    <div
                        class="absolute inset-0 bg-black opacity-70"
                    />
                </div>
            </Transition>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div v-show="show" class="flex min-h-full items-center justify-center">
                    <div
                        class="w-full transform overflow-hidden rounded-2xl border border-orange-500/20 bg-[linear-gradient(180deg,rgba(20,16,13,0.98),rgba(12,10,9,0.96))] shadow-xl transition-all sm:mx-auto sm:w-full"
                        :class="maxWidthClass"
                    >
                        <slot v-if="showSlot" />
                    </div>
                </div>
            </Transition>
        </div>
    </dialog>
</template>
