<!-- resources/js/pages/dashboards/Param/Functions/index.vue -->
<template>
  <VerticalLayout>
    <Head title="Fonctions — Paramétrage" />

    <!-- Header -->
    <b-row class="mb-0">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-hierarchy-3 text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Fonctions</h4>
            <small class="text-muted ms-2">Projet obligatoire</small>
          </div>
          <b-input-group size="sm" class="w-auto">
            <b-input-group-text><i class="ti ti-briefcase"></i></b-input-group-text>
            <b-form-select v-model="selectedProjectId" :options="projectOptions" />
          </b-input-group>
        </div>
      </b-col>
    </b-row>

    <b-row class="g-1">
      <!-- Formulaire + LISTE -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100 position-relative">
          <div v-if="loading" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background:rgba(255,255,255,.6); z-index:2;">
            <b-spinner small class="me-2"></b-spinner> Chargement…
          </div>

          <b-card-header class="py-2 px-3"><h6 class="mb-0">Édition</h6></b-card-header>

          <b-card-body class="p-2">
            <b-alert v-if="!selectedProjectId" show variant="secondary" class="py-2 px-3 mb-2">
              Sélectionnez un projet pour activer le formulaire et la liste.
            </b-alert>

            <b-form v-else @submit.prevent="submit" class="mb-2">
              <b-row class="g-1">
                <b-col cols="12">
                  <label class="form-label mb-1">Caract. Fonction</label>
                  <b-form-input class="form-control-sm" v-model.trim="form.character" placeholder="Ex: DGA, RESP, …" />
                  <small v-if="form.errors.character" class="text-danger">{{ form.errors.character }}</small>
                </b-col>

                <b-col cols="12">
                  <label class="form-label mb-1">Nom <span class="text-danger">*</span></label>
                  <b-form-input class="form-control-sm" v-model.trim="form.name" required />
                  <small v-if="form.errors.name" class="text-danger">{{ form.errors.name }}</small>
                </b-col>

                <!-- AVATAR -->
                <b-col cols="12">
                  <label class="form-label mb-1">Avatar (optionnel)</label>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <img v-if="avatarPreview || form.avatar_url" :src="avatarPreview || form.avatar_url" class="avatar-preview rounded" alt="avatar" />
                    <b-form-file accept="image/*" size="sm" @change="onPickAvatar" />
                    <b-button v-if="form.avatar || form.avatar_url" size="sm" variant="outline-danger" @click="clearAvatar">Retirer</b-button>
                  </div>
                  <small class="text-muted">PNG/JPG ≤ 2 Mo</small>
                  <small v-if="form.errors.avatar" class="text-danger d-block">{{ form.errors.avatar }}</small>
                </b-col>

                <b-col cols="12">
                  <div class="d-flex align-items-center justify-content-between">
                    <label class="form-label mb-1">Liaison (parent)</label>
                    <b-form-checkbox switch size="sm" v-model="noParent">Sans parent</b-form-checkbox>
                  </div>
                  <b-form-select class="form-select-sm" v-model="form.parent_id" :options="parentOptions" :disabled="noParent" />
                  <small v-if="form.errors.parent_id" class="text-danger">{{ form.errors.parent_id }}</small>
                </b-col>

                <b-col cols="12" class="d-flex justify-content-end gap-2 pt-1">
                  <b-button variant="light" size="sm" @click="resetForm"><i class="ti ti-plus me-1"></i> Nouveau</b-button>
                  <b-button variant="primary" size="sm" type="submit" :disabled="loading || form.processing || !form.name">
                    <i class="ti ti-device-floppy me-1"></i> Enregistrer
                  </b-button>
                </b-col>
              </b-row>
            </b-form>

            <!-- LISTE -->
            <DataTable :value="functionsFilteredSorted" size="small" class="pv-table flat">
              <Column header="Photo" style="width:72px">
                <template #body="{ data }">
                  <div class="d-flex align-items-center">
                    <img v-if="data.avatar_url" :src="data.avatar_url" class="tbl-avatar rounded-circle me-2" alt="" />
                    <span v-else class="tbl-initials rounded-circle me-2">{{ initials(data) }}</span>
                  </div>
                </template>
              </Column>
              <Column header="Caract." style="width:110px">
                <template #body="{ data }">
                  <Tag :value="data.character || '—'" :severity="data.character ? 'info' : 'secondary'" class="font-monospace" />
                </template>
              </Column>
              <Column header="Nom">
                <template #body="{ data }"><span class="fw-semibold">{{ data.name }}</span></template>
              </Column>
              <Column header="Parent" style="width:240px">
                <template #body="{ data }">
                  <div class="d-flex align-items-center gap-1">
                    <span class="text-muted">↳</span>
                    <span>{{ parentNameById(data.parent_id) }}</span>
                  </div>
                </template>
              </Column>
              <Column header=" " style="width:110px" bodyClass="text-end">
                <template #body="{ data }">
                  <b-button size="sm" variant="light" class="me-1" @click="editRow(data)" title="Modifier"><i class="ti ti-pencil"></i></b-button>
                  <b-button size="sm" variant="danger" @click="destroy(data.id)" title="Supprimer"><i class="ti ti-trash"></i></b-button>
                </template>
              </Column>
              <template #empty><div class="text-muted py-1">Aucune fonction</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Arborescence -->
      <b-col lg="6" xl="6">
        <b-card no-body class="shadow-sm h-100 position-relative">
          <div v-if="loading" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background:rgba(255,255,255,.6); z-index:2;">
            <b-spinner small class="me-2"></b-spinner> Chargement…
          </div>

          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-folders me-1"></i> Arborescence des fonctions</h6>
            <small class="text-muted">
              <template v-if="selectedProjectId">Projet : <strong>{{ selectedProjectName }}</strong></template>
              <template v-else>—</template>
            </small>
          </b-card-header>

          <b-card-body class="p-2">
            <b-alert v-if="!selectedProjectId" show variant="secondary" class="m-2">
              Sélectionnez un projet pour afficher l’arborescence.
            </b-alert>

            <div v-else>
              <Tree
                :value="treeNodes"
                v-model:expandedKeys="expandedKeys"
                selectionMode="single"
                :filter="true"
                filterMode="lenient"
                :showIcon="false"
                class="w-100 pv-tree rounded"
                @node-select="onNodeSelect"
              >
                <template #default="{ node }">
                  <div class="d-flex align-items-center gap-2">
                    <img v-if="node?.data?.avatar_url" :src="node.data.avatar_url" class="tree-avatar rounded-circle" alt="">
                    <span v-else class="fx-badge" :class="badgeClass(node)" role="img">{{ overlayLetters(node) }}</span>

                    <span class="code-chip font-monospace">{{ nodeCode(node) }}</span>
                    <span class="fw-semibold">{{ node.label }}</span>
                  </div>
                </template>
              </Tree>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import { computed, ref, watch, onMounted } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tree from 'primevue/tree'
import Tag from 'primevue/tag'

const props = defineProps({
  projects:  { type: Array, default: () => [] },
  functions: { type: Array, default: () => [] }, // [{id,project_id,name,character,parent_id,avatar_url}]
  filters:   { type: Object, default: () => ({ project_id: null }) }
})

/* Etat */
const loading = ref(false)
const selectedProjectId = ref(props.filters?.project_id ?? null)
if (!selectedProjectId.value && props.projects?.length === 1) selectedProjectId.value = props.projects[0].id
const selectedProjectName = computed(() => (props.projects||[]).find(p => String(p.id) === String(selectedProjectId.value))?.name ?? '')
const projectOptions = computed(() => [{ value: null, text: '— Sélectionner un projet —', disabled: true }, ...(props.projects||[]).map(p => ({ value: p.id, text: p.name }))])

/* Formulaire (inclut avatar) */
const form = useForm({
  project_id: selectedProjectId.value,
  id: null,
  character: '',
  name: '',
  parent_id: null,
  avatar: null,          // File
  avatar_url: '',        // pour preview quand on édite
  remove_avatar: false,  // bool suppression
})
const noParent = ref(true)
const avatarPreview = ref(null)

/* Chargement par projet */
const routeTo = (name, params = {}) => (typeof window.route === 'function' ? window.route(name, params) : '/param/function')
const fetchProjectData = () => {
  if (!selectedProjectId.value) return
  loading.value = true
  router.get(routeTo('param.function.index'), { project_id: selectedProjectId.value }, {
    preserveState: true, preserveScroll: true, replace: true, onFinish: () => { loading.value = false }
  })
  form.project_id = selectedProjectId.value
}
watch(selectedProjectId, fetchProjectData, { immediate: true })
onMounted(() => { if (selectedProjectId.value) fetchProjectData() })

/* Dérivés */
const functionsFiltered = computed(() => (props.functions||[]).filter(f => String(f.project_id) === String(selectedProjectId.value)))
const byId = computed(() => Object.fromEntries(functionsFiltered.value.map(f => [String(f.id), f])))
const parentNameById = (pid) => (pid ? (byId.value[String(pid)]?.name ?? '—') : '—')

const parentOptions = computed(() => {
  const list = functionsFiltered.value.filter(f => String(f.id) !== String(form.id ?? ''))
  return [{ value: null, text: '— Aucun —' }, ...list.map(f => ({ value: f.id, text: f.name })) ]
})

const functionsFilteredSorted = computed(() => {
  const roots = functionsFiltered.value.filter(f => !f.parent_id)
  const childs= functionsFiltered.value.filter(f => f.parent_id)
  return [
    ...roots.sort((a,b)=>a.name.localeCompare(b.name)),
    ...childs.sort((a,b)=>a.name.localeCompare(b.name)),
  ]
})

/* CRUD (multipart via forceFormData) */
const r = (name, fallback='/') => (typeof window.route === 'function' ? window.route(name) : fallback)
const afterMutate = () => { resetForm(); loading.value = true; router.reload({ only: ['functions'], onFinish: () => { loading.value = false } }) }

const submit = () => {
  form.project_id = selectedProjectId.value
  const opts = { preserveScroll: true, onSuccess: afterMutate, forceFormData: true }
  if (form.id) form.put(r('param.function.update', `/param/function/${form.id}`), opts)
  else         form.post(r('param.function.store','/param/function'), opts)
}

const editRow = (f) => {
  form.id         = f.id
  form.project_id = f.project_id
  form.character  = f.character || ''
  form.name       = f.name
  form.parent_id  = f.parent_id
  form.avatar     = null
  form.avatar_url = f.avatar_url || ''
  form.remove_avatar = false
  avatarPreview.value = null
  noParent.value  = !f.parent_id
}

const destroy = (id) => {
  if (!confirm('Supprimer cette fonction ?')) return
  router.delete(r('param.function.destroy', `/param/function/${id}`), {
    data:{ project_id:selectedProjectId.value }, preserveScroll:true, onSuccess: afterMutate
  })
}

/* Avatar handlers */
const onPickAvatar = (e) => {
  avatarPreview.value = null
  const file = e?.target?.files?.[0]
  if (file) {
    form.remove_avatar = false
    form.avatar = file
    avatarPreview.value = URL.createObjectURL(file)
  }
}
const clearAvatar = () => {
  form.avatar = null
  form.remove_avatar = true
  avatarPreview.value = null
  form.avatar_url = ''
}

/* Helpers */
const resetForm = () => {
  form.reset()
  form.project_id = selectedProjectId.value
  noParent.value = true
  avatarPreview.value = null
}

/* Initiales fallback */
const initials = (f) => {
  const raw = (f?.character || '').trim()
  if (raw) return raw.slice(0,3).toUpperCase()
  const name = (f?.name || '').trim()
  if (!name) return 'F'
  return name.split(/\s+/).map(w => w[0] || '').join('').slice(0,3).toUpperCase()
}

/* Arborescence */
const expandedKeys = ref({})

function buildTree(items){
  const childrenByParent = {}
  items.forEach(it => {
    const pid = String(it.parent_id ?? 'root')
    if (!childrenByParent[pid]) childrenByParent[pid] = []
    childrenByParent[pid].push(it)
  })
  const toNode = (it) => ({
    key:`F-${it.id}`,
    label: it.name,
    data:{
      id:String(it.id),
      code:(it.character || '').toString(),
      avatar_url: it.avatar_url || null,
    },
    children:(childrenByParent[String(it.id)]||[]).map(toNode)
  })
  return (childrenByParent['root']||[]).map(toNode)
}
const treeNodes = computed(() => buildTree(functionsFiltered.value))

/* Pictos */
const nodeCode = (n) => (n?.data?.code || '—')
const overlayLetters = (n) => {
  const raw = (n?.data?.code || '').trim()
  if (raw) return raw.slice(0,3).toUpperCase()
  const name = (n?.label || '').toString().trim()
  if (!name) return 'F'
  const initials = name.split(/\s+/).map(w => w[0] || '').join('')
  return (initials || 'F').slice(0,3).toUpperCase()
}
const badgeClass = (n) => (n?.children?.length ? 'fx-parent' : 'fx-leaf')

const onNodeSelect = (e) => {
  const id = e?.node?.data?.id
  if (!id) return
  const f = byId.value[String(id)]
  if (f) editRow(f)
}
</script>

<style scoped>
/* compact */
:where(h4){ font-size:.9rem; margin:0 }
:where(h6){ font-size:.8rem; margin:0 }
.form-control-sm,.form-select-sm{ font-size:.75rem; height:26px; padding:.15rem .45rem }
.btn{ padding:.2rem .45rem; font-size:.72rem; line-height:1.1; border-radius:.35rem }

/* DataTable */
.pv-table :deep(.p-datatable-thead > tr > th){ background:#f8fafc;border:1px solid #e5e7eb;font-weight:600;font-size:.74rem;color:#475569;padding:.25rem .35rem }
.pv-table :deep(.p-datatable-tbody > tr > td){ border:1px solid #eef2f7;vertical-align:middle;padding:.25rem .35rem;font-size:.72rem }
:deep(.p-tag){ font-weight:600; font-size:.68rem; line-height:1; padding:.15rem .35rem; border-radius:.35rem; height:18px }

/* Avatars */
.avatar-preview{ width:42px; height:42px; object-fit:cover; border:1px solid #e5e7eb }
.tbl-avatar{ width:34px; height:34px; object-fit:cover; border:1px solid #e5e7eb }
.tbl-initials{
  width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center;
  font-weight:800; font-size:.72rem; background:#e2e8f0; color:#111; border:1px solid #cbd5e1;
}
.tree-avatar{ width:22px; height:22px; object-fit:cover; border:1px solid #e5e7eb }

/* Tree */
:deep(.p-tree){ width:100% }
.pv-tree :deep(.p-treenode-icon){ display:none !important }
.pv-tree :deep(.p-tree-container .p-treenode-content){ padding:.25rem .35rem; border-radius:.4rem; font-size:.74rem }
.pv-tree :deep(.p-treenode-content:hover){ background:#f8fafc }

/* Badges quand pas d'image */
.fx-badge{
  min-width:24px; height:18px; border-radius:.35rem;
  display:inline-flex; align-items:center; justify-content:center;
  padding:0 .35rem; font-weight:900; font-size:.72rem; line-height:1;
  color:#111; background:#fef08a; border:1px solid #eab308;
}
.fx-parent{ background:#c7e3ff; border-color:#93c5fd; }
.fx-leaf{   background:#fde68a; border-color:#f59e0b; }

.code-chip{
  background:#eef2f7; border:1px solid #e2e8f0; border-radius:.35rem;
  padding:.05rem .35rem; font-size:.72rem; color:#0f172a;
}
</style>
