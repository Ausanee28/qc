<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    user_name: '',
    name: '',
    employee_id: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Create Account" />

        <div class="ent-header">
            <h1 class="ent-title">Create your account</h1>
            <p class="ent-subtitle">Join the QC Lab system to start inspecting.</p>
        </div>

        <form @submit.prevent="submit" class="ent-form">
            <div class="ent-grid">
                <div class="ent-field-group">
                    <label for="user_name" class="ent-label">Username *</label>
                    <div class="ent-input-wrapper">
                        <input id="user_name" type="text" v-model="form.user_name" required autofocus placeholder="e.g. inspector1" class="ent-input" :class="{ 'has-error': form.errors.user_name }" />
                    </div>
                    <InputError class="ent-error" :message="form.errors.user_name" />
                </div>
                <div class="ent-field-group">
                    <label for="employee_id" class="ent-label">Employee ID *</label>
                    <div class="ent-input-wrapper">
                        <input id="employee_id" type="text" v-model="form.employee_id" required placeholder="e.g. EMP004" class="ent-input" :class="{ 'has-error': form.errors.employee_id }" />
                    </div>
                    <InputError class="ent-error" :message="form.errors.employee_id" />
                </div>
            </div>

            <div class="ent-field-group">
                <label for="name" class="ent-label">Full Name *</label>
                <div class="ent-input-wrapper">
                    <input id="name" type="text" v-model="form.name" required placeholder="e.g. Somchai Tester" class="ent-input" :class="{ 'has-error': form.errors.name }" />
                </div>
                <InputError class="ent-error" :message="form.errors.name" />
            </div>

            <div class="ent-field-group">
                <label for="email" class="ent-label">Email *</label>
                <div class="ent-input-wrapper">
                    <input id="email" type="email" v-model="form.email" required placeholder="e.g. user@company.com" class="ent-input" :class="{ 'has-error': form.errors.email }" />
                </div>
                <InputError class="ent-error" :message="form.errors.email" />
            </div>

            <div class="ent-grid">
                <div class="ent-field-group">
                    <label for="password" class="ent-label">Password *</label>
                    <div class="ent-input-wrapper">
                        <input id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password" required placeholder="••••••••" class="ent-input ent-input-pw" :class="{ 'has-error': form.errors.password }" />
                        <button type="button" class="ent-eye-btn" @click="showPassword = !showPassword" tabindex="-1">
                            <svg v-if="!showPassword" class="ent-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg v-else class="ent-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                        </button>
                    </div>
                    <InputError class="ent-error" :message="form.errors.password" />
                </div>
                <div class="ent-field-group">
                    <label for="password_confirmation" class="ent-label">Confirm Password *</label>
                    <div class="ent-input-wrapper">
                        <input id="password_confirmation" :type="showPassword ? 'text' : 'password'" v-model="form.password_confirmation" required placeholder="••••••••" class="ent-input" :class="{ 'has-error': form.errors.password_confirmation }" />
                    </div>
                    <InputError class="ent-error" :message="form.errors.password_confirmation" />
                </div>
            </div>

            <button type="submit" class="ent-submit-btn" :disabled="form.processing">
                <span v-if="!form.processing">Create Account</span>
                <span v-else class="ent-loading">
                    <svg class="ent-spinner" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" opacity="0.8"></path></svg>
                    Creating...
                </span>
            </button>

            <div class="ent-footer-link">
                Already have an account? <Link :href="route('login')" class="ent-link">Sign in</Link>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
.ent-header { text-align: center; margin-bottom: 28px; }
.ent-title { font-size: 22px; font-weight: 600; color: #f5f5f4; letter-spacing: -0.03em; margin-bottom: 6px; }
.ent-subtitle { font-size: 14px; color: #a8a29e; line-height: 1.5; }
.ent-form { display: flex; flex-direction: column; gap: 16px; }
.ent-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.ent-field-group { display: flex; flex-direction: column; }
.ent-label { display: inline-block; font-size: 13px; font-weight: 500; color: #d6d3d1; margin-bottom: 6px; }
.ent-input-wrapper { position: relative; width: 100%; }
.ent-input {
    width: 100%; height: 42px; padding: 0 14px; font-size: 14px; font-family: inherit; color: #f5f5f4;
    background-color: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; transition: all 0.2s ease; box-sizing: border-box;
    &::-ms-reveal, &::-ms-clear { display: none; }
}
.ent-input::placeholder { color: #8d847d; }
.ent-input:hover { border-color: rgba(251,146,60,0.24); }
.ent-input:focus { outline: none; background-color: rgba(255,255,255,0.06); border-color: #fb923c; box-shadow: 0 0 0 3px rgba(251,146,60,0.14); }
.ent-input.has-error { border-color: #f87171; background-color: rgba(127,29,29,0.16); }
.ent-input.has-error:focus { box-shadow: 0 0 0 3px rgba(248,113,113,0.18); }
.ent-input-pw { padding-right: 44px; }
.ent-eye-btn {
    position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #a8a29e;
    width: 28px; height: 28px; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer;
    transition: color 0.15s, background-color 0.15s;
}
.ent-eye-btn:hover { color: #fdba74; background-color: rgba(251,146,60,0.08); }
.ent-icon { width: 18px; height: 18px; }
.ent-error { margin-top: 4px; font-size: 12px; color: #fca5a5; }
.ent-submit-btn {
    width: 100%; height: 44px; margin-top: 4px; background: linear-gradient(135deg,#fb923c,#f97316 72%,#c2410c); color: #140d08; border: none; border-radius: 12px;
    font-size: 14px; font-weight: 500; font-family: inherit; cursor: pointer;
    transition: background-color 0.2s, transform 0.1s, box-shadow 0.2s; box-shadow: 0 12px 24px rgba(249,115,22,0.24);
}
.ent-submit-btn:hover:not(:disabled) { filter: brightness(1.06); }
.ent-submit-btn:active:not(:disabled) { transform: scale(0.98); }
.ent-submit-btn:disabled { background: rgba(255,255,255,0.08); color: #78716c; cursor: not-allowed; box-shadow: none; }
.ent-loading { display: flex; align-items: center; justify-content: center; gap: 8px; }
.ent-spinner { width: 16px; height: 16px; animation: entSpin 0.7s linear infinite; }
@keyframes entSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.ent-footer-link { text-align: center; font-size: 13px; color: #a8a29e; margin-top: 4px; }
.ent-link { color: #fdba74; font-weight: 600; text-decoration: none; }
.ent-link:hover { text-decoration: underline; }
@media (max-width: 480px) {
    .ent-grid { grid-template-columns: 1fr; }
}
</style>
