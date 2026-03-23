<template>
    <VerticalLayout>
        <Head title="DDM — Matrice des risques" />

        <!-- HEADER -->
        <b-row class="mb-2">
            <b-col class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-layout-grid text-primary fs-5"></i>
                    <h4 class="fw-semibold m-0">Matrice des risques</h4>
                    <small class="text-muted ms-1">Impact × Fréquence — criticité = I × F</small>
                </div>
                <div class="d-flex align-items-center gap-2" v-if="allConfigs.length">
                    <label class="form-label text-muted small mb-0">Configuration :</label>
                    <select v-model="currentConfigId" @change="onConfigChange" class="form-select form-select-sm" style="width: auto">
                        <option v-for="c in allConfigs" :key="c.id" :value="c.id">
                            {{ c.name }} ({{ c.matrix_label }}){{ c.is_active ? ' ✓' : '' }}
                        </option>
                    </select>
                </div>
            </b-col>
        </b-row>

        <!-- Pas de config -->
        <b-alert v-if="!allConfigs.length" variant="warning" show class="px-3 py-2">
            <i class="ti ti-alert-triangle me-1"></i>
            Aucune configuration disponible.
            <a :href="route('risk.core.matrix-config.index')" class="alert-link ms-1">Créer une configuration →</a>
        </b-alert>

        <!-- Config incomplète -->
        <b-alert v-else-if="!matrixData" variant="info" show class="px-3 py-2">
            <i class="ti ti-info-circle me-1"></i>
            Cette configuration n'est pas encore complète.
            <a :href="route('risk.core.impact.index', { config_id: currentConfigId })" class="alert-link ms-1"> Définir les impacts </a>
            <span class="text-muted mx-1">·</span>
            <a :href="route('risk.core.frequency.index', { config_id: currentConfigId })" class="alert-link"> Définir les fréquences </a>
        </b-alert>

        <template v-else>
            <!-- Bandeau config active -->
            <b-alert v-if="matrixData.config.is_active" variant="primary" show class="d-flex align-items-center mb-2 gap-2 px-3 py-2">
                <i class="ti ti-circle-check"></i>
                Configuration active · {{ matrixData.config.matrix_label }} · Score max : {{ matrixData.config.max_score }}
            </b-alert>

            <b-row class="g-3">
                <!-- COLONNE GAUCHE : Heatmap -->
                <b-col lg="8">
                    <b-card no-body class="shadow-sm">
                        <b-card-header class="px-3 py-2">
                            <h6 class="mb-0">
                                <i class="ti ti-grid-dots me-1"></i>
                                Grille {{ matrixData.config.matrix_label }}
                            </h6>
                        </b-card-header>
                        <b-card-body class="p-3">
                            <div class="table-responsive">
                                <table class="matrix-table">
                                    <!-- Headers fréquences -->
                                    <thead>
                                        <tr>
                                            <th class="matrix-corner">
                                                <div class="corner-label-impact">Impact ↑</div>
                                                <div class="corner-label-freq">→ Fréquence</div>
                                            </th>
                                            <th
                                                v-for="freq in matrixData.frequencies"
                                                :key="freq.id"
                                                class="freq-header text-center"
                                                :style="{ borderBottom: `3px solid ${freq.color_code}` }"
                                            >
                                                <div class="d-flex flex-column align-items-center gap-1">
                                                    <span class="score-dot text-white" :style="{ background: freq.color_code }">{{
                                                        freq.score
                                                    }}</span>
                                                    <span class="fw-semibold small" :style="{ color: freq.color_code }">{{ freq.label }}</span>
                                                    <small v-if="freq.recurrence" class="text-muted" style="font-size: 0.62rem">{{
                                                        freq.recurrence
                                                    }}</small>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <!-- Corps — lignes par impact -->
                                    <tbody>
                                        <tr v-for="(row, rowIdx) in matrixData.cells" :key="rowIdx">
                                            <!-- Label impact -->
                                            <td
                                                class="impact-header"
                                                :style="{ borderLeft: `3px solid ${impactByScore(row[0].impact_score)?.color_code}` }"
                                            >
                                                <div class="d-flex align-items-center gap-2">
                                                    <span
                                                        class="score-dot text-white"
                                                        :style="{ background: impactByScore(row[0].impact_score)?.color_code }"
                                                    >
                                                        {{ row[0].impact_score }}
                                                    </span>
                                                    <span
                                                        class="fw-semibold small"
                                                        :style="{ color: impactByScore(row[0].impact_score)?.color_code }"
                                                    >
                                                        {{ impactByScore(row[0].impact_score)?.label }}
                                                    </span>
                                                </div>
                                            </td>
                                            <!-- Cellules -->
                                            <td
                                                v-for="(cell, colIdx) in row"
                                                :key="colIdx"
                                                class="matrix-cell text-center"
                                                :style="{ background: cellBg(cell) }"
                                                :class="{ 'cell-active': isCellActive(cell) }"
                                                @mouseenter="hoveredCell = cell"
                                                @mouseleave="hoveredCell = null"
                                                @click="togglePin(cell)"
                                                :title="`Impact ${cell.impact_score} × Fréquence ${cell.frequency_score} = ${cell.score} (${cell.zone_label ?? ''})`"
                                            >
                                                <span class="cell-score" :style="{ color: cellTextColor(cell) }">
                                                    {{ cell.score }}
                                                </span>
                                                <span v-if="isCellPinned(cell)" class="cell-pin"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>

                <!-- COLONNE DROITE : Détail + Légende + Stats -->
                <b-col lg="4">
                    <!-- Détail cellule active -->
                    <b-card v-if="activeCell" no-body class="mb-3 shadow-sm">
                        <b-card-header class="d-flex justify-content-between align-items-center px-3 py-2">
                            <h6 class="mb-0"><i class="ti ti-info-circle me-1"></i>Détail</h6>
                            <b-button v-if="pinnedCell" size="sm" variant="light" @click="pinnedCell = null">
                                <i class="ti ti-x"></i>
                            </b-button>
                        </b-card-header>
                        <b-card-body class="p-2">
                            <div class="d-flex mb-2 gap-2">
                                <!-- Impact -->
                                <div
                                    class="detail-block flex-fill"
                                    :style="{
                                        borderLeft: `3px solid ${impactByScore(activeCell.impact_score)?.color_code}`,
                                        background: (impactByScore(activeCell.impact_score)?.color_code ?? '#6b7280') + '12',
                                    }"
                                >
                                    <small class="text-muted text-uppercase d-block" style="font-size: 0.62rem">Impact</small>
                                    <span class="fw-semibold small d-block" :style="{ color: impactByScore(activeCell.impact_score)?.color_code }">
                                        {{ impactByScore(activeCell.impact_score)?.label ?? '—' }}
                                    </span>
                                    <small class="font-monospace text-muted">Score {{ activeCell.impact_score }}</small>
                                </div>
                                <!-- Fréquence -->
                                <div
                                    class="detail-block flex-fill"
                                    :style="{
                                        borderLeft: `3px solid ${freqByScore(activeCell.frequency_score)?.color_code}`,
                                        background: (freqByScore(activeCell.frequency_score)?.color_code ?? '#6b7280') + '12',
                                    }"
                                >
                                    <small class="text-muted text-uppercase d-block" style="font-size: 0.62rem">Fréquence</small>
                                    <span class="fw-semibold small d-block" :style="{ color: freqByScore(activeCell.frequency_score)?.color_code }">
                                        {{ freqByScore(activeCell.frequency_score)?.label ?? '—' }}
                                    </span>
                                    <small class="font-monospace text-muted">Score {{ activeCell.frequency_score }}</small>
                                </div>
                            </div>
                            <!-- Criticité -->
                            <div
                                class="detail-block criticality-block"
                                :style="{ borderLeft: `4px solid ${activeCell.zone_color}`, background: (activeCell.zone_color ?? '#6b7280') + '18' }"
                            >
                                <div class="d-flex align-items-center gap-3">
                                    <div>
                                        <small class="text-muted text-uppercase d-block" style="font-size: 0.62rem">Criticité</small>
                                        <span class="fw-bold fs-3 lh-1" :style="{ color: activeCell.zone_color }">{{ activeCell.score }}</span>
                                    </div>
                                    <div>
                                        <span class="fw-semibold d-block" :style="{ color: activeCell.zone_color }">{{
                                            activeCell.zone_label ?? '—'
                                        }}</span>
                                        <small class="font-monospace text-muted"
                                            >{{ activeCell.impact_score }} × {{ activeCell.frequency_score }}</small
                                        >
                                    </div>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>

                    <!-- Légende zones -->
                    <b-card no-body class="mb-3 shadow-sm">
                        <b-card-header class="px-3 py-2">
                            <h6 class="mb-0"><i class="ti ti-color-swatch me-1"></i>Zones de criticité</h6>
                        </b-card-header>
                        <b-card-body class="p-2">
                            <div
                                v-for="zone in matrixData.zones"
                                :key="zone.id"
                                class="zone-legend-item d-flex align-items-center mb-1 gap-2"
                                :class="{ 'zone-highlighted': highlightZone === zone.label }"
                                @mouseenter="highlightZone = zone.label"
                                @mouseleave="highlightZone = null"
                                :style="{ borderLeft: `3px solid ${zone.color_code}`, background: zone.color_code + '12' }"
                            >
                                <span class="color-dot" :style="{ background: zone.color_code }"></span>
                                <span class="fw-semibold small" :style="{ color: zone.color_code }">{{ zone.label }}</span>
                                <small class="font-monospace text-muted ms-auto">[{{ zone.min_score }}–{{ zone.max_score }}]</small>
                            </div>
                        </b-card-body>
                    </b-card>

                    <!-- Stats par zone -->
                    <b-card no-body class="shadow-sm">
                        <b-card-header class="px-3 py-2">
                            <h6 class="mb-0"><i class="ti ti-chart-bar me-1"></i>Répartition</h6>
                        </b-card-header>
                        <b-card-body class="p-2">
                            <div v-for="zone in matrixData.zones" :key="zone.id + '_stat'" class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-semibold" :style="{ color: zone.color_code }">{{ zone.label }}</small>
                                    <small class="font-monospace text-muted">{{ zoneCellCount(zone) }} cell. · {{ zoneCellPercent(zone) }}%</small>
                                </div>
                                <div class="progress" style="height: 6px">
                                    <div class="progress-bar" :style="{ width: zoneCellPercent(zone) + '%', background: zone.color_code }"></div>
                                </div>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
            </b-row>
        </template>
    </VerticalLayout>
</template>

<script setup>
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    allConfigs: { type: Array, default: () => [] },
    selectedConfigId: { type: Number, default: null },
    matrixData: { type: Object, default: null },
});

const CELL_SIZE = computed(() => {
    const size = props.matrixData?.config?.matrix_size ?? 5;
    if (size <= 3) return 88;
    if (size === 4) return 80;
    if (size === 5) return 72;
    if (size === 6) return 64;
    if (size === 7) return 58;
    if (size === 8) return 52;
    if (size === 9) return 46;
    return 42; // 10x10
});
const currentConfigId = ref(props.selectedConfigId);
const hoveredCell = ref(null);
const pinnedCell = ref(null);
const highlightZone = ref(null);

const activeCell = computed(() => pinnedCell.value ?? hoveredCell.value);
const totalCells = computed(() => (props.matrixData ? props.matrixData.config.matrix_size ** 2 : 0));

const impactByScore = (score) => props.matrixData?.impacts?.find((i) => i.score === score) ?? null;
const freqByScore = (score) => props.matrixData?.frequencies?.find((f) => f.score === score) ?? null;

const isCellPinned = (cell) => pinnedCell.value?.impact_score === cell.impact_score && pinnedCell.value?.frequency_score === cell.frequency_score;

const isCellActive = (cell) =>
    (hoveredCell.value?.impact_score === cell.impact_score && hoveredCell.value?.frequency_score === cell.frequency_score) || isCellPinned(cell);

const togglePin = (cell) => {
    pinnedCell.value = isCellPinned(cell) ? null : cell;
};

const cellBg = (cell) => {
    const base = cell.zone_color ?? '#6b7280';
    if (highlightZone.value) return base + (cell.zone_label === highlightZone.value ? 'cc' : '18');
    if (isCellPinned(cell)) return base + 'dd';
    return base + '55';
};

const cellTextColor = (cell) => {
    if (highlightZone.value && cell.zone_label !== highlightZone.value) return '#9ca3af';
    const hex = (cell.zone_color ?? '#6b7280').replace('#', '');
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.55 ? '#1f2937' : '#ffffff';
};

const zoneCellCount = (zone) => (props.matrixData ? props.matrixData.cells.flat().filter((c) => c.zone_label === zone.label).length : 0);
const zoneCellPercent = (zone) => (totalCells.value ? Math.round((zoneCellCount(zone) / totalCells.value) * 100) : 0);

const onConfigChange = () =>
    router.get(route('risk.core.matrix.index'), { config_id: currentConfigId.value }, { preserveState: true, preserveScroll: true });
</script>

<style scoped>
.btn-sm {
    padding: 0.15rem 0.45rem;
    font-size: 0.72rem;
}

/* Matrice */
.matrix-table {
    border-collapse: collapse;
    width: 100%;
}
.matrix-corner {
    width: 140px;
    min-width: 140px;
    padding: 0.5rem;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    position: relative;
}
.corner-label-impact {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #6b7280;
    text-align: right;
}
.corner-label-freq {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #6b7280;
    text-align: center;
    margin-top: 4px;
}
.freq-header {
    padding: 0.4rem 0.3rem;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    min-width: 72px;
}
.impact-header {
    padding: 0.3rem 0.5rem;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    white-space: nowrap;
}
.matrix-cell {
    width: 72px;
    min-width: 72px;
    height: 68px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
    position: relative;
}
.matrix-cell:hover {
    filter: brightness(0.88);
    outline: 2px solid rgba(0, 0, 0, 0.2);
    z-index: 2;
}
.cell-active {
    outline: 2px solid rgba(0, 0, 0, 0.35) !important;
    z-index: 3;
}
.cell-score {
    font-size: 0.95rem;
    font-weight: 700;
    display: block;
    user-select: none;
}
.cell-pin {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 6px;
    height: 6px;
    background: white;
    border-radius: 50%;
    opacity: 0.85;
}
.score-dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 700;
    flex-shrink: 0;
}

/* Détail */
.detail-block {
    padding: 0.4rem 0.6rem;
    border-radius: 6px;
    border-left-width: 3px;
    border-left-style: solid;
}
.criticality-block {
    margin-top: 0.25rem;
}

/* Légende */
.zone-legend-item {
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    cursor: default;
    transition: all 0.15s;
}
.zone-highlighted {
    filter: brightness(0.95);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}
.color-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}
</style>
