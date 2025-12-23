<template>
  <div class="phase3-container">
    <!-- 📊 STATISTIQUES PHASE 3 -->
    <div class="stats-bar mb-3">
      <div class="stat-item">
        <span class="stat-label">Plans approuvés:</span>
        <b-badge bg="info">{{ records.length }}</b-badge>
      </div>
      <div class="stat-item">
        <span class="stat-label">Crit. AVANT:</span>
        <b-badge :bg="getCriticityColor(avgBefore)">{{ formatCriticity(avgBefore) }}</b-badge>
      </div>
      <div class="stat-item">
        <span class="stat-label">Crit. APRÈS:</span>
        <b-badge :bg="getCriticityColor(avgAfter)">{{ formatCriticity(avgAfter) }}</b-badge>
      </div>
      <div class="stat-item">
        <span class="stat-label">Amélioration:</span>
        <b-badge :bg="getImprovementColor(avgImprovement)">{{ formatImprovement(avgImprovement) }}</b-badge>
      </div>
    </div>

    <!-- 📋 TABLE RÉSULTATS -->
    <div class="table-wrapper">
      <table class="excel-table">
        <thead>
          <tr>
            <th class="col-mode">Mode défaillance</th>
            <th class="col-plan">Plan d'action réalisé</th>
            <th class="col-before">Avant</th>
            <th class="col-gfd">G/F/D</th>
            <th class="col-after">Après</th>
            <th class="col-gfd-after">G/F/D</th>
            <th class="col-improvement">Amélioration</th>
            <th class="col-status">Statut</th>
            <th class="col-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="records.length === 0" class="row-empty">
            <td colspan="9" class="text-center text-muted p-4">
              <i class="ti ti-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
              <p class="mt-2">Aucun enregistrement Phase 3 — Approuvez des plans en Phase 2</p>
            </td>
          </tr>

          <tr v-for="record in records" :key="record.id" class="row-result">
            <!-- Mode -->
            <td class="cell-mode">
              <strong>{{ record.failure_mode }}</strong>
              <br><small class="text-muted">{{ truncate(record.effects, 35) }}</small>
            </td>

            <!-- Plan réalisé -->
            <td class="cell-plan">
              <span v-if="record.implemented_prevention_measures" class="plan-text">
                {{ truncate(record.implemented_prevention_measures, 40) }}
              </span>
              <span v-else class="text-muted">—</span>
              <br>
              <small v-if="record.actual_completion_date" class="text-success fw-bold">
                ✓ {{ formatDate(record.actual_completion_date) }}
              </small>
            </td>

            <!-- Criticité AVANT -->
            <td class="cell-before">
              <b-badge
                v-if="record.criticality_nette_before"
                :bg="getCriticityColor(record.criticality_nette_before)"
                class="badge-criticality"
              >
                {{ formatCriticity(record.criticality_nette_before) }}
              </b-badge>
            </td>

            <!-- G/F/D AVANT -->
            <td class="cell-gfd">
              <div class="gfd-badges">
                <span class="badge-mini" :style="{ backgroundColor: getGravityColor(record.gravity_before_id) }">
                  G{{ getGravityDegree(record.gravity_before_id) }}
                </span>
                <span class="badge-mini" :style="{ backgroundColor: getFrequencyColor(record.frequency_before_id) }">
                  F{{ getFrequencyDegree(record.frequency_before_id) }}
                </span>
                <span class="badge-mini" :style="{ backgroundColor: getDetectabilityColor(record.detectability_before_id) }">
                  D{{ getDetectabilityDegree(record.detectability_before_id) }}
                </span>
              </div>
            </td>

            <!-- Criticité APRÈS -->
            <td class="cell-after">
              <b-badge
                v-if="record.criticality_nette_after"
                :bg="getCriticityColor(record.criticality_nette_after)"
                class="badge-criticality"
              >
                {{ formatCriticity(record.criticality_nette_after) }}
              </b-badge>
              <span v-else class="text-muted">—</span>
            </td>

            <!-- G/F/D APRÈS -->
            <td class="cell-gfd-after">
              <div v-if="record.gravity_after_id" class="gfd-badges">
                <span class="badge-mini" :style="{ backgroundColor: getGravityColor(record.gravity_after_id) }">
                  G{{ getGravityDegree(record.gravity_after_id) }}
                </span>
                <span class="badge-mini" :style="{ backgroundColor: getFrequencyColor(record.frequency_after_id) }">
                  F{{ getFrequencyDegree(record.frequency_after_id) }}
                </span>
                <span class="badge-mini" :style="{ backgroundColor: getDetectabilityColor(record.detectability_after_id) }">
                  D{{ getDetectabilityDegree(record.detectability_after_id) }}
                </span>
              </div>
              <span v-else class="text-muted">—</span>
            </td>

            <!-- Amélioration -->
            <td class="cell-improvement">
              <div v-if="record.improvement_percentage" class="improvement-display">
                <b-badge :bg="getImprovementColor(record.improvement_percentage)" class="badge-improvement">
                  {{ formatImprovement(record.improvement_percentage) }}
                </b-badge>
                <small v-if="record.improvement_percentage > 0" class="text-success d-block fw-bold">✓ Amélioré</small>
                <small v-else-if="record.improvement_percentage < 0" class="text-danger d-block fw-bold">✗ Dégradé</small>
              </div>
              <span v-else class="text-muted">—</span>
            </td>

            <!-- Statut -->
            <td class="cell-status">
              <b-badge :bg="getStatusColor(record.action_status)">
                {{ getStatusLabel(record.action_status) }}
              </b-badge>
            </td>

            <!-- Actions -->
            <td class="cell-actions">
              <b-button size="sm" variant="info" @click="openModalViewResults(record)" title="Voir détails" class="btn-mini">
                <i class="ti ti-eye"></i>
              </b-button>
              <b-button size="sm" variant="warning" @click="openModalEditResults(record)" title="Modifier" class="btn-mini">
                <i class="ti ti-pencil"></i>
              </b-button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════
         🔲 MODAL — VOIR DÉTAILS
         ═════════════════════════════════════════════════════════════════════ -->
    <b-modal
      id="modal-view-results"
      v-model="showModalView"
      title="📊 Résultats complets"
      size="lg"
      @ok="showModalView = false"
      ok-only
      ok-title="Fermer"
    >
      <div v-if="viewRecord" class="results-view">
        <!-- MODE -->
        <div class="view-section">
          <h6 class="section-title">📋 Mode défaillance</h6>
          <div class="view-content">
            <p><strong>Mode:</strong> {{ viewRecord.failure_mode }}</p>
            <p><strong>Effets:</strong> {{ viewRecord.effects }}</p>
            <p><strong>Causes:</strong> {{ viewRecord.causes }}</p>
            <p><strong>Contrôles initiaux:</strong> {{ viewRecord.detection_measures }}</p>
          </div>
        </div>

        <!-- PLAN D'ACTION -->
        <div class="view-section">
          <h6 class="section-title">🛡️ Plan d'action</h6>
          <div class="view-content">
            <p><strong>Mesures prévues:</strong> {{ viewRecord.prevention_measures }}</p>
            <p><strong>Responsable:</strong> {{ viewRecord.action_responsible }}</p>
            <p><strong>Délai prévu:</strong> {{ formatDate(viewRecord.action_deadline) }}</p>
          </div>
        </div>

        <!-- CRITICITÉ AVANT -->
        <div class="view-section">
          <h6 class="section-title">📈 Criticité AVANT correction</h6>
          <div class="view-content">
            <div class="criticality-comparison">
              <div class="comparison-item">
                <span class="label">NPR:</span>
                <span class="value">{{ viewRecord.criticality_before }}/125</span>
              </div>
              <div class="comparison-item">
                <span class="label">Criticité nette:</span>
                <b-badge :bg="getCriticityColor(viewRecord.criticality_nette_before)">
                  {{ formatCriticity(viewRecord.criticality_nette_before) }}
                </b-badge>
              </div>
              <div class="comparison-item">
                <span class="label">Norme:</span>
                <span class="value">{{ getStandardName(getStandardFromCriticality(viewRecord.criticality_nette_before)) }}</span>
              </div>
            </div>
            <div class="gfd-display">
              <span class="badge-mini" :style="{ backgroundColor: getGravityColor(viewRecord.gravity_before_id) }">
                G: {{ getGravityLabel(viewRecord.gravity_before_id) }}
              </span>
              <span class="badge-mini" :style="{ backgroundColor: getFrequencyColor(viewRecord.frequency_before_id) }">
                F: {{ getFrequencyLabel(viewRecord.frequency_before_id) }}
              </span>
              <span class="badge-mini" :style="{ backgroundColor: getDetectabilityColor(viewRecord.detectability_before_id) }">
                D: {{ getDetectabilityLabel(viewRecord.detectability_before_id) }}
              </span>
            </div>
          </div>
        </div>

        <!-- ACTIONS RÉALISÉES -->
        <div class="view-section">
          <h6 class="section-title">✅ Actions réalisées</h6>
          <div class="view-content">
            <p><strong>Mesures réalisées:</strong> {{ viewRecord.implemented_prevention_measures }}</p>
            <p><strong>Date réelle:</strong> {{ formatDate(viewRecord.actual_completion_date) }}</p>
          </div>
        </div>

        <!-- CRITICITÉ APRÈS -->
        <div class="view-section">
          <h6 class="section-title">📉 Criticité APRÈS correction</h6>
          <div class="view-content">
            <div class="criticality-comparison">
              <div class="comparison-item">
                <span class="label">NPR:</span>
                <span class="value">{{ viewRecord.criticality_after }}/125</span>
              </div>
              <div class="comparison-item">
                <span class="label">Criticité nette:</span>
                <b-badge :bg="getCriticityColor(viewRecord.criticality_nette_after)">
                  {{ formatCriticity(viewRecord.criticality_nette_after) }}
                </b-badge>
              </div>
              <div class="comparison-item">
                <span class="label">Norme:</span>
                <span class="value">{{ getStandardName(getStandardFromCriticality(viewRecord.criticality_nette_after)) }}</span>
              </div>
            </div>
            <div class="gfd-display">
              <span class="badge-mini" :style="{ backgroundColor: getGravityColor(viewRecord.gravity_after_id) }">
                G: {{ getGravityLabel(viewRecord.gravity_after_id) }}
              </span>
              <span class="badge-mini" :style="{ backgroundColor: getFrequencyColor(viewRecord.frequency_after_id) }">
                F: {{ getFrequencyLabel(viewRecord.frequency_after_id) }}
              </span>
              <span class="badge-mini" :style="{ backgroundColor: getDetectabilityColor(viewRecord.detectability_after_id) }">
                D: {{ getDetectabilityLabel(viewRecord.detectability_after_id) }}
              </span>
            </div>
          </div>
        </div>

        <!-- AMÉLIORATION -->
        <div class="view-section alert-info">
          <h6 class="section-title">🎯 Résultat final</h6>
          <div class="improvement-result">
            <div class="result-item">
              <span class="result-label">Amélioration:</span>
              <b-badge :bg="getImprovementColor(viewRecord.improvement_percentage)" class="badge-improvement">
                {{ formatImprovement(viewRecord.improvement_percentage) }}
              </b-badge>
            </div>
            <div class="result-item">
              <span class="result-label">Statut:</span>
              <span v-if="viewRecord.improvement_percentage > 0" class="text-success fw-bold">
                ✓ Efficace - Risque réduit
              </span>
              <span v-else-if="viewRecord.improvement_percentage < 0" class="text-danger fw-bold">
                ✗ Inefficace - Risque augmenté
              </span>
              <span v-else class="text-warning fw-bold">
                → Aucun changement
              </span>
            </div>
          </div>
        </div>
      </div>
    </b-modal>

    <!-- ═════════════════════════════════════════════════════════════════════
         🔲 MODAL — MODIFIER RÉSULTATS
         ═════════════════════════════════════════════════════════════════════ -->
    <b-modal
      id="modal-edit-results"
      v-model="showModalEdit"
      title="✏️ Enregistrer résultats (Phase 3)"
      size="xl"
      @ok="handleSaveResults"
      @cancel="resetModal"
      ok-title="Enregistrer"
      cancel-title="Annuler"
    >
      <b-form v-if="editRecord">
        <!-- INFOS MODE -->
        <b-card bg-light class="mb-3" title="📋 Mode & Plan d'action">
          <b-card-body class="p-3">
            <b-row>
              <b-col md="6">
                <div><strong>Mode:</strong> {{ editRecord.failure_mode }}</div>
                <div><strong>Plan:</strong> {{ truncate(editRecord.prevention_measures, 50) }}</div>
              </b-col>
              <b-col md="6">
                <div><strong>Crit. AVANT:</strong>
                  <b-badge :bg="getCriticityColor(editRecord.criticality_nette_before)">
                    {{ formatCriticity(editRecord.criticality_nette_before) }}
                  </b-badge>
                </div>
              </b-col>
            </b-row>
          </b-card-body>
        </b-card>

        <!-- ACTIONS RÉALISÉES -->
        <b-card title="✅ Actions réalisées" class="mb-3">
          <b-card-body>
            <b-form-group label="Mesures réalisées *" label-class="fw-bold">
              <b-form-textarea
                v-model="modalForm.implemented_prevention_measures"
                placeholder="Décrivez les actions réellement mises en œuvre..."
                rows="3"
                required
              />
            </b-form-group>

            <b-form-group label="Date réelle d'accomplissement *" label-class="fw-bold">
              <b-form-input
                v-model="modalForm.actual_completion_date"
                type="date"
                required
              />
            </b-form-group>
          </b-card-body>
        </b-card>

        <!-- CRITICITÉ APRÈS -->
        <b-card title="📊 Criticité APRÈS correction" class="mb-3">
          <b-card-body>
            <b-form-group label="Gravité nette *" label-class="fw-bold">
              <b-form-select
                v-model="modalForm.gravity_after_id"
                :options="gravityOptions"
                @change="recalculateAfter"
                required
              />
            </b-form-group>

            <b-form-group label="Fréquence nette *" label-class="fw-bold">
              <b-form-select
                v-model="modalForm.frequency_after_id"
                :options="frequencyOptions"
                @change="recalculateAfter"
                required
              />
            </b-form-group>

            <b-form-group label="Détectabilité nette *" label-class="fw-bold">
              <b-form-select
                v-model="modalForm.detectability_after_id"
                :options="detectabilityOptions"
                @change="recalculateAfter"
                required
              />
            </b-form-group>

            <!-- RÉSULTAT CALCULÉ -->
            <div v-if="modalForm.gravity_after_id && modalForm.frequency_after_id && modalForm.detectability_after_id" class="alert alert-info">
              <strong>Criticité calculée automatiquement:</strong>
              <div class="mt-2">
                <span>NPR: <b-badge bg="dark">{{ modalForm.criticality_after }}/125</b-badge></span>
                <span class="ms-2">CN%: <b-badge :bg="getCriticityColor(modalForm.criticality_nette_after)">{{ formatCriticity(modalForm.criticality_nette_after) }}</b-badge></span>
                <span class="ms-2">Amélioration: <b-badge :bg="getImprovementColor(modalForm.improvement_percentage)">{{ formatImprovement(modalForm.improvement_percentage) }}</b-badge></span>
              </div>
            </div>
          </b-card-body>
        </b-card>

        <!-- STATUT -->
        <b-form-group label="Statut d'action" label-class="fw-bold">
          <b-form-select
            v-model="modalForm.action_status"
            :options="statusOptions"
          />
        </b-form-group>
      </b-form>
    </b-modal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  activities: { type: Array, default: () => [] },
  records: { type: Array, default: () => [] },
  referentials: { type: Object, default: () => ({ gravities: [], frequencies: [], detectabilities: [] }) },
  standards: { type: Array, default: () => [] }
})

const emit = defineEmits(['save-record', 'delete-record'])

const showModalView = ref(false)
const showModalEdit = ref(false)
const viewRecord = ref(null)
const editRecord = ref(null)

const modalForm = ref({
  amdec_record_id: null,
  implemented_prevention_measures: '',
  actual_completion_date: '',
  gravity_after_id: null,
  frequency_after_id: null,
  detectability_after_id: null,
  criticality_after: null,
  criticality_nette_after: null,
  improvement_percentage: null,
  action_status: 'pending'
})

const statusOptions = [
  { value: 'pending', text: '⏳ En attente' },
  { value: 'in_progress', text: '🔄 En cours' },
  { value: 'completed', text: '✅ Terminé' },
  { value: 'cancelled', text: '❌ Annulé' }
]

// ═══════════════════════════════════════════════════════════════════════════
// COMPUTED - PAS DE WATCHERS!
// ═══════════════════════════════════════════════════════════════════════════

const recordsPhase3 = computed(() => {
  return props.records.filter(r => r.phase === 'PHASE3')
})

const gravityOptions = computed(() => [
  { value: null, text: '— Sélectionnez —' },
  ...props.referentials.gravities.map(g => ({ value: g.id, text: `G${g.degree} — ${g.label}` }))
])

const frequencyOptions = computed(() => [
  { value: null, text: '— Sélectionnez —' },
  ...props.referentials.frequencies.map(f => ({ value: f.id, text: `F${f.degree} — ${f.label}` }))
])

const detectabilityOptions = computed(() => [
  { value: null, text: '— Sélectionnez —' },
  ...props.referentials.detectabilities.map(d => ({ value: d.id, text: `D${d.degree} — ${d.label}` }))
])

const avgBefore = computed(() => {
  if (recordsPhase3.value.length === 0) return 0
  const sum = recordsPhase3.value.reduce((acc, r) => acc + (r.criticality_nette_before || 0), 0)
  return sum / recordsPhase3.value.length
})

const avgAfter = computed(() => {
  if (recordsPhase3.value.length === 0) return 0
  const sum = recordsPhase3.value.reduce((acc, r) => acc + (r.criticality_nette_after || 0), 0)
  return sum / recordsPhase3.value.length
})

const avgImprovement = computed(() => {
  if (recordsPhase3.value.length === 0) return 0
  const sum = recordsPhase3.value.reduce((acc, r) => acc + (r.improvement_percentage || 0), 0)
  return sum / recordsPhase3.value.length
})

// ═══════════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════════

const getGravityLabel = (id) => props.referentials.gravities?.find(g => g.id === id)?.label || '—'
const getGravityDegree = (id) => props.referentials.gravities?.find(g => g.id === id)?.degree || '—'
const getGravityColor = (id) => props.referentials.gravities?.find(g => g.id === id)?.color || '#ccc'

const getFrequencyLabel = (id) => props.referentials.frequencies?.find(f => f.id === id)?.label || '—'
const getFrequencyDegree = (id) => props.referentials.frequencies?.find(f => f.id === id)?.degree || '—'
const getFrequencyColor = (id) => props.referentials.frequencies?.find(f => f.id === id)?.color || '#ccc'

const getDetectabilityLabel = (id) => props.referentials.detectabilities?.find(d => d.id === id)?.label || '—'
const getDetectabilityDegree = (id) => props.referentials.detectabilities?.find(d => d.id === id)?.degree || '—'
const getDetectabilityColor = (id) => props.referentials.detectabilities?.find(d => d.id === id)?.color || '#ccc'

const getStandardName = (id) => props.standards?.find(s => s.id === id)?.name || '—'
const getStandardFromCriticality = (crit) => {
  if (!crit && crit !== 0) return null
  return props.standards?.find(s => crit >= s.min_criticality && crit <= s.max_criticality)?.id || null
}

const formatCriticity = (value) => {
  if (!value && value !== 0) return '—'
  if (typeof value !== 'number') return '—'
  return value.toFixed(1) + '%'
}

const formatImprovement = (value) => {
  if (!value && value !== 0) return '—'
  if (typeof value !== 'number') return '—'
  return (value > 0 ? '+' : '') + value.toFixed(1) + '%'
}

const getCriticityColor = (value) => {
  if (!value && value !== 0) return 'secondary'
  if (value <= 20) return 'success'
  if (value <= 40) return 'info'
  if (value <= 60) return 'warning'
  if (value <= 80) return 'danger'
  return 'dark'
}

const getImprovementColor = (value) => {
  if (!value && value !== 0) return 'secondary'
  if (value > 30) return 'success'
  if (value > 0) return 'info'
  if (value < -30) return 'danger'
  if (value < 0) return 'warning'
  return 'secondary'
}

const getStatusColor = (status) => {
  const colors = { pending: 'warning', in_progress: 'primary', completed: 'success', cancelled: 'secondary' }
  return colors[status] || 'secondary'
}

const getStatusLabel = (status) => {
  const labels = { pending: '⏳ En attente', in_progress: '🔄 En cours', completed: '✅ Terminé', cancelled: '❌ Annulé' }
  return labels[status] || status
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fr-FR')
}

const truncate = (text, length) => {
  if (!text) return '—'
  return text.length > length ? text.substring(0, length) + '…' : text
}

// ═══════════════════════════════════════════════════════════════════════════
// METHODS
// ═══════════════════════════════════════════════════════════════════════════

const openModalViewResults = (record) => {
  viewRecord.value = record
  showModalView.value = true
}

const openModalEditResults = (record) => {
  editRecord.value = record
  modalForm.value = {
    amdec_record_id: record.id,
    implemented_prevention_measures: record.implemented_prevention_measures || '',
    actual_completion_date: record.actual_completion_date || '',
    gravity_after_id: record.gravity_after_id,
    frequency_after_id: record.frequency_after_id,
    detectability_after_id: record.detectability_after_id,
    criticality_after: record.criticality_after || null,
    criticality_nette_after: record.criticality_nette_after || null,
    improvement_percentage: record.improvement_percentage || null,
    action_status: record.action_status || 'pending'
  }
  showModalEdit.value = true
}

const recalculateAfter = () => {
  if (!modalForm.value.gravity_after_id || !modalForm.value.frequency_after_id || !modalForm.value.detectability_after_id) {
    modalForm.value.criticality_after = null
    modalForm.value.criticality_nette_after = null
    modalForm.value.improvement_percentage = null
    return
  }

  const gravity = props.referentials.gravities.find(g => g.id === modalForm.value.gravity_after_id)
  const frequency = props.referentials.frequencies.find(f => f.id === modalForm.value.frequency_after_id)
  const detectability = props.referentials.detectabilities.find(d => d.id === modalForm.value.detectability_after_id)

  if (gravity && frequency && detectability) {
    modalForm.value.criticality_after = gravity.degree * frequency.degree * detectability.degree
    modalForm.value.criticality_nette_after = (modalForm.value.criticality_after / 125) * 100

    // Calcul amélioration
    if (editRecord.value?.criticality_nette_before && editRecord.value.criticality_nette_before > 0) {
      modalForm.value.improvement_percentage = 
        ((editRecord.value.criticality_nette_before - modalForm.value.criticality_nette_after) / editRecord.value.criticality_nette_before) * 100
    }
  }
}

const resetModal = () => {
  modalForm.value = {
    amdec_record_id: null,
    implemented_prevention_measures: '',
    actual_completion_date: '',
    gravity_after_id: null,
    frequency_after_id: null,
    detectability_after_id: null,
    criticality_after: null,
    criticality_nette_after: null,
    improvement_percentage: null,
    action_status: 'pending'
  }
  editRecord.value = null
}

const handleSaveResults = () => {
  if (!modalForm.value.implemented_prevention_measures?.trim()) {
    alert('⚠️ Mesures réalisées requises')
    return
  }
  if (!modalForm.value.actual_completion_date) {
    alert('⚠️ Date réelle requise')
    return
  }
  if (!modalForm.value.gravity_after_id || !modalForm.value.frequency_after_id || !modalForm.value.detectability_after_id) {
    alert('⚠️ G, F, D nettes requises')
    return
  }

  emit('save-record', {
    phase: 'PHASE3',
    amdec_record_id: modalForm.value.amdec_record_id,
    implemented_prevention_measures: modalForm.value.implemented_prevention_measures,
    actual_completion_date: modalForm.value.actual_completion_date,
    gravity_after_id: modalForm.value.gravity_after_id,
    frequency_after_id: modalForm.value.frequency_after_id,
    detectability_after_id: modalForm.value.detectability_after_id,
    action_status: modalForm.value.action_status
  })

  showModalEdit.value = false
  resetModal()
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
  padding: 0.75rem;
  background: #f8f9fa;
  border-left: 3px solid #198754;
  border-radius: 0.3rem;
  align-items: center;
  flex-wrap: wrap;
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

/* TABLE */
.table-wrapper {
  border: 1px solid #dee2e6;
  border-radius: 0.3rem;
  overflow-x: auto;
}

.excel-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
  background: white;
}

.excel-table thead {
  background: #198754;
  color: white;
  font-weight: 600;
  position: sticky;
  top: 0;
}

.excel-table th {
  padding: 0.5rem;
  text-align: left;
  border: 1px solid #dee2e6;
  white-space: nowrap;
}

.excel-table td {
  padding: 0.5rem;
  border: 1px solid #dee2e6;
}

.excel-table tbody tr {
  border-bottom: 1px solid #dee2e6;
}

.excel-table tbody tr:nth-child(odd) {
  background: #f8fff9;
}

.excel-table tbody tr:hover {
  background: #d4edda;
}

/* COLONNES */
.col-mode { width: 120px; }
.col-plan { width: 130px; }
.col-before { width: 70px; text-align: center; }
.col-gfd { width: 90px; text-align: center; }
.col-after { width: 70px; text-align: center; }
.col-gfd-after { width: 90px; text-align: center; }
.col-improvement { width: 90px; text-align: center; }
.col-status { width: 80px; text-align: center; }
.col-actions { width: 70px; text-align: center; }

/* CELLS */
.cell-mode {
  font-weight: 500;
  background: #f0f8f5;
}

.cell-before,
.cell-after,
.cell-gfd,
.cell-gfd-after,
.cell-improvement,
.cell-status,
.cell-actions {
  text-align: center;
}

/* BADGES */
.badge-mini {
  display: inline-block;
  padding: 0.2rem 0.4rem;
  border-radius: 0.2rem;
  color: white;
  font-weight: 600;
  font-size: 0.6rem;
  margin: 0.1rem;
}

.badge-criticality {
  font-size: 0.75rem;
  padding: 0.3rem 0.5rem;
  font-weight: 600;
  min-width: 50px;
}

.badge-improvement {
  font-size: 0.75rem;
  padding: 0.3rem 0.5rem;
  font-weight: 600;
  min-width: 55px;
}

.gfd-badges,
.gfd-display {
  display: flex;
  gap: 0.3rem;
  flex-wrap: wrap;
  justify-content: center;
}

/* MODAL */
.view-section {
  border-left: 3px solid #198754;
  padding: 1rem;
  margin-bottom: 1rem;
  background: #f8f9fa;
  border-radius: 0.3rem;
}

.section-title {
  color: #198754;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.view-content p {
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.view-content p strong {
  color: #198754;
}

.criticality-comparison {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.5rem;
}

.comparison-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.comparison-item .label {
  font-weight: 600;
  color: #495057;
  font-size: 0.85rem;
}

.comparison-item .value {
  font-weight: 600;
  color: #198754;
}

.improvement-result {
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
}

.result-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.result-label {
  font-weight: 600;
  color: #495057;
}

.improvement-display {
  text-align: center;
}

.row-empty td {
  padding: 2rem !important;
}

.alert-info {
  border-left-color: #0d6efd !important;
  background-color: #e7f3ff !important;
}

/* BUTTONS */
.btn-mini {
  padding: 0.25rem 0.4rem;
  font-size: 0.7rem;
  border-radius: 0.2rem;
  margin-right: 0.2rem;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .excel-table { font-size: 0.75rem; }
  .col-mode { width: 100px; }
  .col-plan { width: 100px; }
}

@media (max-width: 768px) {
  .stats-bar {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .col-mode { width: 80px; }
  .col-plan { width: 80px; }
  .col-gfd { width: 70px; }
  .col-gfd-after { width: 70px; }
}
</style>