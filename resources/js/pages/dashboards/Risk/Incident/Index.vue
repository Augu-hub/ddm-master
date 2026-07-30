<template>
  <VerticalLayout>
    <div class="page">

      <!-- HEADER -->
      <div class="page-hdr">
        <div class="hdr-left">
          <div class="hdr-icon"><i class="ti ti-alert-triangle"></i></div>
          <div>
            <h1>Incidents</h1>
            <p>Actifs en rouge · Bibliothèque en gris</p>
          </div>
        </div>
        <div class="hdr-right">
          <button class="btn-ghost" @click="openLinkModal"><i class="ti ti-link"></i> Lien de dénonciation</button>
          <Link :href="route('risk.core.incident-library.index')" class="btn-ghost"><i class="ti ti-books"></i> Bibliothèque ({{ props.stats.total_bibliotheque ?? 0 }})</Link>
          <button class="btn-primary" @click="openCreate"><i class="ti ti-plus"></i> Nouvel incident</button>
        </div>
      </div>

      <div class="body">
        <!-- STATS -->
        <div class="tiles">
          <div class="tile"><div class="t-ic" style="background:#d97706"><i class="ti ti-alert-circle"></i></div><div><div class="t-num">{{ props.stats.total_actifs ?? 0 }}</div><div class="t-lbl">Actifs</div></div></div>
          <div class="tile"><div class="t-ic" style="background:#2563eb"><i class="ti ti-books"></i></div><div><div class="t-num">{{ props.stats.total_bibliotheque ?? 0 }}</div><div class="t-lbl">Bibliothèque</div></div></div>
          <div class="tile"><div class="t-ic" style="background:#16a34a"><i class="ti ti-shield-check"></i></div><div><div class="t-num">{{ props.stats.total_convertis ?? 0 }}</div><div class="t-lbl">Convertis</div></div></div>
          <div class="tile"><div class="t-ic" style="background:#dc2626"><i class="ti ti-currency-dollar"></i></div><div><div class="t-num t-num--sm">{{ totalEstimation }}</div><div class="t-lbl">Estimation (actifs)</div></div></div>
        </div>

        <!-- LISTE -->
        <div class="card">
          <div class="card-hdr">
            <span><i class="ti ti-list"></i> Incidents</span>
            <div class="toolbar">
              <div class="search"><i class="ti ti-search"></i><input v-model="search" type="text" placeholder="Rechercher…"/></div>
              <span class="count">{{ filteredIncidents.length }} incident(s)</span>
            </div>
          </div>

          <div class="card-body">
            <div v-if="!filteredIncidents.length" class="empty"><i class="ti ti-inbox"></i><p>Aucun incident</p></div>

            <div v-for="inc in filteredIncidents" :key="inc.id"
                 :class="['inc-row', isLibrary(inc) ? 'inc-row--library' : (inc.is_external ? 'inc-row--ext' : 'inc-row--actif')]">
              <div class="inc-strip" :style="{ background: rowColor(inc) }"></div>

              <div class="inc-main">
                <div class="inc-top">
                  <span class="inc-code" :style="{ color: rowColor(inc) }">{{ inc.code_incident }}</span>
                  <span class="inc-badge" :class="isLibrary(inc) ? 'inc-badge--library' : 'inc-badge--actif'">
                    <i :class="isLibrary(inc) ? 'ti ti-books' : 'ti ti-alert-circle'"></i>
                    {{ isLibrary(inc) ? 'Bibliothèque' : 'Actif' }}
                  </span>
                  <span v-if="inc.is_external" class="inc-badge inc-badge--ext"><i class="ti ti-world"></i> Externe</span>
                </div>

                <div class="inc-lib" :class="isLibrary(inc) ? 'lib-dark' : 'lib-red'">{{ inc.libelle }}</div>

                <div v-if="inc.process_id || inc.activity_id" class="inc-meta">
                  <small v-if="processLabel(inc.process_id)"><i class="ti ti-git-branch"></i>{{ processLabel(inc.process_id) }}</small>
                  <small v-if="activityLabel(inc.activity_id)"><i class="ti ti-point"></i>{{ activityLabel(inc.activity_id) }}</small>
                </div>
                <div class="inc-meta">
                  <small v-if="inc.source"><i class="ti ti-map-pin"></i>{{ inc.source }}</small>
                  <small v-if="inc.date_incident"><i class="ti ti-calendar"></i>{{ formatDate(inc.date_incident) }}</small>
                  <small v-if="inc.evaluation_formatee" class="cost"><i class="ti ti-currency-dollar"></i>{{ inc.evaluation_formatee }}</small>
                  <small class="muted"><i class="ti ti-clock"></i>{{ inc.created_at }}</small>
                </div>
              </div>

              <div class="inc-actions">
                <button class="ic-btn" title="Modifier" @click="openEdit(inc)"><i class="ti ti-pencil"></i></button>
                <button v-if="!isLibrary(inc)" class="ic-btn ic-info" title="Déplacer en bibliothèque" @click="confirmMoveToLibrary(inc)"><i class="ti ti-books"></i></button>
                <button class="ic-btn ic-danger" title="Supprimer" @click="confirmDelete(inc)"><i class="ti ti-trash"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL CRÉATION / ÉDITION -->
    <BModal v-model="showModal" :title="editMode ? `Modifier — ${currentIncident?.code_incident}` : 'Nouvel incident'" size="lg" hide-footer @hidden="resetForm">
      <div class="mgrid">
        <div class="fld">
          <label>Libellé <span class="req">*</span></label>
          <input v-model="form.libelle" type="text" :class="['inp', errors.libelle ? 'inp--err' : '']" placeholder="Décrivez succinctement l'incident…" maxlength="255"/>
          <div v-if="errors.libelle" class="err">{{ errors.libelle }}</div>
        </div>
        <div class="fld">
          <label>Description</label>
          <textarea v-model="form.description" class="inp" rows="3" placeholder="Détails, contexte, impact observé…"></textarea>
        </div>
        <div class="fld" v-if="props.entities.length">
          <label>Entité concernée</label>
          <select v-model="form.entity_id" class="inp">
            <option :value="null">— Sélectionner —</option>
            <option v-for="e in props.entities" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </div>
        <div class="row2">
          <div class="fld" v-if="props.processes.length">
            <label>Processus concerné</label>
            <select v-model="form.process_id" class="inp" @change="form.activity_id = null">
              <option :value="null">— Sélectionner —</option>
              <option v-for="p in props.processes" :key="p.id" :value="p.id">{{ p.code }} — {{ p.name }}</option>
            </select>
          </div>
          <div class="fld">
            <label>Activité concernée</label>
            <select v-model="form.activity_id" class="inp">
              <option :value="null">— Sélectionner —</option>
              <option v-for="a in filteredActivities" :key="a.id" :value="a.id">{{ a.code }} — {{ a.name }}</option>
            </select>
            <span v-if="!form.process_id" class="hint">Choisissez d'abord un processus</span>
          </div>
        </div>
        <div class="row2">
          <div class="fld">
            <label>Date de l'incident</label>
            <input v-model="form.date_incident" type="date" class="inp"/>
          </div>
          <div class="fld">
            <label>Source / Origine</label>
            <input v-model="form.source" type="text" class="inp" placeholder="Ex : audit interne, signalement…" maxlength="255"/>
          </div>
        </div>
        <div class="fld">
          <label>Estimation du montant généré</label>
          <div class="cost-grp">
            <input v-model="form.evaluation_monetaire" type="number" :class="['inp', errors.evaluation_monetaire ? 'inp--err' : '']" placeholder="0.00" min="0" step="0.01"/>
            <select v-model="form.devise" class="inp cost-devise">
              <option v-for="d in props.devises" :key="d.code" :value="d.code">{{ d.code }}</option>
            </select>
          </div>
          <span class="hint">Laissez vide si non quantifiable</span>
        </div>
      </div>
      <div class="modal-actions">
        <button class="btn-ghost" @click="showModal = false"><i class="ti ti-x"></i> Annuler</button>
        <button class="btn-primary" :disabled="submitting" @click="submitForm">
          <i :class="submitting ? 'ti ti-loader-2 spin' : 'ti ti-check'"></i>
          {{ editMode ? 'Enregistrer' : "Créer l'incident" }}
        </button>
      </div>
    </BModal>

    <!-- MODAL LIEN DE DÉNONCIATION -->
    <BModal v-model="showLinkModal" title="Liens de dénonciation" size="lg" hide-footer>
      <p class="modal-note">Générez un lien public pour signaler un incident anonymement.</p>
      <div v-if="props.reporterLinks.length" class="mb-block">
        <h6 class="mtitle">Liens actifs</h6>
        <div v-for="l in props.reporterLinks" :key="l.id" class="link-item">
          <div class="link-ent"><i class="ti ti-building"></i>{{ l.entity_name }}</div>
          <div class="link-row">
            <code class="link-url">{{ reporterUrl(l.token) }}</code>
            <button class="btn-copy" :class="copiedToken === l.token ? 'ok' : ''" @click="copyLink(l.token)">
              <i :class="copiedToken === l.token ? 'ti ti-check' : 'ti ti-copy'"></i>
              {{ copiedToken === l.token ? 'Copié !' : 'Copier' }}
            </button>
          </div>
          <div v-if="clipboardError === l.token" class="clip-err">
            <span><i class="ti ti-alert-triangle"></i> Sélectionnez et copiez manuellement :</span>
            <input type="text" class="inp" :value="reporterUrl(l.token)" readonly @focus="e => e.target.select()"/>
          </div>
          <small v-if="l.expires_at" class="hint"><i class="ti ti-clock"></i> Expire le {{ formatDate(l.expires_at) }}</small>
        </div>
      </div>
      <div v-else class="empty"><i class="ti ti-link-off"></i><p>Aucun lien actif</p></div>

      <h6 class="mtitle">Générer un nouveau lien</h6>
      <div class="mgrid">
        <div class="fld">
          <label>Entité <span class="req">*</span></label>
          <select v-model="linkForm.entity_id" class="inp">
            <option :value="null">— Sélectionner —</option>
            <option v-for="e in props.entities" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </div>
        <div class="fld">
          <label>Message d'accueil (optionnel)</label>
          <input v-model="linkForm.label" type="text" class="inp" placeholder="Ex : Signalez un incident lié à la direction financière…"/>
        </div>
        <div class="fld" style="max-width:220px">
          <label>Date d'expiration (optionnel)</label>
          <input v-model="linkForm.expires_at" type="date" class="inp"/>
        </div>
      </div>
      <div class="modal-actions">
        <button class="btn-ghost" @click="showLinkModal = false">Fermer</button>
        <button class="btn-primary btn-info" :disabled="!linkForm.entity_id || linkSubmitting" @click="submitGenerateLink">
          <i :class="linkSubmitting ? 'ti ti-loader-2 spin' : 'ti ti-link'"></i> Générer
        </button>
      </div>
    </BModal>

    <!-- MODAL BIBLIOTHÈQUE -->
    <BModal v-model="showLibraryModal" title="Déplacer vers la bibliothèque" size="sm" hide-footer>
      <p class="modal-note">L'incident <strong>{{ targetIncident?.code_incident }}</strong> passera en bibliothèque. Il restera visible ici en gris.</p>
      <div class="modal-actions">
        <button class="btn-ghost" @click="showLibraryModal = false">Annuler</button>
        <button class="btn-primary btn-info" :disabled="submitting" @click="doMoveToLibrary">
          <i :class="submitting ? 'ti ti-loader-2 spin' : 'ti ti-books'"></i> Confirmer
        </button>
      </div>
    </BModal>

    <!-- MODAL SUPPRESSION -->
    <BModal v-model="showDeleteModal" title="Supprimer l'incident" size="sm" hide-footer>
      <p class="modal-note">Supprimer <strong>{{ targetIncident?.code_incident }}</strong> ?</p>
      <div class="modal-actions">
        <button class="btn-ghost" @click="showDeleteModal = false">Annuler</button>
        <button class="btn-primary btn-danger" :disabled="submitting" @click="doDelete">
          <i :class="submitting ? 'ti ti-loader-2 spin' : 'ti ti-trash'"></i> Supprimer
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
const isLibrary = inc => inc.statut === 'bibliotheque'

const rowColor = inc => {
  if (isLibrary(inc))    return '#1e293b'
  if (inc.is_external)   return '#3b82f6'
  return '#ef4444'
}

// ── Computed ──────────────────────────────────────────────────────────────────
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
.page{background:#f0f4f8;min-height:calc(100vh - 60px);font-family:'Inter',system-ui,sans-serif;font-size:13px;color:#1e293b;padding-bottom:30px;}

/* HEADER */
.page-hdr{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 22px;background:#0f172a;flex-wrap:wrap;}
.hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:21px;color:#fff;background:linear-gradient(135deg,#d97706,#f59e0b);flex-shrink:0;}
.page-hdr h1{font-size:16px;font-weight:800;color:#f1f5f9;margin:0;}
.page-hdr p{font-size:11px;color:#64748b;margin:0;}
.hdr-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.btn-primary{display:inline-flex;align-items:center;gap:6px;padding:8px 15px;background:#4f46e5;color:#fff;border:none;border-radius:9px;font-size:12.5px;font-weight:700;cursor:pointer;}
.btn-primary:hover:not(:disabled){background:#4338ca;}
.btn-primary:disabled{opacity:.55;cursor:not-allowed;}
.btn-primary.btn-info{background:#0891b2;}.btn-primary.btn-info:hover:not(:disabled){background:#0e7490;}
.btn-primary.btn-danger{background:#dc2626;}.btn-primary.btn-danger:hover:not(:disabled){background:#b91c1c;}
.btn-ghost{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;background:rgba(255,255,255,.07);color:#c8d6e5;border:1px solid rgba(255,255,255,.12);border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;}
.btn-ghost:hover{background:rgba(255,255,255,.14);}

.body{padding:16px 22px;}

/* TILES */
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:14px;}
.tile{display:flex;align-items:center;gap:11px;background:#fff;border:1px solid #e9eef5;border-radius:12px;padding:12px 14px;}
.t-ic{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;}
.t-num{font-size:19px;font-weight:900;color:#0f172a;line-height:1;}
.t-num--sm{font-size:13px;}
.t-lbl{font-size:10px;color:#64748b;font-weight:600;margin-top:3px;}

/* CARD + LIST */
.card{background:#fff;border:1px solid #e9eef5;border-radius:14px;overflow:hidden;}
.card-hdr{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 16px;border-bottom:1px solid #eef2f7;font-size:12px;font-weight:700;color:#334155;flex-wrap:wrap;}
.card-hdr>span{display:flex;align-items:center;gap:7px;}
.card-hdr i{color:#4f46e5;}
.toolbar{display:flex;align-items:center;gap:10px;}
.search{position:relative;}
.search i{position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;}
.search input{padding:6px 10px 6px 30px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;background:#f8fafc;width:220px;}
.count{font-size:11px;color:#94a3b8;font-weight:600;}
.card-body{padding:0;}
.empty{display:flex;flex-direction:column;align-items:center;gap:6px;padding:44px;color:#94a3b8;}
.empty i{font-size:34px;opacity:.3;}
.empty p{margin:0;font-size:13px;}

.inc-row{display:flex;align-items:stretch;gap:12px;padding:12px 14px 12px 0;border-bottom:1px solid #f1f5f9;transition:background .1s;}
.inc-row:last-child{border-bottom:none;}
.inc-row--actif{background:#fff7f7;}.inc-row--actif:hover{background:#fee2e2;}
.inc-row--ext{background:#f5f9ff;}.inc-row--ext:hover{background:#dbeafe;}
.inc-row--library{background:#f8fafc;}.inc-row--library:hover{background:#f1f5f9;}
.inc-strip{width:4px;flex-shrink:0;border-radius:0 3px 3px 0;}
.inc-main{flex:1;min-width:0;padding:2px 0;}
.inc-top{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.inc-code{font-family:monospace;font-size:11.5px;font-weight:700;}
.inc-badge{display:inline-flex;align-items:center;gap:4px;font-size:9.5px;font-weight:700;padding:2px 8px;border-radius:20px;}
.inc-badge--actif{background:#fee2e2;color:#dc2626;}
.inc-badge--library{background:#f1f5f9;color:#334155;}
.inc-badge--ext{background:#dbeafe;color:#1d4ed8;}
.inc-lib{font-weight:700;margin-top:4px;font-size:13px;}
.lib-red{color:#dc2626;}.lib-dark{color:#0f172a;}
.inc-meta{display:flex;gap:14px;flex-wrap:wrap;margin-top:3px;}
.inc-meta small{font-size:11px;color:#64748b;display:flex;align-items:center;gap:4px;}
.inc-meta .cost{color:#dc2626;font-weight:700;}
.inc-meta .muted{color:#94a3b8;}
.inc-actions{display:flex;flex-direction:column;gap:6px;flex-shrink:0;padding:2px 2px 2px 0;}
.ic-btn{width:30px;height:30px;border:1px solid #e2e8f0;background:#fff;border-radius:8px;cursor:pointer;color:#64748b;display:flex;align-items:center;justify-content:center;font-size:14px;}
.ic-btn:hover{background:#eef2ff;border-color:#c7d2fe;color:#4f46e5;}
.ic-info:hover{background:#cffafe;border-color:#67e8f9;color:#0891b2;}
.ic-danger:hover{background:#fee2e2;border-color:#fca5a5;color:#dc2626;}

/* MODAL CONTENT (dans BModal) */
.mgrid{display:flex;flex-direction:column;gap:14px;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media(max-width:560px){.row2{grid-template-columns:1fr;}}
.fld{display:flex;flex-direction:column;gap:5px;min-width:0;}
.fld label{font-size:11.5px;font-weight:700;color:#475569;}
.req{color:#dc2626;}
.inp{width:100%;padding:8px 11px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;font-family:inherit;color:#0f172a;background:#fff;}
textarea.inp{resize:vertical;line-height:1.5;}
.inp:focus{outline:none;border-color:#a5b4fc;box-shadow:0 0 0 3px #c7d2fe55;}
.inp--err{border-color:#fca5a5;background:#fef2f2;}
.err{font-size:11px;color:#dc2626;font-weight:600;}
.hint{font-size:10.5px;color:#94a3b8;display:flex;align-items:center;gap:4px;}
.cost-grp{display:flex;gap:0;}
.cost-grp .inp{border-radius:9px 0 0 9px;border-right:none;}
.cost-devise{max-width:110px;border-radius:0 9px 9px 0;}
.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px;padding-top:14px;border-top:1px solid #eef2f7;}
.modal-note{font-size:13px;color:#475569;margin:0 0 6px;}
.mtitle{font-size:12px;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.04em;margin:14px 0 8px;}
.mb-block{margin-bottom:8px;}
.link-item{border:1px solid #e6eaf2;border-radius:10px;padding:10px 12px;margin-bottom:8px;}
.link-ent{font-size:12px;font-weight:700;color:#334155;display:flex;align-items:center;gap:5px;margin-bottom:6px;}
.link-ent i{color:#94a3b8;}
.link-row{display:flex;align-items:flex-start;gap:8px;}
.link-url{flex:1;font-size:11px;color:#4f46e5;word-break:break-all;background:#f3f5fb;padding:6px 9px;border-radius:7px;font-family:monospace;}
.btn-copy{display:inline-flex;align-items:center;gap:4px;padding:6px 11px;border:1px solid #c7d2fe;background:#eef2ff;color:#4338ca;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;flex-shrink:0;}
.btn-copy.ok{background:#dcfce7;border-color:#86efac;color:#15803d;}
.clip-err{margin-top:8px;display:flex;flex-direction:column;gap:5px;}
.clip-err span{font-size:11px;color:#b45309;display:flex;align-items:center;gap:5px;}
.spin{display:inline-block;animation:spin .7s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}
</style>
