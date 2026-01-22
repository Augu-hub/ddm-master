<template>
  <VerticalLayout>
    <Head title="Indisponibilités Auditeurs" />

    <div class="audit-container">
      <!-- HEADER ÉLÉGANT -->
      <div class="audit-header">
        <div class="header-content">
          <div class="header-title">
            <div class="icon-badge">
              <i class="ti ti-calendar-check"></i>
            </div>
            <div>
              <h1>Calendrier des Indisponibilités</h1>
              <p>Gestion centralisée des absences auditeurs et jours fériés</p>
            </div>
          </div>
          <div class="header-actions">
            <button @click="exportExcel" class="btn-action excel" title="Exporter Excel">
              <i class="ti ti-file-spreadsheet"></i> Excel
            </button>
            <button @click="exportPdf" class="btn-action pdf" title="Exporter PDF">
              <i class="ti ti-file-pdf"></i> PDF
            </button>
          </div>
        </div>

        <!-- MESSAGE FLASH -->
        <transition name="fade">
          <div v-if="page.props.flash?.success" class="alert-success">
            <i class="ti ti-circle-check"></i>
            <span>{{ page.props.flash.success }}</span>
            <button @click="clearFlash" class="btn-close"><i class="ti ti-x"></i></button>
          </div>
        </transition>
      </div>

      <!-- KPI CARDS -->
      <div class="kpi-grid">
        <div class="kpi-card" :style="{ borderLeftColor: '#15803d' }">
          <div class="kpi-value">{{ stats.approved }}</div>
          <div class="kpi-label">Approuvées</div>
          <div class="kpi-sublabel">{{ ((stats.approved / stats.total) * 100 || 0).toFixed(0) }}%</div>
        </div>
        <div class="kpi-card" :style="{ borderLeftColor: '#dc2626' }">
          <div class="kpi-value">{{ stats.pending }}</div>
          <div class="kpi-label">En attente</div>
          <div class="kpi-sublabel">À approuver</div>
        </div>
        <div class="kpi-card" :style="{ borderLeftColor: '#1e40af' }">
          <div class="kpi-value">{{ stats.auditors }}</div>
          <div class="kpi-label">Auditeurs</div>
          <div class="kpi-sublabel">Affectés</div>
        </div>
        <div class="kpi-card" :style="{ borderLeftColor: '#d97706' }">
          <div class="kpi-value">{{ stats.total }}</div>
          <div class="kpi-label">Total entrées</div>
          <div class="kpi-sublabel">Globales + Audit</div>
        </div>
      </div>

      <!-- ONGLETS PREMIUM -->
      <div class="tabs-premium">
        <div class="tabs-list">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="['tab-item', { active: activeTab === tab.id }]"
          >
            <i :class="tab.icon"></i>
            <span>{{ tab.name }}</span>
            <span v-if="tab.id === 'auditor'" class="tab-badge">{{ auditorUnavailabilities.length }}</span>
            <span v-if="tab.id === 'global'" class="tab-badge">{{ globalUnavailabilities.length }}</span>
          </button>
        </div>
        <div class="tabs-line"></div>
      </div>

      <!-- CONTENU ONGLETS -->
      <div class="tabs-content-area">
        <!-- TAB 1: CALENDRIER ANNUEL -->
        <div v-show="activeTab === 'calendar'" class="tab-pane" :class="{ active: activeTab === 'calendar' }">
          <div class="calendar-header">
            <div class="calendar-nav">
              <button @click="prevYear" class="btn-nav-year">
                <i class="ti ti-chevron-left"></i>
              </button>
              <h3 class="year-display">{{ currentYear }}</h3>
              <button @click="nextYear" class="btn-nav-year">
                <i class="ti ti-chevron-right"></i>
              </button>
            </div>
            <div class="calendar-filter">
              <label>Filtrer par auditeur:</label>
              <select v-model="selectedAuditorId" class="select-premium">
                <option :value="null">📊 Tous les auditeurs</option>
                <option v-for="aud in auditors" :key="aud.id" :value="aud.id">
                  👤 {{ aud.first_name }} {{ aud.last_name }}
                </option>
              </select>
            </div>
          </div>

          <!-- CALENDRIER 12 MOIS -->
          <div class="calendar-grid-12">
            <div v-for="month in 12" :key="month" class="month-card-premium">
              <div class="month-header-premium">
                <h4>{{ getMonthName(month) }}</h4>
              </div>
              <div class="month-grid-premium">
                <div class="day-name" v-for="d in 7" :key="'dn-' + d">{{ getDayName(d) }}</div>
                <div
                  v-for="(day, i) in getDaysInMonth(month)"
                  :key="'d-' + month + '-' + i"
                  :class="['day-cell-premium', getDayCellClasses(month, day)]"
                  :style="getDayStylePremium(month, day)"
                  :title="getDayTooltip(month, day)"
                >
                  <span v-if="day" class="day-number">{{ day }}</span>
                  <div v-if="day && hasEvent(month, day)" class="day-event-badge">
                    <span class="event-icon">{{ getEventIcon(month, day) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- LÉGENDE PREMIUM -->
          <div class="legend-premium">
            <div class="legend-section">
              <h5><i class="ti ti-users"></i> Auditeurs</h5>
              <div class="legend-items">
                <div v-for="aud in auditors" :key="'auditor-' + aud.id" class="legend-item-premium">
                  <span class="legend-color" :style="{ backgroundColor: getAuditorColor(aud.id) }"></span>
                  <span class="legend-name">{{ aud.first_name }} {{ aud.last_name }}</span>
                </div>
              </div>
            </div>
            <div class="legend-divider"></div>
            <div class="legend-section">
              <h5><i class="ti ti-palette"></i> Types</h5>
              <div class="legend-items">
                <div v-for="type in allTypes" :key="'type-' + type.id" class="legend-item-premium">
                  <span class="legend-color" :style="{ backgroundColor: type.color }"></span>
                  <span class="legend-name">{{ type.icon }} {{ type.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: AUDITEURS -->
        <div v-show="activeTab === 'auditor'" class="tab-pane" :class="{ active: activeTab === 'auditor' }">
          <!-- FORMULAIRE -->
          <div class="form-card-premium">
            <div class="form-header">
              <h4><i class="ti ti-plus"></i> Nouvelle Indisponibilité Auditeur</h4>
            </div>
            <form @submit.prevent="submitAuditorForm" class="form-premium">
              <div class="form-group">
                <label>Auditeur *</label>
                <select v-model="form.auditor.auditor_id" required>
                  <option value="">-- Sélectionner --</option>
                  <option v-for="aud in auditors" :key="aud.id" :value="aud.id">
                    {{ aud.first_name }} {{ aud.last_name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Type d'absence *</label>
                <select v-model="form.auditor.type" required>
                  <option value="">-- Sélectionner --</option>
                  <option v-for="t in auditorTypes" :key="t.id" :value="t.code">
                    {{ t.icon }} {{ t.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Début *</label>
                <input v-model="form.auditor.date_start" type="date" required />
              </div>
              <div class="form-group">
                <label>Fin *</label>
                <input v-model="form.auditor.date_end" type="date" required />
              </div>
              <button type="submit" class="btn-submit-premium">
                <i class="ti ti-plus"></i> Ajouter
              </button>
            </form>
          </div>

          <!-- TABLEAU AUDITEURS -->
          <div class="table-wrapper-premium">
            <table class="table-premium">
              <thead>
                <tr>
                  <th>Auditeur</th>
                  <th>Type</th>
                  <th>Début</th>
                  <th>Fin</th>
                  <th style="width: 60px;">Jours</th>
                  <th style="width: 100px;">Statut</th>
                  <th style="width: 100px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!auditorUnavailabilities.length" class="table-empty">
                  <td colspan="7">
                    <i class="ti ti-inbox"></i> Aucune indisponibilité
                  </td>
                </tr>
                <tr v-for="u in auditorUnavailabilities" :key="'aud-' + u.id" :class="{ 'row-approved': u.is_approved }">
                  <td class="cell-name">
                    <div class="auditor-badge" :style="{ backgroundColor: getAuditorColor(u.auditor_id) }"></div>
                    <strong>{{ u.auditor?.first_name }} {{ u.auditor?.last_name }}</strong>
                  </td>
                  <td>
                    <span class="type-badge" :style="{ backgroundColor: getTypeColor(u.type, 'auditor') }">
                      {{ getTypeIcon(u.type, 'auditor') }}
                    </span>
                  </td>
                  <td>{{ formatDate(u.date_start) }}</td>
                  <td>{{ formatDate(u.date_end) }}</td>
                  <td class="cell-center">{{ calculateDays(u.date_start, u.date_end) }}</td>
                  <td>
                    <span
                      :class="['status-badge', u.is_approved ? 'status-approved' : 'status-pending']"
                    >
                      <i :class="u.is_approved ? 'ti ti-circle-check' : 'ti ti-clock'"></i>
                      {{ u.is_approved ? 'Approuvée' : 'En attente' }}
                    </span>
                  </td>
                  <td class="cell-actions">
                    <button
                      v-if="!u.is_approved"
                      @click="approveAuditor(u.id)"
                      class="btn-action-sm approve"
                      title="Approuver"
                    >
                      <i class="ti ti-circle-check"></i>
                    </button>
                    <button
                      @click="deleteAuditor(u.id)"
                      class="btn-action-sm delete"
                      title="Supprimer"
                    >
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 3: JOURS FÉRIÉS -->
        <div v-show="activeTab === 'global'" class="tab-pane" :class="{ active: activeTab === 'global' }">
          <!-- FORMULAIRE -->
          <div class="form-card-premium">
            <div class="form-header">
              <h4><i class="ti ti-plus"></i> Nouveau Jour Férié / Blocage</h4>
            </div>
            <form @submit.prevent="submitGlobalForm" class="form-premium">
              <div class="form-group">
                <label>Nom de l'événement *</label>
                <input v-model="form.global.name" type="text" placeholder="Ex: Noël 2024" required />
              </div>
              <div class="form-group">
                <label>Type *</label>
                <select v-model="form.global.type" required>
                  <option value="">-- Sélectionner --</option>
                  <option v-for="t in globalTypes" :key="t.id" :value="t.code">
                    {{ t.icon }} {{ t.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Début *</label>
                <input v-model="form.global.date_start" type="date" required />
              </div>
              <div class="form-group">
                <label>Fin *</label>
                <input v-model="form.global.date_end" type="date" required />
              </div>
              <button type="submit" class="btn-submit-premium">
                <i class="ti ti-plus"></i> Ajouter
              </button>
            </form>
          </div>

          <!-- TABLEAU GLOBAUX -->
          <div class="table-wrapper-premium">
            <table class="table-premium">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Type</th>
                  <th>Début</th>
                  <th>Fin</th>
                  <th style="width: 60px;">Jours</th>
                  <th style="width: 100px;">État</th>
                  <th style="width: 100px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!globalUnavailabilities.length" class="table-empty">
                  <td colspan="7">
                    <i class="ti ti-inbox"></i> Aucun jour férié
                  </td>
                </tr>
                <tr v-for="u in globalUnavailabilities" :key="'global-' + u.id">
                  <td class="cell-name"><strong>{{ u.name }}</strong></td>
                  <td>
                    <span class="type-badge" :style="{ backgroundColor: getTypeColor(u.type, 'global') }">
                      {{ getTypeIcon(u.type, 'global') }}
                    </span>
                  </td>
                  <td>{{ formatDate(u.date_start) }}</td>
                  <td>{{ formatDate(u.date_end) }}</td>
                  <td class="cell-center">{{ calculateDays(u.date_start, u.date_end) }}</td>
                  <td>
                    <span :class="['status-badge', u.is_active ? 'status-active' : 'status-inactive']">
                      <i :class="u.is_active ? 'ti ti-circle-filled' : 'ti ti-circle'"></i>
                      {{ u.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td class="cell-actions">
                    <button
                      @click="deleteGlobal(u.id)"
                      class="btn-action-sm delete"
                      title="Supprimer"
                    >
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 4: TYPES PERSONNALISÉS -->
        <div v-show="activeTab === 'types'" class="tab-pane" :class="{ active: activeTab === 'types' }">
          <div class="types-container">
            <!-- CRÉER TYPE GLOBAL -->
            <div class="type-form-card">
              <div class="form-header">
                <h4><i class="ti ti-plus"></i> Type Global</h4>
                <p>Jours fériés et blocages globaux</p>
              </div>
              <form @submit.prevent="createType('global')" class="form-premium form-types">
                <div class="form-group">
                  <label>Nom du type *</label>
                  <input v-model="typeForm.global.name" type="text" placeholder="Ex: Grève" required />
                </div>
                <div class="form-row-2">
                  <div class="form-group">
                    <label>Icône *</label>
                    <input v-model="typeForm.global.icon" type="text" placeholder="⚡" maxlength="3" required />
                  </div>
                  <div class="form-group">
                    <label>Couleur *</label>
                    <input v-model="typeForm.global.color" type="color" required />
                  </div>
                </div>
                <button type="submit" class="btn-submit-premium w-100">
                  <i class="ti ti-plus"></i> Créer
                </button>
              </form>
            </div>

            <!-- CRÉER TYPE AUDITEUR -->
            <div class="type-form-card">
              <div class="form-header">
                <h4><i class="ti ti-plus"></i> Type Auditeur</h4>
                <p>Absences des auditeurs</p>
              </div>
              <form @submit.prevent="createType('auditor')" class="form-premium form-types">
                <div class="form-group">
                  <label>Nom du type *</label>
                  <input v-model="typeForm.auditor.name" type="text" placeholder="Ex: Vacances" required />
                </div>
                <div class="form-row-2">
                  <div class="form-group">
                    <label>Icône *</label>
                    <input v-model="typeForm.auditor.icon" type="text" placeholder="🏖️" maxlength="3" required />
                  </div>
                  <div class="form-group">
                    <label>Couleur *</label>
                    <input v-model="typeForm.auditor.color" type="color" required />
                  </div>
                </div>
                <button type="submit" class="btn-submit-premium w-100">
                  <i class="ti ti-plus"></i> Créer
                </button>
              </form>
            </div>
          </div>

          <!-- LISTE TYPES -->
          <div class="types-list-section">
            <h4><i class="ti ti-list"></i> Types Créés</h4>
            <div class="types-display">
              <div class="types-column">
                <h5>Types Globaux</h5>
                <div v-for="t in globalTypes" :key="'tg-' + t.id" class="type-item-display">
                  <span class="type-color-dot" :style="{ backgroundColor: t.color }"></span>
                  <span class="type-label">{{ t.icon }} {{ t.name }}</span>
                  <span v-if="t.is_custom" class="badge-custom">Perso</span>
                </div>
              </div>
              <div class="types-column">
                <h5>Types Auditeurs</h5>
                <div v-for="t in auditorTypes" :key="'ta-' + t.id" class="type-item-display">
                  <span class="type-color-dot" :style="{ backgroundColor: t.color }"></span>
                  <span class="type-label">{{ t.icon }} {{ t.name }}</span>
                  <span v-if="t.is_custom" class="badge-custom">Perso</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const page = usePage()

const props = defineProps({
  globalUnavailabilities: { type: Array, default: () => [] },
  auditorUnavailabilities: { type: Array, default: () => [] },
  auditors: { type: Array, default: () => [] },
  globalTypes: { type: Array, default: () => [] },
  auditorTypes: { type: Array, default: () => [] },
})

const currentYear = ref(new Date().getFullYear())
const selectedAuditorId = ref(null)
const activeTab = ref('calendar')
let flashTimeout = null

const form = ref({
  auditor: { auditor_id: null, type: '', date_start: '', date_end: '' },
  global: { name: '', type: '', date_start: '', date_end: '' }
})

const typeForm = ref({
  global: { name: '', icon: '⚡', color: '#dc2626' },
  auditor: { name: '', icon: '🏖️', color: '#15803d' }
})

const tabs = [
  { id: 'calendar', name: 'Calendrier', icon: 'ti ti-calendar-check' },
  { id: 'auditor', name: 'Auditeurs', icon: 'ti ti-users-group' },
  { id: 'global', name: 'Jours Fériés', icon: 'ti ti-flag-2' },
  { id: 'types', name: 'Types', icon: 'ti ti-palette' }
]

const auditorColors = ['#2563eb', '#0891b2', '#7c3aed', '#dc2626', '#15803d', '#f97316', '#6366f1', '#ec4899']
const allTypes = computed(() => [...props.globalTypes, ...props.auditorTypes])

const stats = computed(() => {
  const approved = props.auditorUnavailabilities.filter(u => u.is_approved).length
  const total = props.globalUnavailabilities.length + props.auditorUnavailabilities.length
  return {
    approved,
    pending: props.auditorUnavailabilities.filter(u => !u.is_approved).length,
    total,
    auditors: new Set(props.auditorUnavailabilities.map(u => u.auditor_id)).size
  }
})

// Calendrier
function getMonthName(m) {
  const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
  return months[m - 1]
}

function getDayName(d) {
  const days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
  return days[d - 1]
}

function getDaysInMonth(month) {
  const first = new Date(currentYear.value, month - 1, 1)
  const last = new Date(currentYear.value, month, 0)
  const start = first.getDay() === 0 ? 7 : first.getDay()
  const days = []
  for (let i = 1; i < start; i++) days.push(null)
  for (let i = 1; i <= last.getDate(); i++) days.push(i)
  return days
}

function dateToString(y, m, d) {
  return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`
}

function isDateInRange(check, start, end) {
  const c = new Date(check).getTime()
  const s = new Date(start).getTime()
  const e = new Date(end).getTime()
  return c >= s && c <= e
}

function hasEvent(month, day) {
  if (!day) return false
  const date = dateToString(currentYear.value, month, day)
  return (
    props.globalUnavailabilities.some(u => isDateInRange(date, u.date_start, u.date_end) && u.is_active) ||
    props.auditorUnavailabilities.some(u => {
      if (selectedAuditorId.value && u.auditor_id !== selectedAuditorId.value) return false
      return isDateInRange(date, u.date_start, u.date_end)
    })
  )
}

function getEventIcon(month, day) {
  if (!day) return ''
  const date = dateToString(currentYear.value, month, day)
  const global = props.globalUnavailabilities.find(u => isDateInRange(date, u.date_start, u.date_end) && u.is_active)
  if (global) {
    const type = props.globalTypes.find(t => t.code === global.type)
    return type?.icon || '📌'
  }
  const auditor = props.auditorUnavailabilities.find(u => {
    if (selectedAuditorId.value && u.auditor_id !== selectedAuditorId.value) return false
    return isDateInRange(date, u.date_start, u.date_end)
  })
  if (auditor && auditor.auditor) {
    return (auditor.auditor.code || (auditor.auditor.first_name?.charAt(0) + auditor.auditor.last_name?.charAt(0))).toUpperCase()
  }
  return ''
}

function getDayStylePremium(month, day) {
  if (!day) return {}
  const date = dateToString(currentYear.value, month, day)

  const global = props.globalUnavailabilities.find(u => isDateInRange(date, u.date_start, u.date_end) && u.is_active)
  if (global) {
    const type = props.globalTypes.find(t => t.code === global.type)
    if (type) return { backgroundColor: type.color, color: '#fff', fontWeight: '600' }
  }

  const auditor = props.auditorUnavailabilities.find(u => {
    if (selectedAuditorId.value && u.auditor_id !== selectedAuditorId.value) return false
    return isDateInRange(date, u.date_start, u.date_end)
  })
  if (auditor) {
    const type = props.auditorTypes.find(t => t.code === auditor.type)
    if (type) return { backgroundColor: type.color, color: '#fff', fontWeight: '600' }
  }
  return {}
}

function getDayCellClasses(month, day) {
  if (!day) return 'empty'
  const date = dateToString(currentYear.value, month, day)
  const dayOfWeek = new Date(date).getDay()
  return {
    'has-event': hasEvent(month, day),
    'is-weekend': dayOfWeek === 0 || dayOfWeek === 6
  }
}

function getDayTooltip(month, day) {
  if (!day) return ''
  const date = dateToString(currentYear.value, month, day)
  const global = props.globalUnavailabilities.find(u => isDateInRange(date, u.date_start, u.date_end) && u.is_active)
  const auditor = props.auditorUnavailabilities.find(u => {
    if (selectedAuditorId.value && u.auditor_id !== selectedAuditorId.value) return false
    return isDateInRange(date, u.date_start, u.date_end)
  })
  let text = `${day}/${month}/${currentYear.value}`
  if (global) text += `\n📌 ${global.name}`
  if (auditor) text += `\n👤 ${auditor.auditor?.first_name}`
  return text
}

function getAuditorColor(auditorId) {
  return auditorColors[(auditorId - 1) % auditorColors.length]
}

function getTypeColor(typeCode, category) {
  const types = category === 'global' ? props.globalTypes : props.auditorTypes
  return types.find(t => t.code === typeCode)?.color || '#667eea'
}

function getTypeIcon(typeCode, category) {
  const types = category === 'global' ? props.globalTypes : props.auditorTypes
  return types.find(t => t.code === typeCode)?.icon || '📌'
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function calculateDays(start, end) {
  if (!start || !end) return 0
  const s = new Date(start)
  const e = new Date(end)
  return Math.ceil((e - s) / (1000 * 60 * 60 * 24)) + 1
}

// Actions
function prevYear() { currentYear.value-- }
function nextYear() { currentYear.value++ }

function submitAuditorForm() {
  if (!form.value.auditor.auditor_id || !form.value.auditor.type || !form.value.auditor.date_start || !form.value.auditor.date_end) {
    alert('Veuillez remplir tous les champs')
    return
  }
  router.post(route('param.projects.unavailabilities.store-auditor'), form.value.auditor, {
    onSuccess: () => {
      form.value.auditor = { auditor_id: null, type: '', date_start: '', date_end: '' }
    }
  })
}

function submitGlobalForm() {
  if (!form.value.global.name || !form.value.global.type || !form.value.global.date_start || !form.value.global.date_end) {
    alert('Veuillez remplir tous les champs')
    return
  }
  router.post(route('param.projects.unavailabilities.store-global'), form.value.global, {
    onSuccess: () => {
      form.value.global = { name: '', type: '', date_start: '', date_end: '' }
    }
  })
}

function createType(category) {
  const typeData = category === 'global' ? typeForm.value.global : typeForm.value.auditor
  if (!typeData.name || !typeData.icon || !typeData.color) {
    alert('Veuillez remplir tous les champs')
    return
  }
  router.post(route('param.projects.unavailabilities.create-type'), {
    category,
    ...typeData
  }, {
    onSuccess: () => {
      typeForm.value[category] = { name: '', icon: category === 'global' ? '⚡' : '🏖️', color: category === 'global' ? '#dc2626' : '#15803d' }
    }
  })
}

function approveAuditor(id) {
  if (confirm('Êtes-vous sûr? Cette action est irréversible.')) {
    router.post(route('param.projects.unavailabilities.approve-auditor', id))
  }
}

function deleteAuditor(id) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette indisponibilité?')) {
    router.delete(route('param.projects.unavailabilities.destroy-auditor', id))
  }
}

function deleteGlobal(id) {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce jour férié?')) {
    router.delete(route('param.projects.unavailabilities.destroy-global', id))
  }
}

function exportExcel() {
  window.location.href = route('param.projects.unavailabilities.export-excel')
}

function exportPdf() {
  window.location.href = route('param.projects.unavailabilities.export-pdf')
}

function clearFlash() {
  if (flashTimeout) clearTimeout(flashTimeout)
  flashTimeout = setTimeout(() => {
    page.props.flash = {}
  }, 5000)
}
</script>

<style scoped>
:root {
  --primary: #1e40af;
  --primary-light: #3b82f6;
  --primary-dark: #1e3a8a;
  --accent-green: #15803d;
  --accent-red: #dc2626;
  --accent-orange: #d97706;
  --text-dark: #1f2937;
  --text-light: #6b7280;
  --bg-primary: #f9fafb;
  --bg-secondary: #ffffff;
  --border-color: #e5e7eb;
  --border-light: #f3f4f6;
}

* { box-sizing: border-box; }

.audit-container {
  padding: 2rem;
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  min-height: 100vh;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* HEADER */
.audit-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-bottom: 1rem;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.icon-badge {
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.75rem;
  flex-shrink: 0;
}

.header-title h1 {
  margin: 0;
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--text-dark);
  letter-spacing: -0.5px;
}

.header-title p {
  margin: 0.5rem 0 0;
  font-size: 0.875rem;
  color: var(--text-light);
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.btn-action {
  padding: 0.75rem 1.25rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.btn-action.excel {
  background: #059669;
  color: white;
}

.btn-action.excel:hover {
  background: #047857;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.btn-action.pdf {
  background: #dc2626;
  color: white;
}

.btn-action.pdf:hover {
  background: #b91c1c;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

/* ALERT */
.alert-success {
  background: #ecfdf5;
  border-left: 4px solid var(--accent-green);
  color: #065f46;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.9rem;
  animation: slideDown 0.3s ease;
}

.alert-success i { font-size: 1.25rem; }

.btn-close {
  margin-left: auto;
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* KPI CARDS */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.kpi-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border-left: 4px solid;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.3s;
}

.kpi-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.kpi-value {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text-dark);
  line-height: 1;
  margin-bottom: 0.5rem;
}

.kpi-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-light);
  margin-bottom: 0.25rem;
}

.kpi-sublabel {
  font-size: 0.75rem;
  color: #9ca3af;
}

/* TABS PREMIUM */
.tabs-premium {
  background: white;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.tabs-list {
  display: flex;
  padding: 0;
  margin: 0;
  list-style: none;
  border-bottom: 2px solid var(--border-color);
}

.tab-item {
  flex: 1;
  padding: 1rem 1.5rem;
  border: none;
  background: none;
  color: var(--text-light);
  font-weight: 600;
  cursor: pointer;
  position: relative;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.95rem;
}

.tab-item:hover {
  color: var(--primary);
  background: var(--bg-primary);
}

.tab-item.active {
  color: var(--primary);
}

.tab-item.active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--primary);
  border-radius: 3px 3px 0 0;
}

.tab-badge {
  background: var(--primary);
  color: white;
  font-size: 0.7rem;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  margin-left: 0.5rem;
}

.tabs-line {
  background: var(--border-color);
  height: 2px;
}

/* ONGLETS CONTENU */
.tabs-content-area {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.tab-pane {
  display: none;
}

.tab-pane.active {
  display: block;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* CALENDRIER */
.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid var(--border-color);
}

.calendar-nav {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.btn-nav-year {
  width: 40px;
  height: 40px;
  border: 1px solid var(--border-color);
  background: white;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  color: var(--text-dark);
}

.btn-nav-year:hover {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.year-display {
  font-size: 1.5rem;
  font-weight: 700;
  min-width: 80px;
  text-align: center;
  color: var(--text-dark);
}

.calendar-filter {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.calendar-filter label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-dark);
  white-space: nowrap;
}

.select-premium {
  padding: 0.625rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background: white;
  color: var(--text-dark);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 250px;
}

.select-premium:hover,
.select-premium:focus {
  border-color: var(--primary);
  outline: none;
  box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
}

/* CALENDRIER GRID */
.calendar-grid-12 {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.month-card-premium {
  background: white;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s;
}

.month-card-premium:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.month-header-premium {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
  padding: 0.875rem;
  text-align: center;
}

.month-header-premium h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.month-grid-premium {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  padding: 0.75rem;
  background: white;
}

.day-name {
  text-align: center;
  font-size: 0.65rem;
  font-weight: 800;
  padding: 0.5rem 0;
  color: var(--text-light);
  background: var(--bg-primary);
  border-radius: 4px;
}

.day-cell-premium {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--bg-primary);
  border: 1px solid var(--border-light);
  border-radius: 6px;
  font-size: 0.8rem;
  position: relative;
  cursor: pointer;
  transition: all 0.2s;
}

.day-cell-premium:hover:not(.empty) {
  border-color: var(--primary);
  background: var(--bg-secondary);
  box-shadow: 0 2px 8px rgba(30, 64, 175, 0.1);
}

.day-cell-premium.empty {
  background: transparent;
  border: none;
  cursor: default;
}

.day-cell-premium.is-weekend {
  background: #f0f9ff;
}

.day-cell-premium.has-event {
  border-color: var(--primary);
}

.day-number {
  font-weight: 700;
  color: var(--text-dark);
}

.day-event-badge {
  font-size: 0.7rem;
  margin-top: 1px;
}

.event-icon {
  display: block;
  line-height: 1;
}

/* LÉGENDE */
.legend-premium {
  background: var(--bg-primary);
  padding: 2rem;
  border-radius: 12px;
  display: flex;
  gap: 2rem;
}

.legend-section {
  flex: 1;
}

.legend-section h5 {
  margin: 0 0 1rem;
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-dark);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.legend-item-premium {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem;
  background: white;
  border-radius: 8px;
  font-size: 0.85rem;
  border: 1px solid var(--border-color);
}

.legend-color {
  width: 18px;
  height: 18px;
  border-radius: 6px;
  flex-shrink: 0;
}

.legend-name {
  color: var(--text-dark);
  font-weight: 500;
}

.legend-divider {
  width: 1px;
  background: var(--border-color);
}

/* FORMS */
.form-card-premium {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  margin-bottom: 2rem;
  overflow: hidden;
}

.form-header {
  background: linear-gradient(135deg, var(--primary-dark), var(--primary));
  color: white;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.form-header h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
}

.form-header p {
  margin: 0.25rem 0 0;
  font-size: 0.8rem;
  opacity: 0.9;
}

.form-premium {
  padding: 1.5rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--text-dark);
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.form-group input,
.form-group select {
  padding: 0.75rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  color: var(--text-dark);
  transition: all 0.2s;
  font-weight: 500;
}

.form-group input:hover,
.form-group select:hover {
  border-color: var(--primary-light);
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
}

.btn-submit-premium {
  padding: 0.875rem 1.5rem;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  align-self: flex-end;
}

.btn-submit-premium:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
}

.btn-submit-premium.w-100 {
  align-self: stretch;
}

/* TABLES */
.table-wrapper-premium {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  margin-bottom: 2rem;
}

.table-premium {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.table-premium thead {
  background: var(--bg-primary);
  border-bottom: 2px solid var(--border-color);
}

.table-premium th {
  padding: 1rem 1.25rem;
  text-align: left;
  font-weight: 700;
  color: var(--text-dark);
  white-space: nowrap;
}

.table-premium td {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border-light);
}

.table-premium tbody tr {
  transition: all 0.2s;
}

.table-premium tbody tr:hover {
  background: var(--bg-primary);
}

.table-premium tbody tr.row-approved {
  background: #f0fdf4;
}

.table-empty {
  text-align: center;
  color: var(--text-light);
  padding: 3rem 1.25rem !important;
}

.table-empty i {
  font-size: 2rem;
  opacity: 0.5;
  display: block;
  margin-bottom: 0.5rem;
}

.cell-name {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
}

.auditor-badge {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  flex-shrink: 0;
}

.cell-center {
  text-align: center;
  font-weight: 600;
}

.cell-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.type-badge {
  display: inline-block;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  line-height: 1;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
}

.status-approved {
  background: #dcfce7;
  color: var(--accent-green);
}

.status-pending {
  background: #fef08a;
  color: #854d0e;
}

.status-active {
  background: #dcfce7;
  color: var(--accent-green);
}

.status-inactive {
  background: #e5e7eb;
  color: #4b5563;
}

.btn-action-sm {
  width: 36px;
  height: 36px;
  border: 1px solid var(--border-color);
  background: white;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  color: var(--text-light);
}

.btn-action-sm:hover {
  border-color: var(--primary);
  color: var(--primary);
}

.btn-action-sm.approve:hover {
  background: #dcfce7;
  border-color: var(--accent-green);
  color: var(--accent-green);
}

.btn-action-sm.delete:hover {
  background: #fee2e2;
  border-color: var(--accent-red);
  color: var(--accent-red);
}

/* TYPES */
.types-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.type-form-card {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow: hidden;
}

.form-types {
  padding: 1.5rem;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-row-2 .form-group {
  margin: 0;
}

.types-list-section {
  background: var(--bg-primary);
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.types-list-section h4 {
  margin: 0 0 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--text-dark);
}

.types-display {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
}

.types-column h5 {
  margin: 0 0 1rem;
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-dark);
}

.type-item-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: white;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
}

.type-color-dot {
  width: 20px;
  height: 20px;
  border-radius: 6px;
  flex-shrink: 0;
}

.type-label {
  flex: 1;
  color: var(--text-dark);
  font-weight: 500;
}

.badge-custom {
  background: var(--primary);
  color: white;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: 700;
  white-space: nowrap;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .calendar-grid-12 {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .audit-container {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    gap: 1rem;
  }

  .header-title {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }

  .calendar-grid-12 {
    grid-template-columns: repeat(2, 1fr);
  }

  .calendar-header {
    flex-direction: column;
    gap: 1rem;
  }

  .legend-premium {
    flex-direction: column;
    gap: 1rem;
  }

  .legend-divider {
    width: 100%;
    height: 1px;
  }

  .form-premium {
    grid-template-columns: 1fr;
  }

  .types-display {
    grid-template-columns: 1fr;
  }

  .table-premium {
    font-size: 0.8rem;
  }

  .table-premium th,
  .table-premium td {
    padding: 0.75rem;
  }
}

@media (max-width: 480px) {
  .calendar-grid-12 {
    grid-template-columns: 1fr;
  }

  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .header-title h1 {
    font-size: 1.25rem;
  }

  .tab-item {
    padding: 0.75rem;
    font-size: 0.75rem;
  }
}
</style>