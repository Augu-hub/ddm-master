<template>
    <VerticalLayout>
        <Head title="DDM — Configurations de matrice" />

        <!-- HEADER -->
        <b-row class="mb-2">
            <b-col class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-adjustments-alt text-primary fs-5"></i>
                    <h4 class="m-0 fw-semibold">Configurations de matrice</h4>
                    <small class="text-muted ms-1">3×3 à 10×10</small>
                </div>
                <b-button size="sm" variant="primary" @click="openForm()">
                    <i class="ti ti-plus me-1"></i>Nouvelle configuration
                </b-button>
            </b-col>
        </b-row>

        <!-- STATS -->
        <b-row class="g-2 mb-2">
            <b-col md="4">
                <b-card no-body class="shadow-sm stat-card border-start border-primary border-3">
                    <b-card-body class="p-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon bg-primary"><i class="ti ti-adjustments"></i></div>
                            <div>
                                <small class="text-muted d-block">Configurations</small>
                                <h5 class="mb-0 fw-bold">{{ configs.length }}</h5>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col md="4">
                <b-card no-body class="shadow-sm stat-card border-start border-success border-3">
                    <b-card-body class="p-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon bg-success"><i class="ti ti-circle-check"></i></div>
                            <div>
                                <small class="text-muted d-block">Complètes</small>
                                <h5 class="mb-0 fw-bold">{{ configs.filter(c => c.is_complete).length }}</h5>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col md="4">
                <b-card no-body class="shadow-sm stat-card border-start border-warning border-3">
                    <b-card-body class="p-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon bg-warning"><i class="ti ti-bolt"></i></div>
                            <div>
                                <small class="text-muted d-block">Active</small>
                                <h5 class="mb-0 fw-bold">{{ configs.find(c => c.is_active)?.name ?? '—' }}</h5>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <!-- ALERT feedback -->
        <b-alert v-if="alert.show" :variant="alert.variant" show dismissible
                 @dismissed="alert.show = false" class="py-2 px-3 mb-2">
            {{ alert.message }}
        </b-alert>
        <b-alert v-if="pageError" variant="danger" show class="py-2 px-3 mb-2">
            <i class="ti ti-alert-circle me-1"></i>{{ pageError }}
        </b-alert>

        <!-- FORMULAIRE création/édition -->
        <b-card v-if="showForm" no-body class="shadow-sm mb-3 border-primary">
            <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center bg-light">
                <h6 class="mb-0">
                    <i class="ti ti-adjustments-alt me-1 text-primary"></i>
                    {{ editingId ? 'Modifier la configuration' : 'Nouvelle configuration de matrice' }}
                </h6>
                <span v-if="editingId" class="badge bg-warning text-dark">Mode édition</span>
            </b-card-header>
            <b-card-body class="p-3">
                <b-form @submit.prevent="submitForm">
                    <b-row class="g-2 align-items-end">
                        <b-col md="5">
                            <label class="form-label mb-1">Nom <span class="text-danger">*</span></label>
                            <b-form-input class="form-control-sm" v-model.trim="form.name"
                                          placeholder="ex : Matrice standard 5×5" required />
                            <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name }}</div>
                        </b-col>
                        <b-col md="4">
                            <label class="form-label mb-1">
                                Taille
                                <span v-if="editingId" class="text-muted fw-normal small">(non modifiable)</span>
                                <span v-else class="text-danger">*</span>
                            </label>
                            <!-- 2 lignes : [3 4 5] puis [6 7 8 9 10] -->
                            <div class="size-picker" :class="{ 'size-picker--disabled': !!editingId }">
                                <div class="size-row">
                                    <button v-for="size in [3,4,5]" :key="size" type="button"
                                            class="btn btn-sm size-btn"
                                            :class="form.matrix_size === size ? 'btn-primary' : 'btn-outline-secondary'"
                                            :disabled="!!editingId"
                                            @click="!editingId && (form.matrix_size = size)">
                                        {{ size }}×{{ size }}
                                    </button>
                                </div>
                                <div class="size-row">
                                    <button v-for="size in [6,7,8,9,10]" :key="size" type="button"
                                            class="btn btn-sm size-btn"
                                            :class="form.matrix_size === size ? 'btn-primary' : 'btn-outline-secondary'"
                                            :disabled="!!editingId"
                                            @click="!editingId && (form.matrix_size = size)">
                                        {{ size }}×{{ size }}
                                    </button>
                                </div>
                            </div>
                            <div v-if="errors.matrix_size" class="text-danger small mt-1">{{ errors.matrix_size }}</div>
                        </b-col>
                        <b-col md="3">
                            <label class="form-label mb-1">Description</label>
                            <b-form-input class="form-control-sm" v-model.trim="form.description"
                                          placeholder="Description de la configuration..." />
                        </b-col>

                        <!-- Aperçu zones par défaut -->
                        <b-col cols="12" v-if="!editingId">
                            <small class="text-muted">
                                Zones créées automatiquement
                                <span class="badge bg-secondary ms-1">{{ defaultZones.length }} zones</span>
                                <span class="text-muted ms-1">— score max {{ form.matrix_size * form.matrix_size }}</span>
                            </small>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <span v-for="zone in defaultZones" :key="zone.label"
                                      class="apt-badge"
                                      :style="{ background: zone.color_code + '22', borderColor: zone.color_code, color: zone.color_code }">
                                    <span class="color-dot me-1" :style="{ background: zone.color_code }"></span>
                                    {{ zone.label }} [{{ zone.min_score }}–{{ zone.max_score }}]
                                </span>
                            </div>
                        </b-col>

                        <b-col cols="12" class="d-flex justify-content-end gap-2 pt-1">
                            <b-button size="sm" variant="light" @click="closeForm">
                                <i class="ti ti-x me-1"></i>Annuler
                            </b-button>
                            <b-button size="sm" variant="primary" type="submit" :disabled="processing">
                                <i class="ti ti-loader-2 ti-spin me-1" v-if="processing"></i>
                                <i class="ti ti-device-floppy me-1" v-else></i>
                                {{ editingId ? 'Enregistrer' : 'Créer la configuration' }}
                            </b-button>
                        </b-col>
                    </b-row>
                </b-form>
            </b-card-body>
        </b-card>

        <!-- LISTE des configurations -->
        <div v-if="!configs.length" class="text-center text-muted py-5">
            <i class="ti ti-layout-grid fs-1 opacity-25 d-block mb-2"></i>
            <p class="mb-1">Aucune configuration de matrice.</p>
            <b-button size="sm" variant="outline-primary" @click="openForm()">
                <i class="ti ti-plus me-1"></i>Créer la première configuration
            </b-button>
        </div>

        <div class="d-flex flex-column gap-2">
            <b-card v-for="config in configs" :key="config.id" no-body class="shadow-sm config-card"
                    :class="config.is_active ? 'border-primary border-2' : ''">
                <b-card-body class="p-3">
                    <div class="d-flex align-items-start gap-3">
                        <!-- Badge taille -->
                        <div class="matrix-size-badge"
                             :class="config.is_active ? 'text-primary bg-primary' : 'text-muted bg-light'">
                            <span class="fw-bold fs-5 lh-1">{{ config.matrix_size }}</span>
                            <small class="d-block opacity-75">×{{ config.matrix_size }}</small>
                        </div>

                        <!-- Infos -->
                        <div class="flex-fill min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-semibold">{{ config.name }}</span>
                                <span v-if="config.is_active" class="badge bg-primary">
                                    <i class="ti ti-bolt me-1"></i>Active
                                </span>
                                <span v-else-if="config.is_complete" class="badge bg-success">
                                    <i class="ti ti-check me-1"></i>Complète
                                </span>
                                <span v-else class="badge bg-warning text-dark">
                                    <i class="ti ti-clock me-1"></i>Incomplète
                                </span>
                            </div>
                            <small v-if="config.description" class="text-muted d-block mt-1">{{ config.description }}</small>

                            <!-- Jauges de progression -->
                            <div class="d-flex flex-wrap gap-3 mt-2">
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">Impacts</small>
                                    <div class="progress" style="width:80px;height:5px">
                                        <div class="progress-bar bg-danger"
                                             :style="{ width: fillPercent(config.impact_levels_count, config.matrix_size) + '%' }"></div>
                                    </div>
                                    <small class="font-monospace text-muted">{{ config.impact_levels_count }}/{{ config.matrix_size }}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">Fréquences</small>
                                    <div class="progress" style="width:80px;height:5px">
                                        <div class="progress-bar bg-primary"
                                             :style="{ width: fillPercent(config.frequency_levels_count, config.matrix_size) + '%' }"></div>
                                    </div>
                                    <small class="font-monospace text-muted">{{ config.frequency_levels_count }}/{{ config.matrix_size }}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">Zones</small>
                                    <small class="font-monospace" :class="config.criticality_zones_count > 0 ? 'text-success' : 'text-muted'">
                                        {{ config.criticality_zones_count }}
                                    </small>
                                </div>
                                <small class="text-muted ms-auto">Créée le {{ config.created_at }}</small>
                            </div>

                            <!-- Liens complétion si incomplète -->
                            <div v-if="!config.is_complete" class="d-flex flex-wrap gap-2 mt-2">
                                <small class="text-muted align-self-center">Compléter :</small>
                                <a v-if="config.impact_levels_count < config.matrix_size"
                                   :href="route('risk.core.impact.index', { config_id: config.id })"
                                   class="btn btn-sm btn-outline-danger">
                                    <i class="ti ti-flame me-1"></i>Impacts ({{ config.impact_levels_count }}/{{ config.matrix_size }})
                                </a>
                                <a v-if="config.frequency_levels_count < config.matrix_size"
                                   :href="route('risk.core.frequency.index', { config_id: config.id })"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-wave-sine me-1"></i>Fréquences ({{ config.frequency_levels_count }}/{{ config.matrix_size }})
                                </a>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <b-button v-if="!config.is_active" size="sm"
                                      :variant="config.is_complete ? 'outline-primary' : 'light'"
                                      :disabled="!config.is_complete"
                                      @click="confirmActivate(config)"
                                      :title="config.is_complete ? 'Activer' : 'Configuration incomplète'">
                                <i class="ti ti-bolt me-1"></i>Activer
                            </b-button>
                            <b-button size="sm" variant="light" @click="confirmResetZones(config)" title="Réinitialiser les zones">
                                <i class="ti ti-refresh"></i>
                            </b-button>
                            <b-button size="sm" variant="light" @click="openForm(config)" title="Modifier">
                                <i class="ti ti-pencil"></i>
                            </b-button>
                            <b-button v-if="!config.is_active" size="sm" variant="light" class="text-danger"
                                      @click="confirmDelete(config)" title="Supprimer">
                                <i class="ti ti-trash"></i>
                            </b-button>
                        </div>
                    </div>
                </b-card-body>
            </b-card>
        </div>

        <!-- MODALES -->
        <b-modal v-model="modal.show" :title="modalTitle"
                 :ok-title="modalOkTitle" :ok-variant="modalOkVariant"
                 cancel-title="Annuler" @ok="executeModal" centered>
            <div v-if="modal.type === 'activate'">
                <p><strong>{{ modal.target?.name }}</strong> deviendra la configuration active. Toutes les autres seront désactivées.</p>
                <div class="d-flex gap-3">
                    <small class="text-muted"><strong>Taille :</strong> {{ modal.target?.matrix_label }}</small>
                    <small class="text-muted"><strong>Score max :</strong> {{ modal.target?.max_score }}</small>
                    <small class="text-muted"><strong>Zones :</strong> {{ modal.target?.criticality_zones_count }}</small>
                </div>
            </div>
            <div v-else-if="modal.type === 'delete'">
                <p>« <strong>{{ modal.target?.name }}</strong> » sera supprimée ainsi que ses niveaux et zones. Action irréversible.</p>
            </div>
            <div v-else-if="modal.type === 'reset-zones'">
                <p>Les zones de criticité de « <strong>{{ modal.target?.name }}</strong> » seront remplacées par les valeurs par défaut pour une matrice {{ modal.target?.matrix_label }}.</p>
            </div>
        </b-modal>

    </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
    configs: { type: Array, default: () => [] },
})

// Zones par defaut pour chaque taille de matrice (miroir du PHP RiskCriticalityZone::defaultZonesForSize)
const DEFAULT_ZONES = {
    3: [
        { label: 'Faible',    min_score: 1, max_score: 3, color_code: '#22c55e' },
        { label: 'Modéré',   min_score: 4, max_score: 6, color_code: '#eab308' },
        { label: 'Critique', min_score: 7, max_score: 9, color_code: '#ef4444' },
    ],
    4: [
        { label: 'Faible',   min_score: 1,  max_score: 4,  color_code: '#22c55e' },
        { label: 'Modéré',   min_score: 5,  max_score: 8,  color_code: '#eab308' },
        { label: 'Élevé',    min_score: 9,  max_score: 12, color_code: '#f97316' },
        { label: 'Critique', min_score: 13, max_score: 16, color_code: '#ef4444' },
    ],
    5: [
        { label: 'Négligeable', min_score: 1,  max_score: 4,  color_code: '#22c55e' },
        { label: 'Faible',      min_score: 5,  max_score: 9,  color_code: '#84cc16' },
        { label: 'Modéré',      min_score: 10, max_score: 14, color_code: '#eab308' },
        { label: 'Élevé',       min_score: 15, max_score: 19, color_code: '#f97316' },
        { label: 'Critique',    min_score: 20, max_score: 25, color_code: '#ef4444' },
    ],
    6: [
        { label: 'Négligeable', min_score: 1,  max_score: 6,  color_code: '#22c55e' },
        { label: 'Faible',      min_score: 7,  max_score: 13, color_code: '#84cc16' },
        { label: 'Modéré',      min_score: 14, max_score: 22, color_code: '#eab308' },
        { label: 'Élevé',       min_score: 23, max_score: 29, color_code: '#f97316' },
        { label: 'Critique',    min_score: 30, max_score: 36, color_code: '#ef4444' },
    ],
    7: [
        { label: 'Négligeable', min_score: 1,  max_score: 9,  color_code: '#22c55e' },
        { label: 'Faible',      min_score: 10, max_score: 19, color_code: '#84cc16' },
        { label: 'Modéré',      min_score: 20, max_score: 29, color_code: '#eab308' },
        { label: 'Élevé',       min_score: 30, max_score: 39, color_code: '#f97316' },
        { label: 'Critique',    min_score: 40, max_score: 49, color_code: '#ef4444' },
    ],
    8: [
        { label: 'Négligeable', min_score: 1,  max_score: 12, color_code: '#22c55e' },
        { label: 'Faible',      min_score: 13, max_score: 25, color_code: '#84cc16' },
        { label: 'Modéré',      min_score: 26, max_score: 38, color_code: '#eab308' },
        { label: 'Élevé',       min_score: 39, max_score: 51, color_code: '#f97316' },
        { label: 'Critique',    min_score: 52, max_score: 64, color_code: '#ef4444' },
    ],
    9: [
        { label: 'Négligeable', min_score: 1,  max_score: 16, color_code: '#22c55e' },
        { label: 'Faible',      min_score: 17, max_score: 32, color_code: '#84cc16' },
        { label: 'Modéré',      min_score: 33, max_score: 48, color_code: '#eab308' },
        { label: 'Élevé',       min_score: 49, max_score: 64, color_code: '#f97316' },
        { label: 'Critique',    min_score: 65, max_score: 81, color_code: '#ef4444' },
    ],
    10: [
        { label: 'Négligeable', min_score: 1,  max_score: 20,  color_code: '#22c55e' },
        { label: 'Faible',      min_score: 21, max_score: 40,  color_code: '#84cc16' },
        { label: 'Modéré',      min_score: 41, max_score: 60,  color_code: '#eab308' },
        { label: 'Élevé',       min_score: 61, max_score: 80,  color_code: '#f97316' },
        { label: 'Critique',    min_score: 81, max_score: 100, color_code: '#ef4444' },
    ],
}

const showForm   = ref(false)
const editingId  = ref(null)
const processing = ref(false)
const errors     = ref({})
const alert      = ref({ show: false, variant: 'success', message: '' })
const modal      = ref({ show: false, type: null, target: null })
const form       = ref({ name: '', matrix_size: 5, description: '' })

const page      = usePage()
const pageError = computed(() => page.props.errors?.activation ?? page.props.errors?.delete ?? null)
const defaultZones = computed(() => DEFAULT_ZONES[form.value.matrix_size] ?? DEFAULT_ZONES[5])

const modalTitle    = computed(() => ({ activate: 'Activer cette configuration ?', delete: 'Supprimer ?', 'reset-zones': 'Réinitialiser les zones ?' })[modal.value.type] ?? '')
const modalOkTitle  = computed(() => ({ activate: 'Activer', delete: 'Supprimer', 'reset-zones': 'Réinitialiser' })[modal.value.type] ?? 'OK')
const modalOkVariant = computed(() => ({ activate: 'primary', delete: 'danger', 'reset-zones': 'warning' })[modal.value.type] ?? 'primary')

const showAlert = (message, variant = 'success') => {
    alert.value = { show: true, variant, message }
    setTimeout(() => { alert.value.show = false }, 4000)
}

const openForm = (config = null) => {
    editingId.value = config?.id ?? null
    errors.value    = {}
    form.value = config
        ? { name: config.name, matrix_size: config.matrix_size, description: config.description ?? '' }
        : { name: '', matrix_size: 5, description: '' }
    showForm.value = true
}

const closeForm = () => { showForm.value = false; editingId.value = null; errors.value = {} }

const submitForm = () => {
    processing.value = true
    const url    = editingId.value ? route('risk.core.matrix-config.update', editingId.value) : route('risk.core.matrix-config.store')
    const method = editingId.value ? 'put' : 'post'
    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => { closeForm(); processing.value = false; showAlert('Configuration enregistrée.') },
        onError:   (e) => { errors.value = e; processing.value = false },
    })
}

const confirmActivate   = (config) => { modal.value = { show: true, type: 'activate',    target: config } }
const confirmDelete     = (config) => { modal.value = { show: true, type: 'delete',      target: config } }
const confirmResetZones = (config) => { modal.value = { show: true, type: 'reset-zones', target: config } }

const executeModal = () => {
    const t = modal.value.type
    const id = modal.value.target.id
    if      (t === 'activate')    router.post(route('risk.core.matrix-config.activate', id), {}, { preserveScroll: true, onSuccess: () => showAlert('Configuration activée.') })
    else if (t === 'delete')      router.delete(route('risk.core.matrix-config.destroy', id), { preserveScroll: true, onSuccess: () => showAlert('Configuration supprimée.') })
    else if (t === 'reset-zones') router.post(route('risk.core.matrix-config.reset-zones', id), {}, { preserveScroll: true, onSuccess: () => showAlert('Zones réinitialisées.') })
}

const fillPercent = (count, size) => Math.round((count / size) * 100)
</script>

<style scoped>
.form-control-sm, .form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.btn-sm { padding:.15rem .45rem; font-size:.72rem }
.stat-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:18px }
.stat-card { transition:all .2s }
.stat-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.1); transform:translateY(-2px) }
.config-card { transition:all .2s }
.config-card:hover { box-shadow:0 3px 10px rgba(0,0,0,.08) }
.matrix-size-badge { width:56px; height:56px; border-radius:10px; display:flex; flex-direction:column; align-items:center; justify-content:center; background-color:rgba(0,0,0,.05); flex-shrink:0 }
.apt-badge { padding:.1rem .5rem; border-radius:12px; font-size:.7rem; font-weight:700; border:1px solid; display:inline-flex; align-items:center }
.color-dot { width:8px; height:8px; border-radius:50%; display:inline-block; flex-shrink:0 }

/* Sélecteur de taille en 2 rangées */
.size-picker { display:flex; flex-direction:column; gap:3px }
.size-picker--disabled { opacity:.6; pointer-events:none }
.size-row { display:flex; gap:3px }
.size-btn { flex:1; padding:.1rem .2rem; font-size:.68rem; min-width:0; white-space:nowrap }
</style>
