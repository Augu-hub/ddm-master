<template>
    <VerticalLayout>
        <Head title="DDM — Niveaux d'impact" />

        <!-- HEADER -->
        <b-row class="mb-2">
            <b-col class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-flame text-danger fs-5"></i>
                    <h4 class="m-0 fw-semibold">Niveaux d'impact</h4>
                    <small class="text-muted ms-1">Matrice des risques</small>
                </div>
                <div class="d-flex align-items-center gap-2" v-if="matrixConfigs.length">
                    <label class="form-label mb-0 text-muted small">Configuration :</label>
                    <select v-model="currentConfigId" @change="onConfigChange"
                            class="form-select form-select-sm" style="width:auto">
                        <option v-for="cfg in matrixConfigs" :key="cfg.id" :value="cfg.id">
                            {{ cfg.name }} ({{ cfg.matrix_label }}){{ cfg.is_active ? ' ✓' : '' }}
                        </option>
                    </select>
                </div>
            </b-col>
        </b-row>

        <!-- ALERTE — pas de config -->
        <b-alert v-if="!matrixConfigs.length" variant="warning" show class="py-2 px-3">
            <i class="ti ti-alert-triangle me-1"></i>
            Aucune configuration disponible.
            <a :href="route('risk.core.matrix-config.index')" class="alert-link ms-1">Créer une configuration →</a>
        </b-alert>

        <template v-else>
            <!-- STATS -->
            <b-row class="g-2 mb-2">
                <b-col md="4">
                    <b-card no-body class="shadow-sm stat-card border-start border-danger border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-danger"><i class="ti ti-flame"></i></div>
                                <div>
                                    <small class="text-muted d-block">Niveaux définis</small>
                                    <h5 class="mb-0 fw-bold">{{ impactLevels.length }} / {{ selectedConfig?.matrix_size ?? '—' }}</h5>
                                </div>
                                <div class="ms-auto">
                                    <span v-if="capacityPercent >= 100" class="badge bg-success">Complet</span>
                                    <span v-else class="badge bg-warning text-dark">En cours</span>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height:4px">
                                <div class="progress-bar" :class="capacityPercent >= 100 ? 'bg-success' : 'bg-danger'"
                                     :style="{ width: capacityPercent + '%' }"></div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
                <b-col md="4">
                    <b-card no-body class="shadow-sm stat-card border-start border-primary border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-primary"><i class="ti ti-layout-grid"></i></div>
                                <div>
                                    <small class="text-muted d-block">Taille matrice</small>
                                    <h5 class="mb-0 fw-bold">{{ selectedConfig?.matrix_label ?? '—' }}</h5>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
                <b-col md="4">
                    <b-card no-body class="shadow-sm stat-card border-start border-info border-3">
                        <b-card-body class="p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-info"><i class="ti ti-calculator"></i></div>
                                <div>
                                    <small class="text-muted d-block">Score max matrice</small>
                                    <h5 class="mb-0 fw-bold">{{ selectedConfig ? selectedConfig.matrix_size * selectedConfig.matrix_size : '—' }}</h5>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
            </b-row>

            <b-alert v-if="alert.show" :variant="alert.variant" show dismissible
                     @dismissed="alert.show = false" class="py-2 px-3 mb-2">
                {{ alert.message }}
            </b-alert>

            <b-row class="g-2">
                <!-- FORMULAIRE -->
                <b-col lg="5">
                    <b-card no-body class="shadow-sm">
                        <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="ti ti-flame me-1 text-danger"></i>
                                {{ editingId ? 'Modifier le niveau' : "Ajouter un niveau d'impact" }}
                            </h6>
                            <span v-if="editingId" class="badge bg-warning text-dark">Mode édition</span>
                        </b-card-header>
                        <b-card-body class="p-3">
                            <b-form @submit.prevent="submitForm">
                                <b-row class="g-2">
                                    <b-col cols="8">
                                        <label class="form-label mb-1">Libellé <span class="text-danger">*</span></label>
                                        <b-form-input class="form-control-sm" v-model.trim="form.label"
                                                      placeholder="ex : Catastrophique" required />
                                        <div v-if="errors.label" class="text-danger small mt-1">{{ errors.label }}</div>
                                    </b-col>
                                    <b-col cols="4">
                                        <label class="form-label mb-1">Score <span class="text-danger">*</span></label>
                                        <select v-model.number="form.score" class="form-select form-select-sm">
                                            <option v-for="s in availableScores" :key="s" :value="s">{{ s }}</option>
                                        </select>
                                        <div v-if="errors.score" class="text-danger small mt-1">{{ errors.score }}</div>
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Description qualitative</label>
                                        <b-form-textarea class="form-control-sm" rows="3"
                                                         v-model.trim="form.description"
                                                         placeholder="Décrivez ce niveau d'impact..." />
                                    </b-col>
                                    <b-col cols="12">
                                        <label class="form-label mb-1">Couleur</label>
                                        <div class="d-flex gap-2 align-items-center">
                                            <input type="color" v-model="form.color_code"
                                                   class="form-control form-control-sm form-control-color p-0"
                                                   style="width:36px;height:28px" />
                                            <b-form-input class="form-control-sm font-monospace"
                                                          v-model="form.color_code"
                                                          placeholder="#ef4444" style="max-width:100px" />
                                            <span class="apt-badge" :style="badgeStyle(form.color_code)">
                                                {{ form.label || 'Aperçu' }}
                                            </span>
                                        </div>
                                        <div v-if="errors.color_code" class="text-danger small mt-1">{{ errors.color_code }}</div>
                                    </b-col>
                                    <b-col cols="6">
                                        <label class="form-label mb-1">Ordre d'affichage</label>
                                        <b-form-input class="form-control-sm" type="number" min="0"
                                                      v-model.number="form.sort_order" />
                                    </b-col>
                                    <b-col cols="12" class="d-flex justify-content-between align-items-center pt-1">
                                        <b-button size="sm" variant="light" @click="resetForm">
                                            <i class="ti ti-x me-1"></i>Annuler
                                        </b-button>
                                        <div class="d-flex gap-1">
                                            <b-button size="sm" variant="outline-primary" @click="openMistralPanel">
                                                <i class="ti ti-sparkles me-1"></i>IA
                                            </b-button>
                                            <b-button size="sm" variant="danger" type="submit"
                                                      :disabled="processing || (!canAddMore && !editingId)">
                                                <i class="ti ti-loader-2 ti-spin me-1" v-if="processing"></i>
                                                <i class="ti ti-device-floppy me-1" v-else></i>
                                                {{ editingId ? 'Enregistrer' : 'Créer' }}
                                            </b-button>
                                        </div>
                                    </b-col>
                                </b-row>
                            </b-form>
                        </b-card-body>
                    </b-card>
                </b-col>

                <!-- TABLE -->
                <b-col lg="7">
                    <b-card no-body class="shadow-sm">
                        <b-card-header class="py-2 px-3">
                            <h6 class="mb-0"><i class="ti ti-list me-1"></i>Niveaux définis</h6>
                        </b-card-header>
                        <b-card-body class="p-0">
                            <div v-if="!impactLevels.length" class="text-center text-muted py-5">
                                <i class="ti ti-flame fs-1 opacity-25 d-block mb-2"></i>
                                <p class="mb-0">Aucun niveau d'impact défini.</p>
                            </div>
                            <div v-else>
                                <DataTable :value="sortedLevels" size="small" class="pv-table flat">
                                    <Column header="Score" style="width:55px" bodyClass="text-center">
                                        <template #body="{data}">
                                            <span class="fw-bold font-monospace fs-6" :style="{ color: data.color_code }">{{ data.score }}</span>
                                        </template>
                                    </Column>
                                    <Column header="Libellé">
                                        <template #body="{data}">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="color-dot" :style="{ background: data.color_code }"></span>
                                                <span class="fw-semibold">{{ data.label }}</span>
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Description" bodyClass="text-muted small">
                                        <template #body="{data}">
                                            {{ data.description ? data.description.substring(0, 55) + (data.description.length > 55 ? '…' : '') : '—' }}
                                        </template>
                                    </Column>
                                    <Column header="" style="width:75px" bodyClass="text-end pe-2">
                                        <template #body="{data}">
                                            <b-button size="sm" variant="light" class="me-1" @click="openForm(data)">
                                                <i class="ti ti-pencil"></i>
                                            </b-button>
                                            <b-button size="sm" variant="light" class="text-danger" @click="confirmDelete(data)">
                                                <i class="ti ti-trash"></i>
                                            </b-button>
                                        </template>
                                    </Column>
                                    <template #empty>
                                        <div class="text-muted py-2 text-center">Aucun niveau</div>
                                    </template>
                                </DataTable>

                                <!-- Graduation visuelle -->
                                <div class="p-3 border-top">
                                    <small class="text-muted fw-semibold text-uppercase d-block mb-2">Graduation</small>
                                    <div class="d-flex rounded overflow-hidden" style="height:28px">
                                        <div v-for="level in sortedLevels" :key="level.id"
                                             class="flex-fill d-flex align-items-center justify-content-center small fw-semibold text-white"
                                             :style="{ backgroundColor: level.color_code }"
                                             :title="`Score ${level.score} — ${level.label}`">
                                            {{ level.label }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
            </b-row>
        </template>

        <!-- MODALE suppression -->
        <b-modal v-model="deleteModal.show" title="Supprimer ce niveau ?"
                 ok-title="Supprimer" ok-variant="danger" cancel-title="Annuler"
                 @ok="executeDelete" centered>
            <p>Le niveau <strong>{{ deleteModal.level?.label }}</strong> sera supprimé définitivement.</p>
        </b-modal>

        <!-- OFFCANVAS IA Mistral -->
        <b-offcanvas v-model="mistralPanel.show" placement="end" title="Assistant IA — Impacts">
            <div class="p-2">
                <div class="ai-sector-box mb-3">
                    <label class="form-label mb-1 fw-semibold">
                        <i class="ti ti-robot me-1 text-primary"></i>Secteur d'activité
                    </label>
                    <b-form-input class="form-control-sm" v-model.trim="mistralPanel.sector"
                                  placeholder="ex : industrie agroalimentaire, banque..." />
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1">Contexte <small class="text-muted">(optionnel)</small></label>
                    <b-form-textarea class="form-control-sm" rows="3" v-model.trim="mistralPanel.context"
                                     placeholder="Précisez le type d'organisation..." />
                </div>
                <b-button variant="primary" class="w-100" size="sm"
                          @click="fetchMistralSuggestions"
                          :disabled="mistralPanel.loading || !mistralPanel.sector">
                    <i class="ti ti-loader-2 ti-spin me-1" v-if="mistralPanel.loading"></i>
                    <i class="ti ti-sparkles me-1" v-else></i>
                    {{ mistralPanel.loading ? 'Génération...' : 'Générer les suggestions' }}
                </b-button>
                <b-alert v-if="mistralPanel.error" variant="danger" show class="mt-3 py-2 px-3 small">
                    {{ mistralPanel.error }}
                </b-alert>
                <div v-if="mistralPanel.suggestions.length" class="mt-3 ai-suggestions-box">
                    <div class="ai-header"><i class="ti ti-sparkles me-1"></i><strong>Suggestions</strong> — cliquer pour utiliser</div>
                    <div v-for="(s, i) in mistralPanel.suggestions" :key="i"
                         class="suggestion-chip d-flex align-items-center gap-2 mb-2 w-100"
                         @click="applySuggestion(s)" role="button">
                        <span class="fw-bold font-monospace" :style="{ color: s.color_code }">{{ s.score }}</span>
                        <span class="color-dot" :style="{ background: s.color_code }"></span>
                        <span class="fw-semibold flex-fill">{{ s.label }}</span>
                        <i class="ti ti-arrow-right small"></i>
                    </div>
                </div>
            </div>
        </b-offcanvas>

    </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const props = defineProps({
    matrixConfigs:    { type: Array,  default: () => [] },
    selectedConfigId: { type: Number, default: null },
    impactLevels:     { type: Array,  default: () => [] },
})

const currentConfigId = ref(props.selectedConfigId)
const editingId       = ref(null)
const processing      = ref(false)
const errors          = ref({})
const alert           = ref({ show: false, variant: 'success', message: '' })
const deleteModal     = ref({ show: false, level: null })
const mistralPanel    = ref({ show: false, sector: '', context: '', loading: false, suggestions: [], error: null })

const form = ref({
    matrix_config_id: props.selectedConfigId,
    label: '', score: 1, description: '', color_code: '#6b7280', sort_order: 0,
})

const selectedConfig = computed(() => props.matrixConfigs.find(c => c.id === currentConfigId.value) ?? null)
const sortedLevels   = computed(() => [...props.impactLevels].sort((a, b) => a.sort_order - b.sort_order || a.score - b.score))
const capacityPercent = computed(() => Math.min(100, Math.round((props.impactLevels.length / (selectedConfig.value?.matrix_size ?? 1)) * 100)))
const canAddMore      = computed(() => props.impactLevels.length < (selectedConfig.value?.matrix_size ?? 0))
const usedScores      = computed(() => props.impactLevels.filter(l => l.id !== editingId.value).map(l => l.score))
const availableScores = computed(() => {
    const max = selectedConfig.value?.matrix_size ?? 5
    return Array.from({ length: max }, (_, i) => i + 1).filter(s => !usedScores.value.includes(s) || s === form.value.score)
})

const showAlert = (message, variant = 'success') => {
    alert.value = { show: true, variant, message }
    setTimeout(() => { alert.value.show = false }, 4000)
}

const onConfigChange = () => router.get(route('risk.core.impact.index'), { config_id: currentConfigId.value }, { preserveState: true, preserveScroll: true })

const openForm = (level = null) => {
    editingId.value = level?.id ?? null
    errors.value    = {}
    form.value = level
        ? { matrix_config_id: currentConfigId.value, label: level.label, score: level.score, description: level.description ?? '', color_code: level.color_code, sort_order: level.sort_order }
        : { matrix_config_id: currentConfigId.value, label: '', score: availableScores.value[0] ?? 1, description: '', color_code: '#6b7280', sort_order: props.impactLevels.length }
}

const resetForm = () => {
    editingId.value = null
    errors.value    = {}
    form.value = { matrix_config_id: currentConfigId.value, label: '', score: availableScores.value[0] ?? 1, description: '', color_code: '#6b7280', sort_order: props.impactLevels.length }
}

const submitForm = () => {
    processing.value = true
    const url    = editingId.value ? route('risk.core.impact.update', editingId.value) : route('risk.core.impact.store')
    const method = editingId.value ? 'put' : 'post'
    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => { resetForm(); processing.value = false; showAlert('Niveau enregistré avec succès.') },
        onError:   (e) => { errors.value = e; processing.value = false },
    })
}

const confirmDelete = (level) => { deleteModal.value = { show: true, level } }
const executeDelete = () => {
    router.delete(route('risk.core.impact.destroy', deleteModal.value.level.id), {
        preserveScroll: true,
        onSuccess: () => showAlert('Niveau supprimé.'),
    })
}

const openMistralPanel = () => { mistralPanel.value.show = true }

const fetchMistralSuggestions = async () => {
    mistralPanel.value.loading     = true
    mistralPanel.value.error       = null
    mistralPanel.value.suggestions = []
    try {
        const res  = await fetch(route('risk.core.impact.mistral.suggest'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
            body: JSON.stringify({ sector: mistralPanel.value.sector, context: mistralPanel.value.context, matrix_size: selectedConfig.value?.matrix_size ?? 5 }),
        })
        const data = await res.json()
        if (!res.ok) throw new Error(data.message ?? 'Erreur.')
        mistralPanel.value.suggestions = data.suggestions ?? []
    } catch (err) {
        mistralPanel.value.error = err.message
    } finally {
        mistralPanel.value.loading = false
    }
}

const applySuggestion = (s) => {
    form.value = { matrix_config_id: currentConfigId.value, label: s.label, score: s.score, description: s.description, color_code: s.color_code, sort_order: s.score - 1 }
    mistralPanel.value.show = false
}

const badgeStyle = (colorCode) => ({ background: (colorCode ?? '#6b7280') + '22', borderColor: colorCode, color: colorCode })
</script>

<style scoped>
.form-control-sm, .form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.btn-sm { padding:.15rem .45rem; font-size:.72rem }
.stat-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:18px }
.stat-card { transition:all .2s }
.stat-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.1); transform:translateY(-2px) }
.pv-table :deep(.p-datatable-thead>tr>th) { background:#f8fafc; border:1px solid #e5e7eb; padding:.25rem .35rem; font-size:.73rem }
.pv-table :deep(.p-datatable-tbody>tr>td) { border:1px solid #eef2f7; padding:.25rem .35rem; font-size:.72rem }
.apt-badge { padding:.1rem .5rem; border-radius:12px; font-size:.7rem; font-weight:700; border:1px solid }
.color-dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0 }
.ai-sector-box { background:#f8f9fa; border:1px dashed #dee2e6; border-radius:.4rem; padding:.5rem .75rem }
.ai-suggestions-box { background:linear-gradient(135deg,#fce4e4 0%,#ffd5d5 100%); border:2px solid #dc3545; border-radius:.4rem; padding:.6rem .75rem }
.ai-header { font-weight:600; color:#842029; margin-bottom:.5rem; font-size:.82rem }
.suggestion-chip { padding:.3rem .7rem; cursor:pointer; user-select:none; transition:all .2s; font-size:.78rem; border-radius:20px; background:#fce4e4; color:#842029; border:1px solid #f5c2c7 }
.suggestion-chip:hover { background:#f5c2c7; transform:scale(1.02) }
</style>
