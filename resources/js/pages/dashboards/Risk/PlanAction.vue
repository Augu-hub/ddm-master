<template>
  <VerticalLayout>
    <div class="plan-creation-page">

      <!-- ═══ TOPBAR ═══ -->
      <div class="pc-topbar">
        <div class="pc-topbar-left">
          <i class="ti ti-clipboard-list"></i>
          <div>
            <h1>Plan d'Action</h1>
            <p>{{ isEdit ? 'Modification du plan' : 'Création du plan d\'action' }} — {{ riskCode }}</p>
          </div>
        </div>
        <div class="pc-topbar-right">
          <button v-if="isEdit" class="btn-secondary" @click="viewDetail">
            <i class="ti ti-eye"></i> Voir détail
          </button>
          <button class="btn-icon" @click="goBack" title="Retour">
            <i class="ti ti-arrow-left"></i>
          </button>
        </div>
      </div>

      <!-- ═══ CONTENU ═══ -->
      <div class="pc-body">

        <!-- Banneau risque associé -->
        <div class="risk-banner">
          <div class="rb-icon">
            <i class="ti ti-shield-bolt"></i>
          </div>
          <div class="rb-info">
            <div class="rb-code">{{ riskCode }}</div>
            <div class="rb-title">{{ riskLibelle }}</div>
            <div class="rb-context">
              {{ macroName }} › {{ processName }} › {{ activityName }}
            </div>
            <div v-if="riskCriticality" class="rb-criticality" :style="{ background: riskZoneColor }">
              <i class="ti ti-alert-circle"></i> {{ riskCriticality }}
            </div>
          </div>
          <div class="rb-actions">
            <button class="btn-view-risk" @click="openRisk">
              <i class="ti ti-arrow-up-right"></i> Voir le risque
            </button>
          </div>
        </div>

        <!-- Grille formulaire -->
        <form @submit.prevent="submitForm" class="form-grid">

          <!-- SECTION 1: INFORMATIONS DE BASE -->
          <div class="section">
            <div class="section-title">Informations de base</div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Code du plan *</label>
                <input 
                  v-model="form.code" 
                  type="text" 
                  class="form-input"
                  placeholder="AP-2026-0001"
                  readonly
                />
                <span class="form-hint">Généré automatiquement</span>
              </div>
              <div class="form-group">
                <label class="form-label">Statut initial *</label>
                <select v-model="form.status" class="form-input">
                  <option v-for="s in statuses" :key="s.value" :value="s.value">
                    {{ s.label }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group form-full">
                <label class="form-label">Titre du plan d'action *</label>
                <input 
                  v-model="form.title" 
                  type="text" 
                  class="form-input"
                  placeholder="Ex: Mettre en place une procédure de vérification..."
                  @blur="validateField('title')"
                />
                <span v-if="errors.title" class="form-error">{{ errors.title }}</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group form-full">
                <label class="form-label">Description courte</label>
                <textarea 
                  v-model="form.description" 
                  rows="2" 
                  class="form-input"
                  placeholder="Description brève du plan..."
                ></textarea>
              </div>
            </div>
          </div>

          <!-- SECTION 2: PLAN DÉTAILLÉ -->
          <div class="section">
            <div class="section-title">Plan d'action détaillé</div>

            <div class="form-row">
              <div class="form-group form-full">
                <label class="form-label">Plan d'action *</label>
                <textarea 
                  v-model="form.action_plan" 
                  rows="5" 
                  class="form-input"
                  placeholder="Décrivez les étapes détaillées du plan d'action..."
                  @blur="validateField('action_plan')"
                ></textarea>
                <span v-if="errors.action_plan" class="form-error">{{ errors.action_plan }}</span>
                <span class="form-hint">{{ form.action_plan?.length || 0 }} caractères</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group form-full">
                <label class="form-label">Remarques / Notes supplémentaires</label>
                <textarea 
                  v-model="form.notes" 
                  rows="3" 
                  class="form-input"
                  placeholder="Notes additionnelles..."
                ></textarea>
              </div>
            </div>
          </div>

          <!-- SECTION 3: ASSIGNATION ET PRIORITÉ -->
          <div class="section">
            <div class="section-title">Assignation et priorité</div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Responsable du plan *</label>
                <select v-model="form.assigned_to" class="form-input" @blur="validateField('assigned_to')">
                  <option value="">— Sélectionner un responsable —</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">
                    {{ u.name }}
                  </option>
                </select>
                <span v-if="errors.assigned_to" class="form-error">{{ errors.assigned_to }}</span>
              </div>
              <div class="form-group">
                <label class="form-label">Priorité *</label>
                <select v-model="form.priority" class="form-input">
                  <option v-for="p in priorities" :key="p.value" :value="p.value">
                    {{ p.label }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Entité responsable</label>
                <select v-model="form.entity_id" class="form-input">
                  <option value="">— Aucune entité —</option>
                  <option v-for="e in entities" :key="e.id" :value="e.id">
                    {{ e.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- SECTION 4: PLANIFICATION -->
          <div class="section">
            <div class="section-title">Planification et délais</div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Date de début</label>
                <input 
                  v-model="form.start_date" 
                  type="date" 
                  class="form-input"
                />
              </div>
              <div class="form-group">
                <label class="form-label">Date cible *</label>
                <input 
                  v-model="form.target_date" 
                  type="date" 
                  class="form-input"
                  @blur="validateField('target_date')"
                />
                <span v-if="errors.target_date" class="form-error">{{ errors.target_date }}</span>
              </div>
            </div>

            <div v-if="form.target_date" class="deadline-info">
              <i class="ti ti-info-circle"></i>
              <span>Délai de <strong>{{ daysUntilDeadline }}</strong> jours</span>
            </div>
          </div>

          <!-- SECTION 5: BUDGET -->
          <div class="section">
            <div class="section-title">Coûts (XOF)</div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Coût estimé</label>
                <input 
                  v-model.number="form.cost_estimate" 
                  type="number" 
                  step="1000"
                  class="form-input"
                  placeholder="0"
                />
              </div>
              <div class="form-group">
                <label class="form-label">Coût réel</label>
                <input 
                  v-model.number="form.actual_cost" 
                  type="number" 
                  step="1000"
                  class="form-input"
                  placeholder="0"
                />
              </div>
            </div>

            <div v-if="form.cost_estimate && form.actual_cost" class="cost-info">
              <i class="ti ti-calculator"></i>
              <span>
                Écart: <strong>{{ costGap }}</strong> XOF 
                <span :class="costGap > 0 ? 'text-danger' : 'text-success'">
                  ({{ costGapPercent }}%)
                </span>
              </span>
            </div>
          </div>

          <!-- SECTION 6: SOURCE ET AUTO-GÉNÉRATION -->
          <div class="section">
            <div class="section-title">Source du plan</div>

            <div class="form-row">
              <div class="form-group form-full">
                <label class="checkbox-label">
                  <input 
                    v-model="form.is_auto_generated" 
                    type="checkbox"
                    disabled
                  />
                  <span>Auto-généré depuis un statut de risque</span>
                </label>
              </div>
            </div>

            <div v-if="form.source_status" class="form-row">
              <div class="form-group">
                <label class="form-label">Statut source</label>
                <input 
                  :value="form.source_status" 
                  type="text" 
                  class="form-input"
                  readonly
                />
              </div>
            </div>
          </div>

          <!-- BOUTONS D'ACTION -->
          <div class="form-actions">
            <button type="button" class="btn-cancel" @click="goBack">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button type="button" class="btn-secondary" @click="saveDraft" v-if="!isEdit">
              <i class="ti ti-device-floppy"></i> Enregistrer comme brouillon
            </button>
            <button 
              type="submit" 
              class="btn-primary"
              :disabled="!isFormValid || saving"
            >
              <i :class="saving ? 'ti ti-loader-2 spin' : 'ti ti-check'"></i>
              {{ saving ? 'Enregistrement...' : 'Enregistrer et continuer' }}
            </button>
          </div>
        </form>

        <!-- Onglets pour edit -->
        <div v-if="isEdit && planId" class="tabs-section">
          <div class="tabs-header">
            <button 
              v-for="tab in tabs" 
              :key="tab.id"
              :class="['tab-btn', activeTab === tab.id ? 'tab-active' : '']"
              @click="activeTab = tab.id"
            >
              <i :class="tab.icon"></i> {{ tab.label }}
            </button>
          </div>

          <div class="tabs-content">
            <!-- Tâches -->
            <div v-if="activeTab === 'tasks'" class="tab-panel">
              <div class="panel-header">
                <h3>Tâches du plan</h3>
                <button class="btn-add" @click="openAddTask">
                  <i class="ti ti-plus"></i> Ajouter tâche
                </button>
              </div>
              <div class="tasks-list">
                <div v-if="tasks.length === 0" class="empty">Aucune tâche</div>
                <div v-for="task in tasks" :key="task.id" class="task-item">
                  <div class="ti-checkbox">
                    <input type="checkbox" :checked="task.status === 'completed'" />
                  </div>
                  <div class="ti-info">
                    <div class="ti-title">{{ task.title }}</div>
                    <div class="ti-meta">{{ task.assigned_to_name }} · {{ fmtDate(task.target_date) }}</div>
                  </div>
                  <div class="ti-actions">
                    <button @click="editTask(task)" class="btn-icon-sm">
                      <i class="ti ti-pencil"></i>
                    </button>
                    <button @click="deleteTask(task)" class="btn-icon-sm btn-danger">
                      <i class="ti ti-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Commentaires -->
            <div v-if="activeTab === 'comments'" class="tab-panel">
              <div class="panel-header">
                <h3>Commentaires</h3>
              </div>
              <div class="comments-form">
                <textarea 
                  v-model="newComment" 
                  rows="3" 
                  class="form-input"
                  placeholder="Ajouter un commentaire..."
                ></textarea>
                <button 
                  @click="addComment" 
                  class="btn-primary"
                  :disabled="!newComment.trim()"
                >
                  <i class="ti ti-send"></i> Envoyer
                </button>
              </div>
              <div class="comments-list">
                <div v-if="comments.length === 0" class="empty">Aucun commentaire</div>
                <div v-for="comment in comments" :key="comment.id" class="comment-item">
                  <div class="cm-header">
                    <strong>{{ comment.user_name }}</strong>
                    <span class="cm-date">{{ fmtDateTime(comment.created_at) }}</span>
                  </div>
                  <p>{{ comment.comment }}</p>
                </div>
              </div>
            </div>

            <!-- Historique -->
            <div v-if="activeTab === 'history'" class="tab-panel">
              <div class="panel-header">
                <h3>Historique</h3>
              </div>
              <div class="history-list">
                <div v-if="history.length === 0" class="empty">Aucun historique</div>
                <div v-for="entry in history" :key="entry.id" class="history-item">
                  <span class="hi-action">{{ entry.action }}</span>
                  <span class="hi-desc">{{ entry.description }}</span>
                  <span class="hi-user">{{ entry.user_name }}</span>
                  <span class="hi-date">{{ fmtDateTime(entry.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ═══ MODAL TÂCHE ═══ -->
      <Teleport to="body">
        <Transition name="mf">
          <div v-if="taskModal.open" class="modal-overlay" @click.self="taskModal.open=false">
            <div class="modal-box modal-sm" @click.stop>
              <div class="modal-hdr">
                <span>{{ taskModal.id ? 'Modifier' : 'Nouvelle' }} tâche</span>
                <button class="modal-x" @click="taskModal.open=false"><i class="ti ti-x"></i></button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <label class="form-label">Titre *</label>
                  <input v-model="taskForm.title" type="text" class="form-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Description</label>
                  <textarea v-model="taskForm.description" rows="2" class="form-input"></textarea>
                </div>
                <div class="form-group">
                  <label class="form-label">Responsable</label>
                  <select v-model="taskForm.assigned_to" class="form-input">
                    <option value="">— Aucun —</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Date cible</label>
                  <input v-model="taskForm.target_date" type="date" class="form-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Statut</label>
                  <select v-model="taskForm.status" class="form-input">
                    <option value="pending">En attente</option>
                    <option value="in_progress">En cours</option>
                    <option value="completed">Complétée</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn-cancel" @click="taskModal.open=false">Annuler</button>
                <button class="btn-primary" @click="saveTask" :disabled="!taskForm.title">Enregistrer</button>
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
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const route = useRoute()
const router = useRouter()

// Props depuis Inertia
defineProps({
  riskCode: String,
  riskLibelle: String,
  macroName: String,
  processName: String,
  activityName: String,
  riskCriticality: String,
  riskZoneColor: String,
  riskId: Number,
  isEdit: Boolean,
  planId: Number,
  initialPlan: Object,
  users: Array,
  entities: Array,
  priorities: Array,
  statuses: Array,
})

// State
const form = ref({
  code: '',
  risk_id: null,
  entity_id: null,
  title: '',
  description: '',
  action_plan: '',
  priority: 'medium',
  status: 'pending',
  assigned_to: null,
  target_date: '',
  start_date: '',
  completion_date: null,
  progress: 0,
  cost_estimate: null,
  actual_cost: null,
  notes: '',
  is_auto_generated: false,
  source_status: null,
})

const errors = ref({})
const saving = ref(false)
const tasks = ref([])
const comments = ref([])
const history = ref([])
const newComment = ref('')
const activeTab = ref('tasks')
const taskModal = ref({ open: false, id: null })
const taskForm = ref({ title: '', description: '', assigned_to: '', target_date: '', status: 'pending' })

// Tabs
const tabs = [
  { id: 'tasks', label: 'Tâches', icon: 'ti ti-checkbox' },
  { id: 'comments', label: 'Commentaires', icon: 'ti ti-message' },
  { id: 'history', label: 'Historique', icon: 'ti ti-clock-history' },
]

let flashTimer = null
const flashMsg = ref('')
const flashOk = ref(true)

// Helpers
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
const fmtDateTime = (d) => d ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

const showFlash = (msg, ok = true) => {
  if (flashTimer) clearTimeout(flashTimer)
  flashMsg.value = msg
  flashOk.value = ok
  flashTimer = setTimeout(() => { flashMsg.value = '' }, 4000)
}

// Computed
const daysUntilDeadline = computed(() => {
  if (!form.value.target_date) return 0
  const today = new Date()
  const target = new Date(form.value.target_date)
  return Math.ceil((target - today) / (1000 * 60 * 60 * 24))
})

const costGap = computed(() => {
  if (!form.value.cost_estimate || !form.value.actual_cost) return 0
  return Math.round(form.value.actual_cost - form.value.cost_estimate)
})

const costGapPercent = computed(() => {
  if (!form.value.cost_estimate || !form.value.actual_cost) return 0
  return Math.round((costGap.value / form.value.cost_estimate) * 100)
})

const isFormValid = computed(() => {
  return form.value.title?.trim() &&
         form.value.action_plan?.trim() &&
         form.value.assigned_to &&
         form.value.target_date
})

// Methods
const validateField = (field) => {
  errors.value[field] = ''
  
  if (field === 'title' && !form.value.title?.trim()) {
    errors.value[field] = 'Le titre est requis'
  }
  if (field === 'action_plan' && !form.value.action_plan?.trim()) {
    errors.value[field] = 'Le plan d\'action est requis'
  }
  if (field === 'assigned_to' && !form.value.assigned_to) {
    errors.value[field] = 'Un responsable est requis'
  }
  if (field === 'target_date' && !form.value.target_date) {
    errors.value[field] = 'Une date cible est requise'
  }
}

const submitForm = async () => {
  Object.keys(form.value).forEach(key => validateField(key))
  if (!isFormValid.value) {
    showFlash('Veuillez corriger les erreurs', false)
    return
  }

  saving.value = true
  try {
    const url = form.value.id ? `/m/risk.core/action-plan/${form.value.id}` : '/m/risk.core/action-plan'
    const method = form.value.id ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        risk_id: route.params.riskId,
        ...form.value,
      }),
    })
    
    const data = await response.json()
    if (response.ok && data.success) {
      showFlash(data.message || 'Plan enregistré ✓')
      setTimeout(() => {
        router.push(`/risk/action-tracking/${data.data?.id || ''}`)
      }, 1500)
    } else {
      showFlash(data.message || 'Erreur lors de l\'enregistrement', false)
    }
  } catch (e) {
    showFlash('Erreur réseau', false)
  } finally {
    saving.value = false
  }
}

const saveDraft = async () => {
  form.value.status = 'draft'
  await submitForm()
}

const goBack = () => router.back()

const openRisk = () => {
  router.push(`/risk/register/${route.params.riskId}`)
}

const viewDetail = () => {
  router.push(`/risk/action-tracking/${planId}`)
}

const openAddTask = () => {
  taskForm.value = { title: '', description: '', assigned_to: '', target_date: '', status: 'pending' }
  taskModal.value = { open: true, id: null }
}

const editTask = (task) => {
  Object.assign(taskForm.value, task)
  taskModal.value = { open: true, id: task.id }
}

const saveTask = async () => {
  // À implémenter
  showFlash('Tâche enregistrée')
  taskModal.value.open = false
}

const deleteTask = async (task) => {
  if (!confirm('Supprimer cette tâche?')) return
  // À implémenter
  showFlash('Tâche supprimée')
}

const addComment = async () => {
  if (!newComment.value.trim()) return
  // À implémenter
  newComment.value = ''
  showFlash('Commentaire ajouté')
}

// Lifecycle
onMounted(() => {
  if (route.params.riskId) {
    form.value.risk_id = parseInt(route.params.riskId)
  }
  if (isEdit.value && initialPlan) {
    Object.assign(form.value, initialPlan)
  } else {
    // Générer code
    form.value.code = `AP-${new Date().getFullYear()}-${Math.random().toString().slice(2, 6).padStart(4, '0')}`
  }
})
</script>

<style scoped>
/* ═══ PAGE ═══ */
.plan-creation-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 60px);
  background: #f0f4f8;
  overflow: hidden;
}

/* ═══ TOPBAR ═══ */
.pc-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 20px;
  background: #0f172a;
  color: #e2e8f0;
  flex-shrink: 0;
  gap: 20px;
}

.pc-topbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pc-topbar-left i {
  font-size: 24px;
  color: #60a5fa;
}

.pc-topbar-left h1 {
  font-size: 15px;
  font-weight: 700;
  margin: 0;
  color: #f1f5f9;
}

.pc-topbar-left p {
  font-size: 10px;
  color: #64748b;
  margin: 0;
}

.pc-topbar-right {
  display: flex;
  gap: 8px;
}

/* ═══ BODY ═══ */
.pc-body {
  flex: 1;
  overflow-y: auto;
  padding: 12px 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* ═══ BANNEAU RISQUE ═══ */
.risk-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 9px;
  color: #d97706;
}

.rb-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.rb-info {
  flex: 1;
}

.rb-code {
  font-family: monospace;
  font-size: 10px;
  font-weight: 800;
  color: #4338ca;
  background: #ede9fe;
  padding: 1px 5px;
  border-radius: 3px;
  display: inline-block;
}

.rb-title {
  font-size: 12px;
  font-weight: 700;
  color: #0f172a;
  margin-top: 3px;
}

.rb-context {
  font-size: 9px;
  color: #64748b;
  margin-top: 2px;
}

.rb-criticality {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  padding: 2px 8px;
  border-radius: 6px;
  margin-top: 3px;
}

.rb-actions {
  display: flex;
  gap: 6px;
}

/* ═══ FORMULAIRE ═══ */
.form-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: #fff;
  border-radius: 9px;
  padding: 16px;
  border: 1px solid #e2e8f0;
}

.section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.section-title {
  font-size: 12px;
  font-weight: 800;
  color: #1e293b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e2e8f0;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-group.form-full {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 10px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 11px;
  font-family: inherit;
  background: #f8fafc;
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
  font-family: inherit;
}

.form-hint {
  font-size: 9px;
  color: #64748b;
  font-weight: 500;
}

.form-error {
  font-size: 9px;
  color: #dc2626;
  font-weight: 600;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  cursor: pointer;
}

.checkbox-label input {
  cursor: pointer;
}

/* ═══ INFO BLOCKS ═══ */
.deadline-info,
.cost-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  background: #e0f2fe;
  border-left: 3px solid #0369a1;
  border-radius: 5px;
  font-size: 10px;
  color: #0369a1;
}

.cost-info {
  background: #fef3c7;
  border-left-color: #d97706;
  color: #92400e;
}

.text-danger {
  color: #dc2626;
  font-weight: 700;
}

.text-success {
  color: #16a34a;
  font-weight: 700;
}

/* ═══ BOUTONS ═══ */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid #e2e8f0;
}

.btn-primary,
.btn-secondary,
.btn-cancel {
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
  background: #fff;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.btn-cancel:hover { background: #f1f5f9; }

.btn-icon,
.btn-view-risk {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: #0f172a;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
}

.btn-view-risk {
  background: #2563eb;
}

.btn-view-risk:hover { background: #1d4ed8; }

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

/* ═══ TABS ═══ */
.tabs-section {
  background: #fff;
  border-radius: 9px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  margin-top: 12px;
}

.tabs-header {
  display: flex;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.tab-btn {
  flex: 1;
  padding: 10px;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  border-bottom: 2px solid transparent;
}

.tab-btn:hover {
  background: #f1f5f9;
}

.tab-btn.tab-active {
  color: #2563eb;
  border-bottom-color: #2563eb;
}

.tabs-content {
  padding: 12px;
}

.tab-panel {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.panel-header h3 {
  font-size: 12px;
  font-weight: 700;
  margin: 0;
  color: #1e293b;
}

/* ═══ LISTES ═══ */
.empty {
  text-align: center;
  padding: 20px;
  color: #94a3b8;
  font-size: 11px;
}

.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.task-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.ti-checkbox {
  flex-shrink: 0;
}

.ti-checkbox input {
  cursor: pointer;
}

.ti-info {
  flex: 1;
  min-width: 0;
}

.ti-title {
  font-size: 11px;
  font-weight: 600;
  color: #0f172a;
}

.ti-meta {
  font-size: 9px;
  color: #94a3b8;
  margin-top: 1px;
}

.ti-actions {
  display: flex;
  gap: 3px;
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
}

.btn-icon-sm.btn-danger:hover {
  background: #fee2e2;
  color: #dc2626;
}

/* ═══ COMMENTAIRES ═══ */
.comments-form {
  display: flex;
  gap: 6px;
  margin-bottom: 10px;
}

.comments-form textarea {
  flex: 1;
}

.comments-form button {
  align-self: flex-start;
}

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.comment-item {
  padding: 8px 10px;
  background: #f8fafc;
  border-radius: 6px;
  border-left: 3px solid #2563eb;
}

.cm-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 4px;
  font-size: 10px;
}

.cm-header strong {
  color: #0f172a;
}

.cm-date {
  color: #94a3b8;
  margin-left: auto;
}

.comment-item p {
  margin: 0;
  font-size: 11px;
  color: #334155;
  line-height: 1.6;
}

/* ═══ HISTORIQUE ═══ */
.history-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.history-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  font-size: 10px;
  color: #64748b;
}

.hi-action {
  font-size: 8px;
  font-weight: 700;
  background: #e0e7ff;
  color: #4338ca;
  padding: 1px 5px;
  border-radius: 4px;
  text-transform: uppercase;
  flex-shrink: 0;
}

.hi-desc {
  flex: 1;
}

.hi-user {
  font-weight: 600;
  color: #1e293b;
  flex-shrink: 0;
}

.hi-date {
  font-size: 9px;
  flex-shrink: 0;
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

.modal-sm {
  max-width: 400px;
}

.modal-hdr {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #0f172a;
  color: #f1f5f9;
  font-weight: 700;
  font-size: 12px;
  flex-shrink: 0;
}

.modal-x {
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

.modal-x:hover {
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

.flash-ok {
  background: #f0fdf4;
  border: 1px solid #86efac;
  color: #15803d;
}

.flash-err {
  background: #fef2f2;
  border: 1px solid #fca5a5;
  color: #dc2626;
}

/* ═══ TRANSITIONS ═══ */
.mf-enter-active { transition: opacity .18s, transform .18s; }
.mf-leave-active { transition: opacity .14s, transform .14s; }
.mf-enter-from, .mf-leave-to { opacity: 0; transform: scale(.97); }

.fl-enter-active, .fl-leave-active { transition: opacity .2s, transform .2s; }
.fl-enter-from, .fl-leave-to { opacity: 0; transform: translateX(20px); }

.spin {
  animation: spin 0.7s linear infinite;
  display: inline-block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ═══ RESPONSIVE ═══ */
@media(max-width:768px) {
  .form-row { grid-template-columns: 1fr; }
  .risk-banner { flex-direction: column; align-items: flex-start; }
  .rb-actions { width: 100%; }
  .btn-view-risk { width: 100%; justify-content: center; }
}
</style>