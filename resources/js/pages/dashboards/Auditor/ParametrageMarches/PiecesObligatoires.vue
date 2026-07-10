<template>
  <VerticalLayout>
    <Head title="Paramètres ADM — Pièces obligatoires" />

    <!-- ── HEADER ─────────────────────────────────────────────────── -->
    <b-row class="mb-0 align-items-center">
      <b-col>
        <div class="d-flex align-items-center gap-2">
          <i class="ti ti-files text-primary fs-5"></i>
          <h4 class="m-0 fw-semibold">Pièces obligatoires — Disponibilité documentaire</h4>
          <small class="text-muted ms-2">Passation / Exécution / Matérialité</small>
        </div>
      </b-col>
      <b-col cols="auto" class="d-flex gap-2">
        <b-button size="sm" variant="outline-danger" @click="resetData" :disabled="seeding">
          <i class="ti ti-trash me-1"></i>Reset
        </b-button>
        <b-button size="sm" variant="outline-warning" @click="seedData" :disabled="seeding">
          <i class="ti ti-plant me-1"></i>{{ seeding ? 'Initialisation…' : 'Initialiser' }}
        </b-button>
      </b-col>
    </b-row>

    <b-alert v-if="seedMsg.text" :variant="seedMsg.variant" dismissible @dismissed="seedMsg.text=''" class="py-2 px-3 mt-2" style="font-size:.78rem">
      {{ seedMsg.text }}
    </b-alert>

    <!-- ── STATS ──────────────────────────────────────────────────── -->
    <b-row class="g-2 mb-2 mt-1">
      <b-col lg="3" v-for="s in statsCards" :key="s.label">
        <b-card no-body class="shadow-sm stat-card">
          <b-card-body class="p-2">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon" :class="'bg-'+s.color"><i :class="s.icon"></i></div>
              <div>
                <small class="text-muted d-block" style="font-size:.65rem;line-height:1.1">{{ s.label }}</small>
                <span class="fw-bold">{{ s.count }}</span>
              </div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ── TABS ───────────────────────────────────────────────────── -->
    <b-card no-body class="mb-2 shadow-none border-0">
      <b-card-body class="p-1">
        <b-button-group size="sm">
          <b-button v-for="t in tabs" :key="t.key" @click="activeTab=t.key"
            :variant="activeTab===t.key?'primary':'outline-primary'">
            <i :class="t.icon+' me-1'"></i>{{ t.label }}
          </b-button>
        </b-button-group>
      </b-card-body>
    </b-card>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 1 : CATÉGORIES & PIÈCES
    ══════════════════════════════════════════════════════════════ -->
    <b-row v-if="activeTab==='categories'" class="g-2">
      <b-col cols="12" class="d-flex justify-content-end">
        <b-button size="sm" variant="primary" @click="openCategorieForm()"><i class="ti ti-plus me-1"></i>Nouvelle catégorie</b-button>
      </b-col>
      <b-col lg="4" v-for="cat in categories" :key="cat.id">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-start">
            <div>
              <span class="badge bg-primary mb-1" style="font-size:.65rem">{{ cat.code }}</span>
              <h6 class="mb-0" style="font-size:.85rem">{{ cat.libelle }}</h6>
              <small class="text-muted">{{ piecesForCategorie(cat.id).length }} pièce(s)</small>
            </div>
            <div class="d-flex gap-1">
              <b-button size="sm" variant="light" class="py-0 px-1" @click="openCategorieForm(cat)"><i class="ti ti-pencil" style="font-size:.7rem"></i></b-button>
              <b-button size="sm" variant="light" class="py-0 px-1" @click="destroyCategorie(cat.id)"><i class="ti ti-trash text-danger" style="font-size:.7rem"></i></b-button>
            </div>
          </b-card-header>
          <b-card-body class="p-0" style="max-height:420px;overflow-y:auto">
            <div v-for="p in piecesForCategorie(cat.id)" :key="p.id"
                 class="d-flex justify-content-between align-items-center px-3 py-2 piece-row">
              <div style="min-width:0">
                <div class="text-truncate" style="font-size:.73rem;font-weight:600">{{ p.libelle }}</div>
                <div class="d-flex align-items-center gap-1 mt-1">
                  <code style="font-size:.6rem" class="text-muted">{{ p.code }}</code>
                  <span class="badge" :class="p.incidence==='directe' ? 'bg-danger' : 'bg-secondary'" style="font-size:.58rem">
                    {{ p.incidence==='directe' ? 'Incidence directe' : 'Sans incidence' }}
                  </span>
                </div>
              </div>
              <div class="d-flex gap-1 flex-shrink-0 ms-2">
                <b-button size="sm" variant="light" class="py-0 px-1" @click="openPieceForm(p)"><i class="ti ti-pencil" style="font-size:.65rem"></i></b-button>
                <b-button size="sm" variant="light" class="py-0 px-1" @click="destroyPiece(p.id)"><i class="ti ti-trash text-danger" style="font-size:.65rem"></i></b-button>
              </div>
            </div>
            <div v-if="!piecesForCategorie(cat.id).length" class="text-muted text-center py-3 small">
              Aucune pièce dans cette catégorie
            </div>
          </b-card-body>
          <b-card-footer class="p-2 text-center">
            <b-button size="sm" variant="outline-primary" @click="openPieceForm(null, cat.id)">
              <i class="ti ti-plus me-1"></i>Ajouter une pièce
            </b-button>
          </b-card-footer>
        </b-card>
      </b-col>
      <b-col cols="12" v-if="!categories.length">
        <b-card no-body class="shadow-sm"><b-card-body class="text-center text-muted py-4">Aucune catégorie — initialisez ou créez-en une</b-card-body></b-card>
      </b-col>
    </b-row>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 2 : GRILLE D'APPRÉCIATION
    ══════════════════════════════════════════════════════════════ -->
    <b-row v-if="activeTab==='grille'" class="g-2">
      <b-col cols="12">
        <b-alert variant="info" class="py-2 px-3 mb-0" style="font-size:.78rem">
          <i class="ti ti-info-circle me-1"></i>
          X = % d'<strong>absence</strong> des pièces obligatoires (100 − taux de complétude). Chaque plage donne une appréciation qualitative affichée dans le module mission.
        </b-alert>
      </b-col>
      <b-col cols="12">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-gauge me-1 text-warning"></i>Grille d'appréciation de la disponibilité</h6>
            <b-button size="sm" variant="warning" @click="openGrilleForm()"><i class="ti ti-plus me-1"></i>Ajouter une plage</b-button>
          </b-card-header>
          <b-card-body class="p-0">
            <DataTable :value="grilleAppreciation" size="small" class="pv-table flat">
              <Column header="#" style="width:35px" bodyClass="text-center"><template #body="{data}"><small class="text-muted">{{ data.sort }}</small></template></Column>
              <Column header="Plage de % d'absence" style="width:220px">
                <template #body="{data}"><span class="font-monospace" style="font-size:.75rem">{{ formatBorne(data) }}</span></template>
              </Column>
              <Column header="Appréciation">
                <template #body="{data}">
                  <div class="d-flex align-items-center gap-2">
                    <span class="seuil-dot" :class="'dot-'+(data.couleur||'gray')"></span>
                    <span class="fw-semibold" style="font-size:.78rem">{{ data.appreciation }}</span>
                  </div>
                </template>
              </Column>
              <Column header="" style="width:55px" bodyClass="text-end">
                <template #body="{data}">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="light" @click="openGrilleForm(data)"><i class="ti ti-pencil"></i></b-button>
                    <b-button size="sm" variant="light" @click="destroyGrilleRule(data.id)"><i class="ti ti-trash text-danger"></i></b-button>
                  </div>
                </template>
              </Column>
              <template #empty><div class="text-muted py-3 px-3 small">Aucune plage — initialiser ou ajouter</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 3 : PARAMÈTRES D'AUDIT
    ══════════════════════════════════════════════════════════════ -->
    <b-row v-if="activeTab==='parametres'" class="g-2">
      <b-col cols="12">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0"><i class="ti ti-adjustments-alt me-1 text-secondary"></i>Paramètres numériques d'audit</h6>
              <small class="text-muted">Seuils / plafonds hors grilles (ex: seuil d'auditabilité)</small>
            </div>
            <b-button size="sm" variant="secondary" @click="openParametreForm()"><i class="ti ti-plus me-1"></i>Ajouter</b-button>
          </b-card-header>
          <b-card-body class="p-0">
            <DataTable :value="parametresAudit" size="small" class="pv-table flat">
              <Column header="Code" style="width:180px"><template #body="{data}"><code style="font-size:.68rem">{{ data.code }}</code></template></Column>
              <Column header="Libellé" field="libelle"/>
              <Column header="Valeur" style="width:120px" bodyClass="text-center">
                <template #body="{data}"><span class="fw-bold" style="font-size:.9rem">{{ data.valeur }}</span> <small class="text-muted">{{ data.unite }}</small></template>
              </Column>
              <Column header="Description"><template #body="{data}"><small class="text-muted">{{ data.description }}</small></template></Column>
              <Column header="" style="width:55px" bodyClass="text-end">
                <template #body="{data}">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="light" @click="openParametreForm(data)"><i class="ti ti-pencil"></i></b-button>
                    <b-button size="sm" variant="light" @click="destroyParametre(data.id)"><i class="ti ti-trash text-danger"></i></b-button>
                  </div>
                </template>
              </Column>
              <template #empty><div class="text-muted py-3 px-3 small">Aucun paramètre — initialiser ou ajouter</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ══════════════════════════════════════════════════════════════
         MODALES
    ══════════════════════════════════════════════════════════════ -->

    <!-- Catégorie -->
    <b-modal v-model="modal.categorie" :title="catForm.id ? 'Modifier la catégorie' : 'Nouvelle catégorie'" hide-footer>
      <b-form @submit.prevent="submitCategorie">
        <b-row class="g-2">
          <b-col cols="4"><label class="form-label mb-1">Code *</label><b-form-input class="form-control-sm" v-model.trim="catForm.code" required placeholder="PASSATION"/></b-col>
          <b-col cols="8"><label class="form-label mb-1">Libellé *</label><b-form-input class="form-control-sm" v-model.trim="catForm.libelle" required/></b-col>
          <b-col cols="12"><label class="form-label mb-1">Description</label><b-form-textarea class="form-control-sm" rows="2" v-model.trim="catForm.description"/></b-col>
        </b-row>
        <div class="text-end mt-3">
          <b-button variant="light" class="me-2" @click="modal.categorie=false">Annuler</b-button>
          <b-button variant="primary" type="submit">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

    <!-- Pièce -->
    <b-modal v-model="modal.piece" :title="pieceForm.id ? 'Modifier la pièce' : 'Nouvelle pièce'" hide-footer>
      <b-form @submit.prevent="submitPiece">
        <b-row class="g-2">
          <b-col cols="12">
            <label class="form-label mb-1">Catégorie *</label>
            <b-form-select class="form-select-sm" v-model="pieceForm.categorie_id" required
              :options="categories.map(c=>({value:c.id,text:c.code+' — '+c.libelle}))"/>
          </b-col>
          <b-col cols="5"><label class="form-label mb-1">Code *</label><b-form-input class="form-control-sm" v-model.trim="pieceForm.code" required placeholder="PPMP_VALIDE"/></b-col>
          <b-col cols="7"><label class="form-label mb-1">Libellé *</label><b-form-input class="form-control-sm" v-model.trim="pieceForm.libelle" required/></b-col>
          <b-col cols="6">
            <label class="form-label mb-1">Incidence *</label>
            <b-form-select class="form-select-sm" v-model="pieceForm.incidence"
              :options="[{value:'directe',text:'Incidence directe'},{value:'sans_incidence',text:'Sans incidence'}]"/>
          </b-col>
          <b-col cols="6">
            <label class="form-label mb-1">Obligatoire</label>
            <b-form-select class="form-select-sm" v-model="pieceForm.obligatoire" :options="[{value:1,text:'Oui'},{value:0,text:'Non'}]"/>
          </b-col>
        </b-row>
        <div class="text-end mt-3">
          <b-button variant="light" class="me-2" @click="modal.piece=false">Annuler</b-button>
          <b-button variant="primary" type="submit">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

    <!-- Grille d'appréciation -->
    <b-modal v-model="modal.grille" :title="grilleForm.id ? 'Modifier la plage' : 'Nouvelle plage'" hide-footer size="lg">
      <b-form @submit.prevent="submitGrille">
        <b-row class="g-2">
          <b-col cols="3"><label class="form-label mb-1">Op. min</label><b-form-select class="form-select-sm" v-model="grilleForm.operateur_min" :options="operateurs"/></b-col>
          <b-col cols="3"><label class="form-label mb-1">Borne min (%)</label><b-form-input class="form-control-sm" type="number" step="0.01" v-model.number="grilleForm.borne_min"/></b-col>
          <b-col cols="3"><label class="form-label mb-1">Op. max</label><b-form-select class="form-select-sm" v-model="grilleForm.operateur_max" :options="operateurs"/></b-col>
          <b-col cols="3"><label class="form-label mb-1">Borne max (%)</label><b-form-input class="form-control-sm" type="number" step="0.01" v-model.number="grilleForm.borne_max"/></b-col>
          <b-col cols="8"><label class="form-label mb-1">Appréciation *</label><b-form-input class="form-control-sm" v-model.trim="grilleForm.appreciation" required placeholder="Satisfaisante"/></b-col>
          <b-col cols="4">
            <label class="form-label mb-1">Couleur</label>
            <div class="d-flex gap-2 mt-1">
              <span v-for="c in couleurs" :key="c.val" class="couleur-swatch" :class="['swatch-'+c.val, grilleForm.couleur===c.val?'swatch-selected':'']" @click="grilleForm.couleur=c.val" :title="c.label"></span>
            </div>
          </b-col>
        </b-row>
        <b-alert variant="light" class="py-2 px-3 mt-3 mb-0" style="font-size:.78rem;border-left:3px solid #17a2b8">
          <strong>Aperçu :</strong> {{ formatBorne(grilleForm) }} → {{ grilleForm.appreciation || '…' }}
        </b-alert>
        <div class="text-end mt-3">
          <b-button variant="light" class="me-2" @click="modal.grille=false">Annuler</b-button>
          <b-button variant="primary" type="submit">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

    <!-- Paramètre d'audit -->
    <b-modal v-model="modal.parametre" :title="paramForm.id ? 'Modifier le paramètre' : 'Nouveau paramètre'" hide-footer>
      <b-form @submit.prevent="submitParametre">
        <b-row class="g-2">
          <b-col cols="12"><label class="form-label mb-1">Code *</label><b-form-input class="form-control-sm" v-model.trim="paramForm.code" required placeholder="SEUIL_AUDITABILITE" :disabled="!!paramForm.id"/></b-col>
          <b-col cols="12"><label class="form-label mb-1">Libellé *</label><b-form-input class="form-control-sm" v-model.trim="paramForm.libelle" required/></b-col>
          <b-col cols="6"><label class="form-label mb-1">Valeur *</label><b-form-input class="form-control-sm" type="number" step="0.01" v-model.number="paramForm.valeur" required/></b-col>
          <b-col cols="6"><label class="form-label mb-1">Unité</label><b-form-input class="form-control-sm" v-model.trim="paramForm.unite" placeholder="%"/></b-col>
          <b-col cols="12"><label class="form-label mb-1">Description</label><b-form-textarea class="form-control-sm" rows="2" v-model.trim="paramForm.description"/></b-col>
        </b-row>
        <div class="text-end mt-3">
          <b-button variant="light" class="me-2" @click="modal.parametre=false">Annuler</b-button>
          <b-button variant="primary" type="submit">Enregistrer</b-button>
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

const props = defineProps({
  categories:         { type: Array, default: () => [] },
  pieces:             { type: Array, default: () => [] },
  grilleAppreciation: { type: Array, default: () => [] },
  parametresAudit:    { type: Array, default: () => [] },
  missionsCount:      { type: Number, default: 0 },
  activeTab:          { type: String, default: 'categories' },
})

const categories         = ref([...props.categories])
const pieces             = ref([...props.pieces])
const grilleAppreciation = ref([...props.grilleAppreciation])
const parametresAudit    = ref([...props.parametresAudit])

const activeTab = ref(props.activeTab)
const seeding   = ref(false)
const seedMsg   = reactive({ text: '', variant: 'success' })

const tabs = [
  { key: 'categories', label: 'Catégories & Pièces', icon: 'ti ti-files' },
  { key: 'grille',     label: "Grille d'appréciation", icon: 'ti ti-gauge' },
  { key: 'parametres', label: "Paramètres d'audit",   icon: 'ti ti-adjustments-alt' },
]

const statsCards = computed(() => [
  { label: 'Catégories',   count: categories.value.length, color: 'primary',  icon: 'ti ti-folder' },
  { label: 'Pièces',       count: pieces.value.length,     color: 'warning',  icon: 'ti ti-files' },
  { label: "Plages d'appréciation", count: grilleAppreciation.value.length, color: 'info', icon: 'ti ti-gauge' },
  { label: 'Vérifications de mission', count: props.missionsCount, color: 'success', icon: 'ti ti-clipboard-check' },
])

const piecesForCategorie = catId => pieces.value.filter(p => p.categorie_id === catId).sort((a,b) => a.sort - b.sort)

const operateurs = [{ value: '', text: '—' }, '>', '>=', '<', '<=', '=']
const couleurs   = [{ val:'green',label:'Vert' },{ val:'blue',label:'Bleu' },{ val:'orange',label:'Orange' },{ val:'red',label:'Rouge' },{ val:'gray',label:'Gris' }]

function formatBorne(r) {
  const p = []
  if (r.operateur_min && r.borne_min != null && r.borne_min !== '') p.push(`${r.operateur_min} ${r.borne_min}%`)
  if (r.operateur_max && r.borne_max != null && r.borne_max !== '') p.push(`${r.operateur_max} ${r.borne_max}%`)
  return p.length ? p.join(' et ') : '—'
}

// ── CSRF / fetch ───────────────────────────────────────────────────
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? ''
async function apiFetch(url, method, body) {
  const r = await fetch(url, {
    method,
    headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': csrf() },
    body: body ? JSON.stringify(body) : undefined,
  })
  return r.json()
}

// ── Modales ────────────────────────────────────────────────────────
const modal = reactive({ categorie: false, piece: false, grille: false, parametre: false })

// -- Catégorie --
const catForm = reactive({ id: null, code: '', libelle: '', description: '' })
function openCategorieForm(cat = null) {
  if (cat) Object.assign(catForm, { id: cat.id, code: cat.code, libelle: cat.libelle, description: cat.description || '' })
  else Object.assign(catForm, { id: null, code: '', libelle: '', description: '' })
  modal.categorie = true
}
async function submitCategorie() {
  const payload = { code: catForm.code, libelle: catForm.libelle, description: catForm.description || null }
  if (catForm.id) {
    const res = await apiFetch(`/m/audit.core/pieces-obligatoires/categories/${catForm.id}`, 'PUT', payload)
    if (res.success) {
      const idx = categories.value.findIndex(c => c.id === catForm.id)
      if (idx !== -1) Object.assign(categories.value[idx], payload)
    }
  } else {
    const res = await apiFetch('/m/audit.core/pieces-obligatoires/categories', 'POST', payload)
    if (res.success) categories.value.push({ id: res.id, ...payload, actif: 1, sort: 999 })
  }
  modal.categorie = false
}
async function destroyCategorie(id) {
  if (!confirm('Supprimer cette catégorie ?')) return
  const res = await apiFetch(`/m/audit.core/pieces-obligatoires/categories/${id}`, 'DELETE')
  if (res.success) categories.value = categories.value.filter(c => c.id !== id)
  else if (res.message) alert(res.message)
}

// -- Pièce --
const pieceForm = reactive({ id: null, categorie_id: null, code: '', libelle: '', incidence: 'directe', obligatoire: 1 })
function openPieceForm(piece = null, presetCategorieId = null) {
  if (piece) Object.assign(pieceForm, { id: piece.id, categorie_id: piece.categorie_id, code: piece.code, libelle: piece.libelle, incidence: piece.incidence, obligatoire: piece.obligatoire })
  else Object.assign(pieceForm, { id: null, categorie_id: presetCategorieId, code: '', libelle: '', incidence: 'directe', obligatoire: 1 })
  modal.piece = true
}
async function submitPiece() {
  const payload = {
    categorie_id: pieceForm.categorie_id, code: pieceForm.code, libelle: pieceForm.libelle,
    incidence: pieceForm.incidence, obligatoire: pieceForm.obligatoire,
  }
  if (pieceForm.id) {
    const res = await apiFetch(`/m/audit.core/pieces-obligatoires/pieces/${pieceForm.id}`, 'PUT', payload)
    if (res.success) {
      const idx = pieces.value.findIndex(p => p.id === pieceForm.id)
      if (idx !== -1) Object.assign(pieces.value[idx], payload)
    }
  } else {
    const res = await apiFetch('/m/audit.core/pieces-obligatoires/pieces', 'POST', payload)
    if (res.success) pieces.value.push({ id: res.id, ...payload, actif: 1, sort: 999 })
  }
  modal.piece = false
}
async function destroyPiece(id) {
  if (!confirm('Supprimer cette pièce ?')) return
  const res = await apiFetch(`/m/audit.core/pieces-obligatoires/pieces/${id}`, 'DELETE')
  if (res.success) pieces.value = pieces.value.filter(p => p.id !== id)
  else if (res.message) alert(res.message)
}

// -- Grille d'appréciation --
const grilleForm = reactive({ id: null, borne_min: null, operateur_min: '', borne_max: null, operateur_max: '', appreciation: '', couleur: 'gray' })
function openGrilleForm(rule = null) {
  if (rule) Object.assign(grilleForm, { id: rule.id, borne_min: rule.borne_min, operateur_min: rule.operateur_min || '', borne_max: rule.borne_max, operateur_max: rule.operateur_max || '', appreciation: rule.appreciation, couleur: rule.couleur || 'gray' })
  else Object.assign(grilleForm, { id: null, borne_min: null, operateur_min: '', borne_max: null, operateur_max: '', appreciation: '', couleur: 'gray' })
  modal.grille = true
}
async function submitGrille() {
  const payload = {
    borne_min: grilleForm.borne_min, operateur_min: grilleForm.operateur_min || null,
    borne_max: grilleForm.borne_max, operateur_max: grilleForm.operateur_max || null,
    appreciation: grilleForm.appreciation, couleur: grilleForm.couleur,
  }
  if (grilleForm.id) {
    const res = await apiFetch(`/m/audit.core/pieces-obligatoires/grille-appreciation/${grilleForm.id}`, 'PUT', payload)
    if (res.success) {
      const idx = grilleAppreciation.value.findIndex(r => r.id === grilleForm.id)
      if (idx !== -1) Object.assign(grilleAppreciation.value[idx], payload)
    }
  } else {
    const res = await apiFetch('/m/audit.core/pieces-obligatoires/grille-appreciation', 'POST', payload)
    if (res.success) grilleAppreciation.value.push({ id: res.id, ...payload, actif: 1, sort: 999 })
  }
  modal.grille = false
}
async function destroyGrilleRule(id) {
  if (!confirm('Supprimer cette plage ?')) return
  const res = await apiFetch(`/m/audit.core/pieces-obligatoires/grille-appreciation/${id}`, 'DELETE')
  if (res.success) grilleAppreciation.value = grilleAppreciation.value.filter(r => r.id !== id)
}

// -- Paramètre d'audit --
const paramForm = reactive({ id: null, code: '', libelle: '', valeur: null, unite: '%', description: '' })
function openParametreForm(p = null) {
  if (p) Object.assign(paramForm, { id: p.id, code: p.code, libelle: p.libelle, valeur: p.valeur, unite: p.unite || '%', description: p.description || '' })
  else Object.assign(paramForm, { id: null, code: '', libelle: '', valeur: null, unite: '%', description: '' })
  modal.parametre = true
}
async function submitParametre() {
  const payload = { code: paramForm.code, libelle: paramForm.libelle, valeur: paramForm.valeur, unite: paramForm.unite || '%', description: paramForm.description || null }
  if (paramForm.id) {
    const res = await apiFetch(`/m/audit.core/pieces-obligatoires/parametres-audit/${paramForm.id}`, 'PUT', payload)
    if (res.success) {
      const idx = parametresAudit.value.findIndex(p => p.id === paramForm.id)
      if (idx !== -1) Object.assign(parametresAudit.value[idx], payload)
    }
  } else {
    const res = await apiFetch('/m/audit.core/pieces-obligatoires/parametres-audit', 'POST', payload)
    if (res.success) parametresAudit.value.push({ id: res.id, ...payload, sort: 999 })
  }
  modal.parametre = false
}
async function destroyParametre(id) {
  if (!confirm('Supprimer ce paramètre ?')) return
  const res = await apiFetch(`/m/audit.core/pieces-obligatoires/parametres-audit/${id}`, 'DELETE')
  if (res.success) parametresAudit.value = parametresAudit.value.filter(p => p.id !== id)
}

// ── Reload / Seed / Reset ─────────────────────────────────────────
async function reloadAll() {
  const res = await apiFetch('/m/audit.core/pieces-obligatoires/api/all', 'GET')
  if (!res) return
  categories.value         = res.categories         ?? []
  pieces.value             = res.pieces             ?? []
  grilleAppreciation.value = res.grilleAppreciation ?? []
  parametresAudit.value    = res.parametresAudit    ?? []
}

async function seedData() {
  if (!confirm('Initialiser les pièces obligatoires (pieces_obligatoires.sql) ?')) return
  seeding.value = true
  try {
    const res = await apiFetch('/m/audit.core/pieces-obligatoires/seed', 'POST')
    if (res.success) { await reloadAll(); seedMsg.variant='success'; seedMsg.text=res.message }
    else             { seedMsg.variant='warning'; seedMsg.text=res.message }
  } catch(e) { seedMsg.variant='danger'; seedMsg.text='Erreur seed.' }
  finally { seeding.value=false }
}

async function resetData() {
  if (!confirm('⚠️ Vider TOUTES les tables pièces obligatoires ?')) return
  seeding.value = true
  try {
    const res = await apiFetch('/m/audit.core/pieces-obligatoires/reset', 'POST')
    if (res.success) { await reloadAll(); seedMsg.variant='info'; seedMsg.text=res.message }
  } catch(e) { seedMsg.variant='danger'; seedMsg.text='Erreur reset.' }
  finally { seeding.value=false }
}
</script>

<style scoped>
.form-control-sm,.form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.stat-card { border-left:3px solid transparent }
.stat-icon { width:32px; height:32px; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:15px }
.pv-table :deep(.p-datatable-thead>tr>th) { background:#f8fafc; border:1px solid #e5e7eb; padding:.25rem .35rem; font-size:.74rem }
.pv-table :deep(.p-datatable-tbody>tr>td) { border:1px solid #eef2f7; padding:.25rem .35rem; font-size:.72rem }
.piece-row { border-bottom:1px solid #f5f5f5 }
.piece-row:hover { background:#f8fbff }
.seuil-dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0 }
.dot-green{background:#28a745}.dot-blue{background:#007bff}.dot-orange{background:#fd7e14}.dot-red{background:#dc3545}.dot-gray{background:#6c757d}
.couleur-swatch{width:22px;height:22px;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:all .15s}
.couleur-swatch:hover{transform:scale(1.15)}.swatch-selected{border-color:#333!important;transform:scale(1.2)}
.swatch-green{background:#28a745}.swatch-blue{background:#007bff}.swatch-orange{background:#fd7e14}.swatch-red{background:#dc3545}.swatch-gray{background:#6c757d}
</style>