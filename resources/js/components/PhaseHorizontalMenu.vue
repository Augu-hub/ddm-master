<!-- resources/js/components/PhaseHorizontalMenu.vue -->
<template>
  <div class="d-flex align-items-center ms-4 ps-3 border-start">
    <small class="text-muted me-3 fw-medium">Phases :</small>

    <ul class="nav nav-pills nav-sm gap-2">
      <li
        v-for="group in phasesByType"
        :key="group.phase_type"
        class="nav-item"
      >
        <button
          class="nav-link rounded-pill px-3 py-1 small"
          :class="{ active: activePhase === group.phase_type }"
          @click="scrollTo(group.phase_type)"
        >
          {{ group.label }}
          <span class="badge bg-white text-dark ms-1 px-2 py-0 fs-11">
            {{ group.phases?.length || 0 }}
          </span>
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{
  phasesByType: Array<{
    phase_type: string;
    label: string;
    phases: any[];
  }>;
}>();

const activePhase = ref<string>('');

watch(
  () => props.phasesByType,
  (groups) => {
    if (groups?.length > 0 && !activePhase.value) {
      activePhase.value = groups[0].phase_type;
    }
  },
  { immediate: true }
);

const scrollTo = (phaseType: string) => {
  activePhase.value = phaseType;
  const el = document.getElementById(`phase-group-${phaseType}`);
  if (el) {
    const offset = 120; // hauteur navbar + marge
    const y = el.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top: y, behavior: 'smooth' });
  }
};
</script>

<style scoped>
.nav-pills .nav-link {
  font-weight: 500;
  background-color: #f1f5f9;
  color: #475569;
  border: none;
  transition: all 0.15s;
}

.nav-pills .nav-link.active {
  background-color: #0d6efd;
  color: white;
}

.nav-pills .nav-link:hover:not(.active) {
  background-color: #e2e8f0;
}
</style>