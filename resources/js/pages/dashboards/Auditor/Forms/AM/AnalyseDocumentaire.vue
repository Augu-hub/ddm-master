<template>
  <VerticalLayoutAudit>
    <div class="ade-shell" :style="`--fc:${mc};--fcl:${mc}18;--fcm:${mc}35`">

      <!-- ══ HEADER ══ -->
      <header class="ade-header">
        <div class="ade-hrow">
          <a :href="backUrl || '#'" class="ade-back"><i class="ti ti-arrow-left"></i></a>

          <div class="ade-hinfo">
            <div class="ade-chips">
              <span v-if="formMeta?.code" class="ade-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">
                {{ formMeta.code }}
              </span>
              <span class="ade-phasechip" :style="`background:${mc}15;color:${mc}`">
                <i class="ti ti-folder"></i> {{ formMeta?.phase_label || 'Préparation' }}
              </span>
              <span v-if="auditorRole" class="ade-rolechip" :class="`rc-${auditorRole}`">
                <i class="ti ti-shield-half"></i> {{ auditorRole }}
              </span>
              <span v-if="record?.status" class="ade-vstchip" :class="`gvsc-${record.status}`">
                <i :class="stIcon(record.status)"></i> {{ stLbl(record.status) }}
              </span>
              <span v-if="record?.is_template" class="ade-tpl"><i class="ti ti-template"></i> modèle</span>
            </div>
            <h1 class="ade-htitle">
              <i class="ti ti-file-search"></i>
              {{ formMeta?.label || 'Analyse documentaire de l’entité' }}
            </h1>
            <p class="ade-hsub">
              Vérification de la présence des pièces obligatoires (annexes A1/A2) —
              référentiel ARMP. L’absence d’une pièce à <strong>incidence directe</strong>
              entraîne la non-conformité (NCF).
            </p>
          </div>

          <!-- Jauge disponibilité -->
          <div class="ade-gauge" :title="`Taux d’absence : ${tauxAbsence}%`">
            <svg viewBox="0 0 36 36">
              <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
              <circle cx="18" cy="18" r="15.5" fill="none"
                :stroke="apprecColor" stroke-width="3" stroke-linecap="round"
                :stroke-dasharray="`${dispoPct} ${100 - dispoPct}`" stroke-dashoffset="25"
                style="transition:stroke-dasharray .5s ease"/>
            </svg>
            <div class="ade-gauge-c">
              <span class="ade-gauge-pct" :style="`color:${apprecColor}`">{{ dispoPct }}%</span>
              <span class="ade-gauge-sub">dispo.</span>
            </div>
          </div>
        </div>

        <!-- Barre de stats -->
        <div class="ade-statbar">
          <div class="ade-stat"><b>{{ totals.total }}</b> pièces</div>
          <div class="ade-stat ade-s-ok"><i class="ti ti-check"></i> {{ totals.present }} présentes</div>
          <div class="ade-stat ade-s-ko"><i class="ti ti-x"></i> {{ totals.absent }} absentes</div>
          <div class="ade-stat ade-s-na"><i class="ti ti-minus"></i> {{ totals.na }} N/A</div>
          <div v-if="totals.pending" class="ade-stat ade-s-pend"><i class="ti ti-help"></i> {{ totals.pending }} à renseigner</div>
          <div class="ade-stat ade-s-crit" v-if="totals.ncf">
            <i class="ti ti-alert-triangle"></i> {{ totals.ncf }} NCF (incidence directe)
          </div>
          <div class="ade-apprec" :style="`background:${apprecColor}18;color:${apprecColor};border-color:${apprecColor}45`">
            {{ apprecLabel }}
          </div>
        </div>
      </header>

      <!-- ══ CORPS ══ -->
      <div class="ade-body">

        <div v-if="phaseNotStarted" class="ade-warn">
          <i class="ti ti-player-play"></i>
          Cette phase n’est pas encore démarrée — démarrez-la depuis la liste des phases pour saisir.
        </div>

        <div v-if="!categories.length" class="ade-empty">
          <i class="ti ti-database-off"></i>
          <p>Aucune pièce paramétrée n’a été trouvée pour ce tenant.</p>
          <span>Le paramétrage central (pm_pieces_obligatoires) sera disponible après resynchronisation.</span>
        </div>

        <!-- Catégories -->
        <section v-for="cat in categories" :key="cat.id" class="ade-cat">
          <div class="ade-cat-hd">
            <span class="ade-annexe" :class="`ax-${cat.annexe}`">{{ cat.annexe }}</span>
            <div class="ade-cat-tt">
              <span class="ade-cat-lib">{{ cat.libelle }}</span>
              <span v-if="cat.description" class="ade-cat-desc">{{ cat.description }}</span>
            </div>
            <div class="ade-cat-prog">
              <span class="ade-cat-frac">{{ catStat(cat).answered }}/{{ cat.pieces.length }}</span>
              <div class="ade-cat-bar"><div class="ade-cat-fill" :style="`width:${catStat(cat).pct}%;background:${mc}`"></div></div>
            </div>
          </div>

          <div class="ade-pieces">
            <div v-for="p in cat.pieces" :key="p.id"
              class="ade-piece"
              :class="{ 'pc-absent-crit': rep(p.id).statut==='absent' && p.incidence==='directe' }">
              <div class="ade-piece-main">
                <div class="ade-piece-lib">
                  <span class="ade-piece-name">{{ p.libelle }}</span>
                  <span class="ade-inc" :class="p.incidence==='directe' ? 'inc-d' : 'inc-s'">
                    {{ p.incidence==='directe' ? 'Incidence directe · NCF' : 'Sans incidence · INSF' }}
                  </span>
                  <span v-if="p.compte_auditabilite" class="ade-tag-aud" title="Compte dans le taux d’auditabilité">
                    <i class="ti ti-calculator"></i> auditabilité
                  </span>
                </div>
                <span v-if="p.reference_texte" class="ade-piece-ref">
                  <i class="ti ti-scale"></i> {{ p.reference_texte }}
                </span>
              </div>

              <!-- Choix statut -->
              <div class="ade-choices">
                <button v-for="opt in STATUTS" :key="opt.v"
                  class="ade-choice" :class="[`ch-${opt.v}`, { on: rep(p.id).statut===opt.v }]"
                  :disabled="isLocked"
                  @click="setStatut(p.id, opt.v)">
                  <i :class="opt.icon"></i> {{ opt.l }}
                </button>
              </div>

              <!-- Commentaire (visible si absent ou déjà rempli) -->
              <input v-if="rep(p.id).statut==='absent' || rep(p.id).commentaire"
                v-model="rep(p.id).commentaire"
                :disabled="isLocked"
                class="ade-piece-com"
                type="text"
                placeholder="Observation / référence de la pièce constatée…" />
            </div>
          </div>
        </section>

        <!-- Note de synthèse -->
        <section v-if="categories.length" class="ade-note-sec">
          <label class="ade-note-lbl"><i class="ti ti-notes"></i> Synthèse de l’analyse documentaire</label>
          <textarea v-model="synthese" :disabled="isLocked" rows="4" class="ade-note-ta"
            placeholder="Constats généraux sur la disponibilité et la qualité de la documentation de l’entité…"></textarea>
        </section>
      </div>

      <!-- ══ BARRE D’ACTIONS ══ -->
      <div v-if="!noMission" class="ade-actionbar">
        <div class="ade-ab-left">
          <span v-if="record?.version" class="ade-ver"><i class="ti ti-versions"></i> v{{ record.version }}</span>
          <span v-if="record?.validation_note" class="ade-rej">
            <i class="ti ti-alert-triangle"></i> Rejet : {{ record.validation_note }}
          </span>
        </div>
        <div class="ade-ab-right">
          <button class="ade-btn ade-btn-ghost" :disabled="isLocked || processing" @click="save">
            <i class="ti ti-device-floppy"></i> Enregistrer
          </button>
          <button v-if="record?.id && record.status==='draft'"
            class="ade-btn ade-btn-primary" :style="`background:${mc}`"
            :disabled="processing" @click="soumettre">
            <i class="ti ti-send"></i> Soumettre
          </button>
          <template v-if="canManage && record?.status==='submitted'">
            <button class="ade-btn ade-btn-reject" :disabled="processing" @click="promptReject">
              <i class="ti ti-x"></i> Rejeter
            </button>
            <button class="ade-btn ade-btn-valid" :disabled="processing" @click="valider('validate')">
              <i class="ti ti-check"></i> Valider
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Teleport to="body">
      <transition name="ade-toast">
        <div v-if="toast.show" class="ade-toastbox" :class="`t-${toast.type}`">
          <i :class="toast.type==='success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
// ════════════════════════════════════════════════════════════════════
// AM · Analyse documentaire de l’entité (phase ADE)
//
// Écran dédié alimenté par le paramétrage central déjà synchronisé dans
// le tenant : pièces obligatoires (pm_pieces_obligatoires) groupées par
// catégorie/annexe (pm_pieces_categories) + grille d’appréciation de la
// disponibilité (pm_grille_appreciation_disponibilite). L’auditeur coche
// pour chaque pièce Présente / Absente / N-A ; le taux d’absence est
// calculé (N/A exclu) et comparé à la grille ARMP. Persistance et
// workflow draft→submitted→validated identiques au form générique, via
// le bloc `data` de mission_phase_form_data (DynamicPhaseFormController).
// ════════════════════════════════════════════════════════════════════
import { computed, reactive, ref } from 'vue'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = defineProps({
  mission:      { type: Object,  default: null },
  assignment:   { type: Object,  default: null },
  auditeurs:    { type: Array,   default: () => [] },
  auditorRole:  { type: String,  default: null },
  record:       { type: Object,  default: null },
  formMeta:     { type: Object,  default: null },
  refData:      { type: Object,  default: () => ({ categories: [], grilleDispo: [] }) },
  errors:       { type: Object,  default: () => ({}) },

  noMission:       { type: Boolean, default: false },
  phaseNotStarted: { type: Boolean, default: false },

  missionId:    { type: Number, default: null },
  assignmentId: { type: Number, default: null },
  missionMenu:  { type: Array,  default: () => [] },

  backUrl:     { type: String, default: '' },
  formUrl:     { type: String, default: '' },
  chatBaseUrl: { type: String, default: '' },
})

const STATUTS = [
  { v: 'present', l: 'Présente', icon: 'ti ti-check' },
  { v: 'absent',  l: 'Absente',  icon: 'ti ti-x' },
  { v: 'na',      l: 'N/A',       icon: 'ti ti-minus' },
]

const mc = computed<string>(() => {
  const c = (props.mission as any)?.audit_color || (props.formMeta as any)?.audit_color
  if (c && c !== '#000000' && c !== '#000' && c !== 'null') return c
  return '#2563eb'
})

const categories = computed<any[]>(() => (props.refData as any)?.categories ?? [])
const grilleDispo = computed<any[]>(() => (props.refData as any)?.grilleDispo ?? [])

const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const record = reactive<Record<string, any>>(props.record ? { ...props.record } : {})

// ── État de saisie : réponses par pièce + synthèse ─────────────────────────
const savedData = (props.record as any)?.data ?? {}
const reponses = reactive<Record<number, { statut: string; commentaire: string }>>({})
for (const cat of categories.value) {
  for (const p of cat.pieces) {
    const prev = savedData?.reponses?.[p.id]
    reponses[p.id] = {
      statut:      prev?.statut ?? '',
      commentaire: prev?.commentaire ?? '',
    }
  }
}
const synthese = ref<string>(savedData?.synthese ?? (props.record as any)?.notes ?? '')

function rep(id: number) {
  if (!reponses[id]) reponses[id] = { statut: '', commentaire: '' }
  return reponses[id]
}
function setStatut(id: number, v: string) {
  if (isLocked.value) return
  const r = rep(id)
  r.statut = r.statut === v ? '' : v   // re-clic = désélection
}

// ── Totaux & taux d’absence ────────────────────────────────────────────────
const allPieces = computed<any[]>(() => categories.value.flatMap(c => c.pieces))

const totals = computed(() => {
  let present = 0, absent = 0, na = 0, pending = 0, ncf = 0
  for (const p of allPieces.value) {
    const s = rep(p.id).statut
    if (s === 'present') present++
    else if (s === 'absent') { absent++; if (p.incidence === 'directe') ncf++ }
    else if (s === 'na') na++
    else pending++
  }
  return { total: allPieces.value.length, present, absent, na, pending, ncf }
})

// Taux d’absence = absentes / (présentes + absentes) — N/A et non-renseignées exclues
const tauxAbsence = computed(() => {
  const base = totals.value.present + totals.value.absent
  return base ? Math.round((totals.value.absent / base) * 100) : 0
})
const dispoPct = computed(() => 100 - tauxAbsence.value)

// Appréciation ARMP selon la grille (bornes sur le taux d’ABSENCE)
const apprec = computed(() => {
  const t = tauxAbsence.value
  for (const g of grilleDispo.value) {
    const okMin = g.borne_min == null || (g.operateur_min === '>=' ? t >= g.borne_min : t > g.borne_min)
    const okMax = g.borne_max == null || (g.operateur_max === '<=' ? t <= g.borne_max : t < g.borne_max)
    if (okMin && okMax) return g
  }
  return null
})
const apprecLabel = computed(() => apprec.value?.appreciation ?? '—')
const COLOR_MAP: Record<string, string> = { green: '#16a34a', blue: '#2563eb', orange: '#ea580c', red: '#dc2626' }
const apprecColor = computed(() => COLOR_MAP[apprec.value?.couleur ?? ''] ?? '#64748b')

function catStat(cat: any) {
  const answered = cat.pieces.filter((p: any) => rep(p.id).statut).length
  return { answered, pct: cat.pieces.length ? Math.round((answered / cat.pieces.length) * 100) : 0 }
}

// ── Verrouillage ────────────────────────────────────────────────────────────
const processing = ref(false)
const isLocked = computed(() =>
  record.status === 'validated' || (record.status === 'submitted' && !canManage.value)
)

// ── Actions (mêmes endpoints que le form générique) ─────────────────────────
function buildPayloadData() {
  const out: Record<number, any> = {}
  for (const [id, r] of Object.entries(reponses)) {
    if (r.statut || r.commentaire) out[Number(id)] = { statut: r.statut, commentaire: r.commentaire }
  }
  return {
    reponses: out,
    synthese: synthese.value,
    resume: {
      total: totals.value.total, present: totals.value.present, absent: totals.value.absent,
      na: totals.value.na, ncf: totals.value.ncf,
      taux_absence: tauxAbsence.value, appreciation: apprecLabel.value,
    },
  }
}

async function save() {
  if (isLocked.value) return
  await apiPost(props.formUrl, {
    mission_id:    props.missionId,
    assignment_id: props.assignmentId,
    notes:         synthese.value,
    data:          buildPayloadData(),
  }, (json: any) => {
    if (json.record) Object.assign(record, json.record)
    showToast('success', 'Analyse enregistrée')
  })
}

async function soumettre() {
  if (!record.id) { showToast('error', 'Enregistrez d’abord l’analyse.'); return }
  if (!confirm('Soumettre cette analyse documentaire pour validation par le DM ?')) return
  await apiPost(`${props.formUrl}/soumettre`, { assignment_id: props.assignmentId }, (json: any) => {
    record.status = json.status
    showToast('success', 'Analyse soumise — en attente validation DM')
  })
}

async function valider(action: 'validate' | 'reject', note?: string) {
  await apiPost(`${props.formUrl}/valider`, { assignment_id: props.assignmentId, action, note }, (json: any) => {
    record.status = json.status
    if (action === 'reject') record.validation_note = note
    showToast('success', action === 'validate' ? 'Analyse validée ✓' : 'Analyse rejetée — repassée en brouillon')
  })
}
function promptReject() {
  const note = prompt('Motif du rejet (obligatoire) :')
  if (!note?.trim()) return
  valider('reject', note)
}

async function apiPost(url: string, body: object, onOk: (json: any) => void) {
  processing.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify(body),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json?.message ?? json?.error ?? 'Erreur')
    onOk(json)
  } catch (e: any) {
    showToast('error', e.message)
  } finally {
    processing.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function stLbl(s: string) {
  return ({ draft: 'Brouillon', submitted: 'Soumis — en revue', validated: 'Validé', rejected: 'Rejeté' } as any)[s] ?? s
}
function stIcon(s: string) {
  return ({ draft: 'ti ti-pencil', submitted: 'ti ti-clock', validated: 'ti ti-lock', rejected: 'ti ti-x' } as any)[s] ?? 'ti ti-pencil'
}

const toast = ref({ show: false, type: 'success', msg: '' })
let toastTimer: ReturnType<typeof setTimeout>
function showToast(type: string, msg: string) {
  toast.value = { show: true, type, msg }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value.show = false }, 4000)
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap');
* { box-sizing: border-box; }

.ade-shell {
  font-family: 'Plus Jakarta Sans', sans-serif;
  min-height: calc(100vh - 68px);
  background: #f4f6f8; color: #1e293b; padding-bottom: 84px;
}

/* HEADER */
.ade-header { position: sticky; top: 0; z-index: 30; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 8px rgba(15,23,42,.05); }
.ade-hrow { display: flex; align-items: flex-start; gap: 14px; padding: 16px 22px 12px; }
.ade-back { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--fcl); color: var(--fc); border: 1px solid var(--fcm); text-decoration: none; transition: all .15s; }
.ade-back:hover { background: var(--fc); color: #fff; }
.ade-hinfo { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
.ade-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.ade-code { font-family: 'JetBrains Mono', monospace; font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; border: 1px solid; }
.ade-phasechip, .ade-rolechip, .ade-vstchip, .ade-tpl { display: inline-flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; }
.ade-tpl { background: #ecfeff; color: #0e7490; }
.rc-DM { background: #fef3c7; color: #b45309; } .rc-CM { background: #dbeafe; color: #1d4ed8; }
.rc-AS { background: #d1fae5; color: #047857; } .rc-AJ { background: #ede9fe; color: #6d28d9; }
.gvsc-draft { background: #f1f5f9; color: #64748b; } .gvsc-submitted { background: #fef3c7; color: #b45309; }
.gvsc-validated { background: #d1fae5; color: #047857; } .gvsc-rejected { background: #fee2e2; color: #b91c1c; }
.ade-htitle { margin: 0; font-size: 1.18rem; font-weight: 800; letter-spacing: -.02em; color: #0f172a; display: flex; align-items: center; gap: 8px; }
.ade-hsub { margin: 0; font-size: .76rem; color: #64748b; line-height: 1.4; max-width: 760px; }

.ade-gauge { position: relative; width: 74px; height: 74px; flex-shrink: 0; }
.ade-gauge svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.ade-gauge-c { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.ade-gauge-pct { font-size: 1rem; font-weight: 800; }
.ade-gauge-sub { font-size: .55rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }

.ade-statbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 0 22px 14px; }
.ade-stat { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; color: #475569; background: #f1f5f9; padding: 4px 10px; border-radius: 8px; }
.ade-stat b { font-weight: 800; color: #0f172a; }
.ade-s-ok { background: #dcfce7; color: #15803d; } .ade-s-ko { background: #fee2e2; color: #b91c1c; }
.ade-s-na { background: #f1f5f9; color: #64748b; } .ade-s-pend { background: #fef9c3; color: #a16207; }
.ade-s-crit { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.ade-apprec { margin-left: auto; font-size: .72rem; font-weight: 800; padding: 4px 12px; border-radius: 20px; border: 1px solid; }

/* BODY */
.ade-body { padding: 18px 22px; display: flex; flex-direction: column; gap: 18px; max-width: 1200px; margin: 0 auto; }
.ade-warn { display: flex; align-items: center; gap: 8px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 10px 14px; border-radius: 10px; font-size: .8rem; }
.ade-empty { text-align: center; padding: 48px 20px; color: #94a3b8; }
.ade-empty i { font-size: 2.4rem; } .ade-empty p { font-weight: 700; margin: 10px 0 4px; color: #64748b; } .ade-empty span { font-size: .78rem; }

.ade-cat { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; }
.ade-cat-hd { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fafbfc; border-bottom: 1px solid #eef2f6; }
.ade-annexe { font-family: 'JetBrains Mono', monospace; font-weight: 800; font-size: .78rem; padding: 3px 9px; border-radius: 7px; flex-shrink: 0; }
.ax-A1 { background: #ede9fe; color: #6d28d9; } .ax-A2 { background: #dbeafe; color: #1d4ed8; }
.ade-cat-tt { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.ade-cat-lib { font-weight: 800; font-size: .9rem; color: #0f172a; }
.ade-cat-desc { font-size: .72rem; color: #94a3b8; }
.ade-cat-prog { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.ade-cat-frac { font-size: .72rem; font-weight: 700; color: #64748b; }
.ade-cat-bar { width: 80px; height: 6px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
.ade-cat-fill { height: 100%; border-radius: 4px; transition: width .4s ease; }

.ade-pieces { display: flex; flex-direction: column; }
.ade-piece { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 11px 16px; border-bottom: 1px solid #f1f5f9; }
.ade-piece:last-child { border-bottom: none; }
.pc-absent-crit { background: #fef2f2; }
.ade-piece-main { flex: 1; min-width: 240px; display: flex; flex-direction: column; gap: 3px; }
.ade-piece-lib { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.ade-piece-name { font-weight: 600; font-size: .84rem; color: #1e293b; }
.ade-inc { font-size: .58rem; font-weight: 800; padding: 1px 7px; border-radius: 9px; text-transform: uppercase; letter-spacing: .03em; }
.inc-d { background: #fee2e2; color: #b91c1c; } .inc-s { background: #f1f5f9; color: #64748b; }
.ade-tag-aud { font-size: .58rem; font-weight: 700; color: #0e7490; background: #ecfeff; padding: 1px 7px; border-radius: 9px; display: inline-flex; align-items: center; gap: 3px; }
.ade-piece-ref { font-size: .68rem; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px; }
.ade-choices { display: flex; gap: 5px; flex-shrink: 0; }
.ade-choice { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 700; padding: 5px 11px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .12s; }
.ade-choice:hover:not(:disabled) { border-color: #cbd5e1; }
.ade-choice:disabled { opacity: .5; cursor: not-allowed; }
.ch-present.on { background: #16a34a; border-color: #16a34a; color: #fff; }
.ch-absent.on { background: #dc2626; border-color: #dc2626; color: #fff; }
.ch-na.on { background: #64748b; border-color: #64748b; color: #fff; }
.ade-piece-com { flex-basis: 100%; margin-top: 2px; font-size: .78rem; padding: 7px 11px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
.ade-piece-com:focus { outline: none; border-color: var(--fc); }

.ade-note-sec { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; }
.ade-note-lbl { font-weight: 800; font-size: .82rem; color: #334155; display: flex; align-items: center; gap: 6px; }
.ade-note-ta { width: 100%; font-family: inherit; font-size: .84rem; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; resize: vertical; }
.ade-note-ta:focus { outline: none; border-color: var(--fc); }

/* ACTION BAR */
.ade-actionbar { position: fixed; bottom: 0; left: 0; right: 0; z-index: 40; display: flex; align-items: center; gap: 14px; padding: 12px 22px; background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -2px 12px rgba(15,23,42,.06); }
.ade-ab-left { flex: 1; display: flex; align-items: center; gap: 12px; min-width: 0; }
.ade-ver { font-size: .72rem; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px; }
.ade-rej { font-size: .74rem; color: #b91c1c; display: inline-flex; align-items: center; gap: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ade-ab-right { display: flex; gap: 8px; }
.ade-btn { display: inline-flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 700; padding: 9px 16px; border-radius: 10px; border: none; cursor: pointer; transition: all .15s; }
.ade-btn:disabled { opacity: .55; cursor: not-allowed; }
.ade-btn-ghost { background: #f1f5f9; color: #475569; }
.ade-btn-ghost:hover:not(:disabled) { background: #e2e8f0; }
.ade-btn-primary { color: #fff; } .ade-btn-primary:hover:not(:disabled) { filter: brightness(.95); }
.ade-btn-reject { background: #fee2e2; color: #b91c1c; } .ade-btn-valid { background: #16a34a; color: #fff; }

/* TOAST */
.ade-toastbox { position: fixed; bottom: 84px; left: 50%; transform: translateX(-50%); z-index: 60; display: flex; align-items: center; gap: 8px; padding: 11px 18px; border-radius: 12px; font-size: .82rem; font-weight: 700; color: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.2); }
.ade-toastbox.t-success { background: #16a34a; } .ade-toastbox.t-error { background: #dc2626; }
.ade-toast-enter-active, .ade-toast-leave-active { transition: all .3s ease; }
.ade-toast-enter-from, .ade-toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(12px); }
</style>
