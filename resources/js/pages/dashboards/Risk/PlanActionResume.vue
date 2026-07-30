<template>
  <div class="plan-resume-page">

    <!-- ═══ TOPBAR ═══ -->
    <div class="pr-topbar">
      <div class="pr-left">
        <button class="btn-back" @click="goBack" title="Retour">
          <i class="ti ti-arrow-left"></i>
        </button>
        <div class="pr-title-box">
          <div class="pr-code">{{ plan?.code }}</div>
          <div class="pr-title">{{ plan?.title }}</div>
        </div>
      </div>
      <div class="pr-right">
        <button class="btn-icon" @click="refreshData" title="Rafraîchir">
          <i class="ti ti-refresh"></i>
        </button>
        <button class="btn-secondary" @click="editMode = true" v-if="!editMode">
          <i class="ti ti-pencil"></i> Modifier
        </button>
        <button class="btn-danger" @click="deletePlan" title="Supprimer">
          <i class="ti ti-trash"></i>
        </button>
      </div>
    </div>

    <!-- ═══ CONTENU PRINCIPAL ═══ -->
    <div class="pr-body">

      <!-- GRILLE PRINCIPALE: Gauche (plan) + Droite (infos rapides) -->
      <div class="pr-grid">

        <!-- ═══ GAUCHE: PLAN D'ACTION ═══ -->
        <div class="pr-main">

          <!-- Banneau risque -->
          <div class="risk-card">
            <div class="rc-left">
              <div class="rc-icon">
                <i class="ti ti-shield-bolt"></i>
              </div>
              <div class="rc-info">
                <div class="rc-code">{{ risk?.code_risk }}</div>
                <div class="rc-title">{{ risk?.libelle }}</div>
                <div class="rc-zone" :style="{ color: riskZoneColor }">
                  <i class="ti ti-alert-circle"></i> {{ riskZone }}
                </div>
              </div>
            </div>
            <button class="btn-view-risk" @click="viewRisk">
              <i class="ti ti-arrow-up-right"></i> Voir risque
            </button>
          </div>

          <!-- Recommandations du risque -->
          <div class="recommendations-box" v-if="recommendations.length > 0">
            <div class="rb-header">
              <i class="ti ti-bulb"></i>
              <span>Recommandations pour ce risque</span>
            </div>
            <div class="rb-list">
              <div v-for="rec in recommendations" :key="rec.id" class="rb-item">
                <div class="rbi-icon" :style="{ background: rec.color }">
                  <i :class="rec.icon"></i>
                </div>
                <div class="rbi-text">
                  <div class="rbi-label">{{ rec.label }}</div>
                  <div class="rbi-desc">{{ rec.description }}</div>
                </div>
                <button class="btn-apply" @click="applyRecommendation(rec)" title="Appliquer">
                  <i class="ti ti-check"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Formulaire de modification -->
          <div v-if="editMode" class="edit-form">
            <div class="form-section">
              <h3>Éditer le plan</h3>
              
              <div class="form-grid">
                <div class="form-group">
                  <label>Titre *</label>
                  <input v-model="formData.title" type="text" class="form-input" />
                </div>
                <div class="form-group">
                  <label>Priorité</label>
                  <select v-model="formData.priority" class="form-input">
                    <option value="critical">🔴 Critique</option>
                    <option value="high">🟠 Haute</option>
                    <option value="medium">🟡 Moyenne</option>
                    <option value="low">🟢 Basse</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label>Description</label>
                <textarea v-model="formData.description" rows="2" class="form-input"></textarea>
              </div>

              <div class="form-group">
                <label>Plan d'action détaillé *</label>
                <textarea v-model="formData.action_plan" rows="4" class="form-input"></textarea>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>Date cible</label>
                  <input v-model="formData.target_date" type="date" class="form-input" />
                </div>
                <div class="form-group">
                  <label>Statut</label>
                  <select v-model="formData.status" class="form-input">
                    <option value="pending">En attente</option>
                    <option value="in_progress">En cours</option>
                    <option value="review">En révision</option>
                    <option value="completed">Complété</option>
                    <option value="cancelled">Annulé</option>
                    <option value="blocked">Bloqué</option>
                  </select>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>Progression (%)</label>
                  <div class="progress-input">
                    <input v-model.number="formData.progress" type="range" min="0" max="100" class="form-range" />
                    <span class="progress-value">{{ formData.progress }}%</span>
                  </div>
                </div>
              </div>

              <div class="form-actions">
                <button class="btn-cancel" @click="editMode = false">Annuler</button>
                <button class="btn-primary" @click="savePlan" :disabled="saving">
                  <i :class="saving ? 'ti ti-loader-2 spin' : 'ti ti-check'"></i>
                  {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Affichage normal du plan -->
          <div v-else class="plan-view">
            <div class="plan-header">
              <div class="ph-status">
                <div :class="['status-badge', `status-${plan?.status}`]">
                  {{ statusLabel(plan?.status) }}
                </div>
                <div :class="['priority-badge', `priority-${plan?.priority}`]">
                  {{ priorityLabel(plan?.priority) }}
                </div>
              </div>
              <div class="ph-dates">
                <div v-if="plan?.target_date" class="date-item">
                  <i class="ti ti-calendar"></i>
                  <span>Cible: {{ fmtDate(plan?.target_date) }}</span>
                </div>
                <div v-if="plan?.start_date" class="date-item">
                  <i class="ti ti-calendar-event"></i>
                  <span>Démarrage: {{ fmtDate(plan?.start_date) }}</span>
                </div>
              </div>
            </div>

            <div class="plan-body">
              <div v-if="plan?.description" class="description">
                <h4>Description</h4>
                <p>{{ plan?.description }}</p>
              </div>

              <div class="action-plan">
                <h4>Plan d'action détaillé</h4>
                <div class="ap-content">{{ plan?.action_plan }}</div>
              </div>

              <div v-if="plan?.notes" class="notes">
                <h4>Notes</h4>
                <p>{{ plan?.notes }}</p>
              </div>
            </div>

            <div class="plan-progress">
              <div class="pp-header">
                <span>Progression globale</span>
                <span class="pp-value">{{ plan?.progress }}%</span>
              </div>
              <div class="pp-bar">
                <div class="pp-fill" :style="{ width: plan?.progress + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- ═══ ONGLETS: TÂCHES, COMMENTAIRES, HISTORIQUE ═══ -->
          <div class="tabs-container">
            <div class="tabs-nav">
              <button 
                v-for="tab in tabs"
                :key="tab.id"
                :class="['tab-btn', activeTab === tab.id ? 'active' : '']"
                @click="activeTab = tab.id"
              >
                <i :class="tab.icon"></i>
                {{ tab.label }}
                <span v-if="tab.badge" class="tab-badge">{{ tab.badge }}</span>
              </button>
            </div>

            <div class="tabs-content">

              <!-- TÂCHES -->
              <div v-if="activeTab === 'tasks'" class="tab-panel">
                <div class="panel-toolbar">
                  <h4>Tâches ({{ tasks.length }})</h4>
                  <button class="btn-add" @click="openTaskModal">
                    <i class="ti ti-plus"></i> Ajouter tâche
                  </button>
                </div>

                <div class="tasks-list">
                  <div v-if="tasks.length === 0" class="empty-state">
                    <i class="ti ti-inbox"></i>
                    <p>Aucune tâche créée</p>
                  </div>

                  <div v-for="task in tasks" :key="task.id" class="task-row">
                    <div class="tr-checkbox">
                      <input 
                        type="checkbox" 
                        :checked="task.status === 'completed'"
                        @change="toggleTask(task)"
                        :disabled="loading"
                      />
                    </div>
                    <div class="tr-content">
                      <div class="tr-title" :class="{ completed: task.status === 'completed' }">
                        {{ task.title }}
                      </div>
                      <div class="tr-meta">
                        <span v-if="task.assigned_to_name" class="meta-item">
                          <i class="ti ti-user"></i> {{ task.assigned_to_name }}
                        </span>
                        <span v-if="task.target_date" class="meta-item">
                          <i class="ti ti-calendar"></i> {{ fmtDate(task.target_date) }}
                        </span>
                        <span :class="['status-badge-sm', `status-${task.status}`]">
                          {{ statusLabel(task.status) }}
                        </span>
                      </div>
                      <div v-if="task.description" class="tr-desc">{{ task.description }}</div>
                    </div>
                    <div class="tr-actions">
                      <button class="btn-icon-sm" @click="editTask(task)" title="Modifier">
                        <i class="ti ti-pencil"></i>
                      </button>
                      <button class="btn-icon-sm btn-danger" @click="deleteTask(task)" title="Supprimer">
                        <i class="ti ti-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- COMMENTAIRES -->
              <div v-if="activeTab === 'comments'" class="tab-panel">
                <div class="panel-toolbar">
                  <h4>Commentaires ({{ comments.length }})</h4>
                </div>

                <div class="comments-area">
                  <div class="comment-input">
                    <textarea 
                      v-model="newComment"
                      placeholder="Ajouter un commentaire..."
                      rows="3"
                      class="form-input"
                    ></textarea>
                    <button 
                      class="btn-primary" 
                      @click="addComment"
                      :disabled="!newComment.trim() || commenting"
                    >
                      <i :class="commenting ? 'ti ti-loader-2 spin' : 'ti ti-send'"></i>
                      {{ commenting ? 'Envoi...' : 'Envoyer' }}
                    </button>
                  </div>

                  <div class="comments-list">
                    <div v-if="comments.length === 0" class="empty-state">
                      <i class="ti ti-message"></i>
                      <p>Aucun commentaire</p>
                    </div>

                    <div v-for="comment in comments" :key="comment.id" class="comment-box">
                      <div class="cb-header">
                        <strong>{{ comment.user_name }}</strong>
                        <span class="cb-time">{{ fmtDateTime(comment.created_at) }}</span>
                      </div>
                      <div class="cb-body">{{ comment.comment }}</div>
                      <div v-if="comment.is_internal" class="cb-badge">Interne</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- HISTORIQUE -->
              <div v-if="activeTab === 'history'" class="tab-panel">
                <div class="panel-toolbar">
                  <h4>Historique ({{ history.length }})</h4>
                </div>

                <div class="history-list">
                  <div v-if="history.length === 0" class="empty-state">
                    <i class="ti ti-history"></i>
                    <p>Aucun historique</p>
                  </div>

                  <div v-for="entry in history" :key="entry.id" class="history-entry">
                    <div class="he-icon" :style="{ background: getActionColor(entry.action) }">
                      <i :class="getActionIcon(entry.action)"></i>
                    </div>
                    <div class="he-content">
                      <div class="he-action">{{ formatAction(entry.action) }}</div>
                      <div class="he-desc">{{ entry.description }}</div>
                      <div class="he-meta">
                        <span>{{ entry.user_name }}</span>
                        <span>{{ fmtDateTime(entry.created_at) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- ═══ DROITE: INFOS RAPIDES ═══ -->
        <aside class="pr-sidebar">

          <!-- Bloc responsable -->
          <div class="info-card">
            <div class="ic-header">
              <i class="ti ti-user"></i>
              <span>Responsable</span>
            </div>
            <div class="ic-body">
              <div class="ic-value">{{ plan?.assigned_to_name || '—' }}</div>
              <div class="ic-email" v-if="plan?.assigned_to_email">{{ plan?.assigned_to_email }}</div>
            </div>
          </div>

          <!-- Bloc dates -->
          <div class="info-card">
            <div class="ic-header">
              <i class="ti ti-calendar-event"></i>
              <span>Planification</span>
            </div>
            <div class="ic-body">
              <div class="ic-row">
                <span class="ic-label">Démarrage:</span>
                <span class="ic-value">{{ plan?.start_date ? fmtDate(plan.start_date) : 'Non défini' }}</span>
              </div>
              <div class="ic-row">
                <span class="ic-label">Cible:</span>
                <span class="ic-value">{{ plan?.target_date ? fmtDate(plan.target_date) : 'Non défini' }}</span>
              </div>
              <div class="ic-row">
                <span class="ic-label">Complété:</span>
                <span class="ic-value">{{ plan?.completion_date ? fmtDate(plan.completion_date) : 'Non complété' }}</span>
              </div>
              <div v-if="daysUntilDeadline !== null" class="ic-deadline" :class="{ overdue: daysUntilDeadline < 0 }">
                <i class="ti ti-alert-triangle"></i>
                {{ daysUntilDeadline < 0 ? `${Math.abs(daysUntilDeadline)} jours de retard` : `${daysUntilDeadline} jours restants` }}
              </div>
            </div>
          </div>

          <!-- Bloc budget -->
          <div class="info-card" v-if="plan?.cost_estimate || plan?.actual_cost">
            <div class="ic-header">
              <i class="ti ti-coin"></i>
              <span>Budget (XOF)</span>
            </div>
            <div class="ic-body">
              <div class="ic-row">
                <span class="ic-label">Estimé:</span>
                <span class="ic-value">{{ fmtNumber(plan?.cost_estimate) }}</span>
              </div>
              <div class="ic-row">
                <span class="ic-label">Réel:</span>
                <span class="ic-value">{{ fmtNumber(plan?.actual_cost) }}</span>
              </div>
              <div v-if="plan?.cost_estimate && plan?.actual_cost" class="ic-gap">
                <span class="gap-label">Écart:</span>
                <span :class="['gap-value', costGap > 0 ? 'danger' : 'success']">
                  {{ fmtNumber(costGap) }} ({{ costGapPercent }}%)
                </span>
              </div>
            </div>
          </div>

          <!-- Bloc KPIs -->
          <div class="info-card">
            <div class="ic-header">
              <i class="ti ti-chart-bar"></i>
              <span>Indicateurs</span>
            </div>
            <div class="ic-body">
              <div class="ic-kpi">
                <div class="kpi-label">Tâches</div>
                <div class="kpi-value">
                  {{ tasksCompleted }} / {{ tasks.length }}
                </div>
              </div>
              <div class="ic-kpi">
                <div class="kpi-label">Commentaires</div>
                <div class="kpi-value">{{ comments.length }}</div>
              </div>
              <div class="ic-kpi">
                <div class="kpi-label">Progrès</div>
                <div class="kpi-value">{{ plan?.progress }}%</div>
              </div>
            </div>
          </div>

        </aside>

      </div>

    </div>

    <!-- ═══ MODAL TÂCHE ═══ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="taskModal.open" class="modal-overlay" @click.self="taskModal.open = false">
          <div class="modal-box">
            <div class="modal-header">
              <h3>{{ taskModal.id ? 'Modifier' : 'Nouvelle' }} tâche</h3>
              <button class="modal-close" @click="taskModal.open = false">
                <i class="ti ti-x"></i>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Titre *</label>
                <input v-model="taskForm.title" type="text" class="form-input" />
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea v-model="taskForm.description" rows="2" class="form-input"></textarea>
              </div>
              <div class="form-group">
                <label>Responsable</label>
                <select v-model="taskForm.assigned_to" class="form-input">
                  <option value="">— Aucun —</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Date cible</label>
                <input v-model="taskForm.target_date" type="date" class="form-input" />
              </div>
              <div class="form-group">
                <label>Statut</label>
                <select v-model="taskForm.status" class="form-input">
                  <option value="pending">En attente</option>
                  <option value="in_progress">En cours</option>
                  <option value="completed">Complétée</option>
                  <option value="cancelled">Annulée</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn-cancel" @click="taskModal.open = false">Annuler</button>
              <button class="btn-primary" @click="saveTask" :disabled="!taskForm.title">Enregistrer</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══ FLASH MESSAGES ═══ -->
    <Transition name="flash">
      <div v-if="flash.msg" :class="['flash', flash.ok ? 'flash-ok' : 'flash-error']">
        <i :class="flash.ok ? 'ti ti-check-circle' : 'ti ti-alert-circle'"></i>
        {{ flash.msg }}
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Props
defineProps({
  planId: Number,
  plan: Object,
  risk: Object,
  tasks: Array,
  comments: Array,
  history: Array,
  users: Array,
  riskZoneColor: String,
  riskZone: String,
})

// State
const activeTab = ref('tasks')
const editMode = ref(false)
const loading = ref(false)
const saving = ref(false)
const commenting = ref(false)

const formData = ref({
  title: '',
  description: '',
  action_plan: '',
  priority: 'medium',
  status: 'pending',
  target_date: '',
  progress: 0,
})

const taskForm = ref({
  id: null,
  title: '',
  description: '',
  assigned_to: '',
  target_date: '',
  status: 'pending',
})

const taskModal = ref({ open: false, id: null })
const newComment = ref('')
const flash = ref({ msg: '', ok: true })

let flashTimer = null

// Tabs
const tabs = computed(() => [
  { id: 'tasks', label: 'Tâches', icon: 'ti ti-checkbox', badge: tasks?.length || 0 },
  { id: 'comments', label: 'Commentaires', icon: 'ti ti-message', badge: comments?.length || 0 },
  { id: 'history', label: 'Historique', icon: 'ti ti-clock', badge: history?.length || 0 },
])

// Computed
const tasksCompleted = computed(() => tasks?.filter(t => t.status === 'completed').length || 0)
const daysUntilDeadline = computed(() => {
  if (!plan?.target_date) return null
  const today = new Date()
  const target = new Date(plan.target_date)
  return Math.ceil((target - today) / (1000 * 60 * 60 * 24))
})

const costGap = computed(() => {
  if (!plan?.cost_estimate || !plan?.actual_cost) return 0
  return Math.round(plan.actual_cost - plan.cost_estimate)
})

const costGapPercent = computed(() => {
  if (!plan?.cost_estimate || !plan?.actual_cost) return 0
  return Math.round((costGap.value / plan.cost_estimate) * 100)
})

const recommendations = computed(() => [
  {
    id: 1,
    label: 'Réduire le risque',
    description: 'Mettre en place des mesures de réduction du risque',
    icon: 'ti ti-shield-down',
    color: '#3b82f6',
  },
  {
    id: 2,
    label: 'Atténuer le risque',
    description: 'Appliquer des mesures d\'atténuation progressives',
    icon: 'ti ti-trending-down',
    color: '#f59e0b',
  },
  {
    id: 3,
    label: 'Transférer le risque',
    description: 'Transférer à un tiers ou assurer le risque',
    icon: 'ti ti-exchange',
    color: '#8b5cf6',
  },
])

// Methods
const showFlash = (msg, ok = true) => {
  if (flashTimer) clearTimeout(flashTimer)
  flash.value = { msg, ok }
  flashTimer = setTimeout(() => { flash.value.msg = '' }, 4000)
}

const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
const fmtDateTime = (d) => d ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'
const fmtNumber = (n) => n ? new Intl.NumberFormat('fr-FR').format(n) : '—'

const statusLabel = (status) => {
  const labels = {
    pending: '⏳ En attente',
    in_progress: '🔄 En cours',
    review: '👀 En révision',
    completed: '✓ Complété',
    cancelled: '✕ Annulé',
    blocked: '⛔ Bloqué',
  }
  return labels[status] || status
}

const priorityLabel = (priority) => {
  const labels = {
    critical: '🔴 Critique',
    high: '🟠 Haute',
    medium: '🟡 Moyenne',
    low: '🟢 Basse',
  }
  return labels[priority] || priority
}

const getActionIcon = (action) => {
  const icons = {
    created: 'ti ti-plus-circle',
    updated: 'ti ti-edit-circle',
    deleted: 'ti ti-trash-circle',
    task_added: 'ti ti-list-check',
    comment_added: 'ti ti-message-plus',
  }
  return icons[action] || 'ti ti-info-circle'
}

const getActionColor = (action) => {
  const colors = {
    created: '#10b981',
    updated: '#3b82f6',
    deleted: '#ef4444',
    task_added: '#f59e0b',
    comment_added: '#8b5cf6',
  }
  return colors[action] || '#6b7280'
}

const formatAction = (action) => {
  const formats = {
    created: 'Créé',
    updated: 'Mis à jour',
    deleted: 'Supprimé',
    task_added: 'Tâche ajoutée',
    comment_added: 'Commentaire ajouté',
  }
  return formats[action] || action
}

const goBack = () => router.back()

const viewRisk = () => {
  router.push(`/risk/register/${risk?.id}`)
}

const refreshData = async () => {
  loading.value = true
  try {
    const res = await fetch(`/m/risk.core/action-plan/${planId}`)
    if (res.ok) {
      showFlash('Données rafraîchies')
    }
  } catch (e) {
    showFlash('Erreur de rafraîchissement', false)
  } finally {
    loading.value = false
  }
}

const editTask = (task) => {
  Object.assign(taskForm.value, task)
  taskModal.value = { open: true, id: task.id }
}

const openTaskModal = () => {
  taskForm.value = { id: null, title: '', description: '', assigned_to: '', target_date: '', status: 'pending' }
  taskModal.value = { open: true, id: null }
}

const saveTask = async () => {
  const url = taskForm.value.id ? `/m/risk.core/action-plan/task/${taskForm.value.id}` : `/m/risk.core/action-plan/${planId}/task`
  const method = taskForm.value.id ? 'PUT' : 'POST'

  try {
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: JSON.stringify(taskForm.value),
    })
    if (res.ok) {
      showFlash('Tâche enregistrée')
      taskModal.value.open = false
      await refreshData()
    }
  } catch (e) {
    showFlash('Erreur', false)
  }
}

const deleteTask = async (task) => {
  if (!confirm('Supprimer cette tâche?')) return
  try {
    const res = await fetch(`/m/risk.core/action-plan/task/${task.id}`, { method: 'DELETE' })
    if (res.ok) {
      showFlash('Tâche supprimée')
      await refreshData()
    }
  } catch (e) {
    showFlash('Erreur', false)
  }
}

const toggleTask = async (task) => {
  try {
    await fetch(`/m/risk.core/action-plan/task/${task.id}/toggle`, { method: 'PUT' })
    await refreshData()
  } catch (e) {
    showFlash('Erreur', false)
  }
}

const addComment = async () => {
  if (!newComment.value.trim()) return
  commenting.value = true
  try {
    const res = await fetch(`/m/risk.core/action-plan/${planId}/comment`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: JSON.stringify({ comment: newComment.value }),
    })
    if (res.ok) {
      showFlash('Commentaire ajouté')
      newComment.value = ''
      await refreshData()
    }
  } catch (e) {
    showFlash('Erreur', false)
  } finally {
    commenting.value = false
  }
}

const savePlan = async () => {
  saving.value = true
  try {
    const res = await fetch(`/m/risk.core/action-plan/${planId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: JSON.stringify(formData.value),
    })
    if (res.ok) {
      showFlash('Plan enregistré')
      editMode.value = false
      await refreshData()
    }
  } catch (e) {
    showFlash('Erreur', false)
  } finally {
    saving.value = false
  }
}

const deletePlan = async () => {
  if (!confirm('Supprimer ce plan d\'action?')) return
  try {
    const res = await fetch(`/m/risk.core/action-plan/${planId}`, { method: 'DELETE' })
    if (res.ok) {
      showFlash('Plan supprimé')
      setTimeout(() => router.push('/risk/action-tracking'), 1500)
    }
  } catch (e) {
    showFlash('Erreur', false)
  }
}

const applyRecommendation = (rec) => {
  formData.value.action_plan += `\n\n[${rec.label}] ${rec.description}`
  showFlash('Recommandation appliquée')
}

// Init
onMounted(() => {
  if (plan) {
    formData.value = {
      title: plan.title || '',
      description: plan.description || '',
      action_plan: plan.action_plan || '',
      priority: plan.priority || 'medium',
      status: plan.status || 'pending',
      target_date: plan.target_date || '',
      progress: plan.progress || 0,
    }
  }
})
</script>

<style scoped>
/* ═══ PAGE ═══ */
.plan-resume-page {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #f0f4f8;
}

/* ═══ TOPBAR ═══ */
.pr-topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  background: #0f172a;
  color: #f1f5f9;
  gap: 20px;
  flex-shrink: 0;
  border-bottom: 1px solid #1e293b;
}

.pr-left, .pr-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-back {
  width: 32px;
  height: 32px;
  border: none;
  background: #1e293b;
  color: #94a3b8;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.btn-back:hover {
  background: #334155;
  color: #f1f5f9;
}

.pr-title-box {
  flex: 1;
}

.pr-code {
  font-family: monospace;
  font-size: 9px;
  font-weight: 800;
  color: #60a5fa;
  text-transform: uppercase;
}

.pr-title {
  font-size: 13px;
  font-weight: 700;
  color: #f1f5f9;
  margin-top: 2px;
}

.pr-right {
  justify-self: flex-end;
}

/* ═══ BODY ═══ */
.pr-body {
  flex: 1;
  overflow-y: auto;
  padding: 12px 20px;
}

.pr-grid {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 14px;
  height: 100%;
}

/* ═══ CARTE RISQUE ═══ */
.risk-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border: 1px solid #fcd34d;
  border-radius: 8px;
  color: #92400e;
}

.rc-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.rc-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.rc-info {
  flex: 1;
}

.rc-code {
  font-family: monospace;
  font-size: 9px;
  font-weight: 800;
  color: #4338ca;
  background: #ede9fe;
  padding: 1px 5px;
  border-radius: 3px;
  display: inline-block;
}

.rc-title {
  font-size: 11px;
  font-weight: 700;
  color: #0f172a;
  margin-top: 2px;
}

.rc-zone {
  font-size: 9px;
  margin-top: 2px;
  font-weight: 600;
}

.btn-view-risk {
  padding: 6px 12px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.btn-view-risk:hover {
  background: #1d4ed8;
}

/* ═══ RECOMMANDATIONS ═══ */
.recommendations-box {
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  margin-top: 12px;
}

.rb-header {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 12px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 11px;
  font-weight: 700;
  color: #1e293b;
}

.rb-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 10px;
}

.rb-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.rbi-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 14px;
  flex-shrink: 0;
}

.rbi-text {
  flex: 1;
  min-width: 0;
}

.rbi-label {
  font-size: 10px;
  font-weight: 700;
  color: #1e293b;
}

.rbi-desc {
  font-size: 8px;
  color: #64748b;
  margin-top: 1px;
}

.btn-apply {
  width: 24px;
  height: 24px;
  border: none;
  background: #10b981;
  color: #fff;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  flex-shrink: 0;
}

.btn-apply:hover {
  background: #059669;
}

/* ═══ FORMULAIRE ÉDITION ═══ */
.edit-form {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e2e8f0;
  margin-top: 12px;
}

.form-section h3 {
  font-size: 12px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 12px 0;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 10px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-group label {
  font-size: 10px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input {
  padding: 8px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 11px;
  background: #f8fafc;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: #93c5fd;
  background: #fff;
  box-shadow: 0 0 0 2px rgba(147, 197, 253, 0.25);
}

textarea.form-input {
  resize: vertical;
  min-height: 60px;
}

.progress-input {
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-range {
  flex: 1;
  height: 6px;
  border-radius: 3px;
  cursor: pointer;
}

.progress-value {
  font-size: 11px;
  font-weight: 700;
  color: #1e293b;
  min-width: 35px;
  text-align: right;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid #e2e8f0;
  margin-top: 12px;
}

/* ═══ PLAN VIEW ═══ */
.plan-view {
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  padding: 16px;
  margin-top: 12px;
}

.plan-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.ph-status {
  display: flex;
  gap: 6px;
}

.status-badge,
.priority-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 9px;
  font-weight: 700;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-in_progress { background: #dbeafe; color: #0369a1; }
.status-review { background: #fecaca; color: #7f1d1d; }
.status-completed { background: #dcfce7; color: #166534; }
.status-cancelled { background: #e5e7eb; color: #374151; }
.status-blocked { background: #fed7aa; color: #7c2d12; }

.priority-critical { background: #fecaca; color: #991b1b; }
.priority-high { background: #fed7aa; color: #92400e; }
.priority-medium { background: #fef08a; color: #713f12; }
.priority-low { background: #dcfce7; color: #166534; }

.ph-dates {
  display: flex;
  gap: 12px;
  font-size: 10px;
  color: #64748b;
}

.date-item {
  display: flex;
  align-items: center;
  gap: 3px;
}

.plan-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 16px;
}

.description,
.action-plan,
.notes {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.description h4,
.action-plan h4,
.notes h4 {
  font-size: 11px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.description p,
.notes p {
  font-size: 10px;
  color: #475569;
  line-height: 1.6;
  margin: 0;
}

.ap-content {
  font-size: 10px;
  color: #475569;
  line-height: 1.7;
  white-space: pre-wrap;
  word-break: break-word;
}

.plan-progress {
  padding: 12px;
  background: #f8fafc;
  border-radius: 6px;
}

.pp-header {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 6px;
}

.pp-value {
  color: #2563eb;
}

.pp-bar {
  width: 100%;
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.pp-fill {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #0369a1);
  transition: width 0.3s;
}

/* ═══ TABS ═══ */
.tabs-container {
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  margin-top: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 400px;
}

.tabs-nav {
  display: flex;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  gap: 0;
}

.tab-btn {
  flex: 1;
  padding: 10px;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tab-btn:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.tab-btn.active {
  color: #2563eb;
  border-bottom-color: #2563eb;
}

.tab-badge {
  background: #1e293b;
  color: #f1f5f9;
  padding: 1px 6px;
  border-radius: 10px;
  font-size: 8px;
  min-width: 18px;
  text-align: center;
}

.tabs-content {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
}

.tab-panel {
  display: flex;
  flex-direction: column;
  gap: 10px;
  height: 100%;
}

.panel-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.panel-toolbar h4 {
  font-size: 11px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.btn-add {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

.btn-add:hover {
  background: #1d4ed8;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 40px 20px;
  text-align: center;
  color: #94a3b8;
}

.empty-state i {
  font-size: 28px;
  opacity: 0.5;
}

.empty-state p {
  font-size: 10px;
  margin: 0;
}

/* ═══ TÂCHES ═══ */
.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.task-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 8px 10px;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.tr-checkbox {
  margin-top: 2px;
  flex-shrink: 0;
}

.tr-checkbox input {
  cursor: pointer;
}

.tr-content {
  flex: 1;
  min-width: 0;
}

.tr-title {
  font-size: 11px;
  font-weight: 600;
  color: #1e293b;
}

.tr-title.completed {
  color: #94a3b8;
  text-decoration: line-through;
}

.tr-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 3px;
  font-size: 8px;
  color: #64748b;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 2px;
}

.status-badge-sm {
  display: inline-block;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 7px;
  font-weight: 700;
  text-transform: uppercase;
}

.tr-desc {
  font-size: 9px;
  color: #64748b;
  margin-top: 4px;
  font-style: italic;
}

.tr-actions {
  display: flex;
  gap: 3px;
  flex-shrink: 0;
}

.btn-icon-sm {
  width: 24px;
  height: 24px;
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: #64748b;
}

.btn-icon-sm:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.btn-icon-sm.btn-danger:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fca5a5;
}

/* ═══ COMMENTAIRES ═══ */
.comments-area {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.comment-input {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.comment-input textarea {
  padding: 8px;
}

.comment-input button {
  align-self: flex-start;
}

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.comment-box {
  padding: 8px 10px;
  background: #f8fafc;
  border-radius: 6px;
  border-left: 3px solid #2563eb;
}

.cb-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
  font-size: 9px;
}

.cb-header strong {
  color: #1e293b;
}

.cb-time {
  color: #94a3b8;
}

.cb-body {
  font-size: 10px;
  color: #475569;
  line-height: 1.6;
}

.cb-badge {
  display: inline-block;
  margin-top: 4px;
  font-size: 7px;
  font-weight: 700;
  background: #e0e7ff;
  color: #4338ca;
  padding: 2px 6px;
  border-radius: 3px;
  text-transform: uppercase;
}

/* ═══ HISTORIQUE ═══ */
.history-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.history-entry {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 8px;
  background: #f8fafc;
  border-radius: 6px;
}

.he-icon {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 12px;
  flex-shrink: 0;
}

.he-content {
  flex: 1;
  min-width: 0;
}

.he-action {
  font-size: 10px;
  font-weight: 700;
  color: #1e293b;
}

.he-desc {
  font-size: 9px;
  color: #64748b;
  margin-top: 2px;
}

.he-meta {
  display: flex;
  gap: 8px;
  font-size: 8px;
  color: #94a3b8;
  margin-top: 4px;
}

/* ═══ SIDEBAR ═══ */
.pr-sidebar {
  display: flex;
  flex-direction: column;
  gap: 12px;
  overflow-y: auto;
  padding-right: 6px;
}

.info-card {
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.ic-header {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 12px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 10px;
  font-weight: 700;
  color: #1e293b;
}

.ic-header i {
  color: #2563eb;
  font-size: 12px;
}

.ic-body {
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.ic-value {
  font-size: 10px;
  font-weight: 700;
  color: #1e293b;
}

.ic-email {
  font-size: 8px;
  color: #64748b;
}

.ic-row {
  display: flex;
  justify-content: space-between;
  font-size: 9px;
}

.ic-label {
  color: #64748b;
  font-weight: 600;
}

.ic-deadline {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 8px;
  background: #e0f2fe;
  border-radius: 4px;
  font-size: 8px;
  color: #0369a1;
  font-weight: 700;
  margin-top: 4px;
}

.ic-deadline.overdue {
  background: #fee2e2;
  color: #dc2626;
}

.ic-gap {
  display: flex;
  justify-content: space-between;
  padding: 6px 8px;
  background: #fef3c7;
  border-radius: 4px;
  font-size: 8px;
  margin-top: 4px;
}

.gap-label {
  color: #92400e;
  font-weight: 700;
}

.gap-value {
  font-weight: 700;
}

.gap-value.danger {
  color: #dc2626;
}

.gap-value.success {
  color: #16a34a;
}

.ic-kpi {
  display: flex;
  justify-content: space-between;
  padding: 6px 8px;
  background: #f8fafc;
  border-radius: 4px;
}

.kpi-label {
  font-size: 8px;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.kpi-value {
  font-size: 11px;
  font-weight: 800;
  color: #2563eb;
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
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #0f172a;
  color: #f1f5f9;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.modal-close {
  width: 24px;
  height: 24px;
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
}

.modal-close:hover {
  color: #f1f5f9;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
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

/* ═══ BOUTONS ═══ */
.btn-primary,
.btn-secondary,
.btn-cancel,
.btn-danger {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 8px 16px;
  border: none;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: #2563eb;
  color: #fff;
}

.btn-primary:hover:not(:disabled) { background: #1d4ed8; }
.btn-primary:disabled { opacity: 0.4; cursor: not-allowed; }

.btn-secondary {
  background: #7c3aed;
  color: #fff;
}

.btn-secondary:hover { background: #6d28d9; }

.btn-cancel {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.btn-cancel:hover { background: #e2e8f0; }

.btn-danger {
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fca5a5;
}

.btn-danger:hover { background: #fecaca; }

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  background: #1e293b;
  color: #94a3b8;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
}

.btn-icon:hover {
  background: #334155;
  color: #f1f5f9;
}

/* ═══ FLASH ═══ */
.flash {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 3000;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  box-shadow: 0 4px 16px rgba(0,0,0,.12);
}

.flash-ok {
  background: #f0fdf4;
  border: 1px solid #86efac;
  color: #15803d;
}

.flash-error {
  background: #fef2f2;
  border: 1px solid #fca5a5;
  color: #dc2626;
}

/* ═══ TRANSITIONS ═══ */
.modal-enter-active, .modal-leave-active { transition: opacity .2s, transform .2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(.95); }

.flash-enter-active, .flash-leave-active { transition: opacity .2s, transform .2s; }
.flash-enter-from, .flash-leave-to { opacity: 0; transform: translateX(20px); }

.spin {
  animation: spin 0.7s linear infinite;
  display: inline-block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ═══ RESPONSIVE ═══ */
@media (max-width: 1024px) {
  .pr-grid {
    grid-template-columns: 1fr;
  }
  .pr-sidebar {
    display: none;
  }
}

@media (max-width: 768px) {
  .pr-topbar {
    padding: 10px 12px;
    gap: 10px;
  }
  .plan-header {
    flex-direction: column;
    gap: 10px;
  }
  .ph-dates {
    flex-direction: column;
  }
}
</style>