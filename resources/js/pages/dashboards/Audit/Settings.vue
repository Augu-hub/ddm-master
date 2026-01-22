<template>
  <div class="container-fluid py-4">
    <!-- 📊 HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold text-warning mb-0">⚙️ Paramètres</h2>
        <small class="text-muted">Configuration du module Risques</small>
      </div>
      <button class="btn btn-warning" @click="refreshStats">
        🔄 Actualiser
      </button>
    </div>

    <!-- 📈 STATISTIQUES GLOBALES -->
    <div class="row g-3 mb-4">
      <div class="col-md-2">
        <div class="card border-0 shadow-sm bg-primary text-white">
          <div class="card-body text-center">
            <h5>{{ stats.total_risk_types }}</h5>
            <small>Types de Risques</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm bg-info text-white">
          <div class="card-body text-center">
            <h5>{{ stats.total_frequencies }}</h5>
            <small>Niveaux Fréquence</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm bg-warning text-dark">
          <div class="card-body text-center">
            <h5>{{ stats.total_impacts }}</h5>
            <small>Niveaux Impact</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm bg-danger text-white">
          <div class="card-body text-center">
            <h5>{{ stats.total_entities }}</h5>
            <small>Entités</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm bg-success text-white">
          <div class="card-body text-center">
            <h5>{{ stats.total_processes }}</h5>
            <small>Processus</small>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm bg-secondary text-white">
          <div class="card-body text-center">
            <h5>{{ stats.total_risks }}</h5>
            <small>Risques Total</small>
          </div>
        </div>
      </div>
    </div>

    <!-- 🗂️ ONGLETS -->
    <ul class="nav nav-tabs mb-4" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#risk-types" role="tab">
          📋 Types de Risques
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#frequencies" role="tab">
          📊 Fréquences
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#impacts" role="tab">
          ⚡ Impacts
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#entities" role="tab">
          🏛️ Entités
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#processes" role="tab">
          ⚙️ Processus
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#activities" role="tab">
          📌 Activités
        </a>
      </li>
    </ul>

    <!-- CONTENU ONGLETS -->
    <div class="tab-content">
      <!-- 📋 TYPES DE RISQUES -->
      <div class="tab-pane fade show active" id="risk-types" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">📋 Types de Risques</h6>
            <button class="btn btn-light btn-sm" @click="openModal('riskType')">➕ Ajouter</button>
          </div>
          <div class="card-body">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th>Code</th>
                  <th>Label</th>
                  <th>Description</th>
                  <th>Couleur</th>
                  <th>Actif</th>
                  <th>Ordre</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="type in riskTypes" :key="type.id">
                  <td><strong>{{ type.code }}</strong></td>
                  <td>{{ type.label }}</td>
                  <td><small>{{ truncate(type.description, 40) }}</small></td>
                  <td>
                    <span class="badge" :style="{ backgroundColor: getColorValue(type.color) }">
                      {{ type.color }}
                    </span>
                  </td>
                  <td>
                    <span v-if="type.is_active" class="badge bg-success">Oui</span>
                    <span v-else class="badge bg-danger">Non</span>
                  </td>
                  <td>{{ type.sort_order }}</td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="editRiskType(type)">✏️</button>
                    <button class="btn btn-sm btn-outline-danger" @click="deleteItem('riskType', type.id)">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 📊 FRÉQUENCES -->
      <div class="tab-pane fade" id="frequencies" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">📊 Niveaux de Fréquence</h6>
            <button class="btn btn-light btn-sm" @click="openModal('frequency')">➕ Ajouter</button>
          </div>
          <div class="card-body">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th>Level</th>
                  <th>Label</th>
                  <th>Description</th>
                  <th>Couleur</th>
                  <th>Risques</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="freq in frequencies" :key="freq.id">
                  <td><strong>{{ freq.level }}/5</strong></td>
                  <td>{{ freq.label }}</td>
                  <td><small>{{ truncate(freq.description, 40) }}</small></td>
                  <td>
                    <span class="badge" :style="{ backgroundColor: getColorValue(freq.color) }">
                      {{ freq.color }}
                    </span>
                  </td>
                  <td><span class="badge bg-secondary">{{ countUsage('frequency', freq.id) }}</span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="editFrequency(freq)">✏️</button>
                    <button class="btn btn-sm btn-outline-danger" @click="deleteItem('frequency', freq.id)">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ⚡ IMPACTS -->
      <div class="tab-pane fade" id="impacts" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">⚡ Niveaux d'Impact</h6>
            <button class="btn btn-light btn-sm" @click="openModal('impact')">➕ Ajouter</button>
          </div>
          <div class="card-body">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th>Level</th>
                  <th>Label</th>
                  <th>Description</th>
                  <th>Couleur</th>
                  <th>Risques</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="impact in impacts" :key="impact.id">
                  <td><strong>{{ impact.level }}/5</strong></td>
                  <td>{{ impact.label }}</td>
                  <td><small>{{ truncate(impact.description, 40) }}</small></td>
                  <td>
                    <span class="badge" :style="{ backgroundColor: getColorValue(impact.color) }">
                      {{ impact.color }}
                    </span>
                  </td>
                  <td><span class="badge bg-secondary">{{ countUsage('impact', impact.id) }}</span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="editImpact(impact)">✏️</button>
                    <button class="btn btn-sm btn-outline-danger" @click="deleteItem('impact', impact.id)">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 🏛️ ENTITÉS -->
      <div class="tab-pane fade" id="entities" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">🏛️ Entités</h6>
            <button class="btn btn-light btn-sm" @click="openModal('entity')">➕ Ajouter</button>
          </div>
          <div class="card-body">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th>Code</th>
                  <th>Nom</th>
                  <th>Description</th>
                  <th>Level</th>
                  <th>Risques</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="entity in entities" :key="entity.id">
                  <td><strong>{{ entity.code_base }}</strong></td>
                  <td>{{ entity.name }}</td>
                  <td><small>{{ truncate(entity.description, 40) }}</small></td>
                  <td>
                    <span class="badge bg-light text-dark">{{ entity.level }}</span>
                  </td>
                  <td><span class="badge bg-secondary">{{ countUsage('entity', entity.id) }}</span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="editEntity(entity)">✏️</button>
                    <button class="btn btn-sm btn-outline-danger" @click="deleteItem('entity', entity.id)">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ⚙️ PROCESSUS -->
      <div class="tab-pane fade" id="processes" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">⚙️ Processus</h6>
            <button class="btn btn-light btn-sm" @click="openModal('process')">➕ Ajouter</button>
          </div>
          <div class="card-body">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th>Code</th>
                  <th>Nom</th>
                  <th>Description</th>
                  <th>Entité</th>
                  <th>Activités</th>
                  <th>Risques</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="process in processes" :key="process.id">
                  <td><strong>{{ process.code }}</strong></td>
                  <td>{{ process.name }}</td>
                  <td><small>{{ truncate(process.description, 30) }}</small></td>
                  <td>
                    <span class="badge bg-light text-dark">
                      {{ getEntityName(process.entity_id) }}
                    </span>
                  </td>
                  <td><span class="badge bg-secondary">{{ countActivities(process.id) }}</span></td>
                  <td><span class="badge bg-secondary">{{ countUsage('process', process.id) }}</span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="editProcess(process)">✏️</button>
                    <button class="btn btn-sm btn-outline-danger" @click="deleteItem('process', process.id)">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 📌 ACTIVITÉS -->
      <div class="tab-pane fade" id="activities" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">📌 Activités</h6>
            <button class="btn btn-light btn-sm" @click="openModal('activity')">➕ Ajouter</button>
          </div>
          <div class="card-body">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th>Code</th>
                  <th>Nom</th>
                  <th>Description</th>
                  <th>Processus</th>
                  <th>Risques</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="activity in activities" :key="activity.id">
                  <td><strong>{{ activity.code }}</strong></td>
                  <td>{{ activity.name }}</td>
                  <td><small>{{ truncate(activity.description, 30) }}</small></td>
                  <td>
                    <span class="badge bg-light text-dark">
                      {{ getProcessName(activity.process_id) }}
                    </span>
                  </td>
                  <td><span class="badge bg-secondary">{{ countUsage('activity', activity.id) }}</span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="editActivity(activity)">✏️</button>
                    <button class="btn btn-sm btn-outline-danger" @click="deleteItem('activity', activity.id)">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 🎯 MODAL AJOUT/MODIFICATION -->
    <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
          
          <div class="modal-header bg-warning text-dark border-0 py-3">
            <h5 class="modal-title fw-bold">
              {{ editingItem ? '✏️ Modifier' : '➕ Ajouter' }} {{ modalTitle }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>

          <div class="modal-body">
            <!-- RISK TYPE FORM -->
            <div v-if="currentForm === 'riskType'" class="row g-3">
              <div class="col-12">
                <label class="form-label fw-bold">Code</label>
                <input v-model="formData.code" type="text" class="form-control form-control-sm" placeholder="EX: O">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Label</label>
                <input v-model="formData.label" type="text" class="form-control form-control-sm" placeholder="Opérationnel">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Couleur</label>
                <select v-model="formData.color" class="form-select form-select-sm">
                  <option value="danger">Danger (Rouge)</option>
                  <option value="warning">Warning (Orange)</option>
                  <option value="info">Info (Bleu)</option>
                  <option value="success">Success (Vert)</option>
                  <option value="secondary">Secondary (Gris)</option>
                  <option value="primary">Primary (Bleu foncé)</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Ordre</label>
                <input v-model.number="formData.sort_order" type="number" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input v-model="formData.is_active" type="checkbox" class="form-check-input" id="active">
                  <label class="form-check-label" for="active">Actif</label>
                </div>
              </div>
            </div>

            <!-- FREQUENCY FORM -->
            <div v-else-if="currentForm === 'frequency'" class="row g-3">
              <div class="col-6">
                <label class="form-label fw-bold">Niveau (1-5)</label>
                <input v-model.number="formData.level" type="number" min="1" max="5" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Label</label>
                <input v-model="formData.label" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Couleur</label>
                <select v-model="formData.color" class="form-select form-select-sm">
                  <option value="danger">Danger (Rouge)</option>
                  <option value="warning">Warning (Orange)</option>
                  <option value="info">Info (Bleu)</option>
                  <option value="success">Success (Vert)</option>
                  <option value="secondary">Secondary (Gris)</option>
                </select>
              </div>
            </div>

            <!-- IMPACT FORM -->
            <div v-else-if="currentForm === 'impact'" class="row g-3">
              <div class="col-6">
                <label class="form-label fw-bold">Niveau (1-5)</label>
                <input v-model.number="formData.level" type="number" min="1" max="5" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Label</label>
                <input v-model="formData.label" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Couleur</label>
                <select v-model="formData.color" class="form-select form-select-sm">
                  <option value="danger">Danger (Rouge)</option>
                  <option value="warning">Warning (Orange)</option>
                  <option value="info">Info (Bleu)</option>
                  <option value="success">Success (Vert)</option>
                  <option value="secondary">Secondary (Gris)</option>
                </select>
              </div>
            </div>

            <!-- ENTITY FORM -->
            <div v-else-if="currentForm === 'entity'" class="row g-3">
              <div class="col-6">
                <label class="form-label fw-bold">Code</label>
                <input v-model="formData.code_base" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label fw-bold">Niveau</label>
                <input v-model.number="formData.level" type="number" min="0" max="5" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Nom</label>
                <input v-model="formData.name" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>

            <!-- PROCESS FORM -->
            <div v-else-if="currentForm === 'process'" class="row g-3">
              <div class="col-12">
                <label class="form-label fw-bold">Code</label>
                <input v-model="formData.code" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Nom</label>
                <input v-model="formData.name" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Entité</label>
                <select v-model.number="formData.entity_id" class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>

            <!-- ACTIVITY FORM -->
            <div v-else-if="currentForm === 'activity'" class="row g-3">
              <div class="col-12">
                <label class="form-label fw-bold">Code</label>
                <input v-model="formData.code" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Nom</label>
                <input v-model="formData.name" type="text" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Processus</label>
                <select v-model.number="formData.process_id" class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="p in processes" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea v-model="formData.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light border-top">
            <button @click="closeModal" type="button" class="btn btn-secondary btn-sm">❌ Annuler</button>
            <button @click="saveItem" type="button" class="btn btn-warning btn-sm" :disabled="loading">
              {{ loading ? '⏳' : '💾' }} {{ editingItem ? 'Modifier' : 'Ajouter' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  riskTypes: { type: Array, default: () => [] },
  frequencies: { type: Array, default: () => [] },
  impacts: { type: Array, default: () => [] },
  entities: { type: Array, default: () => [] },
  processes: { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
  stats: { type: Object, default: () => {} },
})

const showModal = ref(false)
const currentForm = ref('')
const editingItem = ref(null)
const loading = ref(false)
const modalTitle = ref('')

const stats = ref(props.stats)
const riskTypes = ref(props.riskTypes)
const frequencies = ref(props.frequencies)
const impacts = ref(props.impacts)
const entities = ref(props.entities)
const processes = ref(props.processes)
const activities = ref(props.activities)

const formData = ref({})

// METHODS
const openModal = (form) => {
  currentForm.value = form
  editingItem.value = null
  modalTitle.value = getModalTitle(form)
  formData.value = {}
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  currentForm.value = ''
  editingItem.value = null
}

const getModalTitle = (form) => {
  const titles = {
    riskType: 'Type de Risque',
    frequency: 'Niveau de Fréquence',
    impact: 'Niveau d\'Impact',
    entity: 'Entité',
    process: 'Processus',
    activity: 'Activité'
  }
  return titles[form] || ''
}

const editRiskType = (type) => {
  currentForm.value = 'riskType'
  editingItem.value = type
  modalTitle.value = 'Type de Risque'
  formData.value = { ...type }
  showModal.value = true
}

const editFrequency = (freq) => {
  currentForm.value = 'frequency'
  editingItem.value = freq
  modalTitle.value = 'Niveau de Fréquence'
  formData.value = { ...freq }
  showModal.value = true
}

const editImpact = (impact) => {
  currentForm.value = 'impact'
  editingItem.value = impact
  modalTitle.value = 'Niveau d\'Impact'
  formData.value = { ...impact }
  showModal.value = true
}

const editEntity = (entity) => {
  currentForm.value = 'entity'
  editingItem.value = entity
  modalTitle.value = 'Entité'
  formData.value = { ...entity }
  showModal.value = true
}

const editProcess = (process) => {
  currentForm.value = 'process'
  editingItem.value = process
  modalTitle.value = 'Processus'
  formData.value = { ...process }
  showModal.value = true
}

const editActivity = (activity) => {
  currentForm.value = 'activity'
  editingItem.value = activity
  modalTitle.value = 'Activité'
  formData.value = { ...activity }
  showModal.value = true
}

const saveItem = async () => {
  if (!formData.value.label && !formData.value.name && !formData.value.code_base && !formData.value.code) {
    alert('Veuillez remplir les champs obligatoires')
    return
  }

  loading.value = true
  try {
    const endpoints = {
      riskType: '/api/settings/risk-types',
      frequency: '/api/settings/frequencies',
      impact: '/api/settings/impacts',
      entity: '/api/settings/entities',
      process: '/api/settings/processes',
      activity: '/api/settings/activities'
    }

    const endpoint = endpoints[currentForm.value]
    const method = editingItem.value ? 'PUT' : 'POST'
    const url = editingItem.value ? `${endpoint}/${editingItem.value.id}` : endpoint

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(formData.value)
    })

    if (response.ok) {
      const data = await response.json()
      alert(data.message || 'Opération réussie')
      closeModal()
      location.reload()
    } else {
      const error = await response.json()
      alert('Erreur: ' + (error.error || error.message))
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur lors de l\'enregistrement')
  } finally {
    loading.value = false
  }
}

const deleteItem = async (type, id) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer?')) return

  try {
    const endpoints = {
      riskType: '/api/settings/risk-types',
      frequency: '/api/settings/frequencies',
      impact: '/api/settings/impacts',
      entity: '/api/settings/entities',
      process: '/api/settings/processes',
      activity: '/api/settings/activities'
    }

    const response = await fetch(`${endpoints[type]}/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
    })

    if (response.ok) {
      alert('Supprimé avec succès')
      location.reload()
    } else {
      const error = await response.json()
      alert('Erreur: ' + error.error)
    }
  } catch (error) {
    console.error('Erreur:', error)
  }
}

const refreshStats = async () => {
  try {
    const response = await fetch('/api/settings/stats')
    if (response.ok) {
      const data = await response.json()
      stats.value = data.stats
    }
  } catch (error) {
    console.error('Erreur refresh:', error)
  }
}

const getColorValue = (color) => {
  const colors = {
    danger: '#dc3545',
    warning: '#ffc107',
    info: '#0dcaf0',
    success: '#198754',
    secondary: '#6c757d',
    primary: '#0d6efd'
  }
  return colors[color] || '#6c757d'
}

const countUsage = (type, id) => {
  // Placeholder - à remplacer par les vraies stats
  return 0
}

const countActivities = (processId) => {
  return activities.value.filter(a => a.process_id === processId).length
}

const getEntityName = (entityId) => {
  const entity = entities.value.find(e => e.id === entityId)
  return entity?.name || '-'
}

const getProcessName = (processId) => {
  const process = processes.value.find(p => p.id === processId)
  return process?.name || '-'
}

const truncate = (text, len) => {
  if (!text) return '-'
  return text.length > len ? text.substring(0, len) + '...' : text
}
</script>

<style scoped>
.modal {
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>