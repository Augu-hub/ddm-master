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
            <td>{{ record.failure_mode }}</td>
            <td>{{ getActivityName(record.activity_id) }}</td>
            <td>
              <small v-if="record.prevention_measures" class="text-truncate d-block">
                {{ record.prevention_measures }}
              </small>
              <span v-else class="text-muted">—</span>
            </td>
            <td>{{ record.action_responsible || '—' }}</td>
            <td>{{ record.action_deadline || '—' }}</td>
            <td class="text-center">
              <b-button 
                size="sm" 
                variant="warning" 
                @click="openModalEditPlan(record)"
                class="btn-action"
              >
                ✏️
              </b-button>
              <b-button 
                size="sm" 
                variant="danger" 
                @click="deleteRecord(record)"
                class="btn-action"
              >
                🗑️
              </b-button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL -->
    <b-modal
      v-model="showModal"
      :title="modalMode === 'add' ? 'Nouveau Plan' : 'Modifier Plan'"
      @ok="savePlan"
      @cancel="resetForm"
      ok-title="Enregistrer"
      cancel-title="Annuler"
    >
      <!-- Mode sélection -->
      <b-form-group label="Mode *">
        <b-form-select
          v-model.number="form.amdec_record_id"
          :options="modeOptions"
          :disabled="modalMode === 'edit'"
        />
      </b-form-group>

      <!-- Prévention -->
      <b-form-group label="Mesures de prévention *">
        <b-form-textarea
          v-model="form.prevention_measures"
          rows="2"
        />
      </b-form-group>

      <!-- Responsable -->
      <b-form-group label="Responsable">
        <b-form-input
          v-model="form.action_responsible"
          type="text"
          placeholder="Nom ou fonction"
        />
      </b-form-group>

      <!-- Délai -->
      <b-form-group label="Délai prévu *">
        <b-form-input
          v-model="form.action_deadline"
          type="date"
        />
      </b-form-group>
    </b-modal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  activities: Array,
  records: Array,
  referentials: Object
})

const emit = defineEmits(['save-record', 'delete-record'])

// STATE
const showModal = ref(false)
const modalMode = ref('add')
const form = ref({
  amdec_record_id: null,
  prevention_measures: '',
  action_responsible: '',
  action_deadline: ''
})

// COMPUTED
const allPhase1Records = computed(() => {
  if (!props.records) return []
  return props.records.filter(r => r.phase === 'PHASE1')
})

const recordsPhase2 = computed(() => {
  if (!props.records) return []
  return props.records.filter(r => r.phase === 'PHASE2')
})

const modeOptions = computed(() => {
  return [
    { value: null, text: '-- Sélectionnez --' },
    ...allPhase1Records.value.map(m => ({
      value: m.id,
      text: m.failure_mode
    }))
  ]
})

// METHODS
const getActivityName = (id) => {
  if (!id || !props.activities) return '—'
  const act = props.activities.find(a => a.id === id)
  return act ? act.name : '—'
}

const openModalAddPlan = () => {
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
    action_deadline: record.action_deadline || ''
  }
  showModal.value = true
}

const resetForm = () => {
  form.value = {
    amdec_record_id: null,
    prevention_measures: '',
    action_responsible: '',
    action_deadline: ''
  }
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
  if (!form.value.action_deadline) {
    alert('⚠️ Définissez un délai')
    return
  }

  emit('save-record', {
    phase: 'PHASE2',
    amdec_record_id: form.value.amdec_record_id,
    prevention_measures: form.value.prevention_measures,
    action_responsible: form.value.action_responsible,
    action_deadline: form.value.action_deadline
  })

  showModal.value = false
  resetForm()
}

const deleteRecord = (record) => {
  if (confirm('Supprimer ce plan ?')) {
    emit('delete-record', record.id)
  }
}
</script>

<style scoped>
.phase2-container {
  padding: 1rem;
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
}

.ms-auto {
  margin-left: auto;
}

.ms-2 {
  margin-left: 0.5rem;
}

.mb-3 {
  margin-bottom: 1rem;
}

.table-wrapper {
  border: 1px solid #dee2e6;
  border-radius: 0.3rem;
  overflow: auto;
}

.table-simple {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.table-simple thead {
  background: #0d6efd;
  color: white;
  font-weight: 600;
}

.table-simple th,
.table-simple td {
  padding: 0.75rem;
  border: 1px solid #dee2e6;
  text-align: left;
}

.table-simple tbody tr:hover {
  background: #f8f9fa;
}

.text-center {
  text-align: center;
}

.text-muted {
  color: #6c757d;
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.d-block {
  display: block;
}

.btn-action {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  margin-right: 0.25rem;
}
</style>