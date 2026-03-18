<template>

    <button
        type="button"
        class="btn btn-sm d-flex align-items-center gap-2"
        style="background:rgba(99,84,207,.1);color:#6354cf;border:1px solid rgba(99,84,207,.3)"
        :disabled="!canSuggest || loading"
        :title="!canSuggest ? 'Sélectionnez d\'abord une activité' : 'Générer des suggestions avec Mistral AI'"
        @click="openModal"
    >
        <span v-if="loading" class="spinner-border spinner-border-sm"></span>
        <i v-else class="ti ti-sparkles"></i>
        <span>{{ loading ? 'Analyse…' : 'Suggérer via Mistral AI' }}</span>
    </button>

    <BModal
        v-model="showModal"
        size="xl"
        hide-footer
        scrollable
        @hidden="onHidden"
    >
        <template #header>
            <div class="d-flex align-items-center gap-3 w-100 pe-2">
                <div class="rounded-3 p-2 flex-shrink-0" style="background:rgba(99,84,207,.1)">
                    <i class="ti ti-sparkles" style="color:#6354cf;font-size:1.1rem"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold">Suggestions Mistral AI</div>
                    <small class="text-muted">
                        {{ activiteNom }} · {{ nomenclatureType || nomenclatureFamille || nomenclatureDomaine || 'Tous types' }}
                    </small>
                </div>
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm me-2"
                    :disabled="loading"
                    @click="fetchSuggestions"
                    title="Régénérer"
                >
                    <i class="ti ti-refresh" :class="{ 'spin': loading }"></i>
                </button>
                <button type="button" class="btn-close" @click="showModal = false"></button>
            </div>
        </template>

        <!-- Chargement -->
        <div v-if="loading" class="text-center py-5">
            <div class="mb-3" style="width:2.5rem;height:2.5rem;border:.3rem solid rgba(99,84,207,.2);border-top-color:#6354cf;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto"></div>
            <div class="text-muted">Analyse du contexte et génération des risques…</div>
            <small class="text-muted">ISO 31000 · COSO ERM</small>
        </div>

        <!-- Erreur -->
        <div v-else-if="error" class="m-3">
            <div class="alert alert-danger border-0 rounded-3 d-flex align-items-center gap-3">
                <i class="ti ti-alert-circle fs-5"></i>
                <span class="flex-grow-1">{{ error }}</span>
                <button type="button" class="btn btn-outline-danger btn-sm" @click="fetchSuggestions">Réessayer</button>
            </div>
        </div>

        <!-- Cards suggestions -->
        <div v-else-if="suggestions.length" class="p-3">

            <p class="text-muted small mb-3">
                <i class="ti ti-info-circle me-1"></i>
                Cliquez sur une suggestion pour la sélectionner, puis cliquez sur <strong>Utiliser</strong>.
            </p>

            <div class="row g-3">
                <div v-for="(risk, idx) in suggestions" :key="idx" class="col-12 col-lg-6">
                    <div
                        class="card h-100 suggestion-card"
                        :class="{ selected: selectedIdx === idx }"
                        style="cursor:pointer;transition:border-color .15s,box-shadow .15s"
                        @click="selectedIdx = selectedIdx === idx ? null : idx"
                    >
                        <div class="card-body">

                            <div class="d-flex align-items-start gap-2 mb-3">
                <span
                    class="badge rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center"
                    style="width:26px;height:26px;background:rgba(99,84,207,.12);color:#6354cf;font-size:.75rem;font-weight:700"
                >{{ idx + 1 }}</span>
                                <div class="fw-semibold lh-sm" style="font-size:.9rem">{{ risk.libelle }}</div>
                            </div>

                            <div class="vstack gap-2">
                                <RiskDetailBlock v-if="risk.causes"              icon="ti-bulb"          label="Causes"                :value="risk.causes" />
                                <RiskDetailBlock v-if="risk.consequences"        icon="ti-alert-triangle" label="Conséquences"          :value="risk.consequences" />
                                <RiskDetailBlock v-if="risk.controles_existants" icon="ti-shield"         label="Contrôles recommandés" :value="risk.controles_existants" />
                                <RiskDetailBlock v-if="risk.plan_traitement"     icon="ti-list-check"     label="Plan de traitement"   :value="risk.plan_traitement" />
                            </div>

                        </div>
                        <div
                            class="card-footer text-center py-2"
                            :style="selectedIdx === idx
                ? 'background:rgba(99,84,207,.07);border-color:rgba(99,84,207,.3)'
                : 'background:transparent;border-color:var(--bs-border-color)'"
                        >
              <span v-if="selectedIdx === idx" class="small fw-semibold" style="color:#6354cf">
                <i class="ti ti-check me-1"></i>Sélectionné
              </span>
                            <span v-else class="small text-muted">Cliquer pour sélectionner</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary" @click="showModal = false">Annuler</button>
                <button
                    type="button"
                    class="btn btn-primary px-4"
                    :disabled="selectedIdx === null"
                    @click="applySelection"
                >
                    <i class="ti ti-check me-1"></i>Utiliser cette suggestion
                </button>
            </div>

        </div>

    </BModal>

</template>

<script setup>
import { ref, computed } from 'vue'
import { BModal } from 'bootstrap-vue-next'
import axios from 'axios'

// Composant interne léger
const RiskDetailBlock = {
    props: { icon: String, label: String, value: String },
    template: `
    <div>
      <div class="text-muted mb-1" style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em">
        <i :class="'ti ' + icon + ' me-1'"></i>{{ label }}
      </div>
      <div class="small text-muted" style="line-height:1.45">{{ value }}</div>
    </div>
  `,
}

const props = defineProps({
    secteur:             { type: String, default: '' },
    activiteCode:        { type: String, default: '' },
    activiteNom:         { type: String, default: '' },
    processusCode:       { type: String, default: '' },
    processusNom:        { type: String, default: '' },
    macroProcessus:      { type: String, default: '' },
    nomenclatureDomaine: { type: String, default: '' },
    nomenclatureFamille: { type: String, default: '' },
    nomenclatureType:    { type: String, default: '' },
})

const emit = defineEmits(['apply'])

const showModal   = ref(false)
const loading     = ref(false)
const error       = ref(null)
const suggestions = ref([])
const selectedIdx = ref(null)

const canSuggest = computed(() => !!props.activiteCode && !!props.activiteNom)

async function openModal() {
    showModal.value = true
    if (suggestions.value.length === 0) {
        await fetchSuggestions()
    }
}

async function fetchSuggestions() {
    loading.value     = true
    error.value       = null
    suggestions.value = []
    selectedIdx.value = null

    try {
        const { data } = await axios.post(route('risk.core.risks.mistral.suggest'), {
            secteur:              props.secteur,
            activite_code:        props.activiteCode,
            activite_nom:         props.activiteNom,
            processus_code:       props.processusCode,
            processus_nom:        props.processusNom,
            macro_processus:      props.macroProcessus,
            nomenclature_domaine: props.nomenclatureDomaine,
            nomenclature_famille: props.nomenclatureFamille,
            nomenclature_type:    props.nomenclatureType,
        })

        if (data.error) {
            error.value = data.error
        } else {
            suggestions.value = data.suggestions ?? []
            if (!suggestions.value.length) {
                error.value = 'Aucune suggestion retournée. Vérifiez la configuration Mistral AI.'
            }
        }
    } catch {
        error.value = 'Impossible de contacter le service Mistral AI.'
    } finally {
        loading.value = false
    }
}

function applySelection() {
    if (selectedIdx.value === null) return
    emit('apply', { ...suggestions.value[selectedIdx.value] })
    showModal.value = false
}

function onHidden() {
    selectedIdx.value = null
    error.value = null
    // On conserve suggestions pour éviter un re-appel si même contexte
}
</script>

<style scoped>
.suggestion-card { border: 1px solid var(--bs-border-color); }
.suggestion-card:hover { border-color: rgba(99,84,207,.4); box-shadow: 0 2px 12px rgba(99,84,207,.08); }
.suggestion-card.selected { border-color: rgba(99,84,207,.5) !important; box-shadow: 0 0 0 3px rgba(99,84,207,.12); }
.spin { animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
