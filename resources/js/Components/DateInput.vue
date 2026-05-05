<script setup>
import { computed, ref } from 'vue';
import { displayToIsoDate, isoToDisplayDate } from '@/lib/date-format';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    ariaLabel: {
        type: String,
        default: 'Select date',
    },
    placeholder: {
        type: String,
        default: 'DD-MM-YYYY',
    },
    fieldClass: {
        type: [String, Array, Object],
        default: '',
    },
    inputClass: {
        type: [String, Array, Object],
        default: '',
    },
    showDisplay: {
        type: Boolean,
        default: true,
    },
    outputFormat: {
        type: String,
        default: 'iso',
        validator: (value) => ['iso', 'display'].includes(value),
    },
});

const emit = defineEmits(['update:modelValue', 'change']);
const nativeInput = ref(null);

const displayValue = computed(() => isoToDisplayDate(props.modelValue));
const nativeValue = computed(() => displayToIsoDate(props.modelValue));

const emitDate = (isoValue) => {
    const nextValue = props.outputFormat === 'display' ? isoToDisplayDate(isoValue) : isoValue;
    emit('update:modelValue', nextValue);
    emit('change', nextValue);
};

</script>

<template>
    <div class="date-input-field" :class="fieldClass">
        <span
            v-if="showDisplay"
            class="date-input-field__display"
            :class="inputClass"
            :data-empty="displayValue ? 'false' : 'true'"
        >
            {{ displayValue || placeholder }}
        </span>
        <span class="date-input-field__button" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M5 11h14M7 21h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
            </svg>
        </span>
        <input
            ref="nativeInput"
            type="date"
            class="date-input-field__native"
            :value="nativeValue"
            :aria-label="ariaLabel"
            @change="emitDate($event.target.value)"
        >
    </div>
</template>

<style scoped>
.date-input-field {
    position: relative;
    min-width: 0;
    display: flex;
    align-items: center;
    cursor: pointer;
}

.date-input-field__display {
    min-width: 0;
    width: 100%;
    flex: 1;
    display: block;
    border: 0 !important;
    background: transparent !important;
    background-color: transparent !important;
    box-shadow: none !important;
    color: inherit !important;
    font: inherit;
    outline: none !important;
    padding: 0 30px 0 0 !important;
    cursor: pointer;
    line-height: 1.5;
    user-select: none;
}

.date-input-field__display[data-empty='true'] {
    color: currentColor;
    opacity: 0.72;
}

.date-input-field--strong-placeholder .date-input-field__display[data-empty='true'] {
    opacity: 1;
}

.date-input-field__button {
    position: absolute;
    top: 50%;
    right: 12px;
    display: inline-flex;
    height: 22px;
    width: 22px;
    align-items: center;
    justify-content: center;
    color: currentColor;
    pointer-events: none;
    transform: translateY(-50%);
    z-index: 2;
}

.date-input-field__button svg {
    height: 16px;
    width: 16px;
}

.date-input-field__native {
    position: absolute;
    inset: 0;
    height: 100%;
    width: 100%;
    border: 0;
    background: transparent;
    color: transparent;
    cursor: pointer;
    opacity: 0;
    -webkit-appearance: none;
    appearance: none;
    z-index: 3;
}

.date-input-field__native::-webkit-calendar-picker-indicator {
    position: absolute;
    inset: 0;
    height: 100%;
    width: 100%;
    cursor: pointer;
    opacity: 0;
}
</style>
