<template>
  <VerticalLayout>
    <div class="ei-page">

      <!-- ── HEADER ── -->
      <div class="ei-header">
        <div class="d-flex align-items-center gap-3">
          <div class="ei-header-icon"><i class="ti ti-shield-bolt"></i></div>
          <div>
            <h4 class="mb-0 fw-bold">Évaluation inhérente</h4>
            <small class="text-muted">Sélectionnez un risque · évaluez · validez sur la matrice</small>
          </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <span class="ei-lbl"><i class="ti ti-layout-grid me-1"></i>Matrice :</span>
          <select v-model="selectedConfigId" class="form-select form-select-sm ei-config-sel"
                  @change="onConfigChange">
            <option v-for="c in props.matrixConfigs" :key="c.id" :value="c.id">
              {{ c.name }} ({{ c.matrix_label }}){{ c.is_active ? ' ✓' : '' }}
            </option>
          </select>
          <Link :href="route('risk.core.risks.index')" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Registre
          </Link>
        </div>
      </div>

      <!-- ── STATS ── -->
      <div class="row g-2 mb-3" style="flex-shrink:0">
        <div class="col-6 col-md-3" v-for="c in statCards" :key="c.label">
          <div class="ei-stat" :class="'ei-stat--'+c.color">
            <i :class="'ti '+c.icon"></i>
            <div>
              <div class="ei-stat-val">{{ c.value }}</div>
              <div class="ei-stat-lbl">{{ c.label }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── CORPS : 3 colonnes ── -->
      <div class="ei-body">

        <!-- ══ COL 1 : LISTE RISQUES ══ -->
        <div class="ei-col-list">
          <div class="ei-col-header">
            <input v-model="search" type="text" class="form-control form-control-sm"
                   placeholder="Rechercher…"/>
            <div class="ei-tabs mt-2">
              <button :class="['ei-tab', activeTab==='all'?'ei-tab--on':'']"
                      @click="activeTab='all'">
                Tous <span class="ei-tab-count">{{ props.risks.length }}</span>
              </button>
              <button :class="['ei-tab', activeTab==='pending'?'ei-tab--on':'']"
                      @click="activeTab='pending'">
                <i class="ti ti-clock me-1"></i>
                <span class="ei-tab-count">{{ pendingRisks.length }}</span>
              </button>
              <button :class="['ei-tab', activeTab==='done'?'ei-tab--on':'']"
                      @click="activeTab='done'">
                <i class="ti ti-check me-1"></i>
                <span class="ei-tab-count">{{ evaluatedRisks.length }}</span>
              </button>
            </div>
          </div>

          <div class="ei-list-scroll">
            <!-- Onglet EN ATTENTE -->
            <template v-if="activeTab==='pending'">
              <div v-if="!pendingRisks.length" class="ei-empty-list">
                <i class="ti ti-check-circle d-block fs-2 opacity-20 mb-1"></i>
                Tous évalués !
              </div>
              <template v-for="macro in buildTree(pendingRisks)" :key="macro.id">
                <div class="ei-grp-macro">
                  <span class="ei-macro-dot" :style="{background:macroColor(macro.kind)}">
                    {{ macroKindLabel(macro.kind) }}
                  </span>{{ macro.name }}
                </div>
                <template v-for="p in macro.processes" :key="p.id">
                  <div class="ei-grp-proc"><span class="ei-proc-code me-1">{{ p.code }}</span>{{ p.name }}</div>
                  <template v-for="a in p.activities" :key="a.id">
                    <div class="ei-grp-act"><span class="ei-act-code me-1">{{ a.code }}</span>{{ a.name }}</div>
                    <div v-for="r in a.risks" :key="r.id"
                         :class="['ei-risk-row','ei-risk-row--pending', selectedRisk?.id===r.id?'ei-risk-row--sel':'']"
                         @click="selectRisk(r)">
                      <div class="ei-dot ei-dot--nd"></div>
                      <div class="min-w-0">
                        <div class="ei-rcode">{{ r.code_risk }}</div>
                        <div class="ei-rlbl text-truncate">{{ r.libelle }}</div>
                      </div>
                    </div>
                  </template>
                </template>
              </template>
            </template>

            <!-- Onglet ÉVALUÉS -->
            <template v-else-if="activeTab==='done'">
              <div v-if="!evaluatedRisks.length" class="ei-empty-list">
                <i class="ti ti-shield d-block fs-2 opacity-20 mb-1"></i>Aucun évalué
              </div>
              <template v-for="macro in buildTree(evaluatedRisks)" :key="macro.id">
                <div class="ei-grp-macro">
                  <span class="ei-macro-dot" :style="{background:macroColor(macro.kind)}">
                    {{ macroKindLabel(macro.kind) }}
                  </span>{{ macro.name }}
                </div>
                <template v-for="p in macro.processes" :key="p.id">
                  <div class="ei-grp-proc"><span class="ei-proc-code me-1">{{ p.code }}</span>{{ p.name }}</div>
                  <template v-for="a in p.activities" :key="a.id">
                    <div class="ei-grp-act"><span class="ei-act-code me-1">{{ a.code }}</span>{{ a.name }}</div>
                    <div v-for="r in a.risks" :key="r.id"
                         :class="['ei-risk-row','ei-risk-row--done', selectedRisk?.id===r.id?'ei-risk-row--sel':'']"
                         @click="selectRisk(r)">
                      <div class="ei-dot ei-dot--ok"></div>
                      <div class="min-w-0">
                        <div class="ei-rcode">{{ r.code_risk }}</div>
                        <div class="ei-rlbl text-truncate">{{ r.libelle }}</div>
                        <span v-if="r.zone_label" class="ei-zone-badge"
                              :style="zoneBadgeStyle(r)">{{ r.zone_label }} ({{ r.criticality_score }})</span>
                      </div>
                    </div>
                  </template>
                </template>
              </template>
            </template>

            <!-- Onglet TOUS -->
            <template v-else>
              <template v-for="macro in groupedTree" :key="macro.id">
                <div class="ei-grp-macro">
                  <span class="ei-macro-dot" :style="{background:macroColor(macro.kind)}">
                    {{ macroKindLabel(macro.kind) }}
                  </span>{{ macro.name }}
                </div>
                <template v-for="p in macro.processes" :key="p.id">
                  <div class="ei-grp-proc"><span class="ei-proc-code me-1">{{ p.code }}</span>{{ p.name }}</div>
                  <template v-for="a in p.activities" :key="a.id">
                    <div class="ei-grp-act"><span class="ei-act-code me-1">{{ a.code }}</span>{{ a.name }}</div>
                    <div v-for="r in a.risks" :key="r.id"
                         :class="['ei-risk-row',
                           isEvaluated(r)?'ei-risk-row--done':'ei-risk-row--pending',
                           selectedRisk?.id===r.id?'ei-risk-row--sel':'']"
                         @click="selectRisk(r)">
                      <div class="ei-dot" :class="isEvaluated(r)?'ei-dot--ok':'ei-dot--nd'"></div>
                      <div class="min-w-0">
                        <div class="ei-rcode">{{ r.code_risk }}</div>
                        <div class="ei-rlbl text-truncate">{{ r.libelle }}</div>
                        <span v-if="r.zone_label" class="ei-zone-badge"
                              :style="zoneBadgeStyle(r)">{{ r.zone_label }} ({{ r.criticality_score }})</span>
                        <span v-else class="ei-tag-nd"><i class="ti ti-clock me-1"></i>À évaluer</span>
                      </div>
                    </div>
                  </template>
                </template>
              </template>
            </template>
          </div>
        </div>

        <!-- ══ COL 2 : ÉVALUATION ══ -->
        <div class="ei-col-eval">
          <div v-if="!selectedRisk" class="ei-eval-empty">
            <i class="ti ti-hand-finger d-block fs-1 mb-3 opacity-20"></i>
            <div class="fw-semibold">Sélectionnez un risque</div>
            <small class="text-muted mt-1">Cliquez dans la liste</small>
          </div>

          <template v-else>
            <!-- Info risque sélectionné -->
            <div class="ei-risk-info">
              <div class="d-flex align-items-start gap-2">
                <span class="ei-code-badge">{{ selectedRisk.code_risk }}</span>
                <div class="min-w-0">
                  <div class="fw-bold lh-sm" style="font-size:13px">{{ selectedRisk.libelle }}</div>
                  <div class="ei-ctx mt-1">
                    <span class="ei-proc-code me-1">{{ selectedRisk.process_code }}</span>
                    {{ selectedRisk.process_name }}
                    <i class="ti ti-chevron-right mx-1 opacity-30"></i>
                    <span class="ei-act-code me-1">{{ selectedRisk.activity_code }}</span>
                    {{ selectedRisk.activity_name }}
                  </div>
                  <div v-if="isEvaluated(selectedRisk)" class="mt-1">
                    <span class="ei-zone-badge" :style="zoneBadgeStyle(selectedRisk)">
                      <i class="ti ti-shield-check me-1"></i>
                      {{ selectedRisk.zone_label }} · score {{ selectedRisk.criticality_score }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- IMPACT -->
            <div class="ei-section-lbl">
              <span class="ei-step">1</span>
              <i class="ti ti-arrow-up me-1 text-danger"></i>Niveau d'impact
            </div>
            <div class="ei-levels-stack">
              <div v-for="lvl in impactLevels" :key="lvl.id"
                   :class="['ei-level', form.impact_id===lvl.id?'ei-level--sel':'']"
                   :style="levelStyle(lvl, form.impact_id===lvl.id)"
                   @click="form.impact_id=lvl.id; form.impact_score=lvl.score">
                <div class="ei-level-dot"
                     :style="`background:${lvl.color_code||'#94a3b8'};color:${onBg(lvl.color_code||'#94a3b8')}`">
                  {{ lvl.score }}
                </div>
                <div class="ei-level-name">{{ lvl.label }}</div>
                <i v-if="form.impact_id===lvl.id" class="ti ti-check-circle ei-sel-icon"></i>
              </div>
            </div>

            <!-- FRÉQUENCE -->
            <div class="ei-section-lbl mt-2">
              <span class="ei-step">2</span>
              <i class="ti ti-clock me-1 text-primary"></i>Fréquence / Vraisemblance
            </div>

            <!-- ÉTAPE 2a : Choisir le critère EN PREMIER (si critères disponibles) -->
            <template v-if="props.frequencyCriteriaTemplates.length">
              <div class="ei-criteria-bar">
                <span class="ei-criteria-lbl"><i class="ti ti-tag me-1"></i>Choisir le critère :</span>
                <div class="d-flex flex-wrap gap-1 mt-1">
                  <span v-for="tpl in props.frequencyCriteriaTemplates" :key="tpl.id"
                        :class="['ei-criteria-chip', selectedCriteriaId===tpl.id?'ei-criteria-chip--on':'']"
                        @click="toggleCriteria(tpl)">
                    {{ tpl.designation }}
                  </span>
                </div>
              </div>

              <!-- ÉTAPE 2b : Niveaux apparaissent APRÈS choix du critère -->
              <div v-if="!selectedCriteriaId" class="ei-criteria-wait">
                <i class="ti ti-arrow-up me-1 opacity-30"></i>
                Sélectionnez un critère pour afficher les niveaux
              </div>
              <div v-else class="ei-levels-stack">
                <div v-for="lvl in frequencyLevels" :key="lvl.id"
                     :class="['ei-level', form.frequency_id===lvl.id?'ei-level--sel':'']"
                     :style="levelStyle(lvl, form.frequency_id===lvl.id)"
                     @click="form.frequency_id=lvl.id; form.frequency_score=lvl.score">
                  <div class="ei-level-dot"
                       :style="`background:${lvl.color_code||'#94a3b8'};color:${onBg(lvl.color_code||'#94a3b8')}`">
                    {{ lvl.score }}
                  </div>
                  <div class="flex-grow-1 min-w-0">
                    <div class="ei-level-name">{{ lvl.label }}</div>
                    <!-- Description du critère sélectionné pour ce niveau -->
                    <div v-if="getCriteriaDesc(lvl)" class="ei-level-criteria">
                      <i class="ti ti-quote me-1 opacity-40"></i>{{ getCriteriaDesc(lvl) }}
                    </div>
                  </div>
                  <i v-if="form.frequency_id===lvl.id" class="ti ti-check-circle ei-sel-icon"></i>
                </div>
              </div>
            </template>

            <!-- Pas de critères configurés → niveaux directs sans description -->
            <template v-else>
              <div class="ei-levels-stack">
                <div v-for="lvl in frequencyLevels" :key="lvl.id"
                     :class="['ei-level', form.frequency_id===lvl.id?'ei-level--sel':'']"
                     :style="levelStyle(lvl, form.frequency_id===lvl.id)"
                     @click="form.frequency_id=lvl.id; form.frequency_score=lvl.score">
                  <div class="ei-level-dot"
                       :style="`background:${lvl.color_code||'#94a3b8'};color:${onBg(lvl.color_code||'#94a3b8')}`">
                    {{ lvl.score }}
                  </div>
                  <div class="ei-level-name">{{ lvl.label }}</div>
                  <i v-if="form.frequency_id===lvl.id" class="ti ti-check-circle ei-sel-icon"></i>
                </div>
              </div>
            </template>

            <!-- Résultat -->
            <div v-if="form.impact_score && form.frequency_score" class="ei-result"
                 :style="resultStyle">
              <div class="ei-result-score">{{ form.impact_score * form.frequency_score }}</div>
              <div class="flex-grow-1">
                <div class="ei-result-zone" :style="{color: currentZone?.color_code??'#64748b'}">
                  {{ currentZone?.label ?? '—' }}
                </div>
                <div class="ei-result-detail">Impact {{ form.impact_score }} × Fréq. {{ form.frequency_score }}</div>
              </div>
            </div>

            <!-- Actions -->
            <div class="ei-eval-footer">
              <button class="btn btn-outline-secondary btn-sm" @click="resetForm">
                <i class="ti ti-x me-1"></i>Annuler
              </button>
              <button class="btn btn-success btn-sm flex-fill fw-semibold"
                      :disabled="!form.impact_score||!form.frequency_score||saving"
                      @click="submitEval">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="ti ti-shield-check me-1"></i>Valider
              </button>
            </div>

            <Transition name="fl">
              <div v-if="flashMsg" class="ei-flash"
                   :class="flashOk?'ei-flash--ok':'ei-flash--err'">
                <i :class="flashOk?'ti ti-check':'ti ti-alert-circle'" class="me-1"></i>
                {{ flashMsg }}
              </div>
            </Transition>
          </template>
        </div>

        <!-- ══ COL 3 : MATRICE ══ -->
        <div class="ei-col-matrix">
          <div class="ei-matrix-toolbar">
            <span class="fw-semibold" style="font-size:.75rem">
              <i class="ti ti-layout-grid me-1"></i>Matrice de criticité
            </span>
            <div class="d-flex gap-1 ms-auto align-items-center">
              <!-- Zoom -->
              <button class="ei-zoom-btn" @click="zoom = Math.max(0.5, zoom - 0.1)" title="Zoom −">
                <i class="ti ti-minus"></i>
              </button>
              <span class="ei-zoom-val">{{ Math.round(zoom*100) }}%</span>
              <button class="ei-zoom-btn" @click="zoom = Math.min(2, zoom + 0.1)" title="Zoom +">
                <i class="ti ti-plus"></i>
              </button>
              <button class="ei-zoom-btn ms-1" @click="zoom=1" title="Réinitialiser">
                <i class="ti ti-refresh"></i>
              </button>
            </div>
            <!-- Filtre activité -->
            <div class="d-flex gap-1 flex-wrap ms-2">
              <button :class="['btn btn-xs ei-filter', !filterActId?'ei-filter--on':'']"
                      @click="filterActId=null">Tous</button>
              <button v-for="a in uniqueActivities" :key="a.id"
                      :class="['btn btn-xs ei-filter', filterActId===a.id?'ei-filter--on':'']"
                      @click="filterActId=a.id">
                <span class="ei-act-code">{{ a.code }}</span>
              </button>
            </div>
          </div>

          <div class="ei-matrix-scroll">
            <div v-if="!props.matrixData" class="ei-eval-empty">
              <i class="ti ti-layout-grid d-block fs-1 mb-2 opacity-20"></i>
              Aucune matrice configurée
            </div>
            <div v-else class="ei-matrix-zoom-wrap" :style="`transform:scale(${zoom});transform-origin:top left`">
              <table class="ei-mat">
                <thead>
                  <tr>
                    <th class="ei-mat-corner">
                      <span class="ei-ci">Impact ↑</span>
                      <span class="ei-cf">Fréquence →</span>
                    </th>
                    <th v-for="freq in freqAsc" :key="freq.id" class="ei-mat-fhd"
                        :style="freq.color_code?`border-bottom:3px solid ${freq.color_code}`:''">
                      <div class="ei-mat-hdot"
                           :style="freq.color_code?`background:${freq.color_code};color:${onBg(freq.color_code)}`:'background:#e2e8f0;color:#94a3b8'">
                        {{ freq.score }}
                      </div>
                      <div class="ei-mat-hlbl" :style="freq.color_code?`color:${freq.color_code}`:''">
                        {{ freq.label }}
                      </div>
                      <div v-if="freq.recurrence" class="ei-mat-hrec">{{ freq.recurrence }}</div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, ri) in cellsDesc" :key="ri">
                    <td class="ei-mat-ihd"
                        :style="getImpact(row[0]?.impact_score)?.color_code?`border-left:4px solid ${getImpact(row[0].impact_score).color_code}`:''">
                      <div class="ei-mat-hdot"
                           :style="getImpact(row[0]?.impact_score)?.color_code?`background:${getImpact(row[0].impact_score).color_code};color:${onBg(getImpact(row[0].impact_score).color_code)}`:'background:#e2e8f0;color:#94a3b8'">
                        {{ row[0]?.impact_score }}
                      </div>
                      <div class="ei-mat-ilbl"
                           :style="getImpact(row[0]?.impact_score)?.color_code?`color:${getImpact(row[0].impact_score).color_code}`:''">
                        {{ getImpact(row[0]?.impact_score)?.label }}
                      </div>
                    </td>
                    <td v-for="cell in row" :key="cell.frequency_score"
                        class="ei-mat-cell"
                        :style="cellStyle(cell)"
                        :class="isCellActive(cell)?'ei-mat-cell--active':''">
                      <!-- Score -->
                      <div class="ei-mat-score"
                           :style="cell.zone_color?`color:${onBg(cell.zone_color)}`:''">
                        {{ cell.score }}
                      </div>
                      <div v-if="cell.zone_label" class="ei-mat-zlbl"
                           :style="cell.zone_color?`color:${onBg(cell.zone_color)}`:''">
                        {{ cell.zone_label }}
                      </div>
                      <!-- Chips risques positionnés -->
                      <div class="ei-mat-chips">
                        <div v-for="r in risksInCell(cell)" :key="r.id"
                             :class="['ei-chip', r.id===selectedRisk?.id?'ei-chip--sel':'']"
                             :title="r.code_risk+' — '+r.libelle"
                             @click.stop="selectRisk(r)">
                          {{ r.code_risk }}
                        </div>
                      </div>
                      <!-- Indicateur cellule active (évaluation en cours) -->
                      <div v-if="isCellActive(cell)" class="ei-mat-cursor">
                        <i class="ti ti-map-pin"></i>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Légende -->
          <div class="ei-legend">
            <div v-for="zone in sortedZones" :key="zone.id" class="ei-legend-item"
                 :style="`border-left:3px solid ${zone.color_code};background:${zone.color_code}12`">
              <span class="ei-legend-dot" :style="`background:${zone.color_code}`"></span>
              <span class="fw-semibold" :style="`color:${zone.color_code};font-size:.65rem`">{{ zone.label }}</span>
              <span class="ei-legend-n">{{ risksInZone(zone).length }}</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  risks:                      { type: Array,  default: () => [] },
  tree:                       { type: Array,  default: () => [] },
  stats:                      { type: Object, default: () => ({}) },
  matrixConfigs:              { type: Array,  default: () => [] },
  matrixData:                 { type: Object, default: () => null },
  selectedConfigId:           { type: Number, default: null },
  frequencyCriteriaTemplates: { type: Array,  default: () => [] },
})

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? ''
}

// ── Stats ─────────────────────────────────────────────────────────────────────
const statCards = computed(() => [
  { label: 'Total',      value: props.stats.total         ?? 0, icon: 'ti-shield',         color: 'primary' },
  { label: 'Évalués',    value: props.stats.evaluated     ?? 0, icon: 'ti-shield-check',   color: 'success' },
  { label: 'En attente', value: props.stats.not_evaluated ?? 0, icon: 'ti-shield-half',    color: 'warning' },
  { label: 'Critiques',  value: props.stats.critical      ?? 0, icon: 'ti-alert-triangle', color: 'danger'  },
])

// ── Config ────────────────────────────────────────────────────────────────────
const selectedConfigId = ref(props.selectedConfigId ?? props.matrixConfigs[0]?.id ?? null)
function onConfigChange() {
  router.get(route('risk.core.evaluation.inherente'),
    { config_id: selectedConfigId.value },
    { preserveState: true, preserveScroll: true })
}

// ── Niveaux ───────────────────────────────────────────────────────────────────
const impactLevels    = computed(() => [...(props.matrixData?.impacts     ?? [])].sort((a,b) => b.score-a.score))
const frequencyLevels = computed(() => [...(props.matrixData?.frequencies ?? [])].sort((a,b) => a.score-b.score))

// ── Critères fréquence ────────────────────────────────────────────────────────
const selectedCriteriaId = ref(null)
const selectedCriteria   = ref(null)

function toggleCriteria(tpl) {
  if (selectedCriteriaId.value === tpl.id) {
    selectedCriteriaId.value = null; selectedCriteria.value = null
  } else {
    selectedCriteriaId.value = tpl.id; selectedCriteria.value = tpl
  }
}
function getCriteriaDesc(lvl) {
  const entry = (selectedCriteria.value?.levels ?? []).find(l => l.frequency_level_id === lvl.id)
  return entry?.description ?? null
}

// ── Liste + Tabs ──────────────────────────────────────────────────────────────
const search    = ref('')
const activeTab = ref('all')

const isEvaluated    = r => !!(r.impact_score && r.frequency_score)
const evaluatedRisks = computed(() => props.risks.filter(r =>  isEvaluated(r)))
const pendingRisks   = computed(() => props.risks.filter(r => !isEvaluated(r)))

const filteredRisks = computed(() => {
  if (!search.value.trim()) return props.risks
  const q = search.value.toLowerCase()
  return props.risks.filter(r =>
    r.libelle?.toLowerCase().includes(q) ||
    r.code_risk?.toLowerCase().includes(q) ||
    r.process_name?.toLowerCase().includes(q)
  )
})

function buildTree(riskList) {
  const ids = new Set(riskList.map(r => r.id))
  const result = []
  for (const macro of props.tree) {
    const processes = []
    for (const p of macro.processes) {
      const activities = []
      for (const a of p.activities) {
        const risks = a.risks.filter(r => ids.has(r.id))
        if (risks.length) activities.push({ ...a, risks })
      }
      if (activities.length) processes.push({ ...p, activities })
    }
    if (processes.length) result.push({ ...macro, processes })
  }
  return result
}
const groupedTree = computed(() => buildTree(filteredRisks.value))

// ── Sélection ─────────────────────────────────────────────────────────────────
const selectedRisk = ref(null)
const saving       = ref(false)
const flashMsg     = ref('')
const flashOk      = ref(true)
let   flashTimer   = null

const form = ref({ impact_id: null, impact_score: null, frequency_id: null, frequency_score: null })

function selectRisk(r) {
  selectedRisk.value = r
  flashMsg.value     = ''
  const impLvl  = impactLevels.value.find(l => l.score === r.impact_score)
  const freqLvl = frequencyLevels.value.find(l => l.score === r.frequency_score)
  form.value = {
    impact_id:       impLvl?.id    ?? null,
    impact_score:    r.impact_score    ?? null,
    frequency_id:    freqLvl?.id   ?? null,
    frequency_score: r.frequency_score ?? null,
  }
  selectedCriteriaId.value = null
  selectedCriteria.value   = null
}

function resetForm() {
  selectedRisk.value = null
  form.value = { impact_id: null, impact_score: null, frequency_id: null, frequency_score: null }
  selectedCriteriaId.value = null
}

// ── Zone courante ─────────────────────────────────────────────────────────────
const currentZone = computed(() => {
  if (!form.value.impact_score || !form.value.frequency_score) return null
  return (props.matrixData?.zones ?? []).find(z =>
    z.impact_score    === form.value.impact_score &&
    z.frequency_score === form.value.frequency_score
  ) ?? null
})

const resultStyle = computed(() => {
  const c = currentZone.value?.color_code ?? '#94a3b8'
  return `background:${c}14;border:1.5px solid ${c}44;border-left:4px solid ${c};border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:12px;margin-top:10px`
})

// ── Sauvegarde ────────────────────────────────────────────────────────────────
async function submitEval() {
  if (!selectedRisk.value || !form.value.impact_score || !form.value.frequency_score) return
  saving.value = true
  try {
    const res = await fetch(route('risk.core.evaluation.inherente.store'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      body: JSON.stringify({
        risk_id:         selectedRisk.value.id,
        impact_score:    form.value.impact_score,
        frequency_score: form.value.frequency_score,
        criteria_id:     selectedCriteriaId.value,
      }),
    })
    const d = await res.json()
    if (res.ok && d.success) {
      showFlash('Évaluation enregistrée ✓', true)
      const idx = props.risks.findIndex(r => r.id === selectedRisk.value.id)
      if (idx !== -1) {
        Object.assign(props.risks[idx], {
          impact_score:      form.value.impact_score,
          frequency_score:   form.value.frequency_score,
          criticality_score: d.risk?.criticality_score ?? form.value.impact_score * form.value.frequency_score,
          zone_label:        d.risk?.zone_label  ?? currentZone.value?.label,
          zone_color:        d.risk?.zone_color  ?? currentZone.value?.color_code,
          impact_label:      d.risk?.impact_label,
          frequency_label:   d.risk?.frequency_label,
        })
        selectedRisk.value = props.risks[idx]
      }
    } else showFlash(d.message ?? 'Erreur', false)
  } catch { showFlash('Erreur réseau', false) }
  finally { saving.value = false }
}

function showFlash(msg, ok) {
  if (flashTimer) clearTimeout(flashTimer)
  flashMsg.value = msg; flashOk.value = ok
  flashTimer = setTimeout(() => { flashMsg.value = '' }, 3500)
}

// ── Matrice ───────────────────────────────────────────────────────────────────
const zoom         = ref(1)
const filterActId  = ref(null)

const freqAsc   = computed(() => [...(props.matrixData?.frequencies ?? [])].sort((a,b) => a.score-b.score))
const cellsDesc = computed(() => [...(props.matrixData?.cells ?? [])].sort((a,b) => b[0]?.impact_score - a[0]?.impact_score))
const sortedZones = computed(() => [...(props.matrixData?.zones ?? [])].sort((a,b) => b.impact_score-a.impact_score||a.frequency_score-b.frequency_score))

const impactMap = computed(() => {
  const m = {}
  for (const i of (props.matrixData?.impacts ?? [])) m[i.score] = i
  return m
})
const getImpact = s => impactMap.value[s] ?? null

const isCellActive = cell =>
  form.value.impact_score    === cell.impact_score &&
  form.value.frequency_score === cell.frequency_score

function cellStyle(cell) {
  const c = cell.zone_color ?? '#e2e8f0'
  if (isCellActive(cell)) return `background:${c};outline:3px solid rgba(0,0,0,.4);z-index:3;position:relative`
  return `background:${c}77`
}

const uniqueActivities = computed(() => {
  const m = new Map()
  for (const macro of props.tree)
    for (const p of macro.processes)
      for (const a of p.activities)
        m.set(a.id, a)
  return [...m.values()]
})

const visibleRisks = computed(() =>
  !filterActId.value ? props.risks : props.risks.filter(r => r.activity_id === filterActId.value)
)

function risksInCell(cell) {
  return visibleRisks.value.filter(r =>
    isEvaluated(r) &&
    r.impact_score    === cell.impact_score &&
    r.frequency_score === cell.frequency_score
  )
}
function risksInZone(zone) {
  return props.risks.filter(r =>
    isEvaluated(r) &&
    r.impact_score    === zone.impact_score &&
    r.frequency_score === zone.frequency_score
  )
}

// ── Helpers visuels ───────────────────────────────────────────────────────────
const macroColor     = kind => ({ Direction:'#9333ea', Réalisation:'#16a34a', Support:'#2563eb' })[kind] ?? '#64748b'
const macroKindLabel = kind => ({ Direction:'DIR', Réalisation:'OP', Support:'SUP' })[kind] ?? (kind??'?')
const zoneBadgeStyle = r => ({ background:(r.zone_color||'#94a3b8')+'20', color:r.zone_color||'#94a3b8', borderColor:(r.zone_color||'#94a3b8')+'55' })

function levelStyle(lvl, active) {
  const c = lvl.color_code ?? '#94a3b8'
  return active
    ? `background:${c}18;border-color:${c};border-left:3px solid ${c}`
    : 'background:#fafafa;border-color:#e2e8f0;border-left:3px solid transparent'
}

function onBg(hex) {
  if (!hex) return '#fff'
  try {
    const r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16)
    return (0.299*r+0.587*g+0.114*b)/255>0.55?'#1f2937':'#fff'
  } catch { return '#fff' }
}
</script>

<style scoped>
.ei-page { padding:18px; display:flex; flex-direction:column; height:calc(100vh - 60px); gap:10px; }

/* HEADER */
.ei-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; flex-shrink:0; }
.ei-header-icon { width:42px; height:42px; border-radius:10px; background:linear-gradient(135deg,#1e293b,#1e3a5f); display:flex; align-items:center; justify-content:center; color:#93c5fd; font-size:20px; flex-shrink:0; }
.ei-lbl { font-size:.75rem; font-weight:600; color:#475569; white-space:nowrap; }
.ei-config-sel { font-size:.76rem; min-width:180px; }

/* STATS */
.ei-stat { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; border:1px solid transparent; }
.ei-stat i { font-size:1.2rem; flex-shrink:0; }
.ei-stat-val { font-size:1.15rem; font-weight:800; line-height:1; }
.ei-stat-lbl { font-size:.63rem; color:#64748b; }
.ei-stat--primary { background:#eff6ff; border-color:#bfdbfe; } .ei-stat--primary i { color:#2563eb; }
.ei-stat--success { background:#f0fdf4; border-color:#bbf7d0; } .ei-stat--success i { color:#16a34a; }
.ei-stat--warning { background:#fffbeb; border-color:#fde68a; } .ei-stat--warning i { color:#d97706; }
.ei-stat--danger  { background:#fef2f2; border-color:#fecaca; } .ei-stat--danger i  { color:#dc2626; }

/* CORPS 3 colonnes */
.ei-body {
  display:flex; flex:1; min-height:0;
  gap:0; border-radius:10px; border:2px solid #e2e8f0; overflow:hidden;
}

/* ── COL 1 : LISTE ── */
.ei-col-list { width:260px; flex-shrink:0; border-right:2px solid #e2e8f0; display:flex; flex-direction:column; background:#f8fafc; }
.ei-col-header { padding:10px 12px; border-bottom:1px solid #e2e8f0; flex-shrink:0; }
.ei-list-scroll { flex:1; overflow-y:auto; }
.ei-empty-list { text-align:center; padding:24px 12px; color:#94a3b8; font-size:.75rem; }

.ei-tabs { display:flex; gap:3px; }
.ei-tab { flex:1; padding:4px 6px; font-size:.67rem; font-weight:600; border:1px solid #e2e8f0; border-radius:5px; cursor:pointer; background:#fff; color:#64748b; display:flex; align-items:center; justify-content:center; gap:3px; transition:all .1s; }
.ei-tab:hover { background:#f1f5f9; }
.ei-tab--on   { background:#1e293b; color:#fff; border-color:#1e293b; }
.ei-tab-count { font-size:.6rem; background:rgba(255,255,255,.2); padding:0 4px; border-radius:8px; }

.ei-grp-macro  { padding:6px 10px 3px; font-size:.63rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#334155; display:flex; align-items:center; gap:5px; background:#f1f5f9; border-bottom:1px solid #e2e8f0; margin-top:2px; }
.ei-grp-proc   { padding:4px 12px; font-size:.67rem; font-weight:700; color:#475569; background:#f8fafc; border-bottom:1px solid #f1f5f9; }
.ei-grp-act    { padding:3px 16px; font-size:.64rem; font-weight:600; color:#64748b; background:#fff; border-bottom:1px solid #f8fafc; }

.ei-macro-dot { display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:.54rem; font-weight:800; padding:1px 5px; border-radius:4px; flex-shrink:0; }
.ei-proc-code { display:inline-block; font-family:monospace; font-size:.58rem; background:#e2e8f0; color:#475569; padding:0 4px; border-radius:3px; white-space:nowrap; }
.ei-act-code  { display:inline-block; font-family:monospace; font-size:.58rem; background:#dcfce7; color:#166534; padding:0 4px; border-radius:3px; white-space:nowrap; }

.ei-risk-row { display:flex; align-items:flex-start; gap:7px; padding:7px 10px 7px 16px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background .1s; }
.ei-risk-row:hover      { background:#eff6ff; }
.ei-risk-row--sel       { background:#dbeafe !important; border-left:3px solid #3b82f6; }
.ei-risk-row--done      { border-left:3px solid #22c55e; }
.ei-risk-row--pending   { border-left:3px solid #f59e0b; }
.ei-risk-row--sel.ei-risk-row--done    { border-left:3px solid #3b82f6; }
.ei-risk-row--sel.ei-risk-row--pending { border-left:3px solid #3b82f6; }
.ei-dot         { width:7px; height:7px; border-radius:50%; flex-shrink:0; margin-top:4px; }
.ei-dot--ok     { background:#22c55e; }
.ei-dot--nd     { background:#f59e0b; }
.ei-rcode { font-family:monospace; font-size:.6rem; font-weight:700; color:#4338ca; }
.ei-rlbl  { font-size:.7rem; font-weight:500; color:#1e293b; line-height:1.3; }
.ei-zone-badge { font-size:.58rem; font-weight:600; padding:1px 5px; border-radius:8px; border:1px solid; white-space:nowrap; display:inline-block; margin-top:2px; }
.ei-tag-nd { font-size:.58rem; background:#fef3c7; color:#92400e; padding:1px 5px; border-radius:8px; display:inline-block; margin-top:2px; }

/* ── COL 2 : ÉVALUATION ── */
.ei-col-eval { width:360px; flex-shrink:0; border-right:2px solid #e2e8f0; display:flex; flex-direction:column; background:#fff; overflow-y:auto; }
.ei-eval-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; text-align:center; padding:24px; }

.ei-risk-info { padding:12px 14px 10px; border-bottom:1px solid #f1f5f9; flex-shrink:0; }
.ei-code-badge { font-family:monospace; font-size:.65rem; font-weight:700; background:#e0e7ff; color:#4338ca; padding:2px 6px; border-radius:5px; white-space:nowrap; flex-shrink:0; }
.ei-ctx { font-size:.68rem; color:#64748b; }

/* Niveaux scroll */
.ei-col-eval > .ei-section-lbl, .ei-col-eval > .ei-levels-stack, .ei-col-eval > .ei-criteria-bar, .ei-col-eval > .ei-result, .ei-col-eval > div.mt-2 { flex-shrink:0; }
.ei-section-lbl { display:flex; align-items:center; gap:6px; font-size:.72rem; font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:.04em; padding:10px 14px 6px; flex-shrink:0; }
.ei-step { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:50%; background:#1e293b; color:#fff; font-size:.62rem; font-weight:800; flex-shrink:0; }

.ei-criteria-bar { display:flex; flex-direction:column; gap:4px; padding:4px 14px 8px; flex-shrink:0; border-bottom:1px solid #f1f5f9; }
.ei-criteria-lbl { font-size:.65rem; font-weight:600; color:#475569; }
.ei-criteria-chip { font-size:.65rem; font-weight:600; padding:4px 10px; border-radius:12px; border:1px solid #e2e8f0; background:#f8fafc; color:#475569; cursor:pointer; transition:all .1s; }
.ei-criteria-chip:hover { background:#eff6ff; border-color:#bfdbfe; }
.ei-criteria-chip--on   { background:#1e293b; color:#fff; border-color:#1e293b; }
.ei-criteria-wait { font-size:.68rem; color:#94a3b8; text-align:center; padding:12px 14px; background:#f8fafc; border-radius:6px; margin:0 14px; }

.ei-levels-stack { display:flex; flex-direction:column; gap:3px; padding:0 14px; }
.ei-level {
  display:flex; align-items:center; gap:8px;
  padding:7px 10px; border-radius:6px; border:1.5px solid;
  cursor:pointer; transition:all .12s;
}
.ei-level:hover { filter:brightness(.96); }
.ei-level--sel  { box-shadow:0 2px 6px rgba(0,0,0,.08); }
.ei-level-dot   { width:28px; height:28px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:800; }
.ei-level-name  { font-size:.78rem; font-weight:700; color:#1e293b; flex:1; }
.ei-level-hint  { font-size:.65rem; color:#64748b; }
.ei-level-criteria { font-size:.65rem; color:#475569; margin-top:2px; background:#f1f5f9; border-radius:3px; padding:2px 5px; border-left:2px solid #94a3b8; line-height:1.4; }
.ei-sel-icon    { font-size:15px; color:#16a34a; flex-shrink:0; }

.ei-result-score  { font-size:2.6rem; font-weight:900; color:#1e293b; min-width:60px; text-align:center; }
.ei-result-zone   { font-size:1rem; font-weight:700; }
.ei-result-detail { font-size:.72rem; color:#64748b; margin-top:3px; }

.ei-eval-footer { padding:10px 14px; border-top:2px solid #f1f5f9; flex-shrink:0; display:flex; gap:8px; background:#fafafa; position:sticky; bottom:0; }
.ei-flash { padding:7px 14px; font-size:.75rem; font-weight:600; display:flex; align-items:center; flex-shrink:0; }
.ei-flash--ok  { background:#dcfce7; color:#15803d; }
.ei-flash--err { background:#fee2e2; color:#dc2626; }
.fl-enter-active,.fl-leave-active { transition:all .2s; }
.fl-enter-from,.fl-leave-to { opacity:0; transform:translateY(4px); }

/* ── COL 3 : MATRICE ── */
.ei-col-matrix { flex:1; min-width:0; display:flex; flex-direction:column; background:#fafafa; overflow:hidden; }

.ei-matrix-toolbar { display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding:8px 12px; border-bottom:1px solid #e2e8f0; flex-shrink:0; background:#fff; }
.ei-zoom-btn { width:24px; height:24px; border-radius:5px; border:1px solid #e2e8f0; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:11px; color:#475569; transition:all .1s; }
.ei-zoom-btn:hover { background:#f1f5f9; }
.ei-zoom-val { font-size:.65rem; font-weight:600; color:#475569; min-width:36px; text-align:center; font-family:monospace; }

.btn-xs { padding:2px 7px; font-size:.63rem; border-radius:5px; border:1px solid; cursor:pointer; }
.ei-filter     { background:#f1f5f9; color:#64748b; border-color:#e2e8f0; }
.ei-filter:hover { background:#e2e8f0; }
.ei-filter--on { background:#1e293b; color:#fff; border-color:#1e293b; }

.ei-matrix-scroll { flex:1; overflow:auto; padding:6px; }
.ei-matrix-zoom-wrap { display:inline-block; transition:transform .15s; }

/* Table matrice */
.ei-mat { border-collapse:collapse; }
.ei-mat-corner { background:#f8fafc; border:1px solid #e5e7eb; padding:.3rem .4rem; min-width:80px; }
.ei-ci,.ei-cf { display:block; font-size:.55rem; font-weight:700; text-transform:uppercase; color:#6b7280; }
.ei-ci { text-align:right; }.ei-cf { text-align:center; margin-top:2px; }
.ei-mat-fhd { background:#f8fafc; border:1px solid #e5e7eb; padding:.2rem .15rem; min-width:66px; text-align:center; vertical-align:top; }
.ei-mat-ihd { background:#f8fafc; border:1px solid #e5e7eb; padding:.2rem .3rem; white-space:nowrap; display:flex; align-items:center; gap:5px; }
.ei-mat-hdot { width:20px; height:20px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:.62rem; font-weight:700; margin:0 auto 2px; }
.ei-mat-hlbl { font-size:.62rem; font-weight:600; }
.ei-mat-hrec { font-size:.52rem; color:#94a3b8; }
.ei-mat-ilbl { font-size:.63rem; font-weight:600; }

.ei-mat-cell { width:66px; min-width:66px; height:62px; border:1px solid rgba(255,255,255,.2); cursor:default; transition:filter .1s; vertical-align:top; padding:2px 3px; position:relative; }
.ei-mat-cell--active { z-index:3 !important; }
.ei-mat-score { font-size:.68rem; font-weight:800; text-align:center; opacity:.8; }
.ei-mat-zlbl  { font-size:.48rem; font-weight:600; text-align:center; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:62px; }
.ei-mat-chips { display:flex; flex-wrap:wrap; gap:2px; justify-content:center; margin-top:2px; }
.ei-chip {
  font-size:.48rem; font-weight:700; padding:1px 3px; border-radius:3px;
  background:rgba(255,255,255,.85); color:#1e293b;
  border:1px solid rgba(0,0,0,.1); cursor:pointer; white-space:nowrap;
  transition:all .1s;
}
.ei-chip:hover { background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.15); }
.ei-chip--sel  { background:#1e293b !important; color:#fff !important; }
.ei-mat-cursor { position:absolute; top:1px; right:2px; font-size:11px; color:#fff; opacity:.9; filter:drop-shadow(0 1px 2px rgba(0,0,0,.4)); }

/* LÉGENDE */
.ei-legend { display:flex; flex-wrap:wrap; gap:5px; padding:8px 12px; border-top:1px solid #e2e8f0; flex-shrink:0; background:#fff; }
.ei-legend-item { display:flex; align-items:center; gap:5px; padding:3px 8px; border-radius:6px; border-left:3px solid; }
.ei-legend-dot  { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.ei-legend-n    { font-size:.6rem; color:#94a3b8; margin-left:4px; }

.min-w-0 { min-width:0; }
.ms-auto { margin-left:auto; }
.btn-sm { font-size:.72rem; padding:.15rem .5rem; }

@media(max-width:1024px) {
  .ei-body { flex-direction:column; }
  .ei-col-list,.ei-col-eval { width:100%; max-height:250px; border-right:none; border-bottom:2px solid #e2e8f0; }
}
</style>