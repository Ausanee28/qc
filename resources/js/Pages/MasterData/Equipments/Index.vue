<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    equipments: Array
});

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    equipment_id: null,
    equipment_name: ''
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (equipment) => {
    isEditing.value = true;
    form.clearErrors();
    form.equipment_id = equipment.equipment_id;
    form.equipment_name = equipment.equipment_name;
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('master-data.equipments.update', form.equipment_id), {
            onSuccess: () => { showModal.value = false; }
        });
    } else {
        form.post(route('master-data.equipments.store'), {
            onSuccess: () => { showModal.value = false; }
        });
    }
};

const deleteEquipment = (id) => {
    if (confirm('Are you sure you want to delete this equipment? Cannot be undone.')) {
        form.delete(route('master-data.equipments.destroy', id));
    }
};
</script>

<template>
    <Head title="Equipment - Master Data" />
    <AuthenticatedLayout>
        <div class="px-[28px] py-[28px] max-w-[1600px] mx-auto text-[color:var(--text-bright)] relative" style="background-color: var(--bg-root); min-height: calc(100vh - 54px);">
            
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-[16px] mb-[28px]">
                <div>
                    <h1 class="text-[1.75rem] font-[800] leading-[1.2] tracking-[-0.025em]" style="color: var(--text-bright);">Equipment</h1>
                    <p class="text-[0.875rem] font-normal mt-[4px]" style="color: var(--text-secondary);">Manage laboratory testing equipment and rigs</p>
                </div>
                
                <div class="flex items-center gap-[16px]">
                    <button @click="openCreateModal" class="h-[34px] rounded-[var(--r-sm)] px-[16px] text-[0.8125rem] font-bold transition-all shadow-btn flex items-center gap-2" style="background-color: var(--brand); color: #ffffff;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        New Equipment
                    </button>
                </div>
            </div>

            <!-- MESSAGES -->
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-[24px] rounded-[var(--r-sm)] p-[16px] flex items-center gap-3" style="background-color: var(--pass-dim); border: 1px solid var(--pass-border); color: var(--pass-bright);">
                <span class="text-[0.875rem] font-semibold">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash && $page.props.flash.error" class="mb-[24px] rounded-[var(--r-sm)] p-[16px] flex items-center gap-3" style="background-color: var(--fail-dim); border: 1px solid var(--fail-border); color: var(--fail-bright);">
                <span class="text-[0.875rem] font-semibold">{{ $page.props.flash.error }}</span>
            </div>

            <div class="midnight-panel">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-base);">
                                <th class="py-[12px] px-[20px] text-[0.6875rem] tracking-[0.1em] uppercase font-bold w-24" style="color: var(--text-secondary);">ID</th>
                                <th class="py-[12px] px-[20px] text-[0.6875rem] tracking-[0.1em] uppercase font-bold" style="color: var(--text-secondary);">Equipment Name</th>
                                <th class="py-[12px] px-[20px] text-[0.6875rem] tracking-[0.1em] uppercase font-bold text-right w-32" style="color: var(--text-secondary);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="equipments.length === 0">
                                <td colspan="3" class="py-[32px] text-center text-[0.875rem]" style="color: var(--text-dim);">No equipment found.</td>
                            </tr>
                            <tr v-for="eq in equipments" :key="eq.equipment_id" class="table-row">
                                <td class="py-[14px] px-[20px] text-[0.875rem] mono" style="color: var(--text-dim);">#{{ eq.equipment_id }}</td>
                                <td class="py-[14px] px-[20px] text-[0.875rem] font-semibold" style="color: var(--text-bright);">{{ eq.equipment_name }}</td>
                                <td class="py-[14px] px-[20px] text-right flex justify-end gap-3">
                                    <button @click="openEditModal(eq)" class="text-[0.8125rem] font-semibold hover:underline" style="color: var(--brand-bright);">Edit</button>
                                    <button @click="deleteEquipment(eq.equipment_id)" class="text-[0.8125rem] font-semibold hover:underline" style="color: var(--fail-bright);">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="w-full max-w-md rounded-[var(--r-lg)] p-[28px] relative overflow-hidden shadow-[var(--shadow-hover)]" style="background-color: var(--bg-panel); border: 1px solid var(--border-bright);">
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background-color: var(--brand);"></div>
                
                <h2 class="text-[1.25rem] font-bold mb-[24px]" style="color: var(--text-bright);">{{ isEditing ? 'Edit Equipment' : 'New Equipment' }}</h2>
                
                <form @submit.prevent="submit">
                    <div class="mb-[28px]">
                        <label class="block text-[0.75rem] font-semibold mb-[8px] tracking-wide uppercase" style="color: var(--text-secondary);">Equipment Name <span class="text-red-500">*</span></label>
                        <input type="text" v-model="form.equipment_name" required
                            class="w-full h-[40px] px-[12px] rounded-[var(--r-sm)] text-[0.875rem] font-medium transition-all focus:outline-none"
                            style="background-color: var(--bg-elevated); border: 1px solid var(--border-base); color: var(--text-bright);"
                            onfocus="this.style.borderColor='var(--brand)'; this.style.boxShadow='0 0 0 3px var(--brand-glow)';"
                            onblur="this.style.borderColor='var(--border-base)'; this.style.boxShadow='none';"
                        />
                        <div v-if="form.errors.equipment_name" class="text-[0.75rem] mt-2" style="color: var(--fail-bright);">{{ form.errors.equipment_name }}</div>
                    </div>

                    <div class="flex justify-end gap-[12px]">
                        <button type="button" @click="showModal = false" class="h-[36px] px-[16px] rounded-[var(--r-sm)] text-[0.8125rem] font-semibold transition-colors" style="color: var(--text-primary); background-color: transparent; border: 1px solid var(--border-base);" onmouseover="this.style.backgroundColor='var(--bg-elevated)';" onmouseout="this.style.backgroundColor='transparent';">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="h-[36px] px-[20px] rounded-[var(--r-sm)] text-[0.8125rem] font-bold transition-all shadow-btn disabled:opacity-50" style="background-color: var(--brand); color: #ffffff;">
                            {{ form.processing ? 'Saving...' : 'Save Equipment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
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
.midnight-panel { background-color: var(--bg-panel); border: 1px solid var(--border-base); border-radius: var(--r-lg); box-shadow: var(--shadow-card); overflow: hidden; }
.table-row { border-bottom: 1px solid var(--border-base); transition: background-color 150ms ease; }
.table-row:last-child { border-bottom: none; }
.table-row:hover { background-color: var(--bg-elevated); }
.shadow-btn { box-shadow: 0 0 16px rgba(59,130,246,0.25); }
.shadow-btn:hover { box-shadow: 0 0 24px rgba(59,130,246,0.40); transform: translateY(-1px); }
</style>
