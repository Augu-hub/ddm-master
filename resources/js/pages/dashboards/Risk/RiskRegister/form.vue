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
                <!-- Bouton Import bibliothèque (création uniquement) -->
                <LibraryImportPicker v-if="!isEdit" @apply="applyLibraryImport" />

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

                        <!-- 1. Localisation -->
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

                        <!-- 2. Identification -->
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
                                        id="field-libelle"
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

                        <!-- 3. Contexte & Parties prenantes -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-0 pb-0 pt-3">
                                <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.05em">
                                    <i class="ti ti-users me-1"></i>Contexte &amp; Parties prenantes
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Entité partenaire impliquée</label>
                                        <textarea v-model="form.entite_partenaire_impliquee" class="form-control" rows="2" placeholder="Entité(s) partenaire(s) concernée(s) par ce risque…"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Outils utilisés</label>
                                        <textarea v-model="form.outils_utilises" class="form-control" rows="2" placeholder="Logiciels, systèmes ou outils impliqués…"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Vraisemblance d'apparition</label>
                                        <input v-model="form.vraisemblance_apparition" type="text" class="form-control" placeholder="Ex : Rare, Occasionnel, Fréquent…" maxlength="255" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Critère du risque</label>
                                        <input v-model="form.critere_risque" type="text" class="form-control" placeholder="Critère de référence applicable…" maxlength="255" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Analyse du risque -->
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
                                        <label class="form-label fw-semibold">Conséquences sur d'autres processus</label>
                                        <textarea v-model="form.consequences_autres_processus" class="form-control" rows="2" placeholder="Processus impactés en cascade…"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Coût des conséquences</label>
                                        <textarea v-model="form.cout_consequences" class="form-control" rows="2" placeholder="Estimation qualitative ou quantitative des coûts engendrés…"></textarea>
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
                                    <i class="ti ti-settings me-1"></i>Gestion
                                </span>
                            </div>
                            <div class="card-body">

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

                                <!-- Risque réalisé -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input
                                            id="risque_realise"
                                            v-model="form.risque_realise"
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                        />
                                        <label class="form-check-label fw-semibold" for="risque_realise">
                                            Risque réalisé
                                        </label>
                                    </div>
                                    <small class="text-muted">Cocher si le risque s'est effectivement matérialisé.</small>
                                </div>

                                <!-- Coût du risque -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Coût du risque</label>
                                    <div class="input-group">
                                        <input
                                            v-model="form.cout_risque"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-control"
                                            placeholder="0.00"
                                        />
                                        <span class="input-group-text text-muted">€</span>
                                    </div>
                                </div>

                                <!-- Lien incident -->
                                <div v-if="form.incident_id" class="alert alert-info border-0 py-2 small mt-2 mb-0">
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
import LibraryImportPicker from './LibraryImportPicker.vue'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    risk:          { type: Object, default: null },
    entities:      { type: Array,  default: () => [] },
    nomenclatures: { type: Array,  default: () => [] },
    fromIncident:  { type: Object, default: null },
})

const isEdit = computed(() => !!props.risk)

// ── Formulaire ─────────────────────────────────────────────────────────────
const form = ref({
    libelle:                       props.risk?.libelle                       ?? props.fromIncident?.libelle      ?? '',
    description:                   props.risk?.description                   ?? props.fromIncident?.description  ?? '',
    entity_id:                     props.risk?.entity_id                     ?? '',
    activity_id:                   props.risk?.activity_id                   ?? '',
    nomenclature_id:               props.risk?.nomenclature_id               ?? '',
    causes:                        props.risk?.causes                        ?? '',
    consequences:                  props.risk?.consequences                  ?? '',
    consequences_autres_processus: props.risk?.consequences_autres_processus ?? '',
    cout_consequences:             props.risk?.cout_consequences             ?? '',
    controles_existants:           props.risk?.controles_existants           ?? '',
    owner:                         props.risk?.owner                         ?? '',
    entite_partenaire_impliquee:   props.risk?.entite_partenaire_impliquee   ?? '',
    outils_utilises:              props.risk?.outils_utilises              ?? '',
    vraisemblance_apparition:      props.risk?.vraisemblance_apparition      ?? '',
    plan_traitement:               props.risk?.plan_traitement               ?? '',
    critere_risque:                props.risk?.critere_risque                ?? '',
    statut:                        props.risk?.statut                        ?? 'draft',
    risque_realise:                props.risk?.risque_realise                ?? false,
    cout_risque:                   props.risk?.cout_risque                   ?? null,
    incident_id:                   props.risk?.incident_id                   ?? props.fromIncident?.incident_id ?? null,
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
    nomenCtx.value = {
        domaine: n.level === 1 ? n.label : '',
        famille: n.level === 2 ? n.label : '',
        type:    n.level === 3 ? n.label : n.label,
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

// ── Application import depuis bibliothèque ────────────────────────────────
function applyLibraryImport(item) {
    form.value.libelle                       = item.libelle                       ?? form.value.libelle
    form.value.description                   = item.description                   ?? form.value.description
    form.value.causes                        = item.causes                        ?? form.value.causes
    form.value.consequences                  = item.consequences                  ?? form.value.consequences
    form.value.consequences_autres_processus = item.consequences_autres_processus ?? form.value.consequences_autres_processus
    form.value.cout_consequences             = item.cout_consequences             ?? form.value.cout_consequences
    form.value.controles_existants           = item.controles_existants           ?? form.value.controles_existants
    form.value.plan_traitement               = item.plan_traitement               ?? form.value.plan_traitement
    form.value.critere_risque                = item.critere_risque                ?? form.value.critere_risque
    form.value.entite_partenaire_impliquee   = item.entite_partenaire_impliquee   ?? form.value.entite_partenaire_impliquee
    form.value.outils_utilises              = item.outils_utilises              ?? form.value.outils_utilises
    form.value.vraisemblance_apparition      = item.vraisemblance_apparition      ?? form.value.vraisemblance_apparition
    form.value.owner                         = item.owner                         ?? form.value.owner

    // Nomenclature — uniquement si non déjà sélectionnée
    if (!form.value.nomenclature_id && item.nomenclature_id) {
        form.value.nomenclature_id = item.nomenclature_id
        nextTick(() => onNomenclatureChange())
    }

    // Coût — uniquement pour les incidents (montant évalué)
    if (item._type === 'incident' && item.cout_risque) {
        form.value.cout_risque = item.cout_risque
    }

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
    if (form.value.nomenclature_id) {
        onNomenclatureChange()
    }
})
</script>
