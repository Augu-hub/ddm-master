<template>
  <VerticalLayout>
    <Head title="DIADDEM — MPA (Projet-centric)" />

    <!-- Header + Projet -->
    <b-row class="mb-0">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-topology-star-3 text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Macro-Processus-Activités</h4>
            <small class="text-muted ms-2">Sélection obligatoire d’un projet</small>
          </div>
          <b-input-group size="sm" class="w-auto">
            <b-input-group-text><i class="ti ti-briefcase"></i></b-input-group-text>
            <b-form-select v-model="selectedProjectId" :options="projectOptions" />
          </b-input-group>
        </div>
      </b-col>
    </b-row>

    <!-- Tabs -->
    <b-card no-body class="mb-2 shadow-none border-0">
      <b-card-body class="p-1">
        <b-button-group size="sm">
          <b-button @click="activeTab='macro'"    :variant="activeTab==='macro'    ? 'primary' : 'outline-primary'"><i class="ti ti-cube me-1"></i> Macro</b-button>
          <b-button @click="activeTab='process'"  :variant="activeTab==='process'  ? 'primary' : 'outline-primary'"><i class="ti ti-settings me-1"></i> Processus</b-button>
          <b-button @click="activeTab='activity'" :variant="activeTab==='activity' ? 'primary' : 'outline-primary'"><i class="ti ti-list-details me-1"></i> Activités</b-button>
        </b-button-group>
      </b-card-body>
    </b-card>

    <b-row class="g-1">
      <!-- Colonne gauche -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <span v-if="activeTab==='macro'">Macros du projet</span>
              <span v-else-if="activeTab==='process'">Création Processus</span>
              <span v-else>Création Activité</span>
            </h6>
            <span class="text-muted small"><strong>{{ selectedProjectName || '—' }}</strong></span>
          </b-card-header>

          <b-card-body class="p-2">
            <b-alert v-if="!selectedProjectId" show variant="secondary" class="py-2 px-3 mb-2">
              Sélectionnez d’abord un projet pour activer les formulaires et les listes.
            </b-alert>

            <!-- ===== MACRO ===== -->
            <div v-if="activeTab==='macro' && selectedProjectId">
              <b-alert v-if="macrosLocked" show variant="success" class="py-2 px-3 mb-2">
                <i class="ti ti-lock me-1"></i> Ensemble complet présent (3/3). Cliquez une ligne pour <strong>renommer</strong>.
              </b-alert>
              <b-alert v-else show variant="info" class="py-2 px-3 mb-2">
                <i class="ti ti-info-circle me-1"></i> Cliquez “Valider les 3 par défaut” pour créer Direction, Réalisation, Support.
              </b-alert>

              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small text-muted">
                  <template v-if="macrosLocked">
                    <i class="ti ti-checks"></i> Macros verrouillés (exactement D/R/S)
                  </template>
                  <template v-else>
                    <i class="ti ti-alert-triangle"></i> Manquants : {{ missingKinds.join(' • ') || '—' }}
                  </template>
                </div>
                <b-button size="sm" variant="outline-primary" @click="validateDefaults" :disabled="macrosLocked">
                  <i class="ti ti-checkup-list me-1"></i> Valider les 3 par défaut
                </b-button>
              </div>

              <DataTable :value="macrosFilteredSorted" size="small" class="pv-table flat">
                <Column header="Code" style="width:120px">
                  <template #body="{ data }">
                    <Tag :value="data.code" :severity="kindSeverity(data.kind)" class="me-1" />
                  </template>
                </Column>
                <Column header="Type" style="width:140px">
                  <template #body="{ data }">
                    <Tag :value="data.kind" :severity="kindSeverity(data.kind)" />
                  </template>
                </Column>
                <Column header="Nom">
                  <template #body="{ data }">
                    <span class="fw-semibold">{{ data.name }}</span>
                  </template>
                </Column>
                <Column header=" " style="width:56px" bodyClass="text-end">
                  <template #body="{ data }">
                    <b-button size="sm" variant="light" @click="openEditModal(data)" title="Renommer"><i class="ti ti-pencil"></i></b-button>
                  </template>
                </Column>
                <template #empty><div class="text-muted py-1">Aucun macro</div></template>
              </DataTable>
            </div>

            <!-- ===== PROCESS ===== -->
            <div v-else-if="activeTab==='process' && selectedProjectId">
              <b-form @submit.prevent="submitProcess" class="mb-2">
                <b-row class="g-2">
                  <b-col cols="12">
                    <label class="form-label mb-1">Macro Processus <span class="text-danger">*</span></label>
                    <b-form-select class="form-select-sm" v-model="processForm.macro_process_id" :options="macroOptions" required />
                  </b-col>
                  <b-col cols="12">
                    <label class="form-label mb-1">Code (auto)</label>
                    <b-form-input class="form-control-sm font-monospace" :value="nextProcessCode" disabled />
                  </b-col>
                  <b-col cols="12">
                    <label class="form-label mb-1">Nom Processus <span class="text-danger">*</span></label>
                    <b-form-input class="form-control-sm" v-model.trim="processForm.name" required />
                  </b-col>
                  <b-col cols="12" class="text-end pt-1">
                    <b-button variant="light" size="sm" class="me-1" @click="clearProcessName">Annuler</b-button>
                    <b-button variant="primary" size="sm" type="submit" :disabled="processForm.processing || !processForm.macro_process_id">Valider</b-button>
                  </b-col>
                </b-row>
              </b-form>

              <DataTable :value="processesFiltered" size="small" class="pv-table flat">
                <Column header="Code" style="width:120px">
                  <template #body="{ data }">
                    <span class="font-monospace text-primary">{{ data.code }}</span>
                  </template>
                </Column>
                <Column header="Libellé">
                  <template #body="{ data }"><span class="fw-semibold">{{ data.name }}</span></template>
                </Column>
                <Column header="Macro" style="width:200px">
                  <template #body="{ data }">
                    <Tag
                      :value="macroNameById(data.macro_process_id)"
                      :severity="kindSeverity(kindByProcess(data.macro_process_id))"
                    />
                  </template>
                </Column>
                <template #empty><div class="text-muted py-1">Aucun processus</div></template>
              </DataTable>
            </div>

            <!-- ===== ACTIVITÉ ===== -->
            <div v-else-if="activeTab==='activity' && selectedProjectId">
              <b-form @submit.prevent="submitActivity" class="mb-2">
                <b-row class="g-2">
                  <b-col cols="12">
                    <label class="form-label mb-1">Macro Processus <span class="text-danger">*</span></label>
                    <b-form-select class="form-select-sm" v-model="activityForm.macro_process_id" :options="macroOptionsActivity" required />
                  </b-col>
                  <b-col cols="12">
                    <label class="form-label mb-1">Processus <span class="text-danger">*</span></label>
                    <b-form-select class="form-select-sm" v-model="activityForm.process_id" :options="processOptionsActivity" :disabled="!activityForm.macro_process_id" required />
                  </b-col>
                  <b-col cols="12">
                    <label class="form-label mb-1">Code (auto)</label>
                    <b-form-input class="form-control-sm font-monospace" :value="nextActivityCode" disabled />
                  </b-col>
                  <b-col cols="12">
                    <label class="form-label mb-1">Libellé Activité <span class="text-danger">*</span></label>
                    <b-form-input class="form-control-sm" v-model.trim="activityForm.name" required />
                  </b-col>
                  <b-col cols="12">
                    <label class="form-label mb-1">Description</label>
                    <b-form-textarea class="form-control-sm compact-textarea" rows="2" v-model.trim="activityForm.description" />
                  </b-col>
                  <b-col cols="12" class="text-end pt-1">
                    <b-button variant="light" size="sm" class="me-1" @click="clearActivityFields">Annuler</b-button>
                    <b-button variant="primary" size="sm" type="submit" :disabled="activityForm.processing || !activityForm.process_id || !activityForm.macro_process_id">Valider</b-button>
                  </b-col>
                </b-row>
              </b-form>

              <DataTable :value="activitiesFiltered" size="small" class="pv-table flat">
                <Column header="Code" style="width:140px">
                  <template #body="{ data }">
                    <span class="font-monospace text-primary">{{ data.code }}</span>
                  </template>
                </Column>
                <Column header="Activité">
                  <template #body="{ data }"><span class="fw-semibold">{{ data.name }}</span></template>
                </Column>
                <Column header="Processus" style="width:220px">
                  <template #body="{ data }">
                    <Tag
                      :value="processNameById(data.process_id)"
                      :severity="kindSeverity(kindByActivity(data.process_id))"
                    />
                  </template>
                </Column>
                <template #empty><div class="text-muted py-1">Aucune activité</div></template>
              </DataTable>
            </div>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Colonne droite : Arborescence -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-folders me-1"></i> Arborescence</h6>
            <small class="text-muted">
              <template v-if="selectedProjectId">Projet : <strong>{{ selectedProjectName }}</strong></template>
              <template v-else>—</template>
            </small>
          </b-card-header>

          <b-card-body class="p-2">
            <b-alert v-if="!selectedProjectId" show variant="secondary" class="m-2">Sélectionnez un projet pour afficher l’arborescence.</b-alert>
            <div v-else>
              <Tree
                :value="treeNodes"
                v-model:expandedKeys="expandedKeys"
                selectionMode="single"
                :filter="true"
                filterMode="lenient"
                class="w-100 pv-tree rounded"
              >
                <!-- Un SEUL pictogramme (couleur par TYPE) + code avant nom -->
                <template #default="{ node }">
                  <div class="d-flex align-items-center gap-2">
                    <span class="icon-badge" :class="typeColorClass(node)">
                      <i :class="nodeIcon(node)" class="node-icon"></i>
                      <span class="badge-letter">{{ badgeLetter(node) }}</span>
                    </span>

                    <span class="code-chip font-monospace">{{ node.data?.code }}</span>
                    <span class="fw-semibold">{{ nodeLabel(node) }}</span>

                    <small
                      v-if="node.data?.type==='macro' && activeTab==='activity'"
                      class="text-muted ms-1"
                    >
                      ({{ countProc(node) }} proc. • {{ countAct(node) }} act.)
                    </small>
                  </div>
                </template>
              </Tree>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- MODAL : Renommer un macro -->
    <b-modal v-model="edit.show" title="Renommer le macro" hide-footer>
      <b-form @submit.prevent="submitEdit">
        <div class="mb-2 small text-muted">
          <span class="me-2"><strong>Code :</strong> <span class="font-monospace">{{ edit.code }}</span></span>
          <span class="me-2"><strong>Type :</strong> {{ edit.kind }}</span>
          <span><strong>Car. :</strong> {{ edit.character || '—' }}</span>
        </div>
        <b-form-group label="Nom du macro" label-for="macroName">
          <b-form-input id="macroName" v-model.trim="edit.name" required />
        </b-form-group>
        <div class="text-end mt-2">
          <b-button variant="light" class="me-2" @click="edit.show=false">Annuler</b-button>
          <b-button variant="primary" type="submit" :disabled="savingEdit || !edit.name">
            <i class="ti ti-device-floppy me-1"></i> Enregistrer
          </b-button>
        </div>
      </b-form>
    </b-modal>
  </VerticalLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tree from 'primevue/tree'
import Tag from 'primevue/tag'

const props = defineProps({
  projects:   { type: Array, default: () => [] },
  macros:     { type: Array, default: () => [] },
  processes:  { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
  filters:    { type: Object, default: () => ({ project_id: null }) }
})

const activeTab = ref('macro')
const selectedProjectId = ref(props.filters?.project_id ?? null)
if (!selectedProjectId.value && (props.projects?.length === 1)) selectedProjectId.value = props.projects[0].id

const projectOptions = computed(() => [
  { value: null, text: '— Sélectionner un projet —', disabled: true },
  ...(props.projects||[]).map(p => ({ value: p.id, text: p.name }))
])
const selectedProjectName = computed(
  () => (props.projects||[]).find(p => String(p.id) === String(selectedProjectId.value))?.name ?? ''
)

const processForm  = useForm({ macro_process_id: null, name:'' })
const activityForm = useForm({ macro_process_id: null, process_id: null, name:'', description:'' })

const storageKey = computed(() => selectedProjectId.value ? `mpa:lastMacro:${selectedProjectId.value}` : null)
const loadLastMacro = () => { try { return storageKey.value && localStorage.getItem(storageKey.value) } catch { return null } }
const saveLastMacro = (macroId) => { try { if (storageKey.value && macroId) localStorage.setItem(storageKey.value, String(macroId)) } catch {} }

watch(selectedProjectId, () => {
  processForm.macro_process_id  = null
  activityForm.macro_process_id = null
  activityForm.process_id       = null
  const last = loadLastMacro()
  if (last && macrosFiltered.value.some(m => String(m.id) === String(last))) {
    processForm.macro_process_id  = String(last)
    activityForm.macro_process_id = String(last)
  }
})

watch(() => processForm.macro_process_id, (val) => {
  if (!val) return
  saveLastMacro(val)
  activityForm.macro_process_id = String(val)
})
watch(() => activityForm.macro_process_id, () => { activityForm.process_id = null })

const macrosFiltered = computed(() =>
  (props.macros||[]).filter(m => String(m.project_id) === String(selectedProjectId.value))
)
const orderDRS = { 'Direction': 1, 'Réalisation': 2, 'Support': 3 }
const macrosFilteredSorted = computed(() => [...macrosFiltered.value].sort((a,b) => (orderDRS[a.kind]||9)-(orderDRS[b.kind]||9)))

const macroOptions = computed(() => {
  if (!selectedProjectId.value) return [{ value: null, text: '— Sélectionner un projet —', disabled: true }]
  const list = macrosFilteredSorted.value
  return [{ value: null, text: list.length ? '— Sélectionner —' : '— Aucun macro —', disabled: true },
          ...list.map(m => ({ value: String(m.id), text: `${m.code} — ${m.name}` })) ]
})
const macroOptionsActivity = macroOptions

const processesFiltered = computed(() => {
  const macroIds = new Set(macrosFiltered.value.map(m => m.id))
  return (props.processes||[]).filter(p => macroIds.has(p.macro_process_id))
})
const activitiesFiltered = computed(() => {
  const macroIds = new Set(macrosFiltered.value.map(m => m.id))
  const processIds = new Set((props.processes||[]).filter(p => macroIds.has(p.macro_process_id)).map(p => p.id))
  return (props.activities||[]).filter(a => processIds.has(a.process_id))
})

const processOptionsActivity = computed(() => {
  const mId = activityForm.macro_process_id
  if (!mId) return [{ value: null, text: '— Choisir un macro d’abord —', disabled: true }]
  const list = (props.processes || []).filter(p => String(p.macro_process_id) === String(mId))
  return [{ value: null, text: list.length ? '— Sélectionner —' : '— Aucun processus —', disabled: true },
          ...list.map(p => ({ value: String(p.id), text: `${p.code} — ${p.name}` })) ]
})

const requiredKinds = ['Direction','Réalisation','Support']
const kindsPresent = computed(() => new Set(macrosFiltered.value.map(m => m.kind)))
const missingKinds = computed(() => requiredKinds.filter(k => !kindsPresent.value.has(k)))
const macrosLocked = computed(() => selectedProjectId.value && macrosFiltered.value.length === 3 && missingKinds.value.length === 0)

const byIdString = (list, key='id') => { const map = {}; (list||[]).forEach(it => { map[String(it[key])] = it }); return map }
const macrosById    = computed(() => byIdString(props.macros))
const processesById = computed(() => byIdString(props.processes))
const macroNameById   = (id) => macrosById.value[String(id)]?.name ?? '—'
const processNameById = (id) => processesById.value[String(id)]?.name ?? '—'

/* Couleur D/R/S gardée pour les tags des LISTES uniquement */
const macroKindById = computed(() => {
  const map = {}
  ;(props.macros || []).forEach(m => { map[String(m.id)] = m.kind })
  return map
})
const kindByProcess  = (macro_process_id) => macroKindById.value[String(macro_process_id)] || null
const kindByActivity = (process_id) => {
  const proc = processesById.value[String(process_id)]
  return proc ? (macroKindById.value[String(proc.macro_process_id)] || null) : null
}

const nextProcessCode = computed(() => {
  const mId = processForm.macro_process_id
  const macro = (props.macros || []).find(m => String(m.id) === String(mId))
  if (!macro) return ''
  const letter = (macro.code || macro.character || macro.kind?.[0] || 'X').toString().trim().toUpperCase().slice(0,1)
  const count  = (props.processes || []).filter(p => String(p.macro_process_id) === String(mId)).length
  return 'P' + String(count + 1).padStart(2, '0') + letter
})
const nextActivityCode = computed(() => {
  const pid  = activityForm.process_id
  const proc = (props.processes||[]).find(p => String(p.id) === String(pid))
  if (!proc) return ''
  let pp = '01', letter = 'X'
  const code = (proc.code || '').toUpperCase()
  const m = code.match(/^P(\d{2})([A-Z])$/)
  if (m) { pp = m[1]; letter = m[2] } else { letter = code.slice(-1) || 'X' }
  const count = (props.activities||[]).filter(a => String(a.process_id) === String(pid)).length
  return 'A' + String(count + 1).padStart(2, '0') + 'P' + pp + letter
})

/* ===== Arborescence (TYPE uniquement, pas de couleur D/R/S) ===== */
const expandedKeys  = ref({})

function makeMacroNode(m) {
  return {
    key: `M-${m.id}`,
    label: m.name,
    // pas d'icon pour ne pas avoir l'icône par défaut
    data: { type: 'macro', id: String(m.id), code: m.code, kind: m.kind },
    children: []
  }
}
function makeProcessNode(p, m) {
  return {
    key: `P-${p.id}`,
    label: p.name,
    data: { type: 'process', id: String(p.id), code: p.code, macroId: String(m.id), kind: m.kind },
    children: []
  }
}
function makeActivityNode(a, p, m) {
  return {
    key: `A-${a.id}`,
    label: a.name,
    data: { type: 'activity', id: String(a.id), code: a.code, processId: String(p.id), macroId: String(m.id), kind: m.kind },
  }
}

const treeNodes = computed(() => {
  const macros = macrosFilteredSorted.value
  if (activeTab.value === 'macro') {
    return macros.map(m => makeMacroNode(m)) // seulement macros
  }
  if (activeTab.value === 'process') {
    return macros.map(m => {
      const root = makeMacroNode(m)
      root.children = (props.processes || [])
        .filter(p => String(p.macro_process_id) === String(m.id))
        .map(p => makeProcessNode(p, m))
      return root
    })
  }
  // activity
  return macros.map(m => {
    const root = makeMacroNode(m)
    const procs = (props.processes || []).filter(p => String(p.macro_process_id) === String(m.id))
    root.children = procs.map(p => {
      const pn = makeProcessNode(p, m)
      pn.children = (props.activities || [])
        .filter(a => String(a.process_id) === String(p.id))
        .map(a => makeActivityNode(a, p, m))
      return pn
    })
    return root
  })
})

/* Rendu helpers */
const nodeLabel    = (n) => n?.label || ''
const nodeIcon     = (n) => (n?.data?.type === 'activity' ? 'pi pi-file' : 'pi pi-folder')
const badgeLetter  = (n) => n?.data?.type === 'macro' ? 'M' : (n?.data?.type === 'process' ? 'P' : 'A')
const typeColorClass = (n) => {
  const t = n?.data?.type
  return t === 'macro' ? 'color-type-macro'
       : t === 'process' ? 'color-type-process'
       : 'color-type-activity'
}

/* Tags des listes (conserve la couleur D/R/S) */
const kindSeverity = (k) => ({ 'Direction':'info','Réalisation':'success','Support':'warning' }[k] || 'secondary')

const countProc = (n) => (n?.children || []).length
const countAct  = (n) => (n?.children || []).reduce((x,c)=> x + (c.children?.length || 0), 0)

/* Routes + Submit */
const r = (name, fallback='/') => (typeof window.route === 'function' ? window.route(name) : fallback)
const submitProcess = () => {
  const payload = { ...processForm.data(), macro_process_id: processForm.macro_process_id ? Number(processForm.macro_process_id) : null }
  if (!payload.macro_process_id) return
  saveLastMacro(payload.macro_process_id)
  activityForm.macro_process_id = String(payload.macro_process_id)
  router.post(r('param.process.store','/param/process'), payload, {
    preserveScroll: true,
    onSuccess: () => { clearProcessName(); router.reload({ only: ['processes'] }) }
  })
}
const submitActivity = () => {
  const payload = { ...activityForm.data() }
  if (!payload.macro_process_id || !payload.process_id) return
  router.post(r('param.activity.store','/param/activity'), payload, {
    preserveScroll: true,
    onSuccess: () => { clearActivityFields(); router.reload({ only: ['activities'] }) }
  })
}
const validateDefaults = () => {
  if (!selectedProjectId.value) return
  router.post(r('param.macro.validate','/param/macro/validate-defaults'), { project_id: selectedProjectId.value }, {
    preserveScroll: true, onSuccess: () => router.reload({ only: ['macros'] })
  })
}

/* Modal rename macro */
const edit = ref({ show:false, id:null, name:'', code:'', kind:'', character:'' })
const savingEdit = ref(false)
const openEditModal = (m) => { edit.value = { show:true, id:m.id, name:m.name, code:m.code, kind:m.kind, character:m.character } }
const submitEdit = async () => {
  if (!edit.value.id || !edit.value.name) return
  try {
    savingEdit.value = true
    await router.put((typeof window.route === 'function' ? window.route('param.macro.update', edit.value.id) : `/param/macro/${edit.value.id}`),
      { name: edit.value.name }, { preserveScroll: true, preserveState: true })
    edit.value.show = false
  } finally {
    savingEdit.value = false
    router.reload({ only: ['macros'] })
  }
}

/* Resets */
const clearProcessName    = () => { processForm.name = '' }
const clearActivityFields = () => { activityForm.name = ''; activityForm.description = '' }
</script>

<style scoped>
/* ——— Ultra compact global ——— */
:where(.topbar, h1,h2,h3,h4,h5,h6,p,small,label,span,th,td,button,input,select,textarea){ letter-spacing:0 }
:where(h4){ font-size:.9rem; margin:0 }
:where(h6){ font-size:.8rem; margin:0 }
:where(label,.form-label){ font-size:.72rem; margin-bottom:.15rem }

/* Espacements compressés */
.mb-3{ margin-bottom:.35rem!important }
.mb-2{ margin-bottom:.25rem!important }
.mb-1{ margin-bottom:.15rem!important }
.p-3 { padding:.5rem!important }
.p-2 { padding:.35rem!important }
.p-1 { padding:.2rem!important }
.py-2{ padding-top:.25rem!important; padding-bottom:.25rem!important }
.px-3{ padding-left:.4rem!important; padding-right:.4rem!important }
.g-2 { --bs-gutter-x:.35rem; --bs-gutter-y:.35rem }
.g-1 { --bs-gutter-x:.25rem; --bs-gutter-y:.25rem }

/* Inputs & boutons XS */
.form-control-sm, .form-select-sm, .input-group-sm>.form-control, .input-group-sm>.form-select{
  font-size:.75rem; height:26px; padding:.15rem .45rem
}
.btn{ padding:.2rem .45rem; font-size:.72rem; line-height:1.1; border-radius:.35rem }
.btn-sm{ padding:.15rem .4rem; font-size:.7rem }

/* Cards compactes */
.card, .b-card{ border-radius:.55rem }
.card-header, .b-card-header{ padding:.35rem .5rem!important }
.card-body, .b-card-body{ padding:.5rem!important }

/* PrimeVue DataTable ultra-flat */
.pv-table :deep(.p-datatable-header),
.pv-table :deep(.p-datatable-footer){ display:none }
.pv-table.flat :deep(.p-datatable-thead > tr > th){
  background:#f8fafc;border:1px solid #e5e7eb;font-weight:600;font-size:.74rem;color:#475569;padding:.25rem .35rem
}
.pv-table.flat :deep(.p-datatable-tbody > tr > td){
  border:1px solid #eef2f7;vertical-align:middle;padding:.25rem .35rem;font-size:.72rem
}
.pv-table.flat :deep(.p-datatable-tbody > tr){ height:26px }

/* Tags */
:deep(.p-tag){ font-weight:600; font-size:.68rem; line-height:1; padding:.15rem .35rem; border-radius:.35rem; height:18px }

/* Tree compact */
:deep(.p-tree){ width:100% }
.pv-tree :deep(.p-tree-container .p-treenode-content){ padding:.25rem .35rem; border-radius:.4rem; font-size:.74rem }
.pv-tree :deep(.p-treenode-content:hover){ background:#f8fafc }
.pv-tree :deep(.p-tree-toggler){ width:1.1rem; height:1.1rem }

/* Icône unique + lettre intégrée (couleur par TYPE seulement) */
.icon-badge{
  position:relative; display:inline-flex; align-items:center; justify-content:center;
  width:22px; height:18px; border-radius:.45rem;
}
.node-icon{ font-size:1rem; color:var(--clr-type, #64748b) }
.badge-letter{
  position:absolute; top:50%; left:50%; transform:translate(-50%,-48%);
  font-size:.7rem; font-weight:900; color:#111; text-shadow:none; pointer-events:none;
}

/* Couleurs TYPE (Macro / Process / Activité) */
.color-type-macro{    --clr-type:#0f766e } /* teal-700 */
.color-type-process{  --clr-type:#7c3aed } /* violet-600 */
.color-type-activity{ --clr-type:#ef4444 } /* red-500 */

/* Code avant nom dans l’arbo */
.code-chip{
  background:#eef2f7;
  border:1px solid #e2e8f0;
  border-radius:.35rem;
  padding:.05rem .35rem;
  font-size:.72rem;
  color:#0f172a;
}

/* Table + textes */
.table td,.table th{ padding:.35rem .45rem; font-size:.74rem }
.small, small{ font-size:.7rem }

/* Textareas XS */
.compact-textarea{ min-height:64px; resize:none; font-size:.75rem }

/* Mono */
.font-monospace{
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
  font-size:.74rem
}
</style>
