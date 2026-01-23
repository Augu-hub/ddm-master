<template>
  <VerticalLayout>
    <Head title="AMDEC — Analyse Modes Défaillance" />

    <!-- HEADER -->
    <b-row class="mb-3">
      <b-col>
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="fw-bold text-danger mb-0">
            <i class="ti ti-alert-triangle me-2"></i> AMDEC PRO
          </h3>
          <div>
            <b-button size="sm" variant="outline-success" @click="exportExcel" v-if="selectedProcessId">
              <i class="ti ti-download me-1"></i> Export Excel
            </b-button>
          </div>
        </div>
      </b-col>
    </b-row>

    <!-- SESSION + PROCESSUS + STATS -->
    <b-row class="mb-3">
      <b-col lg="12">
        <b-card no-body class="border-0 shadow-sm">
          <b-card-body class="p-3">
            <b-row class="align-items-center">
              <b-col lg="3">
                <small class="text-muted d-block mb-1">📅 Session Active</small>
                <div v-if="activeSession" class="d-flex align-items-center gap-2">
                  <b-badge :style="{ backgroundColor: activeSession.color }" class="px-3 py-2">
                    {{ activeSession.name }}
                  </b-badge>
                  <small class="text-success fw-bold">✓ ACTIVE</small>
                </div>
                <small v-else class="text-warning">Aucune session active</small>
              </b-col>
              <b-col lg="4">
                <small class="text-muted d-block mb-1">📊 Processus</small>
                <b-form-select
                  v-model.number="selectedProcessId"
                  :options="processOptions"
                  size="sm"
                  @change="onProcessChanged"
                />
              </b-col>
              <b-col lg="5">
                <small class="text-muted d-block mb-1">📈 Statistiques</small>
                <div class="d-flex gap-3">
                  <span><strong class="text-primary">{{ statistics?.total_records || 0 }}</strong> enreg.</span>
                  <span><strong class="text-success">{{ statistics?.average_improvement || 0 }}%</strong> amélio.</span>
                  <span><strong class="text-danger">{{ statistics?.high_risk_before || 0 }}</strong> risques</span>
                </div>
              </b-col>
            </b-row>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ACTIVITÉS BADGES -->
    <b-row v-if="activities.length > 0" class="mb-3">
      <b-col lg="12">
        <b-card no-body class="border-0 shadow-sm bg-light">
          <b-card-body class="p-2">
            <small class="text-muted fw-bold">📋 Activités du processus ({{ activities.length }})</small>
            <div class="d-flex gap-2 flex-wrap mt-2">
              <b-badge v-for="act in activities" :key="act.id" bg="info" class="px-2 py-1">
                <strong>{{ act.code }}</strong> — {{ act.name }}
              </b-badge>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- TABS PHASES -->
    <b-row v-if="selectedProcessId && !loading">
      <b-col lg="12">
        <b-tabs pills class="mb-3" v-model="activePhaseTab">
          <!-- PHASE 1 -->
          <b-tab title="📋 Phase 1: Identification">
            <Phase1Component
              :activities="activities"
              :records="allRecords"
              :referentials="referentials"
              :standards="standards"
              @save-record="saveRecord"
              @delete-record="deleteRecord"
            />
          </b-tab>

          <!-- PHASE 2 -->
          <b-tab title="🛡️ Phase 2: Plan d'action">
            <Phase2Component
              :activities="activities"
              :records="allRecords"
              :referentials="referentials"
              @save-record="saveRecord"
              @delete-record="deleteRecord"
            />
          </b-tab>

          <!-- PHASE 3 -->
          <b-tab title="✅ Phase 3: Actions & Résultats">
            <Phase3Component
              :activities="activities"
              :records="allRecords"
              :referentials="referentials"
              :standards="standards"
              @save-record="saveRecord"
              @delete-record="deleteRecord"
            />
          </b-tab>
        </b-tabs>
      </b-col>
    </b-row>

    <!-- LOADING -->
    <b-row v-if="loading" class="mt-5">
      <b-col class="text-center">
        <b-spinner class="mb-3"></b-spinner>
        <p class="text-muted">Chargement des données...</p>
      </b-col>
    </b-row>

    <!-- EMPTY STATE -->
    <b-row v-if="!loading && !selectedProcessId" class="mt-5">
      <b-col class="text-center">
        <i class="ti ti-inbox" style="font-size: 48px; opacity: 0.3;"></i>
        <p class="text-muted mt-3">Sélectionnez un processus pour commencer</p>
      </b-col>
    </b-row>

  </VerticalLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import Phase1Component from './components/Phase1.vue'
import Phase2Component from './components/Phase2.vue'
import Phase3Component from './components/Phase3.vue'
import axios from 'axios'
import { route } from 'ziggy-js'

const props = defineProps({
  user: Object,
  link: Object,
  processes: Array,
  activeSession: Object,
  referentials: Object,
  standards: Array,
})

// STATE — ✅ SIMPLE ET CLAIR
const loading = ref(false)
const selectedProcessId = ref(null)
const activePhaseTab = ref(0)
const activities = ref([])
const allRecords = ref([])
const statistics = ref(null)

// COMPUTED
const processOptions = computed(() => [
  { value: null, text: '-- Sélectionnez un processus --' },
  ...(props.processes || []).map(p => ({
    value: p.id,
    text: `${p.code} — ${p.name}`
  }))
])

// ═══════════════════════════════════════════════════════════════════════════
// 🔧 METHODS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * 🔄 Quand le processus change → charger les données
 */
const onProcessChanged = async () => {
  if (!selectedProcessId.value || !props.activeSession) {
    activities.value = []
    allRecords.value = []
    statistics.value = null
    return
  }

  await loadData()
}

/**
 * 📊 Charger les données (PHASE 1+2+3)
 * ✅ UPDATE une SEULE FOIS — pas de modifications progressives
 */
const loadData = async () => {
  if (!selectedProcessId.value || !props.activeSession) return

  loading.value = true

  try {
    const res = await axios.get(route('process.core.amdec.load'), {
      params: {
        session_id: props.activeSession.id,
        process_id: selectedProcessId.value
      }
    })

    if (res.data.success) {
      // ✅ UPDATE une seule fois
      activities.value = res.data.activities || []
      
      allRecords.value = [
        ...(res.data.records?.PHASE1 || []),
        ...(res.data.records?.PHASE2 || []),
        ...(res.data.records?.PHASE3 || [])
      ]
      
      statistics.value = res.data.statistics

      console.log('✅ Données chargées:', {
        activités: activities.value.length,
        records_total: allRecords.value.length,
        phase1: res.data.records?.PHASE1?.length || 0,
        phase2: res.data.records?.PHASE2?.length || 0,
        phase3: res.data.records?.PHASE3?.length || 0
      })
    } else {
      console.warn('⚠️ Erreur charge données:', res.data.message)
    }
  } catch (error) {
    console.error('❌ Erreur loadData:', error.message)
    alert('❌ Erreur chargement: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

/**
 * 💾 DISPATCHER INTELLIGENT
 * Envoi la phase + les données au backend
 * Le backend sait quoi faire avec saveRecord() qui dispatch vers savePhase1/2/3
 */
const saveRecord = async (data) => {
  try {
    // ✅ Construire le payload avec session_id et process_id obligatoires pour PHASE1
    const payload = {
      ...data,
      phase: data.phase || 'PHASE1'
    }

    // PHASE 1 UNIQUEMENT: ajouter session_id et process_id
    if (data.phase === 'PHASE1') {
      payload.session_id = props.activeSession.id
      payload.process_id = selectedProcessId.value
    }

    console.log('📋 saveRecord:', {
      phase: payload.phase,
      amdec_record_id: payload.amdec_record_id,
      failure_mode: payload.failure_mode || 'N/A'
    })

    const res = await axios.post(route('process.core.amdec.save'), payload)

    if (res.data.success) {
      console.log('✅ ' + res.data.message)
      
      // ✅ UPDATE local du record ou ajouter nouveau
      const existingIndex = allRecords.value.findIndex(r => r.id === res.data.record?.id)
      if (existingIndex >= 0) {
        allRecords.value[existingIndex] = res.data.record
      } else if (res.data.record) {
        allRecords.value.push(res.data.record)
      }

      // ✅ Recharger les stats
      if (res.data.statistics) {
        statistics.value = res.data.statistics
      }
    }
  } catch (error) {
    console.error('❌ Erreur saveRecord:', error.message)
    const errorMsg = error.response?.data?.error || error.message
    alert('❌ Erreur: ' + errorMsg)
  }
}

/**
 * 🗑️ Supprimer un enregistrement
 */
const deleteRecord = async (recordId) => {
  if (!confirm('Êtes-vous sûr de supprimer cet enregistrement ?')) return

  try {
    const res = await axios.delete(route('process.core.amdec.delete', recordId))

    if (res.data.success) {
      // ✅ Supprimer du local
      allRecords.value = allRecords.value.filter(r => r.id !== recordId)
      console.log('✅ Enregistrement supprimé')
    }
  } catch (error) {
    console.error('❌ Erreur deleteRecord:', error.message)
    alert('❌ Erreur: ' + error.message)
  }
}

/**
 * 📥 Exporter en Excel
 */
const exportExcel = () => {
  if (!selectedProcessId.value || !props.activeSession) return

  window.location.href = route('process.core.amdec.export-excel', {
    session_id: props.activeSession.id,
    process_id: selectedProcessId.value
  })
}

// ═══════════════════════════════════════════════════════════════════════════
// 📍 INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

// Auto-select le premier processus si disponible
if (props.processes && props.processes.length > 0) {
  selectedProcessId.value = props.processes[0].id
  
  // Auto-load si session active
  if (props.activeSession) {
    loadData()
  }
}
</script>

<style scoped>
h3 {
  font-size: 1.25rem;
}

:deep(.nav-pills .nav-link) {
  color: #666;
  border-radius: 0.5rem;
  margin-right: 0.5rem;
  padding: 0.5rem 1rem;
  font-weight: 500;
}

:deep(.nav-pills .nav-link.active) {
  background-color: #007bff;
  color: white;
}

.gap-2 {
  gap: 0.5rem;
}

.gap-3 {
  gap: 1rem;
}

.align-items-center {
  align-items: center;
}

.d-flex {
  display: flex;
}

.justify-content-between {
  justify-content: space-between;
}

.flex-wrap {
  flex-wrap: wrap;
}

.mt-2 {
  margin-top: 0.5rem;
}

.mt-3 {
  margin-top: 1rem;
}

.mt-5 {
  margin-top: 3rem;
}

.mb-0 {
  margin-bottom: 0;
}

.mb-1 {
  margin-bottom: 0.25rem;
}

.mb-3 {
  margin-bottom: 1rem;
}

.p-2 {
  padding: 0.5rem;
}

.p-3 {
  padding: 1rem;
}

.px-2 {
  padding-left: 0.5rem;
  padding-right: 0.5rem;
}

.px-3 {
  padding-left: 1rem;
  padding-right: 1rem;
}

.py-1 {
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
}

.py-2 {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}
</style>