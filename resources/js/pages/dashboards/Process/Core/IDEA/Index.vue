<template>
  <VerticalLayout>
    <Head title="Matrice IDEA" />

    <!-- ============ HEADER SECTION ============ -->
    <div class="page-header mb-4">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="ti ti-chart-line me-2"></i>Matrice IDEA
          </h1>
          <p class="page-subtitle" v-if="link">
            <i class="ti ti-building me-1"></i>{{ link.entity_name }}
            <span class="mx-2">•</span>
            <i class="ti ti-briefcase me-1"></i>{{ link.function_name }}
          </p>
        </div>
        <div class="header-right">
          <div class="user-info">
            <i class="ti ti-user-circle"></i>
            <span>{{ user?.name }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ SESSION ACTIVE ============ -->
    <b-card v-if="activeSession" class="active-session-card mb-4">
      <div class="session-banner" :style="{ backgroundColor: activeSession.color }">
        <div class="session-content">
          <div class="session-left">
            <i class="ti ti-circle-filled"></i>
            <div>
              <h5 class="session-name">{{ activeSession.name }}</h5>
              <small class="session-date">Créée le {{ formatDate(activeSession.created_at) }}</small>
            </div>
          </div>
          <span class="session-badge">Active</span>
        </div>
      </div>
    </b-card>

    <!-- ============ CONTROLS SECTION ============ -->
    <b-card v-if="activeSession" class="controls-card mb-4">
      <div class="controls-grid">
        <div class="control-group flex-grow-1">
          <label class="control-label">
            <i class="ti ti-flow-switch"></i>Processus
          </label>
          <select v-model="selectedProcessId" @change="loadMatrix" class="form-select">
            <option value="">📋 Sélectionner un processus...</option>
            <option v-for="p in processes" :key="p.id" :value="p.id">
              {{ p.code }} — {{ p.name }}
            </option>
          </select>
        </div>
        <div class="control-actions">
          <b-button 
            @click="exportToExcel" 
            :disabled="!selectedProcessId || saving" 
            variant="outline-primary" 
            class="btn-action"
          >
            <i class="ti ti-file-spreadsheet"></i>
            <span class="d-none d-md-inline">Export Excel</span>
          </b-button>
        </div>
      </div>
    </b-card>

    <!-- ============ MATRICE SECTION ============ -->
    <template v-if="selectedProcessId && activities.length > 0 && functions.length > 0">
      <!-- MATRICE INFO BAR -->
      <div class="matrice-header mb-3">
        <div class="header-info">
          <div>
            <h5 class="matrice-title">{{ selectedProcessName }}</h5>
            <small class="text-muted">
              <i class="ti ti-layout-grid"></i>
              {{ activities.length }} activités × {{ functions.length }} fonctions
            </small>
          </div>
          <div class="completion-indicator">
            <div class="indicator-value" :style="{ color: activeSession.color }">
              {{ getCompletionRate() }}%
            </div>
            <small>Complétude</small>
          </div>
        </div>
      </div>

      <!-- MATRICE TABLE CARD -->
      <b-card class="matrice-card mb-4">
        <div class="table-container">
          <table class="matrice-table">
            <thead>
              <tr>
                <th class="col-activities">
                  <span class="th-label">Activités</span>
                </th>
                <th v-for="func in functions" :key="func.id" class="col-function">
                  <span class="th-label">{{ func.name }}</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(activity, idx) in activities" :key="activity.id" class="data-row" :class="{ 'row-even': idx % 2 === 0 }">
                <!-- ACTIVITÉ -->
                <td class="cell-activity">
                  <div class="activity-cell">
                    <div class="activity-code">{{ activity.code }}</div>
                    <div class="activity-name">{{ activity.name }}</div>
                  </div>
                </td>

                <!-- CELLULES IDEA -->
                <td v-for="func in functions" :key="func.id" class="cell-idea" :class="{ 'cell-with-data': getMatrixCodes(activity.id, func.id).length > 0 }">
                  <div class="idea-cell">
                    <!-- CODES IDEA -->
                    <div class="idea-badges">
                      <span 
                        v-for="code in getMatrixCodes(activity.id, func.id)" 
                        :key="code" 
                        class="idea-badge"
                        :style="{ backgroundColor: getRoleColor(code) }"
                        @click="removeIdeaCode(activity.id, func.id, code)"
                        :title="`${code} - Cliquer pour retirer`"
                      >
                        {{ code }}
                      </span>
                    </div>

                    <!-- BOUTON AJOUTER -->
                    <div class="idea-add-button">
                      <button 
                        class="btn-idea-add"
                        type="button"
                        :id="`idea_btn_${activity.id}_${func.id}`"
                        data-bs-toggle="dropdown"
                        :title="'Ajouter un rôle IDEA'"
                      >
                        <i class="ti ti-plus"></i>
                      </button>
                      <ul :aria-labelledby="`idea_btn_${activity.id}_${func.id}`" class="dropdown-menu dropdown-menu-sm">
                        <li v-for="role in ideaRoles" :key="role.code">
                          <a 
                            class="dropdown-item"
                            @click.prevent="addIdeaCode(activity.id, func.id, role.code)"
                          >
                            <span class="role-badge" :style="{ backgroundColor: getRoleColor(role.code) }">
                              {{ role.code }}
                            </span>
                            <span class="role-text">{{ role.label }}</span>
                          </a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- MATRICE FOOTER -->
        <div class="matrice-footer mt-4 pt-3 border-top">
          <div class="footer-legend">
            <div class="legend-items">
              <div class="legend-item">
                <span class="legend-color" style="background: #8B5CF6;"></span>
                <strong>I</strong> = Impliqué
              </div>
              <div class="legend-item">
                <span class="legend-color" style="background: #DC2626;"></span>
                <strong>D</strong> = Décide
              </div>
              <div class="legend-item">
                <span class="legend-color" style="background: #0284C7;"></span>
                <strong>E</strong> = Élabore
              </div>
              <div class="legend-item">
                <span class="legend-color" style="background: #16A34A;"></span>
                <strong>A</strong> = Applique
              </div>
            </div>
          </div>

          <div class="footer-actions">
            <b-button 
              @click="saveMatrix" 
              :disabled="saving" 
              variant="primary"
              class="btn-save"
            >
              <i class="ti ti-device-floppy"></i>
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </b-button>
          </div>
        </div>
      </b-card>

      <!-- ============ STATISTIQUES ============ -->
      <b-card class="stats-card">
        <h6 class="stats-title mb-3">
          <i class="ti ti-chart-bar"></i>
          Statistiques IDEA
        </h6>

        <div class="stats-grid">
          <div class="stat-card stat-completion">
            <div class="stat-icon">
              <i class="ti ti-progress-check"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ getCompletionRate() }}%</div>
              <div class="stat-label">Complétude</div>
              <small class="stat-detail">{{ getTotalFilledCells() }}/{{ getTotalCells() }} cellules</small>
            </div>
          </div>

          <div class="stat-card stat-decide">
            <div class="stat-icon">
              <i class="ti ti-gavel"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ getDecideCount() }}</div>
              <div class="stat-label">Décide (D)</div>
              <small class="stat-detail">Responsables des décisions</small>
            </div>
          </div>

          <div class="stat-card stat-elaborate">
            <div class="stat-icon">
              <i class="ti ti-pencil"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ getElaborateCount() }}</div>
              <div class="stat-label">Élabore (E)</div>
              <small class="stat-detail">Élaborent les solutions</small>
            </div>
          </div>

          <div class="stat-card stat-apply">
            <div class="stat-icon">
              <i class="ti ti-run"></i>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ getApplyCount() }}</div>
              <div class="stat-label">Applique (A)</div>
              <small class="stat-detail">Exécutent les tâches</small>
            </div>
          </div>
        </div>
      </b-card>
    </template>

    <!-- ============ EMPTY STATE ============ -->
    <div v-else-if="activeSession && !selectedProcessId" class="empty-state">
      <div class="empty-icon">
        <i class="ti ti-inbox"></i>
      </div>
      <h5 class="empty-title">Aucun processus sélectionné</h5>
      <p class="empty-text">Choisissez un processus pour commencer à éditer la matrice IDEA</p>
    </div>

    <!-- NO SESSION -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <i class="ti ti-alert-triangle"></i>
      </div>
      <h5 class="empty-title">Aucune session active</h5>
      <p class="empty-text">Créez une session d'évaluation pour commencer</p>
    </div>

  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layouts/VerticalLayout.vue"
import { ref, computed, onMounted } from "vue"
import axios from "axios"

const props = defineProps({
  user: Object,
  link: Object,
  processes: Array,
  activeSession: Object,
  ideaRoles: Array,
})

const selectedProcessId = ref("")
const activities = ref([])
const functions = ref([])
const matrix = ref({})
const saving = ref(false)

const activeSession = ref(props.activeSession || null)
const ideaRoles = ref(props.ideaRoles || [])

const selectedProcessName = computed(() => {
  const proc = props.processes?.find(p => p.id === parseInt(selectedProcessId.value))
  return proc ? `${proc.code} — ${proc.name}` : ''
})

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const getMatrixCodes = (activityId, functionId) => {
  return matrix.value[activityId]?.[functionId] || []
}

const addIdeaCode = (activityId, functionId, code) => {
  if (!matrix.value[activityId]) matrix.value[activityId] = {}
  const codes = matrix.value[activityId][functionId] || []
  if (!codes.includes(code)) codes.push(code)
  matrix.value[activityId][functionId] = codes
}

const removeIdeaCode = (activityId, functionId, code) => {
  if (!matrix.value[activityId]) return
  const codes = matrix.value[activityId][functionId] || []
  const idx = codes.indexOf(code)
  if (idx >= 0) codes.splice(idx, 1)
  matrix.value[activityId][functionId] = codes
}

const getRoleColor = (code) => {
  const colors = { 
    I: '#8B5CF6',
    D: '#DC2626',
    E: '#0284C7',
    A: '#16A34A'
  }
  return colors[code] || '#6B7280'
}

const getCompletionRate = () => {
  const total = getTotalCells()
  return total === 0 ? 0 : Math.round((getTotalFilledCells() / total) * 100)
}

const getTotalCells = () => activities.value.length * functions.value.length

const getTotalFilledCells = () => {
  let filled = 0
  activities.value.forEach(a => functions.value.forEach(f => {
    if (getMatrixCodes(a.id, f.id).length > 0) filled++
  }))
  return filled
}

const getDecideCount = () => {
  let count = 0
  activities.value.forEach(a => functions.value.forEach(f => {
    if (getMatrixCodes(a.id, f.id).includes('D')) count++
  }))
  return count
}

const getElaborateCount = () => {
  let count = 0
  activities.value.forEach(a => functions.value.forEach(f => {
    if (getMatrixCodes(a.id, f.id).includes('E')) count++
  }))
  return count
}

const getApplyCount = () => {
  let count = 0
  activities.value.forEach(a => functions.value.forEach(f => {
    if (getMatrixCodes(a.id, f.id).includes('A')) count++
  }))
  return count
}

const loadMatrix = async () => {
  if (!selectedProcessId.value || !activeSession.value) return
  try {
    saving.value = true
    const res = await axios.get(route('process.core.idea.matrix.load'), {
      params: { session_id: activeSession.value.id, process_id: selectedProcessId.value }
    })
    activities.value = res.data.activities || []
    functions.value = res.data.functions || []
    matrix.value = {}
    res.data.matrix?.forEach(row => {
      matrix.value[row.activity_id] = {}
      row.assignments?.forEach(assign => {
        const codes = assign.code ? assign.code.split(',').filter(c => c.trim()) : []
        matrix.value[row.activity_id][assign.function_id] = codes
      })
    })
  } catch (e) {
    console.error('Erreur:', e)
  } finally {
    saving.value = false
  }
}

const saveMatrix = async () => {
  if (!selectedProcessId.value || !activeSession.value) return
  try {
    saving.value = true
    const assignments = []
    activities.value.forEach(activity => {
      functions.value.forEach(func => {
        const codes = getMatrixCodes(activity.id, func.id)
        if (codes.length > 0) {
          assignments.push({
            activity_id: activity.id,
            function_id: func.id,
            idea_codes: codes.join(',')
          })
        }
      })
    })
    const res = await axios.post(route('process.core.idea.matrix.save'), {
      session_id: activeSession.value.id,
      process_id: selectedProcessId.value,
      assignments: assignments
    })
    if (res.data.success) alert('✅ Enregistré')
  } catch (e) {
    console.error('Erreur:', e)
  } finally {
    saving.value = false
  }
}

const exportToExcel = async () => {
  if (!selectedProcessId.value || !activeSession.value) return
  try {
    saving.value = true
    const res = await axios.get(route('process.core.idea.export-excel'), {
      params: { session_id: activeSession.value.id, process_id: selectedProcessId.value },
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `IDEA_${activeSession.value.name}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
  } catch (e) {
    console.error('Erreur export:', e)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  activeSession.value = props.activeSession
})
</script>

<style scoped>
/* ============ PAGE HEADER ============ */
.page-header {
  background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
  padding: 2rem;
  border-radius: 8px;
  color: white;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.page-subtitle {
  font-size: 0.95rem;
  opacity: 0.9;
  margin: 0;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.1);
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
}

.user-info i {
  font-size: 1.2rem;
}

/* ============ SESSION CARD ============ */
.active-session-card {
  border: none;
  border-radius: 8px;
  padding: 0;
  overflow: hidden;
}

.session-banner {
  padding: 1.5rem;
  color: white;
}

.session-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.session-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.session-left i {
  font-size: 1.5rem;
}

.session-name {
  font-weight: 600;
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
}

.session-date {
  opacity: 0.9;
  font-size: 0.85rem;
}

.session-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
}

/* ============ CONTROLS ============ */
.controls-card {
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  padding: 1.5rem;
}

.controls-grid {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1rem;
  align-items: flex-end;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.control-label {
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #374151;
}

.control-group select {
  padding: 0.75rem 1rem;
  border: 1px solid #D1D5DB;
  border-radius: 6px;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.control-group select:focus {
  outline: none;
  border-color: #7C3AED;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}

.control-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
}

/* ============ MATRICE HEADER ============ */
.matrice-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #F9FAFB;
  border-radius: 6px;
}

.header-info {
  flex: 1;
}

.matrice-title {
  font-weight: 700;
  font-size: 1.1rem;
  margin: 0 0 0.5rem 0;
  color: #1F2937;
}

.completion-indicator {
  text-align: center;
}

.indicator-value {
  font-size: 1.8rem;
  font-weight: 700;
}

.indicator-value + small {
  font-size: 0.8rem;
  color: #6B7280;
}

/* ============ MATRICE TABLE ============ */
.matrice-card {
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  padding: 0;
  overflow: hidden;
}

.table-container {
  overflow-x: auto;
  max-height: 700px;
  overflow-y: auto;
}

.matrice-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.matrice-table thead {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #5B21B6;
  color: white;
}

.matrice-table th {
  padding: 1rem 0.75rem;
  font-weight: 600;
  text-align: center;
  border: 1px solid #D1D5DB;
}

.th-label {
  display: block;
  word-break: break-word;
  font-size: 0.8rem;
}

.col-activities {
  width: 180px;
  text-align: left;
}

.col-function {
  width: 140px;
  min-width: 140px;
}

.matrice-table tbody tr {
  border-bottom: 1px solid #E5E7EB;
  transition: background-color 0.15s;
}

.matrice-table tbody tr.row-even {
  background: #F9FAFB;
}

.matrice-table tbody tr:hover {
  background: #F3F4F6;
}

.cell-activity {
  background: #F3E8FF;
  padding: 1rem 0.75rem;
  font-weight: 600;
  position: sticky;
  left: 0;
  z-index: 5;
}

.activity-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.activity-code {
  font-weight: 700;
  color: #1F2937;
  font-size: 0.9rem;
}

.activity-name {
  font-weight: 400;
  color: #6B7280;
  font-size: 0.75rem;
  line-height: 1.2;
}

.cell-idea {
  padding: 0.75rem;
  text-align: center;
  vertical-align: middle;
}

.cell-with-data {
  background: #FAF5FF;
}

.idea-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  min-height: 50px;
  justify-content: center;
}

.idea-badges {
  display: flex;
  gap: 0.4rem;
  justify-content: center;
  flex-wrap: wrap;
  width: 100%;
}

.idea-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 4px;
  color: white;
  font-weight: 700;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.idea-badge:hover {
  transform: scale(1.15);
  opacity: 0.85;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.btn-idea-add {
  background: white;
  border: 2px solid #D1D5DB;
  color: #7C3AED;
  width: 28px;
  height: 28px;
  border-radius: 4px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  padding: 0;
}

.btn-idea-add:hover {
  background: #F3E8FF;
  border-color: #7C3AED;
  transform: scale(1.1);
}

.dropdown-menu {
  min-width: 200px;
  border-radius: 6px;
}

.dropdown-item {
  padding: 0.6rem 1rem;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  transition: all 0.15s;
}

.dropdown-item:hover {
  background: #F3E8FF;
}

.role-badge {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.role-text {
  font-weight: 500;
  color: #374151;
}

/* ============ MATRICE FOOTER ============ */
.matrice-footer {
  padding: 1.5rem;
  background: #F9FAFB;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.legend-items {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.85rem;
}

.legend-color {
  width: 20px;
  height: 20px;
  border-radius: 3px;
  flex-shrink: 0;
}

.footer-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-save {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  background: #7C3AED;
  border: none;
  color: white;
  transition: all 0.2s;
}

.btn-save:hover:not(:disabled) {
  background: #6D28D9;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(124, 58, 237, 0.2);
}

/* ============ STATS ============ */
.stats-card {
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  padding: 2rem;
}

.stats-title {
  font-weight: 700;
  font-size: 1.1rem;
  color: #1F2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  border-radius: 8px;
  background: #F9FAFB;
  border-left: 4px solid;
}

.stat-icon {
  font-size: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-completion {
  border-left-color: #7C3AED;
}

.stat-completion .stat-icon {
  color: #7C3AED;
}

.stat-decide {
  border-left-color: #DC2626;
}

.stat-decide .stat-icon {
  color: #DC2626;
}

.stat-elaborate {
  border-left-color: #0284C7;
}

.stat-elaborate .stat-icon {
  color: #0284C7;
}

.stat-apply {
  border-left-color: #16A34A;
}

.stat-apply .stat-icon {
  color: #16A34A;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1F2937;
}

.stat-label {
  font-weight: 600;
  font-size: 0.95rem;
  color: #374151;
}

.stat-detail {
  font-size: 0.8rem;
  color: #6B7280;
}

/* ============ EMPTY STATE ============ */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: #F9FAFB;
  border-radius: 8px;
}

.empty-icon {
  font-size: 4rem;
  color: #D1D5DB;
  margin-bottom: 1rem;
}

.empty-title {
  font-weight: 700;
  font-size: 1.3rem;
  color: #1F2937;
  margin-bottom: 0.5rem;
}

.empty-text {
  color: #6B7280;
  font-size: 0.95rem;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }

  .controls-grid {
    grid-template-columns: 1fr;
  }

  .matrice-footer {
    flex-direction: column;
    gap: 1rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .col-activities {
    width: 120px;
  }

  .col-function {
    width: 100px;
    min-width: 100px;
  }

  .th-label {
    font-size: 0.7rem;
  }
}
</style>