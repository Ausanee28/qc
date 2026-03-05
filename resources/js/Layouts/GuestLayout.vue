<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="enterprise-screen">
        <div class="enterprise-container">
            <!-- Brand Header -->
            <div class="brand-header">
                <div class="brand-logo-container">
                    <svg class="brand-logo-mark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
            </div>

            <!-- Card Layout for the Form -->
            <div class="enterprise-card">
                <slot />
            </div>

            <!-- Minimal Footer -->
            <div class="enterprise-footer">
                <p>QC Lab Management System &copy; {{ new Date().getFullYear() }}</p>
                <div class="footer-links">
                    <a href="#">Privacy</a>
                    <span class="dot">&middot;</span>
                    <a href="#">Terms</a>
                    <span class="dot">&middot;</span>
                    <a href="#">Help</a>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* 
    Enterprise-grade minimalist typography and spacing 
    Inspired by top-tier SaaS platforms (Vercel, Stripe, Linear)
*/

.enterprise-screen {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background-color: #FAFAFA; /* Soft off-white to contrast the pure white card */
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.enterprise-container {
    width: 100%;
    max-width: 420px;
    padding: 0 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Brand Header */
.brand-header {
    margin-bottom: 32px;
    animation: fadeDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.brand-logo-container {
    width: 48px;
    height: 48px;
    background: #000;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.brand-logo-mark {
    width: 24px;
    height: 24px;
    color: #FFF;
}

/* The Authentication Card */
.enterprise-card {
    width: 100%;
    background: #FFFFFF;
    border: 1px solid #EAEAEA;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.03), 0 2px 6px rgba(0, 0, 0, 0.02);
    animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both;
}

/* Footer */
.enterprise-footer {
    margin-top: 32px;
    text-align: center;
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both;
}

.enterprise-footer p {
    font-size: 13px;
    color: #888;
    margin-bottom: 8px;
    font-weight: 400;
}

.footer-links {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    font-size: 13px;
}

.footer-links a {
    color: #666;
    text-decoration: none;
    transition: color 0.15s ease;
}

.footer-links a:hover {
    color: #000;
}

.footer-links .dot {
    color: #CCC;
}

/* Smooth Entrance Animations */
@keyframes fadeDown {
    from { opacity: 0; transform: translateY(-12px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Mobile Adjustments */
@media (max-width: 480px) {
    .enterprise-card {
        padding: 32px 24px;
        border-radius: 12px;
    }
}
</style>
