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
.ent-title { font-size: 22px; font-weight: 600; color: #f5f5f4; letter-spacing: -0.03em; margin-bottom: 6px; }
.ent-subtitle { font-size: 14px; color: #a8a29e; line-height: 1.5; }
.ent-status { padding: 12px 16px; margin-bottom: 20px; font-size: 13px; font-weight: 500; color: #fdba74; background-color: rgba(251,146,60,0.1); border: 1px solid rgba(251,146,60,0.2); border-radius: 12px; text-align: center; }
.ent-form { display: flex; flex-direction: column; gap: 16px; }
.ent-field-group { display: flex; flex-direction: column; }
.ent-label { display: inline-block; font-size: 13px; font-weight: 500; color: #d6d3d1; margin-bottom: 6px; }
.ent-input-wrapper { position: relative; width: 100%; }
.ent-input {
    width: 100%; height: 44px; padding: 0 14px; font-size: 14px; font-family: inherit; color: #f5f5f4;
    background-color: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; transition: all 0.2s ease; box-sizing: border-box;
}
.ent-input::placeholder { color: #8d847d; }
.ent-input:hover { border-color: rgba(251,146,60,0.24); }
.ent-input:focus { outline: none; background-color: rgba(255,255,255,0.06); border-color: #fb923c; box-shadow: 0 0 0 3px rgba(251,146,60,0.14); }
.ent-input.has-error { border-color: #f87171; background-color: rgba(127,29,29,0.16); }
.ent-error { margin-top: 4px; font-size: 12px; color: #fca5a5; }
.ent-submit-btn {
    width: 100%; height: 44px; margin-top: 4px; background: linear-gradient(135deg,#fb923c,#f97316 72%,#c2410c); color: #140d08; border: none; border-radius: 12px;
    font-size: 14px; font-weight: 500; font-family: inherit; cursor: pointer;
    transition: background-color 0.2s, transform 0.1s; box-shadow: 0 12px 24px rgba(249,115,22,0.24);
}
.ent-submit-btn:hover:not(:disabled) { filter: brightness(1.06); }
.ent-submit-btn:active:not(:disabled) { transform: scale(0.98); }
.ent-submit-btn:disabled { background: rgba(255,255,255,0.08); color: #78716c; cursor: not-allowed; }
.ent-loading { display: flex; align-items: center; justify-content: center; gap: 8px; }
.ent-spinner { width: 16px; height: 16px; animation: entSpin 0.7s linear infinite; }
@keyframes entSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.ent-footer-link { text-align: center; font-size: 13px; color: #a8a29e; margin-top: 4px; }
.ent-link { color: #fdba74; font-weight: 600; text-decoration: none; }
.ent-link:hover { text-decoration: underline; }
</style>
