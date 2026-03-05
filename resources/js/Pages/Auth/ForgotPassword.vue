<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: { type: String } });

const form = useForm({ email: '' });

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="ent-header">
            <h1 class="ent-title">Forgot your password?</h1>
            <p class="ent-subtitle">Enter the email address linked to your account and we'll send you a reset link.</p>
        </div>

        <div v-if="status" class="ent-status">{{ status }}</div>

        <form @submit.prevent="submit" class="ent-form">
            <div class="ent-field-group">
                <label for="email" class="ent-label">Email address</label>
                <div class="ent-input-wrapper">
                    <input id="email" type="email" v-model="form.email" required autofocus placeholder="e.g. user@company.com" class="ent-input" :class="{ 'has-error': form.errors.email }" />
                </div>
                <InputError class="ent-error" :message="form.errors.email" />
            </div>

            <button type="submit" class="ent-submit-btn" :disabled="form.processing">
                <span v-if="!form.processing">Send Reset Link</span>
                <span v-else class="ent-loading">
                    <svg class="ent-spinner" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" opacity="0.8"></path></svg>
                    Sending...
                </span>
            </button>

            <div class="ent-footer-link">
                Remember your password? <Link :href="route('login')" class="ent-link">Sign in</Link>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
.ent-header { text-align: center; margin-bottom: 28px; }
.ent-title { font-size: 22px; font-weight: 600; color: #111; letter-spacing: -0.03em; margin-bottom: 6px; }
.ent-subtitle { font-size: 14px; color: #666; line-height: 1.5; }
.ent-status { padding: 12px 16px; margin-bottom: 20px; font-size: 13px; font-weight: 500; color: #065f46; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; text-align: center; }
.ent-form { display: flex; flex-direction: column; gap: 16px; }
.ent-field-group { display: flex; flex-direction: column; }
.ent-label { display: inline-block; font-size: 13px; font-weight: 500; color: #444; margin-bottom: 6px; }
.ent-input-wrapper { position: relative; width: 100%; }
.ent-input {
    width: 100%; height: 44px; padding: 0 14px; font-size: 14px; font-family: inherit; color: #111;
    background-color: #FAFAFA; border: 1px solid #EAEAEA; border-radius: 8px; transition: all 0.2s ease; box-sizing: border-box;
}
.ent-input::placeholder { color: #A1A1AA; }
.ent-input:hover { border-color: #D4D4D8; }
.ent-input:focus { outline: none; background-color: #FFFFFF; border-color: #000; box-shadow: 0 0 0 1px #000; }
.ent-input.has-error { border-color: #EF4444; background-color: #FEF2F2; }
.ent-error { margin-top: 4px; font-size: 12px; color: #EF4444; }
.ent-submit-btn {
    width: 100%; height: 44px; margin-top: 4px; background-color: #000; color: #FFFFFF; border: none; border-radius: 8px;
    font-size: 14px; font-weight: 500; font-family: inherit; cursor: pointer;
    transition: background-color 0.2s, transform 0.1s; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.ent-submit-btn:hover:not(:disabled) { background-color: #1A1A1A; }
.ent-submit-btn:active:not(:disabled) { transform: scale(0.98); }
.ent-submit-btn:disabled { background-color: #EAEAEA; color: #A1A1AA; cursor: not-allowed; }
.ent-loading { display: flex; align-items: center; justify-content: center; gap: 8px; }
.ent-spinner { width: 16px; height: 16px; animation: entSpin 0.7s linear infinite; }
@keyframes entSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.ent-footer-link { text-align: center; font-size: 13px; color: #666; margin-top: 4px; }
.ent-link { color: #000; font-weight: 600; text-decoration: none; }
.ent-link:hover { text-decoration: underline; }
</style>
