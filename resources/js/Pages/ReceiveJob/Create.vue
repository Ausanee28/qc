<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref, watch } from 'vue';

const props = defineProps({ externals: Array, jobs: Object, filters: Object, returningOutsiders: Array, otherExternalId: [String, Number] });
const flash = usePage().props.flash || {};
const currentUserRole = usePage().props.auth?.user?.role ?? '';
const canDelete = currentUserRole === 'admin';
const submitted = ref(false);
const isEditing = ref(false);
const editFormRef = ref(null);
const senderSelectRef = ref(null);
const defaultFilters = {
    search: '',
    status: 'all',
    date_from: '',
    date_to: '',
    per_page: '20',
};

const form = useForm({
    transaction_id: null,
    external_id: '',
    internal_id: null,
    detail: '',
    dmc: '',
    line: '',
    shift: '',
    model: '',
    model: '',
    sender_leader: '',
});

const filterForm = reactive({
    ...defaultFilters,
    search: props.filters?.search ?? defaultFilters.search,
    status: props.filters?.status ?? defaultFilters.status,
    date_from: props.filters?.date_from ?? defaultFilters.date_from,
    date_to: props.filters?.date_to ?? defaultFilters.date_to,
    per_page: String(props.filters?.per_page ?? defaultFilters.per_page),
});

const senderMode = ref('internal'); // 'internal', 'returning_outsider', 'new_outsider'
const selectedReturningOutsider = ref('');

const externalOptions = computed(() => props.externals?.filter(e => e.external_id !== props.otherExternalId) ?? []);

const returningOutsidersOptions = computed(() => props.returningOutsiders ?? []);

watch(senderMode, (newMode) => {
    if (newMode === 'internal') {
        form.external_id = '';
        form.sender_leader = '';
        selectedReturningOutsider.value = '';
    } else if (newMode === 'returning_outsider') {
        form.external_id = props.otherExternalId;
        form.sender_leader = '';
        selectedReturningOutsider.value = '';
    } else if (newMode === 'new_outsider') {
        form.external_id = props.otherExternalId;
        form.sender_leader = '';
        selectedReturningOutsider.value = '';
    }
});

watch(selectedReturningOutsider, (newLeader) => {
    if (senderMode.value === 'returning_outsider' && newLeader) {
        form.sender_leader = newLeader;
    }
});

// Removing watch on external_id for isOtherSender since we use senderMode now
const lineOptions = [
    ...Array.from({ length: 11 }, (_, i) => `Line ${i + 1}`),
    'P4#1',
    'P4#2',
    'P4#3',
    'MTA 1',
    'MTA 2',
    'ITT',
    'ES30',
];
const externalOptionsReady = computed(() => Array.isArray(props.externals));

const jobPaginator = computed(() => props.jobs ?? null);
const jobRows = computed(() => jobPaginator.value?.data ?? []);
const jobLinks = computed(() => jobPaginator.value?.links ?? []);
const workflowReloadOnly = ['jobs', 'filters', 'flash'];
const workflowInvalidateTags = ['workflow', 'dashboard', 'report', 'certificates', 'performance'];

const statusLabel = (job) => {
    if (job.is_deleted) return 'Deleted';
    return job.is_closed ? 'Closed' : 'Open';
};

const statusClass = (job) => {
    if (job.is_deleted) {
        return 'bg-gray-100 text-gray-700 border border-gray-200';
    }

    return 'bg-blue-100 text-blue-700 border border-blue-200';
};

const canEditJob = (job) => !job.is_deleted && !(job.details_count > 0 && !job.is_closed);
const canDeleteJob = (job) => !job.is_deleted && canDelete && !(job.details_count > 0 && !job.is_closed);
const canToggleJobStatus = (job) => !job.is_deleted && (job.is_closed || job.details_count > 0);

const pagerButtonClass = (link) => link.active
    ? 'border-gray-900 bg-gray-900 text-white'
    : 'border-gray-300 text-gray-700 hover:bg-gray-50';

const filterPayload = () => ({
    search: filterForm.search,
    status: filterForm.status,
    date_from: filterForm.date_from,
    date_to: filterForm.date_to,
    per_page: filterForm.per_page,
});

const applyFilters = () => {
    router.get(route('receive-job.create'), filterPayload(), {
        only: workflowReloadOnly,
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    Object.assign(filterForm, defaultFilters);
    applyFilters();
};

const visitPage = (url) => {
    if (!url) return;

    const page = (() => {
        try {
            return new URL(url, window.location.origin).searchParams.get('page');
        } catch {
            return null;
        }
    })();

    router.get(route('receive-job.create'), {
        ...filterPayload(),
        ...(page ? { page } : {}),
    }, {
        only: workflowReloadOnly,
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetForm = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.transaction_id = null;
};

const submit = () => {
    const options = {
        only: workflowReloadOnly,
        invalidateCacheTags: workflowInvalidateTags,
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            submitted.value = true;
            setTimeout(() => submitted.value = false, 3000);
        },
    };

    if (isEditing.value && form.transaction_id) {
        form.put(route('receive-job.update', form.transaction_id), options);
        return;
    }

    form.post(route('receive-job.store'), options);
};

const scrollToEditForm = async () => {
    await nextTick();

    const formElement = editFormRef.value;
    if (formElement instanceof HTMLElement) {
        const topOffset = 88;
        const scrollContainer = formElement.closest('.shell-scroll-region');

        if (scrollContainer instanceof HTMLElement) {
            const targetTop = scrollContainer.scrollTop
                + formElement.getBoundingClientRect().top
                - scrollContainer.getBoundingClientRect().top
                - topOffset;

            scrollContainer.scrollTo({
                top: Math.max(targetTop, 0),
                behavior: 'smooth',
            });
        } else {
            formElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    window.setTimeout(() => {
        const firstInput = senderSelectRef.value;
        if (firstInput && typeof firstInput.focus === 'function') {
            firstInput.focus({ preventScroll: true });
        }
    }, 220);
};

const editJob = (job) => {
    isEditing.value = true;
    form.clearErrors();
    form.transaction_id = job.transaction_id;
    form.external_id = job.external_id;
    form.internal_id = job.internal_id;
    form.detail = job.detail || '';
    form.dmc = job.dmc || '';
    form.line = job.line || '';
    form.shift = job.shift || '';
    form.model = job.model || '';
    form.sender_leader = job.sender_leader || '';

    if (job.external_id == props.otherExternalId) {
        // Find if they exist in returning outsiders
        const isReturning = returningOutsidersOptions.value.some(o => o.sender_leader === job.sender_leader);
        if (isReturning) {
            senderMode.value = 'returning_outsider';
            selectedReturningOutsider.value = job.sender_leader;
        } else {
            senderMode.value = 'new_outsider';
        }
    } else {
        senderMode.value = 'internal';
    }

    void scrollToEditForm();
};

const deleteJob = (job) => {
    if (!canDelete) {
        return;
    }

    if (confirm(`Delete job #${job.transaction_id}?`)) {
        form.delete(route('receive-job.destroy', job.transaction_id), {
            only: workflowReloadOnly,
            invalidateCacheTags: workflowInvalidateTags,
            preserveScroll: true,
            onSuccess: resetForm,
        });
    }
};

const restoreJob = (job) => {
    if (!canDelete) {
        return;
    }

    if (confirm(`Restore job #${job.transaction_id}?`)) {
        form.patch(route('receive-job.restore', job.transaction_id), {
            only: workflowReloadOnly,
            invalidateCacheTags: workflowInvalidateTags,
            preserveScroll: true,
        });
    }
};

const toggleJobStatus = (job) => {
    form.clearErrors();

    if (!job.is_closed && job.details_count === 0) {
        return;
    }

    if (job.is_closed) {
        form.patch(route('receive-job.reopen', job.transaction_id), {
            only: workflowReloadOnly,
            invalidateCacheTags: workflowInvalidateTags,
            preserveScroll: true,
        });
        return;
    }

    form.patch(route('receive-job.close', job.transaction_id), {
        only: workflowReloadOnly,
        invalidateCacheTags: workflowInvalidateTags,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Receive Job" />
    <AuthenticatedLayout>
        <template #title>Receive Job</template>

        <div class="space-y-6">
            <div class="pg-header">
                <div>
                    <h1 class="pg-title">Receive Job</h1>
                    <p class="pg-sub">Record incoming jobs and manage open or closed work items.</p>
                </div>
            </div>

            <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="flash.success || submitted" class="rounded-xl border border-orange-500/20 bg-orange-500/10 px-5 py-4 text-sm text-orange-100">
                    {{ flash.success || 'Job saved successfully.' }}
                </div>
            </transition>

            <div v-if="flash.error" class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                {{ flash.error }}
            </div>

            <form ref="editFormRef" @submit.prevent="submit" class="card card-fill" style="margin:0;display:flex;flex-direction:column;">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-4">
                    <div>
                        <h3 class="text-[15px] font-semibold text-gray-900">{{ isEditing ? 'Edit Job' : 'Job Registration' }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ isEditing ? 'Update job header details before testing starts.' : 'Create a new inspection job in the queue.' }}</p>
                    </div>
                    <button v-if="isEditing" type="button" @click="resetForm" class="btn-outline">Cancel Edit</button>
                </div>

                <div class="form-grow pt-6">
                    <div class="form-grid" style="margin-bottom:24px">
                        <div class="col-span-full border-b border-gray-100 pb-4 mb-4">
                            <label class="form-lbl mb-3 block">ประเภทผู้ส่งงาน (Sender Type) *</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" v-model="senderMode" value="internal" class="h-4 w-4 text-[var(--theme-accent-strong)] focus:ring-[var(--theme-accent-strong)] border-[var(--color-border-strong)] bg-[var(--color-surface-2)]">
                                    <span class="ml-2 text-sm text-[var(--theme-text)] font-medium">QC Line</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" v-model="senderMode" value="returning_outsider" class="h-4 w-4 text-[var(--theme-accent-strong)] focus:ring-[var(--theme-accent-strong)] border-[var(--color-border-strong)] bg-[var(--color-surface-2)]">
                                    <span class="ml-2 text-sm text-[var(--theme-text)] font-medium">Leader (เคยมาส่งแล้ว)</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" v-model="senderMode" value="new_outsider" class="h-4 w-4 text-[var(--theme-accent-strong)] focus:ring-[var(--theme-accent-strong)] border-[var(--color-border-strong)] bg-[var(--color-surface-2)]">
                                    <span class="ml-2 text-sm text-[var(--theme-text)] font-medium">Leader (มาครั้งแรก)</span>
                                </label>
                            </div>
                        </div>

                        <div v-if="senderMode === 'internal'">
                            <label class="form-lbl">Sender (External) *</label>
                            <select ref="senderSelectRef" v-model="form.external_id" required :disabled="!externalOptionsReady" class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>{{ externalOptionsReady ? '-- Select Sender --' : 'Loading senders...' }}</option>
                                <option v-for="e in externalOptions" :key="e.external_id" :value="e.external_id">{{ e.external_name }}</option>
                            </select>
                            <div v-if="form.errors.external_id" class="mt-1 text-xs text-red-600">{{ form.errors.external_id }}</div>
                        </div>

                        <div v-else-if="senderMode === 'returning_outsider'">
                            <label class="form-lbl">Select Leader *</label>
                            <select v-model="selectedReturningOutsider" required class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>-- Select Leader --</option>
                                <option v-for="o in returningOutsidersOptions" :key="o.sender_leader" :value="o.sender_leader">
                                    {{ o.sender_leader }}
                                </option>
                            </select>
                        </div>

                        <div v-if="senderMode === 'new_outsider'">
                            <label class="form-lbl">Leader Name *</label>
                            <input v-model="form.sender_leader" type="text" class="form-inp" required style="padding:10px 12px" placeholder="Enter leader name">
                            <div v-if="form.errors.sender_leader" class="mt-1 text-xs text-red-600">{{ form.errors.sender_leader }}</div>
                        </div>



                        <div>
                            <label class="form-lbl">DMC Code</label>
                            <input v-model="form.dmc" type="text" class="form-inp" style="padding:10px 12px">
                            <div v-if="form.errors.dmc" class="mt-1 text-xs text-red-600">{{ form.errors.dmc }}</div>
                        </div>
                        <div>
                            <label class="form-lbl">Line</label>
                            <select v-model="form.line" class="form-inp" style="padding:10px 12px">
                                <option value="">-- Select Line --</option>
                                <option v-for="lineOption in lineOptions" :key="lineOption" :value="lineOption">{{ lineOption }}</option>
                            </select>
                            <div v-if="form.errors.line" class="mt-1 text-xs text-red-600">{{ form.errors.line }}</div>
                        </div>

                        <div>
                            <label class="form-lbl font-semibold text-gray-900 text-sm">Working Shift</label>
                            <div class="mt-3 flex flex-col gap-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" v-model="form.shift" value="Day Shift" class="h-4 w-4 text-[var(--theme-accent-strong)] focus:ring-[var(--theme-accent-strong)] border-[var(--color-border-strong)] bg-[var(--color-surface-2)]" />
                                    <span class="text-sm font-medium text-[var(--theme-text)]">Day Shift</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" v-model="form.shift" value="Night Shift" class="h-4 w-4 text-[var(--theme-accent-strong)] focus:ring-[var(--theme-accent-strong)] border-[var(--color-border-strong)] bg-[var(--color-surface-2)]" />
                                    <span class="text-sm font-medium text-[var(--theme-text)]">Night Shift</span>
                                </label>
                            </div>
                            <div v-if="form.errors.shift" class="mt-1 text-xs text-red-600">{{ form.errors.shift }}</div>
                        </div>
                        <div>
                            <label class="form-lbl">Model</label>
                            <input v-model="form.model" type="text" class="form-inp" style="padding:10px 12px">
                            <div v-if="form.errors.model" class="mt-1 text-xs text-red-600">{{ form.errors.model }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="form-lbl">Detail</label>
                        <textarea v-model="form.detail" class="form-inp" style="padding:10px 12px;min-height:80px;resize:vertical"></textarea>
                        <div v-if="form.errors.detail" class="mt-1 text-xs text-red-600">{{ form.errors.detail }}</div>
                    </div>
                </div>

                <div style="padding-top:20px;border-top:1px solid rgba(255,255,255,0.08);display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
                    <button type="button" @click="resetForm" class="btn-outline">Clear</button>
                    <button type="submit" :disabled="form.processing" class="btn">
                        <span v-if="form.processing">{{ isEditing ? 'Updating...' : 'Submitting...' }}</span>
                        <span v-else>{{ isEditing ? 'Update Job' : 'Save Job' }}</span>
                    </button>
                </div>
            </form>

            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Jobs</h2>
                        <p class="mt-1 text-sm text-gray-500">Open jobs with test results are locked. Closed jobs can be edited, reopened, or deleted.</p>
                    </div>

                    <form @submit.prevent="applyFilters" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                        <input v-model="filterForm.search" type="text" placeholder="Search job, sender, DMC..." class="lg:col-span-2 rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10" />
                        <select v-model="filterForm.status" class="rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <option value="all">All status</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                            <option value="deleted">Deleted</option>
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
                        <button type="button" @click="applyFilters" class="btn">Apply</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Job</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Sender / Receiver</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Detail</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-if="jobRows.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No jobs found.</td>
                                </tr>
                                <tr v-for="job in jobRows" :key="job.transaction_id" class="align-top">
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="font-mono font-semibold text-gray-900">#{{ job.transaction_id }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ job.receive_date }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ job.dmc || 'No DMC' }} / {{ job.line || 'No line' }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ job.shift || 'No Shift' }} / {{ job.model || 'No Model' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <template v-if="job.external_id == otherExternalId">
                                            <div class="font-medium text-gray-900">{{ job.sender_leader || 'Unknown Leader' }}</div>
                                            <div class="text-[11px] text-gray-500 font-medium tracking-wide">OUTSIDE LEADER</div>
                                        </template>
                                        <template v-else>
                                            <div class="font-medium text-gray-900">{{ job.external_name }}</div>
                                        </template>
                                        <div class="mt-1 text-xs text-gray-500">Receiver: {{ job.internal_name || '-' }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ job.details_count }} test result(s)</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ job.detail || '-' }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span :class="statusClass(job)" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                                            {{ statusLabel(job) }}
                                        </span>
                                        <div v-if="job.return_date && !job.is_deleted" class="mt-2 text-xs text-gray-500">Closed: {{ job.return_date }}</div>
                                        <div v-if="job.deleted_at" class="mt-2 text-xs text-gray-500">Deleted: {{ job.deleted_at }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button :disabled="!canEditJob(job)" @click="editJob(job)" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">Edit</button>
                                            <button :disabled="!canDeleteJob(job)" @click="deleteJob(job)" class="rounded-lg border border-rose-200 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40" :title="!canDelete ? 'Only admin can delete' : ''">Delete</button>
                                            <button :disabled="!canToggleJobStatus(job)" @click="toggleJobStatus(job)" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40" :title="!job.is_closed && job.details_count === 0 ? 'Need at least 1 test result before closing' : ''">
                                                {{ job.is_closed ? 'Reopen' : 'Close' }}
                                            </button>
                                            <button :disabled="!job.is_deleted || !canDelete" @click="restoreJob(job)" class="rounded-lg border border-orange-200 px-3 py-1.5 text-sm text-orange-700 hover:bg-orange-100 disabled:cursor-not-allowed disabled:opacity-40" :title="!canDelete ? 'Only admin can restore' : ''">Restore</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-gray-600">
                            Showing {{ jobPaginator?.from ?? 0 }} to {{ jobPaginator?.to ?? 0 }}
                            <span v-if="typeof jobPaginator?.total === 'number'">of {{ jobPaginator.total }}</span>
                            jobs
                        </div>
                        <div class="flex flex-wrap justify-end gap-2">
                            <button
                                v-for="(link, index) in jobLinks"
                                :key="index"
                                type="button"
                                :disabled="!link.url"
                                @click="visitPage(link.url)"
                                class="rounded-md border px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-40"
                                :class="pagerButtonClass(link)"
                                v-html="link.label"
                            />
                        </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
