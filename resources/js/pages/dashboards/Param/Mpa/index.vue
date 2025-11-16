<template>
  <VerticalLayout>
    <Head title="DIADDEM — MPA (Global)" />

    <!-- HEADER -->
    <b-row class="mb-0">
      <b-col>
        <div class="d-flex align-items-center gap-2">
          <i class="ti ti-topology-star-3 text-primary fs-5"></i>
          <h4 class="m-0 fw-semibold">Macro-Processus-Activités</h4>
          <small class="text-muted ms-2">Mode global (aucun projet requis)</small>
        </div>
      </b-col>
    </b-row>

    <!-- TABS -->
    <b-card no-body class="mb-2 shadow-none border-0">
      <b-card-body class="p-1">
        <b-button-group size="sm">
          <b-button @click="activeTab='macro'"    :variant="tabVariant('macro')"   ><i class="ti ti-cube me-1"></i> Macro</b-button>
          <b-button @click="activeTab='process'"  :variant="tabVariant('process')" ><i class="ti ti-settings me-1"></i> Processus</b-button>
          <b-button @click="activeTab='activity'" :variant="tabVariant('activity')"><i class="ti ti-list-details me-1"></i> Activités</b-button>
        </b-button-group>
      </b-card-body>
    </b-card>

    <b-row class="g-1">

      <!-- GAUCHE -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm">

          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <span v-if="activeTab==='macro'">Macros</span>
              <span v-else-if="activeTab==='process'">Création Processus</span>
              <span v-else>Création Activité</span>
            </h6>
            <small class="text-muted">Global</small>
          </b-card-header>

          <b-card-body class="p-2">

            <!-- ===================== MACRO ===================== -->
            <div v-if="activeTab==='macro'">
              <b-alert :variant="macrosLocked?'success':'info'" show class="py-2 px-3 mb-2">
                <i class="ti" :class="macrosLocked?'ti-lock':'ti-info-circle'"></i>
                <span v-if="macrosLocked"> Ensemble complet (3/3). Cliquez pour renommer.</span>
                <span v-else> Cliquez « Valider les 3 par défaut » pour créer D/R/S.</span>
              </b-alert>

              <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                <span>
                  <template v-if="macrosLocked"><i class="ti ti-checks"></i> Verrouillés</template>
                  <template v-else><i class="ti ti-alert-triangle"></i> Manquants : {{ missingKinds.join(" • ") }}</template>
                </span>

                <b-button size="sm" variant="outline-primary" @click="validateDefaults" :disabled="macrosLocked">
                  <i class="ti ti-checkup-list me-1"></i> Valider par défaut
                </b-button>
              </div>

              <DataTable :value="macrosFilteredSorted" size="small" class="pv-table flat">
                <Column header="Code" style="width:100px">
                  <template #body="{ data }"><Tag :value="data.code" :severity="kindSeverity(data.kind)" /></template>
                </Column>

                <Column header="Type" style="width:120px">
                  <template #body="{ data }"><Tag :value="data.kind" :severity="kindSeverity(data.kind)" /></template>
                </Column>

                <Column header="Nom">
                  <template #body="{ data }"><span class="fw-semibold">{{ data.name }}</span></template>
                </Column>

                <Column header="" style="width:40px" bodyClass="text-end">
                  <template #body="{ data }">
                    <b-button size="sm" variant="light" @click="openEditModal(data)"><i class="ti ti-pencil"></i></b-button>
                  </template>
                </Column>

                <template #empty><div class="text-muted py-1">Aucun macro</div></template>
              </DataTable>
            </div>

            <!-- ===================== PROCESSUS ===================== -->
            <div v-else-if="activeTab==='process'">

              <b-form @submit.prevent="submitProcess" class="mb-2">
                <b-row class="g-2">

                  <!-- Macro -->
                  <b-col cols="12">
                    <label class="form-label mb-1">Macro *</label>
                    <b-form-select class="form-select-sm" v-model="processForm.macro_process_id" :options="macroOptions" required/>
                  </b-col>

                  <!-- Code auto -->
                  <b-col cols="12">
                    <label class="form-label mb-1">Code (auto)</label>
                    <b-form-input class="form-control-sm font-monospace" :value="nextProcessCode" disabled/>
                  </b-col>

                  <!-- Nom -->
                  <b-col cols="12">
                    <label class="form-label mb-1">Nom Processus *</label>
                    <b-form-input class="form-control-sm" v-model.trim="processForm.name" required/>
                  </b-col>

                  <!-- Entrées -->
                  <b-col cols="12" class="mt-2">
                    <label class="form-label mb-1 fw-semibold">Données d'entrée</label>
                    <div v-for="(inp, i) in processForm.inputs" :key="'inp'+i" class="d-flex gap-1 mb-1">
                      <b-form-input class="form-control-sm" v-model="processForm.inputs[i]" placeholder="Ex : Demande initiale"/>
                      <b-button size="sm" variant="danger" @click="removeList(processForm.inputs,i)">✕</b-button>
                    </div>
                    <b-button size="sm" variant="outline-primary" @click="processForm.inputs.push('')">+ Ajouter</b-button>
                  </b-col>

                  <!-- Sorties -->
                  <b-col cols="12" class="mt-2">
                    <label class="form-label mb-1 fw-semibold">Données de sortie</label>
                    <div v-for="(out, i) in processForm.outputs" :key="'out'+i" class="d-flex gap-1 mb-1">
                      <b-form-input class="form-control-sm" v-model="processForm.outputs[i]" placeholder="Ex : Rapport final"/>
                      <b-button size="sm" variant="danger" @click="removeList(processForm.outputs,i)">✕</b-button>
                    </div>
                    <b-button size="sm" variant="outline-primary" @click="processForm.outputs.push('')">+ Ajouter</b-button>
                  </b-col>

                  <!-- Ressources -->
                  <b-col cols="12" class="mt-2">
                    <label class="form-label mb-1 fw-semibold">Ressources</label>
                    <div v-for="(res, i) in processForm.resources" :key="'res'+i" class="d-flex gap-1 mb-1">
                      <b-form-input class="form-control-sm" v-model="processForm.resources[i]" placeholder="Ex : Logiciel ERP"/>
                      <b-button size="sm" variant="danger" @click="removeList(processForm.resources,i)">✕</b-button>
                    </div>
                    <b-button size="sm" variant="outline-primary" @click="processForm.resources.push('')">+ Ajouter</b-button>
                  </b-col>

                  <!-- Boutons -->
                  <b-col cols="12" class="text-end pt-1">
                    <b-button size="sm" variant="light" class="me-1" @click="resetProcess">Annuler</b-button>
                    <b-button size="sm" variant="primary" type="submit" :disabled="!processForm.macro_process_id">Valider</b-button>
                  </b-col>

                </b-row>
              </b-form>

              <!-- TABLE PROCESSES -->
              <DataTable :value="processesFiltered" size="small" class="pv-table flat">
                <Column header="Code" style="width:120px">
                  <template #body="{data}"><span class="font-monospace text-primary">{{ data.code }}</span></template>
                </Column>

                <Column header="Nom">
                  <template #body="{data}"><span class="fw-semibold">{{ data.name }}</span></template>
                </Column>

                <Column header="Macro" style="width:180px">
                  <template #body="{data}">
                    <Tag :value="macroNameById(data.macro_process_id)" :severity="getMacroKindBySeverity(data.macro_process_id)"/>
                  </template>
                </Column>

                <template #empty><div class="text-muted py-1">Aucun processus</div></template>
              </DataTable>
            </div>

            <!-- ===================== ACTIVITÉ ===================== -->
            <div v-else-if="activeTab==='activity'">
              <b-form @submit.prevent="submitActivity" class="mb-2">
                <b-row class="g-2">

                  <b-col cols="12">
                    <label class="form-label mb-1">Macro *</label>
                    <b-form-select class="form-select-sm" v-model="activityForm.macro_process_id" :options="macroOptionsActivity" required/>
                  </b-col>

                  <b-col cols="12">
                    <label class="form-label mb-1">Processus *</label>
                    <b-form-select class="form-select-sm" v-model="activityForm.process_id" :options="processOptionsActivity" :disabled="!activityForm.macro_process_id" required/>
                  </b-col>

                  <b-col cols="12">
                    <label class="form-label mb-1">Code (auto)</label>
                    <b-form-input class="form-control-sm font-monospace" :value="nextActivityCode" disabled/>
                  </b-col>

                  <b-col cols="12">
                    <label class="form-label mb-1">Nom *</label>
                    <b-form-input class="form-control-sm" v-model.trim="activityForm.name" required/>
                  </b-col>

                  <b-col cols="12">
                    <label class="form-label mb-1">Description</label>
                    <b-form-textarea rows="2" class="form-control-sm" v-model.trim="activityForm.description"/>
                  </b-col>

                  <b-col cols="12" class="text-end pt-1">
                    <b-button size="sm" variant="light" class="me-1" @click="resetActivity">Annuler</b-button>
                    <b-button size="sm" variant="primary" type="submit">Valider</b-button>
                  </b-col>

                </b-row>
              </b-form>

              <DataTable :value="activitiesFiltered" size="small" class="pv-table flat">
                <Column header="Code" style="width:140px">
                  <template #body="{data}"><span class="font-monospace text-primary">{{ data.code }}</span></template>
                </Column>

                <Column header="Activité">
                  <template #body="{data}"><span class="fw-semibold">{{ data.name }}</span></template>
                </Column>

                <Column header="Processus" style="width:180px">
                  <template #body="{data}">
                    <Tag :value="processNameById(data.process_id)" :severity="getProcessKindBySeverity(data.process_id)"/>
                  </template>
                </Column>

                <template #empty><div class="text-muted py-1">Aucune activité</div></template>
              </DataTable>
            </div>

          </b-card-body>

        </b-card>
      </b-col>

      <!-- DROITE : ARBORESCENCE -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-folders me-1"></i> Arborescence</h6>
            <small class="text-muted">Global</small>
          </b-card-header>

          <b-card-body class="p-2">

            <Tree :value="treeNodes"
                  v-model:expandedKeys="expandedKeys"
                  selectionMode="single"
                  :filter="true"
                  filterMode="lenient"
                  class="w-100 pv-tree rounded">

              <template #default="{ node }">
                <div class="d-flex align-items-center gap-2">
                  <span class="icon-badge" :class="typeColorClass(node)">
                    <i :class="nodeIcon(node)" class="node-icon"></i>
                    <span class="badge-letter">{{ badgeLetter(node) }}</span>
                  </span>
                  <span class="code-chip font-monospace">{{ node.data.code }}</span>
                  <span class="fw-semibold">{{ node.label }}</span>
                </div>
              </template>

            </Tree>

          </b-card-body>
        </b-card>
      </b-col>

    </b-row>

    <!-- MODAL RENAME MACRO -->
    <b-modal v-model="edit.show" title="Renommer le macro" hide-footer>
      <b-form @submit.prevent="submitEdit">
        <b-form-group label="Nom du macro">
          <b-form-input v-model.trim="edit.name" required />
        </b-form-group>

        <div class="text-end mt-2">
          <b-button variant="light" class="me-2" @click="edit.show=false">Annuler</b-button>
          <b-button variant="primary" type="submit" :disabled="savingEdit">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

  </VerticalLayout>
</template>

<script setup>
import { Head, useForm, router } from "@inertiajs/vue3"
import { ref, computed } from "vue"
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import Tree from "primevue/tree"
import Tag from "primevue/tag"

const props = defineProps({
  macros: Array,
  processes: Array,
  activities: Array
})

/* ================== TABS ================== */
const activeTab = ref('macro')
const tabVariant = t => activeTab.value===t?'primary':'outline-primary'

/* ================== FORMS ================== */
const processForm = useForm({
  macro_process_id: null,
  name: '',
  inputs: [''],
  outputs: [''],
  resources: ['']
})

const activityForm = useForm({
  macro_process_id: null,
  process_id: null,
  name: '',
  description: ''
})

/* ================== MACROS VALIDATION ================== */
const requiredKinds = ['Direction','Réalisation','Support']

const macrosFilteredSorted = computed(() => {
  const order = {Direction:1, Réalisation:2, Support:3}
  return [...props.macros].sort((a,b)=> order[a.kind]-order[b.kind])
})

const kindsPresent = computed(() => props.macros.map(m=>m.kind))
const missingKinds = computed(() => requiredKinds.filter(k=>!kindsPresent.value.includes(k)))
const macrosLocked = computed(() => missingKinds.value.length===0)

/* ================== COMPUTED INDEXES ================== */
const macrosById = computed(() => {
  const map = {}
  props.macros.forEach(m => { map[String(m.id)] = m })
  return map
})

const processesById = computed(() => {
  const map = {}
  props.processes.forEach(p => { map[String(p.id)] = p })
  return map
})

/* ================== SELECT OPTIONS ================== */
const macroOptions = computed(() =>
  [{value:null, text:"— Sélectionner —", disabled:true},
   ...props.macros.map(m=>({value:String(m.id), text:`${m.code} — ${m.name}`}))]
)

const macroOptionsActivity = macroOptions

const processOptionsActivity = computed(() => {
  const mID = activityForm.macro_process_id
  if (!mID)
    return [{value:null, text:"— Choisir un macro —", disabled:true}]

  const list = props.processes.filter(p => String(p.macro_process_id) === String(mID))
  return [
    {value:null, text:list.length?"— Sélectionner —":"— Aucun processus —", disabled:true},
    ...list.map(p=>({value:String(p.id), text:`${p.code} — ${p.name}`}))
  ]
})

/* ================== FILTERED LISTS ================== */
const processesFiltered = computed(() => {
  const mID = processForm.macro_process_id
  if (!mID) return []
  return props.processes.filter(p => String(p.macro_process_id) === String(mID))
})

const activitiesFiltered = computed(() => {
  const pID = activityForm.process_id
  if (!pID) return []
  return props.activities.filter(a => String(a.process_id) === String(pID))
})

/* ================== AUTO CODES ================== */
const nextProcessCode = computed(() => {
  const mID = processForm.macro_process_id
  const macro = props.macros.find(m=>String(m.id)===String(mID))
  if (!macro) return ''
  
  const letter = (macro.code || macro.kind[0] || 'X').slice(-1).toUpperCase()
  const count = props.processes.filter(p=>String(p.macro_process_id)===String(mID)).length + 1
  return 'P' + String(count).padStart(2,'0') + letter
})

const nextActivityCode = computed(() => {
  const pid = activityForm.process_id
  const proc = props.processes.find(p=>String(p.id)===String(pid))
  if (!proc) return ''
  
  const m = proc.code.match(/^P(\d{2})([A-Z])$/)
  const seq = m ? m[1] : '01'
  const letter = m ? m[2] : 'X'
  const count = props.activities.filter(a=>String(a.process_id)===String(pid)).length + 1
  
  return `A${String(count).padStart(2,'0')}P${seq}${letter}`
})

/* ================== HELPERS ================== */
const macroNameById = id => macrosById.value[String(id)]?.name || "—"
const processNameById = id => processesById.value[String(id)]?.name || "—"

const getMacroKindBySeverity = mID => {
  const macro = macrosById.value[String(mID)]
  return kindSeverity(macro?.kind || '')
}

const getProcessKindBySeverity = pID => {
  const proc = processesById.value[String(pID)]
  const macro = macrosById.value[String(proc?.macro_process_id)]
  return kindSeverity(macro?.kind || '')
}

const kindSeverity = k => ({
  Direction: 'info',
  Réalisation: 'success',
  Support: 'warning'
}[k] || 'secondary')

/* ================== TREE NODES ================== */
const expandedKeys = ref({})

const makeMacro = m => ({
  key: `M-${m.id}`,
  label: m.name,
  data: {type:'macro', code:m.code},
  children: []
})

const makeProc = p => ({
  key: `P-${p.id}`,
  label: p.name,
  data: {type:'process', code:p.code},
  children: []
})

const makeAct = a => ({
  key: `A-${a.id}`,
  label: a.name,
  data: {type:'activity', code:a.code}
})

const treeNodes = computed(() =>
  props.macros.map(m => {
    const root = makeMacro(m)
    const procs = props.processes.filter(p => p.macro_process_id === m.id)
    
    root.children = procs.map(p => {
      const node = makeProc(p)
      const acts = props.activities.filter(a => a.process_id === p.id)
      node.children = acts.map(a => makeAct(a))
      return node
    })
    
    return root
  })
)

const badgeLetter = n =>
  n.data.type==='macro'?'M':n.data.type==='process'?'P':'A'

const nodeIcon = n =>
  n.data.type==='activity'?'pi pi-file':'pi pi-folder'

const typeColorClass = n =>
  n.data.type==='macro'?'color-type-macro':
  n.data.type==='process'?'color-type-process':'color-type-activity'

/* ================== ACTIONS ================== */
const removeList = (list, i) => list.splice(i, 1)

const resetProcess = () => {
  processForm.name = ''
  processForm.inputs = ['']
  processForm.outputs = ['']
  processForm.resources = ['']
}

const resetActivity = () => {
  activityForm.name = ''
  activityForm.description = ''
}

const validateDefaults = () => {
  router.post(route('param.projects.macro.validate'), {}, {preserveScroll:true})
}

const submitProcess = () => {
  const cleanInputs = processForm.inputs.filter(i => i.trim() !== '')
  const cleanOutputs = processForm.outputs.filter(o => o.trim() !== '')
  const cleanResources = processForm.resources.filter(r => r.trim() !== '')

  router.post(
    route('param.projects.processus.store'),
    {
      macro_process_id: Number(processForm.macro_process_id),
      name: processForm.name,
      inputs: cleanInputs,
      outputs: cleanOutputs,
      resources: cleanResources
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        resetProcess()
        processForm.macro_process_id = null
        router.reload({only:['processes']})
      }
    }
  )
}

const submitActivity = () => {
  router.post(
    route('param.projects.activites.store'),
    {
      macro_process_id: activityForm.macro_process_id,
      process_id: activityForm.process_id,
      name: activityForm.name,
      description: activityForm.description
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        resetActivity()
        activityForm.macro_process_id = null
        activityForm.process_id = null
        router.reload({only:['activities']})
      }
    }
  )
}

/* ================== EDIT MACRO ================== */
const edit = ref({show:false, id:null, name:''})
const savingEdit = ref(false)

const openEditModal = m => {
  edit.value = {show:true, id:m.id, name:m.name}
}

const submitEdit = () => {
  savingEdit.value = true
  router.put(
    route('param.projects.mpa.update', edit.value.id),
    {name: edit.value.name},
    {
      preserveScroll: true,
      onFinish: () => {
        savingEdit.value = false
        edit.value.show = false
        router.reload({only:['macros']})
      }
    }
  )
}
</script>

<style scoped>
/* Compact DIADDEM layout */
.form-control-sm, .form-select-sm {
  font-size: .75rem;
  height: 26px;
  padding: .15rem .45rem
}

.btn-sm {
  padding: .15rem .45rem;
  font-size: .72rem
}

.pv-table :deep(.p-datatable-thead>tr>th) {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  padding: .25rem .35rem;
  font-size: .74rem
}

.pv-table :deep(.p-datatable-tbody>tr>td) {
  border: 1px solid #eef2f7;
  padding: .25rem .35rem;
  font-size: .72rem
}

/* Tree */
.icon-badge {
  position: relative;
  display: flex;
  width: 22px;
  height: 18px;
  border-radius: .4rem;
  align-items: center;
  justify-content: center
}

.badge-letter {
  position: absolute;
  font-size: .65rem;
  font-weight: 800;
  color: #000
}

.color-type-macro { --clr-type: #0f766e }
.color-type-process { --clr-type: #7c3aed }
.color-type-activity { --clr-type: #ef4444 }

.node-icon { color: var(--clr-type) }

/* Code chip */
.code-chip {
  background: #eef2f7;
  border: 1px solid #e2e8f0;
  padding: .05rem .35rem;
  border-radius: .35rem;
  font-size: .72rem
}
</style>