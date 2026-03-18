<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ jobs: Array, filters: Object });
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const search = () => router.get(route('certificates.index'), { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true });
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
</script>

<template>
    <Head title="Certificates" />
    <AuthenticatedLayout>
        <template #title>Certificates</template>

        <div class="pg-header">
            <div>
                <h1 class="pg-title">Certificates</h1>
                <p class="pg-sub">Generate and download QC test certificates as PDF</p>
            </div>
            <div style="display:flex;gap:8px">
                <input v-model="dateFrom" type="date" class="form-inp" style="padding:6px 10px">
                <input v-model="dateTo" type="date" class="form-inp" style="padding:6px 10px">
                <button @click="search" class="btn" style="padding:6px 14px">Filter</button>
            </div>
        </div>

        <div class="cert-grid">
            <div v-for="j in jobs" :key="j.transaction_id" class="cert-card">
                <div style="display:flex;justify-content:space-between;margin-bottom:12px">
                    <div>
                        <span style="font-size:10px;font-family:monospace;color:#9CA3AF">#{{ j.transaction_id }}</span>
                        <!-- Status Pills (simplified based on OK/NG count logic) -->
                        <span v-if="j.ng_count > 0" class="pill pill-r" style="font-size:9px;margin-left:6px">NG</span>
                        <span v-else-if="j.ok_count > 0" class="pill pill-g" style="font-size:9px;margin-left:6px">OK</span>
                        <span v-else class="pill pill-y" style="font-size:9px;margin-left:6px">Pending</span>
                        
                        <h3 style="font-size:15px;font-weight:700;margin-top:4px">{{ j.detail }}</h3>
                    </div>
                </div>
                <div style="font-size:12px;color:#6B7280;display:flex;flex-direction:column;gap:4px;margin-bottom:14px">
                    <div v-if="j.dmc"><strong>DMC:</strong> {{ j.dmc }}</div>
                    <div><strong>Sender:</strong> {{ j.sender }}</div>
                    <div><strong>Tests:</strong> {{ j.ok_count + j.ng_count }} — <span style="color:#059669">{{ j.ok_count }} OK</span><span v-if="j.ng_count > 0">, <span style="color:#DC2626">{{ j.ng_count }} NG</span></span></div>
                    <div><strong>Date:</strong> {{ formatDate(j.receive_date) }}</div>
                </div>
                <a :href="route('certificates.pdf', j.transaction_id)" target="_blank" class="btn" style="width:100%;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none">
                    📄 Download PDF
                </a>
            </div>
            <div v-if="!jobs.length" style="grid-column:1/-1;text-align:center;padding:40px;color:#9CA3AF;font-size:13px;background:#fff;border-radius:10px;border:1px solid #E5E7EB">
                No certificates found for this period.
            </div>
        </div>
    </AuthenticatedLayout>
</template>
