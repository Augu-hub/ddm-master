<template>
    <div>

        <!-- Select entité -->
        <div class="mb-3">
            <label class="form-label fw-semibold">
                Entité <span class="text-danger">*</span>
            </label>
            <select
                v-model="selectedEntityId"
                class="form-select"
                :class="{ 'is-invalid': error }"
                @change="onEntityChange"
            >
                <option value="">— Sélectionner une entité —</option>
                <option v-for="e in entities" :key="e.id" :value="e.id">
                    {{ e.name }}<template v-if="e.code_base"> ({{ e.code_base }})</template>
                </option>
            </select>
            <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
        </div>

        <!-- Arbre -->
        <div v-if="selectedEntityId">

            <div v-if="loading" class="text-center py-4 text-muted">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Chargement de l'arborescence…
            </div>

            <div v-else-if="tree.length === 0" class="text-center py-3 text-muted small">
                <i class="ti ti-mood-empty me-1"></i>
                Aucune activité assignée à cette entité
            </div>

            <template v-else>

                <!-- Breadcrumb sélection -->
                <div
                    v-if="selectedActivity"
                    class="d-flex align-items-center gap-2 flex-wrap mb-2 px-3 py-2 rounded-2"
                    style="background:var(--bs-primary-bg-subtle);border:1px solid var(--bs-primary-border-subtle)"
                >
                    <i class="ti ti-check-circle text-primary"></i>
                    <span class="small text-muted">{{ selectedActivity._macro_name }}</span>
                    <i class="ti ti-chevron-right text-muted" style="font-size:.75rem"></i>
                    <span class="small text-muted">{{ selectedActivity._process_name }}</span>
                    <i class="ti ti-chevron-right text-muted" style="font-size:.75rem"></i>
                    <span class="small fw-semibold text-primary">{{ selectedActivity.code }} — {{ selectedActivity.name }}</span>
                    <button type="button" class="btn-close ms-auto" style="font-size:.65rem" @click="clearSelection"></button>
                </div>

                <!-- Arbre collapsible -->
                <div class="border rounded-3 overflow-hidden" style="max-height:340px;overflow-y:auto">

                    <div v-for="macro in tree" :key="macro.id">

                        <!-- Nœud macro -->
                        <button
                            type="button"
                            class="d-flex align-items-center w-100 px-3 py-2 gap-2 border-0 border-bottom text-start"
                            :style="openMacros.has(macro.id)
                ? 'background:var(--bs-secondary-bg);font-weight:500'
                : 'background:var(--bs-body-bg)'"
                            @click="toggleMacro(macro.id)"
                        >
              <span class="badge rounded-pill me-1" :class="kindBadge(macro.kind)" style="font-size:.65rem;min-width:22px">
                {{ macro.code }}
              </span>
                            <span class="small flex-grow-1">{{ macro.name }}</span>
                            <i class="ti text-muted" :class="openMacros.has(macro.id) ? 'ti-chevron-down' : 'ti-chevron-right'" style="font-size:.8rem"></i>
                        </button>

                        <!-- Processus -->
                        <template v-if="openMacros.has(macro.id)">
                            <div v-for="proc in macro.processes" :key="proc.id">

                                <button
                                    type="button"
                                    class="d-flex align-items-center w-100 gap-2 border-0 border-bottom text-start py-2"
                                    style="padding-left:2rem"
                                    :style="openProcesses.has(proc.id) ? 'background:var(--bs-tertiary-bg);font-weight:500' : 'background:var(--bs-secondary-bg)'"
                                    @click="toggleProcess(proc.id)"
                                >
                                    <i class="ti ti-git-branch text-muted" style="font-size:.85rem"></i>
                                    <span class="small text-muted font-monospace me-1" style="font-size:.75rem">{{ proc.code }}</span>
                                    <span class="small flex-grow-1">{{ proc.name }}</span>
                                    <i class="ti text-muted" :class="openProcesses.has(proc.id) ? 'ti-chevron-down' : 'ti-chevron-right'" style="font-size:.75rem"></i>
                                </button>

                                <!-- Activités -->
                                <template v-if="openProcesses.has(proc.id)">
                                    <button
                                        v-for="act in proc.activities"
                                        :key="act.id"
                                        type="button"
                                        class="d-flex align-items-center w-100 gap-2 border-0 border-bottom text-start py-2"
                                        style="padding-left:3rem"
                                        :style="isSelected(act.id)
                      ? 'background:var(--bs-primary-bg-subtle)'
                      : 'background:var(--bs-body-bg)'"
                                        @click="selectActivity(act, macro, proc)"
                                    >
                                        <i
                                            class="ti"
                                            :class="isSelected(act.id) ? 'ti-check-circle text-primary' : 'ti-point text-muted'"
                                            style="font-size:.85rem"
                                        ></i>
                                        <span class="small font-monospace text-muted me-1" style="font-size:.72rem">{{ act.code }}</span>
                                        <span class="small flex-grow-1" :class="isSelected(act.id) ? 'fw-semibold text-primary' : ''">
                      {{ act.name }}
                    </span>
                                    </button>
                                </template>

                            </div>
                        </template>

                    </div>
                </div>

                <div v-if="!selectedActivity" class="form-text text-muted mt-1">
                    <i class="ti ti-info-circle me-1"></i>
                    Cliquez sur une activité pour la sélectionner
                </div>

            </template>
        </div>

    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    entities:        { type: Array,  default: () => [] },
    modelEntityId:   { type: [Number, String], default: '' },
    modelActivityId: { type: [Number, String], default: '' },
    error:           { type: String, default: null },
})

const emit = defineEmits(['update:modelEntityId', 'update:modelActivityId', 'selected'])

const selectedEntityId = ref(props.modelEntityId || '')
const tree             = ref([])
const loading          = ref(false)
const openMacros       = ref(new Set())
const openProcesses    = ref(new Set())
const selectedActivity = ref(null)

async function onEntityChange() {
    tree.value = []
    openMacros.value    = new Set()
    openProcesses.value = new Set()
    selectedActivity.value = null

    emit('update:modelEntityId', selectedEntityId.value)
    emit('update:modelActivityId', '')
    emit('selected', null)

    if (!selectedEntityId.value) return

    loading.value = true
    try {
        const { data } = await axios.get(
            route('risk.core.risks.entity-tree', { entityId: selectedEntityId.value })
        )
        tree.value = data

        // Auto-ouvrir si un seul macro
        if (data.length === 1) {
            openMacros.value = new Set([data[0].id])
            if (data[0].processes?.length === 1) {
                openProcesses.value = new Set([data[0].processes[0].id])
            }
        }
    } finally {
        loading.value = false
    }
}

function toggleMacro(id) {
    const s = new Set(openMacros.value)
    s.has(id) ? s.delete(id) : s.add(id)
    openMacros.value = s
}

function toggleProcess(id) {
    const s = new Set(openProcesses.value)
    s.has(id) ? s.delete(id) : s.add(id)
    openProcesses.value = s
}

function selectActivity(act, macro, proc) {
    selectedActivity.value = {
        id:            act.id,
        code:          act.code,
        name:          act.name,
        _macro_name:   `${macro.code} — ${macro.name}`,
        _process_name: `${proc.code} — ${proc.name}`,
    }
    emit('update:modelActivityId', act.id)
    emit('selected', { activity: act, process: proc, macro })
}

function clearSelection() {
    selectedActivity.value = null
    emit('update:modelActivityId', '')
    emit('selected', null)
}

function isSelected(actId) {
    return selectedActivity.value?.id === actId
}

function kindBadge(kind) {
    return {
        'Direction':   'bg-primary-subtle text-primary',
        'Réalisation': 'bg-success-subtle text-success',
        'Support':     'bg-warning-subtle text-warning',
    }[kind] ?? 'bg-secondary-subtle text-secondary'
}

// Init mode édition
watch(() => props.modelEntityId, async (val) => {
    if (val && val !== selectedEntityId.value) {
        selectedEntityId.value = val
        await onEntityChange()
    }
}, { immediate: true })

watch(() => props.modelActivityId, (actId) => {
    if (!actId || selectedActivity.value?.id === actId) return
    for (const macro of tree.value) {
        for (const proc of macro.processes) {
            const act = proc.activities.find(a => a.id === actId)
            if (act) {
                openMacros.value    = new Set([...openMacros.value, macro.id])
                openProcesses.value = new Set([...openProcesses.value, proc.id])
                selectActivity(act, macro, proc)
                return
            }
        }
    }
})
</script>
