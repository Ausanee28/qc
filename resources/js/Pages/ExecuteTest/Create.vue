<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { getEcho } from '@/lib/realtime';

const props = defineProps({
    pendingJobs: Array,
    pendingJobsCount: Number,
    pendingJobsWindow: Number,
    pendingJobsVersion: String,
    methods: Array,
    inspectors: Array,
    results: Object,
    filters: Object,
});
const flash = usePage().props.flash || {};
const currentUserRole = usePage().props.auth?.user?.role ?? '';
const canDelete = currentUserRole === 'admin';
const submitted = ref(false);
const isEditing = ref(false);
const editFormRef = ref(null);
const jobSelectRef = ref(null);
const syncingPendingJobs = ref(false);
const checkingPendingJobsVersion = ref(false);
const pendingJobsSyncIntervalActiveMs = 30000;
const pendingJobsSyncIntervalHiddenMs = 90000;
const currentPendingJobsVersion = ref(props.pendingJobsVersion ?? '');
const openJobCountState = ref(Number(props.pendingJobsCount ?? 0));
const pendingJobsState = ref(Array.isArray(props.pendingJobs) ? props.pendingJobs : []);
const defaultFilters = {
    search: '',
    judgement: 'all',
    record_state: 'active',
    date_from: '',
    date_to: '',
    per_page: '20',
};
let pendingJobsVersionTimer = null;
let pendingJobsEcho = null;
let pendingJobsEchoConnection = null;
let pendingJobsEchoConnectionStateHandler = null;
let usePollingFallback = true;
const jsonHeaders = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
};

const form = useForm({
    detail_id: null,
    transaction_id: '',
    method_id: '',
    internal_id: '',
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: '',
    max_value: '',
    min_value: '',
    judgement: '',
    remark: '',
});

const filterForm = reactive({
    ...defaultFilters,
    search: props.filters?.search ?? defaultFilters.search,
    judgement: props.filters?.judgement ?? defaultFilters.judgement,
    record_state: props.filters?.record_state ?? defaultFilters.record_state,
    date_from: props.filters?.date_from ?? defaultFilters.date_from,
    date_to: props.filters?.date_to ?? defaultFilters.date_to,
    per_page: String(props.filters?.per_page ?? defaultFilters.per_page),
});

const pendingJobOptions = computed(() => pendingJobsState.value ?? []);
const pendingJobsReady = computed(() => Array.isArray(props.pendingJobs));
const pendingJobsWindow = computed(() => Number(props.pendingJobsWindow ?? pendingJobOptions.value.length ?? 0));
const openJobCount = computed(() => Number(openJobCountState.value ?? pendingJobOptions.value.length ?? 0));
const methodOptions = computed(() => props.methods ?? []);
const inspectorOptions = computed(() => props.inspectors ?? []);
const methodOptionsReady = computed(() => Array.isArray(props.methods));
const inspectorOptionsReady = computed(() => Array.isArray(props.inspectors));
const resultPaginator = computed(() => props.results ?? null);
const resultRows = computed(() => resultPaginator.value?.data ?? []);
const resultLinks = computed(() => resultPaginator.value?.links ?? []);
const historyReloadOnly = ['results', 'filters', 'flash'];
const workflowMutationReloadOnly = ['pendingJobs', 'pendingJobsCount', 'pendingJobsVersion', 'results', 'filters', 'flash'];
const pendingJobsReloadOnly = ['pendingJobs', 'pendingJobsCount', 'pendingJobsVersion'];
const workflowInvalidateTags = ['workflow', 'dashboard', 'report', 'certificates', 'performance'];

const judgementClass = (result) => result.judgement === 'OK'
    ? 'judge-pill judge-pill--ok'
    : 'judge-pill judge-pill--ng';

const canEditResult = (result) => !result.is_deleted;
const canDeleteResult = (result) => canDelete && !result.is_deleted;
const canRestoreResult = (result) => canDelete && result.is_deleted;

const pagerButtonClass = (link) => link.active
    ? 'border-gray-900 bg-gray-900 text-white'
    : 'border-gray-300 text-gray-700 hover:bg-gray-50';

const filterPayload = () => ({
    search: filterForm.search,
    judgement: filterForm.judgement,
    record_state: filterForm.record_state,
    date_from: filterForm.date_from,
    date_to: filterForm.date_to,
    per_page: filterForm.per_page,
});

const applyFilters = () => {
    router.get(route('execute-test.create'), filterPayload(), {
        only: historyReloadOnly,
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

    router.get(route('execute-test.create'), {
        ...filterPayload(),
        ...(page ? { page } : {}),
    }, {
        only: historyReloadOnly,
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
        only: workflowMutationReloadOnly,
        invalidateCacheTags: workflowInvalidateTags,
        preserveScroll: true,
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
        const firstInput = jobSelectRef.value;
        if (firstInput && typeof firstInput.focus === 'function') {
            firstInput.focus({ preventScroll: true });
        }
    }, 220);
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
    form.max_value = result.max_value || '';
    form.min_value = result.min_value || '';
    form.judgement = result.judgement || '';
    form.remark = result.remark || '';
    void scrollToEditForm();
};

const deleteResult = (result) => {
    if (!canDelete) {
        return;
    }

    if (confirm(`Delete test result #${result.detail_id}?`)) {
        form.delete(route('execute-test.destroy', result.detail_id), {
            only: workflowMutationReloadOnly,
            invalidateCacheTags: workflowInvalidateTags,
            preserveScroll: true,
            onSuccess: resetForm,
        });
    }
};

const restoreResult = (result) => {
    if (!canDelete) {
        return;
    }

    if (confirm(`Restore test result #${result.detail_id}?`)) {
        form.patch(route('execute-test.restore', result.detail_id), {
            only: workflowMutationReloadOnly,
            invalidateCacheTags: workflowInvalidateTags,
            preserveScroll: true,
        });
    }
};

const toTwoDigits = (value) => String(value).padStart(2, '0');

const nowParts = () => {
    const now = new Date();
    return {
        date: `${now.getFullYear()}-${toTwoDigits(now.getMonth() + 1)}-${toTwoDigits(now.getDate())}`,
        time: `${toTwoDigits(now.getHours())}:${toTwoDigits(now.getMinutes())}`,
    };
};

const setStartNow = () => {
    const { date, time } = nowParts();
    form.start_date = date;
    form.start_time = time;
};

const setEndNow = () => {
    const { date, time } = nowParts();
    form.end_date = date;
    form.end_time = time;
};

const setNowForBoth = () => {
    const { date, time } = nowParts();
    form.start_date = date;
    form.start_time = time;
    form.end_date = date;
    form.end_time = time;
};

const copyStartToEnd = () => {
    form.end_date = form.start_date || '';
    form.end_time = form.start_time || '';
};

const reloadPendingJobs = () => {
    if (syncingPendingJobs.value || form.processing) {
        return;
    }

    router.reload({
        only: pendingJobsReloadOnly,
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            syncingPendingJobs.value = true;
        },
        onFinish: () => {
            syncingPendingJobs.value = false;
            currentPendingJobsVersion.value = props.pendingJobsVersion ?? currentPendingJobsVersion.value;
        },
    });
};

const checkPendingJobsVersion = async ({ force = false } = {}) => {
    if (checkingPendingJobsVersion.value || syncingPendingJobs.value || form.processing) {
        return;
    }

    if (!force && document.hidden) {
        return;
    }

    checkingPendingJobsVersion.value = true;

    try {
        const response = await fetch(route('execute-test.pending-jobs-version'), {
            method: 'GET',
            headers: jsonHeaders,
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Pending jobs version request failed with status ${response.status}`);
        }

        const data = await response.json();
        const latestVersion = String(data?.version ?? '');

        if (latestVersion !== '' && latestVersion !== currentPendingJobsVersion.value) {
            currentPendingJobsVersion.value = latestVersion;
            reloadPendingJobs();
        }
    } catch (error) {
        // Ignore transient polling errors and retry on next tick.
    } finally {
        checkingPendingJobsVersion.value = false;
    }
};

const handlePageFocus = () => {
    if (!usePollingFallback) {
        return;
    }

    checkPendingJobsVersion({ force: true });
};

const handleVisibilityChange = () => {
    if (!usePollingFallback) {
        return;
    }

    if (!document.hidden) {
        startPendingJobsPolling(pendingJobsSyncIntervalActiveMs);
        checkPendingJobsVersion({ force: true });
        return;
    }

    startPendingJobsPolling(pendingJobsSyncIntervalHiddenMs);
};

const stopPendingJobsPolling = () => {
    if (pendingJobsVersionTimer !== null) {
        window.clearInterval(pendingJobsVersionTimer);
        pendingJobsVersionTimer = null;
    }
};

const startPendingJobsPolling = (intervalMs) => {
    if (pendingJobsVersionTimer !== null) {
        window.clearInterval(pendingJobsVersionTimer);
    }

    pendingJobsVersionTimer = window.setInterval(checkPendingJobsVersion, intervalMs);
};

const enablePollingFallback = () => {
    if (usePollingFallback) {
        return;
    }

    usePollingFallback = true;
    startPendingJobsPolling(document.hidden ? pendingJobsSyncIntervalHiddenMs : pendingJobsSyncIntervalActiveMs);
};

const disablePollingFallback = () => {
    if (!usePollingFallback) {
        return;
    }

    usePollingFallback = false;
    stopPendingJobsPolling();
};

onMounted(() => {
    window.addEventListener('focus', handlePageFocus);
    document.addEventListener('visibilitychange', handleVisibilityChange);

    void (async () => {
        pendingJobsEcho = await getEcho();

        if (pendingJobsEcho) {
            disablePollingFallback();

            const dashboardChannel = pendingJobsEcho.private('dashboard.global');
            dashboardChannel.listen('.dashboard.updated', reloadPendingJobs);

            if (typeof dashboardChannel.error === 'function') {
                dashboardChannel.error(() => {
                    enablePollingFallback();
                });
            }

            const nextConnection = pendingJobsEcho?.connector?.pusher?.connection ?? null;
            if (nextConnection && typeof nextConnection.bind === 'function') {
                pendingJobsEchoConnection = nextConnection;
                pendingJobsEchoConnectionStateHandler = ({ current }) => {
                    const state = String(current || '').toLowerCase();

                    if (state === 'connected') {
                        disablePollingFallback();
                        checkPendingJobsVersion({ force: true });
                        return;
                    }

                    if (state === 'connecting' || state === 'initialized') {
                        return;
                    }

                    enablePollingFallback();
                };

                pendingJobsEchoConnection.bind('state_change', pendingJobsEchoConnectionStateHandler);
            }
            return;
        }

        startPendingJobsPolling(document.hidden ? pendingJobsSyncIntervalHiddenMs : pendingJobsSyncIntervalActiveMs);
        checkPendingJobsVersion({ force: true });
    })();
});

onBeforeUnmount(() => {
    stopPendingJobsPolling();

    window.removeEventListener('focus', handlePageFocus);
    document.removeEventListener('visibilitychange', handleVisibilityChange);

    if (pendingJobsEcho) {
        pendingJobsEcho.leave('dashboard.global');
        pendingJobsEcho = null;
    }

    if (pendingJobsEchoConnection && pendingJobsEchoConnectionStateHandler && typeof pendingJobsEchoConnection.unbind === 'function') {
        pendingJobsEchoConnection.unbind('state_change', pendingJobsEchoConnectionStateHandler);
    }
    pendingJobsEchoConnection = null;
    pendingJobsEchoConnectionStateHandler = null;
});

watch(
    () => props.pendingJobs,
    (nextJobs) => {
        pendingJobsState.value = Array.isArray(nextJobs) ? nextJobs : [];
    },
    { immediate: true }
);

watch(
    () => props.pendingJobsCount,
    (count) => {
        if (Number.isFinite(Number(count))) {
            openJobCountState.value = Number(count);
        }
    },
    { immediate: true }
);
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

            <div v-if="openJobCount" class="info-bar" style="margin-bottom:0">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:8px;height:8px;border-radius:50%;background:var(--color-accent-main);animation:pulse 2s infinite"></div>
                    <div class="text-[13px] font-bold text-orange-100">
                        {{ openJobCount }} open job{{ openJobCount > 1 ? 's' : '' }} available for testing
                    </div>
                </div>
                <span class="pill pill-a">Open</span>
            </div>

            <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="flash.success || submitted" class="rounded-xl border border-orange-500/20 bg-orange-500/10 px-5 py-4 text-sm text-orange-100">
                    {{ flash.success || 'Test result saved successfully.' }}
                </div>
            </transition>

            <div v-if="flash.error" class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                {{ flash.error }}
            </div>

            <form ref="editFormRef" @submit.prevent="submit" class="card card-fill" style="margin:0;display:flex;flex-direction:column;">
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
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px">
                                <label class="form-lbl" style="margin-bottom:0">Open Job *</label>
                            </div>
                            <select
                                ref="jobSelectRef"
                                v-model="form.transaction_id"
                                required
                                :disabled="!pendingJobsReady"
                                class="form-inp"
                                style="padding:10px 12px"
                            >
                                <option value="" disabled>{{ pendingJobsReady ? '-- Select Open Job --' : 'Loading open jobs...' }}</option>
                                <option v-for="j in pendingJobOptions" :key="j.transaction_id" :value="String(j.transaction_id)">
                                    {{ j.detail || 'No detail' }}{{ j.cell ? ` (Cell ${j.cell})` : '' }} [{{ j.sender_name || 'Unknown Sender' }}]
                                </option>
                            </select>
                            <div class="mt-1 text-xs text-gray-500">
                                Showing latest open jobs window ({{ pendingJobsWindow }}) for quick selection.
                            </div>
                            <div v-if="form.errors.transaction_id" class="mt-1 text-xs text-red-600">{{ form.errors.transaction_id }}</div>
                        </div>
                        <div>
                            <label class="form-lbl">Inspection Process *</label>
                            <select v-model="form.method_id" required :disabled="!methodOptionsReady" class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>{{ methodOptionsReady ? 'Select method...' : 'Loading methods...' }}</option>
                                <option v-for="m in methodOptions" :key="m.method_id" :value="m.method_id">{{ m.method_name }}</option>
                            </select>
                            <div v-if="form.errors.method_id" class="mt-1 text-xs text-red-600">{{ form.errors.method_id }}</div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-bottom:24px">
                        <div>
                            <label class="form-lbl">Inspector *</label>
                            <select v-model="form.internal_id" required :disabled="!inspectorOptionsReady" class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>{{ inspectorOptionsReady ? 'Select inspector...' : 'Loading inspectors...' }}</option>
                                <option v-for="u in inspectorOptions" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                            </select>
                            <div v-if="form.errors.internal_id" class="mt-1 text-xs text-red-600">{{ form.errors.internal_id }}</div>
                        </div>
                        <div>
                            <label class="form-lbl">Judgement *</label>
                            <div class="radio-grp">
                                <label :class="['radio-card', 'radio-card-option--ok', form.judgement === 'OK' ? 'ok' : '']">
                                    <input type="radio" v-model="form.judgement" value="OK" class="sr-only" required style="display:none" />
                                    <div class="radio-card-badge">OK</div>
                                    <span class="radio-card-label">OK</span>
                                </label>
                                <label :class="['radio-card', 'radio-card-option--ng', form.judgement === 'NG' ? 'ng' : '']">
                                    <input type="radio" v-model="form.judgement" value="NG" class="sr-only" style="display:none" />
                                    <div class="radio-card-badge">NG</div>
                                    <span class="radio-card-label">NG</span>
                                </label>
                            </div>
                            <div v-if="form.errors.judgement" class="mt-1 text-xs text-red-600">{{ form.errors.judgement }}</div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-bottom:24px">
                        <div style="background:rgba(255,255,255,0.03);padding:14px;border-radius:14px;border:1px solid rgba(255,255,255,0.08)">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                                <label class="form-lbl">Start Time *</label>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    <button type="button" @click="setStartNow" class="rounded-md border border-white/10 px-2 py-1 text-xs text-stone-200 hover:bg-orange-500/10">Now</button>
                                    <button type="button" @click="setNowForBoth" class="rounded-md border border-white/10 px-2 py-1 text-xs text-stone-200 hover:bg-orange-500/10">Now + End</button>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px;margin-top:4px">
                                <input v-model="form.start_date" type="date" class="form-inp" required>
                                <input v-model="form.start_time" type="time" class="form-inp" required>
                            </div>
                            <div v-if="form.errors.start_date || form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_date || form.errors.start_time }}</div>
                        </div>
                        <div style="background:rgba(255,255,255,0.03);padding:14px;border-radius:14px;border:1px solid rgba(255,255,255,0.08)">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                                <label class="form-lbl">End Time</label>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    <button type="button" @click="setEndNow" class="rounded-md border border-white/10 px-2 py-1 text-xs text-stone-200 hover:bg-orange-500/10">Now</button>
                                    <button type="button" @click="copyStartToEnd" class="rounded-md border border-white/10 px-2 py-1 text-xs text-stone-200 hover:bg-orange-500/10">Use Start</button>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px;margin-top:4px">
                                <input v-model="form.end_date" type="date" class="form-inp">
                                <input v-model="form.end_time" type="time" class="form-inp">
                            </div>
                            <div v-if="form.errors.end_date || form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_date || form.errors.end_time }}</div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-bottom:24px">
                        <div>
                            <label class="form-lbl">MAX</label>
                            <input
                                v-model="form.max_value"
                                type="text"
                                class="form-inp"
                                style="padding:10px 12px"
                            >
                            <div v-if="form.errors.max_value" class="mt-1 text-xs text-red-600">{{ form.errors.max_value }}</div>
                        </div>
                        <div>
                            <label class="form-lbl">MIN</label>
                            <input
                                v-model="form.min_value"
                                type="text"
                                class="form-inp"
                                style="padding:10px 12px"
                            >
                            <div v-if="form.errors.min_value" class="mt-1 text-xs text-red-600">{{ form.errors.min_value }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="form-lbl">Remark</label>
                        <textarea v-model="form.remark" class="form-inp" style="padding:10px 12px;min-height:60px;resize:vertical"></textarea>
                    </div>
                </div>

                <div style="padding-top:20px;border-top:1px solid rgba(255,255,255,0.08);display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
                    <button type="button" @click="resetForm" class="btn-outline">Clear</button>
                    <button type="submit" :disabled="form.processing" class="btn">
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
                        <select v-model="filterForm.record_state" class="rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <option value="active">Active records</option>
                            <option value="deleted">Deleted records</option>
                            <option value="all">All records</option>
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
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Result</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Job</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Method / Inspector</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Judgement</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-if="resultRows.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No test results found.</td>
                                </tr>
                                <tr v-for="result in resultRows" :key="result.detail_id" class="align-top">
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="font-mono font-semibold text-gray-900">#{{ result.detail_id }}</div>
                                        <div class="mt-1 text-xs text-gray-500">Job #{{ result.transaction_id }}</div>
                                        <div v-if="result.deleted_at" class="mt-1 text-xs text-gray-500">Deleted: {{ result.deleted_at }}</div>
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
                                        <span :class="judgementClass(result)" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                                            {{ result.judgement }}
                                        </span>
                                        <div v-if="result.max_value || result.min_value" class="mt-2 text-xs text-gray-500">
                                            MAX: {{ result.max_value || '-' }} | MIN: {{ result.min_value || '-' }}
                                        </div>
                                        <div v-if="result.remark" class="mt-2 text-xs text-gray-500">{{ result.remark }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button :disabled="!canEditResult(result)" @click="editResult(result)" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">Edit</button>
                                            <button :disabled="!canDeleteResult(result)" @click="deleteResult(result)" class="rounded-lg border border-rose-200 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40" :title="!canDelete ? 'Only admin can delete' : ''">Delete</button>
                                            <button :disabled="!canRestoreResult(result)" @click="restoreResult(result)" class="rounded-lg border border-orange-200 px-3 py-1.5 text-sm text-orange-700 hover:bg-orange-100 disabled:cursor-not-allowed disabled:opacity-40" :title="!canDelete ? 'Only admin can restore' : ''">Restore</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-gray-600">
                            Showing {{ resultPaginator?.from ?? 0 }} to {{ resultPaginator?.to ?? 0 }}
                            <span v-if="typeof resultPaginator?.total === 'number'">of {{ resultPaginator.total }}</span>
                            results
                        </div>
                        <div class="flex flex-wrap justify-end gap-2">
                            <button
                                v-for="(link, index) in resultLinks"
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
