<template>
  <VerticalLayout>
    <Head title="Évaluation des processus" />

    <!-- ========================================================= -->
    <!-- HEADER SESSIONS                                           -->
    <!-- ========================================================= -->
    <b-card class="mb-4 border-0 shadow-lg p-4"
      style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px;">

      <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">

        <!-- INFO USER -->
        <div>
          <h2 class="fw-bold mb-2 text-white" style="font-size: 1.75rem;">
            📋 Évaluation des processus
          </h2>

          <p class="mb-0 text-white" style="opacity: .95; font-size: .95rem;">
            👤 <strong>{{ user?.name }}</strong>
            <span v-if="link" class="ms-2">
              <i class="ti ti-arrow-right"></i>
              <strong>{{ link.function_name }}</strong> —
              <em>{{ link.entity_name }}</em>
            </span>
          </p>
        </div>

        <!-- SESSION SELECTOR -->
        <div>
          <small style="color: rgba(255,255,255,0.9); text-transform: uppercase; font-weight: 700;">
            Session d'évaluation
          </small>

          <div class="d-flex align-items-center gap-2 mt-2">
            <select
              v-model="currentSession"
              @change="loadSession"
              style="padding: .6rem 1rem; border-radius: 6px; border: 2px solid white;
              background: rgba(255,255,255,0.2); color: white; font-weight: 700; min-width: 200px;">
              <option v-for="s in sessions" :key="s.id" :value="s.id" style="color: black;">
                {{ s.name }}
              </option>
            </select>

            <b-button size="sm" variant="light" @click="openCreateSession"
              style="border-radius: 6px; font-weight: 700;">
              <i class="ti ti-plus"></i> Nouvelle session
            </b-button>

            <b-button
              v-if="currentSession"
              size="sm"
              variant="outline-light"
              @click="openDuplicateSession"
              style="border-radius: 6px; font-weight: 700;"
            >
              <i class="ti ti-copy"></i> Dupliquer
            </b-button>
          </div>
        </div>

      </div>

      <!-- STEPS -->
      <div class="mt-4">
        <small style="color: rgba(255,255,255,0.8); text-transform: uppercase; font-weight: 600;">
          Étapes d'évaluation
        </small>

        <div class="d-flex gap-2 flex-wrap mt-2">
          <b-button
            v-for="et in steps"
            :key="et.key"
            :variant="step === et.key ? 'light' : 'outline-light'"
            size="sm"
            class="fw-600"
            @click="setStep(et.key)"
            style="border-radius: 6px; padding: .5rem 1rem; font-weight: 700;">
            {{ et.label }}
          </b-button>
        </div>

        <!-- Compare sessions -->
        <div class="form-check form-check-inline mt-3">
          <input class="form-check-input" type="checkbox" v-model="compareMode" id="compareSessions" />
          <label class="form-check-label text-white fw-bold" for="compareSessions">
            <i class="ti ti-chart-radar"></i> Comparer les sessions
          </label>
        </div>
      </div>

    </b-card>

    <!-- ========================================================= -->
    <!-- MACRO-PROCESSUS                                           -->
    <!-- ========================================================= -->
    <div v-for="macro in macroProcesses" :key="macro.id" class="mb-5">
      <div class="mb-3" style="padding: 1rem 0; border-bottom: 3px solid #667eea;">
        <h4 class="fw-bold mb-0" style="color: #333;">
          <span class="badge"
            style="background: linear-gradient(135deg,#667eea,#764ba2); padding:.4rem .8rem;">
            {{ macro.code }}
          </span>
          {{ macro.name }}
        </h4>
      </div>

      <!-- PROCESS CARDS -->
      <b-row class="g-4">
        <b-col lg="12" v-for="proc in processes.filter(p => p.macro_process_id === macro.id)" :key="proc.id">

          <b-card class="shadow-sm border-0"
            :class="{ 'shadow-lg': activeProcess === proc.id }"
            @click="toggleProcess(proc.id)"
            style="border-radius: 12px; cursor: pointer; border: 2px solid transparent;"
            :style="{ borderColor: activeProcess === proc.id ? sessionColor : '#e5e7eb' }">

            <!-- HEADER PROCESS -->
            <div class="p-3"
              style="background: linear-gradient(135deg,#f5f7fa,#eef1f7);
              display:flex; justify-content:space-between; align-items:center;">
              <div>
                <h5 class="fw-bold mb-1" :style="{ color: sessionColor }">🔧 {{ proc.code }}</h5>
                <p class="mb-0 text-muted">{{ proc.name }}</p>
              </div>

              <i class="ti ti-chevron-down"
                :style="{ transform: activeProcess === proc.id ? 'rotate(180deg)' : 'rotate(0deg)' }"
                style="font-size: 1.4rem; transition: .3s;"></i>
            </div>

            <!-- ==================== CONTENT ==================== -->
            <transition name="expand" @enter="enter" @leave="leave">
              <div v-if="activeProcess === proc.id" class="p-4">

                <!-- ==================== MATURITY SECTION ==================== -->
                <div v-if="step === 'maturity'">

                  <h6 class="fw-bold mb-3" style="color: #333; font-size: 0.95rem;">📋 Critères d'évaluation</h6>

                  <div class="table-responsive mb-4">
                    <table class="table table-sm" style="font-size: 0.85rem;">
                      <thead>
                        <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                          <th style="padding: 0.75rem;">Critère</th>
                          <th colspan="5" style="padding: 0.75rem; text-align:center;">Niveaux</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="crit in groupedMaturity" :key="crit.code" style="border-bottom: 1px solid #f0f0f0;">
                          <td style="padding: 0.75rem;">
                            <strong style="color:#667eea;">{{ crit.code }}</strong>
                            <br />
                            <small style="color: #999;">{{ crit.label }}</small>
                          </td>

                          <td v-for="lvl in crit.levels" :key="lvl.level_score" style="padding: 0.5rem; text-align:center;">
                            <label
                              :style="{
                                display: 'block',
                                padding: '0.6rem',
                                borderRadius: '6px',
                                cursor: 'pointer',
                                border: '2px solid',
                                borderColor: isMaturitySelected(proc.id, crit.code, lvl.level_score) ? sessionColor : '#e5e7eb',
                                backgroundColor: isMaturitySelected(proc.id, crit.code, lvl.level_score) ? sessionColor : '#f9fafb',
                                color: isMaturitySelected(proc.id, crit.code, lvl.level_score) ? 'white' : '#333',
                                fontWeight: isMaturitySelected(proc.id, crit.code, lvl.level_score) ? '600' : '400'
                              }"
                              :title="lvl.level_description"
                            >
                              <input
                                type="radio"
                                :name="`maturity_${proc.id}_${crit.code}`"
                                :value="lvl.level_score"
                                :checked="isMaturitySelected(proc.id, crit.code, lvl.level_score)"
                                @change="onMaturityChange(proc.id, crit.code, lvl.level_score)"
                                style="display:none;"
                              />
                              <small>{{ lvl.level_description }}</small>
                            </label>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- RADAR -->
                  <div class="mb-4" v-if="!compareMode">
                    <h6 class="fw-bold mb-3" style="color: #333;">📊 Radar — Session {{ getSessionName(currentSession) }}</h6>
                    <div style="height:350px; background:#f5f7fa; border-radius:8px; padding:1rem; border:2px solid #667eea;">
                      <canvas :ref="el => radarChart = el"></canvas>
                    </div>
                  </div>

                  <!-- ACTION BUTTONS -->
                  <div class="d-flex gap-2 justify-content-end" style="border-top:1px solid #e5e7eb; padding-top:1.5rem;">
                    <b-button variant="secondary" size="sm" @click="toggleProcess(proc.id)">Fermer</b-button>

                    <b-button
                      size="sm"
                      class="fw-bold"
                      @click="saveMaturity(proc.id)"
                      :disabled="loading"
                      style="background:linear-gradient(135deg,#667eea,#764ba2); border:none; color:white;">
                      <i class="ti ti-device-floppy me-1"></i>
                      {{ loading ? "Sauvegarde..." : "Enregistrer" }}
                    </b-button>
                  </div>

                </div>

                <!-- ==================== MOTRICITY ==================== -->
                <div v-if="step === 'motricity'">
                  <h6 class="fw-bold mb-3" style="color:#333;">⭐ Motricité</h6>

                  <div class="table-responsive mb-4">
                    <table class="table table-sm">
                      <thead>
                        <tr style="background:#667eea; color:white;">
                          <th>Code</th>
                          <th>Description</th>
                          <th style="text-align:center; width:100px;">Sélection</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="scale in motricityScales" :key="scale.id"
                          :class="{ 'table-active': proc.motricity_score === scale.id }">
                          <td><strong style="color:#667eea;">{{ scale.code }}</strong></td>
                          <td>
                            <strong>{{ scale.label }}</strong>
                            <br /><small style="color:#999;">{{ scale.description }}</small>
                          </td>
                          <td style="text-align:center;">
                            <input
                              type="radio"
                              :name="`motricity_${proc.id}`"
                              :value="scale.id"
                              :checked="proc.motricity_score === scale.id"
                              @change="onAxisChange(proc, 'motricity', scale.id)"
                              style="width:20px;height:20px;cursor:pointer;"
                            />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div style="height:300px; background:#f5f7fa; border-radius:8px; padding:1rem; border:2px solid #667eea;">
                    <canvas v-if="proc.motricity_score" :ref="el => gaugeMotricity = el"></canvas>
                    <div v-else style="color:#999; text-align:center;">Sélectionnez un niveau</div>
                  </div>
                </div>

                <!-- ==================== TRANSVERSALITY ==================== -->
                <div v-if="step === 'transversality'">
                  <h6 class="fw-bold mb-3" style="color:#333;">⭐ Transversalité</h6>

                  <div class="table-responsive mb-4">
                    <table class="table table-sm">
                      <thead>
                        <tr style="background:#667eea; color:white;">
                          <th>Code</th>
                          <th>Description</th>
                          <th style="text-align:center; width:100px;">Sélection</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="scale in transversalityScales" :key="scale.id"
                          :class="{ 'table-active': proc.transversality_score === scale.id }">
                          <td><strong style="color:#667eea;">{{ scale.code }}</strong></td>
                          <td>
                            <strong>{{ scale.label }}</strong>
                            <br /><small style="color:#999;">{{ scale.description }}</small>
                          </td>
                          <td style="text-align:center;">
                            <input
                              type="radio"
                              :name="`transversality_${proc.id}`"
                              :value="scale.id"
                              :checked="proc.transversality_score === scale.id"
                              @change="onAxisChange(proc, 'transversality', scale.id)"
                              style="width:20px;height:20px;cursor:pointer;"
                            />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div style="height:300px; background:#f5f7fa; border-radius:8px; padding:1rem; border:2px solid #667eea;">
                    <canvas v-if="proc.transversality_score" :ref="el => gaugeTransversality = el"></canvas>
                    <div v-else style="color:#999; text-align:center;">Sélectionnez un niveau</div>
                  </div>
                </div>

                <!-- ==================== STRATEGIC ==================== -->
                <div v-if="step === 'strategic'">
                  <h6 class="fw-bold mb-3" style="color:#333;">⭐ Poids stratégique</h6>

                  <div class="table-responsive mb-4">
                    <table class="table table-sm">
                      <thead>
                        <tr style="background:#667eea; color:white;">
                          <th>Code</th>
                          <th>Description</th>
                          <th style="text-align:center; width:100px;">Sélection</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="scale in strategicScales" :key="scale.id"
                          :class="{ 'table-active': proc.strategic_score === scale.id }">
                          <td><strong style="color:#667eea;">{{ scale.code }}</strong></td>
                          <td>
                            <strong>{{ scale.label }}</strong>
                            <br /><small style="color:#999;">{{ scale.description }}</small>
                          </td>
                          <td style="text-align:center;">
                            <input
                              type="radio"
                              :name="`strategic_${proc.id}`"
                              :value="scale.id"
                              :checked="proc.strategic_score === scale.id"
                              @change="onAxisChange(proc, 'strategic', scale.id)"
                              style="width:20px;height:20px;cursor:pointer;"
                            />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div style="height:300px; background:#f5f7fa; border-radius:8px; padding:1rem; border:2px solid #667eea;">
                    <canvas v-if="proc.strategic_score" :ref="el => gaugeStrategic = el"></canvas>
                    <div v-else style="color:#999; text-align:center;">Sélectionnez un niveau</div>
                  </div>
                </div>

              </div>
            </transition>

          </b-card>

        </b-col>
      </b-row>

    </div>

    <!-- ========================================================= -->
    <!-- MODAL CREATE SESSION                                      -->
    <!-- ========================================================= -->
    <b-modal v-model="showCreateSession" title="Créer une session" ok-title="Créer" @ok="createSession">

      <div class="mb-3">
        <label class="form-label fw-bold">Nom de la session</label>
        <input v-model="newSessionName" type="text" class="form-control" placeholder="Ex: Session Janvier" />
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Couleur</label>
        <input type="color" v-model="newSessionColor" class="form-control form-control-color" />
      </div>

    </b-modal>

    <!-- ========================================================= -->
    <!-- MODAL DUPLICATE SESSION                                   -->
    <!-- ========================================================= -->
    <b-modal v-model="showDuplicateModal" title="Dupliquer la session" ok-title="Dupliquer" @ok="duplicateSession">

      <div class="mb-3">
        <label class="form-label fw-bold">Nom de la nouvelle session</label>
        <input v-model="duplicateSessionName" type="text" class="form-control" />
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Couleur</label>
        <input type="color" v-model="duplicateSessionColor" class="form-control form-control-color" />
      </div>

      <div class="alert alert-info">
        Les données seront copiées.
      </div>

    </b-modal>

  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"
import axios from "axios"
import { ref, reactive, computed, nextTick, watch, onMounted } from "vue"

let Chart = null

/* ==========================================================
   PROPS
   ========================================================== */
const props = defineProps({
  user: Object,
  link: Object,
  macroProcesses: Array,
  processes: Array,
  sessions: Array,
  maturityLevels: Array,
  motricityScales: Array,
  transversalityScales: Array,
  strategicScales: Array,
})

/* ==========================================================
   INIT CHARTJS
   ========================================================== */
onMounted(async () => {
  try {
    const module = await import("chart.js/auto")
    Chart = module.default
  } catch (e) {
    console.log("Chart JS non chargé")
  }
})

/* ==========================================================
   SESSION
   ========================================================== */
const currentSession = ref(props.sessions[0]?.id || null)

const sessionColor = computed(() => {
  const s = props.sessions.find(s => s.id === currentSession.value)
  return s ? s.color : "#667eea"
})

const getSessionName = (id) => {
  const s = props.sessions.find(x => x.id === id)
  return s ? s.name : ""
}

/* ==========================================================
   STEPS
   ========================================================== */
const step = ref("maturity")
const steps = [
  { key: "maturity", label: "📊 Maturité" },
  { key: "motricity", label: "⭐ Motricité" },
  { key: "transversality", label: "⭐ Transversalité" },
  { key: "strategic", label: "⭐ Stratégique" }
]
const setStep = key => step.value = key

/* ==========================================================
   POPUPS
   ========================================================== */
const showCreateSession = ref(false)
const newSessionName = ref("")
const newSessionColor = ref("#667eea")

const openCreateSession = () => showCreateSession.value = true

const createSession = async () => {
  if (!newSessionName.value.trim()) return

  await axios.post(route("process.core.process.evaluations.sessions.create"), {
    entity_id: props.link.entity_id,
    function_id: props.link.function_id,
    name: newSessionName.value,
    color: newSessionColor.value
  })

  location.reload()
}

const showDuplicateModal = ref(false)
const duplicateSessionName = ref("")
const duplicateSessionColor = ref("#667eea")

const openDuplicateSession = () => {
  const s = props.sessions.find(x => x.id === currentSession.value)
  if (!s) return
  duplicateSessionName.value = s.name + " (copie)"
  showDuplicateModal.value = true
}

const duplicateSession = async () => {
  await axios.post(route("process.core.evaluations.sessions.duplicate"), {
    session_id: currentSession.value,
    name: duplicateSessionName.value,
    color: duplicateSessionColor.value
  })

  location.reload()
}

/* ==========================================================
   EXPAND PROCESS
   ========================================================== */
const activeProcess = ref(null)
const toggleProcess = id => {
  activeProcess.value = activeProcess.value === id ? null : id
  nextTick(() => refreshGraphs(id))
}

/* ==========================================================
   MATURITY SCORES
   ========================================================== */
const maturityScores = reactive({})

const groupedMaturity = computed(() => {
  const g = {}
  props.maturityLevels.forEach(l => {
    if (!g[l.scale_code]) g[l.scale_code] = { code: l.scale_code, label: l.scale_label, levels: [] }
    g[l.scale_code].levels.push(l)
  })
  return Object.values(g)
})

const isMaturitySelected = (procId, code, level) => {
  return maturityScores[procId]?.[code] === level
}

const onMaturityChange = (procId, code, level) => {
  if (!maturityScores[procId]) maturityScores[procId] = {}
  maturityScores[procId][code] = level
  nextTick(() => refreshGraphs(procId))
}

const saveMaturity = async (procId) => {
  const items = Object.entries(maturityScores[procId] || {}).map(([code, level]) => ({
    process_id: procId,
    criterion_code: code,
    level_score: level
  }))

  await axios.post(route("process.core.evaluations.sessions.maturity.save"), {
    session_id: currentSession.value,
    evaluations: items
  })

  alert("Maturité enregistrée")
}

/* ==========================================================
   AXES
   ========================================================== */
const onAxisChange = async (proc, axis, score) => {
  await axios.post(route("process.core.evaluations.sessions.axis.save"), {
    session_id: currentSession.value,
    process_id: proc.id,
    axis,
    score
  })
  proc[`${axis}_score`] = score
  nextTick(() => refreshGraphs(proc.id))
}

/* ==========================================================
   GRAPHIQUES
   ========================================================== */
let radarChart = null
let radarChartInstance = null
let gaugeMotricity = null
let gaugeMotricityInstance = null
let gaugeTransversality = null
let gaugeTransversalityInstance = null
let gaugeStrategic = null
let gaugeStrategicInstance = null

const generateRadar = (procId) => {
  if (!Chart || !radarChart) return

  const labels = groupedMaturity.value.map(x => x.code)
  const values = groupedMaturity.value.map(x => maturityScores[procId]?.[x.code] || 0)

  if (radarChartInstance) radarChartInstance.destroy()

  radarChartInstance = new Chart(radarChart, {
    type: "radar",
    data: {
      labels,
      datasets: [{
        label: "Maturité",
        data: values,
        borderColor: sessionColor.value,
        backgroundColor: sessionColor.value + "33",
        borderWidth: 3,
        pointRadius: 6,
        pointBackgroundColor: sessionColor.value,
        pointBorderColor: "#fff"
      }]
    }
  })
}

const generateGauge = (canvas, instance, score, color) => {
  if (!Chart || !canvas) return null
  if (instance) instance.destroy()

  return new Chart(canvas, {
    type: "doughnut",
    data: {
      datasets: [{
        data: [score, 5 - score],
        backgroundColor: [color, "#e5e7eb"],
        borderWidth: 2,
        circumference: 180,
        rotation: -90
      }]
    },
    options: { cutout: "60%", plugins: { legend: { display: false } } }
  })
}

const refreshGraphs = (procId) => {
  const proc = props.processes.find(x => x.id === procId)
  if (!proc) return

  if (step.value === "maturity") generateRadar(procId)
  if (step.value === "motricity" && proc.motricity_score)
    gaugeMotricityInstance = generateGauge(gaugeMotricity, gaugeMotricityInstance, proc.motricity_score, "#F59E0B")

  if (step.value === "transversality" && proc.transversality_score)
    gaugeTransversalityInstance = generateGauge(gaugeTransversality, gaugeTransversalityInstance, proc.transversality_score, "#8B5CF6")

  if (step.value === "strategic" && proc.strategic_score)
    gaugeStrategicInstance = generateGauge(gaugeStrategic, gaugeStrategicInstance, proc.strategic_score, "#EC4899")
}

/* ==========================================================
   EXPAND ANIMATION
   ========================================================== */
const enter = el => el.style.maxHeight = el.scrollHeight + "px"
const leave = el => el.style.maxHeight = "0px"

</script>

<style scoped>
.expand-enter-active,
.expand-leave-active {
  transition: max-height .3s;
  overflow: hidden;
}
.expand-enter-from,
.expand-leave-to {
  max-height: 0;
}

.table-active {
  background-color: rgba(102,126,234,0.08) !important;
  font-weight: 500;
}
</style>
