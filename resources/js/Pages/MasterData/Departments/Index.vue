<script setup>
import CrudFormModal from '@/Components/CrudFormModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({ departments: Object, filters: Object });

const showModal = ref(false);
const isEditing = ref(false);
const defaultFilters = {
    search: '',
    per_page: '20',
};
const filterForm = reactive({
    ...defaultFilters,
    search: props.filters?.search ?? defaultFilters.search,
    per_page: String(props.filters?.per_page ?? defaultFilters.per_page),
});
const departmentRows = computed(() => props.departments?.data ?? []);
const departmentLinks = computed(() => props.departments?.links ?? []);
const reloadOnly = ['departments', 'filters', 'flash'];
const invalidateCacheTags = ['master-data', 'master-data:departments', 'master-data:external-users'];

const form = useForm({
    department_id: null,
    department_name: '',
    internal_phone: ''
});

const filterPayload = () => ({
    search: filterForm.search,
    per_page: filterForm.per_page,
});

const applyFilters = () => {
    router.get(route('master-data.departments.index'), filterPayload(), {
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
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const openEditModal = (dept) => {
    isEditing.value = true;
    form.clearErrors();
    form.department_id = dept.department_id;
    form.department_name = dept.department_name;
    form.internal_phone = dept.internal_phone || '';
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('master-data.departments.update', form.department_id), {
            only: reloadOnly,
            invalidateCacheTags,
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
        });
    } else {
        form.post(route('master-data.departments.store'), {
            only: reloadOnly,
            invalidateCacheTags,
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
        });
    }
};

const deleteDepartment = (id) => {
    if (confirm('Are you sure you want to delete this department?')) {
        form.delete(route('master-data.departments.destroy', id), {
            only: reloadOnly,
            invalidateCacheTags,
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Departments - Master Data" />
    <AuthenticatedLayout>
        <template #title>Departments</template>
        <div class="w-full">

            <div class="sm:flex sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">Departments</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage company departments and contact info</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button @click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-black transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        New Department
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="master-data-flash-success mb-4 rounded-lg p-4 border flex items-center gap-3">
                <svg class="master-data-flash-success__icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="master-data-flash-success__text text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg bg-red-50 p-4 border border-red-200 flex items-center gap-3">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                <span class="text-sm font-medium text-red-800">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Search -->
            <div class="mb-4">
                <form @submit.prevent="applyFilters" class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative max-w-sm flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input v-model="filterForm.search" type="text" placeholder="Search department or phone..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent" />
                    </div>
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
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Department Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Internal Phone</th>
                                <th class="px-6 py-3 text-right pr-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-if="departmentRows.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                    {{ filterForm.search ? 'No results for "' + filterForm.search + '"' : 'No departments found.' }}
                                </td>
                            </tr>
                            <tr v-for="dept in departmentRows" :key="dept.department_id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">#{{ dept.department_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ dept.department_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">{{ dept.internal_phone || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right pr-6 text-sm font-medium">
                                    <div class="flex justify-end gap-4">
                                        <button @click="openEditModal(dept)" class="text-gray-900 hover:text-black underline decoration-gray-300 underline-offset-4">Edit</button>
                                        <button @click="deleteDepartment(dept.department_id)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        Showing {{ departments?.from ?? 0 }} to {{ departments?.to ?? 0 }} of {{ departments?.total ?? 0 }} departments
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <button
                            v-for="(link, index) in departmentLinks"
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

        <CrudFormModal :show="showModal" :title="isEditing ? 'Edit Department' : 'New Department'" @close="closeModal">
            <form @submit.prevent="submit">
                <div class="mb-5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Department Name <span class="text-rose-400">*</span></label>
                    <input type="text" v-model="form.department_name" required class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                    <div v-if="form.errors.department_name" class="mt-1.5 text-xs text-rose-400">{{ form.errors.department_name }}</div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Internal Phone</label>
                    <input type="text" v-model="form.internal_phone" class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm font-mono text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                    <div v-if="form.errors.internal_phone" class="mt-1.5 text-xs text-rose-400">{{ form.errors.internal_phone }}</div>
                </div>
            </form>
            <template #actions>
                <button type="button" @click="closeModal" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-medium text-stone-300 hover:bg-white/5">Cancel</button>
                <button type="button" @click="submit" :disabled="form.processing" class="btn px-4 py-2 text-sm disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Save Department' }}
                </button>
            </template>
        </CrudFormModal>
    </AuthenticatedLayout>
</template>
