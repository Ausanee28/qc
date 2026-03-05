<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    user_name: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign in" />

        <div class="ent-header">
            <h1 class="ent-title">Sign in to QC Lab</h1>
            <p class="ent-subtitle">Welcome back! Please enter your details.</p>
        </div>

        <div v-if="status" class="ent-status">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="ent-form">
            <!-- Username Field -->
            <div class="ent-field-group">
                <label for="user_name" class="ent-label">Username</label>
                <div class="ent-input-wrapper">
                    <input
                        id="user_name"
                        type="text"
                        v-model="form.user_name"
                        required
                        autofocus
                        placeholder="e.g. admin"
                        class="ent-input"
                        :class="{ 'has-error': form.errors.user_name }"
                    />
                </div>
                <InputError class="ent-error" :message="form.errors.user_name" />
            </div>

            <!-- Password Field -->
            <div class="ent-field-group">
                <label for="password" class="ent-label">Password</label>
                <div class="ent-input-wrapper">
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                        class="ent-input ent-input-pw"
                        :class="{ 'has-error': form.errors.password }"
                    />
                    <!-- Minimal Eye Icon for Show/Hide -->
                    <button type="button" class="ent-eye-btn" @click="showPassword = !showPassword" tabindex="-1" aria-label="Toggle password visibility">
                        <svg v-if="!showPassword" class="ent-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else class="ent-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                <InputError class="ent-error" :message="form.errors.password" />
            </div>

            <!-- Options: Remember me & Forgot Password -->
            <div class="ent-form-options">
                <label class="ent-checkbox-label">
                    <input type="checkbox" v-model="form.remember" class="ent-checkbox" />
                    <span class="ent-checkbox-text">Remember me for 30 days</span>
                </label>
                <Link :href="route('password.request')" class="ent-forgot-link">Forgot password?</Link>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="ent-submit-btn"
                :disabled="form.processing"
            >
                <span v-if="!form.processing">Sign in</span>
                <span v-else class="ent-loading">
                    <svg class="ent-spinner" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                        <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" opacity="0.8"></path>
                    </svg>
                    Signing in...
                </span>
            </button>

            <div class="ent-register-link">
                Don't have an account? <Link :href="route('register')" class="ent-link">Create one</Link>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
/* Typography & Headers */
.ent-header {
    text-align: center;
    margin-bottom: 32px;
}

.ent-title {
    font-size: 24px;
    font-weight: 600;
    color: #111;
    letter-spacing: -0.03em;
    margin-bottom: 6px;
}

.ent-subtitle {
    font-size: 14px;
    color: #666;
    line-height: 1.5;
}

/* Status Notifications */
.ent-status {
    padding: 12px 16px;
    margin-bottom: 24px;
    font-size: 13px;
    font-weight: 500;
    color: #065f46;
    background-color: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 8px;
    text-align: center;
}

/* Form Layout */
.ent-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.ent-field-group {
    display: flex;
    flex-direction: column;
}

.ent-label {
    display: inline-block;
    font-size: 13px;
    font-weight: 500;
    color: #444;
    margin-bottom: 6px;
}

/* Inputs (Senior UI Pattern: Crisp 1px focus rings, background transition) */
.ent-input-wrapper {
    position: relative;
    width: 100%;
}

.ent-input {
    width: 100%;
    height: 44px;
    padding: 0 14px;
    font-size: 14px;
    font-family: inherit;
    color: #111;
    background-color: #FAFAFA;
    border: 1px solid #EAEAEA;
    border-radius: 8px;
    transition: all 0.2s ease;
    box-sizing: border-box;
    /* Hide native eye icon in Edge/IE */
    &::-ms-reveal,
    &::-ms-clear {
        display: none;
    }
}

.ent-input::placeholder {
    color: #A1A1AA;
}

.ent-input:hover {
    border-color: #D4D4D8;
}

.ent-input:focus {
    outline: none;
    background-color: #FFFFFF;
    border-color: #000;
    box-shadow: 0 0 0 1px #000;
}

.ent-input.has-error {
    border-color: #EF4444;
    background-color: #FEF2F2;
}
.ent-input.has-error:focus {
    box-shadow: 0 0 0 1px #EF4444;
}

/* Eye Toggle */
.ent-input-pw {
    padding-right: 44px;
}

.ent-eye-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #A1A1AA;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: color 0.15s, background-color 0.15s;
}

.ent-eye-btn:hover {
    color: #27272A;
    background-color: #F4F4F5;
}

.ent-eye-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px #000;
}

.ent-icon {
    width: 18px;
    height: 18px;
}

/* Validation */
.ent-error {
    margin-top: 6px;
    font-size: 13px;
    color: #EF4444;
}

/* Checkboxes */
.ent-form-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: -4px;
}

.ent-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.ent-checkbox {
    appearance: none;
    width: 16px;
    height: 16px;
    border: 1px solid #D4D4D8;
    border-radius: 4px;
    background-color: #FFFFFF;
    cursor: pointer;
    position: relative;
    transition: all 0.2s;
}

.ent-checkbox:checked {
    background-color: #000;
    border-color: #000;
}

.ent-checkbox:checked::after {
    content: '';
    position: absolute;
    left: 4px;
    top: 1px;
    width: 4px;
    height: 8px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.ent-checkbox:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #000;
}

.ent-checkbox-text {
    font-size: 13px;
    color: #555;
    user-select: none;
}

/* Submit Button */
.ent-submit-btn {
    width: 100%;
    height: 44px;
    margin-top: 8px;
    background-color: #000;
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.1s, box-shadow 0.2s;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.ent-submit-btn:hover:not(:disabled) {
    background-color: #1A1A1A;
}

.ent-submit-btn:active:not(:disabled) {
    transform: scale(0.98);
}

.ent-submit-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px #000;
}

.ent-submit-btn:disabled {
    background-color: #EAEAEA;
    color: #A1A1AA;
    cursor: not-allowed;
    box-shadow: none;
}

.ent-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.ent-spinner {
    width: 16px;
    height: 16px;
    animation: entSpin 0.7s linear infinite;
}

@keyframes entSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Forgot Password Link */
.ent-forgot-link {
    font-size: 13px;
    color: #555;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.15s;
}
.ent-forgot-link:hover {
    color: #000;
    text-decoration: underline;
}

/* Register Link */
.ent-register-link {
    text-align: center;
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}
.ent-link {
    color: #000;
    font-weight: 600;
    text-decoration: none;
}
.ent-link:hover {
    text-decoration: underline;
}
</style>
