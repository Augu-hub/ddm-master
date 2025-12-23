<!-- resources/js/pages/dashboards/Process/Core/Evaluations/ProcessEvaluation/components/RadarChart.vue -->

<template>
  <div style="width:100%; height:380px;">
    <canvas ref="canvasEl"></canvas>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from "vue"
import Chart from "chart.js/auto"

const props = defineProps({
  dataSets: Array, // [3,4,5,...]
})

const canvasEl = ref(null)
let chart = null

onMounted(() => build())
watch(() => props.dataSets, () => build(), { deep:true })

function build() {
  if (!canvasEl.value) return
  if (chart) chart.destroy()

  chart = new Chart(canvasEl.value, {
    type: "radar",
    data: {
      labels: [
        "CEM1","CEM2","CEM3","CEM4","CEM5","CEM6",
        "CEM7","CEM8","CEM9","CEM10","CEM11","CEM12"
      ],
      datasets: [{
        label: "Maturité",
        data: props.dataSets,
        borderColor: "#667eea",
        backgroundColor: "rgba(102,126,234,0.25)",
        pointBackgroundColor: "#667eea",
        pointBorderColor: "#fff",
        borderWidth: 3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        r: {
          beginAtZero: true,
          min: 0,
          max: 5,
          ticks: { stepSize: 1 },
        }
      },
      plugins: {
        legend: { display:false }
      }
    }
  })
}
</script>
