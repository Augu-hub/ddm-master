<template>
  <VerticalLayout>
    <Head title="Modifier l'entité" />

    <!-- Fil d'ariane -->
    <b-row>
      <b-col cols="12">
        <div class="page-title-head d-flex align-items-sm-center flex-sm-row flex-column">
          <div class="flex-grow-1">
            <h4 class="fs-18 fw-semibold m-0">Modifier l'entité</h4>
            <nav aria-label="breadcrumb" class="mt-1">
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                  <Link :href="route('param.projects.entities.index')" class="text-muted">Entités</Link>
                </li>
                <li class="breadcrumb-item active">Modifier</li>
              </ol>
            </nav>
          </div>
          <div class="mt-sm-0 mt-3">
            <Link :href="route('param.projects.entities.index')" class="btn btn-light">
              <i class="ti ti-arrow-left me-1"></i> Retour
            </Link>
          </div>
        </div>
      </b-col>
    </b-row>

    <b-row>
      <b-col lg="8" xl="7">
        <b-card no-body>
          <b-card-header class="border-bottom border-dashed">
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-primary-subtle rounded-circle text-primary fs-20">
                  <i class="ti ti-building"></i>
                </span>
              </div>
              <div>
                <h4 class="header-title mb-0">{{ form.name || 'Entité' }}</h4>
                <small class="text-muted">
                  Niveau <b-badge variant="secondary" class="ms-1">{{ form.level ?? 0 }}</b-badge>
                  <span v-if="parentLabel" class="ms-2">— Parent : <span class="fw-semibold">{{ parentLabel }}</span></span>
                </small>
              </div>
            </div>
          </b-card-header>

          <b-card-body>
            <!-- Messages flash -->
            <b-alert v-if="$page.props.flash?.success" variant="success" dismissible show class="mb-3">
              <i class="ti ti-circle-check me-1"></i> {{ $page.props.flash.success }}
            </b-alert>

            <form @submit.prevent="submit">
              <b-row class="g-3">
                <!-- Nom -->
                <b-col md="6">
                  <label class="form-label">Nom <span class="text-danger">*</span></label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.name }"
                    placeholder="Nom de l'entité"
                  />
                  <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
                </b-col>

                <!-- Code base -->
                <b-col md="6">
                  <label class="form-label">Code base</label>
                  <input
                    v-model="form.code_base"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.code_base }"
                    placeholder="Ex: ENT-01 (auto si vide)"
                  />
                  <div v-if="errors.code_base" class="invalid-feedback">{{ errors.code_base }}</div>
                  <small class="text-muted">Laissez vide pour génération automatique</small>
                </b-col>

                <!-- Parent -->
                <b-col md="6">
                  <label class="form-label">Entité parente</label>
                  <select
                    v-model="form.parent_id"
                    class="form-select"
                    :class="{ 'is-invalid': errors.parent_id }"
                  >
                    <option :value="null">— Aucun (entité racine) —</option>
                    <option
                      v-for="p in parents"
                      :key="p.id"
                      :value="p.id"
                    >
                      {{ '— '.repeat(p.level ?? 0) }}{{ p.name }}
                      <template v-if="p.code_base"> ({{ p.code_base }})</template>
                    </option>
                  </select>
                  <div v-if="errors.parent_id" class="invalid-feedback">{{ errors.parent_id }}</div>
                </b-col>

                <!-- Responsable -->
                <b-col md="6">
                  <label class="form-label">Responsable</label>
                  <input
                    v-model="form.leader"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.leader }"
                    placeholder="Nom du responsable"
                  />
                  <div v-if="errors.leader" class="invalid-feedback">{{ errors.leader }}</div>
                </b-col>

                <!-- Email -->
                <b-col md="6">
                  <label class="form-label">Email</label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                    placeholder="contact@entite.com"
                  />
                  <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                </b-col>

                <!-- Téléphone -->
                <b-col md="6">
                  <label class="form-label">Téléphone</label>
                  <input
                    v-model="form.phone"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.phone }"
                    placeholder="+229 XX XX XX XX"
                  />
                  <div v-if="errors.phone" class="invalid-feedback">{{ errors.phone }}</div>
                </b-col>

                <!-- Logo -->
                <b-col md="6">
                  <label class="form-label">Logo (URL ou chemin)</label>
                  <input
                    v-model="form.logo"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.logo }"
                    placeholder="images/logo.png"
                  />
                  <div v-if="errors.logo" class="invalid-feedback">{{ errors.logo }}</div>
                </b-col>

                <!-- Adresse -->
                <b-col md="6">
                  <label class="form-label">Adresse</label>
                  <input
                    v-model="form.address"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.address }"
                    placeholder="Adresse physique"
                  />
                  <div v-if="errors.address" class="invalid-feedback">{{ errors.address }}</div>
                </b-col>

                <!-- Description -->
                <b-col cols="12">
                  <label class="form-label">Description</label>
                  <textarea
                    v-model="form.description"
                    class="form-control"
                    :class="{ 'is-invalid': errors.description }"
                    rows="3"
                    placeholder="Description de l'entité…"
                  ></textarea>
                  <div v-if="errors.description" class="invalid-feedback">{{ errors.description }}</div>
                </b-col>
              </b-row>

              <!-- Actions -->
              <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-dashed">
                <Link :href="route('param.projects.entities.index')" class="btn btn-light">
                  <i class="ti ti-x me-1"></i> Annuler
                </Link>
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                  <span v-if="form.processing">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Enregistrement…
                  </span>
                  <span v-else>
                    <i class="ti ti-device-floppy me-1"></i> Enregistrer
                  </span>
                </button>
              </div>
            </form>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Panneau info -->
      <b-col lg="4" xl="5">
        <!-- Aperçu -->
        <b-card no-body class="mb-3">
          <b-card-header class="border-bottom border-dashed">
            <h5 class="header-title mb-0"><i class="ti ti-eye me-1 text-primary"></i>Aperçu</h5>
          </b-card-header>
          <b-card-body>
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="avatar-lg flex-shrink-0">
                <template v-if="form.logo">
                  <img :src="form.logo" alt="logo" class="img-fluid rounded-circle" style="width:48px;height:48px;object-fit:cover;" />
                </template>
                <template v-else>
                  <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-24">
                    <i class="ti ti-building"></i>
                  </span>
                </template>
              </div>
              <div>
                <h5 class="mb-0">{{ form.name || '—' }}</h5>
                <span class="text-muted fs-12">{{ form.code_base || 'Code auto' }}</span>
              </div>
            </div>

            <ul class="list-unstyled mb-0 fs-13">
              <li class="d-flex justify-content-between py-1 border-bottom border-dashed">
                <span class="text-muted">Niveau</span>
                <b-badge variant="secondary">{{ computedLevel }}</b-badge>
              </li>
              <li class="d-flex justify-content-between py-1 border-bottom border-dashed">
                <span class="text-muted">Responsable</span>
                <span class="fw-semibold">{{ form.leader || '—' }}</span>
              </li>
              <li class="d-flex justify-content-between py-1 border-bottom border-dashed">
                <span class="text-muted">Email</span>
                <span class="fw-semibold">{{ form.email || '—' }}</span>
              </li>
              <li class="d-flex justify-content-between py-1 border-bottom border-dashed">
                <span class="text-muted">Téléphone</span>
                <span class="fw-semibold">{{ form.phone || '—' }}</span>
              </li>
              <li class="d-flex justify-content-between py-1">
                <span class="text-muted">Adresse</span>
                <span class="fw-semibold">{{ form.address || '—' }}</span>
              </li>
            </ul>
          </b-card-body>
        </b-card>

        <!-- Danger zone -->
        <b-card no-body class="border-danger">
          <b-card-header class="border-bottom border-danger bg-danger-subtle">
            <h5 class="header-title mb-0 text-danger"><i class="ti ti-alert-triangle me-1"></i>Zone dangereuse</h5>
          </b-card-header>
          <b-card-body>
            <p class="text-muted fs-13 mb-3">
              La suppression est définitive. Les sous-entités associées peuvent être affectées.
            </p>
            <button type="button" class="btn btn-danger btn-sm w-100" @click="confirmDelete">
              <i class="ti ti-trash me-1"></i> Supprimer cette entité
            </button>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- Modal suppression -->
    <b-modal
      v-model="showDeleteModal"
      title="Confirmer la suppression"
      header-class="border-bottom border-dashed"
      footer-class="border-top border-dashed"
      centered
    >
      <p>Êtes-vous sûr de vouloir supprimer <strong>{{ entite.name }}</strong> ?</p>
      <p class="text-danger mb-0"><i class="ti ti-alert-triangle me-1"></i>Cette action est irréversible.</p>

      <template #footer>
        <button type="button" class="btn btn-light" @click="showDeleteModal = false">Annuler</button>
        <button type="button" class="btn btn-danger" @click="doDelete" :disabled="deleting">
          <span v-if="deleting"><span class="spinner-border spinner-border-sm me-1"></span>Suppression…</span>
          <span v-else><i class="ti ti-trash me-1"></i>Confirmer</span>
        </button>
      </template>
    </b-modal>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

// ─── Props ─────────────────────────────────────────────────────────────────
interface EntiteData {
  id: number
  name: string
  description?: string | null
  level?: number
  parent_id?: number | null
  code_base?: string | null
  logo?: string | null
  phone?: string | null
  email?: string | null
  leader?: string | null
  address?: string | null
}

interface ParentOption {
  id: number
  name: string
  level?: number
  code_base?: string | null
}

const props = defineProps<{
  entite: EntiteData
  parents: ParentOption[]
}>()

// ─── Formulaire Inertia ─────────────────────────────────────────────────────
const form = useForm({
  name:        props.entite.name        ?? '',
  description: props.entite.description ?? '',
  parent_id:   props.entite.parent_id   ?? null,
  code_base:   props.entite.code_base   ?? '',
  logo:        props.entite.logo        ?? '',
  phone:       props.entite.phone       ?? '',
  email:       props.entite.email       ?? '',
  leader:      props.entite.leader      ?? '',
  address:     props.entite.address     ?? '',
  level:       props.entite.level       ?? 0,
  _method:     'PUT',
})

const errors = computed(() => form.errors)

// Niveau calculé selon parent sélectionné
const computedLevel = computed(() => {
  if (!form.parent_id) return 0
  const parent = props.parents.find(p => p.id === form.parent_id)
  return parent ? Math.min((parent.level ?? 0) + 1, 255) : 0
})

const parentLabel = computed(() => {
  if (!form.parent_id) return null
  return props.parents.find(p => p.id === form.parent_id)?.name ?? null
})

function submit() {
  form.level = computedLevel.value
  form.post(route('param.projects.entities.update', props.entite.id))
}

// ─── Suppression ────────────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const deleting = ref(false)

function confirmDelete() {
  showDeleteModal.value = true
}

function doDelete() {
  deleting.value = true
  router.delete(route('param.projects.entities.destroy', props.entite.id), {
    onFinish: () => {
      deleting.value = false
      showDeleteModal.value = false
    },
  })
}
</script>