<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ externals: Array, internals: Array, equipments: Array });
const flash = usePage().props.flash || {};
const submitted = ref(false);

const form = useForm({
    external_id: '', internal_id: '', equipment_id: '', dmc: '', line: '',
});

const submit = () => {
    form.post(route('receive-job.store'), {
        onSuccess: () => { form.reset(); submitted.value = true; setTimeout(() => submitted.value = false, 3000); },
    });
};
</script>

<template>
    <Head title="Receive Job" />
    <AuthenticatedLayout>
        <template #title>Receive Job</template>

        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">New Job Entry</h2>
                        <p class="text-sm text-gray-500">Register equipment for quality inspection</p>
                    </div>
                </div>
            </div>

            <!-- Success -->
            <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="flash.success || submitted" class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 px-5 py-4 rounded-xl">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-emerald-800">Job registered successfully</p>
                        <p class="text-xs text-emerald-600">The item has been added to the queue</p>
                    </div>
                </div>
            </transition>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden">
                <!-- Section: Parties -->
                <div class="px-6 pt-6 pb-2">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Parties</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sender <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <select v-model="form.external_id" required class="w-full h-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm pl-4 pr-10 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all appearance-none">
                                    <option value="">Select sender...</option>
                                    <option v-for="e in externals" :key="e.external_id" :value="e.external_id">{{ e.external_name }}</option>
                                </select>
                                <svg class="w-4 h-4 text-gray-400 absolute right-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Receiver <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <select v-model="form.internal_id" required class="w-full h-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm pl-4 pr-10 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all appearance-none">
                                    <option value="">Select receiver...</option>
                                    <option v-for="u in internals" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                                </select>
                                <svg class="w-4 h-4 text-gray-400 absolute right-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 mx-6 my-4"></div>

                <!-- Section: Equipment -->
                <div class="px-6 pb-2">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Equipment Details</h3>
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Equipment <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <select v-model="form.equipment_id" required class="w-full h-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm pl-4 pr-10 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all appearance-none">
                                <option value="">Select equipment...</option>
                                <option v-for="eq in equipments" :key="eq.equipment_id" :value="eq.equipment_id">{{ eq.equipment_name }}</option>
                            </select>
                            <svg class="w-4 h-4 text-gray-400 absolute right-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">DMC Code</label>
                            <input v-model="form.dmc" type="text" placeholder="e.g. DMC-20250303-001" class="w-full h-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm px-4 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all placeholder:text-gray-400" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Production Line</label>
                            <input v-model="form.line" type="text" placeholder="e.g. Line A1" class="w-full h-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm px-4 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all placeholder:text-gray-400" />
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="bg-gray-50/50 border-t border-gray-100 px-6 py-4 flex items-center justify-between mt-4">
                    <p class="text-xs text-gray-400">Fields marked with <span class="text-red-400">*</span> are required</p>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 px-6 h-10 rounded-xl text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all disabled:opacity-50">
                        <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        Submit Job
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
