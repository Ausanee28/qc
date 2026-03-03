<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ jobs: Array, filters: Object });

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const search = () => {
    router.get(route('certificates.index'), { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true });
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
</script>

<template>
    <Head title="Certificates" />
    <AuthenticatedLayout>
        <template #title>Certificates</template>

        <div class="mb-5">
            <h2 class="text-lg font-semibold text-gray-900">QC Certificates</h2>
            <p class="text-sm text-gray-500 mt-0.5">Download inspection reports as PDF.</p>
        </div>

        <!-- Filters -->
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input v-model="dateFrom" type="date" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input v-model="dateTo" type="date" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
            </div>
            <button @click="search" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">Search</button>
        </div>

        <!-- Table -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">DMC</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sender</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Equipment</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Received</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tests</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">OK/NG</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="j in jobs" :key="j.transaction_id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">#{{ j.transaction_id }}</td>
                            <td class="px-4 py-2.5 text-gray-800 font-mono text-xs">{{ j.dmc || '-' }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ j.sender }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ j.equipment_name }}</td>
                            <td class="px-4 py-2.5 text-gray-500 text-xs">{{ formatDate(j.receive_date) }}</td>
                            <td class="px-4 py-2.5 text-gray-700 font-mono text-xs">{{ j.test_count }}</td>
                            <td class="px-4 py-2.5">
                                <span class="text-emerald-600 font-semibold text-xs">{{ j.ok_count }}</span>
                                <span class="text-gray-300 mx-1">/</span>
                                <span class="text-red-600 font-semibold text-xs">{{ j.ng_count }}</span>
                            </td>
                            <td class="px-4 py-2.5">
                                <a :href="route('certificates.download', j.transaction_id)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    PDF
                                </a>
                            </td>
                        </tr>
                        <tr v-if="!jobs.length">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">No certificates found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
