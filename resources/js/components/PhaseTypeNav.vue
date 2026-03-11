<template>
  <div class="phase-nav my-4">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <div class="text-muted fw-medium small">Phases :</div>
      
      <!-- Version desktop : pills -->
      <ul class="nav nav-pills gap-2 d-none d-md-flex">
        <li
          v-for="group in phasesByType"
          :key="group.phase_type"
          class="nav-item"
        >
          <button
            class="nav-link rounded-pill px-4 py-2"
            :class="{ active: selectedType === group.phase_type }"
            @click="selectType(group.phase_type)"
          >
            {{ group.label }}
            <span class="badge bg-white text-dark ms-2 fs-10">
              {{ group.phases?.length || 0 }}
            </span>
          </button>
        </li>
      </ul>

      <!-- Version mobile : select -->
      <select
        class="form-select form-select-sm d-md-none"
        v-model="selectedType"
        @change="selectType(selectedType)"
      >
        <option value="" disabled>Choisir une phase...</option>
        <option
          v-for="group in phasesByType"
          :key="group.phase_type"
          :value="group.phase_type"
        >
          {{ group.label }} ({{ group.phases?.length || 0 }})
        </option>
      </select>
    </div>
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

const emit = defineEmits(['select']);

const selectedType = ref<string>('');

watch(
  () => props.phasesByType,
  (newGroups) => {
    if (newGroups?.length > 0 && !selectedType.value) {
      selectedType.value = newGroups[0].phase_type;
      emit('select', selectedType.value);
    }
  },
  { immediate: true }
);

const selectType = (type: string) => {
  selectedType.value = type;
  emit('select', type);
};
</script>

<style scoped>
.phase-nav .nav-link {
  font-size: 0.92rem;
  font-weight: 500;
  background: #f1f5f9;
  color: #475569;
  border: none;
  transition: all 0.15s;
}

.phase-nav .nav-link.active {
  background: #0d6efd;
  color: white;
  box-shadow: 0 1px 4px rgba(13, 110, 253, 0.2);
}

.phase-nav .nav-link:hover:not(.active) {
  background: #e2e8f0;
}
</style>