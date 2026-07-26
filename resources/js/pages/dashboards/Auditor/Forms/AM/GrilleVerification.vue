<template>
  <VerticalLayoutAudit>
    <div class="grv-shell" :style="`--fc:${mc};--fcl:${mc}18;--fcm:${mc}35`">

      <!-- ══ HEADER ══ -->
      <header class="grv-header">
        <div class="grv-hrow">
          <a :href="backUrl || '#'" class="grv-back"><i class="ti ti-arrow-left"></i></a>

          <div class="grv-hinfo">
            <div class="grv-chips">
              <span v-if="grille?.code" class="grv-gcode">{{ grille.code }}</span>
              <span v-if="formMeta?.code" class="grv-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">
                {{ formMeta.code }}
              </span>
              <span class="grv-phasechip" :style="`background:${mc}15;color:${mc}`">
                <i class="ti ti-folder"></i> {{ formMeta?.phase_label || 'Phase' }}
              </span>
              <span v-if="auditorRole" class="grv-rolechip" :class="`rc-${auditorRole}`">
                <i class="ti ti-shield-half"></i> {{ auditorRole }}
              </span>
              <span v-if="record?.status" class="grv-vstchip" :class="`gvsc-${record.status}`">
                <i :class="stIcon(record.status)"></i> {{ stLbl(record.status) }}
              </span>
              <span v-if="record?.is_template" class="grv-tpl"><i class="ti ti-template"></i> modèle</span>
            </div>
            <h1 class="grv-htitle">
              <i class="ti ti-checklist"></i>
              {{ formMeta?.label || grille?.intitule || 'Grille de vérification' }}
            </h1>
            <p v-if="grille?.intitule" class="grv-hsub">{{ grille.intitule }}</p>
          </div>

          <!-- Jauge conformité -->
          <div class="grv-gauge" :title="`Taux de conformité pondéré : ${taux}%`">
            <svg viewBox="0 0 36 36">
              <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
              <circle cx="18" cy="18" r="15.5" fill="none"
                :stroke="conformeColor" stroke-width="3" stroke-linecap="round"
                :stroke-dasharray="`${taux} ${100 - taux}`" stroke-dashoffset="25"
                style="transition:stroke-dasharray .5s ease"/>
            </svg>
            <div class="grv-gauge-c">
              <span class="grv-gauge-pct" :style="`color:${conformeColor}`">{{ taux }}%</span>
              <span class="grv-gauge-sub">conf.</span>
            </div>
          </div>
        </div>

        <!-- Barre de stats -->
        <div class="grv-statbar">
          <div class="grv-stat"><b>{{ totals.total }}</b> points de contrôle</div>
          <div class="grv-stat grv-s-ok"><i class="ti ti-circle-check"></i> {{ totals.respecte }} respectés</div>
          <div class="grv-stat grv-s-mid"><i class="ti ti-circle-half-2"></i> {{ totals.partiel }} partiels</div>
          <div class="grv-stat grv-s-ko"><i class="ti ti-circle-x"></i> {{ totals.non }} non respectés</div>
          <div class="grv-stat grv-s-na"><i class="ti ti-circle-minus"></i> {{ totals.na }} N/A</div>
          <div v-if="totals.pending" class="grv-stat grv-s-pend"><i class="ti ti-help"></i> {{ totals.pending }} à évaluer</div>
          <div v-if="totals.ecartsMajeurs" class="grv-stat grv-s-crit">
            <i class="ti ti-alert-triangle"></i> {{ totals.ecartsMajeurs }} écart(s) majeur(s)
          </div>
          <div class="grv-verdict"
            :style="`background:${conformeColor}18;color:${conformeColor};border-color:${conformeColor}45`">
            <i :class="estConforme ? 'ti ti-shield-check' : 'ti ti-shield-x'"></i>
            {{ estConforme ? 'Conforme' : 'Non conforme' }} · seuil {{ seuils.conformite }}%
          </div>
        </div>
      </header>

      <!-- ══ CORPS ══ -->
      <div class="grv-body">
        <div v-if="phaseNotStarted" class="grv-warn">
          <i class="ti ti-player-play"></i>
          Cette phase n’est pas encore démarrée — démarrez-la depuis la liste des phases pour saisir.
        </div>

        <div v-if="!items.length" class="grv-empty">
          <i class="ti ti-clipboard-off"></i>
          <p>Aucun point de contrôle paramétré pour cette grille{{ grille?.code ? ` (${grille.code})` : '' }}.</p>
          <span>La grille sera disponible après resynchronisation du référentiel.</span>
        </div>

        <div v-else class="grv-items">
          <div v-for="(it, idx) in items" :key="it.id"
            class="grv-item"
            :class="{ 'gi-crit': isMajeurNon(it) }">
            <div class="grv-item-num">{{ it.numero || (idx + 1) }}</div>

            <div class="grv-item-main">
              <p class="grv-item-lib">{{ it.libelle }}</p>
              <div class="grv-item-meta">
                <span v-if="it.gravite_ecart" class="grv-grav" :class="`gg-${it.gravite_ecart}`">
                  <i class="ti ti-alert-triangle"></i> Écart {{ it.gravite_ecart }}
                </span>
                <span v-if="it.reference_ecart" class="grv-ref"><i class="ti ti-scale"></i> {{ it.reference_ecart }}</span>
                <span v-if="it.preuves" class="grv-preuve"><i class="ti ti-paperclip"></i> {{ it.preuves }}</span>
              </div>

              <!-- Commentaire si non/partiel ou déjà rempli -->
              <input v-if="showComment(it.id)"
                v-model="rep(it.id).commentaire"
                :disabled="isLocked"
                class="grv-item-com" type="text"
                placeholder="Constat / observation / référence de la preuve…" />
            </div>

            <!-- Modalités -->
            <div class="grv-choices">
              <button v-for="m in modalites" :key="m.code"
                class="grv-choice" :class="{ on: rep(it.id).modalite === m.code }"
                :style="rep(it.id).modalite === m.code ? `background:${colorOf(m.couleur)};border-color:${colorOf(m.couleur)};color:#fff` : ''"
                :disabled="isLocked"
                :title="`${m.libelle} (${m.exclu_du_calcul ? 'exclu' : m.poids + ' pts'})`"
                @click="setModalite(it.id, m.code)">
                <i :class="m.icone"></i>
                <span class="grv-choice-l">{{ shortLbl(m) }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Synthèse -->
        <section v-if="items.length" class="grv-note-sec">
          <label class="grv-note-lbl"><i class="ti ti-notes"></i> Synthèse & conclusion sur la conformité</label>
          <textarea v-model="synthese" :disabled="isLocked" rows="4" class="grv-note-ta"
            placeholder="Conclusion d’ensemble, écarts majeurs relevés, recommandations…"></textarea>
        </section>
      </div>

      <!-- ══ BARRE D’ACTIONS ══ -->
      <div v-if="!noMission" class="grv-actionbar">
        <div class="grv-ab-left">
          <span v-if="record?.version" class="grv-ver"><i class="ti ti-versions"></i> v{{ record.version }}</span>
          <span v-if="record?.validation_note" class="grv-rej">
            <i class="ti ti-alert-triangle"></i> Rejet : {{ record.validation_note }}
          </span>
        </div>
        <div class="grv-ab-right">
          <button class="grv-btn grv-btn-ghost" :disabled="isLocked || processing" @click="save">
            <i class="ti ti-device-floppy"></i> Enregistrer
          </button>
          <button v-if="record?.id && record.status==='draft'"
            class="grv-btn grv-btn-primary" :style="`background:${mc}`"
            :disabled="processing" @click="soumettre">
            <i class="ti ti-send"></i> Soumettre
          </button>
          <template v-if="canManage && record?.status==='submitted'">
            <button class="grv-btn grv-btn-reject" :disabled="processing" @click="promptReject">
              <i class="ti ti-x"></i> Rejeter
            </button>
            <button class="grv-btn grv-btn-valid" :disabled="processing" @click="valider('validate')">
              <i class="ti ti-check"></i> Valider
            </button>
          </template>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <transition name="grv-toast">
        <div v-if="toast.show" class="grv-toastbox" :class="`t-${toast.type}`">
          <i :class="toast.type==='success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
          {{ toast.msg }}
        </div>
      </transition>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
// ════════════════════════════════════════════════════════════════════
// AM · Grille de vérification de conformité (écran générique)
//
// Un seul composant sert toutes les phases adossées à une grille ARMP
// déjà transcrite (A4 mise en place, A5 PPM, A7 info/transparence, A9
// avis organes, A10 approbation, A11 exécution…). La grille + ses points
// de contrôle + les modalités de pondération + les seuils sont injectés
// par le contrôleur (refData) depuis les pm_* du tenant.
//
// Pondération LINÉAIRE : chaque modalité porte un poids (Respecté=100,
// Partiellement=50, Non=0) ; « Non applicable » est exclu du calcul.
// Taux de conformité = moyenne des poids des points ÉVALUÉS (N-A exclus),
// comparé au seuil de conformité (80 %). Persistance/workflow identiques
// au form générique via le bloc `data` de mission_phase_form_data.
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
  refData:      { type: Object,  default: () => ({ grille: null, items: [], modalites: [], seuils: {} }) },
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

const mc = computed<string>(() => {
  const c = (props.mission as any)?.audit_color || (props.formMeta as any)?.audit_color
  if (c && c !== '#000000' && c !== '#000' && c !== 'null') return c
  return '#2563eb'
})

const grille    = computed<any>(() => (props.refData as any)?.grille ?? null)
const items     = computed<any[]>(() => (props.refData as any)?.items ?? [])
const modalites = computed<any[]>(() => (props.refData as any)?.modalites ?? [])
const seuils    = computed<any>(() => (props.refData as any)?.seuils ?? { conformite: 80, auditabilite: 80 })

const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const record = reactive<Record<string, any>>(props.record ? { ...props.record } : {})

// Modalité "exclue du calcul" (N/A) — pour la logique de dénominateur
const naCode = computed<string>(() => modalites.value.find(m => m.exclu_du_calcul)?.code ?? 'na')
const nonCode = computed<string>(() => {
  const zero = modalites.value.filter(m => !m.exclu_du_calcul && m.poids === 0)
  return zero.length ? zero[0].code : 'non_respecte'
})

// ── État de saisie ─────────────────────────────────────────────────────────
const savedData = (props.record as any)?.data ?? {}
const reponses = reactive<Record<number, { modalite: string; commentaire: string }>>({})
for (const it of items.value) {
  const prev = savedData?.reponses?.[it.id]
  reponses[it.id] = { modalite: prev?.modalite ?? '', commentaire: prev?.commentaire ?? '' }
}
const synthese = ref<string>(savedData?.synthese ?? (props.record as any)?.notes ?? '')

function rep(id: number) {
  if (!reponses[id]) reponses[id] = { modalite: '', commentaire: '' }
  return reponses[id]
}
function setModalite(id: number, code: string) {
  if (isLocked.value) return
  const r = rep(id)
  r.modalite = r.modalite === code ? '' : code
}
function showComment(id: number) {
  const r = rep(id)
  return !!r.commentaire || r.modalite === nonCode.value ||
    (r.modalite && modalites.value.find(m => m.code === r.modalite && m.poids === 50) != null)
}
function isMajeurNon(it: any) {
  return it.gravite_ecart === 'majeur' && rep(it.id).modalite === nonCode.value
}

// ── Totaux & taux pondéré ──────────────────────────────────────────────────
const totals = computed(() => {
  let respecte = 0, partiel = 0, non = 0, na = 0, pending = 0, ecartsMajeurs = 0
  for (const it of items.value) {
    const code = rep(it.id).modalite
    if (!code) { pending++; continue }
    const m = modalites.value.find(x => x.code === code)
    if (!m) { pending++; continue }
    if (m.exclu_du_calcul) na++
    else if (m.poids >= 100) respecte++
    else if (m.poids === 0) { non++; if (it.gravite_ecart === 'majeur') ecartsMajeurs++ }
    else partiel++
  }
  return { total: items.value.length, respecte, partiel, non, na, pending, ecartsMajeurs }
})

// Taux = Σ poids des points évalués (non exclus) / (nb évalués × 100) × 100
const taux = computed(() => {
  let somme = 0, nb = 0
  for (const it of items.value) {
    const code = rep(it.id).modalite
    if (!code) continue
    const m = modalites.value.find(x => x.code === code)
    if (!m || m.exclu_du_calcul) continue
    somme += m.poids; nb++
  }
  return nb ? Math.round(somme / nb) : 0
})
const estConforme = computed(() => taux.value >= (seuils.value.conformite ?? 80))

const COLOR_MAP: Record<string, string> = { green: '#16a34a', blue: '#2563eb', orange: '#ea580c', red: '#dc2626', gray: '#64748b' }
function colorOf(c: string) { return COLOR_MAP[c] ?? '#64748b' }
const conformeColor = computed(() => estConforme.value ? '#16a34a' : (taux.value >= 50 ? '#ea580c' : '#dc2626'))

function shortLbl(m: any) {
  return ({ respecte: 'Respecté', partiellement_respecte: 'Partiel', non_respecte: 'Non', na: 'N/A' } as any)[m.code] ?? m.libelle
}

// ── Verrouillage ────────────────────────────────────────────────────────────
const processing = ref(false)
const isLocked = computed(() =>
  record.status === 'validated' || (record.status === 'submitted' && !canManage.value)
)

// ── Actions ─────────────────────────────────────────────────────────────────
function buildPayloadData() {
  const out: Record<number, any> = {}
  for (const [id, r] of Object.entries(reponses)) {
    if (r.modalite || r.commentaire) out[Number(id)] = { modalite: r.modalite, commentaire: r.commentaire }
  }
  return {
    grille_code: grille.value?.code ?? null,
    reponses: out,
    synthese: synthese.value,
    resume: {
      total: totals.value.total, respecte: totals.value.respecte, partiel: totals.value.partiel,
      non: totals.value.non, na: totals.value.na, ecarts_majeurs: totals.value.ecartsMajeurs,
      taux_conformite: taux.value, conforme: estConforme.value,
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
    showToast('success', 'Grille enregistrée')
  })
}

async function soumettre() {
  if (!record.id) { showToast('error', 'Enregistrez d’abord la grille.'); return }
  if (!confirm('Soumettre cette grille pour validation par le DM ?')) return
  await apiPost(`${props.formUrl}/soumettre`, { assignment_id: props.assignmentId }, (json: any) => {
    record.status = json.status
    showToast('success', 'Grille soumise — en attente validation DM')
  })
}

async function valider(action: 'validate' | 'reject', note?: string) {
  await apiPost(`${props.formUrl}/valider`, { assignment_id: props.assignmentId, action, note }, (json: any) => {
    record.status = json.status
    if (action === 'reject') record.validation_note = note
    showToast('success', action === 'validate' ? 'Grille validée ✓' : 'Grille rejetée — repassée en brouillon')
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

.grv-shell { font-family: 'Plus Jakarta Sans', sans-serif; min-height: calc(100vh - 68px); background: #f4f6f8; color: #1e293b; padding-bottom: 84px; }

/* HEADER */
.grv-header { position: sticky; top: 0; z-index: 30; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 8px rgba(15,23,42,.05); }
.grv-hrow { display: flex; align-items: flex-start; gap: 14px; padding: 16px 22px 12px; }
.grv-back { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--fcl); color: var(--fc); border: 1px solid var(--fcm); text-decoration: none; transition: all .15s; }
.grv-back:hover { background: var(--fc); color: #fff; }
.grv-hinfo { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
.grv-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.grv-gcode { font-family: 'JetBrains Mono', monospace; font-weight: 800; font-size: .72rem; padding: 2px 9px; border-radius: 6px; background: #0f172a; color: #fff; }
.grv-code { font-family: 'JetBrains Mono', monospace; font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; border: 1px solid; }
.grv-phasechip, .grv-rolechip, .grv-vstchip, .grv-tpl { display: inline-flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; }
.grv-tpl { background: #ecfeff; color: #0e7490; }
.rc-DM { background: #fef3c7; color: #b45309; } .rc-CM { background: #dbeafe; color: #1d4ed8; }
.rc-AS { background: #d1fae5; color: #047857; } .rc-AJ { background: #ede9fe; color: #6d28d9; }
.gvsc-draft { background: #f1f5f9; color: #64748b; } .gvsc-submitted { background: #fef3c7; color: #b45309; }
.gvsc-validated { background: #d1fae5; color: #047857; } .gvsc-rejected { background: #fee2e2; color: #b91c1c; }
.grv-htitle { margin: 0; font-size: 1.18rem; font-weight: 800; letter-spacing: -.02em; color: #0f172a; display: flex; align-items: center; gap: 8px; }
.grv-hsub { margin: 0; font-size: .76rem; color: #64748b; line-height: 1.4; max-width: 820px; }

.grv-gauge { position: relative; width: 74px; height: 74px; flex-shrink: 0; }
.grv-gauge svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.grv-gauge-c { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.grv-gauge-pct { font-size: 1rem; font-weight: 800; }
.grv-gauge-sub { font-size: .55rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }

.grv-statbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 0 22px 14px; }
.grv-stat { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; color: #475569; background: #f1f5f9; padding: 4px 10px; border-radius: 8px; }
.grv-stat b { font-weight: 800; color: #0f172a; }
.grv-s-ok { background: #dcfce7; color: #15803d; } .grv-s-mid { background: #ffedd5; color: #c2410c; }
.grv-s-ko { background: #fee2e2; color: #b91c1c; } .grv-s-na { background: #f1f5f9; color: #64748b; }
.grv-s-pend { background: #fef9c3; color: #a16207; } .grv-s-crit { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.grv-verdict { margin-left: auto; display: inline-flex; align-items: center; gap: 5px; font-size: .74rem; font-weight: 800; padding: 4px 12px; border-radius: 20px; border: 1px solid; }

/* BODY */
.grv-body { padding: 18px 22px; display: flex; flex-direction: column; gap: 14px; max-width: 1200px; margin: 0 auto; }
.grv-warn { display: flex; align-items: center; gap: 8px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 10px 14px; border-radius: 10px; font-size: .8rem; }
.grv-empty { text-align: center; padding: 48px 20px; color: #94a3b8; }
.grv-empty i { font-size: 2.4rem; } .grv-empty p { font-weight: 700; margin: 10px 0 4px; color: #64748b; } .grv-empty span { font-size: .78rem; }

.grv-items { display: flex; flex-direction: column; gap: 10px; }
.grv-item { display: flex; align-items: flex-start; gap: 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; }
.gi-crit { border-color: #fca5a5; background: #fef4f4; }
.grv-item-num { flex-shrink: 0; width: 30px; height: 30px; border-radius: 8px; background: var(--fcl); color: var(--fc); font-weight: 800; font-size: .78rem; display: flex; align-items: center; justify-content: center; font-family: 'JetBrains Mono', monospace; }
.grv-item-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
.grv-item-lib { margin: 0; font-size: .84rem; font-weight: 500; color: #1e293b; line-height: 1.45; }
.grv-item-meta { display: flex; flex-wrap: wrap; gap: 8px; }
.grv-grav { font-size: .6rem; font-weight: 800; padding: 1px 7px; border-radius: 9px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 3px; }
.gg-majeur { background: #fee2e2; color: #b91c1c; } .gg-modere { background: #ffedd5; color: #c2410c; }
.grv-ref, .grv-preuve { font-size: .68rem; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px; }
.grv-item-com { margin-top: 2px; font-size: .78rem; padding: 7px 11px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; width: 100%; }
.grv-item-com:focus { outline: none; border-color: var(--fc); }

.grv-choices { display: flex; flex-direction: column; gap: 4px; flex-shrink: 0; width: 118px; }
.grv-choice { display: inline-flex; align-items: center; gap: 6px; font-size: .72rem; font-weight: 700; padding: 6px 10px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .12s; }
.grv-choice:hover:not(:disabled) { border-color: #cbd5e1; }
.grv-choice:disabled { opacity: .5; cursor: not-allowed; }
.grv-choice-l { white-space: nowrap; }

.grv-note-sec { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; margin-top: 6px; }
.grv-note-lbl { font-weight: 800; font-size: .82rem; color: #334155; display: flex; align-items: center; gap: 6px; }
.grv-note-ta { width: 100%; font-family: inherit; font-size: .84rem; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; resize: vertical; }
.grv-note-ta:focus { outline: none; border-color: var(--fc); }

/* ACTION BAR */
.grv-actionbar { position: fixed; bottom: 0; left: 0; right: 0; z-index: 40; display: flex; align-items: center; gap: 14px; padding: 12px 22px; background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -2px 12px rgba(15,23,42,.06); }
.grv-ab-left { flex: 1; display: flex; align-items: center; gap: 12px; min-width: 0; }
.grv-ver { font-size: .72rem; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px; }
.grv-rej { font-size: .74rem; color: #b91c1c; display: inline-flex; align-items: center; gap: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.grv-ab-right { display: flex; gap: 8px; }
.grv-btn { display: inline-flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 700; padding: 9px 16px; border-radius: 10px; border: none; cursor: pointer; transition: all .15s; }
.grv-btn:disabled { opacity: .55; cursor: not-allowed; }
.grv-btn-ghost { background: #f1f5f9; color: #475569; } .grv-btn-ghost:hover:not(:disabled) { background: #e2e8f0; }
.grv-btn-primary { color: #fff; } .grv-btn-primary:hover:not(:disabled) { filter: brightness(.95); }
.grv-btn-reject { background: #fee2e2; color: #b91c1c; } .grv-btn-valid { background: #16a34a; color: #fff; }

/* TOAST */
.grv-toastbox { position: fixed; bottom: 84px; left: 50%; transform: translateX(-50%); z-index: 60; display: flex; align-items: center; gap: 8px; padding: 11px 18px; border-radius: 12px; font-size: .82rem; font-weight: 700; color: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.2); }
.grv-toastbox.t-success { background: #16a34a; } .grv-toastbox.t-error { background: #dc2626; }
.grv-toast-enter-active, .grv-toast-leave-active { transition: all .3s ease; }
.grv-toast-enter-from, .grv-toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(12px); }
</style>
