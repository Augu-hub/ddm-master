<template>
  <VerticalLayoutAudit>
    <div class="mv-shell">

      <!-- ══ HEADER ══ -->
      <header class="mv-header" :style="`--fc:${mc};--fcl:${mc}18;--fcm:${mc}35`">
        <div class="mv-hrow">
          <a :href="props.backUrl" class="mv-back" title="Retour aux phases"><i class="ti ti-arrow-left"></i></a>
          <div class="mv-hinfo">
            <div class="mv-chips">
              <code class="mv-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">{{ mission?.code_mission ?? '—' }}</code>
              <span v-if="form.validation_status" class="mv-vst" :class="`avs-${form.validation_status}`">
                <i :class="vstIcon(form.validation_status)"></i>{{ vstLbl(form.validation_status) }}
              </span>
              <span class="mv-type" :style="`color:${mc};background:${mc}12`"><i class="ti ti-flask"></i> MV · Méthodologie</span>
              <span v-if="props.auditorRole" class="mv-role" :class="`rc-${props.auditorRole}`"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
            </div>
            <h1 class="mv-htitle">Méthodologie de vérification <span class="mv-sub">— Planification de la mission</span></h1>
            <div class="mv-mission-strip">
              <span v-if="mission?.libelle"><i class="ti ti-clipboard-text"></i>{{ mission.libelle }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="mission?.date_debut_fr"><i class="ti ti-calendar"></i>{{ mission.date_debut_fr }} → {{ mission.date_fin_fr }}</span>
            </div>
          </div>
        </div>

        <div class="mv-tabs">
          <button v-for="t in VOLETS" :key="t.k" class="mv-tab" :class="{ on: volet === t.k }"
            :style="volet === t.k ? `border-bottom-color:${mc};color:${mc}` : ''" @click="volet = t.k">
            <span class="mv-tab-n0">{{ t.n }}</span> <i :class="t.icon"></i> {{ t.l }}
            <span class="mv-tab-n">{{ count(t.k) }}</span>
          </button>
        </div>

        <div v-if="form.validation_status === 'validated'" class="mv-banner mv-banner-lock"><i class="ti ti-lock"></i><span>Fiche <strong>validée</strong> — lecture seule</span></div>
        <div v-else-if="form.validation_status === 'in_review'" class="mv-banner mv-banner-review"><i class="ti ti-clock"></i><span>Soumise — en attente DM.<span v-if="canManage"> Vous pouvez valider ou rejeter.</span></span></div>
        <div v-if="props.phaseNotStarted" class="mv-banner mv-banner-warn"><i class="ti ti-player-pause"></i><span>Phase non démarrée.</span></div>
        <div v-if="props.noMission" class="mv-banner mv-banner-warn"><i class="ti ti-alert-triangle"></i><span>Ouvrez ce formulaire depuis une mission.</span></div>
      </header>

      <div v-if="!props.noMission && !props.phaseNotStarted" class="mv-body">

        <!-- Barre IA -->
        <div v-if="!isLocked" class="mv-aibar">
          <div class="mv-aibar-txt">
            <i class="ti ti-sparkles" :style="`color:${mc}`"></i>
            <span>Assistant IA — propose lignes directrices, critères, sources et méthodes à partir du contexte de la mission (et des questions du Champ d'action).</span>
          </div>
          <button class="mv-ai-btn" :style="`background:${mc}`" :disabled="!props.aiEnabled || busy" @click="suggestIA">
            <i :class="busy ? 'ti ti-loader-2 mv-spin' : 'ti ti-wand'"></i>
            {{ props.aiEnabled ? 'Suggérer avec l\'IA' : 'IA non configurée' }}
          </button>
        </div>

        <!-- ════ 1 · LIGNES DIRECTRICES D'ENQUÊTE ════ -->
        <section v-show="volet === 'lignes'" class="mv-card">
          <div class="mv-vhead mv-vh1"><span class="mv-vn">1.</span> Lignes directrices d'enquête</div>
          <div class="mv-scroll">
            <table class="mv-table">
              <thead><tr><th class="mv-num">N°</th><th>Objectif / question d'audit associé</th><th>Ligne directrice d'enquête</th><th>Résultat attendu</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(l, i) in form.lignes" :key="i">
                  <td class="mv-num">{{ i + 1 }}</td>
                  <td><textarea v-model="l.objectif_question" rows="3" :disabled="isLocked" placeholder="Question d'audit associée…"></textarea></td>
                  <td><textarea v-model="l.ligne_directrice" rows="3" :disabled="isLocked" placeholder="Ligne directrice d'enquête…"></textarea></td>
                  <td><textarea v-model="l.resultat_attendu" rows="3" :disabled="isLocked" placeholder="Résultat attendu…"></textarea></td>
                  <td><button v-if="!isLocked" class="mv-del" @click="form.lignes.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.lignes.length"><td colspan="5" class="mv-empty-row">Aucune ligne — ajoutez-en une ou rechargez depuis le Champ d'action.</td></tr>
              </tbody>
            </table>
          </div>
          <div class="mv-row-actions">
            <button v-if="!isLocked" class="mv-add" :style="`color:${mc};border-color:${mc}35`" @click="form.lignes.push(blankLigne())"><i class="ti ti-plus"></i> Ajouter</button>
            <button v-if="!isLocked && (props.trameLignes as any[]).length" class="mv-reload" @click="rechargerLignes"><i class="ti ti-refresh"></i> Recharger depuis le Champ d'action</button>
          </div>
        </section>

        <!-- ════ 2 · CRITÈRES D'AUDIT (SOUS-CRITÈRES) ════ -->
        <section v-show="volet === 'criteres'" class="mv-card">
          <div class="mv-vhead mv-vh2"><span class="mv-vn">2.</span> Critères d'audit <em>(sous-critères)</em></div>
          <div class="mv-scroll">
            <table class="mv-table">
              <thead><tr><th style="width:130px">Critère principal</th><th style="width:150px">Sous-critère</th><th>Source du critère</th><th>Libellé précis retenu pour la mission</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(c, i) in form.criteres" :key="i">
                  <td>
                    <select v-model="c.critere_principal" :disabled="isLocked">
                      <option value="">—</option>
                      <option v-for="r in (props.refCriteres as any[])" :key="r.code" :value="r.nature">{{ r.nature }}</option>
                    </select>
                  </td>
                  <td>
                    <select v-model="c.sous_critere" :disabled="isLocked">
                      <option value="">—</option>
                      <option v-for="r in (props.refSousCriteres as any[])" :key="r.code" :value="r.libelle">{{ r.libelle }}</option>
                    </select>
                  </td>
                  <td><textarea v-model="c.source_critere" rows="2" :disabled="isLocked" placeholder="Circulaire, loi, norme…"></textarea></td>
                  <td><textarea v-model="c.libelle_retenu" rows="2" :disabled="isLocked" placeholder="Libellé précis du critère retenu…"></textarea></td>
                  <td><button v-if="!isLocked" class="mv-del" @click="form.criteres.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.criteres.length"><td colspan="5" class="mv-empty-row">Aucun critère — ajoutez-en un.</td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="mv-add" :style="`color:${mc};border-color:${mc}35`" @click="form.criteres.push(blankCritere())"><i class="ti ti-plus"></i> Ajouter un critère</button>
        </section>

        <!-- ════ 3 · SOURCES DE L'EVIDENCE ════ -->
        <section v-show="volet === 'sources'" class="mv-card">
          <div class="mv-vhead mv-vh3"><span class="mv-vn">3.</span> Sources de l'evidence <em>(evidence sources)</em></div>
          <div class="mv-scroll">
            <table class="mv-table">
              <thead><tr><th class="mv-lnum">Ligne dir. (N°)</th><th>Source de la preuve</th><th>Nature de la preuve attendue</th><th>Modalités d'obtention</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(s, i) in form.sources" :key="i">
                  <td class="mv-lnum"><select v-model.number="s.ligne_num" :disabled="isLocked"><option :value="null">—</option><option v-for="n in ligneNums" :key="n" :value="n">{{ n }}</option></select></td>
                  <td>
                    <select v-model="s.source_preuve" :disabled="isLocked">
                      <option value="">—</option>
                      <option v-for="r in (props.refSourcesPreuve as any[])" :key="r.code" :value="r.libelle">{{ r.libelle }}</option>
                    </select>
                  </td>
                  <td>
                    <select v-model="s.nature_preuve" :disabled="isLocked">
                      <option value="">—</option>
                      <option v-for="r in (props.refNaturePreuve as any[])" :key="r.code" :value="r.libelle">{{ r.libelle }}</option>
                    </select>
                  </td>
                  <td><textarea v-model="s.modalites_obtention" rows="2" :disabled="isLocked" placeholder="Modalités d'obtention…"></textarea></td>
                  <td><button v-if="!isLocked" class="mv-del" @click="form.sources.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.sources.length"><td colspan="5" class="mv-empty-row">Aucune source — ajoutez-en une.</td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="mv-add" :style="`color:${mc};border-color:${mc}35`" @click="form.sources.push(blankSource())"><i class="ti ti-plus"></i> Ajouter une source</button>
        </section>

        <!-- ════ 4 · MÉTHODE DE COLLECTE ════ -->
        <section v-show="volet === 'collecte'" class="mv-card">
          <div class="mv-vhead mv-vh4"><span class="mv-vn">4.</span> Méthode de collecte des données</div>
          <div class="mv-scroll">
            <table class="mv-table">
              <thead><tr><th class="mv-lnum">Ligne dir. (N°)</th><th>Méthode de collecte</th><th>Modalités pratiques</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(c, i) in form.collecte" :key="i">
                  <td class="mv-lnum"><select v-model.number="c.ligne_num" :disabled="isLocked"><option :value="null">—</option><option v-for="n in ligneNums" :key="n" :value="n">{{ n }}</option></select></td>
                  <td>
                    <select v-model="c.methode_collecte" :disabled="isLocked">
                      <option value="">—</option>
                      <option v-for="r in (props.refMethodesCollecte as any[])" :key="r.code" :value="r.libelle">{{ r.libelle }}</option>
                    </select>
                  </td>
                  <td><textarea v-model="c.modalites_pratiques" rows="2" :disabled="isLocked" placeholder="Modalités pratiques de collecte…"></textarea></td>
                  <td><button v-if="!isLocked" class="mv-del" @click="form.collecte.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.collecte.length"><td colspan="4" class="mv-empty-row">Aucune méthode — ajoutez-en une.</td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="mv-add" :style="`color:${mc};border-color:${mc}35`" @click="form.collecte.push(blankCollecte())"><i class="ti ti-plus"></i> Ajouter</button>
        </section>

        <!-- ════ 5 · MÉTHODES D'ANALYSE ════ -->
        <section v-show="volet === 'analyse'" class="mv-card">
          <div class="mv-vhead mv-vh5"><span class="mv-vn">5.</span> Méthodes d'analyse des données</div>
          <div class="mv-scroll">
            <table class="mv-table">
              <thead><tr><th class="mv-lnum">Ligne dir. (N°)</th><th>Méthode d'analyse</th><th>Données concernées</th><th>Résultat attendu de l'analyse</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(a, i) in form.analyse" :key="i">
                  <td class="mv-lnum"><select v-model.number="a.ligne_num" :disabled="isLocked"><option :value="null">—</option><option v-for="n in ligneNums" :key="n" :value="n">{{ n }}</option></select></td>
                  <td>
                    <select v-model="a.methode_analyse" :disabled="isLocked">
                      <option value="">—</option>
                      <option v-for="r in (props.refMethodesAnalyse as any[])" :key="r.code" :value="r.libelle">{{ r.libelle }}</option>
                    </select>
                  </td>
                  <td><textarea v-model="a.donnees_concernees" rows="2" :disabled="isLocked" placeholder="Données concernées…"></textarea></td>
                  <td><textarea v-model="a.resultat_analyse" rows="2" :disabled="isLocked" placeholder="Résultat attendu de l'analyse…"></textarea></td>
                  <td><button v-if="!isLocked" class="mv-del" @click="form.analyse.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.analyse.length"><td colspan="5" class="mv-empty-row">Aucune méthode — ajoutez-en une.</td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="mv-add" :style="`color:${mc};border-color:${mc}35`" @click="form.analyse.push(blankAnalyse())"><i class="ti ti-plus"></i> Ajouter</button>
        </section>

        <!-- Synthèse + signatures -->
        <section class="mv-card mv-sign">
          <div class="mv-field mv-field-full"><label>Synthèse méthodologique</label>
            <textarea v-model="form.synthese" rows="2" :disabled="isLocked" placeholder="Conclusion sur l'approche méthodologique retenue…"></textarea></div>
          <div class="mv-field"><label>Fait par</label><input v-model="form.fait_par" type="text" list="mv-auditeurs" :disabled="isLocked" /></div>
          <div class="mv-field"><label>Revue par</label><input v-model="form.revue_par" type="text" list="mv-auditeurs" :disabled="isLocked" /></div>
          <datalist id="mv-auditeurs"><option v-for="a in (props.auditeurs as any[])" :key="a.id" :value="`${a.nom ?? ''} ${a.prenom ?? ''}`.trim()">{{ a.grade }}</option></datalist>
        </section>

        <!-- Actions -->
        <div class="mv-actions">
          <div class="mv-actions-l"><span class="mv-hint"><i class="ti ti-info-circle"></i> Sections 3/4/5 : rattachez chaque ligne à son N° défini en section 1.</span></div>
          <div class="mv-actions-r">
            <button v-if="!isLocked" class="mv-btn mv-btn-save" :style="`background:${mc}`" :disabled="busy" @click="saveFiche">
              <i :class="busy ? 'ti ti-loader-2 mv-spin' : 'ti ti-device-floppy'"></i> {{ form.id ? 'Enregistrer' : 'Créer la fiche' }}
            </button>
            <button v-if="form.id && form.validation_status === 'draft'" class="mv-btn mv-btn-submit" :disabled="busy" @click="soumettre"><i class="ti ti-send"></i> Soumettre au DM</button>
            <template v-if="canManage && form.validation_status === 'in_review'">
              <button class="mv-btn mv-btn-reject" :disabled="busy" @click="promptReject"><i class="ti ti-x"></i> Rejeter</button>
              <button class="mv-btn mv-btn-validate" :disabled="busy" @click="valider('validate')"><i class="ti ti-shield-check"></i> Valider</button>
            </template>
          </div>
        </div>
      </div>

      <Teleport to="body">
        <transition name="mv-toastx">
          <div v-if="toast.show" class="mv-toast" :class="`mv-toast-${toast.type}`">
            <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>{{ toast.msg }}
          </div>
        </transition>
      </Teleport>
    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
// ════════════════════════════════════════════════════════════════════
// AP · Méthodologie de vérification — 5 volets (lignes directrices,
// critères/sous-critères, sources, collecte, analyse). Selects branchés sur
// les paramètres AP (module Méthodologie). Section 1 pré-remplie depuis les
// questions du Champ d'action. Suggestion IA (Mistral) côté serveur.
// ════════════════════════════════════════════════════════════════════
import { computed, reactive, ref } from 'vue'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = defineProps({
  mission:      { type: Object,  default: null },
  auditeurs:    { type: Array,   default: () => [] },
  auditorRole:  { type: String,  default: null },
  record:       { type: Object,  default: null },
  form:         { type: Object,  default: null },
  refCriteres:        { type: Array, default: () => [] },
  refSousCriteres:    { type: Array, default: () => [] },
  refNaturePreuve:    { type: Array, default: () => [] },
  refSourcesPreuve:   { type: Array, default: () => [] },
  refMethodesCollecte:{ type: Array, default: () => [] },
  refMethodesAnalyse: { type: Array, default: () => [] },
  trameLignes:  { type: Array,   default: () => [] },
  aiEnabled:    { type: Boolean, default: false },
  noMission:       { type: Boolean, default: false },
  phaseNotStarted: { type: Boolean, default: false },
  missionId:    { type: Number, default: null },
  assignmentId: { type: Number, default: null },
  missionMenu:  { type: Array,  default: () => [] },
  backUrl:      { type: String, default: '' },
  formUrl:      { type: String, default: '' },
  chatBaseUrl:  { type: String, default: '' },
})

const mc = computed<string>(() => {
  const c = (props.mission as any)?.audit_color
  return c && c !== '#000000' && c !== 'null' ? c : '#0e7490'
})
const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))

const VOLETS = [
  { k: 'lignes',   n: '1', l: "Lignes directrices", icon: 'ti ti-list-numbers' },
  { k: 'criteres', n: '2', l: 'Critères',           icon: 'ti ti-scale' },
  { k: 'sources',  n: '3', l: "Sources de preuve",  icon: 'ti ti-git-branch' },
  { k: 'collecte', n: '4', l: 'Collecte',           icon: 'ti ti-clipboard-list' },
  { k: 'analyse',  n: '5', l: 'Analyse',            icon: 'ti ti-chart-histogram' },
]
const volet = ref('lignes')

function safeArr(v: any): any[] {
  if (Array.isArray(v)) return v
  if (!v) return []
  try { const p = JSON.parse(v); return Array.isArray(p) ? p : [] } catch { return [] }
}

const src = (props.record ?? props.form ?? {}) as any
const form = reactive<Record<string, any>>({
  id: src.id ?? null,
  lignes:   safeArr(src.lignes),
  criteres: safeArr(src.criteres),
  sources:  safeArr(src.sources),
  collecte: safeArr(src.collecte),
  analyse:  safeArr(src.analyse),
  synthese: src.synthese ?? '',
  fait_par: src.fait_par ?? '', revue_par: src.revue_par ?? '',
  validation_status: src.validation_status ?? null,
})

// Fiche neuve + section 1 vide → pré-remplir depuis les questions du Champ d'action.
if (!form.id && !form.lignes.length && (props.trameLignes as any[]).length) {
  form.lignes = (props.trameLignes as any[]).map(r => ({ ...r }))
}

const isLocked = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)

// N° de lignes directrices disponibles (pour les selects des sections 3/4/5)
const ligneNums = computed(() => form.lignes.map((_: any, i: number) => i + 1))

function count(k: string): number { return (form[k] ?? []).length }

function blankLigne()    { return { objectif_question: '', ligne_directrice: '', resultat_attendu: '' } }
function blankCritere()  { return { critere_principal: '', sous_critere: '', source_critere: '', libelle_retenu: '' } }
function blankSource()   { return { ligne_num: null, source_preuve: '', nature_preuve: '', modalites_obtention: '' } }
function blankCollecte() { return { ligne_num: null, methode_collecte: '', modalites_pratiques: '' } }
function blankAnalyse()  { return { ligne_num: null, methode_analyse: '', donnees_concernees: '', resultat_analyse: '' } }

function rechargerLignes() {
  const seen = new Set(form.lignes.map((l: any) => (l.objectif_question ?? '').trim().toLowerCase()))
  let added = 0
  for (const t of (props.trameLignes as any[])) {
    const key = (t.objectif_question ?? '').trim().toLowerCase()
    if (key && !seen.has(key)) { form.lignes.push({ ...t }); seen.add(key); added++ }
  }
  showToast('success', added ? `${added} ligne(s) importée(s) du Champ d'action` : 'Rien de nouveau à importer')
}

const busy = ref(false)
async function api(url: string, method: string, body?: object): Promise<any> {
  busy.value = true
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    const res = await fetch(url, {
      method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: body ? JSON.stringify(body) : undefined,
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json?.message ?? json?.error ?? 'Erreur')
    return json
  } finally { busy.value = false }
}

async function suggestIA() {
  if (!confirm('Générer une proposition IA de la méthodologie ? Les lignes existantes sont conservées, les suggestions sont ajoutées.')) return
  try {
    const json = await api(`${props.formUrl}/suggest-ia`, 'POST', { mission_id: props.missionId })
    for (const s of (json.lignes ?? [])) {
      const row = form.lignes.find((l: any) => (l.objectif_question ?? '').toLowerCase() === (s.objectif_question ?? '').toLowerCase())
      if (row) { if (!row.ligne_directrice) row.ligne_directrice = s.ligne_directrice ?? ''; if (!row.resultat_attendu) row.resultat_attendu = s.resultat_attendu ?? '' }
      else form.lignes.push({ ...blankLigne(), ...s })
    }
    for (const s of (json.criteres ?? [])) form.criteres.push({ ...blankCritere(), ...s })
    for (const s of (json.sources ?? []))  form.sources.push({ ...blankSource(), ...s })
    for (const s of (json.collecte ?? [])) form.collecte.push({ ...blankCollecte(), ...s })
    for (const s of (json.analyse ?? []))  form.analyse.push({ ...blankAnalyse(), ...s })
    showToast('success', 'Suggestions IA intégrées — relisez et ajustez.')
  } catch (e: any) { showToast('error', e.message) }
}

async function saveFiche() {
  try {
    const json = await api(props.formUrl, 'POST', {
      mission_id: props.missionId, assignment_id: props.assignmentId,
      synthese: form.synthese, fait_par: form.fait_par, revue_par: form.revue_par,
      lignes: form.lignes, criteres: form.criteres, sources: form.sources,
      collecte: form.collecte, analyse: form.analyse,
    })
    if (json.record) { form.id = json.record.id; form.validation_status = json.record.validation_status }
    showToast('success', 'Méthodologie enregistrée')
  } catch (e: any) { showToast('error', e.message) }
}

async function soumettre() {
  if (!confirm('Soumettre la méthodologie pour validation par le DM ?')) return
  try { const j = await api(`${props.formUrl}/${form.id}/soumettre`, 'POST', {}); form.validation_status = j.status; showToast('success', 'Soumise — en attente DM') }
  catch (e: any) { showToast('error', e.message) }
}
async function valider(action: 'validate' | 'reject', note?: string) {
  try { const j = await api(`${props.formUrl}/${form.id}/valider`, 'POST', { action, note }); form.validation_status = j.status; showToast('success', action === 'validate' ? 'Validée ✓' : 'Rejetée') }
  catch (e: any) { showToast('error', e.message) }
}
function promptReject() { const n = prompt('Motif du rejet (obligatoire) :'); if (n?.trim()) valider('reject', n) }

function vstLbl(s: string) { return ({ draft: 'Brouillon', in_review: 'Soumis — en revue', validated: 'Validé' } as any)[s] ?? s }
function vstIcon(s: string) { return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-lock' } as any)[s] ?? 'ti ti-pencil' }

const toast = ref({ show: false, type: 'success', msg: '' })
let tt: ReturnType<typeof setTimeout>
function showToast(type: string, msg: string) { toast.value = { show: true, type, msg }; clearTimeout(tt); tt = setTimeout(() => { toast.value.show = false }, 4000) }
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap');
* { box-sizing: border-box; }
.mv-shell { font-family: 'Plus Jakarta Sans', sans-serif; min-height: calc(100vh - 68px); background: #f4f7f6; color: #1e293b; }

.mv-header { position: sticky; top: 0; z-index: 30; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 8px rgba(15,23,42,.05); }
.mv-hrow { display: flex; align-items: center; gap: 14px; padding: 12px 22px 8px; }
.mv-back { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: var(--fcl); color: var(--fc); border: 1px solid var(--fcm); text-decoration: none; flex-shrink: 0; }
.mv-back:hover { background: var(--fc); color: #fff; }
.mv-hinfo { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.mv-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.mv-code { font-family: 'JetBrains Mono', monospace; font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; border: 1px solid; }
.mv-type { display: inline-flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; }
.mv-role { display: inline-flex; align-items: center; gap: 3px; font-size: .6rem; font-weight: 800; padding: 2px 7px; border-radius: 10px; }
.rc-DM { background: #fef3c7; color: #b45309; } .rc-CM { background: #dbeafe; color: #1d4ed8; } .rc-AS { background: #d1fae5; color: #047857; } .rc-AJ { background: #ede9fe; color: #6d28d9; }
.mv-vst { display: inline-flex; align-items: center; gap: 4px; font-size: .6rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; text-transform: uppercase; }
.avs-draft { background: #f1f5f9; color: #64748b; } .avs-in_review { background: #fef3c7; color: #b45309; } .avs-validated { background: #d1fae5; color: #047857; }
.mv-htitle { margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
.mv-sub { font-size: .72rem; font-weight: 600; color: #94a3b8; }
.mv-mission-strip { display: flex; flex-wrap: wrap; gap: 12px; }
.mv-mission-strip span { display: inline-flex; align-items: center; gap: 4px; font-size: .68rem; color: #64748b; }

.mv-tabs { display: flex; gap: 2px; padding: 0 14px; border-top: 1px solid #f1f5f9; overflow-x: auto; }
.mv-tab { display: inline-flex; align-items: center; gap: 6px; padding: 9px 12px; background: none; border: none; border-bottom: 2px solid transparent; color: #64748b; font-size: .72rem; font-weight: 700; cursor: pointer; font-family: inherit; white-space: nowrap; }
.mv-tab:hover { color: #334155; }
.mv-tab-n0 { font-family: 'JetBrains Mono', monospace; font-size: .62rem; font-weight: 800; opacity: .6; }
.mv-tab-n { font-size: .58rem; font-weight: 800; background: #f1f5f9; color: #64748b; border-radius: 8px; padding: 0 6px; }

.mv-banner { display: flex; align-items: center; gap: 8px; padding: 7px 22px; font-size: .73rem; font-weight: 600; border-top: 1px solid; }
.mv-banner-lock { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.mv-banner-review { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.mv-banner-warn { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }

.mv-body { padding: 14px 22px 88px; max-width: 1500px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }

.mv-aibar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 14px; }
.mv-aibar-txt { display: flex; align-items: center; gap: 8px; font-size: .72rem; color: #475569; }
.mv-ai-btn { display: inline-flex; align-items: center; gap: 6px; font-size: .74rem; font-weight: 700; color: #fff; padding: 8px 14px; border: none; border-radius: 9px; cursor: pointer; font-family: inherit; }
.mv-ai-btn:disabled { opacity: .5; cursor: not-allowed; }

.mv-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 6px rgba(15,23,42,.04); }
.mv-vhead { display: flex; align-items: center; gap: 8px; padding: 11px 16px; font-size: .82rem; font-weight: 800; color: #fff; }
.mv-vhead em { font-style: normal; font-weight: 500; font-size: .68rem; opacity: .82; }
.mv-vn { font-family: 'JetBrains Mono', monospace; }
.mv-vh1 { background: linear-gradient(90deg, #1e3a5f, #2c4a70); }
.mv-vh2 { background: linear-gradient(90deg, #1e3a5f, #2c4a70); }
.mv-vh3 { background: linear-gradient(90deg, #1e3a5f, #2c4a70); }
.mv-vh4 { background: linear-gradient(90deg, #1e3a5f, #2c4a70); }
.mv-vh5 { background: linear-gradient(90deg, #1e3a5f, #2c4a70); }

.mv-scroll { overflow-x: auto; }
.mv-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
.mv-table th { text-align: left; padding: 7px 8px; font-size: .58rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #475569; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.mv-table td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.mv-num { width: 40px; text-align: center; color: #94a3b8; font-weight: 700; }
.mv-lnum { width: 90px; }
.mv-table textarea, .mv-table input, .mv-table select { width: 100%; padding: 5px 7px; border-radius: 7px; border: 1px solid #e2e8f0; font-size: .7rem; font-family: inherit; color: #0f172a; outline: none; background: #fff; resize: vertical; }
.mv-table textarea:focus, .mv-table input:focus, .mv-table select:focus { border-color: var(--fc, #0e7490); }
.mv-table textarea:disabled, .mv-table input:disabled, .mv-table select:disabled { background: #f8fafc; color: #94a3b8; }
.mv-empty-row { text-align: center; padding: 18px; color: #94a3b8; font-style: italic; }
.mv-del { width: 26px; height: 26px; border-radius: 7px; border: 1px solid #fee2e2; background: #fff; color: #dc2626; cursor: pointer; }
.mv-del:hover { background: #dc2626; color: #fff; }

.mv-row-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 0 14px 12px; }
.mv-add { align-self: flex-start; margin: 10px 14px; display: inline-flex; align-items: center; gap: 5px; font-size: .7rem; font-weight: 700; padding: 6px 12px; border: 1px dashed; border-radius: 9px; background: transparent; cursor: pointer; font-family: inherit; }
.mv-row-actions .mv-add { margin: 10px 0 0; }
.mv-reload { margin-top: 10px; display: inline-flex; align-items: center; gap: 5px; font-size: .68rem; font-weight: 700; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; color: #475569; cursor: pointer; font-family: inherit; }
.mv-reload:hover { background: #f8fafc; }

.mv-sign { padding: 12px 16px; display: grid; grid-template-columns: 1fr 220px 220px; gap: 12px; }
@media (max-width: 800px) { .mv-sign { grid-template-columns: 1fr; } }
.mv-field { display: flex; flex-direction: column; gap: 4px; }
.mv-field label { font-size: .66rem; font-weight: 700; color: #475569; }
.mv-field input, .mv-field textarea { padding: 8px 10px; border-radius: 9px; border: 1px solid #e2e8f0; font-size: .74rem; font-family: inherit; outline: none; resize: vertical; }
.mv-field input:focus, .mv-field textarea:focus { border-color: var(--fc, #0e7490); }

.mv-actions { position: fixed; bottom: 0; left: 0; right: 0; z-index: 25; display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 22px; background: rgba(255,255,255,.94); backdrop-filter: blur(8px); border-top: 1px solid #e2e8f0; }
.mv-hint { display: inline-flex; align-items: center; gap: 5px; font-size: .64rem; color: #94a3b8; }
.mv-actions-r { display: flex; gap: 8px; flex-wrap: wrap; }
.mv-btn { display: inline-flex; align-items: center; gap: 6px; font-size: .74rem; font-weight: 700; padding: 8px 16px; border-radius: 9px; border: 1px solid transparent; cursor: pointer; font-family: inherit; }
.mv-btn:disabled { opacity: .55; cursor: not-allowed; }
.mv-btn-save { color: #fff; } .mv-btn-save:hover:not(:disabled) { filter: brightness(1.08); }
.mv-btn-submit { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; } .mv-btn-submit:hover:not(:disabled) { background: #1d4ed8; color: #fff; }
.mv-btn-validate { background: #ecfdf5; border-color: #a7f3d0; color: #047857; } .mv-btn-validate:hover:not(:disabled) { background: #047857; color: #fff; }
.mv-btn-reject { background: #fef2f2; border-color: #fecaca; color: #b91c1c; } .mv-btn-reject:hover:not(:disabled) { background: #b91c1c; color: #fff; }

.mv-toast { position: fixed; bottom: 74px; right: 22px; z-index: 2000; display: flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 11px; font-size: .76rem; font-weight: 700; box-shadow: 0 8px 30px rgba(15,23,42,.18); }
.mv-toast-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.mv-toast-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.mv-toastx-enter-active, .mv-toastx-leave-active { transition: all .2s ease; }
.mv-toastx-enter-from, .mv-toastx-leave-to { opacity: 0; transform: translateY(8px); }
.mv-spin { animation: mv-rot .7s linear infinite; }
@keyframes mv-rot { to { transform: rotate(360deg); } }
</style>
