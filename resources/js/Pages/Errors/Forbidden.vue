<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useTheme } from '@/composables/useTheme';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const { isLightTheme } = useTheme();
const canGoDashboard = computed(() => Boolean(page.props.auth?.user));
const cardTextClass = computed(() => (isLightTheme.value ? 'text-slate-900' : 'text-stone-100'));
const mutedTextClass = computed(() => (isLightTheme.value ? 'text-slate-600' : 'text-stone-400'));
const actionClass = computed(() => (
    isLightTheme.value
        ? 'bg-slate-900 text-white hover:bg-slate-700'
        : 'bg-orange-400 text-stone-950 hover:bg-orange-300'
));
</script>

<template>
    <Head title="Forbidden" />

    <AuthenticatedLayout>
        <template #title>Forbidden</template>

        <section class="flex min-h-[54vh] items-center justify-center px-4 py-10">
            <div class="w-full max-w-lg text-center">
                <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-full border border-amber-300/45 bg-amber-100 text-amber-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">403</p>
                <h1 class="mt-2 text-2xl font-bold" :class="cardTextClass">You do not have access to this page</h1>
                <p class="mt-3 text-sm leading-6" :class="mutedTextClass">
                    This menu is reserved for admin accounts. Your current account is active, but it does not include admin permission.
                </p>
                <div class="mt-6 flex justify-center">
                    <Link
                        v-if="canGoDashboard"
                        :href="route('dashboard')"
                        class="inline-flex h-10 items-center justify-center rounded-lg px-4 text-sm font-semibold transition"
                        :class="actionClass"
                    >
                        Back to Dashboard
                    </Link>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
