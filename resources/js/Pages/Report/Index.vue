<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ results: Array, filters: Object });
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const filter = () => router.get(route('report.index'), { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true });

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
</script>

<template>
    <Head title="Report" />
    <AuthenticatedLayout>
        <template #title>Report</template>

        <!-- Filter -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 mb-6">
            <form @submit.prevent="filter" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">From</label>
                    <input v-model="dateFrom" type="date" class="px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">To</label>
                    <input v-model="dateTo" type="date" class="px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-all">Filter</button>
            </form>
        </div>

        <p class="text-sm text-slate-500 mb-4">Showing <span class="text-white font-semibold">{{ results.length }}</span> result(s)</p>

        <!-- Table -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-800">
                            <th class="px-4 py-3">Line</th>
                            <th class="px-4 py-3">Equipment</th>
                            <th class="px-4 py-3">DMC</th>
                            <th class="px-4 py-3">Sender</th>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Inspector</th>
                            <th class="px-4 py-3">Start</th>
                            <th class="px-4 py-3">End</th>
                            <th class="px-4 py-3">Result</th>
                            <th class="px-4 py-3">Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!results.length">
                            <td colspan="10" class="text-center py-10 text-slate-500">No test results found for the selected date range.</td>
                        </tr>
                        <tr v-for="row in results" :key="row.transaction_id" class="border-b border-slate-800/60 hover:bg-slate-800/20 transition">
                            <td class="px-4 py-3 text-white">{{ row.line || '-' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ row.equipment_name }}</td>
                            <td class="px-4 py-3 text-slate-300 font-mono text-xs">{{ row.dmc || '-' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ row.sender }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ row.method_name }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ row.inspector }}</td>
                            <td class="px-4 py-3 text-slate-400 text-xs">{{ formatDate(row.start_time) }}</td>
                            <td class="px-4 py-3 text-slate-400 text-xs">{{ formatDate(row.end_time) }}</td>
                            <td class="px-4 py-3">
                                <span :class="row.judgement === 'OK' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'" class="px-2.5 py-1 rounded-full text-xs font-bold border">
                                    {{ row.judgement }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-400 text-xs max-w-[120px] truncate">{{ row.remark || '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
