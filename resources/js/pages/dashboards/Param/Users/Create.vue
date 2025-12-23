<template>
  <VerticalLayout>
    <Head title="Ajouter un Utilisateur" />

    <!-- Header avec breadcrumb -->
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

    <!-- Message d'info sur le mot de passe -->
    <b-alert show variant="info" class="modern-alert mb-4">
      <div class="d-flex align-items-start gap-3">
        <i class="ti ti-info-circle fs-4"></i>
        <div>
          <h5 class="alert-heading mb-2">📧 Envoi automatique des identifiants</h5>
          <p class="mb-2">
            Un <strong>mot de passe sécurisé</strong> sera généré automatiquement et envoyé par email 
            à l'utilisateur avec ses identifiants de connexion.
          </p>
          <small class="text-muted">
            Le mot de passe contient : majuscules, minuscules, chiffres et symboles (12 caractères minimum)
          </small>
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
              Matricule <small class="text-muted">(auto-généré si vide)</small>
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
                placeholder="jean.dupont@exemple.com"
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
                :class="{ 'is-invalid': errors.phone }"
              />
            </b-input-group>
            <div class="invalid-feedback" v-if="errors.phone">{{ errors.phone }}</div>
          </b-col>

          <b-col md="12">
            <label class="form-label-modern">Poste / Titre</label>
            <b-form-input
              v-model="form.job_title"
              placeholder="Ex: Responsable Qualité, Directeur des Opérations..."
              class="input-modern"
              :class="{ 'is-invalid': errors.job_title }"
            />
            <div class="invalid-feedback" v-if="errors.job_title">{{ errors.job_title }}</div>
          </b-col>

          <b-col md="12">
            <label class="form-label-modern">Biographie</label>
            <b-form-textarea
              v-model="form.bio"
              rows="3"
              placeholder="Quelques mots sur l'utilisateur..."
              class="input-modern"
              :class="{ 'is-invalid': errors.bio }"
            />
            <div class="invalid-feedback" v-if="errors.bio">{{ errors.bio }}</div>
          </b-col>
        </b-row>
      </div>

      <!-- Section Affectation -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="ti ti-building"></i>
          </div>
          <div>
            <h4 class="section-title">Affectation</h4>
            <p class="section-description">Entité de rattachement de l'utilisateur</p>
          </div>
        </div>

        <b-row class="g-3">
          <b-col md="12">
            <label class="form-label-modern">Entité</label>
            <b-form-select
              v-model="form.entity_id"
              :options="entityOptions"
              class="input-modern"
              :class="{ 'is-invalid': errors.entity_id }"
            >
              <template #first>
                <b-form-select-option :value="null" disabled>
                  -- Sélectionner une entité --
                </b-form-select-option>
              </template>
            </b-form-select>
            <small class="form-hint">
              <i class="ti ti-info-circle me-1"></i>
              L'entité peut être modifiée ultérieurement
            </small>
            <div class="invalid-feedback" v-if="errors.entity_id">{{ errors.entity_id }}</div>
          </b-col>
        </b-row>
      </div>

      <!-- Section Statut et Options -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="ti ti-toggle-right"></i>
          </div>
          <div>
            <h4 class="section-title">Statut et Options</h4>
            <p class="section-description">Configuration du compte utilisateur</p>
          </div>
        </div>

        <b-row class="g-3">
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

          <b-col md="6">
            <label class="form-label-modern">Photo de profil</label>
            <b-form-file
              v-model="form.avatar"
              accept="image/*"
              placeholder="Choisir une image..."
              class="input-modern"
              :class="{ 'is-invalid': errors.avatar }"
            />
            <small class="form-hint">Format: JPG, PNG, GIF (Max: 2MB)</small>
            <div class="invalid-feedback" v-if="errors.avatar">{{ errors.avatar }}</div>
          </b-col>
        </b-row>

        <!-- Prévisualisation de l'avatar -->
        <div v-if="avatarPreview" class="mt-3">
          <label class="form-label-modern">Prévisualisation</label>
          <div class="avatar-preview">
            <img :src="avatarPreview" alt="Prévisualisation" />
          </div>
        </div>

        <!-- Option d'envoi d'email -->
        <div class="mt-4">
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
                  L'utilisateur recevra un email avec ses identifiants de connexion 
                  (email + mot de passe généré automatiquement)
                </small>
              </div>
            </b-form-checkbox>
          </div>
        </div>
      </div>

      <!-- Actions du formulaire -->
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
          {{ loading ? 'Création en cours...' : 'Créer l\'utilisateur' }}
        </b-button>
      </div>
    </b-form>
  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

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
})

const loading = ref(false)
const avatarPreview = ref(null)
const errors = ref(props.errors || {})

const entityOptions = props.entities.map(e => ({ value: e.id, text: e.name }))

const statusOptions = [
  { value: 'active', text: '✅ Actif' },
  { value: 'inactive', text: '⏸️ Inactif' },
  { value: 'suspended', text: '🚫 Suspendu' },
]

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

function submitForm() {
  loading.value = true
  
  const formData = new FormData()
  
  Object.keys(form.value).forEach(key => {
    if (form.value[key] !== null && form.value[key] !== '') {
      formData.append(key, form.value[key])
    }
  })

  router.post(route('param.projects.users.store'), formData, {
    onSuccess: () => {
      // Redirection gérée par le contrôleur
    },
    onError: (err) => {
      errors.value = err
      loading.value = false
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

.modern-breadcrumb :deep(.breadcrumb-item + .breadcrumb-item::before) {
  color: rgba(255, 255, 255, 0.5);
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

.modern-alert .alert-heading {
  color: #0d47a1;
  font-weight: 700;
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

.input-group-modern .input-group-text {
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  border-right: none;
  border-radius: 10px 0 0 10px;
  color: #6c757d;
}

.input-group-modern .input-modern {
  border-left: none;
  border-radius: 0 10px 10px 0;
}

.input-group-modern .input-modern:focus {
  border-left: 2px solid #667eea;
}

.form-hint {
  display: block;
  margin-top: 0.375rem;
  font-size: 0.8125rem;
  color: #6c757d;
}

.invalid-feedback {
  display: block;
  margin-top: 0.375rem;
  font-size: 0.875rem;
  color: #dc3545;
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
  .form-container-modern {
    padding: 1rem;
  }

  .form-section {
    padding: 1rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions button {
    width: 100%;
  }
}
</style>