<template>
  <div class="report-container">
    <!-- PAGE 1: COUVERTURE ET RÉSUMÉ EXÉCUTIF -->
    <div class="report-page cover-page">
      <div class="cover-content">
        <div class="cover-logo">
          <span class="logo-text">FRUCTIVIA AGRO</span>
        </div>
        <div class="cover-title">
          <h1>RAPPORT DE SUIVI</h1>
          <h2>Plans d'Action et Gestion des Risques</h2>
        </div>
        <div class="cover-meta">
          <div class="meta-item">
            <span class="meta-label">Période</span>
            <span class="meta-value">{{ periodLabel }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Date du rapport</span>
            <span class="meta-value">{{ reportDate }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Établi par</span>
            <span class="meta-value">{{ currentUser }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- PAGE 2: RÉSUMÉ EXÉCUTIF -->
    <div class="report-page">
      <div class="report-header">
        <h1>RÉSUMÉ EXÉCUTIF</h1>
      </div>

      <div class="section">
        <h3 class="section-title">Indicateurs clés</h3>
        <div class="kpi-grid">
          <div class="kpi-box">
            <div class="kpi-number">{{ stats.total }}</div>
            <div class="kpi-label">Actions Total</div>
          </div>
          <div class="kpi-box">
            <div class="kpi-number">{{ completionRate }}%</div>
            <div class="kpi-label">Taux de réalisation</div>
          </div>
          <div class="kpi-box">
            <div class="kpi-number">{{ stats.in_progress }}</div>
            <div class="kpi-label">En cours</div>
          </div>
          <div class="kpi-box">
            <div class="kpi-number">{{ stats.completed }}</div>
            <div class="kpi-label">Complétées</div>
          </div>
          <div class="kpi-box alert">
            <div class="kpi-number">{{ stats.overdue }}</div>
            <div class="kpi-label">En retard</div>
          </div>
          <div class="kpi-box critical">
            <div class="kpi-number">{{ stats.critical }}</div>
            <div class="kpi-label">Critiques</div>
          </div>
        </div>
      </div>

      <div class="section">
        <h3 class="section-title">Synthèse de performance</h3>
        <p class="summary-text">
          Le suivi des plans d'action montre une progression{{ completionRate < 50 ? ' insuffisante' : completionRate < 75 ? ' moyenne' : ' satisfaisante' }}.
          <strong>{{ stats.completed }}</strong> actions ont été complétées sur <strong>{{ stats.total }}</strong>, soit un taux de réalisation de <strong>{{ completionRate }}%</strong>.
        </p>
        <p class="summary-text">
          <strong>{{ stats.overdue }}</strong> action(s) est/sont actuellement en retard. 
          <strong>{{ stats.critical }}</strong> action(s) est/sont marquée(s) comme critique(s).
          L'avancement moyen global est de <strong>{{ avgProgress }}%</strong>.
        </p>
      </div>

      <div class="section">
        <h3 class="section-title">Points d'attention prioritaires</h3>
        <ul class="attention-list">
          <li v-if="stats.critical > 0">
            <strong>{{ stats.critical }} action(s) critique(s)</strong> nécessite(nt) une attention immédiate
          </li>
          <li v-if="stats.overdue > 0">
            <strong>{{ stats.overdue }} action(s) en retard</strong> de plus de 5 jours
          </li>
          <li v-if="avgProgress < 50">
            <strong>Progression insuffisante</strong> : {{ avgProgress }}% en moyenne
          </li>
          <li v-if="stats.pending > 0">
            <strong>{{ stats.pending }} action(s)</strong> pas encore démarrées
          </li>
        </ul>
      </div>
    </div>

    <!-- PAGE 3: TABLEAU DE BORD DÉTAILLÉ -->
    <div class="report-page">
      <div class="report-header">
        <h1>TABLEAU DE BORD DÉTAILLÉ</h1>
      </div>

      <div class="section">
        <h3 class="section-title">Distribution des actions par statut</h3>
        <table class="status-table">
          <thead>
            <tr>
              <th>Statut</th>
              <th style="text-align: center;">Nombre</th>
              <th style="text-align: center;">%</th>
              <th>Barre</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in statuses" :key="s.value">
              <td><strong>{{ s.label }}</strong></td>
              <td style="text-align: center;">{{ countByStatus(s.value) }}</td>
              <td style="text-align: center;">{{ statusPercent(s.value) }}%</td>
              <td>
                <div class="bar-container">
                  <div class="bar-fill" :style="{ width: statusPercent(s.value)+'%', backgroundColor: s.color }"></div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="section">
        <h3 class="section-title">Distribution par priorité</h3>
        <table class="priority-table">
          <thead>
            <tr>
              <th>Priorité</th>
              <th style="text-align: center;">Nombre</th>
              <th style="text-align: center;">Moy. Avancement</th>
              <th style="text-align: center;">Complétées</th>
              <th style="text-align: center;">En retard</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in priorityStats" :key="p.priority" :class="'priority-'+p.priority">
              <td><strong>{{ prioLabel(p.priority) }}</strong></td>
              <td style="text-align: center;">{{ p.count }}</td>
              <td style="text-align: center;">{{ p.avg_progress }}%</td>
              <td style="text-align: center;">{{ p.completed }}</td>
              <td style="text-align: center;">{{ p.overdue }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PAGE 4: ACTIONS CRITIQUES ET RETARDÉES -->
    <div v-if="criticalActions.length" class="report-page">
      <div class="report-header">
        <h1>ACTIONS CRITIQUES ET RETARDÉES</h1>
      </div>

      <div v-for="action in criticalActions.slice(0, 8)" :key="action.id" class="action-detail-box">
        <div class="adb-header">
          <span class="adb-code">{{ action.code }}</span>
          <span class="adb-title">{{ action.title }}</span>
          <span :class="['adb-priority', 'priority-'+action.priority]">{{ prioLabel(action.priority) }}</span>
        </div>
        <div class="adb-grid">
          <div class="adb-item">
            <span class="adb-label">Risque</span>
            <span class="adb-value">{{ action.code_risk }} — {{ truncate(action.risk_libelle, 50) }}</span>
          </div>
          <div class="adb-item">
            <span class="adb-label">Responsable</span>
            <span class="adb-value">{{ action.assigned_to_name || '—' }}</span>
          </div>
          <div class="adb-item">
            <span class="adb-label">Statut</span>
            <span class="adb-value">{{ statLabel(action.status) }}</span>
          </div>
          <div class="adb-item">
            <span class="adb-label">Avancement</span>
            <span class="adb-value">{{ action.progress || 0 }}%</span>
          </div>
          <div class="adb-item">
            <span class="adb-label">Échéance</span>
            <span :class="['adb-value', isOverdue(action)?'overdue':'']">
              {{ fmtDate(action.target_date) }}
              <span v-if="isOverdue(action)" class="delay-info"> (retard: {{ daysBehind(action.target_date) }}j)</span>
            </span>
          </div>
          <div class="adb-item">
            <span class="adb-label">Avancement</span>
            <div class="mini-bar">
              <div class="mini-fill" :style="{ width: (action.progress||0)+'%' }"></div>
            </div>
          </div>
        </div>
        <div v-if="action.description" class="adb-desc">
          <strong>Description:</strong> {{ truncate(action.description, 100) }}
        </div>
      </div>
    </div>

    <!-- PAGE 5: PERFORMANCE PAR RESPONSABLE -->
    <div v-if="responsableStats.length" class="report-page">
      <div class="report-header">
        <h1>PERFORMANCE PAR RESPONSABLE</h1>
      </div>

      <div v-for="resp in responsableStats" :key="resp.user_id" class="resp-card">
        <div class="rc-header">
          <h4>{{ resp.name || 'Non assigné' }}</h4>
          <span class="rc-count">{{ resp.total }} action(s)</span>
        </div>
        <div class="rc-metrics">
          <div class="rcm-item">
            <span class="rcm-label">Complétées</span>
            <span class="rcm-value">{{ resp.completed }}/{{ resp.total }} ({{ resp.completion_rate }}%)</span>
          </div>
          <div class="rcm-item">
            <span class="rcm-label">Avancement moyen</span>
            <span class="rcm-value">{{ resp.avg_progress }}%</span>
          </div>
          <div class="rcm-item">
            <span class="rcm-label">En retard</span>
            <span :class="resp.overdue > 0 ? 'rcm-alert' : ''">{{ resp.overdue }}</span>
          </div>
          <div class="rcm-item">
            <span class="rcm-label">Critiques</span>
            <span :class="resp.critical > 0 ? 'rcm-alert' : ''">{{ resp.critical }}</span>
          </div>
        </div>
        <div class="rc-bar">
          <div class="rb-fill" :style="{ width: resp.completion_rate+'%' }"></div>
        </div>
      </div>
    </div>

    <!-- PAGE 6: RISQUES ASSOCIÉS -->
    <div v-if="risksWithActions.length" class="report-page">
      <div class="report-header">
        <h1>RISQUES ET PLANS D'ACTION</h1>
      </div>

      <div v-for="risk in risksWithActions.slice(0, 10)" :key="risk.id" class="risk-card">
        <div class="rcard-header">
          <span class="rc-code">{{ risk.code_risk }}</span>
          <span class="rc-title">{{ truncate(risk.libelle, 70) }}</span>
          <span v-if="risk.criticality_score" class="rc-score" :style="{ background: risk.zone_color || '#6b7280' }">
            {{ risk.criticality_score }}
          </span>
        </div>
        <div class="rcard-meta">
          <span><strong>Process:</strong> {{ risk.process_name }}</span>
          <span><strong>Activité:</strong> {{ risk.activity_name }}</span>
          <span><strong>Actions:</strong> {{ risk.action_count || 0 }}</span>
        </div>
        <div class="rcard-progress">
          <div class="rcp-bar">
            <div class="rcp-fill" :style="{ width: (risk.actions_avg_progress||0)+'%' }"></div>
          </div>
          <span class="rcp-pct">{{ risk.actions_avg_progress || 0 }}%</span>
        </div>
      </div>
    </div>

    <!-- PAGE 7: RECOMMANDATIONS ET ACTIONS CORRECTIVES -->
    <div class="report-page">
      <div class="report-header">
        <h1>RECOMMANDATIONS</h1>
      </div>

      <div class="section">
        <h3 class="section-title">Actions prioritaires</h3>
        <div class="recommendations">
          <div v-if="stats.critical > 0" class="rec-item critical">
            <span class="rec-icon">⚠️</span>
            <div class="rec-content">
              <div class="rec-title">Traiter les actions critiques</div>
              <p>{{ stats.critical }} action(s) marquée(s) comme critique(s) nécessite(nt) une intervention immédiate pour respecter les délais et maintenir la conformité.</p>
            </div>
          </div>

          <div v-if="stats.overdue > 0" class="rec-item urgent">
            <span class="rec-icon">🔴</span>
            <div class="rec-content">
              <div class="rec-title">Accélérer les actions retardées</div>
              <p>{{ stats.overdue }} action(s) est/sont actuellement en retard. Une revue urgente est nécessaire pour identifier les obstacles et mettre en place des mesures correctives.</p>
            </div>
          </div>

          <div v-if="stats.pending > 0" class="rec-item">
            <span class="rec-icon">📋</span>
            <div class="rec-content">
              <div class="rec-title">Démarrer les actions en attente</div>
              <p>{{ stats.pending }} action(s) pas encore démarrée(s). Assurer que les ressources sont allouées et que les responsables sont engagés pour un démarrage rapide.</p>
            </div>
          </div>

          <div class="rec-item">
            <span class="rec-icon">📊</span>
            <div class="rec-content">
              <div class="rec-title">Renforcer le suivi et la communication</div>
              <p>Mettre en place un suivi hebdomadaire des actions critiques et une communication régulière avec les responsables pour assurer une transparence et une responsabilité continues.</p>
            </div>
          </div>

          <div class="rec-item">
            <span class="rec-icon">👥</span>
            <div class="rec-content">
              <div class="rec-title">Évaluer les ressources par responsable</div>
              <p>Analyser la charge de travail des responsables et redistribuer les actions si nécessaire pour optimiser la performance globale.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PAGE 8: CALENDRIER DE SUIVI -->
    <div class="report-page">
      <div class="report-header">
        <h1>CALENDRIER DE SUIVI</h1>
      </div>

      <div class="section">
        <h3 class="section-title">Actions attendues dans les 30 prochains jours</h3>
        <table class="timeline-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Action</th>
              <th>Échéance</th>
              <th>Responsable</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="action in upcomingActions" :key="action.id">
              <td class="td-code">{{ action.code }}</td>
              <td class="td-title">{{ truncate(action.title, 45) }}</td>
              <td class="td-date">{{ fmtDate(action.target_date) }}</td>
              <td>{{ action.assigned_to_name || '—' }}</td>
              <td><span :class="'stat stat-'+action.status">{{ statLabel(action.status) }}</span></td>
            </tr>
          </tbody>
        </table>
        <div v-if="!upcomingActions.length" class="empty-note">
          Aucune action programmée dans les 30 prochains jours.
        </div>
      </div>

      <div class="section">
        <h3 class="section-title">Dates jalons importantes</h3>
        <div class="milestones">
          <div class="ms-item">
            <span class="ms-date">{{ nextReviewDate }}</span>
            <span class="ms-event">Prochaine revue des plans d'action</span>
          </div>
          <div class="ms-item">
            <span class="ms-date">{{ endOfQuarterDate }}</span>
            <span class="ms-event">Fin de trimestre - Revue de conformité</span>
          </div>
        </div>
      </div>
    </div>

    <!-- PAGE 9: ANNEXES -->
    <div class="report-page">
      <div class="report-header">
        <h1>ANNEXES</h1>
      </div>

      <div class="section">
        <h3 class="section-title">Légende et définitions</h3>
        <div class="legend">
          <div class="leg-item">
            <strong>Statut des actions:</strong>
            <ul class="status-legend">
              <li><span class="stat stat-pending">En attente</span> Action programmée, non démarrée</li>
              <li><span class="stat stat-in_progress">En cours</span> Action en cours de réalisation</li>
              <li><span class="stat stat-review">En révision</span> Action en phase de révision</li>
              <li><span class="stat stat-completed">Complétée</span> Action terminée avec succès</li>
              <li><span class="stat stat-cancelled">Annulée</span> Action annulée/suspendue</li>
            </ul>
          </div>
          <div class="leg-item">
            <strong>Niveaux de priorité:</strong>
            <ul class="priority-legend">
              <li><span class="priority-badge critical">Critique</span> Intervention immédiate requise</li>
              <li><span class="priority-badge high">Haute</span> À traiter dans les 7 jours</li>
              <li><span class="priority-badge medium">Moyenne</span> À traiter dans les 30 jours</li>
              <li><span class="priority-badge low">Basse</span> À traiter selon disponibilité</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="section">
        <h3 class="section-title">Notes</h3>
        <ul class="notes-list">
          <li>Ce rapport a été généré automatiquement par le système FRUCTIVIA AGRO</li>
          <li>Les données présentées reflètent l'état au {{ reportDate }}</li>
          <li>Les actions "en retard" sont celles dont la date cible est dépassée</li>
          <li>L'avancement est exprimé en pourcentage (0-100%)</li>
          <li>Pour plus de détails, consulter le module de suivi des actions</li>
        </ul>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  actionPlans:  { type: Array, default: () => [] },
  allRisks:     { type: Array, default: () => [] },
  stats:        { type: Object, default: () => ({}) },
  priorities:   { type: Array, default: () => [] },
  statuses:     { type: Array, default: () => [] },
  users:        { type: Array, default: () => [] },
})

// Données pour le rapport
const reportDate = new Date().toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' })
const currentUser = ref(document.querySelector('meta[name="user-name"]')?.content || 'Administrateur')
const periodLabel = computed(() => {
  const now = new Date()
  const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
  return `${monthNames[now.getMonth()]} ${now.getFullYear()}`
})

// Helpers
const truncate = (s, n = 50) => s && s.length > n ? s.slice(0, n) + '…' : s || ''

const fmtDate = (d) =>
  d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : null

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

// Computed
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
      const completed = plans.filter(ap => ap.status === 'completed').length
      const overdue = plans.filter(ap => isOverdue(ap)).length
      const avg = Math.round(plans.reduce((s, ap) => s + (ap.progress || 0), 0) / plans.length)
      stats[p.value] = {
        priority: p.value,
        count: plans.length,
        completed,
        overdue,
        avg_progress: avg
      }
    }
  })
  return Object.values(stats)
})

const criticalActions = computed(() => {
  return props.actionPlans
    .filter(p => p.priority === 'critical' || (isOverdue(p) && p.status !== 'completed'))
    .sort((a, b) => new Date(a.target_date) - new Date(b.target_date))
})

const responsableStats = computed(() => {
  const stats = {}
  props.actionPlans.forEach(p => {
    const userId = p.assigned_to || 'unassigned'
    if (!stats[userId]) {
      stats[userId] = {
        user_id: userId,
        name: p.assigned_to_name || 'Non assigné',
        total: 0,
        completed: 0,
        pending: 0,
        in_progress: 0,
        overdue: 0,
        critical: 0,
        sum_progress: 0
      }
    }
    stats[userId].total++
    if (p.status === 'completed') stats[userId].completed++
    if (p.status === 'pending') stats[userId].pending++
    if (p.status === 'in_progress') stats[userId].in_progress++
    if (isOverdue(p)) stats[userId].overdue++
    if (p.priority === 'critical') stats[userId].critical++
    stats[userId].sum_progress += (p.progress || 0)
  })
  
  Object.values(stats).forEach(s => {
    s.completion_rate = s.total ? Math.round((s.completed / s.total) * 100) : 0
    s.avg_progress = s.total ? Math.round(s.sum_progress / s.total) : 0
  })
  
  return Object.values(stats).sort((a, b) => b.completion_rate - a.completion_rate)
})

const risksWithActions = computed(() => {
  const riskMap = {}
  props.actionPlans.forEach(p => {
    if (!riskMap[p.risk_id]) {
      const risk = props.allRisks.find(r => r.id === p.risk_id)
      riskMap[p.risk_id] = {
        ...risk,
        action_count: 0,
        sum_progress: 0,
        actions: []
      }
    }
    riskMap[p.risk_id].action_count++
    riskMap[p.risk_id].sum_progress += (p.progress || 0)
    riskMap[p.risk_id].actions.push(p)
  })
  
  Object.values(riskMap).forEach(r => {
    r.actions_avg_progress = r.action_count ? Math.round(r.sum_progress / r.action_count) : 0
  })
  
  return Object.values(riskMap)
})

const upcomingActions = computed(() => {
  const today = new Date()
  const in30days = new Date(today.getTime() + 30 * 24 * 60 * 60 * 1000)
  
  return props.actionPlans
    .filter(p => p.target_date && new Date(p.target_date) >= today && new Date(p.target_date) <= in30days)
    .sort((a, b) => new Date(a.target_date) - new Date(b.target_date))
})

const nextReviewDate = computed(() => {
  const d = new Date()
  d.setDate(d.getDate() + 7)
  return d.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
})

const endOfQuarterDate = computed(() => {
  const now = new Date()
  const quarter = Math.floor(now.getMonth() / 3)
  const endMonth = quarter * 3 + 2
  const d = new Date(now.getFullYear(), endMonth + 1, 0)
  return d.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' })
})
</script>

<style scoped>
/* ═══ IMPRESSION ═══ */
@media print {
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }
  body { margin: 0; padding: 0; }
  .report-container { margin: 0; padding: 0; }
  .report-page { page-break-after: always; }
}

.report-container {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  color: #1f2937;
  background: white;
  line-height: 1.6;
}

/* ═══ PAGE ═══ */
.report-page {
  width: 210mm;
  height: 297mm;
  padding: 20mm;
  background: white;
  margin: 10px auto;
  box-shadow: 0 2px 8px rgba(0,0,0,.15);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* ═══ COUVERTURE ═══ */
.cover-page {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  color: white;
  padding: 40mm 20mm;
}

.cover-content {
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.cover-logo {
  font-size: 24px;
  font-weight: 800;
  letter-spacing: 2px;
  color: #60a5fa;
}

.cover-title {
  flex-direction: column;
  gap: 20px;
}

.cover-title h1 {
  font-size: 48px;
  font-weight: 900;
  margin: 0;
  line-height: 1.2;
}

.cover-title h2 {
  font-size: 28px;
  font-weight: 300;
  margin: 0;
  color: #cbd5e1;
}

.cover-meta {
  display: flex;
  flex-direction: column;
  gap: 15px;
  padding-top: 30px;
  border-top: 1px solid rgba(255,255,255,.2);
}

.meta-item {
  display: flex;
  justify-content: center;
  gap: 15px;
}

.meta-label {
  font-weight: 600;
  color: #94a3b8;
  min-width: 120px;
  text-align: right;
}

.meta-value {
  color: #f1f5f9;
  font-weight: 500;
}

/* ═══ HEADER ═══ */
.report-header {
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 3px solid #0f172a;
}

.report-header h1 {
  font-size: 28px;
  font-weight: 900;
  margin: 0;
  color: #0f172a;
  letter-spacing: 1px;
  text-transform: uppercase;
}

/* ═══ SECTION ═══ */
.section {
  margin-bottom: 20px;
}

.section-title {
  font-size: 14px;
  font-weight: 800;
  color: #1e293b;
  margin: 0 0 12px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e2e8f0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* ═══ KPIs ═══ */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.kpi-box {
  padding: 15px 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}

.kpi-box.alert {
  background: #fef2f2;
  border-color: #fee2e2;
}

.kpi-box.critical {
  background: #fee2e2;
  border-color: #fca5a5;
}

.kpi-number {
  font-size: 32px;
  font-weight: 900;
  color: #0f172a;
  line-height: 1;
  margin-bottom: 8px;
}

.kpi-label {
  font-size: 11px;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* ═══ TEXTE ═══ */
.summary-text {
  margin: 10px 0;
  font-size: 11px;
  line-height: 1.8;
  color: #334155;
  text-align: justify;
}

.attention-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.attention-list li {
  padding: 8px 12px;
  margin-bottom: 8px;
  background: #f8fafc;
  border-left: 4px solid #3b82f6;
  font-size: 11px;
  line-height: 1.6;
}

.attention-list li strong {
  color: #1d4ed8;
}

/* ═══ TABLES ═══ */
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
  margin: 10px 0;
}

table thead {
  background: #f1f5f9;
  border-bottom: 2px solid #0f172a;
}

table th {
  padding: 8px 6px;
  text-align: left;
  font-weight: 800;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 9px;
}

table td {
  padding: 7px 6px;
  border-bottom: 1px solid #e2e8f0;
}

table tbody tr:hover {
  background: #fafbff;
}

.td-code {
  font-family: monospace;
  font-weight: 700;
  color: #4338ca;
}

.td-title {
  font-weight: 600;
}

.td-date {
  white-space: nowrap;
}

/* ═══ BARRE DE PROGRESSION ═══ */
.bar-container {
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s;
}

.mini-bar {
  width: 60px;
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}

.mini-fill {
  height: 100%;
  background: #3b82f6;
  border-radius: 3px;
}

/* ═══ STATUTS ═══ */
.stat {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 5px;
  font-size: 9px;
  font-weight: 700;
  white-space: nowrap;
}

.stat-pending { background: #f1f5f9; color: #475569; }
.stat-in_progress { background: #dbeafe; color: #1d4ed8; }
.stat-review { background: #ede9fe; color: #7c3aed; }
.stat-completed { background: #dcfce7; color: #16a34a; }
.stat-cancelled { background: #fee2e2; color: #dc2626; }

.priority-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 5px;
  font-size: 9px;
  font-weight: 700;
  white-space: nowrap;
}

.priority-badge.critical { background: #fee2e2; color: #991b1b; }
.priority-badge.high { background: #fef3c7; color: #92400e; }
.priority-badge.medium { background: #dbeafe; color: #1e40af; }
.priority-badge.low { background: #dcfce7; color: #166534; }

/* ═══ CARTES DÉTAIL ═══ */
.action-detail-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 12px;
  page-break-inside: avoid;
}

.adb-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e2e8f0;
}

.adb-code {
  font-family: monospace;
  font-size: 10px;
  font-weight: 800;
  color: #4338ca;
  background: #ede9fe;
  padding: 2px 6px;
  border-radius: 4px;
  white-space: nowrap;
}

.adb-title {
  font-weight: 700;
  color: #0f172a;
  flex: 1;
  font-size: 11px;
}

.adb-priority {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 5px;
  font-size: 9px;
  font-weight: 700;
  white-space: nowrap;
}

.adb-priority.priority-critical { background: #fee2e2; color: #991b1b; }
.adb-priority.priority-high { background: #fef3c7; color: #92400e; }
.adb-priority.priority-medium { background: #dbeafe; color: #1e40af; }

.adb-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 8px;
  font-size: 10px;
}

.adb-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.adb-label {
  font-weight: 700;
  color: #64748b;
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.adb-value {
  color: #0f172a;
  font-weight: 500;
}

.adb-value.overdue {
  color: #dc2626;
  font-weight: 700;
}

.delay-info {
  font-size: 9px;
  color: #dc2626;
}

.adb-desc {
  font-size: 10px;
  color: #475569;
  padding: 8px;
  background: #fff;
  border-radius: 5px;
  border-left: 3px solid #3b82f6;
}

/* ═══ CARTES RESPONSABLE ═══ */
.resp-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 12px;
  page-break-inside: avoid;
}

.rc-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e2e8f0;
}

.rc-header h4 {
  font-size: 12px;
  font-weight: 800;
  margin: 0;
  color: #0f172a;
}

.rc-count {
  background: #e0e7ff;
  color: #4338ca;
  padding: 2px 8px;
  border-radius: 5px;
  font-size: 9px;
  font-weight: 700;
}

.rc-metrics {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-bottom: 10px;
  font-size: 10px;
}

.rcm-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.rcm-label {
  font-weight: 700;
  color: #64748b;
  font-size: 8px;
  text-transform: uppercase;
}

.rcm-value {
  color: #0f172a;
  font-weight: 600;
}

.rcm-alert {
  color: #dc2626;
  font-weight: 700;
}

.rc-bar {
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.rb-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #2563eb);
  border-radius: 4px;
}

/* ═══ CARTES RISQUE ═══ */
.risk-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 10px;
  page-break-inside: avoid;
}

.rcard-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  padding-bottom: 6px;
  border-bottom: 1px solid #e2e8f0;
}

.rc-code {
  font-family: monospace;
  font-size: 9px;
  font-weight: 800;
  color: #4338ca;
  background: #ede9fe;
  padding: 1px 5px;
  border-radius: 4px;
  white-space: nowrap;
}

.rc-title {
  flex: 1;
  font-weight: 600;
  color: #0f172a;
  font-size: 11px;
}

.rc-score {
  color: white;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 5px;
  font-size: 10px;
  white-space: nowrap;
}

.rcard-meta {
  display: flex;
  gap: 15px;
  font-size: 9px;
  margin-bottom: 8px;
  color: #64748b;
}

.rcard-meta strong {
  color: #1e293b;
}

.rcard-progress {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
}

.rcp-bar {
  flex: 1;
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}

.rcp-fill {
  height: 100%;
  background: #22c55e;
  border-radius: 3px;
}

.rcp-pct {
  font-weight: 700;
  color: #22c55e;
  min-width: 30px;
}

/* ═══ RECOMMANDATIONS ═══ */
.recommendations {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.rec-item {
  display: flex;
  gap: 12px;
  padding: 12px;
  border-left: 4px solid #3b82f6;
  background: #f0f9ff;
  border-radius: 6px;
  page-break-inside: avoid;
}

.rec-item.critical {
  border-left-color: #dc2626;
  background: #fef2f2;
}

.rec-item.urgent {
  border-left-color: #f59e0b;
  background: #fffbeb;
}

.rec-icon {
  font-size: 18px;
  flex-shrink: 0;
}

.rec-content {
  flex: 1;
}

.rec-title {
  font-weight: 800;
  color: #0f172a;
  font-size: 11px;
  margin-bottom: 4px;
}

.rec-item p {
  margin: 0;
  font-size: 10px;
  line-height: 1.6;
  color: #475569;
}

/* ═══ LÉGENDE ═══ */
.legend {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.leg-item {
  font-size: 10px;
}

.leg-item strong {
  display: block;
  margin-bottom: 6px;
  color: #0f172a;
}

.status-legend,
.priority-legend {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.status-legend li,
.priority-legend li {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 10px;
}

/* ═══ NOTES ═══ */
.notes-list {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 10px;
  color: #64748b;
}

.notes-list li {
  padding: 5px 0;
  padding-left: 16px;
  position: relative;
}

.notes-list li:before {
  content: '•';
  position: absolute;
  left: 0;
  color: #3b82f6;
  font-weight: bold;
}

/* ═══ CALENDRIER ═══ */
.timeline-table {
  margin: 12px 0;
}

.empty-note {
  padding: 20px;
  text-align: center;
  color: #94a3b8;
  font-size: 11px;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px dashed #e2e8f0;
}

.milestones {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ms-item {
  display: flex;
  gap: 15px;
  padding: 10px 12px;
  background: #f8fafc;
  border-left: 3px solid #3b82f6;
  border-radius: 6px;
  font-size: 10px;
}

.ms-date {
  font-weight: 800;
  color: #1e40af;
  min-width: 100px;
}

.ms-event {
  color: #475569;
  flex: 1;
}

/* ═══ RESPONSIVE ═══ */
@media (max-width: 900px) {
  .report-page { width: 100%; margin: 0; }
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .rc-metrics { grid-template-columns: repeat(2, 1fr); }
}
</style>