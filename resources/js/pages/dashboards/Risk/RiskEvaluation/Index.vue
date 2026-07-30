<template>
    <VerticalLayout>
        <div class="eval-layout d-flex" style="height:calc(100vh - 64px); overflow:hidden">

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!--  PANNEAU GAUCHE — Arborescence des risques                 -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <aside
                class="eval-tree border-end bg-white d-flex flex-column"
                style="width:300px; min-width:260px; max-width:340px; flex-shrink:0"
            >
                <!-- En-tête arborescence -->
                <div class="px-3 pt-3 pb-2 border-bottom flex-shrink-0 bg-light">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.04em">
                            <i class="ti ti-sitemap me-1"></i>Registre
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-secondary-subtle text-secondary small">{{ totalRisks }}</span>
                            <button
                                type="button"
                                class="btn btn-outline-secondary px-1 py-0"
                                style="font-size:.65rem; line-height:1.6"
                                :title="allExpanded ? 'Tout replier' : 'Tout déplier'"
                                @click="toggleAllTree"
                            >
                                <i :class="allExpanded ? 'ti ti-arrows-minimize' : 'ti ti-arrows-maximize'"></i>
                            </button>
                        </div>
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="ti ti-search text-muted" style="font-size:.8rem"></i>
                        </span>
                        <input
                            v-model="treeSearch"
                            type="text"
                            class="form-control form-control-sm border-start-0"
                            placeholder="Rechercher…"
                        />
                        <button v-if="treeSearch" class="btn btn-sm btn-outline-secondary" @click="treeSearch = ''">
                            <i class="ti ti-x" style="font-size:.75rem"></i>
                        </button>
                    </div>
                </div>

                <!-- Arborescence scrollable -->
                <div class="overflow-auto flex-grow-1 py-1 px-1">
                    <div v-if="!filteredTree.length" class="text-center text-muted small py-4">
                        <i class="ti ti-search-off d-block fs-3 mb-1 opacity-25"></i>
                        Aucun risque trouvé.
                    </div>

                    <template v-for="macro in filteredTree" :key="macro.id">

                        <!-- ── Macro-processus ─────────────────────── -->
                        <div
                            class="tree-node d-flex align-items-center gap-1 px-2 py-1 rounded-1"
                            @click="toggleNode(openMacros, macro.id)"
                        >
                            <i class="ti small text-muted flex-shrink-0"
                               :class="openMacros.has(macro.id) ? 'ti-chevron-down' : 'ti-chevron-right'"
                            ></i>
                            <i class="ti ti-layers flex-shrink-0" style="color:#6366f1; font-size:.85rem"></i>
                            <span class="small fw-semibold text-truncate" :title="macro.name">
                                <span v-if="macro.code" class="text-muted me-1" style="font-size:.7rem">{{ macro.code }}</span>{{ macro.name }}
                            </span>
                        </div>

                        <template v-if="openMacros.has(macro.id)">
                            <template v-for="process in macro.processes" :key="process.id">

                                <!-- ── Processus ──────────────────── -->
                                <div
                                    class="tree-node d-flex align-items-center gap-1 ps-4 pe-2 py-1 rounded-1"
                                    @click="toggleNode(openProcesses, process.id)"
                                >
                                    <i class="ti small text-muted flex-shrink-0"
                                       :class="openProcesses.has(process.id) ? 'ti-chevron-down' : 'ti-chevron-right'"
                                    ></i>
                                    <i class="ti ti-git-merge flex-shrink-0 text-secondary" style="font-size:.8rem"></i>
                                    <span class="small text-truncate" :title="process.name">{{ process.name }}</span>
                                </div>

                                <template v-if="openProcesses.has(process.id)">
                                    <template v-for="activity in process.activities" :key="activity.id">

                                        <!-- ── Activité ───────────── -->
                                        <div
                                            class="tree-node d-flex align-items-center gap-1 ps-5 pe-2 py-1 rounded-1"
                                            style="padding-left:2.5rem !important"
                                            @click="toggleNode(openActivities, activity.id)"
                                        >
                                            <i class="ti small text-muted flex-shrink-0"
                                               :class="openActivities.has(activity.id) ? 'ti-chevron-down' : 'ti-chevron-right'"
                                            ></i>
                                            <i class="ti ti-bolt flex-shrink-0 text-warning" style="font-size:.8rem"></i>
                                            <span class="small text-truncate" :title="activity.name">{{ activity.name }}</span>
                                        </div>

                                        <template v-if="openActivities.has(activity.id)">

                                            <!-- ── Risque ─────────── -->
                                            <div
                                                v-for="risk in activity.risks"
                                                :key="risk.id"
                                                class="tree-risk d-flex align-items-center gap-2 py-1 pe-2 rounded-1"
                                                style="padding-left:3.6rem; cursor:pointer"
                                                :class="{ 'selected': selectedRisk?.id === risk.id }"
                                                @click="selectRisk(risk)"
                                            >
                                                <!-- Indicateur évaluation -->
                                                <span
                                                    class="flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle"
                                                    style="width:10px; height:10px; min-width:10px"
                                                    :style="{
                                                        backgroundColor: riskMap[risk.id]?.zone_color ?? 'transparent',
                                                        border: riskMap[risk.id]?.score ? 'none' : '1.5px solid #ced4da',
                                                    }"
                                                    :title="riskMap[risk.id]?.score ? `Score ${riskMap[risk.id].score} — ${riskMap[risk.id].zone_label}` : 'Non évalué'"
                                                ></span>

                                                <span class="small text-truncate flex-grow-1" style="min-width:0">
                                                    <span class="font-monospace text-muted" style="font-size:.65rem">{{ risk.code_risk }}</span>
                                                    <span class="ms-1">{{ risk.libelle }}</span>
                                                </span>
                                            </div>

                                        </template>
                                    </template>
                                </template>

                            </template>
                        </template>

                    </template>
                </div>
            </aside>

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!--  PANNEAU DROIT — Évaluation + Table                        -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <main class="flex-grow-1 d-flex flex-column overflow-hidden bg-light">

                <!-- ── Barre de navigation des types ─────────────────── -->
                <div class="bg-white border-bottom px-4 py-2 flex-shrink-0 d-flex align-items-center gap-3 flex-wrap">
                    <div>
                        <h5 class="mb-0 fw-semibold">
                            <i :class="typeIcon" class="me-2 text-primary"></i>{{ typeLabel }}
                        </h5>
                        <small class="text-muted">{{ typeDescription }}</small>
                    </div>
                    <!-- Badge matrice active -->
                    <div v-if="activeMatrix" class="d-flex align-items-center">
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size:.7rem">
                            <i class="ti ti-grid-dots me-1"></i>{{ activeMatrix.name }}
                            <span class="opacity-75 ms-1">({{ activeMatrix.matrix_size }}×{{ activeMatrix.matrix_size }})</span>
                        </span>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <Link
                            v-for="t in evalTypes"
                            :key="t.key"
                            :href="route(`risk.core.evaluation.${t.key}`)"
                            class="btn btn-sm d-flex align-items-center gap-1"
                            :class="t.key === type ? 'btn-primary' : 'btn-outline-secondary'"
                        >
                            <i :class="t.icon"></i>{{ t.label }}
                        </Link>
                    </div>
                </div>

                <!-- ── Zone scrollable droite ─────────────────────────── -->
                <div class="overflow-auto flex-grow-1 p-3">

                    <!-- Alerte : aucune matrice active ─────────────────────── -->
                    <div v-if="!activeMatrix" class="alert alert-warning d-flex align-items-center gap-2 mb-3">
                        <i class="ti ti-alert-triangle fs-5"></i>
                        <div>
                            <strong>Aucune matrice active.</strong>
                            Veuillez activer une configuration de matrice dans les
                            <a :href="route('risk.core.matrix-config.index')" class="alert-link">paramètres</a>.
                        </div>
                    </div>

                    <!-- Invite de sélection -->
                    <div v-else-if="!selectedRisk" class="text-center py-5 text-muted">
                        <i class="ti ti-arrow-left d-block fs-1 mb-3 opacity-20"></i>
                        <p class="mb-0">Sélectionnez un risque dans l'arborescence pour commencer l'évaluation.</p>
                    </div>

                    <!-- ── Panneau d'évaluation actif ───────────────── -->
                    <div v-else-if="selectedRisk">

                        <!-- Fil d'Ariane du risque sélectionné -->
                        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                            <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-1">
                                {{ selectedRisk.code_risk }}
                            </span>
                            <span class="fw-semibold">{{ selectedRisk.libelle }}</span>
                            <span
                                class="badge"
                                :class="selectedRisk.statut === 'actif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'"
                            >{{ selectedRisk.statut }}</span>
                            <!-- Score actuel si déjà évalué -->
                            <template v-if="currentScore">
                                <span class="ms-2 text-muted small">Évaluation actuelle :</span>
                                <span
                                    class="badge px-2 py-1 fw-bold"
                                    :style="{ backgroundColor: currentZoneColor, color: '#fff' }"
                                >{{ currentScore }} — {{ currentZoneLabel }}</span>
                            </template>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" @click="clearSelection">
                                <i class="ti ti-x me-1"></i>Désélectionner
                            </button>
                        </div>

                        <!-- Grille matrice + sélecteurs -->
                        <div class="row g-3 mb-3">

                            <!-- ── Matrice heatmap ──────────────────── -->
                            <div class="col-xl-7 col-lg-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">
                                            <i class="ti ti-grid-dots me-1"></i>Matrice de criticité
                                        </span>
                                        <small class="text-muted">— Cliquez sur une cellule pour sélectionner</small>
                                    </div>
                                    <div class="card-body">
                                        <RiskMatrix
                                            :impact-levels="impactLevels"
                                            :frequency-levels="frequencyLevels"
                                            :zones="criticalityZones"
                                            :selected-impact="selectedImpactId"
                                            :selected-frequency="selectedFrequencyId"
                                            @cell-click="onCellClick"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- ── Sélecteurs + Résultat ──────────────── -->
                            <div class="col-xl-5 col-lg-6 d-flex flex-column gap-3">

                                <!-- Sélecteur Impact -->
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-transparent border-0 pt-3 pb-0">
                                        <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">
                                            <i class="ti ti-trending-up me-1"></i>Impact
                                        </span>
                                    </div>
                                    <div class="card-body pb-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            <button
                                                v-for="level in impactLevels"
                                                :key="level.id"
                                                type="button"
                                                class="btn btn-sm flex-grow-1"
                                                :style="levelStyle(level.color, selectedImpactId === level.id)"
                                                @click="selectedImpactId = selectedImpactId === level.id ? null : level.id"
                                            >
                                                <div class="fw-semibold small">{{ level.label }}</div>
                                                <div style="font-size:.68rem; opacity:.8">Score {{ level.score }}</div>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sélecteur Fréquence -->
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-transparent border-0 pt-3 pb-0">
                                        <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">
                                            <i class="ti ti-repeat me-1"></i>Fréquence / Vraisemblance
                                        </span>
                                    </div>
                                    <div class="card-body pb-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            <button
                                                v-for="level in frequencyLevels"
                                                :key="level.id"
                                                type="button"
                                                class="btn btn-sm flex-grow-1"
                                                :style="levelStyle(level.color, selectedFrequencyId === level.id)"
                                                @click="selectedFrequencyId = selectedFrequencyId === level.id ? null : level.id"
                                            >
                                                <div class="fw-semibold small">{{ level.label }}</div>
                                                <div style="font-size:.68rem; opacity:.8">Score {{ level.score }}</div>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Résultat calculé + Enregistrer -->
                                <div
                                    class="card border-0 shadow-sm"
                                    :style="score && zone
                                        ? { borderLeft: `4px solid ${zone.color_code} !important` }
                                        : {}"
                                >
                                    <div class="card-body">
                                        <div v-if="score" class="d-flex align-items-center gap-3 mb-3">
                                            <div class="text-center">
                                                <div
                                                    class="display-5 fw-bold lh-1"
                                                    :style="{ color: zone?.color_code ?? '#495057' }"
                                                >{{ score }}</div>
                                                <div class="small text-muted mt-1">Score</div>
                                            </div>
                                            <div class="vr" style="height:48px"></div>
                                            <div>
                                                <span
                                                    v-if="zone"
                                                    class="badge px-3 py-2 fs-6"
                                                    :style="{ backgroundColor: zone.color_code, color: '#fff' }"
                                                >{{ zone.label }}</span>
                                                <span v-else class="badge bg-secondary px-3 py-2">Hors zone</span>
                                                <div class="small text-muted mt-1">Zone de criticité</div>
                                            </div>
                                        </div>
                                        <div v-else class="text-muted small mb-3">
                                            <i class="ti ti-info-circle me-1"></i>
                                            Sélectionnez un niveau d'impact <em>et</em> une fréquence.
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-primary w-100"
                                            :disabled="!score || saving"
                                            @click="saveEvaluation"
                                        >
                                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                            <i v-else class="ti ti-device-floppy me-1"></i>
                                            Enregistrer l'évaluation
                                        </button>

                                        <!-- Feedback sauvegarde -->
                                        <div v-if="saveSuccess" class="alert alert-success border-0 py-2 small mt-2 mb-0">
                                            <i class="ti ti-check me-1"></i>Évaluation enregistrée.
                                        </div>
                                        <div v-if="saveError" class="alert alert-danger border-0 py-2 small mt-2 mb-0">
                                            <i class="ti ti-alert-circle me-1"></i>{{ saveError }}
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════ -->
                    <!--  TABLE DES RISQUES ÉVALUÉS                      -->
                    <!-- ═══════════════════════════════════════════════ -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex align-items-center gap-2">
                            <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">
                                <i class="ti ti-table me-1"></i>Risques évalués
                            </span>
                            <span class="badge bg-primary-subtle text-primary ms-1">{{ localEvaluated.length }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Code</th>
                                            <th>Risque</th>
                                            <th class="d-none d-md-table-cell">Macro / Processus / Activité</th>
                                            <th class="text-center">Impact</th>
                                            <th class="text-center">Fréquence</th>
                                            <th class="text-center">Score</th>
                                            <th class="text-center">Zone</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="r in localEvaluated"
                                            :key="r.id"
                                            style="cursor:pointer"
                                            :class="{ 'table-primary': selectedRisk?.id === r.id }"
                                            @click="selectFromTable(r)"
                                        >
                                            <td class="ps-3 font-monospace small text-muted">{{ r.code_risk }}</td>
                                            <td class="small fw-semibold" style="max-width:200px">
                                                <span class="text-truncate d-block">{{ r.libelle }}</span>
                                            </td>
                                            <td class="small text-muted d-none d-md-table-cell" style="max-width:200px">
                                                <span class="text-truncate d-block">
                                                    {{ [r.macro_name, r.process_name, r.activity_name].filter(Boolean).join(' › ') }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    v-if="r.impact_label"
                                                    class="badge"
                                                    :style="{ backgroundColor: r.impact_color ?? '#6c757d', color: '#fff' }"
                                                >{{ r.impact_label }}
                                                    <span class="opacity-75 ms-1">({{ r.impact_score }})</span>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    v-if="r.frequency_label"
                                                    class="badge"
                                                    :style="{ backgroundColor: r.frequency_color ?? '#6c757d', color: '#fff' }"
                                                >{{ r.frequency_label }}
                                                    <span class="opacity-75 ms-1">({{ r.frequency_score }})</span>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    v-if="r.score"
                                                    class="badge fw-bold px-2"
                                                    :style="{ backgroundColor: r.zone_color ?? '#6c757d', color: '#fff', minWidth: '2rem' }"
                                                >{{ r.score }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    v-if="r.zone_label"
                                                    class="badge px-2"
                                                    :style="{
                                                        backgroundColor: (r.zone_color ?? '#6c757d') + '22',
                                                        color: r.zone_color ?? '#6c757d',
                                                        border: `1px solid ${(r.zone_color ?? '#6c757d')}55`
                                                    }"
                                                >{{ r.zone_label }}</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!localEvaluated.length">
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="ti ti-table-off d-block fs-2 mb-2 opacity-25"></i>
                                                Aucun risque évalué pour le moment.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </VerticalLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import RiskMatrix from './RiskMatrix.vue'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    type:              { type: String,  default: 'inherente' },
    tree:              { type: Array,   default: () => [] },
    impactLevels:      { type: Array,   default: () => [] },
    frequencyLevels:   { type: Array,   default: () => [] },
    criticalityZones:  { type: Array,   default: () => [] },
    evaluatedRisks:    { type: Array,   default: () => [] },
    activeMatrix:      { type: Object,  default: null },
})

// ── Types d'évaluation ─────────────────────────────────────────────────────
const evalTypes = [
    { key: 'inherente',  label: 'Inhérente',  icon: 'ti ti-shield' },
    { key: 'residuelle', label: 'Résiduelle', icon: 'ti ti-shield-half' },
    { key: 'cible',      label: 'Cible',      icon: 'ti ti-target' },
]

const typeLabel = computed(() => ({
    inherente:  'Évaluation Inhérente',
    residuelle: 'Évaluation Résiduelle',
    cible:      'Évaluation Cible',
}[props.type] ?? ''))

const typeDescription = computed(() => ({
    inherente:  'Risque brut, avant toute mesure de contrôle',
    residuelle: 'Risque résiduel après mise sous contrôle',
    cible:      "Niveau de risque visé à l'issue du plan de traitement",
}[props.type] ?? ''))

const typeIcon = computed(() => ({
    inherente:  'ti ti-shield',
    residuelle: 'ti ti-shield-half',
    cible:      'ti ti-target',
}[props.type] ?? 'ti ti-chart-bar'))

// ── Arborescence — tous les niveaux ouverts par défaut ────────────────────
const openMacros = ref(new Set(props.tree.map(m => m.id)))

const openProcesses = ref(new Set(
    props.tree.flatMap(m => m.processes?.map(p => p.id) ?? [])
))

const openActivities = ref(new Set(
    props.tree.flatMap(m =>
        m.processes?.flatMap(p => p.activities?.map(a => a.id) ?? []) ?? []
    )
))
const treeSearch     = ref('')

function toggleNode(setRef, id) {
    const s = new Set(setRef.value)
    s.has(id) ? s.delete(id) : s.add(id)
    setRef.value = s
}

const allExpanded = computed(() => {
    const allProcIds = props.tree.flatMap(m => m.processes?.map(p => p.id) ?? [])
    return allProcIds.every(id => openProcesses.value.has(id))
})

function toggleAllTree() {
    if (allExpanded.value) {
        // Tout replier
        openProcesses.value  = new Set()
        openActivities.value = new Set()
    } else {
        // Tout déplier
        openProcesses.value  = new Set(
            props.tree.flatMap(m => m.processes?.map(p => p.id) ?? [])
        )
        openActivities.value = new Set(
            props.tree.flatMap(m =>
                m.processes?.flatMap(p => p.activities?.map(a => a.id) ?? []) ?? []
            )
        )
    }
}

// Map réactive : riskId → { score, zone_color, zone_label, impact_level_id, frequency_level_id }
const riskMap = reactive({})
props.tree.forEach(macro => macro.processes?.forEach(proc =>
    proc.activities?.forEach(act => act.risks?.forEach(r => {
        riskMap[r.id] = {
            score:              r.score,
            zone_color:         r.zone_color,
            zone_label:         r.zone_label,
            impact_level_id:    r.impact_level_id,
            frequency_level_id: r.frequency_level_id,
        }
    }))
))

const totalRisks = computed(() => {
    let n = 0
    props.tree.forEach(m => m.processes?.forEach(p => p.activities?.forEach(a => { n += a.risks?.length ?? 0 })))
    return n
})

const filteredTree = computed(() => {
    if (!treeSearch.value) return props.tree
    const q = treeSearch.value.toLowerCase()
    return props.tree.map(macro => ({
        ...macro,
        processes: macro.processes.map(proc => ({
            ...proc,
            activities: proc.activities.map(act => ({
                ...act,
                risks: act.risks.filter(r =>
                    r.code_risk?.toLowerCase().includes(q) ||
                    r.libelle?.toLowerCase().includes(q)
                )
            })).filter(a => a.risks.length)
        })).filter(p => p.activities.length)
    })).filter(m => m.processes.length)
})

// ── Sélection risque ───────────────────────────────────────────────────────
const selectedRisk       = ref(null)
const selectedImpactId   = ref(null)
const selectedFrequencyId = ref(null)

function selectRisk(risk) {
    selectedRisk.value        = risk
    const saved               = riskMap[risk.id] ?? {}
    selectedImpactId.value    = saved.impact_level_id    ?? null
    selectedFrequencyId.value = saved.frequency_level_id ?? null
    clearFeedback()
}

function clearSelection() {
    selectedRisk.value        = null
    selectedImpactId.value    = null
    selectedFrequencyId.value = null
    clearFeedback()
}

function selectFromTable(r) {
    // Construire un objet compatible avec le format tree risk
    const pseudoRisk = {
        id:       r.id,
        code_risk: r.code_risk,
        libelle:  r.libelle,
        statut:   r.statut,
    }
    selectedRisk.value        = pseudoRisk
    selectedImpactId.value    = r.impact_level_id    ?? null
    selectedFrequencyId.value = r.frequency_level_id ?? null
    clearFeedback()
}

// ── Clic sur cellule de la matrice ─────────────────────────────────────────
function onCellClick(impactId, frequencyId) {
    // Toggle : re-cliquer la cellule sélectionnée désélectionne
    if (selectedImpactId.value === impactId && selectedFrequencyId.value === frequencyId) {
        selectedImpactId.value    = null
        selectedFrequencyId.value = null
    } else {
        selectedImpactId.value    = impactId
        selectedFrequencyId.value = frequencyId
    }
}

// ── Calcul score / zone ────────────────────────────────────────────────────
const score = computed(() => {
    const imp  = props.impactLevels.find(l => l.id === selectedImpactId.value)
    const freq = props.frequencyLevels.find(l => l.id === selectedFrequencyId.value)
    return (imp && freq) ? imp.score * freq.score : null
})

const zone = computed(() => {
    if (!score.value) return null
    return props.criticalityZones.find(z => score.value >= z.min_score && score.value <= z.max_score) ?? null
})

// Score actuel du risque sélectionné (avant modification)
const currentScore     = computed(() => riskMap[selectedRisk.value?.id]?.score     ?? null)
const currentZoneColor = computed(() => riskMap[selectedRisk.value?.id]?.zone_color ?? null)
const currentZoneLabel = computed(() => riskMap[selectedRisk.value?.id]?.zone_label ?? null)

// ── Sauvegarde ─────────────────────────────────────────────────────────────
const saving      = ref(false)
const saveSuccess = ref(false)
const saveError   = ref(null)

function clearFeedback() {
    saveSuccess.value = false
    saveError.value   = null
}

const localEvaluated = ref([...props.evaluatedRisks])

async function saveEvaluation() {
    if (!selectedRisk.value || !selectedImpactId.value || !selectedFrequencyId.value) return
    saving.value      = true
    saveSuccess.value = false
    saveError.value   = null

    try {
        const res = await fetch(route('risk.core.evaluation.save', selectedRisk.value.id), {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept':       'application/json',
            },
            body: JSON.stringify({
                type:               props.type,
                impact_level_id:    selectedImpactId.value,
                frequency_level_id: selectedFrequencyId.value,
            }),
        })

        if (!res.ok) throw new Error(`Erreur HTTP ${res.status}`)

        const data = await res.json()

        if (data.success) {
            // Mise à jour de la map de l'arborescence
            riskMap[selectedRisk.value.id] = data.treeRiskData

            // Mise à jour de la table évalués
            const idx = localEvaluated.value.findIndex(r => r.id === selectedRisk.value.id)
            if (idx >= 0) localEvaluated.value.splice(idx, 1, data.evaluatedRisk)
            else          localEvaluated.value.push(data.evaluatedRisk)

            saveSuccess.value = true
            setTimeout(() => { saveSuccess.value = false }, 3000)
        }
    } catch (e) {
        console.error(e)
        saveError.value = 'Erreur lors de la sauvegarde. Veuillez réessayer.'
    } finally {
        saving.value = false
    }
}

// ── Style boutons de niveau ────────────────────────────────────────────────
function levelStyle(color, selected) {
    if (selected) {
        return {
            backgroundColor: (color ?? '#0d6efd') + '22',
            border:          `2px solid ${color ?? '#0d6efd'}`,
            color:           'inherit',
        }
    }
    return {
        backgroundColor: '#f8f9fa',
        border:          '1px solid #dee2e6',
        color:           'inherit',
    }
}
</script>

<style scoped>
/* ── Nœuds de l'arborescence ─────────────────────────────────────── */
.tree-node {
    cursor: pointer;
    user-select: none;
    transition: background-color .1s;
}
.tree-node:hover {
    background-color: rgba(0, 0, 0, .04);
}

/* ── Ligne risque ─────────────────────────────────────────────────── */
.tree-risk {
    transition: background-color .1s;
}
.tree-risk:hover {
    background-color: rgba(var(--bs-primary-rgb), .06);
}
.tree-risk.selected {
    background-color: rgba(var(--bs-primary-rgb), .12);
    font-weight: 500;
}
</style>
