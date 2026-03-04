<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ externals: Array, internals: Array, equipments: Array });
const flash = usePage().props.flash || {};
const submitted = ref(false);

const form = useForm({
    external_id: '', internal_id: '', equipment_id: '', dmc: '', line: '',
});

const submit = () => {
    form.post(route('receive-job.store'), {
        onSuccess: () => { form.reset(); submitted.value = true; setTimeout(() => submitted.value = false, 3000); },
    });
};
</script>

<template>
    <Head title="Receive Job" />
    <AuthenticatedLayout>
        <template #title>Receive Job</template>
        <div class="pg-header">
                <div>
                    <h1 class="pg-title">Receive Job</h1>
                    <p class="pg-sub">Record a new incoming inspection job</p>
                </div>
            </div>

            <!-- Success Message -->
            <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="flash.success || submitted" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;background:#ECFDF5;border:1px solid #A7F3D0;padding:16px 20px;border-radius:12px">
                    <div style="width:32px;height:32px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <span style="color:#059669;font-size:16px;font-weight:bold">✓</span>
                    </div>
                    <div>
                        <p style="font-size:14px;font-weight:600;color:#065F46;margin:0">Job registered successfully</p>
                        <p style="font-size:12px;color:#059669;margin:0">The item has been added to the queue</p>
                    </div>
                </div>
            </transition>

            <form @submit.prevent="submit" class="card card-fill" style="margin: 0; display: flex; flex-direction: column;">
                <h3 style="font-size:15px;font-weight:600;padding-bottom:12px;border-bottom:1px solid #E5E7EB;margin-bottom:24px;margin-top:0">
                    Job Registration</h3>
                
                <div class="form-grow">
                    <div class="form-grid" style="margin-bottom:24px">
                        <div>
                            <label class="form-lbl">Sender (External) *</label>
                            <select v-model="form.external_id" required class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>-- Select Sender --</option>
                                <option v-for="e in externals" :key="e.external_id" :value="e.external_id">{{ e.external_name }}</option>
                            </select>
                            <div v-if="form.errors.external_id" style="color:#EF4444;font-size:12px;margin-top:4px">{{ form.errors.external_id }}</div>
                        </div>
                        <div>
                            <label class="form-lbl">Receiver (Internal) *</label>
                            <select v-model="form.internal_id" required class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>-- Select Receiver --</option>
                                <option v-for="u in internals" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                            </select>
                            <div v-if="form.errors.internal_id" style="color:#EF4444;font-size:12px;margin-top:4px">{{ form.errors.internal_id }}</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom:24px">
                        <label class="form-lbl">Equipment *</label>
                        <select v-model="form.equipment_id" required class="form-inp" style="padding:10px 12px">
                            <option value="" disabled>-- Select Equipment --</option>
                            <option v-for="eq in equipments" :key="eq.equipment_id" :value="eq.equipment_id">{{ eq.equipment_name }}</option>
                        </select>
                        <div v-if="form.errors.equipment_id" style="color:#EF4444;font-size:12px;margin-top:4px">{{ form.errors.equipment_id }}</div>
                    </div>
                    
                    <div class="form-grid" style="margin-bottom:24px">
                        <div>
                            <label class="form-lbl">DMC Code</label>
                            <input v-model="form.dmc" type="text" class="form-inp" style="padding:10px 12px" placeholder="e.g. DMC-2026-001">
                            <div v-if="form.errors.dmc" style="color:#EF4444;font-size:12px;margin-top:4px">{{ form.errors.dmc }}</div>
                        </div>
                        <div>
                            <label class="form-lbl">Line</label>
                            <input v-model="form.line" type="text" class="form-inp" style="padding:10px 12px" placeholder="e.g. Line 3">
                            <div v-if="form.errors.line" style="color:#EF4444;font-size:12px;margin-top:4px">{{ form.errors.line }}</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom:24px">
                        <label class="form-lbl">Remark</label>
                        <textarea class="form-inp" style="padding:10px 12px;min-height:80px;resize:vertical" placeholder="Optional notes about this job..."></textarea>
                    </div>
                </div>

                <div style="padding-top:20px;border-top:1px solid #E5E7EB;display:flex;justify-content:flex-end;gap:12px;margin-top:auto">
                    <button type="button" @click="form.reset()" class="btn-outline">Clear</button>
                    <button type="submit" :disabled="form.processing" class="btn">
                        <span v-if="form.processing">Submitting...</span>
                        <span v-else>Save & Print Tag</span>
                    </button>
                </div>
            </form>
    </AuthenticatedLayout>
</template>
