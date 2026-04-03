<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ templates: Array, zipAvailable: Boolean });
const templates = computed(() => Array.isArray(props.templates) ? props.templates : []);
const zipAvailable = computed(() => props.zipAvailable !== false);

const uploadForm = useForm({
    label: '',
    file: null,
    marker: '{{DATA_TABLE}}',
    sheet_name: '',
    start_cell: '',
    include_header: false,
});

const editForm = useForm({
    label: '',
    marker: '{{DATA_TABLE}}',
    sheet_name: '',
    start_cell: '',
    include_header: false,
});

const editingId = ref(null);

const setUploadFile = (event) => {
    uploadForm.file = event.target.files?.[0] ?? null;
};

const submitUpload = () => {
    uploadForm.post(route('report.templates.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset('label', 'file', 'sheet_name', 'start_cell');
            uploadForm.marker = '{{DATA_TABLE}}';
            uploadForm.include_header = false;
        },
    });
};

const startEdit = (template) => {
    editingId.value = template.id;
    editForm.reset();
    editForm.clearErrors();
    editForm.label = template.label ?? '';
    editForm.marker = template.marker ?? '{{DATA_TABLE}}';
    editForm.sheet_name = template.sheet_name ?? '';
    editForm.start_cell = template.start_cell ?? '';
    editForm.include_header = Boolean(template.include_header);
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const saveEdit = (templateId) => {
    editForm.put(route('report.templates.update', templateId), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const removeTemplate = (template) => {
    if (!window.confirm(`Delete template "${template.label}"?`)) {
        return;
    }

    router.delete(route('report.templates.destroy', template.id), {
        preserveScroll: true,
    });
};

const placementMode = (template) => (
    template.start_cell
        ? `Start cell: ${template.start_cell}`
        : `Marker: ${template.marker || '{{DATA_TABLE}}'}`
);
</script>

<template>
    <Head title="Report Templates" />
    <AuthenticatedLayout>
        <template #title>Report Templates</template>

        <div class="space-y-5">
            <div class="pg-header">
                <div>
                    <h1 class="pg-title">Excel Form Manager</h1>
                    <p class="pg-sub">Upload form templates and define where exported data should be placed.</p>
                </div>
                <Link :href="route('report.index')" class="export-btn export-btn-outline" style="text-decoration:none">
                    Back to Report
                </Link>
            </div>

            <section class="card card-fill template-panel">
                <h3 class="template-panel__title">Upload New Form</h3>
                <div v-if="!zipAvailable" class="template-alert">
                    PHP ZIP extension (`ext-zip`) is not enabled on this server.
                    You can still upload templates, but template analysis and template-based export will not work until ZIP is enabled.
                </div>
                <div class="template-grid">
                    <div>
                        <label class="template-label">Template name</label>
                        <input v-model="uploadForm.label" type="text" class="form-inp template-input" placeholder="e.g. Monthly QA Form" />
                        <div v-if="uploadForm.errors.label" class="template-error">{{ uploadForm.errors.label }}</div>
                    </div>
                    <div>
                        <label class="template-label">Excel file (.xlsx)</label>
                        <input type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="form-inp template-input-file" @change="setUploadFile" />
                        <div v-if="uploadForm.errors.file" class="template-error">{{ uploadForm.errors.file }}</div>
                    </div>
                    <div>
                        <label class="template-label">Sheet name (optional)</label>
                        <input v-model="uploadForm.sheet_name" type="text" class="form-inp template-input" placeholder="Leave blank for auto" />
                        <div v-if="uploadForm.errors.sheet_name" class="template-error">{{ uploadForm.errors.sheet_name }}</div>
                    </div>
                    <div>
                        <label class="template-label">Start cell (optional)</label>
                        <input v-model="uploadForm.start_cell" type="text" class="form-inp template-input" placeholder="e.g. B8" />
                        <div class="template-hint">If filled, system writes data from this cell directly.</div>
                        <div v-if="uploadForm.errors.start_cell" class="template-error">{{ uploadForm.errors.start_cell }}</div>
                    </div>
                    <div class="template-span-2">
                        <label class="template-label">Marker text</label>
                        <input v-model="uploadForm.marker" type="text" class="form-inp template-input" placeholder="{{DATA_TABLE}}" />
                        <div class="template-hint">Used when Start cell is empty. Put this marker in your template cell.</div>
                        <div v-if="uploadForm.errors.marker" class="template-error">{{ uploadForm.errors.marker }}</div>
                    </div>
                    <label class="template-checkbox">
                        <input v-model="uploadForm.include_header" type="checkbox">
                        Include header row when exporting into template
                    </label>
                </div>
                <div class="template-actions">
                    <button class="export-btn export-btn-primary" :disabled="uploadForm.processing" @click="submitUpload">
                        {{ uploadForm.processing ? 'Uploading...' : 'Upload Template' }}
                    </button>
                </div>
            </section>

            <section class="card card-fill template-panel">
                <h3 class="template-panel__title">Saved Forms</h3>

                <div v-if="templates.length === 0" class="template-empty">
                    No templates yet. Upload your first Excel form above.
                </div>

                <div v-else class="template-list">
                    <article v-for="template in templates" :key="template.id" class="template-item">
                        <div class="template-item__head">
                            <div>
                                <h4 class="template-item__title">{{ template.label }}</h4>
                                <div class="template-item__desc">{{ template.description }}</div>
                            </div>
                            <div class="template-item__tools">
                                <button class="export-btn export-btn-outline" @click="startEdit(template)">Edit</button>
                                <button class="export-btn export-btn-outline" @click="removeTemplate(template)">Delete</button>
                            </div>
                        </div>

                        <div class="template-item__meta">
                            <span>Mode: {{ placementMode(template) }}</span>
                            <span v-if="template.sheet_name">Sheet: {{ template.sheet_name }}</span>
                            <span>Header: {{ template.include_header ? 'Yes' : 'No' }}</span>
                            <span>Size: {{ template.analysis?.file_size_kb ?? 0 }} KB</span>
                            <span v-if="template.analysis?.zip_available !== false">Marker found: {{ template.analysis?.marker_found ? 'Yes' : 'No' }}</span>
                            <span v-else>Marker check: ZIP not available</span>
                        </div>

                        <div v-if="template.analysis?.sheet_names?.length" class="template-item__sheets">
                            Sheets: {{ template.analysis.sheet_names.join(', ') }}
                        </div>

                        <div v-if="editingId === template.id" class="template-edit">
                            <div class="template-grid">
                                <div>
                                    <label class="template-label">Template name</label>
                                    <input v-model="editForm.label" type="text" class="form-inp template-input" />
                                    <div v-if="editForm.errors.label" class="template-error">{{ editForm.errors.label }}</div>
                                </div>
                                <div>
                                    <label class="template-label">Sheet name (optional)</label>
                                    <input v-model="editForm.sheet_name" type="text" class="form-inp template-input" />
                                    <div v-if="editForm.errors.sheet_name" class="template-error">{{ editForm.errors.sheet_name }}</div>
                                </div>
                                <div>
                                    <label class="template-label">Start cell (optional)</label>
                                    <input v-model="editForm.start_cell" type="text" class="form-inp template-input" />
                                    <div v-if="editForm.errors.start_cell" class="template-error">{{ editForm.errors.start_cell }}</div>
                                </div>
                                <div>
                                    <label class="template-label">Marker text</label>
                                    <input v-model="editForm.marker" type="text" class="form-inp template-input" />
                                    <div v-if="editForm.errors.marker" class="template-error">{{ editForm.errors.marker }}</div>
                                </div>
                                <label class="template-checkbox">
                                    <input v-model="editForm.include_header" type="checkbox">
                                    Include header row
                                </label>
                            </div>
                            <div class="template-actions">
                                <button class="export-btn export-btn-outline" :disabled="editForm.processing" @click="cancelEdit">Cancel</button>
                                <button class="export-btn export-btn-primary" :disabled="editForm.processing" @click="saveEdit(template.id)">
                                    {{ editForm.processing ? 'Saving...' : 'Save Settings' }}
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.template-panel {
    padding: 18px;
}

.template-panel__title {
    font-size: 15px;
    font-weight: 700;
    color: #fafaf9;
}

.template-alert {
    margin-top: 10px;
    border: 1px solid rgba(251, 146, 60, 0.28);
    border-radius: 12px;
    background: rgba(120, 53, 15, 0.22);
    color: #fdba74;
    font-size: 12px;
    line-height: 1.6;
    padding: 10px 12px;
}

.template-grid {
    margin-top: 12px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.template-span-2 {
    grid-column: 1 / -1;
}

.template-label {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    color: #d6d3d1;
    font-weight: 600;
}

.template-input,
.template-input-file {
    width: 100%;
}

.template-input-file {
    padding: 8px 10px;
    height: 40px;
}

.template-hint {
    margin-top: 6px;
    font-size: 11px;
    color: #a8a29e;
}

.template-error {
    margin-top: 6px;
    font-size: 12px;
    color: #fca5a5;
}

.template-checkbox {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #e7e5e4;
}

.template-actions {
    margin-top: 14px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.export-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 600;
    font-family: inherit;
    border: 1px solid transparent;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.16s ease;
}

.export-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.export-btn-primary {
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #1c1917;
    box-shadow: 0 14px 26px rgba(249, 115, 22, 0.2);
}

.export-btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 18px 30px rgba(249, 115, 22, 0.24);
}

.export-btn-outline {
    background: rgba(255, 255, 255, 0.03);
    color: #f5f5f4;
    border-color: rgba(255, 255, 255, 0.12);
}

.export-btn-outline:hover:not(:disabled) {
    background: rgba(251, 146, 60, 0.08);
    border-color: rgba(251, 146, 60, 0.2);
}

.template-empty {
    margin-top: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 16px;
    color: #a8a29e;
    font-size: 13px;
    text-align: center;
}

.template-list {
    margin-top: 12px;
    display: grid;
    gap: 12px;
}

.template-item {
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    background: rgba(0, 0, 0, 0.22);
    padding: 14px;
}

.template-item__head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.template-item__title {
    font-size: 15px;
    font-weight: 700;
    color: #fafaf9;
}

.template-item__desc {
    margin-top: 4px;
    font-size: 12px;
    color: #a8a29e;
}

.template-item__tools {
    display: flex;
    gap: 8px;
}

.template-item__meta {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px 14px;
    font-size: 12px;
    color: #d6d3d1;
}

.template-item__sheets {
    margin-top: 8px;
    font-size: 12px;
    color: #a8a29e;
}

.template-edit {
    margin-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding-top: 12px;
}

:global(.theme-shell[data-theme='light'] .template-panel) {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.995), rgba(244, 249, 255, 0.985)) !important;
    border-color: rgba(15, 23, 42, 0.16) !important;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.07) !important;
}

:global(.theme-shell[data-theme='light'] .template-panel__title) {
    color: #0f172a;
}

:global(.theme-shell[data-theme='light'] .template-label),
:global(.theme-shell[data-theme='light'] .template-checkbox) {
    color: #334155;
}

:global(.theme-shell[data-theme='light'] .template-hint) {
    color: #64748b;
}

:global(.theme-shell[data-theme='light'] .template-alert) {
    border-color: rgba(37, 99, 235, 0.24);
    background: rgba(219, 234, 254, 0.74);
    color: #1e40af;
}

:global(.theme-shell[data-theme='light'] .template-empty) {
    border-color: rgba(15, 23, 42, 0.12);
    background: rgba(255, 255, 255, 0.76);
    color: #475569;
}

:global(.theme-shell[data-theme='light'] .template-item) {
    border-color: rgba(15, 23, 42, 0.12);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(241, 245, 252, 0.96));
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
}

:global(.theme-shell[data-theme='light'] .template-item__title) {
    color: #0f172a;
}

:global(.theme-shell[data-theme='light'] .template-item__desc),
:global(.theme-shell[data-theme='light'] .template-item__meta),
:global(.theme-shell[data-theme='light'] .template-item__sheets) {
    color: #475569;
}

:global(.theme-shell[data-theme='light'] .template-edit) {
    border-top-color: rgba(15, 23, 42, 0.12);
}

:global(.theme-shell[data-theme='light'] .export-btn-primary) {
    background: linear-gradient(135deg, #1d4ed8, #1e40af);
    color: #ffffff;
    box-shadow: 0 14px 26px rgba(29, 78, 216, 0.18);
}

:global(.theme-shell[data-theme='light'] .export-btn-primary:hover:not(:disabled)) {
    box-shadow: 0 18px 30px rgba(29, 78, 216, 0.22);
}

:global(.theme-shell[data-theme='light'] .export-btn-outline) {
    background: rgba(255, 255, 255, 0.96);
    border-color: rgba(15, 23, 42, 0.14);
    color: #0f172a;
}

:global(.theme-shell[data-theme='light'] .export-btn-outline:hover:not(:disabled)) {
    background: rgba(239, 246, 255, 0.98);
    border-color: rgba(29, 78, 216, 0.24);
}

@media (max-width: 820px) {
    .template-grid {
        grid-template-columns: 1fr;
    }
}
</style>
