<template>
  <VerticalLayout>
    <div class="rl-page">

      <!-- EN-TÊTE -->
      <div class="rl-header">
        <div class="d-flex align-items-center gap-3">
          <div class="rl-header-icon"><i class="ti ti-books"></i></div>
          <div>
            <h4 class="mb-0 fw-bold">Bibliothèque des risques</h4>
            <small class="text-muted">Analyse · Cliquez sur un risque pour l'ouvrir · les risques s'empilent</small>
          </div>
        </div>
        <Link :href="route('risk.core.risks.index')" class="btn btn-outline-secondary btn-sm">
          <i class="ti ti-arrow-left me-1"></i>Retour au registre
        </Link>
      </div>

      <!-- STATS -->
      <div class="row g-2 mb-3">
        <div class="col-6 col-md-3" v-for="c in statCards" :key="c.label">
          <div class="rl-stat" :class="'rl-stat--'+c.color">
            <i :class="'ti '+c.icon"></i>
            <div>
              <div class="rl-stat-val">{{ c.value }}</div>
              <div class="rl-stat-lbl">{{ c.label }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- VIDE -->
      <div v-if="!props.risks.length" class="rl-empty">
        <i class="ti ti-books d-block fs-1 mb-2 opacity-20"></i>
        La bibliothèque est vide — transférez des risques depuis le registre
      </div>

      <!-- SPLIT PANEL -->
      <div v-else class="rl-split">

        <!-- ═══ COLONNE GAUCHE ═══ -->
        <div class="rl-list-col">
          <div class="rl-list-header">
            <input v-model="search" type="text" class="form-control form-control-sm"
                   placeholder="Rechercher…"/>
            <div class="rl-tabs mt-2">
              <button :class="['rl-tab', activeTab==='all'?'rl-tab--on':'']"
                      @click="activeTab='all'">
                Tous <span class="rl-tab-count">{{ props.risks.length }}</span>
              </button>
              <button :class="['rl-tab', activeTab==='complete'?'rl-tab--on':'']"
                      @click="activeTab='complete'">
                <i class="ti ti-check me-1"></i>Complets
                <span class="rl-tab-count">{{ completeRisks.length }}</span>
              </button>
            </div>
          </div>

          <div class="rl-list">
            <!-- ONGLET COMPLETS -->
            <template v-if="activeTab==='complete'">
              <div v-if="!completeRisks.length" class="rl-list-empty">
                <i class="ti ti-check-circle opacity-20 d-block fs-2 mb-1"></i>
                Aucun risque complété à 100%
              </div>
              <div v-for="risk in completeRisks" :key="risk.id"
                   :class="['rl-risk-item rl-risk-item--complete',
                     openIds.has(risk.id) ? 'rl-risk-item--active':'']"
                   @click="toggleRisk(risk)">
                <div class="d-flex align-items-start gap-2">
                  <div class="rl-risk-dot rl-risk-dot--ok flex-shrink-0 mt-1"></div>
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="rl-risk-code">{{ risk.code_risk }}</div>
                    <div class="rl-risk-label text-truncate">{{ risk.libelle }}</div>
                    <div class="d-flex gap-1 mt-1 flex-wrap">
                      <span v-if="risk.zone_label" class="rl-zone-badge"
                            :style="zoneBadgeStyle(risk)">{{ risk.zone_label }}</span>
                      <span class="rl-complete-tag"><i class="ti ti-check me-1"></i>Complet</span>
                    </div>
                  </div>
                  <i :class="openIds.has(risk.id)?'ti ti-chevron-up':'ti ti-chevron-down'"
                     class="text-muted flex-shrink-0 mt-1" style="font-size:12px"></i>
                </div>
              </div>
            </template>

            <!-- ONGLET TOUS : arbre groupé -->
            <template v-else>
              <template v-for="macro in groupedTree" :key="macro.id">
                <div class="rl-group-macro">
                  <span class="rl-macro-badge" :style="{ background: macroColor(macro.kind) }">
                    {{ macroKindLabel(macro.kind) }}
                  </span>
                  {{ macro.name }}
                </div>
                <template v-for="process in macro.processes" :key="process.id">
                  <div class="rl-group-process">
                    <span class="rl-proc-code me-1">{{ process.code }}</span>{{ process.name }}
                  </div>
                  <template v-for="activity in process.activities" :key="activity.id">
                    <div class="rl-group-activity">
                      <span class="rl-act-code me-1">{{ activity.code }}</span>{{ activity.name }}
                    </div>
                    <div v-for="risk in activity.risks" :key="risk.id"
                         :class="['rl-risk-item', {
                           'rl-risk-item--active':    openIds.has(risk.id),
                           'rl-risk-item--complete':  isComplete(risk),
                           'rl-risk-item--incomplete':!isComplete(risk),
                         }]"
                         @click="toggleRisk(risk)">
                      <div class="d-flex align-items-start gap-2">
                        <div class="rl-risk-dot flex-shrink-0 mt-1"
                             :class="isComplete(risk)?'rl-risk-dot--ok':'rl-risk-dot--pending'"></div>
                        <div class="flex-grow-1 overflow-hidden">
                          <div class="rl-risk-code">{{ risk.code_risk }}</div>
                          <div class="rl-risk-label text-truncate">{{ risk.libelle }}</div>
                          <div class="d-flex gap-1 mt-1 flex-wrap">
                            <span v-if="risk.zone_label" class="rl-zone-badge"
                                  :style="zoneBadgeStyle(risk)">{{ risk.zone_label }}</span>
                            <span v-if="!isComplete(risk)" class="rl-incomplete-tag">
                              <i class="ti ti-pencil me-1"></i>À compléter
                            </span>
                            <span v-else class="rl-complete-tag">
                              <i class="ti ti-check me-1"></i>Complet
                            </span>
                          </div>
                        </div>
                        <i :class="openIds.has(risk.id)?'ti ti-chevron-up':'ti ti-chevron-down'"
                           class="text-muted flex-shrink-0 mt-1" style="font-size:12px"></i>
                      </div>
                    </div>
                  </template>
                </template>
              </template>
            </template>
          </div>
        </div>

        <!-- ═══ COLONNE DROITE : EMPILAGE ═══ -->
        <div class="rl-form-col">

          <!-- Aucun ouvert -->
          <div v-if="openRisks.length === 0" class="rl-form-empty">
            <i class="ti ti-hand-finger d-block fs-1 mb-3 opacity-20"></i>
            <div class="fw-semibold mb-1">Sélectionnez un risque</div>
            <small class="text-muted">Cliquez dans la liste — les risques s'empilent verticalement</small>
          </div>

          <!-- Empilage des risques ouverts -->
          <div v-else class="rl-stacked-scroll">
            <div v-for="entry in openRisks" :key="entry.risk.id" class="rl-risk-card">

              <!-- Header carte -->
              <div class="rl-card-header">
                <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1 min-w-0">
                  <span class="rl-code flex-shrink-0">{{ entry.risk.code_risk }}</span>
                  <span class="fw-bold lh-sm" style="font-size:13px">{{ entry.risk.libelle }}</span>
                  <!-- Badge complétion -->
                  <span class="rl-pct-badge"
                        :style="{ background: pctColor(entry)+'18', color: pctColor(entry), borderColor: pctColor(entry)+'44' }">
                    <i :class="pct(entry)===100 ? 'ti ti-check-circle' : 'ti ti-circle-half'"
                       class="me-1"></i>
                    {{ pct(entry) }}%
                  </span>
                </div>
                <!-- Bouton fermer -->
                <button class="btn-close-card" @click="closeRisk(entry.risk.id)" title="Fermer">
                  <i class="ti ti-x"></i>
                </button>
              </div>

              <!-- Barre progression -->
              <div class="rl-card-prog">
                <div class="rl-progress-bar">
                  <div class="rl-progress-fill"
                       :style="{ width: pct(entry)+'%', background: pctColor(entry) }"></div>
                </div>
              </div>

              <!-- Contexte -->
              <div class="rl-card-ctx">
                <span class="rl-proc-code me-1">{{ entry.risk.process_code }}</span>
                {{ entry.risk.process_name }}
                <i class="ti ti-chevron-right mx-1 opacity-40"></i>
                <span class="rl-act-code me-1">{{ entry.risk.activity_code }}</span>
                {{ entry.risk.activity_name }}
                <span v-if="entry.risk.zone_label" class="ms-2 rl-zone-badge"
                      :style="zoneBadgeStyle(entry.risk)">
                  {{ entry.risk.zone_label }}
                  <small v-if="entry.risk.criticality_score">({{ entry.risk.criticality_score }})</small>
                </span>
              </div>

              <!-- Barre IA -->
              <div class="rl-card-ia-bar">
                <button class="btn btn-xs rl-btn-ia"
                        :disabled="entry.iaLoading" @click="triggerIA(entry)">
                  <span v-if="entry.iaLoading" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="ti ti-sparkles me-1"></i>
                  Suggérer avec l'IA
                </button>
                <small v-if="entry.iaLoading" class="text-muted fst-italic ms-1">Mistral analyse…</small>
                <small v-if="entry.iaError" class="text-danger ms-1">{{ entry.iaError }}</small>
              </div>

              <!-- TABLEAU EXCEL -->
              <div class="rl-table-wrap">
                <table class="rl-table">
                  <thead>
                    <tr>
                      <th class="th-obj">Objectifs</th>
                      <th class="th-proc">Processus / Activité</th>
                      <th class="th-n">N°</th>
                      <th class="th-risk">Risques retenus</th>
                      <th class="th-yellow th-code">CODE Type</th>
                      <th class="th-yellow th-cause">Cause probable / Source du risque</th>
                      <th class="th-yellow th-entite">Entités / Partenaires impliqués</th>
                      <th class="th-yellow th-conseq">Conséquences</th>
                      <th class="th-yellow th-autresproc">Conséquences sur d'autres processus</th>
                      <th class="th-yellow th-yn">Statut : Réalisé O/N</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="td-obj">{{ entry.risk.objective??'—' }}</td>
                      <td class="td-proc">
                        <div class="fw-semibold" style="font-size:11px;color:#276221">{{ entry.risk.process_name }}</div>
                        <div style="font-size:10px;color:#555;margin-top:2px">{{ entry.risk.activity_name }}</div>
                      </td>
                      <td class="td-n">{{ riskIndex(entry.risk) }}</td>
                      <td class="td-risk">
                        <div style="font-size:11.5px;font-weight:600;line-height:1.3">{{ entry.risk.libelle }}</div>
                        <span v-if="entry.risk.zone_label" class="rl-zone-sm"
                              :style="zoneSmStyle(entry.risk)">
                          {{ entry.risk.zone_label }}
                          <small v-if="entry.risk.criticality_score">({{ entry.risk.criticality_score }})</small>
                        </span>
                      </td>

                      <!-- CODE Type -->
                      <td class="td-input td-code">
                        <select v-model="entry.form.nomenclature_id" class="ta-sel">
                          <option :value="null">—</option>
                          <template v-for="parent in nomenclatureTree" :key="parent.id">
                            <optgroup :label="parent.label">
                              <option v-for="child in parent.children"
                                      :key="child.id" :value="child.id">
                                {{ child.label }}
                              </option>
                            </optgroup>
                          </template>
                        </select>
                      </td>

                      <!-- Cause -->
                      <td class="td-input td-ia-cell">
                        <div v-if="entry.iaSugg.causes && !entry.form.causes" class="ia-chip-wrap">
                          <span class="ia-chip" @click="applyIA(entry,'causes')">
                            <i class="ti ti-sparkles me-1"></i>
                            {{ truncate(entry.iaSugg.causes,55) }}
                            <span class="ia-chip-apply">Appliquer</span>
                          </span>
                        </div>
                        <textarea v-model="entry.form.causes" class="ta"
                                  placeholder="Causes identifiées…"></textarea>
                      </td>

                      <!-- Entités -->
                      <td class="td-input">
                        <textarea v-model="entry.form.entite_partenaire_impliquee" class="ta"
                                  placeholder="Entités, partenaires…"></textarea>
                      </td>

                      <!-- Conséquences -->
                      <td class="td-input td-ia-cell">
                        <div v-if="entry.iaSugg.consequences && !entry.form.consequences"
                             class="ia-chip-wrap">
                          <span class="ia-chip" @click="applyIA(entry,'consequences')">
                            <i class="ti ti-sparkles me-1"></i>
                            {{ truncate(entry.iaSugg.consequences,55) }}
                            <span class="ia-chip-apply">Appliquer</span>
                          </span>
                        </div>
                        <textarea v-model="entry.form.consequences" class="ta"
                                  placeholder="Conséquences directes…"></textarea>
                      </td>

                      <!-- Conséquences autres -->
                      <td class="td-input">
                        <textarea v-model="entry.form.consequences_autres_processus" class="ta"
                                  placeholder="Impacts en cascade…"></textarea>
                      </td>

                      <!-- O/N -->
                      <td class="td-input td-yn">
                        <div class="yn-wrap">
                          <button :class="['yn-btn', entry.form.risque_realise?'yn-oui':'']"
                                  @click="entry.form.risque_realise=true">O</button>
                          <button :class="['yn-btn', !entry.form.risque_realise?'yn-non':'']"
                                  @click="entry.form.risque_realise=false">N</button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Actions carte -->
              <div class="rl-card-footer">
                <button class="btn btn-outline-secondary btn-sm"
                        @click="confirmRemove(entry.risk)">
                  <i class="ti ti-book-off me-1"></i>Retirer
                </button>
                <Link :href="route('risk.core.risks.edit', entry.risk.id)"
                      class="btn btn-outline-primary btn-sm">
                  <i class="ti ti-pencil me-1"></i>Fiche complète
                </Link>
                <button class="btn btn-primary btn-sm flex-fill"
                        :disabled="entry.saving" @click="submitSave(entry)">
                  <span v-if="entry.saving" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="ti ti-device-floppy me-1"></i>
                  Enregistrer
                </button>
              </div>

              <!-- Flash inline -->
              <Transition name="fl">
                <div v-if="entry.flash" class="rl-flash"
                     :class="entry.flashOk?'rl-flash--ok':'rl-flash--err'">
                  <i :class="entry.flashOk?'ti ti-check':'ti ti-alert-circle'" class="me-1"></i>
                  {{ entry.flash }}
                </div>
              </Transition>

            </div><!-- /rl-risk-card -->
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL RETRAIT -->
    <BModal v-model="showRemoveModal" title="Retirer de la bibliothèque" size="sm" hide-footer>
      <p class="mb-3">
        Retirer <strong>{{ targetRisk?.code_risk }}</strong> de la bibliothèque ?
        <small class="d-block text-muted mt-1">Le risque sera de nouveau dans le registre.</small>
      </p>
      <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary btn-sm" @click="showRemoveModal=false">Annuler</button>
        <button class="btn btn-secondary btn-sm" :disabled="removeSubmitting" @click="doRemove">
          <span v-if="removeSubmitting" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-book-off me-1"></i>Retirer
        </button>
      </div>
    </BModal>

  </VerticalLayout>
</template>

<script setup>
import { ref, computed, watch, reactive, nextTick } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { BModal } from 'bootstrap-vue-next'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  risks:         { type: Array,  default: () => [] },
  tree:          { type: Array,  default: () => [] },
  stats:         { type: Object, default: () => ({}) },
  nomenclatures: { type: Array,  default: () => [] },
  entities:      { type: Array,  default: () => [] },
})

// ── CSRF + apiFetch ───────────────────────────────────────────────────────────
function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? ''
}
async function apiFetch(routeName, routeParams, method, body) {
  const url = routeParams !== null ? route(routeName, routeParams) : route(routeName)
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept':       'application/json',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify(method === 'POST' ? body : { ...body, _method: method }),
  })
  return res
}

// ── Stats ─────────────────────────────────────────────────────────────────────
const statCards = computed(() => [
  { label: 'Bibliothèque',   value: props.stats.total_bibliotheque ?? 0, icon: 'ti-books',        color: 'primary'   },
  { label: 'Actifs',         value: props.stats.total_actif        ?? 0, icon: 'ti-shield-check', color: 'success'   },
  { label: 'Brouillons',     value: props.stats.total_draft        ?? 0, icon: 'ti-shield-half',  color: 'warning'   },
  { label: 'Registre total', value: props.stats.total_registre     ?? 0, icon: 'ti-shield',       color: 'secondary' },
])

// ── Nomenclature optgroup ─────────────────────────────────────────────────────
const nomenclatureTree = computed(() => {
  const parents = props.nomenclatures.filter(n => !n.parent_id || n.level === 1)
  return parents
    .map(p => ({ ...p, children: props.nomenclatures.filter(n => n.parent_id === p.id) }))
    .filter(p => p.children.length > 0)
})

// ── Tabs + Recherche ──────────────────────────────────────────────────────────
const search    = ref('')
const activeTab = ref('all')

// Complétion d'un risque (objet props.risks)
const REQUIRED = ['causes', 'consequences', 'entite_partenaire_impliquee']
const isComplete = risk => REQUIRED.every(f => risk[f] && String(risk[f]).trim())

const completeRisks   = computed(() => props.risks.filter(r => isComplete(r)))
const filteredRisks   = computed(() => {
  if (!search.value.trim()) return props.risks
  const q = search.value.toLowerCase()
  return props.risks.filter(r =>
    r.libelle?.toLowerCase().includes(q) ||
    r.code_risk?.toLowerCase().includes(q) ||
    r.process_name?.toLowerCase().includes(q) ||
    r.activity_name?.toLowerCase().includes(q)
  )
})

const groupedTree = computed(() => {
  const ids = new Set(filteredRisks.value.map(r => r.id))
  const result = []
  for (const macro of props.tree) {
    const processes = []
    for (const process of macro.processes) {
      const activities = []
      for (const activity of process.activities) {
        const risks = activity.risks
          .filter(r => ids.has(r.id))
          .slice()
          .sort((a, b) => (isComplete(a) ? 1 : 0) - (isComplete(b) ? 1 : 0))
        if (risks.length) activities.push({ ...activity, risks })
      }
      if (activities.length) processes.push({ ...process, activities })
    }
    if (processes.length) result.push({ ...macro, processes })
  }
  return result
})

// ── Empilage des risques ouverts ──────────────────────────────────────────────
// openRisks = tableau d'entrées { risk, form, iaSugg, iaLoading, iaError, saving, flash, flashOk }
const openRisks = ref([])
const openIds   = computed(() => new Set(openRisks.value.map(e => e.risk.id)))

function makeEntry(risk) {
  return reactive({
    risk,
    form: {
      nomenclature_id:               risk.nomenclature_id               ?? null,
      causes:                        risk.causes                        ?? '',
      entite_partenaire_impliquee:   risk.entite_partenaire_impliquee   ?? '',
      consequences:                  risk.consequences                  ?? '',
      consequences_autres_processus: risk.consequences_autres_processus ?? '',
      cout_consequences:             risk.cout_consequences             ?? '',
      controles_existants:           risk.controles_existants           ?? '',
      owner:                         risk.owner                         ?? '',
      risque_realise:                risk.risque_realise                ?? false,
      plan_traitement:               risk.plan_traitement               ?? '',
    },
    iaSugg:    {},
    iaLoading: false,
    iaError:   '',
    saving:    false,
    flash:     '',
    flashOk:   true,
    _flashTimer: null,
  })
}

function toggleRisk(risk) {
  if (openIds.value.has(risk.id)) {
    // Déjà ouvert → fermer
    closeRisk(risk.id)
  } else {
    // Nouveau → ajouter EN DESSOUS des existants
    openRisks.value.push(makeEntry(risk))
    // Scroll vers la nouvelle carte
    nextTick(() => {
      const cards = document.querySelectorAll('.rl-risk-card')
      if (cards.length) cards[cards.length - 1].scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
  }
}

function closeRisk(id) {
  const idx = openRisks.value.findIndex(e => e.risk.id === id)
  if (idx !== -1) openRisks.value.splice(idx, 1)
}

// ── Watch nomenclature par entrée → IA auto ───────────────────────────────────
watch(
  () => openRisks.value.map(e => e.form.nomenclature_id),
  (newVals, oldVals) => {
    if (!oldVals) return
    newVals.forEach((val, i) => {
      if (val && val !== oldVals[i]) triggerIA(openRisks.value[i])
    })
  },
  { deep: false }
)

// ── Complétion par entrée ─────────────────────────────────────────────────────
const TRACKED = ['causes', 'consequences', 'entite_partenaire_impliquee', 'nomenclature_id']
const OPTIONAL = ['controles_existants', 'owner', 'consequences_autres_processus']

function pct(entry) {
  const allTracked = [...TRACKED, ...OPTIONAL]
  const requiredFilled = TRACKED.filter(f => {
    const v = entry.form[f]
    return v !== null && v !== '' && v !== undefined
  })
  if (requiredFilled.length === TRACKED.length) return 100
  const filled = allTracked.filter(f => {
    const v = entry.form[f]
    return v !== null && v !== '' && v !== undefined
  })
  return Math.round((filled.length / allTracked.length) * 99)
}

function pctColor(entry) {
  const p = pct(entry)
  if (p >= 100) return '#16a34a'
  if (p >= 50)  return '#d97706'
  return '#dc2626'
}

// ── IA par entrée ─────────────────────────────────────────────────────────────
async function triggerIA(entry) {
  entry.iaLoading = true
  entry.iaError   = ''
  entry.iaSugg    = {}

  let nomenclatureContext = ''
  if (entry.form.nomenclature_id) {
    const child = props.nomenclatures.find(n => n.id === entry.form.nomenclature_id)
    if (child) {
      const parent = props.nomenclatures.find(n => n.id === child.parent_id)
      nomenclatureContext = parent ? `${parent.label} > ${child.label}` : child.label
    }
  }

  try {
    const res = await apiFetch('risk.core.risks-analyses.mistral-suggest', null, 'POST', {
      libelle:              entry.risk.libelle,
      process_name:         entry.risk.process_name,
      activity_name:        entry.risk.activity_name,
      macro_process_name:   entry.risk.macro_process_name,
      macro_process_kind:   entry.risk.macro_process_kind,
      nomenclature_context: nomenclatureContext,
      criticality_score:    entry.risk.criticality_score,
      zone_label:           entry.risk.zone_label,
      impact_label:         entry.risk.impact_label,
      frequency_label:      entry.risk.frequency_label,
    })
    if (!res.ok) {
      const err = await res.json().catch(() => ({}))
      entry.iaError = err.message ?? `Erreur HTTP ${res.status}`
      return
    }
    const d = await res.json()
    entry.iaSugg = d.suggestions ?? {}
    if (!d.suggestions) entry.iaError = d.message ?? 'Réponse IA invalide'
  } catch (e) {
    entry.iaError = 'Erreur réseau : ' + e.message
  } finally {
    entry.iaLoading = false
  }
}

function applyIA(entry, field) {
  if (entry.iaSugg[field]) entry.form[field] = entry.iaSugg[field]
}

// ── Sauvegarde par entrée ─────────────────────────────────────────────────────
async function submitSave(entry) {
  entry.saving = true
  try {
    const res = await apiFetch(
      'risk.core.risks-analyses.update',
      { id: entry.risk.id },
      'PUT',
      entry.form
    )
    const d = await res.json()
    if (res.ok && d.success) {
      showFlash(entry, 'Analyse enregistrée ✓', true)
      // Mettre à jour l'objet dans props.risks
      const idx = props.risks.findIndex(r => r.id === entry.risk.id)
      if (idx !== -1) {
        Object.assign(props.risks[idx], entry.form)
        // Repointer entry.risk sur l'objet mis à jour
        entry.risk = props.risks[idx]
      }
    } else {
      showFlash(entry, d.message ?? 'Erreur lors de la sauvegarde', false)
    }
  } catch (e) {
    showFlash(entry, 'Erreur réseau', false)
  } finally {
    entry.saving = false
  }
}

function showFlash(entry, msg, ok) {
  if (entry._flashTimer) clearTimeout(entry._flashTimer)
  entry.flash   = msg
  entry.flashOk = ok
  entry._flashTimer = setTimeout(() => { entry.flash = '' }, 3000)
}

// ── Retrait ───────────────────────────────────────────────────────────────────
const showRemoveModal  = ref(false)
const targetRisk       = ref(null)
const removeSubmitting = ref(false)

function confirmRemove(risk) {
  targetRisk.value      = risk
  showRemoveModal.value = true
}
function doRemove() {
  removeSubmitting.value = true
  router.post(
    route('risk.core.risks-analyses.remove-from-library', { id: targetRisk.value.id }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        closeRisk(targetRisk.value.id)
        showRemoveModal.value = false
      },
      onFinish: () => { removeSubmitting.value = false },
    }
  )
}

// ── N° dans l'arbre ───────────────────────────────────────────────────────────
function riskIndex(risk) {
  for (const macro of props.tree)
    for (const process of macro.processes)
      for (const activity of process.activities) {
        const idx = activity.risks.findIndex(r => r.id === risk.id)
        if (idx !== -1) return idx + 1
      }
  return 1
}

// ── Helpers visuels ───────────────────────────────────────────────────────────
const macroColor     = kind => ({ Direction:'#9333ea', Réalisation:'#16a34a', Support:'#2563eb' })[kind] ?? '#64748b'
const macroKindLabel = kind => ({ Direction:'DIR', Réalisation:'OP', Support:'SUP' })[kind] ?? (kind??'?')
const truncate       = (str, n) => str && str.length > n ? str.substring(0, n) + '…' : (str ?? '')
const zoneBadgeStyle   = r => ({ background:(r.zone_color||'#94a3b8')+'20', color:r.zone_color||'#94a3b8', borderColor:(r.zone_color||'#94a3b8')+'50' })
const zoneSmStyle      = r => ({ background:(r.zone_color||'#94a3b8')+'18', color:r.zone_color||'#94a3b8', borderColor:(r.zone_color||'#94a3b8')+'55' })
</script>

<style scoped>
.rl-page { padding:18px; display:flex; flex-direction:column; height:calc(100vh - 60px); }
.rl-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:12px; flex-shrink:0; }
.rl-header-icon { width:40px; height:40px; border-radius:9px; flex-shrink:0; background:linear-gradient(135deg,#1e293b,#1e3a5f); display:flex; align-items:center; justify-content:center; color:#93c5fd; font-size:18px; }
.rl-stat { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; border:1px solid transparent; }
.rl-stat i { font-size:1.2rem; flex-shrink:0; }
.rl-stat-val { font-size:1.15rem; font-weight:800; line-height:1; }
.rl-stat-lbl { font-size:.63rem; color:#64748b; }
.rl-stat--primary   { background:#eff6ff; border-color:#bfdbfe; } .rl-stat--primary i   { color:#2563eb; }
.rl-stat--success   { background:#f0fdf4; border-color:#bbf7d0; } .rl-stat--success i   { color:#16a34a; }
.rl-stat--warning   { background:#fffbeb; border-color:#fde68a; } .rl-stat--warning i   { color:#d97706; }
.rl-stat--secondary { background:#f8fafc; border-color:#e2e8f0; } .rl-stat--secondary i { color:#475569; }
.rl-empty { text-align:center; padding:48px; color:#94a3b8; }
.rl-split { display:flex; gap:0; flex:1; min-height:0; border-radius:10px; border:2px solid #e2e8f0; overflow:hidden; }

/* LISTE GAUCHE */
.rl-list-col { width:300px; flex-shrink:0; border-right:2px solid #e2e8f0; display:flex; flex-direction:column; background:#f8fafc; }
.rl-list-header { padding:10px 12px; border-bottom:1px solid #e2e8f0; flex-shrink:0; }
.rl-list { flex:1; overflow-y:auto; }
.rl-list-empty { text-align:center; padding:24px 12px; color:#94a3b8; font-size:.75rem; }
.rl-tabs { display:flex; gap:3px; }
.rl-tab { flex:1; padding:4px 6px; font-size:.67rem; font-weight:600; border:1px solid #e2e8f0; border-radius:5px; cursor:pointer; background:#fff; color:#64748b; display:flex; align-items:center; justify-content:center; gap:4px; transition:all .1s; }
.rl-tab:hover { background:#f1f5f9; }
.rl-tab--on   { background:#1e293b; color:#fff; border-color:#1e293b; }
.rl-tab-count { font-size:.6rem; background:rgba(255,255,255,.18); padding:0 4px; border-radius:8px; }
.rl-group-macro    { padding:7px 12px 4px; font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#334155; display:flex; align-items:center; gap:6px; background:#f1f5f9; border-bottom:1px solid #e2e8f0; margin-top:2px; }
.rl-group-process  { padding:5px 14px; font-size:.68rem; font-weight:700; color:#475569; background:#f8fafc; border-bottom:1px solid #f1f5f9; }
.rl-group-activity { padding:4px 18px; font-size:.65rem; font-weight:600; color:#64748b; background:#fff; border-bottom:1px solid #f8fafc; }
.rl-macro-badge { display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:.55rem; font-weight:800; padding:1px 6px; border-radius:4px; text-transform:uppercase; letter-spacing:.06em; flex-shrink:0; }
.rl-proc-code { display:inline-block; font-family:monospace; font-size:.6rem; background:#e2e8f0; color:#475569; padding:0 4px; border-radius:3px; }
.rl-act-code  { display:inline-block; font-family:monospace; font-size:.6rem; background:#dcfce7; color:#166534; padding:0 4px; border-radius:3px; }
.rl-code      { font-family:monospace; font-size:.65rem; font-weight:700; background:#e0e7ff; color:#4338ca; padding:1px 5px; border-radius:4px; white-space:nowrap; flex-shrink:0; }
.rl-risk-item { padding:8px 12px 8px 18px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background .1s; }
.rl-risk-item:hover             { background:#eff6ff; }
.rl-risk-item--active           { background:#dbeafe !important; border-left:3px solid #3b82f6; }
.rl-risk-item--incomplete       { border-left:3px solid #f59e0b; }
.rl-risk-item--complete         { border-left:3px solid #22c55e; }
.rl-risk-item--active.rl-risk-item--incomplete { border-left:3px solid #3b82f6; }
.rl-risk-item--active.rl-risk-item--complete   { border-left:3px solid #3b82f6; }
.rl-risk-dot          { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.rl-risk-dot--ok      { background:#22c55e; }
.rl-risk-dot--pending { background:#f59e0b; }
.rl-risk-code  { font-family:monospace; font-size:.62rem; font-weight:700; color:#4338ca; }
.rl-risk-label { font-size:.72rem; font-weight:600; color:#1e293b; line-height:1.3; }
.rl-zone-badge    { font-size:.6rem; font-weight:600; padding:1px 6px; border-radius:10px; border:1px solid; white-space:nowrap; }
.rl-zone-sm       { font-size:.6rem; font-weight:600; padding:1px 6px; border-radius:8px; border:1px solid; display:inline-block; margin-top:3px; }
.rl-incomplete-tag { font-size:.58rem; background:#fef3c7; color:#92400e; padding:1px 5px; border-radius:8px; }
.rl-complete-tag   { font-size:.58rem; background:#dcfce7; color:#15803d; padding:1px 5px; border-radius:8px; }

/* PANNEAU DROIT — EMPILAGE */
.rl-form-col   { flex:1; min-width:0; display:flex; flex-direction:column; background:#f1f5f9; overflow:hidden; }
.rl-form-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; text-align:center; padding:32px; background:#fff; }
.rl-stacked-scroll { flex:1; overflow-y:auto; padding:12px; display:flex; flex-direction:column; gap:12px; }

/* CARTE RISQUE */
.rl-risk-card {
  background:#fff;
  border-radius:10px;
  border:1.5px solid #e2e8f0;
  overflow:hidden;
  flex-shrink:0;
}
.rl-risk-card:has(.rl-flash--ok) { border-color:#86efac; }

.rl-card-header {
  display:flex; align-items:center; gap:10px;
  padding:10px 14px 8px;
  border-bottom:1px solid #f1f5f9;
  background:#fafafa;
}
.rl-pct-badge {
  font-size:.65rem; font-weight:700; padding:2px 7px;
  border-radius:10px; border:1px solid; white-space:nowrap;
}
.btn-close-card {
  flex-shrink:0; width:24px; height:24px; border-radius:6px;
  border:1px solid #e2e8f0; background:#fff; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  color:#94a3b8; font-size:12px; transition:all .1s;
}
.btn-close-card:hover { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }

.rl-card-prog {
  padding:0 14px 4px;
  background:#fafafa;
  border-bottom:1px solid #f1f5f9;
}
.rl-progress-bar  { height:4px; background:#e2e8f0; border-radius:4px; overflow:hidden; }
.rl-progress-fill { height:100%; border-radius:4px; transition:width .3s, background .3s; }

.rl-card-ctx {
  padding:6px 14px;
  font-size:.72rem; color:#475569;
  background:#f8fafc;
  border-bottom:1px solid #f1f5f9;
  display:flex; align-items:center; flex-wrap:wrap; gap:4px;
}
.rl-card-ia-bar {
  padding:7px 14px;
  display:flex; align-items:center; gap:8px;
  border-bottom:1px solid #f1f5f9;
}
.rl-card-footer {
  padding:8px 14px;
  border-top:1.5px solid #f1f5f9;
  display:flex; gap:8px; align-items:center;
  background:#fafafa;
}

/* IA */
.btn-xs { padding:2px 8px; font-size:.67rem; border-radius:5px; border:1px solid transparent; cursor:pointer; }
.rl-btn-ia          { background:#f3e8ff; color:#7c3aed; border-color:#ddd6fe; }
.rl-btn-ia:hover    { background:#ddd6fe; }
.rl-btn-ia:disabled { opacity:.5; cursor:not-allowed; }
.ia-chip-wrap { margin-bottom:4px; }
.ia-chip { display:inline-flex; align-items:center; gap:4px; font-size:.63rem; padding:3px 8px; border-radius:12px; background:#f3e8ff; color:#6d28d9; border:1px solid #ddd6fe; cursor:pointer; transition:all .1s; line-height:1.4; max-width:100%; }
.ia-chip:hover { background:#ede9fe; }
.ia-chip-apply { font-size:.58rem; font-weight:700; background:#7c3aed; color:#fff; padding:1px 5px; border-radius:8px; margin-left:4px; white-space:nowrap; }

/* TABLEAU */
.rl-table-wrap { overflow-x:auto; padding:10px 14px; }
.rl-table { border-collapse:collapse; width:100%; min-width:900px; font-size:11.5px; }
.rl-table thead tr { background:#e2efda; }
.rl-table th { border:1px solid #b8b8b8; padding:5px 6px; font-weight:700; font-size:11px; color:#1a1a1a; text-align:center; line-height:1.3; vertical-align:middle; }
.th-obj      { background:#c6efce; color:#276221; width:11%; }
.th-proc     { background:#d9ead3; color:#276221; width:13%; }
.th-n        { background:#d9ead3; color:#276221; width:4%; }
.th-risk     { background:#f4cccc; color:#7f0000; width:14%; }
.th-code     { width:7%; }
.th-cause    { width:14%; }
.th-entite   { width:12%; }
.th-conseq   { width:14%; }
.th-autresproc { width:13%; }
.th-yn       { width:7%; }
.th-yellow   { background:#ffff00; color:#333; }
.rl-table td { border:1px solid #b8b8b8; padding:4px 5px; vertical-align:top; }
.td-obj  { background:#f1f8eb; font-size:11px; color:#276221; font-weight:500; vertical-align:middle; line-height:1.4; }
.td-proc { background:#e8f5e2; font-size:11px; vertical-align:middle; }
.td-n    { background:#f9f9f9; text-align:center; font-weight:700; color:#444; vertical-align:middle; font-size:12px; }
.td-risk { background:#fff; vertical-align:middle; }
.td-code { background:#fffde7; vertical-align:middle; }
.td-input { background:#fffff0; vertical-align:top; padding:3px !important; }
.td-ia-cell { position:relative; }
.td-yn  { vertical-align:middle !important; text-align:center; }
.ta { width:100%; min-height:58px; resize:vertical; padding:3px 5px; font-size:11px; line-height:1.4; border:0.5px solid #d1d5db; border-radius:3px; background:transparent; color:#1e293b; font-family:inherit; }
.ta:focus { outline:none; border-color:#3b82f6; background:#fff; }
.ta-sel { width:100%; padding:2px 4px; font-size:11px; border:0.5px solid #d1d5db; border-radius:3px; background:transparent; color:#1e293b; font-family:inherit; height:26px; }
.ta-sel:focus { outline:none; border-color:#3b82f6; }
.yn-wrap { display:flex; gap:4px; justify-content:center; }
.yn-btn  { flex:1; padding:3px 5px; font-size:11px; font-weight:600; border:0.5px solid #d1d5db; border-radius:3px; cursor:pointer; background:#f8fafc; color:#64748b; transition:all .15s; }
.yn-oui  { background:#fee2e2; color:#b91c1c; border-color:#fca5a5; }
.yn-non  { background:#dcfce7; color:#15803d; border-color:#86efac; }
.rl-flash { padding:7px 14px; font-size:.75rem; font-weight:600; display:flex; align-items:center; }
.rl-flash--ok  { background:#dcfce7; color:#15803d; }
.rl-flash--err { background:#fee2e2; color:#dc2626; }
.fl-enter-active,.fl-leave-active { transition:all .2s; }
.fl-enter-from,.fl-leave-to       { opacity:0; transform:translateY(4px); }
.btn-sm { font-size:.72rem; padding:.15rem .5rem; }
.min-w-0 { min-width:0; }
@media (max-width:768px) {
  .rl-split    { flex-direction:column; }
  .rl-list-col { width:100%; max-height:260px; border-right:none; border-bottom:2px solid #e2e8f0; }
}
</style>