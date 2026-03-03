<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ externals: Array, internals: Array, equipments: Array });
const flash = usePage().props.flash || {};

const form = useForm({
    external_id: '', internal_id: '', equipment_id: '', dmc: '', line: '',
});

const submit = () => {
    form.post(route('receive-job.store'), { onSuccess: () => form.reset() });
};
</script>

<template>
    <Head title="Receive Job" />
    <AuthenticatedLayout>
        <template #title>Receive Job</template>

        <div class="max-w-2xl">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-900">New Job Entry</h2>
                <p class="text-sm text-gray-500 mt-0.5">Register a new equipment for quality inspection.</p>
            </div>

            <div v-if="flash.success" class="mb-4 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-4 py-3 rounded-lg">
                {{ flash.success }}
            </div>

            <form @submit.prevent="submit" class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Sender (External) <span class="text-red-500">*</span></label>
                        <select v-model="form.external_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Select sender</option>
                            <option v-for="e in externals" :key="e.external_id" :value="e.external_id">{{ e.external_name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Receiver (Internal) <span class="text-red-500">*</span></label>
                        <select v-model="form.internal_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Select receiver</option>
                            <option v-for="u in internals" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Equipment <span class="text-red-500">*</span></label>
                    <select v-model="form.equipment_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Select equipment</option>
                        <option v-for="eq in equipments" :key="eq.equipment_id" :value="eq.equipment_id">{{ eq.equipment_name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">DMC</label>
                        <input v-model="form.dmc" type="text" placeholder="Enter DMC code" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Line</label>
                        <input v-model="form.line" type="text" placeholder="Enter line" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-gray-100">
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50">
                        Submit Job
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
