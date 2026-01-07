<template>
  <div class="phase2-container">
    <!-- STAT BAR -->
    <div class="stats-bar mb-3">
      <span><strong>{{ allPhase1Records.length }}</strong> Phase 1</span>
      <span class="ms-auto"><strong>{{ recordsPhase2.length }}</strong> Plans</span>
      <b-button 
        variant="primary" 
        size="sm" 
        @click="openModalAddPlan"
        :disabled="allPhase1Records.length === 0"
        class="ms-2"
      >
        <i class="ti ti-plus"></i> Plan
      </b-button>
    </div>

    <!-- TABLE -->
    <div class="table-wrapper">
      <table class="table-simple">
        <thead>
          <tr>
            <th>Mode</th>
            <th>Activité</th>
            <th>Prévention</th>
            <th>Responsable</th>
            <th>Délai</th>
            <th style="width: 80px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="recordsPhase2.length === 0">
            <td colspan="6" class="text-center text-muted">Aucun plan</td>
          </tr>

          <tr v-for="record in recordsPhase2" :key="record.id">
            <td><strong>{{ record.failure_mode }}</strong></td>
            <td><small>{{ getActivityName(record.activity_id) }}</small></td>
            <td>
              <small v-if="record.prevention_measures" class="text-truncate d-block">
                {{ record.prevention_measures }}
              </small>
              <span v-else class="text-muted">—</span>
            </td>
            <td>{{ record.action_responsible || '—' }}</td>
            <td>{{ formatDate(record.action_deadline) || '—' }}</td>
            <td class="text-center">
              <b-button 
                size="sm" 
                variant="warning" 
                @click="openModalEditPlan(record)"
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
    <!-- 🔲 MODAL — AJOUTER/MODIFIER AVEC IA AUTOMATIQUE -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <b-modal
      v-model="showModal"
      :title="modalMode === 'add' ? 'Nouveau Plan d\'action' : 'Modifier Plan d\'action'"
      size="lg"
      @ok="savePlan"
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
                <strong>Effets:</strong> {{ selectedMode.effects }}<br>
                <strong>Causes:</strong> {{ selectedMode.causes }}<br>
                <strong>Criticité:</strong> {{ formatCriticity(selectedMode.criticality_nette_before) }}
              </small>
            </b-form-group>
          </b-col>
        </b-row>

        <!-- 🤖 SUGGESTIONS IA (générées quand on sélectionne un mode) -->
        <b-row v-if="aiSuggestions.prevention_measures || aiSuggestions.action_responsible" class="mb-3">
          <b-col cols="12">
            <div class="ai-suggestions-auto">
              <div class="ai-header">
                ✨ <strong>Suggestions IA</strong>
                <small v-if="isAiLoading" class="text-info">
                  <i class="ti ti-loading"></i> Génération en cours...
                </small>
              </div>

              <!-- Suggestion Mesures de prévention -->
              <div v-if="aiSuggestions.prevention_measures" class="ai-suggestion">
                <div class="ai-suggestion-title">🛡️ Mesures de prévention</div>
                <div class="ai-suggestion-text">{{ aiSuggestions.prevention_measures }}</div>
                <div class="ai-suggestion-actions">
                  <b-button 
                    size="sm" 
                    variant="success"
                    @click="form.prevention_measures = aiSuggestions.prevention_measures"
                  >
                    ✅ Utiliser
                  </b-button>
                  <b-button 
                    size="sm" 
                    variant="outline-secondary"
                    @click="aiSuggestions.prevention_measures = null"
                  >
                    ✕ Ignorer
                  </b-button>
                </div>
              </div>

              <!-- Suggestion Responsable -->
              <div v-if="aiSuggestions.action_responsible" class="ai-suggestion">
                <div class="ai-suggestion-title">👤 Responsable de l'action</div>
                <div class="ai-suggestion-text">{{ aiSuggestions.action_responsible }}</div>
                <div class="ai-suggestion-actions">
                  <b-button 
                    size="sm" 
                    variant="success"
                    @click="form.action_responsible = aiSuggestions.action_responsible"
                  >
                    ✅ Utiliser
                  </b-button>
                  <b-button 
                    size="sm" 
                    variant="outline-secondary"
                    @click="aiSuggestions.action_responsible = null"
                  >
                    ✕ Ignorer
                  </b-button>
                </div>
              </div>

              <small v-if="aiError" class="text-danger d-block mt-2">
                ⚠️ {{ aiError }}
              </small>
            </div>
          </b-col>
        </b-row>

        <!-- ROW 2: Prévention (full width) -->
        <b-row class="mb-2">
          <b-col cols="12">
            <b-form-group label="Mesures de prévention *" label-class="fw-bold" label-cols="12">
              <b-form-textarea
                v-model="form.prevention_measures"
                placeholder="Décrivez les actions préventives..."
                rows="3"
                size="sm"
                required
              />
              <small class="text-muted d-block mt-1">
                {{ form.prevention_measures.length }}/500 caractères
              </small>
            </b-form-group>
          </b-col>
        </b-row>

        <!-- ROW 3: Responsable + Délai -->
        <b-row class="mb-2">
          <b-col cols="6">
            <b-form-group label="Responsable *" label-class="fw-bold" label-cols="12">
              <b-form-input
                v-model="form.action_responsible"
                type="text"
                placeholder="Nom ou fonction"
                size="sm"
                maxlength="100"
                required
              />
            </b-form-group>
          </b-col>
          <b-col cols="6">
            <b-form-group label="Délai prévu *" label-class="fw-bold" label-cols="12">
              <b-form-input
                v-model="form.action_deadline"
                type="date"
                size="sm"
                required
              />
            </b-form-group>
          </b-col>
        </b-row>

        <!-- ROW 4: Statut (optionnel) -->
        <b-row>
          <b-col cols="12">
            <b-form-group label="Statut" label-class="fw-bold" label-cols="12">
              <b-form-select
                v-model="form.action_status"
                :options="statusOptions"
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
  referentials: { type: Object, default: () => ({}) },
  sessionId: { type: Number, default: null },
  processId: { type: Number, default: null }
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
  prevention_measures: '',
  action_responsible: '',
  action_deadline: '',
  action_status: 'pending'
})

const aiSuggestions = ref({
  prevention_measures: null,
  action_responsible: null
})

// ═══════════════════════════════════════════════════════════════════════════
// COMPUTED
// ═══════════════════════════════════════════════════════════════════════════

const allPhase1Records = computed(() => {
  if (!props.records) return []
  return props.records.filter(r => r.phase === 'PHASE1')
})

const recordsPhase2 = computed(() => {
  if (!props.records) return []
  return props.records.filter(r => r.phase === 'PHASE2')
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
      text: `${m.failure_mode} (Crit. ${formatCriticity(m.criticality_nette_before)})`
    }))
  ]
})

const statusOptions = computed(() => [
  { value: 'pending', text: '⏳ En attente' },
  { value: 'in_progress', text: '🔄 En cours' },
  { value: 'completed', text: '✅ Complété' },
  { value: 'cancelled', text: '❌ Annulé' }
])

// ═══════════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════════

const getActivityName = (id) => {
  if (!id || !props.activities) return '—'
  const act = props.activities.find(a => a.id === id)
  return act ? act.name : '—'
}

const formatCriticity = (value) => {
  if (!value && value !== 0) return '—'
  return typeof value === 'number' ? value.toFixed(0) + '%' : '—'
}

const formatDate = (date) => {
  if (!date) return null
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// ═══════════════════════════════════════════════════════════════════════════
// 🤖 MODE SÉLECTIONNÉ — Générer les suggestions IA
// ═══════════════════════════════════════════════════════════════════════════

const onModeSelected = () => {
  if (!selectedMode.value) {
    aiSuggestions.value = { prevention_measures: null, action_responsible: null }
    aiError.value = ''
    return
  }

  // Débounce 500ms
  if (aiTimeout.value) clearTimeout(aiTimeout.value)
  
  aiTimeout.value = setTimeout(() => {
    generatePhase2Suggestions()
  }, 500)
}

/**
 * 🤖 GÉNÉRER LES SUGGESTIONS PHASE 2 PAR IA
 */
const generatePhase2Suggestions = async () => {
  if (!selectedMode.value) return

  isAiLoading.value = true
  aiError.value = ''
  aiSuggestions.value = { prevention_measures: null, action_responsible: null }

  try {
    console.log('🔍 Generating Phase2 suggestions for:', selectedMode.value.failure_mode)

    const res = await axios.post(route('process.core.amdec.ai.suggest'), {
      phase: 'PHASE2',
      payload: {
        failure_mode: selectedMode.value.failure_mode,
        activity_name: getActivityName(selectedMode.value.activity_id),
        effects: selectedMode.value.effects,
        causes: selectedMode.value.causes,
        current_controls: selectedMode.value.current_controls
      }
    })

    console.log('📥 Phase2 response:', res.data)

    if (res.data.success && res.data.suggestions) {
      aiSuggestions.value = {
        prevention_measures: res.data.suggestions.prevention_measures || null,
        action_responsible: res.data.suggestions.action_responsible || null
      }
      console.log('✅ Phase2 suggestions generated:', aiSuggestions.value)
    } else {
      aiError.value = res.data.error || 'Erreur génération'
      console.warn('⚠️ Response format incorrect:', res.data)
    }

  } catch (err) {
    console.error('❌ Erreur Phase2:', err)
    aiError.value = 'Erreur connexion IA: ' + (err.response?.data?.error || err.message)
  } finally {
    isAiLoading.value = false
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// MODAL MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════

const openModalAddPlan = () => {
  if (allPhase1Records.value.length === 0) {
    alert('⚠️ Créez d\'abord des modes de défaillance (PHASE 1)')
    return
  }
  modalMode.value = 'add'
  resetForm()
  showModal.value = true
}

const openModalEditPlan = (record) => {
  modalMode.value = 'edit'
  form.value = {
    amdec_record_id: record.id,
    prevention_measures: record.prevention_measures || '',
    action_responsible: record.action_responsible || '',
    action_deadline: record.action_deadline || '',
    action_status: record.action_status || 'pending'
  }
  aiSuggestions.value = { prevention_measures: null, action_responsible: null }
  aiError.value = ''
  showModal.value = true
}

const resetForm = () => {
  if (aiTimeout.value) clearTimeout(aiTimeout.value)
  
  form.value = {
    amdec_record_id: null,
    prevention_measures: '',
    action_responsible: '',
    action_deadline: '',
    action_status: 'pending'
  }
  aiSuggestions.value = { prevention_measures: null, action_responsible: null }
  aiError.value = ''
  isAiLoading.value = false
}

const savePlan = () => {
  if (!form.value.amdec_record_id) {
    alert('⚠️ Sélectionnez un mode')
    return
  }
  if (!form.value.prevention_measures.trim()) {
    alert('⚠️ Remplissez les mesures de prévention')
    return
  }
  if (!form.value.action_responsible.trim()) {
    alert('⚠️ Définissez un responsable')
    return
  }
  if (!form.value.action_deadline) {
    alert('⚠️ Définissez un délai')
    return
  }

  emit('save-record', {
    phase: 'PHASE2',
    amdec_record_id: form.value.amdec_record_id,
    prevention_measures: form.value.prevention_measures,
    action_responsible: form.value.action_responsible,
    action_deadline: form.value.action_deadline,
    action_status: form.value.action_status
  })

  showModal.value = false
  resetForm()
}

const deleteRecord = (record) => {
  if (confirm('⚠️ Êtes-vous sûr de supprimer ce plan ?')) {
    emit('delete-record', record.id)
  }
}
</script>

<style scoped>
.phase2-container {
  padding: 1rem;
  background: #fff;
}

.stats-bar {
  display: flex;
  gap: 1rem;
  align-items: center;
  padding: 0.75rem;
  background: #f8f9fa;
  border-left: 3px solid #0d6efd;
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
.mt-1 { margin-top: 0.25rem; }
.mt-2 { margin-top: 0.5rem; }
.d-block { display: block; }

.text-center { text-align: center; }
.text-muted { color: #6c757d; font-size: 0.85rem; }
.text-info { color: #0dcaf0; font-size: 0.85rem; }
.text-danger { color: #dc3545; font-size: 0.85rem; }

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.fw-bold {
  font-weight: 600;
}

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
  background: #0d6efd;
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
  background: #e7f3ff;
}

/* 🤖 IA SUGGESTIONS */
.ai-suggestions-auto {
  background: linear-gradient(135deg, #fff8e6 0%, #ffe6e6 100%);
  border: 2px solid #ffc107;
  border-radius: 0.4rem;
  padding: 1rem;
  margin-bottom: 1rem;
}

.ai-header {
  font-weight: 600;
  color: #ff6b6b;
  margin-bottom: 1rem;
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.ai-suggestion {
  background: white;
  border: 1px solid #ffe0b2;
  border-left: 4px solid #ffc107;
  border-radius: 0.3rem;
  padding: 0.75rem;
  margin-bottom: 0.75rem;
}

.ai-suggestion-title {
  font-weight: 600;
  color: #ff6b6b;
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