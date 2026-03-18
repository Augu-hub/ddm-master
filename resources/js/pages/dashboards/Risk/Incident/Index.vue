<template>
    <VerticalLayout>
        <div class="container-fluid py-3">

            <!-- En-tête -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-0 fw-semibold">
                        <i class="ti ti-alert-triangle me-2 text-warning"></i>
                        Incidents actifs
                    </h4>
                    <small class="text-muted">Identification et suivi des incidents en cours</small>
                </div>
                <div class="d-flex gap-2">
                    <Link :href="route('risk.core.incident-library.index')" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-books me-1"></i>
                        Bibliothèque ({{ stats.total_bibliotheque ?? 0 }})
                    </Link>
                    <button class="btn btn-primary btn-sm" @click="openCreate">
                        <i class="ti ti-plus me-1"></i>
                        Nouvel incident
                    </button>
                </div>
            </div>

            <!-- Cartes stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-warning bg-opacity-10 rounded-3 p-2">
                                    <i class="ti ti-alert-circle text-warning fs-5"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold">{{ stats.total_actifs ?? incidents.length }}</div>
                                    <small class="text-muted">Incidents actifs</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-info bg-opacity-10 rounded-3 p-2">
                                    <i class="ti ti-books text-info fs-5"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold">{{ stats.total_bibliotheque ?? 0 }}</div>
                                    <small class="text-muted">En bibliothèque</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-success bg-opacity-10 rounded-3 p-2">
                                    <i class="ti ti-shield-check text-success fs-5"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold">{{ stats.total_convertis ?? 0 }}</div>
                                    <small class="text-muted">Convertis en risque</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-danger bg-opacity-10 rounded-3 p-2">
                                    <i class="ti ti-currency-dollar text-danger fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:.9rem">{{ totalEvaluation }}</div>
                                    <small class="text-muted">Exposition actifs</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <DataTable
                        :value="incidents"
                        :paginator="true"
                        :rows="10"
                        :rowsPerPageOptions="[10, 25, 50]"
                        filterDisplay="menu"
                        v-model:filters="filters"
                        :globalFilterFields="['code_incident','libelle','source']"
                        sortMode="multiple"
                        removableSort
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                    >
                        <template #header>
                            <div class="d-flex align-items-center px-2 pt-2">
                <span class="p-input-icon-left">
                  <i class="pi pi-search" />
                  <InputText v-model="filters['global'].value" placeholder="Rechercher…" class="p-inputtext-sm" style="width:260px" />
                </span>
                            </div>
                        </template>

                        <template #empty>
                            <div class="text-center py-4 text-muted">
                                <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                Aucun incident actif pour le moment
                            </div>
                        </template>

                        <Column field="code_incident" header="Code" sortable style="min-width:140px">
                            <template #body="{ data }">
                                <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ data.code_incident }}</span>
                            </template>
                        </Column>

                        <Column field="libelle" header="Libellé" sortable style="min-width:220px">
                            <template #body="{ data }">
                                <div class="fw-semibold">{{ data.libelle }}</div>
                                <small v-if="data.source" class="text-muted">
                                    <i class="ti ti-map-pin me-1"></i>{{ data.source }}
                                </small>
                            </template>
                        </Column>

                        <Column field="date_incident" header="Date" sortable style="min-width:120px">
                            <template #body="{ data }">
                                <span class="text-muted small">{{ formatDate(data.date_incident) }}</span>
                            </template>
                        </Column>

                        <Column field="evaluation_monetaire" header="Évaluation" sortable style="min-width:150px">
                            <template #body="{ data }">
                                <span v-if="data.evaluation_formatee" class="text-danger fw-semibold">{{ data.evaluation_formatee }}</span>
                                <span v-else class="text-muted small">Non évaluée</span>
                            </template>
                        </Column>

                        <Column field="created_at" header="Créé le" sortable style="min-width:110px">
                            <template #body="{ data }">
                                <small class="text-muted">{{ data.created_at }}</small>
                            </template>
                        </Column>

                        <Column header="Actions" style="min-width:160px" :exportable="false">
                            <template #body="{ data }">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-outline-primary btn-sm" title="Modifier" @click="openEdit(data)">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" title="Vers bibliothèque" @click="confirmMoveToLibrary(data)">
                                        <i class="ti ti-books"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" title="Supprimer" @click="confirmDelete(data)">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>

        </div>

        <!-- ── Modal Création / Édition ── -->
        <BModal v-model="showModal" :title="editMode ? `Modifier — ${currentIncident?.code_incident}` : 'Nouvel incident'" size="lg" hide-footer @hidden="resetForm">
            <form @submit.prevent="submitForm">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Libellé <span class="text-danger">*</span></label>
                        <input v-model="form.libelle" type="text" class="form-control" :class="{'is-invalid': errors.libelle}" placeholder="Décrivez succinctement l'incident…" maxlength="255" />
                        <div v-if="errors.libelle" class="invalid-feedback">{{ errors.libelle }}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea v-model="form.description" class="form-control" rows="3" placeholder="Détails, contexte, impact observé…"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date de l'incident</label>
                        <input v-model="form.date_incident" type="date" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Source / Origine</label>
                        <input v-model="form.source" type="text" class="form-control" placeholder="Ex : audit interne, signalement…" maxlength="255" />
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Évaluation monétaire</label>
                        <div class="input-group">
                            <input v-model="form.evaluation_monetaire" type="number" class="form-control" :class="{'is-invalid': errors.evaluation_monetaire}" placeholder="0.00" min="0" step="0.01" />
                            <select v-model="form.devise" class="form-select" style="max-width:130px">
                                <option v-for="d in devises" :key="d.code" :value="d.code">{{ d.code }}</option>
                            </select>
                        </div>
                        <div class="form-text text-muted">Laissez vide si non quantifiable</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary" @click="showModal = false">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="submitting">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ti ti-check me-1"></i>
                        {{ editMode ? 'Enregistrer' : "Créer l'incident" }}
                    </button>
                </div>
            </form>
        </BModal>

        <!-- ── Modal confirmation bibliothèque ── -->
        <BModal v-model="showLibraryModal" title="Déplacer vers la bibliothèque" size="sm" hide-footer>
            <p class="mb-3">
                L'incident <strong>{{ targetIncident?.code_incident }}</strong> sera déplacé vers la bibliothèque.
                Vous pourrez le réactiver ou le convertir en risque ultérieurement.
            </p>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="showLibraryModal = false">Annuler</button>
                <button class="btn btn-info btn-sm text-white" :disabled="submitting" @click="doMoveToLibrary">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ti ti-books me-1"></i>Confirmer
                </button>
            </div>
        </BModal>

        <!-- ── Modal confirmation suppression ── -->
        <BModal v-model="showDeleteModal" title="Supprimer l'incident" size="sm" hide-footer>
            <p class="mb-3">Supprimer l'incident <strong>{{ targetIncident?.code_incident }}</strong> ?</p>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="showDeleteModal = false">Annuler</button>
                <button class="btn btn-danger btn-sm" :disabled="submitting" @click="doDelete">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ti ti-trash me-1"></i>Supprimer
                </button>
            </div>
        </BModal>

    </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { FilterMatchMode } from 'primevue/api'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import { BModal } from 'bootstrap-vue-next'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
    incidents: { type: Array,  default: () => [] },
    devises:   { type: Array,  default: () => [] },
    stats:     { type: Object, default: () => ({}) },
})

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })

const showModal        = ref(false)
const showLibraryModal = ref(false)
const showDeleteModal  = ref(false)
const editMode         = ref(false)
const submitting       = ref(false)
const currentIncident  = ref(null)
const targetIncident   = ref(null)

const emptyForm = () => ({ libelle: '', description: '', evaluation_monetaire: null, devise: 'XOF', date_incident: '', source: '' })
const form   = ref(emptyForm())
const errors = ref({})

const totalEvaluation = computed(() => {
    const total = props.incidents
        .filter(i => i.evaluation_monetaire != null)
        .reduce((s, i) => s + parseFloat(i.evaluation_monetaire), 0)
    if (total === 0) return 'Non évalués'
    return new Intl.NumberFormat('fr-FR').format(total) + ' XOF'
})

function formatDate(dateStr) {
    if (!dateStr) return '—'
    const [y, m, d] = dateStr.split('-')
    return `${d}/${m}/${y}`
}

function resetForm() {
    form.value = emptyForm()
    errors.value = {}
    editMode.value = false
    currentIncident.value = null
}

function openCreate() { resetForm(); showModal.value = true }

function openEdit(incident) {
    editMode.value = true
    currentIncident.value = incident
    form.value = {
        libelle:              incident.libelle,
        description:          incident.description ?? '',
        evaluation_monetaire: incident.evaluation_monetaire,
        devise:               incident.devise ?? 'XOF',
        date_incident:        incident.date_incident ?? '',
        source:               incident.source ?? '',
    }
    showModal.value = true
}

function confirmMoveToLibrary(incident) { targetIncident.value = incident; showLibraryModal.value = true }
function confirmDelete(incident)        { targetIncident.value = incident; showDeleteModal.value  = true }

function submitForm() {
    submitting.value = true
    errors.value = {}
    const url    = editMode.value
        ? route('risk.core.incidents.update', currentIncident.value.id)
        : route('risk.core.incidents.store')
    const method = editMode.value ? 'put' : 'post'
    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false },
        onError:   (e) => { errors.value = e },
        onFinish:  () => { submitting.value = false },
    })
}

function doMoveToLibrary() {
    submitting.value = true
    router.post(route('risk.core.incidents.move-to-library', targetIncident.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { showLibraryModal.value = false },
        onFinish:  () => { submitting.value = false },
    })
}

function doDelete() {
    submitting.value = true
    router.delete(route('risk.core.incidents.destroy', targetIncident.value.id), {
        preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false },
        onFinish:  () => { submitting.value = false },
    })
}
</script>
