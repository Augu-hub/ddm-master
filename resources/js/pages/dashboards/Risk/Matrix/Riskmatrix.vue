<template>
    <VerticalLayout>
        <Head title="RISK — Cartographie des risques" />

        <b-row class="mb-2">
            <b-col class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-layout-grid fs-5" style="color:#8b6914"></i>
                    <h4 class="fw-semibold m-0">Cartographie des risques</h4>
                    <small class="text-muted ms-1">Impact × Vraisemblance</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <transition name="fade">
                        <span v-if="saveStatus === 'saving'" class="d-flex align-items-center gap-1 text-muted small">
                            <span class="spinner-border spinner-border-sm"></span> Sauvegarde…
                        </span>
                        <span v-else-if="saveStatus === 'saved'" class="badge bg-success-subtle text-success border border-success-subtle small">
                            <i class="ti ti-check me-1"></i>Sauvegardé
                        </span>
                    </transition>

                    <div v-if="activeCfg" class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Matrice active (appliquée partout)">
                            <i class="ti ti-grid-dots me-1"></i>{{ activeCfg.name }} ({{ activeCfg.matrix_label }}) — matrice active
                        </span>
                    </div>

                    <div v-if="matrixData" class="btn-group btn-group-sm">
                        <button :class="['btn', view === 'grid' ? 'btn-active-palette' : 'btn-outline-secondary']"
                            @click="view = 'grid'" title="Vue grille">
                            <i class="ti ti-layout-grid"></i>
                        </button>
                        <button :class="['btn', view === 'heatmap' ? 'btn-active-palette' : 'btn-outline-secondary']"
                            @click="view = 'heatmap'" title="Vue heatmap">
                            <i class="ti ti-chart-area"></i>
                        </button>
                    </div>
                </div>
            </b-col>
        </b-row>

        <b-alert v-if="!allConfigs || !allConfigs.length" variant="warning" show class="px-3 py-2">
            <i class="ti ti-alert-triangle me-1"></i>Aucune configuration.
            <a :href="route('risk.matrix-config.index')" class="alert-link ms-1">Créer →</a>
        </b-alert>
        <b-alert v-else-if="!matrixData" variant="info" show class="px-3 py-2">
            <i class="ti ti-info-circle me-1"></i>Configuration incomplète.
        </b-alert>

        <template v-else>
            <b-row class="g-3">

                <!-- COLONNE GAUCHE : MATRICE -->
                <b-col lg="8">

                    <!-- VUE GRILLE -->
                    <b-card v-if="view === 'grid'" no-body class="shadow-sm mb-3">
                        <b-card-header class="px-3 py-2 d-flex align-items-center justify-content-between">
                            <h6 class="mb-0">
                                <i class="ti ti-grid-dots me-1"></i>
                                Grille {{ matrixData.config.matrix_label }} — max {{ matrixData.config.max_score }}
                            </h6>
                            <small class="text-muted"><i class="ti ti-mouse me-1"></i>Cliquer = épingler le détail</small>
                        </b-card-header>
                        <b-card-body class="p-2 p-md-3">
                            <div class="table-responsive">
                                <table class="mat-table">
                                    <thead>
                                        <tr>
                                            <th class="mat-corner">
                                                <span class="mat-ci">Impact ↑</span>
                                                <span class="mat-cf">→ Vraisemblance</span>
                                            </th>
                                            <th v-for="freq in freqAsc" :key="freq.id" class="mat-fhd text-center"
                                                :style="freq.color_code ? 'border-bottom:3px solid ' + freq.color_code : 'border-bottom:3px solid #e2e8f0'">
                                                <div class="d-flex flex-column align-items-center gap-1 py-1">
                                                    <span class="mat-dot"
                                                        :style="freq.color_code ? 'background:' + freq.color_code + ';color:' + onBg(freq.color_code) : 'background:#e2e8f0;color:#94a3b8'">
                                                        {{ freq.score }}
                                                    </span>
                                                    <span class="mat-flbl"
                                                        :style="freq.color_code ? 'color:' + freq.color_code : 'color:#64748b'">
                                                        {{ freq.label }}
                                                    </span>
                                                    <small v-if="freq.recurrence" class="text-muted mat-rec">{{ freq.recurrence }}</small>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, ri) in cellsDesc" :key="ri">
                                            <td class="mat-ihd"
                                                :style="getImpact(row[0]?.impact_score)?.color_code ? 'border-left:4px solid ' + getImpact(row[0].impact_score).color_code : 'border-left:4px solid #e2e8f0'">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="mat-dot"
                                                        :style="getImpact(row[0]?.impact_score)?.color_code ? 'background:' + getImpact(row[0].impact_score).color_code + ';color:' + onBg(getImpact(row[0].impact_score).color_code) : 'background:#e2e8f0;color:#94a3b8'">
                                                        {{ row[0]?.impact_score || '' }}
                                                    </span>
                                                    <span class="mat-ilbl"
                                                        :style="getImpact(row[0]?.impact_score)?.color_code ? 'color:' + getImpact(row[0].impact_score).color_code : 'color:#334155'">
                                                        {{ getImpact(row[0]?.impact_score)?.label || '' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td v-for="cell in row" :key="cell.frequency_score"
                                                class="mat-cell text-center"
                                                :class="{ 'mat-cell--active': isCellActive(cell), 'mat-cell--pinned': isCellPinned(cell) }"
                                                :style="cellBg(cell)"
                                                @mouseenter="hoveredCell = cell"
                                                @mouseleave="hoveredCell = null"
                                                @click="togglePin(cell)"
                                                :title="cellTitle(cell)">
                                                <span class="mat-score"
                                                    :style="cell.zone_color ? 'color:' + onBg(cell.zone_color) : 'color:#94a3b8'">
                                                    {{ cell.score }}
                                                </span>
                                                <div class="mat-zlbl"
                                                    :style="cell.zone_color ? 'color:' + onBg(cell.zone_color) : 'color:#cbd5e1'">
                                                    {{ cell.zone_label ?? '' }}
                                                </div>
                                                <span v-if="isCellPinned(cell)" class="mat-pin"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </b-card-body>
                    </b-card>

                    <!-- VUE HEATMAP -->
                    <b-card v-else no-body class="shadow-sm mb-3">
                        <b-card-header class="px-3 py-2">
                            <h6 class="mb-0">
                                <i class="ti ti-chart-area me-1"></i>Heatmap {{ matrixData.config.matrix_label }}
                            </h6>
                        </b-card-header>
                        <b-card-body class="p-3">
                            <div class="hm-wrap">
                                <div class="hm-ylabels">
                                    <div v-for="imp in impactDesc" :key="imp.id" class="hm-ylabel">
                                        <span :style="imp.color_code ? 'color:' + imp.color_code : ''">{{ imp.label }}</span>
                                    </div>
                                </div>
                                <div class="hm-right">
                                    <div class="hm-grid"
                                        :style="'grid-template-columns:repeat(' + matrixData.config.matrix_size + ',1fr);grid-template-rows:repeat(' + matrixData.config.matrix_size + ',1fr)'">
                                        <div v-for="cell in hmCells" :key="cell.key" class="hm-cell"
                                            :style="cell.zone_color ? 'background:' + cell.zone_color : 'background:#f1f5f9'"
                                            :title="cellTitle(cell)">
                                        </div>
                                    </div>
                                    <div class="hm-xlabels">
                                        <div v-for="freq in freqAsc" :key="freq.id" class="hm-xlabel">
                                            <span :style="freq.color_code ? 'color:' + freq.color_code : ''">{{ freq.label }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>

                    <!-- DÉTAIL CELLULE ÉPINGLÉE -->
                    <transition name="slide-down">
                        <b-card v-if="pinnedCell" no-body class="shadow-sm mb-3">
                            <b-card-header class="d-flex align-items-center justify-content-between px-3 py-2">
                                <h6 class="mb-0">
                                    <i class="ti ti-crosshair me-1"></i>
                                    I{{ pinnedCell.impact_score }} × V{{ pinnedCell.frequency_score }} =
                                    <span class="ms-1 fw-bold"
                                        :style="pinnedCell.zone_color ? 'color:' + pinnedCell.zone_color : 'color:#94a3b8'">
                                        {{ pinnedCell.score }}
                                    </span>
                                </h6>
                                <b-button size="sm" variant="light" @click="pinnedCell = null">
                                    <i class="ti ti-x"></i>
                                </b-button>
                            </b-card-header>
                            <b-card-body class="p-3">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="detail-card" :style="detailCardStyle(getImpact(pinnedCell.impact_score)?.color_code)">
                                            <div class="detail-icon"><i class="ti ti-arrow-up"></i></div>
                                            <div class="detail-label">Impact</div>
                                            <div class="detail-value">{{ getImpact(pinnedCell.impact_score)?.label || '—' }}</div>
                                            <div class="detail-score">Score {{ pinnedCell.impact_score }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-card" :style="detailCardStyle(getFreq(pinnedCell.frequency_score)?.color_code)">
                                            <div class="detail-icon"><i class="ti ti-clock"></i></div>
                                            <div class="detail-label">Vraisemblance</div>
                                            <div class="detail-value">{{ getFreq(pinnedCell.frequency_score)?.label || '—' }}</div>
                                            <div class="detail-score">Score {{ pinnedCell.frequency_score }}</div>
                                            <div v-if="getFreq(pinnedCell.frequency_score)?.recurrence" class="detail-desc">
                                                {{ getFreq(pinnedCell.frequency_score).recurrence }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-card" :style="detailCardStyle(pinnedCell.zone_color)">
                                            <div class="detail-icon"><i class="ti ti-alert-triangle"></i></div>
                                            <div class="detail-label">Criticité</div>
                                            <div class="detail-value"
                                                :style="'color:' + (pinnedCell.zone_color || '#64748b')">
                                                {{ pinnedCell.zone_label || 'Non défini' }}
                                            </div>
                                            <div class="detail-score">{{ pinnedCell.score }} pts</div>
                                        </div>
                                    </div>
                                </div>
                            </b-card-body>
                        </b-card>
                    </transition>
                </b-col>

                <!-- COLONNE DROITE : ÉDITION DES CELLULES -->
                <b-col lg="4">
                    <b-card class="shadow-sm">
                        <b-card-header class="px-3 py-2">
                            <h6 class="mb-0">
                                <i class="ti ti-edit me-1" style="color:#8b6914"></i>
                                Personnalisation des cellules
                            </h6>
                        </b-card-header>
                        <b-card-body class="p-0">
                            <div class="cell-selector p-3 border-bottom">
                                <label class="form-label small fw-semibold">Sélectionner une cellule :</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <select v-model="selectedImpact" class="form-select form-select-sm">
                                            <option v-for="imp in impactDesc" :key="imp.id" :value="imp.score">
                                                I{{ imp.score }} - {{ imp.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select v-model="selectedFreq" class="form-select form-select-sm">
                                            <option v-for="freq in freqAsc" :key="freq.id" :value="freq.score">
                                                V{{ freq.score }} - {{ freq.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-2 text-center">
                                    <span class="badge bg-light text-dark">
                                        Score = I{{ selectedImpact }} × V{{ selectedFreq }} = <strong>{{ selectedImpact * selectedFreq }}</strong>
                                    </span>
                                </div>
                            </div>

                            <div v-if="selectedCell" class="zone-edit-panel p-3">
                                <div class="form-group mb-3">
                                    <label class="form-label small fw-semibold">
                                        <i class="ti ti-tag me-1"></i>Libellé de la criticité
                                    </label>
                                    <input type="text" v-model="selectedCell.label" class="form-control"
                                        placeholder="Ex: Négligeable, Faible, Modéré, Élevé, Critique">
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label small fw-semibold">
                                        <i class="ti ti-color-swatch me-1"></i>Couleur
                                    </label>
                                    <div class="color-row">
                                        <input type="color" v-model="selectedCell.color" class="color-input">
                                        <span class="color-preview" :style="'background:' + selectedCell.color"></span>
                                        <span class="color-hex">{{ selectedCell.color }}</span>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label small fw-semibold">
                                        <i class="ti ti-notes me-1"></i>Description / Recommandation
                                    </label>
                                    <textarea v-model="selectedCell.description" rows="3" class="form-control"
                                        placeholder="Décrire ce niveau de risque et les actions recommandées..."></textarea>
                                </div>

                                <div class="preview-box"
                                    :style="'background:' + selectedCell.color + '18;border-left:4px solid ' + selectedCell.color">
                                    <div class="preview-label" :style="'color:' + selectedCell.color">
                                        {{ selectedCell.label || 'Nouvelle criticité' }}
                                    </div>
                                    <div class="preview-score">{{ selectedImpact * selectedFreq }} pts</div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button class="btn btn-palette btn-sm" @click="saveCurrentCell">
                                        <i class="ti ti-device-floppy me-1"></i>Enregistrer
                                    </button>
                                </div>
                            </div>

                            <div v-else class="text-center text-muted py-5">
                                <i class="ti ti-grid-dots fs-2 opacity-25"></i>
                                <p class="small mt-2">Sélectionnez une cellule pour la personnaliser</p>
                            </div>
                        </b-card-body>
                    </b-card>

                    <!-- Liste des zones définies -->
                    <b-card class="shadow-sm mt-3">
                        <b-card-header class="px-3 py-2">
                            <h6 class="mb-0"><i class="ti ti-list me-1"></i>Zones de criticité définies</h6>
                        </b-card-header>
                        <b-card-body class="p-2">
                            <div v-for="zone in sortedZones" :key="zone.id" class="legend-item"
                                :style="'border-left:4px solid ' + zone.color_code">
                                <span class="legend-dot" :style="'background:' + zone.color_code"></span>
                                <span class="legend-label" :style="'color:' + zone.color_code">{{ zone.label }}</span>
                                <span class="legend-coord">{{ zone.min_score }}–{{ zone.max_score }}</span>
                                <span class="legend-score">pts</span>
                            </div>
                            <div v-if="!sortedZones.length" class="text-center text-muted small py-3">
                                Aucune zone définie. Sélectionnez une cellule pour commencer.
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>

            </b-row>
        </template>
    </VerticalLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'

// ─── Palette P2 : vert → olive → orange → brun-rouge ───────────────────────
// Ces couleurs correspondent à la palette de la seconde matrice.
// Elles sont utilisées comme valeurs par défaut si les color_code
// ne sont pas renseignés dans la configuration backend.
const PALETTE_IMPACTS = {
    5: '#8B2500',
    4: '#C04020',
    3: '#D4700A',
    2: '#B89B20',
    1: '#5A8A30',
}
const PALETTE_FREQS = {
    1: '#5A8A30',
    2: '#8A9A28',
    3: '#C07830',
    4: '#C04820',
    5: '#8B2500',
}
// ────────────────────────────────────────────────────────────────────────────

// Props
const props = defineProps({
    allConfigs: { type: Array, default: () => [] },
    selectedConfigId: { type: Number, default: null },
    matrixData: { type: Object, default: null },
    masteryLevels: { type: Array, default: () => [] },
})

// État local
const allConfigs     = ref(props.allConfigs || [])
const matrixData     = ref(props.matrixData)
const masteryLevels  = ref(props.masteryLevels || [])
const currentConfigId = ref(props.selectedConfigId)
const activeCfg = computed(() => allConfigs.value.find(c => c.is_active) || allConfigs.value.find(c => c.id === props.selectedConfigId) || allConfigs.value[0] || null)
const view           = ref('grid')
const hoveredCell    = ref(null)
const pinnedCell     = ref(null)
const saveStatus     = ref('idle')
const selectedImpact = ref(1)
const selectedFreq   = ref(1)
const selectedCell   = ref(null)

// ─── Computed ───────────────────────────────────────────────────────────────

const sortedZones = computed(() => {
    if (!matrixData.value?.zones) return []
    // Zones par PLAGE de score (min_score → max_score), du plus faible au plus fort.
    return [...matrixData.value.zones].sort((a, b) => (a.min_score ?? 0) - (b.min_score ?? 0))
})

const impactDesc = computed(() =>
    [...(matrixData.value?.impacts ?? [])].sort((a, b) => b.score - a.score)
)

const freqAsc = computed(() =>
    [...(matrixData.value?.frequencies ?? [])].sort((a, b) => a.score - b.score)
)

const cellsDesc = computed(() =>
    matrixData.value?.cells
        ? [...matrixData.value.cells].sort((a, b) => b[0]?.impact_score - a[0]?.impact_score)
        : []
)

const hmCells = computed(() => {
    const out = []
    for (const row of cellsDesc.value) {
        for (const cell of [...row].sort((a, b) => a.frequency_score - b.frequency_score)) {
            out.push({ key: `${cell.impact_score}_${cell.frequency_score}`, ...cell })
        }
    }
    return out
})

const impactMap = computed(() => {
    const map = {}
    for (const imp of (matrixData.value?.impacts ?? [])) map[imp.score] = imp
    return map
})

const freqMap = computed(() => {
    const map = {}
    for (const freq of (matrixData.value?.frequencies ?? [])) map[freq.score] = freq
    return map
})

const getImpact = (s) => impactMap.value[s] ?? null
const getFreq   = (s) => freqMap.value[s]   ?? null

// ─── Helpers ────────────────────────────────────────────────────────────────

/**
 * Retourne la couleur de texte (noir ou blanc) adaptée au fond hex donné.
 */
const onBg = (hex) => {
    if (!hex || hex === '#') return '#fff'
    try {
        const r = parseInt(hex.slice(1, 3), 16)
        const g = parseInt(hex.slice(3, 5), 16)
        const b = parseInt(hex.slice(5, 7), 16)
        return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.55 ? '#1f2937' : '#fff'
    } catch {
        return '#fff'
    }
}

const detailCardStyle = (hex) =>
    hex
        ? `border-left:4px solid ${hex};background:${hex}12;border-radius:6px;padding:.5rem .75rem`
        : 'border-left:4px solid #e2e8f0;background:#f8fafc;border-radius:6px;padding:.5rem .75rem'

const cellTitle = (cell) =>
    `I${cell.impact_score} × V${cell.frequency_score} = ${cell.score}` +
    (cell.zone_label ? ` — ${cell.zone_label}` : '')

const isCellPinned = (c) =>
    !!pinnedCell.value &&
    pinnedCell.value.impact_score    === c.impact_score &&
    pinnedCell.value.frequency_score === c.frequency_score

const isCellActive = (c) =>
    (!!hoveredCell.value &&
     hoveredCell.value.impact_score    === c.impact_score &&
     hoveredCell.value.frequency_score === c.frequency_score) ||
    isCellPinned(c)

const togglePin = (cell) => {
    pinnedCell.value = isCellPinned(cell) ? null : { ...cell }
}

const cellBg = (cell) => {
    if (!cell.zone_color) {
        return isCellActive(cell) ? 'background:#f1f5f9' : 'background:#f8fafc'
    }
    const c = cell.zone_color
    if (isCellPinned(cell)) return `background:${c}ee`
    if (isCellActive(cell)) return `background:${c}bb`
    return `background:${c}88`
}

// ─── Navigation / config ────────────────────────────────────────────────────

const onConfigChange = () => {
    router.get(
        route('risk.matrix.index'),
        { config_id: currentConfigId.value },
        { preserveState: true, preserveScroll: true },
    )
}

// ─── Sauvegarde ─────────────────────────────────────────────────────────────

const flash = () => {
    saveStatus.value = 'saved'
    setTimeout(() => { saveStatus.value = 'idle' }, 2500)
}

const saveCurrentCell = async () => {
    if (!selectedCell.value) return

    const data = {
        impact_score:    selectedImpact.value,
        frequency_score: selectedFreq.value,
        label:           selectedCell.value.label,
        color_code:      selectedCell.value.color,
        description:     selectedCell.value.description || '',
    }

    saveStatus.value = 'saving'

    try {
        if (selectedCell.value.id) {
            await axios.put(`/m/risk.core/matrix-config/zones/${selectedCell.value.id}`, data)
        } else {
            await axios.post(`/m/risk.core/matrix-config/${currentConfigId.value}/zones`, data)
        }
        flash()
        await refreshMatrixData()
    } catch (error) {
        console.error('Save error:', error)
        saveStatus.value = 'error'
        alert('Erreur lors de la sauvegarde : ' + (error.response?.data?.message || error.message))
    }
}

// ─── Chargement de la cellule sélectionnée ──────────────────────────────────

const loadSelectedCell = () => {
    const cell = findCell(selectedImpact.value, selectedFreq.value)
    if (cell) {
        selectedCell.value = {
            id:          cell.zone_id,
            label:       cell.zone_label       || '',
            color:       cell.zone_color       || paletteColor(selectedImpact.value, selectedFreq.value),
            description: cell.zone_description || '',
        }
    } else {
        selectedCell.value = {
            id:          null,
            label:       '',
            color:       paletteColor(selectedImpact.value, selectedFreq.value),
            description: '',
        }
    }
}

/**
 * Couleur par défaut issue de la palette P2 selon le score de criticité.
 */
const paletteColor = (i, f) => {
    const s = i * f
    if (s >= 20) return '#8B2500'
    if (s >= 12) return '#C04020'
    if (s >= 6)  return '#C07830'
    if (s >= 3)  return '#8A9A28'
    return '#5A8A30'
}

const findCell = (impact, freq) => {
    for (const row of matrixData.value?.cells ?? []) {
        for (const cell of row) {
            if (cell.impact_score === impact && cell.frequency_score === freq) return cell
        }
    }
    return null
}

// ─── Rafraîchissement ───────────────────────────────────────────────────────

const refreshMatrixData = async () => {
    try {
        const response = await axios.get(route('risk.matrix.data'), {
            params: { config_id: currentConfigId.value },
        })
        if (response.data.matrixData)   matrixData.value    = response.data.matrixData
        if (response.data.masteryLevels) masteryLevels.value = response.data.masteryLevels
        loadSelectedCell()
    } catch (error) {
        console.error('Refresh error:', error)
    }
}

// ─── Watchers / lifecycle ───────────────────────────────────────────────────

watch([selectedImpact, selectedFreq], () => {
    loadSelectedCell()
})

onMounted(() => {
    if (selectedImpact.value && selectedFreq.value) loadSelectedCell()
})
</script>

<style scoped>
/* ── Boutons palette ───────────────────────────────────────────── */
.btn-palette {
    background: #4c3e2a;
    color: #f0d080;
    border: none;
}
.btn-palette:hover { background: #3a2e1e; color: #f0d080; }

.btn-active-palette {
    background: #4c3e2a !important;
    color: #f0d080 !important;
    border-color: #8b6914 !important;
}

/* ── Matrice ───────────────────────────────────────────────────── */
.mat-table { border-collapse: collapse; width: 100%; }

.mat-corner {
    width: 130px; min-width: 130px;
    padding: .45rem;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
}
.mat-ci, .mat-cf {
    display: block;
    font-size: .6rem; font-weight: 700;
    text-transform: uppercase; color: #6b7280;
}
.mat-ci { text-align: right; }
.mat-cf { text-align: center; margin-top: 3px; }

.mat-fhd {
    padding: .3rem .2rem;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    min-width: 85px;
}
.mat-ihd {
    padding: .28rem .4rem;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    white-space: nowrap;
}

.mat-flbl { font-size: .7rem; font-weight: 600; }
.mat-ilbl { font-size: .75rem; font-weight: 600; }
.mat-rec  { font-size: .55rem; }

.mat-dot {
    width: 24px; height: 24px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .7rem; font-weight: 700;
    flex-shrink: 0;
}

.mat-cell {
    width: 85px; min-width: 85px; height: 70px;
    border: 1px solid rgba(255,255,255,.3);
    cursor: pointer;
    transition: all .14s;
    position: relative;
}
.mat-cell:hover           { filter: brightness(.82); outline: 2px solid rgba(0,0,0,.22); z-index: 2; }
.mat-cell--active         { outline: 2px solid rgba(0,0,0,.3) !important; z-index: 3; }
.mat-cell--pinned         { outline: 3px solid rgba(0,0,0,.52) !important; z-index: 4; }

.mat-score  { font-size: .9rem; font-weight: 800; display: block; }
.mat-zlbl   {
    font-size: .55rem; font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 80px;
}
.mat-pin {
    position: absolute; top: 3px; right: 3px;
    width: 6px; height: 6px;
    background: #fff; border-radius: 50%;
}

/* ── Heatmap ───────────────────────────────────────────────────── */
.hm-wrap    { display: flex; gap: 12px; align-items: stretch; min-height: 280px; }
.hm-ylabels { display: flex; flex-direction: column; justify-content: space-around; width: 100px; text-align: right; }
.hm-ylabel  { font-size: .7rem; font-weight: 600; padding: 4px 0; }
.hm-right   { flex: 1; display: flex; flex-direction: column; gap: 8px; }
.hm-grid    {
    display: grid; flex: 1;
    gap: 2px; border-radius: 8px; overflow: hidden;
}
.hm-cell        { border-radius: 3px; transition: all .1s; cursor: pointer; }
.hm-cell:hover  { filter: brightness(.82); }
.hm-xlabels { display: flex; justify-content: space-around; margin-top: 6px; }
.hm-xlabel  { font-size: .65rem; font-weight: 600; text-align: center; flex: 1; }

/* ── Détail cellule ────────────────────────────────────────────── */
.detail-card  { padding: .8rem; border-radius: 10px; background: #fff; height: 100%; }
.detail-icon  { font-size: 1.4rem; margin-bottom: .5rem; color: #94a3b8; }
.detail-label { font-size: .6rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: .25rem; }
.detail-value { font-size: 1rem; font-weight: 700; margin-bottom: .25rem; }
.detail-score { font-size: .7rem; font-family: monospace; color: #64748b; }
.detail-desc  { font-size: .65rem; color: #64748b; margin-top: .5rem; }

/* ── Panneau d'édition ─────────────────────────────────────────── */
.cell-selector  { background: #fafafa; }
.zone-edit-panel { background: #fff; }

.form-group label {
    font-size: .7rem; font-weight: 600; color: #475569;
    display: block; margin-bottom: 4px;
}
.form-control, .form-select {
    font-size: .75rem; padding: 6px 10px;
    border: 1px solid #e2e8f0; border-radius: 6px; width: 100%;
}
.form-control:focus, .form-select:focus {
    outline: none;
    border-color: #8b6914;
    box-shadow: 0 0 0 2px rgba(139,105,20,.15);
}

.color-row   { display: flex; align-items: center; gap: 10px; }
.color-input { width: 45px; height: 35px; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; }
.color-preview { width: 30px; height: 30px; border-radius: 6px; border: 1px solid #e2e8f0; }
.color-hex   { font-family: monospace; font-size: .7rem; color: #64748b; }

.preview-box   { padding: 12px; border-radius: 0; margin-top: 12px; border-left: 4px solid #ccc; }
.preview-label { font-weight: 700; font-size: .9rem; margin-bottom: 4px; }
.preview-score { font-size: .7rem; font-family: monospace; opacity: .7; }

.form-actions { display: flex; gap: 8px; }

/* ── Légende des zones ─────────────────────────────────────────── */
.legend-item  {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 10px; margin-bottom: 4px;
    border-radius: 6px; background: #f8fafc;
    border-left: 4px solid transparent;
}
.legend-dot   { width: 10px; height: 10px; border-radius: 50%; }
.legend-label { font-weight: 600; font-size: .7rem; }
.legend-coord {
    font-size: .6rem; font-family: monospace; color: #64748b;
    background: #e2e8f0; padding: 1px 4px; border-radius: 4px;
}
.legend-score { font-size: .65rem; font-weight: 600; color: #475569; }

/* ── Transitions ───────────────────────────────────────────────── */
.slide-down-enter-active { transition: all .22s ease-out; }
.slide-down-enter-from   { opacity: 0; transform: translateY(-8px); }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>