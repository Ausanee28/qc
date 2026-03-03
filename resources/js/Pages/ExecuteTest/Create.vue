<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ pendingJobs: Array, methods: Array, inspectors: Array });
const flash = usePage().props.flash || {};

const form = useForm({
    transaction_id: '', method_id: '', internal_id: '',
    start_date: '', start_time: '', end_date: '', end_time: '',
    judgement: '', remark: '',
});

const submit = () => {
    form.post(route('execute-test.store'), { onSuccess: () => form.reset() });
};
</script>

<template>
    <Head title="Execute Test" />
    <AuthenticatedLayout>
        <template #title>Execute Test</template>

        <div class="max-w-2xl">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-900">Record Test Result</h2>
                <p class="text-sm text-gray-500 mt-0.5">Select a pending job and record the test outcome.</p>
            </div>

            <div v-if="flash.success" class="mb-4 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-4 py-3 rounded-lg">
                {{ flash.success }}
            </div>

            <form @submit.prevent="submit" class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">
                <!-- Job & Method -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Pending Job <span class="text-red-500">*</span></label>
                        <select v-model="form.transaction_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Select job</option>
                            <option v-for="j in pendingJobs" :key="j.transaction_id" :value="j.transaction_id">
                                #{{ j.transaction_id }} — {{ j.equipment_name }} {{ j.dmc ? `(${j.dmc})` : '' }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Test Method <span class="text-red-500">*</span></label>
                        <select v-model="form.method_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Select method</option>
                            <option v-for="m in methods" :key="m.method_id" :value="m.method_id">{{ m.method_name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Inspector & Judgement -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Inspector <span class="text-red-500">*</span></label>
                        <select v-model="form.internal_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Select inspector</option>
                            <option v-for="u in inspectors" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Judgement <span class="text-red-500">*</span></label>
                        <div class="flex gap-2 mt-1">
                            <label :class="['flex-1 text-center py-2.5 rounded-lg border-2 cursor-pointer text-sm font-semibold transition-all', form.judgement === 'OK' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-400 hover:border-gray-300']">
                                <input type="radio" v-model="form.judgement" value="OK" class="sr-only" required /> OK
                            </label>
                            <label :class="['flex-1 text-center py-2.5 rounded-lg border-2 cursor-pointer text-sm font-semibold transition-all', form.judgement === 'NG' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-400 hover:border-gray-300']">
                                <input type="radio" v-model="form.judgement" value="NG" class="sr-only" /> NG
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Times -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Start Date</label>
                        <input v-model="form.start_date" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Start Time</label>
                        <input v-model="form.start_time" type="time" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">End Date</label>
                        <input v-model="form.end_date" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">End Time</label>
                        <input v-model="form.end_time" type="time" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark</label>
                    <textarea v-model="form.remark" rows="2" placeholder="Optional notes..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                </div>

                <div class="flex justify-end pt-2 border-t border-gray-100">
                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50">
                        Save Result
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
