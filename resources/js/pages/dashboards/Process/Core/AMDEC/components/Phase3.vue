<template>
  <div class="phase3-container">
    <!-- STAT BAR -->
    <div class="stats-bar mb-3">
      <span><strong>{{ allPhase1Records.length }}</strong> Phase 1</span>
      <span><strong>{{ allPhase2Records.length }}</strong> Phase 2</span>
      <span class="ms-auto"><strong>{{ recordsPhase3.length }}</strong> Évaluations</span>
      <b-button 
        variant="success" 
        size="sm" 
        @click="openModalAddEval"
        :disabled="allPhase2Records.length === 0"
        class="ms-2"
      >
        <i class="ti ti-plus"></i> Évaluer
      </b-button>
    </div>

    <!-- TABLE -->
    <div class="table-wrapper">
      <table class="table-simple">
        <thead>
          <tr>
            <th>Mode</th>
            <th>Activité</th>
            <th>Crit. Avant</th>
            <th>Crit. Après</th>
            <th>Amélioration</th>
            <th style="width: 80px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="recordsPhase3.length === 0">
            <td colspan="6" class="text-center text-muted">Aucune évaluation</td>
          </tr>

          <tr v-for="record in recordsPhase3" :key="record.id">
            <td><strong>{{ record.failure_mode }}</strong></td>
            <td><small>{{ getActivityName(record.activity_id) }}</small></td>
            <td>
              <b-badge :bg="getCriticityColor(record.criticality_nette_before)">
                {{ formatCriticity(record.criticality_nette_before) }}
              </b-badge>
            </td>
            <td>
              <b-badge :bg="getCriticityColor(record.criticality_nette_after)">
                {{ formatCriticity(record.criticality_nette_after) }}
              </b-badge>
            </td>
            <td>
              <span :class="getImprovementClass(record.improvement_percentage)">
                {{ formatImprovement(record.improvement_percentage) }}
              </span>
            </td>
            <td class="text-center">
              <b-button 
                size="sm" 
                variant="warning" 
                @click="openModalEditEval(record)"
                class="btn-action"
                title="Modifier"
              >
                <i class="ti ti-pencil"></i>
              </b-button>
              <b-button 
                size="sm" 
                variant="danger" 
                @click="deleteRecord(record)"
                class="btn-action"
                title="Supprimer"
              >
                <i class="ti ti-trash"></i>
              </b-button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 🔲 MODAL — AJOUTER/MODIFIER ÉVALUATION POST-CORRECTION -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <b-modal
      v-model="showModal"
      :title="modalMode === 'add' ? 'Évaluation post-correction' : 'Modifier Évaluation'"
      size="lg"
      @ok="saveEval"
      @cancel="resetForm"
      ok-title="Enregistrer"
      cancel-title="Annuler"
      scrollable
    >
      <b-form>
        <!-- ROW 1: Mode sélection -->
        <b-row class="mb-2">
          <b-col cols="12">
            <b-form-group label="Mode défaillance *" label-class="fw-bold" label-cols="12">
              <b-form-select
                v-model.number="form.amdec_record_id"
                :options="modeOptions"
                :disabled="modalMode === 'edit'"
                size="sm"
                required
                @change="onModeSelected"
              />
              <small v-if="selectedMode" class="text-info d-block mt-2">
                <strong>Criticité AVANT:</strong> {{ formatCriticity(selectedMode.criticality_nette_before) }}<br>
                <strong>Mesures:</strong> {{ selectedMode.prevention_measures }}
              </small>
            </b-form-group>
          </b-col>
        </b-row>

        <!-- ROW 2: Données AVANT (lecture seule) -->
        <b-row class="mb-3">
          <b-col cols="4">
            <b-form-group label="Gravité Avant" label-class="fw-bold" label-cols="12">
              <b-form-input
                :value="getGravityLabel(selectedMode?.gravity_before_id)"
                readonly
                size="sm"
                class="bg-light"
              />
            </b-form-group>
          </b-col>
          <b-col cols="4">
            <b-form-group label="Fréquence Avant" label-class="fw-bold" label-cols="12">
              <b-form-input
                :value="getFrequencyLabel(selectedMode?.frequency_before_id)"
                readonly
                size="sm"
                class="bg-light"
              />
            </b-form-group>
          </b-col>
          <b-col cols="4">
            <b-form-group label="Détectabilité Avant" label-class="fw-bold" label-cols="12">
              <b-form-input
                :value="getDetectabilityLabel(selectedMode?.detectability_before_id)"
                readonly
                size="sm"
                class="bg-light"
              />
            </b-form-group>
          </b-col>
        </b-row>

        <!-- 🤖 SUGGESTIONS IA (générées quand on sélectionne un mode) -->
        <b-row v-if="aiSuggestions.efficacy_criterion || aiSuggestions.efficacy_measure" class="mb-3">
          <b-col cols="12">
            <div class="ai-suggestions-auto">
              <div class="ai-header">
                ✨ <strong>Critères d'efficacité suggérés</strong>
                <small v-if="isAiLoading" class="text-info">
                  <i class="ti ti-loading"></i> Génération en cours...
                </small>
              </div>

              <!-- Suggestion Critère d'efficacité -->
              <div v-if="aiSuggestions.efficacy_criterion" class="ai-suggestion">
                <div class="ai-suggestion-title">📏 Critère d'efficacité</div>
                <div class="ai-suggestion-text">{{ aiSuggestions.efficacy_criterion }}</div>
                <div class="ai-suggestion-actions">
                  <b-button 
                    size="sm" 
                    variant="success"
                    @click="form.efficacy_criterion = aiSuggestions.efficacy_criterion"
                  >
                    ✅ Utiliser
                  </b-button>
                </div>
              </div>

              <!-- Suggestion Mesure d'efficacité -->
              <div v-if="aiSuggestions.efficacy_measure" class="ai-suggestion">
                <div class="ai-suggestion-title">📊 Mesure d'efficacité</div>
                <div class="ai-suggestion-text">{{ aiSuggestions.efficacy_measure }}</div>
                <div class="ai-suggestion-actions">
                  <b-button 
                    size="sm" 
                    variant="success"
                    @click="form.efficacy_measure = aiSuggestions.efficacy_measure"
                  >
                    ✅ Utiliser
                  </b-button>
                </div>
              </div>

              <small v-if="aiError" class="text-danger d-block mt-2">
                ⚠️ {{ aiError }}
              </small>
            </div>
          </b-col>
        </b-row>

        <!-- ROW 3: Évaluation APRÈS (Saisie) -->
        <b-row class="mb-2">
          <b-col cols="4">
            <b-form-group label="Gravité Après *" label-class="fw-bold" label-cols="12">
              <b-form-select
                v-model.number="form.gravity_after_id"
                :options="gravityOptions"
                size="sm"
                required
                @change="recalculate"
              />
            </b-form-group>
          </b-col>
          <b-col cols="4">
            <b-form-group label="Fréquence Après *" label-class="fw-bold" label-cols="12">
              <b-form-select
                v-model.number="form.frequency_after_id"
                :options="frequencyOptions"
                size="sm"
                required
                @change="recalculate"
              />
            </b-form-group>
          </b-col>
          <b-col cols="4">
            <b-form-group label="Détectabilité Après *" label-class="fw-bold" label-cols="12">
              <b-form-select
                v-model.number="form.detectability_after_id"
                :options="detectabilityOptions"
                size="sm"
                required
                @change="recalculate"
              />
            </b-form-group>
          </b-col>
        </b-row>

        <!-- ROW 4: Résultats (lecture seule) -->
        <b-row class="mb-3">
          <b-col cols="4">
            <b-form-group label="Criticité Après %" label-class="fw-bold" label-cols="12">
              <b-form-input
                :value="formatCriticity(form.criticality_nette_after)"
                readonly
                size="sm"
                class="bg-light fw-bold"
              />
            </b-form-group>
          </b-col>
          <b-col cols="4">
            <b-form-group label="Amélioration %" label-class="fw-bold" label-cols="12">
              <b-form-input
                :value="formatImprovement(form.improvement_percentage)"
                readonly
                size="sm"
                class="bg-light fw-bold"
                :class="form.improvement_percentage > 0 ? 'text-success' : 'text-danger'"
              />
            </b-form-group>
          </b-col>
          <b-col cols="4">
            <b-form-group label="Statut" label-class="fw-bold" label-cols="12">
              <b-badge 
                :bg="form.improvement_percentage > 0 ? 'success' : 'warning'"
                class="d-block text-center"
              >
                {{ form.improvement_percentage > 0 ? '✅ Amélioration' : '⚠️ À vérifier' }}
              </b-badge>
            </b-form-group>
          </b-col>
        </b-row>

        <!-- ROW 5: Critères (optionnels) -->
        <b-row>
          <b-col cols="6">
            <b-form-group label="Critère d'efficacité" label-class="fw-bold" label-cols="12">
              <b-form-textarea
                v-model="form.efficacy_criterion"
                placeholder="Comment mesurer si l'action est efficace?"
                rows="2"
                size="sm"
              />
            </b-form-group>
          </b-col>
          <b-col cols="6">
            <b-form-group label="Mesure d'efficacité" label-class="fw-bold" label-cols="12">
              <b-form-textarea
                v-model="form.efficacy_measure"
                placeholder="Indicateurs et méthodes de vérification"
                rows="2"
                size="sm"
              />
            </b-form-group>
          </b-col>
        </b-row>
      </b-form>
    </b-modal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  activities: { type: Array, default: () => [] },
  records: { type: Array, default: () => [] },
  referentials: { type: Object, default: () => ({
    gravities: [],
    frequencies: [],
    detectabilities: []
  }) }
})

const emit = defineEmits(['save-record', 'delete-record'])

// STATE
const showModal = ref(false)
const modalMode = ref('add')
const isAiLoading = ref(false)
const aiError = ref('')
const aiTimeout = ref(null)

const form = ref({
  amdec_record_id: null,
  gravity_after_id: null,
  frequency_after_id: null,
  detectability_after_id: null,
  criticality_after: null,
  criticality_nette_after: null,
  improvement_percentage: null,
  efficacy_criterion: '',
  efficacy_measure: ''
})

const aiSuggestions = ref({
  efficacy_criterion: null,
  efficacy_measure: null
})

// ═══════════════════════════════════════════════════════════════════════════
// COMPUTED
// ═══════════════════════════════════════════════════════════════════════════

const allPhase1Records = computed(() => {
  if (!props.records) return []
  return props.records.filter(r => r.phase === 'PHASE1')
})

const allPhase2Records = computed(() => {
  if (!props.records) return []
  return props.records.filter(r => r.phase === 'PHASE2')
})

const recordsPhase3 = computed(() => {
  if (!props.records) return []
  return props.records.filter(r => r.phase === 'PHASE3')
})

const selectedMode = computed(() => {
  if (!form.value.amdec_record_id) return null
  return allPhase1Records.value.find(r => r.id === form.value.amdec_record_id)
})

const modeOptions = computed(() => {
  return [
    { value: null, text: '-- Sélectionnez --' },
    ...allPhase1Records.value.map(m => ({
      value: m.id,
      text: `${m.failure_mode}`
    }))
  ]
})

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

const getActivityName = (id) => {
  if (!id || !props.activities) return '—'
  const act = props.activities.find(a => a.id === id)
  return act ? act.name : '—'
}

const getGravityLabel = (id) => {
  if (!id) return '—'
  const g = props.referentials.gravities?.find(g => g.id === id)
  return g ? `${g.degree} - ${g.label}` : '—'
}

const getFrequencyLabel = (id) => {
  if (!id) return '—'
  const f = props.referentials.frequencies?.find(f => f.id === id)
  return f ? `${f.degree} - ${f.label}` : '—'
}

const getDetectabilityLabel = (id) => {
  if (!id) return '—'
  const d = props.referentials.detectabilities?.find(d => d.id === id)
  return d ? `${d.degree} - ${d.label}` : '—'
}

const formatCriticity = (value) => {
  if (!value && value !== 0) return '—'
  return typeof value === 'number' ? value.toFixed(0) + '%' : '—'
}

const formatImprovement = (value) => {
  if (!value && value !== 0) return '—'
  return typeof value === 'number' ? value.toFixed(1) + '%' : '—'
}

const getCriticityColor = (value) => {
  if (!value && value !== 0) return 'secondary'
  if (value <= 20) return 'success'
  if (value <= 40) return 'info'
  if (value <= 60) return 'warning'
  if (value <= 80) return 'danger'
  return 'dark'
}

const getImprovementClass = (value) => {
  if (!value && value !== 0) return 'text-muted'
  return value > 0 ? 'text-success fw-bold' : 'text-warning'
}

const recalculate = () => {
  if (!form.value.gravity_after_id || !form.value.frequency_after_id || !form.value.detectability_after_id) {
    form.value.criticality_after = null
    form.value.criticality_nette_after = null
    form.value.improvement_percentage = null
    return
  }

  const gravity = props.referentials.gravities?.find(g => g.id === form.value.gravity_after_id)
  const frequency = props.referentials.frequencies?.find(f => f.id === form.value.frequency_after_id)
  const detectability = props.referentials.detectabilities?.find(d => d.id === form.value.detectability_after_id)

  if (gravity && frequency && detectability) {
    form.value.criticality_after = gravity.degree * frequency.degree * detectability.degree
    form.value.criticality_nette_after = (form.value.criticality_after / 125) * 100

    // Calculer l'amélioration
    if (selectedMode.value?.criticality_nette_before && selectedMode.value.criticality_nette_before > 0) {
      form.value.improvement_percentage = (
        (selectedMode.value.criticality_nette_before - form.value.criticality_nette_after) /
        selectedMode.value.criticality_nette_before
      ) * 100
    }
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// 🤖 MODE SÉLECTIONNÉ — Générer les suggestions IA
// ═══════════════════════════════════════════════════════════════════════════

const onModeSelected = () => {
  if (!selectedMode.value) {
    aiSuggestions.value = { efficacy_criterion: null, efficacy_measure: null }
    aiError.value = ''
    return
  }

  if (aiTimeout.value) clearTimeout(aiTimeout.value)
  
  aiTimeout.value = setTimeout(() => {
    generatePhase3Suggestions()
  }, 500)
}

/**
 * 🤖 GÉNÉRER LES SUGGESTIONS PHASE 3 PAR IA
 */
const generatePhase3Suggestions = async () => {
  if (!selectedMode.value) return

  isAiLoading.value = true
  aiError.value = ''
  aiSuggestions.value = { efficacy_criterion: null, efficacy_measure: null }

  try {
    console.log('🔍 Generating Phase3 suggestions for:', selectedMode.value.failure_mode)

    const res = await axios.post(route('process.core.amdec.ai.suggest'), {
      phase: 'PHASE3',
      payload: {
        failure_mode: selectedMode.value.failure_mode,
        activity_name: getActivityName(selectedMode.value.activity_id),
        prevention_measures: selectedMode.value.prevention_measures
      }
    })

    console.log('📥 Phase3 response:', res.data)

    if (res.data.success && res.data.suggestions) {
      aiSuggestions.value = {
        efficacy_criterion: res.data.suggestions.efficacy_criterion || null,
        efficacy_measure: res.data.suggestions.efficacy_measure || null
      }
      console.log('✅ Phase3 suggestions generated:', aiSuggestions.value)
    } else {
      aiError.value = res.data.error || 'Erreur génération'
    }

  } catch (err) {
    console.error('❌ Erreur Phase3:', err)
    aiError.value = 'Erreur connexion IA'
  } finally {
    isAiLoading.value = false
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// MODAL MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

const openModalAddEval = () => {
  if (allPhase1Records.value.length === 0) {
    alert('⚠️ Créez d\'abord des modes de défaillance (PHASE 1)')
    return
  }
  modalMode.value = 'add'
  resetForm()
  showModal.value = true
}

const openModalEditEval = (record) => {
  modalMode.value = 'edit'
  form.value = {
    amdec_record_id: record.id,
    gravity_after_id: record.gravity_after_id,
    frequency_after_id: record.frequency_after_id,
    detectability_after_id: record.detectability_after_id,
    criticality_after: record.criticality_after,
    criticality_nette_after: record.criticality_nette_after,
    improvement_percentage: record.improvement_percentage,
    efficacy_criterion: record.efficacy_criterion || '',
    efficacy_measure: record.efficacy_measure || ''
  }
  aiSuggestions.value = { efficacy_criterion: null, efficacy_measure: null }
  aiError.value = ''
  showModal.value = true
}

const resetForm = () => {
  if (aiTimeout.value) clearTimeout(aiTimeout.value)
  
  form.value = {
    amdec_record_id: null,
    gravity_after_id: null,
    frequency_after_id: null,
    detectability_after_id: null,
    criticality_after: null,
    criticality_nette_after: null,
    improvement_percentage: null,
    efficacy_criterion: '',
    efficacy_measure: ''
  }
  aiSuggestions.value = { efficacy_criterion: null, efficacy_measure: null }
  aiError.value = ''
  isAiLoading.value = false
}

const saveEval = () => {
  if (!form.value.amdec_record_id) {
    alert('⚠️ Sélectionnez un mode')
    return
  }
  if (!form.value.gravity_after_id) {
    alert('⚠️ Sélectionnez la gravité après')
    return
  }
  if (!form.value.frequency_after_id) {
    alert('⚠️ Sélectionnez la fréquence après')
    return
  }
  if (!form.value.detectability_after_id) {
    alert('⚠️ Sélectionnez la détectabilité après')
    return
  }

  emit('save-record', {
    phase: 'PHASE3',
    amdec_record_id: form.value.amdec_record_id,
    gravity_after_id: form.value.gravity_after_id,
    frequency_after_id: form.value.frequency_after_id,
    detectability_after_id: form.value.detectability_after_id,
    efficacy_criterion: form.value.efficacy_criterion,
    efficacy_measure: form.value.efficacy_measure
  })

  showModal.value = false
  resetForm()
}

const deleteRecord = (record) => {
  if (confirm('⚠️ Êtes-vous sûr de supprimer cette évaluation ?')) {
    emit('delete-record', record.id)
  }
}
</script>

<style scoped>
.phase3-container {
  padding: 1rem;
  background: #fff;
}

.stats-bar {
  display: flex;
  gap: 1rem;
  align-items: center;
  padding: 0.75rem;
  background: #f8f9fa;
  border-left: 3px solid #198754;
  border-radius: 0.3rem;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.ms-auto {
  margin-left: auto;
}

.ms-2 {
  margin-left: 0.5rem;
}

.mb-3 { margin-bottom: 1rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mt-2 { margin-top: 0.5rem; }
.d-block { display: block; }

.text-center { text-align: center; }
.text-muted { color: #6c757d; font-size: 0.85rem; }
.text-info { color: #0dcaf0; font-size: 0.85rem; }
.text-danger { color: #dc3545; font-size: 0.85rem; }
.text-success { color: #198754; }
.text-warning { color: #ffc107; }
.fw-bold { font-weight: 600; }

/* TABLE */
.table-wrapper {
  border: 1px solid #dee2e6;
  border-radius: 0.3rem;
  overflow: auto;
}

.table-simple {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  background: white;
}

.table-simple thead {
  background: #198754;
  color: white;
  font-weight: 600;
  position: sticky;
  top: 0;
  z-index: 10;
}

.table-simple th {
  padding: 0.75rem;
  border: 1px solid #dee2e6;
  text-align: left;
  white-space: nowrap;
  font-size: 0.8rem;
}

.table-simple td {
  padding: 0.75rem;
  border: 1px solid #dee2e6;
  text-align: left;
}

.table-simple tbody tr:nth-child(odd) {
  background: #fafbfc;
}

.table-simple tbody tr:hover {
  background: #e8f5e9;
}

/* 🤖 IA SUGGESTIONS */
.ai-suggestions-auto {
  background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
  border: 2px solid #4caf50;
  border-radius: 0.4rem;
  padding: 1rem;
  margin-bottom: 1rem;
}

.ai-header {
  font-weight: 600;
  color: #2e7d32;
  margin-bottom: 1rem;
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.ai-suggestion {
  background: white;
  border: 1px solid #c8e6c9;
  border-left: 4px solid #4caf50;
  border-radius: 0.3rem;
  padding: 0.75rem;
  margin-bottom: 0.75rem;
}

.ai-suggestion-title {
  font-weight: 600;
  color: #2e7d32;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.ai-suggestion-text {
  background: #fafbfc;
  padding: 0.75rem;
  border-radius: 0.2rem;
  font-size: 0.85rem;
  color: #495057;
  margin-bottom: 0.75rem;
  line-height: 1.5;
  max-height: 120px;
  overflow-y: auto;
}

.ai-suggestion-actions {
  display: flex;
  gap: 0.5rem;
}

.ai-suggestion-actions button {
  font-size: 0.75rem;
}

.bg-light {
  background-color: #f8f9fa !important;
}

.btn-action {
  padding: 0.25rem 0.5rem;
  font-size: 0.7rem;
  margin-right: 0.25rem;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .stats-bar {
    flex-direction: column;
    align-items: flex-start;
  }

  .ms-auto {
    margin-left: 0;
    margin-top: 0.5rem;
  }

  .table-simple {
    font-size: 0.75rem;
  }

  .table-simple th,
  .table-simple td {
    padding: 0.5rem;
  }
}
</style>