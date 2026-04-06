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

        <div class="login-root rounded-[24px] p-4 sm:p-5">
            <div class="login-block login-block--hero mb-8">
                <div class="login-kicker inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">
                    Factory QC
                </div>
                <h1 class="login-title mt-4 font-['Sora'] text-4xl font-semibold leading-[0.95] tracking-[-0.04em] text-stone-50 sm:text-5xl">
                    Welcome Back
                </h1>
                <p class="mt-4 max-w-sm text-sm leading-6 text-stone-400">
                    Sign in to continue with job intake, test execution, and quality reporting.
                </p>
            </div>

            <div v-if="status" class="login-block login-block--status login-status mb-5 rounded-2xl px-4 py-3 text-sm font-medium">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="login-block login-block--field">
                    <label for="user_name" class="mb-2 block text-sm font-semibold text-stone-300">Username</label>
                        <input
                            id="user_name"
                            v-model="form.user_name"
                            type="text"
                            required
                            autofocus
                            class="h-12 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-stone-100 outline-none transition focus:border-blue-500/40 focus:ring-4 focus:ring-blue-300/20"
                            :class="{ 'border-rose-300 bg-rose-50 focus:border-rose-400 focus:ring-rose-100': form.errors.user_name }"
                        />
                    <InputError class="mt-2 text-sm" :message="form.errors.user_name" />
                </div>

                <div class="login-block login-block--field">
                    <label for="password" class="mb-2 block text-sm font-semibold text-stone-300">Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            class="password-input h-12 w-full rounded-2xl border border-white/10 bg-white/5 px-4 pr-12 text-sm text-stone-100 outline-none transition focus:border-blue-500/40 focus:ring-4 focus:ring-blue-300/20"
                            :class="{ 'border-rose-300 bg-rose-50 focus:border-rose-400 focus:ring-rose-100': form.errors.password }"
                        />
                        <button
                            type="button"
                            class="login-eye absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-xl text-stone-500 transition"
                            @click="showPassword = !showPassword"
                            aria-label="Toggle password visibility"
                        >
                            <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <InputError class="mt-2 text-sm" :message="form.errors.password" />
                </div>

                <div class="login-block login-block--meta flex flex-col gap-3 text-sm text-stone-400 sm:flex-row sm:items-center sm:justify-between">
                    <label class="flex items-center gap-3">
                        <input v-model="form.remember" type="checkbox" class="login-remember" />
                        <span>Remember me</span>
                    </label>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="login-link font-semibold transition">
                        Need password help?
                    </Link>
                </div>

                <button
                    type="submit"
                    class="login-submit flex h-12 w-full items-center justify-center rounded-2xl px-4 text-sm font-semibold uppercase tracking-[0.18em] transition hover:translate-y-[-1px] hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="form.processing"
                >
                    <span v-if="!form.processing">Sign In</span>
                    <span v-else class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                            <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" opacity="0.8"></path>
                        </svg>
                        Signing In
                    </span>
                </button>

                <div class="login-block login-block--footer pt-1 text-center text-sm text-stone-400">
                    New here?
                    <Link :href="route('register')" class="login-link font-semibold transition">
                        Create account
                    </Link>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>

<style scoped>
.login-root {
    position: relative;
}

.login-block {
    opacity: 0;
    animation: login-rise 620ms cubic-bezier(0.2, 0.7, 0.2, 1) both;
}

.login-block--hero { animation-delay: 60ms; }
.login-block--status { animation-delay: 120ms; }
.login-block--field:nth-of-type(1) { animation-delay: 150ms; }
.login-block--field:nth-of-type(2) { animation-delay: 210ms; }
.login-block--meta { animation-delay: 270ms; }
.login-submit { animation: login-rise 620ms cubic-bezier(0.2, 0.7, 0.2, 1) both; animation-delay: 330ms; }
.login-block--footer { animation-delay: 390ms; }

.login-kicker {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(251, 146, 60, 0.2);
    background: rgba(251, 146, 60, 0.1);
    color: #fdba74;
}

.login-kicker::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.22), transparent);
    transform: translateX(-130%);
    animation: login-sheen 3.4s ease-in-out infinite;
}

.login-title {
    text-shadow: 0 10px 34px rgba(0, 0, 0, 0.32);
}

.login-status {
    border: 1px solid rgba(251, 146, 60, 0.2);
    background: rgba(251, 146, 60, 0.1);
    color: #fdba74;
}

.login-eye:hover {
    background: rgba(251, 146, 60, 0.1);
    color: #fdba74;
}

.login-link {
    color: #f5f5f4;
}

.login-link:hover {
    color: #fdba74;
}

.login-remember {
    appearance: none;
    -webkit-appearance: none;
    display: grid;
    place-content: center;
    width: 1rem;
    height: 1rem;
    border-radius: 0.35rem;
    border: 1px solid rgba(148, 163, 184, 0.65);
    background-color: rgba(255, 255, 255, 0.96);
    cursor: pointer;
    transition: border-color 160ms ease, background-color 160ms ease, box-shadow 160ms ease;
}

.login-remember::before {
    content: '';
    width: 0.44rem;
    height: 0.44rem;
    transform: scale(0);
    transition: transform 120ms ease-in-out;
    clip-path: polygon(14% 53%, 0 67%, 39% 100%, 100% 31%, 86% 17%, 39% 70%);
    background: #ffffff;
}

.login-remember:checked {
    border-color: #1d4ed8;
    background-color: #1d4ed8;
}

.login-remember:checked::before {
    transform: scale(1);
}

.login-remember:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.2);
}

.password-input::-ms-reveal,
.password-input::-ms-clear {
    display: none;
}

.password-input::-webkit-credentials-auto-fill-button,
.password-input::-webkit-textfield-decoration-container {
    display: none !important;
}

.password-input,
#user_name {
    transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
}

.password-input:hover,
#user_name:hover {
    transform: translateY(-1px);
}

.login-submit {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #fb923c, #f97316 72%, #c2410c);
    color: #140d08;
    box-shadow: 0 16px 30px rgba(249, 115, 22, 0.24);
}

.login-submit::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(100deg, transparent 18%, rgba(255, 255, 255, 0.22) 50%, transparent 82%);
    transform: translateX(-140%);
    transition: transform 420ms ease;
}

.login-submit:hover::after {
    transform: translateX(140%);
}

:global(html[data-theme='light'] .login-kicker),
:global(.theme-guest[data-theme='light'] .login-kicker) {
    border-color: rgba(29, 78, 216, 0.22) !important;
    background: rgba(219, 234, 254, 0.92) !important;
    color: #1d4ed8 !important;
}

:global(html[data-theme='light'] .login-title),
:global(.theme-guest[data-theme='light'] .login-title) {
    text-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
}

:global(html[data-theme='light'] .login-status),
:global(.theme-guest[data-theme='light'] .login-status) {
    border-color: rgba(29, 78, 216, 0.16);
    background: rgba(239, 246, 255, 0.96);
    color: #1d4ed8;
}

:global(html[data-theme='light'] .login-eye:hover),
:global(.theme-guest[data-theme='light'] .login-eye:hover) {
    background: rgba(219, 234, 254, 0.92);
    color: #1d4ed8;
}

:global(html[data-theme='light'] .login-link),
:global(.theme-guest[data-theme='light'] .login-link) {
    color: #1e40af;
}

:global(html[data-theme='light'] .login-link:hover),
:global(.theme-guest[data-theme='light'] .login-link:hover) {
    color: #1d4ed8;
}

:global(html[data-theme='light'] .login-submit),
:global(.theme-guest[data-theme='light'] .login-submit) {
    background: linear-gradient(135deg, #1d4ed8, #1e40af) !important;
    color: #ffffff !important;
    box-shadow: 0 16px 30px rgba(29, 78, 216, 0.18) !important;
}

:global(html[data-theme='dark'] .login-remember),
:global(.theme-guest[data-theme='dark'] .login-remember) {
    border-color: rgba(251, 146, 60, 0.45);
    background-color: rgba(255, 255, 255, 0.08);
}

:global(html[data-theme='dark'] .login-remember:checked),
:global(.theme-guest[data-theme='dark'] .login-remember:checked) {
    border-color: #f97316;
    background-color: #f97316;
}

:global(html[data-theme='dark'] .login-remember:focus-visible),
:global(.theme-guest[data-theme='dark'] .login-remember:focus-visible) {
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
}

@keyframes login-rise {
    from { opacity: 0; transform: translateY(14px) scale(0.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes login-sheen {
    0%, 100% { transform: translateX(-130%); }
    45%, 60% { transform: translateX(130%); }
}

@media (prefers-reduced-motion: reduce) {
    .login-block,
    .login-submit,
    .login-kicker::after {
        animation: none !important;
        transition: none !important;
    }

    .login-submit::after {
        display: none;
    }
}
</style>
