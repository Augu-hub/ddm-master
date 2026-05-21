<template>
    <VerticalLayout>
        <div class="container-fluid py-3">
            <!-- En-tête -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-semibold mb-0">
                        <i class="ti ti-shield text-primary me-2"></i>
                        Registre des risques
                    </h4>
                    <small class="text-muted">Identification, évaluation et suivi des risques</small>
                </div>
                <div class="d-flex gap-2">
                    <Link :href="route('risk.core.risk-library.index')" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-books me-1"></i>Bibliothèque
                    </Link>
                    <Link :href="route('risk.core.risks.create')" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus me-1"></i>Nouveau risque
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3" v-for="card in statCards" :key="card.label">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div :class="`bg-${card.color} rounded-3 bg-opacity-10 p-2`">
                                    <i :class="`${card.icon} text-${card.color} fs-5`"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold">{{ card.value }}</div>
                                    <small class="text-muted">{{ card.label }}</small>
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
                        :value="risksFiltered"
                        :paginator="true"
                        :rows="15"
                        :rowsPerPageOptions="[15, 25, 50]"
                        filterDisplay="menu"
                        v-model:filters="filters"
                        :globalFilterFields="['code_risk', 'libelle', 'owner', 'activity_name', 'process_name', 'nomenclature_label']"
                        sortMode="multiple"
                        removableSort
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                        rowHover
                        style="cursor: pointer"
                        @row-click="openDetail($event.data)"
                    >
                        <template #header>
                            <div class="d-flex align-items-center flex-wrap gap-2 px-2 pt-2">
                                <span class="p-input-icon-left">
                                    <i class="pi pi-search" />
                                    <InputText
                                        v-model="filters['global'].value"
                                        placeholder="Rechercher…"
                                        class="p-inputtext-sm"
                                        style="width: 240px"
                                    />
                                </span>
                                <select v-model="filterStatut" class="form-select form-select-sm ms-auto" style="width: 160px">
                                    <option value="">Tous les statuts</option>
                                    <option value="draft">Brouillon</option>
                                    <option value="actif">Actif</option>
                                    <option value="archive">Archivé</option>
                                </select>
                            </div>
                        </template>

                        <template #empty>
                            <div class="text-muted py-4 text-center">
                                <i class="ti ti-shield-off fs-1 d-block mb-2"></i>
                                Aucun risque enregistré
                            </div>
                        </template>

                        <Column field="code_risk" header="Code" sortable style="min-width: 130px">
                            <template #body="{ data }">
                                <span class="badge bg-primary-subtle text-primary font-monospace">{{ data.code_risk }}</span>
                            </template>
                        </Column>

                        <Column field="libelle" header="Risque" sortable style="min-width: 220px">
                            <template #body="{ data }">
                                <div class="fw-semibold">{{ data.libelle }}</div>
                                <small v-if="data.nomenclature_label" class="text-muted">
                                    <i class="ti ti-tag me-1"></i>{{ data.nomenclature_label }}
                                </small>
                            </template>
                        </Column>

                        <Column field="activity_name" header="Activité / Processus" sortable style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="small fw-semibold">{{ data.activity_name }}</div>
                                <small class="text-muted">{{ data.process_code }} — {{ data.process_name }}</small>
                            </template>
                        </Column>

                        <Column field="criticality_score" header="Criticité" sortable style="min-width: 130px">
                            <template #body="{ data }">
                                <span
                                    v-if="data.zone_label"
                                    class="badge rounded-pill fw-semibold px-3 py-1"
                                    :style="`background:${data.zone_color}20;color:${data.zone_color};border:1px solid ${data.zone_color}40`"
                                >
                                    {{ data.zone_label }}
                                    <span class="ms-1 opacity-75" v-if="data.criticality_score">({{ data.criticality_score }})</span>
                                </span>
                                <span v-else class="text-muted small">Non évaluée</span>
                            </template>
                        </Column>

                        <Column field="owner" header="Owner" sortable style="min-width: 130px">
                            <template #body="{ data }">
                                <span v-if="data.owner" class="small"> <i class="ti ti-user text-muted me-1"></i>{{ data.owner }} </span>
                                <span v-else class="text-muted small">—</span>
                            </template>
                        </Column>

                        <Column field="statut" header="Statut" sortable style="min-width: 110px">
                            <template #body="{ data }">
                                <span :class="`badge bg-${data.statut_badge}-subtle text-${data.statut_badge}`">
                                    {{ data.statut_label }}
                                </span>
                            </template>
                        </Column>

                        <Column header="Actions" style="min-width: 180px" :exportable="false">
                            <template #body="{ data }">
                                <div class="d-flex gap-1" @click.stop>
                                    <Link :href="route('risk.core.risks.edit', data.id)" class="btn btn-outline-primary btn-sm" title="Modifier">
                                        <i class="ti ti-pencil"></i>
                                    </Link>
                                    <button
                                        v-if="data.statut !== 'actif'"
                                        class="btn btn-outline-success btn-sm"
                                        title="Activer"
                                        @click="doActivate(data)"
                                    >
                                        <i class="ti ti-check"></i>
                                    </button>
                                    <button
                                        v-if="data.statut !== 'archive'"
                                        class="btn btn-outline-secondary btn-sm"
                                        title="Archiver"
                                        @click="confirmArchive(data)"
                                    >
                                        <i class="ti ti-archive"></i>
                                    </button>
                                    <button
                                        class="btn btn-sm"
                                        :class="data.moved_to_library_at ? 'btn-warning' : 'btn-outline-info'"
                                        :title="data.moved_to_library_at ? 'Retirer de la bibliothèque' : 'Transférer en bibliothèque'"
                                        @click="data.moved_to_library_at ? confirmRemoveLibrary(data) : confirmMoveToLibrary(data)"
                                    >
                                        <i :class="data.moved_to_library_at ? 'ti ti-book-off' : 'ti ti-books'"></i>
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

        <!-- ── Offcanvas détail ── -->
        <BOffcanvas v-model="showDetail" placement="end" style="width: 520px">
            <template #header>
                <div class="d-flex align-items-center flex-wrap gap-2" v-if="detailRisk">
                    <span class="badge bg-primary-subtle text-primary font-monospace">{{ detailRisk.code_risk }}</span>
                    <span :class="`badge bg-${detailRisk.statut_badge}-subtle text-${detailRisk.statut_badge}`">{{ detailRisk.statut_label }}</span>
                </div>
            </template>
            <template v-if="detailRisk">
                <h6 class="fw-bold mb-3">{{ detailRisk.libelle }}</h6>

                <!-- Zone criticité -->
                <div
                    v-if="detailRisk.zone_label"
                    class="rounded-3 mb-3 p-3"
                    :style="`background:${detailRisk.zone_color}12;border-left:4px solid ${detailRisk.zone_color}`"
                >
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-flame"></i>
                        <strong>{{ detailRisk.zone_label }}</strong>
                        <span class="text-muted small ms-auto">Score : {{ detailRisk.criticality_score }}</span>
                    </div>
                    <div class="small text-muted mt-1">Impact : {{ detailRisk.impact_label }} · Fréquence : {{ detailRisk.frequency_label }}</div>
                </div>

                <!-- Localisation -->
                <div class="mb-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em">Localisation</div>
                    <div class="d-flex align-items-center small flex-wrap gap-2">
                        <span class="badge bg-secondary-subtle text-secondary">{{ detailRisk.macro_process_name }}</span>
                        <i class="ti ti-chevron-right text-muted" style="font-size: 0.75rem"></i>
                        <span class="badge bg-secondary-subtle text-secondary">{{ detailRisk.process_name }}</span>
                        <i class="ti ti-chevron-right text-muted" style="font-size: 0.75rem"></i>
                        <span class="badge bg-primary-subtle text-primary">{{ detailRisk.activity_name }}</span>
                    </div>
                </div>

                <!-- Détails métier -->
                <div class="vstack gap-3">
                    <DetailRow icon="ti-tag" label="Classification" :value="detailRisk.nomenclature_label" />
                    <DetailRow icon="ti-user" label="Owner" :value="detailRisk.owner" />
                    <DetailRow icon="ti-bulb" label="Causes" :value="detailRisk.causes" multiline />
                    <DetailRow icon="ti-alert-triangle" label="Conséquences" :value="detailRisk.consequences" multiline />
                    <DetailRow icon="ti-shield" label="Contrôles" :value="detailRisk.controles_existants" multiline />
                    <DetailRow icon="ti-list-check" label="Plan de traitement" :value="detailRisk.plan_traitement" multiline />
                    <div v-if="detailRisk.incident_id" class="small text-muted">
                        <i class="ti ti-link me-1"></i>Converti depuis l'incident #{{ detailRisk.incident_id }}
                    </div>
                </div>
            </template>
        </BOffcanvas>

        <!-- ── Modales confirmation ── -->
        <BModal v-model="showArchiveModal" title="Archiver le risque" size="sm" hide-footer>
            <p class="mb-3">
                Archiver <strong>{{ targetRisk?.code_risk }}</strong> ?
            </p>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="showArchiveModal = false">Annuler</button>
                <button class="btn btn-secondary btn-sm" :disabled="submitting" @click="doArchive">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ti ti-archive me-1"></i>Archiver
                </button>
            </div>
        </BModal>

        <BModal v-model="showDeleteModal" title="Supprimer le risque" size="sm" hide-footer>
            <p class="mb-3">
                Supprimer définitivement <strong>{{ targetRisk?.code_risk }}</strong> ?
            </p>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="showDeleteModal = false">Annuler</button>
                <button class="btn btn-danger btn-sm" :disabled="submitting" @click="doDelete">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ti ti-trash me-1"></i>Supprimer
                </button>
            </div>
        </BModal>

        <BModal v-model="showMoveLibraryModal" title="Transférer en bibliothèque" size="sm" hide-footer>
            <p class="mb-3">
                Transférer <strong>{{ targetRisk?.code_risk }}</strong> dans la bibliothèque des risques ?
            </p>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="showMoveLibraryModal = false">Annuler</button>
                <button class="btn btn-info btn-sm text-white" :disabled="submitting" @click="doMoveToLibrary">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ti ti-books me-1"></i>Transférer
                </button>
            </div>
        </BModal>

        <BModal v-model="showRemoveLibraryModal" title="Retirer de la bibliothèque" size="sm" hide-footer>
            <p class="mb-3">
                Retirer <strong>{{ targetRisk?.code_risk }}</strong> de la bibliothèque ?<br>
                <small class="text-muted">Le risque restera visible dans le registre.</small>
            </p>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="showRemoveLibraryModal = false">Annuler</button>
                <button class="btn btn-warning btn-sm" :disabled="submitting" @click="doRemoveFromLibrary">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ti ti-book-off me-1"></i>Retirer
                </button>
            </div>
        </BModal>
    </VerticalLayout>
</template>

<script setup>
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { BModal, BOffcanvas } from 'bootstrap-vue-next';
import { FilterMatchMode } from 'primevue/api';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import { computed, ref } from 'vue';

// Composant interne bloc détail
const DetailRow = {
    props: { icon: String, label: String, value: String, multiline: Boolean },
    template: `
    <div v-if="value">
      <div class="text-muted small fw-semibold mb-1">
        <i :class="'ti ' + icon + ' me-1'"></i>{{ label }}
      </div>
      <div class="small" :style="multiline ? 'white-space:pre-line' : ''">{{ value }}</div>
    </div>
  `,
};

const props = defineProps({
    risks: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });
const filterStatut = ref('');

const risksFiltered = computed(() => (filterStatut.value ? props.risks.filter((r) => r.statut === filterStatut.value) : props.risks));

const statCards = computed(() => [
    { label: 'Total', value: props.stats.total ?? 0, icon: 'ti ti-shield', color: 'primary' },
    { label: 'Actifs', value: props.stats.total_actif ?? 0, icon: 'ti ti-shield-check', color: 'success' },
    { label: 'Brouillons', value: props.stats.total_draft ?? 0, icon: 'ti ti-shield-half', color: 'warning' },
    { label: 'Depuis incidents', value: props.stats.from_incidents ?? 0, icon: 'ti ti-link', color: 'info' },
]);

const showDetail = ref(false);
const detailRisk = ref(null);
const showArchiveModal = ref(false);
const showDeleteModal = ref(false);
const showMoveLibraryModal = ref(false);
const showRemoveLibraryModal = ref(false);
const submitting = ref(false);
const targetRisk = ref(null);

function openDetail(risk) {
    detailRisk.value = risk;
    showDetail.value = true;
}
function confirmArchive(risk) {
    targetRisk.value = risk;
    showArchiveModal.value = true;
}
function confirmDelete(risk) {
    targetRisk.value = risk;
    showDeleteModal.value = true;
}

function doActivate(risk) {
    router.post(route('risk.core.risks.activate', risk.id), {}, { preserveScroll: true });
}

function doArchive() {
    submitting.value = true;
    router.post(
        route('risk.core.risks.archive', targetRisk.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showArchiveModal.value = false;
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}

function doDelete() {
    submitting.value = true;
    router.delete(route('risk.core.risks.destroy', targetRisk.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}

function confirmMoveToLibrary(risk) {
    targetRisk.value = risk;
    showMoveLibraryModal.value = true;
}

function confirmRemoveLibrary(risk) {
    targetRisk.value = risk;
    showRemoveLibraryModal.value = true;
}

function doMoveToLibrary() {
    submitting.value = true;
    router.post(route('risk.core.risks.move-to-library', targetRisk.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { showMoveLibraryModal.value = false; },
        onFinish:  () => { submitting.value = false; },
    });
}

function doRemoveFromLibrary() {
    submitting.value = true;
    router.post(route('risk.core.risks.remove-from-library', targetRisk.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { showRemoveLibraryModal.value = false; },
        onFinish:  () => { submitting.value = false; },
    });
}
</script>
