<template>
  <VerticalLayout>
    <Head title="Paramètres ADM — Grilles de vérification ARMP" />

    <!-- ── HEADER ─────────────────────────────────────────────────── -->
    <b-row class="mb-0 align-items-center">
      <b-col>
        <div class="d-flex align-items-center gap-2">
          <i class="ti ti-checklist text-primary fs-5"></i>
          <h4 class="m-0 fw-semibold">Grilles de vérification — ARMP</h4>
          <small class="text-muted ms-2">Paramétrage par mode de passation</small>
        </div>
      </b-col>
      <b-col cols="auto" class="d-flex gap-2">
        <b-button size="sm" variant="outline-danger" @click="resetData" :disabled="seeding">
          <i class="ti ti-trash me-1"></i>Reset
        </b-button>
        <b-button size="sm" variant="outline-warning" @click="seedData" :disabled="seeding">
          <i class="ti ti-plant me-1"></i>{{ seeding ? 'Initialisation…' : 'Initialiser (SQL ARMP)' }}
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

    <!-- ══════════════════════════════════════════════════════════════
         NAVIGATION PRINCIPALE — PAR MODE DE PASSATION
         (même logique que l'onglet "Modes de passation" du module Seuils/Délais)
    ══════════════════════════════════════════════════════════════ -->
    <b-card no-body class="shadow-sm mb-2">
      <b-card-header class="py-2 px-3">
        <small class="text-muted d-block mb-1">Sélectionnez un mode de passation</small>
        <b-button-group size="sm">
          <b-button v-for="m in modeTabs" :key="m.code"
            @click="activeMode = m.code"
            :variant="activeMode === m.code ? 'primary' : 'outline-primary'">
            {{ m.code }}
            <span class="badge bg-light text-dark ms-1" style="font-size:.6rem">{{ grillesForMode(m.code).length }}</span>
          </b-button>
        </b-button-group>
      </b-card-header>
    </b-card>

    <b-row class="g-2" v-if="activeMode">
      <!-- ══════════════════════════════════════════════════════════
           COLONNE GAUCHE — Grilles applicables à ce mode (par nature)
      ══════════════════════════════════════════════════════════ -->
      <b-col lg="5">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0">
                Mode <span class="badge" :class="modeBadgeClass(activeMode)">{{ activeMode }}</span>
                — {{ modeLibelle(activeMode) }}
              </h6>
              <small class="text-muted">Grilles rattachées à ce mode, regroupées par nature de marché</small>
            </div>
            <b-button size="sm" variant="primary" @click="openGrilleForm(null, activeMode)"><i class="ti ti-plus"></i></b-button>
          </b-card-header>
          <b-card-body class="p-0" style="max-height:650px;overflow-y:auto">
            <div v-for="nat in naturesForMode" :key="nat.key" class="nature-block">
              <div class="nature-header px-3 py-2">
                <span class="badge bg-warning text-dark" style="font-size:.65rem">{{ nat.label }}</span>
              </div>
              <div v-for="g in nat.grilles" :key="g.id"
                   class="grille-row px-3 py-2"
                   :class="selectedGrille?.id === g.id ? 'grille-active' : ''"
                   @click="selectGrille(g)">
                <div class="d-flex justify-content-between align-items-start">
                  <div style="flex:1;min-width:0">
                    <div class="d-flex align-items-center gap-1">
                      <span class="badge bg-dark" style="font-size:.6rem">{{ g.code }}</span>
                      <span v-if="g.avec_prequalification===1" class="badge bg-info text-dark" style="font-size:.58rem">Avec PQ</span>
                      <span v-else-if="g.avec_prequalification===0" class="badge bg-secondary" style="font-size:.58rem">Sans PQ</span>
                    </div>
                    <div class="text-truncate mt-1" style="font-size:.71rem;font-weight:600">{{ g.intitule }}</div>

                    <!-- Organes rattachés à cette grille (badge + dropdown, même pattern que Modes de passation) -->
                    <div class="d-flex flex-wrap gap-1 align-items-center mt-1" @click.stop>
                      <span v-for="oc in getGrilleOrganes(g.id)" :key="oc"
                            class="badge bg-success d-flex align-items-center gap-1"
                            style="font-size:.6rem;padding:.2rem .4rem;cursor:pointer"
                            @click="removeGrilleOrgane(g.id, oc)">
                        {{ oc }} <i class="ti ti-x" style="font-size:.55rem"></i>
                      </span>
                      <b-dropdown size="sm" variant="outline-success" no-caret toggle-class="py-0 px-1" boundary="viewport">
                        <template #button-content><i class="ti ti-plus" style="font-size:.6rem"></i></template>
                        <b-dropdown-header>Organe compétent pour cette grille</b-dropdown-header>
                        <b-dropdown-item
                          v-for="o in organes.filter(og => !getGrilleOrganes(g.id).includes(og.code))"
                          :key="o.code" @click="addGrilleOrgane(g.id, o.code)" style="font-size:.72rem">
                          <span class="badge bg-success me-1" style="font-size:.6rem">{{ o.sigle||o.code }}</span>{{ o.libelle }}
                        </b-dropdown-item>
                      </b-dropdown>
                      <span class="badge bg-light text-muted border ms-auto" style="font-size:.58rem">{{ itemCount(g.id) }} pts</span>
                    </div>
                  </div>
                  <div class="d-flex gap-1 ms-2">
                    <b-button size="sm" variant="light" class="py-0 px-1" @click.stop="openGrilleForm(g)"><i class="ti ti-pencil" style="font-size:.65rem"></i></b-button>
                    <b-button size="sm" variant="light" class="py-0 px-1" @click.stop="destroyGrille(g.id)"><i class="ti ti-trash text-danger" style="font-size:.65rem"></i></b-button>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="!naturesForMode.length" class="text-muted text-center py-4 small">
              Aucune grille rattachée au mode {{ activeMode }} — cliquez + pour en créer une
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- ══════════════════════════════════════════════════════════
           COLONNE DROITE — Items de la grille sélectionnée
      ══════════════════════════════════════════════════════════ -->
      <b-col lg="7">
        <b-card v-if="selectedGrille" no-body class="shadow-sm">
          <b-card-header class="py-2 px-3">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="mb-1">{{ selectedGrille.code }} — {{ selectedGrille.intitule }}</h6>
                <div class="d-flex flex-wrap gap-1">
                  <span v-if="selectedGrille.nature_marche_code" class="badge bg-warning text-dark">
                    {{ natureLibelle(selectedGrille.nature_marche_code) }}
                  </span>
                  <span v-else class="badge bg-light text-dark border">Toutes natures</span>
                  <span class="badge" :class="modeBadgeClass(selectedGrille.mode_passation_code)">
                    {{ selectedGrille.mode_passation_code || 'Tous modes' }}
                  </span>
                  <span v-for="oc in getGrilleOrganes(selectedGrille.id)" :key="oc" class="badge bg-success">{{ oc }}</span>
                </div>
              </div>
              <b-button size="sm" variant="primary" @click="openItemForm()">
                <i class="ti ti-plus me-1"></i>Point de contrôle
              </b-button>
              <b-button size="sm" variant="outline-info" class="ms-1" @click="analyserGrilleIA" :disabled="analysingGrille">
                <i class="ti ti-sparkles me-1"></i>{{ analysingGrille ? 'Analyse en cours…' : 'Analyser toute la grille (IA)' }}
              </b-button>
            </div>
          </b-card-header>
          <b-card-body class="p-0" style="max-height:610px;overflow-y:auto">
            <DataTable :value="selectedItems" size="small" class="pv-table flat">
              <Column header="N°" style="width:55px" bodyClass="text-center">
                <template #body="{data}"><span class="fw-bold" style="font-size:.72rem">{{ data.numero }}</span></template>
              </Column>
              <Column header="Point de contrôle">
                <template #body="{data}"><span style="font-size:.72rem">{{ data.libelle_controle }}</span></template>
              </Column>
              <Column header="Opérations" style="width:230px">
                <template #body="{data}">
                  <div class="d-flex flex-column gap-1 align-items-start">
                    <span v-for="o in getItemOperations(data.id)" :key="o.id"
                          class="badge d-flex align-items-center gap-1 bg-secondary text-wrap text-start"
                          style="font-size:.6rem;cursor:pointer;max-width:100%" :title="o.libelle"
                          @click="removeItemOperation(data.id, o.id)">
                      <b>{{ o.code }}</b> — {{ o.libelle }} <i class="ti ti-x flex-shrink-0" style="font-size:.55rem"></i>
                    </span>
                    <b-dropdown size="sm" variant="outline-secondary" no-caret toggle-class="py-0 px-1" boundary="viewport">
                      <template #button-content><i class="ti ti-plus" style="font-size:.6rem"></i></template>
                      <b-dropdown-header>Rattacher une opération (filtre les délais)</b-dropdown-header>
                      <b-dropdown-item v-for="o in operations" :key="o.id" @click="addItemOperation(data.id, o.id)" style="font-size:.72rem">
                        {{ o.code }} — {{ o.libelle }}
                      </b-dropdown-item>
                    </b-dropdown>
                  </div>
                </template>
              </Column>
              <Column header="Articles" style="width:150px">
                <template #body="{data}">
                  <div class="d-flex flex-wrap gap-1 align-items-center">
                    <span v-for="a in getItemArticles(data.id)" :key="a.id"
                          class="badge d-flex align-items-center gap-1"
                          :class="a.genere_par_ia ? 'bg-info text-dark' : 'bg-dark'"
                          style="font-size:.6rem;cursor:pointer"
                          @click="articleDetail = a">
                      Art. {{ a.numero }}
                      <i class="ti ti-x" style="font-size:.55rem" @click.stop="removeItemArticle(data.id, a.id)"></i>
                    </span>
                    <b-dropdown size="sm" variant="outline-dark" no-caret toggle-class="py-0 px-1" boundary="viewport">
                      <template #button-content><i class="ti ti-plus" style="font-size:.6rem"></i></template>
                      <b-dropdown-header>Rattacher un article</b-dropdown-header>
                      <b-dropdown-item v-for="a in articlesLoi" :key="a.id" @click="addItemArticle(data.id, a.id)" style="font-size:.72rem">
                        Art. {{ a.numero }} — {{ a.titre || a.texte_reference }}
                      </b-dropdown-item>
                    </b-dropdown>
                  </div>
                </template>
              </Column>
              <Column header="Délais" style="width:280px">
                <template #body="{data}">
                  <div v-if="data.depend_delai" class="d-flex flex-column align-items-start gap-1">
                    <span v-for="d in getItemDelaisMulti(data)" :key="d.id"
                          class="badge d-flex align-items-center gap-1 bg-success text-wrap text-start"
                          style="font-size:.6rem;cursor:pointer;max-width:100%"
                          @click="removeItemDelaiMulti(data.id, d.id)">
                      <i class="ti ti-clock flex-shrink-0" style="font-size:.55rem"></i>{{ delaiSummary(d.id) }}
                      <i class="ti ti-x flex-shrink-0" style="font-size:.55rem"></i>
                    </span>
                    <b-dropdown size="sm" variant="outline-success" no-caret toggle-class="py-0 px-1" boundary="viewport">
                      <template #button-content><i class="ti ti-plus" style="font-size:.6rem"></i></template>
                      <b-dropdown-header>Délais compatibles ({{ selectedGrille?.mode_passation_code || 'Tous' }})</b-dropdown-header>
                      <b-dropdown-item v-if="!delaisPourItem(data).length" disabled style="font-size:.7rem">Aucun délai compatible</b-dropdown-item>
                      <b-dropdown-item v-for="d in delaisPourItem(data)" :key="d.id" @click="addItemDelaiMulti(data.id, d.id)" style="font-size:.72rem">
                        <span v-if="d.__suggere" class="badge bg-warning text-dark me-1" style="font-size:.58rem">Suggéré</span>
                        {{ delaiSummary(d.id) }}
                      </b-dropdown-item>
                    </b-dropdown>
                  </div>
                  <span v-else class="text-muted" style="font-size:.65rem">—</span>
                </template>
              </Column>
              <Column header="" style="width:40px" bodyClass="text-center">
                <template #body="{data}">
                  <b-button size="sm" variant="outline-info" class="py-0 px-1" @click="analyserItemIA(data)" :disabled="analysingItem===data.id" title="Analyser avec l'IA">
                    <i class="ti" :class="analysingItem===data.id ? 'ti-loader-2' : 'ti-sparkles'" style="font-size:.7rem"></i>
                  </b-button>
                </template>
              </Column>
              <Column header="" style="width:70px" bodyClass="text-end">
                <template #body="{data}">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="light" @click="openItemForm(data)"><i class="ti ti-pencil"></i></b-button>
                    <b-button size="sm" variant="light" @click="destroyItem(data.id)"><i class="ti ti-trash text-danger"></i></b-button>
                  </div>
                </template>
              </Column>
              <template #empty><div class="text-muted py-3 px-3 small">Aucun point de contrôle — ajoutez-en un</div></template>
            </DataTable>
          </b-card-body>
        </b-card>

        <b-card v-else no-body class="shadow-sm">
          <b-card-body class="text-center text-muted py-5">
            <i class="ti ti-arrow-left fs-3 d-block mb-2"></i>
            Sélectionnez une grille à gauche pour voir/éditer ses points de contrôle
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ══════════════════════════════════════════════════════════════
         MODALE — GRILLE (créer/éditer)
    ══════════════════════════════════════════════════════════════ -->
    <b-modal v-model="modal.grille" :title="grilleForm.id ? 'Modifier la grille' : 'Nouvelle grille'" hide-footer size="lg">
      <b-form @submit.prevent="submitGrille">
        <b-row class="g-2">
          <b-col cols="4">
            <label class="form-label mb-1">Code *</label>
            <b-form-input class="form-control-sm" v-model.trim="grilleForm.code" required
              placeholder="A6-DAO-SANSPQ-FS" :disabled="!!grilleForm.id"/>
          </b-col>
          <b-col cols="4">
            <label class="form-label mb-1">Famille (regroupement)</label>
            <b-form-input class="form-control-sm" v-model.trim="grilleForm.code_parent" placeholder="A6"/>
          </b-col>
          <b-col cols="4">
            <label class="form-label mb-1">Phase</label>
            <b-form-select class="form-select-sm" v-model="grilleForm.phase_marche"
              :options="[{value:'',text:'—'},'PLA','DAO','ROO','EVA','SAN','EXE','REP','CAT']"/>
          </b-col>
          <b-col cols="12">
            <label class="form-label mb-1">Intitulé *</label>
            <b-form-textarea class="form-control-sm" rows="2" v-model.trim="grilleForm.intitule" required/>
          </b-col>
          <b-col cols="4">
            <label class="form-label mb-1">Nature de marché</label>
            <b-form-select class="form-select-sm" v-model="grilleForm.nature_marche_code"
              :options="[{value:'',text:'— Toutes natures —'},...naturesMarche.map(n=>({value:n.code,text:n.code+' — '+n.libelle}))]"/>
          </b-col>
          <b-col cols="4">
            <label class="form-label mb-1">Mode de passation *</label>
            <b-form-select class="form-select-sm" v-model="grilleForm.mode_passation_code" :options="modePassationOptions"/>
          </b-col>
          <b-col cols="4">
            <label class="form-label mb-1">Préqualification</label>
            <b-form-select class="form-select-sm" v-model="grilleForm.avec_prequalification"
              :options="[{value:'',text:'— Indifférent —'},{value:'1',text:'Avec préqualification'},{value:'0',text:'Sans préqualification'}]"/>
          </b-col>
        </b-row>
        <div class="text-end mt-3">
          <b-button variant="light" class="me-2" @click="modal.grille=false">Annuler</b-button>
          <b-button variant="primary" type="submit">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

    <!-- ══════════════════════════════════════════════════════════════
         MODALE — ITEM (point de contrôle)
    ══════════════════════════════════════════════════════════════ -->
    <b-modal v-model="modal.item" :title="itemForm.id ? 'Modifier le point de contrôle' : 'Nouveau point de contrôle'" hide-footer size="lg">
      <b-form @submit.prevent="submitItem">
        <b-row class="g-2">
          <b-col cols="3">
            <label class="form-label mb-1">N° *</label>
            <b-form-input class="form-control-sm" v-model.trim="itemForm.numero" required placeholder="7 bis"/>
          </b-col>
          <b-col cols="9">
            <label class="form-label mb-1">Obligatoire</label>
            <b-form-select class="form-select-sm" v-model="itemForm.obligatoire" :options="[{value:1,text:'Oui'},{value:0,text:'Non'}]"/>
          </b-col>
          <b-col cols="12">
            <label class="form-label mb-1">Libellé du contrôle *</label>
            <b-form-textarea class="form-control-sm" rows="4" v-model.trim="itemForm.libelle_controle" required
              placeholder="Conformité de... aux exigences de l'article ... de la loi n°2020-26..."/>
          </b-col>
        </b-row>
        <b-alert variant="light" class="py-2 px-3 mt-2 mb-0" style="font-size:.72rem;border-left:3px solid #17a2b8">
          <i class="ti ti-info-circle me-1"></i>
          Si le libellé contient le mot "délai", le point sera automatiquement marqué comme lié à un délai —
          rattachez-le ensuite à une règle existante depuis la colonne "Délai" du tableau.
        </b-alert>
        <div class="text-end mt-3">
          <b-button variant="light" class="me-2" @click="modal.item=false">Annuler</b-button>
          <b-button variant="primary" type="submit">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

    <!-- ══════════════════════════════════════════════════════════════
         MODALE — DÉTAIL D'UN ARTICLE (titre + contenu complet)
    ══════════════════════════════════════════════════════════════ -->
    <b-modal :model-value="!!articleDetail" @update:model-value="v => { if (!v) articleDetail = null }"
             :title="articleDetail ? ('Article ' + articleDetail.numero) : ''" hide-footer size="lg">
      <div v-if="articleDetail">
        <div class="mb-2">
          <span class="badge bg-dark me-2">{{ articleDetail.texte_reference }}</span>
          <span v-if="articleDetail.source_loi" class="badge bg-light text-dark border">{{ articleDetail.source_loi }}</span>
          <span v-if="articleDetail.genere_par_ia" class="badge bg-info text-dark ms-1"><i class="ti ti-sparkles me-1"></i>Généré par IA</span>
        </div>
        <h6 v-if="articleDetail.titre">{{ articleDetail.titre }}</h6>
        <p v-if="articleDetail.contenu" style="white-space:pre-line;font-size:.85rem">{{ articleDetail.contenu }}</p>
        <p v-else class="text-muted fst-italic small">
          Contenu non renseigné — relancez l'analyse IA sur ce point de contrôle, ou complétez-le manuellement.
        </p>
      </div>
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
  naturesMarche:  { type: Array, default: () => [] },
  modesPassation: { type: Array, default: () => [] },
  organes:        { type: Array, default: () => [] },
  delais:         { type: Array, default: () => [] },
  delaiModes:     { type: Array, default: () => [] }, // [{delai_id, mode_passation_code}]
  delaiOrganes:   { type: Array, default: () => [] }, // [{delai_id, organe_code}]
  operations:     { type: Array, default: () => [] },
  grilles:        { type: Array, default: () => [] },
  items:          { type: Array, default: () => [] },
  grilleOrganes:  { type: Array, default: () => [] }, // [{grille_id, organe_code}]
  articlesLoi:      { type: Array, default: () => [] },
  itemsArticles:    { type: Array, default: () => [] }, // [{item_id, article_id, genere_par_ia}]
  itemsDelaisMulti: { type: Array, default: () => [] }, // [{item_id, delai_id, genere_par_ia}]
  itemsOperations:  { type: Array, default: () => [] }, // [{item_id, operation_id, genere_par_ia}]
})

const naturesMarche  = ref([...props.naturesMarche])
const modesPassation = ref([...props.modesPassation])
const organes        = ref([...props.organes])
const delais         = ref([...props.delais])
const delaiModes     = ref([...props.delaiModes])
const delaiOrganes   = ref([...props.delaiOrganes])
const operations     = ref([...props.operations])
const grilles        = ref([...props.grilles])
const items          = ref([...props.items])
const grilleOrganes  = ref([...props.grilleOrganes])
const articlesLoi      = ref([...props.articlesLoi])
const itemsArticles    = ref([...props.itemsArticles])
const itemsDelaisMulti = ref([...props.itemsDelaisMulti])
const itemsOperations  = ref([...props.itemsOperations])
const articleDetail = ref(null) // article actuellement affiché dans la modale de détail
const analysingItem = ref(null)
const analysingGrille = ref(false)

const seeding = ref(false)
const seedMsg = reactive({ text: '', variant: 'success' })
const selectedGrille = ref(null)

// ── Modes en dur si pas encore seedés dans pm_modes_passation (AMI/DP) ──
const EXTRA_MODES = [
  { code: 'AMI', libelle: "Avis à manifestation d'intérêt", code_famille: 'PI' },
  { code: 'DP',  libelle: 'Demande de propositions',        code_famille: 'PI' },
]

// ── Onglets de mode (source unique : pm_modes_passation + AMI/DP si absents) ──
const modeTabs = computed(() => {
  const existing = modesPassation.value.map(m => ({ code: m.code, libelle: m.libelle }))
  const codes = new Set(existing.map(m => m.code))
  const extra = EXTRA_MODES.filter(m => !codes.has(m.code))
  return [...existing, ...extra]
})

const activeMode = ref(null)
// Sélectionne le premier mode dès que les données arrivent
if (modeTabs.value.length && !activeMode.value) activeMode.value = modeTabs.value[0].code

const modePassationOptions = computed(() => [
  { value: '', text: '— Tous modes —' },
  ...modesPassation.value.map(m => ({ value: m.code, text: m.code + ' — ' + m.libelle })),
  { value: 'AMI', text: "AMI — Avis à manifestation d'intérêt" },
  { value: 'DP',  text: 'DP — Demande de propositions' },
])

function grillesForMode(modeCode) {
  return grilles.value.filter(g => g.mode_passation_code === modeCode || !g.mode_passation_code)
}

// ── Grilles du mode actif, regroupées par nature de marché ──
const naturesForMode = computed(() => {
  if (!activeMode.value) return []
  const list = grillesForMode(activeMode.value)
  const map = {}
  list.forEach(g => {
    const key = g.nature_marche_code || '__TOUTES__'
    if (!map[key]) map[key] = { key, label: g.nature_marche_code ? natureLibelle(g.nature_marche_code) : 'Toutes natures', grilles: [] }
    map[key].grilles.push(g)
  })
  return Object.values(map).sort((a, b) => a.label.localeCompare(b.label))
})

function selectGrille(g) { selectedGrille.value = g }

const selectedItems = computed(() => {
  if (!selectedGrille.value) return []
  return items.value
    .filter(i => i.grille_id === selectedGrille.value.id)
    .sort((a, b) => a.sort - b.sort)
})

function itemCount(grilleId) {
  return items.value.filter(i => i.grille_id === grilleId).length
}

const statsCards = computed(() => [
  { label: 'Grilles',           count: grilles.value.length,                          color: 'primary',  icon: 'ti ti-folder' },
  { label: 'Modes couverts',    count: modeTabs.value.filter(m=>grillesForMode(m.code).length).length, color: 'info', icon: 'ti ti-arrows-shuffle' },
  { label: 'Points de contrôle',count: items.value.length,                            color: 'success',  icon: 'ti ti-checklist' },
  { label: 'Liés à un délai',   count: items.value.filter(i=>i.depend_delai).length,  color: 'warning',  icon: 'ti ti-clock' },
])

// ── Helpers d'affichage ────────────────────────────────────────────
const natureLibelle = code => naturesMarche.value.find(n=>n.code===code)?.libelle || code
const modeLibelle   = code => modeTabs.value.find(m=>m.code===code)?.libelle || code
const modeBadgeClass = c => ({ SD:'bg-success', DC:'bg-info text-dark', DRP:'bg-warning text-dark', AOO:'bg-danger', AOR:'bg-danger', GAG:'bg-secondary', ACC:'bg-secondary', AMI:'bg-dark', DP:'bg-dark' }[c]||'bg-secondary')

function getGrilleOrganes(grilleId) {
  return grilleOrganes.value.filter(o => o.grille_id === grilleId).map(o => o.organe_code)
}

// ── Articles de loi rattachés à un item (M2M) ─────────────────────────
// ── Opérations rattachées à un item (M2M) — c'est le filtre précis pour les délais ──
function getItemOperations(itemId) {
  const ids = itemsOperations.value.filter(o => o.item_id === itemId).map(o => o.operation_id)
  return operations.value.filter(o => ids.includes(o.id))
}
async function addItemOperation(itemId, operationId) {
  const res = await apiFetch(`/m/audit.core/grilles-verification/items/${itemId}/link-operation`, 'POST', { operation_id: operationId })
  if (!res.success) return
  itemsOperations.value.push({ item_id: itemId, operation_id: operationId, genere_par_ia: 0 })

  // Auto-visualisation : dès qu'une opération est enregistrée, on rattache
  // directement tous les délais compatibles (mode de la grille + organe),
  // sans attendre que l'admin les ajoute un par un depuis le dropdown.
  const modeCode = selectedGrille.value?.mode_passation_code
  const delaisCompatibles = delais.value.filter(d => {
    if (d.operation_id !== operationId) return false
    const modes = getDelaiModes(d.id)
    return !modes.length || !modeCode || modes.includes(modeCode)
  })
  for (const d of delaisCompatibles) {
    const dejaLie = itemsDelaisMulti.value.some(x => x.item_id === itemId && x.delai_id === d.id)
    if (!dejaLie) await addItemDelaiMulti(itemId, d.id)
  }
}
async function removeItemOperation(itemId, operationId) {
  const res = await apiFetch(`/m/audit.core/grilles-verification/items/${itemId}/unlink-operation`, 'DELETE', { operation_id: operationId })
  if (res.success) {
    const idx = itemsOperations.value.findIndex(o => o.item_id === itemId && o.operation_id === operationId)
    if (idx !== -1) itemsOperations.value.splice(idx, 1)
  }
}

function getItemArticles(itemId) {
  const ids = itemsArticles.value.filter(a => a.item_id === itemId).map(a => a.article_id)
  return articlesLoi.value.filter(a => ids.includes(a.id))
}

// ── Délais rattachés à un item : fusion du lien historique 1-1 (delai_id)
//    et des liens multiples M2M (pm_grilles_verification_items_delais) ──
function getItemDelaisMulti(item) {
  const idsM2M = itemsDelaisMulti.value.filter(d => d.item_id === item.id).map(d => d.delai_id)
  const ids = new Set(idsM2M)
  if (item.delai_id) ids.add(item.delai_id)
  return delais.value.filter(d => ids.has(d.id))
}

async function addItemArticle(itemId, articleId) {
  const res = await apiFetch(`/m/audit.core/grilles-verification/items/${itemId}/link-article`, 'POST', { article_id: articleId })
  if (res.success) itemsArticles.value.push({ item_id: itemId, article_id: articleId, genere_par_ia: 0 })
}
async function removeItemArticle(itemId, articleId) {
  const res = await apiFetch(`/m/audit.core/grilles-verification/items/${itemId}/unlink-article`, 'DELETE', { article_id: articleId })
  if (res.success) {
    const idx = itemsArticles.value.findIndex(a => a.item_id === itemId && a.article_id === articleId)
    if (idx !== -1) itemsArticles.value.splice(idx, 1)
  }
}
async function addItemDelaiMulti(itemId, delaiId) {
  const res = await apiFetch(`/m/audit.core/grilles-verification/items/${itemId}/link-delai-multi`, 'POST', { delai_id: delaiId })
  if (res.success) {
    itemsDelaisMulti.value.push({ item_id: itemId, delai_id: delaiId, genere_par_ia: 0 })
    const idx = items.value.findIndex(i => i.id === itemId)
    if (idx !== -1) items.value[idx].depend_delai = 1
  }
}
async function removeItemDelaiMulti(itemId, delaiId) {
  const res = await apiFetch(`/m/audit.core/grilles-verification/items/${itemId}/unlink-delai-multi`, 'DELETE', { delai_id: delaiId })
  if (res.success) {
    const idx = itemsDelaisMulti.value.findIndex(d => d.item_id === itemId && d.delai_id === delaiId)
    if (idx !== -1) itemsDelaisMulti.value.splice(idx, 1)
  }
}

// ── Analyse IA (Mistral) : extrait UNIQUEMENT les articles de loi cités
//    (numéro + intitulé + contenu). L'opération et le délai restent un choix
//    manuel de l'admin dans les dropdowns "Opérations"/"Délais" ci-dessus —
//    l'IA ne les devine plus (trop peu fiable sur ce référentiel).
async function analyserItemIA(item) {
  analysingItem.value = item.id
  try {
    const res = await apiFetch(`/m/audit.core/grilles-verification/items/${item.id}/ia-analyser`, 'POST')
    if (res.success) {
      (res.articles || []).forEach(a => {
        if (!itemsArticles.value.find(x => x.item_id === item.id && x.article_id === a.id)) {
          itemsArticles.value.push({ item_id: item.id, article_id: a.id, genere_par_ia: 1 })
        }
        const existingIdx = articlesLoi.value.findIndex(x => x.id === a.id)
        if (existingIdx === -1) articlesLoi.value.push(a)
        else articlesLoi.value[existingIdx] = a // rafraîchit titre/contenu si complétés
      })
    }
  } finally {
    analysingItem.value = null
  }
}

async function analyserGrilleIA() {
  if (!selectedGrille.value) return
  if (!confirm(`Analyser les ${selectedItems.value.length} points de contrôle de cette grille avec l'IA ? Cela peut prendre un moment.`)) return
  analysingGrille.value = true
  try {
    for (const item of selectedItems.value) {
      await analyserItemIA(item)
    }
  } finally {
    analysingGrille.value = false
  }
}

function delaiSummary(delaiId) {
  const d = delais.value.find(x => x.id === delaiId)
  if (!d) return '—'
  const op = operations.value.find(o => o.id === d.operation_id)
  const opLabel = op ? `${op.code} — ${op.libelle}` : ''
  if (d.delai_type === 'sans-delai') return `Sans délai — ${opLabel}`
  if (d.delai_type === 'non-defini') return `Délai non défini — ${opLabel}`
  return `${d.delai_valeur ?? '?'} ${d.delai_unite ?? ''} — ${opLabel}`
}

// ── Modes rattachés à un délai (pivot pm_delai_modes). Aucune entrée = s'applique à tous les modes.
function getDelaiModes(delaiId) {
  return delaiModes.value.filter(m => m.delai_id === delaiId).map(m => m.mode_passation_code)
}

// ── Filtre : ne garder que les délais compatibles avec le mode de la grille active,
//    puis score par recouvrement de mots-clés avec le libellé de l'opération liée,
//    pour faire remonter la suggestion la plus probable en tête de liste.
const STOPWORDS = new Set(['de','du','des','la','le','les','et','en','au','aux','à','a','d','l','un','une','pour','par','sur','dans','ou','qui','que','ne','se','ce','ces','son','sa','ses'])

function keywords(text) {
  return (text || '')
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // retire les accents
    .replace(/[^a-z0-9\s]/g, ' ')
    .split(/\s+/)
    .filter(w => w.length >= 4 && !STOPWORDS.has(w))
}

function scoreMatch(itemLibelle, delai) {
  const op = operations.value.find(o => o.id === delai.operation_id)
  if (!op) return 0
  const a = new Set(keywords(itemLibelle))
  const b = keywords(op.libelle)
  let score = 0
  b.forEach(w => { if (a.has(w)) score++ })
  return score
}

function delaisPourItem(item) {
  if (!selectedGrille.value) return []
  const modeCode = selectedGrille.value.mode_passation_code
  const operationIds = getItemOperations(item.id).map(o => o.id)

  // 1) Si des opérations sont explicitement rattachées à l'item, c'est LE filtre
  //    précis : on ne montre que les délais de ces opérations (plus de devinette).
  if (operationIds.length) {
    return delais.value
      .filter(d => operationIds.includes(d.operation_id))
      .filter(d => {
        const modes = getDelaiModes(d.id)
        return !modes.length || !modeCode || modes.includes(modeCode)
      })
      .map(d => ({ ...d, __suggere: true }))
  }

  // 2) Sinon, filtre par mode + score de mots-clés (comportement précédent, en repli)
  let candidats = delais.value.filter(d => {
    const modes = getDelaiModes(d.id)
    return !modes.length || !modeCode || modes.includes(modeCode)
  })

  candidats = candidats.map(d => ({ ...d, __score: scoreMatch(item.libelle_controle, d) }))
  candidats.sort((x, y) => y.__score - x.__score)

  if (candidats.length && candidats[0].__score > 0 && (candidats.length === 1 || candidats[0].__score > (candidats[1]?.__score ?? 0))) {
    candidats[0].__suggere = true
  }
  return candidats
}

// ── Auto-rattachement à la création d'un item : si un seul délai correspond
//    sans ambiguïté (mode compatible + score de mots-clés le plus élevé et unique),
//    on le propose déjà rattaché — l'auditeur/admin peut toujours le changer ensuite.
function autoSuggestDelai(libelle, modeCode) {
  let candidats = delais.value.filter(d => {
    const modes = getDelaiModes(d.id)
    return !modes.length || !modeCode || modes.includes(modeCode)
  })
  candidats = candidats.map(d => ({ ...d, __score: scoreMatch(libelle, d) }))
  candidats.sort((x, y) => y.__score - x.__score)
  if (candidats.length && candidats[0].__score >= 2 && candidats[0].__score > (candidats[1]?.__score ?? 0)) {
    return candidats[0].id
  }
  return null
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

// ── Organes <-> Grille ───────────────────────────────────────────────
async function addGrilleOrgane(grilleId, organeCode) {
  const res = await apiFetch('/m/audit.core/grilles-verification/grille-organes', 'POST', { grille_id: grilleId, organe_code: organeCode })
  if (res.success) grilleOrganes.value.push({ grille_id: grilleId, organe_code: organeCode })
}
async function removeGrilleOrgane(grilleId, organeCode) {
  const res = await apiFetch('/m/audit.core/grilles-verification/grille-organes', 'DELETE', { grille_id: grilleId, organe_code: organeCode })
  if (res.success) {
    const idx = grilleOrganes.value.findIndex(o => o.grille_id === grilleId && o.organe_code === organeCode)
    if (idx !== -1) grilleOrganes.value.splice(idx, 1)
  }
}

// ── Formulaire GRILLE ────────────────────────────────────────────────
const modal = reactive({ grille: false, item: false })
const grilleForm = reactive({
  id: null, code: '', code_parent: '', intitule: '',
  nature_marche_code: '', mode_passation_code: '', avec_prequalification: '', phase_marche: '',
})

function openGrilleForm(g = null, presetMode = null) {
  if (g) {
    Object.assign(grilleForm, {
      id: g.id, code: g.code, code_parent: g.code_parent || '', intitule: g.intitule,
      nature_marche_code: g.nature_marche_code || '', mode_passation_code: g.mode_passation_code || '',
      avec_prequalification: g.avec_prequalification === null ? '' : String(g.avec_prequalification),
      phase_marche: g.phase_marche || '',
    })
  } else {
    Object.assign(grilleForm, { id: null, code: '', code_parent: '', intitule: '', nature_marche_code: '', mode_passation_code: presetMode || '', avec_prequalification: '', phase_marche: '' })
  }
  modal.grille = true
}

async function submitGrille() {
  const payload = {
    code_parent: grilleForm.code_parent || null,
    intitule: grilleForm.intitule,
    nature_marche_code: grilleForm.nature_marche_code || null,
    mode_passation_code: grilleForm.mode_passation_code || null,
    avec_prequalification: grilleForm.avec_prequalification === '' ? null : Number(grilleForm.avec_prequalification),
    phase_marche: grilleForm.phase_marche || null,
  }
  if (grilleForm.id) {
    const res = await apiFetch(`/m/audit.core/grilles-verification/grilles/${grilleForm.id}`, 'PUT', payload)
    if (res.success) {
      const idx = grilles.value.findIndex(g => g.id === grilleForm.id)
      if (idx !== -1) Object.assign(grilles.value[idx], payload)
    }
  } else {
    const res = await apiFetch('/m/audit.core/grilles-verification/grilles', 'POST', { ...payload, code: grilleForm.code })
    if (res.success) grilles.value.push({ id: res.id, code: grilleForm.code, ...payload, actif: 1, sort: 999 })
  }
  modal.grille = false
}

async function destroyGrille(id) {
  if (!confirm('Supprimer cette grille et tous ses points de contrôle ?')) return
  const res = await apiFetch(`/m/audit.core/grilles-verification/grilles/${id}`, 'DELETE')
  if (res.success) {
    grilles.value = grilles.value.filter(g => g.id !== id)
    items.value   = items.value.filter(i => i.grille_id !== id)
    grilleOrganes.value = grilleOrganes.value.filter(o => o.grille_id !== id)
    if (selectedGrille.value?.id === id) selectedGrille.value = null
  }
}

// ── Formulaire ITEM ──────────────────────────────────────────────────
const itemForm = reactive({ id: null, numero: '', libelle_controle: '', obligatoire: 1 })

function openItemForm(item = null) {
  if (item) Object.assign(itemForm, { id: item.id, numero: item.numero, libelle_controle: item.libelle_controle, obligatoire: item.obligatoire })
  else Object.assign(itemForm, { id: null, numero: '', libelle_controle: '', obligatoire: 1 })
  modal.item = true
}

async function submitItem() {
  if (itemForm.id) {
    const payload = { numero: itemForm.numero, libelle_controle: itemForm.libelle_controle, obligatoire: itemForm.obligatoire }
    const res = await apiFetch(`/m/audit.core/grilles-verification/items/${itemForm.id}`, 'PUT', payload)
    if (res.success) {
      const idx = items.value.findIndex(i => i.id === itemForm.id)
      if (idx !== -1) Object.assign(items.value[idx], payload)
    }
  } else {
    const payload = { grille_id: selectedGrille.value.id, numero: itemForm.numero, libelle_controle: itemForm.libelle_controle, obligatoire: itemForm.obligatoire }
    const res = await apiFetch('/m/audit.core/grilles-verification/items', 'POST', payload)
    if (res.success) {
      const dependDelai = /d[ée]lai/i.test(itemForm.libelle_controle) ? 1 : 0
      items.value.push({ id: res.id, ...payload, depend_delai: dependDelai, delai_id: null, sort: 999 })

      // Auto-suggestion : si un délai correspond sans ambiguïté (mode + mots-clés),
      // on le rattache tout de suite — modifiable ensuite via le dropdown "Délai".
      if (dependDelai) {
        const suggestedId = autoSuggestDelai(itemForm.libelle_controle, selectedGrille.value.mode_passation_code)
        if (suggestedId) await linkItemDelai(res.id, suggestedId)
      }
    }
  }
  modal.item = false
}

async function destroyItem(id) {
  if (!confirm('Supprimer ce point de contrôle ?')) return
  const res = await apiFetch(`/m/audit.core/grilles-verification/items/${id}`, 'DELETE')
  if (res.success) items.value = items.value.filter(i => i.id !== id)
}

async function linkItemDelai(itemId, delaiId) {
  const res = await apiFetch(`/m/audit.core/grilles-verification/items/${itemId}/link-delai`, 'PUT', { delai_id: delaiId })
  if (res.success) {
    const idx = items.value.findIndex(i => i.id === itemId)
    if (idx !== -1) items.value[idx].delai_id = delaiId
  }
}

// ── Seed / Reset ─────────────────────────────────────────────────────
async function reloadAll() {
  const res = await apiFetch('/m/audit.core/grilles-verification/api/all', 'GET')
  if (!res) return
  naturesMarche.value  = res.naturesMarche  ?? []
  modesPassation.value = res.modesPassation ?? []
  organes.value        = res.organes        ?? []
  delais.value         = res.delais         ?? []
  delaiModes.value     = res.delaiModes     ?? []
  delaiOrganes.value   = res.delaiOrganes   ?? []
  operations.value     = res.operations     ?? []
  grilles.value        = res.grilles        ?? []
  items.value          = res.items          ?? []
  grilleOrganes.value  = res.grilleOrganes  ?? []
  articlesLoi.value      = res.articlesLoi      ?? []
  itemsArticles.value    = res.itemsArticles    ?? []
  itemsDelaisMulti.value = res.itemsDelaisMulti ?? []
  itemsOperations.value  = res.itemsOperations  ?? []
  if (modeTabs.value.length && !activeMode.value) activeMode.value = modeTabs.value[0].code
}

async function seedData() {
  if (!confirm('Importer les grilles ARMP (grilles_armp_complet.sql) ?')) return
  seeding.value = true
  try {
    const res = await apiFetch('/m/audit.core/grilles-verification/seed', 'POST')
    if (res.success) { await reloadAll(); seedMsg.variant='success'; seedMsg.text=res.message }
    else             { seedMsg.variant='warning'; seedMsg.text=res.message }
  } catch(e) { seedMsg.variant='danger'; seedMsg.text='Erreur seed.' }
  finally { seeding.value=false }
}

async function resetData() {
  if (!confirm('⚠️ Vider TOUTES les grilles de vérification ?')) return
  seeding.value = true
  try {
    const res = await apiFetch('/m/audit.core/grilles-verification/reset', 'POST')
    if (res.success) { await reloadAll(); selectedGrille.value = null; seedMsg.variant='info'; seedMsg.text=res.message }
  } catch(e) { seedMsg.variant='danger'; seedMsg.text='Erreur reset.' }
  finally { seeding.value=false }
}
</script>

<style scoped>
.form-control-sm,.form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.stat-card { border-left:3px solid transparent }
.stat-icon { width:32px; height:32px; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:15px }
.pv-table :deep(.p-datatable-thead>tr>th) { background:#f8fafc; border:1px solid #e5e7eb; padding:.25rem .35rem; font-size:.74rem }
.pv-table :deep(.p-datatable-tbody>tr>td) { border:1px solid #eef2f7; padding:.4rem .35rem; font-size:.72rem; vertical-align:top }
.nature-header { background:#fff8ec; border-bottom:1px solid #f5e9d0; border-top:1px solid #f5e9d0 }
.grille-row { border-bottom:1px solid #f5f5f5; cursor:pointer; transition:background .15s }
.grille-row:hover { background:#f8fbff }
.grille-active { background:#e8f4fd; border-left:3px solid #1a56db }
</style>