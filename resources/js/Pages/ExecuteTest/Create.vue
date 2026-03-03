<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pendingJobs: Array, methods: Array, inspectors: Array });
const flash = usePage().props.flash || {};
const submitted = ref(false);

const form = useForm({
    transaction_id: '', method_id: '', internal_id: '',
    start_date: '', start_time: '', end_date: '', end_time: '',
    judgement: '', remark: '',
});

const submit = () => {
    form.post(route('execute-test.store'), {
        onSuccess: () => { form.reset(); submitted.value = true; setTimeout(() => submitted.value = false, 3000); },
    });
};
</script>

<template>
    <Head title="Execute Test" />
    <AuthenticatedLayout>
        <template #title>Execute Test</template>

        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Record Test Result</h2>
                        <p class="text-sm text-gray-500">Select a pending job and record the outcome</p>
                    </div>
                </div>
            </div>

            <!-- Pending count -->
            <div v-if="pendingJobs.length" class="mb-6 flex items-center gap-2 text-xs text-gray-400">
                <div class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></div>
                {{ pendingJobs.length }} pending job{{ pendingJobs.length > 1 ? 's' : '' }} awaiting test
            </div>

            <!-- Success -->
            <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="flash.success || submitted" class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 px-5 py-4 rounded-xl">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <p class="text-sm font-semibold text-emerald-800">Test result saved successfully</p>
                </div>
            </transition>

            <form @submit.prevent="submit" class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden">
                <!-- Job & Method -->
                <div class="px-6 pt-6 pb-2">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Test Configuration</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pending Job <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <select v-model="form.transaction_id" required class="w-full h-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm pl-4 pr-10 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all appearance-none">
                                    <option value="">Select job...</option>
                                    <option v-for="j in pendingJobs" :key="j.transaction_id" :value="j.transaction_id">
                                        #{{ j.transaction_id }} — {{ j.equipment_name }} {{ j.dmc ? `(${j.dmc})` : '' }}
                                    </option>
                                </select>
                                <svg class="w-4 h-4 text-gray-400 absolute right-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Test Method <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <select v-model="form.method_id" required class="w-full h-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm pl-4 pr-10 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all appearance-none">
                                    <option value="">Select method...</option>
                                    <option v-for="m in methods" :key="m.method_id" :value="m.method_id">{{ m.method_name }}</option>
                                </select>
                                <svg class="w-4 h-4 text-gray-400 absolute right-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 mx-6 my-4"></div>

                <!-- Inspector & Judgement -->
                <div class="px-6 pb-2">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Result</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Inspector <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <select v-model="form.internal_id" required class="w-full h-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm pl-4 pr-10 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all appearance-none">
                                    <option value="">Select inspector...</option>
                                    <option v-for="u in inspectors" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                                </select>
                                <svg class="w-4 h-4 text-gray-400 absolute right-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Judgement <span class="text-red-400">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label :class="['flex flex-col items-center py-3 rounded-xl border-2 cursor-pointer transition-all duration-200', form.judgement === 'OK' ? 'border-emerald-500 bg-emerald-50 shadow-sm shadow-emerald-100' : 'border-gray-200 bg-gray-50 hover:border-gray-300']">
                                    <input type="radio" v-model="form.judgement" value="OK" class="sr-only" required />
                                    <svg class="w-5 h-5 mb-1" :class="form.judgement === 'OK' ? 'text-emerald-600' : 'text-gray-300'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="text-sm font-bold" :class="form.judgement === 'OK' ? 'text-emerald-700' : 'text-gray-400'">OK</span>
                                </label>
                                <label :class="['flex flex-col items-center py-3 rounded-xl border-2 cursor-pointer transition-all duration-200', form.judgement === 'NG' ? 'border-red-500 bg-red-50 shadow-sm shadow-red-100' : 'border-gray-200 bg-gray-50 hover:border-gray-300']">
                                    <input type="radio" v-model="form.judgement" value="NG" class="sr-only" />
                                    <svg class="w-5 h-5 mb-1" :class="form.judgement === 'NG' ? 'text-red-600' : 'text-gray-300'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="text-sm font-bold" :class="form.judgement === 'NG' ? 'text-red-700' : 'text-gray-400'">NG</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 mx-6 my-4"></div>

                <!-- Time -->
                <div class="px-6 pb-2">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Time Period</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Start Date</label>
                            <input v-model="form.start_date" type="date" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm px-3 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Start Time</label>
                            <input v-model="form.start_time" type="time" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm px-3 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">End Date</label>
                            <input v-model="form.end_date" type="date" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm px-3 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">End Time</label>
                            <input v-model="form.end_time" type="time" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm px-3 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all" />
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 mx-6 my-4"></div>

                <!-- Remark -->
                <div class="px-6 pb-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remark</label>
                    <textarea v-model="form.remark" rows="2" placeholder="Optional notes..." class="w-full rounded-xl bg-gray-50 border border-gray-200 text-gray-900 text-sm px-4 py-3 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all placeholder:text-gray-400 resize-none"></textarea>
                </div>

                <!-- Submit -->
                <div class="bg-gray-50/50 border-t border-gray-100 px-6 py-4 flex items-center justify-end mt-2">
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 px-6 h-10 rounded-xl text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all disabled:opacity-50">
                        <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        Save Result
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
