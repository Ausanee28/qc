<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ jobs: Object, filters: Object });
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const perPage = ref(String(props.filters.per_page ?? 12));
const certificateReloadOnly = ['jobs', 'filters', 'flash'];

const search = () => router.get(route('certificates.index'), {
    date_from: dateFrom.value,
    date_to: dateTo.value,
    per_page: perPage.value,
}, {
    only: certificateReloadOnly,
    preserveState: true,
    preserveScroll: true,
    replace: true,
});

const visitPage = (url) => {
    if (!url) return;

    router.visit(url, {
        only: certificateReloadOnly,
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
const jobRows = computed(() => props.jobs?.data ?? []);
</script>

<template>
    <Head title="Certificates" />
    <AuthenticatedLayout>
        <template #title>Certificates</template>

        <div class="pg-header">
            <div>
                <h1 class="pg-title">Certificates</h1>
                <p class="pg-sub">Generate and download QC test certificates as PDF without loading the whole period at once.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input v-model="dateFrom" type="date" class="form-inp" style="padding:6px 10px">
                <input v-model="dateTo" type="date" class="form-inp" style="padding:6px 10px">
                <select v-model="perPage" class="form-inp" style="padding:6px 10px">
                    <option value="12">12 / page</option>
                    <option value="24">24 / page</option>
                    <option value="48">48 / page</option>
                </select>
                <button @click="search" class="btn" style="padding:6px 14px">Filter</button>
            </div>
        </div>

        <div class="cert-grid">
            <div v-for="j in jobRows" :key="j.transaction_id" class="cert-card">
                <div style="display:flex;justify-content:space-between;margin-bottom:12px">
                    <div>
                        <span style="font-size:10px;font-family:monospace;color:#78716c">#{{ j.transaction_id }}</span>
                        <!-- Status Pills (simplified based on OK/NG count logic) -->
                        <span v-if="j.ng_count > 0" class="pill pill-r" style="font-size:9px;margin-left:6px">NG</span>
                        <span v-else-if="j.ok_count > 0" class="pill pill-g" style="font-size:9px;margin-left:6px">OK</span>
                        <span v-else class="pill pill-y" style="font-size:9px;margin-left:6px">Pending</span>
                        
                        <h3 style="font-size:15px;font-weight:700;margin-top:4px">{{ j.detail }}</h3>
                    </div>
                </div>
                <div style="font-size:12px;color:#a8a29e;display:flex;flex-direction:column;gap:4px;margin-bottom:14px">
                    <div v-if="j.dmc"><strong>DMC:</strong> {{ j.dmc }}</div>
                    <div><strong>Sender:</strong> {{ j.sender }}</div>
                    <div><strong>Tests:</strong> {{ j.ok_count + j.ng_count }} — <span style="color:#fdba74">{{ j.ok_count }} OK</span><span v-if="j.ng_count > 0">, <span style="color:#e7e5e4">{{ j.ng_count }} NG</span></span></div>
                    <div><strong>Date:</strong> {{ formatDate(j.receive_date) }}</div>
                </div>
                <a :href="route('certificates.pdf', j.transaction_id)" target="_blank" class="btn" style="width:100%;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none">
                    Download PDF
                </a>
            </div>
            <div v-if="!jobRows.length" style="grid-column:1/-1;text-align:center;padding:40px;color:#a8a29e;font-size:13px;background:rgba(18,18,18,0.92);border-radius:16px;border:1px solid rgba(255,255,255,0.08)">
                No certificates found for this period.
            </div>
        </div>

        <div v-if="(props.jobs?.links?.length ?? 0) > 3" class="mt-6 flex flex-col gap-3 rounded-2xl border border-white/10 bg-black/20 px-5 py-4 text-sm text-stone-300 sm:flex-row sm:items-center sm:justify-between">
            <div>
                Showing {{ jobRows.length }} certificate job(s) on page {{ props.jobs?.current_page ?? 1 }}
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="(link, index) in (props.jobs?.links ?? [])"
                    :key="index"
                    :disabled="!link.url"
                    @click="visitPage(link.url)"
                    class="rounded-md border px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-40"
                    :class="link.active ? 'border-orange-400 bg-orange-500/20 text-orange-100' : 'border-white/10 text-stone-300 hover:bg-white/5'"
                    v-html="link.label"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.cert-shell__card,
.cert-shell__line,
.cert-shell__button {
    position: relative;
    overflow: hidden;
}

.cert-shell__card {
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-height: 224px;
    padding: 18px;
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(24, 18, 14, 0.92), rgba(12, 12, 12, 0.96));
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.cert-shell__card::after,
.cert-shell__line::after,
.cert-shell__button::after {
    content: '';
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.14), transparent);
    animation: cert-shell-shimmer 1.25s ease-in-out infinite;
}

.cert-shell__line,
.cert-shell__button {
    background: rgba(255, 255, 255, 0.06);
    border-radius: 12px;
}

.cert-shell__line {
    height: 14px;
}

.cert-shell__line-sm {
    width: 72px;
}

.cert-shell__line-md {
    width: 62%;
}

.cert-shell__line-lg {
    width: 84%;
    height: 18px;
}

.cert-shell__stack {
    display: grid;
    gap: 10px;
    margin-top: 2px;
}

.cert-shell__button {
    margin-top: auto;
    width: 100%;
    height: 38px;
    border-radius: 999px;
}

@keyframes cert-shell-shimmer {
    to {
        transform: translateX(100%);
    }
}
</style>
