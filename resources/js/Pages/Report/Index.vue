<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ results: Array, filters: Object });
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const search = () => router.get(route('report.index'), { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true });

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';

const okCount = computed(() => props.results.filter(r => r.judgement === 'OK').length);
const ngCount = computed(() => props.results.filter(r => r.judgement === 'NG').length);
</script>

<template>
    <Head title="Report" />
    <AuthenticatedLayout>
        <template #title>Report</template>
        
        <div class="pg-header">
                <div>
                    <h1 class="pg-title">Report</h1>
                    <p class="pg-sub">View and export completed test results</p>
                </div>
                <div style="display:flex;gap:8px;align-items:end">
                    <input v-model="dateFrom" type="date" class="form-inp" style="padding:6px 10px">
                    <input v-model="dateTo" type="date" class="form-inp" style="padding:6px 10px">
                    <button @click="search" class="btn" style="padding:6px 14px">Filter</button>
                    <button class="btn-outline" onclick="window.print()">📊 Export CSV</button>
                </div>
            </div>
            
            <div style="font-size:12px;color:#6B7280;margin-bottom:12px;display:flex;align-items:center;gap:12px">
                <div>Showing <strong style="color:#111827">{{ results.length }}</strong> result(s)</div>
                <div v-if="results.length > 0" style="display:flex;gap:8px;font-size:11px;font-weight:600">
                    <div style="background:#ECFDF5;color:#065F46;padding:2px 8px;border-radius:12px;border:1px solid #A7F3D0">{{ okCount }} OK</div>
                    <div style="background:#FEF2F2;color:#991B1B;padding:2px 8px;border-radius:12px;border:1px solid #FECACA">{{ ngCount }} NG</div>
                </div>
            </div>

            <div class="tbl" style="margin-bottom:20px">
                <table v-if="results.length">
                    <thead>
                        <tr>
                            <th>Line</th>
                            <th>Date</th>
                            <th>Sender</th>
                            <th>DMC</th>
                            <th>Detail</th>
                            <th>Process</th>
                            <th>Inspector</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Result</th>
                            <th>Remark</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in results" :key="r.transaction_id">
                            <td>{{ r.line || '—' }}</td>
                            <td style="font-size:11px">{{ formatDate(r.receive_date) }}</td>
                            <td>{{ r.sender }}</td>
                            <td style="font-weight:700">{{ r.dmc || '—' }}</td>
                            <td>{{ r.detail }}</td>
                            <td>{{ r.method_name }}</td>
                            <td>{{ r.inspector }}</td>
                            <td style="font-size:11px">{{ r.start_time ? new Date(r.start_time).toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'}) : '—' }}</td>
                            <td style="font-size:11px">{{ r.end_time ? new Date(r.end_time).toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'}) : '—' }}</td>
                            <td>
                                <span v-if="r.judgement === 'OK'" class="pill pill-g">OK</span>
                                <span v-else-if="r.judgement === 'NG'" class="pill pill-r">NG</span>
                                <span v-else class="pill pill-y">{{ r.judgement }}</span>
                            </td>
                            <td style="font-size:11px;color:#9CA3AF">{{ r.remark || '' }}</td>
                            <td>
                                <a :href="route('certificates.pdf', r.transaction_id)" target="_blank" class="btn-outline" style="padding:2px 6px;font-size:10px;text-decoration:none">
                                    📄
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!results.length" style="padding:40px;text-align:center;color:#9CA3AF;font-size:13px;background:#fff;border-radius:8px">
                    No results found. Try adjusting the date range.
                </div>
            </div>
    </AuthenticatedLayout>
</template>
