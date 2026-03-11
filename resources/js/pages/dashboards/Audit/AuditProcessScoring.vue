<template>
  <div class="audit-container">

    <!-- HEADER -->
    <div class="header">
      <h1>⭐ Audit des Processus</h1>
      <p>Pondération, Notation et Programmation des Missions</p>
    </div>

    <!-- STATS GLOBALES -->
    <div class="stats-bar">
      <div class="stat-item">
        <span class="stat-num">{{ selectedEntityIds.length }}</span>
        <span class="stat-lbl">entité(s)</span>
      </div>
      <div class="stat-item">
        <span class="stat-num">{{ processesFiltered.length }}</span>
        <span class="stat-lbl">processus</span>
      </div>
      <div class="stat-item">
        <span class="stat-num">{{ risksFiltered.length }}</span>
        <span class="stat-lbl">risques</span>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs-wrapper">
      <div class="tabs">
        <button @click="tab='pond'"     :class="{active:tab==='pond'}">📊 Pondération</button>
        <button @click="tab='note'"     :class="{active:tab==='note'}">⭐ Notation</button>
        <button @click="tab='risks'"    :class="{active:tab==='risks'}">⚠️ Risques</button>
        <button @click="tab='missions'" :class="{active:tab==='missions'}">📝 Missions</button>
      </div>
    </div>

    <!-- ===================== TAB 1 : PONDÉRATION ===================== -->
    <div v-show="tab==='pond'" class="tab-panel">

      <!-- SÉLECTEUR ENTITÉS EN CASES À COCHER -->
      <div class="entity-selector-panel">
        <span class="esp-title">🏢 Entités :</span>
        <div class="esp-checks">
          <label
            v-for="e in entities" :key="e.id"
            class="ent-chip" :class="{active: selectedEntityIds.includes(e.id)}"
          >
            <input type="checkbox" :value="e.id" v-model="selectedEntityIds" />
            {{ e.name }}
          </label>
        </div>
        <button v-if="selectedEntityIds.length > 0" class="btn-clear" @click="selectedEntityIds=[]">✕ Tout</button>
      </div>

      <div v-if="selectedEntityIds.length === 0" class="empty-state">
        👆 Cochez au moins une entité pour afficher les processus
      </div>

      <div v-else>
        <div class="pond-header">
          <h2>📊 Pondération des Processus</h2>
          <p class="subtitle">Cochez les années pour lesquelles chaque processus doit être audité</p>
        </div>

        <!-- UN BLOC PAR ENTITÉ -->
        <div v-for="entityId in selectedEntityIds" :key="entityId" class="entity-pond-block">
          <div class="epb-header">
            <span class="entity-badge">🏢 {{ getEntityName(entityId) }}</span>
            <span class="epb-count">{{ getProcessesForEntity(entityId).length }} processus — {{ getRisksForEntityDirect(entityId).length }} risques</span>
          </div>

          <table class="pond-table">
            <thead>
              <tr>
                <th style="width:90px">Code</th>
                <th>Processus</th>
                <th style="width:75px;text-align:center">Risques</th>
                <th v-for="y in years" :key="y" style="width:72px;text-align:center">{{ y }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in getProcessesForEntity(entityId)" :key="p.process_id">
                <td><span class="code">{{ p.code }}</span></td>
                <td>{{ p.name }}</td>
                <td class="center">
                  <!-- Nombre de risques liés directement à cette entité ET ce processus via risks.entity_id -->
                  <span class="risk-count-badge">{{ getRisksCountEntityProcess(entityId, p.process_id) }}</span>
                </td>
                <td v-for="y in years" :key="y" class="year-cell">
                  <input
                    type="checkbox"
                    class="year-check"
                    :checked="getPonderation(entityId, p.process_id, y)"
                    @change="onToggleYear(entityId, p.process_id, y, $event.target.checked)"
                  />
                </td>
              </tr>
              <tr v-if="getProcessesForEntity(entityId).length === 0">
                <td colspan="6" class="empty-row">Aucun processus lié à cette entité</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===================== TAB 2 : NOTATION ===================== -->
    <div v-show="tab==='note'" class="tab-panel">

      <div class="entity-selector-panel">
        <span class="esp-title">🏢 Entité :</span>
        <div class="esp-checks">
          <label
            v-for="e in entities" :key="e.id"
            class="ent-chip" :class="{active: noteEntityId===e.id}"
          >
            <input type="radio" :value="e.id" v-model="noteEntityId" />
            {{ e.name }}
          </label>
        </div>
      </div>

      <div v-if="!noteEntityId" class="empty-state">👆 Sélectionnez une entité pour noter ses processus</div>

      <div v-else>
        <h2>⭐ Notation — {{ getEntityName(noteEntityId) }}</h2>
        <div class="table-scroll">
          <table class="note-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Processus</th>
                <th v-for="f in factors" :key="f.id">{{ f.label }}</th>
                <th>Moy</th>
                <th>Rang</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in getProcessesForEntity(noteEntityId)" :key="p.process_id">
                <td><span class="code">{{ p.code }}</span></td>
                <td>{{ p.name }}</td>
                <td v-for="f in factors" :key="f.id">
                  <div class="scores">
                    <button
                      v-for="s in scales" :key="s.value"
                      @click="onSaveScore(p.process_id, f.id, s.value, noteEntityId)"
                      :class="['score-btn', {on: getScore(p.process_id, f.id, noteEntityId)===s.value}]"
                    >{{ s.value }}</button>
                  </div>
                </td>
                <td class="center">{{ (p.average_score||0).toFixed(2) }}</td>
                <td class="center">{{ p.ranking_position||'—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===================== TAB 3 : RISQUES ===================== -->
    <div v-show="tab==='risks'" class="tab-panel">

      <div class="entity-selector-panel">
        <span class="esp-title">🏢 Entités :</span>
        <div class="esp-checks">
          <label
            v-for="e in entities" :key="e.id"
            class="ent-chip" :class="{active: riskEntityIds.includes(e.id)}"
          >
            <input type="checkbox" :value="e.id" v-model="riskEntityIds" />
            {{ e.name }}
          </label>
        </div>
      </div>

      <div v-if="riskEntityIds.length===0" class="empty-state">👆 Sélectionnez des entités pour voir leurs risques</div>

      <div v-else>
        <!-- UN BLOC PAR ENTITÉ -->
        <div v-for="entityId in riskEntityIds" :key="entityId" class="entity-pond-block">
          <div class="epb-header">
            <span class="entity-badge">🏢 {{ getEntityName(entityId) }}</span>
            <span class="epb-count">{{ getRisksForEntityEnriched(entityId).length }} risques</span>
          </div>

          <table class="risks-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Libellé</th>
                <th>Processus</th>
                <th>Criticité</th>
                <th>Score proc.</th>
                <th>Moyenne</th>
                <th>Rang</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in getRisksForEntityEnriched(entityId)" :key="r.id">
                <td><span class="code risk">{{ r.code }}</span></td>
                <td>{{ r.label }}</td>
                <td>{{ r.process_code }}</td>
                <td class="center"><span :class="['crit', critClass(r.criticality_gross)]">{{ r.criticality_gross||'N/A' }}</span></td>
                <td class="center">{{ (r.process_average_score||0).toFixed(2) }}</td>
                <td class="center"><strong :class="riskAvgClass(r.risk_average)">{{ (r.risk_average||0).toFixed(2) }}</strong></td>
                <td class="center"><span class="rank-badge">{{ r.rank||'—' }}</span></td>
                <td class="center"><span :class="['status-chip',r.status]">{{ r.status }}</span></td>
              </tr>
              <tr v-if="getRisksForEntityEnriched(entityId).length===0">
                <td colspan="8" class="empty-row">Aucun risque pour cette entité</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===================== TAB 4 : MISSIONS ===================== -->
    <div v-show="tab==='missions'" class="tab-panel">
      <h2>📝 Programmation des Missions</h2>

      <!-- ENTITÉS POUR MISSIONS -->
      <div class="entity-selector-panel">
        <span class="esp-title">🏢 Entités :</span>
        <div class="esp-checks">
          <label
            v-for="e in entities" :key="e.id"
            class="ent-chip" :class="{active: missionEntityIds.includes(e.id)}"
          >
            <input type="checkbox" :value="e.id" v-model="missionEntityIds" @change="onMissionEntitiesChange" />
            {{ e.name }}
          </label>
        </div>
      </div>

      <!-- FILTRE ANNÉE -->
      <div class="year-filter">
        <label><strong>Année :</strong></label>
        <button v-for="y in years" :key="y" :class="['year-filter-btn',{active:selectedYear===y}]" @click="selectedYear=y">{{ y }}</button>
      </div>

      <!-- ERREUR IA -->
      <div v-if="aiError" class="ai-error">
        ⚠️ {{ aiError }}
        <button @click="aiError=null" class="ai-error-close">&times;</button>
      </div>

      <div class="missions-layout">

        <!-- GAUCHE : risques par entité avec cases à cocher -->
        <div class="left-half">
          <h3>📊 Risques à inclure</h3>

          <div v-if="missionEntityIds.length===0" class="empty">
            Cochez les entités pour voir leurs risques
          </div>

          <div v-else class="entity-risks-container">
            <div v-for="entityId in missionEntityIds" :key="entityId" class="entity-risk-group">
              <h4 class="entity-group-title">
                🏢 {{ getEntityName(entityId) }}
                <span class="epb-count">{{ getMissionRisksForEntity(entityId).length }}</span>
              </h4>

              <div v-if="getMissionRisksForEntity(entityId).length===0" class="empty-row-inline">
                Aucun risque pour cette entité
              </div>

              <div
                v-for="r in getMissionRisksForEntity(entityId)" :key="r.id"
                class="risk-check-item" :class="{selected: form.risks.includes(r.id)}"
              >
                <label class="risk-check-label">
                  <input type="checkbox" :value="r.id" v-model="form.risks" />
                  <span class="code risk">{{ r.code }}</span>
                  <span class="process-code">{{ r.process_code }}</span>
                  <span class="risk-label">{{ cut(r.label,38) }}</span>
                  <span :class="['crit',critClass(r.criticality_gross)]">{{ r.criticality_gross }}</span>
                  <span class="score-tag">{{ (r.process_average_score||0).toFixed(1) }}</span>
                  <span class="risk-avg-tag">{{ ((r.criticality_gross||0)*(r.process_average_score||0)).toFixed(1) }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- DROITE : formulaire -->
        <div class="right-half">
          <div class="form-box-full">
            <h3>➕ Nouvelle Mission <span class="ai-badge">🤖 IA</span></h3>
            <form @submit.prevent="onCreateMission">

              <!-- RISQUES SÉLECTIONNÉS -->
              <div class="field-full">
                <label>Risques sélectionnés ({{ form.risks.length }})</label>
                <div class="selected-risks-summary">
                  <div v-if="form.risks.length===0" class="empty-small">Aucun risque sélectionné</div>
                  <div v-else class="selected-risks-list">
                    <div v-for="rid in form.risks" :key="rid" class="selected-risk-tag">
                      <span class="code risk">{{ findRisk(rid)?.code }}</span>
                      <span class="risk-label-mini">{{ cut(findRisk(rid)?.label,18) }}</span>
                      <button type="button" @click="removeRisk(rid)" class="remove-btn-small">✕</button>
                    </div>
                  </div>
                </div>
                <div v-if="form.risks.length>0" class="ai-bar">
                  <button type="button" class="btn-ai-full" @click="aiFullProposal" :disabled="aiLoading.full">
                    <span v-if="aiLoading.full" class="spinner-sm"></span>
                    🤖 {{ aiLoading.full ? 'Génération IA...' : 'Générer toute la mission avec l\'IA' }}
                  </button>
                </div>
              </div>

              <!-- BUT -->
              <div class="field-full">
                <label>But de la mission <span class="req">*</span>
                  <span class="ai-btns-inline">
                    <button v-if="form.risks.length>0" type="button" class="btn-ai-mini" @click="aiSuggestGoals" :disabled="aiLoading.goals">
                      <span v-if="aiLoading.goals" class="spinner-xs"></span><span v-else>🤖</span> 3 propositions
                    </button>
                    <button v-if="form.but&&form.risks.length>0" type="button" class="btn-ai-mini btn-ai-revise" @click="aiReviseGoal" :disabled="aiLoading.revise">
                      <span v-if="aiLoading.revise" class="spinner-xs"></span><span v-else>🔄</span> Reformuler
                    </button>
                  </span>
                </label>
                <div v-if="aiGoalSuggestions.length>0" class="ai-goals">
                  <div v-for="(g,i) in aiGoalSuggestions" :key="i" class="ai-goal" :class="{best:i===0}" @click="applyGoal(g.but)">
                    <span class="ai-goal-rank">{{ i===0?'★':i+1 }}</span>
                    <div><strong>{{ g.short_label||'Proposition '+(i+1) }}</strong><p>{{ g.but }}</p></div>
                  </div>
                </div>
                <textarea v-model="form.but" required rows="2" placeholder="Objectif de la mission..."></textarea>
              </div>

              <!-- TYPE -->
              <div class="row-2">
                <div class="field-col">
                  <label>Type de mission <span class="req">*</span>
                    <button v-if="form.risks.length>0&&form.but" type="button" class="btn-ai-mini" @click="aiSuggestType" :disabled="aiLoading.type">
                      <span v-if="aiLoading.type" class="spinner-xs"></span><span v-else>🤖</span> Suggérer
                    </button>
                  </label>
                  <div v-if="aiSuggestedType" class="ai-suggestion">
                    <span>💡 <strong>{{ aiSuggestedType.label||aiSuggestedType.suggested_type_label }}</strong></span>
                    <button type="button" class="btn-ai-apply" @click="applyTypeSuggestion">Appliquer</button>
                  </div>
                  <select v-model="form.type_mission" required>
                    <option value="">-- Type --</option>
                    <option v-for="t in missionTypes" :key="t.id" :value="t.code">{{ t.label }}</option>
                  </select>
                </div>
              </div>

              <!-- DESCRIPTION / PRÉOCCUPATION -->
              <div class="row-2">
                <div class="field-col">
                  <label>Description <button v-if="form.description" type="button" class="btn-regen" @click="aiRegenField" :disabled="aiLoading.regen">🔄</button></label>
                  <textarea v-model="form.description" rows="2" placeholder="Description..."></textarea>
                </div>
                <div class="field-col">
                  <label>Préoccupation <button v-if="form.preoccupation" type="button" class="btn-regen" @click="aiRegenField" :disabled="aiLoading.regen">🔄</button></label>
                  <textarea v-model="form.preoccupation" rows="2" placeholder="Préoccupation..."></textarea>
                </div>
              </div>

              <!-- RÉSULTAT / CHAMP MISSION -->
              <div class="row-2">
                <div class="field-col">
                  <label>Résultat <button v-if="form.resultat" type="button" class="btn-regen" @click="aiRegenField" :disabled="aiLoading.regen">🔄</button></label>
                  <textarea v-model="form.resultat" rows="2" placeholder="Résultat attendu..."></textarea>
                </div>
                <div class="field-col">
                  <label>Champ mission <button v-if="form.champ_mission" type="button" class="btn-regen" @click="aiRegenField" :disabled="aiLoading.regen">🔄</button></label>
                  <textarea v-model="form.champ_mission" rows="2" placeholder="Champ de la mission..."></textarea>
                </div>
              </div>

              <!-- FONCTIONS / PROCÉDURE -->
              <div class="row-2">
                <div class="field-col">
                  <label>Fonctions / Processus <button v-if="form.fonction_processus" type="button" class="btn-regen" @click="aiRegenField" :disabled="aiLoading.regen">🔄</button></label>
                  <textarea v-model="form.fonction_processus" rows="2" placeholder="Fonctions concernées..."></textarea>
                </div>
                <div class="field-col">
                  <label>Procédure <button v-if="form.procedure" type="button" class="btn-regen" @click="aiRegenField" :disabled="aiLoading.regen">🔄</button></label>
                  <textarea v-model="form.procedure" rows="2" placeholder="Procédure..."></textarea>
                </div>
              </div>

              <!-- DATE / REMPLIR -->
              <div class="row-2">
                <div class="field-col">
                  <label>Date proposition</label>
                  <input type="date" v-model="form.proposition_date" />
                </div>
                <div class="field-col">
                  <label>&nbsp;</label>
                  <button v-if="form.but&&form.risks.length>0&&form.type_mission" type="button" class="btn-ai-fields" @click="aiGenerateFields" :disabled="aiLoading.fields">
                    <span v-if="aiLoading.fields" class="spinner-sm"></span>
                    🤖 {{ aiLoading.fields?'Génération...':'Remplir les champs' }}
                  </button>
                </div>
              </div>

              <div class="actions">
                <button type="button" @click="resetForm" class="btn-sec">Réinitialiser</button>
                <button type="submit" class="btn-pri">✅ Créer Mission</button>
              </div>
            </form>
          </div>

          <!-- LISTE MISSIONS -->
          <div class="missions-list-bottom">
            <h3>📋 Missions ({{ missions.length }})</h3>
            <div v-if="missions.length===0" class="empty">Aucune mission créée</div>
            <div v-else class="missions-scroll">
              <div v-for="m in missions" :key="m.id" class="mission-card-compact">
                <div class="m-head-compact">
                  <strong>{{ m.numero }}</strong>
                  <span class="m-type">{{ m.type_mission||'N/A' }}</span>
                  <button @click="onDelMission(m.id)" class="del">🗑️</button>
                </div>
                <div class="m-entities-compact">
                  <span v-for="e in m.entities" :key="e.id" class="entity-tag">{{ e.code_base||e.name }}</span>
                </div>
                <div class="m-but-compact">{{ cut(m.but,80) }}</div>
                <div v-if="m.risks&&m.risks.length>0" class="m-risks-compact">
                  <span v-for="risk in m.risks.slice(0,4)" :key="risk.id" class="risk-tag">{{ risk.code }}</span>
                  <span v-if="m.risks.length>4" class="more-risks">+{{ m.risks.length-4 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'

// ==================== PROPS ====================
const props = defineProps({
  entities:         { type: Array,  default: () => [] },
  selectedEntityId: { type: Number, default: null },
  /**
   * processes : tableau de TOUS les processus toutes entités confondues
   * Chaque processus doit avoir : { process_id, code, name, average_score, ranking_position }
   * La liaison entité↔processus se fait via risks.entity_id
   */
  processes:   { type: Array,  default: () => [] },
  /**
   * risks : tableau de TOUS les risques avec entity_id direct (risks.entity_id)
   * { id, code, label, entity_id, process_id, process_code, criticality_gross, status }
   */
  risks:       { type: Array,  default: () => [] },
  /**
   * ponderation : { [`${entity_id}_${process_id}`]: { year_2024:0|1, year_2025:0|1, year_2026:0|1 } }
   */
  ponderation: { type: Object, default: () => ({}) },
  factors:     { type: Array,  default: () => [] },
  scales:      { type: Array,  default: () => [] },
  /**
   * scores : { [`${process_id}_${entity_id}`]: { scores: { [factor_id]: value } } }
   */
  scores:      { type: Object, default: () => ({}) },
  missionTypes:{ type: Array,  default: () => [] },
})

const baseUrl = '/m/audit.core/audit'
const years   = [2024, 2025, 2026]

// ==================== STATE ====================
const entities     = ref(props.entities)
const processes    = ref(props.processes)
const allRisks     = ref(props.risks)       // tous les risques (avec entity_id direct)
const factors      = ref(props.factors)
const scales       = ref(props.scales)
const missionTypes = ref(props.missionTypes)

const tab = ref('pond')

// Entités cochées par onglet (indépendants)
const selectedEntityIds = ref(props.selectedEntityId ? [props.selectedEntityId] : [])
const noteEntityId      = ref(props.selectedEntityId || null)
const riskEntityIds     = ref(props.selectedEntityId ? [props.selectedEntityId] : [])
const missionEntityIds  = ref([])

// Pondération locale : clé = `${entity_id}_${process_id}`
const ponderationLocal = ref({})

// Scores locaux : clé = `${process_id}_${entity_id}`
const scoresLocal = ref({})

// Missions
const missions     = ref([])
const selectedYear = ref(2025)

// Risques enrichis pour missions (chargés via API par entité)
const missionRisksMap = ref({})  // { [entity_id]: [risk enrichi] }

// IA
const aiError           = ref(null)
const aiSuggestedType   = ref(null)
const aiGoalSuggestions = ref([])
const aiLoading = reactive({ type:false, goals:false, fields:false, revise:false, full:false, regen:false })

// Formulaire
const form = ref({
  type_mission:'', but:'', description:'', preoccupation:'',
  resultat:'', champ_mission:'', fonction_processus:'', procedure:'',
  proposition_date: new Date().toISOString().split('T')[0],
  risks: [],
})

// ==================== INIT ====================
onMounted(() => {
  initPonderation()
  initScores()
  loadMissions()
})

const initPonderation = () => {
  const p = {}
  for (const [key, data] of Object.entries(props.ponderation || {})) {
    p[key] = {
      2024: data.year_2024 === 1,
      2025: data.year_2025 === 1,
      2026: data.year_2026 === 1,
    }
  }
  ponderationLocal.value = p
}

const initScores = () => {
  const s = {}
  for (const [key, data] of Object.entries(props.scores || {})) {
    s[key] = { ...(data.scores || data) }
  }
  scoresLocal.value = s
}

// ==================== COMPUTED ====================

// Stats globales basées sur les entités cochées dans l'onglet Pondération
const processesFiltered = computed(() => {
  if (selectedEntityIds.value.length === 0) return []
  const pids = new Set(
    allRisks.value
      .filter(r => selectedEntityIds.value.includes(r.entity_id))
      .map(r => r.process_id)
  )
  return processes.value.filter(p => pids.has(p.process_id))
})

const risksFiltered = computed(() => {
  if (selectedEntityIds.value.length === 0) return []
  return allRisks.value.filter(r => selectedEntityIds.value.includes(r.entity_id))
})

// ==================== HELPERS ====================

const getEntityName = (id) => entities.value.find(e => e.id === id)?.name || `Entité #${id}`

/**
 * Processus d'une entité = processus ayant au moins un risque avec risks.entity_id = entityId
 */
const getProcessesForEntity = (entityId) => {
  const processIds = [...new Set(
    allRisks.value.filter(r => r.entity_id === entityId).map(r => r.process_id)
  )]
  return processes.value.filter(p => processIds.includes(p.process_id))
}

/**
 * Tous les risques d'une entité (jointure directe via risks.entity_id)
 */
const getRisksForEntityDirect = (entityId) => {
  return allRisks.value.filter(r => r.entity_id === entityId)
}

/**
 * Nombre de risques d'une entité pour un processus donné
 */
const getRisksCountEntityProcess = (entityId, processId) => {
  return allRisks.value.filter(r => r.entity_id === entityId && r.process_id === processId).length
}

/**
 * Risques d'une entité enrichis avec average_score et ranking (onglet Risques)
 */
const getRisksForEntityEnriched = (entityId) => {
  return allRisks.value
    .filter(r => r.entity_id === entityId)
    .map(r => {
      const proc    = processes.value.find(p => p.process_id === r.process_id)
      const procAvg = proc?.average_score || 0
      return { ...r, process_average_score: procAvg, risk_average: (r.criticality_gross || 0) * procAvg }
    })
    .sort((a, b) => b.risk_average - a.risk_average)
    .map((r, i) => ({ ...r, rank: i + 1 }))
}

/**
 * Risques pour les missions — utilise missionRisksMap si disponible, sinon allRisks
 */
const getMissionRisksForEntity = (entityId) => {
  if (missionRisksMap.value[entityId]) return missionRisksMap.value[entityId]
  return allRisks.value
    .filter(r => r.entity_id === entityId)
    .map(r => {
      const proc = processes.value.find(p => p.process_id === r.process_id)
      return { ...r, process_average_score: proc?.average_score || 0 }
    })
    .sort((a, b) => (b.criticality_gross||0) - (a.criticality_gross||0))
}

const findRisk = (id) => {
  for (const list of Object.values(missionRisksMap.value)) {
    const f = list.find(r => r.id === id)
    if (f) return f
  }
  return allRisks.value.find(r => r.id === id) || null
}

const cut = (txt, len) => {
  if (!txt) return ''
  return txt.length > len ? txt.slice(0, len) + '…' : txt
}

const critClass = (c) => { if (c>=12) return 'high'; if (c>=8) return 'med'; if (c>=5) return 'low'; return '' }
const riskAvgClass = (v) => { if (v>=15) return 'avg-high'; if (v>=10) return 'avg-med'; if (v>=5) return 'avg-low'; return '' }
const removeRisk = (id) => { form.value.risks = form.value.risks.filter(r => r !== id) }

const resetForm = () => {
  form.value = {
    type_mission:'', but:'', description:'', preoccupation:'',
    resultat:'', champ_mission:'', fonction_processus:'', procedure:'',
    proposition_date: new Date().toISOString().split('T')[0],
    risks: [],
  }
  aiSuggestedType.value = null
  aiGoalSuggestions.value = []
  aiError.value = null
}

// ==================== PONDÉRATION ====================

const getPonderation = (entityId, processId, year) => {
  return ponderationLocal.value[`${entityId}_${processId}`]?.[year] === true
}

const onToggleYear = async (entityId, processId, year, checked) => {
  const key = `${entityId}_${processId}`
  if (!ponderationLocal.value[key]) {
    ponderationLocal.value[key] = { 2024: false, 2025: false, 2026: false }
  }
  ponderationLocal.value[key][year] = checked
  try {
    await axios.post(`${baseUrl}/process-ponderation`, {
      entity_id: entityId, process_id: processId, year, is_selected: checked ? 1 : 0,
    })
  } catch (e) {
    ponderationLocal.value[key][year] = !checked  // rollback
    aiError.value = 'Erreur sauvegarde pondération'
  }
}

// ==================== NOTATION ====================

const getScore = (processId, factorId, entityId) => {
  return scoresLocal.value[`${processId}_${entityId}`]?.[factorId] || 0
}

const onSaveScore = async (processId, factorId, value, entityId) => {
  const key = `${processId}_${entityId}`
  if (!scoresLocal.value[key]) scoresLocal.value[key] = {}
  scoresLocal.value[key][factorId] = value
  try {
    await axios.put(`${baseUrl}/process-scoring/${processId}/${entityId}`, {
      factor_scores: { [factorId]: value },
    })
    window.location.reload()
  } catch (e) { aiError.value = 'Erreur sauvegarde score' }
}

// ==================== MISSIONS ====================

const onMissionEntitiesChange = async () => {
  if (missionEntityIds.value.length === 0) { missionRisksMap.value = {}; return }
  try {
    const { data } = await axios.post(`${baseUrl}/risks/by-entities`, {
      entity_ids: missionEntityIds.value,
      year: selectedYear.value,
    })
    if (data.success) {
      const map = {}
      for (const [eid, group] of Object.entries(data.risks_by_entity)) {
        map[parseInt(eid)] = group.risks
      }
      missionRisksMap.value = map
    } else { aiError.value = data.error }
  } catch (e) { aiError.value = e?.response?.data?.error || 'Erreur de communication' }
}

const loadMissions = async () => {
  const eids = [...new Set([...selectedEntityIds.value, ...missionEntityIds.value])]
  if (eids.length === 0) return
  try {
    const res = await axios.get(`${baseUrl}/missions`, { params: { entity_id: eids[0] } })
    missions.value = res.data.missions || []
  } catch (e) { console.error(e) }
}

const onCreateMission = async () => {
  if (missionEntityIds.value.length === 0) { aiError.value = 'Sélectionnez au moins une entité'; return }
  if (!form.value.but.trim() || !form.value.type_mission) { aiError.value = 'Type et But obligatoires'; return }
  try {
    const res = await axios.post(`${baseUrl}/missions`, {
      entity_ids: missionEntityIds.value,
      selected_risk_ids: form.value.risks,
      type_mission: form.value.type_mission,
      but: form.value.but,
      description: form.value.description,
      preoccupation: form.value.preoccupation,
      resultat: form.value.resultat,
      champ_mission: form.value.champ_mission,
      fonction_processus: form.value.fonction_processus,
      procedure: form.value.procedure,
      proposition_date: form.value.proposition_date,
    })
    if (res.data.success) { await loadMissions(); resetForm(); alert('✅ Mission créée !') }
  } catch (e) { aiError.value = e?.response?.data?.error || 'Erreur création mission' }
}

const onDelMission = async (id) => {
  if (!confirm('Supprimer cette mission ?')) return
  try { await axios.delete(`${baseUrl}/missions/${id}`); await loadMissions() }
  catch (e) { aiError.value = 'Erreur suppression' }
}

// ==================== IA ====================
const aiFullProposal = async () => {
  if (!form.value.risks.length) { aiError.value = 'Sélectionnez au moins un risque'; return }
  aiLoading.full = true; aiError.value = null
  try {
    const { data } = await axios.post(`${baseUrl}/ai/full-proposal`, { risk_ids: form.value.risks })
    if (data.success) {
      if (data.buts?.length) { aiGoalSuggestions.value = data.buts; form.value.but = data.buts[0]?.but||'' }
      if (data.type)   aiSuggestedType.value = data.type
      if (data.fields) applyFields(data.fields)
    } else aiError.value = data.error
  } catch (e) { aiError.value = e?.response?.data?.error||'Erreur IA' }
  finally { aiLoading.full = false }
}

const aiSuggestGoals = async () => {
  if (!form.value.risks.length) { aiError.value = 'Sélectionnez un risque'; return }
  aiLoading.goals = true; aiGoalSuggestions.value = []
  try {
    const { data } = await axios.post(`${baseUrl}/ai/suggest-goals`, { risk_ids: form.value.risks })
    if (data.success) aiGoalSuggestions.value = data.suggestions||[]
    else aiError.value = data.error
  } catch (e) { aiError.value = e?.response?.data?.error||'Erreur IA' }
  finally { aiLoading.goals = false }
}

const applyGoal = (but) => {
  form.value.but = but; aiGoalSuggestions.value = []
  if (form.value.risks.length > 0) setTimeout(() => aiSuggestType(), 100)
}

const aiReviseGoal = async () => {
  if (!form.value.but || !form.value.risks.length) return
  aiLoading.revise = true
  try {
    const { data } = await axios.post(`${baseUrl}/ai/revise-goal`, { current_goal: form.value.but, risk_ids: form.value.risks })
    if (data.success && data.but_reformule) form.value.but = data.but_reformule
    else aiError.value = data.error
  } catch (e) { aiError.value = e?.response?.data?.error||'Erreur IA' }
  finally { aiLoading.revise = false }
}

const aiSuggestType = async () => {
  if (!form.value.but || !form.value.risks.length) return
  aiLoading.type = true; aiSuggestedType.value = null
  try {
    const { data } = await axios.post(`${baseUrl}/ai/suggest-type`, { risk_ids: form.value.risks, selected_goal: form.value.but })
    if (data.success && data.type) aiSuggestedType.value = data.type
  } catch (e) { aiError.value = e?.response?.data?.error||'Erreur IA' }
  finally { aiLoading.type = false }
}

const applyTypeSuggestion = () => {
  if (aiSuggestedType.value) {
    form.value.type_mission = aiSuggestedType.value.code || aiSuggestedType.value.suggested_type_code
    aiSuggestedType.value = null
  }
}

const aiGenerateFields = async () => {
  if (!form.value.but || !form.value.type_mission || !form.value.risks.length) {
    aiError.value = 'But, type et risques requis'; return
  }
  aiLoading.fields = true
  try {
    const { data } = await axios.post(`${baseUrl}/ai/generate-fields`, {
      risk_ids: form.value.risks, selected_goal: form.value.but, type_code: form.value.type_mission,
    })
    if (data.success && data.fields) applyFields(data.fields)
    else aiError.value = data.error
  } catch (e) { aiError.value = e?.response?.data?.error||'Erreur IA' }
  finally { aiLoading.fields = false }
}

const aiRegenField = async () => {
  aiLoading.regen = true
  try { await aiGenerateFields() } finally { aiLoading.regen = false }
}

const applyFields = (f) => {
  form.value.description        = f.description||''
  form.value.preoccupation      = f.preoccupation||''
  form.value.resultat           = f.resultat||''
  form.value.champ_mission      = f.champ_mission||''
  form.value.fonction_processus = f.fonction_processus||''
  form.value.procedure          = f.procedure||''
}

// Recharger les risques quand l'année change (onglet missions)
watch(() => selectedYear.value, () => {
  if (missionEntityIds.value.length > 0) onMissionEntitiesChange()
})

watch(() => form.value.risks, (val) => {
  if (val.length === 0) { aiSuggestedType.value = null; aiGoalSuggestions.value = [] }
})
</script>

<style scoped>
.audit-container {
  max-width: 1800px; margin: 0 auto; padding: 1rem;
  background: #f0f2f5; min-height: 100vh;
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; font-size: 0.85rem;
}

/* HEADER */
.header { background: linear-gradient(135deg,#1565c0,#1976d2,#1e88e5); color:white; padding:1.2rem 1.5rem; border-radius:10px; margin-bottom:0.8rem; box-shadow:0 2px 8px rgba(25,118,210,.3); }
.header h1 { margin:0 0 0.25rem; font-size:1.4rem; }
.header p  { margin:0; opacity:.85; font-size:0.88rem; }

/* STATS */
.stats-bar { display:flex; gap:1.2rem; background:white; border:1px solid #e0e0e0; border-radius:8px; padding:0.6rem 1rem; margin-bottom:0.8rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.stat-item { display:flex; align-items:baseline; gap:0.35rem; }
.stat-num  { font-size:1.4rem; font-weight:800; color:#1565c0; line-height:1; }
.stat-lbl  { font-size:0.73rem; color:#888; }

/* TABS */
.tabs-wrapper { background:white; border:1px solid #e0e0e0; border-radius:10px 10px 0 0; padding:0.4rem 0.8rem 0; }
.tabs { display:flex; gap:0.2rem; }
.tabs button { padding:0.6rem 1.2rem; border:none; background:none; cursor:pointer; font-weight:600; font-size:0.85rem; border-bottom:3px solid transparent; transition:all 0.2s; border-radius:6px 6px 0 0; color:#666; }
.tabs button:hover { background:#f5f5f5; color:#333; }
.tabs button.active { color:#1565c0; border-bottom-color:#1565c0; background:#e3f2fd; }

/* TAB PANEL */
.tab-panel { background:white; border:1px solid #e0e0e0; border-top:none; border-radius:0 0 10px 10px; padding:1.2rem; box-shadow:0 1px 3px rgba(0,0,0,.05); margin-bottom:1rem; }
.tab-panel h2 { margin:0 0 0.6rem; color:#1565c0; font-size:1.1rem; }

/* SÉLECTEUR ENTITÉS */
.entity-selector-panel { display:flex; align-items:center; gap:0.7rem; flex-wrap:wrap; background:#f8f9ff; border:1px solid #c5cae9; border-radius:8px; padding:0.55rem 0.9rem; margin-bottom:1rem; }
.esp-title { font-weight:700; color:#3949ab; font-size:0.82rem; white-space:nowrap; }
.esp-checks { display:flex; flex-wrap:wrap; gap:0.35rem; flex:1; }
.ent-chip { display:inline-flex; align-items:center; gap:0.28rem; padding:0.26rem 0.65rem; border:1.5px solid #c5cae9; border-radius:20px; background:white; cursor:pointer; font-size:0.8rem; font-weight:500; color:#555; transition:all 0.15s; user-select:none; }
.ent-chip:hover { border-color:#3949ab; color:#3949ab; background:#e8eaf6; }
.ent-chip.active { background:#3949ab; color:white; border-color:#3949ab; }
.ent-chip input { display:none; }
.btn-clear { background:none; border:1px solid #ef5350; color:#ef5350; padding:0.22rem 0.55rem; border-radius:4px; cursor:pointer; font-size:0.73rem; white-space:nowrap; transition:all 0.15s; }
.btn-clear:hover { background:#ef5350; color:white; }

/* PONDÉRATION BLOCS */
.pond-header { margin-bottom:0.8rem; }
.subtitle { margin:0; color:#888; font-size:0.8rem; }
.entity-pond-block { margin-bottom:1.2rem; border:1px solid #e0e0e0; border-radius:8px; overflow:hidden; }
.epb-header { display:flex; align-items:center; gap:0.6rem; padding:0.48rem 0.9rem; background:linear-gradient(90deg,#e8eaf6,#f5f5f5); border-bottom:1px solid #e0e0e0; }
.entity-badge { font-weight:700; font-size:0.85rem; color:#3949ab; }
.epb-count { background:#e8eaf6; color:#3949ab; padding:0.1rem 0.42rem; border-radius:10px; font-size:0.7rem; font-weight:600; }

/* TABLES */
table { width:100%; border-collapse:collapse; font-size:0.82rem; background:white; }
thead { background:#1565c0; color:white; }
th, td { padding:0.52rem 0.7rem; text-align:left; border-bottom:1px solid #e8e8e8; }
th { font-weight:600; font-size:0.77rem; text-transform:uppercase; letter-spacing:0.03em; }
tbody tr:hover { background:#f5f7ff; }
.center { text-align:center; }
.empty-row { text-align:center; color:#bbb; font-style:italic; padding:1rem; }
.empty-row-inline { padding:0.4rem 0.6rem; color:#bbb; font-style:italic; font-size:0.77rem; }
.table-scroll { overflow-x:auto; }

/* CASES À COCHER ANNÉE */
.year-cell { background:#fafafa; text-align:center; }
.year-check { width:17px; height:17px; cursor:pointer; accent-color:#1565c0; display:inline-block; vertical-align:middle; }

/* CODES & BADGES */
.code { background:#1565c0; color:white; padding:0.11rem 0.38rem; border-radius:4px; font-size:0.72rem; font-weight:700; font-family:'Consolas',monospace; display:inline-block; }
.code.risk { background:#e65100; }
.crit { padding:0.11rem 0.38rem; border-radius:4px; font-weight:700; font-size:0.72rem; color:white; display:inline-block; min-width:25px; text-align:center; }
.crit.high { background:#c62828; }
.crit.med  { background:#e65100; }
.crit.low  { background:#f9a825; color:#333; }
.avg-high { color:#c62828; }
.avg-med  { color:#e65100; }
.avg-low  { color:#f9a825; }
.rank-badge { background:#5e35b1; color:white; padding:0.11rem 0.42rem; border-radius:10px; font-size:0.7rem; font-weight:700; }
.risk-count-badge { background:#e3f2fd; color:#1565c0; padding:0.11rem 0.42rem; border-radius:10px; font-size:0.72rem; font-weight:600; }
.status-chip { padding:0.11rem 0.42rem; border-radius:10px; font-size:0.7rem; font-weight:600; }
.status-chip.identified { background:#e3f2fd; color:#1565c0; }
.status-chip.assessed   { background:#fff8e1; color:#e65100; }
.status-chip.mitigated  { background:#e8f5e9; color:#2e7d32; }
.status-chip.monitored  { background:#f3e5f5; color:#6a1b9a; }
.status-chip.closed     { background:#f5f5f5; color:#757575; }

/* NOTATION */
.scores { display:flex; gap:0.11rem; justify-content:center; }
.score-btn { width:25px; height:25px; border:1px solid #ddd; background:white; border-radius:4px; cursor:pointer; font-weight:700; font-size:0.78rem; transition:all 0.15s; }
.score-btn:hover { border-color:#1565c0; background:#e3f2fd; }
.score-btn.on { background:#43a047; color:white; border-color:#43a047; }

/* MISSIONS */
.year-filter { display:flex; align-items:center; gap:0.4rem; margin-bottom:0.8rem; padding:0.42rem 0.8rem; background:white; border-radius:6px; border:1px solid #e0e0e0; }
.year-filter label { font-size:0.82rem; }
.year-filter-btn { padding:0.26rem 0.65rem; border:1px solid #ddd; background:white; border-radius:4px; cursor:pointer; font-size:0.78rem; font-weight:600; transition:all 0.15s; }
.year-filter-btn.active { background:#1565c0; color:white; border-color:#1565c0; }
.missions-layout { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:0.8rem; }
.left-half, .right-half { border:1px solid #e0e0e0; border-radius:10px; padding:0.8rem; background:#fafafa; max-height:860px; overflow-y:auto; }
.left-half h3, .right-half h3 { margin:0 0 0.8rem; color:#1565c0; font-size:0.95rem; padding-bottom:0.4rem; border-bottom:2px solid #e3f2fd; }

/* RISQUES AVEC CASES */
.entity-risks-container { display:flex; flex-direction:column; gap:0.8rem; }
.entity-group-title { display:flex; align-items:center; gap:0.5rem; margin:0 0 0.4rem; color:#1565c0; font-size:0.87rem; font-weight:700; border-bottom:1px dashed #c5cae9; padding-bottom:0.22rem; }
.risk-check-item { background:white; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:0.22rem; transition:all 0.1s; }
.risk-check-item:hover { background:#f5f7ff; border-color:#c5cae9; }
.risk-check-item.selected { background:#e8f4fd; border-color:#1976d2; }
.risk-check-label { display:flex; align-items:center; gap:0.32rem; padding:0.36rem 0.58rem; cursor:pointer; font-size:0.77rem; }
.risk-check-label input[type="checkbox"] { width:15px; height:15px; cursor:pointer; accent-color:#1565c0; flex-shrink:0; }
.process-code { background:#e3f2fd; color:#1565c0; padding:0.08rem 0.32rem; border-radius:3px; font-weight:700; font-family:monospace; font-size:0.68rem; white-space:nowrap; }
.risk-label { flex:1; color:#333; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.score-tag { background:#43a047; color:white; padding:0.08rem 0.36rem; border-radius:4px; font-weight:700; font-size:0.67rem; white-space:nowrap; }
.risk-avg-tag { background:#7b1fa2; color:white; padding:0.08rem 0.4rem; border-radius:4px; font-weight:700; font-size:0.67rem; white-space:nowrap; }

/* FORMULAIRE */
.form-box-full { background:white; border:1px solid #e0e0e0; border-radius:10px; padding:0.8rem; margin-bottom:0.8rem; }
.req { color:#c62828; }
.row-2 { display:grid; grid-template-columns:1fr 1fr; gap:0.55rem; margin-bottom:0.5rem; }
.field-col { display:flex; flex-direction:column; gap:0.18rem; }
.field-col label, .field-full label { font-weight:600; font-size:0.76rem; color:#555; display:flex; align-items:center; gap:0.28rem; flex-wrap:wrap; }
.field-col select, .field-col textarea, .field-col input,
.field-full textarea, .field-full input { padding:0.3rem 0.48rem; border:1px solid #ddd; border-radius:5px; font-family:inherit; font-size:0.8rem; transition:border-color 0.2s; background:#fafafa; }
.field-col select:focus, .field-col textarea:focus, .field-col input:focus,
.field-full textarea:focus, .field-full input:focus { border-color:#1565c0; outline:none; background:white; box-shadow:0 0 0 2px rgba(21,101,192,.1); }
.field-col textarea, .field-full textarea { resize:vertical; min-height:38px; }
.field-full { display:flex; flex-direction:column; gap:0.18rem; margin-bottom:0.5rem; }

/* RISQUES SÉLECTIONNÉS */
.selected-risks-summary { border:1px solid #e0e0e0; border-radius:6px; padding:0.42rem; background:#fff; min-height:44px; margin-bottom:0.38rem; }
.empty-small { text-align:center; color:#bbb; font-style:italic; padding:0.38rem; font-size:0.77rem; }
.selected-risks-list { display:flex; flex-wrap:wrap; gap:0.22rem; }
.selected-risk-tag { display:inline-flex; align-items:center; gap:0.22rem; background:#e3f2fd; border:1px solid #bbdefb; border-radius:4px; padding:0.16rem 0.42rem; font-size:0.72rem; }
.risk-label-mini { max-width:105px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.remove-btn-small { background:#ef5350; color:white; border:none; border-radius:50%; width:14px; height:14px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.57rem; }
.remove-btn-small:hover { background:#c62828; }

/* ACTIONS */
.actions { display:flex; gap:0.55rem; justify-content:flex-end; margin-top:0.75rem; padding-top:0.55rem; border-top:1px solid #e8e8e8; }
.btn-sec { padding:0.36rem 0.85rem; background:#f5f5f5; border:1px solid #ddd; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.79rem; }
.btn-sec:hover { background:#e0e0e0; }
.btn-pri { padding:0.36rem 1rem; background:#43a047; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.79rem; box-shadow:0 2px 4px rgba(67,160,71,.3); }
.btn-pri:hover { background:#2e7d32; transform:translateY(-1px); }

/* MISSIONS LISTE */
.missions-list-bottom { background:white; border:1px solid #e0e0e0; border-radius:10px; padding:0.8rem; }
.missions-scroll { max-height:255px; overflow-y:auto; }
.mission-card-compact { background:#fafafa; border:1px solid #e8e8e8; border-radius:6px; padding:0.52rem; margin-bottom:0.32rem; transition:border-color 0.15s; }
.mission-card-compact:hover { border-color:#1565c0; }
.m-head-compact { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.16rem; gap:0.35rem; }
.m-type { background:#e3f2fd; color:#1565c0; padding:0.07rem 0.3rem; border-radius:3px; font-size:0.66rem; font-weight:700; }
.del { background:none; border:none; cursor:pointer; font-size:0.85rem; color:#bbb; transition:all 0.15s; }
.del:hover { color:#c62828; }
.m-entities-compact { display:flex; gap:0.16rem; flex-wrap:wrap; margin:0.14rem 0; }
.entity-tag { background:#e8eaf6; color:#3949ab; padding:0.05rem 0.26rem; border-radius:3px; font-size:0.63rem; font-weight:600; border:1px solid #c5cae9; }
.m-but-compact { font-size:0.73rem; color:#777; line-height:1.3; margin-bottom:0.16rem; }
.m-risks-compact { display:flex; gap:0.16rem; flex-wrap:wrap; }
.risk-tag { background:#fff3e0; color:#e65100; padding:0.05rem 0.24rem; border-radius:3px; font-size:0.62rem; border:1px solid #ffcc80; font-weight:600; }
.more-risks { color:#bbb; font-size:0.62rem; font-style:italic; }

/* IA */
.ai-badge { background:linear-gradient(135deg,#7c3aed,#9333ea); color:#fff; padding:0.11rem 0.42rem; border-radius:10px; font-size:0.66rem; font-weight:700; margin-left:0.32rem; }
.ai-error { background:#fef2f2; border:1px solid #fca5a5; color:#dc2626; padding:0.42rem 0.8rem; border-radius:6px; margin-bottom:0.8rem; display:flex; align-items:center; gap:0.5rem; font-size:0.79rem; }
.ai-error-close { background:none; border:none; color:#dc2626; font-size:1.1rem; cursor:pointer; margin-left:auto; }
.ai-bar { display:flex; margin-top:0.38rem; padding:0.42rem 0.58rem; background:#ede9fe; border:1px dashed #7c3aed; border-radius:6px; }
.btn-ai-full { background:linear-gradient(135deg,#7c3aed,#9333ea); color:#fff; border:none; padding:0.38rem 0.85rem; border-radius:6px; font-weight:700; font-size:0.77rem; cursor:pointer; display:flex; align-items:center; gap:0.28rem; transition:all 0.2s; }
.btn-ai-full:hover:not(:disabled) { transform:translateY(-1px); }
.btn-ai-full:disabled { opacity:0.6; cursor:not-allowed; }
.btn-ai-mini { background:#ede9fe; color:#7c3aed; border:1px solid #7c3aed; padding:0.12rem 0.38rem; border-radius:4px; font-weight:700; font-size:0.66rem; cursor:pointer; display:inline-flex; align-items:center; gap:0.16rem; transition:all 0.15s; }
.btn-ai-mini:hover:not(:disabled) { background:#7c3aed; color:#fff; }
.btn-ai-mini:disabled { opacity:0.5; cursor:not-allowed; }
.btn-ai-revise { border-color:#d97706; color:#d97706; background:#fffbeb; }
.btn-ai-revise:hover:not(:disabled) { background:#d97706; color:#fff; }
.btn-ai-fields { background:linear-gradient(135deg,#7c3aed,#9333ea); color:#fff; border:none; padding:0.36rem 0.75rem; border-radius:6px; font-weight:600; font-size:0.75rem; cursor:pointer; display:flex; align-items:center; gap:0.28rem; width:100%; justify-content:center; transition:all 0.2s; }
.btn-ai-fields:disabled { opacity:0.6; cursor:not-allowed; }
.btn-ai-apply { background:#7c3aed; color:#fff; border:none; padding:0.16rem 0.42rem; border-radius:4px; font-size:0.68rem; font-weight:700; cursor:pointer; margin-left:0.38rem; }
.btn-regen { background:none; border:1px solid #ddd; padding:0.07rem 0.26rem; border-radius:3px; font-size:0.62rem; cursor:pointer; transition:all 0.15s; }
.btn-regen:hover:not(:disabled) { border-color:#7c3aed; background:#ede9fe; }
.btn-regen:disabled { opacity:0.4; cursor:not-allowed; }
.ai-btns-inline { display:inline-flex; gap:0.26rem; margin-left:auto; }
.ai-suggestion { background:#ede9fe; border:1px solid rgba(124,58,237,.2); border-radius:6px; padding:0.36rem 0.58rem; margin-bottom:0.38rem; font-size:0.75rem; display:flex; align-items:center; gap:0.38rem; animation:aiFade 0.3s ease; }
.ai-goals { display:flex; flex-direction:column; gap:0.26rem; margin-bottom:0.42rem; animation:aiFade 0.3s ease; }
.ai-goal { display:flex; align-items:flex-start; gap:0.42rem; padding:0.42rem 0.58rem; background:#fff; border:1px solid #e0e0e0; border-radius:6px; cursor:pointer; transition:all 0.15s; }
.ai-goal:hover { border-color:#7c3aed; background:#ede9fe; }
.ai-goal.best { border-color:#7c3aed; background:#f5f3ff; }
.ai-goal-rank { width:19px; height:19px; border-radius:50%; background:#e0e0e0; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.68rem; flex-shrink:0; }
.ai-goal.best .ai-goal-rank { background:#7c3aed; color:#fff; }
.ai-goal strong { font-size:0.75rem; display:block; }
.ai-goal p { font-size:0.68rem; color:#666; margin:0.07rem 0 0; line-height:1.3; }
.spinner-sm { display:inline-block; width:12px; height:12px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin 0.6s linear infinite; }
.spinner-xs { display:inline-block; width:9px; height:9px; border:1.5px solid rgba(124,58,237,.2); border-top-color:#7c3aed; border-radius:50%; animation:spin 0.6s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
@keyframes aiFade { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }

/* VIDE */
.empty { text-align:center; padding:1.3rem; color:#bbb; font-style:italic; background:#fafafa; border-radius:6px; border:1px dashed #ddd; font-size:0.81rem; }
.empty-state { text-align:center; padding:2.5rem; background:#fafafa; border-radius:8px; border:2px dashed #ddd; color:#999; font-size:1rem; margin-top:0.5rem; }

/* RESPONSIVE */
@media (max-width:1200px) { .missions-layout { grid-template-columns:1fr; } .left-half,.right-half { max-height:500px; } }
@media (max-width:768px) {
  .tabs button { font-size:0.77rem; padding:0.42rem 0.75rem; }
  .row-2 { grid-template-columns:1fr; }
  .entity-selector-panel { flex-direction:column; align-items:flex-start; }
}
</style>