<template>
  <b-card class="shadow-sm p-4">

    <!-- TITRE -->
    <h5 class="fw-bold mb-3">
      <i class="ti ti-star"></i>
      {{ axisLabel }}
    </h5>

    <!-- TABLE DES NIVEAUX -->
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr class="table-header">
            <th style="width: 20%">Code</th>
            <th style="width: 40%">Description</th>
            <th class="text-center" style="width: 40%">Sélection</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="item in scales" :key="item.id">

            <!-- CODE -->
            <td>
              <strong class="text-primary">{{ item.code }}</strong>
            </td>

            <!-- DESCRIPTION -->
            <td>
              <strong>{{ item.label }}</strong><br />
              <small class="text-muted">{{ item.description }}</small>
            </td>

            <!-- ÉTOILES -->
            <td class="text-center">
              <div class="d-flex justify-content-center gap-1">
                <span
                  v-for="n in 5"
                  :key="n"
                  class="star"
                  :class="{ active: selectedScore === n }"
                  @click="selectScore(n)"
                >
                  ⭐
                </span>
              </div>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

  </b-card>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";

/* PROPS */
const props = defineProps({
  axis: String,          // motricity / transversality / strategic
  session: Number,
  process: Number,
  scales: Array,
});

/* EMIT */
const emit = defineEmits(["save"]);

/* SCORE SÉLECTIONNÉ */
const selectedScore = ref(null);

/* LABEL DE L’AXE */
const axisLabel = computed(() => {
  return {
    motricity: "Motricité",
    transversality: "Transversalité",
    strategic: "Poids stratégique",
  }[props.axis] || "Axe";
});

/* CHOIX ÉTOILES */
const selectScore = (score) => {
  selectedScore.value = score;

  emit("save", {
    axis: props.axis,
    score,
  });
};
</script>

<style scoped>
.table-header {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.star {
  font-size: 1.6rem;
  cursor: pointer;
  opacity: 0.4;
  transition: 0.15s;
}

.star:hover {
  transform: scale(1.15);
}

.star.active {
  opacity: 1;
  text-shadow: 0 0 8px #ffd700;
  color: #ffcc00;
}
</style>
