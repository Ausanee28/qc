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

        <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Test Report</h2>
                        <p class="text-sm text-gray-500">Detailed test results filtered by date</p>
                    </div>
                </div>
            </div>
            <!-- Filters -->
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

        <!-- Stats bar -->
        <div v-if="results.length" class="flex gap-3 mb-4">
            <div class="bg-white border border-gray-200/80 rounded-xl px-4 py-2 flex items-center gap-2">
                <span class="text-xs text-gray-400">Total</span>
                <span class="text-sm font-bold text-gray-900">{{ results.length }}</span>
            </div>
            <div class="bg-emerald-50 border border-emerald-200/60 rounded-xl px-4 py-2 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                <span class="text-sm font-bold text-emerald-700">{{ okCount }} OK</span>
            </div>
            <div class="bg-red-50 border border-red-200/60 rounded-xl px-4 py-2 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-red-500"></div>
                <span class="text-sm font-bold text-red-700">{{ ngCount }} NG</span>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">DMC</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Sender</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Equipment</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Method</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Inspector</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Received</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in results" :key="r.transaction_id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3 text-gray-400 font-mono text-xs">#{{ r.transaction_id }}</td>
                            <td class="px-4 py-3 text-gray-800 font-mono text-xs">{{ r.dmc || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 text-sm">{{ r.sender }}</td>
                            <td class="px-4 py-3 text-gray-700 text-sm">{{ r.equipment_name }}</td>
                            <td class="px-4 py-3 text-gray-700 text-sm">{{ r.method_name }}</td>
                            <td class="px-4 py-3 text-gray-700 text-sm">{{ r.inspector }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ formatDate(r.receive_date) }}</td>
                            <td class="px-4 py-3">
                                <span :class="r.judgement === 'OK' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold border">
                                    <div :class="r.judgement === 'OK' ? 'bg-emerald-500' : 'bg-red-500'" class="w-1.5 h-1.5 rounded-full"></div>
                                    {{ r.judgement }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!results.length" class="px-6 py-12 text-center">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <p class="text-sm text-gray-400">No results found. Try adjusting the date range.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
