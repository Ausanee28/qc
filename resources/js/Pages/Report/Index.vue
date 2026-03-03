<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ results: Array, filters: Object });

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const search = () => {
    router.get(route('report.index'), { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true });
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
</script>

<template>
    <Head title="Report" />
    <AuthenticatedLayout>
        <template #title>Report</template>

        <div class="mb-5">
            <h2 class="text-lg font-semibold text-gray-900">Test Report</h2>
            <p class="text-sm text-gray-500 mt-0.5">View detailed test results by date range.</p>
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
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Inspector</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Received</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in results" :key="r.transaction_id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">#{{ r.transaction_id }}</td>
                            <td class="px-4 py-2.5 text-gray-800 font-mono text-xs">{{ r.dmc || '-' }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ r.sender }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ r.equipment_name }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ r.method_name }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ r.inspector }}</td>
                            <td class="px-4 py-2.5 text-gray-500 text-xs">{{ formatDate(r.receive_date) }}</td>
                            <td class="px-4 py-2.5">
                                <span :class="r.judgement === 'OK' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'" class="px-2 py-0.5 rounded-full text-[11px] font-semibold border">
                                    {{ r.judgement }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!results.length">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">No results found for the selected period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
