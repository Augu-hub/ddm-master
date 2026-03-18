<template>
    <VerticalLayout>
        <div class="container-fluid py-3" style="max-width:920px">

            <!-- En-tête -->
            <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                <Link :href="route('risk.core.risks.index')" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left"></i>
                </Link>
                <div class="flex-grow-1">
                    <h4 class="mb-0 fw-semibold">
                        <i class="ti ti-shield-plus me-2 text-primary"></i>
                        {{ isEdit ? `Modifier — ${risk?.code_risk}` : 'Nouveau risque' }}
                    </h4>
                    <small v-if="fromIncident" class="text-info">
                        <i class="ti ti-link me-1"></i>
                        Converti depuis l'incident {{ fromIncident.code_origine }}
                    </small>
                </div>
                <!-- Bouton Mistral AI -->
                <MistralRiskSuggester
                    :secteur="secteurEntite"
                    :activite-code="actCtx.activity_code"
                    :activite-nom="actCtx.activity_name"
                    :processus-code="actCtx.process_code"
                    :processus-nom="actCtx.process_name"
                    :macro-processus="actCtx.macro_name"
                    :nomenclature-domaine="nomenCtx.domaine"
                    :nomenclature-famille="nomenCtx.famille"
                    :nomenclature-type="nomenCtx.type"
                    @apply="applyMistral"
                />
            </div>

            <form @submit.prevent="submitForm">
                <div class="row g-4">

                    <!-- ── Colonne principale ──────────────────────────────────────── -->
                    <div class="col-lg-8">

                        <!-- Identification -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-0 pb-0 pt-3">
                <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.05em">
                  <i class="ti ti-id me-1"></i>Identification
                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Libellé <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.libelle"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.libelle }"
                                        placeholder="Intitulé du risque…"
                                        maxlength="255"
                                    />
                                    <div v-if="errors.libelle" class="invalid-feedback">{{ errors.libelle }}</div>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea v-model="form.description" class="form-control" rows="2" placeholder="Description générale…"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Localisation -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-0 pb-0 pt-3">
                <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.05em">
                  <i class="ti ti-sitemap me-1"></i>Localisation du risque
                </span>
                            </div>
                            <div class="card-body">
                                <!-- Arbre entité → activité -->
                                <ActivityTreePicker
                                    :entities="entities"
                                    v-model:model-entity-id="form.entity_id"
                                    v-model:model-activity-id="form.activity_id"
                                    :error="errors.entity_id || errors.activity_id"
                                    @selected="onActivitySelected"
                                />

                                <!-- Nomenclature -->
                                <div class="mt-3">
                                    <label class="form-label fw-semibold">
                                        Type de risque (nomenclature) <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        v-model="form.nomenclature_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': errors.nomenclature_id }"
                                        @change="onNomenclatureChange"
                                    >
                                        <option value="">— Sélectionner un type —</option>
                                        <option v-for="n in nomenclatures" :key="n.id" :value="n.id">{{ n.label }}</option>
                                    </select>
                                    <div v-if="errors.nomenclature_id" class="invalid-feedback">{{ errors.nomenclature_id }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Analyse du risque -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-0 pb-0 pt-3">
                <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.05em">
                  <i class="ti ti-clipboard-text me-1"></i>Analyse du risque
                </span>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Causes</label>
                                        <textarea v-model="form.causes" class="form-control" rows="3" placeholder="Quelles sont les causes potentielles de ce risque ?"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Conséquences / impacts</label>
                                        <textarea v-model="form.consequences" class="form-control" rows="3" placeholder="Quels seraient les impacts si ce risque se matérialisait ?"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Contrôles existants</label>
                                        <textarea v-model="form.controles_existants" class="form-control" rows="3" placeholder="Dispositifs de maîtrise déjà en place…"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Plan de traitement</label>
                                        <textarea v-model="form.plan_traitement" class="form-control" rows="3" placeholder="Actions prévues pour réduire ou traiter ce risque…"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Colonne latérale ───────────────────────────────────────── -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm sticky-top" style="top:80px">
                            <div class="card-header bg-transparent border-0 pb-0 pt-3">
                <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.05em">
                  <i class="ti ti-flame me-1"></i>Évaluation
                </span>
                            </div>
                            <div class="card-body">

                                <!-- Impact -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Niveau d'impact</label>
                                    <div class="d-flex flex-column gap-2">
                                        <label
                                            v-for="lvl in impactLevels"
                                            :key="lvl.id"
                                            class="d-flex align-items-center gap-2 p-2 rounded-2"
                                            :style="form.impact_level_id === lvl.id
                        ? `background:${lvl.color}20;border:1px solid ${lvl.color}60;cursor:pointer`
                        : 'border:1px solid var(--bs-border-color);cursor:pointer'"
                                        >
                                            <input type="radio" v-model="form.impact_level_id" :value="lvl.id" class="form-check-input mt-0" @change="computeCriticality" />
                                            <span class="badge rounded-pill" :style="`background:${lvl.color};color:#fff`">{{ lvl.score }}</span>
                                            <span class="small fw-semibold">{{ lvl.label }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Fréquence -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Niveau de fréquence</label>
                                    <div class="d-flex flex-column gap-2">
                                        <label
                                            v-for="lvl in frequencyLevels"
                                            :key="lvl.id"
                                            class="d-flex align-items-center gap-2 p-2 rounded-2"
                                            :style="form.frequency_level_id === lvl.id
                        ? `background:${lvl.color}20;border:1px solid ${lvl.color}60;cursor:pointer`
                        : 'border:1px solid var(--bs-border-color);cursor:pointer'"
                                        >
                                            <input type="radio" v-model="form.frequency_level_id" :value="lvl.id" class="form-check-input mt-0" @change="computeCriticality" />
                                            <span class="badge rounded-pill" :style="`background:${lvl.color};color:#fff`">{{ lvl.score }}</span>
                                            <span class="small fw-semibold">{{ lvl.label }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Résultat criticité preview -->
                                <div
                                    v-if="critPreview"
                                    class="rounded-3 p-3 text-center mb-3"
                                    :style="`background:${critPreview.color}15;border:2px solid ${critPreview.color}40`"
                                >
                                    <div class="small text-muted mb-1">Criticité estimée</div>
                                    <div class="fw-bold fs-5" :style="`color:${critPreview.color}`">Score {{ critPreview.score }}</div>
                                    <div class="small text-muted">Le calcul définitif est fait côté serveur</div>
                                </div>
                                <div v-else class="text-center text-muted small py-2 mb-3">
                                    Sélectionnez impact + fréquence
                                </div>

                                <hr class="my-3" />

                                <!-- Owner -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Responsable (owner)</label>
                                    <input v-model="form.owner" type="text" class="form-control" placeholder="Nom ou fonction…" />
                                </div>

                                <!-- Statut -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Statut</label>
                                    <select v-model="form.statut" class="form-select">
                                        <option value="draft">Brouillon</option>
                                        <option value="actif">Actif</option>
                                    </select>
                                </div>

                                <!-- Lien incident -->
                                <div v-if="form.incident_id" class="alert alert-info border-0 py-2 small mb-0">
                                    <i class="ti ti-link me-1"></i>
                                    Lié à l'incident {{ fromIncident?.code_origine }}
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons soumission -->
                <div class="d-flex justify-content-end gap-2 pt-3 pb-5 border-top mt-2">
                    <Link :href="route('risk.core.risks.index')" class="btn btn-outline-secondary">Annuler</Link>
                    <button type="submit" class="btn btn-primary px-4" :disabled="submitting">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ti ti-check me-1"></i>
                        {{ isEdit ? 'Enregistrer les modifications' : 'Créer le risque' }}
                    </button>
                </div>
            </form>
        </div>
    </VerticalLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import ActivityTreePicker from './activitytreepicker.vue'
import MistralRiskSuggester from './mistralrisksuggester.vue'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    risk:             { type: Object, default: null },
    entities:         { type: Array,  default: () => [] },
    nomenclatures:    { type: Array,  default: () => [] },
    impactLevels:     { type: Array,  default: () => [] },
    frequencyLevels:  { type: Array,  default: () => [] },
    fromIncident:     { type: Object, default: null },
})

const isEdit = computed(() => !!props.risk)

// ── Formulaire ─────────────────────────────────────────────────────────────
const form = ref({
    libelle:              props.risk?.libelle             ?? props.fromIncident?.libelle             ?? '',
    description:          props.risk?.description         ?? props.fromIncident?.description         ?? '',
    entity_id:            props.risk?.entity_id           ?? '',
    activity_id:          props.risk?.activity_id         ?? '',
    nomenclature_id:      props.risk?.nomenclature_id     ?? '',
    causes:               props.risk?.causes              ?? '',
    consequences:         props.risk?.consequences        ?? '',
    controles_existants:  props.risk?.controles_existants ?? '',
    owner:                props.risk?.owner               ?? '',
    plan_traitement:      props.risk?.plan_traitement     ?? '',
    impact_level_id:      props.risk?.impact_level_id     ?? null,
    frequency_level_id:   props.risk?.frequency_level_id  ?? null,
    statut:               props.risk?.statut              ?? 'draft',
    incident_id:          props.risk?.incident_id         ?? props.fromIncident?.incident_id ?? null,
})

const errors     = ref({})
const submitting = ref(false)

// ── Contexte activité (alimenté par ActivityTreePicker @selected) ──────────
const actCtx = ref({
    activity_code: props.risk?.activity_code    ?? '',
    activity_name: props.risk?.activity_name    ?? '',
    process_code:  props.risk?.process_code     ?? '',
    process_name:  props.risk?.process_name     ?? '',
    macro_name:    props.risk?.macro_process_name
        ? `${props.risk.macro_process_code} — ${props.risk.macro_process_name}`
        : '',
})

function onActivitySelected(payload) {
    if (!payload) {
        form.value.activity_id = ''
        actCtx.value = { activity_code: '', activity_name: '', process_code: '', process_name: '', macro_name: '' }
        return
    }
    form.value.activity_id = payload.activity.id
    actCtx.value = {
        activity_code: payload.activity.code,
        activity_name: payload.activity.name,
        process_code:  payload.process.code,
        process_name:  payload.process.name,
        macro_name:    `${payload.macro.code} — ${payload.macro.name}`,
    }
}

// ── Secteur de l'entité ────────────────────────────────────────────────────
const secteurEntite = computed(() => {
    if (!form.value.entity_id) return ''
    const e = props.entities.find(e => e.id === form.value.entity_id)
    return e?.secteur ?? e?.description ?? e?.name ?? ''
})

// ── Contexte nomenclature pour Mistral ────────────────────────────────────
const nomenCtx = ref({ domaine: '', famille: '', type: '' })

function onNomenclatureChange() {
    const n = props.nomenclatures.find(n => n.id === form.value.nomenclature_id)
    if (!n) { nomenCtx.value = { domaine: '', famille: '', type: '' }; return }
    // Adapter selon la structure réelle de risk_nomenclatures
    nomenCtx.value = {
        domaine: n.level === 1 ? n.label : '',
        famille: n.level === 2 ? n.label : '',
        type:    n.level === 3 ? n.label : n.label,
    }
}

// ── Criticité preview (côté client uniquement) ────────────────────────────
const critPreview = ref(null)

function computeCriticality() {
    if (!form.value.impact_level_id || !form.value.frequency_level_id) {
        critPreview.value = null
        return
    }
    const impact = props.impactLevels.find(l => l.id === form.value.impact_level_id)
    const freq   = props.frequencyLevels.find(l => l.id === form.value.frequency_level_id)
    if (!impact || !freq) return
    critPreview.value = {
        score: parseFloat(impact.score) * parseFloat(freq.score),
        color: impact.color,   // color_code renvoyé par le controller sous la clé 'color'
    }
}

// ── Application suggestion Mistral ────────────────────────────────────────
function applyMistral(suggestion) {
    form.value.libelle             = suggestion.libelle
    form.value.causes              = suggestion.causes
    form.value.consequences        = suggestion.consequences
    form.value.controles_existants = suggestion.controles_existants
    form.value.plan_traitement     = suggestion.plan_traitement
    nextTick(() => {
        document.getElementById('field-libelle')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    })
}

// ── Soumission ─────────────────────────────────────────────────────────────
function submitForm() {
    submitting.value = true
    errors.value     = {}

    const url    = isEdit.value ? route('risk.core.risks.update', props.risk.id) : route('risk.core.risks.store')
    const method = isEdit.value ? 'put' : 'post'

    router[method](url, form.value, {
        onError:  (e) => { errors.value = e },
        onFinish: () => { submitting.value = false },
    })
}

// ── Init ───────────────────────────────────────────────────────────────────
onMounted(() => {
    if (form.value.impact_level_id && form.value.frequency_level_id) {
        computeCriticality()
    }
    if (form.value.nomenclature_id) {
        onNomenclatureChange()
    }
})
</script>
