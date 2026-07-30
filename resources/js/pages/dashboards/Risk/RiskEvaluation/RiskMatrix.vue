<template>
    <div class="risk-matrix-wrapper">

        <!-- Label axe horizontal -->
        <div class="text-center small text-muted mb-2" style="padding-left:96px">
            <i class="ti ti-repeat me-1"></i>Fréquence / Vraisemblance →
        </div>

        <div class="d-flex align-items-stretch gap-1">

            <!-- Label axe vertical (Impact ↑) -->
            <div
                class="d-flex align-items-center justify-content-center flex-shrink-0"
                style="width:18px"
            >
                <span
                    class="small text-muted"
                    style="writing-mode:vertical-rl; transform:rotate(180deg); white-space:nowrap; font-size:.7rem"
                >↑ Impact</span>
            </div>

            <!-- Table matrice -->
            <div style="overflow-x:auto; flex-grow:1; min-width:0">
                <table
                    class="risk-matrix"
                    style="border-collapse:separate; border-spacing:3px; table-layout:auto; width:100%"
                >
                    <!-- Ligne d'en-tête : niveaux de fréquence -->
                    <thead>
                        <tr>
                            <th style="width:96px; min-width:80px"></th>
                            <th
                                v-for="freq in sortedFrequencies"
                                :key="freq.id"
                                class="text-center pb-1"
                                style="min-width:56px; vertical-align:bottom"
                            >
                                <span
                                    class="badge d-inline-block"
                                    :style="{ backgroundColor: freq.color ?? '#6c757d', color: '#fff', fontSize: '.65rem' }"
                                >{{ freq.label }}</span>
                                <div class="text-muted" style="font-size:.6rem">×{{ freq.score }}</div>
                            </th>
                        </tr>
                    </thead>

                    <!-- Corps : Impact (du plus élevé au plus faible) -->
                    <tbody>
                        <tr v-for="impact in sortedImpacts" :key="impact.id">

                            <!-- Label impact -->
                            <td
                                class="text-end pe-2"
                                style="vertical-align:middle; white-space:nowrap"
                            >
                                <span
                                    class="badge"
                                    :style="{ backgroundColor: impact.color ?? '#6c757d', color: '#fff', fontSize: '.65rem' }"
                                >{{ impact.label }}</span>
                                <div class="text-muted" style="font-size:.6rem">×{{ impact.score }}</div>
                            </td>

                            <!-- Cellules -->
                            <td
                                v-for="freq in sortedFrequencies"
                                :key="freq.id"
                                class="matrix-cell"
                                :style="cellStyle(impact, freq)"
                                style="height:52px; min-width:56px; text-align:center; vertical-align:middle; border-radius:5px; cursor:pointer; position:relative;"
                                :title="`Impact ${impact.label} × Fréquence ${freq.label} = ${impact.score * freq.score}`"
                                @click="$emit('cell-click', impact.id, freq.id)"
                            >
                                <!-- Marqueur (point pulsant) sur la cellule sélectionnée -->
                                <div
                                    v-if="selectedImpact === impact.id && selectedFrequency === freq.id"
                                    class="matrix-marker"
                                    style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%)"
                                >
                                    <div class="marker-ring"></div>
                                    <div class="marker-dot"></div>
                                </div>

                                <!-- Score de la cellule -->
                                <span
                                    class="fw-bold"
                                    :style="{
                                        fontSize: '.72rem',
                                        color: '#fff',
                                        textShadow: '0 1px 2px rgba(0,0,0,.45)',
                                        opacity: (selectedImpact === impact.id && selectedFrequency === freq.id) ? 0 : 0.75,
                                    }"
                                >{{ impact.score * freq.score }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    impactLevels:      { type: Array, default: () => [] },
    frequencyLevels:   { type: Array, default: () => [] },
    zones:             { type: Array, default: () => [] },
    selectedImpact:    { type: [Number, String, null], default: null },
    selectedFrequency: { type: [Number, String, null], default: null },
})

defineEmits(['cell-click'])

// Impact trié du plus élevé au plus faible (ligne du haut = risque maximal)
const sortedImpacts = computed(() =>
    [...props.impactLevels].sort((a, b) => b.score - a.score)
)

// Fréquence triée du plus faible au plus élevé (colonne gauche = rare)
const sortedFrequencies = computed(() =>
    [...props.frequencyLevels].sort((a, b) => a.score - b.score)
)

function findZone(score) {
    return props.zones.find(z => score >= z.min_score && score <= z.max_score) ?? null
}

function cellStyle(impact, freq) {
    const score    = impact.score * freq.score
    const zone     = findZone(score)
    const color    = zone?.color_code ?? '#dee2e6'
    const isActive = props.selectedImpact === impact.id && props.selectedFrequency === freq.id
    return {
        backgroundColor: color,
        boxShadow: isActive ? `inset 0 0 0 3px rgba(255,255,255,.8), 0 0 0 2px ${color}` : 'none',
        transition: 'box-shadow .15s',
    }
}
</script>

<style scoped>
.matrix-cell:hover {
    filter: brightness(1.12);
    z-index: 1;
}

/* ── Marqueur pulsant ─────────────────────────────────────────── */
.matrix-marker {
    width: 26px;
    height: 26px;
    pointer-events: none;
}

.marker-dot {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 13px;
    height: 13px;
    background: #fff;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 1px 4px rgba(0,0,0,.45);
    z-index: 2;
}

.marker-ring {
    position: absolute;
    top: 0;
    left: 0;
    width: 26px;
    height: 26px;
    border: 3px solid rgba(255,255,255,.85);
    border-radius: 50%;
    animation: ring-pulse 1.8s ease-out infinite;
    z-index: 1;
}

@keyframes ring-pulse {
    0%   { transform: scale(.6); opacity: 1; }
    70%  { transform: scale(1.5); opacity: 0; }
    100% { transform: scale(.6); opacity: 0; }
}
</style>
