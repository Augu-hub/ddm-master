<template>
  <VerticalLayout>
    <Head title="Évaluation des processus" />

    <!-- EN-TÊTE -->
    <b-card class="mb-3 border-0 shadow-sm p-3" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
          <h4 class="fw-semibold text-primary mb-1">
            📋 Évaluation des processus
          </h4>
          <p class="text-muted mb-1">
            👤 {{ user?.name }}
            <span v-if="link">
              — <strong>{{ link.function_name }}</strong> ({{ link.entity_name }})
            </span>
          </p>
        </div>

        <div class="mt-2 mt-md-0">
          <b-button-group>
            <b-button
              v-for="et in steps"
              :key="et.key"
              :variant="et.key === step ? 'primary' : 'outline-primary'"
              size="sm"
              @click="setStep(et.key)"
            >
              {{ et.label }}
            </b-button>
          </b-button-group>
        </div>
      </div>
    </b-card>

    <!-- MACROS -->
    <b-card v-for="macro in macroProcesses" :key="macro.id" class="mb-4 shadow-sm border-0 bg-white">
      <h5 class="fw-semibold text-dark mb-3">
        🔄 {{ macro.code }} — {{ macro.name }}
      </h5>

      <!-- PROCESSUS -->
      <div
        v-for="proc in processes.filter(p => p.macro_process_id === macro.id)"
        :key="proc.id"
        class="mb-3 border rounded-3 p-3 bg-light shadow-sm"
      >
        <div class="d-flex justify-content-between align-items-center cursor-pointer" @click="toggleProcess(proc.id)">
          <h6 class="fw-semibold text-primary m-0">
            🔧 {{ proc.code }} — {{ proc.name }}
          </h6>
          <b-button size="sm" :variant="activeProcess === proc.id ? 'outline-danger' : 'outline-primary'">
            <i :class="activeProcess === proc.id ? 'ti ti-x' : 'ti ti-eye'"></i>
            {{ activeProcess === proc.id ? 'Fermer' : 'Évaluer' }}
          </b-button>
        </div>

        <!-- CONTENU ÉVALUATION -->
        <transition name="fade">
          <div v-if="activeProcess === proc.id" class="mt-4 border-top pt-3">

            <!-- ════════ MATURITÉ: RADAR ════════ -->
            <div v-if="step === 'maturity'">
              <b-row class="g-3">
                <!-- TABLEAU MATURITÉ -->
                <b-col lg="6">
                  <h6 class="fw-semibold mb-3">📋 Critères d'évaluation</h6>
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" style="font-size: 0.75rem">
                      <thead class="table-primary">
                        <tr>
                          <th style="width: 30%">Critère</th>
                          <th colspan="5" class="text-center">Niveau</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="crit in groupedMaturity" :key="crit.code">
                          <td class="fw-semibold small">
                            <strong>{{ crit.code }}</strong><br/>
                            <small class="text-muted">{{ crit.label }}</small>
                          </td>

                          <td v-for="lvl in crit.levels" :key="lvl.level_score" class="text-center p-1">
                            <label 
                              class="mb-0 d-block cursor-pointer" 
                              :title="lvl.level_description"
                              style="padding: 4px; border-radius: 4px; transition: all 0.2s"
                              :style="{ 
                                backgroundColor: isSelected(proc.id, crit.code, lvl.level_score) ? '#0d6efd' : '#f8f9fa',
                                color: isSelected(proc.id, crit.code, lvl.level_score) ? 'white' : 'black',
                                border: isSelected(proc.id, crit.code, lvl.level_score) ? '2px solid #0d6efd' : '1px solid #dee2e6'
                              }"
                            >
                              <input
                                type="radio"
                                :name="`maturity_${proc.id}_${crit.code}`"
                                :value="lvl.level_score"
                                :checked="isSelected(proc.id, crit.code, lvl.level_score)"
                                @change="onMaturityChange(proc.id, crit.code, lvl.level_score)"
                                style="display: none"
                              />
                              <small style="display: block; line-height: 1.2">
                                <strong>{{ lvl.level_description }}</strong>
                              </small>
                            </label>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </b-col>

                <!-- GRAPHIQUE RADAR -->
                <b-col lg="6">
                  <h6 class="fw-semibold mb-3">📊 Visualisation Radar</h6>
                  <div style="position: relative; height: 350px; width: 100%">
                    <canvas ref="radarChart" id="radarChart"></canvas>
                  </div>
                </b-col>
              </b-row>

              <!-- BOUTONS -->
              <div class="d-flex justify-content-end gap-2 mt-4">
                <b-button variant="success" size="sm" @click="saveMaturity(proc.id)">
                  ✅ ENREGISTRER
                </b-button>
                <b-button variant="secondary" size="sm" @click="toggleProcess(proc.id)">
                  ✕ FERMER
                </b-button>
              </div>
            </div>

            <!-- ════════ MOTRICITÉ: TABLEAU + JAUGE ════════ -->
            <div v-if="step === 'motricity'">
              <b-row class="g-3">
                <!-- TABLEAU MOTRICITÉ -->
                <b-col lg="6">
                  <h6 class="fw-semibold mb-3">⭐ Motricité — Influence du processus</h6>
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" style="font-size: 0.85rem">
                      <thead class="table-primary">
                        <tr>
                          <th>Code</th>
                          <th>Description</th>
                          <th class="text-center">Sélection</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="scale in motricityScales" :key="scale.id" :class="getAxisValue(proc, 'motricity') === scale.id ? 'table-light' : ''">
                          <td class="fw-semibold">{{ scale.code }}</td>
                          <td>
                            <strong>{{ scale.label }}</strong><br/>
                            <small class="text-muted">{{ scale.description }}</small>
                          </td>
                          <td class="text-center">
                            <input
                              type="radio"
                              :name="`motricity_${proc.id}`"
                              :value="scale.id"
                              :checked="getAxisValue(proc, 'motricity') === scale.id"
                              @change="onAxisChange(proc, 'motricity', scale.id)"
                            />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </b-col>

                <!-- JAUGE MOTRICITÉ -->
                <b-col lg="6">
                  <h6 class="fw-semibold mb-3">📊 Visualisation</h6>
                  <div class="text-center">
                    <div v-if="getAxisValue(proc, 'motricity')" style="position: relative; height: 300px">
                      <canvas ref="gaugeMotricity" id="gaugeMotricity"></canvas>
                    </div>
                    <div v-else class="text-muted p-5">
                      Sélectionnez un niveau pour voir la jauge
                    </div>
                  </div>
                </b-col>
              </b-row>
            </div>

            <!-- ════════ TRANSVERSALITÉ: TABLEAU + JAUGE ════════ -->
            <div v-if="step === 'transversality'">
              <b-row class="g-3">
                <!-- TABLEAU TRANSVERSALITÉ -->
                <b-col lg="6">
                  <h6 class="fw-semibold mb-3">⭐ Transversalité — Partage interservices</h6>
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" style="font-size: 0.85rem">
                      <thead class="table-primary">
                        <tr>
                          <th>Code</th>
                          <th>Description</th>
                          <th class="text-center">Sélection</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="scale in transversalityScales" :key="scale.id" :class="getAxisValue(proc, 'transversality') === scale.id ? 'table-light' : ''">
                          <td class="fw-semibold">{{ scale.code }}</td>
                          <td>
                            <strong>{{ scale.label }}</strong><br/>
                            <small class="text-muted">{{ scale.description }}</small>
                          </td>
                          <td class="text-center">
                            <input
                              type="radio"
                              :name="`transversality_${proc.id}`"
                              :value="scale.id"
                              :checked="getAxisValue(proc, 'transversality') === scale.id"
                              @change="onAxisChange(proc, 'transversality', scale.id)"
                            />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </b-col>

                <!-- JAUGE TRANSVERSALITÉ -->
                <b-col lg="6">
                  <h6 class="fw-semibold mb-3">📊 Visualisation</h6>
                  <div class="text-center">
                    <div v-if="getAxisValue(proc, 'transversality')" style="position: relative; height: 300px">
                      <canvas ref="gaugeTransversality" id="gaugeTransversality"></canvas>
                    </div>
                    <div v-else class="text-muted p-5">
                      Sélectionnez un niveau pour voir la jauge
                    </div>
                  </div>
                </b-col>
              </b-row>
            </div>

            <!-- ════════ STRATÉGIQUE: TABLEAU + JAUGE ════════ -->
            <div v-if="step === 'strategic'">
              <b-row class="g-3">
                <!-- TABLEAU STRATÉGIQUE -->
                <b-col lg="6">
                  <h6 class="fw-semibold mb-3">⭐ Poids Stratégique — Impact stratégique</h6>
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" style="font-size: 0.85rem">
                      <thead class="table-primary">
                        <tr>
                          <th>Code</th>
                          <th>Description</th>
                          <th class="text-center">Sélection</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr v-for="scale in strategicScales" :key="scale.id" :class="getAxisValue(proc, 'strategic') === scale.id ? 'table-light' : ''">
                          <td class="fw-semibold">{{ scale.code }}</td>
                          <td>
                            <strong>{{ scale.label }}</strong><br/>
                            <small class="text-muted">{{ scale.description }}</small>
                          </td>
                          <td class="text-center">
                            <input
                              type="radio"
                              :name="`strategic_${proc.id}`"
                              :value="scale.id"
                              :checked="getAxisValue(proc, 'strategic') === scale.id"
                              @change="onAxisChange(proc, 'strategic', scale.id)"
                            />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </b-col>

                <!-- JAUGE STRATÉGIQUE -->
                <b-col lg="6">
                  <h6 class="fw-semibold mb-3">📊 Visualisation</h6>
                  <div class="text-center">
                    <div v-if="getAxisValue(proc, 'strategic')" style="position: relative; height: 300px">
                      <canvas ref="gaugeStrategic" id="gaugeStrategic"></canvas>
                    </div>
                    <div v-else class="text-muted p-5">
                      Sélectionnez un niveau pour voir la jauge
                    </div>
                  </div>
                </b-col>
              </b-row>
            </div>

          </div>
        </transition>
      </div>
    </b-card>

  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import axios from 'axios'
import { reactive, ref, computed, watch, nextTick, onMounted } from 'vue'

const props = defineProps({
  user: Object,
  link: Object,
  macroProcesses: Array,
  processes: Array,
  maturityLevels: Array,
  motricityScales: Array,
  transversalityScales: Array,
  strategicScales: Array,
  step: String,
})

// Chart.js
let Chart
onMounted(async () => {
  try {
    const module = await import('chart.js/auto')
    Chart = module.default
  } catch (e) {
    console.warn('Chart.js not available')
  }
})

// Étapes
const steps = [
  { key: 'maturity', label: '📊 Maturité' },
  { key: 'motricity', label: '⭐ Motricité' },
  { key: 'transversality', label: '⭐ Transversalité' },
  { key: 'strategic', label: '⭐ Stratégique' },
]

const step = ref(props.step || 'maturity')
const setStep = key => {
  step.value = key
  router.visit(route('process.core.process.evaluations.entry.index', { step: key }), { preserveScroll: true })
}

const activeProcess = ref(null)
const radarChart = ref(null)
const gaugeMotricity = ref(null)
const gaugeTransversality = ref(null)
const gaugeStrategic = ref(null)

let radarChartInstance = null
let gaugeMotricityInstance = null
let gaugeTransversalityInstance = null
let gaugeStrategicInstance = null

// MATURITÉ
const maturityScores = reactive({})

const groupedMaturity = computed(() => {
  const groups = {}
  props.maturityLevels?.forEach(lvl => {
    if (!groups[lvl.scale_code]) {
      groups[lvl.scale_code] = {
        code: lvl.scale_code,
        label: lvl.scale_label,
        description: lvl.scale_description,
        levels: [],
      }
    }
    groups[lvl.scale_code].levels.push(lvl)
  })
  return Object.values(groups)
})

const isSelected = (procId, code, lvl) => maturityScores[procId]?.[code] === lvl

const onMaturityChange = (procId, code, lvl) => {
  if (!maturityScores[procId]) maturityScores[procId] = {}
  maturityScores[procId][code] = lvl

  nextTick(() => {
    generateRadarChart(procId)
  })
}

// Générer RADAR pour maturité
const generateRadarChart = (procId) => {
  if (!Chart || !radarChart.value) return

  const labels = groupedMaturity.value.map(c => c.code)
  const data = groupedMaturity.value.map(c => maturityScores[procId]?.[c.code] || 0)

  if (radarChartInstance) radarChartInstance.destroy()

  try {
    radarChartInstance = new Chart(radarChart.value, {
      type: 'radar',
      data: {
        labels,
        datasets: [{
          label: 'Maturité',
          data,
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59, 130, 246, 0.2)',
          borderWidth: 2,
          pointRadius: 5,
          pointBackgroundColor: '#3B82F6',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          tension: 0.4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          r: {
            beginAtZero: true,
            max: 5,
            min: 0,
            ticks: { stepSize: 1 },
            grid: { drawBorder: true },
          },
        },
        plugins: {
          legend: { display: true, position: 'bottom' },
        },
      },
    })
  } catch (e) {
    console.error('Radar error:', e)
  }
}

// Générer JAUGE pour autres axes
const generateGauge = (canvasRef, instanceRef, score, label, color) => {
  if (!Chart || !canvasRef.value) return

  const ctx = canvasRef.value
  
  if (instanceRef) instanceRef.destroy()

  try {
    instanceRef = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Évalué', 'Restant'],
        datasets: [{
          data: [score, 5 - score],
          backgroundColor: [color, '#E5E7EB'],
          borderColor: ['#fff', '#fff'],
          borderWidth: 2,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { enabled: true },
        },
      },
    })
  } catch (e) {
    console.error('Gauge error:', e)
  }

  return instanceRef
}

watch(() => activeProcess.value, () => {
  if (activeProcess.value) {
    if (step.value === 'maturity') {
      nextTick(() => generateRadarChart(activeProcess.value))
    } else if (step.value === 'motricity') {
      const proc = props.processes.find(p => p.id === activeProcess.value)
      if (getAxisValue(proc, 'motricity')) {
        nextTick(() => {
          gaugeMotricityInstance = generateGauge(gaugeMotricity, gaugeMotricityInstance, getAxisValue(proc, 'motricity'), 'Motricité', '#F59E0B')
        })
      }
    } else if (step.value === 'transversality') {
      const proc = props.processes.find(p => p.id === activeProcess.value)
      if (getAxisValue(proc, 'transversality')) {
        nextTick(() => {
          gaugeTransversalityInstance = generateGauge(gaugeTransversality, gaugeTransversalityInstance, getAxisValue(proc, 'transversality'), 'Transversalité', '#8B5CF6')
        })
      }
    } else if (step.value === 'strategic') {
      const proc = props.processes.find(p => p.id === activeProcess.value)
      if (getAxisValue(proc, 'strategic')) {
        nextTick(() => {
          gaugeStrategicInstance = generateGauge(gaugeStrategic, gaugeStrategicInstance, getAxisValue(proc, 'strategic'), 'Stratégique', '#EC4899')
        })
      }
    }
  }
})

const saveMaturity = async (procId) => {
  if (!maturityScores[procId]) return

  try {
    const evaluations = Object.entries(maturityScores[procId]).map(([code, level]) => ({
      process_id: procId,
      criterion_code: code,
      level_score: level,
    }))

    await axios.post(route('process.core.process.evaluations.maturity.save'), { evaluations })
    alert('✅ Maturité enregistrée!')
    location.reload()
  } catch (err) {
    console.error('Erreur:', err)
    alert('❌ Erreur: ' + (err.response?.data?.message || err.message))
  }
}

// AUTRES AXES
const toggleProcess = (id) => {
  activeProcess.value = activeProcess.value === id ? null : id
}

const getAxisValue = (proc, axis) => {
  if (axis === 'motricity') return proc.motricity_score
  if (axis === 'transversality') return proc.transversality_score
  if (axis === 'strategic') return proc.strategic_score
  return null
}

const onAxisChange = (proc, axis, value) => {
  axios.post(route('process.core.process.evaluations.axis.save'), {
    process_id: proc.id,
    axis,
    score: value,
  }).then(() => {
    if (axis === 'motricity') {
      proc.motricity_score = value
      nextTick(() => {
        gaugeMotricityInstance = generateGauge(gaugeMotricity, gaugeMotricityInstance, value, 'Motricité', '#F59E0B')
      })
    } else if (axis === 'transversality') {
      proc.transversality_score = value
      nextTick(() => {
        gaugeTransversalityInstance = generateGauge(gaugeTransversality, gaugeTransversalityInstance, value, 'Transversalité', '#8B5CF6')
      })
    } else if (axis === 'strategic') {
      proc.strategic_score = value
      nextTick(() => {
        gaugeStrategicInstance = generateGauge(gaugeStrategic, gaugeStrategicInstance, value, 'Stratégique', '#EC4899')
      })
    }
  }).catch(err => console.error('Error:', err))
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.cursor-pointer {
  cursor: pointer;
}

label {
  cursor: pointer;
}
</style>