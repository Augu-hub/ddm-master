<template>
  <div class="factors-year-wrapper">
    <!-- HEADER -->
    <div class="header-section">
      <div class="header-content">
        <div>
          <h1 class="page-title">🎯 Facteurs d'Audit par Année</h1>
          <p class="page-subtitle">Liaison des facteurs avec les années d'exercice</p>
        </div>
      </div>
    </div>

    <!-- SÉLECTION ANNÉE -->
    <div class="year-selector">
      <div class="selector-container">
        <label class="selector-label">📅 Année d'exercice:</label>
        <select v-model.number="selectedYear" @change="loadFactorsForYear" class="year-select">
          <option :value="null">-- Sélectionner une année --</option>
          <option v-for="year in availableYears" :key="year" :value="year">
            Année {{ year }}
          </option>
        </select>
      </div>

      <div class="selector-info" v-if="selectedYear">
        <span class="info-badge">
          📊 {{ factorsForSelectedYear.length }} facteurs liés
        </span>
      </div>
    </div>

    <!-- CONTENU PRINCIPAL -->
    <div class="main-content">
      <!-- SECTION 1: FACTEURS POUR L'ANNÉE SÉLECTIONNÉE -->
      <div v-if="selectedYear" class="factors-section">
        <div class="section-header">
          <h2>Facteurs pour l'année {{ selectedYear }}</h2>
          <button @click="showAddModal = true" class="btn-add-factor" v-if="!showAddModal">
            ➕ Ajouter un facteur
          </button>
        </div>

        <!-- TABLEAU FACTEURS ANNÉE -->
        <div v-if="factorsForSelectedYear.length > 0" class="table-container">
          <table class="factors-table">
            <thead>
              <tr>
                <th class="col-order">Ordre</th>
                <th class="col-label">Libellé</th>
                <th class="col-description">Description</th>
                <th class="col-importance">Importance</th>
                <th class="col-weight">Poids</th>
                <th class="col-count">Évaluations</th>
                <th class="col-avg-score">Score Moyen</th>
                <th class="col-action">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="factor in sortedFactorsForYear" :key="factor.id" class="factor-row">
                <td class="col-order">{{ factor.order_position }}</td>
                <td class="col-label">
                  <span class="factor-label">{{ factor.label }}</span>
                </td>
                <td class="col-description">
                  <span class="factor-desc">{{ truncate(factor.description, 60) }}</span>
                </td>
                <td class="col-importance">
                  <span :class="['importance-badge', `imp-${factor.importance}`]">
                    {{ getImportanceLabel(factor.importance) }}
                  </span>
                </td>
                <td class="col-weight">
                  <span class="weight-value">{{ factor.weight }}</span>
                </td>
                <td class="col-count">
                  <span class="count-badge">{{ factor.evaluations_count }}</span>
                </td>
                <td class="col-avg-score">
                  <span v-if="factor.average_score !== undefined" class="avg-score">
                    {{ factor.average_score.toFixed(2) }}/5
                  </span>
                  <span v-else class="avg-score empty">—</span>
                </td>
                <td class="col-action">
                  <button
                    @click="removeFactorFromYear(factor.id, selectedYear)"
                    class="btn-remove"
                    title="Retirer"
                  >
                    ❌
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ÉTAT VIDE -->
        <div v-else class="empty-state">
          <p class="empty-icon">📭</p>
          <p class="empty-message">Aucun facteur lié à cette année</p>
          <button @click="showAddModal = true" class="btn-add-first">
            ➕ Ajouter le premier facteur
          </button>
        </div>
      </div>

      <!-- MODAL AJOUT FACTEUR -->
      <div v-if="showAddModal && selectedYear" class="modal-overlay" @click.self="closeAddModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Ajouter un facteur à l'année {{ selectedYear }}</h3>
            <button @click="closeAddModal" class="btn-close">✕</button>
          </div>

          <div class="modal-body">
            <!-- FILTRAGE FACTEURS DISPONIBLES -->
            <div class="form-group">
              <label class="form-label">🎯 Facteur</label>
              <input
                v-model="searchFactors"
                type="text"
                placeholder="Rechercher un facteur..."
                class="form-input search-input"
              />
            </div>

            <!-- LISTE FACTEURS DISPONIBLES -->
            <div class="available-factors">
              <div class="factors-list-header">
                Facteurs disponibles ({{ availableFactorsForAdd.length }})
              </div>

              <div v-if="availableFactorsForAdd.length > 0" class="factors-list">
                <div
                  v-for="factor in availableFactorsForAdd"
                  :key="factor.id"
                  class="factor-item"
                  @click="selectFactorToAdd(factor)"
                  :class="{ selected: selectedFactorToAdd?.id === factor.id }"
                >
                  <div class="factor-item-header">
                    <span class="factor-order">{{ factor.order_position }}</span>
                    <span class="factor-title">{{ factor.label }}</span>
                  </div>
                  <div class="factor-item-meta">
                    <span class="weight-tag">Poids: {{ factor.weight }}</span>
                    <span class="importance-tag">Importance: {{ getImportanceLabel(factor.importance) }}</span>
                  </div>
                </div>
              </div>

              <div v-else class="empty-list">
                Tous les facteurs sont déjà liés à cette année
              </div>
            </div>

            <!-- FORMULAIRE ÉVALUATION -->
            <div v-if="selectedFactorToAdd" class="evaluation-form">
              <div class="form-divider">Paramètres d'évaluation</div>

              <div class="form-group">
                <label class="form-label">📊 Score initial</label>
                <div class="score-selector">
                  <button
                    v-for="n in 5"
                    :key="n"
                    @click="newEvaluation.score = n"
                    :class="['score-btn', { active: newEvaluation.score === n }]"
                  >
                    {{ n }}
                  </button>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">📝 Justification (optionnel)</label>
                <textarea
                  v-model="newEvaluation.justification"
                  placeholder="Raison de cette liaison..."
                  rows="2"
                  class="form-textarea"
                ></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeAddModal" class="btn-cancel">
              Annuler
            </button>
            <button
              @click="addFactorToYear"
              class="btn-confirm"
              :disabled="!selectedFactorToAdd || addingFactor"
            >
              {{ addingFactor ? '⏳ Ajout...' : '✅ Ajouter' }}
            </button>
          </div>
        </div>
      </div>

      <!-- SECTION 2: VUE COMPARÉE ANNÉES (EN OPTION) -->
      <div v-if="availableYears.length > 1" class="comparison-section">
        <div class="section-header">
          <h2>📊 Comparaison Multi-Années</h2>
        </div>

        <div class="comparison-grid">
          <div v-for="year in availableYears" :key="year" class="year-card">
            <div class="year-card-header">
              <h3>{{ year }}</h3>
              <span class="year-count">{{ factorsByYear[year]?.count || 0 }} facteurs</span>
            </div>

            <div class="year-card-factors">
              <div
                v-for="factor in factorsByYear[year]?.factors"
                :key="factor.id"
                class="factor-mini"
              >
                <span class="factor-mini-name">{{ factor.label }}</span>
                <span class="factor-mini-weight">{{ factor.weight }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LÉGENDE -->
    <div class="legend-section">
      <h3>📖 Légende des Importances</h3>
      <div class="legend-grid">
        <div class="legend-item">
          <span class="legend-color imp-1"></span>
          <span>1 = Très faible</span>
        </div>
        <div class="legend-item">
          <span class="legend-color imp-2"></span>
          <span>2 = Faible</span>
        </div>
        <div class="legend-item">
          <span class="legend-color imp-3"></span>
          <span>3 = Moyen</span>
        </div>
        <div class="legend-item">
          <span class="legend-color imp-4"></span>
          <span>4 = Important</span>
        </div>
        <div class="legend-item">
          <span class="legend-color imp-5"></span>
          <span>5 = Très important</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  availableYears: Array,
  factorsByYear: Object,
  allFactors: Array,
})

// ✅ STATE
const selectedYear = ref(null)
const showAddModal = ref(false)
const searchFactors = ref('')
const selectedFactorToAdd = ref(null)
const addingFactor = ref(false)
const removingFactor = ref(null)
const factorsForSelectedYear = ref([])

const newEvaluation = ref({
  score: 3,
  justification: '',
})

// ✅ UTILITAIRES
const truncate = (text, length) => {
  return text?.length > length ? text.substring(0, length) + '...' : text
}

const getImportanceLabel = (importance) => {
  const labels = {
    1: 'Très faible',
    2: 'Faible',
    3: 'Moyen',
    4: 'Important',
    5: 'Très important',
  }
  return labels[importance] || 'Inconnu'
}

// ✅ COMPUTED
const sortedFactorsForYear = computed(() => {
  return [...factorsForSelectedYear.value].sort((a, b) => a.order_position - b.order_position)
})

const alreadyLinkedFactors = computed(() => {
  return factorsForSelectedYear.value.map(f => f.id)
})

const availableFactorsForAdd = computed(() => {
  return (props.allFactors || [])
    .filter(f => !alreadyLinkedFactors.value.includes(f.id))
    .filter(f => f.label.toLowerCase().includes(searchFactors.value.toLowerCase()))
})

// ✅ CHARGER FACTEURS POUR L'ANNÉE
const loadFactorsForYear = async () => {
  if (!selectedYear.value) {
    factorsForSelectedYear.value = []
    return
  }

  try {
    const response = await fetch(
      `/m/audit.core/api/audit/factors-by-year/${selectedYear.value}`
    )
    const data = await response.json()

    if (data.success) {
      factorsForSelectedYear.value = data.factors || []
    } else {
      factorsForSelectedYear.value = []
      alert('Erreur: ' + (data.error || 'Impossible de charger les facteurs'))
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur réseau: ' + error.message)
  }
}

// ✅ SÉLECTIONNER FACTEUR À AJOUTER
const selectFactorToAdd = (factor) => {
  selectedFactorToAdd.value = factor
  newEvaluation.value = {
    score: 3,
    justification: '',
  }
}

// ✅ AJOUTER FACTEUR À L'ANNÉE
const addFactorToYear = async () => {
  if (!selectedFactorToAdd.value || !selectedYear.value) return

  addingFactor.value = true

  try {
    const payload = {
      factor_id: selectedFactorToAdd.value.id,
      evaluation_year: selectedYear.value,
      score: newEvaluation.value.score,
      justification: newEvaluation.value.justification || null,
    }

    const response = await fetch('/m/audit.core/api/audit/factors/link-year', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify(payload),
    })

    const data = await response.json()

    if (data.success) {
      // ✅ RECHARGER LES FACTEURS
      await loadFactorsForYear()
      closeAddModal()
    } else {
      alert('Erreur: ' + (data.error || 'Erreur inconnue'))
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur réseau: ' + error.message)
  } finally {
    addingFactor.value = false
  }
}

// ✅ RETIRER FACTEUR DE L'ANNÉE
const removeFactorFromYear = async (factorId, year) => {
  if (!confirm('Êtes-vous sûr de retirer ce facteur de cette année ?')) return

  removingFactor.value = factorId

  try {
    // ✅ TROUVER L'ÉVALUATION CORRESPONDANTE
    const evaluation = factorsForSelectedYear.value.find(f => f.id === factorId)
    if (!evaluation) return

    // ✅ NOTE: Vous devez adapter l'endpoint pour récupérer l'ID d'évaluation
    // Pour maintenant, on suppose que vous avez un endpoint de suppression par facteur_id et year
    const response = await fetch(
      `/m/audit.core/api/audit/factors/unlink-year/${factorId}?year=${year}`,
      {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
      }
    )

    const data = await response.json()

    if (data.success) {
      await loadFactorsForYear()
    } else {
      alert('Erreur: ' + (data.error || 'Erreur inconnue'))
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur réseau: ' + error.message)
  } finally {
    removingFactor.value = null
  }
}

// ✅ FERMER MODAL
const closeAddModal = () => {
  showAddModal.value = false
  selectedFactorToAdd.value = null
  searchFactors.value = ''
  newEvaluation.value = {
    score: 3,
    justification: '',
  }
}
</script>

<style scoped>
:root {
  --primary: #1a237e;
  --primary-light: #283593;
  --secondary: #455a64;
  --success: #2e7d32;
  --warning: #f57c00;
  --info: #1565c0;
  --danger: #d32f2f;
  --light: #f5f7fa;
  --border: #cfd8dc;
  --text: #212121;
}

.factors-year-wrapper {
  min-height: 100vh;
  background: var(--light);
  padding: 2rem 1rem;
}

.header-section {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
}

.page-title {
  font-size: 1.8rem;
  font-weight: 600;
  color: var(--primary);
  margin: 0;
}

.page-subtitle {
  color: var(--secondary);
  font-size: 0.95rem;
  margin: 0.5rem 0 0 0;
}

.year-selector {
  max-width: 1400px;
  margin: 0 auto 2rem;
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  gap: 2rem;
  align-items: center;
}

.selector-container {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.selector-label {
  font-weight: 600;
  color: var(--secondary);
  white-space: nowrap;
}

.year-select {
  padding: 0.75rem 1rem;
  border: 2px solid var(--border);
  border-radius: 6px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  min-width: 200px;
}

.year-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
}

.selector-info {
  margin-left: auto;
}

.info-badge {
  display: inline-block;
  background: var(--info);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
}

.main-content {
  max-width: 1400px;
  margin: 0 auto;
}

.factors-section {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h2 {
  margin: 0;
  color: var(--primary);
  font-size: 1.3rem;
}

.btn-add-factor {
  padding: 0.75rem 1.5rem;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-add-factor:hover {
  background: var(--primary-light);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.table-container {
  overflow-x: auto;
}

.factors-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.factors-table thead {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
}

.factors-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  white-space: nowrap;
}

.factors-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--light);
}

.factor-row:hover {
  background: #fafafa;
}

.col-order { width: 80px; }
.col-label { width: 150px; }
.col-description { width: 250px; }
.col-importance { width: 120px; }
.col-weight { width: 100px; }
.col-count { width: 100px; }
.col-avg-score { width: 120px; }
.col-action { width: 80px; }

.factor-label {
  font-weight: 600;
  color: var(--text);
}

.factor-desc {
  color: var(--secondary);
  font-size: 0.85rem;
}

.importance-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.8rem;
  color: white;
}

.importance-badge.imp-1 {
  background: #90caf9;
}

.importance-badge.imp-2 {
  background: #64b5f6;
}

.importance-badge.imp-3 {
  background: #42a5f5;
}

.importance-badge.imp-4 {
  background: #2196f3;
}

.importance-badge.imp-5 {
  background: #1565c0;
}

.weight-value {
  font-weight: 600;
  color: var(--primary);
}

.count-badge {
  display: inline-block;
  background: var(--light);
  color: var(--secondary);
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.8rem;
}

.avg-score {
  font-weight: 700;
  color: var(--success);
}

.avg-score.empty {
  color: var(--secondary);
}

.btn-remove {
  padding: 0.4rem 0.6rem;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
  transition: all 0.2s;
}

.btn-remove:hover {
  transform: scale(1.2);
  background: #ffebee;
}

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: var(--secondary);
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.empty-message {
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
}

.btn-add-first {
  padding: 0.75rem 1.5rem;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-add-first:hover {
  background: var(--primary-light);
}

/* MODAL */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid var(--border);
}

.modal-header h3 {
  margin: 0;
  color: var(--primary);
  font-size: 1.2rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--secondary);
}

.btn-close:hover {
  color: var(--text);
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 600;
  color: var(--secondary);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.form-input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid var(--border);
  border-radius: 6px;
  font-size: 0.9rem;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
}

.search-input {
  margin-bottom: 1rem;
}

.available-factors {
  margin-bottom: 2rem;
}

.factors-list-header {
  font-weight: 600;
  color: var(--secondary);
  padding: 0.75rem 0;
  border-bottom: 2px solid var(--border);
  margin-bottom: 0.75rem;
}

.factors-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-height: 300px;
  overflow-y: auto;
}

.factor-item {
  padding: 1rem;
  border: 2px solid var(--border);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.factor-item:hover {
  border-color: var(--primary);
  background: #f0f7ff;
}

.factor-item.selected {
  border-color: var(--primary);
  background: #e3f2fd;
}

.factor-item-header {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-bottom: 0.5rem;
}

.factor-order {
  display: inline-block;
  background: var(--primary);
  color: white;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.8rem;
}

.factor-title {
  font-weight: 600;
  color: var(--text);
}

.factor-item-meta {
  display: flex;
  gap: 0.5rem;
  font-size: 0.8rem;
}

.weight-tag,
.importance-tag {
  background: var(--light);
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  color: var(--secondary);
}

.empty-list {
  padding: 1.5rem;
  text-align: center;
  color: var(--secondary);
  background: var(--light);
  border-radius: 6px;
}

.evaluation-form {
  background: var(--light);
  padding: 1.5rem;
  border-radius: 6px;
  margin-top: 1.5rem;
}

.form-divider {
  font-weight: 600;
  color: var(--secondary);
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--border);
}

.score-selector {
  display: flex;
  gap: 0.5rem;
}

.score-btn {
  flex: 1;
  padding: 0.75rem;
  border: 2px solid var(--border);
  background: white;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.score-btn:hover {
  border-color: var(--primary);
  background: #f0f7ff;
}

.score-btn.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid var(--border);
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.9rem;
  resize: vertical;
}

.form-textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
}

.modal-footer {
  display: flex;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid var(--border);
  background: var(--light);
}

.btn-cancel,
.btn-confirm {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-cancel {
  background: white;
  color: var(--secondary);
  border: 1px solid var(--border);
}

.btn-cancel:hover {
  background: var(--light);
}

.btn-confirm {
  background: var(--primary);
  color: white;
}

.btn-confirm:hover:not(:disabled) {
  background: var(--primary-light);
  transform: translateY(-2px);
}

.btn-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* COMPARAISON */
.comparison-section {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.comparison-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-top: 1rem;
}

.year-card {
  border: 2px solid var(--border);
  border-radius: 8px;
  padding: 1.5rem;
  transition: all 0.3s;
}

.year-card:hover {
  border-color: var(--primary);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.year-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--border);
}

.year-card-header h3 {
  margin: 0;
  color: var(--primary);
}

.year-count {
  background: var(--info);
  color: white;
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.8rem;
}

.year-card-factors {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.factor-mini {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  background: var(--light);
  border-radius: 4px;
  font-size: 0.85rem;
}

.factor-mini-name {
  font-weight: 500;
  color: var(--text);
}

.factor-mini-weight {
  background: var(--primary);
  color: white;
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  font-weight: 600;
  font-size: 0.75rem;
}

/* LÉGENDE */
.legend-section {
  max-width: 1400px;
  margin: 0 auto;
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.legend-section h3 {
  margin: 0 0 1rem 0;
  color: var(--primary);
}

.legend-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.legend-color {
  width: 30px;
  height: 30px;
  border-radius: 4px;
}

.legend-color.imp-1 {
  background: #90caf9;
}

.legend-color.imp-2 {
  background: #64b5f6;
}

.legend-color.imp-3 {
  background: #42a5f5;
}

.legend-color.imp-4 {
  background: #2196f3;
}

.legend-color.imp-5 {
  background: #1565c0;
}

@media (max-width: 768px) {
  .year-selector {
    flex-direction: column;
    align-items: flex-start;
  }

  .selector-info {
    margin-left: 0;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .factors-table {
    font-size: 0.8rem;
  }

  .col-description { display: none; }
  .col-importance { width: 90px; }

  .comparison-grid {
    grid-template-columns: 1fr;
  }
}
</style>