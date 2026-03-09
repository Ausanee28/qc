<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    departments: Array
});

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    department_id: null,
    department_name: '',
    internal_phone: ''
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (department) => {
    isEditing.value = true;
    form.clearErrors();
    form.department_id = department.department_id;
    form.department_name = department.department_name;
    form.internal_phone = department.internal_phone || '';
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('master-data.departments.update', form.department_id), {
            onSuccess: () => { showModal.value = false; }
        });
    } else {
        form.post(route('master-data.departments.store'), {
            onSuccess: () => { showModal.value = false; }
        });
    }
};

const deleteDepartment = (id) => {
    if (confirm('Are you sure you want to delete this department?')) {
        form.delete(route('master-data.departments.destroy', id));
    }
};
</script>

<template>
    <Head title="Departments - Master Data" />
    <AuthenticatedLayout>
        <div class="w-full text-[color:var(--text-bright)] relative" style="background-color: var(--bg-root); min-height: calc(100vh - 54px);">
            
            <!-- PAGE HEADER -->
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-[16px] mb-[28px]">
                <div>
                    <h1 class="text-[1.75rem] font-[800] leading-[1.2] tracking-[-0.025em]" style="color: var(--text-bright);">Departments</h1>
                    <p class="text-[0.875rem] font-normal mt-[4px]" style="color: var(--text-secondary);">Manage company departments and contact info</p>
                </div>
                
                <div class="flex items-center gap-[16px]">
                    <button @click="openCreateModal" class="h-[34px] rounded-[var(--r-sm)] px-[16px] text-[0.8125rem] font-bold transition-all shadow-btn flex items-center gap-2" style="background-color: var(--brand); color: #ffffff;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        New Department
                    </button>
                </div>
            </div>

            <!-- MESSAGES -->
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-[24px] rounded-[var(--r-sm)] p-[16px] flex items-center gap-3" style="background-color: var(--pass-dim); border: 1px solid var(--pass-border); color: var(--pass-bright);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-[0.875rem] font-semibold">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash && $page.props.flash.error" class="mb-[24px] rounded-[var(--r-sm)] p-[16px] flex items-center gap-3" style="background-color: var(--fail-dim); border: 1px solid var(--fail-border); color: var(--fail-bright);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-[0.875rem] font-semibold">{{ $page.props.flash.error }}</span>
            </div>

            <!-- DATA TABLE -->
            <div class="midnight-panel">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-base);">
                                <th class="py-[12px] px-[20px] text-[0.6875rem] tracking-[0.1em] uppercase font-bold" style="color: var(--text-secondary);">ID</th>
                                <th class="py-[12px] px-[20px] text-[0.6875rem] tracking-[0.1em] uppercase font-bold" style="color: var(--text-secondary);">Department Name</th>
                                <th class="py-[12px] px-[20px] text-[0.6875rem] tracking-[0.1em] uppercase font-bold" style="color: var(--text-secondary);">Internal Phone</th>
                                <th class="py-[12px] px-[20px] text-[0.6875rem] tracking-[0.1em] uppercase font-bold text-right" style="color: var(--text-secondary);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="departments.length === 0">
                                <td colspan="4" class="py-[32px] text-center text-[0.875rem]" style="color: var(--text-dim);">No departments found.</td>
                            </tr>
                            <tr v-for="dept in departments" :key="dept.department_id" class="table-row">
                                <td class="py-[14px] px-[20px] text-[0.875rem] mono" style="color: var(--text-dim);">#{{ dept.department_id }}</td>
                                <td class="py-[14px] px-[20px] text-[0.875rem] font-semibold" style="color: var(--text-bright);">{{ dept.department_name }}</td>
                                <td class="py-[14px] px-[20px] text-[0.875rem] mono" style="color: var(--text-primary);">{{ dept.internal_phone || '-' }}</td>
                                <td class="py-[14px] px-[20px] text-right flex justify-end gap-3">
                                    <button @click="openEditModal(dept)" class="text-[0.8125rem] font-semibold hover:underline" style="color: var(--brand-bright);">Edit</button>
                                    <button @click="deleteDepartment(dept.department_id)" class="text-[0.8125rem] font-semibold hover:underline" style="color: var(--fail-bright);">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="w-full max-w-md rounded-[var(--r-lg)] p-[28px] relative overflow-hidden shadow-[var(--shadow-hover)]" style="background-color: var(--bg-panel); border: 1px solid var(--border-bright);">
                <!-- Glowing top border -->
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background-color: var(--brand);"></div>
                
                <h2 class="text-[1.25rem] font-bold mb-[24px]" style="color: var(--text-bright);">{{ isEditing ? 'Edit Department' : 'New Department' }}</h2>
                
                <form @submit.prevent="submit">
                    <div class="mb-[20px]">
                        <label class="block text-[0.75rem] font-semibold mb-[8px] tracking-wide uppercase" style="color: var(--text-secondary);">Department Name <span class="text-red-500">*</span></label>
                        <input type="text" v-model="form.department_name" required
                            class="w-full h-[40px] px-[12px] rounded-[var(--r-sm)] text-[0.875rem] font-medium transition-all focus:outline-none"
                            style="background-color: var(--bg-elevated); border: 1px solid var(--border-base); color: var(--text-bright);"
                            onfocus="this.style.borderColor='var(--brand)'; this.style.boxShadow='0 0 0 3px var(--brand-glow)';"
                            onblur="this.style.borderColor='var(--border-base)'; this.style.boxShadow='none';"
                        />
                        <div v-if="form.errors.department_name" class="text-[0.75rem] mt-2" style="color: var(--fail-bright);">{{ form.errors.department_name }}</div>
                    </div>
                    
                    <div class="mb-[28px]">
                        <label class="block text-[0.75rem] font-semibold mb-[8px] tracking-wide uppercase" style="color: var(--text-secondary);">Internal Phone</label>
                        <input type="text" v-model="form.internal_phone" 
                            class="w-full h-[40px] px-[12px] rounded-[var(--r-sm)] text-[0.875rem] font-medium transition-all focus:outline-none mono"
                            style="background-color: var(--bg-elevated); border: 1px solid var(--border-base); color: var(--text-bright);"
                            onfocus="this.style.borderColor='var(--brand)'; this.style.boxShadow='0 0 0 3px var(--brand-glow)';"
                            onblur="this.style.borderColor='var(--border-base)'; this.style.boxShadow='none';"
                        />
                        <div v-if="form.errors.internal_phone" class="text-[0.75rem] mt-2" style="color: var(--fail-bright);">{{ form.errors.internal_phone }}</div>
                    </div>

                    <div class="flex justify-end gap-[12px]">
                        <button type="button" @click="showModal = false" class="h-[36px] px-[16px] rounded-[var(--r-sm)] text-[0.8125rem] font-semibold transition-colors" style="color: var(--text-primary); background-color: transparent; border: 1px solid var(--border-base);" onmouseover="this.style.backgroundColor='var(--bg-elevated)';" onmouseout="this.style.backgroundColor='transparent';">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="h-[36px] px-[20px] rounded-[var(--r-sm)] text-[0.8125rem] font-bold transition-all shadow-btn disabled:opacity-50" style="background-color: var(--brand); color: #ffffff;">
                            {{ form.processing ? 'Saving...' : 'Save Department' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
/* ── Color System: Midnight Vivid ── */
:root {
  --bg-root:      #0b0f1a;
  --bg-panel:     #111827;
  --bg-elevated:  #1a2235;
  --border-base:  rgba(255,255,255,0.08);
  --border-bright: rgba(255,255,255,0.14);
  --text-bright:   #f0f6ff;
  --text-primary:  #c8d8ee;
  --text-secondary:#7a90ad;
  --text-dim:      #4a5a70;
  --brand:         #3b82f6;
  --brand-glow:    rgba(59,130,246,0.20);
  --brand-bright:  #60a5fa;
  --pass-dim:      rgba(16,185,129,0.15);
  --pass-bright:   #34d399;
  --pass-border:   rgba(16,185,129,0.30);
  --fail-dim:      rgba(244,63,94,0.15);
  --fail-bright:   #fb7185;
  --fail-border:   rgba(244,63,94,0.30);
  --r-sm: 8px; --r-lg: 16px;
  --shadow-card:   0 4px 20px rgba(0,0,0,0.35), 0 1px 3px rgba(0,0,0,0.20);
  --shadow-hover:  0 8px 32px rgba(0,0,0,0.45), 0 2px 8px rgba(0,0,0,0.25);
}

.mono { font-family: 'JetBrains Mono', monospace; }

.midnight-panel {
    background-color: var(--bg-panel);
    border: 1px solid var(--border-base);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-card);
    overflow: hidden;
}

.table-row {
    border-bottom: 1px solid var(--border-base);
    transition: background-color 150ms ease;
}
.table-row:last-child {
    border-bottom: none;
}
.table-row:hover {
    background-color: var(--bg-elevated);
}

.shadow-btn {
    box-shadow: 0 0 16px rgba(59,130,246,0.25);
}
.shadow-btn:hover {
    box-shadow: 0 0 24px rgba(59,130,246,0.40);
    transform: translateY(-1px);
}
</style>
