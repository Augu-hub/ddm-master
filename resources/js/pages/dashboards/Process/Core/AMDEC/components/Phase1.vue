<template>
  <div class="phase1-container">
    <!-- 📊 STATISTIQUES -->
    <div class="stats-bar mb-3">
      <div class="stat-item">
        <span class="stat-label">Modes:</span>
        <b-badge bg="success">{{ totalModes }}</b-badge>
      </div>
      <div class="stat-item">
        <span class="stat-label">Activités:</span>
        <b-badge bg="primary">{{ uniqueActivities.length }}</b-badge>
      </div>
      <div class="stat-item">
        <span class="stat-label">Crit. moy:</span>
        <b-badge :bg="getCriticityColor(avgCriticity)">{{ formatCriticity(avgCriticity) }}</b-badge>
      </div>
      <div class="stat-item ms-auto">
        <b-button variant="success" size="sm" @click="openModalAdd">
          <i class="ti ti-plus me-1"></i>Ajouter
        </b-button>
      </div>
    </div>

    <!-- 📋 TABLE AMDEC COMPLÈTE (REGROUPÉE PAR ACTIVITÉ) -->
    <div class="table-wrapper">
      <table class="amdec-table">
        <thead>
          <tr>
            <th class="col-activity">Activité</th>
            <th class="col-num">N°</th>
            <th class="col-mode">Mode de défaillance</th>
            <th class="col-effects">Effets</th>
            <th class="col-gravity">G</th>
            <th class="col-causes">Causes</th>
            <th class="col-frequency">F</th>
            <th class="col-controls">Contrôles</th>
            <th class="col-detect">D</th>
            <th class="col-criticality">Criticité %</th>
            <th class="col-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- AFFICHER PAR ACTIVITÉ -->
          <template v-if="groupedByActivity.length === 0">
            <tr class="row-empty">
              <td colspan="11" class="text-center text-muted p-3">
                Aucun mode — Cliquez "Ajouter"
              </td>
            </tr>
          </template>

          <template v-for="(actGroup, actIdx) in groupedByActivity" :key="actGroup.activity.id">
            <!-- HEADER ACTIVITÉ -->
            <tr class="row-activity-header">
              <td colspan="11" class="cell-activity-header">
                <strong>{{ formatActivityName(actGroup.activity.name) }}</strong>
                <span class="badge-count">
                  <b-badge bg="secondary">{{ actGroup.modes.length }} mode(s)</b-badge>
                </span>
              </td>
            </tr>

            <!-- LIGNES MODES DE CETTE ACTIVITÉ -->
            <tr v-for="(mode, modeIdx) in actGroup.modes" :key="mode.id" class="row-mode">
              <!-- Activité (vide sauf 1ère ligne) -->
              <td v-if="modeIdx === 0" class="cell-activity" :rowspan="actGroup.modes.length">
                <small class="activity-code">{{ getActivityCode(actGroup.activity.name) }}</small>
              </td>

              <!-- N° Mode -->
              <td class="cell-num">
                <b-badge bg="dark">{{ modeIdx + 1 }}</b-badge>
              </td>

              <!-- Mode défaillance -->
              <td class="cell-mode">
                <strong>{{ mode.failure_mode }}</strong>
              </td>

              <!-- Effets -->
              <td class="cell-effects">
                <small>{{ truncate(mode.effects, 25) }}</small>
              </td>

              <!-- Gravité -->
              <td class="cell-gravity">
                <div class="score-display">
                  <span class="score-badge" :style="{ backgroundColor: getGravityColor(mode.gravity_before_id) }">
                    {{ getGravityDegree(mode.gravity_before_id) }}
                  </span>
                </div>
              </td>

              <!-- Causes -->
              <td class="cell-causes">
                <small>{{ truncate(mode.causes, 25) }}</small>
              </td>

              <!-- Fréquence -->
              <td class="cell-frequency">
                <div class="score-display">
                  <span class="score-badge" :style="{ backgroundColor: getFrequencyColor(mode.frequency_before_id) }">
                    {{ getFrequencyDegree(mode.frequency_before_id) }}
                  </span>
                </div>
              </td>

              <!-- Contrôles -->
              <td class="cell-controls">
                <small>{{ truncate(mode.current_controls, 25) }}</small>
              </td>

              <!-- Détectabilité -->
              <td class="cell-detect">
                <div class="score-display">
                  <span class="score-badge" :style="{ backgroundColor: getDetectabilityColor(mode.detectability_before_id) }">
                    {{ getDetectabilityDegree(mode.detectability_before_id) }}
                  </span>
                </div>
              </td>

              <!-- Criticité -->
              <td class="cell-criticality">
                <b-badge
                  v-if="mode.criticality_nette_before"
                  :bg="getCriticityColor(mode.criticality_nette_before)"
                  class="badge-criticality"
                >
                  {{ formatCriticity(mode.criticality_nette_before) }}
                </b-badge>
              </td>

              <!-- Actions -->
              <td class="cell-actions">
                <b-button size="sm" variant="warning" @click="openModalEdit(mode)" class="btn-action" title="Modifier">
                  <i class="ti ti-pencil"></i>
                </b-button>
                <b-button size="sm" variant="danger" @click="deleteRecord(mode)" class="btn-action" title="Supprimer">
                  <i class="ti ti-trash"></i>
                </b-button>
              </td>
            </tr>

            <!-- LIGNE TOTAL ACTIVITÉ -->
            <tr class="row-activity-total">
              <td colspan="9" class="cell-total-label">
                <strong>TOTAL {{ formatActivityName(actGroup.activity.name) }}</strong>
              </td>
              <td class="cell-total-value">
                <b-badge
                  :bg="getCriticityColor(calculateActivityCriticity(actGroup.modes))"
                  class="badge-total"
                >
                  {{ formatCriticity(calculateActivityCriticity(actGroup.modes)) }}
                </b-badge>
              </td>
              <td class="cell-total-empty"></td>
            </tr>

            <!-- SPACER BETWEEN ACTIVITIES -->
            <tr v-if="actIdx < groupedByActivity.length - 1" class="row-spacer">
              <td colspan="11"></td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════ -->
    <!-- 🔲 MODAL — AJOUTER/MODIFIER (LARGE + COMPACT) -->
    <!-- ═════════════════════════════════════════════════════════════════════ -->
    <b-modal
      id="modal-phase1-amdec"
      v-model="showModal"
      :title="modalMode === 'add' ? 'Ajouter mode défaillance' : 'Modifier mode défaillance'"
      size="lg"
      @ok="handleSave"
      @cancel="resetModal"
      ok-title="Enregistrer"
      cancel-title="Annuler"
    >
      <b-form>
        <!-- ROW 1: Activité + Mode défaillance -->
        <b-row class="mb-2">
          <b-col cols="6">
            <b-form-group label="Activité *" label-class="fw-bold" label-cols="12">
              <b-form-select 
                v-model.number="form.activity_id" 
                :options="activityOptions"
                :disabled="modalMode === 'edit'"
                size="sm"
                required
              />
            </b-form-group>
          </b-col>
          <b-col cols="6">
            <b-form-group label="Mode défaillance *" label-class="fw-bold" label-cols="12">
              <b-form-input 
                v-model="form.failure_mode" 
                placeholder="Ex: Infos fausses"
                size="sm"
                maxlength="255"
                required
              />
            </b-form-group>
          </b-col>
        </b-row>

        <!-- ROW 2: Effets (full width) -->
        <b-row class="mb-2">
          <b-col cols="12">
            <b-form-group label="Effets *" label-class="fw-bold" label-cols="12">
              <b-form-textarea 
                v-model="form.effects" 
                placeholder="Effets..."
                rows="1"
                size="sm"
                required
              />
            </b-form-group>
          </b-col>
        </b-row>

        <!-- ROW 3: Gravité + Causes -->
        <b-row class="mb-2">
          <b-col cols="6">
            <b-form-group label="Gravité *" label-class="fw-bold" label-cols="12">
              <b-form-select 
                v-model.number="form.gravity_before_id" 
                :options="gravityOptions"
                @change="recalculate"
                size="sm"
                required
              />
            </b-form-group>
          </b-col>
          <b-col cols="6">
            <b-form-group label="Causes *" label-class="fw-bold" label-cols="12">
              <b-form-textarea 
                v-model="form.causes" 
                placeholder="Causes..."
                rows="1"
                size="sm"
                required
              />
            </b-form-group>
          </b-col>
        </b-row>

        <!-- ROW 4: Fréquence + Contrôles -->
        <b-row class="mb-2">
          <b-col cols="6">
            <b-form-group label="Fréquence *" label-class="fw-bold" label-cols="12">
              <b-form-select 
                v-model.number="form.frequency_before_id" 
                :options="frequencyOptions"
                @change="recalculate"
                size="sm"
                required
              />
            </b-form-group>
          </b-col>
          <b-col cols="6">
            <b-form-group label="Contrôles *" label-class="fw-bold" label-cols="12">
              <b-form-textarea 
                v-model="form.current_controls" 
                placeholder="Contrôles actuels..."
                rows="1"
                size="sm"
                required
              />
            </b-form-group>
          </b-col>
        </b-row>

        <!-- ROW 5: Détectabilité + Criticité -->
        <b-row>
          <b-col cols="6">
            <b-form-group label="Détectabilité *" label-class="fw-bold" label-cols="12">
              <b-form-select 
                v-model.number="form.detectability_before_id" 
                :options="detectabilityOptions"
                @change="recalculate"
                size="sm"
                required
              />
            </b-form-group>
          </b-col>
          <b-col cols="6">
            <b-form-group label="Criticité %" label-class="fw-bold" label-cols="12">
              <b-input-group>
                <b-form-input
                  :value="formatCriticity(form.criticality_nette_before)"
                  readonly
                  size="sm"
                  class="bg-light"
                />
              </b-input-group>
            </b-form-group>
          </b-col>
        </b-row>
      </b-form>
    </b-modal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  activities: { type: Array, default: () => [] },
  records: { type: Array, default: () => [] },
  referentials: { type: Object, default: () => ({
    gravities: [],
    frequencies: [],
    detectabilities: []
  }) },
  sessionId: { type: Number, default: null },
  processId: { type: Number, default: null }
})

const emit = defineEmits(['save-record', 'delete-record'])

const showModal = ref(false)
const modalMode = ref('add')

const form = ref({
  activity_id: null,
  failure_mode: '',
  effects: '',
  gravity_before_id: null,
  causes: '',
  frequency_before_id: null,
  current_controls: '',
  detectability_before_id: null,
  criticality_before: null,
  criticality_nette_before: null,
  amdec_record_id: null
})

// ═══════════════════════════════════════════════════════════════════════════
// COMPUTED
// ═══════════════════════════════════════════════════════════════════════════

const recordsByPhase = computed(() => {
  if (!props.records) return []
  return props.records.filter(r => r.phase && r.phase !== null)
})

const totalModes = computed(() => recordsByPhase.value.length)

const uniqueActivities = computed(() => {
  const unique = new Map()
  recordsByPhase.value.forEach(r => {
    if (!unique.has(r.activity_id)) {
      const activity = props.activities.find(a => a.id === r.activity_id)
      if (activity) unique.set(r.activity_id, activity)
    }
  })
  return Array.from(unique.values())
})

const groupedByActivity = computed(() => {
  const groups = []
  uniqueActivities.value.forEach(activity => {
    const modes = recordsByPhase.value.filter(r => r.activity_id === activity.id)
    groups.push({ activity, modes })
  })
  return groups
})

const avgCriticity = computed(() => {
  if (recordsByPhase.value.length === 0) return 0
  const sum = recordsByPhase.value.reduce((acc, r) => acc + (r.criticality_nette_before || 0), 0)
  return sum / recordsByPhase.value.length
})

const activityOptions = computed(() => [
  { value: null, text: '— Sélectionnez —' },
  ...props.activities.map(a => ({
    value: a.id,
    text: formatActivityName(a.name)
  }))
])

const gravityOptions = computed(() => [
  { value: null, text: '— Sélectionnez —' },
  ...props.referentials.gravities.map(g => ({
    value: g.id,
    text: `${g.degree} - ${g.label}`
  }))
])

const frequencyOptions = computed(() => [
  { value: null, text: '— Sélectionnez —' },
  ...props.referentials.frequencies.map(f => ({
    value: f.id,
    text: `${f.degree} - ${f.label}`
  }))
])

const detectabilityOptions = computed(() => [
  { value: null, text: '— Sélectionnez —' },
  ...props.referentials.detectabilities.map(d => ({
    value: d.id,
    text: `${d.degree} - ${d.label}`
  }))
])

// ═══════════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════════

const formatActivityName = (fullName) => {
  // "A01P01D — A1A02P01D — A2" → "A1A02P01D — A2"
  // Enlever "AC" et garder le reste
  if (!fullName) return '—'
  const cleaned = fullName.replace(/^AC\s*/, '').trim()
  return cleaned || fullName
}

const getActivityCode = (fullName) => {
  // Extraire juste "A1" ou "A2"
  const match = fullName?.match(/A\d+/)
  return match ? match[0] : '—'
}

const getActivityName = (id) => {
  const act = props.activities?.find(a => a.id === id)
  return act?.name || '—'
}

const getGravityLabel = (id) => {
  const g = props.referentials.gravities?.find(g => g.id === id)
  return g?.label || '—'
}

const getGravityDegree = (id) => {
  const g = props.referentials.gravities?.find(g => g.id === id)
  return g?.degree || '—'
}

const getGravityColor = (id) => {
  const g = props.referentials.gravities?.find(g => g.id === id)
  return g?.color || '#ccc'
}

const getFrequencyLabel = (id) => {
  const f = props.referentials.frequencies?.find(f => f.id === id)
  return f?.label || '—'
}

const getFrequencyDegree = (id) => {
  const f = props.referentials.frequencies?.find(f => f.id === id)
  return f?.degree || '—'
}

const getFrequencyColor = (id) => {
  const f = props.referentials.frequencies?.find(f => f.id === id)
  return f?.color || '#ccc'
}

const getDetectabilityLabel = (id) => {
  const d = props.referentials.detectabilities?.find(d => d.id === id)
  return d?.label || '—'
}

const getDetectabilityDegree = (id) => {
  const d = props.referentials.detectabilities?.find(d => d.id === id)
  return d?.degree || '—'
}

const getDetectabilityColor = (id) => {
  const d = props.referentials.detectabilities?.find(d => d.id === id)
  return d?.color || '#ccc'
}

const formatCriticity = (value) => {
  if (!value && value !== 0) return '—'
  return typeof value === 'number' ? value.toFixed(0) + '%' : '—'
}

const getCriticityColor = (value) => {
  if (!value && value !== 0) return 'secondary'
  if (value <= 20) return 'success'
  if (value <= 40) return 'info'
  if (value <= 60) return 'warning'
  if (value <= 80) return 'danger'
  return 'dark'
}

const truncate = (text, length) => {
  if (!text) return '—'
  return text.length > length ? text.substring(0, length) + '…' : text
}

const calculateActivityCriticity = (modes) => {
  if (modes.length === 0) return 0
  const sum = modes.reduce((acc, m) => acc + (m.criticality_nette_before || 0), 0)
  return sum / modes.length
}

const recalculate = () => {
  if (!form.value.gravity_before_id || !form.value.frequency_before_id || !form.value.detectability_before_id) {
    form.value.criticality_before = null
    form.value.criticality_nette_before = null
    return
  }

  const gravity = props.referentials.gravities?.find(g => g.id === form.value.gravity_before_id)
  const frequency = props.referentials.frequencies?.find(f => f.id === form.value.frequency_before_id)
  const detectability = props.referentials.detectabilities?.find(d => d.id === form.value.detectability_before_id)

  if (gravity && frequency && detectability) {
    form.value.criticality_before = gravity.degree * frequency.degree * detectability.degree
    form.value.criticality_nette_before = (form.value.criticality_before / 125) * 100
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// METHODS
// ═══════════════════════════════════════════════════════════════════════════

const openModalAdd = () => {
  if (props.activities.length === 0) {
    alert('⚠️ Aucune activité disponible')
    return
  }
  modalMode.value = 'add'
  resetModal()
  showModal.value = true
}

const openModalEdit = (mode) => {
  modalMode.value = 'edit'
  form.value = {
    activity_id: mode.activity_id,
    failure_mode: mode.failure_mode,
    effects: mode.effects || '',
    gravity_before_id: mode.gravity_before_id,
    causes: mode.causes || '',
    frequency_before_id: mode.frequency_before_id,
    current_controls: mode.current_controls || '',
    detectability_before_id: mode.detectability_before_id,
    criticality_before: mode.criticality_before,
    criticality_nette_before: mode.criticality_nette_before,
    amdec_record_id: mode.id
  }
  showModal.value = true
}

const resetModal = () => {
  form.value = {
    activity_id: null,
    failure_mode: '',
    effects: '',
    gravity_before_id: null,
    causes: '',
    frequency_before_id: null,
    current_controls: '',
    detectability_before_id: null,
    criticality_before: null,
    criticality_nette_before: null,
    amdec_record_id: null
  }
}

const handleSave = () => {
  // Validation
  if (!form.value.activity_id) { alert('⚠️ Activité requise'); return }
  if (!form.value.failure_mode?.trim()) { alert('⚠️ Mode défaillance requis'); return }
  if (!form.value.effects?.trim()) { alert('⚠️ Effets requis'); return }
  if (!form.value.gravity_before_id) { alert('⚠️ Gravité requise'); return }
  if (!form.value.causes?.trim()) { alert('⚠️ Causes requises'); return }
  if (!form.value.frequency_before_id) { alert('⚠️ Fréquence requise'); return }
  if (!form.value.current_controls?.trim()) { alert('⚠️ Contrôles requis'); return }
  if (!form.value.detectability_before_id) { alert('⚠️ Détectabilité requise'); return }

  emit('save-record', {
    phase: 'PHASE1',
    session_id: props.sessionId,
    process_id: props.processId,
    activity_id: form.value.activity_id,
    failure_mode: form.value.failure_mode,
    effects: form.value.effects,
    gravity_before_id: form.value.gravity_before_id,
    causes: form.value.causes,
    frequency_before_id: form.value.frequency_before_id,
    current_controls: form.value.current_controls,
    detectability_before_id: form.value.detectability_before_id,
    amdec_record_id: form.value.amdec_record_id || undefined
  })

  showModal.value = false
  resetModal()
}

const deleteRecord = (mode) => {
  if (confirm('⚠️ Êtes-vous sûr de supprimer ce mode ?')) {
    emit('delete-record', mode.id)
  }
}
</script>

<style scoped>
.phase1-container {
  padding: 1rem;
  background: #fff;
}

.stats-bar {
  display: flex;
  gap: 1rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-left: 3px solid #007bff;
  border-radius: 0.3rem;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.stat-label {
  font-weight: 600;
  color: #495057;
  font-size: 0.85rem;
}

.ms-auto {
  margin-left: auto;
}

.mb-3 {
  margin-bottom: 1rem;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

/* TABLE AMDEC */
.table-wrapper {
  border: 1px solid #dee2e6;
  border-radius: 0.3rem;
  overflow-x: auto;
}

.amdec-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
  background: white;
}

.amdec-table thead {
  background: #007bff;
  color: white;
  font-weight: 600;
  position: sticky;
  top: 0;
  z-index: 10;
}

.amdec-table th {
  padding: 0.5rem;
  text-align: center;
  border: 1px solid #dee2e6;
  white-space: nowrap;
  font-size: 0.75rem;
}

.amdec-table td {
  padding: 0.5rem;
  border: 1px solid #dee2e6;
}

.amdec-table tbody tr {
  border-bottom: 1px solid #dee2e6;
}

.amdec-table tbody tr:nth-child(odd) {
  background: #fafbfc;
}

.amdec-table tbody tr:hover {
  background: #e7f3ff;
}

/* ACTIVITY HEADER ROW */
.row-activity-header {
  background: #007bff;
  color: white;
  font-weight: 600;
  height: 35px;
}

.row-activity-header td {
  padding: 0.5rem 1rem;
  border: 1px solid #0056b3;
  vertical-align: middle;
}

.cell-activity-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.badge-count {
  display: flex;
  gap: 0.5rem;
}

/* ACTIVITY TOTAL ROW */
.row-activity-total {
  background: #e7f3ff;
  font-weight: 600;
  border-top: 2px solid #007bff;
  height: 32px;
}

.row-activity-total td {
  padding: 0.4rem 0.5rem;
  border: 1px solid #cfe2ff;
  vertical-align: middle;
}

.cell-total-label {
  text-align: right;
  padding-right: 1rem;
  color: #0056b3;
}

.cell-total-value {
  text-align: center;
  font-weight: 700;
}

.cell-total-empty {
  background: transparent;
  border: none;
}

.badge-total {
  font-size: 0.8rem;
  padding: 0.4rem 0.6rem;
  font-weight: 700;
  display: inline-block;
}

/* SPACER BETWEEN ACTIVITIES */
.row-spacer {
  height: 10px;
  background: white;
}

.row-spacer td {
  padding: 0;
  border: none;
}

/* MODE ROWS */
.row-mode {
  height: auto;
}

.row-mode:hover {
  background: #e7f3ff;
}

/* COLONNES */
.col-activity { min-width: 80px; text-align: center; }
.col-num { width: 35px; text-align: center; }
.col-mode { min-width: 100px; text-align: left; }
.col-effects { min-width: 90px; text-align: left; }
.col-gravity { width: 40px; text-align: center; }
.col-causes { min-width: 90px; text-align: left; }
.col-frequency { width: 40px; text-align: center; }
.col-controls { min-width: 90px; text-align: left; }
.col-detect { width: 40px; text-align: center; }
.col-criticality { width: 70px; text-align: center; }
.col-actions { width: 70px; text-align: center; }

/* CELLS */
.cell-activity {
  background: #f0f7ff;
  font-weight: 500;
  text-align: center;
}

.activity-code {
  font-weight: 700;
  color: #0056b3;
  font-size: 0.85rem;
}

.cell-mode,
.cell-effects,
.cell-causes,
.cell-controls {
  font-size: 0.75rem;
  text-align: left;
}

.cell-gravity,
.cell-frequency,
.cell-detect,
.cell-criticality,
.cell-actions {
  text-align: center;
}

/* SCORE DISPLAY */
.score-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
}

.score-badge {
  display: inline-block;
  color: white;
  font-weight: 700;
  padding: 0.2rem 0.4rem;
  border-radius: 0.2rem;
  min-width: 24px;
  text-align: center;
  font-size: 0.75rem;
}

.badge-criticality {
  font-size: 0.75rem;
  padding: 0.3rem 0.5rem;
  font-weight: 600;
  min-width: 50px;
  display: inline-block;
}

.text-muted {
  color: #6c757d;
}

.text-center {
  text-align: center;
}

.p-3 {
  padding: 1rem;
}

/* BUTTONS */
.btn-action {
  padding: 0.25rem 0.4rem;
  font-size: 0.7rem;
  margin: 0.1rem;
}

/* MODAL */
.fw-bold {
  font-weight: 600;
}

.bg-light {
  background-color: #f8f9fa !important;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .amdec-table { font-size: 0.75rem; }
  .amdec-table th { font-size: 0.7rem; }
}

@media (max-width: 768px) {
  .stats-bar {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .amdec-table { font-size: 0.7rem; }
  .amdec-table th { font-size: 0.65rem; padding: 0.3rem; }
  .amdec-table td { padding: 0.3rem; }
  
  .cell-activity-header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>