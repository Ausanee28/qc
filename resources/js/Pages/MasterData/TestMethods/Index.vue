<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({ testMethods: Object, equipments: Array, filters: Object });

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
const equipmentOptions = computed(() => props.equipments ?? []);
const equipmentOptionsReady = computed(() => Array.isArray(props.equipments));
const methodRows = computed(() => props.testMethods?.data ?? []);
const methodLinks = computed(() => props.testMethods?.links ?? []);
const reloadOnly = ['testMethods', 'filters', 'flash'];

const form = useForm({
    method_id: null,
    method_name: '',
    equipment_id: ''
});

const filterPayload = () => ({
    search: filterForm.search,
    per_page: filterForm.per_page,
});

const applyFilters = () => {
    router.get(route('master-data.test-methods.index'), filterPayload(), {
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

const openEditModal = (method) => {
    isEditing.value = true;
    form.clearErrors();
    form.method_id = method.method_id;
    form.method_name = method.method_name;
    form.equipment_id = method.equipment_id;
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('master-data.test-methods.update', form.method_id), {
            only: reloadOnly,
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
        });
    } else {
        form.post(route('master-data.test-methods.store'), {
            only: reloadOnly,
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
        });
    }
};

const deleteMethod = (id) => {
    if (confirm('Are you sure you want to delete this test method?')) {
        form.delete(route('master-data.test-methods.destroy', id), {
            only: reloadOnly,
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Test Methods - Master Data" />
    <AuthenticatedLayout>
        <template #title>Test Methods</template>
        <div class="w-full">

            <div class="sm:flex sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">Test Methods</h1>
                    <p class="text-sm text-gray-500 mt-1">Configure testing methodologies and instrument links</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button @click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-black transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        New Method
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg bg-green-50 p-4 border border-green-200 flex items-center gap-3">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="text-sm font-medium text-green-800">{{ $page.props.flash.success }}</span>
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
                        <input v-model="filterForm.search" type="text" placeholder="Search method or equipment..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent" />
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
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Method Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Parent Equipment</th>
                                <th class="px-6 py-3 text-right pr-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-if="methodRows.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                    {{ filterForm.search ? 'No results for "' + filterForm.search + '"' : 'No test methods found.' }}
                                </td>
                            </tr>
                            <tr v-for="method in methodRows" :key="method.method_id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">#{{ method.method_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ method.method_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ method.equipment?.equipment_name ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right pr-6 text-sm font-medium">
                                    <div class="flex justify-end gap-4">
                                        <button @click="openEditModal(method)" class="text-gray-900 hover:text-black underline decoration-gray-300 underline-offset-4">Edit</button>
                                        <button @click="deleteMethod(method.method_id)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        Showing {{ testMethods.from ?? 0 }} to {{ testMethods.to ?? 0 }} of {{ testMethods.total ?? 0 }} methods
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <button
                            v-for="(link, index) in methodLinks"
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

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md z-10 p-6">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gray-900 rounded-t-xl"></div>
                <h2 class="text-xl font-bold text-gray-900 mb-6 mt-2">{{ isEditing ? 'Edit Method' : 'New Method' }}</h2>
                <form @submit.prevent="submit">
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Method Name <span class="text-red-500">*</span></label>
                        <input type="text" v-model="form.method_name" required class="w-full h-10 px-3 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900" />
                        <div v-if="form.errors.method_name" class="text-xs text-red-600 mt-1.5">{{ form.errors.method_name }}</div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Link to Equipment <span class="text-red-500">*</span></label>
                        <select v-model="form.equipment_id" required :disabled="!equipmentOptionsReady" class="w-full h-10 px-3 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 disabled:cursor-wait disabled:bg-gray-100">
                            <option value="" disabled>{{ equipmentOptionsReady ? 'Select Equipment...' : 'Loading equipment...' }}</option>
                            <option v-for="eq in equipmentOptions" :key="eq.equipment_id" :value="eq.equipment_id">{{ eq.equipment_name }}</option>
                        </select>
                        <div v-if="form.errors.equipment_id" class="text-xs text-red-600 mt-1.5">{{ form.errors.equipment_id }}</div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-gray-900 hover:bg-black disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Save Method' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
