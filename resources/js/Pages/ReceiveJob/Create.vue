<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ externals: Array, internals: Array, equipments: Array });

const form = useForm({
    external_id: '', internal_id: '', equipment_id: '', dmc: '', line: '',
});

const lines = Array.from({ length: 10 }, (_, i) => `Line ${i + 1}`);

const submit = () => form.post(route('receive-job.store'), { preserveScroll: true });
</script>

<template>
    <Head title="Receive Job" />
    <AuthenticatedLayout>
        <template #title>Receive Job</template>
        <div class="max-w-3xl mx-auto">
            <div v-if="$page.props.flash?.success" class="mb-6 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 px-5 py-4 rounded-xl text-sm">
                ✅ {{ $page.props.flash.success }}
            </div>

            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 md:p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Sender (External) <span class="text-red-400">*</span></label>
                            <select v-model="form.external_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="">-- Select Sender --</option>
                                <option v-for="ext in externals" :key="ext.external_id" :value="ext.external_id">{{ ext.external_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Receiver (Internal) <span class="text-red-400">*</span></label>
                            <select v-model="form.internal_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="">-- Select Receiver --</option>
                                <option v-for="u in internals" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Equipment <span class="text-red-400">*</span></label>
                        <select v-model="form.equipment_id" required class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                            <option value="">-- Select Equipment --</option>
                            <option v-for="eq in equipments" :key="eq.equipment_id" :value="eq.equipment_id">{{ eq.equipment_name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">DMC Code</label>
                            <input v-model="form.dmc" type="text" placeholder="Enter DMC code" class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-600 focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Line</label>
                            <select v-model="form.line" class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="">-- Select Line --</option>
                                <option v-for="l in lines" :key="l" :value="l">{{ l }}</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" :disabled="form.processing" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold rounded-xl shadow-lg transition-all">
                        Receive Job
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
