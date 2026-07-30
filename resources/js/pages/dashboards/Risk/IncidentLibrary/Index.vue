<template>
    <VerticalLayout>
        <div class="container-fluid py-3">
            <!-- En-tête -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-semibold mb-0">
                        <i class="ti ti-books text-info me-2"></i>
                        Bibliothèque des incidents
                    </h4>
                    <small class="text-muted">Incidents archivés — source de transformation en risques</small>
                </div>
                <Link :href="route('risk.core.incidents.index')" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left me-1"></i>
                    Incidents actifs
                </Link>
            </div>

            <!-- Cartes stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-info rounded-3 bg-opacity-10 p-2">
                                    <i class="ti ti-books text-info fs-5"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold">{{ incidents.length }}</div>
                                    <small class="text-muted">En bibliothèque</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-warning rounded-3 bg-opacity-10 p-2">
                                    <i class="ti ti-alert-triangle text-warning fs-5"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold">{{ stats.total_actifs ?? 0 }}</div>
                                    <small class="text-muted">Actifs en cours</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-success rounded-3 bg-opacity-10 p-2">
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
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-danger rounded-3 bg-opacity-10 p-2">
                                    <i class="ti ti-currency-dollar text-danger fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size: 0.9rem">{{ evaluationTotale }}</div>
                                    <small class="text-muted">Exposition cumulée</small>
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
                        :globalFilterFields="['code_incident', 'libelle', 'source']"
                        sortMode="multiple"
                        removableSort
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                    >
                        <template #header>
                            <div class="d-flex align-items-center px-2 pt-2">
                                <span class="p-input-icon-left">
                                    <i class="pi pi-search" />
                                    <InputText
                                        v-model="filters['global'].value"
                                        placeholder="Rechercher…"
                                        class="p-inputtext-sm"
                                        style="width: 260px"
                                    />
                                </span>
                            </div>
                        </template>

                        <template #empty>
                            <div class="text-muted py-4 text-center">
                                <i class="ti ti-books fs-1 d-block mb-2"></i>
                                La bibliothèque est vide — déplacez des incidents actifs ici
                            </div>
                        </template>

                        <Column field="code_incident" header="Code" sortable style="min-width: 140px">
                            <template #body="{ data }">
                                <span class="badge bg-info-subtle text-info font-monospace">{{ data.code_incident }}</span>
                            </template>
                        </Column>

                        <Column field="libelle" header="Libellé" sortable style="min-width: 220px">
                            <template #body="{ data }">
                                <div class="fw-semibold">{{ data.libelle }}</div>
                                <small v-if="data.description" class="text-muted text-truncate d-block" style="max-width: 260px">
                                    {{ data.description }}
                                </small>
                            </template>
                        </Column>

                        <Column field="date_incident_fr" header="Date incident" sortable style="min-width: 130px">
                            <template #body="{ data }">
                                <span class="text-muted small">{{ data.date_incident_fr ?? '—' }}</span>
                            </template>
                        </Column>

                        <Column field="evaluation_monetaire" header="Évaluation" sortable style="min-width: 150px">
                            <template #body="{ data }">
                                <span v-if="data.evaluation_formatee" class="text-danger fw-semibold">{{ data.evaluation_formatee }}</span>
                                <span v-else class="text-muted small">—</span>
                            </template>
                        </Column>

                        <Column field="moved_to_library_at" header="Archivé le" sortable style="min-width: 120px">
                            <template #body="{ data }">
                                <small class="text-muted">{{ data.moved_to_library_at }}</small>
                            </template>
                        </Column>

                        <Column header="Actions" style="min-width: 210px" :exportable="false">
                            <template #body="{ data }">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-outline-secondary btn-sm" title="Détail" @click="openDetail(data)">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-warning btn-sm" title="Réactiver" @click="confirmReactivate(data)">
                                        <i class="ti ti-refresh"></i>
                                    </button>
                                    <button class="btn btn-primary btn-sm" title="Convertir en risque" @click="confirmConvert(data)">
                                        <i class="ti ti-shield-plus me-1"></i>Risque
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- ── Modal détail ── -->
        <BModal
            v-model="showDetailModal"
            :title="`Détail — ${detailIncident?.code_incident}`"
            size="lg"
            ok-only
            ok-title="Fermer"
            ok-variant="outline-secondary"
        >
            <template v-if="detailIncident">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Code</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-info-subtle text-info font-monospace">{{ detailIncident.code_incident }}</span>
                    </dd>
                    <dt class="col-sm-4 text-muted">Libellé</dt>
                    <dd class="col-sm-8 fw-semibold">{{ detailIncident.libelle }}</dd>
                    <template v-if="detailIncident.description">
                        <dt class="col-sm-4 text-muted">Description</dt>
                        <dd class="col-sm-8">{{ detailIncident.description }}</dd>
                    </template>
                    <dt class="col-sm-4 text-muted">Date incident</dt>
                    <dd class="col-sm-8">{{ detailIncident.date_incident_fr ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Source</dt>
                    <dd class="col-sm-8">{{ detailIncident.source ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Évaluation</dt>
                    <dd class="col-sm-8 text-danger fw-semibold">{{ detailIncident.evaluation_formatee ?? 'Non évaluée' }}</dd>
                    <dt class="col-sm-4 text-muted">Archivé le</dt>
                    <dd class="col-sm-8">{{ detailIncident.moved_to_library_at }}</dd>
                </dl>
            </template>
        </BModal>

        <!-- ── Modal réactivation ── -->
        <BModal v-model="showReactivateModal" title="Réactiver l'incident" size="sm" hide-footer>
            <p class="mb-3">
                Réactiver <strong>{{ targetIncident?.code_incident }}</strong> ? Il sera replacé dans les incidents actifs.
            </p>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="showReactivateModal = false">Annuler</button>
                <button class="btn btn-warning btn-sm" :disabled="submitting" @click="doReactivate">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ti ti-refresh me-1"></i>Réactiver
                </button>
            </div>
        </BModal>

        <!-- ── Modal conversion ── -->
        <BModal v-model="showConvertModal" title="Convertir en risque" size="md" hide-footer>
            <template v-if="targetIncident">
                <div class="alert alert-info rounded-3 mb-3 border-0">
                    <i class="ti ti-info-circle me-2"></i>
                    L'incident sera marqué comme <strong>converti</strong> et ses données pré-rempliront la fiche risque.
                </div>
                <p class="mb-1">
                    <strong>{{ targetIncident.code_incident }}</strong> — {{ targetIncident.libelle }}
                </p>
                <p v-if="targetIncident.evaluation_formatee" class="text-danger mb-3">Exposition : {{ targetIncident.evaluation_formatee }}</p>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-secondary btn-sm" @click="showConvertModal = false">Annuler</button>
                    <button class="btn btn-primary btn-sm" :disabled="submitting" @click="doConvert">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ti ti-shield-plus me-1"></i>Convertir en risque
                    </button>
                </div>
            </template>
        </BModal>
    </VerticalLayout>
</template>

<script setup>
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { BModal } from 'bootstrap-vue-next';
import { FilterMatchMode } from 'primevue/api';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import { computed, ref } from 'vue';

const props = defineProps({
    incidents: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });

const showDetailModal = ref(false);
const showReactivateModal = ref(false);
const showConvertModal = ref(false);
const submitting = ref(false);
const targetIncident = ref(null);
const detailIncident = ref(null);

const evaluationTotale = computed(() => {
    if (!props.stats.evaluation_totale) return 'Non évaluée';
    return new Intl.NumberFormat('fr-FR').format(props.stats.evaluation_totale) + ' XOF';
});

function openDetail(incident) {
    detailIncident.value = incident;
    showDetailModal.value = true;
}
function confirmReactivate(incident) {
    targetIncident.value = incident;
    showReactivateModal.value = true;
}
function confirmConvert(incident) {
    targetIncident.value = incident;
    showConvertModal.value = true;
}

function doReactivate() {
    submitting.value = true;
    router.post(
        route('risk.core.incident-library.reactivate', targetIncident.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showReactivateModal.value = false;
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}

function doConvert() {
    submitting.value = true;
    router.post(
        route('risk.core.incident-library.convert-to-risk', targetIncident.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showConvertModal.value = false;
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}
</script>
