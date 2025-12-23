<template>
  <VerticalLayout>
    <Head title="Contrat d'interfaces" />

    <b-card class="shadow-sm">
      <!-- 📋 HEADER CONTRAT -->
      <div class="contract-header mb-4 pb-4 border-bottom">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h5 class="fw-bold mb-1">
              <i class="ti ti-file-text text-primary"></i>
              Contrat d'interfaces
            </h5>
            <p class="text-muted small mb-0">
              Processus: <strong>{{ process?.name || '—' }}</strong>
            </p>
          </div>
          <div class="col-md-6 text-end">
            <p class="small mb-1">
              <strong>Date création:</strong> {{ createdDate }}
            </p>
            <p class="small text-muted mb-0">
              <strong>Propriétaire:</strong> {{ ownerName || '—' }}
            </p>
          </div>
        </div>

        <!-- Finalité -->
        <div class="mt-3 p-3 bg-light rounded">
          <strong>Finalité:</strong>
          <p class="mb-0">{{ process?.purpose || 'Non défini' }}</p>
        </div>
      </div>

      <!-- 🔍 SÉLECTION FONCTION/ENTITÉ -->
      <div class="bg-light p-3 rounded mb-4">
        <h6 class="fw-semibold mb-3">
          <i class="ti ti-filter"></i> Filtrer par fonction et entité
        </h6>
        
        <div class="row g-3">
          <div class="col-md-6">
            <b-form-group label="Entité" class="mb-0">
              <b-form-select 
                v-model="selectedEntity" 
                :options="entityOptions"
                @change="onFilterChange"
              />
            </b-form-group>
          </div>

          <div class="col-md-6">
            <b-form-group label="Fonction" class="mb-0">
              <b-form-select 
                v-model="selectedFunction" 
                :options="functionOptions"
                @change="onFilterChange"
                :disabled="!selectedEntity"
              />
            </b-form-group>
          </div>
        </div>

        <!-- Acteur sélectionné -->
        <div v-if="selectedFunction && selectedEntity" class="mt-3 p-2 bg-white border rounded">
          <small class="text-muted">
            <strong>Acteur:</strong> {{ selectedActorName || '—' }}
          </small>
        </div>
      </div>

      <!-- 📊 TABLEAU CONTRAT D'INTERFACES -->
      <div v-if="process && displayedRows.length" class="contract-table-section mb-4">
        <h6 class="fw-semibold mb-3">
          <i class="ti ti-table"></i> Données d'entrée et de sortie
        </h6>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr class="text-center">
                <th style="width: 30%" class="bg-primary text-white">
                  <i class="ti ti-download"></i> Données de sortie
                </th>
                <th style="width: 15%">
                  <i class="ti ti-user"></i> Utilisateur
                </th>
                <th style="width: 25%">
                  <i class="ti ti-message-circle"></i> Attentes utilisateurs
                </th>
                <th style="width: 20%">
                  <i class="ti ti-users"></i> Acteur
                </th>
                <th style="width: 10%">
                  <i class="ti ti-file"></i> Documents
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="row in displayedRows" :key="row.id" class="data-row">
                <!-- Données de sortie / Entrée -->
                <td class="fw-semibold text-primary">
                  {{ row.label }}
                  <span v-if="row.type === 'input'" class="badge bg-info ms-2">Entrée</span>
                  <span v-else class="badge bg-success ms-2">Sortie</span>
                </td>

                <!-- Utilisateur -->
                <td class="small">
                  <input 
                    v-if="row.editable" 
                    type="text" 
                    class="form-control form-control-sm"
                    v-model="row.user"
                    placeholder="Saisir..."
                  />
                  <span v-else>{{ row.user || '—' }}</span>
                </td>

                <!-- Attentes utilisateurs -->
                <td class="small">
                  <textarea 
                    v-if="row.editable" 
                    class="form-control form-control-sm"
                    rows="2"
                    v-model="row.expectation"
                    placeholder="Saisir attentes..."
                  ></textarea>
                  <small v-else class="text-muted">{{ row.expectation || '—' }}</small>
                </td>

                <!-- Acteur -->
                <td class="small">
                  <select 
                    v-if="row.editable" 
                    class="form-select form-select-sm"
                    v-model="row.actor"
                  >
                    <option value="">— Sélectionner —</option>
                    <option v-for="actor in actorList" :key="actor" :value="actor">
                      {{ actor }}
                    </option>
                  </select>
                  <span v-else>{{ row.actor || '—' }}</span>
                </td>

                <!-- Documents -->
                <td class="text-center small">
                  <input 
                    v-if="row.editable"
                    type="file" 
                    class="form-control form-control-sm"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png"
                    @change="e => attachDoc(row, e)"
                  />
                  <span v-else-if="row.document" class="text-success">
                    <i class="ti ti-check"></i> {{ row.document }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Indicateurs -->
        <div v-if="process.indicators" class="mt-4 p-3 bg-light rounded">
          <h6 class="fw-semibold mb-2">📈 Indicateurs d'activité</h6>
          <p class="small mb-0" v-html="process.indicators"></p>
        </div>
      </div>

      <!-- 📦 RESSOURCES -->
      <div v-if="resources.length" class="resources-section mb-4">
        <h6 class="fw-semibold mb-3">
          <i class="ti ti-package"></i> Ressources requises
        </h6>

        <div class="row">
          <div v-for="resource in resources" :key="resource.id" class="col-md-4 mb-3">
            <b-card class="shadow-sm h-100">
              <b-card-text>
                <i class="ti ti-box text-primary"></i>
                <strong>{{ resource.label }}</strong>
              </b-card-text>
            </b-card>
          </div>
        </div>
      </div>

      <!-- 🎯 ACTIONS -->
      <div class="d-flex gap-2 justify-content-between mt-4 pt-4 border-top">
        <div>
          <b-button 
            variant="outline-secondary" 
            @click="goBack"
            class="me-2"
          >
            <i class="ti ti-arrow-left"></i> Retour
          </b-button>

          <b-button 
            variant="outline-primary" 
            @click="exportToExcel"
          >
            <i class="ti ti-download"></i> Exporter Excel
          </b-button>
        </div>

        <div>
          <b-button 
            v-if="!isEditing"
            variant="primary" 
            @click="startEditing"
          >
            <i class="ti ti-edit"></i> Modifier
          </b-button>

          <div v-else>
            <b-button 
              variant="secondary" 
              @click="cancelEditing"
              class="me-2"
            >
              <i class="ti ti-x"></i> Annuler
            </b-button>

            <b-button 
              variant="success" 
              @click="saveChanges"
              :disabled="isSaving"
            >
              <b-spinner small v-if="isSaving"></b-spinner>
              <i v-else class="ti ti-check"></i>
              {{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}
            </b-button>
          </div>
        </div>
      </div>

    </b-card>

  </VerticalLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import VerticalLayout from '@/layouts/VerticalLayout.vue'

const props = defineProps({
  process: {
    type: Object,
    required: true
  },
  entities: {
    type: Array,
    default: () => []
  },
  functions: {
    type: Array,
    default: () => []
  },
  functionAssignments: {
    type: Array,
    default: () => []
  }
})

/* ========== ÉTAT ========== */
const selectedEntity = ref(null)
const selectedFunction = ref(null)
const isEditing = ref(false)
const isSaving = ref(false)

const inputs = ref([])
const outputs = ref([])
const resources = ref([])
const actorList = ref([])

const form = useForm({
  rows: []
})

/* ========== OPTIONS SÉLECTEURS ========== */
const entityOptions = computed(() => 
  props.entities.map(e => ({
    value: e.id,
    text: e.name
  }))
)

const functionOptions = computed(() => {
  if (!selectedEntity.value) return []
  
  return props.functions
    .filter(f => {
      const assignment = props.functionAssignments.find(
        fa => fa.function_id === f.id && fa.entity_id === selectedEntity.value
      )
      return !!assignment
    })
    .map(f => ({
      value: f.id,
      text: f.name
    }))
})

const selectedActorName = computed(() => {
  const func = props.functions.find(f => f.id === selectedFunction.value)
  return func?.name || '—'
})

const ownerName = computed(() => {
  return props.process?.owner?.name || props.process?.owner_name || '—'
})

const createdDate = computed(() => {
  if (!props.process?.created_at) return 'N/A'
  return new Date(props.process.created_at).toLocaleDateString('fr-FR')
})

/* ========== DONNÉES AFFICHÉES ========== */
const displayedRows = computed(() => {
  const rows = [
    ...inputs.value.map(i => ({ ...i, type: 'input', editable: isEditing.value })),
    ...outputs.value.map(o => ({ ...o, type: 'output', editable: isEditing.value }))
  ]
  return rows
})

/* ========== CHARGER LES DONNÉES ========== */
async function onFilterChange() {
  if (!selectedEntity.value || !selectedFunction.value) {
    inputs.value = []
    outputs.value = []
    return
  }

  try {
    // Charger les entrées
    const inputRes = await fetch(
      `/api/processes/${props.process.id}/inputs`
    )
    if (inputRes.ok) {
      const data = await inputRes.json()
      inputs.value = (data.inputs || []).map(i => ({
        id: i.id,
        label: i.label,
        type: 'input',
        user: '',
        expectation: '',
        actor: selectedActorName.value,
        document: null
      }))
    }

    // Charger les sorties
    const outputRes = await fetch(
      `/api/processes/${props.process.id}/outputs`
    )
    if (outputRes.ok) {
      const data = await outputRes.json()
      outputs.value = (data.outputs || []).map(o => ({
        id: o.id,
        label: o.label,
        type: 'output',
        user: '',
        expectation: '',
        actor: selectedActorName.value,
        document: null
      }))
    }

    // Charger les ressources
    const resRes = await fetch(
      `/api/processes/${props.process.id}/resources`
    )
    if (resRes.ok) {
      const data = await resRes.json()
      resources.value = data.resources || []
    }

    // Charger les acteurs disponibles
    const actorsRes = await fetch(
      `/api/functions/${selectedFunction.value}/actors`
    )
    if (actorsRes.ok) {
      const data = await actorsRes.json()
      actorList.value = data.actors || []
    }

  } catch (error) {
    console.error('Erreur chargement données:', error)
  }
}

/* ========== MODIFIER DOCUMENT ========== */
function attachDoc(row, event) {
  const file = event.target.files?.[0]
  if (file) {
    row.document = file.name
  }
}

/* ========== ÉDITION ========== */
function startEditing() {
  isEditing.value = true
  form.rows = displayedRows.value.map(r => ({ ...r }))
}

function cancelEditing() {
  isEditing.value = false
}

async function saveChanges() {
  isSaving.value = true

  try {
    const response = await fetch(
      `/m/process.core/process/${props.process.id}/contract/update`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({
          entity_id: selectedEntity.value,
          function_id: selectedFunction.value,
          rows: form.rows
        })
      }
    )

    if (response.ok) {
      isEditing.value = false
      // Recharger la page
      router.reload()
    }

  } catch (error) {
    console.error('Erreur sauvegarde:', error)
  } finally {
    isSaving.value = false
  }
}

/* ========== EXPORT EXCEL ========== */
function exportToExcel() {
  // Créer un formulaire pour exporter
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = `/m/process.core/process/${props.process.id}/contract/export`
  
  const csrfInput = document.createElement('input')
  csrfInput.name = '_token'
  csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.content
  form.appendChild(csrfInput)

  document.body.appendChild(form)
  form.submit()
  document.body.removeChild(form)
}

/* ========== NAVIGATION ========== */
function goBack() {
  router.visit(route('process.contracts.index'))
}

/* ========== LIFECYCLE ========== */
onMounted(() => {
  // Charger les données si des entités/fonctions sont disponibles
  if (props.entities.length > 0) {
    selectedEntity.value = props.entities[0].id
  }
})
</script>

<style scoped>
.contract-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 8px;
}

.contract-table-section .table {
  background: white;
}

.contract-table-section tbody tr:hover {
  background-color: #f8f9fa;
}

.data-row td {
  vertical-align: middle;
}

.resources-section {
  border-left: 4px solid #0d6efd;
  padding-left: 16px;
}

.form-control-sm,
.form-select-sm {
  font-size: 0.875rem;
}

textarea.form-control {
  resize: vertical;
  min-height: 50px;
}

table th {
  font-weight: 600;
  background-color: #f8f9fa;
}

.badge {
  font-size: 0.75rem;
  padding: 0.35em 0.6em;
}
</style>