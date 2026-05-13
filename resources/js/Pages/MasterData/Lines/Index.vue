<script setup>
import CrudFormModal from '@/Components/CrudFormModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({ lines: Object, filters: Object });

const showModal = ref(false);
const isEditing = ref(false);
const defaultFilters = {
    search: '',
    status: 'all',
    per_page: '20',
};
const filterForm = reactive({
    ...defaultFilters,
    search: props.filters?.search ?? defaultFilters.search,
    status: props.filters?.status ?? defaultFilters.status,
    per_page: String(props.filters?.per_page ?? defaultFilters.per_page),
});
const lineRows = computed(() => props.lines?.data ?? []);
const lineLinks = computed(() => props.lines?.links ?? []);
const reloadOnly = ['lines', 'filters', 'flash'];
const invalidateCacheTags = ['master-data', 'master-data:lines', 'workflow', 'receive-job'];
const statusLabels = {
    true: 'Active',
    false: 'Inactive',
};
const statusBadge = {
    true: 'status-pill status-pill--active',
    false: 'status-pill status-pill--inactive',
};

const form = useForm({
    line_id: null,
    line_name: '',
    sort_order: 0,
});

const filterPayload = () => ({
    search: filterForm.search,
    status: filterForm.status,
    per_page: filterForm.per_page,
});

const applyFilters = () => {
    router.get(route('master-data.lines.index'), filterPayload(), {
        only: reloadOnly,
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
    router.visit(url, {
        only: reloadOnly,
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.line_id = null;
    form.sort_order = 0;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const openEditModal = (line) => {
    isEditing.value = true;
    form.clearErrors();
    form.line_id = line.line_id;
    form.line_name = line.line_name;
    form.sort_order = Number(line.sort_order ?? 0);
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('master-data.lines.update', form.line_id), {
            only: reloadOnly,
            invalidateCacheTags,
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
        });
    } else {
        form.post(route('master-data.lines.store'), {
            only: reloadOnly,
            invalidateCacheTags,
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
        });
    }
};

const toggleLineActive = (line) => {
    const nextIsActive = !Boolean(line.is_active);
    const actionLabel = nextIsActive ? 'activate' : 'deactivate';

    if (!confirm(`Are you sure you want to ${actionLabel} this line?`)) {
        return;
    }

    router.patch(route('master-data.lines.set-active', line.line_id), {
        is_active: nextIsActive,
    }, {
        only: reloadOnly,
        invalidateCacheTags,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Lines - Master Data" />
    <AuthenticatedLayout>
        <template #title>Lines</template>
        <div class="w-full">
            <div class="sm:flex sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">Lines</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage production lines available on Receive Job</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button @click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-black transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        New Line
                    </button>
                </div>
            </div>

            <div v-if="$page.props.flash?.success" class="master-data-flash-success mb-4 rounded-lg p-4 border flex items-center gap-3">
                <svg class="master-data-flash-success__icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="master-data-flash-success__text text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg bg-red-50 p-4 border border-red-200 flex items-center gap-3">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                <span class="text-sm font-medium text-red-800">{{ $page.props.flash.error }}</span>
            </div>

            <div class="mb-4">
                <form @submit.prevent="applyFilters" class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative max-w-sm flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input v-model="filterForm.search" type="text" placeholder="Search line name..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent" />
                    </div>
                    <select v-model="filterForm.status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                        <option value="all">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select v-model="filterForm.per_page" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                        <option value="10">10 / page</option>
                        <option value="20">20 / page</option>
                        <option value="50">50 / page</option>
                        <option value="100">100 / page</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="button" @click="resetFilters" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Reset</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-gray-900 hover:bg-black">Apply</button>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Line Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sort</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right pr-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-if="lineRows.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                    {{ filterForm.search ? 'No results for "' + filterForm.search + '"' : 'No lines found.' }}
                                </td>
                            </tr>
                            <tr v-for="line in lineRows" :key="line.line_id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">#{{ line.line_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ line.line_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">{{ line.sort_order ?? 0 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex px-2.5 py-0.5 rounded-md text-xs font-medium', statusBadge[String(Boolean(line.is_active))]]">
                                        {{ statusLabels[String(Boolean(line.is_active))] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right pr-6 text-sm font-medium">
                                    <div class="flex justify-end gap-4">
                                        <button @click="openEditModal(line)" class="text-gray-900 hover:text-black underline decoration-gray-300 underline-offset-4">Edit</button>
                                        <button
                                            @click="toggleLineActive(line)"
                                            :class="Boolean(line.is_active) ? 'text-amber-700 hover:text-amber-900' : 'text-emerald-700 hover:text-emerald-900'"
                                        >
                                            {{ Boolean(line.is_active) ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        Showing {{ lines?.from ?? 0 }} to {{ lines?.to ?? 0 }} of {{ lines?.total ?? 0 }} lines
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <button
                            v-for="(link, index) in lineLinks"
                            :key="index"
                            :disabled="!link.url"
                            @click="visitPage(link.url)"
                            class="rounded-md border px-3 py-1.5 text-sm text-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
                            :class="link.active ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 hover:bg-gray-50'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <CrudFormModal :show="showModal" :title="isEditing ? 'Edit Line' : 'New Line'" @close="closeModal">
            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Line Name <span class="text-rose-400">*</span></label>
                    <input type="text" v-model="form.line_name" required class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                    <div v-if="form.errors.line_name" class="mt-1.5 text-xs text-rose-400">{{ form.errors.line_name }}</div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Sort Order</label>
                    <input type="number" v-model="form.sort_order" min="0" max="999999" class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm font-mono text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                    <div v-if="form.errors.sort_order" class="mt-1.5 text-xs text-rose-400">{{ form.errors.sort_order }}</div>
                </div>
            </form>
            <template #actions>
                <button type="button" @click="closeModal" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-medium text-stone-300 hover:bg-white/5">Cancel</button>
                <button type="button" @click="submit" :disabled="form.processing" class="btn btn-save px-4 py-2 text-sm disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Save Line' }}
                </button>
            </template>
        </CrudFormModal>
    </AuthenticatedLayout>
</template>
