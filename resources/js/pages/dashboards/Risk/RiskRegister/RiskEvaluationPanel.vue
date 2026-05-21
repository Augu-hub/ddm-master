<template>
    <div>
        <!-- Sélecteurs Impact & Fréquence côte à côte -->
        <div class="row g-3 mb-3">

            <!-- ── Impact ─────────────────────────────────────────────────── -->
            <div class="col-sm-6">
                <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.04em">
                    <i class="ti ti-trending-up me-1"></i>Impact
                </div>
                <div class="d-flex flex-column gap-2">
                    <button
                        v-for="level in impactLevels"
                        :key="level.id"
                        type="button"
                        class="btn btn-sm text-start position-relative px-3 py-2"
                        :style="levelStyle(level.color, modelImpact === level.id)"
                        @click="toggleImpact(level.id)"
                    >
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div class="flex-grow-1">
                                <span class="fw-semibold">{{ level.label }}</span>
                                <span
                                    v-if="level.description"
                                    class="d-block small text-truncate"
                                    style="max-width:180px; opacity:.75"
                                >{{ level.description }}</span>
                            </div>
                            <span
                                class="badge flex-shrink-0"
                                :style="{ backgroundColor: level.color ?? '#6c757d', color: '#fff', minWidth: '2rem' }"
                            >{{ level.score }}</span>
                        </div>
                        <i
                            v-if="modelImpact === level.id"
                            class="ti ti-check position-absolute"
                            style="right:8px; top:50%; transform:translateY(-50%); font-size:.9rem"
                        ></i>
                    </button>
                </div>
            </div>

            <!-- ── Fréquence ──────────────────────────────────────────────── -->
            <div class="col-sm-6">
                <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.04em">
                    <i class="ti ti-repeat me-1"></i>Fréquence / Vraisemblance
                </div>
                <div class="d-flex flex-column gap-2">
                    <button
                        v-for="level in frequencyLevels"
                        :key="level.id"
                        type="button"
                        class="btn btn-sm text-start position-relative px-3 py-2"
                        :style="levelStyle(level.color, modelFrequency === level.id)"
                        @click="toggleFrequency(level.id)"
                    >
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div class="flex-grow-1">
                                <span class="fw-semibold">{{ level.label }}</span>
                                <span
                                    v-if="level.description"
                                    class="d-block small text-truncate"
                                    style="max-width:180px; opacity:.75"
                                >{{ level.description }}</span>
                            </div>
                            <span
                                class="badge flex-shrink-0"
                                :style="{ backgroundColor: level.color ?? '#6c757d', color: '#fff', minWidth: '2rem' }"
                            >{{ level.score }}</span>
                        </div>
                        <i
                            v-if="modelFrequency === level.id"
                            class="ti ti-check position-absolute"
                            style="right:8px; top:50%; transform:translateY(-50%); font-size:.9rem"
                        ></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Résultat criticité ──────────────────────────────────────────── -->
        <div
            class="rounded-3 p-3 border"
            :style="resultContainerStyle"
            style="transition: background-color .2s, border-color .2s"
        >
            <div v-if="score" class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Score -->
                <div class="text-center px-2">
                    <div class="display-6 fw-bold lh-1" :style="{ color: zone?.color_code ?? '#495057' }">
                        {{ score }}
                    </div>
                    <div class="small text-muted mt-1">Score</div>
                </div>

                <div class="vr" style="height:48px"></div>

                <!-- Zone -->
                <div>
                    <span
                        v-if="zone"
                        class="badge px-3 py-2 fs-6"
                        :style="{ backgroundColor: zone.color_code, color: '#fff' }"
                    >{{ zone.label }}</span>
                    <span v-else class="badge bg-secondary px-3 py-2 fs-6">Hors zone</span>
                    <div class="small text-muted mt-1">Zone de criticité</div>
                </div>

                <!-- Détail calcul -->
                <div class="ms-auto text-muted small text-end d-none d-sm-block">
                    <span>Impact {{ selectedImpact?.score }}</span>
                    <span class="mx-1">×</span>
                    <span>Fréquence {{ selectedFrequency?.score }}</span>
                    <span class="mx-1">=</span>
                    <strong>{{ score }}</strong>
                </div>

                <!-- Réinitialiser -->
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary flex-shrink-0"
                    title="Réinitialiser"
                    @click="clearSelection"
                >
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <div v-else class="text-center py-2 text-muted small">
                <i class="ti ti-target d-block fs-2 mb-1 opacity-25"></i>
                Sélectionnez un niveau d'impact <em>et</em> une fréquence pour calculer la criticité.
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    modelImpact:     { type: [Number, String, null], default: null },
    modelFrequency:  { type: [Number, String, null], default: null },
    impactLevels:    { type: Array, default: () => [] },
    frequencyLevels: { type: Array, default: () => [] },
    zones:           { type: Array, default: () => [] },
})

// ── Emits ──────────────────────────────────────────────────────────────────
const emit = defineEmits(['update:modelImpact', 'update:modelFrequency'])

// ── Computed ───────────────────────────────────────────────────────────────
const selectedImpact = computed(() =>
    props.impactLevels.find(l => l.id === props.modelImpact) ?? null
)

const selectedFrequency = computed(() =>
    props.frequencyLevels.find(l => l.id === props.modelFrequency) ?? null
)

const score = computed(() => {
    if (!selectedImpact.value || !selectedFrequency.value) return null
    return selectedImpact.value.score * selectedFrequency.value.score
})

const zone = computed(() => {
    if (!score.value) return null
    return props.zones.find(z => score.value >= z.min_score && score.value <= z.max_score) ?? null
})

const resultContainerStyle = computed(() => {
    if (!zone.value?.color_code) {
        return { backgroundColor: '#f8f9fa', borderColor: '#dee2e6' }
    }
    return {
        backgroundColor: zone.value.color_code + '12',
        borderColor:     zone.value.color_code + '55',
    }
})

// ── Méthodes ───────────────────────────────────────────────────────────────
function levelStyle(color, selected) {
    if (selected) {
        return {
            backgroundColor: (color ?? '#0d6efd') + '1e',
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

function toggleImpact(id) {
    emit('update:modelImpact', props.modelImpact === id ? null : id)
}

function toggleFrequency(id) {
    emit('update:modelFrequency', props.modelFrequency === id ? null : id)
}

function clearSelection() {
    emit('update:modelImpact',    null)
    emit('update:modelFrequency', null)
}
</script>
