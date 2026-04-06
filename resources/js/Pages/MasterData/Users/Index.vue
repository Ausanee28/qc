<script setup>
import CrudFormModal from '@/Components/CrudFormModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({ users: Object, filters: Object });

const showModal = ref(false);
const isEditing = ref(false);
const showResetModal = ref(false);
const resetTarget = ref(null);
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

const roleLabels = { admin: 'Admin', engineer: 'Engineer', inspector: 'Inspector' };
const roleBadge = {
    admin: 'users-pill users-pill--admin',
    engineer: 'users-pill users-pill--engineer',
    inspector: 'users-pill users-pill--inspector',
};
const statusLabels = {
    true: 'Active',
    false: 'Inactive',
};
const statusBadge = {
    true: 'users-pill users-pill--active',
    false: 'users-pill users-pill--inactive',
};
const userRows = computed(() => props.users?.data ?? []);
const userLinks = computed(() => props.users?.links ?? []);
const reloadOnly = ['users', 'filters', 'flash'];
const invalidateCacheTags = ['master-data', 'master-data:users', 'workflow'];
const editableRoles = ['admin', 'inspector'];

const form = useForm({
    user_id: null,
    user_name: '',
    name: '',
    employee_id: '',
    role: 'inspector',
    is_active: true,
    password: '',
    password_confirmation: '',
});

const resetForm = useForm({
    employee_id: '',
    password: '',
    password_confirmation: '',
});

const filterPayload = () => ({
    search: filterForm.search,
    status: filterForm.status,
    per_page: filterForm.per_page,
});

const applyFilters = () => {
    router.get(route('master-data.users.index'), filterPayload(), {
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
    form.role = 'inspector';
    form.is_active = true;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const openResetModal = (user) => {
    resetTarget.value = user;
    resetForm.reset();
    resetForm.clearErrors();
    showResetModal.value = true;
};

const closeResetModal = () => {
    showResetModal.value = false;
    resetTarget.value = null;
};

const openEditModal = (user) => {
    isEditing.value = true;
    form.clearErrors();
    form.user_id = user.user_id;
    form.user_name = user.user_name;
    form.name = user.name || '';
    form.employee_id = user.employee_id || '';
    form.role = editableRoles.includes(user.role) ? user.role : 'inspector';
    form.is_active = Boolean(user.is_active);
    form.password = '';
    form.password_confirmation = '';
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('master-data.users.update', form.user_id), {
            only: reloadOnly,
            invalidateCacheTags,
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
        });
    } else {
        form.post(route('master-data.users.store'), {
            only: reloadOnly,
            invalidateCacheTags,
            preserveScroll: true,
            onSuccess: () => { closeModal(); },
        });
    }
};

const deleteUser = (id) => {
    if (confirm('Are you sure you want to delete this user?')) {
        form.delete(route('master-data.users.destroy', id), {
            only: reloadOnly,
            invalidateCacheTags,
            preserveScroll: true,
        });
    }
};

const toggleUserActive = (user) => {
    const nextIsActive = !Boolean(user.is_active);
    const actionLabel = nextIsActive ? 'activate' : 'deactivate';

    if (!confirm(`Are you sure you want to ${actionLabel} this user?`)) {
        return;
    }

    router.patch(route('master-data.users.set-active', user.user_id), {
        is_active: nextIsActive,
    }, {
        only: reloadOnly,
        invalidateCacheTags,
        preserveScroll: true,
    });
};

const submitReset = () => {
    if (!resetTarget.value) return;

    resetForm.post(route('master-data.users.reset-password', resetTarget.value.user_id), {
        only: reloadOnly,
        invalidateCacheTags,
        preserveScroll: true,
        onSuccess: () => { closeResetModal(); },
    });
};
</script>

<template>
    <Head title="Users - Master Data" />
    <AuthenticatedLayout>
        <template #title>Users</template>
        <div class="w-full">

            <div class="sm:flex sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">Internal Users</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage QC lab staff accounts and roles</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button @click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-black transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        New User
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="users-flash users-flash--success mb-4 rounded-lg border p-4 flex items-center gap-3">
                <svg class="users-flash__icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="users-flash__text text-sm font-semibold">{{ $page.props.flash.success }}</span>
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
                        <input v-model="filterForm.search" type="text" placeholder="Search name, username, employee ID, or role..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent" />
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
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right pr-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-if="userRows.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                    {{ filterForm.search ? 'No results for "' + filterForm.search + '"' : 'No users found.' }}
                                </td>
                            </tr>
                            <tr v-for="user in userRows" :key="user.user_id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">#{{ user.user_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="avatar flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                                            {{ (user.name || user.user_name).charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ user.name || '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ user.user_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">{{ user.user_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">{{ user.employee_id || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex px-2.5 py-0.5 rounded-md text-xs font-medium border', roleBadge[user.role] || 'users-pill users-pill--neutral']">
                                        {{ roleLabels[user.role] || user.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex px-2.5 py-0.5 rounded-md text-xs font-medium border', statusBadge[String(Boolean(user.is_active))]]">
                                        {{ statusLabels[String(Boolean(user.is_active))] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right pr-6 text-sm font-medium">
                                    <div class="ml-auto grid w-full max-w-[30rem] grid-cols-4 gap-2">
                                        <button @click="openEditModal(user)" class="inline-flex w-full justify-center whitespace-nowrap text-xs font-semibold text-gray-900 underline decoration-gray-300 underline-offset-4 hover:text-black">Edit</button>
                                        <button
                                            @click="toggleUserActive(user)"
                                            class="users-action-button inline-flex w-full justify-center whitespace-nowrap text-xs font-semibold underline underline-offset-4"
                                            :class="Boolean(user.is_active) ? 'users-action--deactivate' : 'users-action--activate'"
                                        >
                                            {{ Boolean(user.is_active) ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button @click="openResetModal(user)" class="inline-flex w-full justify-center whitespace-nowrap text-xs font-semibold text-blue-700 hover:text-blue-900 underline decoration-blue-300 underline-offset-4">Reset Password</button>
                                        <button @click="deleteUser(user.user_id)" class="inline-flex w-full justify-center whitespace-nowrap text-xs font-semibold text-red-600 hover:text-red-900 underline decoration-red-300 underline-offset-4">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        Showing {{ users?.from ?? 0 }} to {{ users?.to ?? 0 }} of {{ users?.total ?? 0 }} users
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <button
                            v-for="(link, index) in userLinks"
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

        <CrudFormModal :show="showModal" :title="isEditing ? 'Edit User' : 'New User'" max-width="2xl" @close="closeModal">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Full Name <span class="text-rose-400">*</span></label>
                        <input type="text" v-model="form.name" required class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Username <span class="text-rose-400">*</span></label>
                        <input type="text" v-model="form.user_name" required class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm font-mono text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                        <div v-if="form.errors.user_name" class="mt-1 text-xs text-rose-400">{{ form.errors.user_name }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Employee ID</label>
                        <input type="text" v-model="form.employee_id" class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm font-mono text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                        <div v-if="form.errors.employee_id" class="mt-1 text-xs text-rose-400">{{ form.errors.employee_id }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Role <span class="text-rose-400">*</span></label>
                        <select v-model="form.role" required class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50">
                            <option value="inspector">Inspector</option>
                            <option value="admin">Admin</option>
                        </select>
                        <div v-if="form.errors.role" class="mt-1 text-xs text-rose-400">{{ form.errors.role }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Account Status</label>
                        <select v-model="form.is_active" class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50">
                            <option :value="true">Active</option>
                            <option :value="false">Inactive</option>
                        </select>
                    </div>
                </div>
                <div v-if="!isEditing" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">
                            Password <span class="text-rose-400">*</span>
                        </label>
                        <input type="password" v-model="form.password" required class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                        <div v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Confirm Password</label>
                        <input type="password" v-model="form.password_confirmation" :required="!!form.password" class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                    </div>
                </div>
                <div v-else class="rounded-xl border border-orange-500/15 bg-orange-500/10 p-4 text-sm text-orange-100">
                    Password changes are handled from <span class="font-semibold">Reset Password</span> to keep admin actions consistent and auditable.
                </div>
            </form>
            <template #actions>
                <button type="button" @click="closeModal" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-medium text-stone-300 hover:bg-white/5">Cancel</button>
                <button type="button" @click="submit" :disabled="form.processing" class="btn px-4 py-2 text-sm disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Save User' }}
                </button>
            </template>
        </CrudFormModal>

        <CrudFormModal :show="showResetModal" title="Admin Reset Password" max-width="lg" @close="closeResetModal">
            <form @submit.prevent="submitReset">
                <div class="space-y-5">
                    <div class="rounded-xl border border-orange-500/15 bg-orange-500/10 p-4 text-sm text-orange-100">
                        <div class="font-semibold">{{ resetTarget?.name || '-' }}</div>
                        <div class="mt-1 text-orange-100/75">Username: {{ resetTarget?.user_name || '-' }}</div>
                        <div class="mt-1 text-orange-100/75">Employee ID on record: {{ resetTarget?.employee_id || 'Not set' }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Confirm Employee ID <span class="text-rose-400">*</span></label>
                        <input
                            type="text"
                            v-model="resetForm.employee_id"
                            required
                            placeholder="Enter employee ID to confirm"
                            class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm font-mono text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50"
                        />
                        <div v-if="resetForm.errors.employee_id" class="mt-1 text-xs text-rose-400">{{ resetForm.errors.employee_id }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">New Password <span class="text-rose-400">*</span></label>
                            <input type="password" v-model="resetForm.password" required class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                            <div v-if="resetForm.errors.password" class="mt-1 text-xs text-rose-400">{{ resetForm.errors.password }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-stone-300 mb-2">Confirm Password <span class="text-rose-400">*</span></label>
                            <input type="password" v-model="resetForm.password_confirmation" required class="w-full h-10 rounded-lg border border-white/10 bg-white/5 px-3 text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500/50" />
                        </div>
                    </div>
                </div>
            </form>
            <template #actions>
                <button type="button" @click="closeResetModal" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-medium text-stone-300 hover:bg-white/5">Cancel</button>
                <button type="button" @click="submitReset" :disabled="resetForm.processing || !resetTarget?.employee_id" class="btn px-4 py-2 text-sm disabled:opacity-50">
                    {{ resetForm.processing ? 'Resetting...' : 'Reset Password' }}
                </button>
            </template>
        </CrudFormModal>
    </AuthenticatedLayout>
</template>

<style scoped>
.users-pill {
    font-weight: 700;
}

:global(.theme-shell[data-theme='dark'] .users-pill--admin) {
    background: rgba(251, 146, 60, 0.16) !important;
    border-color: rgba(251, 146, 60, 0.56) !important;
    color: #fed7aa !important;
}

:global(.theme-shell[data-theme='dark'] .users-pill--engineer) {
    background: rgba(59, 130, 246, 0.18) !important;
    border-color: rgba(59, 130, 246, 0.56) !important;
    color: #bfdbfe !important;
}

:global(.theme-shell[data-theme='dark'] .users-pill--inspector) {
    background: rgba(226, 232, 240, 0.18) !important;
    border-color: rgba(226, 232, 240, 0.52) !important;
    color: #f1f5f9 !important;
}

:global(.theme-shell[data-theme='dark'] .users-pill--active) {
    background: rgba(34, 197, 94, 0.18) !important;
    border-color: rgba(34, 197, 94, 0.58) !important;
    color: #bbf7d0 !important;
}

:global(.theme-shell[data-theme='dark'] .users-pill--inactive) {
    background: rgba(244, 63, 94, 0.18) !important;
    border-color: rgba(244, 63, 94, 0.58) !important;
    color: #fecdd3 !important;
}

:global(.theme-shell[data-theme='dark'] .users-pill--neutral) {
    background: rgba(148, 163, 184, 0.2) !important;
    border-color: rgba(148, 163, 184, 0.56) !important;
    color: #e2e8f0 !important;
}

:global(.theme-shell[data-theme='light'] .users-pill--admin) {
    background: rgba(255, 237, 213, 0.95) !important;
    border-color: rgba(251, 146, 60, 0.35) !important;
    color: #c2410c !important;
}

:global(.theme-shell[data-theme='light'] .users-pill--engineer) {
    background: rgba(224, 242, 254, 0.95) !important;
    border-color: rgba(59, 130, 246, 0.32) !important;
    color: #1d4ed8 !important;
}

:global(.theme-shell[data-theme='light'] .users-pill--inspector) {
    background: rgba(241, 245, 249, 0.96) !important;
    border-color: rgba(148, 163, 184, 0.34) !important;
    color: #334155 !important;
}

:global(.theme-shell[data-theme='light'] .users-pill--active) {
    background: rgba(220, 252, 231, 0.96) !important;
    border-color: rgba(34, 197, 94, 0.34) !important;
    color: #15803d !important;
}

:global(.theme-shell[data-theme='light'] .users-pill--inactive) {
    background: rgba(255, 228, 230, 0.96) !important;
    border-color: rgba(244, 63, 94, 0.34) !important;
    color: #be123c !important;
}

:global(.theme-shell[data-theme='light'] .users-pill--neutral) {
    background: rgba(241, 245, 249, 0.96) !important;
    border-color: rgba(148, 163, 184, 0.34) !important;
    color: #334155 !important;
}

:global(.theme-shell[data-theme='light'] .users-flash--success) {
    background: #dcfce7 !important;
    border-color: #86efac !important;
}

:global(.theme-shell[data-theme='light'] .users-flash--success .users-flash__icon) {
    color: #16a34a !important;
}

:global(.theme-shell[data-theme='light'] .users-flash--success .users-flash__text) {
    color: #166534 !important;
}

:global(.theme-shell[data-theme='dark'] .users-flash--success) {
    background: rgba(34, 197, 94, 0.18) !important;
    border-color: rgba(74, 222, 128, 0.42) !important;
}

:global(.theme-shell[data-theme='dark'] .users-flash--success .users-flash__icon) {
    color: #4ade80 !important;
}

:global(.theme-shell[data-theme='dark'] .users-flash--success .users-flash__text) {
    color: #bbf7d0 !important;
}

:global(.theme-shell[data-theme='light'] .users-action--activate) {
    color: #047857 !important;
    text-decoration-color: rgba(5, 150, 105, 0.45) !important;
}

:global(.theme-shell[data-theme='light'] .users-action--activate:hover) {
    color: #065f46 !important;
}

:global(.theme-shell[data-theme='dark'] .users-action--activate) {
    color: #4ade80 !important;
    text-decoration-color: rgba(74, 222, 128, 0.6) !important;
}

:global(.theme-shell[data-theme='dark'] .users-action--activate:hover) {
    color: #86efac !important;
}

:global(.theme-shell[data-theme='light'] .users-action--deactivate) {
    color: #b45309 !important;
    text-decoration-color: rgba(245, 158, 11, 0.5) !important;
}

:global(.theme-shell[data-theme='light'] .users-action--deactivate:hover) {
    color: #92400e !important;
}

:global(.theme-shell[data-theme='dark'] .users-action--deactivate) {
    color: #fbbf24 !important;
    text-decoration-color: rgba(251, 191, 36, 0.6) !important;
}

:global(.theme-shell[data-theme='dark'] .users-action--deactivate:hover) {
    color: #fcd34d !important;
}
</style>
