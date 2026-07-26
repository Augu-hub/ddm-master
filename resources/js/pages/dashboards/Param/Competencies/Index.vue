<!-- resources/views/dashboards/Param/Auditors/Edit.vue -->
<!-- Cette vue est presque identique à Create.vue -->
<!-- Les différences principales: -->
<!-- 1. Le titre change "Créer" → "Modifier" -->
<!-- 2. Les données sont pré-remplies -->
<!-- 3. La route POST devient PUT -->
<!-- 4. Code minimum modifié pour les compétences existantes -->

<template>
  <VerticalLayout>
    <Head :title="`Modifier l'Auditeur - ${auditor.full_name}`" />

    <div class="edit-container">
      <!-- Bouton retour -->
      <Link 
        :href="route('param.projects.auditors.show', auditor.id)"
        class="btn-back"
      >
        <i class="ti ti-arrow-left me-2"></i>
        Retour aux détails
      </Link>

      <!-- Header -->
      <div class="page-header-modern mb-4">
        <div class="d-flex align-items-center gap-3">
          <div class="header-icon-wrapper">
            <i class="ti ti-pencil fs-2"></i>
          </div>
          <div>
            <h2 class="header-title mb-1">Modifier l'Auditeur</h2>
            <p class="header-subtitle mb-0">{{ auditor.full_name }}</p>
          </div>
        </div>
      </div>

      <!-- Afficher les erreurs globales -->
      <b-alert 
        v-if="errors.error" 
        variant="danger" 
        dismissible
        class="mb-4"
      >
        <i class="ti ti-alert-circle me-2"></i>
        {{ errors.error }}
      </b-alert>

      <!-- Formulaire -->
      <form @submit.prevent="submitForm" class="auditor-form">
        <!-- Informations Identité -->
        <div class="form-section">
          <div class="section-header">
            <i class="ti ti-user-circle"></i>
            <h4>Informations d'Identité</h4>
          </div>

          <b-row class="g-3">
            <b-col md="6">
              <b-form-group 
                label="Prénom *"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model="form.first_name"
                  :state="getFieldState('first_name')"
                  placeholder="Jean"
                  class="form-input-modern"
                />
                <b-form-invalid-feedback v-if="errors.first_name">
                  {{ errors.first_name }}
                </b-form-invalid-feedback>
              </b-form-group>
            </b-col>

            <b-col md="6">
              <b-form-group 
                label="Nom de famille *"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model="form.last_name"
                  :state="getFieldState('last_name')"
                  placeholder="Dupont"
                  class="form-input-modern"
                />
                <b-form-invalid-feedback v-if="errors.last_name">
                  {{ errors.last_name }}
                </b-form-invalid-feedback>
              </b-form-group>
            </b-col>

            <b-col md="6">
              <b-form-group 
                label="Email *"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model="form.email"
                  type="email"
                  :state="getFieldState('email')"
                  placeholder="jean.dupont@example.com"
                  class="form-input-modern"
                />
                <b-form-invalid-feedback v-if="errors.email">
                  {{ errors.email }}
                </b-form-invalid-feedback>
              </b-form-group>
            </b-col>

            <b-col md="6">
              <b-form-group 
                label="Téléphone"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model="form.phone"
                  placeholder="+225 01 01 01 01 01"
                  class="form-input-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="6">
              <b-form-group 
                label="Date de naissance"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model="form.date_of_birth"
                  type="date"
                  class="form-input-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="6">
              <b-form-group 
                label="Genre"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-select
                  v-model="form.gender"
                  :options="[
                    { value: null, text: '- Sélectionner -' },
                    { value: 'M', text: 'Masculin' },
                    { value: 'F', text: 'Féminin' },
                  ]"
                  class="form-select-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="12">
              <b-form-group 
                label="Lieu de naissance"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model="form.birthplace"
                  placeholder="Abidjan"
                  class="form-input-modern"
                />
              </b-form-group>
            </b-col>
          </b-row>
        </div>

        <!-- Adresse -->
        <div class="form-section">
          <div class="section-header">
            <i class="ti ti-map-pin"></i>
            <h4>Adresse</h4>
          </div>

          <b-row class="g-3">
            <b-col md="12">
              <b-form-group 
                label="Adresse complète"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-textarea
                  v-model="form.address"
                  rows="2"
                  placeholder="Adresse complète"
                  class="form-input-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="4">
              <b-form-group 
                label="Ville"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model="form.city"
                  placeholder="Abidjan"
                  class="form-input-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="4">
              <b-form-group 
                label="Code postal"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model="form.postal_code"
                  placeholder="01"
                  class="form-input-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="4">
              <b-form-group 
                label="Pays"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model="form.country"
                  placeholder="Côte d'Ivoire"
                  class="form-input-modern"
                />
              </b-form-group>
            </b-col>
          </b-row>
        </div>

        <!-- Informations Professionnelles -->
        <div class="form-section">
          <div class="section-header">
            <i class="ti ti-briefcase"></i>
            <h4>Informations Professionnelles</h4>
          </div>

          <b-row class="g-3">
            <b-col md="6">
              <b-form-group 
                label="Expérience Audit (années)"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model.number="form.audit_experience"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="form-input-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="6">
              <b-form-group 
                label="Autre expérience (années)"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-input
                  v-model.number="form.other_experience"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="form-input-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="12">
              <b-form-group 
                label="Biographie"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-textarea
                  v-model="form.bio"
                  rows="3"
                  placeholder="Informations complémentaires..."
                  class="form-input-modern"
                  maxlength="1000"
                />
                <small class="text-muted d-block mt-2">{{ form.bio.length }}/1000 caractères</small>
              </b-form-group>
            </b-col>

            <b-col md="6">
              <b-form-group 
                label="Entité"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-select
                  v-model="form.entity_id"
                  :options="[
                    { value: null, text: '- Non assignée -' },
                    ...entities.map(e => ({ value: e.id, text: e.name }))
                  ]"
                  class="form-select-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="6">
              <b-form-group 
                label="Statut *"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <b-form-select
                  v-model="form.status"
                  :options="[
                    { value: 'active', text: 'Actif' },
                    { value: 'inactive', text: 'Inactif' },
                    { value: 'suspended', text: 'Suspendu' },
                  ]"
                  :state="getFieldState('status')"
                  class="form-select-modern"
                />
              </b-form-group>
            </b-col>

            <b-col md="12">
              <b-form-group 
                label="Avatar"
                label-class="form-label-modern"
                class="form-group-modern"
              >
                <div class="avatar-upload-area">
                  <input 
                    type="file" 
                    ref="fileInput" 
                    @change="handleAvatarChange"
                    accept="image/*"
                    class="d-none"
                  />
                  <div @click="$refs.fileInput.click()" class="avatar-upload-placeholder">
                    <i class="ti ti-cloud-upload"></i>
                    <p>Cliquez pour changer l'avatar (JPG, PNG, GIF - Max 2MB)</p>
                  </div>
                  <div class="current-avatar">
                    <img :src="avatarPreview || auditor.avatar_url" :alt="auditor.full_name" />
                  </div>
                </div>
              </b-form-group>
            </b-col>
          </b-row>
        </div>

        <!-- Boutons -->
        <div class="form-actions">
          <b-button 
            variant="secondary" 
            @click="$router.back()"
            class="btn-secondary-modern"
          >
            <i class="ti ti-x me-2"></i>
            Annuler
          </b-button>
          <b-button 
            variant="primary" 
            type="submit"
            :disabled="isSubmitting"
            class="btn-primary-modern"
          >
            <b-spinner small v-if="isSubmitting" class="me-2"></b-spinner>
            <i v-else class="ti ti-check me-2"></i>
            {{ isSubmitting ? 'Mise à jour...' : 'Mettre à jour' }}
          </b-button>
        </div>
      </form>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  auditor: Object,
  entities: Array,
  categories: Array,
  competenciesByCategory: Array,
})

const form = ref({
  first_name: props.auditor.first_name || '',
  last_name: props.auditor.last_name || '',
  email: props.auditor.email || '',
  phone: props.auditor.phone || '',
  date_of_birth: props.auditor.date_of_birth || '',
  birthplace: props.auditor.birthplace || '',
  address: props.auditor.address || '',
  city: props.auditor.city || '',
  postal_code: props.auditor.postal_code || '',
  country: props.auditor.country || '',
  audit_experience: props.auditor.audit_experience || 0,
  other_experience: props.auditor.other_experience || 0,
  gender: props.auditor.gender || null,
  status: props.auditor.status || 'active',
  bio: props.auditor.bio || '',
  entity_id: props.auditor.entity_id || null,
})

const errors = ref({})
const isSubmitting = ref(false)
const fileInput = ref(null)
const avatarFile = ref(null)
const avatarPreview = ref('')

function getFieldState(field) {
  if (errors.value[field]) return false
  return null
}

function handleAvatarChange(event) {
  const file = event.target.files[0]
  if (file) {
    avatarFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      avatarPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function submitForm() {
  isSubmitting.value = true
  errors.value = {}

  const formData = new FormData()
  
  // Ajouter tous les champs
  Object.keys(form.value).forEach(key => {
    formData.append(key, form.value[key] ?? '')
  })

  // Ajouter l'avatar s'il existe
  if (avatarFile.value) {
    formData.append('avatar', avatarFile.value)
  }

  // Ajouter la méthode PUT
  formData.append('_method', 'PUT')

  router.post(route('param.projects.auditors.update', props.auditor.id), formData, {
    onError: (errs) => {
      errors.value = errs
      isSubmitting.value = false
    },
    onFinish: () => {
      isSubmitting.value = false
    }
  })
}
</script>

<style scoped>
/* Les styles sont identiques à Create.vue - voir Create.vue pour tous les styles */
.edit-container {
  padding: 0;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  color: #667eea;
  text-decoration: none;
  margin-bottom: 1.5rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-back:hover {
  color: #764ba2;
}

.page-header-modern {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 16px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.header-icon-wrapper {
  width: 64px;
  height: 64px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
}

.header-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
}

.header-subtitle {
  font-size: 0.95rem;
  opacity: 0.9;
}

.auditor-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.form-section {
  background: white;
  padding: 1.5rem;
  border-radius: 14px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
}

.section-header i {
  font-size: 1.5rem;
  color: #667eea;
}

.section-header h4 {
  margin: 0;
  color: #212529;
  font-weight: 600;
}

.form-label-modern {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
}

.form-input-modern,
.form-select-modern {
  border: 2px solid #e9ecef;
  border-radius: 10px;
  padding: 0.75rem;
  transition: all 0.2s;
}

.form-input-modern:focus,
.form-select-modern:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.avatar-upload-area {
  margin-top: 0.5rem;
}

.avatar-upload-placeholder {
  border: 2px dashed #e9ecef;
  border-radius: 10px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.avatar-upload-placeholder:hover {
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.avatar-upload-placeholder i {
  font-size: 2rem;
  color: #667eea;
  margin-bottom: 0.5rem;
}

.current-avatar {
  margin-top: 1rem;
  text-align: center;
}

.current-avatar img {
  max-width: 100%;
  max-height: 200px;
  border-radius: 10px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 2px solid #f0f0f0;
}

.btn-primary-modern,
.btn-secondary-modern {
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-primary-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.btn-secondary-modern:hover {
  background-color: #e9ecef;
}

@media (max-width: 767px) {
  .page-header-modern {
    padding: 1rem;
  }

  .header-title {
    font-size: 1.5rem;
  }

  .form-section {
    padding: 1rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn-primary-modern,
  .btn-secondary-modern {
    width: 100%;
  }
}
</style>