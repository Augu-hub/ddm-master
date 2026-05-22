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
                  {{ form.code || codeAuto }}
                </code>
                <span class="badge" :class="vstBadge(form.validation_status || 'draft')">
                  <i :class="vstIcon(form.validation_status || 'draft')"></i>
                  {{ vstLbl(form.validation_status || 'draft') }}
                </span>
                <span class="badge bg-warning text-dark">{{ badgeLabel }}</span>
                <span v-if="sourceLabel" class="badge"
                      :class="sourceLabel === 'RADO+RCI' ? 'bg-info text-dark' : 'bg-secondary'">
                  <i class="ti ti-link"></i> {{ sourceLabel }}
                </span>
                <span v-if="props.auditorRole" class="badge bg-secondary">{{ props.auditorRole }}</span>
              </div>
              <h6 class="mb-0 fw-bold">{{ titreFormulaire }}</h6>
              <div class="d-flex gap-3 flex-wrap mt-1">
                <small v-if="missionLibelle" class="text-muted">
                  <i class="ti ti-file-description"></i> {{ missionLibelle }}
                </small>
                <small v-if="codeMission" class="text-muted">
                  <i class="ti ti-clipboard"></i> {{ codeMission }}
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
        <div v-if="form.validation_status === 'validated'"
             class="alert alert-success mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
          <i class="ti ti-lock"></i> {{ badgeLabel }} <strong>validé définitivement</strong> — lecture seule
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

      <!-- BARRE ACTIONS -->
      <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
        <div class="alert py-2 px-3 mb-0 small flex-grow-1"
             :class="sourceLabel === 'RADO+RCI' ? 'alert-info' : sourceLabel === 'RCI' ? 'alert-secondary' : 'alert-warning'">
          <i class="ti ti-info-circle"></i>
          <span v-if="sourceLabel === 'RADO+RCI'">
            Objectifs issus du <strong>Rapport d'Orientation (RADO)</strong>.
            Tests liés au <strong>{{ libelleReferentiel }}</strong> (col. H et J).
          </span>
          <span v-else-if="sourceLabel === 'RCI'">
            Objectifs issus du <strong>{{ libelleReferentiel }}</strong> (aucun RADO disponible).
          </span>
          <span v-else>
            <i class="ti ti-alert-triangle text-warning"></i>
            Aucun RADO ni référentiel trouvé. Complétez ces formulaires ou importez un Excel.
          </span>
        </div>
        <a v-if="props.urlModeleExcel" :href="props.urlModeleExcel" class="btn btn-outline-success btn-sm" download>
          <i class="ti ti-file-download"></i> Modèle Excel
        </a>
        <label v-if="!isLocked" class="btn btn-outline-primary btn-sm mb-0">
          <span v-if="uploadLoading" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-file-upload"></i> Importer Excel
          <input type="file" accept=".xlsx,.xls" class="d-none" @change="importerExcel" />
        </label>
      </div>

      <!-- ══════════════════════════════════════════════════════════
           ALERTE VIDE — enrichie avec diagnostic
           ══════════════════════════════════════════════════════════ -->
      <div v-if="!objectifs.length" class="alert alert-warning">
        <i class="ti ti-alert-triangle me-1"></i>
        <strong>Aucun objectif disponible.</strong>
        <ul class="mb-0 mt-1 small">
          <li>
            Vérifiez que le <strong>Rapport d'Orientation (RADO)</strong> contient des axes d'audit —
            il n'est pas nécessaire qu'il soit validé, un brouillon suffit.
          </li>
          <li>
            Si le RADO existe mais est vide (pas d'axes générés par l'IA), ouvrez-le et utilisez
            <em>"Grouper IA"</em> pour créer les objectifs.
          </li>
          <li>
            Source détectée : <strong>{{ props.missionContext?.source || 'none' }}</strong>
            — RADO ID : <strong>{{ props.donneesRCI?.rado_id ?? 'aucun' }}</strong>
            — Lignes RCI : <strong>{{ props.donneesRCI?.total ?? 0 }}</strong>
          </li>
        </ul>
      </div>

      <!-- TABLEAU -->
      <div v-if="objectifs.length" class="card border-0 shadow-sm mb-3">
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle mb-0" style="min-width:1200px">
            <thead>
              <tr>
                <th :colspan="isLocked ? 11 : 12"
                    class="text-center text-white fw-bold py-2"
                    style="background:#0f172a;font-size:.85rem;letter-spacing:.06em">
                  {{ titreFormulaire.toUpperCase() }}
                </th>
              </tr>
              <tr class="table-light">
                <td colspan="6" class="small py-1 px-2">
                  <span class="fw-semibold text-secondary">Mission :</span>
                  <span class="ms-2 fw-bold">{{ missionLibelle || '—' }}</span>
                  <span v-if="codeMission" class="ms-3 text-muted">{{ codeMission }}</span>
                </td>
                <td :colspan="isLocked ? 5 : 6" class="small py-1 px-2 text-end">
                  <span class="fw-semibold text-secondary">Responsable :</span>
                  <span class="ms-2 fw-bold">{{ responsableMission }}</span>
                </td>
              </tr>
              <tr>
                <td :colspan="isLocked ? 11 : 12" class="small py-1 px-2 fst-italic"
                    style="background:#fffbeb;color:#92400e;border-color:#fde68a">
                  📌 <strong>Objectif d'Audit</strong> = texte exact du RADO ·
                  <strong>Test</strong> = col. H du référentiel · <strong>Procédures</strong> = col. J
                </td>
              </tr>
              <tr>
                <th colspan="3" class="text-center text-white small py-1" style="background:#1e40af">Objectif d'Audit</th>
                <th colspan="2" class="text-center text-white small py-1" style="background:#6d28d9">TESTS &amp; PROCÉDURES</th>
                <th colspan="6" class="text-center text-white small py-1" style="background:#065f46">AFFECTATIONS</th>
                <th v-if="!isLocked" class="text-center text-white small py-1" style="background:#374151">+</th>
              </tr>
              <tr>
                <th class="text-center small" style="background:#1e40af;color:#fff;width:65px">Réf.<br/>Obj.</th>
                <th class="small" style="background:#1e40af;color:#fff;min-width:200px">
                  Objectif d'Audit
                  <div style="font-size:.58rem;opacity:.75;font-weight:400">↑ Source : RADO</div>
                </th>
                <th class="text-center small" style="background:#1e40af;color:#fff;width:85px">Réf.<br/>Réf.</th>
                <th class="small" style="background:#6d28d9;color:#fff;min-width:190px">
                  Test d'Audit
                  <div style="font-size:.58rem;opacity:.75;font-weight:400">↑ Référentiel col. H</div>
                </th>
                <th class="small" style="background:#6d28d9;color:#fff;min-width:200px">
                  Procédures d'Audit
                  <div style="font-size:.58rem;opacity:.75;font-weight:400">↑ Référentiel col. J</div>
                </th>
                <th class="text-center small" style="background:#065f46;color:#fff;width:75px">Taille<br/>Éch.</th>
                <th class="text-center small" style="background:#065f46;color:#fff;width:85px">Période<br/>testée</th>
                <th class="small" style="background:#065f46;color:#fff;width:120px">Auditeur</th>
                <th class="text-center small" style="background:#065f46;color:#fff;width:90px">Début/<br/>Fin</th>
                <th class="small" style="background:#065f46;color:#fff;width:110px">Lieu</th>
                <th v-if="!isLocked" class="text-center small" style="background:#374151;color:#fff;width:36px">⚙</th>
              </tr>
            </thead>

            <tbody>
              <template v-for="(obj, oi) in objectifs" :key="obj._uid">

                <!-- Bandeau axe RADO -->
                <tr v-if="obj._axe_rado">
                  <td :colspan="isLocked ? 11 : 12" class="py-1 px-2"
                      style="background:#eff6ff;border-color:#bfdbfe">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-primary" style="font-size:.58rem">
                        <i class="ti ti-compass"></i> AXE RADO
                      </span>
                      <span class="small fw-semibold text-primary">{{ obj._axe_rado }}</span>
                      <span v-if="obj._priorite" class="badge ms-1" style="font-size:.53rem"
                            :class="obj._priorite === 'haute' ? 'bg-danger' : obj._priorite === 'moyenne' ? 'bg-warning text-dark' : 'bg-secondary'">
                        {{ obj._priorite }}
                      </span>
                      <span v-if="obj._indicateurs" class="text-muted small ms-auto fst-italic">
                        <i class="ti ti-chart-bar"></i> {{ obj._indicateurs }}
                      </span>
                    </div>
                  </td>
                </tr>

                <template v-for="(test, ti) in obj.tests" :key="test._tuid">
                  <tr :class="oi % 2 === 0 ? '' : 'table-light'">

                    <td v-if="ti === 0" :rowspan="obj.tests.length" class="align-top text-center px-2 py-2">
                      <span class="badge bg-primary d-block mb-1" style="font-family:monospace;font-size:.78rem">{{ obj.num }}</span>
                      <span class="badge d-block" style="font-size:.52rem"
                            :class="obj._rado_id ? 'bg-info text-dark' : 'bg-secondary'">
                        {{ obj._rado_id ? 'RADO' : 'RÉF.' }}
                      </span>
                    </td>

                    <td v-if="ti === 0" :rowspan="obj.tests.length" class="align-top px-2 py-2">
                      <textarea v-if="!isLocked"
                                class="form-control form-control-sm mb-2"
                                v-model="obj.objectif" rows="4"
                                @change="obj._edited = true"
                                placeholder="S'assurer que…"
                                style="font-size:.73rem;resize:vertical"></textarea>
                      <p v-else class="mb-0 small" style="white-space:pre-wrap">{{ obj.objectif || '—' }}</p>
                      <button v-if="!isLocked"
                              class="btn btn-outline-primary btn-sm w-100 mt-1"
                              style="font-size:.6rem;padding:2px 6px"
                              @click="ajouterTest(obj)">
                        <i class="ti ti-plus"></i> Ajouter un test
                      </button>
                    </td>

                    <td v-if="ti === 0" :rowspan="obj.tests.length" class="align-top text-center px-2 py-2">
                      <span v-if="obj.ref_rci" class="badge"
                            style="background:#ede9fe;color:#7c3aed;font-family:monospace;font-size:.65rem">
                        {{ obj.ref_rci }}
                      </span>
                      <span v-else class="text-muted small">—</span>
                    </td>

                    <!-- Test d'Audit -->
                    <td class="align-top px-2 py-2">
                      <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge" style="background:#ede9fe;color:#7c3aed;font-family:monospace;font-size:.6rem">
                          {{ test.ref }}
                        </span>
                        <div v-if="!isLocked" class="ms-auto d-flex gap-1">
                          <button v-if="obj.tests.length > 1"
                                  class="btn btn-sm btn-outline-danger"
                                  style="font-size:.58rem;padding:2px 5px"
                                  @click="supprimerTest(obj, ti)">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </div>
                      <textarea v-if="!isLocked"
                                class="form-control form-control-sm"
                                v-model="test.libelle" rows="3"
                                @change="test._edited = true"
                                placeholder="Vérification de…"
                                style="font-size:.72rem;resize:vertical"></textarea>
                      <p v-else class="mb-0 small" style="white-space:pre-wrap">{{ test.libelle || '—' }}</p>
                    </td>

                    <!-- Procédures -->
                    <td class="align-top px-2 py-2">
                      <div v-if="!isLocked" class="d-flex flex-column gap-1">
                        <div v-for="(proc, pi) in test.procedures" :key="pi" class="d-flex align-items-start gap-1">
                          <span class="text-muted fw-bold" style="font-size:.6rem;min-width:14px;margin-top:5px">{{ pi + 1 }}.</span>
                          <textarea class="form-control form-control-sm flex-grow-1"
                                    v-model="test.procedures[pi]" rows="2"
                                    @change="test._edited = true"
                                    :placeholder="`Étape ${pi + 1}…`"
                                    style="font-size:.68rem;resize:vertical"></textarea>
                          <button class="btn btn-sm btn-outline-secondary"
                                  style="font-size:.55rem;padding:2px 4px;margin-top:2px"
                                  @click="supprimerProcedure(test, pi)">
                            <i class="ti ti-x"></i>
                          </button>
                        </div>
                        <button class="btn btn-outline-success btn-sm" style="font-size:.6rem" @click="ajouterProcedure(test)">
                          <i class="ti ti-plus"></i> Étape
                        </button>
                      </div>
                      <div v-else class="d-flex flex-column gap-1">
                        <div v-for="(proc, pi) in test.procedures" :key="pi" class="d-flex gap-1 small">
                          <span class="text-muted fw-bold" style="min-width:14px">{{ pi + 1 }}.</span>
                          <span>{{ proc }}</span>
                        </div>
                        <span v-if="!test.procedures?.length" class="text-muted fst-italic small">—</span>
                      </div>
                    </td>

                    <!-- Taille Éch. -->
                    <td class="text-center align-middle px-1 py-1">
                      <input v-if="!isLocked" type="text" class="form-control form-control-sm text-center"
                             v-model="test.taille_echantillon" @change="test._edited = true"
                             placeholder="30" style="font-size:.7rem" />
                      <span v-else class="small">{{ test.taille_echantillon || '—' }}</span>
                    </td>

                    <!-- Période testée -->
                    <td class="text-center align-middle px-1 py-1">
                      <input v-if="!isLocked" type="text" class="form-control form-control-sm text-center"
                             v-model="test.periode_testee" @change="test._edited = true"
                             placeholder="Période testée" style="font-size:.7rem" />
                      <span v-else class="small">{{ test.periode_testee || '—' }}</span>
                    </td>

                    <!-- Auditeur -->
                    <td class="align-middle px-1 py-1">
                      <select v-if="!isLocked && auditeurOptions.length"
                              class="form-select form-select-sm" v-model="test.auditeur"
                              @change="test._edited = true" style="font-size:.7rem">
                        <option value="">—</option>
                        <option v-for="a in auditeurOptions" :key="a.id" :value="a.full_name">
                          {{ a.full_name }} ({{ a.role_code }})
                        </option>
                      </select>
                      <input v-else-if="!isLocked" type="text" class="form-control form-control-sm"
                             v-model="test.auditeur" @change="test._edited = true" style="font-size:.7rem" />
                      <span v-else class="small">{{ test.auditeur || '—' }}</span>
                    </td>

                    <!-- Date Début/Fin -->
                    <td class="text-center align-middle px-1 py-1">
                      <input v-if="!isLocked" type="date" class="form-control form-control-sm"
                             v-model="test.date_debut" @change="test._edited = true" style="font-size:.68rem" />
                      <span v-else class="small">{{ formatDate(test.date_debut) }}</span>
                      <br>
                      <input v-if="!isLocked" type="date" class="form-control form-control-sm"
                             v-model="test.date_fin" @change="test._edited = true" style="font-size:.68rem" />
                      <span v-else class="small">{{ formatDate(test.date_fin) }}</span>
                    </td>

                    <!-- Lieu -->
                    <td class="align-middle px-1 py-1">
                      <input v-if="!isLocked" type="text" class="form-control form-control-sm"
                             v-model="test.lieu" @change="test._edited = true"
                             placeholder="Direction…" style="font-size:.7rem" />
                      <span v-else class="small">{{ test.lieu || '—' }}</span>
                    </td>

                    <td v-if="!isLocked" class="align-middle px-1 py-1"></td>
                  </tr>
                </template>

                <!-- Séparateur -->
                <tr>
                  <td :colspan="isLocked ? 11 : 12" class="p-0" style="background:#cbd5e1;height:3px;border:none"></td>
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
            <span class="badge bg-warning text-dark">{{ objectifs.length }} objectif(s) · {{ totalTests }} test(s)</span>
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
                <i :class="toast.type === 'success' ? 'ti ti-circle-check' : toast.type === 'info' ? 'ti ti-info-circle' : 'ti ti-alert-circle'"></i>
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
import { ref, computed, reactive, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ══════════════════════════════════════════════════════════════════
// Props — identiques à ProgCi, utilisables pour les 3 programmes
// ══════════════════════════════════════════════════════════════════
const props = withDefaults(defineProps<{
  // Méta du programme — passé depuis le contrôleur
  programmeType?:    'conformite' | 'marches' | 'transactions' | 'ci'  // pour labels dynamiques
  mission?:          any
  auditorRole?:      string
  missionId?:        number
  assignmentId?:     number
  form?:             any
  phaseAuditeurs?:   any[]
  donneesRCI?:       { lignes?: any[]; total?: number; source?: string; processus?: string; rado_id?: number | null }
  missionContext?:   {
    mission_id?:      number
    assignment_id?:   number
    mission_libelle?: string
    code_mission?:    string
    processus?:       string
    source?:          string
  }
  backUrl?:          string
  urlStore?:         string
  urlUpdate?:        string
  urlSoumettre?:     string
  urlValider?:       string
  urlUpload?:        string
  urlBase?:          string
  urlModeleExcel?:   string
  urlIndex?:         string
}>(), {
  programmeType:  'conformite',
  phaseAuditeurs: () => [],
  donneesRCI:     () => ({ lignes: [], total: 0 }),
  missionContext: () => ({}),
})

let _uid = 0; const uid  = () => String(++_uid)
let _tid = 0; const tuid = () => String(++_tid)

// Labels dynamiques selon le type
const LABELS: Record<string, { titre: string; badge: string; code: string; referentiel: string }> = {
  ci:           { titre: 'Programme de Travail d\'Audit — Contrôle Interne',            badge: 'Programme CI',           code: 'PTCI-AUTO',    referentiel: 'Référentiel CI' },
  conformite:   { titre: 'Programme de Travail d\'Audit — Conformité',                  badge: 'Programme Conformité',   code: 'PTCONF-AUTO',  referentiel: 'Référentiel Conformité' },
  marches:      { titre: 'Programme de Travail d\'Audit — Marchés & Transactions',      badge: 'Programme Marchés',      code: 'PTMAR-AUTO',   referentiel: 'Référentiel Marchés (AMQ)' },
  transactions: { titre: 'Programme de Travail d\'Audit — Transactions Financières',    badge: 'Programme Transactions', code: 'PTTRANS-AUTO', referentiel: 'Référentiel Transactions (AR)' },
}
const currentLabels  = computed(() => LABELS[props.programmeType ?? 'conformite'] ?? LABELS.conformite)
const titreFormulaire = computed(() => currentLabels.value.titre)
const badgeLabel      = computed(() => currentLabels.value.badge)
const codeAuto        = computed(() => currentLabels.value.code)
const libelleReferentiel = computed(() => currentLabels.value.referentiel)

// ══════════════════════════════════════════════════════════════════
// Form réactif
// ══════════════════════════════════════════════════════════════════
const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  ...(props.form ?? {}),
})

const dynUrls = reactive({
  upload:    props.urlUpload    ?? null as string | null,
  update:    props.urlUpdate    ?? null as string | null,
  soumettre: props.urlSoumettre ?? null as string | null,
  valider:   props.urlValider   ?? null as string | null,
})

const objectifs     = reactive<any[]>([])
const processing    = ref(false)
const uploadLoading = ref(false)
const toast = ref({ show: false, type: 'success', msg: '' })
let _tt: any

// ══════════════════════════════════════════════════════════════════
// initObjectifs — CORRIGÉE
//
// Règle principale : si le contrôleur a fourni donneesRCI.lignes
// (calculées depuis RADO+référentiel), on les utilise TOUJOURS
// sauf si l'auditeur a déjà édité ET que le RADO n'a pas changé de source.
//
// En pratique :
//   - source = 'rado+rci' OU rado_id présent → donneesRCI en priorité
//   - source = 'rci' ET form.lignes non vide ET form.lignes contient des _rado_id → form.lignes
//   - Sinon → donneesRCI (fraîchement calculé par le contrôleur)
// ══════════════════════════════════════════════════════════════════
function initObjectifs() {
  objectifs.splice(0)

  const fromRCI  = safeArr(props.donneesRCI?.lignes)
  const fromForm = safeArr(props.form?.lignes)
  const srcRCI   = props.donneesRCI?.source ?? props.missionContext?.source ?? 'none'
  const hasRado  = srcRCI === 'rado+rci' || (props.donneesRCI?.rado_id != null)

  // Vérifier si les lignes sauvegardées en base viennent déjà du RADO
  const formLignesHaveRado = fromForm.length > 0 && fromForm.some((l: any) => l._rado_id != null)

  let source: any[]

  if (hasRado && fromRCI.length) {
    // RADO disponible → TOUJOURS utiliser les données fraîches calculées par le contrôleur
    // Même si form.lignes existe : le contrôleur a déjà géré le merge côté PHP
    source = fromRCI
  } else if (!hasRado && formLignesHaveRado && fromForm.length) {
    // Pas de RADO détecté mais les lignes sauvegardées viennent d'un ancien RADO → les garder
    source = fromForm
  } else if (!hasRado && fromForm.length && fromRCI.length === 0) {
    // Pas de RADO, pas de données fraîches → utiliser ce qui est sauvegardé
    source = fromForm
  } else if (fromRCI.length) {
    // Données fraîches disponibles → les utiliser
    source = fromRCI
  } else {
    source = fromForm
  }

  source.forEach((o: any) => objectifs.push(hydrateObj(o)))
}
initObjectifs()

function hydrateObj(o: any) {
  return { ...o, _uid: uid(), _edited: false, tests: (o.tests ?? []).map((t: any) => hydrateTest(t)) }
}
function hydrateTest(t: any) {
  return {
    ...t, _tuid: tuid(), _edited: false,
    procedures: Array.isArray(t.procedures) ? [...t.procedures] : [],
  }
}

// ══════════════════════════════════════════════════════════════════
// Computed
// ══════════════════════════════════════════════════════════════════
const canManage  = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked   = computed(() => form.validation_status === 'validated' || (form.validation_status === 'in_review' && !canManage.value))
const totalTests = computed(() => objectifs.reduce((s, o) => s + (o.tests?.length ?? 0), 0))
const auditeurOptions    = computed(() => props.phaseAuditeurs ?? [])
const missionLibelle     = computed(() => props.mission?.libelle ?? props.missionContext?.mission_libelle ?? props.form?.mission?.libelle ?? '')
const codeMission        = computed(() => props.mission?.code_mission ?? props.missionContext?.code_mission ?? '')
const responsableMission = computed(() => {
  const dm = (props.phaseAuditeurs ?? []).find(a => a.role_code === 'DM' || a.role_code === 'CM')
  return dm?.full_name ?? '—'
})
const sourceLabel = computed(() => {
  const src = props.missionContext?.source ?? props.donneesRCI?.source ?? ''
  if (src === 'rado+rci') return 'RADO+RCI'
  if (src === 'rci') return 'RCI'
  if (objectifs.some(o => o._rado_id)) return 'RADO+RCI'
  if (objectifs.length) return 'RCI'
  return ''
})

// ══════════════════════════════════════════════════════════════════
// Mutations
// ══════════════════════════════════════════════════════════════════
function ajouterTest(obj: any) {
  const nb = obj.tests.length; const L = ['a','b','c','d','e','f']
  if (nb === 1) obj.tests[0].ref = 'T_'+obj.num+'_a'
  obj.tests.push(hydrateTest({ ref: 'T_'+obj.num+'_'+(L[nb] ?? (nb+1)), libelle: '', procedures: [] }))
  obj._edited = true
}
function supprimerTest(obj: any, idx: number) {
  if (obj.tests.length <= 1) { showToast('error', 'Au moins un test requis.'); return }
  obj.tests.splice(idx, 1)
  if (obj.tests.length === 1) obj.tests[0].ref = 'T_'+obj.num
  obj._edited = true
}
function ajouterProcedure(test: any)                { test.procedures.push(''); test._edited = true }
function supprimerProcedure(test: any, idx: number)  { test.procedures.splice(idx, 1); test._edited = true }

// ══════════════════════════════════════════════════════════════════
// Import Excel
// ══════════════════════════════════════════════════════════════════
async function importerExcel(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  const url  = dynUrls.upload ?? props.urlUpload
  if (!file || !url) { showToast('error', 'URL d\'import non disponible.'); return }
  uploadLoading.value = true
  try {
    const fd = new FormData(); fd.append('file', file); fd.append('_token', csrf())
    const res = await fetch(url, { method: 'POST', body: fd })
    const d   = await res.json()
    if (d.success && d.lignes?.length) {
      objectifs.splice(0, objectifs.length, ...d.lignes.map((o: any) => hydrateObj(o)))
      showToast('success', d.message ?? `${d.total} objectif(s) importé(s)`)
    } else showToast('error', d.error ?? 'Erreur lors de l\'import.')
  } catch { showToast('error', 'Erreur réseau.') }
  finally { uploadLoading.value = false; (event.target as HTMLInputElement).value = '' }
}

// ══════════════════════════════════════════════════════════════════
// Sérialisation
// ══════════════════════════════════════════════════════════════════
function serialize() {
  return objectifs.map(o => ({
    num: o.num, objectif: o.objectif, ref_rci: o.ref_rci,
    tests: (o.tests ?? []).map((t: any) => ({
      ref: t.ref, libelle: t.libelle, procedures: t.procedures ?? [],
      auditeur: t.auditeur ?? '', date_debut: t.date_debut ?? '', date_fin: t.date_fin ?? '',
      lieu: t.lieu ?? '', taille_echantillon: t.taille_echantillon ?? '', periode_testee: t.periode_testee ?? '',
    })),
    _source: o._source ?? null, _rado_id: o._rado_id ?? null, _rci_id: o._rci_id ?? null,
    _axe_rado: o._axe_rado ?? null, _priorite: o._priorite ?? null, _indicateurs: o._indicateurs ?? null,
    _criteres_eval: o._criteres_eval ?? null, _risque_code: o._risque_code ?? null,
    _risque_libelle: o._risque_libelle ?? null, _process_name: o._process_name ?? null,
    _objectif_operationnel: o._objectif_operationnel ?? null, _description_controle: o._description_controle ?? null,
    _preuve_controle: o._preuve_controle ?? null, _type_controle: o._type_controle ?? null,
    _criticite: o._criticite ?? null, _responsable: o._responsable ?? null,
  }))
}

// ══════════════════════════════════════════════════════════════════
// Submit / workflow
// ══════════════════════════════════════════════════════════════════
async function submit(silent = false) {
  processing.value = !silent
  try {
    const url    = form.id ? (dynUrls.update ?? props.urlUpdate) : props.urlStore
    const method = form.id ? 'PUT' : 'POST'
    if (!url) { if (!silent) showToast('error', 'URL de sauvegarde indisponible.'); return }
    const res = await fetch(url, {
      method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        mission_id:    props.missionId    ?? props.missionContext?.mission_id,
        assignment_id: props.assignmentId ?? props.missionContext?.assignment_id,
        lignes:        JSON.stringify(serialize()),
      }),
    })
    const d = await res.json()
    if (d.success || res.ok) {
      if (!silent) showToast('success', form.id ? `${badgeLabel.value} mis à jour.` : `${badgeLabel.value} créé.`)
      if (d.form) Object.assign(form, { id: d.form.id, code: d.form.code, validation_status: d.form.validation_status })
      if (d.urlUpdate)    dynUrls.update    = d.urlUpdate
      if (d.urlSoumettre) dynUrls.soumettre = d.urlSoumettre
      if (d.urlValider)   dynUrls.valider   = d.urlValider
      if (d.urlUpload)    dynUrls.upload    = d.urlUpload
    } else { if (!silent) showToast('error', d.message ?? 'Erreur lors de l\'enregistrement.') }
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
    if (d.success) { form.validation_status = d.status; showToast('success', action === 'validate' ? `${badgeLabel.value} validé ✓` : 'Rejeté.') }
    else showToast('error', d.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  processing.value = false
}

function promptReject() {
  const n = prompt('Motif du rejet (obligatoire) :', '')
  if (!n?.trim()) return
  valider('reject', n.trim())
}

// ══════════════════════════════════════════════════════════════════
// Utils
// ══════════════════════════════════════════════════════════════════
function showToast(t: string, m: string, dur = 4500) {
  if (_tt) clearTimeout(_tt)
  toast.value = { show: true, type: t, msg: m }
  _tt = setTimeout(() => { toast.value.show = false }, dur)
}
function safeArr(v: any): any[] {
  if (Array.isArray(v)) return [...v]
  if (!v) return []
  try { const d = JSON.parse(v); return Array.isArray(d) ? d : [] } catch { return [] }
}
function csrf() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
function vstLbl(s: string)  { return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓' } as any)[s] ?? s }
function vstIcon(s: string) { return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check' } as any)[s] ?? 'ti ti-circle' }
function vstBadge(s: string){ return ({ draft: 'bg-secondary', in_review: 'bg-info text-dark', validated: 'bg-success' } as any)[s] ?? 'bg-secondary' }
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