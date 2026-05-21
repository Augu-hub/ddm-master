<template>
    <VerticalLayout>
        <div class="container-fluid py-3">

            <!-- En-tête ──────────────────────────────────────────────────────── -->
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h4 class="fw-semibold mb-0">
                        <i class="ti ti-books text-primary me-2"></i>
                        Bibliothèque des risques
                    </h4>
                    <small class="text-muted">Risques transférés depuis le registre — vue arborescente</small>
                </div>
                <Link :href="route('risk.core.risks.index')" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left me-1"></i>Retour au registre
                </Link>
            </div>

            <!-- Statistiques ─────────────────────────────────────────────────── -->
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

            <!-- Arbre vide ───────────────────────────────────────────────────── -->
            <div v-if="!tree.length" class="text-center py-5 text-muted">
                <i class="ti ti-books fs-1 d-block mb-2 opacity-25"></i>
                <p class="mb-0">La bibliothèque est vide.</p>
                <p class="small">Transférez des risques depuis le registre pour les retrouver ici.</p>
            </div>

            <!-- Arborescence ─────────────────────────────────────────────────── -->
            <div v-else class="vstack gap-3">

                <!-- ── Niveau 1 : Macro-processus ── -->
                <div
                    v-for="macro in tree"
                    :key="macro.id"
                    class="card border-0 shadow-sm"
                >
                    <!-- Header macro -->
                    <div
                        class="card-header border-0 py-2 px-3 d-flex align-items-center gap-2"
                        style="cursor:pointer; background:var(--bs-primary-bg-subtle)"
                        @click="toggleMacro(macro.id)"
                    >
                        <i
                            class="ti text-primary"
                            :class="openMacros.has(macro.id) ? 'ti-chevron-down' : 'ti-chevron-right'"
                        ></i>
                        <span class="badge bg-primary-subtle text-primary font-monospace me-1">{{ macro.code }}</span>
                        <span class="fw-semibold text-primary">{{ macro.name }}</span>
                        <span v-if="macro.kind" class="badge bg-secondary-subtle text-secondary ms-1 small">{{ macro.kind }}</span>
                        <span class="ms-auto badge bg-primary text-white">
                            {{ countRisks(macro) }} risque{{ countRisks(macro) > 1 ? 's' : '' }}
                        </span>
                    </div>

                    <!-- Body macro (processus) -->
                    <Transition name="slide-down">
                        <div v-if="openMacros.has(macro.id)" class="card-body p-0">
                            <div
                                v-for="process in macro.processes"
                                :key="process.id"
                                class="border-bottom"
                            >
                                <!-- Header processus -->
                                <div
                                    class="d-flex align-items-center gap-2 px-4 py-2"
                                    style="cursor:pointer; background:var(--bs-secondary-bg-subtle)"
                                    @click="toggleProcess(process.id)"
                                >
                                    <i
                                        class="ti text-secondary"
                                        :class="openProcesses.has(process.id) ? 'ti-chevron-down' : 'ti-chevron-right'"
                                    ></i>
                                    <span class="badge bg-secondary-subtle text-secondary font-monospace me-1">{{ process.code }}</span>
                                    <span class="fw-semibold small">{{ process.name }}</span>
                                    <span class="ms-auto text-muted small">
                                        {{ countProcessRisks(process) }} risque{{ countProcessRisks(process) > 1 ? 's' : '' }}
                                    </span>
                                </div>

                                <!-- Activités du processus -->
                                <Transition name="slide-down">
                                    <div v-if="openProcesses.has(process.id)">
                                        <div
                                            v-for="activity in process.activities"
                                            :key="activity.id"
                                        >
                                            <!-- Header activité -->
                                            <div
                                                class="d-flex align-items-center gap-2 px-5 py-2 border-bottom"
                                                style="cursor:pointer; background:var(--bs-body-bg)"
                                                @click="toggleActivity(activity.id)"
                                            >
                                                <i
                                                    class="ti text-muted"
                                                    :class="openActivities.has(activity.id) ? 'ti-chevron-down' : 'ti-chevron-right'"
                                                ></i>
                                                <i class="ti ti-activity text-info me-1"></i>
                                                <span class="badge bg-info-subtle text-info font-monospace me-1">{{ activity.code }}</span>
                                                <span class="small fw-semibold">{{ activity.name }}</span>
                                                <span class="ms-auto badge bg-info-subtle text-info small">
                                                    {{ activity.risks.length }} risque{{ activity.risks.length > 1 ? 's' : '' }}
                                                </span>
                                            </div>

                                            <!-- Liste des risques de l'activité -->
                                            <Transition name="slide-down">
                                                <div v-if="openActivities.has(activity.id)" class="px-5 pt-2 pb-3">
                                                    <div
                                                        v-for="risk in activity.risks"
                                                        :key="risk.id"
                                                        class="card border mb-2 shadow-none"
                                                        style="cursor:pointer"
                                                        @click="openDetail(risk)"
                                                    >
                                                        <div class="card-body py-2 px-3">
                                                            <div class="d-flex align-items-start gap-2 flex-wrap">
                                                                <span class="badge bg-primary-subtle text-primary font-monospace mt-1">{{ risk.code_risk }}</span>
                                                                <div class="flex-grow-1">
                                                                    <div class="fw-semibold small">{{ risk.libelle }}</div>
                                                                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                                                        <small v-if="risk.nomenclature_label" class="text-muted">
                                                                            <i class="ti ti-tag me-1"></i>{{ risk.nomenclature_label }}
                                                                        </small>
                                                                        <span
                                                                            v-if="risk.zone_label"
                                                                            class="badge rounded-pill small"
                                                                            :style="risk.zone_color
                                                                                ? `background:${risk.zone_color}20;color:${risk.zone_color};border:1px solid ${risk.zone_color}40`
                                                                                : 'background:#eee;color:#666'"
                                                                        >
                                                                            {{ risk.zone_label }}
                                                                            <span v-if="risk.criticality_score" class="ms-1 opacity-75">({{ risk.criticality_score }})</span>
                                                                        </span>
                                                                        <span :class="`badge bg-${risk.statut_badge}-subtle text-${risk.statut_badge} small`">
                                                                            {{ risk.statut_label }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <!-- Action retirer -->
                                                                <button
                                                                    class="btn btn-outline-secondary btn-sm ms-auto"
                                                                    title="Retirer de la bibliothèque"
                                                                    @click.stop="confirmRemove(risk)"
                                                                >
                                                                    <i class="ti ti-book-off"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- ── Offcanvas détail risque ── -->
        <BOffcanvas v-model="showDetail" placement="end" style="width: 520px">
            <template #header>
                <div class="d-flex align-items-center flex-wrap gap-2" v-if="detailRisk">
                    <span class="badge bg-primary-subtle text-primary font-monospace">{{ detailRisk.code_risk }}</span>
                    <span :class="`badge bg-${detailRisk.statut_badge}-subtle text-${detailRisk.statut_badge}`">{{ detailRisk.statut_label }}</span>
                    <small class="text-muted ms-auto"><i class="ti ti-books me-1"></i>Bibliothèque depuis {{ detailRisk.moved_to_library_at }}</small>
                </div>
            </template>
            <template v-if="detailRisk">
                <h6 class="fw-bold mb-3">{{ detailRisk.libelle }}</h6>

                <!-- Zone criticité -->
                <div
                    v-if="detailRisk.zone_label"
                    class="rounded-3 mb-3 p-3"
                    :style="detailRisk.zone_color
                        ? `background:${detailRisk.zone_color}12;border-left:4px solid ${detailRisk.zone_color}`
                        : 'background:#f8f9fa;border-left:4px solid #dee2e6'"
                >
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-flame"></i>
                        <strong>{{ detailRisk.zone_label }}</strong>
                        <span class="text-muted small ms-auto">Score : {{ detailRisk.criticality_score }}</span>
                    </div>
                    <div class="small text-muted mt-1">
                        Impact : {{ detailRisk.impact_label ?? '—' }} · Fréquence : {{ detailRisk.frequency_label ?? '—' }}
                    </div>
                </div>

                <!-- Localisation -->
                <div class="mb-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:0.05em">Localisation</div>
                    <div class="d-flex align-items-center small flex-wrap gap-2">
                        <span class="badge bg-secondary-subtle text-secondary">{{ detailRisk.macro_process_name }}</span>
                        <i class="ti ti-chevron-right text-muted" style="font-size:0.75rem"></i>
                        <span class="badge bg-secondary-subtle text-secondary">{{ detailRisk.process_name }}</span>
                        <i class="ti ti-chevron-right text-muted" style="font-size:0.75rem"></i>
                        <span class="badge bg-primary-subtle text-primary">{{ detailRisk.activity_name }}</span>
                    </div>
                </div>

                <!-- Détails -->
                <div class="vstack gap-3">
                    <DetailRow icon="ti-tag"            label="Classification"    :value="detailRisk.nomenclature_label" />
                    <DetailRow icon="ti-user"           label="Owner"             :value="detailRisk.owner" />
                    <DetailRow icon="ti-bulb"           label="Causes"            :value="detailRisk.causes"             multiline />
                    <DetailRow icon="ti-alert-triangle" label="Conséquences"      :value="detailRisk.consequences"       multiline />
                    <DetailRow icon="ti-shield"         label="Contrôles"         :value="detailRisk.controles_existants" multiline />
                    <DetailRow icon="ti-list-check"     label="Plan de traitement" :value="detailRisk.plan_traitement"   multiline />
                </div>

                <!-- Actions depuis l'offcanvas -->
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm" @click="confirmRemove(detailRisk); showDetail = false">
                        <i class="ti ti-book-off me-1"></i>Retirer de la bibliothèque
                    </button>
                    <Link :href="route('risk.core.risks.edit', detailRisk.id)" class="btn btn-outline-primary btn-sm">
                        <i class="ti ti-pencil me-1"></i>Modifier
                    </Link>
                </div>
            </template>
        </BOffcanvas>

        <!-- ── Modale confirmation retrait ── -->
        <BModal v-model="showRemoveModal" title="Retirer de la bibliothèque" size="sm" hide-footer>
            <p class="mb-3">
                Retirer <strong>{{ targetRisk?.code_risk }}</strong> de la bibliothèque ?<br>
                <small class="text-muted">Le risque sera de nouveau disponible dans le registre.</small>
            </p>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="showRemoveModal = false">Annuler</button>
                <button class="btn btn-secondary btn-sm" :disabled="submitting" @click="doRemove">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ti ti-book-off me-1"></i>Retirer
                </button>
            </div>
        </BModal>
    </VerticalLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import { BModal, BOffcanvas } from 'bootstrap-vue-next'

// ── Composant interne ligne de détail ──────────────────────────────────────
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
}

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    tree:  { type: Array,  default: () => [] },
    stats: { type: Object, default: () => ({}) },
})

// ── Statistiques ───────────────────────────────────────────────────────────
const statCards = computed(() => [
    { label: 'Dans la bibliothèque', value: props.stats.total_bibliotheque ?? 0, icon: 'ti ti-books',          color: 'primary' },
    { label: 'Actifs',               value: props.stats.total_actif        ?? 0, icon: 'ti ti-shield-check',   color: 'success' },
    { label: 'Brouillons',           value: props.stats.total_draft        ?? 0, icon: 'ti ti-shield-half',    color: 'warning' },
    { label: 'Dans le registre',     value: props.stats.total_registre     ?? 0, icon: 'ti ti-shield',         color: 'secondary' },
])

// ── État des nœuds ouverts ─────────────────────────────────────────────────
const openMacros     = ref(new Set(props.tree.map(m => m.id)))   // tout ouvert par défaut
const openProcesses  = ref(new Set())
const openActivities = ref(new Set())

function toggleMacro(id)    { openMacros.value.has(id)     ? openMacros.value.delete(id)     : openMacros.value.add(id) }
function toggleProcess(id)  { openProcesses.value.has(id)  ? openProcesses.value.delete(id)  : openProcesses.value.add(id) }
function toggleActivity(id) { openActivities.value.has(id) ? openActivities.value.delete(id) : openActivities.value.add(id) }

// ── Compteurs ──────────────────────────────────────────────────────────────
function countRisks(macro) {
    return macro.processes.reduce((sum, p) => sum + countProcessRisks(p), 0)
}
function countProcessRisks(process) {
    return process.activities.reduce((sum, a) => sum + a.risks.length, 0)
}

// ── Détail offcanvas ───────────────────────────────────────────────────────
const showDetail = ref(false)
const detailRisk  = ref(null)
function openDetail(risk) {
    detailRisk.value = risk
    showDetail.value = true
}

// ── Retrait bibliothèque ───────────────────────────────────────────────────
const showRemoveModal = ref(false)
const targetRisk      = ref(null)
const submitting      = ref(false)

function confirmRemove(risk) {
    targetRisk.value      = risk
    showRemoveModal.value = true
}

function doRemove() {
    submitting.value = true
    router.post(
        route('risk.core.risk-library.remove-from-library', targetRisk.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => { showRemoveModal.value = false },
            onFinish:  () => { submitting.value = false },
        },
    )
}
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.2s ease;
    overflow: hidden;
}
.slide-down-enter-from,
.slide-down-leave-to {
    max-height: 0;
    opacity: 0;
}
.slide-down-enter-to,
.slide-down-leave-from {
    max-height: 2000px;
    opacity: 1;
}
</style>
