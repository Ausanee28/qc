<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ jobs: Array, filters: Object });
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const filter = () => router.get(route('certificates.index'), { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true });

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';

const statusClass = (job) => {
    if (job.test_count > 0 && job.ng_count == 0) return { class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', label: 'OK' };
    if (job.ng_count > 0) return { class: 'bg-red-500/10 text-red-400 border-red-500/20', label: 'NG' };
    return { class: 'bg-amber-500/10 text-amber-400 border-amber-500/20', label: 'Pending' };
};
</script>

<template>
    <Head title="Certificates" />
    <AuthenticatedLayout>
        <template #title>Certificates</template>

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

        <p class="text-sm text-slate-500 mb-6">Found <span class="text-white font-semibold">{{ jobs.length }}</span> job(s)</p>

        <div v-if="!jobs.length" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center">
            <p class="text-slate-500">No jobs found for the selected date range.</p>
        </div>

        <!-- Cards -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="job in jobs" :key="job.transaction_id" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 flex flex-col hover:border-indigo-500/30 transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-mono text-slate-500">QC-{{ String(job.transaction_id).padStart(5, '0') }}</span>
                            <span :class="statusClass(job).class" class="text-[10px] font-bold px-2 py-0.5 rounded-full border">{{ statusClass(job).label }}</span>
                        </div>
                        <h3 class="text-base font-bold text-white">{{ job.dmc || 'No DMC' }}</h3>
                    </div>
                </div>
                <div class="space-y-2 mb-4 flex-1">
                    <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Line</span><span class="text-slate-300 font-medium">{{ job.line || '-' }}</span></div>
                    <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Sender</span><span class="text-slate-300 font-medium">{{ job.sender }}</span></div>
                    <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Equipment</span><span class="text-slate-300 font-medium">{{ job.equipment_name }}</span></div>
                    <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Date</span><span class="text-slate-300 font-medium">{{ formatDate(job.receive_date) }}</span></div>
                    <div class="flex items-center gap-2 text-sm"><span class="text-slate-500 w-20">Tests</span><span class="text-slate-300 font-medium">{{ job.test_count }} test(s) — <span class="text-emerald-400">{{ job.ok_count }} OK</span><template v-if="job.ng_count > 0">, <span class="text-red-400">{{ job.ng_count }} NG</span></template></span></div>
                </div>
                <a :href="route('certificates.pdf', job.transaction_id)" target="_blank" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 text-white text-sm font-bold rounded-xl transition-all">
                    📄 Download PDF Certificate
                </a>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
