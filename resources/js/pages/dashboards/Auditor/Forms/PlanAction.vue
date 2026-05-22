<template>
  <VerticalLayoutAudit>
    <div class="pa-shell">

      <!-- Topbar -->
      <header class="pa-topbar">
        <div class="pa-topbar__left">
          <a :href="backUrl" class="pa-ib" title="Retour"><i class="ti ti-arrow-left"></i></a>
          <span class="pa-code">PLAN ACTION</span>
        </div>
        <div class="pa-topbar__right">
          <span class="pa-chip"><i class="ti ti-user-check"></i>{{ auditeurNom }}</span>
          <span class="pa-chip"><i class="ti ti-shield-half"></i>{{ auditorRole }}</span>
          <button class="pa-btn pa-btn--refresh" @click="refreshData" :disabled="loading">
            <i class="ti ti-refresh" :class="{ 'pa-spin': loading }"></i> Rafraîchir
          </button>
        </div>
      </header>

      <!-- Dashboard stats -->
      <div class="pa-stats" v-if="stats.length">
        <div v-for="stat in stats" :key="stat.priorite" class="pa-stat-card" :class="`card--${stat.priorite}`">
          <div class="stat-label">{{ stat.priorite.toUpperCase() }}</div>
          <div class="stat-total">{{ stat.total }} actions</div>
          <div class="stat-progress"><div class="stat-progress-bar" :style="{ width: (stat.realisees / stat.total * 100) + '%' }"></div></div>
          <div class="stat-detail">{{ stat.realisees }} / {{ stat.total }} réalisées</div>
          <div v-if="stat.prochaine_echeance" class="stat-ech">
            <i class="ti ti-calendar"></i> Prochaine éch. : {{ formatDate(stat.prochaine_echeance) }}
          </div>
        </div>
      </div>

      <!-- Tableau des actions -->
      <div class="pa-table-wrap">
        <table class="pa-table">
          <thead>
            <tr>
              <th>N° FRAP</th><th>Rubrique</th><th>Problème</th><th>Recommandation</th>
              <th>Priorité</th><th>Statut</th><th>Taux</th><th>Responsable</th><th>Échéance</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="frap in fraps" :key="frap.frap_id" :class="`priority-${frap.priorite}`">
              <td class="pa-num"><button class="pa-frap-link" @click="openFrapDetail(frap)">{{ frap.num_frap }}</button></td>
              <td class="pa-rub">{{ frap.rubrique || '—' }}</td>
              <td class="pa-pb">{{ truncate(frap.probleme, 80) }}</td>
              <td class="pa-reco">{{ truncate(frap.recommandation, 80) }}</td>
              <td>
                <select class="pa-priorite-sel" :value="frap.priorite" @change="updatePriorite(frap, $event.target.value)">
                  <option value="basse">Basse</option>
                  <option value="moyenne">Moyenne</option>
                  <option value="haute">Haute</option>
                  <option value="critique">Critique</option>
                </select>
              </td>
              <td>
                <select class="pa-statut-sel" :value="frap.suivi_statut || 'a_faire'" @change="updateSuivi(frap, 'statut', $event.target.value)">
                  <option value="a_faire">À faire</option>
                  <option value="en_cours">En cours</option>
                  <option value="realise">Réalisé</option>
                  <option value="non_realise">Non réalisé</option>
                  <option value="reporte">Reporté</option>
                </select>
              </td>
              <td>
                <div class="pa-taux-wrap">
                  <input type="range" min="0" max="100" step="5" class="pa-taux-range"
                    :value="frap.taux_realisation || 0" @input="updateSuivi(frap, 'taux_realisation', parseInt($event.target.value))" />
                  <span class="pa-taux-value">{{ frap.taux_realisation || 0 }}%</span>
                </div>
              </td>
              <td class="pa-resp">{{ frap.personne_responsable || '—' }}</td>
              <td class="pa-date">
                <span :class="{ 'date-depassee': frap.alerte_echeance === 'depasse', 'date-proche': frap.alerte_echeance === 'proche' }">
                  {{ formatDate(frap.date_echeance) || '—' }}
                </span>
              </td>
              <td class="pa-actions">
                <button class="pa-action-btn" @click="openCommentModal(frap)" title="Commentaire"><i class="ti ti-message"></i></button>
                <button class="pa-action-btn" @click="openFrapDetail(frap)" title="Détail FRAP"><i class="ti ti-eye"></i></button>
              </td>
            </tr>
            <tr v-if="!fraps.length">
              <td colspan="10" class="pa-empty">Aucune action. Générez des FRAP via les observations (Outil XIV).</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal commentaire -->
      <div v-if="commentModal.visible" class="om-overlay" @click.self="commentModal.visible = false">
        <div class="om-dialog" style="max-width: 500px;">
          <div class="om-hd"><h3>Commentaire de suivi</h3><button @click="commentModal.visible = false">×</button></div>
          <div class="om-body"><textarea class="pa-ta" rows="4" v-model="commentModal.text"></textarea></div>
          <div class="om-ft"><button class="pa-btn pa-btn--ghost" @click="commentModal.visible = false">Annuler</button><button class="om-confirm" @click="saveCommentaire">Enregistrer</button></div>
        </div>
      </div>

      <!-- Modal détail FRAP -->
      <div v-if="frapModal.visible" class="om-overlay" @click.self="frapModal.visible = false">
        <div class="fm-dialog">
          <div class="fm-hd"><div class="fm-hd__left"><i class="ti ti-clipboard-text"></i><div><h2>{{ frapModal.frap?.num_frap }}</h2><div class="fm-sub">{{ frapModal.frap?.rubrique }} · {{ frapModal.frap?.sous_rubrique || '' }}</div></div></div><div class="fm-hd__right"><span class="pa-niv-badge" :style="niveauStyle(frapModal.frap?.niveau_controle_interne)">{{ frapModal.frap?.niveau_controle_interne || '—' }}</span><button @click="frapModal.visible = false">✕</button></div></div>
          <div class="fm-body"><div class="fm-grid"><div class="fm-field fm-field--full"><label>Constat</label><div class="fm-val">{{ frapModal.frap?.fait_constats || '—' }}</div></div><div class="fm-field"><label>Problème</label><div class="fm-val">{{ frapModal.frap?.probleme || '—' }}</div></div><div class="fm-field"><label>Causes</label><div class="fm-val">{{ frapModal.frap?.causes || '—' }}</div></div><div class="fm-field"><label>Impacts</label><div class="fm-val">{{ frapModal.frap?.impacts || '—' }}</div></div><div class="fm-field fm-field--full"><label>Recommandation</label><div class="fm-val fm-val--reco">{{ frapModal.frap?.recommandation || '—' }}</div></div><div class="fm-field"><label>Responsable</label><div class="fm-val">{{ frapModal.frap?.personne_responsable || '—' }}</div></div><div class="fm-field"><label>Échéance</label><div class="fm-val">{{ formatDate(frapModal.frap?.date_echeance) }}</div></div><div class="fm-field"><label>Points forts</label><div class="fm-val">{{ frapModal.frap?.points_forts || '—' }}</div></div></div></div>
          <div class="fm-ft"><button class="pa-btn pa-btn--ghost" @click="frapModal.visible = false">Fermer</button></div>
        </div>
      </div>

      <!-- Toast -->
      <Transition name="toast"><div v-if="toast.show" class="pa-toast" :class="`toast--${toast.type}`"><i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>{{ toast.msg }}<button @click="toast.show = false">✕</button></div></Transition>

    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = defineProps<{
  mission?: any
  auditorRole?: string
  auditeurNom?: string
  missionId?: number
  assignmentId?: number
  backUrl?: string
  urlApiData?: string
  urlApiPriorite?: string
  urlApiSuivi?: string
}>()

// State
const fraps = ref<any[]>([])
const stats = ref<any[]>([])
const loading = ref(false)
const toast = ref({ show: false, type: 'success', msg: '' })
let _tt: ReturnType<typeof setTimeout> | null = null
const commentModal = reactive({ visible: false, frap: null as any, text: '' })
const frapModal = reactive({ visible: false, frap: null as any })

// Helpers
function formatDate(d?: string | null) { return d ? new Date(d).toLocaleDateString('fr-FR') : '' }
function truncate(str: string, len: number) { if (!str) return '—'; return str.length > len ? str.slice(0, len) + '…' : str; }
function csrf() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
function showToast(type: string, msg: string, dur = 4500) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show: true, type, msg }
  _tt = setTimeout(() => { toast.value.show = false }, dur)
}

// Chargement initial
async function refreshData() {
  if (!props.urlApiData) return
  loading.value = true
  try {
    const url = `${props.urlApiData}?mission_id=${props.missionId}&assignment_id=${props.assignmentId}`
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } })
    const data = await res.json()
    if (data.success) {
      fraps.value = data.fraps
      stats.value = data.stats
    } else {
      showToast('error', data.error || 'Erreur chargement')
    }
  } catch (e) {
    showToast('error', 'Erreur réseau')
  } finally {
    loading.value = false
  }
}

// Mise à jour priorité
async function updatePriorite(frap: any, priorite: string) {
  if (!props.urlApiPriorite) return
  const url = props.urlApiPriorite.replace('__FRAP_ID__', frap.frap_id)
  try {
    const res = await fetch(url, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ priorite })
    })
    const data = await res.json()
    if (data.success) {
      frap.priorite = priorite
      showToast('success', 'Priorité mise à jour')
      refreshData() // recharge les stats
    } else {
      showToast('error', data.error || 'Erreur')
    }
  } catch {
    showToast('error', 'Erreur réseau')
  }
}

// Mise à jour suivi (statut, taux, commentaire)
async function updateSuivi(frap: any, field: string, value: any) {
  if (!props.urlApiSuivi) return
  const payload: any = {}
  if (field === 'statut') payload.statut = value
  else if (field === 'taux_realisation') payload.taux_realisation = value
  else if (field === 'commentaire') payload.commentaire = value
  else if (field === 'date_verification') payload.date_verification = value
  if (!Object.keys(payload).length) return
  const url = props.urlApiSuivi.replace('__FRAP_ID__', frap.frap_id)
  try {
    const res = await fetch(url, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(payload)
    })
    const data = await res.json()
    if (data.success) {
      if (field === 'statut') frap.suivi_statut = value
      else if (field === 'taux_realisation') frap.taux_realisation = value
      else if (field === 'commentaire') frap.commentaire = value
      showToast('success', 'Suivi mis à jour')
      refreshData()
    } else {
      showToast('error', data.error || 'Erreur')
    }
  } catch {
    showToast('error', 'Erreur réseau')
  }
}

function openCommentModal(frap: any) {
  commentModal.frap = frap
  commentModal.text = frap.commentaire || ''
  commentModal.visible = true
}

async function saveCommentaire() {
  if (!commentModal.frap) return
  await updateSuivi(commentModal.frap, 'commentaire', commentModal.text)
  commentModal.visible = false
}

function openFrapDetail(frap: any) {
  frapModal.frap = frap
  frapModal.visible = true
}

function niveauStyle(v?: string): Record<string, string> {
  const map: Record<string, any> = {
    'satisfaisant': { bg: '#d1fae5', color: '#065f46' },
    'a_ameliorer':  { bg: '#fef3c7', color: '#92400e' },
    'insuffisant':  { bg: '#fee2e2', color: '#dc2626' },
    'critique':     { bg: '#fce7f3', color: '#9d174d' }
  }
  return map[v?.toLowerCase() || ''] || { background: '#f1f5f9', color: '#475569' }
}

onMounted(() => {
  refreshData()
})
</script>


<style scoped>
.pa-shell { display: flex; flex-direction: column; height: 100vh; background: #f1f5f9; }
.pa-topbar { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 1rem; background: white; border-bottom: 1px solid #e2e8f0; }
.pa-code { background: #0f172a; color: white; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; }
.pa-sdot { width: 8px; height: 8px; border-radius: 50%; }
.sd--draft { background: #94a3b8; }
.sd--in_review { background: #2563eb; }
.sd--validated { background: #16a34a; }
.pa-chip { display: inline-flex; align-items: center; gap: 0.3rem; background: #f1f5f9; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; }
.pa-btn { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.8rem; border-radius: 6px; font-size: 0.75rem; font-weight: 500; border: 1px solid transparent; cursor: pointer; }
.pa-btn--save { background: #0f172a; color: white; }
.pa-btn--submit { background: #2563eb; color: white; }
.pa-btn--validate { background: #10b981; color: white; }
.pa-btn--reject { background: #dc2626; color: white; }
.pa-stats { display: flex; gap: 1rem; padding: 1rem; flex-wrap: wrap; position: relative; }
.pa-stat-card { flex: 1; background: white; border-radius: 10px; padding: 0.75rem; border-left: 4px solid; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.card--critique { border-left-color: #dc2626; }
.card--haute { border-left-color: #f59e0b; }
.card--moyenne { border-left-color: #3b82f6; }
.card--basse { border-left-color: #10b981; }
.stat-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: #64748b; }
.stat-total { font-size: 1.2rem; font-weight: 800; }
.stat-progress { background: #e2e8f0; border-radius: 4px; margin: 0.5rem 0; height: 6px; overflow: hidden; }
.stat-progress-bar { background: var(--blue, #1e40af); height: 6px; }
.stat-detail { font-size: 0.7rem; color: #475569; }
.stat-ech { font-size: 0.7rem; display: flex; align-items: center; gap: 0.3rem; margin-top: 0.3rem; }
.pa-refresh-btn { position: absolute; top: 1rem; right: 1rem; background: white; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.pa-spin { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.pa-table-wrap { overflow-x: auto; margin: 0 1rem 1rem; background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.pa-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
.pa-table th, .pa-table td { padding: 0.6rem 0.5rem; border-bottom: 1px solid #f1f5f9; text-align: left; vertical-align: middle; }
.pa-table th { background: #f8fafc; font-weight: 600; }
tr.priority-critique td { background: #fef2f2; }
tr.priority-haute td { background: #fffbeb; }
tr.priority-moyenne td { background: #eff6ff; }
.pa-frap-link { background: #1e3a5f; color: white; border: none; padding: 0.1rem 0.4rem; border-radius: 4px; cursor: pointer; }
.pa-priorite-sel, .pa-statut-sel { padding: 0.2rem 0.4rem; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 0.7rem; }
.pa-taux-wrap { display: flex; align-items: center; gap: 0.25rem; }
.pa-taux-range { width: 70px; }
.date-depassee { color: #dc2626; font-weight: 600; }
.date-proche { color: #d97706; }
.pa-action-btn { background: none; border: none; font-size: 1rem; cursor: pointer; color: #64748b; margin: 0 0.2rem; }
.pa-empty { text-align: center; padding: 2rem; color: #94a3b8; }
.om-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.om-dialog, .fm-dialog { background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; display: flex; flex-direction: column; overflow: hidden; }
.om-hd, .fm-hd { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #e2e8f0; }
.fm-hd__left { display: flex; align-items: center; gap: 0.5rem; }
.fm-sub { font-size: 0.7rem; color: #64748b; }
.pa-niv-badge { display: inline-block; padding: 0.1rem 0.4rem; border-radius: 20px; font-size: 0.6rem; font-weight: 700; }
.fm-body { flex: 1; overflow: auto; padding: 1rem; }
.fm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.fm-field--full { grid-column: span 2; }
.fm-val { background: #f8fafc; padding: 0.4rem 0.6rem; border-radius: 6px; font-size: 0.75rem; }
.fm-val--reco { background: #eff6ff; border-left: 3px solid #2563eb; }
.om-ft, .fm-ft { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem; border-top: 1px solid #e2e8f0; }
.pa-ta { width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.5rem; font-size: 0.75rem; }
.pa-toast { position: fixed; bottom: 1rem; right: 1rem; display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.75rem; z-index: 2000; }
.toast--success { background: #065f46; color: white; }
.toast--error { background: #dc2626; color: white; }
.toast-enter-active, .toast-leave-active { transition: all 0.2s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>