<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: '' },
    error: { type: String, default: '' },
});
</script>

<template>
    <GuestLayout>
        <Head title="Password Help" />

        <div class="ent-header">
            <div class="ent-kicker">Account recovery</div>
            <h1 class="ent-title">Need help signing in?</h1>
            <p class="ent-subtitle">Password reset for this system is handled by an administrator using your employee ID.</p>
        </div>

        <div v-if="status" class="ent-status">{{ status }}</div>
        <div v-if="error" class="ent-error-banner">{{ error }}</div>

        <div class="ent-form">
            <div class="help-card">
                <div class="help-card__title">What to prepare</div>
                <div class="help-card__value">Your employee ID</div>
                <p class="help-card__text">Give your employee ID to an admin so they can verify your account and reset the password for you.</p>
            </div>

            <div class="ent-step-grid">
                <div class="ent-step">
                    <div class="ent-step__index">1</div>
                    <div>
                        <div class="ent-step__title">Contact an admin</div>
                        <p class="ent-step__text">Reach out to your QC lab administrator or system owner.</p>
                    </div>
                </div>
                <div class="ent-step">
                    <div class="ent-step__index">2</div>
                    <div>
                        <div class="ent-step__title">Confirm employee ID</div>
                        <p class="ent-step__text">The admin will confirm the employee ID tied to your account before resetting.</p>
                    </div>
                </div>
                <div class="ent-step">
                    <div class="ent-step__index">3</div>
                    <div>
                        <div class="ent-step__title">Sign in with the new password</div>
                        <p class="ent-step__text">Return to the login page once the admin completes the reset.</p>
                    </div>
                </div>
            </div>

            <div class="ent-actions">
                <Link :href="route('login')" class="ent-secondary-btn">Back to Sign In</Link>
                <div class="ent-inline-note">If you do not know your employee ID, ask your supervisor or QC admin.</div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.ent-header { text-align: center; margin-bottom: 20px; }
.ent-kicker { display: inline-flex; padding: 6px 12px; border-radius: 9999px; border: 1px solid rgba(251,146,60,0.18); background: rgba(251,146,60,0.08); color: #fdba74; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; margin-bottom: 10px; }
.ent-title { font-size: 22px; font-weight: 600; color: #f5f5f4; letter-spacing: -0.03em; margin-bottom: 6px; }
.ent-subtitle { font-size: 14px; color: #a8a29e; line-height: 1.5; max-width: 480px; margin: 0 auto; }
.ent-status { padding: 10px 14px; margin-bottom: 14px; font-size: 13px; font-weight: 500; color: #fdba74; background-color: rgba(251,146,60,0.1); border: 1px solid rgba(251,146,60,0.2); border-radius: 12px; text-align: center; }
.ent-error-banner { padding: 10px 14px; margin-bottom: 14px; font-size: 13px; font-weight: 500; color: #fecaca; background-color: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.2); border-radius: 12px; text-align: center; }
.ent-form { display: flex; flex-direction: column; gap: 12px; }
.help-card { border: 1px solid rgba(251,146,60,0.16); border-radius: 16px; background: linear-gradient(160deg, rgba(56,33,17,0.72), rgba(28,18,12,0.78)); padding: 14px; }
.help-card__title { font-size: 12px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(253,186,116,0.82); }
.help-card__value { margin-top: 8px; font-size: 24px; font-weight: 600; letter-spacing: -0.03em; color: #fff7ed; }
.help-card__text { margin-top: 8px; font-size: 13px; line-height: 1.5; color: #d6d3d1; }
.ent-step-grid { display: grid; gap: 10px; }
.ent-step { display: grid; grid-template-columns: 38px minmax(0,1fr); gap: 12px; align-items: start; border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; background: rgba(255,255,255,0.03); padding: 12px; }
.ent-step__index { display: flex; width: 38px; height: 38px; align-items: center; justify-content: center; border-radius: 12px; background: rgba(251,146,60,0.12); color: #fdba74; font-size: 15px; font-weight: 700; }
.ent-step__title { font-size: 14px; font-weight: 600; color: #f5f5f4; }
.ent-step__text { margin-top: 2px; font-size: 13px; line-height: 1.45; color: #a8a29e; }
.ent-actions { display: flex; flex-direction: column; gap: 10px; padding-top: 2px; }
.ent-secondary-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; border-radius: 12px; border: 1px solid rgba(251,146,60,0.18); background: linear-gradient(135deg,#fb923c,#f97316 72%,#c2410c); color: #140d08; font-size: 14px; font-weight: 700; text-decoration: none; box-shadow: 0 12px 24px rgba(249,115,22,0.24); transition: transform 0.15s ease, filter 0.2s ease; }
.ent-secondary-btn:hover { filter: brightness(1.06); transform: translateY(-1px); }
.ent-inline-note { text-align: center; font-size: 12px; line-height: 1.5; color: #a8a29e; }
:global(html[data-theme='light'] .ent-kicker),
:global(html[data-theme='light'] .ent-status),
:global(.theme-guest[data-theme='light'] .ent-kicker),
:global(.theme-guest[data-theme='light'] .ent-status) {
    border-color: rgba(29, 78, 216, 0.18);
    background: rgba(219, 234, 254, 0.9);
    color: #1d4ed8;
}

:global(html[data-theme='light'] .ent-title),
:global(html[data-theme='light'] .help-card__value),
:global(html[data-theme='light'] .ent-step__title),
:global(.theme-guest[data-theme='light'] .ent-title),
:global(.theme-guest[data-theme='light'] .help-card__value),
:global(.theme-guest[data-theme='light'] .ent-step__title) {
    color: #0f172a;
}

:global(html[data-theme='light'] .ent-subtitle),
:global(html[data-theme='light'] .help-card__text),
:global(html[data-theme='light'] .ent-step__text),
:global(html[data-theme='light'] .ent-inline-note),
:global(.theme-guest[data-theme='light'] .ent-subtitle),
:global(.theme-guest[data-theme='light'] .help-card__text),
:global(.theme-guest[data-theme='light'] .ent-step__text),
:global(.theme-guest[data-theme='light'] .ent-inline-note) {
    color: #475569;
}

:global(html[data-theme='light'] .help-card),
:global(.theme-guest[data-theme='light'] .help-card) {
    border-color: rgba(29, 78, 216, 0.16);
    background: linear-gradient(160deg, rgba(239, 246, 255, 0.98), rgba(219, 234, 254, 0.92));
}

:global(html[data-theme='light'] .help-card__title),
:global(.theme-guest[data-theme='light'] .help-card__title) {
    color: #1d4ed8;
}

:global(html[data-theme='light'] .ent-step),
:global(.theme-guest[data-theme='light'] .ent-step) {
    border-color: rgba(15, 23, 42, 0.08);
    background: rgba(255, 255, 255, 0.94);
}

:global(html[data-theme='light'] .ent-step__index),
:global(.theme-guest[data-theme='light'] .ent-step__index) {
    background: rgba(219, 234, 254, 0.92);
    color: #1d4ed8;
}

:global(html[data-theme='light'] .ent-secondary-btn),
:global(.theme-guest[data-theme='light'] .ent-secondary-btn) {
    border-color: rgba(29, 78, 216, 0.14);
    background: linear-gradient(135deg, #1d4ed8, #1e40af 72%, #1e3a8a);
    color: #ffffff;
    box-shadow: 0 12px 24px rgba(29, 78, 216, 0.18);
}
</style>
