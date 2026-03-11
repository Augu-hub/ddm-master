<template>
  <div v-if="store.phasesByType.length" class="phase-nav">

    <!-- Code mission -->
    <div v-if="store.missionCode" class="mission-code">
      <i class="ti ti-clipboard-list"></i>
      {{ store.missionCode }}
    </div>

    <!-- Groupes -->
    <div
      v-for="group in store.phasesByType"
      :key="group.phase_type"
      class="phase-group"
    >
      <!-- Titre du groupe -->
      <div class="group-header">
        <i :class="groupIcon(group.phase_type)"></i>
        <span>{{ group.label }}</span>
        <span class="count">{{ group.phases.length }}</span>
      </div>

      <!-- Liste des phases -->
      <div class="phase-list">
        <div
          v-for="phase in group.phases"
          :key="phase.assignment_id"
          class="phase-item"
        >
          <div class="phase-left">
            <span
              class="status-dot"
              :style="{ background: phaseStatusColor(phase.phase_status) }"
            ></span>
            <span class="phase-label">
              {{ phase.label || 'Sans libellé' }}
            </span>
          </div>

          <span class="status-text">
            {{ phaseStatusLabel(phase.phase_status) }}
          </span>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { usePhaseNavStore } from '@/stores/phaseNavStore'

const store = usePhaseNavStore()

const GROUP_ICONS: Record<string, string> = {
  PREPARATION: 'ti ti-clipboard-list',
  VERIFICATION: 'ti ti-search',
  CONCLUSION: 'ti ti-check',
  SUIVI: 'ti ti-refresh'
}

const groupIcon = (t: string) => GROUP_ICONS[t] || 'ti ti-layers'

const STATUS_COLORS: Record<string, string> = {
  pending: '#D97706',
  in_progress: '#2563EB',
  completed: '#059669',
  skipped: '#9CA3AF'
}

const STATUS_LABELS: Record<string, string> = {
  pending: 'En attente',
  in_progress: 'En cours',
  completed: 'Terminée',
  skipped: 'Ignorée'
}

const phaseStatusColor = (s: string) =>
  STATUS_COLORS[s] || '#CBD5E1'

const phaseStatusLabel = (s: string) =>
  STATUS_LABELS[s] || s
</script>

<style scoped>
.phase-nav {
  background: #fff;
  padding: 16px;
  border-top: 1px solid #e5e7eb;
}

/* Code mission */
.mission-code {
  font-weight: 700;
  font-size: 0.9rem;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 6px;
  color: #374151;
}

/* Groupe */
.phase-group {
  margin-bottom: 20px;
}

/* Header groupe */
.group-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  margin-bottom: 8px;
  color: #111827;
}

.count {
  margin-left: auto;
  background: #f3f4f6;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
}

/* Liste phases */
.phase-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.phase-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 10px;
  border-radius: 6px;
  background: #f9fafb;
  transition: background 0.2s ease;
}

.phase-item:hover {
  background: #f3f4f6;
}

.phase-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.phase-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #374151;
}

.status-text {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
}
</style>