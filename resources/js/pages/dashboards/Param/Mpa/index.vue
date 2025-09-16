<template>
  <VerticalLayout>
    <Head title="Processus — Paramétrage" />

    <!-- Titre compact -->
    <b-row class="g-1">
      <b-col cols="12">
        <div class="d-flex align-items-center justify-content-between py-1">
          <div>
            <h4 class="fs-16 fw-semibold m-0">DIADDEM — Processus</h4>
            <small class="text-muted">Interface compacte (sans scroll interne des formulaires)</small>
          </div>
        </div>
      </b-col>
    </b-row>

    <!-- Onglets -->
    <b-card no-body class="mb-2 shadow-none border-0">
      <b-card-body class="p-1">
        <b-button-group size="sm">
          <b-button @click="activeTab='macro'"   :variant="activeTab==='macro'   ? 'primary' : 'outline-primary'"><i class="ti ti-cube me-1"></i> Macro</b-button>
          <b-button @click="activeTab='process'" :variant="activeTab==='process' ? 'primary' : 'outline-primary'"><i class="ti ti-settings me-1"></i> Processus</b-button>
          <b-button @click="activeTab='activity'" :variant="activeTab==='activity' ? 'primary' : 'outline-primary'"><i class="ti ti-list-details me-1"></i> Activités</b-button>
        </b-button-group>
      </b-card-body>
    </b-card>

    <b-row class="g-2">
      <!-- Formulaire (50%) -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3">
            <h4 class="header-title mb-0 fs-14">
              <span v-if="activeTab==='macro'">Édition Macro</span>
              <span v-else-if="activeTab==='process'">Création Processus</span>
              <span v-else>Édition Activité</span>
            </h4>
          </b-card-header>

          <b-card-body class="p-3">
            <!-- ===== MACRO ===== -->
            <b-form v-if="activeTab==='macro'" @submit.prevent="submitMacro">
              <b-row class="g-2">
                <b-col cols="12">
                  <label class="form-label mb-1">Projet <span class="text-danger">*</span></label>
                  <b-form-select v-model="macroForm.project_id" :options="projectOptions" required class="form-select-sm compact-input"/>
                </b-col>

                <b-col md="4">
                  <label class="form-label mb-1">Code <span class="text-danger">*</span></label>
                  <b-form-input v-model.trim="macroForm.code" required class="form-control-sm compact-input"/>
                </b-col>

                <b-col md="8">
                  <label class="form-label mb-1">Nom Macro <span class="text-danger">*</span></label>
                  <b-form-input v-model.trim="macroForm.name" required class="form-control-sm compact-input"/>
                </b-col>

                <b-col md="4">
                  <label class="form-label mb-1">Caract.</label>
                  <b-form-input v-model.trim="macroForm.character" class="form-control-sm compact-input text-center"/>
                </b-col>

                <b-col md="8">
                  <label class="form-label mb-1">Description</label>
                  <b-form-textarea v-model.trim="macroForm.description" v-autosize rows="2" class="form-control-sm compact-input compact-textarea"/>
                </b-col>

                <b-col cols="12" class="text-end pt-1">
                  <b-button variant="light" size="sm" class="me-1" @click="macroForm.reset()">Annuler</b-button>
                  <b-button variant="primary" size="sm" type="submit" :disabled="macroForm.processing">Valider</b-button>
                </b-col>
              </b-row>
            </b-form>

            <!-- ===== PROCESS ===== -->
            <b-form v-else-if="activeTab==='process'" @submit.prevent="submitProcess">
              <b-row class="g-2">
                <b-col md="6">
                  <label class="form-label mb-1">Projet <span class="text-danger">*</span></label>
                  <b-form-select v-model="processForm.project_id" :options="projectOptions" required class="form-select-sm compact-input"/>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Entité</label>
                  <b-form-select v-model="processForm.entity_id" :options="entityOptions" class="form-select-sm compact-input"/>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Macro Processus</label>
                  <b-form-select v-model="processForm.macro_id" :options="macroOptions" class="form-select-sm compact-input"/>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Car.</label>
                  <b-form-input v-model.trim="processForm.car" placeholder="O1" class="form-control-sm compact-input text-center"/>
                </b-col>

                <b-col cols="12">
                  <label class="form-label mb-1">Nom Processus <span class="text-danger">*</span></label>
                  <b-form-input v-model.trim="processForm.name" required class="form-control-sm compact-input"/>
                  <small v-if="processForm.errors.name" class="text-danger">{{ processForm.errors.name }}</small>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Description / Finalité</label>
                  <b-form-textarea v-model.trim="processForm.description" v-autosize rows="2" class="form-control-sm compact-input compact-textarea"/>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Objectif Opérationnel</label>
                  <b-form-textarea v-model.trim="processForm.objective" v-autosize rows="2" class="form-control-sm compact-input compact-textarea"/>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Donnée d’entrée</label>
                  <b-form-input v-model.trim="processForm.input_data" class="form-control-sm compact-input"/>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Donnée de sortie</label>
                  <b-form-input v-model.trim="processForm.output_data" class="form-control-sm compact-input"/>
                </b-col>

                <b-col cols="12">
                  <label class="form-label mb-1">Ressources</label>
                  <b-form-input v-model.trim="processForm.resources" class="form-control-sm compact-input"/>
                </b-col>

                <b-col cols="12" class="text-end pt-1">
                  <b-button variant="light" size="sm" class="me-1" @click="processForm.reset()">Annuler</b-button>
                  <b-button variant="primary" size="sm" type="submit" :disabled="processForm.processing">Valider</b-button>
                </b-col>
              </b-row>
            </b-form>

            <!-- ===== ACTIVITY ===== -->
            <b-form v-else @submit.prevent="submitActivity">
              <b-row class="g-2">
                <b-col md="6">
                  <label class="form-label mb-1">Macro Processus</label>
                  <b-form-select v-model="activityForm.macro_id" :options="macroOptions" class="form-select-sm compact-input"/>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Processus <span class="text-danger">*</span></label>
                  <b-form-select v-model="activityForm.process_id" :options="processOptions" required class="form-select-sm compact-input"/>
                </b-col>

                <b-col md="4">
                  <label class="form-label mb-1">Code <span class="text-danger">*</span></label>
                  <b-form-input v-model.trim="activityForm.code" required class="form-control-sm compact-input"/>
                </b-col>

                <b-col md="8">
                  <label class="form-label mb-1">Libellé Activité <span class="text-danger">*</span></label>
                  <b-form-input v-model.trim="activityForm.name" required class="form-control-sm compact-input"/>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Acteur</label>
                  <b-form-input v-model.trim="activityForm.actor" class="form-control-sm compact-input"/>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Fréquence</label>
                  <b-form-select v-model="activityForm.frequency" :options="frequencyOptions" class="form-select-sm compact-input"/>
                </b-col>

                <b-col cols="12">
                  <label class="form-label mb-1">Description</label>
                  <b-form-textarea v-model.trim="activityForm.description" v-autosize rows="2" class="form-control-sm compact-input compact-textarea"/>
                </b-col>

                <b-col cols="12" class="text-end pt-1">
                  <b-button variant="light" size="sm" class="me-1" @click="activityForm.reset()">Annuler</b-button>
                  <b-button variant="primary" size="sm" type="submit" :disabled="activityForm.processing">Valider</b-button>
                </b-col>
              </b-row>
            </b-form>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Liste (50%) -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3">
            <h4 class="header-title mb-0 fs-14">
              <span v-if="activeTab==='macro'">Macro-processus</span>
              <span v-else-if="activeTab==='process'">Processus</span>
              <span v-else>Activités</span>
            </h4>
          </b-card-header>

          <b-card-body class="p-0">
            <div class="table-responsive">
              <!-- MACROS -->
              <b-table-simple v-if="activeTab==='macro'" small hover class="mb-0">
                <b-thead class="bg-light">
                  <b-tr>
                    <b-th class="text-muted text-uppercase fs-11">ID</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Nom</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Description</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Car.</b-th>
                  </b-tr>
                </b-thead>
                <b-tbody>
                  <b-tr v-for="m in macros" :key="m.id">
                    <b-td class="font-monospace text-primary">{{ m.code }}</b-td>
                    <b-td class="fw-semibold">{{ m.name }}</b-td>
                    <b-td class="text-wrap" style="max-width:300px">{{ m.description || '—' }}</b-td>
                    <b-td class="text-center">{{ m.character || '—' }}</b-td>
                  </b-tr>
                  <b-tr v-if="!macros || !macros.length">
                    <b-td colspan="4" class="text-center text-muted py-2">Aucun macro</b-td>
                  </b-tr>
                </b-tbody>
              </b-table-simple>

              <!-- PROCESS -->
              <b-table-simple v-else-if="activeTab==='process'" small hover class="mb-0">
                <b-thead class="bg-light">
                  <b-tr>
                    <b-th class="text-muted text-uppercase fs-11">Code</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Libellé</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Objectif</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Macro</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Entité</b-th>
                  </b-tr>
                </b-thead>
                <b-tbody>
                  <b-tr v-for="p in processes" :key="p.id">
                    <b-td class="font-monospace text-primary">{{ p.code }}</b-td>
                    <b-td class="fw-semibold">{{ p.name }}</b-td>
                    <b-td class="text-wrap" style="max-width:260px">{{ p.objective || '—' }}</b-td>
                    <b-td>{{ p.macro?.name ?? '—' }}</b-td>
                    <b-td>{{ p.entity?.name ?? '—' }}</b-td>
                  </b-tr>
                  <b-tr v-if="!processes || !processes.length">
                    <b-td colspan="5" class="text-center text-muted py-2">Aucun processus</b-td>
                  </b-tr>
                </b-tbody>
              </b-table-simple>

              <!-- ACTIVITÉS -->
              <b-table-simple v-else small hover class="mb-0">
                <b-thead class="bg-light">
                  <b-tr>
                    <b-th class="text-muted text-uppercase fs-11">Code</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Activité</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Processus</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Macro</b-th>
                    <b-th class="text-muted text-uppercase fs-11">Entité</b-th>
                  </b-tr>
                </b-thead>
                <b-tbody>
                  <b-tr v-for="a in activities" :key="a.id">
                    <b-td class="font-monospace text-primary">{{ a.code }}</b-td>
                    <b-td class="fw-semibold text-wrap" style="max-width:280px">{{ a.name }}</b-td>
                    <b-td>{{ a.process?.name ?? '—' }}</b-td>
                    <b-td>{{ a.process?.macro?.code ?? '—' }}</b-td>
                    <b-td>{{ a.process?.entity?.name ?? '—' }}</b-td>
                  </b-tr>
                  <b-tr v-if="!activities || !activities.length">
                    <b-td colspan="5" class="text-center text-muted py-2">Aucune activité</b-td>
                  </b-tr>
                </b-tbody>
              </b-table-simple>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, toRefs, ref, nextTick } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  processes:  { type: Array, default: () => [] },
  projects:   { type: Array, default: () => [] },
  entities:   { type: Array, default: () => [] },
  macros:     { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
})
const { processes, projects, entities, macros, activities } = toRefs(props)

const r = (name, fallback = '/') => (typeof window.route === 'function' ? window.route(name) : fallback)
const activeTab = ref('macro')

/* FORMS */
const macroForm = useForm({ project_id: null, code: '', name: '', description: '', character: '' })
const processForm = useForm({
  project_id: null, entity_id: null, macro_id: null, code: '', car: '', name: '',
  description: '', objective: '', input_data: '', output_data: '', resources: ''
})
const activityForm = useForm({ macro_id: null, process_id: null, code: '', name: '', description: '', actor: '', frequency: '' })

/* v-autosize => variable nommée vAutosize pour utiliser v-autosize */
const vAutosize = {
  mounted(el) {
    const resize = () => { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px' }
    el.__resize = resize; el.style.overflow = 'hidden'; el.style.resize = 'none'
    nextTick(resize); el.addEventListener('input', resize)
  },
  unmounted(el) { el.removeEventListener('input', el.__resize) }
}

/* Options */
const projectOptions = computed(() => [{ value: null, text: '— Sélectionner —', disabled: true }, ...(projects.value||[]).map(p => ({ value: p.id, text: p.name }))])
const entityOptions  = computed(() => [{ value: null, text: '— Sélectionner —' }, ...(entities.value||[]).map(e => ({ value: e.id, text: e.name }))])
const macroOptions   = computed(() => [{ value: null, text: '— Sélectionner —' }, ...(macros.value||[]).map(m => ({ value: m.id, text: `${m.code} — ${m.name}` }))])
const processOptions = computed(() => {
  const base=[{ value: null, text: '— Sélectionner —', disabled: true }]
  if (!activityForm.macro_id) return [...base, ...(processes.value||[]).map(p=>({ value:p.id, text:`${p.code} — ${p.name}`}))]
  const filtered=(processes.value||[]).filter(p=>p.macro_id===activityForm.macro_id)
  return [...base, ...filtered.map(p=>({ value:p.id, text:`${p.code} — ${p.name}`}))]
})

/* Submit */
const submitMacro    = () => macroForm.post(r('param.macro.store','/param/macro'),           { preserveScroll: true, onSuccess: () => macroForm.reset() })
const submitProcess  = () => processForm.post(r('param.process.store','/param/process'),     { preserveScroll: true, onSuccess: () => processForm.reset() })
const submitActivity = () => activityForm.post(r('param.activity.store','/param/activity'),  { preserveScroll: true, onSuccess: () => activityForm.reset() })
</script>

<style scoped>
/* Conteneur compact : pas de scroll interne dans les cartes/formulaires */
:deep(.container-fluid){padding:8px;}
.card{margin-bottom:0;}
.card-body{overflow:visible;}

/* Inputs compacts et sobres */
.compact-input{
  background:#f9fafb;
  border:1px solid #e5e7eb;
  border-radius:8px;
  padding:.35rem .55rem;
  font-size:.85rem;
}
.compact-input:focus{background:#fff;border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.15);outline:0;}
.compact-textarea{min-height:72px;line-height:1.35;overflow:hidden!important;resize:none!important;}

/* Labels compacts */
.form-label{font-size:.82rem;font-weight:600;color:#334155;margin-bottom:.1rem;}

/* Tables serrées */
.table-responsive{max-height:none;overflow:visible;}
.table td,.table th,.b-table-stacked-sm td,.b-table-stacked-sm th{padding:.4rem .5rem;}

/* Typo compacte */
.fs-11{font-size:.68rem;}
.fs-14{font-size:.92rem;}
.fs-16{font-size:1rem;}
</style>
