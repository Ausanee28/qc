<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="verify-copy mb-4 text-sm">
            Thanks for signing up! Before getting started, could you verify your
            email address by clicking on the link we just emailed to you? If you
            didn't receive the email, we will gladly send you another.
        </div>

        <div
            class="verify-status mb-4 rounded-2xl px-4 py-3 text-sm font-medium"
            v-if="verificationLinkSent"
        >
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Resend Verification Email
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="verify-link rounded-md text-sm underline focus:outline-none focus:ring-2 focus:ring-offset-2"
                    >Log Out</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
.verify-copy {
    color: #a8a29e;
}

.verify-status {
    border: 1px solid rgba(251, 146, 60, 0.2);
    background: rgba(251, 146, 60, 0.1);
    color: #fdba74;
}

.verify-link {
    color: #d6d3d1;
}

.verify-link:hover {
    color: #fdba74;
}

.verify-link:focus {
    box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.14);
}

:global(html[data-theme='light']) .verify-copy {
    color: #475569;
}

:global(html[data-theme='light']) .verify-status {
    border-color: rgba(29, 78, 216, 0.18);
    background: rgba(219, 234, 254, 0.9);
    color: #1d4ed8;
}

:global(html[data-theme='light']) .verify-link {
    color: #1e40af;
}

:global(html[data-theme='light']) .verify-link:hover {
    color: #1d4ed8;
}

:global(html[data-theme='light']) .verify-link:focus {
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
}
</style>
