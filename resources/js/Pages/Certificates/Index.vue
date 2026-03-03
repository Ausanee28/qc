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

        <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">QC Certificates</h2>
                    <p class="text-sm text-gray-500">Download inspection reports as PDF</p>
                </div>
            </div>
            <div class="flex items-end gap-2">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">From</label>
                    <input v-model="dateFrom" type="date" class="h-10 rounded-xl bg-white border border-gray-200 text-sm px-3 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all" />
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">To</label>
                    <input v-model="dateTo" type="date" class="h-10 rounded-xl bg-white border border-gray-200 text-sm px-3 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all" />
                </div>
                <button @click="search" class="h-10 px-5 rounded-xl text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 transition-all">Search</button>
            </div>
        </div>

        <div v-if="jobs.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="j in jobs" :key="j.transaction_id" class="bg-white border border-gray-200/80 rounded-2xl p-5 hover:shadow-md hover:border-gray-300 transition-all group">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-400 font-mono">#{{ j.transaction_id }}</p>
                        <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ j.equipment_name }}</p>
                    </div>
                    <div class="flex gap-1">
                        <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ j.ok_count }} OK</span>
                        <span class="text-[10px] font-semibold text-red-700 bg-red-50 px-1.5 py-0.5 rounded border border-red-200">{{ j.ng_count }} NG</span>
                    </div>
                </div>
                <div class="space-y-1.5 mb-4 text-xs text-gray-500">
                    <p>{{ j.sender }}</p>
                    <p>{{ formatDate(j.receive_date) }}</p>
                    <p v-if="j.dmc" class="font-mono">{{ j.dmc }}</p>
                </div>
                <a :href="route('certificates.pdf', j.transaction_id)" class="w-full flex items-center justify-center gap-2 h-9 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-900 hover:text-white border border-gray-200 hover:border-gray-900 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Download PDF
                </a>
            </div>
        </div>

        <div v-else class="bg-white border border-gray-200/80 rounded-2xl p-12 text-center">
            <p class="text-sm text-gray-400">No certificates found for this period.</p>
        </div>
    </AuthenticatedLayout>
</template>
