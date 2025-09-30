<template>
  <VerticalLayout>
    <Head title="Entités — Paramétrage" />

    <!-- Header -->
    <b-row class="mb-2">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-building-community text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Entités</h4>
            <small class="text-muted ms-2">Créez et organisez les entités de vos projets</small>
          </div>
          <div class="d-none d-md-flex align-items-center">
            <b-input-group size="sm" class="w-auto">
              <b-input-group-text><i class="ti ti-search"></i></b-input-group-text>
              <b-form-input v-model="q" placeholder="Rechercher..." />
            </b-input-group>
          </div>
        </div>
      </b-col>
    </b-row>

    <b-row>
      <!-- Formulaire (ultra-compact) -->
      <b-col lg="7" xl="8" class="mb-3">
        <b-card no-body>
          <b-card-header class="py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 d-flex align-items-center gap-2">
              <i class="ti ti-building text-primary"></i> Nouvelle entité
            </h6>
            <span class="text-muted small"><i class="ti ti-info-circle"></i> Niveau & code auto</span>
          </b-card-header>

          <b-card-body class="py-2">
            <!-- Contexte mini -->
            <div class="border rounded p-2 mb-2 bg-light d-flex flex-wrap align-items-center gap-2">
              <span class="badge bg-primary"><i class="ti ti-briefcase me-1"></i>{{ selectedProjectName || 'Projet ' }}</span>
              <span class="badge bg-secondary"><i class="ti ti-hierarchy-2 me-1"></i>{{ selectedParentName || 'Aucun parent' }}</span>
              <span class="badge bg-dark"><i class="ti ti-stairs-up me-1"></i>Niveau {{ autoLevel }}</span>
              <span class="badge bg-light text-dark"><i class="ti ti-letter-a me-1"></i>{{ previewCodeBase }}</span>
            </div>

            <b-form @submit.prevent="submit">
              <b-row class="g-2">
                <b-col md="6">
                  <label class="form-label mb-1">Projet <span class="text-danger">*</span></label>
                  <b-form-select size="sm" v-model="form.project_id" :options="projectOptions" required />
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Nom Entité<span class="text-danger">*</span></label>
                  <b-form-input size="sm" v-model.trim="form.name" placeholder="Nom de l'entité" required />
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Parent</label>
                  <b-form-select size="sm" v-model="form.parent_id" :options="parentOptions">
                    <template #first>
                      <option :value="null">— Aucun —</option>
                    </template>
                  </b-form-select>
                </b-col>

                <b-col md="6">
                  <label class="form-label mb-1">Responsable</label>
                  <b-input-group size="sm">
                    <b-input-group-text><i class="ti ti-user-circle"></i></b-input-group-text>
                    <b-form-input v-model="form.leader" />
                  </b-input-group>
                </b-col>

                <!-- Bloc options replié (évite le scroll) -->
                <b-col cols="12">
                  <b-button
                    size="sm"
                    variant="light"
                    class="mt-1"
                    @click="showMore=!showMore"
                  >
                    <i :class="showMore ? 'ti ti-chevron-up' : 'ti ti-chevron-down'"></i>
                    Plus d’options
                  </b-button>
                </b-col>

                <b-collapse v-model="showMore">
                  <b-row class="g-2 mt-1">
                    <b-col md="6">
                      <label class="form-label mb-1">Email</label>
                      <b-input-group size="sm">
                        <b-input-group-text><i class="ti ti-mail"></i></b-input-group-text>
                        <b-form-input type="email" v-model="form.email" />
                      </b-input-group>
                    </b-col>

                    <b-col md="6">
                      <label class="form-label mb-1">Téléphone</label>
                      <b-input-group size="sm">
                        <b-input-group-text><i class="ti ti-phone"></i></b-input-group-text>
                        <b-form-input v-model="form.phone" />
                      </b-input-group>
                    </b-col>

                    <b-col md="6">
                      <label class="form-label mb-1">Logo (URL)</label>
                      <b-input-group size="sm">
                        <b-input-group-text><i class="ti ti-photo"></i></b-input-group-text>
                        <b-form-input v-model="form.logo" />
                      </b-input-group>
                    </b-col>

                    <b-col md="6">
                      <label class="form-label mb-1">Code (auto si vide)</label>
                      <b-form-input size="sm" v-model="form.code_base" placeholder="Laisser vide pour auto" />
                    </b-col>

                    <b-col md="6">
                      <label class="form-label mb-1">Adresse</label>
                      <b-form-textarea rows="2" v-model="form.address" />
                    </b-col>

                    <b-col md="6">
                      <label class="form-label mb-1">Description</label>
                      <b-form-textarea rows="2" v-model="form.description" />
                    </b-col>
                  </b-row>
                </b-collapse>

                <b-col cols="12" class="d-flex justify-content-end gap-2 mt-2">
                  <b-button size="sm" variant="light" :disabled="form.processing" @click="form.reset()">
                    <i class="ti ti-arrow-back-up me-1"></i> Annuler
                  </b-button>
                  <b-button size="sm" variant="primary" type="submit" :disabled="form.processing">
                    <i class="ti ti-device-floppy me-1"></i> Enregistrer
                  </b-button>
                </b-col>
              </b-row>
            </b-form>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Liste (très compacte) -->
      <b-col lg="5" xl="4" class="mb-3">
        <b-card no-body>
          <b-card-header class="py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 d-flex align-items-center gap-2">
              <i class="ti ti-list-details text-primary"></i> Entités
            </h6>
            <div class="text-muted small">{{ filtered.length }} élément(s)</div>
          </b-card-header>

          <b-card-body class="p-0">
            <div class="table-responsive">
              <b-table-simple hover class="table table-sm mb-0 align-middle">
                <b-thead class="bg-light">
                  <b-tr>
                    <b-th>Entité / Projet</b-th>
                    <b-th>Mère</b-th>
                    <b-th class="text-end" style="width:96px;">Actions</b-th>
                  </b-tr>
                </b-thead>
                <b-tbody>
                  <b-tr v-for="e in filtered" :key="e.id">
                    <b-td class="fw-semibold">
                      <div class="d-flex flex-column">
                        <div class="d-flex align-items-center gap-2">
                          <i class="ti ti-building text-secondary"></i>
                          <span>{{ e.name }}</span>
                          
                        </div>
                        <div class="small text-muted mt-1">
                          <span class="badge bg-primary">{{ e.project?.name || '—' }}</span>
                          
                        </div>
                      </div>
                    </b-td>
                    <b-td><span v-if="e.code_base" class="badge bg-light text-dark">{{ e.code_base }}</span></b-td>
                    <b-td class="text-end">
                      <Link :href="route('param.entities.edit', e.id)" class="btn btn-light btn-icon btn-sm me-1" title="Modifier">
                        <i class="ti ti-pencil"></i>
                      </Link>
                      <b-button size="sm" variant="danger" class="btn-icon" @click="destroyEntity(e.id)" title="Supprimer">
                        <i class="ti ti-trash"></i>
                      </b-button>
                    </b-td>
                  </b-tr>
                  <b-tr v-if="filtered.length === 0">
                    <b-td colspan="3" class="text-center text-muted py-3">Aucune entité.</b-td>
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

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

type Project = { id:number|string; name:string }
type Parent  = { id:number|string; name:string; project_id:number|string; level?:number|null; code_base?:string|null }
type Entity  = {
  id:number|string; project_id:number|string; name:string; description?:string|null;
  level?: number|null; parent_id?: number|string|null; code_base?:string|null; logo?:string|null;
  phone?:string|null; email?:string|null; leader?:string|null; address?:string|null;
  project?: { id:number|string; name:string } | null; parent?: { id:number|string; name:string; level?:number|null; code_base?:string|null } | null;
}

const props = defineProps<{ entities: Entity[]; projects: Project[]; parents: Parent[] }>()
const q = ref('')

// Formulaire compact (peu de champs pour éviter le scroll)
const form = useForm({
  project_id: '' as number|string,
  name: '',
  parent_id: null as null|number|string,
  leader: '',
  // options repliées
  email: '',
  phone: '',
  logo: '',
  address: '',
  description: '',
  code_base: '', // auto si vide
})

const showMore = ref(false)

// Options
const projectOptions = computed(() =>
  props.projects.map(p => ({ value: p.id, text: p.name }))
)

// Parents filtrés par projet
const parentOptions = computed(() => {
  if (!form.project_id) return props.parents.map(p => ({ value: p.id, text: p.name }))
  return props.parents
    .filter(p => String(p.project_id) === String(form.project_id))
    .map(p => ({ value: p.id, text: p.name }))
})

// Contexte / preview
const selectedProjectName = computed(() =>
  props.projects.find(p => String(p.id) === String(form.project_id))?.name ?? ''
)
const selectedParent = computed(() =>
  props.parents.find(p => String(p.id) === String(form.parent_id))
)
const selectedParentName = computed(() => selectedParent.value?.name ?? '')

// Niveau auto (affiché, sans champ dédié)
const autoLevel = computed(() => {
  if (!form.parent_id) return 0
  const par = selectedParent.value
  return Math.min(((par?.level ?? 0) + 1), 255)
})

// Prévision code_base (UI non bloquante)
const previewCodeBase = computed(() => {
  if (form.code_base?.trim()) return form.code_base.trim()
  const prefix = selectedParent.value?.code_base
    ? selectedParent.value.code_base
    : (() => {
        const raw = (form.name || '').normalize('NFD').replace(/\p{Diacritic}/gu,'').toUpperCase().replace(/[^A-Z0-9]/g,'')
        const base = raw.slice(0,3).padEnd(3,'X')
        return base || 'ENT'
      })()
  const siblingsCount = props.entities.filter(e => {
    const sameProject = String(e.project_id) === String(form.project_id)
    const sameParent  = form.parent_id ? String(e.parent_id) === String(form.parent_id) : (e.parent_id == null)
    return sameProject && sameParent
  }).length
  const seq = String(siblingsCount + 1).padStart(2, '0')
  return `${prefix}-${seq}`
})

// Filtre liste
const entities = computed(() => props.entities ?? [])
const filtered = computed(() => {
  if (!q.value.trim()) return entities.value
  const k = q.value.toLowerCase()
  return entities.value.filter(e =>
    [e.name, e.project?.name, e.parent?.name, e.code_base].filter(Boolean).some(v => String(v).toLowerCase().includes(k))
  )
})

const submit = () => {
  const payload = { ...form.data(), level: autoLevel.value || 0 }
  router.post(route('param.entities.store'), payload, {
    preserveScroll: true,
    onSuccess: () => { form.reset(); showMore.value = false },
  })
}

const destroyEntity = (id: number|string) => {
  if (confirm('Supprimer cette entité ?')) {
    router.delete(route('param.entities.destroy', id), {
      preserveScroll: true,
      onSuccess: () => router.reload({ only: ['entities'] }),
    })
  }
}
</script>

<style scoped>
/* Micro-optimisations pour réduire la hauteur et éviter tout scroll visuel */
.table td, .table th { vertical-align: middle; }
.form-label { font-size: .85rem; }
.badge { font-weight: 500; }
.btn-icon { padding: .25rem .45rem; line-height: 1; }
</style>
