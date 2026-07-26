<template>
  <VerticalLayoutAudit>
    <div class="pa-shell">

      <!-- ══ HEADER compact — infos mission auto-chargées ══ -->
      <header class="pa-header" :style="`--fc:${mc};--fcl:${mc}18;--fcm:${mc}35`">
        <div class="pa-hrow">
          <a :href="props.backUrl" class="pa-back" title="Retour aux phases"><i class="ti ti-arrow-left"></i></a>
          <div class="pa-hinfo">
            <div class="pa-chips">
              <code class="pa-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">{{ mission?.code_mission ?? '—' }}</code>
              <span v-if="form.validation_status" class="pa-vst" :class="`avs-${form.validation_status}`">
                <i :class="vstIcon(form.validation_status)"></i>{{ vstLbl(form.validation_status) }}
              </span>
              <span class="pa-type" :style="`color:${mc};background:${mc}12`"><i class="ti ti-viewfinder"></i> PA · Portée de l'audit</span>
              <span v-if="props.auditorRole" class="pa-role" :class="`rc-${props.auditorRole}`"><i class="ti ti-shield-half"></i>{{ props.auditorRole }}</span>
            </div>
            <h1 class="pa-htitle">Portée de l'audit</h1>
            <!-- Bandeau mission auto (pas de champ vide, pas de scroll) -->
            <div class="pa-mission-strip">
              <span v-if="mission?.libelle"><i class="ti ti-clipboard-text"></i>{{ mission.libelle }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="mission?.date_debut_fr"><i class="ti ti-calendar"></i>{{ mission.date_debut_fr }} → {{ mission.date_fin_fr }}</span>
              <span v-if="programmeName" class="pa-prog"><i class="ti ti-route"></i>{{ programmeName }}</span>
            </div>
          </div>
        </div>

        <!-- Sélecteur de volet -->
        <div class="pa-tabs">
          <button v-for="t in VOLETS" :key="t.k" class="pa-tab" :class="{ on: volet === t.k }"
            :style="volet === t.k ? `border-bottom-color:${mc};color:${mc}` : ''" @click="volet = t.k">
            <i :class="t.icon"></i> {{ t.l }}
            <span class="pa-tab-n">{{ count(t.k) }}</span>
          </button>
        </div>

        <div v-if="form.validation_status === 'validated'" class="pa-banner pa-banner-lock">
          <i class="ti ti-lock"></i><span>Fiche <strong>validée</strong> — lecture seule</span>
        </div>
        <div v-else-if="form.validation_status === 'in_review'" class="pa-banner pa-banner-review">
          <i class="ti ti-clock"></i><span>Soumise — en attente DM.<span v-if="canManage"> Vous pouvez valider ou rejeter.</span></span>
        </div>
        <div v-if="props.phaseNotStarted" class="pa-banner pa-banner-warn"><i class="ti ti-player-pause"></i><span>Phase non démarrée.</span></div>
        <div v-if="props.noMission" class="pa-banner pa-banner-warn"><i class="ti ti-alert-triangle"></i><span>Ouvrez ce formulaire depuis une mission.</span></div>
      </header>

      <div v-if="!props.noMission && !props.phaseNotStarted" class="pa-body">

        <!-- Liaison programme -->
        <div class="pa-progbar">
          <label><i class="ti ti-route" :style="`color:${mc}`"></i> Programme audité</label>
          <select v-model="form.process_id" :disabled="isLocked" @change="onProcessChange">
            <option :value="null">— Sélectionner le programme —</option>
            <option v-for="p in (props.programmes as any[])" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <button v-if="form.process_id && !isLocked" class="pa-load" :style="`border-color:${mc}40;color:${mc}`" :disabled="busy" @click="chargerCadre">
            <i class="ti ti-database-import"></i> Charger depuis la base
          </button>
        </div>

        <!-- ════ VOLET 1 · IMPORTANCE DES SUJETS ════ -->
        <section v-show="volet === 'sujets'" class="pa-card">
          <div class="pa-vhead pa-vh1"><span class="pa-vn">1.</span> Importance des sujets</div>
          <div class="pa-scroll">
            <table class="pa-table">
              <thead>
                <tr>
                  <th class="pa-num">N°</th><th>Sujet / Thème examiné</th><th>Enjeu (description courte)</th>
                  <th>Poids budgétaire ou social estimé</th><th>Niveau d'importance</th><th>Justification</th><th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(s, i) in form.sujets" :key="i">
                  <td class="pa-num">{{ i + 1 }}</td>
                  <td><textarea v-model="s.sujet" rows="2" :disabled="isLocked" placeholder="Sujet examiné…"></textarea></td>
                  <td><textarea v-model="s.enjeu" rows="2" :disabled="isLocked" placeholder="Enjeu…"></textarea></td>
                  <td><input v-model="s.poids" type="text" :disabled="isLocked" placeholder="ex : 1,2 Md FCFA" /></td>
                  <td>
                    <select v-model="s.niveau_importance" :disabled="isLocked" :class="nivClass(s.niveau_importance)">
                      <option value="">—</option><option>Faible</option><option>Moyen</option><option>Élevé</option>
                    </select>
                  </td>
                  <td><textarea v-model="s.justification" rows="2" :disabled="isLocked" placeholder="Justification…"></textarea></td>
                  <td><button v-if="!isLocked" class="pa-del" @click="form.sujets.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.sujets.length"><td colspan="7" class="pa-empty-row">Aucun sujet — ajoutez-en un.</td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="pa-add" :style="`color:${mc};border-color:${mc}35`" @click="form.sujets.push(blankSujet())">
            <i class="ti ti-plus"></i> Ajouter un sujet
          </button>
        </section>

        <!-- ════ VOLET 2 · RESSOURCES ET RÉSULTATS ════ -->
        <section v-show="volet === 'ressources'" class="pa-card">
          <div class="pa-vhead pa-vh2"><span class="pa-vn">2.</span> Analyse des ressources et résultats <em>(cadre logique)</em></div>
          <div class="pa-scroll">
            <table class="pa-table">
              <thead>
                <tr>
                  <th class="pa-num">N°</th><th>Composante du cadre logique</th><th>Libellé / Description</th>
                  <th>Indicateur associé</th><th>Valeur / Quantité</th><th>Unité</th><th>Source de la donnée</th><th>Observations</th><th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(r, i) in form.ressources" :key="i" :class="`cl-${slug(r.composante)}`">
                  <td class="pa-num">{{ i + 1 }}</td>
                  <td>
                    <select v-model="r.composante" :disabled="isLocked">
                      <option value="">—</option>
                      <option v-for="c in CADRE" :key="c">{{ c }}</option>
                    </select>
                  </td>
                  <td><textarea v-model="r.libelle" rows="2" :disabled="isLocked"></textarea></td>
                  <td><textarea v-model="r.indicateur" rows="2" :disabled="isLocked"></textarea></td>
                  <td><input v-model="r.valeur" type="text" :disabled="isLocked" /></td>
                  <td><input v-model="r.unite" type="text" :disabled="isLocked" class="pa-unite" /></td>
                  <td><textarea v-model="r.source" rows="2" :disabled="isLocked"></textarea></td>
                  <td><textarea v-model="r.observations" rows="2" :disabled="isLocked"></textarea></td>
                  <td><button v-if="!isLocked" class="pa-del" @click="form.ressources.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.ressources.length"><td colspan="9" class="pa-empty-row">
                  Vide — sélectionnez un programme puis « Charger depuis la base », ou ajoutez une ligne.
                </td></tr>
              </tbody>
            </table>
          </div>
          <button v-if="!isLocked" class="pa-add" :style="`color:${mc};border-color:${mc}35`" @click="form.ressources.push(blankRessource())">
            <i class="ti ti-plus"></i> Ajouter une ligne
          </button>
        </section>

        <!-- ════ VOLET 3 · RISQUES D'AUDIT ════ -->
        <section v-show="volet === 'risques'" class="pa-card">
          <div class="pa-vhead pa-vh3"><span class="pa-vn">3.</span> Analyse des risques d'audit <em>(brut → mitigation → net)</em></div>
          <div class="pa-scroll">
            <table class="pa-table pa-risktable">
              <thead>
                <tr>
                  <th class="pa-num">N°</th><th>Libellé du risque</th><th>Catégorie</th>
                  <th class="pa-pi">Prob. brute</th><th class="pa-pi">Impact brut</th><th class="pa-sc">Score</th><th class="pa-nv">Niveau brut</th>
                  <th>Moyens de mitigation</th>
                  <th class="pa-pi">Prob. nette</th><th class="pa-pi">Impact net</th><th class="pa-sc">Score</th><th class="pa-nv">Niveau net</th>
                  <th>Responsable</th><th>Observations</th><th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(r, i) in form.risques" :key="i">
                  <td class="pa-num">{{ i + 1 }}</td>
                  <td><textarea v-model="r.libelle" rows="2" :disabled="isLocked"></textarea></td>
                  <td><input v-model="r.categorie" type="text" list="pa-cats" :disabled="isLocked" /></td>
                  <td class="pa-pi"><select v-model.number="r.proba_brute" :disabled="isLocked"><option v-for="n in [1,2,3]" :key="n" :value="n">{{ n }}</option></select></td>
                  <td class="pa-pi"><select v-model.number="r.impact_brut" :disabled="isLocked"><option v-for="n in [1,2,3]" :key="n" :value="n">{{ n }}</option></select></td>
                  <td class="pa-sc">{{ score(r.proba_brute, r.impact_brut) }}</td>
                  <td class="pa-nv"><span class="pa-niv" :class="nivScoreClass(score(r.proba_brute, r.impact_brut))">{{ nivScore(score(r.proba_brute, r.impact_brut)) }}</span></td>
                  <td><textarea v-model="r.moyens_mitigation" rows="2" :disabled="isLocked"></textarea></td>
                  <td class="pa-pi"><select v-model.number="r.proba_nette" :disabled="isLocked"><option v-for="n in [1,2,3]" :key="n" :value="n">{{ n }}</option></select></td>
                  <td class="pa-pi"><select v-model.number="r.impact_nette" :disabled="isLocked"><option v-for="n in [1,2,3]" :key="n" :value="n">{{ n }}</option></select></td>
                  <td class="pa-sc">{{ score(r.proba_nette, r.impact_nette) }}</td>
                  <td class="pa-nv"><span class="pa-niv" :class="nivScoreClass(score(r.proba_nette, r.impact_nette))">{{ nivScore(score(r.proba_nette, r.impact_nette)) }}</span></td>
                  <td><input v-model="r.responsable" type="text" :disabled="isLocked" /></td>
                  <td><textarea v-model="r.observations" rows="2" :disabled="isLocked"></textarea></td>
                  <td><button v-if="!isLocked" class="pa-del" @click="form.risques.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <tr v-if="!form.risques.length"><td colspan="15" class="pa-empty-row">Aucun risque — ajoutez-en ou insérez un risque type.</td></tr>
              </tbody>
            </table>
            <datalist id="pa-cats"><option v-for="c in CATS" :key="c" :value="c" /></datalist>
          </div>
          <div class="pa-risk-actions">
            <button v-if="!isLocked" class="pa-add" :style="`color:${mc};border-color:${mc}35`" @click="form.risques.push(blankRisque())">
              <i class="ti ti-plus"></i> Ajouter un risque
            </button>
            <div v-if="!isLocked && (props.risquesTypes as any[]).length" class="pa-suggest">
              <span>Risques types :</span>
              <button v-for="(rt, i) in (props.risquesTypes as any[])" :key="i" class="pa-sugg-chip"
                :style="`border-color:${mc}30;color:${mc}`" @click="addRisqueType(rt)">
                <i class="ti ti-plus"></i> {{ rt.libelle }}
              </button>
            </div>
          </div>
        </section>

        <!-- Synthèse + signatures -->
        <section class="pa-card pa-sign">
          <div class="pa-field pa-field-full"><label>Synthèse de la portée</label>
            <textarea v-model="form.synthese" rows="2" :disabled="isLocked" placeholder="Conclusion sur le périmètre retenu…"></textarea></div>
          <div class="pa-field"><label>Fait par</label><input v-model="form.fait_par" type="text" list="pa-auditeurs" :disabled="isLocked" /></div>
          <div class="pa-field"><label>Revue par</label><input v-model="form.revue_par" type="text" list="pa-auditeurs" :disabled="isLocked" /></div>
          <datalist id="pa-auditeurs"><option v-for="a in (props.auditeurs as any[])" :key="a.id" :value="`${a.nom ?? ''} ${a.prenom ?? ''}`.trim()">{{ a.grade }}</option></datalist>
        </section>

        <!-- Actions -->
        <div class="pa-actions">
          <div class="pa-actions-l">
            <span class="pa-risk-legend"><i class="pa-niv niv-faible"></i>Faible <i class="pa-niv niv-moyen"></i>Moyen <i class="pa-niv niv-eleve"></i>Élevé</span>
          </div>
          <div class="pa-actions-r">
            <button v-if="!isLocked" class="pa-btn pa-btn-save" :style="`background:${mc}`" :disabled="busy" @click="saveFiche">
              <i :class="busy ? 'ti ti-loader-2 pa-spin' : 'ti ti-device-floppy'"></i> {{ form.id ? 'Enregistrer' : 'Créer la fiche' }}
            </button>
            <button v-if="form.id && form.validation_status === 'draft'" class="pa-btn pa-btn-submit" :disabled="busy" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre au DM
            </button>
            <template v-if="canManage && form.validation_status === 'in_review'">
              <button class="pa-btn pa-btn-reject" :disabled="busy" @click="promptReject"><i class="ti ti-x"></i> Rejeter</button>
              <button class="pa-btn pa-btn-validate" :disabled="busy" @click="valider('validate')"><i class="ti ti-shield-check"></i> Valider</button>
            </template>
          </div>
        </div>
      </div>

      <Teleport to="body">
        <transition name="pa-toastx">
          <div v-if="toast.show" class="pa-toast" :class="`pa-toast-${toast.type}`">
            <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>{{ toast.msg }}
          </div>
        </transition>
      </Teleport>
    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
// ════════════════════════════════════════════════════════════════════
// AP · Portée de l'audit — 3 volets (importance des sujets, cadre logique
// ressources/résultats, risques brut→net). Auto-alimenté depuis le
// programme audité (objectifs + activités de la base). Scoring P×I auto.
// ════════════════════════════════════════════════════════════════════
import { computed, reactive, ref } from 'vue'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const props = defineProps({
  mission:      { type: Object,  default: null },
  auditeurs:    { type: Array,   default: () => [] },
  auditorRole:  { type: String,  default: null },
  record:       { type: Object,  default: null },
  form:         { type: Object,  default: null },
  programmes:   { type: Array,   default: () => [] },
  pcProcessId:  { type: Number,  default: null },
  cadreLogiqueBase: { type: Array, default: () => [] },
  risquesTypes: { type: Array,   default: () => [] },
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
  return c && c !== '#000000' && c !== 'null' ? c : '#059669'
})
const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))

const VOLETS = [
  { k: 'sujets',     l: 'Importance des sujets', icon: 'ti ti-list-numbers' },
  { k: 'ressources', l: 'Ressources & résultats', icon: 'ti ti-sitemap' },
  { k: 'risques',    l: "Risques d'audit",        icon: 'ti ti-alert-triangle' },
]
const CADRE = ['Intrants', 'Activités', 'Extrants', 'Résultats', 'Impact']
const CATS  = ["Disponibilité de l'information", 'Qualité des données', 'Traçabilité', 'Cadrage', 'Organisation', 'Conformité']
const volet = ref('sujets')

function safeArr(v: any): any[] {
  if (Array.isArray(v)) return v
  if (!v) return []
  try { const p = JSON.parse(v); return Array.isArray(p) ? p : [] } catch { return [] }
}

const src = (props.record ?? props.form ?? {}) as any
const form = reactive<Record<string, any>>({
  id: src.id ?? null,
  process_id: src.process_id ?? props.pcProcessId ?? null,
  sujets: safeArr(src.sujets),
  ressources: safeArr(src.ressources),
  risques: safeArr(src.risques),
  synthese: src.synthese ?? '',
  fait_par: src.fait_par ?? '', revue_par: src.revue_par ?? '',
  validation_status: src.validation_status ?? null,
})

const isLocked = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)

const programmeName = computed(() =>
  ((props.programmes as any[]).find(p => p.id === form.process_id) ?? {}).name ?? null
)

// Auto : si la fiche est neuve et vide, pré-remplir le cadre logique depuis
// la base (aucun champ vide au premier affichage si un programme est lié).
if (!form.id && !form.ressources.length && (props.cadreLogiqueBase as any[]).length) {
  form.ressources = (props.cadreLogiqueBase as any[]).map(r => ({ ...r }))
}

function count(k: string): number { return (form[k] ?? []).length }
function slug(c?: string) { return (c ?? '').toLowerCase().normalize('NFD').replace(/[^a-z]/g, '') }

function blankSujet() { return { sujet: '', enjeu: '', poids: '', niveau_importance: '', justification: '' } }
function blankRessource() { return { composante: '', libelle: '', indicateur: '', valeur: '', unite: '', source: '', observations: '' } }
function blankRisque() { return { libelle: '', categorie: '', proba_brute: 1, impact_brut: 1, moyens_mitigation: '', proba_nette: 1, impact_nette: 1, responsable: '', observations: '' } }
function addRisqueType(rt: any) { form.risques.push({ ...blankRisque(), libelle: rt.libelle, categorie: rt.categorie }) }

// Scoring
function score(p?: number, i?: number): number { return (Number(p) || 0) * (Number(i) || 0) }
function nivScore(s: number): string { return s >= 6 ? 'Élevé' : s >= 3 ? 'Moyen' : s > 0 ? 'Faible' : '—' }
function nivScoreClass(s: number): string { return s >= 6 ? 'niv-eleve' : s >= 3 ? 'niv-moyen' : s > 0 ? 'niv-faible' : 'niv-none' }
function nivClass(n?: string): string { return ({ 'Élevé': 'pa-niv-eleve', 'Moyen': 'pa-niv-moyen', 'Faible': 'pa-niv-faible' } as any)[n ?? ''] ?? '' }

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

function onProcessChange() { /* la trame se charge à la demande via le bouton */ }

async function chargerCadre() {
  try {
    const json = await api(`${props.formUrl}/suggest-cadre`, 'POST', { process_id: form.process_id })
    const lignes = json.lignes ?? []
    // Fusion : on ajoute uniquement les lignes non déjà présentes (par libellé)
    const seen = new Set(form.ressources.map((r: any) => (r.libelle ?? '').trim().toLowerCase()))
    let added = 0
    for (const l of lignes) {
      const key = (l.libelle ?? '').trim().toLowerCase()
      if (!seen.has(key)) { form.ressources.push({ ...l }); seen.add(key); added++ }
    }
    volet.value = 'ressources'
    showToast('success', added ? `${added} ligne(s) chargée(s) depuis la base` : 'Rien de nouveau à charger')
  } catch (e: any) { showToast('error', e.message) }
}

async function saveFiche() {
  try {
    const json = await api(props.formUrl, 'POST', {
      mission_id: props.missionId, assignment_id: props.assignmentId,
      process_id: form.process_id, synthese: form.synthese,
      fait_par: form.fait_par, revue_par: form.revue_par,
      sujets: form.sujets, ressources: form.ressources, risques: form.risques,
    })
    if (json.record) { form.id = json.record.id; form.validation_status = json.record.validation_status }
    showToast('success', 'Portée enregistrée')
  } catch (e: any) { showToast('error', e.message) }
}

async function soumettre() {
  if (!confirm('Soumettre la portée pour validation par le DM ?')) return
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
.pa-shell { font-family: 'Plus Jakarta Sans', sans-serif; min-height: calc(100vh - 68px); background: #f4f7f6; color: #1e293b; }

/* HEADER */
.pa-header { position: sticky; top: 0; z-index: 30; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 8px rgba(15,23,42,.05); }
.pa-hrow { display: flex; align-items: center; gap: 14px; padding: 12px 22px 8px; }
.pa-back { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: var(--fcl); color: var(--fc); border: 1px solid var(--fcm); text-decoration: none; flex-shrink: 0; }
.pa-back:hover { background: var(--fc); color: #fff; }
.pa-hinfo { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.pa-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.pa-code { font-family: 'JetBrains Mono', monospace; font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; border: 1px solid; }
.pa-type { display: inline-flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; }
.pa-role { display: inline-flex; align-items: center; gap: 3px; font-size: .6rem; font-weight: 800; padding: 2px 7px; border-radius: 10px; }
.rc-DM { background: #fef3c7; color: #b45309; } .rc-CM { background: #dbeafe; color: #1d4ed8; } .rc-AS { background: #d1fae5; color: #047857; } .rc-AJ { background: #ede9fe; color: #6d28d9; }
.pa-vst { display: inline-flex; align-items: center; gap: 4px; font-size: .6rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; text-transform: uppercase; }
.avs-draft { background: #f1f5f9; color: #64748b; } .avs-in_review { background: #fef3c7; color: #b45309; } .avs-validated { background: #d1fae5; color: #047857; }
.pa-htitle { margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
.pa-mission-strip { display: flex; flex-wrap: wrap; gap: 12px; }
.pa-mission-strip span { display: inline-flex; align-items: center; gap: 4px; font-size: .68rem; color: #64748b; }
.pa-prog { color: #047857 !important; font-weight: 600; }

.pa-tabs { display: flex; gap: 2px; padding: 0 18px; border-top: 1px solid #f1f5f9; }
.pa-tab { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; background: none; border: none; border-bottom: 2px solid transparent; color: #64748b; font-size: .74rem; font-weight: 700; cursor: pointer; font-family: inherit; }
.pa-tab:hover { color: #334155; }
.pa-tab-n { font-size: .58rem; font-weight: 800; background: #f1f5f9; color: #64748b; border-radius: 8px; padding: 0 6px; }

.pa-banner { display: flex; align-items: center; gap: 8px; padding: 7px 22px; font-size: .73rem; font-weight: 600; border-top: 1px solid; }
.pa-banner-lock { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.pa-banner-review { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.pa-banner-warn { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }

/* BODY */
.pa-body { padding: 14px 22px 88px; max-width: 1500px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }

.pa-progbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 14px; }
.pa-progbar label { display: inline-flex; align-items: center; gap: 6px; font-size: .72rem; font-weight: 800; color: #334155; }
.pa-progbar select { flex: 1; min-width: 240px; }
.pa-load { display: inline-flex; align-items: center; gap: 5px; font-size: .68rem; font-weight: 700; padding: 7px 12px; border: 1px solid; border-radius: 9px; background: #fff; cursor: pointer; font-family: inherit; }
.pa-load:disabled { opacity: .5; cursor: not-allowed; }

.pa-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 6px rgba(15,23,42,.04); }
.pa-vhead { display: flex; align-items: center; gap: 8px; padding: 11px 16px; font-size: .82rem; font-weight: 800; color: #fff; }
.pa-vhead em { font-style: normal; font-weight: 500; font-size: .68rem; opacity: .8; }
.pa-vn { font-family: 'JetBrains Mono', monospace; }
.pa-vh1 { background: linear-gradient(90deg, #1e3a5f, #2c4a70); }
.pa-vh2 { background: linear-gradient(90deg, #1e4620, #2d5a30); }
.pa-vh3 { background: linear-gradient(90deg, #5b1e2c, #7a2c3c); }

.pa-scroll { overflow-x: auto; }
.pa-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
.pa-table th { text-align: left; padding: 7px 8px; font-size: .58rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #475569; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.pa-table td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.pa-num { width: 34px; text-align: center; color: #94a3b8; font-weight: 700; }
.pa-table textarea, .pa-table input, .pa-table select { width: 100%; padding: 5px 7px; border-radius: 7px; border: 1px solid #e2e8f0; font-size: .7rem; font-family: inherit; color: #0f172a; outline: none; background: #fff; resize: vertical; }
.pa-table textarea:focus, .pa-table input:focus, .pa-table select:focus { border-color: var(--fc, #059669); }
.pa-table textarea:disabled, .pa-table input:disabled, .pa-table select:disabled { background: #f8fafc; color: #94a3b8; }
.pa-unite { min-width: 60px; }
.pa-empty-row { text-align: center; padding: 18px; color: #94a3b8; font-style: italic; }
.pa-del { width: 26px; height: 26px; border-radius: 7px; border: 1px solid #fee2e2; background: #fff; color: #dc2626; cursor: pointer; }
.pa-del:hover { background: #dc2626; color: #fff; }

/* Cadre logique — teinte par composante */
.cl-intrants td:nth-child(2) { border-left: 3px solid #3b82f6; }
.cl-activites td:nth-child(2) { border-left: 3px solid #8b5cf6; }
.cl-extrants td:nth-child(2) { border-left: 3px solid #f59e0b; }
.cl-resultats td:nth-child(2) { border-left: 3px solid #10b981; }
.cl-impact td:nth-child(2) { border-left: 3px solid #ec4899; }

/* Risques */
.pa-risktable { min-width: 1200px; }
.pa-pi { width: 62px; } .pa-pi select { text-align: center; }
.pa-sc { width: 46px; text-align: center; font-weight: 800; color: #334155; vertical-align: middle; }
.pa-nv { width: 76px; vertical-align: middle; }
.pa-niv { display: inline-block; font-size: .58rem; font-weight: 800; padding: 2px 7px; border-radius: 10px; text-align: center; min-width: 54px; }
.niv-faible, .pa-niv-faible { background: #d1fae5; color: #047857; }
.niv-moyen, .pa-niv-moyen { background: #fed7aa; color: #c2410c; }
.niv-eleve, .pa-niv-eleve { background: #fecaca; color: #b91c1c; }
.niv-none { background: #f1f5f9; color: #94a3b8; }
select.pa-niv-eleve { background: #fef2f2; } select.pa-niv-moyen { background: #fff7ed; } select.pa-niv-faible { background: #ecfdf5; }

.pa-add { align-self: flex-start; margin: 10px 14px; display: inline-flex; align-items: center; gap: 5px; font-size: .7rem; font-weight: 700; padding: 6px 12px; border: 1px dashed; border-radius: 9px; background: transparent; cursor: pointer; font-family: inherit; }
.pa-risk-actions { display: flex; flex-direction: column; gap: 6px; padding: 0 14px 12px; }
.pa-suggest { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.pa-suggest > span { font-size: .64rem; font-weight: 700; color: #94a3b8; }
.pa-sugg-chip { display: inline-flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 700; padding: 3px 10px; border: 1px dashed; border-radius: 14px; background: #fff; cursor: pointer; font-family: inherit; }

.pa-sign { padding: 12px 16px; display: grid; grid-template-columns: 1fr 220px 220px; gap: 12px; }
@media (max-width: 800px) { .pa-sign { grid-template-columns: 1fr; } }
.pa-field { display: flex; flex-direction: column; gap: 4px; }
.pa-field label { font-size: .66rem; font-weight: 700; color: #475569; }
.pa-field input, .pa-field textarea { padding: 8px 10px; border-radius: 9px; border: 1px solid #e2e8f0; font-size: .74rem; font-family: inherit; outline: none; resize: vertical; }
.pa-field input:focus, .pa-field textarea:focus { border-color: var(--fc, #059669); }

.pa-actions { position: fixed; bottom: 0; left: 0; right: 0; z-index: 25; display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 22px; background: rgba(255,255,255,.94); backdrop-filter: blur(8px); border-top: 1px solid #e2e8f0; }
.pa-risk-legend { display: inline-flex; align-items: center; gap: 6px; font-size: .64rem; color: #64748b; }
.pa-risk-legend .pa-niv { min-width: 14px; padding: 5px; }
.pa-actions-r { display: flex; gap: 8px; flex-wrap: wrap; }
.pa-btn { display: inline-flex; align-items: center; gap: 6px; font-size: .74rem; font-weight: 700; padding: 8px 16px; border-radius: 9px; border: 1px solid transparent; cursor: pointer; font-family: inherit; }
.pa-btn:disabled { opacity: .55; cursor: not-allowed; }
.pa-btn-save { color: #fff; } .pa-btn-save:hover:not(:disabled) { filter: brightness(1.08); }
.pa-btn-submit { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; } .pa-btn-submit:hover:not(:disabled) { background: #1d4ed8; color: #fff; }
.pa-btn-validate { background: #ecfdf5; border-color: #a7f3d0; color: #047857; } .pa-btn-validate:hover:not(:disabled) { background: #047857; color: #fff; }
.pa-btn-reject { background: #fef2f2; border-color: #fecaca; color: #b91c1c; } .pa-btn-reject:hover:not(:disabled) { background: #b91c1c; color: #fff; }

.pa-toast { position: fixed; bottom: 74px; right: 22px; z-index: 2000; display: flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 11px; font-size: .76rem; font-weight: 700; box-shadow: 0 8px 30px rgba(15,23,42,.18); }
.pa-toast-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.pa-toast-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.pa-toastx-enter-active, .pa-toastx-leave-active { transition: all .2s ease; }
.pa-toastx-enter-from, .pa-toastx-leave-to { opacity: 0; transform: translateY(8px); }
.pa-spin { animation: pa-rot .7s linear infinite; }
@keyframes pa-rot { to { transform: rotate(360deg); } }
</style>
