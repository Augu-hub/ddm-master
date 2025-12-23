<template>
  <div>
    <b-row class="g-4">
      <!-- TABLEAU DE SÉLECTION -->
      <b-col lg="7">
        <div class="table-responsive">
          <table class="table table-sm table-hover" style="font-size: 0.85rem;">
            <thead style="background: #f0fdf4; border-bottom: 2px solid #10b981;">
              <tr>
                <th style="padding: 0.75rem;">Code</th>
                <th style="padding: 0.75rem;">Libellé</th>
                <th style="padding: 0.75rem;">Description</th>
                <th class="text-center" style="padding: 0.75rem; width: 80px;">Sélection</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="scale in scales" :key="scale.id" :class="{ 'table-active': selectedScale === scale.id }">
                <td style="padding: 0.75rem; font-weight: 600; color: #10b981; min-width: 60px;">
                  {{ scale.code }}
                </td>
                <td style="padding: 0.75rem;">
                  <strong style="display: block; font-size: 0.9rem;">{{ scale.label }}</strong>
                </td>
                <td style="padding: 0.75rem; font-size: 0.85rem; color: #666;">
                  {{ scale.description }}
                </td>
                <td style="padding: 0.75rem; text-align: center;">
                  <input 
                    type="radio"
                    :name="`${axis}_select`"
                    :value="scale.id"
                    :checked="selectedScale === scale.id"
                    @change="onScaleSelected(scale)"
                    style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </b-col>

      <!-- GAUGE VISUELLE -->
      <b-col lg="5">
        <div style="padding: 2rem; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 8px; border: 2px solid #10b981; text-align: center;">
          <div v-if="selectedScale">
            <div style="position: relative; height: 250px; margin-bottom: 2rem;">
              <canvas ref="gaugeChart"></canvas>
            </div>
            
            <!-- INFOS SÉLECTION -->
            <div style="padding: 1rem; background: white; border-radius: 6px; border: 1px solid #dcfce7;">
              <div style="margin-bottom: 0.75rem;">
                <strong style="color: #10b981; font-size: 1.1rem;">{{ selectedScaleData.code }}</strong>
              </div>
              <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">
                {{ selectedScaleData.label }}
              </div>
              <div style="font-size: 0.85rem; color: #999; padding-top: 0.5rem; border-top: 1px solid #e5e7eb;">
                {{ selectedScaleData.description }}
              </div>
            </div>
          </div>
          <div v-else style="color: #999; padding: 2rem;">
            <i class="ti ti-chart-pie" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
            Sélectionnez un niveau
          </div>
        </div>
      </b-col>
    </b-row>

    <!-- BOUTONS ACTIONS -->
    <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
      <b-button variant="secondary" @click="reset" class="fw-bold">
        <i class="ti ti-x me-1"></i> Réinitialiser
      </b-button>
      <b-button :style="{ background: color, border: 'none', color: 'white' }" @click="saveSelection" class="fw-bold">
        <i class="ti ti-device-floppy me-1"></i> Enregistrer
      </b-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue'

const props = defineProps({
  processId: [String, Number],
  sessionId: [String, Number],
  axis: String,
  color: String,
  scales: Array,
  initialScore: [Number, String],
})

const emit = defineEmits(['save'])

let Chart
onMounted(async () => {
  try {
    const module = await import('chart.js/auto')
    Chart = module.default
  } catch (e) {
    console.warn('Chart.js not available')
  }
})

const selectedScale = ref(null)
const gaugeChart = ref(null)
let gaugeInstance = null

const selectedScaleData = computed(() => {
  const scale = props.scales?.find(s => s.id === selectedScale.value)
  return scale || { code: '', label: '', description: '' }
})

const onScaleSelected = (scale) => {
  selectedScale.value = scale.id
  nextTick(() => generateGauge())
}

const generateGauge = () => {
  if (!Chart || !gaugeChart.value || !selectedScale.value) return
  
  const scale = props.scales.find(s => s.id === selectedScale.value)
  if (!scale) return
  
  // Déterminer le score (1-5) basé sur la position
  const scoreValue = scale.id || 1
  
  if (gaugeInstance) gaugeInstance.destroy()
  
  try {
    gaugeInstance = new Chart(gaugeChart.value, {
      type: 'doughnut',
      data: { 
        labels: ['Sélection', 'Restant'], 
        datasets: [{ 
          data: [scoreValue, 5 - scoreValue], 
          backgroundColor: [props.color || '#10b981', '#E5E7EB'], 
          borderColor: ['#fff', '#fff'], 
          borderWidth: 3,
          borderRadius: 0,
          rotation: -90,
          circumference: 180
        }] 
      },
      options: { 
        responsive: true, 
        maintainAspectRatio: false, 
        plugins: { 
          legend: { display: false }, 
          tooltip: { enabled: true } 
        },
        cutout: '60%'
      }
    })
  } catch (e) {
    console.error('Gauge error:', e)
  }
}

const saveSelection = () => {
  if (!selectedScale.value) {
    alert('⚠️ Veuillez sélectionner une option')
    return
  }
  
  // Émettre vers le parent
  emit('save', props.axis, selectedScale.value)
}

const reset = () => {
  selectedScale.value = null
  if (gaugeInstance) gaugeInstance.destroy()
}

// Initialiser avec la valeur si elle existe
watch(
  () => props.initialScore,
  (newVal) => {
    if (newVal) {
      selectedScale.value = newVal
      nextTick(() => generateGauge())
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.table-active {
  background-color: rgba(16, 185, 129, 0.05) !important;
}

tr:hover {
  background-color: rgba(16, 185, 129, 0.02);
}

input[type="radio"] {
  cursor: pointer;
}
</style>