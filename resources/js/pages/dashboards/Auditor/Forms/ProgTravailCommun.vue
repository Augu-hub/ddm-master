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
                <code class="bg-dark text-white px-2 py-1 rounded small fw-bold">
                  {{ form.code || config.codePrefix + '-AUTO' }}
                </code>
                <span class="badge" :class="vstBadge(form.validation_status || 'draft')">
                  <i :class="vstIcon(form.validation_status || 'draft')"></i>
                  {{ vstLbl(form.validation_status || 'draft') }}
                </span>
                <span class="badge" :class="'bg-' + config.color + ' text-white'">
                  {{ config.titre }}
                </span>
                <span v-if="props.auditorRole" class="badge bg-secondary">{{ props.auditorRole }}</span>
                <span v-if="aiRunning" class="badge bg-primary">
                  <span class="spinner-border spinner-border-sm me-1" style="width:.55rem;height:.55rem"></span>
                  IA en cours…
                </span>
              </div>
              <h6 class="mb-0 fw-bold">{{ config.titreComplet }}</h6>
              <div class="d-flex gap-3 flex-wrap mt-1">
                <small v-if="missionLibelle" class="text-muted">
                  <i class="ti ti-file-description"></i> {{ missionLibelle }}
                </small>
                <small class="fw-semibold" style="color:#7c3aed">
                  <i :class="config.iconSousTitre"></i>
                  {{ sourceItems.length }} {{ config.labelItems }}
                </small>
                <small class="text-success fw-semibold">
                  <i class="ti ti-checklist"></i> {{ diligences.length }} diligence(s)
                </small>
              </div>
            </div>
          </div>
        </div>
        <!-- Banners workflow -->
        <div v-if="form.validation_status === 'validated'"
             class="alert alert-success mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
          <i class="ti ti-lock"></i> <strong>Validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status === 'in_review'"
             class="alert alert-info mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
          <i class="ti ti-clock"></i> Soumis pour validation
          <span v-if="canManage"> · DM/CM peut valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status === 'draft' && form.validation_note"
             class="alert alert-danger mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>
      </div>

      <!-- SECTION SOURCE (marchés / textes / transactions) -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between"
             :style="'background:#' + config.colorHex + ';color:#fff'">
          <span class="small fw-bold">
            <i :class="config.iconSousTitre"></i>
            {{ config.titreSection1 }}
          </span>
          <button v-if="!isLocked" class="btn btn-sm btn-light" style="font-size:.65rem"
                  @click="ajouterSourceItem">
            <i class="ti ti-plus"></i> Ajouter
          </button>
        </div>
        <div class="card-body p-0">
          <div v-if="!sourceItems.length" class="p-3 text-muted small fst-italic">
            <i class="ti ti-info-circle"></i>
            {{ props.donneesSource?.source !== 'none'
              ? config.msgSourceDisponible
              : config.msgSourceVide }}
          </div>
          <div class="table-responsive">
            <table v-if="sourceItems.length" class="table table-sm table-bordered mb-0" style="font-size:.75rem">
              <thead>
                <tr>
                  <th v-for="col in config.colonnesSource" :key="col.key"
                      class="small py-1 px-2" style="background:#f8fafc">
                    {{ col.label }}
                  </th>
                  <th v-if="!isLocked" style="width:40px;background:#f8fafc"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in sourceItems" :key="idx">
                  <td v-for="col in config.colonnesSource" :key="col.key" class="px-2 py-1 align-middle">
                    <input v-if="!isLocked" type="text" class="form-control form-control-sm"
                           v-model="item[col.key]" style="font-size:.72rem"
                           :placeholder="col.placeholder || col.label"/>
                    <span v-else class="small">{{ item[col.key] || '—' }}</span>
                  </td>
                  <td v-if="!isLocked" class="text-center align-middle px-1">
                    <button class="btn btn-sm btn-outline-danger" style="font-size:.6rem;padding:2px 5px"
                            @click="supprimerSourceItem(idx)">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SECTION DILIGENCES -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between"
             style="background:#0f172a;color:#fff">
          <span class="small fw-bold">
            <i class="ti ti-list-check"></i> Diligences d'audit
          </span>
          <button v-if="!isLocked" class="btn btn-sm btn-outline-light" style="font-size:.65rem"
                  @click="ajouterDiligence">
            <i class="ti ti-plus"></i> Ajouter
          </button>
        </div>

        <div v-if="!diligences.length" class="p-3 text-muted small fst-italic">
          <i class="ti ti-info-circle"></i>
          Aucune diligence. Cliquez « Ajouter » ou laissez l'IA générer depuis les données sources.
        </div>

        <div class="table-responsive">
          <table v-if="diligences.length" class="table table-sm table-bordered mb-0" style="min-width:1000px;font-size:.73rem">
            <thead>
              <tr>
                <th class="text-center small py-1" style="background:#1e40af;color:#fff;width:60px">Réf.</th>
                <th v-if="config.colonneContexte" class="small py-1" style="background:#1e40af;color:#fff;width:130px">
                  {{ config.labelContexte }}
                </th>
                <th class="small py-1" style="background:#6d28d9;color:#fff;min-width:220px">
                  Diligence d'audit
                </th>
                <th class="small py-1" style="background:#6d28d9;color:#fff;min-width:200px">Procédures</th>
                <th class="small py-1 text-center" style="background:#065f46;color:#fff;width:110px">Responsable</th>
                <th class="small py-1 text-center" style="background:#065f46;color:#fff;width:90px">Début</th>
                <th class="small py-1 text-center" style="background:#065f46;color:#fff;width:90px">Fin</th>
                
                <th v-if="!isLocked" class="text-center small py-1" style="background:#374151;color:#fff;width:64px">⚙</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(dil, idx) in diligences" :key="dil._uid" :class="idx % 2 === 0 ? '' : 'table-light'">

                <!-- Réf. -->
                <td class="text-center align-middle px-1 py-1">
                  <span class="badge bg-secondary" style="font-family:monospace;font-size:.65rem">{{ dil.ref }}</span>
                </td>

                <!-- Contexte (marché / texte / nature transaction) -->
                <td v-if="config.colonneContexte" class="align-middle px-2 py-1">
                  <input v-if="!isLocked" type="text" class="form-control form-control-sm"
                         v-model="dil[config.keyContexte]" style="font-size:.7rem"
                         :placeholder="config.labelContexte"/>
                  <span v-else class="small">{{ dil[config.keyContexte] || '—' }}</span>
                </td>

                <!-- Diligence -->
                <td class="align-top px-2 py-1">
                  <div class="d-flex align-items-start gap-1 mb-1">
                    <span v-if="dil._ia_loading" class="badge bg-light border text-primary" style="font-size:.55rem">
                      <span class="spinner-border spinner-border-sm" style="width:.45rem;height:.45rem"></span> IA…
                    </span>
                    <div v-if="!isLocked" class="ms-auto d-flex gap-1">
                      <button class="btn btn-sm"
                              style="background:#eef2ff;border-color:#c7d2fe;color:#4338ca;font-size:.58rem;padding:2px 6px"
                              :disabled="dil._ia_loading || aiRunning"
                              @click="suggererIA(dil)"
                              title="Générer par IA">
                        <i class="ti ti-sparkles"></i> IA
                      </button>
                    </div>
                  </div>
                  <textarea v-if="!isLocked"
                            class="form-control form-control-sm"
                            v-model="dil.diligence" rows="3"
                            style="font-size:.72rem;resize:vertical"
                            placeholder="Vérifier / S'assurer / Contrôler…"></textarea>
                  <p v-else class="mb-0 small" style="white-space:pre-wrap">{{ dil.diligence || '—' }}</p>

                  <!-- Suggestion IA -->
                  <div v-if="dil._ia_suggestion" class="mt-2 pt-2 border-top" style="border-color:#c7d2fe!important">
                    <div class="small fw-semibold mb-1" style="color:#4338ca;font-size:.6rem">
                      <i class="ti ti-sparkles"></i> Suggestion IA — cliquer pour appliquer
                    </div>
                    <div class="rounded p-2"
                         style="background:#eef2ff;border:1px solid #c7d2fe;font-size:.68rem;cursor:pointer"
                         @click="appliquerSuggestion(dil)">
                      {{ dil._ia_suggestion.diligence }}
                    </div>
                  </div>
                </td>

                <!-- Procédures -->
                <td class="align-top px-2 py-1">
                  <div v-if="!isLocked" class="d-flex flex-column gap-1">
                    <div v-for="(proc, pi) in dil.procedures" :key="pi" class="d-flex align-items-start gap-1">
                      <span class="text-muted fw-bold" style="font-size:.6rem;min-width:13px;margin-top:5px">{{ pi+1 }}.</span>
                      <textarea class="form-control form-control-sm flex-grow-1"
                                v-model="dil.procedures[pi]" rows="2"
                                style="font-size:.68rem;resize:vertical"
                                :placeholder="`Étape ${pi+1}…`"></textarea>
                      <button class="btn btn-sm btn-outline-secondary" style="font-size:.55rem;padding:2px 4px;margin-top:2px"
                              @click="dil.procedures.splice(pi, 1)">
                        <i class="ti ti-x"></i>
                      </button>
                    </div>
                    <button class="btn btn-outline-success btn-sm" style="font-size:.6rem"
                            @click="dil.procedures.push('')">
                      <i class="ti ti-plus"></i> Étape
                    </button>
                  </div>
                  <div v-else class="d-flex flex-column gap-1">
                    <div v-for="(proc, pi) in dil.procedures" :key="pi" class="d-flex gap-1 small">
                      <span class="text-muted fw-bold" style="min-width:13px">{{ pi+1 }}.</span>
                      <span>{{ proc }}</span>
                    </div>
                    <span v-if="!dil.procedures?.length" class="text-muted fst-italic small">—</span>
                  </div>
                </td>

                <!-- Responsable -->
                <td class="align-middle px-1 py-1">
                  <select v-if="!isLocked && auditeurOptions.length"
                          class="form-select form-select-sm" v-model="dil.responsable"
                          style="font-size:.7rem">
                    <option value="">—</option>
                    <option v-for="a in auditeurOptions" :key="a.id" :value="a.full_name">
                      {{ a.full_name }} ({{ a.role_code }})
                    </option>
                  </select>
                  <input v-else-if="!isLocked" type="text" class="form-control form-control-sm"
                         v-model="dil.responsable" style="font-size:.7rem"/>
                  <span v-else class="small">{{ dil.responsable || '—' }}</span>
                </td>

                <!-- Date début -->
                <td class="text-center align-middle px-1 py-1">
                  <input v-if="!isLocked" type="date" class="form-control form-control-sm"
                         v-model="dil.date_debut" style="font-size:.68rem"/>
                  <span v-else class="small">{{ formatDate(dil.date_debut) }}</span>
                </td>

                <!-- Date fin -->
                <td class="text-center align-middle px-1 py-1">
                  <input v-if="!isLocked" type="date" class="form-control form-control-sm"
                         v-model="dil.date_fin" style="font-size:.68rem"/>
                  <span v-else class="small">{{ formatDate(dil.date_fin) }}</span>
                </td>

                <!-- Statut -->
              

                <!-- Actions -->
                <td v-if="!isLocked" class="text-center align-middle px-1 py-1">
                  <button class="btn btn-sm btn-outline-danger" style="font-size:.6rem;padding:2px 5px"
                          @click="supprimerDiligence(idx)">
                    <i class="ti ti-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- OBSERVATIONS -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 px-3" style="background:#1e293b;color:#fff">
          <span class="small fw-bold"><i class="ti ti-note"></i> Observations générales</span>
        </div>
        <div class="card-body p-3">
          <textarea class="form-control form-control-sm" v-model="form.observations"
                    :disabled="isLocked" rows="3"
                    placeholder="Observations sur le programme de travail…"
                    style="font-size:.78rem"></textarea>
        </div>
      </div>

      <!-- FOOTER -->
      <div class="card border-0 shadow-sm">
        <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex gap-2">
            <button v-if="!isLocked" type="button" class="btn btn-outline-secondary btn-sm"
                    :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button v-if="!isLocked" type="button" class="btn btn-dark btn-sm"
                    :disabled="processing" @click="submit()">
              <span v-if="processing" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="ti ti-device-floppy me-1"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <span v-if="form.id" class="badge bg-success"><i class="ti ti-check"></i> {{ form.code }}</span>
            <span class="badge bg-warning text-dark">
              {{ sourceItems.length }} {{ config.labelItems }} · {{ diligences.length }} diligence(s)
            </span>
          </div>
          <div class="d-flex gap-2">
            <button v-if="form.id && form.validation_status === 'draft'"
                    type="button" class="btn btn-primary btn-sm" :disabled="processing" @click="soumettre">
              <i class="ti ti-send me-1"></i> Soumettre
            </button>
            <template v-if="canManage && form.validation_status === 'in_review'">
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

    <!-- TOAST -->
    <Teleport to="body">
      <div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
        <Transition name="fade">
          <div v-if="toast.show" class="toast show align-items-center"
               :class="toast.type === 'success' ? 'text-bg-success' : toast.type === 'info' ? 'text-bg-info' : 'text-bg-danger'"
               role="alert">
            <div class="d-flex">
              <div class="toast-body d-flex align-items-center gap-2 small">
                <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-circle'"></i>
                {{ toast.msg }}
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="toast.show = false"></button>
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

// ── Props ──────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  // Config du programme (injectée par la page spécifique)
  config: {
    titre:            string   // "Conformité" | "Marchés" | "Transactions"
    titreComplet:     string
    titreSection1:    string
    codePrefix:       string   // "PCONF" | "PMAR" | "PTRANS"
    color:            string   // classe Bootstrap
    colorHex:         string   // couleur hex pour le header
    iconSousTitre:    string   // icone tabler
    labelItems:       string   // "texte(s)" | "marché(s)" | "transaction(s)"
    labelContexte:    string   // "Texte réf." | "N° Marché" | "Nature"
    keyContexte:      string   // "texte_ref" | "marche_numero" | "nature_transaction"
    keySourceItems:   string   // "textes_reference" | "marches_selectionnes" | "transactions_cibles"
    colonnesSource:   {key: string, label: string, placeholder?: string}[]
    colonneContexte:  boolean
    msgSourceDisponible: string
    msgSourceVide:    string
  }
  mission?:          any
  auditorRole?:      string
  missionId?:        number
  assignmentId?:     number
  form?:             any
  phaseAuditeurs?:   any[]
  donneesSource?:    { textes?: any[]; marches?: any[]; transactions?: any[]; diligences?: any[]; source?: string }
  missionContext?:   { mission_id?: number; assignment_id?: number; mission_libelle?: string; code_mission?: string }
  backUrl?:          string
  urlStore?:         string
  urlUpdate?:        string
  urlSoumettre?:     string
  urlValider?:       string
  urlAiSuggest?:     string
  urlBase?:          string
}>(), {
  phaseAuditeurs: () => [],
  donneesSource:  () => ({}),
  missionContext: () => ({}),
})

let _uid = 0; const uid = () => String(++_uid)

const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  observations: '',
  ...(props.form ?? {}),
})

// URLs dynamiques (mis à jour après store)
const dynUrls = reactive({
  aiSuggest: props.urlAiSuggest ?? null as string | null,
  update:    props.urlUpdate    ?? null as string | null,
  soumettre: props.urlSoumettre ?? null as string | null,
  valider:   props.urlValider   ?? null as string | null,
})

// ── Source items (textes / marchés / transactions) ────────────
const sourceItems = reactive<any[]>([])
// ── Diligences ─────────────────────────────────────────────────
const diligences = reactive<any[]>([])

function initData() {
  sourceItems.splice(0)
  diligences.splice(0)

  const keySource = props.config.keySourceItems
  const fromForm  = safeArr(props.form?.[keySource])
  const fromSrc   = safeArr((props.donneesSource as any)?.[keySource.replace('_selectionnes','').replace('_cibles','')] ?? (props.donneesSource as any)?.textes ?? (props.donneesSource as any)?.marches ?? (props.donneesSource as any)?.transactions)

  // Source items : depuis form si déjà sauvegardé, sinon depuis source
  ;(fromForm.length ? fromForm : fromSrc).forEach((item: any) => sourceItems.push({ ...item }))

  // Diligences : depuis form si déjà sauvegardé
  const dilFromForm = safeArr(props.form?.diligences)
  const dilFromSrc  = safeArr(props.donneesSource?.diligences)
  ;(dilFromForm.length ? dilFromForm : dilFromSrc).forEach((d: any) => diligences.push(hydrateDil(d)))
}
initData()

function hydrateDil(d: any) {
  return {
    ...d,
    _uid:           uid(),
    _ia_loading:    false,
    _ia_suggestion: null as any,
    procedures:     Array.isArray(d.procedures) ? [...d.procedures] : [],
  }
}

const processing = ref(false)
const aiRunning  = ref(false)
const toast = ref({ show: false, type: 'success', msg: '' })
let _tt: any

function showToast(t: string, m: string, dur = 4000) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show: true, type: t, msg: m }
  _tt = setTimeout(() => { toast.value.show = false }, dur)
}

// ── Computed ───────────────────────────────────────────────────
const canManage       = computed(() => ['DM','CM'].includes(props.auditorRole ?? ''))
const isLocked        = computed(() => form.validation_status === 'validated' || (form.validation_status === 'in_review' && !canManage.value))
const auditeurOptions = computed(() => props.phaseAuditeurs ?? [])
const missionLibelle  = computed(() => props.mission?.libelle ?? props.missionContext?.mission_libelle ?? '')

function urlAI(): string | null {
  if (dynUrls.aiSuggest)  return dynUrls.aiSuggest
  if (props.urlAiSuggest) return props.urlAiSuggest
  if (form.id && props.urlBase) return `${props.urlBase}/${form.id}/ai-suggest`
  return null
}

// ── IA auto au chargement ──────────────────────────────────────
onMounted(async () => {
  if (isLocked.value || !diligences.some(d => d._needs_ai)) return
  if (!form.id) {
    const ok = await autoSave()
    if (!ok) return
  }
  aiRunning.value = true
  let nb = 0
  for (const dil of diligences) {
    if (!dil._needs_ai) continue
    await suggererIASilencieux(dil)
    nb++
  }
  aiRunning.value = false
  if (nb > 0) {
    await submit(true)
    showToast('success', `IA : ${nb} diligence(s) générée(s) et sauvegardées.`)
  }
})

async function autoSave(): Promise<boolean> {
  if (!props.urlStore) return false
  try {
    const res = await fetch(props.urlStore, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        mission_id:    props.missionId    ?? props.missionContext?.mission_id,
        assignment_id: props.assignmentId ?? props.missionContext?.assignment_id,
        ...serializePayload(),
      }),
    })
    const d = await res.json()
    if ((d.success || res.ok) && d.form) {
      Object.assign(form, { id: d.form.id, code: d.form.code, validation_status: d.form.validation_status })
      if (d.urlAiSuggest) dynUrls.aiSuggest = d.urlAiSuggest
      if (d.urlUpdate)    dynUrls.update    = d.urlUpdate
      if (d.urlSoumettre) dynUrls.soumettre = d.urlSoumettre
      if (d.urlValider)   dynUrls.valider   = d.urlValider
      return true
    }
    return false
  } catch { return false }
}

async function suggererIASilencieux(dil: any) {
  const url = urlAI(); if (!url) return
  dil._ia_loading = true
  try {
    const r = await callAI(url, buildDilPayload(dil))
    if (r.success) {
      dil.diligence  = r.diligence
      dil.procedures = Array.isArray(r.procedures) ? r.procedures : []
      dil._needs_ai  = false
    }
  } catch { /* silencieux */ }
  finally { dil._ia_loading = false }
}

async function suggererIA(dil: any) {
  const url = urlAI()
  if (!url) { showToast(form.id ? 'error' : 'info', form.id ? 'URL IA indisponible.' : 'Enregistrez d\'abord.'); return }
  dil._ia_loading = true; dil._ia_suggestion = null
  try {
    const r = await callAI(url, buildDilPayload(dil))
    if (r.success) dil._ia_suggestion = { diligence: r.diligence, procedures: r.procedures }
    else showToast('error', r.error ?? 'Aucune suggestion.')
  } catch { showToast('error', 'Erreur IA.') }
  finally { dil._ia_loading = false }
}

function appliquerSuggestion(dil: any) {
  if (!dil._ia_suggestion) return
  dil.diligence      = dil._ia_suggestion.diligence
  dil.procedures     = Array.isArray(dil._ia_suggestion.procedures) ? dil._ia_suggestion.procedures : []
  dil._ia_suggestion = null
  showToast('success', 'Suggestion appliquée.')
}

function buildDilPayload(dil: any) {
  return {
    diligence: {
      ref:        dil.ref,
      diligence:  dil.diligence,
      procedures: dil.procedures,
      [props.config.keyContexte]: dil[props.config.keyContexte] ?? '',
      ...(dil.article    ? { article: dil.article }       : {}),
      ...(dil.texte_ref  ? { texte_ref: dil.texte_ref }   : {}),
      ...(dil.assertion  ? { assertion: dil.assertion }   : {}),
      ...(dil.phase_marche ? { phase_marche: dil.phase_marche } : {}),
    },
    context: {
      ...(props.missionContext ?? {}),
      mission_libelle: missionLibelle.value,
    }
  }
}

async function callAI(url: string, body: any) {
  const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify(body) })
  if (!r.ok) throw new Error(`HTTP ${r.status}`)
  return r.json()
}

// ── CRUD source items ─────────────────────────────────────────
function ajouterSourceItem() {
  const newItem: any = {}
  props.config.colonnesSource.forEach(c => { newItem[c.key] = '' })
  sourceItems.push(newItem)
}
function supprimerSourceItem(idx: number) { sourceItems.splice(idx, 1) }

// ── CRUD diligences ───────────────────────────────────────────
function ajouterDiligence() {
  const nb  = diligences.length + 1
  const pre = props.config.codePrefix === 'PCONF' ? 'D-C' : props.config.codePrefix === 'PMAR' ? 'D-M' : 'D-T'
  diligences.push(hydrateDil({
    ref: pre + String(nb).padStart(2, '0'),
    diligence: '', procedures: [],
    [props.config.keyContexte]: '',
    responsable: '', date_debut: '', date_fin: '', statut: 'planifie', observations: '',
  }))
}
function supprimerDiligence(idx: number) { diligences.splice(idx, 1) }

function serializePayload() {
  const key = props.config.keySourceItems
  return {
    [key]:      JSON.stringify(sourceItems.map(i => ({ ...i }))),
    diligences: JSON.stringify(diligences.map(d => ({
      ref:         d.ref,
      diligence:   d.diligence,
      procedures:  d.procedures ?? [],
      responsable: d.responsable ?? '',
      date_debut:  d.date_debut  ?? '',
      date_fin:    d.date_fin    ?? '',
      statut:      d.statut      ?? 'planifie',
      observations:d.observations ?? '',
      [props.config.keyContexte]: d[props.config.keyContexte] ?? '',
      ...(d.article      ? { article: d.article }             : {}),
      ...(d.texte_ref    ? { texte_ref: d.texte_ref }         : {}),
      ...(d.assertion    ? { assertion: d.assertion }         : {}),
      ...(d.phase_marche ? { phase_marche: d.phase_marche }   : {}),
    }))),
    responsables:  JSON.stringify([]),
    observations:  form.observations ?? '',
  }
}

async function submit(silent = false) {
  processing.value = !silent
  try {
    const url    = form.id ? (dynUrls.update ?? props.urlUpdate) : props.urlStore
    const method = form.id ? 'PUT' : 'POST'
    if (!url) { if (!silent) showToast('error', 'URL de sauvegarde indisponible.'); return }
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        mission_id:    props.missionId    ?? props.missionContext?.mission_id,
        assignment_id: props.assignmentId ?? props.missionContext?.assignment_id,
        ...serializePayload(),
      }),
    })
    const d = await res.json()
    if (d.success || res.ok) {
      if (!silent) showToast('success', form.id ? 'Mis à jour.' : 'Créé.')
      if (d.form) Object.assign(form, { id: d.form.id, code: d.form.code, validation_status: d.form.validation_status })
      if (d.urlAiSuggest) dynUrls.aiSuggest = d.urlAiSuggest
      if (d.urlUpdate)    dynUrls.update    = d.urlUpdate
      if (d.urlSoumettre) dynUrls.soumettre = d.urlSoumettre
      if (d.urlValider)   dynUrls.valider   = d.urlValider
    } else if (!silent) showToast('error', d.message ?? 'Erreur.')
  } catch { if (!silent) showToast('error', 'Erreur réseau.') }
  finally { processing.value = false }
}

function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function soumettre() {
  processing.value = true
  try {
    const url = dynUrls.soumettre ?? props.urlSoumettre ?? ''
    const d = await (await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId ?? props.missionContext?.mission_id, assignment_id: props.assignmentId ?? props.missionContext?.assignment_id }) })).json()
    if (d.success) { form.validation_status = 'in_review'; showToast('success', 'Soumis pour validation.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

async function valider(action: string, note?: string) {
  processing.value = true
  try {
    const url = dynUrls.valider ?? props.urlValider ?? ''
    const d = await (await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId ?? props.missionContext?.mission_id, assignment_id: props.assignmentId ?? props.missionContext?.assignment_id, action, note }) })).json()
    if (d.success) { form.validation_status = d.status; showToast('success', action === 'validate' ? 'Validé ✓' : 'Rejeté.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

function promptReject() {
  const n = prompt('Motif du rejet (obligatoire) :', '')
  if (!n?.trim()) return
  valider('reject', n.trim())
}

// ── Helpers ────────────────────────────────────────────────────
function safeArr(v: any): any[] {
  if (Array.isArray(v)) return [...v]
  if (!v) return []
  try { const d = JSON.parse(v); return Array.isArray(d) ? d : [] } catch { return [] }
}
function csrf() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
function vstLbl(s: string)   { return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓' } as any)[s] ?? s }
function vstIcon(s: string)  { return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check' } as any)[s] ?? 'ti ti-circle' }
function vstBadge(s: string) { return ({ draft: 'bg-secondary', in_review: 'bg-info text-dark', validated: 'bg-success' } as any)[s] ?? 'bg-secondary' }
function statutLbl(s: string){ return ({ planifie: 'Planifié', en_cours: 'En cours', realise: 'Réalisé', reporte: 'Reporté' } as any)[s] ?? s }
function statutBadge(s: string) { return ({ planifie: 'bg-secondary', en_cours: 'bg-info text-dark', realise: 'bg-success', reporte: 'bg-warning text-dark' } as any)[s] ?? 'bg-secondary' }
function formatDate(d: string) {
  if (!d) return '—'
  try { return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) } catch { return d }
}

onBeforeUnmount(() => { if (_tt) clearTimeout(_tt) })
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all .2s ease }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(6px) }
</style>