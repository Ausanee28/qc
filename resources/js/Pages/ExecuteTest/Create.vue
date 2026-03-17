<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pendingJobs: Array, methods: Array, inspectors: Array, results: Object, filters: Object });
const flash = usePage().props.flash || {};
const submitted = ref(false);
const isEditing = ref(false);

const form = useForm({
    detail_id: null,
    transaction_id: '',
    method_id: '',
    internal_id: '',
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: '',
    judgement: '',
    remark: '',
});

const filterForm = useForm({
    search: props.filters?.search ?? '',
    judgement: props.filters?.judgement ?? 'all',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    per_page: String(props.filters?.per_page ?? 20),
});

const applyFilters = () => {
    router.get(route('execute-test.create'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.judgement = 'all';
    filterForm.date_from = '';
    filterForm.date_to = '';
    filterForm.per_page = '20';
    applyFilters();
};

const visitPage = (url) => {
    if (!url) return;
    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetForm = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.detail_id = null;
};

const submit = () => {
    const options = {
        onSuccess: () => {
            resetForm();
            submitted.value = true;
            setTimeout(() => submitted.value = false, 3000);
        },
    };

    if (isEditing.value && form.detail_id) {
        form.put(route('execute-test.update', form.detail_id), options);
        return;
    }

    form.post(route('execute-test.store'), options);
};

const editResult = (result) => {
    isEditing.value = true;
    form.clearErrors();
    form.detail_id = result.detail_id;
    form.transaction_id = result.transaction_id;
    form.method_id = result.method_id;
    form.internal_id = result.internal_id;
    form.start_date = result.start_date || '';
    form.start_time = result.start_time || '';
    form.end_date = result.end_date || '';
    form.end_time = result.end_time || '';
    form.judgement = result.judgement || '';
    form.remark = result.remark || '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteResult = (result) => {
    if (confirm(`Delete test result #${result.detail_id}?`)) {
        form.delete(route('execute-test.destroy', result.detail_id));
    }
};
</script>

<template>
    <Head title="Execute Test" />
    <AuthenticatedLayout>
        <template #title>Execute Test</template>

        <div class="space-y-6">
            <div class="pg-header">
                <div>
                    <h1 class="pg-title">Execute Test</h1>
                    <p class="pg-sub">Record, edit, and review inspection results for open jobs.</p>
                </div>
            </div>

            <div v-if="pendingJobs.length" class="info-bar" style="margin-bottom:0">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:8px;height:8px;border-radius:50%;background:#F59E0B;animation:pulse 2s infinite"></div>
                    <div class="text-[13px] font-bold text-blue-900">
                        {{ pendingJobs.length }} open job{{ pendingJobs.length > 1 ? 's' : '' }} available for testing
                    </div>
                </div>
                <span class="pill pill-y">Open</span>
            </div>

            <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="flash.success || submitted" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                    {{ flash.success || 'Test result saved successfully.' }}
                </div>
            </transition>

            <div v-if="flash.error" class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                {{ flash.error }}
            </div>

            <form @submit.prevent="submit" class="card card-fill" style="margin:0;display:flex;flex-direction:column;">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-4">
                    <div>
                        <h3 class="text-[15px] font-semibold text-gray-900">{{ isEditing ? 'Edit Test Result' : 'Test Execution' }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ isEditing ? 'Adjust an existing recorded result.' : 'Create a new test result for an open job.' }}</p>
                    </div>
                    <button v-if="isEditing" type="button" @click="resetForm" class="btn-outline">Cancel Edit</button>
                </div>

                <div class="form-grow pt-6">
                    <div class="form-grid" style="margin-bottom:24px">
                        <div>
                            <label class="form-lbl">Open Job *</label>
                            <select v-model="form.transaction_id" required class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>Select job...</option>
                                <option v-for="j in pendingJobs" :key="j.transaction_id" :value="j.transaction_id">
                                    #{{ j.transaction_id }} - {{ j.detail || 'No detail' }} {{ j.dmc ? `(${j.dmc})` : '' }}
                                </option>
                            </select>
                            <div v-if="form.errors.transaction_id" class="mt-1 text-xs text-red-600">{{ form.errors.transaction_id }}</div>
                        </div>
                        <div>
                            <label class="form-lbl">Inspection Process *</label>
                            <select v-model="form.method_id" required class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>Select method...</option>
                                <option v-for="m in methods" :key="m.method_id" :value="m.method_id">{{ m.method_name }}</option>
                            </select>
                            <div v-if="form.errors.method_id" class="mt-1 text-xs text-red-600">{{ form.errors.method_id }}</div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-bottom:24px">
                        <div>
                            <label class="form-lbl">Inspector *</label>
                            <select v-model="form.internal_id" required class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>Select inspector...</option>
                                <option v-for="u in inspectors" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                            </select>
                            <div v-if="form.errors.internal_id" class="mt-1 text-xs text-red-600">{{ form.errors.internal_id }}</div>
                        </div>
                        <div>
                            <label class="form-lbl">Judgement *</label>
                            <div class="radio-grp">
                                <label :class="['radio-card', form.judgement === 'OK' ? 'ok' : '']" style="cursor:pointer">
                                    <input type="radio" v-model="form.judgement" value="OK" class="sr-only" required style="display:none" />
                                    <div style="width:20px;height:20px;border-radius:50%;background:#10B981;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">OK</div>
                                    <span style="font-size:13px;font-weight:600">OK</span>
                                </label>
                                <label :class="['radio-card', form.judgement === 'NG' ? 'ng' : '']" style="cursor:pointer">
                                    <input type="radio" v-model="form.judgement" value="NG" class="sr-only" style="display:none" />
                                    <div style="width:20px;height:20px;border-radius:50%;background:#EF4444;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">NG</div>
                                    <span style="font-size:13px;font-weight:600;color:#EF4444">NG</span>
                                </label>
                            </div>
                            <div v-if="form.errors.judgement" class="mt-1 text-xs text-red-600">{{ form.errors.judgement }}</div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-bottom:24px">
                        <div style="background:#F9FAFB;padding:14px;border-radius:8px;border:1px solid #E5E7EB">
                            <label class="form-lbl">Start Time *</label>
                            <div style="display:flex;gap:8px;margin-top:4px">
                                <input v-model="form.start_date" type="date" class="form-inp" required>
                                <input v-model="form.start_time" type="time" class="form-inp" required>
                            </div>
                            <div v-if="form.errors.start_date || form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_date || form.errors.start_time }}</div>
                        </div>
                        <div style="background:#F9FAFB;padding:14px;border-radius:8px;border:1px solid #E5E7EB">
                            <label class="form-lbl">End Time</label>
                            <div style="display:flex;gap:8px;margin-top:4px">
                                <input v-model="form.end_date" type="date" class="form-inp">
                                <input v-model="form.end_time" type="time" class="form-inp">
                            </div>
                            <div v-if="form.errors.end_date || form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_date || form.errors.end_time }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="form-lbl">Remark</label>
                        <textarea v-model="form.remark" class="form-inp" style="padding:10px 12px;min-height:60px;resize:vertical" placeholder="Optional notes about this test..."></textarea>
                    </div>
                </div>

                <div style="padding-top:20px;border-top:1px solid #E5E7EB;display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
                    <button type="button" @click="resetForm" class="btn-outline">Clear</button>
                    <button type="submit" :disabled="form.processing" class="btn" style="background:linear-gradient(135deg,#059669,#0D9488)">
                        <span v-if="form.processing">{{ isEditing ? 'Updating...' : 'Submitting...' }}</span>
                        <span v-else>{{ isEditing ? 'Update Result' : 'Submit Test Result' }}</span>
                    </button>
                </div>
            </form>

            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Test Results</h2>
                        <p class="mt-1 text-sm text-gray-500">Use filters and pagination to review long-term historical records without losing context.</p>
                    </div>

                    <form @submit.prevent="applyFilters" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                        <input v-model="filterForm.search" type="text" placeholder="Search job, method, inspector..." class="lg:col-span-2 rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10" />
                        <select v-model="filterForm.judgement" class="rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <option value="all">All judgement</option>
                            <option value="OK">OK</option>
                            <option value="NG">NG</option>
                        </select>
                        <input v-model="filterForm.date_from" type="date" class="rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10" />
                        <input v-model="filterForm.date_to" type="date" class="rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10" />
                        <select v-model="filterForm.per_page" class="rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <option value="10">10 / page</option>
                            <option value="20">20 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                    </form>
                    <div class="mt-3 flex justify-end gap-2">
                        <button type="button" @click="resetFilters" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">Reset</button>
                        <button type="button" @click="applyFilters" class="rounded-lg bg-gray-900 px-3 py-1.5 text-sm text-white hover:bg-black">Apply</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Result</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Job</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Method / Inspector</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Judgement</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-if="results.data.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No test results found.</td>
                            </tr>
                            <tr v-for="result in results.data" :key="result.detail_id" class="align-top">
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div class="font-mono font-semibold text-gray-900">#{{ result.detail_id }}</div>
                                    <div class="mt-1 text-xs text-gray-500">Job #{{ result.transaction_id }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ result.job_label }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div>{{ result.method_name || '-' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ result.inspector_name || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div>{{ result.start_date }} {{ result.start_time }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ result.end_date ? `${result.end_date} ${result.end_time}` : 'End time not set' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span :class="result.judgement === 'OK' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                                        {{ result.judgement }}
                                    </span>
                                    <div v-if="result.remark" class="mt-2 text-xs text-gray-500">{{ result.remark }}</div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button @click="editResult(result)" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">Edit</button>
                                        <button @click="deleteResult(result)" class="rounded-lg border border-rose-200 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-gray-600">
                        Showing {{ results.from ?? 0 }} to {{ results.to ?? 0 }} of {{ results.total ?? 0 }} results
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <button
                            v-for="(link, index) in results.links"
                            :key="index"
                            :disabled="!link.url"
                            @click="visitPage(link.url)"
                            class="rounded-md border px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-40"
                            :class="link.active ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
