<template>
  <VerticalLayout>
    <div class="container-fluid py-3">

      <!-- En-tête -->
      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0 fw-semibold">
            <i class="ti ti-alert-triangle me-2 text-warning"></i>Incidents
          </h4>
          <small class="text-muted">Actifs en rouge · Bibliothèque en noir</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-outline-info btn-sm" @click="openLinkModal">
            <i class="ti ti-link me-1"></i>Lien de dénonciation
          </button>
          <Link :href="route('risk.core.incident-library.index')" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-books me-1"></i>Bibliothèque ({{ props.stats.total_bibliotheque ?? 0 }})
          </Link>
          <button class="btn btn-primary btn-sm" @click="openCreate">
            <i class="ti ti-plus me-1"></i>Nouvel incident
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
          <div class="stat-card stat-card--warning">
            <i class="ti ti-alert-circle"></i>
            <div>
              <div class="stat-val">{{ props.stats.total_actifs ?? 0 }}</div>
              <div class="stat-lbl">Actifs</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card stat-card--info">
            <i class="ti ti-books"></i>
            <div>
              <div class="stat-val">{{ props.stats.total_bibliotheque ?? 0 }}</div>
              <div class="stat-lbl">Bibliothèque</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card stat-card--success">
            <i class="ti ti-shield-check"></i>
            <div>
              <div class="stat-val">{{ props.stats.total_convertis ?? 0 }}</div>
              <div class="stat-lbl">Convertis</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card stat-card--danger">
            <i class="ti ti-currency-dollar"></i>
            <div>
              <div class="stat-val" style="font-size:.82rem">{{ totalEstimation }}</div>
              <div class="stat-lbl">Estimation</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recherche -->
      <div class="d-flex align-items-center gap-2 mb-2">
        <input v-model="search" type="text"
               class="form-control form-control-sm"
               placeholder="Rechercher…" style="max-width:260px"/>
        <small class="text-muted">{{ filteredIncidents.length }} incident(s)</small>
      </div>

      <!-- Liste -->
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div v-if="!filteredIncidents.length" class="text-center py-5 text-muted">
            <i class="ti ti-inbox d-block fs-1 mb-2 opacity-25"></i>
            Aucun incident
          </div>

          <div v-for="inc in filteredIncidents" :key="inc.id"
               :class="['inc-row', isLibrary(inc) ? 'inc-row--library' : (inc.is_external ? 'inc-row--ext' : 'inc-row--actif')]">

            <!-- Bande colorée gauche -->
            <div class="inc-strip" :style="{ background: rowColor(inc) }"></div>

            <div class="flex-grow-1 overflow-hidden">
              <!-- Code + badges -->
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="inc-code" :style="{ color: rowColor(inc) }">
                  {{ inc.code_incident }}
                </span>
                <!-- Badge statut -->
                <span class="inc-badge"
                      :class="isLibrary(inc) ? 'inc-badge--library' : 'inc-badge--actif'">
                  <i :class="isLibrary(inc) ? 'ti ti-books' : 'ti ti-alert-circle'" class="me-1"></i>
                  {{ isLibrary(inc) ? 'Bibliothèque' : 'Actif' }}
                </span>
                <span v-if="inc.is_external" class="inc-badge inc-badge--ext">
                  <i class="ti ti-world me-1"></i>Externe
                </span>
              </div>

              <!-- Libellé — rouge si actif, noir si bibliothèque -->
              <div class="fw-semibold mt-1"
                   :class="isLibrary(inc) ? 'text-dark' : 'text-danger'">
                {{ inc.libelle }}
              </div>

              <!-- Processus / Activité -->
              <div v-if="inc.process_id || inc.activity_id"
                   class="d-flex gap-3 flex-wrap mt-1">
                <small v-if="processLabel(inc.process_id)" class="text-muted">
                  <i class="ti ti-git-branch me-1"></i>{{ processLabel(inc.process_id) }}
                </small>
                <small v-if="activityLabel(inc.activity_id)" class="text-muted">
                  <i class="ti ti-point me-1"></i>{{ activityLabel(inc.activity_id) }}
                </small>
              </div>

              <!-- Meta -->
              <div class="d-flex gap-3 flex-wrap mt-1">
                <small v-if="inc.source" class="text-muted">
                  <i class="ti ti-map-pin me-1"></i>{{ inc.source }}
                </small>
                <small v-if="inc.date_incident" class="text-muted">
                  <i class="ti ti-calendar me-1"></i>{{ formatDate(inc.date_incident) }}
                </small>
                <small v-if="inc.evaluation_formatee" class="fw-semibold text-danger">
                  <i class="ti ti-currency-dollar me-1"></i>{{ inc.evaluation_formatee }}
                </small>
                <small class="text-muted">
                  <i class="ti ti-clock me-1"></i>{{ inc.created_at }}
                </small>
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex flex-column gap-1 flex-shrink-0">
              <!-- Modifier : toujours visible -->
              <button class="btn btn-outline-primary btn-sm" title="Modifier"
                      @click="openEdit(inc)">
                <i class="ti ti-pencil"></i>
              </button>

              <!-- Vers bibliothèque : UNIQUEMENT si actif -->
              <button v-if="!isLibrary(inc)"
                      class="btn btn-outline-info btn-sm" title="Déplacer en bibliothèque"
                      @click="confirmMoveToLibrary(inc)">
                <i class="ti ti-books"></i>
              </button>

              <!-- Supprimer : toujours visible -->
              <button class="btn btn-outline-danger btn-sm" title="Supprimer"
                      @click="confirmDelete(inc)">
                <i class="ti ti-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL CRÉATION / ÉDITION -->
    <BModal v-model="showModal"
            :title="editMode ? `Modifier — ${currentIncident?.code_incident}` : 'Nouvel incident'"
            size="lg" hide-footer @hidden="resetForm">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-semibold">Libellé <span class="text-danger">*</span></label>
          <input v-model="form.libelle" type="text" class="form-control"
                 :class="{'is-invalid': errors.libelle}"
                 placeholder="Décrivez succinctement l'incident…" maxlength="255"/>
          <div v-if="errors.libelle" class="invalid-feedback">{{ errors.libelle }}</div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Description</label>
          <textarea v-model="form.description" class="form-control" rows="3"
                    placeholder="Détails, contexte, impact observé…"></textarea>
        </div>
        <div class="col-12" v-if="props.entities.length">
          <label class="form-label fw-semibold">Entité concernée</label>
          <select v-model="form.entity_id" class="form-select form-select-sm">
            <option :value="null">— Sélectionner —</option>
            <option v-for="e in props.entities" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </div>
        <div class="col-md-6" v-if="props.processes.length">
          <label class="form-label fw-semibold">Processus concerné</label>
          <select v-model="form.process_id" class="form-select form-select-sm"
                  @change="form.activity_id = null">
            <option :value="null">— Sélectionner —</option>
            <option v-for="p in props.processes" :key="p.id" :value="p.id">
              {{ p.code }} — {{ p.name }}
            </option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Activité concernée</label>
          <select v-model="form.activity_id" class="form-select form-select-sm">
            <option :value="null">— Sélectionner —</option>
            <option v-for="a in filteredActivities" :key="a.id" :value="a.id">
              {{ a.code }} — {{ a.name }}
            </option>
          </select>
          <div v-if="!form.process_id" class="form-text text-muted">
            Choisissez d'abord un processus
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Date de l'incident</label>
          <input v-model="form.date_incident" type="date" class="form-control form-control-sm"/>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Source / Origine</label>
          <input v-model="form.source" type="text" class="form-control form-control-sm"
                 placeholder="Ex : audit interne, signalement…" maxlength="255"/>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Estimation du montant généré</label>
          <div class="input-group">
            <input v-model="form.evaluation_monetaire" type="number"
                   class="form-control form-control-sm"
                   :class="{'is-invalid': errors.evaluation_monetaire}"
                   placeholder="0.00" min="0" step="0.01"/>
            <select v-model="form.devise" class="form-select form-select-sm" style="max-width:120px">
              <option v-for="d in props.devises" :key="d.code" :value="d.code">{{ d.code }}</option>
            </select>
          </div>
          <div class="form-text text-muted">Laissez vide si non quantifiable</div>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
        <button class="btn btn-outline-secondary btn-sm" @click="showModal = false">Annuler</button>
        <button class="btn btn-primary btn-sm" :disabled="submitting" @click="submitForm">
          <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-check me-1"></i>
          {{ editMode ? 'Enregistrer' : "Créer l'incident" }}
        </button>
      </div>
    </BModal>

    <!-- MODAL LIEN DE DÉNONCIATION -->
    <BModal v-model="showLinkModal" title="Liens de dénonciation" size="lg" hide-footer>
      <p class="text-muted small mb-3">
        Générez un lien public pour signaler un incident anonymement.
      </p>
      <div v-if="props.reporterLinks.length" class="mb-3">
        <h6 class="fw-semibold mb-2">Liens actifs</h6>
        <div v-for="l in props.reporterLinks" :key="l.id" class="p-2 rounded mb-2 border">
          <div class="fw-semibold small mb-1">
            <i class="ti ti-building me-1 text-muted"></i>{{ l.entity_name }}
          </div>
          <div class="d-flex align-items-start gap-2">
            <code class="small text-primary flex-grow-1" style="word-break:break-all">
              {{ reporterUrl(l.token) }}
            </code>
            <button class="btn btn-sm flex-shrink-0"
                    :class="copiedToken === l.token ? 'btn-success' : 'btn-outline-primary'"
                    @click="copyLink(l.token)">
              <i :class="copiedToken === l.token ? 'ti ti-check' : 'ti ti-copy'" class="me-1"></i>
              {{ copiedToken === l.token ? 'Copié !' : 'Copier' }}
            </button>
          </div>
          <div v-if="clipboardError === l.token" class="mt-2">
            <small class="text-warning d-block mb-1">
              <i class="ti ti-alert-triangle me-1"></i>Sélectionnez et copiez manuellement :
            </small>
            <input type="text" class="form-control form-control-sm font-monospace"
                   :value="reporterUrl(l.token)" readonly @focus="e => e.target.select()"/>
          </div>
          <small v-if="l.expires_at" class="text-muted d-block mt-1">
            <i class="ti ti-clock me-1"></i>Expire le {{ formatDate(l.expires_at) }}
          </small>
        </div>
      </div>
      <div v-else class="text-center text-muted py-3 mb-3">
        <i class="ti ti-link-off d-block fs-3 opacity-25 mb-1"></i>Aucun lien actif
      </div>
      <h6 class="fw-semibold mb-2">Générer un nouveau lien</h6>
      <div class="row g-2">
        <div class="col-12">
          <label class="form-label small fw-semibold">Entité <span class="text-danger">*</span></label>
          <select v-model="linkForm.entity_id" class="form-select form-select-sm">
            <option :value="null">— Sélectionner —</option>
            <option v-for="e in props.entities" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label small fw-semibold">Message d'accueil (optionnel)</label>
          <input v-model="linkForm.label" type="text" class="form-control form-control-sm"
                 placeholder="Ex : Signalez un incident lié à la direction financière…"/>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Date d'expiration (optionnel)</label>
          <input v-model="linkForm.expires_at" type="date" class="form-control form-control-sm"/>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
        <button class="btn btn-outline-secondary btn-sm" @click="showLinkModal = false">Fermer</button>
        <button class="btn btn-info btn-sm text-white"
                :disabled="!linkForm.entity_id || linkSubmitting" @click="submitGenerateLink">
          <span v-if="linkSubmitting" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-link me-1"></i>Générer
        </button>
      </div>
    </BModal>

    <!-- MODAL BIBLIOTHÈQUE -->
    <BModal v-model="showLibraryModal" title="Déplacer vers la bibliothèque" size="sm" hide-footer>
      <p class="mb-3">
        L'incident <strong>{{ targetIncident?.code_incident }}</strong> passera en bibliothèque.
        Il restera visible ici avec une couleur noire.
      </p>
      <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary btn-sm" @click="showLibraryModal = false">Annuler</button>
        <button class="btn btn-info btn-sm text-white" :disabled="submitting" @click="doMoveToLibrary">
          <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-books me-1"></i>Confirmer
        </button>
      </div>
    </BModal>

    <!-- MODAL SUPPRESSION -->
    <BModal v-model="showDeleteModal" title="Supprimer l'incident" size="sm" hide-footer>
      <p class="mb-3">Supprimer <strong>{{ targetIncident?.code_incident }}</strong> ?</p>
      <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary btn-sm" @click="showDeleteModal = false">Annuler</button>
        <button class="btn btn-danger btn-sm" :disabled="submitting" @click="doDelete">
          <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-trash me-1"></i>Supprimer
        </button>
      </div>
    </BModal>

  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { BModal } from 'bootstrap-vue-next'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  incidents:     { type: Array,  default: () => [] },
  devises:       { type: Array,  default: () => [] },
  stats:         { type: Object, default: () => ({}) },
  processes:     { type: Array,  default: () => [] },
  activities:    { type: Array,  default: () => [] },
  entities:      { type: Array,  default: () => [] },
  reporterLinks: { type: Array,  default: () => [] },
})

// ── État ──────────────────────────────────────────────────────────────────────
const search           = ref('')
const showModal        = ref(false)
const showLibraryModal = ref(false)
const showDeleteModal  = ref(false)
const showLinkModal    = ref(false)
const editMode         = ref(false)
const submitting       = ref(false)
const linkSubmitting   = ref(false)
const currentIncident  = ref(null)
const targetIncident   = ref(null)
const copiedToken      = ref(null)
const clipboardError   = ref(null)

const emptyForm = () => ({
  libelle: '', description: '',
  entity_id: null, process_id: null, activity_id: null,
  evaluation_monetaire: null, devise: 'XOF',
  date_incident: '', source: '',
})
const form     = ref(emptyForm())
const errors   = ref({})
const linkForm = ref({ entity_id: null, label: '', expires_at: '' })

// ── Helpers statut ────────────────────────────────────────────────────────────
// Un incident est "bibliothèque" s'il a le statut 'bibliotheque'
const isLibrary = inc => inc.statut === 'bibliotheque'

// Couleur selon statut :
//   actif    → rouge  #ef4444
//   externe  → bleu   #3b82f6
//   biblio   → noir   #1e293b
const rowColor = inc => {
  if (isLibrary(inc))    return '#1e293b'
  if (inc.is_external)   return '#3b82f6'
  return '#ef4444'
}

// ── Computed ──────────────────────────────────────────────────────────────────
// Tous les incidents sont affichés (actifs + bibliothèque)
// Le contrôleur doit renvoyer TOUS les incidents (actifs + bibliothèque)
const filteredIncidents = computed(() => {
  if (!search.value.trim()) return props.incidents
  const q = search.value.toLowerCase()
  return props.incidents.filter(i =>
    i.libelle?.toLowerCase().includes(q) ||
    i.code_incident?.toLowerCase().includes(q) ||
    i.source?.toLowerCase().includes(q)
  )
})

const filteredActivities = computed(() => {
  if (!form.value.process_id) return props.activities
  return props.activities.filter(a => a.process_id === form.value.process_id)
})

const processLabel  = id => id ? (props.processes.find(p => p.id === id)?.name  ?? null) : null
const activityLabel = id => id ? (props.activities.find(a => a.id === id)?.name ?? null) : null

const totalEstimation = computed(() => {
  const total = props.incidents
    .filter(i => i.evaluation_monetaire != null && i.statut === 'actif')
    .reduce((s, i) => s + parseFloat(i.evaluation_monetaire), 0)
  if (total === 0) return 'Non estimé'
  return new Intl.NumberFormat('fr-FR').format(total) + ' XOF'
})

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  const p = d.split('-')
  return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : d
}
function reporterUrl(token) {
  return window.location.origin + '/m/risk.core/report/' + token
}
async function copyLink(token) {
  clipboardError.value = null
  const url = reporterUrl(token)
  if (navigator.clipboard?.writeText) {
    try { await navigator.clipboard.writeText(url); showCopied(token); return } catch (_) {}
  }
  try {
    const ta = document.createElement('textarea')
    ta.value = url; ta.style.cssText = 'position:fixed;left:-9999px;opacity:0'
    document.body.appendChild(ta); ta.focus(); ta.select()
    if (document.execCommand('copy')) { document.body.removeChild(ta); showCopied(token); return }
    document.body.removeChild(ta)
  } catch (_) {}
  clipboardError.value = token
}
function showCopied(t) { copiedToken.value = t; setTimeout(() => { copiedToken.value = null }, 2500) }

// ── Formulaire ────────────────────────────────────────────────────────────────
function resetForm() {
  form.value = emptyForm(); errors.value = {}
  editMode.value = false; currentIncident.value = null
}
function openCreate()    { resetForm(); showModal.value = true }
function openLinkModal() { clipboardError.value = null; showLinkModal.value = true }

function openEdit(inc) {
  editMode.value = true; currentIncident.value = inc
  form.value = {
    libelle:              inc.libelle,
    description:          inc.description          ?? '',
    entity_id:            inc.entity_id            ?? null,
    process_id:           inc.process_id           ?? null,
    activity_id:          inc.activity_id          ?? null,
    evaluation_monetaire: inc.evaluation_monetaire,
    devise:               inc.devise               ?? 'XOF',
    date_incident:        inc.date_incident        ?? '',
    source:               inc.source               ?? '',
  }
  showModal.value = true
}

function confirmMoveToLibrary(i) { targetIncident.value = i; showLibraryModal.value = true }
function confirmDelete(i)        { targetIncident.value = i; showDeleteModal.value  = true }

function submitForm() {
  submitting.value = true; errors.value = {}
  const url    = editMode.value
    ? route('risk.core.incidents.update', currentIncident.value.id)
    : route('risk.core.incidents.store')
  const method = editMode.value ? 'put' : 'post'
  router[method](url, form.value, {
    preserveScroll: true,
    onSuccess: () => { showModal.value = false },
    onError:   e  => { errors.value = e },
    onFinish:  () => { submitting.value = false },
  })
}

function doMoveToLibrary() {
  submitting.value = true
  router.post(route('risk.core.incidents.move-to-library', targetIncident.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => { showLibraryModal.value = false },
    onFinish:  () => { submitting.value = false },
  })
}

function doDelete() {
  submitting.value = true
  router.delete(route('risk.core.incidents.destroy', targetIncident.value.id), {
    preserveScroll: true,
    onSuccess: () => { showDeleteModal.value = false },
    onFinish:  () => { submitting.value = false },
  })
}

function submitGenerateLink() {
  linkSubmitting.value = true
  router.post(route('risk.core.incidents.reporter-link'), linkForm.value, {
    preserveScroll: true,
    onSuccess: () => { linkForm.value = { entity_id: null, label: '', expires_at: '' } },
    onFinish:  () => { linkSubmitting.value = false },
  })
}
</script>

<style scoped>
/* STATS */
.stat-card {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 10px; border: 1px solid transparent;
}
.stat-card i   { font-size: 1.3rem; flex-shrink: 0; }
.stat-val      { font-size: 1.25rem; font-weight: 800; line-height: 1; }
.stat-lbl      { font-size: .65rem; color: #64748b; margin-top: 2px; }
.stat-card--warning { background: #fffbeb; border-color: #fde68a; }
.stat-card--warning i { color: #d97706; }
.stat-card--info    { background: #eff6ff; border-color: #bfdbfe; }
.stat-card--info i  { color: #2563eb; }
.stat-card--success { background: #f0fdf4; border-color: #bbf7d0; }
.stat-card--success i { color: #16a34a; }
.stat-card--danger  { background: #fff1f2; border-color: #fecdd3; }
.stat-card--danger i  { color: #dc2626; }

/* LIGNES */
.inc-row {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 12px 14px 12px 0;
  border-bottom: 1px solid #f1f5f9;
  transition: background .1s;
}
.inc-row:last-child { border-bottom: none; }

/* Actif → fond rouge très léger */
.inc-row--actif           { background: #fff5f5; }
.inc-row--actif:hover     { background: #fee2e2; }

/* Externe → fond bleu très léger */
.inc-row--ext             { background: #eff6ff; }
.inc-row--ext:hover       { background: #dbeafe; }

/* Bibliothèque → fond gris, texte sombre */
.inc-row--library         { background: #f8fafc; }
.inc-row--library:hover   { background: #f1f5f9; }

.inc-strip { width: 4px; align-self: stretch; flex-shrink: 0; }

.inc-code { font-family: monospace; font-size: .75rem; font-weight: 700; }

.inc-badge {
  display: inline-flex; align-items: center;
  font-size: .6rem; font-weight: 600;
  padding: 1px 7px; border-radius: 20px;
}
.inc-badge--actif   { background: #fee2e2; color: #dc2626; }
.inc-badge--library { background: #f1f5f9; color: #1e293b; }
.inc-badge--ext     { background: #dbeafe; color: #1d4ed8; }

/* FORM */
.form-control-sm, .form-select-sm { font-size: .75rem; height: 28px; padding: .18rem .45rem; }
textarea.form-control-sm           { height: auto; }
.btn-sm { font-size: .72rem; padding: .15rem .5rem; }
</style>