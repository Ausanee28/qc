<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pendingJobs: Array, methods: Array, inspectors: Array });

const form = useForm({
    transaction_id: '', method_id: '', internal_id: '', judgement: '',
    start_date: '', start_time: '', end_date: '', end_time: '', remark: '',
});

const submit = () => form.post(route('execute-test.store'), { preserveScroll: true });
</script>

<template>
    <Head title="Execute Test" />
    <AuthenticatedLayout>
        <template #title>Execute Test</template>
        <div class="max-w-3xl mx-auto">
            <div v-if="$page.props.flash?.success" class="mb-6 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 px-5 py-4 rounded-xl text-sm">
                ✅ {{ $page.props.flash.success }}
            </div>

            <div v-if="!pendingJobs.length && !$page.props.flash?.success" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center">
                <p class="text-slate-400 mb-4">No pending jobs available.</p>
                <a :href="route('receive-job.create')" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-xl transition-all">Receive a Job First</a>
            </div>

            <div v-else class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 md:p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Pending Job <span class="text-red-400">*</span></label>
                            <select v-model="form.transaction_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="">-- Select Job --</option>
                                <option v-for="j in pendingJobs" :key="j.transaction_id" :value="j.transaction_id">
                                    #{{ j.transaction_id }} — {{ j.equipment_name }} {{ j.dmc ? '(' + j.dmc + ')' : '' }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Judgement <span class="text-red-400">*</span></label>
                            <select v-model="form.judgement" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="">-- Select --</option>
                                <option value="OK">OK (Pass)</option>
                                <option value="NG">NG (Fail)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Test Method <span class="text-red-400">*</span></label>
                            <select v-model="form.method_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="">-- Select Method --</option>
                                <option v-for="m in methods" :key="m.method_id" :value="m.method_id">{{ m.method_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Inspector <span class="text-red-400">*</span></label>
                            <select v-model="form.internal_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="">-- Select Inspector --</option>
                                <option v-for="u in inspectors" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-slate-800/30 p-4 rounded-xl border border-slate-700/50">
                            <p class="text-xs text-slate-400 font-medium mb-3">Start Time</p>
                            <div class="grid grid-cols-2 gap-3">
                                <input v-model="form.start_date" type="date" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-indigo-500">
                                <input v-model="form.start_time" type="time" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="bg-slate-800/30 p-4 rounded-xl border border-slate-700/50">
                            <p class="text-xs text-slate-400 font-medium mb-3">End Time</p>
                            <div class="grid grid-cols-2 gap-3">
                                <input v-model="form.end_date" type="date" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-indigo-500">
                                <input v-model="form.end_time" type="time" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Remark</label>
                        <textarea v-model="form.remark" rows="3" placeholder="Optional notes..." class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-600 focus:ring-2 focus:ring-indigo-500 transition-all"></textarea>
                    </div>

                    <button type="submit" :disabled="form.processing" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold rounded-xl shadow-lg transition-all">
                        Submit Test Result
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
