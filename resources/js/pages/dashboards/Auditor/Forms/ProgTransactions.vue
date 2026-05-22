<template>
  <VerticalLayoutAudit>
    <div class="container-fluid py-3">

      <!-- HEADER -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
          <div class="d-flex align-items-start gap-2">
            <a :href="props.backUrl" class="btn btn-outline-secondary btn-sm mt-1">
              <i class="ti ti-arrow-left"></i>
            </a>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <code class="bg-dark text-white px-2 py-1 rounded small fw-bold">{{ form.code || 'PTRANS-AUTO' }}</code>
                <span class="badge" :class="vstBadge(form.validation_status||'draft')">
                  <i :class="vstIcon(form.validation_status||'draft')"></i>
                  {{ vstLbl(form.validation_status||'draft') }}
                </span>
                <span class="badge bg-success">Programme Transactions</span>
                <span v-if="sourceLabel" class="badge bg-secondary">
                  <i class="ti ti-link"></i> {{ sourceLabel }}
                </span>
                <span v-if="props.auditorRole" class="badge bg-secondary">{{ props.auditorRole }}</span>
                <span v-if="aiAutoRunning" class="badge bg-primary">
                  <span class="spinner-border spinner-border-sm me-1" style="width:.6rem;height:.6rem"></span>
                  IA en cours…
                </span>
              </div>
              <h6 class="mb-0 fw-bold">Programme de Travail d'Audit — Contrôle des Transactions</h6>
              <div class="d-flex gap-3 flex-wrap mt-1">
                <small v-if="missionLibelle" class="text-muted">
                  <i class="ti ti-file-description"></i> {{ missionLibelle }}
                </small>
                <small v-if="codeMission" class="text-muted">
                  <i class="ti ti-clipboard"></i> {{ codeMission }}
                </small>
                <small class="text-success fw-semibold">
                  <i class="ti ti-arrows-exchange"></i> {{ lignes.length }} transaction(s)
                </small>
                <small class="text-success fw-semibold">
                  <i class="ti ti-checklist"></i> {{ totalDiligences }} diligence(s)
                </small>
              </div>
            </div>
          </div>
        </div>
        <div v-if="form.validation_status==='validated'"
             class="alert alert-success mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
          <i class="ti ti-lock"></i> Programme Transactions <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status==='in_review'"
             class="alert alert-info mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
          <i class="ti ti-clock"></i> Soumis pour validation
          <span v-if="canManage"> · DM/CM peut valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status==='draft' && form.validation_note"
             class="alert alert-danger mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>
      </div>

      <!-- BARRE D'ACTIONS -->
      <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
        <div class="alert alert-success py-2 px-3 mb-0 small flex-grow-1">
          <i class="ti ti-info-circle"></i>
          Transactions issues de l'<strong>Analyse des Risques (AR)</strong>.
          Pour chaque processus retenu, une diligence est générée selon les assertions d'audit
          (Exhaustivité, Existence, Évaluation…). L'IA propose les tests adaptés.
        </div>
        <button v-if="!isLocked" class="btn btn-outline-primary btn-sm" @click="ajouterLigne">
          <i class="ti ti-plus"></i> Ajouter une transaction
        </button>
      </div>

      <!-- ALERTE source manquante -->
      <div v-if="!lignes.length" class="alert alert-warning">
        <i class="ti ti-alert-triangle"></i>
        Aucune transaction disponible. Complétez d'abord l'<strong>Analyse des Risques (AR)</strong>
        ou ajoutez des lignes manuellement.
      </div>

      <!-- TABLEAU -->
      <div v-if="lignes.length" class="card border-0 shadow-sm mb-3">
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle mb-0 table-prog">
            <thead>
              <tr>
                <th :colspan="isLocked ? 10 : 11"
                    class="text-center text-white fw-bold py-2 th-dark-header">
                  PROGRAMME DE TRAVAIL D'AUDIT — CONTRÔLE DES TRANSACTIONS
                </th>
              </tr>
              <tr class="table-light">
                <td colspan="5" class="small py-1 px-2">
                  <span class="fw-semibold text-secondary">Mission d'audit :</span>
                  <span class="ms-2 fw-bold">{{ missionLibelle || '—' }}</span>
                </td>
                <td :colspan="isLocked ? 5 : 6" class="small py-1 px-2 text-end">
                  <span class="fw-semibold text-secondary">Responsable :</span>
                  <span class="ms-2 fw-bold">{{ responsableMission }}</span>
                </td>
              </tr>
              <tr>
                <td :colspan="isLocked ? 10 : 11" class="small py-1 px-2 fst-italic th-note-transactions">
                  📌 Diligences basées sur les assertions d'audit (Exhaustivité, Existence, Évaluation…).
                  Chaque ligne cible une nature de transaction et une assertion à tester.
                  Tests et procédures générés par l'IA à partir du contexte risque (AR).
                </td>
              </tr>
              <tr>
                <th colspan="4" class="text-center text-white small py-1 th-identification">IDENTIFICATION TRANSACTION</th>
                <th colspan="3" class="text-center text-white small py-1 th-diligence">DILIGENCE &amp; PROCÉDURES</th>
                <th colspan="3" class="text-center text-white small py-1 th-planification">PLANIFICATION</th>
                <th v-if="!isLocked" class="text-center text-white small py-1 th-actions">⚙</th>
              </tr>
              <tr>
                <th class="text-center small th-col-identification" style="width:70px">Réf.</th>
                <th class="small th-col-identification" style="min-width:150px">
                  Nature transaction<br/>
                  <span class="col-subtitle">Source : AR</span>
                </th>
                <th class="text-center small th-col-identification" style="width:90px">Période<br/>testée</th>
                <th class="small th-col-identification" style="width:110px">
                  Assertion<br/>
                  <span class="col-subtitle">⬤ Exhaustivité…</span>
                </th>
                <th class="text-center small th-col-identification" style="width:80px">Taille<br/>Échantillon</th>
                <th class="small th-col-diligence" style="min-width:210px">
                  Diligence d'audit<br/>
                  <span class="col-subtitle">⬤ Test à réaliser</span>
                </th>
                <th class="small th-col-diligence" style="min-width:200px">
                  Procédures d'audit<br/>
                  <span class="col-subtitle">⬤ Étapes concrètes</span>
                </th>
                <th class="small th-col-planification" style="width:120px">Auditeur<br/>Responsable</th>
                <th class="text-center small th-col-planification" style="width:95px">Date<br/>Début</th>
                <th class="text-center small th-col-planification" style="width:95px">Date<br/>Fin</th>
                <th v-if="!isLocked" class="text-center small th-col-actions" style="width:38px">⚙</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(ligne, li) in lignes" :key="ligne._uid">
                <tr :class="li%2===0 ? '' : 'table-light'">

                  <!-- Réf. -->
                  <td class="text-center align-top px-2 py-2">
                    <span class="badge bg-success d-block font-monospace small-badge">{{ ligne.ref }}</span>
                  </td>

                  <!-- Nature transaction -->
                  <td class="align-top px-2 py-2">
                    <textarea v-if="!isLocked" class="form-control form-control-sm"
                              v-model="ligne.nature_transaction" rows="2"
                              @change="ligne._edited=true"
                              placeholder="Achats, Salaires, Immobilisations…"
                              style="font-size:.73rem;resize:vertical"></textarea>
                    <p v-else class="mb-0 small">{{ ligne.nature_transaction || '—' }}</p>
                  </td>

                  <!-- Période -->
                  <td class="text-center align-top px-1 py-1">
                    <input v-if="!isLocked" type="text" class="form-control form-control-sm text-center"
                           v-model="ligne.periode_testee" @change="ligne._edited=true"
                           placeholder="Jan–Juin 2024" style="font-size:.7rem"/>
                    <span v-else class="small">{{ ligne.periode_testee || '—' }}</span>
                  </td>

                  <!-- Assertion -->
                  <td class="align-top px-1 py-1">
                    <select v-if="!isLocked" class="form-select form-select-sm" v-model="ligne.assertion" @change="ligne._edited=true" style="font-size:.7rem">
                      <option value="">— Assertion —</option>
                      <option v-for="a in assertions" :key="a" :value="a">{{ a }}</option>
                    </select>
                    <span v-else class="small">{{ ligne.assertion || '—' }}</span>
                  </td>

                  <!-- Taille échantillon -->
                  <td class="text-center align-middle px-1 py-1">
                    <input v-if="!isLocked" type="text" class="form-control form-control-sm text-center"
                           v-model="ligne.taille_echantillon" @change="ligne._edited=true"
                           placeholder="30…" style="font-size:.71rem"/>
                    <span v-else class="small">{{ ligne.taille_echantillon || '—' }}</span>
                  </td>

                  <!-- Diligence -->
                  <td class="align-top px-2 py-2">
                    <div class="d-flex align-items-center gap-1 mb-1">
                      <span v-if="ligne._ia_loading" class="badge bg-light border text-primary ia-spinner-badge">
                        <span class="spinner-border spinner-border-sm" style="width:.5rem;height:.5rem"></span> IA…
                      </span>
                      <div v-if="!isLocked" class="ms-auto d-flex gap-1">
                        <button class="btn btn-sm btn-ia" :disabled="ligne._ia_loading"
                                title="Générer par IA" @click="reformulerLigne(ligne)">
                          <i class="ti ti-sparkles"></i> IA
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-sm-action" @click="supprimerLigne(li)">
                          <i class="ti ti-trash"></i>
                        </button>
                      </div>
                    </div>
                    <textarea v-if="!isLocked" class="form-control form-control-sm"
                              v-model="ligne.diligence" rows="3"
                              @change="ligne._edited=true"
                              placeholder="Vérifier / S'assurer / Contrôler…"
                              style="font-size:.73rem;resize:vertical"></textarea>
                    <p v-else class="mb-0 small">{{ ligne.diligence || '—' }}</p>
                    <div v-if="ligne._ia_suggestions?.length" class="mt-2 pt-2 border-top border-success-subtle">
                      <div class="small fw-semibold mb-1 text-ia-label">
                        <i class="ti ti-sparkles"></i> Propositions IA — cliquer pour appliquer
                      </div>
                      <div v-for="(s, si) in ligne._ia_suggestions" :key="si"
                           class="rounded p-2 mb-1 suggestion-ia-item"
                           @click="appliquerSuggestion(ligne, s)">
                        {{ s.diligence }}
                        <span v-if="s.procedures?.length" class="ms-1 badge bg-secondary badge-xs">{{ s.procedures.length }} étapes</span>
                      </div>
                    </div>
                  </td>

                  <!-- Procédures -->
                  <td class="align-top px-2 py-2">
                    <div v-if="!isLocked" class="d-flex flex-column gap-1">
                      <div v-for="(proc, pi) in ligne.procedures" :key="pi" class="d-flex align-items-start gap-1">
                        <span class="text-muted fw-bold proc-num">{{ pi+1 }}.</span>
                        <textarea class="form-control form-control-sm flex-grow-1"
                                  v-model="ligne.procedures[pi]" rows="2"
                                  @change="ligne._edited=true"
                                  :placeholder="`Étape ${pi+1}…`"
                                  style="font-size:.69rem;resize:vertical"></textarea>
                        <button class="btn btn-sm btn-outline-secondary btn-sm-action mt-1"
                                @click="supprimerProcedure(ligne, pi)">
                          <i class="ti ti-x"></i>
                        </button>
                      </div>
                      <button class="btn btn-outline-success btn-sm btn-add-step" @click="ajouterProcedure(ligne)">
                        <i class="ti ti-plus"></i> Étape
                      </button>
                    </div>
                    <div v-else class="d-flex flex-column gap-1">
                      <div v-for="(proc, pi) in ligne.procedures" :key="pi" class="d-flex gap-1 small">
                        <span class="text-muted fw-bold" style="min-width:14px">{{ pi+1 }}.</span>
                        <span>{{ proc }}</span>
                      </div>
                      <span v-if="!ligne.procedures?.length" class="text-muted fst-italic small">—</span>
                    </div>
                  </td>

                  <!-- Auditeur -->
                  <td class="align-middle px-1 py-1">
                    <select v-if="!isLocked && auditeurOptions.length"
                            class="form-select form-select-sm" v-model="ligne.auditeur"
                            @change="ligne._edited=true" style="font-size:.71rem">
                      <option value="">—</option>
                      <option v-for="a in auditeurOptions" :key="a.id" :value="a.full_name">{{ a.full_name }} ({{ a.role_code }})</option>
                    </select>
                    <input v-else-if="!isLocked" type="text" class="form-control form-control-sm"
                           v-model="ligne.auditeur" @change="ligne._edited=true" style="font-size:.71rem"/>
                    <span v-else class="small">{{ ligne.auditeur || '—' }}</span>
                  </td>

                  <!-- Date Début -->
                  <td class="text-center align-middle px-1 py-1">
                    <input v-if="!isLocked" type="date" class="form-control form-control-sm"
                           v-model="ligne.date_debut" @change="ligne._edited=true" style="font-size:.69rem"/>
                    <span v-else class="small">{{ formatDate(ligne.date_debut) }}</span>
                  </td>

                  <!-- Date Fin -->
                  <td class="text-center align-middle px-1 py-1">
                    <input v-if="!isLocked" type="date" class="form-control form-control-sm"
                           v-model="ligne.date_fin" @change="ligne._edited=true" style="font-size:.69rem"/>
                    <span v-else class="small">{{ formatDate(ligne.date_fin) }}</span>
                  </td>

                  <td v-if="!isLocked" class="text-center align-middle px-1 py-1"></td>
                </tr>

                <!-- Séparateur -->
                <tr>
                  <td :colspan="isLocked ? 10 : 11" class="p-0 separator-row"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- FOOTER -->
      <div class="card border-0 shadow-sm">
        <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex gap-2">
            <button v-if="!isLocked" type="button" class="btn btn-outline-secondary btn-sm" :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button v-if="!isLocked" type="button" class="btn btn-dark btn-sm" :disabled="processing" @click="submit">
              <span v-if="processing" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="ti ti-device-floppy me-1"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <span v-if="form.id" class="badge bg-success"><i class="ti ti-check"></i> {{ form.code }}</span>
            <span class="badge bg-warning text-dark">{{ lignes.length }} transaction(s) · {{ totalDiligences }} diligence(s)</span>
          </div>
          <div class="d-flex gap-2">
            <button v-if="form.id && form.validation_status==='draft'" type="button" class="btn btn-primary btn-sm" :disabled="processing" @click="soumettre">
              <i class="ti ti-send me-1"></i> Soumettre
            </button>
            <template v-if="canManage && form.validation_status==='in_review'">
              <button type="button" class="btn btn-success btn-sm" :disabled="processing" @click="valider('validate')">
                <i class="ti ti-circle-check me-1"></i> Valider
              </button>
              <button type="button" class="btn btn-outline-danger btn-sm" :disabled="processing" @click="promptReject">
                <i class="ti ti-circle-x me-1"></i> Rejeter
              </button>
            </template>
          </div>
        </div>
      </div>

    </div>

    <!-- Toast -->
    <Teleport to="body">
      <div class="position-fixed bottom-0 end-0 p-3 toast-container-z">
        <Transition name="fade">
          <div v-if="toast.show" class="toast show align-items-center"
               :class="toast.type==='success' ? 'text-bg-success' : 'text-bg-danger'" role="alert">
            <div class="d-flex">
              <div class="toast-body d-flex align-items-center gap-2 small">
                <i :class="toast.type==='success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
                {{ toast.msg }}
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="toast.show=false"></button>
            </div>
          </div>
        </Transition>
      </div>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

const assertions = ['Exhaustivité', 'Existence', 'Évaluation', 'Classification', 'Période', 'Séparation des tâches']

const props = withDefaults(defineProps<{
  mission?:        any
  auditorRole?:    string
  missionId?:      number
  assignmentId?:   number
  form?:           any
  phaseAuditeurs?: any[]
  donneesSource?:  { lignes?: any[]; total?: number; source?: string }
  missionContext?: { mission_id?: number; mission_libelle?: string; code_mission?: string; source?: string }
  backUrl?:        string
  urlStore?:       string
  urlUpdate?:      string
  urlSoumettre?:   string
  urlValider?:     string
  urlAiSuggest?:   string
  urlBase?:        string
}>(), {
  phaseAuditeurs: () => [],
  donneesSource:  () => ({ lignes: [], total: 0 }),
  missionContext: () => ({}),
})

let _uid = 0; const uid = () => String(++_uid)

const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  ...(props.form ?? {}),
})

const lignes = reactive<any[]>(safeArr(props.form?.lignes).map(l => hydrateLigne(l)))
if (!lignes.length && (props.donneesSource?.lignes?.length ?? 0) > 0) {
  props.donneesSource!.lignes!.forEach(l => lignes.push(hydrateLigne(l)))
}

const processing    = ref(false)
const aiAutoRunning = ref(false)
const toast = ref({ show: false, type: 'success', msg: '' })
let _tt: any

function showToast(t: string, m: string) { if (_tt) clearTimeout(_tt); toast.value = { show: true, type: t, msg: m }; _tt = setTimeout(() => { toast.value.show = false }, 4500) }
function hydrateLigne(l: any) { return { ...l, _uid: uid(), _edited: false, _ia_loading: false, _ia_suggestions: null as any[] | null, procedures: Array.isArray(l.procedures) ? [...l.procedures] : [] } }

const canManage       = computed(() => ['DM','CM'].includes(props.auditorRole ?? ''))
const isLocked        = computed(() => form.validation_status === 'validated' || (form.validation_status === 'in_review' && !canManage.value))
const auditeurOptions = computed(() => props.phaseAuditeurs ?? [])
const totalDiligences = computed(() => lignes.length)
const missionLibelle  = computed(() => props.mission?.libelle ?? props.missionContext?.mission_libelle ?? '')
const codeMission     = computed(() => props.mission?.code_mission ?? props.missionContext?.code_mission ?? '')
const sourceLabel     = computed(() => props.donneesSource?.source === 'ar' ? 'AR' : lignes.length ? 'AR' : '')
const responsableMission = computed(() => { const dm = (props.phaseAuditeurs ?? []).find(a => a.role_code === 'DM' || a.role_code === 'CM'); return dm?.full_name ?? '—' })

function urlAI(): string | null { if (props.urlAiSuggest) return props.urlAiSuggest; if (form.id && props.urlBase) return `${props.urlBase}/${form.id}/ai-suggest`; return null }

onMounted(async () => {
  if (isLocked.value || !lignes.some(l => l._needs_ai)) return
  if (!form.id) { const ok = await autoSave(); if (!ok) return }
  aiAutoRunning.value = true; let nb = 0
  for (const ligne of lignes) { if (!ligne._needs_ai) continue; await aiReformuler(ligne); nb++ }
  aiAutoRunning.value = false
  if (nb > 0) { await submit(true); showToast('success', `IA : ${nb} diligence(s) générée(s) et sauvegardées.`) }
})

async function autoSave(): Promise<boolean> {
  if (!props.urlStore) return false
  try {
    const res = await fetch(props.urlStore, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, lignes: JSON.stringify(serialize()) }) })
    const d = await res.json()
    if ((d.success || res.ok) && d.form) { Object.assign(form, { id: d.form.id, code: d.form.code, validation_status: d.form.validation_status }); return true }
    return false
  } catch { return false }
}

async function aiReformuler(ligne: any) {
  const url = urlAI(); if (!url) return
  ligne._ia_loading = true
  try {
    const r = await callAI(url, { ligne: buildPayload(ligne), context: ctx(ligne) })
    if (r.success && r.diligence) { ligne.diligence = r.diligence; ligne.procedures = Array.isArray(r.procedures) ? r.procedures : []; ligne._needs_ai = false; ligne._edited = true }
  } catch { /* silencieux */ }
  finally { ligne._ia_loading = false }
}

async function reformulerLigne(ligne: any) {
  const url = urlAI(); if (!url) { showToast(form.id ? 'error' : 'info', form.id ? 'URL IA indisponible.' : 'Enregistrez d\'abord.'); return }
  ligne._ia_loading = true; ligne._ia_suggestions = null
  try {
    const r = await callAI(url, { ligne: buildPayload(ligne), context: ctx(ligne) })
    if (r.success && r.diligence) ligne._ia_suggestions = [{ diligence: r.diligence, procedures: r.procedures }]
    else showToast('error', r.error ?? 'Aucune suggestion.')
  } catch { showToast('error', 'Erreur IA.') }
  finally { ligne._ia_loading = false }
}

function appliquerSuggestion(ligne: any, s: any) { ligne.diligence = s.diligence; ligne.procedures = Array.isArray(s.procedures) ? [...s.procedures] : []; ligne._ia_suggestions = null; ligne._edited = true; showToast('success', 'Suggestion appliquée.') }
async function callAI(url: string, body: any) { const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify(body) }); if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json() }
function buildPayload(l: any) { return { ref: l.ref, nature_transaction: l.nature_transaction, assertion: l.assertion, periode_testee: l.periode_testee, diligence: l.diligence } }
function ctx(l: any) { return { ...(props.missionContext ?? {}), nature_transaction: l.nature_transaction ?? '', assertion: l.assertion ?? '', periode: l.periode_testee ?? '' } }

function ajouterLigne() {
  const n = lignes.length + 1
  lignes.push(hydrateLigne({ ref: 'T-' + String(n).padStart(2,'0'), nature_transaction: '', periode_testee: '', assertion: 'Exhaustivité', taille_echantillon: '', diligence: '', procedures: [], auditeur: '', date_debut: '', date_fin: '' }))
}
function supprimerLigne(idx: number) { lignes.splice(idx, 1) }
function ajouterProcedure(l: any) { l.procedures.push(''); l._edited = true }
function supprimerProcedure(l: any, idx: number) { l.procedures.splice(idx, 1); l._edited = true }

function serialize() {
  return lignes.map(l => ({ ref: l.ref, nature_transaction: l.nature_transaction, periode_testee: l.periode_testee, assertion: l.assertion, taille_echantillon: l.taille_echantillon ?? '', diligence: l.diligence, procedures: l.procedures ?? [], auditeur: l.auditeur ?? '', date_debut: l.date_debut ?? '', date_fin: l.date_fin ?? '', _source: l._source ?? null, _ar_id: l._ar_id ?? null }))
}

async function submit(silent = false) {
  processing.value = !silent
  try {
    const url = form.id ? props.urlUpdate! : props.urlStore!; const method = form.id ? 'PUT' : 'POST'
    const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, lignes: JSON.stringify(serialize()) }) })
    const d = await res.json()
    if (d.success || res.ok) { if (!silent) showToast('success', form.id ? 'Mis à jour.' : 'Créé.'); if (d.form) Object.assign(form, { id: d.form.id, code: d.form.code, validation_status: d.form.validation_status }) }
    else if (!silent) showToast('error', d.message ?? 'Erreur.')
  } catch { if (!silent) showToast('error', 'Erreur réseau.') }
  finally { processing.value = false }
}

function annuler() { if (props.backUrl) router.visit(props.backUrl) }
async function soumettre() { processing.value = true; try { const d = await (await fetch(props.urlSoumettre || '', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId }) })).json(); if (d.success) { form.validation_status = 'in_review'; showToast('success', 'Soumis.') } else showToast('error', d.error ?? 'Erreur') } catch { showToast('error', 'Erreur réseau') }; processing.value = false }
async function valider(action: string, note?: string) { processing.value = true; try { const d = await (await fetch(props.urlValider || '', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, action, note }) })).json(); if (d.success) { form.validation_status = d.status; showToast('success', action === 'validate' ? 'Validé ✓' : 'Rejeté.') } else showToast('error', d.error ?? 'Erreur') } catch { showToast('error', 'Erreur réseau') }; processing.value = false }
function promptReject() { const n = prompt('Motif du rejet :'); if (!n?.trim()) return; valider('reject', n.trim()) }
function safeArr(v: any): any[] { if (Array.isArray(v)) return [...v]; if (!v) return []; try { const d = JSON.parse(v); return Array.isArray(d) ? d : [] } catch { return [] } }
function csrf() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
function vstLbl(s: string)   { return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓' } as any)[s] ?? s }
function vstIcon(s: string)  { return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check' } as any)[s] ?? 'ti ti-circle' }
function vstBadge(s: string) { return ({ draft: 'bg-secondary', in_review: 'bg-info text-dark', validated: 'bg-success' } as any)[s] ?? 'bg-secondary' }
function formatDate(d: string) { if (!d) return '—'; try { return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' }) } catch { return d } }
onBeforeUnmount(() => { if (_tt) clearTimeout(_tt) })
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all .2s ease }
.fade-enter-from, .fade-leave-to       { opacity: 0; transform: translateY(6px) }
.table-prog          { min-width: 1150px }
.th-dark-header      { background: #0f172a; font-size: .85rem; letter-spacing: .05em }
.th-note-transactions{ background: #fffbeb; color: #92400e; border-color: #fde68a }
.th-identification   { background: #065f46 }
.th-diligence        { background: #6d28d9 }
.th-planification    { background: #1e40af }
.th-actions          { background: #374151 }
.th-col-identification { background: #065f46; color: #fff }
.th-col-diligence      { background: #6d28d9; color: #fff }
.th-col-planification  { background: #1e40af; color: #fff }
.th-col-actions        { background: #374151; color: #fff; width: 38px }
.col-subtitle          { font-size: .6rem; opacity: .8; font-weight: 400 }
.separator-row         { background: #cbd5e1; height: 3px; border: none }
.small-badge     { font-family: monospace; font-size: .75rem }
.badge-xs        { font-size: .55rem }
.ia-spinner-badge{ font-size: .58rem }
.btn-ia          { background: #eef2ff; border-color: #c7d2fe; color: #4338ca; font-size: .6rem; padding: 2px 7px }
.btn-sm-action   { font-size: .6rem; padding: 2px 5px }
.btn-add-step    { font-size: .63rem }
.proc-num        { font-size: .63rem; min-width: 14px; margin-top: 5px }
.text-ia-label     { color: #4338ca; font-size: .62rem }
.suggestion-ia-item{ background: #eef2ff; border: 1px solid #c7d2fe; font-size: .68rem; cursor: pointer; transition: background .15s }
.suggestion-ia-item:hover { background: #e0e7ff }
.toast-container-z { z-index: 9999 }
</style>