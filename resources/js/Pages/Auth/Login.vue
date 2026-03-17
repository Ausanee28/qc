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

        <div class="rounded-[24px] p-4 sm:p-5">
            <div class="mb-8">
                <div class="inline-flex rounded-full border border-zinc-300/90 bg-zinc-100/85 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-800">
                    Factory QC
                </div>
                <h1 class="mt-4 font-['Sora'] text-4xl font-semibold leading-[0.95] tracking-[-0.04em] text-slate-900 sm:text-5xl">
                    Welcome Back
                </h1>
                <p class="mt-4 max-w-sm text-sm leading-6 text-slate-600">
                    Sign in to continue with job intake, test execution, and quality reporting.
                </p>
            </div>

            <div v-if="status" class="mb-5 rounded-2xl border border-zinc-300 bg-zinc-100 px-4 py-3 text-sm font-medium text-zinc-700">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="user_name" class="mb-2 block text-sm font-semibold text-zinc-700">Username</label>
                    <input
                        id="user_name"
                        v-model="form.user_name"
                        type="text"
                        required
                        autofocus
                        placeholder="e.g. admin"
                        class="h-12 w-full rounded-2xl border border-zinc-300/90 bg-white/95 px-4 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-4 focus:ring-zinc-200"
                        :class="{ 'border-rose-300 bg-rose-50 focus:border-rose-400 focus:ring-rose-100': form.errors.user_name }"
                    />
                    <InputError class="mt-2 text-sm" :message="form.errors.user_name" />
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-zinc-700">Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            placeholder="Enter your password"
                            class="h-12 w-full rounded-2xl border border-zinc-300/90 bg-white/95 px-4 pr-12 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-4 focus:ring-zinc-200"
                            :class="{ 'border-rose-300 bg-rose-50 focus:border-rose-400 focus:ring-rose-100': form.errors.password }"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-xl text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700"
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

                <div class="flex flex-col gap-3 text-sm text-zinc-600 sm:flex-row sm:items-center sm:justify-between">
                    <label class="flex items-center gap-3">
                        <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-300" />
                        <span>Remember me</span>
                    </label>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="font-semibold text-zinc-700 transition hover:text-zinc-900">
                        Forgot password?
                    </Link>
                </div>

                <button
                    type="submit"
                    class="flex h-12 w-full items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#09090b,#27272a,#09090b)] px-4 text-sm font-semibold uppercase tracking-[0.18em] text-white shadow-[0_16px_30px_rgba(0,0,0,0.25)] transition hover:translate-y-[-1px] hover:brightness-110 hover:shadow-[0_20px_34px_rgba(0,0,0,0.3)] disabled:cursor-not-allowed disabled:opacity-60"
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

                <div class="pt-1 text-center text-sm text-zinc-600">
                    New here?
                    <Link :href="route('register')" class="font-semibold text-zinc-900 transition hover:text-black">
                        Create account
                    </Link>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
