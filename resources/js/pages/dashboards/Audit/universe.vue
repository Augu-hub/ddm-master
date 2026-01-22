<template>
  <div class="container-fluid py-2">
    <!-- HEADER -->
    <div class="mb-2">
      <h5 class="fw-bold text-dark mb-0">📋 Formulaire d'identification de l'Univers d'audits et risques</h5>
    </div>

    <!-- INFO MISSION - FILTRES -->
    <div class="card mb-2 border-0 shadow-sm" style="background-color: #f8f9fa;">
      <div class="card-header bg-info text-white fw-bold py-2" style="font-size: 0.9rem;">
        ℹ️ Info mission
      </div>
      <div class="card-body py-3">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label fw-bold small">Entité *</label>
            <select v-model.number="selectedEntity" class="form-select form-select-sm" @change="onFilterChange">
              <option :value="null">-- Sélectionner une entité --</option>
              <option v-for="e in entities" :key="e.id" :value="e.id">
                {{ e.code_base }} - {{ e.name }}
              </option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-bold small">Année *</label>
            <select v-model.number="selectedYear" class="form-select form-select-sm" @change="onFilterChange">
              <option :value="null">-- Sélectionner une année --</option>
              <option v-for="y in years" :key="y" :value="y">
                {{ y }}
              </option>
            </select>
          </div>

          <div class="col-md-3">
            <div class="form-check mt-4">
              <input 
                v-model="isSessionMode" 
                type="checkbox" 
                class="form-check-input"
                id="sessionModeCheck"
                style="width: 18px; height: 18px; cursor: pointer;"
              >
              <label class="form-check-label fw-bold small ms-2" for="sessionModeCheck">
                Mode Session DDM
              </label>
            </div>
          </div>

          <div class="col-md-3">
            <small class="text-muted d-block mb-2">{{ statusMessage }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- ALERTE DDM -->
    <div v-if="isSessionMode" class="alert alert-warning alert-dismissible fade show mb-2" role="alert">
      <strong>⚠️ Mode Session DDM Activé</strong><br>
      <small>Aucune donnée DDM trouvée. Les risques ne seront pas affichés. Vérifiez vos paramètres DDM.</small>
      <button type="button" class="btn-close" @click="isSessionMode = false"></button>
    </div>

    <!-- TABLEAU UNIVERS D'AUDIT - AVEC SCROLL GELÉ -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-primary text-white py-2">
        <div class="d-flex justify-content-between align-items-center" style="font-size: 0.9rem;">
          <h6 class="fw-bold mb-0">📊 UNIVERS D'AUDIT - {{ risks.length }} risque(s)</h6>
          <button 
            class="btn btn-sm btn-success" 
            @click="openCreateModal" 
            :disabled="!selectedEntity || !selectedYear || loading"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            ➕ Créer Risque
          </button>
        </div>
      </div>

      <!-- TABLEAU AVEC SCROLL GELÉ -->
      <div class="table-wrapper">
        <table class="table table-bordered table-sm mb-0 audit-table">
          <!-- EN-TÊTES GELÉS -->
          <thead class="table-header-fixed">
            <tr>
              <!-- COLONNES FIXES (STICKY LEFT) -->
              <th class="col-entity">ENTITÉ</th>
              <th class="col-process">PROCESSUS</th>
              <th class="col-activity">ACT</th>
              <th class="col-risk">RISQUE</th>

              <!-- COLONNES SCROLLABLES -->
              <th class="text-center">IMPACT<br>BRUT</th>
              <th class="text-center">FREQ<br>BRUT</th>
              <th class="text-center">GLOBAL<br>BRUT</th>
              <th>PROCÉDURE<br>CONTRÔLE</th>
              <th class="text-center">IMPACT<br>NET</th>
              <th class="text-center">FREQ<br>NET</th>
              <th class="text-center">GLOBAL<br>NET</th>
              <th class="text-center">CODE<br>NATURE</th>
              <th class="text-center">QUALIF<br>NATURE</th>
              <th class="text-center">EVAL<br>✓</th>
            </tr>
          </thead>

          <!-- RISQUES -->
          <tbody>
            <tr v-for="risk in risks" :key="risk.id" class="align-middle">
              <!-- COLONNES FIXES -->
              <td class="col-entity fw-bold small text-center text-dark">
                {{ getEntityName(risk.entity_id) }}
              </td>

              <td class="col-process small text-center text-dark" style="background-color: #ffffff; color: #000000;">
                <strong>{{ getProcessName(risk.process_id) }}</strong>
              </td>

              <td class="col-activity small text-center text-dark" style="background-color: #ffffff; color: #000000;">
                <strong>{{ getActivityName(risk.activity_id) }}</strong>
              </td>

              <td class="col-risk" style="background-color: #ffffff; color: #000000;">
                <small class="fw-bold d-block text-dark">{{ risk.code }}</small>
                <small class="text-dark">{{ truncate(risk.label, 30) }}</small>
              </td>

              <!-- IMPACT BRUT (BADGE AVEC COULEUR - LECTURE SEULE) -->
              <td class="text-center p-2">
                <span 
                  v-if="risk.impact_level" 
                  class="badge fw-bold text-white"
                  :style="{ 
                    backgroundColor: getColorFromPalette(risk.impact_color),
                    padding: '0.5rem 0.75rem'
                  }"
                >
                  {{ risk.impact_level }}
                </span>
                <span v-else class="badge bg-secondary small">-</span>
              </td>

              <!-- FRÉQUENCE BRUT (BADGE AVEC COULEUR - LECTURE SEULE) -->
              <td class="text-center p-2">
                <span 
                  v-if="risk.frequency_level" 
                  class="badge fw-bold text-white"
                  :style="{ 
                    backgroundColor: getColorFromPalette(risk.frequency_color),
                    padding: '0.5rem 0.75rem'
                  }"
                >
                  {{ risk.frequency_level }}
                </span>
                <span v-else class="badge bg-secondary small">-</span>
              </td>

              <!-- GLOBAL BRUT (CRITICITÉ) - LECTURE SEULE -->
              <td class="text-center p-2" :style="getCellBackground(risk.criticality)">
                <span 
                  v-if="risk.criticality" 
                  class="badge fw-bold text-white"
                  :style="{ 
                    backgroundColor: getCriticalityColor(risk.criticality),
                    padding: '0.5rem 0.75rem'
                  }"
                >
                  {{ risk.criticality }}
                </span>
                <span v-else class="badge bg-secondary small">-</span>
              </td>

              <!-- PROCÉDURE CONTRÔLE - LECTURE SEULE -->
              <td class="small text-muted">
                {{ truncate(risk.control_procedure, 25) }}
              </td>

              <!-- IMPACT NET (BADGE AVEC COULEUR - LECTURE SEULE) -->
              <td class="text-center p-2">
                <span 
                  v-if="risk.impact_net" 
                  class="badge fw-bold text-white"
                  :style="{ 
                    backgroundColor: getLevelColorFromValue(risk.impact_net),
                    padding: '0.5rem 0.75rem'
                  }"
                >
                  {{ risk.impact_net }}
                </span>
                <span v-else class="badge bg-secondary small">-</span>
              </td>

              <!-- FRÉQUENCE NET (BADGE AVEC COULEUR - LECTURE SEULE) -->
              <td class="text-center p-2">
                <span 
                  v-if="risk.frequency_net" 
                  class="badge fw-bold text-white"
                  :style="{ 
                    backgroundColor: getLevelColorFromValue(risk.frequency_net),
                    padding: '0.5rem 0.75rem'
                  }"
                >
                  {{ risk.frequency_net }}
                </span>
                <span v-else class="badge bg-secondary small">-</span>
              </td>

              <!-- GLOBAL NET (CALCULÉ & LECTURE SEULE) -->
              <td class="text-center p-2" :style="getCellBackground(getCriticalityNet(risk))">
                <span 
                  v-if="getCriticalityNet(risk)" 
                  class="badge fw-bold text-white"
                  :style="{ 
                    backgroundColor: getCriticalityColor(getCriticalityNet(risk)),
                    padding: '0.5rem 0.75rem'
                  }"
                >
                  {{ getCriticalityNet(risk) }}
                </span>
                <span v-else class="badge bg-secondary small">-</span>
              </td>

              <!-- CODE NATURE - LECTURE SEULE -->
              <td class="text-center small">
                {{ risk.control_nature_code || '-' }}
              </td>

              <!-- QUALIFICATION NATURE (AUTO) - LECTURE SEULE -->
              <td class="text-center p-2" :style="getCellBackground(null, getQualificationNet(risk))">
                <span 
                  v-if="getQualificationNet(risk)" 
                  class="badge fw-bold text-white"
                  :style="{ 
                    backgroundColor: getQualificationColor(getQualificationNet(risk)),
                    padding: '0.5rem 0.75rem'
                  }"
                >
                  {{ getQualificationNet(risk) }}
                </span>
                <span v-else class="badge bg-secondary small">-</span>
              </td>

              <!-- ÉVALUATION (CHECKBOX - SEULE MODIFICATION AUTORISÉE) -->
              <td class="text-center">
                <input 
                  v-model="risk.is_evaluated" 
                  type="checkbox"
                  class="form-check-input"
                  style="width: 18px; height: 18px; cursor: pointer;"
                  @change="saveRiskField(risk, 'is_evaluated')"
                >
              </td>
            </tr>

            <!-- AUCUN RÉSULTAT -->
            <tr v-if="risks.length === 0">
              <td colspan="14" class="p-4 text-center text-muted">
                <small v-if="!selectedEntity || !selectedYear">
                  📭 Sélectionnez une entité et une année pour charger les risques
                </small>
                <small v-else-if="loading">
                  ⏳ Chargement des risques...
                </small>
                <small v-else>
                  📭 Aucun risque. Créez en un nouveau.
                </small>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL CRÉER RISQUE -->
    <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-success text-white border-0 py-2">
            <h6 class="modal-title fw-bold">➕ Créer un Nouveau Risque</h6>
            <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
          </div>

          <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            <div class="row g-3">
              <div class="col-12">
                <h6 class="fw-bold text-success mb-2" style="font-size: 0.9rem;">
                  <span class="badge bg-success me-2">1</span> Contexte
                </h6>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Entité</label>
                <input 
                  type="text" 
                  class="form-control form-control-sm" 
                  :value="getEntityName(selectedEntity)" 
                  readonly
                >
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Année</label>
                <input 
                  type="text" 
                  class="form-control form-control-sm" 
                  :value="selectedYear" 
                  readonly
                >
              </div>

              <div class="col-12">
                <h6 class="fw-bold text-success mb-2 mt-2" style="font-size: 0.9rem;">
                  <span class="badge bg-success me-2">2</span> Détails
                </h6>
              </div>

              <div class="col-12">
                <label class="form-label fw-bold small">Libellé *</label>
                <input 
                  v-model="form.label" 
                  type="text" 
                  class="form-control form-control-sm" 
                  placeholder="Libellé du risque"
                >
              </div>

              <div class="col-12">
                <label class="form-label fw-bold small">Description</label>
                <textarea 
                  v-model="form.description" 
                  class="form-control form-control-sm" 
                  rows="2" 
                  placeholder="Description..."
                ></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Type Risque</label>
                <select v-model.number="form.risk_type_id" class="form-select form-select-sm">
                  <option :value="null">-- Sélectionner --</option>
                  <option v-for="t in riskTypes" :key="t.id" :value="t.id">
                    {{ t.code }} - {{ t.label }}
                  </option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Fréquence Brute</label>
                <select v-model.number="form.frequency_level_id" class="form-select form-select-sm">
                  <option :value="null">-- Sélectionner --</option>
                  <option v-for="f in frequencies" :key="f.id" :value="f.id">
                    {{ f.level }} - {{ f.label }}
                  </option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Impact Brut</label>
                <select v-model.number="form.impact_level_id" class="form-select form-select-sm">
                  <option :value="null">-- Sélectionner --</option>
                  <option v-for="i in impacts" :key="i.id" :value="i.id">
                    {{ i.level }} - {{ i.label }}
                  </option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Procédure Contrôle</label>
                <textarea 
                  v-model="form.control_procedure" 
                  class="form-control form-control-sm" 
                  rows="2" 
                  placeholder="Procédure..."
                ></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Processus</label>
                <select v-model.number="form.process_id" class="form-select form-select-sm">
                  <option :value="null">-- Sélectionner --</option>
                  <option v-for="p in processes" :key="p.id" :value="p.id">
                    {{ p.code }} - {{ p.name }}
                  </option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Activité</label>
                <select v-model.number="form.activity_id" class="form-select form-select-sm">
                  <option :value="null">-- Sélectionner --</option>
                  <option v-for="a in activities" :key="a.id" :value="a.id">
                    {{ a.code }} - {{ a.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light border-top py-2">
            <button @click="closeModal" class="btn btn-secondary btn-sm">❌ Annuler</button>
            <button 
              @click="createRisk" 
              class="btn btn-success btn-sm" 
              :disabled="!form.label || loading"
            >
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              💾 Créer
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
  entities: { type: Array, default: () => [] },
  processes: { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
  riskTypes: { type: Array, default: () => [] },
  frequencies: { type: Array, default: () => [] },
  impacts: { type: Array, default: () => [] },
  matrix: { type: Array, default: () => [] },
  initialRisks: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
})

// Palette de couleurs (Bootstrap)
const colorPalette = ref({
  danger: '#dc3545',
  warning: '#ffc107',
  info: '#0dcaf0',
  success: '#28a745',
  secondary: '#6c757d',
  primary: '#0d6efd'
})

// STATE
const selectedEntity = ref(null)
const selectedYear = ref(null)
const risks = ref([])
const showModal = ref(false)
const loading = ref(false)
const isSessionMode = ref(false)

const form = ref({
  label: '',
  description: '',
  risk_type_id: null,
  frequency_level_id: null,
  impact_level_id: null,
  process_id: null,
  activity_id: null,
  control_procedure: '',
})

const statusMessage = computed(() => {
  if (!selectedEntity.value || !selectedYear.value) return '⏳ Sélectionnez une entité et une année'
  if (loading.value) return '⏳ Chargement...'
  return `✅ ${risks.value.length} risque(s) chargé(s)`
})

onMounted(() => {
  risks.value = props.initialRisks || []
})

// ════════════════════════════════════════════════════════════════════════════════════
// METHODS - CHARGEMENT & FILTRAGE
// ════════════════════════════════════════════════════════════════════════════════════

const onFilterChange = async () => {
  if (selectedEntity.value && selectedYear.value) {
    await loadRisks()
  } else {
    risks.value = []
  }
}

const loadRisks = async () => {
  try {
    if (isSessionMode.value) {
      risks.value = []
      return
    }

    loading.value = true
    const response = await fetch('/api/audit/universe/load-risks', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({
        entity_id: selectedEntity.value,
        year: selectedYear.value
      })
    })

    if (response.ok) {
      const data = await response.json()
      risks.value = data.risks || []
      
      // Mise à jour palette couleurs depuis backend
      if (data.colors) {
        colorPalette.value = { ...colorPalette.value, ...data.colors }
      }
    } else {
      console.error('Erreur chargement risques')
    }
  } catch (error) {
    console.error('Error loading risks:', error)
  } finally {
    loading.value = false
  }
}

const saveRiskField = async (risk, field) => {
  try {
    await fetch(`/api/audit/universe/update-risk/${risk.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({ field, value: risk[field] })
    })
  } catch (error) {
    console.error('Error saving field:', error)
  }
}

const openCreateModal = () => {
  form.value = {
    label: '',
    description: '',
    risk_type_id: null,
    frequency_level_id: null,
    impact_level_id: null,
    process_id: null,
    activity_id: null,
    control_procedure: '',
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

const createRisk = async () => {
  if (!form.value.label) return

  try {
    loading.value = true
    const response = await fetch('/api/audit/universe/create-risk', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({
        ...form.value,
        entity_id: selectedEntity.value,
        year: selectedYear.value
      })
    })

    if (response.ok) {
      const data = await response.json()
      risks.value.push(data.risk)
      closeModal()
      alert('✅ Risque créé avec succès')
      await loadRisks()
    } else {
      const error = await response.json()
      alert('❌ Erreur: ' + (error.message || 'Une erreur est survenue'))
    }
  } catch (error) {
    console.error('Error:', error)
    alert('❌ Erreur réseau')
  } finally {
    loading.value = false
  }
}

// ════════════════════════════════════════════════════════════════════════════════════
// HELPERS - LOGIQUE MÉTIER & COULEURS
// ════════════════════════════════════════════════════════════════════════════════════

/**
 * Convertit le nom de couleur (danger, warning, etc) en code hex
 */
const getColorFromPalette = (colorName) => {
  if (!colorName) return colorPalette.value.secondary
  return colorPalette.value[colorName] || colorPalette.value.secondary
}

/**
 * Couleur par niveau numérique (0-5)
 * 0-2 = vert, 2.5-3.5 = orange, 4-5 = rouge
 */
const getLevelColorFromValue = (value) => {
  if (!value) return colorPalette.value.secondary
  if (value <= 2) return colorPalette.value.success      // Vert
  if (value <= 3.5) return colorPalette.value.warning    // Orange
  return colorPalette.value.danger                       // Rouge
}

/**
 * Couleur pour criticité (brute ou nette)
 * 1-4 = vert, 5-9 = orange, 10+ = rouge
 */
const getCriticalityColor = (criticality) => {
  if (!criticality) return colorPalette.value.secondary
  if (criticality <= 4) return colorPalette.value.success      // Vert
  if (criticality <= 9) return colorPalette.value.warning      // Orange
  return colorPalette.value.danger                             // Rouge
}

/**
 * Couleur par qualification (SYSCOHADA)
 */
const getQualificationColor = (qual) => {
  const q = qual?.toUpperCase() || ''
  if (q === 'ACCEPTABLE') return colorPalette.value.success
  if (q === 'EFFICACE') return colorPalette.value.info
  if (q === 'A RENFORCER') return colorPalette.value.warning
  if (q === 'INACCEPTABLE') return colorPalette.value.danger
  return colorPalette.value.secondary
}

/**
 * Style de fond légère pour les cellules
 */
const getCellBackground = (numValue, qual) => {
  let color = 'transparent'

  if (numValue) {
    const col = getCriticalityColor(numValue)
    color = col + '1a'
  } else if (qual) {
    const col = getQualificationColor(qual)
    color = col + '1a'
  }

  return { backgroundColor: color }
}

/**
 * Calcule la criticité NETTE
 */
const getCriticalityNet = (risk) => {
  if (!risk.frequency_net || !risk.impact_net) return null
  const net = Math.round((risk.frequency_net * risk.impact_net) * 10) / 10
  return net > 0 ? net : null
}

/**
 * Récupère la qualification NETTE depuis la matrice
 */
const getQualificationNet = (risk) => {
  if (!risk.impact_net || !risk.frequency_net) return null

  const criticalityNet = getCriticalityNet(risk)
  if (!criticalityNet) return null

  const matrixEntry = props.matrix?.find(m => {
    const matrixCrit = m.frequency_level * m.impact_level
    return Math.abs(matrixCrit - criticalityNet) < 0.1
  })

  return matrixEntry?.qualification || null
}

/**
 * Récupère le nom de l'entité
 */
const getEntityName = (id) => {
  const entity = props.entities?.find(e => e.id === id)
  return entity ? entity.code_base : '-'
}

/**
 * Récupère le nom du processus
 */
const getProcessName = (id) => {
  const process = props.processes?.find(p => p.id === id)
  return process ? process.code : '-'
}

/**
 * Récupère le nom de l'activité
 */
const getActivityName = (id) => {
  const activity = props.activities?.find(a => a.id === id)
  return activity ? activity.code : '-'
}

/**
 * Tronque le texte
 */
const truncate = (text, len) => {
  if (!text) return '-'
  return text.length > len ? text.substring(0, len) + '...' : text
}
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════════════════════════
   STYLES TABLEAU AVEC SCROLL GELÉ
   ════════════════════════════════════════════════════════════════════════════════════ */

.table-wrapper {
  max-height: 600px;
  overflow-y: auto;
  overflow-x: auto;
  border: 1px solid #dee2e6;
  position: relative;
}

.audit-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: white;
}

/* EN-TÊTES GELÉS EN HAUT */
.table-header-fixed {
  position: sticky;
  top: 0;
  z-index: 20;
  background-color: #0d6efd;
  color: white;
}

.table-header-fixed tr {
  background-color: #0d6efd;
  color: white;
}

.table-header-fixed th {
  padding: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid #0a58ca;
  text-align: center;
  background-color: #0d6efd;
  color: white;
  white-space: nowrap;
}

/* COLONNES FIXES (STICKY LEFT) */
.col-entity,
.col-process,
.col-activity,
.col-risk {
  position: sticky;
  background-color: #0d6efd;
  color: white;
  z-index: 21;
}

.col-entity {
  left: 0;
  width: 80px;
}

.col-process {
  left: 80px;
  width: 100px;
}

.col-activity {
  left: 180px;
  width: 60px;
}

.col-risk {
  left: 240px;
  width: 180px;
}

/* CORPS DU TABLEAU */
.audit-table tbody td {
  padding: 0.5rem;
  font-size: 0.75rem;
  border: 1px solid #dee2e6;
  vertical-align: middle;
}

/* COLONNES FIXES DANS LES LIGNES */
.audit-table tbody td.col-entity,
.audit-table tbody td.col-process,
.audit-table tbody td.col-activity,
.audit-table tbody td.col-risk {
  position: sticky;
  background-color: #ffffff;
  z-index: 10;
  color: #000000;
}

.audit-table tbody td.col-entity {
  left: 0;
  width: 80px;
  font-weight: 600;
  color: #1a1a1a;
  background-color: #ffffff;
}

.audit-table tbody td.col-process {
  left: 80px;
  width: 100px;
  color: #000000;
  background-color: #ffffff;
  font-weight: 500;
}

.audit-table tbody td.col-activity {
  left: 180px;
  width: 60px;
  color: #000000;
  background-color: #ffffff;
  font-weight: 500;
}

.audit-table tbody td.col-risk {
  left: 240px;
  width: 180px;
  color: #000000;
  background-color: #ffffff;
}

/* HOVER LIGNES */
.audit-table tbody tr:hover {
  background-color: #f8f9fa;
}

/* MODAL */
.modal {
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.modal-dialog {
  max-width: 600px;
  width: 90%;
}

/* BADGES */
.badge {
  padding: 0.35rem 0.65rem;
  font-size: 0.7rem;
}

/* TEXTE PETITE TAILLE */
small {
  font-size: 0.75rem;
}
</style>