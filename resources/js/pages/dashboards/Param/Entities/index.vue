<template>
  <VerticalLayout>
    <Head title="Entités — Paramétrage" />

    <!-- Titre + actions -->
    <b-row>
      <b-col cols="12">
        <div class="page-title-head d-flex align-items-sm-center flex-sm-row flex-column">
          <div class="flex-grow-1">
            <h4 class="fs-18 fw-semibold m-0">Entités</h4>
            <p class="text-muted mb-0">Gérez les entités de vos projets</p>
          </div>

          <div class="mt-sm-0 mt-3">
            <Link 
              :href="r('param.entities.index','/param/entities')" 
              class="btn btn-light"
            >
              <i class="ti ti-arrow-left me-1"></i> Retour à la liste
            </Link>
          </div>
        </div>
      </b-col>
    </b-row>

    <!-- CONTENU PRINCIPAL - Formulaire et liste côte à côte -->
    <b-row class="mt-4">
      <!-- Formulaire création -->
      <b-col lg="5" xl="4" class="mb-4">
        <b-card no-body class="h-100 quick-form-card">
          <b-card-header class="d-flex justify-content-between align-items-center">
            <h4 class="header-title mb-0">
              <i class="ti ti-edit me-1 text-primary"></i> Création d'entité
            </h4>
          </b-card-header>

          <b-card-body>
            <b-form @submit.prevent="submit">
              <b-row class="g-3">
                <!-- Choix du projet (bande jaune) -->
                <b-col cols="12">
                  <label class="form-label">Choix du projet <span class="text-danger">*</span></label>
                  <b-form-select
                    v-model="form.project_id"
                    :options="projectOptions"
                    class="bg-amber-200/70 border-amber-300 focus:ring-amber-400"
                  ></b-form-select>
                </b-col>

                <!-- Entité parente -->
                <b-col cols="12">
                  <label class="form-label">Entité parente</label>
                  <b-form-select
                    v-model="form.parent_id"
                    :options="parentOptions"
                  ></b-form-select>
                </b-col>

                <!-- Code (ID entité) -->
                <b-col cols="12">
                  <label class="form-label">ID entité (Code) <span class="text-danger">*</span></label>
                  <b-form-input
                    v-model.trim="form.code"
                    placeholder="Ex. DABA1"
                    :state="!form.errors.code && null"
                  />
                  <small v-if="form.errors.code" class="text-danger">{{ form.errors.code }}</small>
                </b-col>

                <!-- Raison sociale -->
                <b-col cols="12">
                  <label class="form-label">Raison sociale <span class="text-danger">*</span></label>
                  <b-form-input
                    v-model.trim="form.name"
                    placeholder="Nom de l'entité"
                    :state="!form.errors.name && null"
                  />
                  <small v-if="form.errors.name" class="text-danger">{{ form.errors.name }}</small>
                </b-col>

                <!-- Dirigeant et Téléphone -->
                <b-col md="6">
                  <label class="form-label">Dirigeant</label>
                  <b-form-input
                    v-model.trim="form.manager"
                    placeholder="Responsable"
                  />
                </b-col>

                <b-col md="6">
                  <label class="form-label">Téléphone</label>
                  <b-form-input
                    v-model.trim="form.phone"
                    placeholder="Ex. 0022890312588"
                  />
                </b-col>

                <!-- Actions -->
                <b-col cols="12" class="text-end pt-2">
                  <b-button variant="light" class="me-2" @click="form.reset()">Réinitialiser</b-button>
                  <b-button variant="primary" type="submit" :disabled="form.processing">
                    <i class="ti ti-check me-1"></i>
                    <span v-if="form.processing">Enregistrement…</span>
                    <span v-else>Créer l'entité</span>
                  </b-button>
                </b-col>
              </b-row>
            </b-form>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Liste des entités -->
      <b-col lg="7" xl="8" class="mb-4">
        <b-card no-body class="h-100">
          <b-card-header class="d-flex justify-content-between align-items-center">
            <h4 class="header-title mb-0">
              <i class="ti ti-list-details me-1 text-primary"></i> Liste des entités
            </h4>
            <div class="text-muted">
              {{ entities.length }} entité(s)
            </div>
          </b-card-header>

          <b-card-body class="p-0">
            <div class="table-responsive">
              <b-table-simple hover class="table-custom table-centered table-sm table-nowrap mb-0">
                <b-thead class="bg-light">
                  <b-tr>
                    <b-th class="text-muted text-uppercase fs-12">ID entité</b-th>
                    <b-th class="text-muted text-uppercase fs-12">Raison sociale</b-th>
                    <b-th class="text-muted text-uppercase fs-12">Dirigeant</b-th>
                    <b-th class="text-muted text-uppercase fs-12">Téléphone</b-th>
                    <b-th class="text-muted text-uppercase fs-12">Projet</b-th>
                    <b-th class="text-muted text-uppercase fs-12 text-end">Actions</b-th>
                  </b-tr>
                </b-thead>
                <b-tbody>
                  <b-tr v-for="e in entities" :key="e.id" class="align-middle">
                    <b-td class="fw-semibold font-monospace">{{ e.code }}</b-td>
                    <b-td class="fw-semibold">{{ e.name }}</b-td>
                    <b-td>{{ e.manager || '—' }}</b-td>
                    <b-td>{{ e.phone || '—' }}</b-td>
                    <b-td>{{ e.project?.name || '—' }}</b-td>
                    <b-td class="text-end">
                      <Link
                        :href="r('param.entities.edit', `/param/entities/${e.id}/edit`)"
                        class="btn btn-sm btn-light me-1"
                      >
                        <i class="ti ti-pencil"></i>
                      </Link>
                      <b-button
                        size="sm"
                        variant="danger"
                        @click="deleteEntity(e.id)"
                      >
                        <i class="ti ti-trash"></i>
                      </b-button>
                    </b-td>
                  </b-tr>
                  <b-tr v-if="!entities || entities.length === 0">
                    <b-td colspan="6" class="text-center text-muted py-4">Aucune entité pour le moment.</b-td>
                  </b-tr>
                </b-tbody>
              </b-table-simple>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- Sidebar d'aide -->
    <b-row class="mt-4">
      <b-col cols="12">
        <b-alert variant="primary" class="d-flex align-items-center" show>
          <i class="ti ti-help fs-18 me-2"></i>
          <div><b>Aide :</b> Les entités représentent les différentes structures organisationnelles de vos projets.</div>
        </b-alert>

        <b-card no-body class="sidebar-card">
          <b-card-body class="text-white position-relative">
            <h1 class="mb-1"><i class="ti ti-building"></i></h1>
            <h4 class="text-white">Vos Entités</h4>
            <p class="text-white text-opacity-75">Créez et organisez la structure de vos projets</p>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { computed, toRefs } from 'vue'

// Layout (adapté à votre structure)
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  entities: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
  parents:  { type: Array, default: () => [] },
})
const { entities, projects, parents } = toRefs(props)

// Fonction de routage
const r = (name, fallback = '/') =>
  (typeof window.route === 'function' ? window.route(name) : fallback)

// Formulaire
const form = useForm({
  project_id: null, 
  parent_id: null, 
  code: '', 
  name: '', 
  manager: '', 
  phone: '',
})

// Options pour les selects
const projectOptions = computed(() => [
  { value: null, text: '— Sélectionner —', disabled: true },
  ...(projects.value || []).map(p => ({ value: p.id, text: p.name }))
])

const parentOptions = computed(() => {
  const options = [{ value: null, text: '— Aucune —' }]
  
  if (!form.project_id) {
    return options
  }
  
  const filteredParents = (parents.value || []).filter(p => p.project_id === form.project_id)
  return [
    ...options,
    ...filteredParents.map(p => ({ 
      value: p.id, 
      text: `${p.code} — ${p.name}` 
    }))
  ]
})

// Soumission du formulaire
const submit = () =>
  form.post(r('param.entities.store','/param/entities'), { 
    preserveScroll: true,
    onSuccess: () => form.reset()
  })

// Suppression d'une entité
const deleteEntity = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette entité ?')) {
    router.delete(r('param.entities.destroy', `/param/entities/${id}`), { 
      preserveScroll: true 
    })
  }
}
</script>

<style scoped>
.quick-form-card {
  border: 1px solid #e9ecef;
}
.sidebar-card {
  background: linear-gradient(135deg, #405189 0%, #5a71b9 100%);
  overflow: hidden;
  position: relative;
}
.sidebar-card::before {
  content: "";
  position: absolute;
  bottom: 0;
  right: 0;
  width: 100px;
  height: 100px;
  background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="rgba(255,255,255,0.1)" d="M20,20 C40,0 60,0 80,20 C100,40 100,60 80,80 C60,100 40,100 20,80 C0,60 0,40 20,20 Z"></path></svg>') no-repeat;
  background-size: contain;
}
.bg-amber-200\/70 {
  background-color: rgba(253, 230, 138, 0.7);
}
.border-amber-300 {
  border-color: rgb(252, 211, 77);
}
.focus\:ring-amber-400:focus {
  --tw-ring-color: rgba(251, 191, 36, 0.5);
  box-shadow: var(--tw-ring-inset) 0 0 0 calc(3px + var(--tw-ring-offset-width)) var(--tw-ring-color);
}
.font-monospace {
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
}
</style>