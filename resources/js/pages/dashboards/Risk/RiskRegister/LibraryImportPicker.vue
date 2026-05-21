<template>
    <!-- Bouton déclencheur -->
    <button type="button" class="btn btn-outline-secondary btn-sm" @click="open">
        <i class="ti ti-library me-1"></i>Importer depuis la bibliothèque
    </button>

    <!-- Modal -->
    <BModal
        v-model="show"
        title="Importer depuis la bibliothèque"
        size="lg"
        hide-footer
        scrollable
    >
        <template #header>
            <div class="d-flex align-items-center gap-2 w-100">
                <i class="ti ti-library text-primary fs-5"></i>
                <span class="fw-semibold">Importer depuis la bibliothèque</span>
                <button type="button" class="btn-close ms-auto" @click="show = false"></button>
            </div>
        </template>

        <!-- Chargement -->
        <div v-if="loading" class="text-center py-5 text-muted">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Chargement de la bibliothèque…
        </div>

        <template v-else>
            <!-- Onglets -->
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <button
                        class="nav-link"
                        :class="{ active: tab === 'risks' }"
                        @click="tab = 'risks'"
                    >
                        <i class="ti ti-shield me-1"></i>
                        Risques
                        <span class="badge bg-primary-subtle text-primary ms-1">{{ risks.length }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button
                        class="nav-link"
                        :class="{ active: tab === 'incidents' }"
                        @click="tab = 'incidents'"
                    >
                        <i class="ti ti-alert-triangle me-1"></i>
                        Incidents
                        <span class="badge bg-warning-subtle text-warning ms-1">{{ incidents.length }}</span>
                    </button>
                </li>
            </ul>

            <!-- Recherche -->
            <div class="mb-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input
                        v-model="search"
                        type="text"
                        class="form-control"
                        placeholder="Rechercher par code, libellé, processus…"
                        autofocus
                    />
                    <button v-if="search" class="btn btn-outline-secondary" @click="search = ''">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>

            <!-- Liste risques -->
            <div v-if="tab === 'risks'">
                <div v-if="!filteredRisks.length" class="text-center py-4 text-muted">
                    <i class="ti ti-shield-off d-block fs-2 mb-2 opacity-25"></i>
                    Aucun risque en bibliothèque{{ search ? ' pour cette recherche' : '' }}.
                </div>
                <div
                    v-for="risk in filteredRisks"
                    :key="risk.id"
                    class="card border mb-2 shadow-none"
                    :class="selected?.id === risk.id && selected?._type === 'risk' ? 'border-primary' : 'border-light'"
                    style="cursor:pointer; transition: border-color .15s"
                    @click="select(risk)"
                >
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-start gap-2">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="badge bg-primary-subtle text-primary font-monospace">{{ risk.code }}</span>
                                    <span v-if="risk.nomenclature_label" class="badge bg-secondary-subtle text-secondary small">
                                        <i class="ti ti-tag me-1"></i>{{ risk.nomenclature_label }}
                                    </span>
                                    <small class="text-muted ms-auto">{{ risk.moved_to_library_at }}</small>
                                </div>
                                <div class="fw-semibold small">{{ risk.libelle }}</div>
                                <div v-if="risk.macro_process_name || risk.process_name || risk.activity_name" class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                    <small class="text-muted">
                                        <i class="ti ti-sitemap me-1"></i>
                                        {{ [risk.macro_process_name, risk.process_name, risk.activity_name].filter(Boolean).join(' › ') }}
                                    </small>
                                </div>
                                <div v-if="risk.description" class="small text-muted mt-1 text-truncate" style="max-width:480px">
                                    {{ risk.description }}
                                </div>
                            </div>
                            <div class="flex-shrink-0 pt-1">
                                <i
                                    class="ti fs-5"
                                    :class="selected?.id === risk.id && selected?._type === 'risk'
                                        ? 'ti-circle-check text-primary'
                                        : 'ti-circle text-muted'"
                                ></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste incidents -->
            <div v-if="tab === 'incidents'">
                <div v-if="!filteredIncidents.length" class="text-center py-4 text-muted">
                    <i class="ti ti-alert-circle d-block fs-2 mb-2 opacity-25"></i>
                    Aucun incident en bibliothèque{{ search ? ' pour cette recherche' : '' }}.
                </div>
                <div
                    v-for="incident in filteredIncidents"
                    :key="incident.id"
                    class="card border mb-2 shadow-none"
                    :class="selected?.id === incident.id && selected?._type === 'incident' ? 'border-primary' : 'border-light'"
                    style="cursor:pointer; transition: border-color .15s"
                    @click="select(incident)"
                >
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-start gap-2">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="badge bg-warning-subtle text-warning font-monospace">{{ incident.code }}</span>
                                    <span v-if="incident.source" class="badge bg-secondary-subtle text-secondary small">
                                        {{ incident.source }}
                                    </span>
                                    <small v-if="incident.date_incident" class="text-muted">
                                        <i class="ti ti-calendar me-1"></i>{{ incident.date_incident }}
                                    </small>
                                    <small class="text-muted ms-auto">Biblio. {{ incident.moved_to_library_at }}</small>
                                </div>
                                <div class="fw-semibold small">{{ incident.libelle }}</div>
                                <div v-if="incident.description" class="small text-muted mt-1 text-truncate" style="max-width:480px">
                                    {{ incident.description }}
                                </div>
                                <div v-if="incident.cout_risque" class="small text-muted mt-1">
                                    <i class="ti ti-coin me-1"></i>Évaluation : {{ formatAmount(incident.cout_risque) }}
                                </div>
                            </div>
                            <div class="flex-shrink-0 pt-1">
                                <i
                                    class="ti fs-5"
                                    :class="selected?.id === incident.id && selected?._type === 'incident'
                                        ? 'ti-circle-check text-primary'
                                        : 'ti-circle text-muted'"
                                ></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Footer -->
        <template #footer>
            <div class="d-flex align-items-center justify-content-between w-100 gap-2">
                <div v-if="selected" class="small text-muted">
                    <i class="ti ti-check text-success me-1"></i>
                    <strong>{{ selected.code }}</strong> — {{ selected.libelle }}
                </div>
                <div v-else class="small text-muted">Sélectionnez un élément dans la liste.</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="show = false">Annuler</button>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        :disabled="!selected"
                        @click="applySelection"
                    >
                        <i class="ti ti-download me-1"></i>Importer
                    </button>
                </div>
            </div>
        </template>
    </BModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { BModal } from 'bootstrap-vue-next'

// ── Emits ──────────────────────────────────────────────────────────────────
const emit = defineEmits(['apply'])

// ── State ──────────────────────────────────────────────────────────────────
const show     = ref(false)
const loading  = ref(false)
const tab      = ref('risks')
const search   = ref('')
const risks    = ref([])
const incidents = ref([])
const selected = ref(null)

// ── Filtres ────────────────────────────────────────────────────────────────
const filteredRisks = computed(() => {
    if (!search.value) return risks.value
    const q = search.value.toLowerCase()
    return risks.value.filter(r =>
        r.code?.toLowerCase().includes(q) ||
        r.libelle?.toLowerCase().includes(q) ||
        r.activity_name?.toLowerCase().includes(q) ||
        r.process_name?.toLowerCase().includes(q) ||
        r.macro_process_name?.toLowerCase().includes(q) ||
        r.nomenclature_label?.toLowerCase().includes(q)
    )
})

const filteredIncidents = computed(() => {
    if (!search.value) return incidents.value
    const q = search.value.toLowerCase()
    return incidents.value.filter(i =>
        i.code?.toLowerCase().includes(q) ||
        i.libelle?.toLowerCase().includes(q) ||
        i.source?.toLowerCase().includes(q)
    )
})

// ── Ouverture : chargement des données ────────────────────────────────────
async function open() {
    show.value     = true
    selected.value = null
    search.value   = ''

    if (risks.value.length || incidents.value.length) return   // déjà chargé

    loading.value = true
    try {
        const res  = await fetch(route('risk.core.risks.library-items'))
        const data = await res.json()
        risks.value     = data.risks     ?? []
        incidents.value = data.incidents ?? []
    } catch (e) {
        console.error('Erreur chargement bibliothèque', e)
    } finally {
        loading.value = false
    }
}

// ── Sélection ─────────────────────────────────────────────────────────────
function select(item) {
    selected.value = selected.value?.id === item.id && selected.value?._type === item._type
        ? null
        : item
}

// ── Application ───────────────────────────────────────────────────────────
function applySelection() {
    if (!selected.value) return
    emit('apply', { ...selected.value })
    show.value = false
}

// ── Helpers ───────────────────────────────────────────────────────────────
function formatAmount(val) {
    if (!val) return '—'
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(val)
}
</script>
