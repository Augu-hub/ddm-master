<template>
  <div class="mr-page">
    <!-- ─── En-tête ─── -->
    <header class="mr-header">
      <div class="mr-header__titles">
        <div class="mr-header__icon"><i class="ti ti-clipboard-list"></i></div>
        <div>
          <h1 class="mr-header__title">Demandes de mission d'audit</h1>
          <p class="mr-header__subtitle">Gestion et suivi de toutes les demandes</p>
        </div>
      </div>

      <button class="mr-btn mr-btn--primary" :disabled="generating" @click="generateAndShareLink">
        <i class="ti" :class="generating ? 'ti-loader-2 mr-spin' : 'ti-plus'"></i>
        <span>{{ generating ? 'Génération…' : 'Générer un lien de formulaire' }}</span>
      </button>
    </header>

    <!-- ─── Bandeau de statistiques ─── -->
    <section class="mr-stats">
      <div class="mr-stat">
        <span class="mr-stat__value">{{ missionRequests.total ?? missionRequests.data.length }}</span>
        <span class="mr-stat__label"><i class="ti ti-files"></i> Total demandes</span>
      </div>
      <div v-for="s in statusList" :key="s.key" class="mr-stat" :class="`mr-stat--${s.key}`">
        <span class="mr-stat__value">{{ pageCounts[s.key] || 0 }}</span>
        <span class="mr-stat__label"><i class="ti" :class="s.icon"></i> {{ s.label }}</span>
      </div>
      <p v-if="missionRequests.total > missionRequests.data.length" class="mr-stats__hint">
        <i class="ti ti-info-circle"></i> Compteurs de statut calculés sur la page affichée
      </p>
    </section>

    <!-- ─── Tableau ─── -->
    <section class="mr-card">
      <div class="mr-table-scroll">
        <table class="mr-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Objectif</th>
              <th>Entité</th>
              <th>Générée par</th>
              <th>Remplie par</th>
              <th>Statut</th>
              <th>Dates</th>
              <th class="mr-table__actions-col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="mission in missionRequests.data" :key="mission.id" class="mr-row">
              <td>
                <span class="mr-code">{{ mission.code }}</span>
              </td>

              <td class="mr-objective">
                <span :title="mission.mission_objective">{{ truncate(mission.mission_objective, 46) }}</span>
              </td>

              <td>
                <span class="mr-entity"><i class="ti ti-building"></i> {{ mission.entity?.name || 'N/A' }}</span>
              </td>

              <td>
                <div v-if="mission.requester" class="mr-user">
                  <span class="mr-avatar mr-avatar--blue">{{ initial(mission.requester.name) }}</span>
                  <div class="mr-user__meta">
                    <p class="mr-user__name">{{ mission.requester.name }}</p>
                    <p class="mr-user__mail">{{ mission.requester.email }}</p>
                  </div>
                </div>
                <span v-else class="mr-muted">—</span>
              </td>

              <td>
                <div v-if="mission.filled_by_name" class="mr-user">
                  <span class="mr-avatar mr-avatar--green">{{ initial(mission.filled_by_name) }}</span>
                  <div class="mr-user__meta">
                    <p class="mr-user__name">{{ mission.filled_by_name }}</p>
                    <p class="mr-user__mail">{{ mission.filled_by_email }}</p>
                  </div>
                </div>
                <span v-else class="mr-pill mr-pill--pending"><i class="ti ti-clock"></i> En attente</span>
              </td>

              <td>
                <span class="mr-pill" :class="`mr-pill--${mission.status}`">
                  <i class="ti" :class="statusIcon(mission.status)"></i>
                  {{ statusLabel(mission.status) }}
                </span>
              </td>

              <td>
                <div class="mr-dates">
                  <span class="mr-dates__row">
                    <span class="mr-dates__label">Demande</span>
                    <span class="mr-dates__value">{{ formatDate(mission.requested_date) }}</span>
                  </span>
                  <span v-if="mission.start_date" class="mr-dates__row">
                    <span class="mr-dates__label">Audit</span>
                    <span class="mr-dates__value">{{ formatDate(mission.start_date) }} → {{ formatDate(mission.end_date) }}</span>
                  </span>
                </div>
              </td>

              <td>
                <div class="mr-actions">
                  <a
                    :href="`/m/audit.core/api/audit/mission-requests/${mission.code}`"
                    class="mr-icon-btn"
                    title="Voir la demande"
                  ><i class="ti ti-eye"></i></a>
                  <button
                    v-if="mission.status === 'draft'"
                    class="mr-icon-btn"
                    title="Copier le lien de remplissage"
                    @click="copyShareLink(mission.share_code)"
                  ><i class="ti ti-link"></i></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- État vide -->
      <div v-if="missionRequests.data.length === 0" class="mr-empty">
        <i class="ti ti-clipboard-off mr-empty__icon"></i>
        <p class="mr-empty__msg">Aucune demande de mission pour le moment</p>
        <button class="mr-btn mr-btn--primary" @click="generateAndShareLink">
          <i class="ti ti-plus"></i> Créer la première demande
        </button>
      </div>
    </section>

    <!-- ─── Pagination ─── -->
    <nav v-if="missionRequests.links && missionRequests.links.length > 3" class="mr-pagination">
      <span class="mr-pagination__info">
        Affichage {{ missionRequests.from }}–{{ missionRequests.to }} sur {{ missionRequests.total }}
      </span>
      <div class="mr-pagination__links">
        <a
          v-for="link in missionRequests.links"
          :key="link.label"
          :href="link.url"
          class="mr-page-link"
          :class="{ 'is-active': link.active, 'is-disabled': !link.url }"
          v-html="link.label"
        ></a>
      </div>
    </nav>

    <!-- ─── Modal : lien généré ─── -->
    <transition name="mr-fade">
      <div v-if="showModal" class="mr-overlay" @click="closeModal">
        <div class="mr-modal" @click.stop>
          <div class="mr-modal__head">
            <i class="ti ti-circle-check"></i>
            <h2>Lien de formulaire généré</h2>
          </div>
          <div class="mr-modal__body">
            <div class="mr-note">
              <p>Partagez ce lien avec la personne qui doit créer une nouvelle demande de mission.</p>
              <p class="mr-note__ok"><i class="ti ti-user-check"></i> Vous êtes enregistré comme générateur du lien.</p>
            </div>

            <label class="mr-field-label"><i class="ti ti-link"></i> Lien à partager</label>
            <div class="mr-copy-box">
              <input type="text" :value="generatedLink" readonly class="mr-copy-input" />
              <button class="mr-btn mr-btn--primary mr-btn--sm" @click="copyGeneratedLink">
                <i class="ti ti-copy"></i> Copier
              </button>
            </div>
            <p class="mr-hint"><i class="ti ti-info-circle"></i> Lien valable indéfiniment. Votre nom sera enregistré comme générateur.</p>

            <div class="mr-modal__actions">
              <button class="mr-btn mr-btn--ghost" @click="closeModal">Fermer</button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  missionRequests: Object,
})

const showModal = ref(false)
const generating = ref(false)
const generatedLink = ref('')

const statusList = [
  { key: 'draft',     label: 'Brouillon', icon: 'ti-pencil' },
  { key: 'submitted', label: 'Soumis',    icon: 'ti-send' },
  { key: 'approved',  label: 'Approuvé',  icon: 'ti-circle-check' },
  { key: 'rejected',  label: 'Rejeté',    icon: 'ti-circle-x' },
]

// Compteurs par statut sur la page courante (la pagination limite la portée)
const pageCounts = computed(() => {
  const counts = {}
  for (const m of props.missionRequests?.data ?? []) {
    counts[m.status] = (counts[m.status] || 0) + 1
  }
  return counts
})

const initial = (name) => (name?.trim()?.charAt(0) || '?').toUpperCase()

const truncate = (text, length) =>
  text?.length > length ? text.substring(0, length) + '…' : text

const statusLabel = (status) => ({
  draft: 'Brouillon',
  submitted: 'Soumis',
  approved: 'Approuvé',
  rejected: 'Rejeté',
}[status] || status)

const statusIcon = (status) => ({
  draft: 'ti-pencil',
  submitted: 'ti-send',
  approved: 'ti-circle-check',
  rejected: 'ti-circle-x',
}[status] || 'ti-point')

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const copyShareLink = async (shareCode) => {
  const link = `${window.location.origin}/m/audit.core/api/audit/mission-requests/${shareCode}/fill`
  try {
    await navigator.clipboard.writeText(link)
    alert('Lien de remplissage copié !')
  } catch {
    alert('Erreur lors de la copie')
  }
}

const generateAndShareLink = async () => {
  generating.value = true
  try {
    const response = await fetch('/m/audit.core/api/audit/mission-requests/generate-link', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
    })
    const data = await response.json()
    if (data.success) {
      generatedLink.value = data.form_link
      showModal.value = true
    } else {
      alert('Erreur : ' + (data.error || 'Erreur inconnue'))
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur réseau : ' + error.message)
  } finally {
    generating.value = false
  }
}

const copyGeneratedLink = async () => {
  try {
    await navigator.clipboard.writeText(generatedLink.value)
    alert('Lien copié !')
  } catch {
    alert('Erreur lors de la copie')
  }
}

const closeModal = () => {
  showModal.value = false
}
</script>

<style scoped>
.mr-page {
  --bg: #f4f6fb;
  --surface: #ffffff;
  --border: #e6eaf2;
  --text: #1e293b;
  --muted: #64748b;
  --primary: #4f46e5;
  --primary-dark: #4338ca;
  --primary-soft: #eef2ff;
  --shadow: 0 1px 3px rgba(15, 23, 42, .06), 0 8px 24px rgba(15, 23, 42, .05);

  min-height: 100vh;
  background: var(--bg);
  padding: 1.75rem;
  color: var(--text);
  font-size: .9rem;
}

/* ── Header ── */
.mr-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}
.mr-header__titles { display: flex; align-items: center; gap: 1rem; }
.mr-header__icon {
  width: 48px; height: 48px; border-radius: 12px;
  display: grid; place-items: center;
  background: var(--primary-soft); color: var(--primary);
  font-size: 1.5rem;
}
.mr-header__title { margin: 0; font-size: 1.4rem; font-weight: 700; letter-spacing: -.01em; }
.mr-header__subtitle { margin: .15rem 0 0; color: var(--muted); font-size: .88rem; }

/* ── Boutons ── */
.mr-btn {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .62rem 1.15rem; border-radius: 9px; border: 1px solid transparent;
  font-weight: 600; font-size: .88rem; cursor: pointer;
  transition: all .18s ease; text-decoration: none; white-space: nowrap;
}
.mr-btn--sm { padding: .5rem .9rem; font-size: .82rem; }
.mr-btn--primary { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, .28); }
.mr-btn--primary:hover:not(:disabled) { background: var(--primary-dark); transform: translateY(-1px); }
.mr-btn--primary:disabled { opacity: .65; cursor: not-allowed; box-shadow: none; }
.mr-btn--ghost { background: #fff; color: var(--muted); border-color: var(--border); }
.mr-btn--ghost:hover { background: #f8fafc; color: var(--text); }
.mr-spin { animation: mr-rotate .9s linear infinite; }
@keyframes mr-rotate { to { transform: rotate(360deg); } }

/* ── Stats ── */
.mr-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem; margin-bottom: 1.5rem;
}
.mr-stat {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 12px; padding: 1rem 1.15rem; box-shadow: var(--shadow);
  display: flex; flex-direction: column; gap: .3rem;
}
.mr-stat__value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
.mr-stat__label { display: flex; align-items: center; gap: .35rem; color: var(--muted); font-size: .8rem; font-weight: 500; }
.mr-stat--draft     { border-top: 3px solid #3b82f6; }
.mr-stat--submitted { border-top: 3px solid #f59e0b; }
.mr-stat--approved  { border-top: 3px solid #10b981; }
.mr-stat--rejected  { border-top: 3px solid #ef4444; }
.mr-stats__hint {
  grid-column: 1 / -1; margin: -.25rem 0 0; color: var(--muted);
  font-size: .78rem; display: flex; align-items: center; gap: .35rem;
}

/* ── Card / Table ── */
.mr-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; box-shadow: var(--shadow); overflow: hidden; }
.mr-table-scroll { overflow-x: auto; }
.mr-table { width: 100%; border-collapse: collapse; }
.mr-table thead th {
  text-align: left; padding: .85rem 1rem; font-size: .74rem; letter-spacing: .04em;
  text-transform: uppercase; color: var(--muted); font-weight: 600;
  background: #fafbfe; border-bottom: 1px solid var(--border); white-space: nowrap;
}
.mr-table__actions-col { text-align: right; }
.mr-table tbody td { padding: .8rem 1rem; border-bottom: 1px solid #f1f4f9; vertical-align: middle; }
.mr-row:last-child td { border-bottom: none; }
.mr-row:hover { background: #fafbff; }

.mr-code {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: .82rem; font-weight: 600; color: var(--primary);
  background: var(--primary-soft); padding: .28rem .6rem; border-radius: 6px; white-space: nowrap;
}
.mr-objective { max-width: 260px; font-weight: 500; }
.mr-entity { display: inline-flex; align-items: center; gap: .35rem; color: var(--text); }
.mr-entity .ti { color: var(--muted); }
.mr-muted { color: var(--muted); }

/* utilisateur */
.mr-user { display: flex; align-items: center; gap: .55rem; }
.mr-avatar {
  width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
  display: grid; place-items: center; color: #fff; font-weight: 600; font-size: .85rem;
}
.mr-avatar--blue  { background: linear-gradient(135deg, #6366f1, #4f46e5); }
.mr-avatar--green { background: linear-gradient(135deg, #34d399, #059669); }
.mr-user__meta { min-width: 0; }
.mr-user__name { margin: 0; font-weight: 600; font-size: .84rem; white-space: nowrap; }
.mr-user__mail { margin: 0; color: var(--muted); font-size: .76rem; white-space: nowrap; }

/* pills statut */
.mr-pill {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .28rem .65rem; border-radius: 999px; font-size: .78rem; font-weight: 600; white-space: nowrap;
}
.mr-pill--draft     { background: #eff6ff; color: #2563eb; }
.mr-pill--submitted { background: #fff7ed; color: #d97706; }
.mr-pill--approved  { background: #ecfdf5; color: #059669; }
.mr-pill--rejected  { background: #fef2f2; color: #dc2626; }
.mr-pill--pending   { background: #f1f5f9; color: #64748b; }

/* dates */
.mr-dates { display: flex; flex-direction: column; gap: .25rem; }
.mr-dates__row { display: flex; gap: .5rem; font-size: .8rem; }
.mr-dates__label { color: var(--muted); min-width: 54px; }
.mr-dates__value { font-weight: 600; }

/* actions */
.mr-actions { display: flex; gap: .4rem; justify-content: flex-end; }
.mr-icon-btn {
  width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--border);
  background: #fff; color: var(--muted); display: grid; place-items: center;
  cursor: pointer; transition: all .15s ease; text-decoration: none; font-size: 1.05rem;
}
.mr-icon-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

/* état vide */
.mr-empty { padding: 3.5rem 2rem; text-align: center; }
.mr-empty__icon { font-size: 3rem; color: #cbd5e1; }
.mr-empty__msg { color: var(--muted); font-size: 1.02rem; margin: .75rem 0 1.25rem; }

/* pagination */
.mr-pagination {
  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;
  margin-top: 1.25rem; padding: .9rem 1.15rem;
  background: var(--surface); border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow);
}
.mr-pagination__info { color: var(--muted); font-size: .84rem; }
.mr-pagination__links { display: flex; gap: .35rem; flex-wrap: wrap; }
.mr-page-link {
  min-width: 34px; padding: .4rem .65rem; border: 1px solid var(--border); border-radius: 8px;
  text-decoration: none; color: var(--text); font-size: .82rem; text-align: center; transition: all .15s ease;
}
.mr-page-link:hover:not(.is-disabled):not(.is-active) { background: var(--primary-soft); border-color: var(--primary); color: var(--primary); }
.mr-page-link.is-active { background: var(--primary); border-color: var(--primary); color: #fff; }
.mr-page-link.is-disabled { color: #cbd5e1; cursor: not-allowed; }

/* modal */
.mr-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, .5); backdrop-filter: blur(2px); display: grid; place-items: center; z-index: 1000; padding: 1rem; }
.mr-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 560px; box-shadow: 0 24px 60px rgba(15, 23, 42, .3); overflow: hidden; }
.mr-modal__head { display: flex; align-items: center; gap: .6rem; padding: 1.4rem 1.6rem; background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.mr-modal__head .ti { font-size: 1.4rem; }
.mr-modal__head h2 { margin: 0; font-size: 1.15rem; font-weight: 700; }
.mr-modal__body { padding: 1.6rem; }
.mr-note { background: #f8fafc; border-left: 3px solid #10b981; border-radius: 8px; padding: 1rem 1.15rem; margin-bottom: 1.25rem; }
.mr-note p { margin: 0; color: var(--muted); font-size: .88rem; }
.mr-note__ok { margin-top: .5rem !important; color: #059669 !important; font-weight: 600; display: flex; align-items: center; gap: .35rem; }
.mr-field-label { display: flex; align-items: center; gap: .35rem; font-size: .82rem; font-weight: 600; color: var(--text); margin-bottom: .5rem; }
.mr-copy-box { display: flex; gap: .5rem; }
.mr-copy-input { flex: 1; padding: .65rem .85rem; border: 1.5px solid var(--border); border-radius: 8px; font-family: ui-monospace, monospace; font-size: .8rem; background: #f8fafc; color: var(--text); }
.mr-hint { display: flex; align-items: center; gap: .35rem; color: var(--muted); font-size: .8rem; margin: .75rem 0 0; }
.mr-modal__actions { display: flex; justify-content: flex-end; margin-top: 1.5rem; }

/* transitions */
.mr-fade-enter-active, .mr-fade-leave-active { transition: opacity .2s ease; }
.mr-fade-enter-from, .mr-fade-leave-to { opacity: 0; }

/* responsive */
@media (max-width: 768px) {
  .mr-page { padding: 1rem; }
  .mr-header { flex-direction: column; align-items: flex-start; }
  .mr-btn--primary { width: 100%; justify-content: center; }
}
</style>
