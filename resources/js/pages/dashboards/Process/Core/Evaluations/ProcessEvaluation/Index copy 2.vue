<template>
  <VerticalLayout>
    <Head title="Évaluation des processus" />

    <!-- ========================================================= -->
    <!-- HEADER PRINCIPAL -->
    <!-- ========================================================= -->
    <b-card
      class="mb-4 border-0 shadow-lg p-4"
      style="background: linear-gradient(135deg,#10b981,#059669); border-radius:12px;"
    >
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h2 class="fw-bold mb-2 text-white">Évaluation des processus</h2>
          <p class="mb-0 text-white" style="opacity:.95;">
            <strong>{{ user?.name }}</strong>
            <span v-if="link" class="ms-2">
              <i class="ti ti-arrow-right"></i>
              <strong>{{ link.function_name }}</strong> — <em>{{ link.entity_name }}</em>
            </span>
          </p>
        </div>
      </div>
    </b-card>

    <!-- ========================================================= -->
    <!-- VUE 1 : LISTE DES PROCESSUS -->
    <!-- ========================================================= -->
    <div v-if="!selectedProcess">
      <b-card class="border-0 shadow-sm mb-4">
        <h5 class="fw-bold mb-0">
          <i class="ti ti-list me-2"></i>Processus disponibles
        </h5>
      </b-card>

      <!-- STATS RÉSUMÉ -->
      <b-row class="mb-4 g-3">
        <b-col md="4">
          <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#ffffff);border-left:5px solid #10b981;">
            <div class="fw-bold" style="font-size:2rem;color:#10b981;">{{ processes.length }}</div>
            <small class="text-muted">Processus total</small>
          </b-card>
        </b-col>
        <b-col md="4">
          <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#ffffff);border-left:5px solid #10b981;">
            <div class="fw-bold" style="font-size:2rem;color:#10b981;">{{ countTotalEvaluations() }}</div>
            <small class="text-muted">Évaluations</small>
          </b-card>
        </b-col>
        <b-col md="4">
          <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#ffffff);border-left:5px solid #10b981;">
            <div class="fw-bold" style="font-size:2rem;color:#10b981;">{{ getGlobalAverageScore().toFixed(2) }}</div>
            <small class="text-muted">Score moyen global</small>
          </b-card>
        </b-col>
      </b-row>

      <!-- TABLEAU PROCESSUS -->
      <b-card class="border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead style="background:#f3f4f6;border-bottom:2px solid #10b981;">
              <tr>
                <th style="min-width:80px;">Code</th>
                <th style="min-width:250px;">Processus</th>
                <th class="text-center" style="min-width:120px;">
                  <i class="ti ti-chart-line me-1"></i>Évaluations
                </th>
                <th class="text-center" style="min-width:120px;">
                  <i class="ti ti-star me-1"></i>Score moyen
                </th>
                <th class="text-center" style="min-width:100px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="process in processes" :key="process.id" class="process-row">
                <!-- Code -->
                <td>
                  <span class="badge bg-success fw-bold" style="font-size:0.9rem;">{{ process.code }}</span>
                </td>
                
                <!-- Nom -->
                <td>
                  <strong style="font-size:1rem;">{{ process.name }}</strong><br>
                  <small class="text-muted">{{ truncateText(process.description, 100) }}</small>
                </td>

                <!-- Nombre d'évaluations -->
                <td class="text-center">
                  <span class="badge" :style="{ background: countProcessEvaluations(process.id) > 0 ? '#10b981' : '#d1d5db', color: 'white', padding: '0.5rem 0.75rem', fontSize: '0.95rem' }">
                    {{ countProcessEvaluations(process.id) }}
                  </span>
                </td>

                <!-- Score moyen -->
                <td class="text-center">
                  <div style="display: inline-block; position: relative;">
                    <div class="score-badge-large" :style="{ borderColor: getColorByScore(getProcessAverageScore(process.id)) }">
                      {{ getProcessAverageScore(process.id).toFixed(2) }}
                    </div>
                    <small class="text-muted" style="display:block;margin-top:4px;">/5</small>
                  </div>
                </td>

                <!-- Actions -->
                <td class="text-center">
                  <b-button 
                    variant="success" 
                    size="sm" 
                    class="fw-bold"
                    @click="selectProcess(process)">
                    <i class="ti ti-arrow-right me-1"></i> Ouvrir
                  </b-button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </b-card>
    </div>

    <!-- ========================================================= -->
    <!-- VUE 2 : SESSIONS ET ÉVALUATIONS DU PROCESSUS -->
    <!-- ========================================================= -->
    <div v-else>
      <!-- Header Processus -->
      <b-card class="mb-4 border-0 shadow-sm" style="background:#ecfdf5;border-left:5px solid #10b981;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h5 class="fw-bold text-success">{{ selectedProcess.code }} — {{ selectedProcess.name }}</h5>
            <small class="text-muted">{{ selectedProcess.description }}</small>
          </div>
          <b-button variant="outline-success" size="sm" @click="backToProcessList" class="fw-bold">
            <i class="ti ti-arrow-left me-1"></i> Retour
          </b-button>
        </div>
      </b-card>

      <!-- GESTION SESSIONS -->
      <b-card class="mb-4 border-0 shadow-sm">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
          <h5 class="fw-bold mb-0">
            <i class="ti ti-calendar-event me-2"></i>Session d'évaluation
          </h5>
          <b-button variant="success" size="sm" class="fw-bold" @click="showCreateSessionModal = true">
            <i class="ti ti-plus me-1"></i> Créer
          </b-button>
        </div>

        <select v-model="selectedSession" @change="onSessionSelected" class="form-select mb-3" style="max-width:400px;">
          <option value="">— Choisir une session —</option>
          <option v-for="s in processSessions" :value="s.id" :key="s.id">
            {{ s.name }} ({{ formatDate(s.created_at) }}) — {{ s.status === 'open' ? '🟢 Ouvert' : '🔴 Clôturé' }}
          </option>
        </select>

        <!-- Actions Session -->
        <div v-if="selectedSession" class="d-flex gap-2 flex-wrap mb-3">
          <b-button size="sm" variant="outline-secondary" @click="closeSession" 
            v-if="getCurrentSession()?.status === 'open'" class="fw-bold">
            <i class="ti ti-lock me-1"></i> Clôturer
          </b-button>
          <b-button size="sm" variant="outline-secondary" @click="duplicateSession" 
            v-if="getCurrentSession()?.status === 'closed'" class="fw-bold">
            <i class="ti ti-copy me-1"></i> Dupliquer
          </b-button>
          <b-button size="sm" variant="outline-danger" @click="deleteSession" class="fw-bold">
            <i class="ti ti-trash me-1"></i> Supprimer
          </b-button>
        </div>

        <div v-if="successMessage" class="alert alert-success mb-0">
          <i class="ti ti-check me-1"></i> {{ successMessage }}
        </div>
        <div v-if="error" class="alert alert-danger mb-0">
          <i class="ti ti-alert-circle me-1"></i> {{ error }}
        </div>
      </b-card>

      <!-- MODAL CRÉATION SESSION -->
      <b-modal v-model="showCreateSessionModal" title="Créer une nouvelle session"
        ok-title="Créer" cancel-title="Annuler" @ok="createSession" centered>
        <label class="fw-bold">Nom</label>
        <input v-model="newSessionName" class="form-control mb-3" placeholder="ex: Q1 2025" />
        <label class="fw-bold">Couleur</label>
        <input v-model="newSessionColor" type="color" class="form-control form-control-color" style="width:80px;height:40px;" />
      </b-modal>

      <!-- TABLEAU ÉVALUATIONS DE LA SESSION -->
      <b-card v-if="selectedSession" class="border-0 shadow-sm">
        <h5 class="fw-bold mb-3">
          <i class="ti ti-chart-line me-2"></i>Évaluations
        </h5>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead style="background:#f3f4f6;border-bottom:2px solid #10b981;">
              <tr>
                <th style="min-width:100px;">Score</th>
                <th class="text-center" style="min-width:100px;">Maturité</th>
                <th class="text-center" style="min-width:100px;">Motricité</th>
                <th class="text-center" style="min-width:120px;">Transversalité</th>
                <th class="text-center" style="min-width:120px;">Stratégique</th>
                <th class="text-center" style="min-width:80px;">Taux</th>
                <th class="text-center" style="min-width:100px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr class="evaluation-row">
                <!-- Score Global -->
                <td>
                  <span class="score-badge" :style="{ borderColor: getColorByScore(currentEvaluation?.criticality_score || 0) }">
                    {{ (currentEvaluation?.criticality_score || 0).toFixed(1) }}
                  </span>
                </td>

                <!-- Maturité -->
                <td class="text-center">
                  <span v-if="currentEvaluation?.maturity_score" class="badge-score"
                    :style="{ background: getColorByLevel(currentEvaluation.maturity_score) }">
                    {{ currentEvaluation.maturity_score.toFixed(1) }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>

                <!-- Motricité -->
                <td class="text-center">
                  <span v-if="currentEvaluation?.motricity_score" class="badge-score"
                    :style="{ background: getColorByLevel(currentEvaluation.motricity_score) }">
                    {{ currentEvaluation.motricity_score.toFixed(1) }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>

                <!-- Transversalité -->
                <td class="text-center">
                  <span v-if="currentEvaluation?.transversality_score" class="badge-score"
                    :style="{ background: getColorByLevel(currentEvaluation.transversality_score) }">
                    {{ currentEvaluation.transversality_score.toFixed(1) }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>

                <!-- Stratégique -->
                <td class="text-center">
                  <span v-if="currentEvaluation?.strategic_score" class="badge-score"
                    :style="{ background: getColorByLevel(currentEvaluation.strategic_score) }">
                    {{ currentEvaluation.strategic_score.toFixed(1) }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>

                <!-- Taux -->
                <td class="text-center">
                  <span class="badge" :style="{ background: getProgressColor() }">
                    {{ getEvaluationCount() }}/4
                  </span>
                </td>

                <!-- Actions -->
                <td class="text-center">
                  <b-button 
                    variant="success" 
                    size="sm" 
                    class="fw-bold"
                    @click="startEvaluation"
                    :disabled="getCurrentSession()?.status !== 'open'">
                    <i class="ti ti-pencil me-1"></i> Évaluer
                  </b-button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </b-card>

      <!-- Pas de session sélectionnée -->
      <div v-else class="text-center py-5">
        <i class="ti ti-calendar fs-1 text-muted"></i>
        <p class="text-muted mt-2">Sélectionnez une session pour voir les évaluations</p>
      </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL MATURITÉ -->
    <!-- ========================================================= -->
    <b-modal v-model="showMaturityModal" title="Matrice de maturité" size="lg"
      ok-title="Enregistrer & Continuer" cancel-title="Annuler" @ok="saveMaturity" centered>
      
      <div class="table-responsive">
        <table class="table table-sm">
          <thead style="background:#10b981;color:white;">
            <tr>
              <th>Critère</th>
              <th v-for="lvl in [1,2,3,4,5]" :key="lvl" class="text-center">N{{ lvl }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="scale in groupedMaturity" :key="scale.code">
              <td>
                <strong class="text-success">{{ scale.code }}</strong><br>
                <small class="text-muted">{{ scale.label }}</small>
              </td>
              <td v-for="lvl in scale.levels" :key="lvl.level_score">
                <div class="maturity-box"
                  :style="maturityBoxColor(scale.code, lvl.level_score)"
                  @click="toggleMaturity(scale.code, lvl.level_score)">
                  <strong>{{ lvl.level_label }}</strong><br>
                  <small class="text-muted">{{ lvl.level_description }}</small>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </b-modal>

    <!-- ========================================================= -->
    <!-- MODAL MOTRICITÉ -->
    <!-- ========================================================= -->
    <b-modal v-model="showMotricityModal" title="Motricité" size="lg"
      ok-title="Enregistrer & Continuer" cancel-title="Annuler" @ok="saveMotricity" centered>
      
      <div v-for="scale in motricityScales" :key="scale.id" class="mb-4 p-3" style="background:#f9fafb;border-radius:8px;border-left:4px solid #f59e0b;">
        <label class="fw-bold">{{ scale.name }}</label>
        <small class="text-muted d-block mb-3">{{ scale.description }}</small>
        <div class="d-flex gap-2 flex-wrap">
          <b-button 
            v-for="level in [1,2,3,4,5]" 
            :key="level" 
            size="sm"
            :style="motricitySelection === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}"
            :variant="motricitySelection === level ? '' : 'outline-warning'"
            @click="motricitySelection = level">
            {{ level }}
          </b-button>
        </div>
      </div>
    </b-modal>

    <!-- ========================================================= -->
    <!-- MODAL TRANSVERSALITÉ -->
    <!-- ========================================================= -->
    <b-modal v-model="showTransversalityModal" title="Transversalité" size="lg"
      ok-title="Enregistrer & Continuer" cancel-title="Annuler" @ok="saveTransversality" centered>
      
      <div v-for="scale in transversalityScales" :key="scale.id" class="mb-4 p-3" style="background:#f9fafb;border-radius:8px;border-left:4px solid #8b5cf6;">
        <label class="fw-bold">{{ scale.name }}</label>
        <small class="text-muted d-block mb-3">{{ scale.description }}</small>
        <div class="d-flex gap-2 flex-wrap">
          <b-button 
            v-for="level in [1,2,3,4,5]" 
            :key="level" 
            size="sm"
            :style="transversalitySelection === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}"
            :variant="transversalitySelection === level ? '' : 'outline-info'"
            @click="transversalitySelection = level">
            {{ level }}
          </b-button>
        </div>
      </div>
    </b-modal>

    <!-- ========================================================= -->
    <!-- MODAL STRATÉGIQUE -->
    <!-- ========================================================= -->
    <b-modal v-model="showStrategicModal" title="Poids stratégique" size="lg"
      ok-title="Terminer l'évaluation" cancel-title="Annuler" @ok="saveStrategic" centered>
      
      <div v-for="scale in strategicScales" :key="scale.id" class="mb-4 p-3" style="background:#f9fafb;border-radius:8px;border-left:4px solid #ec4899;">
        <label class="fw-bold">{{ scale.name }}</label>
        <small class="text-muted d-block mb-3">{{ scale.description }}</small>
        <div class="d-flex gap-2 flex-wrap">
          <b-button 
            v-for="level in [1,2,3,4,5]" 
            :key="level" 
            size="sm"
            :style="strategicSelection === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}"
            :variant="strategicSelection === level ? '' : 'outline-danger'"
            @click="strategicSelection = level">
            {{ level }}
          </b-button>
        </div>
      </div>
    </b-modal>

  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"
import axios from "axios"
import { ref, reactive, computed, onMounted } from "vue"

const props = defineProps({
  user: Object,
  link: Object,
  processes: Array,
  maturityLevels: Array,
  motricityScales: Array,
  transversalityScales: Array,
  strategicScales: Array,
})

/* ================================================================
   STATE
================================================================ */
const selectedProcess = ref(null)
const selectedSession = ref("")
const processSessions = ref([])
const processEvaluations = reactive({})

const error = ref("")
const successMessage = ref("")

const showCreateSessionModal = ref(false)
const newSessionName = ref("")
const newSessionColor = ref("#10b981")

const showMaturityModal = ref(false)
const showMotricityModal = ref(false)
const showTransversalityModal = ref(false)
const showStrategicModal = ref(false)

const maturityData = reactive({})
const motricitySelection = ref(null)
const transversalitySelection = ref(null)
const strategicSelection = ref(null)

const isSaving = ref(false)

/* ================================================================
   COMPUTED
================================================================ */
const groupedMaturity = computed(() => {
  const map = {}
  props.maturityLevels.forEach(l => {
    if (!map[l.scale_code]) {
      map[l.scale_code] = { code: l.scale_code, label: l.scale_label, levels: [] }
    }
    map[l.scale_code].levels.push(l)
  })
  return Object.values(map)
})

const currentEvaluation = computed(() => {
  if (!selectedProcess.value || !selectedSession.value) return null
  const key = `${selectedSession.value}-${selectedProcess.value.id}`
  return processEvaluations[key]
})

/* ================================================================
   FUNCTIONS
================================================================ */
const formatDate = d => d?.substring(0, 10) || ""
const truncateText = (text, max = 80) => text && text.length > max ? text.substring(0, max) + "..." : text || "N/A"

const selectProcess = (process) => {
  selectedProcess.value = process
  selectedSession.value = ""
  processSessions.value = []
  loadProcessSessions()
}

const backToProcessList = () => {
  selectedProcess.value = null
  selectedSession.value = ""
  processSessions.value = []
  Object.keys(processEvaluations).forEach(k => delete processEvaluations[k])
}

const getCurrentSession = () => {
  return processSessions.value.find(s => s.id === selectedSession.value)
}

const getColorByLevel = (level) => {
  const colors = { 1: "#ef4444", 2: "#ef4444", 3: "#f59e0b", 4: "#10b981", 5: "#10b981" }
  return colors[level] || "#d1d5db"
}

const getColorByScore = (score) => {
  if (!score || score === 0) return "#d1d5db"
  if (score >= 4) return "#10b981"
  if (score >= 3) return "#f59e0b"
  if (score >= 2) return "#ef4444"
  return "#d1d5db"
}

const getProgressColor = () => {
  const count = getEvaluationCount()
  if (count === 4) return "#10b981"
  if (count >= 1) return "#f59e0b"
  return "#d1d5db"
}

const getEvaluationCount = () => {
  const e = currentEvaluation.value
  if (!e) return 0
  let count = 0
  if (e.maturity_score) count++
  if (e.motricity_score) count++
  if (e.transversality_score) count++
  if (e.strategic_score) count++
  return count
}

const countProcessEvaluations = (processId) => {
  let count = 0
  for (let key in processEvaluations) {
    if (key.endsWith(`-${processId}`)) {
      count++
    }
  }
  return count
}

const countTotalEvaluations = () => {
  return Object.keys(processEvaluations).length
}

const getProcessAverageScore = (processId) => {
  let total = 0, count = 0
  for (let key in processEvaluations) {
    if (key.endsWith(`-${processId}`)) {
      const evaluation = processEvaluations[key]
      if (evaluation.criticality_score) {
        total += evaluation.criticality_score
        count++
      }
    }
  }
  return count ? total / count : 0
}

const getGlobalAverageScore = () => {
  let total = 0, count = 0
  for (let key in processEvaluations) {
    const evaluation = processEvaluations[key]
    if (evaluation.criticality_score) {
      total += evaluation.criticality_score
      count++
    }
  }
  return count ? total / count : 0
}

const maturityBoxColor = (code, level) => {
  const selected = maturityData[code] === level
  const color = getColorByLevel(level)
  
  if (selected) {
    return { background: color, color: "white", border: `2px solid ${color}` }
  }
  
  return { background: "#f9fafb", border: "1px solid #d1d5db", color: "#374151" }
}

/* ================================================================
   API
================================================================ */
const loadProcessSessions = async () => {
  try {
    const res = await axios.get(route("process.core.evaluations.sessions"), {
      params: { process_id: selectedProcess.value.id }
    })
    processSessions.value = res.data
    await loadProcessEvaluations()
  } catch (e) {
    error.value = "Erreur chargement sessions"
  }
}

const loadProcessEvaluations = async () => {
  try {
    const res = await axios.get(route("process.core.evaluations.all-sessions"), {
      params: { entity_id: props.link?.entity_id }
    })
    
    if (res.data.evaluations) {
      Object.entries(res.data.evaluations).forEach(([key, evaluation]) => {
        processEvaluations[key] = evaluation
      })
    }
  } catch (e) {
    console.warn("Erreur", e)
  }
}

const onSessionSelected = () => {
  error.value = ""
  successMessage.value = ""
}

const createSession = async () => {
  try {
    const res = await axios.post(route("process.core.evaluations.sessions.create"), {
      process_id: selectedProcess.value.id,
      entity_id: props.link.entity_id,
      function_id: props.link.function_id,
      name: newSessionName.value,
      color: newSessionColor.value,
    })

    showCreateSessionModal.value = false
    newSessionName.value = ""
    newSessionColor.value = "#10b981"

    await loadProcessSessions()
    selectedSession.value = res.data.session_id
    successMessage.value = "Session créée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch {
    error.value = "Erreur création"
  }
}

const closeSession = async () => {
  try {
    await axios.post(route("process.core.evaluations.sessions.close"), {
      session_id: selectedSession.value
    })
    await loadProcessSessions()
    successMessage.value = "Session clôturée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch {
    error.value = "Erreur clôture"
  }
}

const duplicateSession = async () => {
  const s = getCurrentSession()
  if (!s) return

  try {
    const res = await axios.post(route("process.core.evaluations.sessions.duplicate"), {
      source_session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      entity_id: props.link.entity_id,
      function_id: props.link.function_id,
      name: s.name + " (copie)",
      color: s.color,
    })

    await loadProcessSessions()
    selectedSession.value = res.data.session_id
    successMessage.value = "Session dupliquée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch {
    error.value = "Erreur duplication"
  }
}

const deleteSession = async () => {
  if (!confirm("Supprimer cette session ?")) return

  try {
    await axios.post(route("process.core.evaluations.sessions.delete"), {
      session_id: selectedSession.value
    })
    selectedSession.value = ""
    await loadProcessSessions()
    successMessage.value = "Session supprimée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch {
    error.value = "Erreur suppression"
  }
}

const startEvaluation = async () => {
  try {
    const res = await axios.get(route("process.core.evaluations.load"), {
      params: {
        session_id: selectedSession.value,
        process_id: selectedProcess.value.id
      }
    })

    Object.keys(maturityData).forEach(k => delete maturityData[k])
    Object.assign(maturityData, res.data.maturity || {})

    if (res.data.axes) {
      motricitySelection.value = res.data.axes.motricity_score
      transversalitySelection.value = res.data.axes.transversality_score
      strategicSelection.value = res.data.axes.strategic_score
    }

    showMaturityModal.value = true
  } catch {
    error.value = "Erreur chargement"
  }
}

const toggleMaturity = (code, level) => {
  maturityData[code] = level
}

const saveMaturity = async () => {
  if (Object.keys(maturityData).length === 0) {
    error.value = "Sélectionnez au moins un critère"
    return
  }

  isSaving.value = true
  const evaluations = Object.entries(maturityData).map(([code, level]) => ({
    process_id: selectedProcess.value.id,
    criterion_code: code,
    level_score: level,
  }))

  try {
    await axios.post(route("process.core.evaluations.maturity.save"), {
      session_id: selectedSession.value,
      evaluations,
    })
    successMessage.value = "Maturité enregistrée ✓"
    showMaturityModal.value = false
    setTimeout(() => { showMotricityModal.value = true }, 500)
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch {
    error.value = "Erreur"
  } finally {
    isSaving.value = false
  }
}

const saveMotricity = async () => {
  if (!motricitySelection.value) {
    error.value = "Sélectionnez un niveau"
    return
  }
  isSaving.value = true

  try {
    await axios.post(route("process.core.evaluations.axis.save"), {
      session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      axis: 'motricity',
      score: motricitySelection.value,
    })
    successMessage.value = "Motricité enregistrée ✓"
    showMotricityModal.value = false
    setTimeout(() => { showTransversalityModal.value = true }, 500)
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch {
    error.value = "Erreur"
  } finally {
    isSaving.value = false
  }
}

const saveTransversality = async () => {
  if (!transversalitySelection.value) {
    error.value = "Sélectionnez un niveau"
    return
  }
  isSaving.value = true

  try {
    await axios.post(route("process.core.evaluations.axis.save"), {
      session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      axis: 'transversality',
      score: transversalitySelection.value,
    })
    successMessage.value = "Transversalité enregistrée ✓"
    showTransversalityModal.value = false
    setTimeout(() => { showStrategicModal.value = true }, 500)
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch {
    error.value = "Erreur"
  } finally {
    isSaving.value = false
  }
}

const saveStrategic = async () => {
  if (!strategicSelection.value) {
    error.value = "Sélectionnez un niveau"
    return
  }
  isSaving.value = true

  try {
    await axios.post(route("process.core.evaluations.axis.save"), {
      session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      axis: 'strategic',
      score: strategicSelection.value,
    })
    successMessage.value = "Évaluation terminée ✓"
    showStrategicModal.value = false
    setTimeout(() => {
      successMessage.value = ""
      Object.keys(maturityData).forEach(k => delete maturityData[k])
      motricitySelection.value = null
      transversalitySelection.value = null
      strategicSelection.value = null
      loadProcessEvaluations()
    }, 2000)
  } catch {
    error.value = "Erreur"
  } finally {
    isSaving.value = false
  }
}

onMounted(async () => {
  await loadProcessEvaluations()
})
</script>

<style scoped>
.table-responsive { border-radius: 8px; }
.table { margin-bottom: 0; }
.table thead th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 1rem; }
.table tbody td { padding: 1rem; vertical-align: middle; }
.process-row { transition: background-color 0.2s ease; }
.process-row:hover { background-color: rgba(16, 185, 129, 0.08) !important; }
.evaluation-row { background: #ecfdf5; }

.score-badge { 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  width: 50px; 
  height: 50px; 
  border: 3px solid #d1d5db; 
  border-radius: 50%; 
  background: #f9fafb; 
  font-weight: 700; 
  font-size: 1rem; 
  color: #374151; 
}

.score-badge-large { 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  width: 70px; 
  height: 70px; 
  border: 4px solid #d1d5db; 
  border-radius: 50%; 
  background: #f9fafb; 
  font-weight: 800; 
  font-size: 1.3rem; 
  color: #10b981;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.badge-score { 
  color: white; 
  font-weight: 600; 
  padding: 0.5rem 0.75rem; 
  font-size: 0.9rem; 
  border-radius: 6px; 
  display: inline-block; 
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.maturity-box { 
  cursor: pointer; 
  transition: 0.2s; 
  padding: 10px; 
  border-radius: 6px; 
  text-align: left; 
  background: #f9fafb; 
  border: 1px solid #d1d5db; 
  min-height: 86px; 
}

.maturity-box:hover { 
  transform: scale(1.01); 
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.alert { 
  animation: slideDown 0.3s ease; 
  margin-bottom: 0; 
}

@keyframes slideDown { 
  from { 
    opacity: 0; 
    transform: translateY(-10px); 
  } 
  to { 
    opacity: 1; 
    transform: translateY(0); 
  } 
}

button.fw-bold { 
  font-weight: 700 !important; 
}
</style>