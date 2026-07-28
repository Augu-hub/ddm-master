<template>
  <VerticalLayout>
    <div class="tracking-page">

      <!-- ═══ TOPBAR ═══ -->
      <div class="t-topbar">
        <div class="t-topbar-left">
          <i class="ti ti-chart-line"></i>
          <div>
            <h1>Suivi des plans d'action</h1>
            <p>Avancement, échéances et alertes — données en temps réel du registre</p>
          </div>
        </div>
        <div class="t-topbar-right">
          <button class="btn-export" @click="exportToPdf">
            <i class="ti ti-download"></i> PDF
          </button>
          <button class="btn-export" @click="exportToExcel">
            <i class="ti ti-file-spreadsheet"></i> Excel
          </button>
          <button class="btn-icon" @click="reload" title="Actualiser">
            <i class="ti ti-refresh"></i>
          </button>
        </div>
      </div>

      <!-- ═══ KPIs GLOBAUX ═══ -->
      <div class="kpis-container">
        <div class="kpi-card">
          <div class="kpi-icon" style="background:#e0e7ff; color:#4338ca">
            <i class="ti ti-list-check"></i>
          </div>
          <div class="kpi-content">
            <div class="kpi-value">{{ stats.total }}</div>
            <div class="kpi-label">Actions Total</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon" style="background:#dbeafe; color:#1d4ed8">
            <i class="ti ti-player-play"></i>
          </div>
          <div class="kpi-content">
            <div class="kpi-value">{{ stats.in_progress }}</div>
            <div class="kpi-label">En cours</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon" style="background:#dcfce7; color:#16a34a">
            <i class="ti ti-check-circle"></i>
          </div>
          <div class="kpi-content">
            <div class="kpi-value">{{ stats.completed }}</div>
            <div class="kpi-label">Terminées</div>
            <div class="kpi-percent">{{ completionRate }}%</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon" style="background:#fee2e2; color:#dc2626">
            <i class="ti ti-alert-circle"></i>
          </div>
          <div class="kpi-content">
            <div class="kpi-value">{{ stats.overdue }}</div>
            <div class="kpi-label">En retard</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon" style="background:#fef3c7; color:#d97706">
            <i class="ti ti-alert-triangle"></i>
          </div>
          <div class="kpi-content">
            <div class="kpi-value">{{ stats.critical }}</div>
            <div class="kpi-label">Critiques</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-icon" style="background:#f3e8ff; color:#7c3aed">
            <i class="ti ti-trending-up"></i>
          </div>
          <div class="kpi-content">
            <div class="kpi-value">{{ avgProgress }}%</div>
            <div class="kpi-label">Avancement moy</div>
          </div>
        </div>
      </div>

      <!-- ═══ GRAPHIQUES ═══ -->
      <div class="charts-grid">
        <!-- Progression par statut -->
        <div class="chart-card">
          <div class="cc-title">
            <i class="ti ti-chart-pie"></i> Distribution par statut
          </div>
          <div class="status-chart">
            <div v-for="s in statuses" :key="s.value" class="status-bar">
              <div class="sb-label">{{ s.label }}</div>
              <div class="sb-track">
                <div class="sb-fill" :style="{ width: statusPercent(s.value)+'%', background: s.color }"></div>
              </div>
              <div class="sb-count">{{ countByStatus(s.value) }}</div>
            </div>
          </div>
        </div>

        <!-- Progression globale -->
        <div class="chart-card">
          <div class="cc-title">
            <i class="ti ti-trending-up"></i> Progression globale
          </div>
          <div class="progress-tracker">
            <div class="pt-item">
              <span class="pt-label">Avancement moyen</span>
              <div class="pt-bar">
                <div class="pt-fill" :style="{ width: avgProgress+'%' }"></div>
              </div>
              <span class="pt-pct">{{ avgProgress }}%</span>
            </div>
            <div class="pt-detail">
              <div v-for="p in priorityStats" :key="p.priority" class="ptd-row">
                <span :class="'prio prio-'+p.priority">{{ prioLabel(p.priority) }}</span>
                <span class="ptd-count">{{ p.count }} actions</span>
                <div class="ptd-bar">
                  <div class="ptd-fill" :style="{ width: p.avg_progress+'%' }"></div>
                </div>
                <span class="ptd-pct">{{ p.avg_progress }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions par priorité -->
        <div class="chart-card">
          <div class="cc-title">
            <i class="ti ti-flag"></i> Actions par priorité
          </div>
          <div class="priority-chart">
            <div v-for="p in priorities" :key="p.value" class="prio-item">
              <span :class="['prio-badge','prio-'+p.value]">{{ p.label }}</span>
              <div class="prio-bar">
                <div class="prio-fill" :style="{ width: priorityPercent(p.value)+'%', background: getPriorityColor(p.value) }"></div>
              </div>
              <span class="prio-num">{{ countByPriority(p.value) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ TABLEAU DÉTAILLÉ ═══ -->
      <div class="tracking-section">
        <div class="ts-header">
          <h3><i class="ti ti-list"></i> Actions en cours de suivi</h3>
          <div class="ts-filters">
            <select v-model="filterStatus" class="fsel">
              <option value="">Tous les statuts</option>
              <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
            <select v-model="filterPriority" class="fsel">
              <option value="">Toutes priorités</option>
              <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
            <div class="search-box">
              <i class="ti ti-search"></i>
              <input v-model="searchQ" placeholder="Rechercher…" class="fsearch" />
            </div>
          </div>
        </div>

        <table class="tracking-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Risque</th>
              <th>Action</th>
              <th>Responsable</th>
              <th>Priorité</th>
              <th>Statut</th>
              <th>Début</th>
              <th>Échéance</th>
              <th>Avancement</th>
              <th>Retard</th>
              <th>Tâches</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="plan in filteredPlans" :key="plan.id" :class="['trow', 'trow-'+plan.status, isOverdue(plan)?'trow-late':'']">
              <td><span class="cell-code">{{ plan.code }}</span></td>
              <td>
                <div class="cell-risk">
                  <span class="cr-code">{{ plan.code_risk }}</span>
                  <span class="cr-lib">{{ truncate(plan.risk_libelle, 40) }}</span>
                </div>
              </td>
              <td>
                <div class="cell-action">
                  <div class="ca-title">{{ truncate(plan.title, 50) }}</div>
                  <div v-if="plan.description" class="ca-desc">{{ truncate(plan.description, 45) }}</div>
                </div>
              </td>
              <td class="cell-user">{{ plan.assigned_to_name || '—' }}</td>
              <td><span :class="'prio prio-'+plan.priority">{{ prioLabel(plan.priority) }}</span></td>
              <td><span :class="'stat stat-'+plan.status">{{ statLabel(plan.status) }}</span></td>
              <td class="cell-date">{{ fmtDate(plan.start_date) || '—' }}</td>
              <td :class="['cell-date', isOverdue(plan)?'cell-date-late':'']">
                {{ fmtDate(plan.target_date) || '—' }}
              </td>
              <td>
                <div class="cell-progress">
                  <div class="prog-bar">
                    <div class="prog-fill" :style="{ width: (plan.progress||0)+'%' }"></div>
                  </div>
                  <span class="prog-pct">{{ plan.progress || 0 }}%</span>
                </div>
              </td>
              <td v-if="isOverdue(plan)" class="cell-delay">
                <span class="delay-badge"><i class="ti ti-clock-x"></i> {{ daysBehind(plan.target_date) }}j</span>
              </td>
              <td v-else class="cell-delay">—</td>
              <td class="cell-tasks">
                <span class="task-count" :title="getTasksSummary(plan.id)">
                  <i class="ti ti-checkbox"></i> {{ tasksStats[plan.id]?.completed || 0 }}/{{ tasksStats[plan.id]?.total || 0 }}
                </span>
              </td>
              <td class="cell-actions">
                <button class="aib" @click="openTrackingDetail(plan)" title="Détail">
                  <i class="ti ti-eye"></i>
                </button>
                <button class="aib" @click="editProgress(plan)" title="Mise à jour">
                  <i class="ti ti-pencil"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!filteredPlans.length" class="empty-message">
          <i class="ti ti-inbox"></i>
          <p>Aucune action pour ces filtres</p>
        </div>
      </div>

      <!-- ═══ ACTIONS CRITIQUES ═══ -->
      <div v-if="criticalActions.length" class="critical-section">
        <div class="cs-header">
          <i class="ti ti-alert-triangle"></i>
          <h3>Actions critiques ou en retard</h3>
        </div>
        <div class="critical-list">
          <div v-for="plan in criticalActions" :key="plan.id" class="critical-item">
            <div class="ci-icon" :style="{ background: plan.priority==='critical'?'#fee2e2':'#fef3c7', color: plan.priority==='critical'?'#dc2626':'#d97706' }">
              <i :class="['ti', plan.priority==='critical'?'ti-alert-circle':'ti-alert-triangle']"></i>
            </div>
            <div class="ci-content">
              <div class="ci-title">
                <span class="ci-code">{{ plan.code }}</span>
                {{ plan.title }}
              </div>
              <div class="ci-meta">
                <span>Responsable: <strong>{{ plan.assigned_to_name || '—' }}</strong></span>
                <span>Échéance: <strong :class="isOverdue(plan)?'text-red':''">{{ fmtDate(plan.target_date) }}</strong></span>
                <span>Avancement: <strong>{{ plan.progress || 0 }}%</strong></span>
              </div>
              <div v-if="isOverdue(plan)" class="ci-warning">
                <i class="ti ti-clock-x"></i> En retard de {{ daysBehind(plan.target_date) }} jours
              </div>
            </div>
            <button class="btn-action" @click="openTrackingDetail(plan)">
              <i class="ti ti-arrow-right"></i> Voir
            </button>
          </div>
        </div>
      </div>

      <!-- ═══ MODAL SUIVI ═══ -->
      <Teleport to="body">
        <Transition name="mf">
          <div v-if="trackingModal.open" class="modal-overlay" @click.self="trackingModal.open=false">
            <div class="modal-box modal-tracking" @click.stop>
              <div class="modal-hdr">
                <span class="mh-code">{{ trackingModal.plan?.code }}</span>
                <span class="mh-title">{{ trackingModal.plan?.title }}</span>
                <button class="modal-x" @click="trackingModal.open=false"><i class="ti ti-x"></i></button>
              </div>

              <div class="modal-body tracking-detail">
                <!-- Banneau risque -->
                <div class="td-risk-banner">
                  <i class="ti ti-shield-bolt"></i>
                  <div>
                    <div class="drb-code">{{ trackingModal.plan?.code_risk }}</div>
                    <div class="drb-lib">{{ trackingModal.plan?.risk_libelle }}</div>
                  </div>
                </div>

                <!-- Grille info + progression -->
                <div class="td-grid">
                  <div class="td-section">
                    <div class="tds-title">Informations</div>
                    <div class="td-row"><span>Responsable</span><span>{{ trackingModal.plan?.assigned_to_name || '—' }}</span></div>
                    <div class="td-row"><span>Entité</span><span>{{ trackingModal.plan?.entity_name || '—' }}</span></div>
                    <div class="td-row"><span>Priorité</span><span :class="'prio prio-'+trackingModal.plan?.priority">{{ prioLabel(trackingModal.plan?.priority) }}</span></div>
                    <div class="td-row"><span>Statut</span><span :class="'stat stat-'+trackingModal.plan?.status">{{ statLabel(trackingModal.plan?.status) }}</span></div>
                  </div>

                  <div class="td-section">
                    <div class="tds-title">Échéances</div>
                    <div class="td-row"><span>Début</span><span>{{ fmtDate(trackingModal.plan?.start_date) || '—' }}</span></div>
                    <div class="td-row"><span>Cible</span><span :class="isOverdue(trackingModal.plan)?'text-red':''">{{ fmtDate(trackingModal.plan?.target_date) }}</span></div>
                    <div class="td-row"><span>Terminé</span><span>{{ fmtDate(trackingModal.plan?.completion_date) || '—' }}</span></div>
                    <div v-if="isOverdue(trackingModal.plan)" class="td-warning">
                      <i class="ti ti-alert-triangle"></i> En retard de {{ daysBehind(trackingModal.plan?.target_date) }} jours
                    </div>
                  </div>

                  <div class="td-section">
                    <div class="tds-title">Progression</div>
                    <div class="progress-large">
                      <div class="pl-track">
                        <div class="pl-fill" :style="{ width: (trackingModal.plan?.progress||0)+'%' }"></div>
                      </div>
                      <span class="pl-pct">{{ trackingModal.plan?.progress || 0 }}%</span>
                    </div>
                    <div class="pt-note">
                      <i class="ti ti-info-circle"></i>
                      Calculée automatiquement d'après les tâches terminées ({{ trackingTasks.filter(t=>t.status==='completed').length }}/{{ trackingTasks.length }}). Cochez les tâches ci-dessous pour la faire évoluer.
                    </div>
                  </div>
                </div>

                <!-- Description -->
                <div v-if="trackingModal.plan?.description" class="td-text-block">
                  <div class="tds-title">Description</div>
                  <p>{{ trackingModal.plan?.description }}</p>
                </div>

                <!-- Plan d'action -->
                <div v-if="trackingModal.plan?.action_plan" class="td-text-block">
                  <div class="tds-title">Plan d'action</div>
                  <p class="prewrap">{{ trackingModal.plan?.action_plan }}</p>
                </div>

                <!-- Tâches -->
                <div class="td-tasks-section">
                  <div class="tds-hdr">
                    <span><i class="ti ti-checkbox"></i> Tâches ({{ trackingTasks.length }})</span>
                    <button class="btn-add-sm" @click="addTask"><i class="ti ti-plus"></i> Tâche</button>
                  </div>
                  <div v-if="trackingTasks.length" class="td-tasks-list">
                    <div v-for="t in trackingTasks" :key="t.id" :class="['tt-item','tt-'+t.status]">
                      <div class="tti-check">
                        <input type="checkbox" :checked="t.status==='completed'" @change="toggleTask(t)" />
                      </div>
                      <div class="tti-content">
                        <div class="ttc-title">{{ t.title }}</div>
                        <div class="ttc-meta">{{ t.assigned_to_name || '—' }} · {{ fmtDate(t.target_date) || '—' }}</div>
                      </div>
                      <span :class="'stat stat-'+t.status" style="font-size:9px">{{ statLabel(t.status) }}</span>
                      <div class="tti-btns">
                        <button class="aib" @click="editTask(t)"><i class="ti ti-pencil"></i></button>
                        <button class="aib aib-del" @click="deleteTask(t)"><i class="ti ti-trash"></i></button>
                      </div>
                    </div>
                  </div>
                  <div v-else class="td-empty">Aucune tâche définie</div>
                </div>

                <!-- Commentaires -->
                <div class="td-comments-section">
                  <div class="tds-hdr"><span><i class="ti ti-message"></i> Commentaires</span></div>
                  <div class="tc-input">
                    <textarea v-model="newComment" rows="2" placeholder="Ajouter un commentaire…" class="finp"></textarea>
                    <button class="btn-send" @click="addComment" :disabled="!newComment.trim()">
                      <i class="ti ti-send"></i> Envoyer
                    </button>
                  </div>
                  <div v-for="c in trackingComments" :key="c.id" class="tc-item">
                    <div class="tci-meta">
                      <strong>{{ c.user_name || 'Anonyme' }}</strong>
                      <span class="tci-date">{{ fmtDateTime(c.created_at) }}</span>
                    </div>
                    <p>{{ c.comment }}</p>
                  </div>
                  <div v-if="!trackingComments.length" class="td-empty">Aucun commentaire</div>
                </div>

                <!-- Historique -->
                <div class="td-history-section">
                  <div class="tds-title"><i class="ti ti-clock-history"></i> Historique</div>
                  <div v-for="h in trackingHistory" :key="h.id" class="th-item">
                    <span class="th-action">{{ h.action }}</span>
                    <span class="th-desc">{{ h.description }}</span>
                    <span class="th-user">{{ h.user_name || 'Système' }}</span>
                    <span class="th-date">{{ fmtDateTime(h.created_at) }}</span>
                  </div>
                  <div v-if="!trackingHistory.length" class="td-empty">Aucun historique</div>
                </div>
              </div>

              <div class="modal-footer">
                <button class="btn-close" @click="trackingModal.open=false"><i class="ti ti-x"></i> Fermer</button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- ═══ FLASH ═══ -->
      <Transition name="fl">
        <div v-if="flashMsg" :class="['flash', flashOk?'flash-ok':'flash-err']">
          <i :class="flashOk?'ti ti-check-circle':'ti ti-alert-circle'"></i>
          {{ flashMsg }}
        </div>
      </Transition>

    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  actionPlans:  { type: Array, default: () => [] },
  allRisks:     { type: Array, default: () => [] },
  stats:        { type: Object, default: () => ({}) },
  priorities:   { type: Array, default: () => [] },
  statuses:     { type: Array, default: () => [] },
})

// State
const filterStatus = ref('')
const filterPriority = ref('')
const searchQ = ref('')
const trackingModal = ref({ open: false, plan: null })
const trackingTasks = ref([])
const trackingComments = ref([])
const trackingHistory = ref([])
const newComment = ref('')
const progressValue = ref(0)
// Comptes de tâches réels (fournis par le contrôleur : tasks_total / tasks_completed)
const tasksStats = computed(() => {
  const m = {}
  for (const p of (props.actionPlans || [])) {
    m[p.id] = { total: p.tasks_total || 0, completed: p.tasks_completed || 0 }
  }
  return m
})
const flashMsg = ref('')
const flashOk = ref(true)
let flashTimer = null

// Helpers
const truncate = (s, n = 50) => s && s.length > n ? s.slice(0, n) + '…' : s || ''

const fmtDate = (d) =>
  d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : null

const fmtDateTime = (d) =>
  d ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : null

const isOverdue = (p) =>
  p && p.status !== 'completed' && p.status !== 'cancelled' && p.target_date &&
  new Date(p.target_date) < new Date()

const daysBehind = (date) => {
  if (!date) return 0
  const today = new Date()
  const target = new Date(date)
  const diff = Math.floor((today - target) / (1000 * 60 * 60 * 24))
  return Math.max(0, diff)
}

const prioLabel = (v) => props.priorities.find(p => p.value === v)?.label || v
const statLabel = (v) => props.statuses.find(s => s.value === v)?.label || v
const countByStatus = (v) => props.actionPlans.filter(p => p.status === v).length
const countByPriority = (v) => props.actionPlans.filter(p => p.priority === v).length
const statusPercent = (v) => props.actionPlans.length ? Math.round((countByStatus(v) / props.actionPlans.length) * 100) : 0
const priorityPercent = (v) => props.actionPlans.length ? Math.round((countByPriority(v) / props.actionPlans.length) * 100) : 0

const getPriorityColor = (p) => {
  const colors = { critical: '#ef4444', high: '#f59e0b', medium: '#3b82f6', low: '#10b981' }
  return colors[p] || '#6b7280'
}

const showFlash = (msg, ok = true) => {
  if (flashTimer) clearTimeout(flashTimer)
  flashMsg.value = msg
  flashOk.value = ok
  flashTimer = setTimeout(() => { flashMsg.value = '' }, 4000)
}

// Computed
const filteredPlans = computed(() => {
  let list = props.actionPlans || []
  if (filterStatus.value) list = list.filter(p => p.status === filterStatus.value)
  if (filterPriority.value) list = list.filter(p => p.priority === filterPriority.value)
  if (searchQ.value) {
    const q = searchQ.value.toLowerCase()
    list = list.filter(p =>
      p.title?.toLowerCase().includes(q) ||
      p.code?.toLowerCase().includes(q) ||
      p.risk_libelle?.toLowerCase().includes(q)
    )
  }
  return list.sort((a, b) => new Date(b.target_date) - new Date(a.target_date))
})

const completionRate = computed(() =>
  props.actionPlans.length ? Math.round((props.stats.completed || 0) / props.actionPlans.length * 100) : 0
)

const avgProgress = computed(() => {
  const total = props.actionPlans.reduce((s, p) => s + (p.progress || 0), 0)
  return props.actionPlans.length ? Math.round(total / props.actionPlans.length) : 0
})

const priorityStats = computed(() => {
  const stats = {}
  props.priorities.forEach(p => {
    const plans = props.actionPlans.filter(ap => ap.priority === p.value)
    if (plans.length) {
      const avg = Math.round(plans.reduce((s, ap) => s + (ap.progress || 0), 0) / plans.length)
      stats[p.value] = { priority: p.value, count: plans.length, avg_progress: avg }
    }
  })
  return Object.values(stats)
})

const criticalActions = computed(() => {
  return props.actionPlans.filter(p =>
    p.priority === 'critical' || (isOverdue(p) && p.status !== 'completed')
  ).slice(0, 5)
})

// Modal
const openTrackingDetail = async (plan) => {
  trackingModal.value = { open: true, plan }
  progressValue.value = plan.progress || 0
  trackingTasks.value = []
  trackingComments.value = []
  trackingHistory.value = []

  try {
    const [tasks, comments, history] = await Promise.all([
      fetch(`/m/risk.core/action-plan/${plan.id}/tasks`).then(r => r.json()),
      fetch(`/m/risk.core/action-plan/${plan.id}/comments`).then(r => r.json()),
      fetch(`/m/risk.core/action-plan/${plan.id}/history`).then(r => r.json()),
    ])
    trackingTasks.value = tasks.tasks || []
    trackingComments.value = comments.comments || []
    trackingHistory.value = history.history || []
  } catch (e) {
    console.error('Erreur lors du chargement des détails', e)
  }
}

const updateProgress = async () => {
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
    const r = await fetch(`/m/risk.core/action-plan/${trackingModal.value.plan.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ progress: progressValue.value }),
    })
    const d = await r.json()
    if (r.ok && d.success) {
      trackingModal.value.plan.progress = progressValue.value
      showFlash('Progression mise à jour')
    }
  } catch (e) {
    showFlash('Erreur', false)
  }
}

const addComment = async () => {
  if (!newComment.value.trim()) return
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
    const r = await fetch('/m/risk.core/action-plan/comment', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({
        plan_id: trackingModal.value.plan.id,
        comment: newComment.value.trim(),
        is_internal: false,
      }),
    })
    const d = await r.json()
    if (r.ok && d.success) {
      newComment.value = ''
      showFlash('Commentaire ajouté')
      const c = await fetch(`/m/risk.core/action-plan/${trackingModal.value.plan.id}/comments`).then(r => r.json())
      trackingComments.value = c.comments || []
    }
  } catch { showFlash('Erreur', false) }
}

const toggleTask = async (t) => {
  const newStatus = t.status === 'completed' ? 'pending' : 'completed'
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
    const r = await fetch(`/m/risk.core/action-plan/task/${t.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ status: newStatus }),
    })
    if (r.ok) {
      t.status = newStatus
      showFlash('Tâche mise à jour')
    }
  } catch { showFlash('Erreur', false) }
}

const editTask = (t) => {
  // À implémenter: ouvrir modal d'édition tâche
  console.log('Edit task:', t)
}

const deleteTask = async (t) => {
  if (!confirm('Supprimer cette tâche?')) return
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
    const r = await fetch(`/m/risk.core/action-plan/task/${t.id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf },
    })
    if (r.ok) {
      trackingTasks.value = trackingTasks.value.filter(x => x.id !== t.id)
      showFlash('Tâche supprimée')
    }
  } catch { showFlash('Erreur', false) }
}

const addTask = () => {
  // À implémenter: ouvrir modal création tâche
  console.log('Add task for plan:', trackingModal.value.plan.id)
}

const editProgress = (plan) => {
  progressValue.value = plan.progress || 0
  openTrackingDetail(plan)
}

const getTasksSummary = (planId) => {
  const stats = tasksStats.value[planId] || { completed: 0, total: 0 }
  return `${stats.completed} tâche(s) complétée(s) sur ${stats.total}`
}

const exportToPdf = () => {
  showFlash('Export PDF en cours…')
  // À implémenter avec jsPDF
}

const exportToExcel = () => {
  showFlash('Export Excel en cours…')
  // À implémenter avec SheetJS
}

const reload = () => router.reload({ preserveState: true })
</script>

<style scoped>
/* ═══ PAGE ═══ */
.tracking-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 60px);
  background: #f0f4f8;
  overflow: hidden;
  font-size: 12px;
}

/* ═══ TOPBAR ═══ */
.t-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 20px;
  background: #0f172a;
  color: #e2e8f0;
  flex-shrink: 0;
  gap: 20px;
}

.t-topbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.t-topbar-left i {
  font-size: 24px;
  color: #60a5fa;
}

.t-topbar-left h1 {
  font-size: 15px;
  font-weight: 700;
  margin: 0;
  color: #f1f5f9;
}

.t-topbar-left p {
  font-size: 10px;
  color: #64748b;
  margin: 0;
}

.t-topbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-export {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 6px 13px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 7px;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

.btn-export:hover { background: #1d4ed8; }

.btn-icon {
  width: 32px;
  height: 32px;
  border: 1px solid rgba(255,255,255,.15);
  background: rgba(255,255,255,.07);
  color: #94a3b8;
  border-radius: 7px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ═══ KPIs ═══ */
.kpis-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  padding: 12px 18px;
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
  overflow-x: auto;
}

.kpi-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: #f8fafc;
  border-radius: 9px;
  border: 1px solid #e2e8f0;
}

.kpi-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.kpi-content {
  flex: 1;
  min-width: 0;
}

.kpi-value {
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}

.kpi-label {
  font-size: 9px;
  color: #64748b;
  margin-top: 2px;
}

.kpi-percent {
  font-size: 10px;
  color: #2563eb;
  font-weight: 600;
  margin-top: 2px;
}

/* ═══ CHARTS ═══ */
.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 12px;
  padding: 12px 18px;
  flex-shrink: 0;
  overflow-x: auto;
}

.chart-card {
  background: #fff;
  border-radius: 9px;
  border: 1px solid #e2e8f0;
  padding: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}

.cc-title {
  font-size: 11px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.status-chart {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.status-bar {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
}

.sb-label { width: 60px; font-weight: 600; color: #475569; }
.sb-track { flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; }
.sb-fill { height: 100%; border-radius: 4px; }
.sb-count { min-width: 30px; text-align: right; color: #64748b; font-weight: 600; }

.progress-tracker {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.pt-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.pt-label { font-size: 10px; font-weight: 600; color: #475569; }
.pt-bar { height: 12px; background: #e2e8f0; border-radius: 6px; }
.pt-fill { height: 100%; background: linear-gradient(90deg, #3b82f6, #2563eb); border-radius: 6px; }
.pt-pct { font-size: 9px; font-weight: 700; color: #1d4ed8; }

.pt-detail {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.ptd-row {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 9px;
}

.prio { display: inline-block; padding: 2px 6px; border-radius: 5px; font-weight: 700; }
.prio-critical { background: #fee2e2; color: #991b1b; }
.prio-high { background: #fef3c7; color: #92400e; }
.prio-medium { background: #dbeafe; color: #1e40af; }
.prio-low { background: #dcfce7; color: #166534; }

.ptd-count { min-width: 70px; }
.ptd-bar { width: 40px; height: 6px; background: #e2e8f0; border-radius: 3px; }
.ptd-fill { height: 100%; background: #3b82f6; border-radius: 3px; }
.ptd-pct { min-width: 30px; text-align: right; font-weight: 600; color: #64748b; }

.priority-chart {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.prio-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 10px;
}

.prio-badge { padding: 3px 8px; border-radius: 6px; font-weight: 600; min-width: 70px; }
.prio-bar { flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; }
.prio-num { min-width: 30px; text-align: right; font-weight: 700; color: #475569; }

/* ═══ SECTION SUIVI ═══ */
.tracking-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 12px;
  margin: 12px 18px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}

.ts-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  flex-shrink: 0;
  flex-wrap: wrap;
  gap: 10px;
}

.ts-header h3 {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 5px;
}

.ts-filters {
  display: flex;
  align-items: center;
  gap: 7px;
  flex-wrap: wrap;
}

.fsel {
  padding: 4px 8px;
  border: 1px solid #e2e8f0;
  border-radius: 5px;
  font-size: 10px;
  background: #fff;
}

.search-box { position: relative; }
.search-box i { position: absolute; left: 7px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #94a3b8; }
.fsearch { padding: 4px 7px 4px 24px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 10px; width: 160px; background: #f8fafc; }

.tracking-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
  flex: 1;
  overflow: auto;
}

.tracking-table th {
  text-align: left;
  padding: 6px 8px;
  background: #f1f5f9;
  border-bottom: 1.5px solid #e2e8f0;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: .04em;
  font-size: 8px;
  white-space: nowrap;
  position: sticky;
  top: 0;
}

.trow {
  border-bottom: 1px solid #f1f5f9;
}

.trow:hover { background: #fafbff; }
.trow-late { background: #fef2f2 !important; }
.trow-completed { opacity: .7; }

.cell-code { font-family: monospace; font-size: 9px; font-weight: 700; color: #4338ca; background: #ede9fe; padding: 1px 5px; border-radius: 3px; }

.cell-risk {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.cr-code { font-size: 9px; font-weight: 700; color: #4338ca; }
.cr-lib { font-size: 9px; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.cell-action {
  min-width: 0;
}

.ca-title { font-weight: 600; color: #0f172a; }
.ca-desc { font-size: 9px; color: #64748b; }

.cell-user { white-space: nowrap; }
.cell-date { white-space: nowrap; }
.cell-date-late { color: #dc2626; font-weight: 700; }

.cell-progress {
  display: flex;
  align-items: center;
  gap: 4px;
}

.prog-bar { width: 50px; height: 6px; background: #e2e8f0; border-radius: 3px; }
.prog-fill { height: 100%; background: #22c55e; border-radius: 3px; }
.prog-pct { font-weight: 600; color: #22c55e; }

.cell-delay {
  white-space: nowrap;
}

.delay-badge {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  background: #fee2e2;
  color: #dc2626;
  padding: 2px 6px;
  border-radius: 5px;
  font-weight: 600;
  font-size: 9px;
}

.cell-tasks {
  white-space: nowrap;
}

.task-count {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-weight: 600;
  color: #2563eb;
}

.cell-actions {
  display: flex;
  gap: 3px;
}

.aib {
  width: 24px;
  height: 24px;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  color: #64748b;
}

.aib:hover { background: #f1f5f9; border-color: #94a3b8; }
.aib-del:hover { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }

.empty-message {
  text-align: center;
  padding: 30px;
  color: #94a3b8;
}

.empty-message i { font-size: 32px; display: block; opacity: .2; margin-bottom: 8px; }

/* ═══ SECTION CRITIQUE ═══ */
.critical-section {
  background: #fff;
  border-radius: 9px;
  margin: 0 18px 12px;
  border: 1px solid #fee2e2;
  overflow: hidden;
}

.cs-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: #fef2f2;
  border-bottom: 1px solid #fee2e2;
  color: #991b1b;
}

.cs-header i { font-size: 16px; }
.cs-header h3 { font-size: 12px; font-weight: 700; margin: 0; }

.critical-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.critical-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 14px;
  border-bottom: 1px solid #fee2e2;
}

.critical-item:last-child { border-bottom: none; }

.ci-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.ci-content {
  flex: 1;
  min-width: 0;
}

.ci-title {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 4px;
}

.ci-code {
  font-family: monospace;
  font-size: 9px;
  font-weight: 700;
  background: #ede9fe;
  color: #4338ca;
  padding: 1px 5px;
  border-radius: 3px;
  margin-right: 4px;
}

.ci-meta {
  display: flex;
  gap: 12px;
  font-size: 10px;
  color: #64748b;
  margin-bottom: 4px;
}

.ci-warning {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  color: #dc2626;
  font-weight: 600;
}

.btn-action {
  padding: 4px 12px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}

/* ═══ MODAL ═══ */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15,23,42,.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 20px;
}

.modal-box {
  background: #fff;
  border-radius: 16px;
  max-width: 920px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(0,0,0,.3);
}

.modal-tracking {
  max-width: 1000px;
}

.modal-hdr {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 16px;
  background: #0f172a;
  color: #e2e8f0;
  font-weight: 700;
  flex-shrink: 0;
  flex-wrap: wrap;
}

.mh-code {
  font-family: monospace;
  font-size: 10px;
  font-weight: 800;
  background: #2563eb;
  color: #fff;
  padding: 2px 8px;
  border-radius: 4px;
}

.mh-title {
  font-size: 13px;
  flex: 1;
  color: #f1f5f9;
}

.modal-x {
  width: 28px;
  height: 28px;
  border: 1px solid rgba(255,255,255,.1);
  background: rgba(255,255,255,.07);
  color: #94a3b8;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-left: auto;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 10px 16px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  flex-shrink: 0;
}

.btn-close {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 7px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 7px;
  background: #fff;
  color: #475569;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
}

.btn-send {
  padding: 6px 12px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-weight: 600;
}

.btn-send:disabled { opacity: .4; cursor: not-allowed; }

.btn-add-sm {
  display: flex;
  align-items: center;
  gap: 3px;
  padding: 4px 9px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

/* ═══ DETAIL MODAL ═══ */
.tracking-detail {
  gap: 12px !important;
}

.td-risk-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 9px;
  color: #d97706;
}

.td-risk-banner i { font-size: 18px; }

.drb-code {
  font-family: monospace;
  font-size: 10px;
  font-weight: 800;
  color: #4338ca;
  background: #ede9fe;
  padding: 1px 5px;
  border-radius: 3px;
}

.drb-lib {
  font-size: 11px;
  font-weight: 600;
  color: #0f172a;
  margin-top: 2px;
}

.td-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
}

.td-section {
  background: #f8fafc;
  border-radius: 7px;
  padding: 9px 11px;
  border: 1px solid #e2e8f0;
}

.tds-title {
  font-size: 9px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .04em;
  margin-bottom: 6px;
  padding-bottom: 4px;
  border-bottom: 1px solid #e2e8f0;
}

.td-row {
  display: flex;
  justify-content: space-between;
  padding: 3px 0;
  font-size: 10px;
  border-bottom: 1px solid #f1f5f9;
}

.td-row:last-child { border-bottom: none; }
.td-row span:first-child { color: #64748b; }
.td-row span:last-child { font-weight: 500; color: #0f172a; text-align: right; }

.td-warning {
  display: flex;
  align-items: center;
  gap: 4px;
  background: #fee2e2;
  color: #991b1b;
  padding: 4px 7px;
  border-radius: 5px;
  font-size: 9px;
  font-weight: 600;
  margin-top: 6px;
}

.progress-large {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
}

.pl-track {
  flex: 1;
  height: 12px;
  background: #e2e8f0;
  border-radius: 6px;
}

.pl-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #2563eb);
  border-radius: 6px;
}

.pl-pct {
  min-width: 40px;
  text-align: right;
  font-weight: 700;
  color: #1d4ed8;
}

.pt-slider-container {
  display: flex;
  align-items: center;
}

.pt-slider {
  width: 100%;
  cursor: pointer;
}

.pt-note {
  display: flex;
  align-items: flex-start;
  gap: 5px;
  font-size: 9.5px;
  color: #64748b;
  line-height: 1.5;
  background: #f1f5f9;
  border-radius: 6px;
  padding: 6px 8px;
}
.pt-note i { color: #3b82f6; margin-top: 1px; flex-shrink: 0; }

.td-text-block {
  background: #f8fafc;
  border-radius: 7px;
  padding: 9px 12px;
  border: 1px solid #e2e8f0;
}

.td-text-block p {
  font-size: 11px;
  color: #334155;
  line-height: 1.7;
  margin: 5px 0 0;
}

.prewrap { white-space: pre-wrap; }

.td-tasks-section,
.td-comments-section,
.td-history-section {
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  overflow: hidden;
}

.tds-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 12px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 10px;
  font-weight: 700;
  color: #334155;
}

.td-tasks-list {
  display: flex;
  flex-direction: column;
}

.tt-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 12px;
  border-bottom: 1px solid #f1f5f9;
}

.tt-item:last-child { border-bottom: none; }
.tt-completed .tti-content { opacity: .6; }

.tti-check { display: flex; align-items: center; }
.tti-check input { cursor: pointer; }

.tti-content {
  flex: 1;
  min-width: 0;
}

.ttc-title {
  font-size: 11px;
  font-weight: 600;
  color: #0f172a;
}

.ttc-meta {
  font-size: 9px;
  color: #64748b;
  margin-top: 2px;
}

.tti-btns {
  display: flex;
  gap: 3px;
}

.stat {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 7px;
}

.stat-pending { background: #f1f5f9; color: #475569; }
.stat-in_progress { background: #dbeafe; color: #1d4ed8; }
.stat-completed { background: #dcfce7; color: #16a34a; }
.stat-cancelled { background: #fee2e2; color: #dc2626; }

.td-empty {
  padding: 10px 12px;
  text-align: center;
  font-size: 10px;
  color: #94a3b8;
}

.tc-input {
  display: flex;
  gap: 6px;
  padding: 9px 12px;
  border-bottom: 1px solid #e2e8f0;
}

.tc-input .finp {
  flex: 1;
  padding: 6px 9px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 11px;
  font-family: inherit;
}

.tc-item {
  padding: 7px 12px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 10px;
}

.tci-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 3px;
}

.tci-meta strong { color: #0f172a; }
.tci-date { font-size: 9px; color: #94a3b8; }

.tc-item p {
  margin: 0;
  color: #334155;
  line-height: 1.6;
}

.th-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 5px 12px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 9px;
  color: #64748b;
}

.th-action {
  font-size: 8px;
  font-weight: 700;
  background: #e0e7ff;
  color: #4338ca;
  padding: 1px 6px;
  border-radius: 5px;
  text-transform: uppercase;
  flex-shrink: 0;
}

.th-desc { flex: 1; }
.th-user { font-weight: 600; color: #0f172a; }
.th-date { flex-shrink: 0; }

/* ═══ FLASH ═══ */
.flash {
  position: fixed;
  bottom: 18px;
  right: 18px;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
  box-shadow: 0 4px 16px rgba(0,0,0,.12);
}

.flash-ok { background: #f0fdf4; border: 1px solid #86efac; color: #15803d; }
.flash-err { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; }

.text-red { color: #dc2626; }

/* Transitions */
.mf-enter-active { transition: opacity .18s, transform .18s; }
.mf-leave-active { transition: opacity .14s, transform .14s; }
.mf-enter-from, .mf-leave-to { opacity: 0; transform: scale(.97); }

.fl-enter-active, .fl-leave-active { transition: opacity .2s, transform .2s; }
.fl-enter-from, .fl-leave-to { opacity: 0; transform: translateX(20px); }
</style>