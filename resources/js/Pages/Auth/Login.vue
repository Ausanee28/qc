<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    user_name: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign in" />

        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Welcome back</h2>
            <p class="text-sm text-gray-500 mt-1.5">Sign in to your account to continue</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-4 py-3 rounded-lg">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="user_name" class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                <input
                    id="user_name"
                    type="text"
                    v-model="form.user_name"
                    required
                    autofocus
                    placeholder="Enter your username"
                    class="w-full h-11 rounded-lg bg-gray-50 border border-gray-200 text-gray-900 placeholder-gray-400 px-4 text-sm focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all"
                />
                <InputError class="mt-1.5" :message="form.errors.user_name" />
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                <input
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    placeholder="Enter your password"
                    class="w-full h-11 rounded-lg bg-gray-50 border border-gray-200 text-gray-900 placeholder-gray-400 px-4 text-sm focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all"
                />
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.remember" class="w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900" />
                    <span class="text-sm text-gray-500">Remember me</span>
                </label>
            </div>

            <button
                type="submit"
                class="w-full h-11 flex items-center justify-center rounded-lg text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 active:bg-gray-950 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all disabled:opacity-50"
                :disabled="form.processing"
            >
                <span v-if="!form.processing">Sign in</span>
                <svg v-else class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            </button>
        </form>
    </GuestLayout>
</template>
