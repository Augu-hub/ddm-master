<template>
  <VerticalLayout>
    <Head title="Matrice RACI" />

    <!-- HEADER -->
    <div class="header-raci mb-3">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h1 class="header-title">
            <i class="ti ti-users-group me-2"></i>Matrice RACI
          </h1>
          <p class="header-subtitle" v-if="link">{{ link.entity_name }} • {{ link.function_name }}</p>
        </div>
        <div class="col-md-6 text-end">
          <span class="user-badge"><i class="ti ti-user me-1"></i>{{ user?.name }}</span>
        </div>
      </div>
    </div>

    <!-- SESSION ACTIVE -->
    <b-card v-if="activeSession" class="session-card mb-3" :style="{ borderTopColor: activeSession.color }">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5 class="session-title mb-0" :style="{ color: activeSession.color }">
            <i class="ti ti-circle-filled me-2"></i>{{ activeSession.name }}
          </h5>
          <small class="text-muted">{{ formatDate(activeSession.created_at) }}</small>
        </div>
        <span class="badge" :style="{ backgroundColor: activeSession.color }">Active</span>
      </div>
    </b-card>

    <!-- SÉLECTION PROCESSUS -->
    <b-card v-if="activeSession" class="mb-3">
      <div class="row g-2">
        <div class="col-md-10">
          <select v-model="selectedProcessId" @change="loadMatrix" class="form-select form-select-sm">
            <option value="">📋 Choisir un processus...</option>
            <option v-for="p in processes" :key="p.id" :value="p.id">
              {{ p.code }} — {{ p.name }}
            </option>
          </select>
        </div>
        <div class="col-md-2">
          <b-button @click="exportToExcel" :disabled="!selectedProcessId || saving" variant="outline-info" size="sm" class="w-100">
            <i class="ti ti-download"></i> Excel
          </b-button>
        </div>
      </div>
    </b-card>

    <!-- MATRICE RACI -->
    <template v-if="selectedProcessId && activities.length > 0 && functions.length > 0">
      <b-card class="mb-3">
        <!-- INFO -->
        <div class="info-bar mb-2 p-2 rounded" :style="{ backgroundColor: activeSession.color + '15', borderLeft: '3px solid ' + activeSession.color }">
          <div class="d-flex justify-content-between">
            <div>
              <strong>{{ selectedProcessName }}</strong>
              <small class="text-muted d-block">{{ activities.length }}a × {{ functions.length }}f</small>
            </div>
            <div class="completion-badge" :style="{ backgroundColor: activeSession.color, color: 'white' }">
              {{ getCompletionRate() }}%
            </div>
          </div>
        </div>

        <!-- TABLEAU -->
        <div class="table-wrapper">
          <table class="matrice-table">
            <thead>
              <tr>
                <th class="col-act">Activités</th>
                <th v-for="func in functions" :key="func.id" class="col-func">
                  {{ func.name }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(activity, idx) in activities" :key="activity.id" :class="{ 'alt': idx % 2 === 1 }">
                <td class="cell-act">
                  <strong>{{ activity.code }}</strong>
                  <small class="d-block text-muted" style="font-size: 0.7rem;">{{ activity.name }}</small>
                  <span v-if="getActivityErrors(activity.id).length > 0" class="err-badge">
                    {{ getActivityErrors(activity.id).length }}
                  </span>
                </td>
                <td v-for="func in functions" :key="func.id" :class="getCellClass(activity.id, func.id)">
                  <div class="cell-content">
                    <div class="raci-codes">
                      <span v-for="code in getMatrixCodes(activity.id, func.id)" :key="code" class="code" :style="{ backgroundColor: getRoleColor(code) }" @click="removeRaciCode(activity.id, func.id, code)">
                        {{ code }}
                      </span>
                    </div>
                    <div class="dropdown d-inline">
                      <button class="btn-add" type="button" :id="'b_' + activity.id + '_' + func.id" data-bs-toggle="dropdown">+</button>
                      <ul :aria-labelledby="'b_' + activity.id + '_' + func.id" class="dropdown-menu dropdown-menu-sm">
                        <li v-for="role in raciRoles" :key="role.code">
                          <a class="dropdown-item" @click.prevent="addRaciCode(activity.id, func.id, role.code)">
                            <span class="badge me-1" :style="{ backgroundColor: getRoleColor(role.code) }">{{ role.code }}</span>
                            {{ role.label }}
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

        <!-- FOOTER -->
        <div class="mt-2 text-end">
          <b-button @click="saveMatrix" :disabled="saving" variant="primary" size="sm">
            <i class="ti ti-device-floppy me-1"></i> {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </b-button>
        </div>
      </b-card>

      <!-- STATS -->
      <b-card class="stats-card">
        <div class="row g-2 mb-3">
          <div class="col-6 col-md-3">
            <div class="stat-box stat-completion">
              <div class="stat-value">{{ getCompletionRate() }}%</div>
              <small>Complétude</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-box stat-errors">
              <div class="stat-value">{{ getCriticalErrorsCount() }}</div>
              <small>Erreurs</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-box stat-warnings">
              <div class="stat-value">{{ getWarningsCount() }}</div>
              <small>Avertissements</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-box" :class="getTotalErrorsCount() === 0 ? 'stat-ok' : 'stat-ko'">
              <div class="stat-value">{{ getTotalErrorsCount() === 0 ? '✅' : '❌' }}</div>
              <small>Status</small>
            </div>
          </div>
        </div>

        <!-- ANOMALIES COMPACTES -->
        <div v-if="getTotalErrorsCount() > 0" class="anomalies">
          <div v-if="errorsDetailed.no_accountable.length > 0" class="anom-error">
            <strong>❌ Pas A:</strong> {{ errorsDetailed.no_accountable.join(', ') }}
          </div>
          <div v-if="errorsDetailed.no_responsible.length > 0" class="anom-error">
            <strong>❌ Pas R:</strong> {{ errorsDetailed.no_responsible.join(', ') }}
          </div>
          <div v-if="errorsDetailed.r_and_a_together.length > 0" class="anom-warn">
            <strong>⚠️ R+A:</strong> {{ errorsDetailed.r_and_a_together.slice(0, 2).join(', ') }}<span v-if="errorsDetailed.r_and_a_together.length > 2">...</span>
          </div>
        </div>
        <div v-else class="text-success small">
          <i class="ti ti-check"></i> Matrice conforme ✓
        </div>
      </b-card>
    </template>

    <!-- MESSAGE VIDE -->
    <div v-else-if="activeSession && !selectedProcessId" class="empty-state">
      <i class="ti ti-inbox"></i>
      <h6>Sélectionner un processus</h6>
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
  raciRoles: Array,
})

const selectedProcessId = ref("")
const activities = ref([])
const functions = ref([])
const matrix = ref({})
const saving = ref(false)

const activeSession = ref(props.activeSession || null)
const raciRoles = ref(props.raciRoles || [])

const errorsDetailed = ref({
  no_accountable: [],
  no_responsible: [],
  r_and_a_together: []
})

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

const addRaciCode = (activityId, functionId, code) => {
  if (!matrix.value[activityId]) matrix.value[activityId] = {}
  const codes = matrix.value[activityId][functionId] || []
  if (!codes.includes(code)) codes.push(code)
  matrix.value[activityId][functionId] = codes
  updateErrors()
}

const removeRaciCode = (activityId, functionId, code) => {
  if (!matrix.value[activityId]) return
  const codes = matrix.value[activityId][functionId] || []
  const idx = codes.indexOf(code)
  if (idx >= 0) codes.splice(idx, 1)
  matrix.value[activityId][functionId] = codes
  updateErrors()
}

const getCellClass = (activityId, functionId) => {
  const codes = getMatrixCodes(activityId, functionId)
  return {
    'cell-raci': true,
    'cell-warn': codes.includes('R') && codes.includes('A'),
    'cell-filled': codes.length > 0
  }
}

const getRoleColor = (code) => {
  const colors = { R: '#3B82F6', A: '#EF4444', C: '#F59E0B', I: '#10B981' }
  return colors[code] || '#6B7280'
}

const updateErrors = () => {
  errorsDetailed.value = { no_accountable: [], no_responsible: [], r_and_a_together: [] }
  activities.value.forEach(activity => {
    let rCount = 0, aCount = 0
    functions.value.forEach(func => {
      const codes = getMatrixCodes(activity.id, func.id)
      rCount += codes.filter(c => c === 'R').length
      aCount += codes.filter(c => c === 'A').length
      if (codes.includes('R') && codes.includes('A')) {
        errorsDetailed.value.r_and_a_together.push(`${activity.code}→${func.name}`)
      }
    })
    if (aCount === 0) errorsDetailed.value.no_accountable.push(activity.code)
    if (rCount === 0) errorsDetailed.value.no_responsible.push(activity.code)
  })
}

const getActivityErrors = (activityId) => {
  const activity = activities.value.find(a => a.id === activityId)
  if (!activity) return []
  const errors = []
  if (errorsDetailed.value.no_accountable.includes(activity.code)) errors.push("A")
  if (errorsDetailed.value.no_responsible.includes(activity.code)) errors.push("R")
  return errors
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

const getCriticalErrorsCount = () => errorsDetailed.value.no_accountable.length + errorsDetailed.value.no_responsible.length
const getWarningsCount = () => errorsDetailed.value.r_and_a_together.length
const getTotalErrorsCount = () => getCriticalErrorsCount() + getWarningsCount()

const loadMatrix = async () => {
  if (!selectedProcessId.value || !activeSession.value) return
  try {
    saving.value = true
    const res = await axios.get(route('process.core.raci.matrix.load'), {
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
    updateErrors()
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
            raci_codes: codes.join(',')
          })
        }
      })
    })
    const res = await axios.post(route('process.core.raci.matrix.save'), {
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
    const res = await axios.get(route('process.core.raci.export-excel'), {
      params: { session_id: activeSession.value.id, process_id: selectedProcessId.value },
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `RACI_${activeSession.value.name}.xlsx`)
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
* { box-sizing: border-box; }

.header-raci {
  background: linear-gradient(135deg, #2E75B6 0%, #1F4E78 100%);
  padding: 1.2rem;
  border-radius: 6px;
  color: white;
  margin: -1rem -1rem 0 -1rem;
  padding: 1.2rem;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}

.header-subtitle {
  font-size: 0.8rem;
  opacity: 0.85;
  margin: 0.3rem 0 0 0;
}

.user-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.4rem 0.8rem;
  border-radius: 4px;
  font-size: 0.8rem;
}

.session-card {
  border-top: 3px solid #2E75B6;
  padding: 1rem;
  border-radius: 4px;
}

.session-title {
  font-size: 1rem;
  font-weight: 600;
}

.info-bar {
  font-size: 0.9rem;
  border-radius: 4px;
}

.completion-badge {
  padding: 0.4rem 1rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.9rem;
}

.table-wrapper {
  overflow-x: auto;
  max-height: 600px;
  overflow-y: auto;
}

.matrice-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.matrice-table thead {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #1F4E78;
  color: white;
}

.matrice-table th {
  padding: 0.6rem;
  font-weight: 600;
  text-align: center;
  border: 1px solid #D1D5DB;
}

.col-act {
  width: 160px;
  text-align: left;
  background: #EBF2FA;
}

.col-func {
  width: 120px;
  min-width: 120px;
  font-size: 0.75rem;
}

.matrice-table tbody tr {
  border-bottom: 1px solid #E5E7EB;
}

.matrice-table tbody tr.alt {
  background: #F9FAFB;
}

.matrice-table tbody tr:hover {
  background: #F3F4F6;
}

.cell-act {
  background: #EBF2FA;
  padding: 0.6rem;
  font-weight: 600;
  position: relative;
}

.err-badge {
  display: inline-block;
  background: #FEE2E2;
  color: #DC2626;
  padding: 0.2rem 0.4rem;
  border-radius: 2px;
  font-size: 0.65rem;
  margin-top: 0.2rem;
}

.cell-raci {
  padding: 0.5rem;
  text-align: center;
  vertical-align: middle;
}

.cell-filled {
  background: #F0F9FF;
}

.cell-warn {
  background: #FFFBEB;
  border: 1px solid #FBBF24;
}

.cell-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
  min-height: 40px;
  justify-content: center;
}

.raci-codes {
  display: flex;
  gap: 0.3rem;
  justify-content: center;
  flex-wrap: wrap;
  width: 100%;
}

.code {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 3px;
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.code:hover {
  transform: scale(1.1);
  opacity: 0.85;
}

.btn-add {
  background: white;
  border: 1px solid #D1D5DB;
  color: #6B7280;
  width: 22px;
  height: 22px;
  border-radius: 2px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-add:hover {
  background: #F3F4F6;
  border-color: #2E75B6;
  color: #2E75B6;
}

.dropdown-menu {
  min-width: 180px;
}

.dropdown-item {
  padding: 0.4rem 0.8rem;
  font-size: 0.8rem;
}

.badge {
  font-size: 0.7rem;
  padding: 0.3rem 0.5rem;
}

.stats-card {
  border-radius: 4px;
  padding: 1rem;
}

.stat-box {
  padding: 0.8rem;
  border-radius: 4px;
  text-align: center;
  border-left: 3px solid;
}

.stat-completion {
  background: #EBF2FA;
  border-left-color: #3B82F6;
}

.stat-errors {
  background: #FEE2E2;
  border-left-color: #EF4444;
}

.stat-warnings {
  background: #FEF3C7;
  border-left-color: #F59E0B;
}

.stat-ok {
  background: #D1FAE5;
  border-left-color: #10B981;
}

.stat-ko {
  background: #FEE2E2;
  border-left-color: #EF4444;
}

.stat-value {
  font-size: 1.4rem;
  font-weight: 700;
}

.stat-box small {
  display: block;
  font-size: 0.7rem;
  margin-top: 0.2rem;
}

.anomalies {
  font-size: 0.8rem;
}

.anom-error {
  background: #FEE2E2;
  border-left: 2px solid #EF4444;
  padding: 0.5rem;
  margin-bottom: 0.4rem;
  color: #991B1B;
}

.anom-warn {
  background: #FEF3C7;
  border-left: 2px solid #F59E0B;
  padding: 0.5rem;
  color: #92400E;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-state i {
  font-size: 3rem;
  color: #D1D5DB;
  display: block;
  margin-bottom: 0.5rem;
}

.empty-state h6 {
  color: #6B7280;
}
</style>