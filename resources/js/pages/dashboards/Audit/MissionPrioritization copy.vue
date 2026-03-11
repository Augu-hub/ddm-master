<template>
  <div class="wrapper">
    <!-- HEADER -->
    <div class="header-box">
      <h1>⭐ Priorisation des Missions d'Audit</h1>
      <p>Évaluation basée sur les facteurs de risque avec historique</p>
    </div>

    <!-- SÉLECTION ANNÉE -->
    <div class="selector-box">
      <label class="label">📅 Sélectionner une année:</label>
      <select v-model.number="selectedYear" @change="onYearChange" class="year-select">
        <option :value="null">-- Choisir une année --</option>
        <option v-for="year in store.availableYears" :key="year" :value="year">
          {{ year }}
        </option>
      </select>
      <span v-if="missionsByYear.length > 0" class="mission-count">
        📊 {{ missionsByYear.length }} missions
      </span>
    </div>

    <!-- CONTENU -->
    <div v-if="selectedYear" class="content">
      <!-- FACTEURS -->
      <div class="factors-box">
        <h3>📋 Critères d'Évaluation (avec Importance)</h3>
        <div class="factors-list">
          <div v-for="(factor, idx) in store.allFactors" :key="factor?.id" class="factor-card">
            <span class="num">{{ idx + 1 }}</span>
            <div class="factor-info">
              <span class="text">{{ factor?.label || 'Facteur' }}</span>
              <span class="weight">({{ factor?.weight || 25 }}%)</span>
            </div>
          </div>
        </div>
      </div>

      <!-- LÉGENDE SCALES -->
     

      <!-- TABLEAU GROUPÉ PAR ENTITÉ -->
      <div v-if="missionsByYearGrouped.length > 0" class="table-box">
        <div v-for="entityGroup in missionsByYearGrouped" :key="entityGroup.entity.id" class="entity-group">
          <!-- EN-TÊTE ENTITÉ -->
          <div class="entity-header">
            <h4>
              <i class="ti ti-building"></i> {{ entityGroup.entity.name }}
              <span class="entity-count">({{ entityGroup.missions.length }})</span>
            </h4>
          </div>

          <!-- TABLEAU PAR ENTITÉ -->
          <table class="mission-table">
            <thead>
              <tr>
                <th style="width: 120px">Code</th>
                <th>Mission</th>
                <th v-for="(f, idx) in store.allFactors" :key="`th-${f.id}`" class="factor-col">
                  F{{ idx + 1 }}
                </th>
                <th v-for="factor in store.allFactors" :key="`th-${factor.id}`" class="factor-col">
                  {{ factor.label }}
                </th>
                <th class="calc-col">Total</th>
                <th class="calc-col">Coeff</th>
                <th class="calc-col">Niveau</th>
                <th class="calc-col">Poids</th>
                <th class="calc-col">Rang</th>
                <th class="calc-col">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="mission in entityGroup.missions" :key="`row-${mission.id}`" class="mission-row">
                <!-- CODE (DOUBLE-CLIC → MODAL) -->
                <td class="code-cell">
                  <span 
                    class="code-badge" 
                    @dblclick="openMissionModal(mission)" 
                    style="cursor: pointer;" 
                    title="Double-clic pour voir les détails"
                  >
                    {{ mission.code }}
                  </span>
                </td>

                <!-- MISSION -->
                <td class="mission-cell">
                  {{ truncate(mission.mission_objective, 30) }}
                </td>

                <!-- FACTEURS AVEC BOUTONS COLORÉS + ANCIEN MOYEN -->
                <td v-for="factor in store.allFactors" :key="`cell-${mission.id}-${factor.id}`" class="score-cell">
                  <!-- ANCIEN MOYEN (SI EXISTE) - MARQUÉ EN HAUT À DROITE -->
                  <div v-if="getOldScore(mission.id, factor.id) > 0" class="old-score-badge">
                    <small title="Ancienne sélection">{{ getOldScore(mission.id, factor.id) }}</small>
                  </div>

                  <!-- BOUTONS AVEC MARQUAGE -->
                  <div class="score-buttons">
                    <button
                      v-for="scale in factorScales"
                      :key="`btn-${mission.id}-${factor.id}-${scale.value}`"
                      @click="handleScoreUpdate(mission.id, factor.id, scale.value)"
                      :class="[
                        'btn', 
                        'btn-scale',
                        { active: store.getScore(mission.id, factor.id) === scale.value },
                        { 'was-selected': getOldScore(mission.id, factor.id) === scale.value && store.getScore(mission.id, factor.id) !== scale.value }
                      ]"
                      :style="getButtonStyle(scale)"
                      :title="`${scale.label}: ${scale.description}`"
                    >
                      {{ scale.value }}
                    </button>
                  </div>
                </td>

                <!-- TOTAL -->
                <td class="calc-cell">
                  <strong>{{ store.getTotal(mission.id).toFixed(2) }}</strong>
                </td>

                <!-- COEFF -->
                <td class="calc-cell">
                  <strong>{{ store.getCoeff(mission.id).toFixed(2) }}</strong>
                </td>

                <!-- NIVEAU -->
                <td class="calc-cell">
                  <span :class="['level', store.getLevel(mission.id).toLowerCase()]">
                    {{ store.getLevel(mission.id) }}
                  </span>
                </td>

                <!-- POIDS -->
                <td class="calc-cell weight-cell">
                  <strong>{{ store.getWeightedScore(mission.id).toFixed(2) }}</strong>
                </td>

                <!-- RANG -->
                <td class="calc-cell rank-cell">
                  <strong :class="getRankClass(store.getRank(mission.id))">
                    {{ store.getRank(mission.id) }}
                  </strong>
                </td>

                <!-- STATUS -->
                <td class="calc-cell">
                  <span v-if="store.saving[mission.id]" class="status-saving">⏳</span>
                  <span v-else-if="isSaved(mission.id)" class="status-saved">✅</span>
                  <span v-else class="status-unsaved">•</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="empty-message">
        <p>📭 Aucune mission pour {{ selectedYear }}</p>
      </div>

      

      <!-- LÉGENDE -->
      <div class="legend-box">
        <h3>📖 Légende</h3>
        <div class="legend-items">
          <div class="legend-item">
            <span class="legend-badge critical">Critique</span>
            <span>≥ 3.0</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge considerable">Considérable</span>
            <span>2.0 - 2.99</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge important">Important</span>
            <span>1.0 - 1.99</span>
          </div>
          <div class="legend-item">
            <span class="legend-badge mineur">Mineur</span>
            <span>&lt; 1.0</span>
          </div>
        </div>
        <div class="legend-items" style="margin-top: 1rem;">
          <div class="legend-item">
            <strong>Status:</strong>
            <span>⏳ = Enregistrement | ✅ = Enregistré | • = Non enregistré</span>
          </div>
          <div class="legend-item">
            <strong>Ancien Score:</strong>
            <span>Petit badge en haut à droite + Bouton semi-transparent</span>
          </div>
          <div class="legend-item">
            <strong>Double-clic Code:</strong>
            <span>Ouvre modal avec détails complets et entité</span>
          </div>
          <div class="legend-item">
            <strong>Regroupement:</strong>
            <span>Par entité avec compteur missions</span>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="select-message">
      <p>👆 Sélectionne une année pour commencer</p>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- MODAL DÉTAILS MISSION -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="missionModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" v-if="selectedMission">
            <h5 class="modal-title">
              <i class="ti ti-document"></i> Détails Mission: {{ selectedMission.code }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedMission">
            <div class="row">
              <!-- COLONNE GAUCHE -->
              <div class="col-md-6">
                <h6 class="text-muted text-uppercase mb-2">📋 Informations Générales</h6>
                <div class="detail-item">
                  <span class="detail-label">Code:</span>
                  <span class="detail-value fw-bold">{{ selectedMission.code }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Mission:</span>
                  <span class="detail-value">{{ selectedMission.mission_objective }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">🏢 Entité:</span>
                  <span class="detail-value fw-bold">{{ selectedMission.entity?.name }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Processus:</span>
                  <span class="detail-value">{{ selectedMission.process?.name }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Statut:</span>
                  <span class="detail-value">
                    <span class="badge bg-info">{{ selectedMission.status }}</span>
                  </span>
                </div>
              </div>

              <!-- COLONNE DROITE -->
              <div class="col-md-6">
                <h6 class="text-muted text-uppercase mb-2">📅 Dates</h6>
                <div class="detail-item">
                  <span class="detail-label">Demandée:</span>
                  <span class="detail-value">{{ selectedMission.requested_date }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Début:</span>
                  <span class="detail-value">{{ selectedMission.start_date || '—' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Fin:</span>
                  <span class="detail-value">{{ selectedMission.end_date || '—' }}</span>
                </div>

                <h6 class="text-muted text-uppercase mb-2 mt-3">🎯 Évaluation</h6>
                <div class="detail-item">
                  <span class="detail-label">Coefficient:</span>
                  <span class="detail-value fw-bold">{{ selectedMission.coefficient }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Niveau:</span>
                  <span class="detail-value">
                    <span :class="['badge', getLevelBgClass(selectedMission.level)]">
                      {{ selectedMission.level }}
                    </span>
                  </span>
                </div>
              </div>
            </div>

            <!-- DESCRIPTION -->
            <div v-if="selectedMission.description" class="mt-3">
              <h6 class="text-muted text-uppercase mb-2">📝 Description</h6>
              <div class="alert alert-light">
                {{ selectedMission.description }}
              </div>
            </div>

            <!-- SCORES -->
            <div v-if="selectedMission.factorScores && Object.keys(selectedMission.factorScores).length > 0" class="mt-3">
              <h6 class="text-muted text-uppercase mb-2">📊 Scores par Facteur</h6>
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th>Facteur</th>
                      <th class="text-center" style="width: 80px">Score</th>
                      <th>Échelle</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="factor in store.allFactors" :key="factor.id">
                      <td><strong>{{ factor.label }}</strong></td>
                      <td class="text-center">
                        <span class="badge bg-secondary">
                          {{ selectedMission.factorScores[factor.id] || '—' }}
                        </span>
                      </td>
                      <td>
                        <span v-if="selectedMission.factorScores[factor.id]" class="scale-label">
                          {{ getScaleLabel(selectedMission.factorScores[factor.id]) }}
                        </span>
                        <span v-else class="text-muted">—</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal-footer" v-if="selectedMission">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuditStore } from '@/stores/auditStore'

interface FactorScale {
  id: number
  value: number
  label: string
  description: string
  color: string
}

interface Props {
  missions?: any[]
  factors?: any[]
  factorScales?: FactorScale[]
  factorScores?: Record<number, any>
}

const props = withDefaults(defineProps<Props>(), {
  missions: () => [],
  factors: () => [],
  factorScales: () => [],
  factorScores: () => ({}),
})

const store = useAuditStore()
const selectedYear = ref<number | null>(null)
const saveTimers = ref<Record<number, NodeJS.Timeout>>({})
const factorScales = ref<FactorScale[]>(props.factorScales || [])
const selectedMission = ref<any>(null)
const allMissions = ref<any[]>(props.missions || [])

// INIT
onMounted(() => {
  allMissions.value = props.missions || []
  store.initializeData(props.missions || [], props.factors || [], extractScoresOnly(props.factorScores || {}))
})

// EXTRAIRE SCORES UNIQUEMENT
function extractScoresOnly(factorScores: Record<number, any>) {
  const result: Record<number, Record<number, number>> = {}
  for (const [missionId, data] of Object.entries(factorScores)) {
    if (data && typeof data === 'object' && 'scores' in data) {
      result[parseInt(missionId)] = data.scores
    } else {
      result[parseInt(missionId)] = data || {}
    }
  }
  return result
}

// MISSIONS BY YEAR
const missionsByYear = computed(() => {
  if (!selectedYear.value) return []
  return store.allMissions.filter(m => {
    if (!m?.requested_date) return false
    const year = new Date(m.requested_date).getFullYear()
    return year === selectedYear.value
  })
})

// MISSIONS GROUPÉES PAR ENTITÉ
const missionsByYearGrouped = computed(() => {
  const grouped: Record<number, { entity: any; missions: any[] }> = {}
  
  missionsByYear.value.forEach(mission => {
    const entityId = mission.entity?.id || 0
    if (!grouped[entityId]) {
      grouped[entityId] = {
        entity: mission.entity || { id: 0, name: 'N/A' },
        missions: []
      }
    }
    grouped[entityId].missions.push(mission)
  })
  
  return Object.values(grouped).sort((a, b) => 
    (a.entity.name || '').localeCompare(b.entity.name || '')
  )
})

// STATS
const counts = computed(() => {
  const levels = { critique: 0, considerable: 0, important: 0, mineur: 0 }
  missionsByYear.value.forEach(m => {
    const level = store.getLevel(m.id)
    if (level === 'Critique') levels.critique++
    else if (level === 'Considérable') levels.considerable++
    else if (level === 'Important') levels.important++
    else levels.mineur++
  })
  return levels
})

const avgCoeff = computed(() => {
  if (missionsByYear.value.length === 0) return 0
  const sum = missionsByYear.value.reduce((a, m) => a + store.getCoeff(m.id), 0)
  return sum / missionsByYear.value.length
})

// HANDLERS
const truncate = (text: string | null | undefined, len: number): string => {
  if (!text) return '—'
  return text.length > len ? text.substring(0, len) + '...' : text
}

const handleScoreUpdate = (missionId: number, factorId: number, score: number): void => {
  store.updateScore(missionId, factorId, score)
  if (saveTimers.value[missionId]) {
    clearTimeout(saveTimers.value[missionId])
  }
  saveTimers.value[missionId] = setTimeout(() => {
    store.saveMissionScore(missionId)
  }, 800)
}

const isSaved = (missionId: number): boolean => {
  const mission = store.getMissionById(missionId)
  return mission && mission.coefficient ? mission.coefficient > 0 : false
}

const getRankClass = (rank: string): string => {
  if (rank === '1er') return 'rank-first'
  if (rank === '2e') return 'rank-second'
  if (rank === '3e') return 'rank-third'
  return ''
}

const getButtonStyle = (scale: FactorScale) => {
  return {
    backgroundColor: scale.color,
    borderColor: scale.color,
    color: '#fff',
  }
}

// ANCIEN SCORE
const getOldScore = (missionId: number, factorId: number): number => {
  const data = props.factorScores?.[missionId]
  if (!data) return 0
  // Retourner le score actuel comme "ancien" (mémorisation basique)
  const scores = data.scores || data
  return scores?.[factorId] || 0
}

// MODAL - CHARGER MISSION COMPLÈTE
const openMissionModal = (mission: any) => {
  selectedMission.value = {
    ...mission,
    factorScores: {}
  }
  
  // Charger les scores
  const data = props.factorScores?.[mission.id]
  if (data && typeof data === 'object') {
    selectedMission.value.factorScores = data.scores || data
  }
  
  new window.bootstrap.Modal(document.getElementById('missionModal')).show()
}

const getScaleLabel = (value: number): string => {
  const scale = factorScales.value.find(s => s.value === value)
  return scale ? scale.label : '—'
}

const getLevelBgClass = (level: string): string => {
  const map: Record<string, string> = {
    'Critique': 'bg-danger',
    'Considérable': 'bg-warning text-dark',
    'Important': 'bg-primary',
    'Mineur': 'bg-success',
  }
  return map[level] || 'bg-secondary'
}

const onYearChange = (): void => {
  store.resetScores()
}
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.wrapper {
  background: #ffffff;
  min-height: 100vh;
  padding: 2rem 1rem;
}

.header-box {
  background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
  color: white;
  padding: 2rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.header-box h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.header-box p {
  font-size: 1rem;
  opacity: 0.9;
}

.selector-box {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.label {
  font-weight: 600;
  color: #333;
}

.year-select {
  padding: 0.75rem 1rem;
  border: 2px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  min-width: 150px;
}

.year-select:focus {
  outline: none;
  border-color: #1a237e;
  box-shadow: 0 0 0 3px rgba(26,35,126,0.1);
}

.mission-count {
  color: #666;
  font-weight: 500;
}

.content {
  max-width: 2400px;
  margin: 0 auto;
}

.factors-box {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.factors-box h3 {
  color: #1a237e;
  margin-bottom: 1rem;
}

.factors-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
}

.factor-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: #f5f5f5;
  border-left: 4px solid #1a237e;
  border-radius: 4px;
}

.factor-card .num {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: #1a237e;
  color: white;
  border-radius: 50%;
  font-weight: bold;
  flex-shrink: 0;
}

.factor-info {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.factor-info .text {
  font-size: 0.95rem;
  color: #333;
  font-weight: 500;
}

.factor-info .weight {
  font-size: 0.85rem;
  color: #666;
  font-weight: 600;
}

.scales-legend-box {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.scales-legend-box h3 {
  color: #1a237e;
  margin-bottom: 1rem;
}

.scales-legend {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.scale-legend-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 4px;
}

.scale-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  color: white;
  font-weight: bold;
  border-radius: 6px;
  flex-shrink: 0;
  font-size: 1.2rem;
}

.scale-info {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.scale-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #333;
}

.scale-desc {
  font-size: 0.8rem;
  color: #666;
}

.table-box {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.entity-group {
  margin-bottom: 2rem;
  overflow: hidden;
}

.entity-header {
  background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
  padding: 1rem 1.5rem;
  border-bottom: 2px solid #1a237e;
}

.entity-header h4 {
  color: #1a237e;
  margin: 0;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.entity-count {
  background: #1a237e;
  color: white;
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  font-size: 0.85rem;
  font-weight: bold;
}

.mission-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1400px;
  font-size: 0.9rem;
}

.mission-table thead {
  background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
  color: white;
  position: sticky;
  top: 0;
  z-index: 5;
}

.mission-table th {
  padding: 0.75rem;
  text-align: center;
  font-weight: 600;
  font-size: 0.85rem;
  border-right: 1px solid rgba(255,255,255,0.2);
}

.mission-table th:first-child {
  text-align: left;
}

.mission-table th:last-child {
  border-right: none;
}

.factor-col {
  min-width: 110px;
}

.calc-col {
  min-width: 70px;
}

.weight-cell {
  background-color: rgba(255,215,0,0.1);
  font-weight: 700;
  color: #ff8c00;
}

.rank-cell {
  background-color: rgba(26,35,126,0.05);
  font-weight: 700;
}

.rank-cell :deep(.rank-first) {
  color: #d4af37;
  font-size: 1.1rem;
}

.rank-cell :deep(.rank-second) {
  color: #c0c0c0;
  font-size: 1.05rem;
}

.rank-cell :deep(.rank-third) {
  color: #cd7f32;
  font-size: 1rem;
}

.mission-row {
  border-bottom: 1px solid #e0e0e0;
  transition: background 0.2s;
}

.mission-row:hover {
  background: #f9f9f9;
}

.code-cell {
  padding: 0.75rem;
  font-weight: 600;
  width: 120px;
}

.code-badge {
  display: inline-block;
  background: #1a237e;
  color: white;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-family: monospace;
}

.mission-cell {
  padding: 0.75rem;
  color: #555;
  font-size: 0.9rem;
}

.score-cell {
  padding: 0.5rem;
  text-align: center;
  position: relative;
}

/* ✅ ANCIEN SCORE - PETIT BADGE EN HAUT À DROITE */
.old-score-badge {
  position: absolute;
  top: 2px;
  right: 3px;
  background: linear-gradient(135deg, rgba(255, 152, 0, 0.3), rgba(255, 152, 0, 0.1));
  color: #ff6f00;
  padding: 0.2rem 0.35rem;
  border-radius: 2px;
  font-weight: bold;
  border: 1.5px solid #ffb74d;
  font-size: 0.7rem;
  box-shadow: 0 1px 2px rgba(255, 152, 0, 0.2);
}

.score-buttons {
  display: flex;
  gap: 0.2rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-top: 0.2rem;
}

.btn-scale {
  width: 35px;
  height: 35px;
  padding: 0;
  border: 2px solid transparent;
  background: white;
  color: white;
  border-radius: 4px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.85rem;
  opacity: 0.6;
}

.btn-scale:hover {
  opacity: 0.9;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.btn-scale.active {
  opacity: 1;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  border-width: 3px;
}

/* ✅ MARQUAGE: ANCIEN BOUTON SÉLECTIONNÉ (SEMI-TRANSPARENT) */
.btn-scale.was-selected {
  opacity: 0.4;
  border: 2px dashed;
  box-shadow: inset 0 0 3px rgba(0,0,0,0.2);
}

.calc-cell {
  padding: 0.75rem;
  text-align: center;
  font-weight: 600;
  color: #1a237e;
}

.level {
  display: inline-block;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  font-size: 0.8rem;
  color: white;
  font-weight: 600;
}

.level.critique {
  background: #d32f2f;
}

.level.considerable {
  background: #f57c00;
}

.level.important {
  background: #1976d2;
}

.level.mineur {
  background: #388e3c;
}

.status-saving {
  animation: spin 1s linear infinite;
}

.status-saved {
  color: #388e3c;
  font-weight: bold;
}

.status-unsaved {
  color: #999;
  font-weight: bold;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-message,
.select-message {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 3rem;
  text-align: center;
  color: #666;
  font-size: 1.1rem;
}

.stats-box {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stats-box h3 {
  color: #1a237e;
  margin-bottom: 1.5rem;
}

.stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: #f5f5f5;
  padding: 1rem;
  border-radius: 6px;
  border-left: 4px solid #1a237e;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-label {
  color: #666;
  font-size: 0.8rem;
  text-transform: uppercase;
  font-weight: 600;
}

.stat-num {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1a237e;
}

.stat-num.critical {
  color: #d32f2f;
}

.stat-num.considerable {
  color: #f57c00;
}

.stat-num.important {
  color: #1976d2;
}

.stat-num.mineur {
  color: #388e3c;
}

.legend-box {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.legend-box h3 {
  color: #1a237e;
  margin-bottom: 1.5rem;
}

.legend-items {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.legend-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 4px;
  color: white;
  font-weight: 600;
  font-size: 0.85rem;
  min-width: 100px;
  text-align: center;
  flex-shrink: 0;
}

.legend-badge.critical {
  background: #d32f2f;
}

.legend-badge.considerable {
  background: #f57c00;
}

.legend-badge.important {
  background: #1976d2;
}

.legend-badge.mineur {
  background: #388e3c;
}

/* MODAL */
.detail-item {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.75rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.detail-label {
  font-weight: 600;
  color: #666;
  min-width: 120px;
}

.detail-value {
  color: #333;
  flex: 1;
}

.scale-label {
  font-size: 0.9rem;
  color: #1a237e;
}

@media (max-width: 768px) {
  .selector-box {
    flex-direction: column;
    align-items: stretch;
  }

  .year-select {
    min-width: unset;
  }

  .mission-table {
    font-size: 0.8rem;
  }

  .btn-scale {
    width: 30px;
    height: 30px;
    font-size: 0.75rem;
  }

  .mission-cell {
    display: none;
  }

  .stats-cards {
    grid-template-columns: repeat(2, 1fr);
  }

  .factor-col {
    min-width: 80px;
  }

  .calc-col {
    min-width: 50px;
  }
}
</style>