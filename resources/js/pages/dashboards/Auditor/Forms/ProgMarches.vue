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
                <code class="bg-dark text-white px-2 py-1 rounded small fw-bold">{{ form.code || 'PMAR-AUTO' }}</code>
                <span class="badge" :class="vstBadge(form.validation_status||'draft')">
                  <i :class="vstIcon(form.validation_status||'draft')"></i>
                  {{ vstLbl(form.validation_status||'draft') }}
                </span>
                <span class="badge bg-warning text-dark">Programme Marchés</span>
                <span v-if="sourceLabel" class="badge" :class="sourceLabel==='RADO+RCI'?'bg-info text-dark':'bg-secondary'">
                  <i class="ti ti-link"></i> {{ sourceLabel }}
                </span>
                <span v-if="props.auditorRole" class="badge bg-secondary">{{ props.auditorRole }}</span>
              </div>
              <h6 class="mb-0 fw-bold">Programme de Travail d'Audit — Marchés Publics</h6>
              <div class="d-flex gap-3 flex-wrap mt-1">
                <small v-if="mission?.code_mission" class="text-muted">
                  <i class="ti ti-clipboard"></i> {{ mission.code_mission }}
                </small>
                <small v-if="mission?.libelle" class="text-muted">
                  <i class="ti ti-file-description"></i> {{ mission.libelle }}
                </small>
                <small class="fw-semibold" style="color:#7c3aed">
                  <i class="ti ti-target"></i> {{ objectifs.length }} objectif(s)
                </small>
                <small class="text-success fw-semibold">
                  <i class="ti ti-checklist"></i> {{ totalTests }} test(s)
                </small>
              </div>
            </div>
          </div>
        </div>
        <div v-if="form.validation_status==='validated'"
             class="alert alert-success mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
          <i class="ti ti-lock"></i> Programme Marchés <strong>validé définitivement</strong> — lecture seule
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
        <div class="alert py-2 px-3 mb-0 small flex-grow-1"
             :class="sourceLabel==='RADO+RCI' ? 'alert-info' : 'alert-warning'">
          <i class="ti ti-info-circle"></i>
          <span v-if="sourceLabel==='RADO+RCI'">
            Objectifs issus du <strong>Rapport d'Orientation</strong>.
            Tests et procédures liés au <strong>RCI</strong> (col. H et J).
            L'IA génère des propositions adaptées à chaque objectif.
          </span>
          <span v-else>
            Objectifs issus du <strong>RCI</strong>.
            Tests et procédures liés au RCI (col. H et J).
            L'IA reformule les tests selon l'objectif.
          </span>
        </div>
        <a v-if="props.urlModeleExcel" :href="props.urlModeleExcel"
           class="btn btn-outline-success btn-sm" download>
          <i class="ti ti-file-download"></i> Modèle Excel
        </a>
        <label v-if="!isLocked" class="btn btn-outline-primary btn-sm mb-0">
          <span v-if="uploadLoading" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-file-upload"></i> Importer Excel
          <input type="file" accept=".xlsx,.xls" class="d-none" @change="importerExcel"/>
        </label>
      </div>

      <!-- ALERTE source manquante -->
      <div v-if="!hasDonneesRCI && !objectifs.length" class="alert alert-warning">
        <i class="ti ti-alert-triangle"></i>
        Aucun RCI disponible. Complétez d'abord le <strong>Référentiel de Contrôle Interne</strong>
        (et idéalement le <strong>Rapport d'Orientation</strong>) ou importez un fichier Excel.
      </div>

      <!-- TABLEAU PROGRAMME CI -->
      <div v-if="objectifs.length" class="card border-0 shadow-sm mb-3">
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle mb-0" style="min-width:1100px">
            <thead>
              <!-- Titre document -->
              <tr>
                <th :colspan="isLocked ? 11 : 12"
                    class="text-center text-white fw-bold py-2"
                    style="background:#0f172a;font-size:.85rem;letter-spacing:.05em">
                  PROGRAMME DE TRAVAIL D'AUDIT — MARCHÉS PUBLICS
                </th>
              </tr>
              <!-- Mission / Responsable -->
              <tr class="table-light">
                <td colspan="5" class="small py-1 px-2">
                  <span class="fw-semibold text-secondary">Mission d'audit :</span>
                  <span class="ms-2 fw-bold">
                    {{ mission?.libelle || props.missionContext?.mission_libelle || '—' }}
                  </span>
                </td>
                <td :colspan="isLocked ? 6 : 7" class="small py-1 px-2 text-end">
                  <span class="fw-semibold text-secondary">Responsable :</span>
                  <span class="ms-2 fw-bold">{{ responsableMission }}</span>
                </td>
              </tr>
              <!-- Note liaison -->
              <tr>
                <td :colspan="isLocked ? 11 : 12" class="small py-1 px-2 fst-italic"
                    style="background:#fffbeb;color:#92400e;border-color:#fde68a">
                  📌 Les colonnes « Test d'Audit » et « Procédures d'Audit » sont liées aux
                  colonnes « Description du Contrôle » (H) et « Preuve du Contrôle » (J) du RCI.
                  Les objectifs sont issus du Rapport d'Orientation (RADO).
                </td>
              </tr>
              <!-- Groupes colonnes -->
              <tr>
                <th colspan="3" class="text-center text-white small py-1"
                    style="background:#1e40af">IDENTIFICATION</th>
                <th colspan="2" class="text-center text-white small py-1"
                    style="background:#6d28d9">TESTS &amp; PROCÉDURES → liés au RCI</th>
                <th colspan="6" class="text-center text-white small py-1"
                    style="background:#065f46">PLANIFICATION / RESSOURCES</th>
                <th v-if="!isLocked" class="text-center text-white small py-1"
                    style="background:#374151">⚙</th>
              </tr>
              <!-- Colonnes -->
              <tr>
                <th class="text-center small" style="background:#1e40af;color:#fff;width:70px">
                  Réf.<br/>Objectif
                </th>
                <th class="small" style="background:#1e40af;color:#fff;min-width:180px">
                  Objectif d'Audit<br/>
                  <span style="font-size:.6rem;opacity:.8;font-weight:400">Source : RADO / RCI</span>
                </th>
                <th class="text-center small" style="background:#1e40af;color:#fff;width:90px">
                  Réf.<br/>Contrôle RCI
                </th>
                <th class="small" style="background:#6d28d9;color:#fff;min-width:190px">
                  Test d'Audit<br/>
                  <span style="font-size:.6rem;opacity:.8;font-weight:400">⬤ lien → col. H du RCI</span>
                </th>
                <th class="small" style="background:#6d28d9;color:#fff;min-width:200px">
                  Procédures d'Audit<br/>
                  <span style="font-size:.6rem;opacity:.8;font-weight:400">⬤ lien → col. J du RCI</span>
                </th>
                <th class="text-center small" style="background:#065f46;color:#fff;width:80px">
                  Taille<br/>Échantillon
                </th>
                <th class="text-center small" style="background:#065f46;color:#fff;width:90px">
                  Période<br/>testée
                </th>
                <th class="small" style="background:#065f46;color:#fff;width:120px">
                  Auditeur<br/>Responsable
                </th>
                <th class="text-center small" style="background:#065f46;color:#fff;width:95px">
                  Date<br/>Début
                </th>
                <th class="text-center small" style="background:#065f46;color:#fff;width:95px">
                  Date<br/>Fin
                </th>
                <th class="small" style="background:#065f46;color:#fff;width:120px">
                  Lieu /<br/>Local
                </th>
                <th v-if="!isLocked" class="text-center small"
                    style="background:#374151;color:#fff;width:38px">⚙</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(obj, oi) in objectifs" :key="obj._uid">

                <!-- Bandeau axe RADO (si disponible) -->
                <tr v-if="obj._axe_rado || obj._axe">
                  <td :colspan="isLocked ? 11 : 12" class="py-1 px-2"
                      style="background:#eff6ff;border-color:#bfdbfe">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-primary" style="font-size:.6rem">
                        <i class="ti ti-compass"></i> Axe RADO
                      </span>
                      <span class="small fw-semibold text-primary">
                        {{ obj._axe_rado || obj._axe }}
                      </span>
                      <span v-if="obj._indicateurs" class="text-muted small ms-auto">
                        <i class="ti ti-chart-bar"></i> {{ obj._indicateurs }}
                      </span>
                    </div>
                  </td>
                </tr>

                <template v-for="(test, ti) in obj.tests" :key="test._tuid">
                  <tr :class="oi%2===0 ? '' : 'table-light'">

                    <!-- Réf. Objectif (rowspan) -->
                    <td v-if="ti===0" :rowspan="obj.tests.length"
                        class="align-top text-center px-2 py-2">
                      <span class="badge bg-primary d-block mb-1"
                            style="font-family:monospace;font-size:.75rem">{{ obj.num }}</span>
                      <span class="badge d-block"
                            :class="obj._source?.startsWith('RADO') ? 'bg-info text-dark' : 'bg-secondary'"
                            style="font-size:.55rem">
                        {{ obj._source?.startsWith('RADO') ? 'RADO' : 'RCI' }}
                      </span>
                    </td>

                    <!-- Objectif d'Audit (rowspan) -->
                    <td v-if="ti===0" :rowspan="obj.tests.length" class="align-top px-2 py-2">
                      <div v-if="!isLocked">
                        <textarea class="form-control form-control-sm mb-2"
                                  v-model="obj.objectif" rows="3"
                                  @change="obj._edited=true"
                                  placeholder="S'assurer que…"
                                  style="font-size:.73rem;resize:vertical"></textarea>
                        <button class="btn btn-outline-primary w-100"
                                style="font-size:.63rem;padding:2px 6px"
                                @click="ajouterTest(obj)">
                          <i class="ti ti-plus"></i> Ajouter un test
                        </button>
                      </div>
                      <p v-else class="mb-0 small">{{ obj.objectif || '—' }}</p>
                    </td>

                    <!-- Réf. Contrôle RCI (rowspan) -->
                    <td v-if="ti===0" :rowspan="obj.tests.length"
                        class="align-top text-center px-2 py-2">
                      <span v-if="obj.ref_rci" class="badge"
                            style="background:#ede9fe;color:#7c3aed;font-family:monospace;font-size:.68rem">
                        {{ obj.ref_rci }}
                      </span>
                      <span v-else class="text-muted small">—</span>
                    </td>

                    <!-- Test d'Audit -->
                    <td class="align-top px-2 py-2">
                      <!-- En-tête test : ref + spinner IA + boutons -->
                      <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge"
                              style="background:#ede9fe;color:#7c3aed;font-family:monospace;font-size:.63rem">
                          {{ test.ref }}
                        </span>
                        <span v-if="test._ia_loading"
                              class="badge bg-light border text-primary"
                              style="font-size:.58rem">
                          <span class="spinner-border spinner-border-sm"
                                style="width:.5rem;height:.5rem"></span> IA…
                        </span>
                        <div v-if="!isLocked" class="ms-auto d-flex gap-1">
                          <button class="btn btn-sm"
                                  style="background:#eef2ff;border-color:#c7d2fe;color:#4338ca;
                                         font-size:.6rem;padding:2px 7px"
                                  :disabled="test._ia_loading"
                                  title="Générer un test adapté à cet objectif par IA"
                                  @click="reformulerTest(obj, test)">
                            <i class="ti ti-sparkles"></i> IA
                          </button>
                          <button v-if="obj.tests.length > 1"
                                  class="btn btn-sm btn-outline-danger"
                                  style="font-size:.6rem;padding:2px 5px"
                                  @click="supprimerTest(obj, ti)">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </div>

                      <!-- Libellé test -->
                      <textarea v-if="!isLocked"
                                class="form-control form-control-sm"
                                v-model="test.libelle" rows="3"
                                @change="test._edited=true"
                                placeholder="Rapprochement entre… / Examen des…"
                                style="font-size:.73rem;resize:vertical"></textarea>
                      <p v-else class="mb-0 small">{{ test.libelle || '—' }}</p>

                      <!-- Suggestions IA (propositions à cliquer) -->
                      <div v-if="test._ia_suggestions?.length"
                           class="mt-2 pt-2 border-top"
                           style="border-color:#c7d2fe!important">
                        <div class="small fw-semibold mb-1" style="color:#4338ca;font-size:.62rem">
                          <i class="ti ti-sparkles"></i>
                          Propositions IA — cliquer pour appliquer
                        </div>
                        <div v-for="(s, si) in test._ia_suggestions" :key="si"
                             class="rounded p-2 mb-1"
                             style="background:#eef2ff;border:1px solid #c7d2fe;
                                    font-size:.68rem;cursor:pointer"
                             @mouseenter="$event.currentTarget.style.background='#e0e7ff'"
                             @mouseleave="$event.currentTarget.style.background='#eef2ff'"
                             @click="appliquerSuggestion(test, s)">
                          <strong style="color:#4338ca">{{ s.ref }}</strong>
                          — {{ s.libelle }}
                          <span v-if="s.procedures?.length"
                                class="ms-1 badge bg-secondary"
                                style="font-size:.55rem">
                            {{ s.procedures.length }} étapes
                          </span>
                        </div>
                      </div>
                    </td>

                    <!-- Procédures d'Audit -->
                    <td class="align-top px-2 py-2">
                      <div v-if="!isLocked" class="d-flex flex-column gap-1">
                        <div v-for="(proc, pi) in test.procedures" :key="pi"
                             class="d-flex align-items-start gap-1">
                          <span class="text-muted fw-bold"
                                style="font-size:.63rem;min-width:14px;margin-top:5px">
                            {{ pi+1 }}.
                          </span>
                          <textarea class="form-control form-control-sm flex-grow-1"
                                    v-model="test.procedures[pi]"
                                    rows="2" @change="test._edited=true"
                                    :placeholder="`Étape ${pi+1}…`"
                                    style="font-size:.69rem;resize:vertical"></textarea>
                          <button class="btn btn-sm btn-outline-secondary"
                                  style="font-size:.58rem;padding:2px 5px;margin-top:2px"
                                  @click="supprimerProcedure(test, pi)">
                            <i class="ti ti-x"></i>
                          </button>
                        </div>
                        <button class="btn btn-outline-success btn-sm"
                                style="font-size:.63rem"
                                @click="ajouterProcedure(test)">
                          <i class="ti ti-plus"></i> Étape
                        </button>
                      </div>
                      <div v-else class="d-flex flex-column gap-1">
                        <div v-for="(proc, pi) in test.procedures" :key="pi"
                             class="d-flex gap-1 small">
                          <span class="text-muted fw-bold" style="min-width:14px">{{ pi+1 }}.</span>
                          <span>{{ proc }}</span>
                        </div>
                        <span v-if="!test.procedures?.length"
                              class="text-muted fst-italic small">—</span>
                      </div>
                    </td>

                    <!-- Taille Échantillon -->
                    <td class="text-center align-middle px-1 py-1">
                      <input v-if="!isLocked" type="text"
                             class="form-control form-control-sm text-center"
                             v-model="test.taille_echantillon"
                             @change="test._edited=true"
                             placeholder="30…"
                             style="font-size:.71rem"/>
                      <span v-else class="small">{{ test.taille_echantillon || '—' }}</span>
                    </td>

                    <!-- Période testée -->
                    <td class="text-center align-middle px-1 py-1">
                      <input v-if="!isLocked" type="text"
                             class="form-control form-control-sm text-center"
                             v-model="test.periode_testee"
                             @change="test._edited=true"
                             placeholder="Jan–Mar…"
                             style="font-size:.71rem"/>
                      <span v-else class="small">{{ test.periode_testee || '—' }}</span>
                    </td>

                    <!-- Auditeur Responsable -->
                    <td class="align-middle px-1 py-1">
                      <select v-if="!isLocked && auditeurOptions.length"
                              class="form-select form-select-sm"
                              v-model="test.auditeur"
                              @change="test._edited=true"
                              style="font-size:.71rem">
                        <option value="">—</option>
                        <option v-for="a in auditeurOptions" :key="a.id" :value="a.full_name">
                          {{ a.full_name }} ({{ a.role_code }})
                        </option>
                      </select>
                      <input v-else-if="!isLocked" type="text"
                             class="form-control form-control-sm"
                             v-model="test.auditeur"
                             @change="test._edited=true"
                             style="font-size:.71rem"/>
                      <span v-else class="small">{{ test.auditeur || '—' }}</span>
                    </td>

                    <!-- Date Début -->
                    <td class="text-center align-middle px-1 py-1">
                      <input v-if="!isLocked" type="date"
                             class="form-control form-control-sm"
                             v-model="test.date_debut"
                             @change="test._edited=true"
                             style="font-size:.69rem"/>
                      <span v-else class="small">{{ formatDate(test.date_debut) }}</span>
                    </td>

                    <!-- Date Fin -->
                    <td class="text-center align-middle px-1 py-1">
                      <input v-if="!isLocked" type="date"
                             class="form-control form-control-sm"
                             v-model="test.date_fin"
                             @change="test._edited=true"
                             style="font-size:.69rem"/>
                      <span v-else class="small">{{ formatDate(test.date_fin) }}</span>
                    </td>

                    <!-- Lieu / Local -->
                    <td class="align-middle px-1 py-1">
                      <input v-if="!isLocked" type="text"
                             class="form-control form-control-sm"
                             v-model="test.lieu"
                             @change="test._edited=true"
                             placeholder="Direction…"
                             style="font-size:.71rem"/>
                      <span v-else class="small">{{ test.lieu || '—' }}</span>
                    </td>

                    <!-- Actions (colonne supplémentaire) -->
                    <td v-if="!isLocked" class="text-center align-middle px-1 py-1">
                      <!-- vide — actions dans la cellule test -->
                    </td>

                  </tr>
                </template>

                <!-- Séparateur entre objectifs -->
                <tr>
                  <td :colspan="isLocked ? 11 : 12" class="p-0"
                      style="background:#cbd5e1;height:3px;border:none"></td>
                </tr>

              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- FOOTER -->
      <div class="card border-0 shadow-sm">
        <div class="card-body py-2 px-3 d-flex align-items-center
                    justify-content-between flex-wrap gap-2">
          <div class="d-flex gap-2">
            <button v-if="!isLocked" type="button"
                    class="btn btn-outline-secondary btn-sm"
                    :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button v-if="!isLocked" type="button"
                    class="btn btn-dark btn-sm"
                    :disabled="processing" @click="submit">
              <span v-if="processing" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="ti ti-device-floppy me-1"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>

          <div class="d-flex gap-2 align-items-center">
            <span v-if="form.id" class="badge bg-success">
              <i class="ti ti-check"></i> {{ form.code }}
            </span>
            <span class="badge bg-warning text-dark">
              {{ objectifs.length }} objectif(s) · {{ totalTests }} test(s)
            </span>
          </div>

          <div class="d-flex gap-2">
            <button v-if="form.id && form.validation_status==='draft'"
                    type="button" class="btn btn-primary btn-sm"
                    :disabled="processing" @click="soumettre">
              <i class="ti ti-send me-1"></i> Soumettre
            </button>
            <template v-if="canManage && form.validation_status==='in_review'">
              <button type="button" class="btn btn-success btn-sm"
                      :disabled="processing" @click="valider('validate')">
                <i class="ti ti-circle-check me-1"></i> Valider
              </button>
              <button type="button" class="btn btn-outline-danger btn-sm"
                      :disabled="processing" @click="promptReject">
                <i class="ti ti-circle-x me-1"></i> Rejeter
              </button>
            </template>
          </div>
        </div>
      </div>

    </div>

    <!-- Toast -->
    <Teleport to="body">
      <div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
        <Transition name="fade">
          <div v-if="toast.show"
               class="toast show align-items-center"
               :class="toast.type==='success' ? 'text-bg-success' : 'text-bg-danger'"
               role="alert">
            <div class="d-flex">
              <div class="toast-body d-flex align-items-center gap-2 small">
                <i :class="toast.type==='success'?'ti ti-circle-check':'ti ti-alert-circle'"></i>
                {{ toast.msg }}
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto"
                      @click="toast.show=false"></button>
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
  mission?:        any
  auditorRole?:    string
  missionId?:      number
  assignmentId?:   number
  form?:           any
  phaseAuditeurs?: any[]
  donneesRCI?:     { lignes?: any[]; total?: number; source?: string; processus?: string }
  missionContext?: {
    mission_id?:      number
    mission_libelle?: string
    code_mission?:    string
    processus?:       string
    source?:          string   // 'rado+rci' | 'rci' | 'none'
  }
  backUrl?:        string
  urlStore?:       string
  urlUpdate?:      string
  urlSoumettre?:   string
  urlValider?:     string
  urlAiSuggest?:   string
  urlUpload?:      string
  urlModeleExcel?: string
  urlBase?:        string
}>(), {
  phaseAuditeurs: () => [],
  donneesRCI:     () => ({ lignes: [], total: 0 }),
  missionContext: () => ({}),
})

const mission = props.mission ?? props.form?.mission
let _uid = 0; const uid  = () => String(++_uid)
let _tid = 0; const tuid = () => String(++_tid)

// ── State ──────────────────────────────────────────────────────
const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  ...(props.form ?? {}),
})

const objectifs = reactive<any[]>(
  safeArr(props.form?.lignes).map(o => hydrateObj(o))
)
if (!objectifs.length && (props.donneesRCI?.lignes?.length ?? 0) > 0) {
  props.donneesRCI!.lignes!.forEach(o => objectifs.push(hydrateObj(o)))
}

const processing    = ref(false)
const uploadLoading = ref(false)
const toast = ref({ show: false, type: 'success', msg: '' })
let _tt: any

function showToast(t: string, m: string) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show: true, type: t, msg: m }
  _tt = setTimeout(() => { toast.value.show = false }, 4500)
}

function hydrateObj(o: any) {
  return {
    ...o,
    _uid: uid(), _edited: false,
    tests: (o.tests ?? []).map((t: any) => hydrateTest(t)),
  }
}
function hydrateTest(t: any) {
  return {
    ...t, _tuid: tuid(), _edited: false,
    _ia_loading:     false,
    _ia_suggestions: null as any[] | null,
    procedures:      Array.isArray(t.procedures) ? [...t.procedures] : [],
  }
}

// ── Computed ───────────────────────────────────────────────────
const canManage       = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked        = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)
const hasDonneesRCI   = computed(() => (props.donneesRCI?.lignes?.length ?? 0) > 0)
const auditeurOptions = computed(() => props.phaseAuditeurs ?? [])
const totalTests      = computed(() => objectifs.reduce((s, o) => s + (o.tests?.length ?? 0), 0))

const responsableMission = computed(() => {
  const dm = (props.phaseAuditeurs ?? []).find(a => a.role_code === 'DM' || a.role_code === 'CM')
  return dm?.full_name ?? '—'
})

// Label de la source affiché dans le header
const sourceLabel = computed(() => {
  const src = props.donneesRCI?.source ?? props.missionContext?.source ?? ''
  if (src === 'rado+rci') return 'RADO+RCI'
  if (src === 'rci')      return 'RCI'
  // Détecter depuis les objectifs chargés
  if (objectifs.some(o => o._rado_id || (o._source && o._source.startsWith('RADO')))) return 'RADO+RCI'
  if (objectifs.length)   return 'RCI'
  return ''
})

// ── IA automatique à l'ouverture ──────────────────────────────
onMounted(async () => {
  if (isLocked.value) return
  const besoinIA = objectifs.some(o => o._needs_ai)
  if (!besoinIA) return

  // Auto-save silencieux si pas encore de form.id
  if (!form.id) await autoSave()
  if (!form.id) return  // auto-save a échoué

  // Lancer l'IA pour chaque objectif marqué _needs_ai
  for (const obj of objectifs) {
    if (obj._needs_ai) await aiReformuler(obj)
  }
})

// Auto-save silencieux pour obtenir form.id avant l'IA
async function autoSave() {
  if (!props.urlStore) return
  try {
    const res = await fetch(props.urlStore, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body:    JSON.stringify({
        mission_id:    props.missionId,
        assignment_id: props.assignmentId,
        lignes:        JSON.stringify(serialize()),
      }),
    })
    const d = await res.json()
    if ((d.success || res.ok) && d.form) {
      Object.assign(form, {
        id: d.form.id, code: d.form.code,
        validation_status: d.form.validation_status,
      })
    }
  } catch { /* silencieux */ }
}

// URL IA dynamique (fonctionne même après auto-save)
function urlAI(): string | null {
  if (!form.id) return null
  if (props.urlAiSuggest) return props.urlAiSuggest
  if (props.urlBase) return `${props.urlBase}/${form.id}/ai-suggest`
  return null
}

// Reformuler tous les tests d'un objectif (appel auto au chargement)
async function aiReformuler(obj: any) {
  const url = urlAI(); if (!url) return
  obj.tests.forEach((t: any) => { t._ia_loading = true })
  try {
    const r = await callAI(url, {
      objectif: buildObjectifPayload(obj, obj.tests),
      mode:    'reformuler',
      context: ctx(obj),
    })
    if (r.success && r.tests?.length) {
      obj.tests.splice(0, obj.tests.length, ...r.tests.map((t: any) => hydrateTest(t)))
      obj._needs_ai = false
      obj._edited   = true
    }
  } catch { /* silencieux */ }
  finally { obj.tests.forEach((t: any) => { t._ia_loading = false }) }
}

// Reformuler un test spécifique (bouton IA manuel)
async function reformulerTest(obj: any, test: any) {
  const url = urlAI()
  if (!url) { showToast('error', 'Enregistrez d\'abord le formulaire.'); return }
  test._ia_loading     = true
  test._ia_suggestions = null
  try {
    const r = await callAI(url, {
      objectif: buildObjectifPayload(obj, [test]),
      mode:    'reformuler',
      context: ctx(obj),
    })
    if (r.success && r.tests?.length) {
      test._ia_suggestions = r.tests
    } else {
      showToast('error', r.error ?? 'Aucune suggestion générée.')
    }
  } catch { showToast('error', 'Erreur lors de la génération IA.') }
  finally { test._ia_loading = false }
}

// Appliquer une suggestion IA
function appliquerSuggestion(test: any, s: any) {
  test.libelle         = s.libelle
  test.procedures      = s.procedures ?? []
  test._ia_suggestions = null
  test._edited         = true
  showToast('success', 'Suggestion appliquée.')
}

async function callAI(url: string, body: any) {
  const r = await fetch(url, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
    body:    JSON.stringify(body),
  })
  return r.json()
}

// Construit le payload objectif avec toutes les méta RADO+RCI
function buildObjectifPayload(obj: any, tests: any[]) {
  return {
    num:      obj.num,
    objectif: obj.objectif,
    ref_rci:  obj.ref_rci,
    tests,
    // Méta RADO
    _axe_rado:              obj._axe_rado              ?? obj._axe ?? '',
    _indicateurs:           obj._indicateurs            ?? '',
    // Méta RCI complètes
    _risque_code:           obj._risque_code            ?? '',
    _risque_libelle:        obj._risque_libelle         ?? '',
    _process_name:          obj._process_name           ?? '',
    _objectif_operationnel: obj._objectif_operationnel  ?? '',
    _description_controle:  obj._description_controle   ?? '',
    _preuve_controle:       obj._preuve_controle        ?? '',
    _type_controle:         obj._type_controle          ?? '',
    _criticite:             obj._criticite              ?? 0,
    _responsable:           obj._responsable            ?? '',
  }
}

// Contexte enrichi envoyé à l'IA — RADO + RCI complets
function ctx(obj: any) {
  return {
    ...(props.missionContext ?? {}),
    // Contexte RADO
    axe_rado:               obj._axe_rado       ?? obj._axe       ?? '',
    indicateurs:            obj._indicateurs                       ?? '',
    // Contexte RCI complet
    risque_code:            obj._risque_code                       ?? '',
    risque_libelle:         obj._risque_libelle                    ?? '',
    processus:              obj._process_name   ?? props.missionContext?.processus ?? '',
    objectif_operationnel:  obj._objectif_operationnel             ?? '',
    type_controle:          obj._type_controle                     ?? '',
    criticite:              obj._criticite                         ?? 0,
    responsable:            obj._responsable                       ?? '',
    obj_num:                obj.num                                ?? '',
  }
}

// ── CRUD tests / procédures ────────────────────────────────────
function ajouterTest(obj: any) {
  const nb = obj.tests.length
  const L  = ['a','b','c','d','e','f']
  if (nb === 1) obj.tests[0].ref = 'T_' + obj.num + '_a'
  obj.tests.push(hydrateTest({
    ref:      'T_' + obj.num + '_' + (L[nb] ?? (nb + 1)),
    libelle:  '',
    procedures: [],
  }))
  obj._edited = true
}
function supprimerTest(obj: any, idx: number) {
  if (obj.tests.length <= 1) { showToast('error', 'Au moins un test requis.'); return }
  obj.tests.splice(idx, 1)
  if (obj.tests.length === 1) obj.tests[0].ref = 'T_' + obj.num
  obj._edited = true
}
function ajouterProcedure(test: any)             { test.procedures.push(''); test._edited = true }
function supprimerProcedure(test: any, idx: number) { test.procedures.splice(idx, 1); test._edited = true }

// ── Import Excel ───────────────────────────────────────────────
async function importerExcel(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file || !props.urlUpload) return
  uploadLoading.value = true
  try {
    const fd = new FormData()
    fd.append('file', file)
    fd.append('_token', csrf())
    const res = await fetch(props.urlUpload, { method: 'POST', body: fd })
    const d   = await res.json()
    if (d.success && d.lignes?.length) {
      objectifs.splice(0, objectifs.length, ...d.lignes.map((o: any) => hydrateObj(o)))
      showToast('success', d.message ?? `${d.total} objectif(s) importé(s)`)
    } else {
      showToast('error', d.error ?? 'Erreur lors de l\'import.')
    }
  } catch { showToast('error', 'Erreur réseau.') }
  finally {
    uploadLoading.value = false
    ;(event.target as HTMLInputElement).value = ''
  }
}

// ── Sérialisation — conserve toutes les méta RADO+RCI ─────────
function serialize() {
  return objectifs.map(o => ({
    num:      o.num,
    objectif: o.objectif,
    ref_rci:  o.ref_rci,
    tests: (o.tests ?? []).map((t: any) => ({
      ref:                 t.ref,
      libelle:             t.libelle,
      procedures:          t.procedures ?? [],
      auditeur:            t.auditeur            ?? '',
      date_debut:          t.date_debut           ?? '',
      date_fin:            t.date_fin             ?? '',
      lieu:                t.lieu                 ?? '',
      taille_echantillon:  t.taille_echantillon   ?? '',
      periode_testee:      t.periode_testee       ?? '',
    })),
    // Méta conservées pour rechargements ultérieurs (RADO + RCI)
    _source:                  o._source                  ?? null,
    _rado_id:                 o._rado_id                 ?? null,
    _rci_id:                  o._rci_id                  ?? null,
    _axe_rado:                o._axe_rado                ?? o._axe ?? null,
    _indicateurs:             o._indicateurs             ?? null,
    _risque_code:             o._risque_code             ?? null,
    _risque_libelle:          o._risque_libelle          ?? null,
    _process_name:            o._process_name            ?? null,
    _objectif_operationnel:   o._objectif_operationnel   ?? null,
    _description_controle:    o._description_controle    ?? null,
    _preuve_controle:         o._preuve_controle         ?? null,
    _type_controle:           o._type_controle           ?? null,
    _criticite:               o._criticite               ?? null,
    _responsable:             o._responsable             ?? null,
  }))
}

// ── Submit ─────────────────────────────────────────────────────
async function submit() {
  processing.value = true
  try {
    const url    = form.id ? props.urlUpdate! : props.urlStore!
    const method = form.id ? 'PUT' : 'POST'
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        mission_id:    props.missionId,
        assignment_id: props.assignmentId,
        lignes:        JSON.stringify(serialize()),
      }),
    })
    const d = await res.json()
    if (d.success || res.ok) {
      showToast('success', form.id ? 'Programme Marchés mis à jour.' : 'Programme Marchés créé.')
      if (d.form) Object.assign(form, {
        id: d.form.id, code: d.form.code,
        validation_status: d.form.validation_status,
      })
    } else {
      showToast('error', d.message ?? 'Erreur lors de l\'enregistrement.')
    }
  } catch { showToast('error', 'Erreur réseau.') }
  finally { processing.value = false }
}

function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function soumettre() {
  processing.value = true
  try {
    const d = await (await fetch(props.urlSoumettre || '', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId }),
    })).json()
    if (d.success) { form.validation_status = 'in_review'; showToast('success', 'Soumis pour validation.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

async function valider(action: string, note?: string) {
  processing.value = true
  try {
    const d = await (await fetch(props.urlValider || '', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, action, note }),
    })).json()
    if (d.success) {
      form.validation_status = d.status
      showToast('success', action === 'validate' ? 'Programme Marchés validé ✓' : 'Rejeté.')
    } else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

function promptReject() {
  const n = prompt('Motif du rejet (obligatoire) :')
  if (!n?.trim()) return
  valider('reject', n.trim())
}

// ── Helpers ────────────────────────────────────────────────────
function safeArr(v: any): any[] {
  if (Array.isArray(v)) return [...v]
  if (!v) return []
  try { const d = JSON.parse(v); return Array.isArray(d) ? d : [] } catch { return [] }
}
function csrf() {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
}
function vstLbl(s: string) {
  return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓' } as any)[s] ?? s
}
function vstIcon(s: string) {
  return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check' } as any)[s] ?? 'ti ti-circle'
}
function vstBadge(s: string) {
  return ({ draft: 'bg-secondary', in_review: 'bg-info text-dark', validated: 'bg-success' } as any)[s] ?? 'bg-secondary'
}
function formatDate(d: string) {
  if (!d) return '—'
  try {
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch { return d }
}

onBeforeUnmount(() => { if (_tt) clearTimeout(_tt) })
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all .2s ease }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(6px) }
</style>