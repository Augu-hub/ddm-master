<template>
  <VerticalLayout>
    <div class="rl-page">

      <!-- EN-TÊTE -->
      <div class="rl-header">
        <div class="d-flex align-items-center gap-3">
          <div class="rl-header-icon"><i class="ti ti-books"></i></div>
          <div>
            <h4 class="mb-0 fw-bold">Bibliothèque des risques</h4>
            <small class="text-muted">
              Sélectionnez un risque dans la liste · complétez son analyse à droite
            </small>
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

        <!-- ═══ COLONNE GAUCHE : LISTE ═══ -->
        <div class="rl-list-col">
          <div class="rl-list-header">
            <input v-model="search" type="text" class="form-control form-control-sm"
                   placeholder="Rechercher…"/>
            <small class="text-muted mt-1 d-block">
              {{ filteredRisks.length }} risque(s)
            </small>
          </div>

          <div class="rl-list">
            <template v-for="macro in groupedTree" :key="macro.id">
              <div class="rl-group-macro">
                <span class="rl-macro-badge" :style="{ background: macroColor(macro.kind) }">
                  {{ macroKindLabel(macro.kind) }}
                </span>
                {{ macro.name }}
              </div>

              <template v-for="process in macro.processes" :key="process.id">
                <div class="rl-group-process">
                  <span class="rl-proc-code me-1">{{ process.code }}</span>
                  {{ process.name }}
                </div>

                <template v-for="activity in process.activities" :key="activity.id">
                  <div class="rl-group-activity">
                    <span class="rl-act-code me-1">{{ activity.code }}</span>
                    {{ activity.name }}
                  </div>

                  <div v-for="risk in activity.risks" :key="risk.id"
                       :class="['rl-risk-item', {
                         'rl-risk-item--active':     selectedRisk?.id === risk.id,
                         'rl-risk-item--complete':   isComplete(risk),
                         'rl-risk-item--incomplete': !isComplete(risk),
                       }]"
                       @click="selectRisk(risk)">
                    <div class="d-flex align-items-start gap-2">
                      <div class="rl-risk-dot flex-shrink-0 mt-1"
                           :class="isComplete(risk) ? 'rl-risk-dot--ok' : 'rl-risk-dot--pending'">
                      </div>
                      <div class="flex-grow-1 overflow-hidden">
                        <div class="rl-risk-code">{{ risk.code_risk }}</div>
                        <div class="rl-risk-label text-truncate">{{ risk.libelle }}</div>
                        <div class="d-flex gap-1 mt-1 flex-wrap">
                          <span v-if="risk.zone_label" class="rl-zone-badge"
                                :style="{ background: (risk.zone_color||'#94a3b8')+'20', color: risk.zone_color||'#94a3b8', borderColor: (risk.zone_color||'#94a3b8')+'50' }">
                            {{ risk.zone_label }}
                          </span>
                          <span v-if="!isComplete(risk)" class="rl-incomplete-tag">
                            <i class="ti ti-pencil me-1"></i>À compléter
                          </span>
                          <span v-else class="rl-complete-tag">
                            <i class="ti ti-check me-1"></i>Complet
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </template>
            </template>
          </div>
        </div>

        <!-- ═══ COLONNE DROITE : ANALYSE TABULAIRE ═══ -->
        <div class="rl-form-col">

          <!-- Aucun risque sélectionné -->
          <div v-if="!selectedRisk" class="rl-form-empty">
            <i class="ti ti-hand-finger d-block fs-1 mb-3 opacity-20"></i>
            <div class="fw-semibold mb-1">Sélectionnez un risque</div>
            <small class="text-muted">
              Cliquez sur un risque dans la liste pour compléter son analyse
            </small>
          </div>

          <!-- Risque sélectionné -->
          <template v-else>

            <!-- En-tête risque -->
            <div class="rl-form-header">
              <div class="d-flex align-items-start gap-2 mb-2">
                <span class="rl-code flex-shrink-0">{{ selectedRisk.code_risk }}</span>
                <h6 class="fw-bold mb-0 lh-sm">{{ selectedRisk.libelle }}</h6>
              </div>
              <div class="rl-context-box mb-2">
                <span class="rl-proc-code me-1">{{ selectedRisk.process_code }}</span>
                {{ selectedRisk.process_name }}
                <i class="ti ti-chevron-right mx-1 opacity-40"></i>
                <span class="rl-act-code me-1">{{ selectedRisk.activity_code }}</span>
                {{ selectedRisk.activity_name }}
              </div>
              <!-- Barre complétion -->
              <div class="rl-progress-wrap">
                <div class="rl-progress-bar">
                  <div class="rl-progress-fill"
                       :style="{ width: completionPct + '%', background: completionColor }"></div>
                </div>
                <small :style="{ color: completionColor }" class="fw-semibold">
                  {{ completionPct }}% complété
                  <i v-if="completionPct === 100" class="ti ti-check-circle ms-1"></i>
                </small>
              </div>
            </div>

            <!-- ── TABLEAU FORMAT EXCEL ── -->
            <div class="rl-form-body">

              <!-- Criticité lecture seule -->
              <div v-if="selectedRisk.zone_label" class="rl-crit-bar mb-2">
                <i class="ti ti-flame me-1 text-danger"></i>
                <span class="rl-crit-lbl">Criticité :</span>
                <span class="rl-zone-badge-lg"
                      :style="{ background: (selectedRisk.zone_color||'#94a3b8')+'20', color: selectedRisk.zone_color||'#94a3b8', borderColor: (selectedRisk.zone_color||'#94a3b8')+'55' }">
                  {{ selectedRisk.zone_label }}
                  <small v-if="selectedRisk.criticality_score" class="ms-1 opacity-75">({{ selectedRisk.criticality_score }})</small>
                </span>
                <small class="text-muted ms-2">
                  Impact : {{ selectedRisk.impact_label ?? '—' }} · Fréquence : {{ selectedRisk.frequency_label ?? '—' }}
                </small>
              </div>

              <!-- Tableau analyse -->
              <div class="rl-table-wrap">
                <table class="rl-table">
                  <thead>
                    <tr>
                      <th class="th-obj">Objectifs</th>
                      <th class="th-proc">Processus / Activité</th>
                      <th class="th-n">N°</th>
                      <th class="th-risk">Risques retenus</th>
                      <th class="th-code th-yellow">CODE Type</th>
                      <th class="th-yellow">Cause probable / Source du risque (département)</th>
                      <th class="th-yellow">Entités / Partenaires impliqués</th>
                      <th class="th-yellow">Conséquences</th>
                      <th class="th-yellow">Conséquences sur d'autres processus</th>
                      <th class="th-yellow th-yn">Statut : Réalisé O/N</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <!-- Objectifs -->
                      <td class="td-obj">{{ selectedRisk.objective ?? '—' }}</td>

                      <!-- Processus / Activité -->
                      <td class="td-proc">
                        <div class="fw-semibold" style="font-size:11px;color:#276221">{{ selectedRisk.process_name }}</div>
                        <div style="font-size:10px;color:#555;margin-top:2px">{{ selectedRisk.activity_name }}</div>
                      </td>

                      <!-- N° -->
                      <td class="td-n">{{ riskIndexInActivity }}</td>

                      <!-- Risque retenu + zone -->
                      <td class="td-risk">
                        <div style="font-size:11.5px;font-weight:600;line-height:1.3">{{ selectedRisk.libelle }}</div>
                        <span v-if="selectedRisk.zone_label" class="rl-zone-sm"
                              :style="{ background: (selectedRisk.zone_color||'#94a3b8')+'18', color: selectedRisk.zone_color||'#94a3b8', borderColor: (selectedRisk.zone_color||'#94a3b8')+'55' }">
                          {{ selectedRisk.zone_label }}
                          <small v-if="selectedRisk.criticality_score">({{ selectedRisk.criticality_score }})</small>
                        </span>
                      </td>

                      <!-- CODE Type -->
                      <td class="td-code td-input">
                        <select v-model="form.nomenclature_id" class="ta-sel">
                          <option :value="null">—</option>
                          <option v-for="n in props.nomenclatures" :key="n.id" :value="n.id">
                            {{ n.label }}
                          </option>
                        </select>
                      </td>

                      <!-- Cause probable -->
                      <td class="td-input">
                        <textarea v-model="form.causes" class="ta"
                                  placeholder="Causes identifiées…"></textarea>
                      </td>

                      <!-- Entités / Partenaires -->
                      <td class="td-input">
                        <textarea v-model="form.entite_partenaire_impliquee" class="ta"
                                  placeholder="Entités, partenaires…"></textarea>
                      </td>

                      <!-- Conséquences -->
                      <td class="td-input">
                        <textarea v-model="form.consequences" class="ta"
                                  placeholder="Conséquences directes…"></textarea>
                      </td>

                      <!-- Conséquences autres processus -->
                      <td class="td-input">
                        <textarea v-model="form.consequences_autres_processus" class="ta"
                                  placeholder="Impacts en cascade…"></textarea>
                      </td>

                      <!-- Statut Réalisé O/N -->
                      <td class="td-input td-yn">
                        <div class="yn-wrap">
                          <button :class="['yn-btn', form.risque_realise ? 'yn-oui' : '']"
                                  @click="form.risque_realise = true">O</button>
                          <button :class="['yn-btn', !form.risque_realise ? 'yn-non' : '']"
                                  @click="form.risque_realise = false">N</button>
                        </div>
                      </td>
                    </tr>

                    <!-- Ligne champs supplémentaires : Coût + Contrôles + Owner + Plan -->
                 

                    
                    
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Actions fixes en bas -->
            <div class="rl-form-footer">
              <button class="btn btn-outline-secondary btn-sm"
                      @click="confirmRemove(selectedRisk)">
                <i class="ti ti-book-off me-1"></i>Retirer
              </button>
              <Link :href="route('risk.core.risks.edit', selectedRisk.id)"
                    class="btn btn-outline-primary btn-sm">
                <i class="ti ti-pencil me-1"></i>Fiche complète
              </Link>
              <button class="btn btn-primary btn-sm flex-fill" :disabled="saveSubmitting"
                      @click="submitSave">
                <span v-if="saveSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="ti ti-device-floppy me-1"></i>
                Enregistrer l'analyse
              </button>
            </div>

            <!-- Flash -->
            <Transition name="fl">
              <div v-if="flashMsg" class="rl-flash" :class="flashOk ? 'rl-flash--ok' : 'rl-flash--err'">
                <i :class="flashOk ? 'ti ti-check' : 'ti ti-alert-circle'" class="me-1"></i>
                {{ flashMsg }}
              </div>
            </Transition>

          </template>
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
        <button class="btn btn-outline-secondary btn-sm" @click="showRemoveModal = false">Annuler</button>
        <button class="btn btn-secondary btn-sm" :disabled="removeSubmitting" @click="doRemove">
          <span v-if="removeSubmitting" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-book-off me-1"></i>Retirer
        </button>
      </div>
    </BModal>

  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
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

// ── Stats ─────────────────────────────────────────────────────────────────────
const statCards = computed(() => [
  { label: 'Bibliothèque',   value: props.stats.total_bibliotheque ?? 0, icon: 'ti-books',        color: 'primary'   },
  { label: 'Actifs',         value: props.stats.total_actif        ?? 0, icon: 'ti-shield-check', color: 'success'   },
  { label: 'Brouillons',     value: props.stats.total_draft        ?? 0, icon: 'ti-shield-half',  color: 'warning'   },
  { label: 'Registre total', value: props.stats.total_registre     ?? 0, icon: 'ti-shield',       color: 'secondary' },
])

// ── Recherche & arbre filtré ──────────────────────────────────────────────────
const search = ref('')

const filteredRisks = computed(() => {
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
        const risks = activity.risks.filter(r => ids.has(r.id))
        if (risks.length) activities.push({ ...activity, risks })
      }
      if (activities.length) processes.push({ ...process, activities })
    }
    if (processes.length) result.push({ ...macro, processes })
  }
  return result
})

// ── Sélection risque ──────────────────────────────────────────────────────────
const selectedRisk   = ref(null)
const saveSubmitting = ref(false)
const flashMsg       = ref('')
const flashOk        = ref(true)
let   flashTimer     = null

const emptyForm = () => ({
  nomenclature_id:               null,
  causes:                        '',
  entite_partenaire_impliquee:   '',
  consequences:                  '',
  consequences_autres_processus: '',
  cout_consequences:             '',
  controles_existants:           '',
  owner:                         '',
  risque_realise:                false,
  plan_traitement:               '',
})
const form = ref(emptyForm())

function selectRisk(risk) {
  selectedRisk.value = risk
  form.value = {
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
  }
}

// Numéro du risque dans son activité
const riskIndexInActivity = computed(() => {
  if (!selectedRisk.value) return 1
  for (const macro of props.tree) {
    for (const process of macro.processes) {
      for (const activity of process.activities) {
        const idx = activity.risks.findIndex(r => r.id === selectedRisk.value.id)
        if (idx !== -1) return idx + 1
      }
    }
  }
  return 1
})

// ── Complétion ────────────────────────────────────────────────────────────────
const requiredForComplete = ['causes', 'consequences', 'entite_partenaire_impliquee']
const allFields = ['causes', 'consequences', 'entite_partenaire_impliquee',
                   'controles_existants', 'owner', 'nomenclature_id']

const isComplete = risk =>
  requiredForComplete.every(f => risk[f] && String(risk[f]).trim())

const completionPct = computed(() => {
  if (!selectedRisk.value) return 0
  const filled = allFields.filter(f => {
    const v = form.value[f]
    return v !== null && v !== '' && v !== undefined
  })
  return Math.round((filled.length / allFields.length) * 100)
})

const completionColor = computed(() => {
  if (completionPct.value >= 100) return '#16a34a'
  if (completionPct.value >= 50)  return '#d97706'
  return '#dc2626'
})

// ── Sauvegarde ────────────────────────────────────────────────────────────────
function submitSave() {
  if (!selectedRisk.value) return
  saveSubmitting.value = true
  router.put(route('risk.core.risk-library.update', selectedRisk.value.id), form.value, {
    preserveScroll: true,
    preserveState:  true,
    onSuccess: () => {
      showFlash('Analyse enregistrée', true)
      const idx = props.risks.findIndex(r => r.id === selectedRisk.value.id)
      if (idx !== -1) Object.assign(props.risks[idx], form.value)
    },
    onError: () => showFlash('Erreur lors de la sauvegarde', false),
    onFinish: () => { saveSubmitting.value = false },
  })
}

function showFlash(msg, ok) {
  if (flashTimer) clearTimeout(flashTimer)
  flashMsg.value = msg; flashOk.value = ok
  flashTimer = setTimeout(() => { flashMsg.value = '' }, 3000)
}

// ── Retrait ───────────────────────────────────────────────────────────────────
const showRemoveModal  = ref(false)
const targetRisk       = ref(null)
const removeSubmitting = ref(false)

function confirmRemove(risk) { targetRisk.value = risk; showRemoveModal.value = true }
function doRemove() {
  removeSubmitting.value = true
  router.post(route('risk.core.risk-library.remove-from-library', targetRisk.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showRemoveModal.value = false
      if (selectedRisk.value?.id === targetRisk.value.id) selectedRisk.value = null
    },
    onFinish: () => { removeSubmitting.value = false },
  })
}

// ── Helpers visuels ───────────────────────────────────────────────────────────
const macroColor = kind => ({
  Direction: '#9333ea', Réalisation: '#16a34a', Support: '#2563eb'
})[kind] ?? '#64748b'

const macroKindLabel = kind => ({
  Direction: 'DIR', Réalisation: 'OP', Support: 'SUP'
})[kind] ?? (kind ?? '?')
</script>

<style scoped>
.rl-page { padding:18px; display:flex; flex-direction:column; height:calc(100vh - 60px); }

/* HEADER */
.rl-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:12px; flex-shrink:0; }
.rl-header-icon { width:40px; height:40px; border-radius:9px; flex-shrink:0; background:linear-gradient(135deg,#1e293b,#1e3a5f); display:flex; align-items:center; justify-content:center; color:#93c5fd; font-size:18px; }

/* STATS */
.rl-stat { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; border:1px solid transparent; }
.rl-stat i { font-size:1.2rem; flex-shrink:0; }
.rl-stat-val { font-size:1.15rem; font-weight:800; line-height:1; }
.rl-stat-lbl { font-size:.63rem; color:#64748b; }
.rl-stat--primary   { background:#eff6ff; border-color:#bfdbfe; } .rl-stat--primary i   { color:#2563eb; }
.rl-stat--success   { background:#f0fdf4; border-color:#bbf7d0; } .rl-stat--success i   { color:#16a34a; }
.rl-stat--warning   { background:#fffbeb; border-color:#fde68a; } .rl-stat--warning i   { color:#d97706; }
.rl-stat--secondary { background:#f8fafc; border-color:#e2e8f0; } .rl-stat--secondary i { color:#475569; }

.rl-empty { text-align:center; padding:48px; color:#94a3b8; }

/* SPLIT PANEL */
.rl-split { display:flex; gap:0; flex:1; min-height:0; border-radius:10px; border:2px solid #e2e8f0; overflow:hidden; }

/* LISTE GAUCHE */
.rl-list-col { width:300px; flex-shrink:0; border-right:2px solid #e2e8f0; display:flex; flex-direction:column; background:#f8fafc; }
.rl-list-header { padding:10px 12px; border-bottom:1px solid #e2e8f0; flex-shrink:0; }
.rl-list { flex:1; overflow-y:auto; }

.rl-group-macro    { padding:7px 12px 4px; font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#334155; display:flex; align-items:center; gap:6px; background:#f1f5f9; border-bottom:1px solid #e2e8f0; margin-top:2px; }
.rl-group-process  { padding:5px 14px; font-size:.68rem; font-weight:700; color:#475569; background:#f8fafc; border-bottom:1px solid #f1f5f9; }
.rl-group-activity { padding:4px 18px; font-size:.65rem; font-weight:600; color:#64748b; background:#fff; border-bottom:1px solid #f8fafc; }

.rl-macro-badge { display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:.55rem; font-weight:800; padding:1px 6px; border-radius:4px; text-transform:uppercase; letter-spacing:.06em; flex-shrink:0; }
.rl-proc-code   { display:inline-block; font-family:monospace; font-size:.6rem; background:#e2e8f0; color:#475569; padding:0 4px; border-radius:3px; }
.rl-act-code    { display:inline-block; font-family:monospace; font-size:.6rem; background:#dcfce7; color:#166534; padding:0 4px; border-radius:3px; }
.rl-code        { font-family:monospace; font-size:.65rem; font-weight:700; background:#e0e7ff; color:#4338ca; padding:1px 5px; border-radius:4px; white-space:nowrap; flex-shrink:0; }

.rl-risk-item { padding:8px 12px 8px 18px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background .1s; }
.rl-risk-item:hover            { background:#eff6ff; }
.rl-risk-item--active          { background:#dbeafe !important; border-left:3px solid #3b82f6; }
.rl-risk-item--incomplete      { border-left:3px solid #f59e0b; }
.rl-risk-item--complete        { border-left:3px solid #22c55e; }
.rl-risk-item--active.rl-risk-item--incomplete { border-left:3px solid #3b82f6; }
.rl-risk-item--active.rl-risk-item--complete   { border-left:3px solid #3b82f6; }

.rl-risk-dot          { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.rl-risk-dot--ok      { background:#22c55e; }
.rl-risk-dot--pending { background:#f59e0b; }
.rl-risk-code  { font-family:monospace; font-size:.62rem; font-weight:700; color:#4338ca; }
.rl-risk-label { font-size:.72rem; font-weight:600; color:#1e293b; line-height:1.3; }
.rl-zone-badge { font-size:.6rem; font-weight:600; padding:1px 6px; border-radius:10px; border:1px solid; white-space:nowrap; }
.rl-incomplete-tag { font-size:.58rem; background:#fef3c7; color:#92400e; padding:1px 5px; border-radius:8px; }
.rl-complete-tag   { font-size:.58rem; background:#dcfce7; color:#15803d; padding:1px 5px; border-radius:8px; }

/* FORMULAIRE DROITE */
.rl-form-col { flex:1; min-width:0; display:flex; flex-direction:column; background:#fff; overflow:hidden; }
.rl-form-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; text-align:center; padding:32px; }

.rl-form-header { padding:12px 16px 10px; border-bottom:1px solid #f1f5f9; flex-shrink:0; }
.rl-form-body   { flex:1; overflow:auto; padding:12px 16px; }
.rl-form-footer { padding:10px 16px; border-top:2px solid #f1f5f9; flex-shrink:0; display:flex; gap:8px; align-items:center; background:#fafafa; }

.rl-context-box  { font-size:.72rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:5px 9px; color:#475569; }
.rl-progress-wrap { display:flex; align-items:center; gap:8px; }
.rl-progress-bar  { height:5px; background:#e2e8f0; border-radius:4px; overflow:hidden; flex:1; }
.rl-progress-fill { height:100%; border-radius:4px; transition:width .3s, background .3s; }

/* CRITICITÉ bar */
.rl-crit-bar { display:flex; align-items:center; gap:6px; flex-wrap:wrap; font-size:.72rem; padding:5px 0; }
.rl-crit-lbl { font-weight:600; color:#475569; }
.rl-zone-badge-lg { font-size:.72rem; font-weight:600; padding:2px 9px; border-radius:12px; border:1px solid; }
.rl-zone-sm       { font-size:.6rem; font-weight:600; padding:1px 6px; border-radius:8px; border:1px solid; display:inline-block; margin-top:3px; }

/* ── TABLEAU ANALYSE (format Excel) ── */
.rl-table-wrap { overflow-x:auto; border-radius:6px; }
.rl-table { border-collapse:collapse; width:100%; min-width:900px; font-size:11.5px; }

/* En-têtes */
.rl-table thead tr { background:#e2efda; }
.rl-table th { border:1px solid #b8b8b8; padding:5px 6px; font-weight:700; font-size:11px; color:#1a1a1a; text-align:center; line-height:1.3; vertical-align:middle; }
.th-obj  { background:#c6efce; color:#276221; width:12%; }
.th-proc { background:#d9ead3; color:#276221; width:14%; }
.th-n    { background:#d9ead3; color:#276221; width:4%; }
.th-risk { background:#f4cccc; color:#7f0000; width:16%; }
.th-code { width:7%; }
.th-yellow { background:#ffff00; color:#333; }
.th-yn     { width:8%; }

/* Cellules données */
.rl-table td { border:1px solid #b8b8b8; padding:4px 5px; vertical-align:top; }
.td-obj  { background:#f1f8eb; font-size:11px; color:#276221; font-weight:500; vertical-align:middle; line-height:1.4; }
.td-proc { background:#e8f5e2; font-size:11px; vertical-align:middle; }
.td-n    { background:#f9f9f9; text-align:center; font-weight:700; color:#444; vertical-align:middle; font-size:12px; }
.td-risk { background:#fff; vertical-align:middle; }
.td-code { background:#fffde7; vertical-align:middle; }

/* Cellules saisie */
.td-input { background:#fffff0; vertical-align:top; padding:3px !important; }
.td-yn    { vertical-align:middle !important; text-align:center; }

/* Lignes extra */
.tr-extra td { border:1px solid #b8b8b8; padding:4px 5px; }
.td-extra-meta { background:#f1f5f9; font-size:11px; color:#475569; font-weight:600; vertical-align:middle; }
.extra-label { display:flex; align-items:center; font-size:11px; font-weight:600; color:#374151; }

/* Inputs intégrés dans les cellules */
.ta {
  width:100%; min-height:60px; resize:vertical;
  padding:3px 5px; font-size:11px; line-height:1.4;
  border:0.5px solid #d1d5db; border-radius:3px;
  background:transparent; color:#1e293b;
  font-family:inherit;
}
.ta:focus { outline:none; border-color:#3b82f6; background:#fff; }

.ta-inp {
  width:100%; padding:3px 5px; font-size:11px;
  border:0.5px solid #d1d5db; border-radius:3px;
  background:transparent; color:#1e293b;
  font-family:inherit; height:26px;
}
.ta-inp:focus { outline:none; border-color:#3b82f6; background:#fff; }

.ta-sel {
  width:100%; padding:2px 4px; font-size:11px;
  border:0.5px solid #d1d5db; border-radius:3px;
  background:transparent; color:#1e293b;
  font-family:inherit; height:26px;
}
.ta-sel:focus { outline:none; border-color:#3b82f6; }

/* O/N toggle */
.yn-wrap { display:flex; gap:4px; justify-content:center; }
.yn-btn  { flex:1; padding:3px 5px; font-size:11px; font-weight:600; border:0.5px solid #d1d5db; border-radius:3px; cursor:pointer; background:#f8fafc; color:#64748b; transition:all .15s; }
.yn-oui  { background:#fee2e2; color:#b91c1c; border-color:#fca5a5; }
.yn-non  { background:#dcfce7; color:#15803d; border-color:#86efac; }

/* Flash */
.rl-flash { position:sticky; bottom:0; margin:0 -16px -10px; padding:8px 16px; font-size:.75rem; font-weight:600; display:flex; align-items:center; }
.rl-flash--ok  { background:#dcfce7; color:#15803d; }
.rl-flash--err { background:#fee2e2; color:#dc2626; }
.fl-enter-active,.fl-leave-active { transition:all .2s; }
.fl-enter-from,.fl-leave-to       { opacity:0; transform:translateY(4px); }

/* Form buttons */
.btn-sm { font-size:.72rem; padding:.15rem .5rem; }

/* Responsive */
@media (max-width: 768px) {
  .rl-split    { flex-direction:column; }
  .rl-list-col { width:100%; max-height:280px; border-right:none; border-bottom:2px solid #e2e8f0; }
}
</style>