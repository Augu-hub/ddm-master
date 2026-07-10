<template>
  <VerticalLayout>
    <Head title="Paramètres — Audit de Performance" />

    <!-- ── EN-TÊTE ────────────────────────────────────────────────── -->
    <div class="ap-page-header">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <div class="ap-eyebrow">Paramétrage · Audit interne</div>
          <h4 class="m-0 fw-bold">Audit de Performance</h4>
          <p class="text-muted mb-0 mt-1" style="font-size:.82rem">
            ISSAI 3000 / 3100 — Les 4 « E » : Économie, Efficience, Efficacité, Qualité de service
          </p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="ap-kpi">
            <span class="ap-kpi-value">{{ totalParams }}</span>
            <span class="ap-kpi-label">paramètres actifs</span>
          </div>
          <div class="vr mx-1 d-none d-md-block" style="height:32px;opacity:.15"></div>
          <b-button size="sm" variant="outline-danger" @click="resetData" :disabled="seeding">
            <i class="ti ti-trash me-1"></i>Réinitialiser
          </b-button>
          <b-button size="sm" variant="dark" @click="seedData" :disabled="seeding">
            <i class="ti ti-plant me-1"></i>{{ seeding ? 'Initialisation…' : 'Charger les valeurs par défaut' }}
          </b-button>
        </div>
      </div>
    </div>

    <b-alert v-if="seedMsg.text" :variant="seedMsg.variant" dismissible @dismissed="seedMsg.text=''" class="py-2 px-3 mt-2" style="font-size:.78rem">
      {{ seedMsg.text }}
    </b-alert>

    <b-alert v-if="emptyModulesCount > 0 && !seedMsg.text" variant="warning" class="py-2 px-3 mt-2 d-flex align-items-center gap-2" style="font-size:.78rem">
      <i class="ti ti-alert-triangle"></i>
      {{ emptyModulesCount }} module(s) sans aucune donnée — utilisez « Charger les valeurs par défaut » pour partir du référentiel ISSAI standard.
    </b-alert>

    <!-- ══════════════════════════════════════════════════════════════
         CORPS — rail des modules (gauche) + contenu (droite)
    ══════════════════════════════════════════════════════════════ -->
    <b-row class="g-3 mt-1">

      <!-- ── RAIL DES MODULES ─────────────────────────────────────── -->
      <b-col lg="3">
        <b-card no-body class="shadow-sm ap-rail-card">
          <div class="ap-rail-title">Modules</div>
          <div class="ap-rail">
            <button
              v-for="(mod, i) in MODULES" :key="mod.key"
              class="ap-module-item"
              :class="{ active: activeModule === mod.key }"
              :style="{ '--mod-color': colorHex(mod.color) }"
              @click="activeModule = mod.key"
            >
              <span class="ap-module-num">{{ pad(i + 1) }}</span>
              <i :class="mod.icon + ' ap-module-icon'"></i>
              <span class="ap-module-text">
                <strong>{{ mod.label }}</strong>
                <small>{{ mod.subtitle }}</small>
              </span>
              <span class="ap-module-count">{{ moduleTotal(mod) }}</span>
            </button>
          </div>
        </b-card>

        <b-card no-body class="shadow-sm mt-2 ap-help-card">
          <b-card-body class="p-3">
            <div class="d-flex gap-2 align-items-start">
              <i class="ti ti-bulb text-warning mt-1"></i>
              <small class="text-muted" style="font-size:.72rem;line-height:1.4">
                Ces référentiels alimentent les listes déroulantes utilisées lors du cadrage
                et de la planification des missions d'audit de performance (type <strong>AP</strong>).
              </small>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- ── CONTENU DU MODULE SÉLECTIONNÉ ────────────────────────── -->
      <b-col lg="9">
        <div class="ap-module-header" :style="{ '--mod-color': colorHex(currentModule.color) }">
          <i :class="currentModule.icon"></i>
          <div>
            <h5 class="mb-0 fw-semibold">{{ currentModule.label }}</h5>
            <small class="text-muted">{{ currentModule.description }}</small>
          </div>
        </div>

        <b-row class="g-2">
          <b-col :lg="ent.width" v-for="ent in entitiesForModule" :key="ent.key">
            <b-card no-body class="shadow-sm h-100 ap-entity-card">
              <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                  <i :class="ent.icon" :style="{ color: colorHex(currentModule.color) }"></i>
                  {{ ent.label }}
                </h6>
                <b-button size="sm" :style="{ backgroundColor: colorHex(currentModule.color), borderColor: colorHex(currentModule.color) }"
                          class="text-white" @click="openForm(ent)">
                  <i class="ti ti-plus"></i>
                </b-button>
              </b-card-header>
              <b-card-body class="p-0">
                <DataTable :value="state[ent.prop]" size="small" class="pv-table flat">
                  <Column v-for="col in ent.columns" :key="col.field" :header="col.header" :style="col.width ? ('width:' + col.width) : ''">
                    <template #body="{ data }">
                      <span v-if="col.tag" class="ap-code-chip" :style="chipStyle(currentModule.color)">{{ data[col.field] }}</span>
                      <small v-else-if="col.muted" class="text-muted">{{ data[col.field] || '—' }}</small>
                      <span v-else style="font-size:.73rem;white-space:pre-line">{{ data[col.field] }}</span>
                    </template>
                  </Column>
                  <Column header="" style="width:55px" bodyClass="text-end">
                    <template #body="{ data }">
                      <div class="d-flex gap-1 justify-content-end">
                        <b-button size="sm" variant="light" @click="editItem(ent, data)"><i class="ti ti-pencil"></i></b-button>
                        <b-button size="sm" variant="light" @click="destroyItem(ent, data.id)"><i class="ti ti-trash text-danger"></i></b-button>
                      </div>
                    </template>
                  </Column>
                  <template #empty>
                    <div class="text-center text-muted py-3 small">
                      <i class="ti ti-inbox d-block mb-1" style="font-size:1.3rem;opacity:.4"></i>
                      Aucune donnée — cliquez <strong>+</strong> pour ajouter, ou initialisez les valeurs par défaut.
                    </div>
                  </template>
                </DataTable>
              </b-card-body>
            </b-card>
          </b-col>
        </b-row>
      </b-col>
    </b-row>

    <!-- ══════════════════════════════════════════════════════════════
         MODALE GÉNÉRIQUE (add / edit) — pilotée par currentEntity.fields
    ══════════════════════════════════════════════════════════════ -->
    <b-modal v-model="modal.open" :title="modalTitle" hide-footer :size="currentEntity && currentEntity.width === 12 ? 'lg' : ''">
      <b-form @submit.prevent="submitForm">
        <b-row class="g-2">
          <b-col v-for="f in (currentEntity ? currentEntity.fields : [])" :key="f.name" :cols="f.col || 12">
            <label class="form-label mb-1">{{ f.label }} <span v-if="f.required" class="text-danger">*</span></label>

            <b-form-input v-if="f.type === 'text'" class="form-control-sm" v-model.trim="form[f.name]" :required="f.required"/>
            <b-form-input v-else-if="f.type === 'number'" type="number" class="form-control-sm" v-model.number="form[f.name]" :required="f.required"/>
            <b-form-textarea v-else-if="f.type === 'textarea'" class="form-control-sm" rows="3" v-model.trim="form[f.name]" :required="f.required"/>
            <b-form-select v-else-if="f.type === 'select'" class="form-select-sm" v-model="form[f.name]" :options="selectOptions(f)"/>
          </b-col>
        </b-row>
        <div class="text-end mt-3">
          <b-button variant="light" class="me-2" @click="modal.open = false">Annuler</b-button>
          <b-button variant="dark" type="submit">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import { ref, reactive, computed } from "vue"
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"
import DataTable from "primevue/datatable"
import Column from "primevue/column"

// ── Props (clés = Str::camel(slug) côté contrôleur) ─────────────────────────
const props = defineProps({
  beneficiaires:           { type: Array, default: () => [] },
  niveauxRisque:           { type: Array, default: () => [] },
  approchesAudit:          { type: Array, default: () => [] },
  typesAssurance:          { type: Array, default: () => [] },
  attributsPreuve:         { type: Array, default: () => [] },
  criteres:                { type: Array, default: () => [] },
  methodesCollecte:        { type: Array, default: () => [] },
  risquesAudit:            { type: Array, default: () => [] },
  methodesAnalyse:         { type: Array, default: () => [] },
  typesPreuve:             { type: Array, default: () => [] },
  partiesPrenantes:        { type: Array, default: () => [] },
  facteursSelectionTheme:  { type: Array, default: () => [] },
  perimetreDimensions:     { type: Array, default: () => [] },
  sousCriteresPreuve:      { type: Array, default: () => [] },
  naturePreuve:            { type: Array, default: () => [] },
  sourcesPreuve:           { type: Array, default: () => [] },
  activeTab:               { type: String, default: 'referentiels' },
})

const BASE_URL = '/m/audit.core/param-perf'

// ═══════════════════════════════════════════════════════════════════
//  MODULES — regroupement logique des 13 référentiels (ordre = ordre
//  naturel de configuration d'une mission : du cadrage à la stratégie)
// ═══════════════════════════════════════════════════════════════════
const MODULES = [
  {
    key: 'cadrage', label: 'Cadrage de la mission', subtitle: 'Approche, assurance, risque',
    description: "Paramètres qui déterminent comment une mission d'audit de performance est cadrée dès sa planification.",
    icon: 'ti ti-target-arrow', color: 'primary',
  },
  {
    key: 'criteres', label: 'Critères & bénéficiaires', subtitle: 'Les 4 « E », public cible',
    description: "Les critères d'évaluation de la performance (Économie, Efficience, Efficacité, Qualité de service) et leurs bénéficiaires.",
    icon: 'ti ti-scale', color: 'success',
  },
  {
    key: 'methodologie', label: 'Méthodologie & preuve', subtitle: 'Collecte, analyse, preuve',
    description: "Méthodes de collecte et d'analyse des données, et caractérisation de la preuve d'audit : ses types, sa nature, ses sources et les sous-critères auxquels elle est comparée.",
    icon: 'ti ti-flask', color: 'info',
  },
  {
    key: 'risques', label: 'Risques & parties prenantes', subtitle: "Risques d'audit, acteurs externes",
    description: "Risques génériques à surveiller pendant la mission, et parties prenantes à consulter.",
    icon: 'ti ti-shield-exclamation', color: 'danger',
  },
  {
    key: 'planification', label: 'Planification stratégique', subtitle: 'Sélection des thèmes, périmètre',
    description: "Facteurs de sélection des thèmes d'audit et grille de délimitation du périmètre (Qui / Quoi / Quand / Où).",
    icon: 'ti ti-map-2', color: 'warning',
  },
]

// ═══════════════════════════════════════════════════════════════════
//  ENTITÉS — slug URL, clé prop, module parent, colonnes, champs form
// ═══════════════════════════════════════════════════════════════════
const ENTITIES = [
  {
    key: 'approches-audit', prop: 'approchesAudit', module: 'cadrage', label: "Approches d'audit", icon: 'ti ti-route', width: 4,
    columns: [{ field:'code', header:'Code', width:'55px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
      { name:'norme', label:'Norme', type:'text', col:12 },
    ],
  },
  {
    key: 'types-assurance', prop: 'typesAssurance', module: 'cadrage', label: "Type d'assurance", icon: 'ti ti-shield-check', width: 4,
    columns: [{ field:'code', header:'Code', width:'55px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
      { name:'norme', label:'Norme', type:'text', col:12 },
    ],
  },
  {
    key: 'niveaux-risque', prop: 'niveauxRisque', module: 'cadrage', label: 'Niveaux de risque', icon: 'ti ti-alert-triangle', width: 4,
    columns: [{ field:'niveau', header:'Niv.', width:'45px' }, { field:'code', header:'Code', width:'90px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'niveau', label:'Niveau (1-3)', type:'number', required:true, col:4 },
      { name:'code', label:'Code', type:'text', required:true, col:8 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:12 },
      { name:'couleur', label:'Couleur', type:'select', options:['green','orange','red','gray'], col:6 },
      { name:'norme', label:'Norme', type:'text', col:6 },
    ],
  },

  {
    key: 'criteres', prop: 'criteres', module: 'criteres', label: "Critères principaux d'audit (les « E »)", icon: 'ti ti-target', width: 12,
    columns: [
      { field:'code', header:'Code', width:'65px', tag:true },
      { field:'nature', header:'Nature', width:'150px' },
      { field:'but', header:'But', muted:true },
      { field:'beneficiaire_code', header:'Bénéf.', width:'70px', tag:true },
    ],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:3 },
      { name:'nature', label:'Nature', type:'text', required:true, col:9 },
      { name:'but', label:'But', type:'text', col:6 },
      { name:'norme', label:'Norme', type:'text', col:3 },
      { name:'beneficiaire_code', label:'Bénéficiaire', type:'select', optionsFrom:'beneficiaires', col:3 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },
  {
    key: 'beneficiaires', prop: 'beneficiaires', module: 'criteres', label: 'Bénéficiaires / Public cible', icon: 'ti ti-users', width: 6,
    columns: [{ field:'code', header:'Code', width:'70px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },
  {
    key: 'attributs-preuve', prop: 'attributsPreuve', module: 'criteres', label: 'Attributs de preuve', icon: 'ti ti-certificate', width: 6,
    columns: [{ field:'code', header:'Code', width:'55px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },

  {
    key: 'methodes-collecte', prop: 'methodesCollecte', module: 'methodologie', label: 'Méthodes de collecte', icon: 'ti ti-clipboard-list', width: 4,
    columns: [{ field:'code', header:'Code', width:'55px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },
  {
    key: 'methodes-analyse', prop: 'methodesAnalyse', module: 'methodologie', label: "Méthodes d'analyse", icon: 'ti ti-chart-histogram', width: 4,
    columns: [{ field:'code', header:'Code', width:'65px', tag:true }, { field:'libelle', header:'Libellé' }, { field:'type', header:'Type', width:'95px', muted:true }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'type', label:'Type', type:'select', options:['quantitative','qualitative'], col:6 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },
  {
    key: 'types-preuve', prop: 'typesPreuve', module: 'methodologie', label: 'Types de preuve', icon: 'ti ti-file-check', width: 4,
    columns: [{ field:'code', header:'Code', width:'55px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },
  {
    key: 'sous-criteres-preuve', prop: 'sousCriteresPreuve', module: 'methodologie', label: 'Sous-critères de la preuve', icon: 'ti ti-gavel', width: 4,
    columns: [{ field:'code', header:'Code', width:'95px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },
  {
    key: 'nature-preuve', prop: 'naturePreuve', module: 'methodologie', label: 'Nature de la preuve', icon: 'ti ti-fingerprint', width: 4,
    columns: [{ field:'code', header:'Code', width:'65px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },
  {
    key: 'sources-preuve', prop: 'sourcesPreuve', module: 'methodologie', label: 'Sources de la preuve', icon: 'ti ti-git-branch', width: 4,
    columns: [{ field:'code', header:'Code', width:'90px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },

  {
    key: 'risques-audit', prop: 'risquesAudit', module: 'risques', label: "Risques d'audit", icon: 'ti ti-alert-octagon', width: 6,
    columns: [{ field:'code', header:'Code', width:'45px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },
  {
    key: 'parties-prenantes', prop: 'partiesPrenantes', module: 'risques', label: 'Parties prenantes', icon: 'ti ti-users-group', width: 6,
    columns: [{ field:'code', header:'Code', width:'65px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:4 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:8 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },

  {
    key: 'facteurs-selection-theme', prop: 'facteursSelectionTheme', module: 'planification', label: 'Facteurs de sélection des thèmes', icon: 'ti ti-list-check', width: 6,
    columns: [{ field:'code', header:'Code', width:'190px', tag:true }, { field:'libelle', header:'Libellé' }],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:5 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:7 },
      { name:'description', label:'Description', type:'textarea', col:12 },
    ],
  },
  {
    key: 'perimetre-dimensions', prop: 'perimetreDimensions', module: 'planification', label: 'Périmètre — Qui / Quoi / Quand / Où', icon: 'ti ti-frame', width: 6,
    columns: [
      { field:'code', header:'Dim.', width:'80px', tag:true },
      { field:'libelle', header:'Libellé', width:'100px' },
      { field:'questions_cles', header:'Questions clés' },
    ],
    fields: [
      { name:'code', label:'Code', type:'text', required:true, col:3 },
      { name:'libelle', label:'Libellé', type:'text', required:true, col:9 },
      { name:'questions_cles', label:'Questions clés (une par ligne)', type:'textarea', col:12 },
    ],
  },
]

// ── État réactif — un tableau par référentiel, initialisé depuis les props ──
const state = reactive(
  Object.fromEntries(ENTITIES.map(e => [e.prop, [...(props[e.prop] || [])]]))
)

const seeding = ref(false)
const seedMsg = reactive({ text: '', variant: 'success' })

// ── Navigation modules ───────────────────────────────────────────────────
const activeModule = ref('cadrage')
const currentModule = computed(() => MODULES.find(m => m.key === activeModule.value) || MODULES[0])
const entitiesForModule = computed(() => ENTITIES.filter(e => e.module === activeModule.value))

function moduleTotal(mod) {
  return ENTITIES.filter(e => e.module === mod.key)
    .reduce((sum, e) => sum + (state[e.prop]?.length || 0), 0)
}

const totalParams = computed(() => ENTITIES.reduce((sum, e) => sum + (state[e.prop]?.length || 0), 0))
const emptyModulesCount = computed(() => MODULES.filter(m => moduleTotal(m) === 0).length)

function pad(n) { return String(n).padStart(2, '0') }

// Palette accent par module — utilisée pour le rail, les cartes et le bandeau
function colorHex(name) {
  return {
    primary: '#4f46e5',
    success: '#059669',
    info:    '#0891b2',
    danger:  '#dc2626',
    warning: '#d97706',
  }[name] || '#6c757d'
}

// Style des badges de code (fond teinté clair + texte coloré = contraste garanti,
// contrairement au Tag PrimeVue par défaut qui suppose un fond opaque)
function chipStyle(modColor) {
  const hex = colorHex(modColor)
  return {
    backgroundColor: `color-mix(in srgb, ${hex} 14%, white)`,
    color: hex,
  }
}

// ── Modale générique ─────────────────────────────────────────────────────
const modal = reactive({ open: false })
const currentEntity  = ref(null)
const currentEditId  = ref(null)
const form = reactive({})

const modalTitle = computed(() => {
  if (!currentEntity.value) return ''
  return (currentEditId.value ? 'Modifier — ' : 'Ajouter — ') + currentEntity.value.label
})

function resetForm(ent) {
  Object.keys(form).forEach(k => delete form[k])
  ent.fields.forEach(f => { form[f.name] = f.type === 'number' ? null : '' })
}

function openForm(ent) {
  currentEntity.value = ent
  currentEditId.value = null
  resetForm(ent)
  modal.open = true
}

function editItem(ent, data) {
  currentEntity.value = ent
  currentEditId.value = data.id
  resetForm(ent)
  ent.fields.forEach(f => { form[f.name] = data[f.name] ?? (f.type === 'number' ? null : '') })
  modal.open = true
}

function selectOptions(f) {
  if (f.optionsFrom) {
    const src = state[f.optionsFrom] || []
    return [{ value:'', text:'—' }, ...src.map(r => ({ value:r.code, text:`${r.code} — ${r.libelle}` }))]
  }
  return [{ value:'', text:'—' }, ...(f.options || [])]
}

// ── CSRF + fetch helper ──────────────────────────────────────────────────
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? ''

async function apiFetch(url, method, body) {
  const r = await fetch(url, {
    method,
    headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': csrf() },
    body: body ? JSON.stringify(body) : undefined,
  })
  return r.json()
}

// ── Submit / Destroy génériques ──────────────────────────────────────────
async function submitForm() {
  const ent = currentEntity.value
  if (!ent) return

  const payload = {}
  ent.fields.forEach(f => { payload[f.name] = form[f.name] === '' ? null : form[f.name] })

  try {
    if (currentEditId.value) {
      const res = await apiFetch(`${BASE_URL}/${ent.key}/${currentEditId.value}`, 'PUT', payload)
      if (res.success) {
        const idx = state[ent.prop].findIndex(x => x.id === currentEditId.value)
        if (idx !== -1) state[ent.prop][idx] = { ...state[ent.prop][idx], ...payload }
      } else if (res.message) {
        seedMsg.variant = 'danger'; seedMsg.text = res.message
      }
    } else {
      const res = await apiFetch(`${BASE_URL}/${ent.key}`, 'POST', payload)
      if (res.success) {
        state[ent.prop].push({ id: res.id, sort: 999, ...payload })
      } else if (res.message) {
        seedMsg.variant = 'danger'; seedMsg.text = res.message
      }
    }
    modal.open = false
  } catch (e) {
    console.error(e)
  }
}

async function destroyItem(ent, id) {
  if (!confirm('Supprimer ?')) return
  try {
    const res = await apiFetch(`${BASE_URL}/${ent.key}/${id}`, 'DELETE')
    if (res.success) {
      const idx = state[ent.prop].findIndex(x => x.id === id)
      if (idx !== -1) state[ent.prop].splice(idx, 1)
    }
  } catch (e) {
    console.error(e)
  }
}

// ── Reload complet, Seed, Reset ──────────────────────────────────────────
async function reloadAll() {
  const res = await apiFetch(`${BASE_URL}/api/all`, 'GET')
  if (!res) return
  ENTITIES.forEach(ent => { state[ent.prop] = res[ent.prop] ?? [] })
}

async function seedData() {
  if (!confirm('Charger le référentiel ISSAI par défaut ?')) return
  seeding.value = true
  try {
    const res = await apiFetch(`${BASE_URL}/seed`, 'POST')
    if (res.success) { await reloadAll(); seedMsg.variant='success'; seedMsg.text=res.message }
    else             { seedMsg.variant='warning'; seedMsg.text=res.message }
  } catch (e) { seedMsg.variant='danger'; seedMsg.text='Erreur seed.' }
  finally { seeding.value = false }
}

async function resetData() {
  if (!confirm('⚠️ Vider TOUS les référentiels de paramétrage Audit de Performance ?')) return
  seeding.value = true
  try {
    const res = await apiFetch(`${BASE_URL}/reset`, 'POST')
    if (res.success) { await reloadAll(); seedMsg.variant='info'; seedMsg.text=res.message }
  } catch (e) { seedMsg.variant='danger'; seedMsg.text='Erreur reset.' }
  finally { seeding.value = false }
}
</script>

<style scoped>
.form-control-sm,.form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.btn-sm { padding:.15rem .45rem; font-size:.72rem }
.pv-table :deep(.p-datatable-thead>tr>th) { background:#f8fafc; border:1px solid #e5e7eb; padding:.25rem .35rem; font-size:.74rem }
.pv-table :deep(.p-datatable-tbody>tr>td) { border:1px solid #eef2f7; padding:.25rem .35rem; font-size:.72rem }

/* ── En-tête de page ────────────────────────────────────────────────── */
.ap-page-header { padding:.25rem 0 .5rem }
.ap-eyebrow { font-size:.66rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#94a3b8; margin-bottom:.15rem }
.ap-kpi { display:flex; flex-direction:column; align-items:flex-end; line-height:1.1 }
.ap-kpi-value { font-size:1.15rem; font-weight:800; color:#1e293b }
.ap-kpi-label { font-size:.62rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em }

/* ── Rail des modules ───────────────────────────────────────────────── */
.ap-rail-card { overflow:hidden }
.ap-rail-title { font-size:.66rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#94a3b8; padding:.75rem .9rem .35rem }
.ap-module-item {
  display:grid;
  grid-template-columns:26px 22px 1fr auto;
  align-items:center;
  gap:.55rem;
  width:100%;
  border:none;
  border-left:3px solid transparent;
  background:transparent;
  text-align:left;
  padding:.6rem .8rem;
  cursor:pointer;
  transition:background .15s, border-color .15s;
}
.ap-module-item:hover { background:#f8fafc }
.ap-module-item.active {
  background: color-mix(in srgb, var(--mod-color) 8%, white);
  border-left-color: var(--mod-color);
}
.ap-module-num {
  width:22px; height:22px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:.6rem; font-weight:800;
  background:#f1f5f9; color:#94a3b8;
}
.ap-module-item.active .ap-module-num { background:var(--mod-color); color:#fff }
.ap-module-icon { font-size:1rem; color:#94a3b8 }
.ap-module-item.active .ap-module-icon { color:var(--mod-color) }
.ap-module-text { display:flex; flex-direction:column; line-height:1.25; min-width:0 }
.ap-module-text strong { font-size:.78rem; color:#334155; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.ap-module-text small { font-size:.64rem; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.ap-module-count {
  font-size:.66rem; font-weight:800; min-width:22px; text-align:center;
  background:#f1f5f9; color:#94a3b8; border-radius:20px; padding:.15rem .4rem;
}
.ap-module-item.active .ap-module-count {
  background: color-mix(in srgb, var(--mod-color) 16%, white);
  color: var(--mod-color);
}
.ap-help-card { background:#fafafa; border:1px dashed #e2e8f0 }

/* ── Bandeau d'entête du module actif ───────────────────────────────── */
.ap-module-header {
  display:flex; align-items:center; gap:.85rem;
  padding:.9rem 1.1rem; border-radius:10px; margin-bottom:.75rem;
  background: color-mix(in srgb, var(--mod-color) 6%, white);
  border:1px solid color-mix(in srgb, var(--mod-color) 20%, white);
}
.ap-module-header i { font-size:1.65rem; color:var(--mod-color) }

.ap-code-chip {
  display:inline-block;
  font-size:.68rem;
  font-weight:700;
  padding:.2rem .55rem;
  border-radius:5px;
  white-space:nowrap;
  line-height:1.3;
}
</style>