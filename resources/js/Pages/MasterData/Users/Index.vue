<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array
});

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    user_id: null,
    employee_id: '',
    name: '',
    email: '',
    user_name: '',
    password: '',
    role: 'qc'
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
    form.user_id = user.user_id;
    form.employee_id = user.employee_id;
    form.name = user.name;
    form.email = user.email || '';
    form.user_name = user.user_name;
    form.password = ''; // intentionally blank, only fill if changing
    form.role = user.role;
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('master-data.users.update', form.user_id), {
            onSuccess: () => { showModal.value = false; }
        });
    } else {
        form.post(route('master-data.users.store'), {
            onSuccess: () => { showModal.value = false; }
        });
    }
};

const deleteUser = (id) => {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        form.delete(route('master-data.users.destroy', id));
    }
};
</script>

<template>
    <Head title="Users - Master Data" />
    <AuthenticatedLayout>
        <div class="w-full">
            
            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">Internal Users</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage system access and privileges</p>
                </div>
                
                <div class="mt-4 sm:mt-0 flex items-center gap-4">
                    <button @click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-black transition-colors focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        New User
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
                                <th scope="col" class="px-6 py-3 text-left pl-6 text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Emp ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Username</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-right pr-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-if="users.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No users found.</td>
                            </tr>
                            <tr v-for="user in users" :key="user.user_id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ user.employee_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center text-xs font-bold text-white">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ user.user_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ user.email || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="user.role === 'admin'" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold tracking-wide bg-zinc-200 text-zinc-800 border border-zinc-300">ADMIN</span>
                                    <span v-else class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold tracking-wide bg-gray-100 text-gray-600 border border-gray-200">QC</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right pr-6 text-sm font-medium">
                                    <div class="flex justify-end gap-4">
                                        <button @click="openEditModal(user)" class="text-gray-900 hover:text-black transition-colors underline decoration-gray-300 underline-offset-4">Edit</button>
                                        <button @click="deleteUser(user.user_id)" class="text-red-600 hover:text-red-900 transition-colors">Delete</button>
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
                
                <h2 class="text-xl font-bold text-gray-900 mb-6 mt-2">{{ isEditing ? 'Edit User' : 'New User' }}</h2>
                
                <form @submit.prevent="submit">
                    <!-- Basic Info -->
                    <div class="flex gap-4 mb-5">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Employee ID <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.employee_id" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 font-mono focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all"
                            />
                            <div v-if="form.errors.employee_id" class="text-xs text-red-600 mt-1.5">{{ form.errors.employee_id }}</div>
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Role <span class="text-red-500">*</span></label>
                            <select v-model="form.role" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all"
                            >
                                <option value="qc">QC Technician</option>
                                <option value="admin">Administrator</option>
                            </select>
                            <div v-if="form.errors.role" class="text-xs text-red-600 mt-1.5">{{ form.errors.role }}</div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" v-model="form.name" required
                            class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all"
                        />
                        <div v-if="form.errors.name" class="text-xs text-red-600 mt-1.5">{{ form.errors.name }}</div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Email</label>
                        <input type="email" v-model="form.email" 
                            class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all"
                        />
                        <div v-if="form.errors.email" class="text-xs text-red-600 mt-1.5">{{ form.errors.email }}</div>
                    </div>

                    <!-- Auth Block -->
                    <div class="mt-6 pt-5 border-t border-gray-100">
                        <div class="mb-5">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Login Username <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.user_name" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 font-bold font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all"
                            />
                            <div v-if="form.errors.user_name" class="text-xs text-red-600 mt-1.5">{{ form.errors.user_name }}</div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Password <span v-if="!isEditing" class="text-red-500">*</span></label>
                            <input type="password" v-model="form.password" :required="!isEditing" :placeholder="isEditing ? 'Leave blank to keep current' : 'Min 8 characters'"
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all"
                            />
                            <div v-if="form.errors.password" class="text-xs text-red-600 mt-1.5">{{ form.errors.password }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
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
