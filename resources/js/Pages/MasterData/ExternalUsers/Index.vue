<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    externalUsers: Array,
    departments: Array
});

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    external_id: null,
    external_name: '',
    department_id: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    form.clearErrors();
    form.external_id = user.external_id;
    form.external_name = user.external_name;
    form.department_id = user.department_id;
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('master-data.external-users.update', form.external_id), {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post(route('master-data.external-users.store'), {
            onSuccess: () => showModal.value = false,
        });
    }
};

const deleteUser = (id) => {
    if (confirm('Are you sure you want to delete this external user?')) {
        router.delete(route('master-data.external-users.destroy', id));
    }
};
</script>

<template>
    <Head title="External Users - Master Data" />
    <AuthenticatedLayout>
        <div class="w-full">
            
            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">External Users</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage external clients and business partners</p>
                </div>
                
                <div class="mt-4 sm:mt-0 flex items-center gap-4">
                    <button @click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-black transition-colors focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        New External User
                    </button>
                </div>
            </div>

            <!-- MESSAGES -->
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    <span class="text-sm font-medium text-green-800">{{ $page.props.flash.success }}</span>
                </div>
            </div>
            <div v-if="$page.props.flash && $page.props.flash.error" class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                    <span class="text-sm font-medium text-red-800">{{ $page.props.flash.error }}</span>
                </div>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left pl-6 text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">External Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Department</th>
                                <th scope="col" class="px-6 py-3 text-right pr-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-if="externalUsers.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">No external users found.</td>
                            </tr>
                            <tr v-for="exUser in externalUsers" :key="exUser.external_id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">#{{ exUser.external_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ exUser.external_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ exUser.department ? exUser.department.department_name : 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right pr-6 text-sm font-medium">
                                    <div class="flex justify-end gap-4">
                                        <button @click="openEditModal(exUser)" class="text-gray-900 hover:text-black transition-colors underline decoration-gray-300 underline-offset-4">Edit</button>
                                        <button @click="deleteUser(exUser.external_id)" class="text-red-600 hover:text-red-900 transition-colors">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- MODAL OVERLAY AND CONTENT -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

            <!-- Modal Panel -->
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden z-10 p-6">
                <!-- Top decoration border -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-gray-900"></div>
                
                <h2 class="text-xl font-bold text-gray-900 mb-6 mt-2">{{ isEditing ? 'Edit External User' : 'New External User' }}</h2>
                
                <form @submit.prevent="submit">
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">External Name <span class="text-red-500">*</span></label>
                        <input type="text" v-model="form.external_name" required
                            class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all"
                        />
                        <div v-if="form.errors.external_name" class="text-xs text-red-600 mt-1.5">{{ form.errors.external_name }}</div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Department <span class="text-red-500">*</span></label>
                        <select v-model="form.department_id" required
                            class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all"
                        >
                            <option value="" disabled>Select Department...</option>
                            <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">{{ dept.department_name }}</option>
                        </select>
                        <div v-if="form.errors.department_id" class="text-xs text-red-600 mt-1.5">{{ form.errors.department_id }}</div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-gray-900 shadow-sm hover:bg-black focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Saving...' : 'Save User' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
