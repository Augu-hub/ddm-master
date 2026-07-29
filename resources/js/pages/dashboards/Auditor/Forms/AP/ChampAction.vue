<template>
  <VerticalLayoutAudit>
    <div class="ca-shell">

      <!-- ══ HEADER — infos mission auto-chargées ══ -->
      <header class="ca-header" :style="`--fc:${mc};--fcl:${mc}18;--fcm:${mc}35`">
        <div class="ca-hrow">
          <a :href="props.backUrl" class="ca-back" title="Retour aux phases"><i class="ti ti-arrow-left"></i></a>
          <div class="ca-hinfo">
            <div class="ca-chips">
              <code class="ca-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">{{ mission?.code_mission ?? '—' }}</code>
              <span v-if="form.validation_status" class="ca-vst" :class="`avs-${form.validation_status}`">
                <i :class="vstIcon(form.validation_status)"></i>{{ vstLbl(form.validation_status) }}
              </span>
              <span class="ca-type" :style="`color:${mc};background:${mc}12`"><i class="ti ti-target-arrow"></i> CA · Champ d'action</span>
              <span v-if="props.auditorRole" class="ca-role" :class="`rc-${props.auditorRole}`"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
            </div>
            <h1 class="ca-htitle">Champ d'action <span class="ca-sub">— Sous-phase 1.4 · Planification de la mission</span></h1>
            <div class="ca-mission-strip">
              <span v-if="mission?.libelle"><i class="ti ti-clipboard-text"></i>{{ mission.libelle }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="mission?.date_debut_fr"><i class="ti ti-calendar"></i>{{ mission.date_debut_fr }} → {{ mission.date_fin_fr }}</span>
            </div>
          </div>
        </div>

        <!-- Sélecteur de volet -->
        <div class="ca-tabs">
          <button v-for="t in VOLETS" :key="t.k" class="ca-tab" :class="{ on: volet === t.k }"
            :style="volet === t.k ? `border-bottom-color:${mc};color:${mc}` : ''" @click="volet = t.k">
            <span class="ca-tab-n0">{{ t.n }}</span> <i :class="t.icon"></i> {{ t.l }}
            <span class="ca-tab-n">{{ count(t.k) }}</span>
          </button>
        </div>

        <div v-if="form.validation_status === 'validated'" class="ca-banner ca-banner-lock">
          <i class="ti ti-lock"></i><span>Fiche <strong>validée</strong> — lecture seule</span>
        </div>
        <div v-else-if="form.validation_status === 'in_review'" class="ca-banner ca-banner-review">
          <i class="ti ti-clock"></i><span>Soumise — en attente DM.<span v-if="canManage"> Vous pouvez valider ou rejeter.</span></span>
        </div>
        <div v-if="props.phaseNotStarted" class="ca-banner ca-banner-warn"><i class="ti ti-player-pause"></i><span>Phase non démarrée.</span></div>
        <div v-if="props.noMission" class="ca-banner ca-banner-warn"><i class="ti ti-alert-triangle"></i><span>Ouvrez ce formulaire depuis une mission.</span></div>
      </header>

      <div v-if="!props.noMission && !props.phaseNotStarted" class="ca-body">

        <!-- Barre IA -->
        <div v-if="!isLocked" class="ca-aibar">
          <div class="ca-aibar-txt">
            <i class="ti ti-sparkles" :style="`color:${mc}`"></i>
            <span>Assistant IA — propose approches, objectifs, questions, étendue, limites et conclusions à partir du contexte de la mission.</span>
          </div>
          <button class="ca-ai-btn" :style="`background:${mc}`" :disabled="!props.aiEnabled || busy" @click="suggestIA">
            <i :class="busy ? 'ti ti-loader-2 ca-spin' : 'ti ti-wand'"></i>
            {{ props.aiEnabled ? 'Suggérer avec l\'IA' : 'IA non configurée' }}
          </button>
        </div>

        <!-- ════ VOLET 1 · APPROCHES D'AUDIT ════ -->
        <section v-show="volet === 'approches'" class="ca-card">
          <div class="ca-vhead ca-vh1"><span class="ca-vn">1.</span> Approches d'audit <em>(suivant les grands critères — les 4 « E »)</em></div>
          <div class="ca-scroll">
            <table class="ca-table">
              <thead><tr><th class="ca-num">N°</th><th>Critère</th><th>Approche retenue</th><th>Justification</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(a, i) in form.approches" :key="i">
                  <td class="ca-num">{{ i + 1 }}</td>
                  <td>
                    <input v-if="a.critere_nature" :value="a.critere_nature" class="ca-ro-cell" disabled />
                    <input v-else v-model="a.critere_nature" type="text" :disabled="isLocked" placeholder="Critère…" />
                  </td>
                  <td>
                    <select v-model="a.approche_code" :disabled="isLocked" @change="onApprocheChange(a)">
                      <option value="">— Approche —</option>
                      <option v-for="ap in (props.approches as any[])" :key="ap.code" :value="ap.code">{{ ap.libelle }}</option>
                    </select>
                  </td>
                  <td><textarea v-model="a.justification" rows="2" :disabled="isLocked" placeholder="Justification du choix…"></textarea></td>
                  <td><button v-if="!isLocked" class="ca-del" @click="form.approches.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.approches.length"><td colspan="5" class="ca-empty-row">Aucune ligne — ajoutez un critère ou rechargez la trame.</td></tr>
              </tbody>
            </table>
          </div>
          <div class="ca-row-actions">
            <button v-if="!isLocked" class="ca-add" :style="`color:${mc};border-color:${mc}35`" @click="form.approches.push(blankApproche())"><i class="ti ti-plus"></i> Ajouter</button>
            <button v-if="!isLocked" class="ca-reload" @click="rechargerTrame('approches')"><i class="ti ti-refresh"></i> Recharger les critères</button>
          </div>
        </section>

        <!-- ════ VOLET 2 · OBJECTIFS D'AUDIT ════ -->
        <section v-show="volet === 'objectifs'" class="ca-card">
          <div class="ca-vhead ca-vh2"><span class="ca-vn">2.</span> Objectifs d'audit <em>(suivant les approches)</em></div>
          <div class="ca-scroll">
            <table class="ca-table">
              <thead><tr><th class="ca-num">N°</th><th>Objectif d'audit</th><th>Approche associée</th><th>Critère associé</th><th>Sous-critères / Source</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(o, i) in form.objectifs" :key="i">
                  <td class="ca-num">{{ i + 1 }}</td>
                  <td><textarea v-model="o.objectif" rows="2" :disabled="isLocked" placeholder="Objectif d'audit…"></textarea></td>
                  <td>
                    <select v-model="o.approche_code" :disabled="isLocked">
                      <option value="">—</option>
                      <option v-for="ap in (props.approches as any[])" :key="ap.code" :value="ap.code">{{ ap.libelle }}</option>
                    </select>
                  </td>
                  <td>
                    <select v-model="o.critere_code" :disabled="isLocked">
                      <option value="">—</option>
                      <option v-for="c in (props.criteres as any[])" :key="c.code" :value="c.code">{{ c.nature }}</option>
                    </select>
                  </td>
                  <td><textarea v-model="o.sous_criteres_source" rows="2" :disabled="isLocked" placeholder="Sous-critères / source…"></textarea></td>
                  <td><button v-if="!isLocked" class="ca-del" @click="form.objectifs.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.objectifs.length"><td colspan="6" class="ca-empty-row">Aucun objectif — ajoutez-en un.</td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="ca-add" :style="`color:${mc};border-color:${mc}35`" @click="form.objectifs.push(blankObjectif())"><i class="ti ti-plus"></i> Ajouter un objectif</button>
        </section>

        <!-- ════ VOLET 3 · QUESTION PRINCIPALE ════ -->
        <section v-show="volet === 'questions'" class="ca-card">
          <div class="ca-vhead ca-vh3"><span class="ca-vn">3.</span> Question principale <em>(suivant les approches)</em></div>
          <div class="ca-scroll">
            <table class="ca-table">
              <thead><tr><th class="ca-num">Obj. n°</th><th>Question principale d'audit</th><th>Sous-questions <em>(une par ligne)</em></th><th></th></tr></thead>
              <tbody>
                <tr v-for="(q, i) in form.questions" :key="i">
                  <td class="ca-num"><input v-model="q.objectif_num" type="text" class="ca-mini" :disabled="isLocked" placeholder="1" /></td>
                  <td><textarea v-model="q.question_principale" rows="3" :disabled="isLocked" placeholder="Les manuels ont-ils été distribués… ?"></textarea></td>
                  <td><textarea v-model="q.sous_questions" rows="3" :disabled="isLocked" placeholder="- Sous-question 1&#10;- Sous-question 2"></textarea></td>
                  <td><button v-if="!isLocked" class="ca-del" @click="form.questions.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.questions.length"><td colspan="4" class="ca-empty-row">Aucune question — ajoutez-en une.</td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="ca-add" :style="`color:${mc};border-color:${mc}35`" @click="form.questions.push(blankQuestion())"><i class="ti ti-plus"></i> Ajouter une question</button>
        </section>

        <!-- ════ VOLET 4 · ÉTENDUE DE L'AUDIT ════ -->
        <section v-show="volet === 'etendue'" class="ca-card">
          <div class="ca-vhead ca-vh4"><span class="ca-vn">4.</span> Étendue de l'audit <em>(périmètre Qui / Quoi / Quand / Où)</em></div>
          <div class="ca-scroll">
            <table class="ca-table">
              <thead><tr><th style="width:90px">Dimension</th><th>Questions clés <em>(rappel)</em></th><th>Contenu retenu pour cette mission</th><th>Justification</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(d, i) in form.etendue" :key="i">
                  <td><span class="ca-dim" :class="`dim-${slug(d.dimension_libelle)}`">{{ d.dimension_libelle || '—' }}</span></td>
                  <td><div class="ca-qcles">{{ d.questions_cles || '—' }}</div></td>
                  <td><textarea v-model="d.contenu" rows="3" :disabled="isLocked" placeholder="Contenu retenu…"></textarea></td>
                  <td><textarea v-model="d.justification" rows="3" :disabled="isLocked" placeholder="Justification…"></textarea></td>
                  <td><button v-if="!isLocked" class="ca-del" @click="form.etendue.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.etendue.length"><td colspan="5" class="ca-empty-row">Vide — rechargez les dimensions du périmètre.</td></tr>
              </tbody>
            </table>
          </div>
          <div class="ca-row-actions">
            <button v-if="!isLocked" class="ca-add" :style="`color:${mc};border-color:${mc}35`" @click="form.etendue.push(blankEtendue())"><i class="ti ti-plus"></i> Ajouter</button>
            <button v-if="!isLocked" class="ca-reload" @click="rechargerTrame('etendue')"><i class="ti ti-refresh"></i> Recharger Qui/Quoi/Quand/Où</button>
          </div>
        </section>

        <!-- ════ VOLET 5 · LIMITES ════ -->
        <section v-show="volet === 'limites'" class="ca-card">
          <div class="ca-vhead ca-vh5"><span class="ca-vn">5.</span> Limites de l'audit</div>
          <div class="ca-scroll">
            <table class="ca-table">
              <thead><tr><th class="ca-num">N°</th><th>Limite identifiée</th><th>Raison / Justification</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(l, i) in form.limites" :key="i">
                  <td class="ca-num">{{ i + 1 }}</td>
                  <td><textarea v-model="l.limite" rows="2" :disabled="isLocked" placeholder="Limite identifiée…"></textarea></td>
                  <td><textarea v-model="l.raison" rows="2" :disabled="isLocked" placeholder="Raison / justification…"></textarea></td>
                  <td><button v-if="!isLocked" class="ca-del" @click="form.limites.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.limites.length"><td colspan="4" class="ca-empty-row">Aucune limite — ajoutez-en une.</td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="ca-add" :style="`color:${mc};border-color:${mc}35`" @click="form.limites.push(blankLimite())"><i class="ti ti-plus"></i> Ajouter une limite</button>
        </section>

        <!-- ════ VOLET 6 · CONCLUSIONS POTENTIELLES ════ -->
        <section v-show="volet === 'conclusions'" class="ca-card">
          <div class="ca-vhead ca-vh6"><span class="ca-vn">6.</span> Conclusions potentielles</div>
          <div class="ca-scroll">
            <table class="ca-table">
              <thead><tr><th class="ca-num">N°</th><th>Conclusion potentielle envisagée</th><th>Lien avec l'objectif d'audit</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(c, i) in form.conclusions" :key="i">
                  <td class="ca-num">{{ i + 1 }}</td>
                  <td><textarea v-model="c.conclusion" rows="2" :disabled="isLocked" placeholder="Conclusion potentielle…"></textarea></td>
                  <td><input v-model="c.lien_objectif" type="text" :disabled="isLocked" placeholder="Objectif n°1" /></td>
                  <td><button v-if="!isLocked" class="ca-del" @click="form.conclusions.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.conclusions.length"><td colspan="4" class="ca-empty-row">Aucune conclusion — ajoutez-en une.</td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="ca-add" :style="`color:${mc};border-color:${mc}35`" @click="form.conclusions.push(blankConclusion())"><i class="ti ti-plus"></i> Ajouter une conclusion</button>
        </section>

        <!-- ════ VOLET 7 · PERTINENCE DU MANDAT ════ -->
        <section v-show="volet === 'pertinence'" class="ca-card">
          <div class="ca-vhead ca-vh7"><span class="ca-vn">7.</span> Pertinence du mandat de l'auditeur</div>
          <div class="ca-scroll">
            <table class="ca-table">
              <thead><tr><th>Question de vérification</th><th style="width:150px">Réponse</th><th>Commentaire</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(p, i) in form.pertinence" :key="i">
                  <td><textarea v-model="p.question" rows="2" :disabled="isLocked" placeholder="Question de vérification…"></textarea></td>
                  <td>
                    <select v-model="p.reponse" :disabled="isLocked" :class="repClass(p.reponse)">
                      <option value="">—</option><option>Oui</option><option>Non</option><option>Partiellement</option><option>N/A</option>
                    </select>
                  </td>
                  <td><textarea v-model="p.commentaire" rows="2" :disabled="isLocked" placeholder="Commentaire…"></textarea></td>
                  <td><button v-if="!isLocked" class="ca-del" @click="form.pertinence.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.pertinence.length"><td colspan="4" class="ca-empty-row">Vide — rechargez les questions type.</td></tr>
              </tbody>
            </table>
          </div>
          <div class="ca-row-actions">
            <button v-if="!isLocked" class="ca-add" :style="`color:${mc};border-color:${mc}35`" @click="form.pertinence.push(blankPertinence())"><i class="ti ti-plus"></i> Ajouter</button>
            <button v-if="!isLocked" class="ca-reload" @click="rechargerTrame('pertinence')"><i class="ti ti-refresh"></i> Recharger les questions type</button>
          </div>
        </section>

        <!-- Synthèse + signatures -->
        <section class="ca-card ca-sign">
          <div class="ca-field ca-field-full"><label>Synthèse du champ d'action</label>
            <textarea v-model="form.synthese" rows="2" :disabled="isLocked" placeholder="Conclusion sur le champ retenu…"></textarea></div>
          <div class="ca-field"><label>Fait par</label><input v-model="form.fait_par" type="text" list="ca-auditeurs" :disabled="isLocked" /></div>
          <div class="ca-field"><label>Revue par</label><input v-model="form.revue_par" type="text" list="ca-auditeurs" :disabled="isLocked" /></div>
          <datalist id="ca-auditeurs"><option v-for="a in (props.auditeurs as any[])" :key="a.id" :value="`${a.nom ?? ''} ${a.prenom ?? ''}`.trim()">{{ a.grade }}</option></datalist>
        </section>

        <!-- Actions -->
        <div class="ca-actions">
          <div class="ca-actions-l"><span class="ca-hint"><i class="ti ti-info-circle"></i> Les trames Approches / Étendue / Pertinence viennent du paramétrage AP.</span></div>
          <div class="ca-actions-r">
            <button v-if="!isLocked" class="ca-btn ca-btn-save" :style="`background:${mc}`" :disabled="busy" @click="saveFiche">
              <i :class="busy ? 'ti ti-loader-2 ca-spin' : 'ti ti-device-floppy'"></i> {{ form.id ? 'Enregistrer' : 'Créer la fiche' }}
            </button>
            <button v-if="form.id && form.validation_status === 'draft'" class="ca-btn ca-btn-submit" :disabled="busy" @click="soumettre"><i class="ti ti-send"></i> Soumettre au DM</button>
            <template v-if="canManage && form.validation_status === 'in_review'">
              <button class="ca-btn ca-btn-reject" :disabled="busy" @click="promptReject"><i class="ti ti-x"></i> Rejeter</button>
              <button class="ca-btn ca-btn-validate" :disabled="busy" @click="valider('validate')"><i class="ti ti-shield-check"></i> Valider</button>
            </template>
          </div>
        </div>
      </div>

      <Teleport to="body">
        <transition name="ca-toastx">
          <div v-if="toast.show" class="ca-toast" :class="`ca-toast-${toast.type}`">
            <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>{{ toast.msg }}
          </div>
        </transition>
      </Teleport>
    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
// ════════════════════════════════════════════════════════════════════
// AP · Champ d'action (sous-phase 1.4 · Planification) — 7 volets.
// Trames pré-remplies depuis les paramètres AP (critères, approches,
// dimensions du périmètre) + suggestion IA (Mistral) côté serveur.
// ════════════════════════════════════════════════════════════════════
import { computed, reactive, ref } from 'vue'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = defineProps({
  mission:      { type: Object,  default: null },
  auditeurs:    { type: Array,   default: () => [] },
  auditorRole:  { type: String,  default: null },
  record:       { type: Object,  default: null },
  form:         { type: Object,  default: null },
  criteres:     { type: Array,   default: () => [] },
  approches:    { type: Array,   default: () => [] },
  dimensions:   { type: Array,   default: () => [] },
  trameApproches:  { type: Array, default: () => [] },
  trameEtendue:    { type: Array, default: () => [] },
  tramePertinence: { type: Array, default: () => [] },
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
  return c && c !== '#000000' && c !== 'null' ? c : '#0891b2'
})
const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))

const VOLETS = [
  { k: 'approches',   n: '1', l: 'Approches',     icon: 'ti ti-route' },
  { k: 'objectifs',   n: '2', l: 'Objectifs',     icon: 'ti ti-target' },
  { k: 'questions',   n: '3', l: 'Question principale', icon: 'ti ti-help-circle' },
  { k: 'etendue',     n: '4', l: 'Étendue (Qui/Quoi/Quand/Où)', icon: 'ti ti-frame' },
  { k: 'limites',     n: '5', l: 'Limites',       icon: 'ti ti-line-dashed' },
  { k: 'conclusions', n: '6', l: 'Conclusions',   icon: 'ti ti-bulb' },
  { k: 'pertinence',  n: '7', l: 'Pertinence du mandat', icon: 'ti ti-gavel' },
]
const volet = ref('approches')

function safeArr(v: any): any[] {
  if (Array.isArray(v)) return v
  if (!v) return []
  try { const p = JSON.parse(v); return Array.isArray(p) ? p : [] } catch { return [] }
}

const src = (props.record ?? props.form ?? {}) as any
const form = reactive<Record<string, any>>({
  id: src.id ?? null,
  approches:   safeArr(src.approches),
  objectifs:   safeArr(src.objectifs),
  questions:   safeArr(src.questions),
  etendue:     safeArr(src.etendue),
  limites:     safeArr(src.limites),
  conclusions: safeArr(src.conclusions),
  pertinence:  safeArr(src.pertinence),
  synthese: src.synthese ?? '',
  fait_par: src.fait_par ?? '', revue_par: src.revue_par ?? '',
  validation_status: src.validation_status ?? null,
})

// Pré-remplissage des trames au premier affichage (fiche neuve, volet vide).
if (!form.id) {
  if (!form.approches.length)  form.approches  = (props.trameApproches as any[]).map(r => ({ ...r }))
  if (!form.etendue.length)    form.etendue    = (props.trameEtendue as any[]).map(r => ({ ...r }))
  if (!form.pertinence.length) form.pertinence = (props.tramePertinence as any[]).map(r => ({ ...r }))
}

const isLocked = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)

function count(k: string): number { return (form[k] ?? []).length }
function slug(c?: string) { return (c ?? '').toLowerCase().normalize('NFD').replace(/[^a-z]/g, '') }
function repClass(r?: string) { return ({ Oui: 'rep-oui', Non: 'rep-non', Partiellement: 'rep-part' } as any)[r ?? ''] ?? '' }

function blankApproche()   { return { critere_code: '', critere_nature: '', approche_code: '', approche_libelle: '', justification: '' } }
function blankObjectif()   { return { objectif: '', approche_code: '', critere_code: '', sous_criteres_source: '' } }
function blankQuestion()   { return { objectif_num: '', question_principale: '', sous_questions: '' } }
function blankEtendue()    { return { dimension_code: '', dimension_libelle: '', questions_cles: '', contenu: '', justification: '' } }
function blankLimite()     { return { limite: '', raison: '' } }
function blankConclusion() { return { conclusion: '', lien_objectif: '' } }
function blankPertinence() { return { question: '', reponse: '', commentaire: '' } }

function onApprocheChange(a: any) {
  const ap = (props.approches as any[]).find(x => x.code === a.approche_code)
  a.approche_libelle = ap?.libelle ?? ''
}

// Recharge une trame de paramètres sans écraser les lignes déjà saisies (fusion par clé).
function rechargerTrame(k: 'approches' | 'etendue' | 'pertinence') {
  const trame: any[] = k === 'approches' ? (props.trameApproches as any[])
    : k === 'etendue' ? (props.trameEtendue as any[]) : (props.tramePertinence as any[])
  const keyOf = (r: any) => k === 'approches' ? (r.critere_nature ?? '').toLowerCase()
    : k === 'etendue' ? (r.dimension_libelle ?? '').toLowerCase() : (r.question ?? '').toLowerCase()
  const seen = new Set(form[k].map(keyOf))
  let added = 0
  for (const t of trame) { if (!seen.has(keyOf(t))) { form[k].push({ ...t }); added++ } }
  showToast('success', added ? `${added} ligne(s) rechargée(s)` : 'Trame déjà complète')
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

// ── Suggestion IA : fusion non destructive dans les volets ──
async function suggestIA() {
  if (!confirm("Générer une proposition IA du champ d'action ? Les lignes existantes sont conservées, les suggestions sont ajoutées.")) return
  try {
    const json = await api(`${props.formUrl}/suggest-ia`, 'POST', { mission_id: props.missionId })

    // Approches : compléter par critère (nature)
    for (const s of (json.approches ?? [])) {
      const row = form.approches.find((a: any) => (a.critere_nature ?? '').toLowerCase() === (s.critere_nature ?? '').toLowerCase())
      if (row) {
        if (!row.justification) row.justification = s.justification ?? ''
        if (!row.approche_libelle && s.approche_libelle) {
          row.approche_libelle = s.approche_libelle
          const ap = (props.approches as any[]).find(x => (x.libelle ?? '').toLowerCase() === (s.approche_libelle ?? '').toLowerCase())
          if (ap) row.approche_code = ap.code
        }
      } else if (s.critere_nature) {
        form.approches.push({ ...blankApproche(), critere_nature: s.critere_nature, approche_libelle: s.approche_libelle ?? '', justification: s.justification ?? '' })
      }
    }
    // Étendue : compléter par dimension
    for (const s of (json.etendue ?? [])) {
      const row = form.etendue.find((d: any) => (d.dimension_libelle ?? '').toLowerCase() === (s.dimension_libelle ?? '').toLowerCase())
      if (row) { if (!row.contenu) row.contenu = s.contenu ?? ''; if (!row.justification) row.justification = s.justification ?? '' }
      else if (s.dimension_libelle) form.etendue.push({ ...blankEtendue(), dimension_libelle: s.dimension_libelle, contenu: s.contenu ?? '', justification: s.justification ?? '' })
    }
    // Objectifs / questions / limites / conclusions : ajout
    for (const s of (json.objectifs ?? []))   form.objectifs.push({ ...blankObjectif(), objectif: s.objectif ?? '', sous_criteres_source: s.sous_criteres_source ?? '', approche_code: codeForApproche(s.approche_libelle), critere_code: codeForCritere(s.critere_nature) })
    for (const s of (json.questions ?? []))    form.questions.push({ ...blankQuestion(), objectif_num: s.objectif_num ?? '', question_principale: s.question_principale ?? '', sous_questions: s.sous_questions ?? '' })
    for (const s of (json.limites ?? []))      form.limites.push({ ...blankLimite(), limite: s.limite ?? '', raison: s.raison ?? '' })
    for (const s of (json.conclusions ?? []))  form.conclusions.push({ ...blankConclusion(), conclusion: s.conclusion ?? '', lien_objectif: s.lien_objectif ?? '' })

    showToast('success', 'Suggestions IA intégrées — relisez et ajustez.')
  } catch (e: any) { showToast('error', e.message) }
}
function codeForApproche(lib?: string) { return ((props.approches as any[]).find(a => (a.libelle ?? '').toLowerCase() === (lib ?? '').toLowerCase()) ?? {}).code ?? '' }
function codeForCritere(nat?: string) { return ((props.criteres as any[]).find(c => (c.nature ?? '').toLowerCase() === (nat ?? '').toLowerCase()) ?? {}).code ?? '' }

async function saveFiche() {
  try {
    const json = await api(props.formUrl, 'POST', {
      mission_id: props.missionId, assignment_id: props.assignmentId,
      synthese: form.synthese, fait_par: form.fait_par, revue_par: form.revue_par,
      approches: form.approches, objectifs: form.objectifs, questions: form.questions,
      etendue: form.etendue, limites: form.limites, conclusions: form.conclusions, pertinence: form.pertinence,
    })
    if (json.record) { form.id = json.record.id; form.validation_status = json.record.validation_status }
    showToast('success', "Champ d'action enregistré")
  } catch (e: any) { showToast('error', e.message) }
}

async function soumettre() {
  if (!confirm('Soumettre le champ d\'action pour validation par le DM ?')) return
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
.ca-shell { font-family: 'Plus Jakarta Sans', sans-serif; min-height: calc(100vh - 68px); background: #f4f7f6; color: #1e293b; }

/* HEADER */
.ca-header { position: sticky; top: 0; z-index: 30; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 8px rgba(15,23,42,.05); }
.ca-hrow { display: flex; align-items: center; gap: 14px; padding: 12px 22px 8px; }
.ca-back { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: var(--fcl); color: var(--fc); border: 1px solid var(--fcm); text-decoration: none; flex-shrink: 0; }
.ca-back:hover { background: var(--fc); color: #fff; }
.ca-hinfo { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.ca-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.ca-code { font-family: 'JetBrains Mono', monospace; font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; border: 1px solid; }
.ca-type { display: inline-flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; }
.ca-role { display: inline-flex; align-items: center; gap: 3px; font-size: .6rem; font-weight: 800; padding: 2px 7px; border-radius: 10px; }
.rc-DM { background: #fef3c7; color: #b45309; } .rc-CM { background: #dbeafe; color: #1d4ed8; } .rc-AS { background: #d1fae5; color: #047857; } .rc-AJ { background: #ede9fe; color: #6d28d9; }
.ca-vst { display: inline-flex; align-items: center; gap: 4px; font-size: .6rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; text-transform: uppercase; }
.avs-draft { background: #f1f5f9; color: #64748b; } .avs-in_review { background: #fef3c7; color: #b45309; } .avs-validated { background: #d1fae5; color: #047857; }
.ca-htitle { margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
.ca-sub { font-size: .72rem; font-weight: 600; color: #94a3b8; }
.ca-mission-strip { display: flex; flex-wrap: wrap; gap: 12px; }
.ca-mission-strip span { display: inline-flex; align-items: center; gap: 4px; font-size: .68rem; color: #64748b; }

.ca-tabs { display: flex; gap: 2px; padding: 0 14px; border-top: 1px solid #f1f5f9; overflow-x: auto; }
.ca-tab { display: inline-flex; align-items: center; gap: 6px; padding: 9px 12px; background: none; border: none; border-bottom: 2px solid transparent; color: #64748b; font-size: .72rem; font-weight: 700; cursor: pointer; font-family: inherit; white-space: nowrap; }
.ca-tab:hover { color: #334155; }
.ca-tab-n0 { font-family: 'JetBrains Mono', monospace; font-size: .62rem; font-weight: 800; opacity: .6; }
.ca-tab-n { font-size: .58rem; font-weight: 800; background: #f1f5f9; color: #64748b; border-radius: 8px; padding: 0 6px; }

.ca-banner { display: flex; align-items: center; gap: 8px; padding: 7px 22px; font-size: .73rem; font-weight: 600; border-top: 1px solid; }
.ca-banner-lock { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.ca-banner-review { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.ca-banner-warn { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }

/* BODY */
.ca-body { padding: 14px 22px 88px; max-width: 1500px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }

.ca-aibar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 14px; }
.ca-aibar-txt { display: flex; align-items: center; gap: 8px; font-size: .72rem; color: #475569; }
.ca-ai-btn { display: inline-flex; align-items: center; gap: 6px; font-size: .74rem; font-weight: 700; color: #fff; padding: 8px 14px; border: none; border-radius: 9px; cursor: pointer; font-family: inherit; }
.ca-ai-btn:disabled { opacity: .5; cursor: not-allowed; }

.ca-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 6px rgba(15,23,42,.04); }
.ca-vhead { display: flex; align-items: center; gap: 8px; padding: 11px 16px; font-size: .82rem; font-weight: 800; color: #fff; }
.ca-vhead em { font-style: normal; font-weight: 500; font-size: .68rem; opacity: .82; }
.ca-vn { font-family: 'JetBrains Mono', monospace; }
.ca-vh1 { background: linear-gradient(90deg, #1e3a5f, #2c4a70); }
.ca-vh2 { background: linear-gradient(90deg, #0e4b5a, #12707f); }
.ca-vh3 { background: linear-gradient(90deg, #3730a3, #4f46e5); }
.ca-vh4 { background: linear-gradient(90deg, #92400e, #d97706); }
.ca-vh5 { background: linear-gradient(90deg, #5b1e2c, #7a2c3c); }
.ca-vh6 { background: linear-gradient(90deg, #1e4620, #2d5a30); }
.ca-vh7 { background: linear-gradient(90deg, #334155, #475569); }

.ca-scroll { overflow-x: auto; }
.ca-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
.ca-table th { text-align: left; padding: 7px 8px; font-size: .58rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #475569; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.ca-table th em { text-transform: none; font-weight: 500; letter-spacing: 0; color: #94a3b8; }
.ca-table td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.ca-num { width: 46px; text-align: center; color: #94a3b8; font-weight: 700; }
.ca-table textarea, .ca-table input, .ca-table select { width: 100%; padding: 5px 7px; border-radius: 7px; border: 1px solid #e2e8f0; font-size: .7rem; font-family: inherit; color: #0f172a; outline: none; background: #fff; resize: vertical; }
.ca-table textarea:focus, .ca-table input:focus, .ca-table select:focus { border-color: var(--fc, #0891b2); }
.ca-table textarea:disabled, .ca-table input:disabled, .ca-table select:disabled { background: #f8fafc; color: #94a3b8; }
.ca-ro-cell { background: #f8fafc !important; font-weight: 700; color: #334155 !important; }
.ca-mini { text-align: center; }
.ca-qcles { font-size: .66rem; color: #64748b; white-space: pre-line; line-height: 1.35; max-width: 320px; }
.ca-dim { display: inline-block; font-size: .64rem; font-weight: 800; padding: 3px 9px; border-radius: 10px; background: #f1f5f9; color: #475569; }
.dim-qui { background: #dbeafe; color: #1d4ed8; } .dim-quoi { background: #dcfce7; color: #15803d; } .dim-quand { background: #fef3c7; color: #b45309; } .dim-ou, .dim-o { background: #fae8ff; color: #a21caf; }
.ca-empty-row { text-align: center; padding: 18px; color: #94a3b8; font-style: italic; }
.ca-del { width: 26px; height: 26px; border-radius: 7px; border: 1px solid #fee2e2; background: #fff; color: #dc2626; cursor: pointer; }
.ca-del:hover { background: #dc2626; color: #fff; }
select.rep-oui { background: #ecfdf5; } select.rep-non { background: #fef2f2; } select.rep-part { background: #fff7ed; }

.ca-row-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 0 14px 12px; }
.ca-add { align-self: flex-start; margin: 10px 14px; display: inline-flex; align-items: center; gap: 5px; font-size: .7rem; font-weight: 700; padding: 6px 12px; border: 1px dashed; border-radius: 9px; background: transparent; cursor: pointer; font-family: inherit; }
.ca-row-actions .ca-add { margin: 10px 0 0; }
.ca-reload { margin-top: 10px; display: inline-flex; align-items: center; gap: 5px; font-size: .68rem; font-weight: 700; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; color: #475569; cursor: pointer; font-family: inherit; }
.ca-reload:hover { background: #f8fafc; }

.ca-sign { padding: 12px 16px; display: grid; grid-template-columns: 1fr 220px 220px; gap: 12px; }
@media (max-width: 800px) { .ca-sign { grid-template-columns: 1fr; } }
.ca-field { display: flex; flex-direction: column; gap: 4px; }
.ca-field label { font-size: .66rem; font-weight: 700; color: #475569; }
.ca-field input, .ca-field textarea { padding: 8px 10px; border-radius: 9px; border: 1px solid #e2e8f0; font-size: .74rem; font-family: inherit; outline: none; resize: vertical; }
.ca-field input:focus, .ca-field textarea:focus { border-color: var(--fc, #0891b2); }

.ca-actions { position: fixed; bottom: 0; left: 0; right: 0; z-index: 25; display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 22px; background: rgba(255,255,255,.94); backdrop-filter: blur(8px); border-top: 1px solid #e2e8f0; }
.ca-hint { display: inline-flex; align-items: center; gap: 5px; font-size: .64rem; color: #94a3b8; }
.ca-actions-r { display: flex; gap: 8px; flex-wrap: wrap; }
.ca-btn { display: inline-flex; align-items: center; gap: 6px; font-size: .74rem; font-weight: 700; padding: 8px 16px; border-radius: 9px; border: 1px solid transparent; cursor: pointer; font-family: inherit; }
.ca-btn:disabled { opacity: .55; cursor: not-allowed; }
.ca-btn-save { color: #fff; } .ca-btn-save:hover:not(:disabled) { filter: brightness(1.08); }
.ca-btn-submit { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; } .ca-btn-submit:hover:not(:disabled) { background: #1d4ed8; color: #fff; }
.ca-btn-validate { background: #ecfdf5; border-color: #a7f3d0; color: #047857; } .ca-btn-validate:hover:not(:disabled) { background: #047857; color: #fff; }
.ca-btn-reject { background: #fef2f2; border-color: #fecaca; color: #b91c1c; } .ca-btn-reject:hover:not(:disabled) { background: #b91c1c; color: #fff; }

.ca-toast { position: fixed; bottom: 74px; right: 22px; z-index: 2000; display: flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 11px; font-size: .76rem; font-weight: 700; box-shadow: 0 8px 30px rgba(15,23,42,.18); }
.ca-toast-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.ca-toast-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.ca-toastx-enter-active, .ca-toastx-leave-active { transition: all .2s ease; }
.ca-toastx-enter-from, .ca-toastx-leave-to { opacity: 0; transform: translateY(8px); }
.ca-spin { animation: ca-rot .7s linear infinite; }
@keyframes ca-rot { to { transform: rotate(360deg); } }
</style>
