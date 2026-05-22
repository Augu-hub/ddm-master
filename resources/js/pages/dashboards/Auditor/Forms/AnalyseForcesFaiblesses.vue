<template>
  <VerticalLayoutAudit>
    <div class="aff-shell">

      <!-- ═══ HEADER ═══ -->
      <header class="aff-header">
        <div class="aff-hrow">
          <a :href="props.backUrl" class="aff-back">
            <i class="ti ti-arrow-left"></i>
          </a>
          <div class="aff-hinfo">
            <div class="aff-chips">
              <code class="aff-code">{{ form.code || 'AFF-AUTO' }}</code>
              <span class="aff-chip" :class="`chip-${form.validation_status || 'draft'}`">
                <i :class="statusIcon(form.validation_status || 'draft')"></i>
                {{ statusLabel(form.validation_status || 'draft') }}
              </span>
              <span class="aff-chip chip-type">TFFA</span>
              <span v-if="props.auditorRole" class="aff-chip" :class="`chip-role-${props.auditorRole}`">
                {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="aff-title">Tableau des Forces et Faiblesses Apparentes</h1>
            <div class="aff-meta">
              <span v-if="props.mission?.code"><i class="ti ti-clipboard"></i>{{ props.mission.code }}</span>
              <span v-if="props.mission?.entity_name"><i class="ti ti-building"></i>{{ props.mission.entity_name }}</span>
              <span v-if="props.assignment?.phase_label"><i class="ti ti-layers"></i>{{ props.assignment.phase_label }}</span>
              <span class="cnt-f"><i class="ti ti-arrow-up-circle"></i>{{ forces.length }} force(s)</span>
              <span class="cnt-w"><i class="ti ti-arrow-down-circle"></i>{{ faiblesses.length }} faiblesse(s)</span>
            </div>
          </div>
        </div>

        <!-- Bandeaux statut -->
        <div v-if="form.validation_status === 'validated'" class="aff-banner banner-lock">
          <i class="ti ti-lock"></i>
          Formulaire <strong>validé définitivement</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status === 'in_review'" class="aff-banner banner-review">
          <i class="ti ti-clock"></i>
          Soumis pour validation
          <span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status === 'draft' && form.validation_note" class="aff-banner banner-reject">
          <i class="ti ti-circle-x"></i>
          Rejeté — <em>{{ form.validation_note }}</em>
        </div>
      </header>

      <div class="aff-body">

        <!-- ══ EN-TÊTE TFFA ══ -->
        <div class="tffa-entete">
          <div class="eb eb-mission">
            <div class="eb-titre">Mission</div>
            <table class="eb-tbl">
              <tr>
                <td class="eb-lbl">Mission :</td>
                <td class="eb-val">{{ props.mission?.libelle || props.mission?.title || '—' }}</td>
                <td class="eb-lbl">Phase :</td>
                <td class="eb-val">{{ props.assignment?.phase_label || '—' }}</td>
              </tr>
              <tr>
                <td class="eb-lbl">Code TFFA :</td>
                <td class="eb-val">
                  <input class="eb-inp eb-ro" :value="form.code || 'AFF-AUTO'" readonly />
                </td>
                <td class="eb-lbl">Domaine :</td>
                <td class="eb-val">
                  <input class="eb-inp" v-model="synthese.domaine" :disabled="isLocked" placeholder="ex: Achats, RH…" />
                </td>
              </tr>
            </table>
          </div>

          
        </div>

        <!-- ══ BANDEAU DONNÉES BD ══ -->
        <div v-if="hasDonneesDB" class="db-banner">
          <i class="ti ti-database"></i>
          <span v-if="wasAutoImported">
            <i class="ti ti-circle-check" style="color:#059669"></i>
            Données auto-importées depuis les analyses :
            <strong class="cnt-f">{{ props.donneesDB?.forces?.length || 0 }} force(s)</strong> et
            <strong class="cnt-w">{{ props.donneesDB?.faiblesses?.length || 0 }} faiblesse(s)</strong>
            <template v-if="sourcesBadges"> — Sources : {{ sourcesBadges }}</template>
          </span>
          <span v-else>
            Données disponibles depuis les analyses :
            <strong class="cnt-f">{{ nbForcesNonImportees }} force(s) non importée(s)</strong> et
            <strong class="cnt-w">{{ nbFaiblessesNonImportees }} faiblesse(s) non importée(s)</strong>
            <template v-if="sourcesBadges"> — Sources : {{ sourcesBadges }}</template>
          </span>
          <div v-if="!isLocked && !wasAutoImported" class="db-actions">
            <button class="btn btn-db btn-sm" @click="showDBPanel = !showDBPanel">
              <i class="ti ti-table-import"></i>
              {{ showDBPanel ? 'Masquer' : 'Voir et importer' }}
            </button>
            <button class="btn btn-save btn-sm" @click="importAll">
              <i class="ti ti-download"></i> Tout importer
            </button>
          </div>
          <div v-if="!isLocked && wasAutoImported" class="db-actions">
            <button class="btn btn-db btn-sm" @click="showDBPanel = !showDBPanel">
              <i class="ti ti-eye"></i>
              {{ showDBPanel ? 'Masquer' : 'Voir le détail' }}
            </button>
          </div>
        </div>

        <!-- ══ PANNEAU IMPORT BD ══ -->
        <div v-if="showDBPanel && !isLocked" class="db-panel">
          <div class="db-panel-hdr">
            <span><i class="ti ti-database"></i> Données issues des analyses (AR / APT / ACONF / AMQ)</span>
            <button class="btn btn-ghost btn-xs" @click="showDBPanel = false">
              <i class="ti ti-x"></i>
            </button>
          </div>
          <div class="db-cols">
            <!-- Forces BD -->
            <div class="db-col">
              <div class="db-col-hdr db-f">
                <i class="ti ti-arrow-up-circle"></i>
                Forces — {{ props.donneesDB?.forces?.length || 0 }}
                <button class="btn btn-xs btn-force ml-auto" @click="importAllForces">Tout importer</button>
              </div>
              <div v-if="!props.donneesDB?.forces?.length" class="db-empty">Aucune force trouvée</div>
              <div
                v-for="(item, idx) in (props.donneesDB?.forces || [])"
                :key="idx"
                class="db-item db-item--f"
              >
                <div class="db-item-body">
                  <div class="db-dom">{{ domaineLabel(item.domaine) }}</div>
                  <div class="db-src">{{ item._source }}</div>
                  <div class="db-lib">{{ item.libelle }}</div>
                  <div v-if="item.processus_concerne" class="db-proc">{{ item.processus_concerne }}</div>
                </div>
                <button
                  class="btn btn-xs db-add"
                  :class="isImported(forces, item) ? 'btn-imported btn-force' : 'btn-force'"
                  @click="importForce(item)"
                >
                  <i class="ti" :class="isImported(forces, item) ? 'ti-check' : 'ti-plus'"></i>
                </button>
              </div>
            </div>

            <!-- Faiblesses BD -->
            <div class="db-col">
              <div class="db-col-hdr db-w">
                <i class="ti ti-arrow-down-circle"></i>
                Faiblesses — {{ props.donneesDB?.faiblesses?.length || 0 }}
                <button class="btn btn-xs btn-faib ml-auto" @click="importAllFaiblesses">Tout importer</button>
              </div>
              <div v-if="!props.donneesDB?.faiblesses?.length" class="db-empty">Aucune faiblesse trouvée</div>
              <div
                v-for="(item, idx) in (props.donneesDB?.faiblesses || [])"
                :key="idx"
                class="db-item db-item--w"
              >
                <div class="db-item-body">
                  <div class="db-dom">{{ domaineLabel(item.domaine) }}</div>
                  <div class="db-src">{{ item._source }}</div>
                  <div class="db-lib">{{ item.libelle }}</div>
                  <div v-if="item.processus_concerne" class="db-proc">{{ item.processus_concerne }}</div>
                  <div v-if="item.fonctions" class="db-proc">Fonctions : {{ item.fonctions }}</div>
                  <div v-if="item.objectif_controle" class="db-proc">Obj. : {{ item.objectif_controle }}</div>
                </div>
                <button
                  class="btn btn-xs db-add"
                  :class="isImported(faiblesses, item) ? 'btn-imported btn-faib' : 'btn-faib'"
                  @click="importFaiblesse(item)"
                >
                  <i class="ti" :class="isImported(faiblesses, item) ? 'ti-check' : 'ti-plus'"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ══ TFFA PRINCIPAL ══ -->
        <div class="tffa-main">

          <!-- ─── FORCES ─── -->
          <div class="tffa-side">
            <div class="side-title side-title--f">
              Forces
              <span class="side-total">{{ forces.length }}</span>
            </div>

            <div class="col-hdr col-hdr--f">
              <div class="ch ch-flag">F</div>
              <div class="ch ch-num">N°</div>
              <div class="ch ch-lib">Libellé / Point fort</div>
              <div class="ch ch-act"></div>
            </div>

            <div v-if="!isLocked" class="side-tb">
              <select class="sb-sel" v-model="newForceDom">
                <option value="">— Sélectionner un domaine —</option>
                <option v-for="(lbl, k) in domainesList" :key="k" :value="k">{{ lbl }}</option>
              </select>
              <button class="btn btn-force btn-xs" :disabled="!newForceDom" @click="addForce">
                <i class="ti ti-plus"></i> Ajouter
              </button>
            </div>

            <div class="tbl-body">
              <template v-for="(domLbl, domKey) in domainesAvecForces" :key="domKey">
                <div class="sec-row sec-row--f">
                  <span class="sec-arrow">▸</span>
                  <span class="sec-lbl">{{ domLbl }}</span>
                  <span class="sec-count">{{ (forcesByDomain[domKey] || []).length }}</span>
                </div>
                <template v-for="(item, li) in (forcesByDomain[domKey] || [])" :key="item._uid">
                  <div class="dat-row dat-row--f" :class="li % 2 === 0 ? 'row-even' : 'row-odd'">
                    <div class="dc dc-flag"><span class="flag-f">F</span></div>
                    <div class="dc dc-num">{{ item._num }}</div>
                    <div class="dc dc-lib">
                      <textarea
                        v-if="!isLocked"
                        class="c-ta"
                        v-model="item.libelle"
                        rows="2"
                        placeholder="Libellé force / point fort…"
                      ></textarea>
                      <span v-else class="c-ro">{{ item.libelle || '—' }}</span>
                      <div class="sub-proc">
                        <input
                          v-if="!isLocked"
                          class="c-sub-inp"
                          v-model="item.processus_concerne"
                          placeholder="Processus concerné…"
                        />
                        <span v-else-if="item.processus_concerne" class="c-sub-ro">{{ item.processus_concerne }}</span>
                      </div>
                      <span v-if="item._source" class="src-badge">{{ item._source }}</span>
                    </div>
                    <div class="dc dc-act">
                      <button v-if="!isLocked" class="c-del" @click="removeForce(item)" title="Supprimer">×</button>
                    </div>
                  </div>
                </template>
              </template>
              <div v-if="!forces.length" class="dat-row dat-empty">
                <div class="dc dc-flag"></div>
                <div class="dc dc-num">—</div>
                <div class="dc dc-lib empty-txt">Aucune force enregistrée</div>
                <div class="dc dc-act"></div>
              </div>

              <div class="total-row">
                <div class="dc dc-flag"></div>
                <div class="dc dc-num fw">{{ forces.length }}</div>
                <div class="dc dc-lib"><strong>TOTAL FORCES</strong></div>
                <div class="dc dc-act"></div>
              </div>
            </div>
          </div>

          <!-- ─── FAIBLESSES ─── -->
          <div class="tffa-side">
            <div class="side-title side-title--w">
              Faiblesses
              <span class="side-total">{{ faiblesses.length }}</span>
            </div>

            <div class="col-hdr col-hdr--w">
              <div class="ch ch-flag">F</div>
              <div class="ch ch-num">N°</div>
              <div class="ch ch-proc">Processus</div>
              <div class="ch ch-svc">Fonctions</div>
              <div class="ch ch-lib-w">Libellé / Risque</div>
              <div class="ch ch-obj">Objectif d'audit</div>
              <div class="ch ch-act"></div>
            </div>

            <div v-if="!isLocked" class="side-tb">
              <select class="sb-sel" v-model="newFaibDom">
                <option value="">— Sélectionner un domaine —</option>
                <option v-for="(lbl, k) in domainesList" :key="k" :value="k">{{ lbl }}</option>
              </select>
              <button class="btn btn-faib btn-xs" :disabled="!newFaibDom" @click="addFaiblesse">
                <i class="ti ti-plus"></i> Ajouter
              </button>
            </div>

            <div class="tbl-body">
              <template v-for="(domLbl, domKey) in domainesAvecFaiblesses" :key="domKey">
                <div class="sec-row sec-row--w">
                  <span class="sec-arrow">▸</span>
                  <span class="sec-lbl">{{ domLbl }}</span>
                  <span class="sec-count">{{ (faiblessesByDomain[domKey] || []).length }}</span>
                </div>
                <template v-for="(item, li) in (faiblessesByDomain[domKey] || [])" :key="item._uid">
                  <div class="dat-row dat-row--w" :class="li % 2 === 0 ? 'row-even' : 'row-odd'">
                    <div class="dc dc-flag"><span class="flag-w">F</span></div>
                    <div class="dc dc-num">{{ item._num }}</div>
                    <div class="dc dc-proc">
                      <input v-if="!isLocked" class="c-inp" v-model="item.processus_concerne" placeholder="Processus…" />
                      <span v-else class="c-ro c-ro-sm">{{ item.processus_concerne || '—' }}</span>
                    </div>
                    <div class="dc dc-svc">
                      <input v-if="!isLocked" class="c-inp" v-model="item.fonctions" placeholder="Fonctions…" />
                      <span v-else class="c-ro c-ro-sm">{{ item.fonctions || '—' }}</span>
                    </div>
                    <div class="dc dc-lib-w">
                      <textarea
                        v-if="!isLocked"
                        class="c-ta"
                        v-model="item.libelle"
                        rows="3"
                        placeholder="Libellé faiblesse / risque…"
                      ></textarea>
                      <span v-else class="c-ro">{{ item.libelle || '—' }}</span>
                      <span v-if="item._source" class="src-badge">{{ item._source }}</span>
                    </div>
                    <div class="dc dc-obj">
                      <textarea
                        v-if="!isLocked"
                        class="c-ta"
                        v-model="item.objectif_controle"
                        rows="3"
                        placeholder="Objectif de contrôle…"
                      ></textarea>
                      <span v-else class="c-ro">{{ item.objectif_controle || '—' }}</span>
                    </div>
                    <div class="dc dc-act">
                      <button v-if="!isLocked" class="c-del" @click="removeFaiblesse(item)" title="Supprimer">×</button>
                    </div>
                  </div>
                </template>
              </template>
              <div v-if="!faiblesses.length" class="dat-row dat-empty">
                <div class="dc dc-flag"></div>
                <div class="dc dc-num">—</div>
                <div class="dc dc-proc"></div>
                <div class="dc dc-svc"></div>
                <div class="dc dc-lib-w empty-txt">Aucune faiblesse enregistrée</div>
                <div class="dc dc-obj"></div>
                <div class="dc dc-act"></div>
              </div>

              <div class="total-row">
                <div class="dc dc-flag"></div>
                <div class="dc dc-num fw">{{ faiblesses.length }}</div>
                <div class="dc dc-proc"></div>
                <div class="dc dc-svc"></div>
                <div class="dc dc-lib-w"><strong>TOTAL FAIBLESSES</strong></div>
                <div class="dc dc-obj"></div>
                <div class="dc dc-act"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ══ BARRE DE SYNTHÈSE ══ -->
        <div class="synth-bar">
          <div class="sv" :class="forces.length >= faiblesses.length ? 'sv-fav' : 'sv-risk'">
            {{ forces.length >= faiblesses.length
              ? '✓ Profil favorable — les forces dominent'
              : '⚠ Profil à risque — les faiblesses dominent' }}
          </div>
          <div class="synth-stats">
            <template v-for="(lbl, k) in domainesList" :key="k">
              <span class="ss">
                <span class="ss-dom">{{ lbl }}</span>
                <span class="ss-f">{{ (forcesByDomain[k] || []).length }}F</span>
                <span class="ss-sep">/</span>
                <span class="ss-w">{{ (faiblessesByDomain[k] || []).length }}W</span>
              </span>
            </template>
          </div>
        </div>

        <!-- ══ FOOTER ══ -->
        <footer class="aff-footer">
          <div>
            <button
              v-if="!isLocked"
              type="button"
              class="btn btn-ghost btn-sm"
              :disabled="processing"
              @click="annuler"
            >
              <i class="ti ti-x"></i> Annuler
            </button>
            <button
              v-if="!isLocked"
              type="button"
              class="btn btn-save btn-sm"
              :disabled="processing"
              @click="submit"
            >
              <span v-if="processing" class="spin-s"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>

          <div class="footer-mid">
            <span v-if="form.id" class="saved-code">
              <i class="ti ti-check"></i> {{ form.code }}
            </span>
          </div>

          <div>
            <button
              v-if="form.id && form.validation_status === 'draft'"
              type="button"
              class="btn btn-sub btn-sm"
              :disabled="processing"
              @click="soumettre"
            >
              <i class="ti ti-send"></i> Soumettre
            </button>
            <template v-if="canManage && form.validation_status === 'in_review'">
              <button
                type="button"
                class="btn btn-ok btn-sm"
                :disabled="processing"
                @click="valider('validate')"
              >
                <i class="ti ti-circle-check"></i> Valider
              </button>
              <button
                type="button"
                class="btn btn-rej btn-sm"
                :disabled="processing"
                @click="promptReject"
              >
                <i class="ti ti-circle-x"></i> Rejeter
              </button>
            </template>
          </div>
        </footer>
      </div>
    </div>

    <!-- Toast -->
    <Teleport to="body">
      <Transition name="toast-t">
        <div v-if="toast.show" class="aff-toast" :class="`toast--${toast.type}`">
          <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
          {{ toast.msg }}
        </div>
      </Transition>
    </Teleport>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onBeforeUnmount, toRaw } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ── Types ──────────────────────────────────────────────────────────────
interface AffItem {
  _uid: string
  _num?: number
  _source?: string
  _risk_score?: number
  domaine: string
  libelle: string
  processus_concerne: string
}

interface ForceItem extends AffItem {}
interface FaiblesseItem extends AffItem {
  fonctions: string
  objectif_controle: string
}

// ── Props ──────────────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  mission?:       any
  assignment?:    any
  auditorRole?:   string
  missionId?:     number
  assignmentId?:  number
  form?:          any
  affList?:       any[]
  currentAuditor?: any
  phaseAuditeurs?: any[]
  domaines?:      Record<string, string>
  donneesDB?:     { forces: any[], faiblesses: any[] }
  backUrl?:       string
  formUrl?:       string
  urlStore?:      string
  urlUpdate?:     string
  urlSoumettre?:  string
  urlValider?:    string
  urlIndex?:      string
}>(), {
  affList:        () => [],
  phaseAuditeurs: () => [],
  donneesDB:      () => ({ forces: [], faiblesses: [] }),
  domaines:       () => ({
    analyse_risques:     'Analyse des Risques',
    analyse_processus:   'Analyse des Processus',
    repartition_taches:  'Répartition des Tâches',
    analyse_procedures:  'Analyse des Procédures',
    controle_interne:    'Contrôle Interne',
    controle_conformite: 'Contrôle de Conformité',
  }),
})

// ── Constantes ─────────────────────────────────────────────────────────
const GRAVITES = [
  { key: 'faible',   label: 'Faible'   },
  { key: 'modere',   label: 'Modéré'   },
  { key: 'eleve',    label: 'Élevé'    },
  { key: 'critique', label: 'Critique' },
]

// ── UID local ──────────────────────────────────────────────────────────
let _uidCounter = 0
const uid = () => String(++_uidCounter)

// ── État réactif ────────────────────────────────────────────────────────
const form = reactive<any>({
  id: null, code: '', validation_status: 'draft', validation_note: '',
  fait_par: '', revue_par: '',
  // On spread les champs scalaires uniquement (pas forces/faiblesses/synthese qui sont gérés séparément)
  ...(props.form
    ? Object.fromEntries(
        Object.entries(props.form).filter(([k]) => !['forces', 'faiblesses', 'synthese'].includes(k))
      )
    : {}),
})

const forces = reactive<ForceItem[]>(
  safeArr(props.form?.forces).map((f: any) => ({ ...f, _uid: uid() }))
)

const faiblesses = reactive<FaiblesseItem[]>(
  safeArr(props.form?.faiblesses).map((w: any) => ({ ...w, _uid: uid() }))
)

const _synt = props.form?.synthese ?? {}
const synthese = reactive<any>({
  domaine: '', degre_gravite_global: '', consequence_principale: '',
  objectif_controle_general: '', reference_rapport: '', periode_couverte: '',
  date_fait: '', date_revue: '',
  ...(_synt && typeof _synt === 'object' ? JSON.parse(JSON.stringify(_synt)) : {}),
})

// ── Auto-import des données BD si le formulaire est vide ────────────────
// Si forces ET faiblesses sont vides au chargement, on importe automatiquement
// les données issues des analyses (donneesDB) sans toucher au formulaire sauvegardé.
if (forces.length === 0 && faiblesses.length === 0) {
  ;(props.donneesDB?.forces ?? []).forEach((item: any) => {
    forces.push({
      domaine: item.domaine,
      libelle: item.libelle,
      processus_concerne: item.processus_concerne ?? '',
      _uid: uid(),
      _source: item._source,
      _risk_score: item._risk_score,
    })
  })
  ;(props.donneesDB?.faiblesses ?? []).forEach((item: any) => {
    faiblesses.push({
      domaine: item.domaine,
      libelle: item.libelle,
      processus_concerne: item.processus_concerne ?? '',
      fonctions: item.fonctions ?? '',
      objectif_controle: item.objectif_controle ?? '',
      _uid: uid(),
      _source: item._source,
      _risk_score: item._risk_score,
    })
  })
}

const processing  = ref(false)
const showDBPanel = ref(false)
const newForceDom = ref('')
const newFaibDom  = ref('')

const toast = ref({ show: false, type: 'success', msg: '' })
let _toastTimer: ReturnType<typeof setTimeout> | null = null

// ── Computed ────────────────────────────────────────────────────────────
const canManage = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))

const isLocked = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)

const hasDonneesDB = computed(() =>
  (props.donneesDB?.forces?.length ?? 0) + (props.donneesDB?.faiblesses?.length ?? 0) > 0
)

// Vrai si toutes les données BD sont déjà présentes dans les tableaux (auto-import au chargement)
const wasAutoImported = computed(() => {
  const dbF = props.donneesDB?.forces ?? []
  const dbW = props.donneesDB?.faiblesses ?? []
  if (!dbF.length && !dbW.length) return false
  return dbF.every((item: any) => isImported(forces, item)) &&
         dbW.every((item: any) => isImported(faiblesses, item))
})

// Nombre de forces/faiblesses BD pas encore dans les tableaux
const nbForcesNonImportees = computed(() =>
  (props.donneesDB?.forces ?? []).filter((item: any) => !isImported(forces, item)).length
)
const nbFaiblessesNonImportees = computed(() =>
  (props.donneesDB?.faiblesses ?? []).filter((item: any) => !isImported(faiblesses, item)).length
)

const sourcesBadges = computed(() => {
  const all = [...(props.donneesDB?.forces ?? []), ...(props.donneesDB?.faiblesses ?? [])]
  return [...new Set(all.map(i => (i._source ?? '').split('/')[0]).filter(Boolean))].join(', ')
})

const forcesByDomain = computed(() => {
  const map: Record<string, ForceItem[]> = {}
  let n = 1
  const domKeys = Object.keys(toRaw(props.domaines) ?? {})
  for (const domKey of domKeys) {
    map[domKey] = forces
      .filter(f => f.domaine === domKey)
      .map(f => ({ ...f, _num: n++ }))
  }
  return map
})

const faiblessesByDomain = computed(() => {
  const map: Record<string, FaiblesseItem[]> = {}
  let n = 1
  const domKeys = Object.keys(toRaw(props.domaines) ?? {})
  for (const domKey of domKeys) {
    map[domKey] = faiblesses
      .filter(w => w.domaine === domKey)
      .map(w => ({ ...w, _num: n++ }))
  }
  return map
})

// Uniquement les domaines qui ont au moins une force enregistrée
const domainesAvecForces = computed(() => {
  const result: Record<string, string> = {}
  for (const [k, v] of Object.entries(toRaw(props.domaines) ?? {})) {
    if ((forcesByDomain.value[k] ?? []).length > 0) result[k] = v
  }
  return result
})

// Uniquement les domaines qui ont au moins une faiblesse enregistrée
const domainesAvecFaiblesses = computed(() => {
  const result: Record<string, string> = {}
  for (const [k, v] of Object.entries(toRaw(props.domaines) ?? {})) {
    if ((faiblessesByDomain.value[k] ?? []).length > 0) result[k] = v
  }
  return result
})

// Plain object sûr pour v-for dans le template (évite les problèmes de Proxy Inertia)
const domainesList = computed((): Record<string, string> => ({ ...(toRaw(props.domaines) ?? {}) }))

// ── Helpers ─────────────────────────────────────────────────────────────
function safeArr(v: any): any[] {
  if (Array.isArray(v)) return [...v]
  if (!v) return []
  try { const d = JSON.parse(v); return Array.isArray(d) ? d : [] } catch { return [] }
}

function domaineLabel(k: string): string {
  return props.domaines?.[k] ?? k
}

function csrf(): string {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
}

function statusLabel(s: string): string {
  return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé', rejected: 'Rejeté' } as any)[s] ?? s
}

function statusIcon(s: string): string {
  return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check', rejected: 'ti ti-circle-x' } as any)[s] ?? 'ti ti-circle'
}

function showToast(type: string, msg: string) {
  if (_toastTimer) clearTimeout(_toastTimer)
  toast.value = { show: true, type, msg }
  _toastTimer = setTimeout(() => { toast.value.show = false }, 4000)
}

// ── CRUD items ──────────────────────────────────────────────────────────
function addForce() {
  if (!newForceDom.value) return
  forces.push({ domaine: newForceDom.value, libelle: '', processus_concerne: '', _uid: uid() })
  newForceDom.value = ''
}

function addFaiblesse() {
  if (!newFaibDom.value) return
  faiblesses.push({ domaine: newFaibDom.value, libelle: '', processus_concerne: '', fonctions: '', objectif_controle: '', _uid: uid() })
  newFaibDom.value = ''
}

function removeForce(item: ForceItem) {
  const i = forces.findIndex(f => f._uid === item._uid)
  if (i >= 0) forces.splice(i, 1)
}

function removeFaiblesse(item: FaiblesseItem) {
  const i = faiblesses.findIndex(w => w._uid === item._uid)
  if (i >= 0) faiblesses.splice(i, 1)
}

// ── Import depuis BD ────────────────────────────────────────────────────
function isImported(list: any[], item: any): boolean {
  return list.some(x => x.libelle === item.libelle && x.domaine === item.domaine)
}

function importForce(item: any) {
  if (isImported(forces, item)) return
  forces.push({
    domaine: item.domaine, libelle: item.libelle,
    processus_concerne: item.processus_concerne ?? '',
    _uid: uid(), _source: item._source,
  })
}

function importFaiblesse(item: any) {
  if (isImported(faiblesses, item)) return
  faiblesses.push({
    domaine: item.domaine, libelle: item.libelle,
    processus_concerne: item.processus_concerne ?? '',
    fonctions: item.fonctions ?? '',
    objectif_controle: item.objectif_controle ?? '',
    _uid: uid(), _source: item._source,
  })
}

function importAllForces() {
  ;(props.donneesDB?.forces ?? []).forEach(importForce)
  showToast('success', `${props.donneesDB?.forces?.length ?? 0} force(s) importée(s)`)
}

function importAllFaiblesses() {
  ;(props.donneesDB?.faiblesses ?? []).forEach(importFaiblesse)
  showToast('success', `${props.donneesDB?.faiblesses?.length ?? 0} faiblesse(s) importée(s)`)
}

function importAll() {
  importAllForces()
  importAllFaiblesses()
  showDBPanel.value = false
}

// ── Sérialisation ───────────────────────────────────────────────────────
function serializeForces(): string {
  return JSON.stringify(forces.map(f => ({
    domaine: f.domaine, libelle: f.libelle,
    processus_concerne: f.processus_concerne,
    _source: f._source ?? null,
  })))
}

function serializeFaiblesses(): string {
  return JSON.stringify(faiblesses.map(w => ({
    domaine: w.domaine, libelle: w.libelle,
    processus_concerne: w.processus_concerne,
    fonctions: w.fonctions, objectif_controle: w.objectif_controle,
    _source: w._source ?? null,
  })))
}

// ── Submit ───────────────────────────────────────────────────────────────
async function submit() {
  processing.value = true
  try {
    const payload = {
      mission_id:    props.missionId,
      assignment_id: props.assignmentId,
      fait_par:      form.fait_par,
      revue_par:     form.revue_par,
      forces:        serializeForces(),
      faiblesses:    serializeFaiblesses(),
      synthese:      JSON.stringify({ ...synthese }),
    }

    const url    = form.id ? (props.urlUpdate ?? `${props.formUrl}/${form.id}`) : (props.urlStore ?? props.formUrl)
    const method = form.id ? 'PUT' : 'POST'

    const res = await fetch(url!, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(payload),
    })

    const d = await res.json()

    if (d.success || res.ok) {
      showToast('success', form.id ? 'TFFA mis à jour.' : 'TFFA créé.')
      if (d.form) {
        Object.assign(form, {
          id:                d.form.id,
          code:              d.form.code,
          validation_status: d.form.validation_status,
        })
      }
    } else {
      showToast('error', d.message ?? 'Une erreur est survenue.')
    }
  } catch {
    showToast('error', 'Erreur réseau.')
  } finally {
    processing.value = false
  }
}

function annuler() {
  if (props.backUrl) router.visit(props.backUrl)
}

async function soumettre() {
  processing.value = true
  try {
    const res = await fetch(props.urlSoumettre ?? '', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId }),
    })
    const d = await res.json()
    if (d.success) {
      form.validation_status = 'in_review'
      showToast('success', 'Formulaire soumis pour validation.')
    } else {
      showToast('error', d.error ?? 'Erreur lors de la soumission.')
    }
  } catch {
    showToast('error', 'Erreur réseau.')
  } finally {
    processing.value = false
  }
}

async function valider(action: string, note?: string) {
  processing.value = true
  try {
    const res = await fetch(props.urlValider ?? '', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, action, note }),
    })
    const d = await res.json()
    if (d.success) {
      form.validation_status = d.status
      showToast('success', action === 'validate' ? 'Formulaire validé.' : 'Formulaire rejeté.')
    } else {
      showToast('error', d.error ?? 'Erreur.')
    }
  } catch {
    showToast('error', 'Erreur réseau.')
  } finally {
    processing.value = false
  }
}

function promptReject() {
  const note = prompt('Motif du rejet :')
  if (!note?.trim()) return
  valider('reject', note.trim())
}

onBeforeUnmount(() => { if (_toastTimer) clearTimeout(_toastTimer) })
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box }

.aff-shell {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  font-family: 'Segoe UI', system-ui, sans-serif;
  background: #f0f4f8;
}

/* ── Header ── */
.aff-header {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  padding: 10px 18px 0;
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
}
.aff-hrow { display: flex; align-items: flex-start; gap: 10px; padding-bottom: 8px }
.aff-back {
  display: flex; align-items: center; justify-content: center;
  width: 30px; height: 30px; border: 1px solid #e5e7eb;
  border-radius: 6px; color: #6b7280; text-decoration: none; flex-shrink: 0;
}
.aff-back:hover { background: #f3f4f6 }
.aff-hinfo { flex: 1; min-width: 0 }
.aff-chips { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-bottom: 2px }
.aff-code {
  font-size: .65rem; font-weight: 700; background: #1e293b; color: #fff;
  padding: 1px 6px; border-radius: 3px; font-family: ui-monospace, monospace;
}
.aff-chip {
  display: inline-flex; align-items: center; gap: 2px;
  font-size: .63rem; font-weight: 600; padding: 1px 6px;
  border-radius: 8px; border: 1px solid transparent;
}
.chip-draft     { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb }
.chip-in_review { background: #e3f2fd; color: #1565C0; border-color: rgba(21,101,192,.2) }
.chip-validated { background: #ecfdf5; color: #059669; border-color: #a7f3d0 }
.chip-type      { background: #fdf2f8; color: #9d174d; border-color: #fbcfe8 }
.chip-role-DM   { background: #f5f3ff; color: #7c3aed; border-color: #ddd6fe }
.chip-role-CM   { background: #f0f9ff; color: #0284c7; border-color: #bae6fd }
.chip-role-AS   { background: #f0fdf4; color: #059669; border-color: #a7f3d0 }
.chip-role-AJ   { background: #fffbeb; color: #d97706; border-color: #fde68a }
.aff-title { font-size: .95rem; font-weight: 800; color: #111827; margin: 0 0 2px }
.aff-meta  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: .7rem; color: #6b7280 }
.aff-meta span { display: flex; align-items: center; gap: 3px }
.cnt-f { color: #059669 !important }
.cnt-w { color: #dc2626 !important }
.aff-banner {
  display: flex; align-items: center; gap: 6px;
  padding: 5px 0; font-size: .74rem; font-weight: 500;
  border-top: 1px solid transparent;
}
.banner-lock   { color: #059669; border-top-color: #a7f3d0 }
.banner-review { color: #1565C0 }
.banner-reject { color: #dc2626 }

/* ── Body ── */
.aff-body {
  flex: 1; display: flex; flex-direction: column;
  overflow: hidden; height: calc(100vh - 90px);
}

/* ── En-tête TFFA ── */
.tffa-entete {
  display: flex; background: #fff;
  border-bottom: 2px solid #1e293b; flex-shrink: 0; flex-wrap: wrap;
}
.eb { flex: 1; min-width: 200px; padding: 8px 12px; border-right: 1px solid #d1d5db }
.eb:last-child { border-right: none }
.eb-titre {
  font-size: .6rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: .06em; color: #fff; background: #1e293b;
  padding: 2px 7px; border-radius: 3px; margin-bottom: 5px; display: inline-block;
}
.eb-tbl { width: 100%; border-collapse: collapse }
.eb-tbl td { padding: 2px 4px; font-size: .7rem; vertical-align: middle }
.eb-lbl { font-size: .62rem; font-weight: 600; color: #6b7280; white-space: nowrap; width: 1% }
.eb-val { color: #111827 }
.eb-val.fw { font-weight: 600 }
.eb-inp {
  border: 1px solid #e5e7eb; background: #fff; color: #111827;
  padding: 2px 5px; border-radius: 3px; font-size: .7rem; font-family: inherit;
  outline: none; width: 100%;
}
.eb-inp:focus { border-color: #1565C0 }
.eb-inp.eb-ro, .eb-inp:disabled { background: #f9fafb; color: #6b7280 }
.eb-inp.w100 { width: 100% }
.grav-radios { display: flex; gap: 4px; flex-wrap: wrap }
.grav-rlbl { display: inline-flex; align-items: center; gap: 3px; cursor: pointer; font-size: .68rem }
.b-grav { font-size: .62rem; font-weight: 700; padding: 2px 6px; border-radius: 4px; cursor: pointer }
.b-grav--sel { outline: 2px solid currentColor; outline-offset: 1px; font-weight: 800 }
.grav-faible   { background: #d1fae5; color: #065f46 }
.grav-modere   { background: #fef3c7; color: #92400e }
.grav-eleve    { background: #fef2f2; color: #991b1b }
.grav-critique { background: #7f1d1d; color: #fff }

/* ── Bandeau BD ── */
.db-banner {
  display: flex; align-items: center; gap: 10px;
  padding: 7px 14px; background: #eff6ff; border-bottom: 1px solid #bfdbfe;
  font-size: .74rem; color: #1e40af; flex-shrink: 0; flex-wrap: wrap;
}
.db-actions { display: flex; gap: 6px; margin-left: auto }

/* ── Panneau import BD ── */
.db-panel {
  background: #fff; border-bottom: 2px solid #e5e7eb;
  padding: 8px 14px; flex-shrink: 0;
  max-height: 300px; overflow: hidden;
  display: flex; flex-direction: column; gap: 7px;
}
.db-panel-hdr {
  display: flex; align-items: center; justify-content: space-between;
  font-size: .72rem; font-weight: 700; color: #374151; flex-shrink: 0;
}
.db-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; overflow-y: auto; flex: 1 }
.db-cols::-webkit-scrollbar { width: 3px }
.db-col { display: flex; flex-direction: column; gap: 4px }
.db-col-hdr {
  display: flex; align-items: center; gap: 5px;
  font-size: .64rem; font-weight: 700; padding: 4px 7px;
  border-radius: 5px; text-transform: uppercase; letter-spacing: .04em; flex-shrink: 0;
}
.db-f { background: #d1fae5; color: #065f46 }
.db-w { background: #fef2f2; color: #991b1b }
.db-empty { font-size: .68rem; color: #9ca3af; padding: 8px; font-style: italic }
.db-item {
  display: flex; align-items: flex-start; gap: 6px;
  padding: 6px 8px; border-radius: 6px; border: 1px solid; font-size: .7rem;
}
.db-item--f { background: #f9fef9; border-color: #bbf7d0 }
.db-item--w { background: #fffafa; border-color: #fecaca }
.db-item-body { flex: 1; min-width: 0 }
.db-dom  { font-size: .58rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 1px }
.db-src  { font-size: .58rem; color: #9ca3af; font-family: ui-monospace, monospace }
.db-lib  { font-size: .7rem; color: #111827; font-weight: 500; line-height: 1.4 }
.db-proc { font-size: .62rem; color: #6b7280; margin-top: 1px }
.db-add  { flex-shrink: 0 }
.btn-imported { opacity: .55; background: #f0fdf4 !important }
.ml-auto { margin-left: auto }

/* ── TFFA Main ── */
.tffa-main {
  display: flex; flex: 1; overflow: hidden; min-height: 0;
  border-top: 1px solid #e5e7eb;
}
.tffa-side {
  flex: 1; display: flex; flex-direction: column;
  min-width: 0; overflow: hidden; border-right: 2px solid #6b7280;
}
.tffa-side:last-child { border-right: none }

.side-title {
  display: flex; align-items: center; justify-content: space-between;
  padding: 5px 10px; font-size: .72rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: .06em;
  border-bottom: 1px solid; flex-shrink: 0; color: #fff;
}
.side-title--f { background: #1565C0; border-bottom-color: #1e40af }
.side-title--w { background: #1565C0; border-bottom-color: #1e40af }
.side-total { font-size: .7rem; background: rgba(255,255,255,.2); padding: 1px 7px; border-radius: 8px }

.col-hdr {
  display: flex; align-items: center;
  background: #2563EB; border-bottom: 1px solid #1d4ed8; flex-shrink: 0;
}
.ch {
  font-size: .56rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
  color: rgba(255,255,255,.9); padding: 4px; white-space: nowrap;
  border-right: 1px solid rgba(255,255,255,.1);
  display: flex; align-items: center; gap: 2px;
}
.ch:last-child { border-right: none }
.ch-flag { width: 26px; justify-content: center; flex-shrink: 0 }
.ch-num  { width: 34px; justify-content: center; flex-shrink: 0 }
.ch-act  { width: 24px; justify-content: center; flex-shrink: 0 }
.ch-lib  { flex: 1 }
.ch-proc { width: 80px; flex-shrink: 0 }
.ch-svc  { width: 70px; flex-shrink: 0 }
.ch-lib-w { flex: 1 }
.ch-obj  { width: 130px; flex-shrink: 0 }

.side-tb {
  display: flex; align-items: center; gap: 5px;
  padding: 5px 8px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;
}
.sb-sel {
  padding: 3px 5px; font-size: .68rem; border: 1px solid #e5e7eb;
  border-radius: 4px; background: #fff; outline: none; font-family: inherit; flex: 1; max-width: 200px;
}
.sb-sel:focus { border-color: #1565C0 }

.tbl-body { flex: 1; overflow-y: auto; background: #fff }
.tbl-body::-webkit-scrollbar { width: 4px }
.tbl-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px }

.sec-row {
  display: flex; align-items: center; gap: 6px;
  padding: 4px 8px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;
}
.sec-row--f { background: #dbeafe; border-left: 4px solid #2563EB }
.sec-row--w { background: #fce7f3; border-left: 4px solid #ec4899 }
.sec-arrow  { font-size: .7rem; font-weight: 800; color: #1e293b }
.sec-lbl    { font-size: .72rem; font-weight: 700; color: #1e293b; flex: 1 }
.sec-count  {
  font-size: .6rem; font-weight: 700; background: rgba(0,0,0,.08);
  padding: 1px 5px; border-radius: 8px; color: #374151;
}

.dat-row { display: flex; align-items: stretch; border-bottom: 1px solid #f3f4f6; min-height: 30px }
.dat-row--f.row-even { background: #fff }
.dat-row--f.row-odd  { background: #f0f9ff }
.dat-row--w.row-even { background: #fff }
.dat-row--w.row-odd  { background: #fef9f9 }
.dat-empty { background: #fafafa }
.empty-txt { color: #d1d5db; font-style: italic; font-size: .65rem; align-items: center; display: flex }

.dc {
  padding: 2px 4px; border-right: 1px solid #f1f5f9;
  display: flex; align-items: flex-start; font-size: .7rem;
}
.dc:last-child { border-right: none }
.dc-flag  { width: 26px; justify-content: center; align-items: center; flex-shrink: 0 }
.dc-num   { width: 34px; justify-content: center; align-items: center; font-weight: 700; color: #374151; font-size: .68rem; flex-shrink: 0 }
.dc-lib   { flex: 1; flex-direction: column; gap: 2px }
.dc-act   { width: 24px; justify-content: center; align-items: center; flex-shrink: 0 }
.dc-proc  { width: 80px; flex-shrink: 0; flex-direction: column }
.dc-svc   { width: 70px; flex-shrink: 0; flex-direction: column }
.dc-lib-w { flex: 1; flex-direction: column; gap: 2px }
.dc-obj   { width: 130px; flex-shrink: 0; flex-direction: column }
.fw { font-weight: 700 }

.flag-f { font-size: .56rem; font-weight: 800; color: #1d4ed8; background: #dbeafe; padding: 1px 3px; border-radius: 2px }
.flag-w { font-size: .56rem; font-weight: 800; color: #be185d; background: #fce7f3; padding: 1px 3px; border-radius: 2px }

.c-ta {
  width: 100%; border: none; background: transparent;
  font-size: .7rem; font-family: inherit; resize: none; outline: none;
  color: #111827; line-height: 1.4; padding: 1px 2px;
}
.c-ta:focus { background: #fffbeb; border-radius: 2px }
.c-inp {
  width: 100%; border: none; background: transparent;
  font-size: .68rem; font-family: inherit; outline: none; color: #111827; padding: 1px 2px; height: 22px;
}
.c-inp:focus { background: #fffbeb; border-radius: 2px }
.c-ro     { font-size: .7rem; color: #111827; line-height: 1.4; white-space: pre-wrap; word-break: break-word }
.c-ro-sm  { font-size: .66rem }
.sub-proc { margin-top: 2px }
.c-sub-inp {
  width: 100%; border: none; background: transparent; font-size: .62rem; color: #6b7280;
  font-family: inherit; outline: none; border-top: 1px dashed #e5e7eb; padding-top: 2px;
}
.c-sub-inp:focus { background: #f0f9ff }
.c-sub-ro { font-size: .62rem; color: #6b7280; border-top: 1px dashed #e5e7eb; padding-top: 2px; display: block }
.src-badge {
  display: inline-block; font-size: .55rem; font-weight: 700;
  font-family: ui-monospace, monospace; background: #e2e8f0; color: #475569;
  padding: 0 4px; border-radius: 3px; margin-top: 2px;
}
.c-del {
  background: none; border: none; cursor: pointer; color: #d1d5db;
  font-size: .8rem; padding: 1px 3px; border-radius: 3px; line-height: 1;
}
.c-del:hover { color: #ef4444; background: #fee2e2 }

.total-row {
  display: flex; align-items: center;
  border-top: 2px solid #1e293b; background: #f1f5f9; min-height: 26px;
}

/* ── Synthèse barre ── */
.synth-bar {
  background: #1e293b; color: #fff;
  padding: 6px 14px; flex-shrink: 0;
  display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
}
.sv { font-size: .72rem; font-weight: 700; flex-shrink: 0 }
.sv-fav  { color: #4ade80 }
.sv-risk { color: #f87171 }
.synth-stats { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; flex: 1 }
.ss      { display: flex; align-items: center; gap: 3px; font-size: .62rem }
.ss-dom  { color: rgba(255,255,255,.55); max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap }
.ss-f    { color: #4ade80; font-weight: 700 }
.ss-sep  { color: rgba(255,255,255,.3) }
.ss-w    { color: #f87171; font-weight: 700 }

/* ── Buttons ── */
.btn {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 5px 10px; border-radius: 5px; font-size: .76rem; font-weight: 600;
  border: none; cursor: pointer; font-family: inherit;
  transition: all .13s; white-space: nowrap;
}
.btn-save  { background: #1e293b; color: #fff }
.btn-save:hover:not(:disabled) { background: #0f172a }
.btn-ghost { background: #fff; color: #374151; border: 1px solid #e5e7eb }
.btn-ghost:hover:not(:disabled) { background: #f9fafb }
.btn-db    { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe }
.btn-sub   { background: #eff6ff; color: #2563EB; border: 1px solid #bfdbfe }
.btn-ok    { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0 }
.btn-rej   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca }
.btn-force { background: #f0fdf4; color: #059669; border: 1px solid #bbf7d0 }
.btn-faib  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca }
.btn-sm  { padding: 4px 9px; font-size: .72rem }
.btn-xs  { padding: 2px 6px; font-size: .66rem }
.btn:disabled { opacity: .45; cursor: not-allowed }

/* ── Footer ── */
.aff-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 16px; background: #fff; border-top: 1px solid #e5e7eb;
  flex-wrap: wrap; gap: 6px; flex-shrink: 0;
}
.aff-footer > div { display: flex; gap: 5px; flex-wrap: wrap }
.footer-mid { flex: 1; display: flex; justify-content: center }
.saved-code { font-size: .7rem; color: #059669; display: flex; align-items: center; gap: 3px; font-weight: 600 }

/* ── Toast ── */
.aff-toast {
  position: fixed; top: 14px; right: 14px; z-index: 9999;
  display: flex; align-items: center; gap: 6px;
  padding: 8px 13px; border-radius: 7px; font-size: .76rem; font-weight: 600;
  box-shadow: 0 4px 16px rgba(0,0,0,.12); border: 1px solid transparent;
}
.toast--success { background: #ecfdf5; color: #059669; border-color: #a7f3d0 }
.toast--error   { background: #fef2f2; color: #dc2626; border-color: #fecaca }
.toast-t-enter-active, .toast-t-leave-active { transition: all .22s }
.toast-t-enter-from, .toast-t-leave-to { opacity: 0; transform: translateX(8px) }

/* ── Spinner ── */
.spin-s {
  width: 10px; height: 10px; border-radius: 50%;
  border: 2px solid currentColor; border-top-color: transparent;
  animation: spin .6s linear infinite; display: inline-block; flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg) } }

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 4px; height: 4px }
::-webkit-scrollbar-track { background: transparent }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px }
</style>