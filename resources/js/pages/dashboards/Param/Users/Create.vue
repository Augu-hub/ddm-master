<template>
  <VerticalLayout>
    <Head title="Ajouter un Utilisateur" />

    <!-- Header -->
    <div class="page-header-form">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <b-breadcrumb class="modern-breadcrumb">
            <b-breadcrumb-item @click="$inertia.visit(route('param.projects.users.index'))">
              <i class="ti ti-users me-1"></i>
              Utilisateurs
            </b-breadcrumb-item>
            <b-breadcrumb-item active>Nouvel utilisateur</b-breadcrumb-item>
          </b-breadcrumb>
          <h2 class="page-title mt-2">
            <i class="ti ti-user-plus me-2"></i>
            Ajouter un Utilisateur
          </h2>
        </div>
        <b-button 
          variant="outline-secondary" 
          @click="$inertia.visit(route('param.projects.users.index'))"
        >
          <i class="ti ti-arrow-left me-2"></i>
          Retour
        </b-button>
      </div>
    </div>

    <!-- Info Alert -->
    <b-alert show variant="info" class="modern-alert mb-4">
      <div class="d-flex align-items-start gap-3">
        <i class="ti ti-info-circle fs-4"></i>
        <div>
          <h5 class="alert-heading mb-2">🔐 Synchronisation Master → Tenant</h5>
          <p class="mb-2">
            L'utilisateur sera créé en tenant avec synchronisation automatique des <strong>fonctions</strong> 
            depuis la base Master.
          </p>
          <small class="text-muted">
            Un <strong>mot de passe sécurisé</strong> sera généré automatiquement
          </small>
        </div>
      </div>
    </b-alert>

    <!-- Alert erreurs globales -->
    <b-alert v-if="hasErrors" show variant="danger" class="mb-4">
      <div class="d-flex align-items-start gap-3">
        <i class="ti ti-alert-circle fs-4"></i>
        <div>
          <h5 class="alert-heading mb-2">❌ Erreurs de validation</h5>
          <ul class="mb-0">
            <li v-for="(messages, field) in errors" :key="field">
              <strong>{{ field }}:</strong> {{ Array.isArray(messages) ? messages.join(', ') : messages }}
            </li>
          </ul>
        </div>
      </div>
    </b-alert>

    <!-- Formulaire -->
    <b-form @submit.prevent="submitForm" class="form-container-modern">
      
      <!-- Section Informations personnelles -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="ti ti-user"></i>
          </div>
          <div>
            <h4 class="section-title">Informations personnelles</h4>
            <p class="section-description">Les informations de base de l'utilisateur</p>
          </div>
        </div>

        <b-row class="g-3">
          <b-col md="8">
            <label class="form-label-modern">
              Nom complet <span class="text-danger">*</span>
            </label>
            <b-form-input
              v-model="form.name"
              placeholder="Ex: Jean Dupont"
              class="input-modern"
              :class="{ 'is-invalid': errors.name }"
              required
            />
            <div class="invalid-feedback" v-if="errors.name">{{ errors.name }}</div>
          </b-col>

          <b-col md="4">
            <label class="form-label-modern">
              Matricule <small class="text-muted">(auto)</small>
            </label>
            <b-form-input
              v-model="form.matricule"
              placeholder="USR2024XXXX"
              class="input-modern"
              :class="{ 'is-invalid': errors.matricule }"
            />
            <div class="invalid-feedback" v-if="errors.matricule">{{ errors.matricule }}</div>
          </b-col>

          <b-col md="6">
            <label class="form-label-modern">
              Email <span class="text-danger">*</span>
            </label>
            <b-input-group class="input-group-modern">
              <template #prepend>
                <span class="input-group-text">
                  <i class="ti ti-mail"></i>
                </span>
              </template>
              <b-form-input
                v-model="form.email"
                type="email"
                placeholder="jean@exemple.com"
                class="input-modern"
                :class="{ 'is-invalid': errors.email }"
                required
              />
            </b-input-group>
            <div class="invalid-feedback" v-if="errors.email">{{ errors.email }}</div>
          </b-col>

          <b-col md="6">
            <label class="form-label-modern">Téléphone</label>
            <b-input-group class="input-group-modern">
              <template #prepend>
                <span class="input-group-text">
                  <i class="ti ti-phone"></i>
                </span>
              </template>
              <b-form-input
                v-model="form.phone"
                type="tel"
                placeholder="+229 XX XX XX XX"
                class="input-modern"
              />
            </b-input-group>
          </b-col>

          <b-col md="12">
            <label class="form-label-modern">Poste / Titre</label>
            <b-form-input
              v-model="form.job_title"
              placeholder="Ex: Responsable Qualité"
              class="input-modern"
            />
          </b-col>
        </b-row>
      </div>

      <!-- Section Affectation & Entité -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="ti ti-building"></i>
          </div>
          <div>
            <h4 class="section-title">Affectation à l'entité</h4>
            <p class="section-description">Entité de rattachement et fonctions associées</p>
          </div>
        </div>

        <b-row class="g-3">
          <!-- Sélection de l'entité -->
          <b-col md="6">
            <label class="form-label-modern">
              Entité <span class="text-danger">*</span>
            </label>
            <b-form-select
              v-model="form.entity_id"
              :options="entityOptions"
              class="input-modern"
              :class="{ 'is-invalid': errors.entity_id }"
              @change="onEntitySelected"
              required
            >
              <template #first>
                <b-form-select-option :value="null" disabled>
                  -- Sélectionner une entité --
                </b-form-select-option>
              </template>
            </b-form-select>
            <div class="invalid-feedback" v-if="errors.entity_id">{{ errors.entity_id }}</div>
          </b-col>

          <!-- Statut -->
          <b-col md="6">
            <label class="form-label-modern">
              Statut <span class="text-danger">*</span>
            </label>
            <b-form-select
              v-model="form.status"
              :options="statusOptions"
              class="input-modern"
              :class="{ 'is-invalid': errors.status }"
              required
            />
            <div class="invalid-feedback" v-if="errors.status">{{ errors.status }}</div>
          </b-col>
        </b-row>

        <!-- Fonctions disponibles pour l'entité sélectionnée -->
        <div v-if="form.entity_id && availableFunctions.length > 0" class="mt-4">
          <label class="form-label-modern">
            <i class="ti ti-tools me-2"></i>
            Fonctions à assigner
            <small class="text-muted">(depuis le Master)</small>
          </label>

          <div class="functions-grid">
            <div 
              v-for="fn in availableFunctions" 
              :key="fn.id" 
              class="function-card"
              :class="{ 'selected': isSelectedFunction(fn.id) }"
              @click="toggleFunction(fn)"
            >
              <div class="function-checkbox">
                <input 
                  type="checkbox" 
                  :checked="isSelectedFunction(fn.id)"
                  @change="toggleFunction(fn)"
                  class="form-check-input"
                />
              </div>
              <div class="function-content">
                <h6 class="function-name">{{ fn.name }}</h6>
                <!-- ✅ CORRIGÉ: Utiliser fn.character au lieu de fn.code -->
                <p class="function-code">{{ fn.character }}</p>
              </div>
            </div>
          </div>

          <!-- Statut chargement fonctions -->
          <small v-if="loadingFunctions" class="text-info d-block mt-2">
            <i class="ti ti-loading"></i> Chargement des fonctions...
          </small>
        </div>

        <div v-else-if="form.entity_id && !loadingFunctions && availableFunctions.length === 0" class="alert alert-warning mt-3">
          <i class="ti ti-alert-triangle me-2"></i>
          Aucune fonction disponible pour cette entité
        </div>

        <div v-else-if="!form.entity_id" class="alert alert-info mt-3">
          <i class="ti ti-info-circle me-2"></i>
          Sélectionnez une entité pour voir les fonctions disponibles
        </div>
      </div>

      <!-- Section Options -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="ti ti-toggle-right"></i>
          </div>
          <div>
            <h4 class="section-title">Options</h4>
            <p class="section-description">Configuration supplémentaire</p>
          </div>
        </div>

        <b-row class="g-3">
          <!-- Avatar -->
          <b-col md="12">
            <label class="form-label-modern">Photo de profil</label>
            <b-form-file
              v-model="form.avatar"
              accept="image/*"
              placeholder="Choisir une image..."
              class="input-modern"
            />
            <small class="form-hint">Format: JPG, PNG, GIF (Max: 2MB)</small>
          </b-col>

          <!-- Biographie -->
          <b-col md="12">
            <label class="form-label-modern">Biographie</label>
            <b-form-textarea
              v-model="form.bio"
              rows="3"
              placeholder="Quelques informations sur l'utilisateur..."
              class="input-modern"
            />
          </b-col>

          <!-- Avatar preview -->
          <b-col v-if="avatarPreview" md="12">
            <label class="form-label-modern">Prévisualisation</label>
            <div class="avatar-preview">
              <img :src="avatarPreview" alt="Prévisualisation" />
            </div>
          </b-col>

          <!-- Email option -->
          <b-col md="12" class="mt-3">
            <div class="email-option-card">
              <b-form-checkbox
                v-model="form.send_email"
                class="form-check-modern"
              >
                <div class="checkbox-content">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="ti ti-mail text-primary fs-5"></i>
                    <strong>Envoyer l'email de bienvenue</strong>
                  </div>
                  <small class="text-muted d-block">
                    L'utilisateur recevra ses identifiants par email
                  </small>
                </div>
              </b-form-checkbox>
            </div>
          </b-col>
        </b-row>
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <b-button 
          variant="light" 
          size="lg"
          @click="$inertia.visit(route('param.projects.users.index'))"
          :disabled="loading"
        >
          <i class="ti ti-x me-2"></i>
          Annuler
        </b-button>
        <b-button 
          type="submit" 
          variant="primary" 
          size="lg"
          :disabled="loading"
        >
          <b-spinner v-if="loading" small class="me-2"></b-spinner>
          <i v-else class="ti ti-check me-2"></i>
          {{ loading ? 'Création...' : 'Créer l\'utilisateur' }}
        </b-button>
      </div>
    </b-form>
  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import axios from 'axios'

const props = defineProps({
  entities: Array,
  errors: Object,
})

const form = ref({
  name: '',
  email: '',
  phone: '',
  matricule: '',
  status: 'active',
  job_title: '',
  bio: '',
  entity_id: null,
  avatar: null,
  send_email: true,
  function_assignments: [],
})

const loading = ref(false)
const avatarPreview = ref(null)
const errors = ref(props.errors || {})
const availableFunctions = ref([])
const loadingFunctions = ref(false)

const entityOptions = computed(() =>
  props.entities.map(e => ({ value: e.id, text: e.name }))
)

const statusOptions = [
  { value: 'active', text: '✅ Actif' },
  { value: 'inactive', text: '⏸️ Inactif' },
  { value: 'suspended', text: '🚫 Suspendu' },
]

const hasErrors = computed(() => Object.keys(errors.value).length > 0)

// ✅ Quand on change l'entité, charger les fonctions
const onEntitySelected = async () => {
  availableFunctions.value = []
  form.value.function_assignments = []

  if (!form.value.entity_id) return

  loadingFunctions.value = true

  try {
    // ✅ CORRIGÉ: Utiliser la bonne route nommée
    const response = await axios.get(route('param.projects.users.functions-for-entity'), {
      params: { entity_id: form.value.entity_id }
    })

    console.log('✅ Fonctions reçues:', response.data)

    if (response.data.success) {
      availableFunctions.value = response.data.functions
    }
  } catch (error) {
    console.error('❌ Erreur chargement fonctions:', error)
  } finally {
    loadingFunctions.value = false
  }
}

// ✅ Vérifier si une fonction est sélectionnée
const isSelectedFunction = (functionId) => {
  return form.value.function_assignments.some(f => f.function_id === functionId)
}

// ✅ Toggle fonction
const toggleFunction = (fn) => {
  const index = form.value.function_assignments.findIndex(
    f => f.function_id === fn.id
  )

  if (index > -1) {
    form.value.function_assignments.splice(index, 1)
  } else {
    form.value.function_assignments.push({
      function_id: fn.id,
      entity_id: form.value.entity_id,
      is_primary: form.value.function_assignments.length === 0, // 1ère fonction = primaire
      role_label: null
    })
  }

  console.log('✅ Fonctions sélectionnées:', form.value.function_assignments)
}

// ✅ Prévisualisation avatar
watch(() => form.value.avatar, (newAvatar) => {
  if (newAvatar) {
    const reader = new FileReader()
    reader.onload = (e) => {
      avatarPreview.value = e.target.result
    }
    reader.readAsDataURL(newAvatar)
  } else {
    avatarPreview.value = null
  }
})

// ✅ Submit formulaire - CORRIGÉ
function submitForm() {
  loading.value = true

  const formData = new FormData()

  Object.keys(form.value).forEach(key => {
    const value = form.value[key]

    if (key === 'function_assignments') {
      // ✅ CORRIGÉ: Envoyer function_assignments comme array FormData
      // function_assignments[0][function_id] = 1
      // function_assignments[0][entity_id] = 2
      // etc.
      value.forEach((fn, index) => {
        formData.append(`function_assignments[${index}][function_id]`, fn.function_id)
        formData.append(`function_assignments[${index}][entity_id]`, fn.entity_id || '')
        formData.append(`function_assignments[${index}][is_primary]`, fn.is_primary ? '1' : '0')
        formData.append(`function_assignments[${index}][role_label]`, fn.role_label || '')
      })
    } else if (key === 'send_email') {
      // ✅ CORRIGÉ: Envoyer send_email comme "0" ou "1"
      formData.append(key, value ? '1' : '0')
    } else if (key === 'avatar') {
      // ✅ CORRIGÉ: Avatar est un File, l'envoyer directement
      if (value) {
        formData.append(key, value)
      }
    } else if (value !== null && value !== '') {
      formData.append(key, value)
    }
  })

  console.log('📤 FormData envoyée:', {
    function_assignments: form.value.function_assignments,
    send_email: form.value.send_email
  })

  // ✅ CORRIGÉ: Utiliser la bonne route nommée
  router.post(route('param.projects.users.store'), formData, {
    onError: (err) => {
      errors.value = err
      loading.value = false
      console.error('❌ Erreur validation:', err)
    },
    onFinish: () => {
      loading.value = false
    },
  })
}
</script>

<style scoped>
.page-header-form {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 16px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  margin-bottom: 2rem;
}

.modern-breadcrumb {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  margin-bottom: 0;
}

.modern-breadcrumb :deep(.breadcrumb-item) {
  color: rgba(255, 255, 255, 0.8);
  cursor: pointer;
}

.modern-breadcrumb :deep(.breadcrumb-item.active) {
  color: white;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
}

.modern-alert {
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  border-left: 4px solid #2196f3;
}

.form-container-modern {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.form-section {
  padding: 2rem;
  border: 2px solid #f0f0f0;
  border-radius: 12px;
  margin-bottom: 2rem;
  transition: all 0.3s;
}

.form-section:hover {
  border-color: #e0e0e0;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
}

.section-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
  color: #212529;
}

.section-description {
  font-size: 0.875rem;
  color: #6c757d;
  margin: 0;
}

.form-label-modern {
  font-weight: 600;
  font-size: 0.9375rem;
  color: #495057;
  margin-bottom: 0.5rem;
  display: block;
}

.input-modern {
  border: 2px solid #e9ecef;
  border-radius: 10px;
  padding: 0.75rem 1rem;
  transition: all 0.2s;
  font-size: 0.9375rem;
}

.input-modern:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
  outline: none;
}

.input-modern.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
  margin-top: 0.375rem;
  font-size: 0.875rem;
  color: #dc3545;
}

/* FUNCTIONS GRID */
.functions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.function-card {
  border: 2px solid #e9ecef;
  border-radius: 12px;
  padding: 1.25rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.function-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.function-card.selected {
  background: linear-gradient(135deg, #f0f4ff 0%, #e8eeff 100%);
  border-color: #667eea;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
}

.function-checkbox {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.function-checkbox .form-check-input {
  cursor: pointer;
  width: 20px;
  height: 20px;
  border-radius: 4px;
}

.function-content {
  flex: 1;
}

.function-name {
  font-weight: 600;
  margin: 0 0 0.25rem 0;
  color: #212529;
  font-size: 0.95rem;
}

.function-code {
  margin: 0.25rem 0 0.5rem 0;
  color: #667eea;
  font-family: 'Courier New', monospace;
  font-size: 0.8rem;
  font-weight: 600;
}

.email-option-card {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border: 2px solid #dee2e6;
  border-radius: 12px;
  padding: 1.5rem;
}

.form-check-modern {
  display: flex;
  align-items: flex-start;
}

.checkbox-content {
  flex: 1;
  margin-left: 0.5rem;
}

.avatar-preview {
  width: 120px;
  height: 120px;
  border-radius: 12px;
  overflow: hidden;
  border: 3px solid #e9ecef;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.avatar-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 2rem;
  margin-top: 2rem;
  border-top: 2px solid #f0f0f0;
}

.form-actions button {
  border-radius: 10px;
  padding: 0.75rem 2rem;
  font-weight: 600;
  transition: all 0.3s;
}

.form-actions button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

@media (max-width: 767px) {
  .functions-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions button {
    width: 100%;
  }
}
</style>