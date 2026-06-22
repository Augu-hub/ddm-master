<template>
  <VerticalLayout>
    <div class="page">

      <div class="page-hdr">
        <div class="page-hdr-left">
          <div class="hdr-icon hdr-icon--ctrl"><i class="ti ti-shield-lock"></i></div>
          <div>
            <h1>Mise sous contrôle</h1>
            <p>Définissez la procédure, le référentiel et le responsable pour chaque risque évalué</p>
          </div>
        </div>
        <div class="page-hdr-right">
          <div class="stat-row">
            <div class="stat"><span>{{ stats?.with_inherent || 0 }}</span><small>Inhérents</small></div>
            <div class="stat stat-ok"><span>{{ localWithControlCount }}</span><small>Contrôlés</small></div>
            <div class="stat stat-nd"><span>{{ (stats?.with_inherent || 0) - localWithControlCount }}</span><small>En attente</small></div>
          </div>
          <div class="nav-links">
            <Link :href="route('risk.core.evaluation.inherente')" class="btn-nav"><i class="ti ti-arrow-left"></i> Inhérent</Link>
            <button
              class="btn-nav btn-nav-next"
              @click="goToResiduel"
              :disabled="!hasControlledRisks"
            >
              Résiduel <i class="ti ti-arrow-right"></i>
              <span v-if="!hasControlledRisks" class="tooltip">Aucun risque contrôlé</span>
            </button>
          </div>
        </div>
      </div>

      <div class="table-wrap">
        <div class="table-toolbar">
          <div class="tt-left">
            <div class="search-box"><i class="ti ti-search"></i><input v-model="searchQ" placeholder="Rechercher…"/></div>
            <div class="filter-tabs">
              <button :class="['ftab',filter==='all'?'ftab--on':'']" @click="filter='all'">Tous <span>{{ withInherent.length }}</span></button>
              <button :class="['ftab',filter==='done'?'ftab--on':'']" @click="filter='done'"><i class="ti ti-check"></i> Contrôlés <span>{{ withControl.length }}</span></button>
              <button :class="['ftab',filter==='todo'?'ftab--on':'']" @click="filter='todo'"><i class="ti ti-clock"></i> À traiter <span>{{ withoutControl.length }}</span></button>
            </div>
          </div>
        </div>
        <div class="table-scroll">
          <table class="risk-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Risque</th>
                <th>Processus / Activité</th>
                <th>Objectif du processus</th>
                <th class="th-score-c">Score I.</th>
                <th class="th-score-c">Zone I.</th>
                <th>Procédure</th>
                <th>Référentiel</th>
                <th>Responsable</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="macro in displayTree" :key="macro.id">
                <tr class="tr-macro">
                  <td colspan="10">
                    <span class="macro-badge" :style="{background:macroColor(macro.kind)}">{{ macroKindLabel(macro.kind) }}</span>
                    {{ macro.name }}
                  </td>
                </tr>
                <template v-for="proc in macro.processes" :key="proc.id">
                  <template v-for="act in proc.activities" :key="act.id">
                    <tr v-for="risk in act.risks" :key="risk.id"
                        :class="['tr-risk',selectedRisk?.id===risk.id?'tr-risk--sel':'',risk.has_control?'tr-risk--done':'', !risk.criticality_score?'tr-risk--locked':'']">
                      <td><span class="code-badge">{{ risk.code_risk }}</span></td>
                      <td class="td-name"><div class="risk-name">{{ risk.libelle }}</div></td>
                      <td>
                        <div class="proc-line"><span class="proc-code">{{ proc.code }}</span> {{ proc.name }}</div>
                        <div class="act-line"><span class="act-code">{{ act.code }}</span> {{ act.name }}</div>
                      </td>
                      <td class="td-obj"><span class="obj-text">{{ proc.objective||act.objective||'—' }}</span></td>
                      <td class="td-sc">
                        <span v-if="risk.criticality_score" class="big-score" :style="{background:risk.zone_color||'#6b7280'}">{{ risk.criticality_score }}</span>
                        <span v-else class="nd">—</span>
                      </td>
                      <td>
                        <span v-if="risk.zone_label" class="zone-pill" :style="{background:(risk.zone_color||'#6b7280')+'18',color:risk.zone_color||'#6b7280',border:'1px solid '+(risk.zone_color||'#6b7280')+'44'}">{{ risk.zone_label }}</span>
                      </td>
                      <td class="td-proc-ctrl td-clickable" :class="!risk.criticality_score?'td-clickable--off':''" @click="risk.criticality_score&&openModal(risk)" title="Cliquer pour définir le contrôle">
                        <span v-if="risk.control_procedure" class="ctrl-text">{{ truncate(risk.control_procedure,50) }}</span>
                        <span v-else-if="risk.criticality_score" class="nd-cell--edit"><i class="ti ti-plus"></i> Définir</span>
                        <span v-else class="nd">Inhérent requis</span>
                      </td>
                      <td class="td-clickable" :class="!risk.criticality_score?'td-clickable--off':''" @click="risk.criticality_score&&openModal(risk)" title="Cliquer pour définir le contrôle">
                        <span v-if="risk.referential_type" class="ref-badge">{{ risk.referential_type }}</span>
                        <span v-else-if="risk.criticality_score" class="nd-cell--edit"><i class="ti ti-plus"></i> Définir</span>
                        <span v-else class="nd">—</span>
                      </td>
                      <td class="td-clickable" :class="!risk.criticality_score?'td-clickable--off':''" @click="risk.criticality_score&&openModal(risk)" title="Cliquer pour définir le contrôle">
                        <span v-if="risk.owner" class="owner-text"><i class="ti ti-user"></i> {{ risk.owner }}</span>
                        <span v-else-if="risk.criticality_score" class="nd-cell--edit"><i class="ti ti-plus"></i> Définir</span>
                        <span v-else class="nd">—</span>
                      </td>
                      <td>
                        <span :class="['status-pill', risk.has_control?'sp-done':'sp-todo']">
                          <i :class="risk.has_control?'ti ti-shield-check':'ti ti-clock'"></i>
                          {{ risk.has_control?'Contrôlé':'À définir' }}
                        </span>
                      </td>
                    </tr>
                  </template>
                </template>
              </template>
              <tr v-if="!filteredRisks.length">
                <td colspan="10" class="empty-row"><i class="ti ti-inbox"></i> Aucun risque trouvé</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- MODAL CONTRÔLE -->
      <Teleport to="body">
        <Transition name="mfade">
          <div v-if="modal" class="modal-ov" @click.self="closeModal">
            <div class="modal-box modal-box-lg" @click.stop>
              <div class="modal-hdr">
                <span class="modal-code">{{ selectedRisk?.code_risk }}</span>
                <div>
                  <div class="modal-rname">{{ selectedRisk?.libelle }}</div>
                  <div class="modal-ctx">{{ selectedRisk?.process_name }} › {{ selectedRisk?.activity_name }}</div>
                </div>
                <div class="modal-inh-score" v-if="selectedRisk?.criticality_score" :style="{background:(selectedRisk.zone_color||'#6b7280')+'20',color:selectedRisk.zone_color||'#6b7280'}">
                  <i class="ti ti-shield-bolt"></i> I:{{ selectedRisk.criticality_score }} — {{ selectedRisk.zone_label }}
                </div>
                <button class="modal-x" @click="closeModal"><i class="ti ti-x"></i></button>
              </div>

              <div class="modal-body">
                <!-- A. TYPE -->
                <div class="form-section">
                  <div class="form-sec-hdr"><span class="sec-num">A</span><i class="ti ti-tag"></i><strong>Type de procédure de contrôle</strong>
                    <span v-if="form.control_type" class="sel-badge" :style="{background:CTRL_TYPES.find(t=>t.code===form.control_type)?.color}">{{ form.control_type }}</span>
                  </div>
                  <div class="ct-wrap">
                    <table class="ct">
                      <thead><tr>
                        <th class="ct-c-hd">Dimension</th>
                        <th v-for="ct in CTRL_TYPES" :key="ct.code" class="ct-l-hd" :class="form.control_type===ct.code?'ct-l-hd--sel':''" :style="{borderTop:'4px solid '+ct.color}">
                          <div class="lhd-pill" :style="{background:ct.color}">{{ ct.label }}</div>
                        </th>
                      </tr></thead>
                      <tbody>
                        <tr><td class="ct-c"><div class="ct-cname">Définition</div></td>
                          <td v-for="ct in CTRL_TYPES" :key="ct.code" :class="['ct-d','ct-d--click',form.control_type===ct.code?'ct-d--sel':'']" :style="form.control_type===ct.code?{background:ct.color+'14',borderLeft:'3px solid '+ct.color}:{}" @click="form.control_type=form.control_type===ct.code?null:ct.code">{{ ct.desc }}</td>
                        </tr>
                        <tr><td class="ct-c"><div class="ct-cname">Objectif</div></td>
                          <td v-for="ct in CTRL_TYPES" :key="ct.code" :class="['ct-d','ct-d--click',form.control_type===ct.code?'ct-d--sel':'']" :style="form.control_type===ct.code?{background:ct.color+'14',borderLeft:'3px solid '+ct.color}:{}" @click="form.control_type=form.control_type===ct.code?null:ct.code">{{ ct.obj }}</td>
                        </tr>
                        <tr><td class="ct-c"><div class="ct-cname">Exemple</div></td>
                          <td v-for="ct in CTRL_TYPES" :key="ct.code" :class="['ct-d','ct-d--click',form.control_type===ct.code?'ct-d--sel':'']" :style="form.control_type===ct.code?{background:ct.color+'14',borderLeft:'3px solid '+ct.color}:{}" @click="form.control_type=form.control_type===ct.code?null:ct.code"><span style="font-style:italic;color:#64748b">{{ ct.ex }}</span></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- B. DESCRIPTION -->
                <div class="form-section" style="margin-top:14px">
                  <div class="form-sec-hdr"><span class="sec-num">B</span><i class="ti ti-notes"></i><strong>Description & Responsabilité</strong></div>
                  <div class="ctrl-2col">
                    <div style="flex:2">
                      <label class="flbl">Description de la procédure de contrôle</label>
                      <textarea v-model="form.control_procedure" rows="5" class="finp" placeholder="Décrivez la procédure : qui fait quoi, quand, comment…"></textarea>
                    </div>
                    <div>
                      <label class="flbl">Propriétaire du contrôle</label>
                      <input v-model="form.owner" class="finp" placeholder="Nom / Fonction / Service"/>
                      <label class="flbl" style="margin-top:10px">Statut du contrôle</label>
                      <div class="chips">
                        <span v-for="s in CTRL_STATUTS" :key="s.code" :class="['chip',form.control_status===s.code?'chip--sel':'']" :style="form.control_status===s.code?{background:s.color,color:'#fff',borderColor:s.color}:{}" @click="form.control_status=form.control_status===s.code?null:s.code">{{ s.label }}</span>
                      </div>
                      <label class="flbl" style="margin-top:10px">Date prochaine révision</label>
                      <input v-model="form.next_review_date" type="date" class="finp"/>
                    </div>
                  </div>
                </div>

                <!-- C. MAÎTRISE -->
                <div class="form-section" style="margin-top:14px">
                  <div class="form-sec-hdr"><span class="sec-num">C</span><i class="ti ti-gauge"></i><strong>Niveau de maîtrise</strong>
                    <span v-if="form.mastery_level_id&&masteryLevels.length" class="sel-badge" :style="{background:masteryLevels.find(m=>m.id===form.mastery_level_id)?.color_code}">{{ masteryLevels.find(m=>m.id===form.mastery_level_id)?.label }}</span>
                  </div>
                  <div v-if="masteryLevels.length" class="ct-wrap">
                    <table class="ct">
                      <thead><tr>
                        <th class="ct-c-hd">Dimension</th>
                        <th v-for="m in masteryLevels" :key="m.id" class="ct-l-hd" :class="form.mastery_level_id===m.id?'ct-l-hd--sel':''" :style="{borderTop:'4px solid '+(m.color_code||'#94a3b8')}">
                          <div class="lhd-pill" :style="{background:m.color_code||'#94a3b8'}">{{ m.label }}</div>
                        </th>
                      </tr></thead>
                      <tbody>
                        <tr><td class="ct-c"><div class="ct-cname">Description</div></td>
                          <td v-for="m in masteryLevels" :key="m.id" :class="['ct-d','ct-d--click',form.mastery_level_id===m.id?'ct-d--sel':'']" :style="form.mastery_level_id===m.id?{background:(m.color_code||'#94a3b8')+'14',borderLeft:'3px solid '+(m.color_code||'#94a3b8')}:{}" @click="form.mastery_level_id=form.mastery_level_id===m.id?null:m.id">{{ m.description||'—' }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div v-else style="padding:12px 14px">
                    <label class="flbl">Efficacité estimée</label>
                    <div class="eff-row"><span class="eff-t">Faible</span><input type="range" min="0" max="100" step="10" v-model.number="form.efficacite" class="eff-rng"/><span class="eff-v" :style="{color:effColor}">{{ form.efficacite }}%</span><span class="eff-t">Élevée</span></div>
                    <div class="eff-bar"><div :style="{width:form.efficacite+'%',background:effColor}"></div></div>
                  </div>
                </div>

                <!-- D. RÉFÉRENTIEL -->
                <div class="form-section" style="margin-top:14px">
                  <div class="form-sec-hdr"><span class="sec-num">D</span><i class="ti ti-book"></i><strong>Référentiel</strong></div>
                  <div style="padding:12px 14px">
                    <div class="ref-grid">
                      <div v-for="ref in referentials" :key="ref.code" :class="['ref-card',form.referential_type===ref.code?'ref-card--sel':'']" @click="form.referential_type=form.referential_type===ref.code?null:ref.code">
                        <strong>{{ ref.code }}</strong><small>{{ ref.label }}</small>
                      </div>
                    </div>
                    <div v-if="form.referential_type" class="ref-details">
                      <div>
                        <label class="flbl">Référence document (optionnel)</label>
                        <input v-model="form.referential_ref" class="finp" placeholder="Ex: ISO 9001:2015 §8.4.1"/>
                      </div>
                      <div>
                        <label class="flbl">Ou saisissez manuellement</label>
                        <input v-model="form.referential_manual" class="finp" placeholder="Saisie libre du référentiel…"/>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- E. PÉRIODICITÉ -->
                <div class="form-section" style="margin-top:14px">
                  <div class="form-sec-hdr"><span class="sec-num">E</span><i class="ti ti-calendar-repeat"></i><strong>Périodicité du contrôle</strong></div>
                  <div style="padding:10px 14px">
                    <div class="chips">
                      <span v-for="p in PERIODICITES" :key="p.code" :class="['chip',form.periodicite===p.code?'chip--blue':'']" @click="form.periodicite=form.periodicite===p.code?null:p.code">{{ p.label }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal-footer">
                <div class="mf-btns">
                  <button class="btn-cancel" @click="closeModal"><i class="ti ti-x"></i> Annuler</button>
                  <button class="btn-save" :disabled="saving" @click="save">
                    <i v-if="saving" class="ti ti-loader-2 ti-spin"></i><i v-else class="ti ti-check"></i>
                    {{ saving?'Enregistrement…':'Valider le contrôle' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <Transition name="fl">
        <div v-if="flashMsg" :class="['flash',flashOk?'flash-ok':'flash-err']">
          <i :class="flashOk?'ti ti-check-circle':'ti ti-alert-circle'"></i> {{ flashMsg }}
        </div>
      </Transition>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  risks: { type: Array, default: () => [] },
  tree:  { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  matrixConfigs: { type: Array, default: () => [] },
  matrixData:    { type: Object, default: () => null },
  selectedConfigId: { type: Number, default: null },
  referentials:  { type: Array, default: () => [] },
  masteryLevels: { type: Array, default: () => [] },
})

const CTRL_TYPES = [
  { code:'preventif',     label:'Préventif',     color:'#22c55e', desc:'Évite l\'occurrence avant qu\'elle se produise', obj:'Réduire la probabilité d\'occurrence', ex:'Formation, procédure, double validation' },
  { code:'detectif',      label:'Détectif',      color:'#3b82f6', desc:'Détecte et signale une anomalie après occurrence', obj:'Identifier rapidement les défaillances', ex:'Audit, contrôle périodique, alerte' },
  { code:'correctif',     label:'Correctif',     color:'#f59e0b', desc:'Corrige ou atténue les effets d\'un risque réalisé', obj:'Réduire l\'impact après occurrence', ex:'Plan de reprise, assurance, communication' },
  { code:'directif',      label:'Directif',      color:'#8b5cf6', desc:'Guide et encadre les comportements', obj:'Orienter les actions et décisions', ex:'Politique, charte, directive' },
  { code:'compensatoire', label:'Compensatoire', color:'#ec4899', desc:'Compense les faiblesses d\'autres contrôles', obj:'Pallier l\'absence d\'un contrôle primaire', ex:'Revue manuelle compensant un système défaillant' },
]
const PERIODICITES = [{code:'continu',label:'Continu'},{code:'quotidien',label:'Quotidien'},{code:'hebdomadaire',label:'Hebdo.'},{code:'mensuel',label:'Mensuel'},{code:'trimestriel',label:'Trimestr.'},{code:'semestriel',label:'Semestr.'},{code:'annuel',label:'Annuel'}]
const CTRL_STATUTS = [{code:'en_place',label:'En place',color:'#22c55e'},{code:'partiel',label:'Partiel',color:'#f59e0b'},{code:'planifie',label:'Planifié',color:'#3b82f6'},{code:'non_applique',label:'Non appliqué',color:'#ef4444'}]

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''
const searchQ = ref('')
const filter  = ref('all')
const modal   = ref(false)
const saving  = ref(false)
const selectedRisk = ref(null)
const flashMsg = ref(''); const flashOk = ref(true); let flashTimer = null

const form = ref({
  control_procedure: '',
  control_type: null,
  owner: '',
  control_status: 'en_place',
  next_review_date: '',
  mastery_level_id: null,
  efficacite: 50,
  referential_type: null,
  referential_ref: '',
  referential_manual: '',
  periodicite: null
})

const effColor = computed(() => {
  const v = form.value.efficacite
  return v >= 80 ? '#22c55e' : v >= 50 ? '#f59e0b' : '#ef4444'
})

const macroColor = k => ({Direction:'#7c3aed',Réalisation:'#0d9488',Support:'#2563eb'})[k]||'#64748b'
const macroKindLabel = k => ({Direction:'DIR',Réalisation:'OP',Support:'SUP'})[k]||(k||'?')
const truncate = (s,n=50) => s?.length > n ? s.slice(0,n)+'…' : s||''

const withInherent = computed(() => props.risks.filter(r => r.criticality_score))
const withControl = computed(() => props.risks.filter(r => r.has_control))
const withoutControl = computed(() => withInherent.value.filter(r => !r.has_control))

// Compteur local de risques controles : initialise depuis stats, puis incremente
// localement a chaque validation reussie, SANS attendre un rechargement de page.
const localWithControlCount = ref(props.stats?.with_control || 0)

// Le bouton "Residuel" se debloque automatiquement dès qu'au moins un risque
// est controle, meme si la prop stats (calculee au chargement initial de la page)
// n'a pas encore ete rafraichie par Inertia.
const hasControlledRisks = computed(() => localWithControlCount.value > 0)

const filteredRisks = computed(() => {
  let r = filter.value === 'done'
    ? withControl.value
    : filter.value === 'todo'
      ? withoutControl.value
      : withInherent.value

  if(searchQ.value) {
    const q = searchQ.value.toLowerCase()
    r = r.filter(x =>
      x.libelle?.toLowerCase().includes(q) ||
      x.code_risk?.toLowerCase().includes(q)
    )
  }
  return r
})

const displayTree = computed(() => {
  const ids = new Set(filteredRisks.value.map(r => r.id))
  return props.tree
    .map(m => ({
      ...m,
      processes: m.processes
        .map(p => ({
          ...p,
          activities: p.activities
            .map(a => ({
              ...a,
              risks: a.risks.filter(r => ids.has(r.id))
            }))
            .filter(a => a.risks.length)
        }))
        .filter(p => p.activities.length)
    }))
    .filter(m => m.processes.length)
})

// Fonction pour naviguer vers la page résiduel
const goToResiduel = () => {
  if (!hasControlledRisks.value) {
    showFlash('Veuillez d\'abord définir des contrôles avant de passer au résiduel', false)
    return
  }

  try {
    router.visit(route('risk.core.evaluation.residuelle'))
  } catch (e) {
    console.error('Erreur de navigation:', e)
    window.location.href = '/m/risk.core/evaluation/residuelle'
  }
}

const openModal = risk => {
  selectedRisk.value = risk
  Object.assign(form.value, {
    control_procedure: risk.control_procedure || risk.controles_existants || '',
    control_type: risk.control_type || null,
    owner: risk.owner || '',
    control_status: risk.control_status || 'en_place',
    next_review_date: risk.next_review_date || '',
    mastery_level_id: risk.mastery_level_id || null,
    efficacite: risk.efficacite || 50,
    referential_type: risk.referential_type || null,
    referential_ref: risk.referential_ref || '',
    referential_manual: '',
    periodicite: risk.periodicite || null
  })
  modal.value = true
}

const closeModal = () => {
  modal.value = false
}

const showFlash = (msg, ok) => {
  if(flashTimer) clearTimeout(flashTimer)
  flashMsg.value = msg
  flashOk.value = ok
  flashTimer = setTimeout(() => {
    flashMsg.value = ''
  }, 3500)
}

const patchRisk = (id, upd) => {
  // props.risks et props.tree sont deux structures SEPAREES construites
  // independamment cote serveur (buildTree() copie les risques dans son
  // propre arbre). Il faut patcher les deux, sinon le tableau (qui lit
  // displayTree, base sur props.tree) ne reflete jamais la mise a jour.
  const idx = props.risks.findIndex(r => r.id === id)
  if (idx !== -1) {
    Object.assign(props.risks[idx], upd)
    selectedRisk.value = props.risks[idx]
  }

  for (const macro of props.tree) {
    for (const proc of macro.processes) {
      for (const act of proc.activities) {
        const r = act.risks.find(r => r.id === id)
        if (r) Object.assign(r, upd)
      }
    }
  }
}

const save = async () => {
  if(!selectedRisk.value) {
    showFlash('Aucun risque sélectionné', false)
    return
  }

  saving.value = true

  try {
    const payload = {
      risk_id: selectedRisk.value.id,
      control_procedure: form.value.control_procedure || '',
      control_type: form.value.control_type,
      owner: form.value.owner || '',
      control_status: form.value.control_status || 'en_place',
      next_review_date: form.value.next_review_date || null,
      mastery_level_id: form.value.mastery_level_id,
      efficacite: form.value.efficacite || 50,
      referential_type: form.value.referential_type,
      referential_ref: form.value.referential_ref || '',
      referential_manual: form.value.referential_manual || '',
      periodicite: form.value.periodicite
    }

    const response = await fetch(route('risk.core.evaluation.controle.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })

    const data = await response.json()

    if(response.ok && data.success) {
      showFlash('Contrôle enregistré ✓', true)

      const hadControlBefore = !!selectedRisk.value.has_control
      const saved = data.saved || {}

      const updatedRisk = {
        ...selectedRisk.value,
        has_control: true,
        control_id: data.control_id,
        control_procedure: saved.control_procedure ?? saved.description ?? form.value.control_procedure,
        control_type: saved.type ?? form.value.control_type,
        owner: saved.owner ?? form.value.owner,
        control_status: saved.status ?? form.value.control_status,
        mastery_level_id: saved.mastery_level_id ?? form.value.mastery_level_id,
        periodicite: saved.periodicite ?? form.value.periodicite,
        efficacite: saved.efficacite ?? form.value.efficacite,
        referential_type: saved.referential_type ?? (form.value.referential_type || form.value.referential_manual),
        referential_ref: saved.referential_ref ?? form.value.referential_ref,
        next_review_date: saved.next_review_date ?? form.value.next_review_date
      }

      patchRisk(selectedRisk.value.id, updatedRisk)

      // Deblocage automatique du bouton Residuel : on incremente le compteur
      // local SEULEMENT si ce risque n'etait pas deja controle avant (evite
      // de compter deux fois une simple modification).
      if (!hadControlBefore) {
        localWithControlCount.value += 1
      }

      closeModal()
    } else {
      showFlash(data.message || 'Erreur lors de l\'enregistrement', false)
    }
  } catch (error) {
    console.error('Erreur réseau:', error)
    showFlash('Erreur réseau', false)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.page{display:flex;flex-direction:column;height:calc(100vh - 60px);background:#f0f4f8;overflow:hidden;font-family:'Inter',system-ui,sans-serif;font-size:13px;}
.page-hdr{display:flex;align-items:center;justify-content:space-between;padding:10px 22px;background:#0f172a;flex-shrink:0;flex-wrap:wrap;gap:10px;}
.page-hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.hdr-icon--ctrl{background:linear-gradient(135deg,#0369a1,#0ea5e9);}
.page-hdr-left h1{font-size:16px;font-weight:800;color:#f1f5f9;margin:0;}
.page-hdr-left p{font-size:11px;color:#64748b;margin:0;}
.page-hdr-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.stat-row{display:flex;gap:6px;}
.stat{display:flex;flex-direction:column;align-items:center;padding:4px 12px;border-radius:8px;background:rgba(255,255,255,.07);}
.stat span{font-size:16px;font-weight:800;color:#f1f5f9;line-height:1;}
.stat small{font-size:9px;color:#64748b;font-weight:600;}
.stat-ok span{color:#4ade80;}.stat-nd span{color:#fbbf24;}
.nav-links{display:flex;gap:6px;}
.btn-nav{display:flex;align-items:center;gap:5px;padding:7px 14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.07);color:#c8d6e5;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;position:relative;}
.btn-nav:hover:not(:disabled){background:rgba(255,255,255,.13);}
.btn-nav:disabled{opacity:0.5;cursor:not-allowed;}
.btn-nav:disabled .tooltip{display:none;}
.btn-nav:disabled:hover .tooltip{display:block;position:absolute;bottom:100%;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:4px 8px;border-radius:4px;font-size:10px;white-space:nowrap;z-index:10;}
.btn-nav-next{background:#0ea5e9!important;color:#fff!important;border-color:#0ea5e9!important;}
.btn-nav-next:hover:not(:disabled){background:#0284c7!important;}
/* Table */
.table-wrap{flex:1;display:flex;flex-direction:column;overflow:hidden;background:#fff;}
.table-toolbar{display:flex;align-items:center;padding:10px 16px;border-bottom:1px solid #e2e8f0;flex-shrink:0;flex-wrap:wrap;gap:8px;}
.tt-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.search-box{position:relative;}.search-box i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;}
.search-box input{padding:7px 10px 7px 32px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;background:#f8fafc;width:220px;}
.filter-tabs{display:flex;gap:4px;}
.ftab{display:flex;align-items:center;gap:5px;padding:5px 12px;border:1.5px solid #e2e8f0;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;background:#fff;color:#64748b;transition:all .12s;}
.ftab:hover{border-color:#93c5fd;color:#2563eb;}.ftab--on{border-color:#0ea5e9;background:#e0f2fe;color:#0369a1;}
.ftab span{background:#e2e8f0;border-radius:10px;padding:0 6px;font-size:9px;font-weight:700;}
.ftab--on span{background:#bae6fd;color:#0369a1;}
.table-scroll{flex:1;overflow:auto;}
.risk-table{width:100%;border-collapse:collapse;font-size:12px;}
.risk-table thead th{position:sticky;top:0;z-index:4;text-align:left;padding:9px 12px;background:#f8fafc;border-bottom:2px solid #e2e8f0;font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;}
.risk-table td{padding:8px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.tr-macro td{background:#0f172a;padding:7px 12px;font-size:11px;font-weight:700;color:#94a3b8;}
.macro-badge{display:inline-block;font-size:9px;font-weight:800;padding:2px 8px;border-radius:4px;color:#fff;margin-right:8px;}
.tr-risk{transition:background .1s;}
.tr-risk:hover{background:#f0f6ff;}.tr-risk--sel{background:#e0f2fe!important;}.tr-risk--done{border-left:3px solid #0ea5e9;}
.tr-risk--locked{opacity:.55;}
.code-badge{font-family:monospace;font-size:10px;font-weight:700;color:#4338ca;background:#ede9fe;padding:2px 7px;border-radius:5px;}
.td-name{max-width:180px;}.risk-name{font-size:12px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.proc-line{font-size:10px;color:#475569;display:flex;align-items:center;gap:4px;}
.act-line{font-size:9px;color:#94a3b8;display:flex;align-items:center;gap:4px;margin-top:2px;}
.proc-code{font-family:monospace;font-size:9px;background:#e2e8f0;color:#475569;padding:0 4px;border-radius:3px;}
.act-code{font-family:monospace;font-size:8px;background:#dcfce7;color:#15803d;padding:0 4px;border-radius:3px;}
.td-obj{max-width:150px;}.obj-text{font-size:10px;color:#64748b;font-style:italic;}
.th-score-c{text-align:center;}.td-sc{text-align:center;}
.big-score{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:9px;font-size:14px;font-weight:900;color:#fff;}
.nd{color:#cbd5e1;font-size:11px;}
.zone-pill{font-size:10px;font-weight:700;padding:3px 10px;border-radius:12px;display:inline-block;white-space:nowrap;}
.td-proc-ctrl{max-width:160px;}.ctrl-text{font-size:10px;color:#475569;}
.ref-badge{font-size:9px;font-weight:700;padding:2px 8px;border-radius:6px;background:#e0e7ff;color:#4338ca;}
.owner-text{font-size:10px;color:#475569;display:flex;align-items:center;gap:4px;}
.status-pill{display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:4px 10px;border-radius:12px;white-space:nowrap;}
.sp-done{background:#e0f2fe;color:#0369a1;}.sp-todo{background:#fef3c7;color:#d97706;}
.empty-row{text-align:center;padding:40px!important;color:#94a3b8;}

/* Cellules cliquables (remplacent l'ancienne colonne Action) */
.td-clickable{cursor:pointer;transition:background .12s;border-radius:6px;}
.td-clickable:hover{background:#e0f2fe!important;}
.td-clickable--off{cursor:not-allowed;}
.td-clickable--off:hover{background:transparent!important;}
.nd-cell--edit{display:inline-flex;align-items:center;gap:4px;color:#0369a1;font-size:10px;font-weight:700;padding:4px 9px;border:1.5px dashed #bae6fd;border-radius:8px;background:#f0f9ff;}
.td-clickable:hover .nd-cell--edit{background:#e0f2fe;border-color:#0ea5e9;}

/* Modal */
.modal-ov{position:fixed;inset:0;background:rgba(2,6,23,.7);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;z-index:2000;padding:20px;}
.modal-box{background:#fff;border-radius:18px;width:min(940px,100%);max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.3);}
.modal-box-lg{width:min(1000px,100%);}
.modal-hdr{display:flex;align-items:center;gap:12px;padding:14px 20px;background:#0f172a;flex-shrink:0;flex-wrap:wrap;}
.modal-code{font-family:monospace;font-size:12px;font-weight:800;background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:7px;flex-shrink:0;}
.modal-rname{font-size:14px;font-weight:700;color:#f1f5f9;}
.modal-ctx{font-size:10px;color:#64748b;margin-top:1px;}
.modal-inh-score{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 12px;border-radius:8px;margin-left:auto;}
.modal-x{width:30px;height:30px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.06);color:#94a3b8;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;}
.modal-x:hover{background:rgba(255,0,0,.2);color:#f87171;}
.modal-body{flex:1;overflow-y:auto;padding:18px 20px;}
/* Form sections */
.form-section{border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
.form-sec-hdr{display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#1e293b;flex-wrap:wrap;}
.sec-num{width:22px;height:22px;border-radius:50%;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;}
.sel-badge{margin-left:auto;font-size:10px;font-weight:800;padding:3px 12px;border-radius:10px;color:#fff;}
/* CT tables */
.ct-wrap{overflow-x:auto;max-height:200px;overflow-y:auto;}
.ct{width:100%;border-collapse:collapse;font-size:11px;}
.ct-c-hd{position:sticky;top:0;left:0;z-index:4;background:#1e293b;color:#cbd5e1;padding:8px 12px;font-size:10px;font-weight:700;text-align:left;min-width:160px;border-right:1px solid #334155;}
.ct-l-hd{position:sticky;top:0;z-index:3;background:#1e293b;color:#cbd5e1;padding:7px 10px;text-align:center;min-width:140px;border-right:1px solid #334155;}
.ct-l-hd--sel{background:#1e3a5f!important;}
.lhd-pill{font-size:10px;font-weight:800;padding:2px 9px;border-radius:6px;color:#fff;display:inline-block;margin-bottom:3px;}
.ct-c{position:sticky;left:0;z-index:2;background:#f8fafc;padding:8px 12px;border-right:1px solid #e2e8f0;border-bottom:1px solid #f1f5f9;min-width:160px;vertical-align:top;}
.ct-cname{font-size:11px;font-weight:700;color:#1e293b;}
.ct-d{padding:8px 12px;border-right:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;font-size:11px;line-height:1.5;color:#374151;vertical-align:top;}
.ct-d--click{cursor:pointer;transition:background .1s;}.ct-d--click:hover{background:#f0f7ff!important;}.ct-d--sel{background:#e0f2fe!important;}
/* Form inputs */
.ctrl-2col{display:flex;gap:14px;padding:14px;}
.flbl{font-size:10px;font-weight:700;color:#475569;display:block;margin-bottom:4px;}
.finp{width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;background:#fff;box-sizing:border-box;font-family:inherit;}
.finp:focus{outline:none;border-color:#93c5fd;}
textarea.finp{resize:vertical;min-height:60px;}
.chips{display:flex;flex-wrap:wrap;gap:5px;margin-top:4px;}
.chip{padding:5px 11px;border:1.5px solid #e2e8f0;border-radius:14px;cursor:pointer;font-size:10px;font-weight:600;transition:all .12s;}
.chip:hover{border-color:#93c5fd;background:#f0f7ff;}
.chip--sel{border-color:#0ea5e9!important;background:#e0f2fe!important;color:#0369a1!important;}
.chip--blue{border-color:#0ea5e9!important;background:#e0f2fe!important;color:#0369a1!important;}
.eff-row{display:flex;align-items:center;gap:8px;margin-bottom:6px;}
.eff-t{font-size:10px;color:#64748b;white-space:nowrap;}.eff-rng{flex:1;}.eff-v{font-size:15px;font-weight:800;font-family:monospace;min-width:40px;text-align:center;}
.eff-bar{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;}.eff-bar>div{height:100%;border-radius:3px;transition:width .2s,background .2s;}
.ref-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:6px;margin-bottom:10px;}
.ref-card{padding:8px;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;text-align:center;transition:all .1s;}
.ref-card:hover{border-color:#93c5fd;background:#f0f7ff;}.ref-card--sel{border-color:#0ea5e9;background:#e0f2fe;}
.ref-card strong{font-size:10px;display:block;color:#1e293b;}.ref-card small{font-size:8px;color:#64748b;}
.ref-details{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
/* Footer */
.modal-footer{display:flex;justify-content:flex-end;padding:12px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;flex-shrink:0;}
.mf-btns{display:flex;gap:8px;}
.btn-cancel{display:flex;align-items:center;gap:5px;padding:9px 16px;border:1.5px solid #e2e8f0;border-radius:9px;background:#fff;color:#475569;font-size:12px;font-weight:600;cursor:pointer;}
.btn-cancel:hover{background:#f1f5f9;}
.btn-save{display:flex;align-items:center;gap:6px;padding:9px 20px;border:none;border-radius:9px;background:#0ea5e9;color:#fff;font-size:12px;font-weight:800;cursor:pointer;}
.btn-save:hover:not(:disabled){background:#0284c7;}.btn-save:disabled{opacity:.4;cursor:not-allowed;}
.flash{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:8px;padding:11px 18px;border-radius:12px;font-size:12px;font-weight:700;box-shadow:0 4px 16px rgba(0,0,0,.12);}
.flash-ok{background:#dcfce7;color:#15803d;border:1px solid #86efac;}.flash-err{background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;}
.fl-enter-active,.fl-leave-active{transition:opacity .2s,transform .2s;}.fl-enter-from,.fl-leave-to{opacity:0;transform:translateX(20px);}
.mfade-enter-active{transition:opacity .18s,transform .18s;}.mfade-leave-active{transition:opacity .14s,transform .14s;}.mfade-enter-from,.mfade-leave-to{opacity:0;transform:scale(.97);}
::-webkit-scrollbar{width:4px;height:4px;}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:2px;}
@keyframes ti-spin{to{transform:rotate(360deg);}}.ti-spin{animation:ti-spin .7s linear infinite;display:inline-block;}
</style>