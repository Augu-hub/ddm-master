<template>
  <header
    v-if="phaseNavStore.phasesByType.length"
    class="phase-topbar"
  >
    <div class="topbar-left">
      <strong>{{ phaseNavStore.missionCode }}</strong>
      <span>{{ phaseNavStore.missionLabel }}</span>
    </div>

    <ul class="phase-menu">
      <li
        v-for="group in phaseNavStore.phasesByType"
        :key="group.phase_type"
        class="menu-group"
      >
        <span class="group-label">
          {{ group.label }}
        </span>

        <!-- PHASES -->
        <ul class="submenu">
          <li
            v-for="phase in group.phases"
            :key="phase.assignment_id"
            class="submenu-phase"
          >
            <span class="phase-label">
              {{ phase.label }}
            </span>

            <!-- SOUS PHASES -->
            <ul
              v-if="phase.sub_phases?.length"
              class="sub-submenu"
            >
              <li
                v-for="sub in phase.sub_phases"
                :key="sub.id"
                class="subphase-item"
              >
                {{ sub.label }}
              </li>
            </ul>

          </li>
        </ul>
      </li>
    </ul>
  </header>
</template>

<script setup>
import { usePhaseNavStore } from '@/stores/phaseNavStore'
const phaseNavStore = usePhaseNavStore()
</script>

<style scoped>
.phase-topbar {
  display: flex;
  align-items: center;
  gap: 40px;
  padding: 12px 24px;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
}

.phase-menu {
  display: flex;
  gap: 30px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.menu-group {
  position: relative;
  font-weight: 600;
  cursor: pointer;
}

.submenu {
  position: absolute;
  top: 28px;
  left: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 6px 0;
  display: none;
  min-width: 220px;
  list-style: none;
}

.menu-group:hover .submenu {
  display: block;
}

.submenu-phase {
  padding: 6px 12px;
  position: relative;
}

.submenu-phase:hover {
  background: #f3f4f6;
}

/* SOUS-SOUS MENU */
.sub-submenu {
  margin-top: 6px;
  margin-left: 10px;
  padding-left: 10px;
  border-left: 2px solid #e5e7eb;
  list-style: none;
}

.subphase-item {
  font-size: 0.75rem;
  padding: 4px 0;
  color: #6b7280;
}

.subphase-item:hover {
  color: #111827;
}
</style>