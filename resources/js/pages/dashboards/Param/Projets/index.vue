<template>
  <VerticalLayout>
    <Head title="Projets — Paramétrage" />

    <!-- Titre + filtres -->
    <b-row>
      <b-col cols="12">
        <div class="page-title-head d-flex align-items-sm-center flex-sm-row flex-column">
          <div class="flex-grow-1">
            <h4 class="fs-18 fw-semibold m-0">Projets</h4>
            <p class="text-muted mb-0">Gérez les informations de vos projets</p>
          </div>

          <div class="mt-sm-0 mt-3">
            <b-form @submit.prevent>
              <b-row class="g-2 align-items-center mb-0">
                <div class="col-auto">
                  <a href="#" class="btn btn-light">
                    <i class="ti ti-sort-ascending me-1"></i> Sort By
                  </a>
                </div>

                <div class="col-sm-auto">
                  <b-input-group>
                    <FlatPicker
                      id="date"
                      v-model="date"
                      custom-class="border-0 shadow"
                      :options="{ dateFormat: 'd M', mode: 'range' }"
                    />
                    <b-input-group-text class="bg-primary border-primary text-white">
                      <i class="ti ti-calendar fs-15"></i>
                    </b-input-group-text>
                  </b-input-group>
                </div>
              </b-row>
            </b-form>
          </div>
        </div>
      </b-col>
    </b-row>

    <!-- Statistiques rapides -->
    <b-row class="mb-4">
      <b-col lg="3" md="6" class="mb-3">
        <b-card class="h-100 stats-card">
          <div class="py-3 text-center">
            <p class="text-muted mb-1">Total Projets</p>
            <h4 class="mb-0">{{ projects.length }}</h4>
          </div>
        </b-card>
      </b-col>
      <b-col lg="3" md="6" class="mb-3">
        <b-card class="h-100 stats-card">
          <div class="py-3 text-center">
            <p class="text-muted mb-1">Dernière maj</p>
            <h4 class="mb-0">{{ lastUpdated || '—' }}</h4>
          </div>
        </b-card>
      </b-col>
      <b-col lg="3" md="6" class="mb-3">
        <b-card class="h-100 stats-card">
          <div class="py-3 text-center">
            <p class="text-muted mb-1">Nouveaux (30j)</p>
            <h4 class="mb-0">{{ recentCount }}</h4>
          </div>
        </b-card>
      </b-col>
      <b-col lg="3" md="6" class="mb-3">
        <b-card class="h-100 stats-card">
          <div class="py-3 text-center">
            <p class="text-muted mb-1">Complétés</p>
            <h4 class="mb-0">—</h4>
          </div>
        </b-card>
      </b-col>
    </b-row>

    <!-- CONTENU PRINCIPAL - Formulaire et liste côte à côte -->
    <b-row>
      <!-- Formulaire création -->
      <b-col lg="5" xl="4" class="mb-4">
        <b-card no-body class="h-100 quick-form-card">
          <b-card-header class="d-flex justify-content-between align-items-center">
            <h4 class="header-title mb-0">
              <i class="ti ti-edit me-1 text-primary"></i> Nouveau projet
            </h4>
            <Link
              :href="route('param.projects.create')"
              class="btn btn-sm btn-primary">
              <i class="ti ti-plus me-1"></i> Formulaire complet
            </Link>
          </b-card-header>

          <b-card-body>
            <b-form @submit.prevent="submit">
              <b-row class="g-3">
                <b-col cols="12">
                  <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                  <b-form-input
                    id="code"
                    v-model="form.code"
                    placeholder="Ex: DABA, KKL, RAMCO"
                    :state="!form.errors.code && null"
                    required
                  />
                  <small v-if="form.errors.code" class="text-danger">{{ form.errors.code }}</small>
                </b-col>

                <b-col cols="12">
                  <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                  <b-form-input
                    id="name"
                    v-model="form.name"
                    placeholder="Ex: DABA FINANCE, KEKELI"
                    :state="!form.errors.name && null"
                    required
                  />
                  <small v-if="form.errors.name" class="text-danger">{{ form.errors.name }}</small>
                </b-col>

                <b-col cols="12">
                  <label for="character" class="form-label">Caractère</label>
                  <b-form-input
                    id="character"
                    v-model="form.character"
                    placeholder="Caractère unique"
                    :state="!form.errors.character && null"
                  />
                  <small v-if="form.errors.character" class="text-danger">{{ form.errors.character }}</small>
                </b-col>

                <b-col cols="12">
                  <label for="description" class="form-label">Description</label>
                  <b-form-textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    placeholder="Décrivez le projet..."
                    :state="!form.errors.description && null"
                  />
                  <small v-if="form.errors.description" class="text-danger">{{ form.errors.description }}</small>
                </b-col>

                <b-col cols="12" class="text-end">
                  <b-button variant="light" class="me-2" :disabled="form.processing" @click="cancel">Annuler</b-button>
                  <b-button variant="primary" type="submit" :disabled="form.processing">
                    <i class="ti ti-check me-1"></i> Valider
                  </b-button>
                </b-col>
              </b-row>
            </b-form>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Liste des projets -->
      <b-col lg="7" xl="8" class="mb-4">
        <b-card no-body class="h-100">
          <b-card-header class="d-flex justify-content-between align-items-center">
            <h4 class="header-title mb-0">
              <i class="ti ti-list-details me-1 text-primary"></i> Liste des projets
            </h4>
            <div class="d-flex gap-2">
              <Link
                :href="route('param.projects.create')"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus me-1"></i> Nouveau projet
              </Link>
            </div>
          </b-card-header>

          <b-card-body class="p-0">
            <div class="bg-light bg-opacity-50 py-1 text-center">
              <p class="m-0"><b>{{ projects.length }}</b> projet(s) enregistré(s)</p>
            </div>

            <div class="table-responsive">
              <b-table-simple hover class="table-custom table-centered table-sm table-nowrap mb-0">
                <b-thead class="bg-light">
                  <b-tr>
                    <b-th class="text-muted text-uppercase fs-12">Code</b-th>
                    <b-th class="text-muted text-uppercase fs-12">Nom</b-th>
                    <b-th class="text-muted text-uppercase fs-12">Caractère</b-th>
                    <b-th class="text-muted text-uppercase fs-12">Description</b-th>
                    <b-th class="text-muted text-uppercase fs-12 text-end">Actions</b-th>
                  </b-tr>
                </b-thead>
                <b-tbody>
                  <b-tr v-for="project in projects" :key="project.id" class="align-middle">
                    <b-td class="fw-semibold">{{ project.code ?? project.id }}</b-td>
                    <b-td>{{ project.name }}</b-td>
                    <b-td>{{ project.character || '—' }}</b-td>
                    <b-td class="text-truncate" style="max-width: 200px">{{ project.description || '—' }}</b-td>
                    <b-td class="text-end" style="width: 120px">
                      <Link
                        :href="route('param.projects.edit', project.id)"
                        class="btn btn-sm btn-light me-1">
                        <i class="ti ti-pencil"></i>
                      </Link>
                      <b-button
                        size="sm"
                        variant="danger"
                        @click="destroyProject(project.id)">
                        <i class="ti ti-trash"></i>
                      </b-button>
                    </b-td>
                  </b-tr>
                  <b-tr v-if="!projects || projects.length === 0">
                    <b-td colspan="5" class="text-center text-muted py-4">Aucun projet pour le moment.</b-td>
                  </b-tr>
                </b-tbody>
              </b-table-simple>
            </div>
          </b-card-body>

          <b-card-footer v-if="projects.length > 0">
            <div class="align-items-center justify-content-between row text-sm-start text-center">
              <div class="col-sm">
                <div class="text-muted">Affichage <span class="fw-semibold">{{ projects.length }}</span> résultat(s)</div>
              </div>
            </div>
          </b-card-footer>
        </b-card>
      </b-col>
    </b-row>

    <!-- Sidebar d'aide -->
    <b-row>
      <b-col cols="12">
        <b-alert v-model="showAlert" variant="primary" class="d-flex align-items-center">
          <i class="ti ti-help fs-18 me-2"></i>
          <div><b>Aide :</b> Gérez vos projets et organigrammes dans le module Paramétrage</div>
        </b-alert>

        <b-card no-body class="sidebar-card">
          <b-card-body class="text-white position-relative">
            <h1 class="mb-1"><i class="ti ti-folders"></i></h1>
            <h4 class="text-white">Vos Projets</h4>
            <p class="text-white text-opacity-75">Créez, éditez et organisez vos projets et entités</p>
            <Link :href="route('param.projects.create')" class="btn btn-sm rounded-pill btn-info">
              Nouveau projet
            </Link>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { defineAsyncComponent, computed, ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

// Helpers & layout
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

// Props du contrôleur
type Project = { 
  id: number|string; 
  code?: string; 
  name: string; 
  character?: string;
  description?: string; 
  updated_at?: string; 
  created_at?: string 
}

const props = defineProps<{ projects: Project[] }>()

// Données en-tête
const date = ref('01 May to 15 May')
const showAlert = ref(true)

// Stats dérivées
const projects = computed(() => props.projects ?? [])
const lastUpdated = computed(() => {
  const d = projects.value
    .map(p => p.updated_at || p.created_at)
    .filter(Boolean)
    .sort()
    .pop()
  return d ? new Date(d as string).toLocaleDateString() : ''
})
const recentCount = computed(() => {
  const now = Date.now()
  const THIRTY = 1000 * 60 * 60 * 24 * 30
  return projects.value.filter(p => {
    const d = new Date((p.updated_at || p.created_at || '') as string).getTime()
    return d && now - d <= THIRTY
  }).length
})

// Composants async
const FlatPicker = defineAsyncComponent({
  loader: () => import('@/components/FlatPicker.vue'),
  loadingComponent: { template: '<input class="form-control border-0 shadow" placeholder="Sélectionner une période" />' },
  errorComponent: { template: '<input class="form-control border-0 shadow" placeholder="Sélectionner une période" />' },
  delay: 50,
  timeout: 15000,
})

// Formulaire de création rapide
const form = useForm({
  code: '',
  name: '',
  character: '',
  description: '',
})

const submit = () => {
  form.post(route('param.projects.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  })
}

const cancel = () => form.reset()

// Suppression
const destroyProject = (id: number|string) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce projet ?')) {
    router.delete(route('param.projects.destroy', id), { 
      preserveScroll: true,
      onSuccess: () => {
        // Recharger les données après suppression
        router.reload({ only: ['projects'] })
      }
    })
  }
}
</script>

<style scoped>
.stats-card {
  transition: all 0.3s ease;
}
.stats-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
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
</style>