<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ pendingJobs: Array, methods: Array, inspectors: Array });
const flash = usePage().props.flash || {};
const submitted = ref(false);

const form = useForm({
    transaction_id: '', method_id: '', internal_id: '',
    start_date: '', start_time: '', end_date: '', end_time: '',
    judgement: '', remark: '',
});

const submit = () => {
    form.post(route('execute-test.store'), {
        onSuccess: () => { form.reset(); submitted.value = true; setTimeout(() => submitted.value = false, 3000); },
    });
};
</script>

<template>
    <Head title="Execute Test" />
    <AuthenticatedLayout>
        <template #title>Execute Test</template>
        
        <div class="pg-header">
                <div>
                    <h1 class="pg-title">Execute Test</h1>
                    <p class="pg-sub">Record test results for a pending job</p>
                </div>
            </div>

            <!-- Pending count & Active Job Info -->
            <div v-if="pendingJobs.length" class="info-bar" style="margin-bottom:20px">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:8px;height:8px;border-radius:50%;background:#F59E0B;animation:pulse 2s infinite"></div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#1E40AF">
                            {{ pendingJobs.length }} pending job{{ pendingJobs.length > 1 ? 's' : '' }} awaiting test
                        </div>
                    </div>
                </div>
                <span class="pill pill-y">Pending</span>
            </div>

            <!-- Success -->
            <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="flash.success || submitted" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;background:#ECFDF5;border:1px solid #A7F3D0;padding:16px 20px;border-radius:12px">
                    <div style="width:32px;height:32px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <span style="color:#059669;font-size:16px;font-weight:bold">✓</span>
                    </div>
                    <div>
                        <p style="font-size:14px;font-weight:600;color:#065F46;margin:0">Test result saved successfully</p>
                    </div>
                </div>
            </transition>

            <form @submit.prevent="submit" class="card card-fill" style="margin: 0; display: flex; flex-direction: column;">
                <h3 style="font-size:15px;font-weight:600;padding-bottom:12px;border-bottom:1px solid #E5E7EB;margin-bottom:24px;margin-top:0">
                    Test Execution</h3>
                
                <div class="form-grow">
                    <div class="form-grid" style="margin-bottom:24px">
                        <div>
                            <label class="form-lbl">Pending Job *</label>
                            <select v-model="form.transaction_id" required class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>Select job...</option>
                                <option v-for="j in pendingJobs" :key="j.transaction_id" :value="j.transaction_id">
                                    #{{ j.transaction_id }} — {{ j.equipment_name }} {{ j.dmc ? `(${j.dmc})` : '' }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="form-lbl">Test Method *</label>
                            <select v-model="form.method_id" required class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>Select method...</option>
                                <option v-for="m in methods" :key="m.method_id" :value="m.method_id">{{ m.method_name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-bottom:24px">
                        <div>
                            <label class="form-lbl">Inspector *</label>
                            <select v-model="form.internal_id" required class="form-inp" style="padding:10px 12px">
                                <option value="" disabled>Select inspector...</option>
                                <option v-for="u in inspectors" :key="u.user_id" :value="u.user_id">{{ u.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-lbl">Judgement *</label>
                            <div class="radio-grp">
                                <label :class="['radio-card', form.judgement === 'OK' ? 'ok' : '']" style="cursor:pointer">
                                    <input type="radio" v-model="form.judgement" value="OK" class="sr-only" required style="display:none" />
                                    <div style="width:20px;height:20px;border-radius:50%;background:#10B981;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">✓</div>
                                    <span style="font-size:13px;font-weight:600">OK</span>
                                </label>
                                <label :class="['radio-card', form.judgement === 'NG' ? 'ng' : '']" style="cursor:pointer">
                                    <input type="radio" v-model="form.judgement" value="NG" class="sr-only" style="display:none" />
                                    <div style="width:20px;height:20px;border-radius:50%;background:#EF4444;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">✕</div>
                                    <span style="font-size:13px;font-weight:600;color:#EF4444">NG</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-bottom:24px">
                        <div style="background:#F9FAFB;padding:14px;border-radius:8px;border:1px solid #E5E7EB">
                            <label class="form-lbl" style="display:flex;justify-content:space-between">Start Time 
                                <button type="button" @click="() => { const d = new Date(); form.start_date = d.toISOString().split('T')[0]; form.start_time = d.toTimeString().slice(0,5); }" class="btn-outline" style="padding:2px 8px;font-size:10px">Now</button>
                            </label>
                            <div style="display:flex;gap:8px;margin-top:4px">
                                <input v-model="form.start_date" type="date" class="form-inp" required>
                                <input v-model="form.start_time" type="time" class="form-inp" required>
                            </div>
                        </div>
                        <div style="background:#F9FAFB;padding:14px;border-radius:8px;border:1px solid #E5E7EB">
                            <label class="form-lbl" style="display:flex;justify-content:space-between">End Time 
                                <button type="button" @click="() => { const d = new Date(); form.end_date = d.toISOString().split('T')[0]; form.end_time = d.toTimeString().slice(0,5); }" class="btn-outline" style="padding:2px 8px;font-size:10px">Now</button>
                            </label>
                            <div style="display:flex;gap:8px;margin-top:4px">
                                <input v-model="form.end_date" type="date" class="form-inp" required>
                                <input v-model="form.end_time" type="time" class="form-inp" required>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom:24px">
                        <label class="form-lbl">Remark</label>
                        <textarea v-model="form.remark" class="form-inp" style="padding:10px 12px;min-height:60px;resize:vertical" placeholder="Optional notes about this test..."></textarea>
                    </div>
                </div>

                <div style="padding-top:20px;border-top:1px solid #E5E7EB;display:flex;justify-content:flex-end;gap:12px;margin-top:auto">
                    <button type="button" @click="form.reset()" class="btn-outline">Clear</button>
                    <button type="submit" :disabled="form.processing" class="btn" style="background:linear-gradient(135deg,#059669,#0D9488)">
                        <span v-if="form.processing">Submitting...</span>
                        <span v-else>Submit Test Result</span>
                    </button>
                </div>
            </form>
    </AuthenticatedLayout>
</template>
