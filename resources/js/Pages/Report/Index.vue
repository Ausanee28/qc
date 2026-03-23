<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ results: Array, filters: Object });
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const dmcSearch = ref(props.filters.dmc || '');

const search = () => router.get(route('report.index'), {
    date_from: dateFrom.value,
    date_to: dateTo.value,
    dmc: dmcSearch.value
}, { preserveState: true });

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';

const okCount = computed(() => props.results.filter(r => r.judgement === 'OK').length);
const ngCount = computed(() => props.results.filter(r => r.judgement === 'NG').length);

// -- Selection State --
const selectedIds = ref([]);
const selectAll = ref(false);

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedIds.value = props.results.map(r => r.transaction_id);
    } else {
        selectedIds.value = [];
    }
};

const toggleRow = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
    selectAll.value = selectedIds.value.length === props.results.length;
};

const isSelected = (id) => selectedIds.value.includes(id);
const hasSelection = computed(() => selectedIds.value.length > 0);

// -- Export Modal --
const showExportModal = ref(false);
const customFilename = ref('');
const exportMode = ref('all'); // 'all' or 'selected'

const openExport = (mode) => {
    exportMode.value = mode;
    customFilename.value = `QC_Report_${dateFrom.value}_to_${dateTo.value}`;
    showExportModal.value = true;
};

const doExport = () => {
    const ids = exportMode.value === 'selected' ? selectedIds.value.join(',') : 'all';
    const name = encodeURIComponent(customFilename.value || 'export');
    const url = route('report.export') + `?ids=${ids}&date_from=${dateFrom.value}&date_to=${dateTo.value}&filename=${name}`;
    window.location.href = url;
    showExportModal.value = false;
};
</script>

<template>
    <Head title="Report" />
    <AuthenticatedLayout>
        <template #title>Report</template>
        
        <div class="pg-header">
                <div>
                    <h1 class="pg-title">Report</h1>
                    <p class="pg-sub">View and export completed test results</p>
                </div>
                <div style="display:flex;gap:8px;align-items:end">
                    <input v-model="dmcSearch" @keyup.enter="search" type="text" placeholder="DMC code..." class="form-inp" style="padding:6px 10px; width:150px">
                    <input v-model="dateFrom" type="date" class="form-inp" style="padding:6px 10px">
                    <input v-model="dateTo" type="date" class="form-inp" style="padding:6px 10px">
                    <button @click="search" class="btn" style="padding:6px 14px">Filter</button>
                </div>
            </div>
            
            <!-- Selection toolbar & stats -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
                <div style="font-size:12px;color:#a8a29e;display:flex;align-items:center;gap:12px">
                    <div>Showing <strong style="color:#fafaf9">{{ results.length }}</strong> result(s)</div>
                    <div v-if="results.length > 0" style="display:flex;gap:8px;font-size:11px;font-weight:600">
                        <div class="report-summary-pill report-summary-pill-ok">{{ okCount }} OK</div>
                        <div class="report-summary-pill report-summary-pill-ng">{{ ngCount }} NG</div>
                    </div>
                    <div v-if="hasSelection" class="report-summary-pill report-summary-pill-selected">
                        {{ selectedIds.length }} selected
                    </div>
                </div>
                <div style="display:flex;gap:6px">
                    <button v-if="hasSelection" @click="openExport('selected')" class="export-btn export-btn-primary">
                        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Export Selected ({{ selectedIds.length }})
                    </button>
                    <button v-if="results.length > 0" @click="openExport('all')" class="export-btn export-btn-outline">
                        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Export All
                    </button>
                </div>
            </div>

            <div class="tbl" style="margin-bottom:20px">
                <table v-if="results.length">
                    <thead>
                        <tr>
                            <th style="width:36px;text-align:center">
                                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="row-check" />
                            </th>
                            <th>Line</th>
                            <th>Date</th>
                            <th>Sender</th>
                            <th>DMC</th>
                            <th>Detail</th>
                            <th>Process</th>
                            <th>Inspector</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Result</th>
                            <th>Remark</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in results" :key="r.transaction_id" :class="{ 'row-selected': isSelected(r.transaction_id) }">
                            <td style="text-align:center">
                                <input type="checkbox" :checked="isSelected(r.transaction_id)" @change="toggleRow(r.transaction_id)" class="row-check" />
                            </td>
                            <td>{{ r.line || '—' }}</td>
                            <td style="font-size:11px">{{ formatDate(r.receive_date) }}</td>
                            <td>{{ r.sender }}</td>
                            <td style="font-weight:700">{{ r.dmc || '—' }}</td>
                            <td>{{ r.detail }}</td>
                            <td>{{ r.method_name }}</td>
                            <td>{{ r.inspector }}</td>
                            <td style="font-size:11px">{{ r.start_time ? new Date(r.start_time).toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'}) : '—' }}</td>
                            <td style="font-size:11px">{{ r.end_time ? new Date(r.end_time).toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'}) : '—' }}</td>
                            <td>
                                <span v-if="r.judgement === 'OK'" class="pill pill-g">OK</span>
                                <span v-else-if="r.judgement === 'NG'" class="pill pill-r">NG</span>
                                <span v-else class="pill pill-y">{{ r.judgement }}</span>
                            </td>
                            <td style="font-size:11px;color:#78716c">{{ r.remark || '' }}</td>
                            <td>
                                <a :href="route('certificates.pdf', r.transaction_id)" target="_blank" class="btn-outline" style="padding:2px 8px;font-size:10px;text-decoration:none">
                                    PDF
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!results.length" style="padding:40px;text-align:center;color:#a8a29e;font-size:13px;background:rgba(18,18,18,0.92);border-radius:16px;border:1px solid rgba(255,255,255,0.08)">
                    No results found. Try adjusting the date range.
                </div>
            </div>

        <!-- Export Filename Modal -->
        <Teleport to="body">
            <div v-if="showExportModal" class="modal-overlay" @click.self="showExportModal = false">
                <div class="modal-card">
                    <div class="modal-header">
                        <h3 class="modal-title">Export to CSV</h3>
                        <button class="modal-close" @click="showExportModal = false">×</button>
                    </div>
                    <div class="modal-body">
                        <label class="modal-label">File name</label>
                        <div style="display:flex;align-items:center;gap:0">
                            <input v-model="customFilename" type="text" class="modal-input" placeholder="Enter file name" @keyup.enter="doExport" autofocus />
                            <span class="modal-ext">.csv</span>
                        </div>
                        <div class="modal-hint">{{ exportMode === 'selected' ? `${selectedIds.length} record(s) selected` : `All ${results.length} record(s)` }}</div>
                    </div>
                    <div class="modal-footer">
                        <button class="export-btn export-btn-outline" @click="showExportModal = false">Cancel</button>
                        <button class="export-btn export-btn-primary" @click="doExport">
                            <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Download
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.report-summary-pill {
    padding: 2px 8px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.report-summary-pill-ok {
    background: rgba(251, 146, 60, 0.12);
    border-color: rgba(251, 146, 60, 0.2);
    color: #fdba74;
}

.report-summary-pill-ng {
    background: rgba(41, 37, 36, 0.88);
    border-color: rgba(255, 255, 255, 0.08);
    color: #e7e5e4;
}

.report-summary-pill-selected {
    background: rgba(251, 146, 60, 0.14);
    border-color: rgba(251, 146, 60, 0.2);
    color: #fdba74;
    font-size: 11px;
    font-weight: 600;
}

.row-check {
    width: 16px;
    height: 16px;
    accent-color: #f97316;
    cursor: pointer;
}

.row-selected {
    background: linear-gradient(90deg, rgba(251, 146, 60, 0.12), rgba(15, 15, 15, 0.92)) !important;
}

.export-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    font-family: inherit;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid transparent;
    border-radius: 999px;
}

.export-btn-primary {
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #1c1917;
    box-shadow: 0 14px 26px rgba(249, 115, 22, 0.2);
}

.export-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 18px 30px rgba(249, 115, 22, 0.24);
}

.export-btn-outline {
    background: rgba(255, 255, 255, 0.03);
    color: #f5f5f4;
    border-color: rgba(255, 255, 255, 0.1);
}

.export-btn-outline:hover {
    background: rgba(251, 146, 60, 0.08);
    border-color: rgba(251, 146, 60, 0.2);
}

/* Modal */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.68); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999;
    animation: fadeIn 0.15s ease;
}
.modal-card {
    background: linear-gradient(180deg, rgba(22, 22, 22, 0.98), rgba(12, 12, 12, 0.98));
    border: 1px solid rgba(251, 146, 60, 0.14);
    border-radius: 20px; width: 420px; max-width: 90vw; box-shadow: 0 28px 70px rgba(0,0,0,0.45);
    animation: slideUp 0.2s ease;
}
.modal-header {
    display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.08);
}
.modal-title { font-size: 15px; font-weight: 600; color: #fafaf9; margin: 0; }
.modal-close {
    background: none; border: none; font-size: 16px; color: #a8a29e; cursor: pointer; width: 28px; height: 28px; border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
}
.modal-close:hover { background: rgba(251, 146, 60, 0.08); color: #fdba74; }
.modal-body { padding: 20px; }
.modal-label { display: block; font-size: 13px; font-weight: 500; color: #d6d3d1; margin-bottom: 6px; }
.modal-input {
    flex: 1; height: 40px; padding: 0 12px; font-size: 14px; font-family: inherit; color: #fafaf9;
    background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px 0 0 12px; outline: none;
    transition: border-color 0.15s; box-sizing: border-box;
}
.modal-input:focus { border-color: rgba(251,146,60,0.38); background: rgba(0,0,0,0.45); }
.modal-ext {
    height: 40px; padding: 0 12px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-left: none;
    border-radius: 0 12px 12px 0; font-size: 13px; font-weight: 500; color: #a8a29e;
    display: flex; align-items: center; box-sizing: border-box;
}
.modal-hint { font-size: 11px; color: #78716c; margin-top: 8px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 20px; border-top: 1px solid rgba(255,255,255,0.08); }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
