<!-- resources/views/dashboards/Param/Auditors/Create.vue - DESIGN PROFESSIONNEL -->

<template>
  <VerticalLayout>
    <Head title="Enregistrement des auditeurs" />

    <div class="auditor-registration">
      <!-- Header -->
      <div class="registration-header">
        <h1>
          <i class="ti ti-user-plus"></i>
          Enregistrement des auditeurs
        </h1>
      </div>

      <!-- Main Form Container -->
      <div class="registration-container">
        <!-- Erreurs globales -->
        <b-alert 
          v-if="Object.keys(errors).length > 0" 
          variant="danger" 
          dismissible
          class="mb-3"
        >
          <i class="ti ti-alert-circle me-2"></i>
          <strong>Erreurs de validation:</strong>
          <ul class="mb-0 mt-2">
            <li v-for="(messages, field) in errors" :key="field">
              <strong>{{ formatFieldName(field) }}:</strong> {{ Array.isArray(messages) ? messages.join(', ') : messages }}
            </li>
          </ul>
        </b-alert>

        <!-- Container Principal 2 colonnes -->
        <div class="registration-content">
          <!-- ================== COLONNE GAUCHE - FORMULAIRE ================== -->
          <div class="form-column">
            <form @submit.prevent="submitForm" class="auditor-form">

              <!-- Identité Auditeur Section -->
              <div class="form-panel">
                <div class="panel-header">
                  <strong>Identité auditeur</strong>
                </div>
                <div class="panel-body">
                  <!-- Code Projet -->
                  <div class="form-group-compact">
                    <label>Code projet</label>
                    <b-form-select
                      v-model="form.entity_id"
                      :options="[
                        { value: null, text: '- Sélectionner -' },
                        ...entities.map(e => ({ value: e.id, text: e.name }))
                      ]"
                      class="form-control-sm"
                    />
                  </div>

                  <!-- ID Auditeur / Resp -->
                  <div class="form-row-compact">
                    <div class="form-group-compact flex-1">
                      <label>ID Auditeur</label>
                      <b-form-input
                        v-model="form.audit_code"
                        disabled
                        class="form-control-sm bg-light"
                        :value="generateCode()"
                      />
                    </div>
                    <div class="form-group-compact flex-1 ms-2">
                      <label>Resp.</label>
                      <b-form-input
                        disabled
                        class="form-control-sm bg-light"
                        value="(généré)"
                      />
                    </div>
                  </div>

                  <!-- Nom / Prénoms -->
                  <div class="form-row-compact">
                    <div class="form-group-compact flex-1">
                      <label>Nom</label>
                      <b-form-input
                        v-model="form.last_name"
                        :state="getFieldState('last_name')"
                        placeholder="Dupont"
                        class="form-control-sm"
                      />
                    </div>
                    <div class="form-group-compact flex-1 ms-2">
                      <label>Prénoms</label>
                      <b-form-input
                        v-model="form.first_name"
                        :state="getFieldState('first_name')"
                        placeholder="Jean"
                        class="form-control-sm"
                      />
                    </div>
                  </div>

                  <!-- Email -->
                  <div class="form-group-compact">
                    <label>Email *</label>
                    <b-form-input
                      v-model="form.email"
                      type="email"
                      :state="getFieldState('email')"
                      placeholder="email@example.com"
                      class="form-control-sm"
                    />
                  </div>

                  <!-- Date Naissance / Lieu -->
                  <div class="form-row-compact">
                    <div class="form-group-compact flex-1">
                      <label>Date Naiss./Lieu</label>
                      <b-form-input
                        v-model="form.date_of_birth"
                        type="date"
                        class="form-control-sm"
                      />
                    </div>
                    <div class="form-group-compact flex-1 ms-2">
                      <b-form-input
                        v-model="form.birthplace"
                        placeholder="Abidjan"
                        class="form-control-sm mt-4"
                      />
                    </div>
                  </div>

                  <!-- Adresse -->
                  <div class="form-group-compact">
                    <label>Adresse</label>
                    <b-form-textarea
                      v-model="form.address"
                      rows="2"
                      placeholder="Adresse complète"
                      class="form-control-sm"
                    />
                  </div>

                  <!-- Téléphone / Genre -->
                  <div class="form-row-compact">
                    <div class="form-group-compact flex-1">
                      <label>Téléphone</label>
                      <b-form-input
                        v-model="form.phone"
                        placeholder="+225 01 01 01 01"
                        class="form-control-sm"
                      />
                    </div>
                    <div class="form-group-compact flex-1 ms-2">
                      <label>Genre</label>
                      <b-form-select
                        v-model="form.gender"
                        :options="[
                          { value: null, text: '-' },
                          { value: 'M', text: 'M' },
                          { value: 'F', text: 'F' },
                        ]"
                        class="form-control-sm"
                      />
                    </div>
                  </div>

                  <!-- Expérience Audit / Autre -->
                  <div class="form-row-compact">
                    <div class="form-group-compact flex-1">
                      <label>Expér. Audit</label>
                      <b-form-input
                        v-model.number="form.audit_experience"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="form-control-sm"
                      />
                    </div>
                    <div class="form-group-compact flex-1 ms-2">
                      <label>Expér. Hors Audit</label>
                      <b-form-input
                        v-model.number="form.other_experience"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="form-control-sm"
                      />
                    </div>
                  </div>

                  <!-- Code / Statut -->
                  <div class="form-row-compact">
                    <div class="form-group-compact flex-1">
                      <label>Code</label>
                      <b-form-input
                        disabled
                        placeholder="Auto"
                        class="form-control-sm bg-light"
                      />
                    </div>
                    <div class="form-group-compact flex-1 ms-2">
                      <label>Statut</label>
                      <b-form-select
                        v-model="form.status"
                        :options="[
                          { value: 'active', text: 'Actif' },
                          { value: 'inactive', text: 'Inactif' },
                          { value: 'suspended', text: 'Suspendu' },
                        ]"
                        class="form-control-sm"
                      />
                    </div>
                  </div>

                  <!-- Biographie -->
                  <div class="form-group-compact">
                    <label>Biographie</label>
                    <b-form-textarea
                      v-model="form.bio"
                      rows="2"
                      maxlength="1000"
                      class="form-control-sm"
                    />
                  </div>

                  <!-- Avatar -->
                  <div class="form-group-compact">
                    <label>Photo</label>
                    <div class="avatar-upload-group">
                      <input 
                        type="file" 
                        ref="fileInput" 
                        @change="handleAvatarChange"
                        accept="image/*"
                        class="d-none"
                      />
                      <button 
                        type="button"
                        @click="$refs.fileInput.click()"
                        class="btn btn-sm btn-outline-secondary w-100"
                      >
                        <i class="ti ti-cloud-upload me-2"></i>
                        Uploader une photo
                      </button>
                      <small v-if="avatarPreview" class="text-success d-block mt-1">
                        <i class="ti ti-check me-1"></i>Photo sélectionnée
                      </small>
                    </div>
                  </div>

                  <!-- Checkbox Email -->
                  <div class="form-group-compact">
                    <b-form-checkbox v-model="form.send_email" class="form-check-sm">
                      Envoyer email avec mot de passe
                    </b-form-checkbox>
                  </div>
                </div>
              </div>

              <!-- Buttons -->
              <div class="form-buttons">
                <button 
                  type="button"
                  @click="$router.back()"
                  class="btn btn-sm btn-outline-secondary"
                >
                  <i class="ti ti-x me-1"></i>
                  Annuler
                </button>
                <button 
                  type="submit"
                  :disabled="isSubmitting"
                  class="btn btn-sm btn-primary"
                >
                  <b-spinner small v-if="isSubmitting" class="me-2"></b-spinner>
                  <i v-else class="ti ti-check me-1"></i>
                  {{ isSubmitting ? 'Création...' : 'Valider' }}
                </button>
              </div>
            </form>
          </div>

          <!-- ================== COLONNE DROITE - PHOTO ET COMPÉTENCES ================== -->
          <div class="right-column">
            <!-- Photo Box -->
            <div class="right-panel photo-panel">
              <div class="panel-header">
                <strong>Photo</strong>
              </div>
              <div class="panel-body">
                <div v-if="avatarPreview" class="photo-preview">
                  <img :src="avatarPreview" alt="Avatar" />
                </div>
                <div v-else class="photo-placeholder">
                  <i class="ti ti-photo"></i>
                  <p>Photo</p>
                </div>
              </div>
            </div>

            <!-- Compétences Box -->
            <div class="right-panel competencies-panel">
              <div class="panel-header">
                <strong>Compétence</strong>
              </div>
              <div class="panel-body">
                <div v-if="competenciesByCategory && competenciesByCategory.length > 0" class="competencies-list">
                  <div 
                    v-for="category in competenciesByCategory" 
                    :key="category.id"
                    class="competency-category"
                  >
                    <div class="category-header">
                      <strong>{{ category.name }}</strong>
                      <input 
                        type="checkbox"
                        :checked="isAllCompetenciesSelectedInCategory(category.id)"
                        @change="toggleAllCompetenciesInCategory(category.id)"
                        class="form-check-input"
                        title="Sélectionner tous"
                      />
                    </div>
                    <div class="competencies-checkboxes">
                      <label 
                        v-for="comp in (category.competencies || [])" 
                        :key="comp.id"
                        class="competency-item"
                      >
                        <input 
                          type="checkbox"
                          v-model="selectedCompetencies"
                          :native-value="comp.id"
                          class="form-check-input"
                        />
                        <span class="competency-text">{{ comp.code }}</span>
                      </label>
                    </div>
                  </div>
                </div>
                <div v-else class="text-muted small">
                  Aucune compétence disponible
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  entities: {
    type: Array,
    default: () => []
  },
  competenciesByCategory: {
    type: Array,
    default: () => []
  },
})

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  birthplace: '',
  address: '',
  city: '',
  postal_code: '',
  country: '',
  audit_experience: null,
  other_experience: null,
  gender: null,
  status: 'active',
  bio: '',
  audit_code: '',
  entity_id: null,
  send_email: false,
})

const selectedCompetencies = ref([])
const errors = ref({})
const isSubmitting = ref(false)
const fileInput = ref(null)
const avatarFile = ref(null)
const avatarPreview = ref('')

function generateCode() {
  return `AUD${String(Math.floor(Math.random() * 10000)).padStart(4, '0')}`
}

function formatFieldName(field) {
  return field
    .replace(/([A-Z])/g, ' $1')
    .replace(/_/g, ' ')
    .replace(/^\w/, (c) => c.toUpperCase())
}

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

function isAllCompetenciesSelectedInCategory(categoryId) {
  const category = props.competenciesByCategory.find(c => c.id === categoryId)
  if (!category) return false
  const competencyIds = (category.competencies || []).map(c => c.id)
  return competencyIds.length > 0 && competencyIds.every(id => selectedCompetencies.value.includes(id))
}

function toggleAllCompetenciesInCategory(categoryId) {
  const category = props.competenciesByCategory.find(c => c.id === categoryId)
  if (!category) return

  const competencyIds = (category.competencies || []).map(c => c.id)
  const isAllSelected = isAllCompetenciesSelectedInCategory(categoryId)

  if (isAllSelected) {
    // Déselectionner tous
    selectedCompetencies.value = selectedCompetencies.value.filter(id => !competencyIds.includes(id))
  } else {
    // Selectionner tous
    competencyIds.forEach(id => {
      if (!selectedCompetencies.value.includes(id)) {
        selectedCompetencies.value.push(id)
      }
    })
  }
}

function submitForm() {
  isSubmitting.value = true
  errors.value = {}

  const data = {
    ...form.value,
    send_email: form.value.send_email ? '1' : '0',
    competencies: selectedCompetencies.value.map((compId) => ({
      competency_id: parseInt(compId),
      level: 1,
      is_primary: false,
    })),
  }

  if (avatarFile.value) {
    const formData = new FormData()
    Object.keys(data).forEach(key => {
      if (key === 'competencies') {
        formData.append(key, JSON.stringify(data[key]))
      } else {
        formData.append(key, data[key] ?? '')
      }
    })
    formData.append('avatar', avatarFile.value)

    router.post(route('param.projects.auditors.store'), formData, {
      onError: (errs) => {
        errors.value = errs
        isSubmitting.value = false
      },
      onFinish: () => {
        isSubmitting.value = false
      }
    })
  } else {
    router.post(route('param.projects.auditors.store'), data, {
      onError: (errs) => {
        errors.value = errs
        isSubmitting.value = false
      },
      onFinish: () => {
        isSubmitting.value = false
      }
    })
  }
}
</script>

<style scoped>
.auditor-registration {
  background: #f5f5f5;
  padding: 1rem;
  border-radius: 8px;
}

.registration-header {
  background: white;
  padding: 1rem;
  border-bottom: 3px solid #0066cc;
  border-radius: 4px 4px 0 0;
  margin-bottom: 1rem;
}

.registration-header h1 {
  margin: 0;
  font-size: 1.25rem;
  color: #0066cc;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.registration-container {
  background: white;
  border-radius: 4px;
  padding: 1rem;
  border: 1px solid #ccc;
}

.registration-content {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 1.5rem;
}

/* ===================== COLONNE GAUCHE ===================== */
.form-column {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-panel {
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
  background: white;
}

.panel-header {
  background: #f8f8f8;
  padding: 0.75rem;
  border-bottom: 1px solid #ddd;
  font-weight: 600;
  color: #333;
  font-size: 0.9rem;
}

.panel-body {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.form-group-compact {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-group-compact label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.form-control-sm {
  height: 28px;
  padding: 0.25rem 0.5rem;
  font-size: 0.85rem;
  border: 1px solid #ccc;
  border-radius: 3px;
}

.form-control-sm:focus {
  border-color: #0066cc;
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

.form-row-compact {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  align-items: flex-start;
}

.form-row-compact .flex-1 {
  flex: 1;
}

.form-row-compact .ms-2 {
  margin-left: 0.5rem !important;
}

.form-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid #ddd;
}

.form-buttons .btn {
  padding: 0.4rem 0.8rem;
  font-size: 0.85rem;
}

.form-check-sm {
  font-size: 0.85rem;
}

/* ===================== COLONNE DROITE ===================== */
.right-column {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.right-panel {
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
  background: white;
}

.photo-panel {
  min-height: 180px;
}

.photo-panel .panel-body {
  padding: 0;
  gap: 0;
}

.photo-placeholder {
  width: 100%;
  height: 160px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f0f0f0;
  color: #999;
  font-size: 2rem;
}

.photo-placeholder p {
  margin: 0.5rem 0 0 0;
  font-size: 0.9rem;
}

.photo-preview {
  width: 100%;
  height: 160px;
  overflow: hidden;
}

.photo-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ===================== COMPÉTENCES ===================== */
.competencies-panel {
  flex: 1;
}

.competencies-list {
  max-height: 400px;
  overflow-y: auto;
}

.competency-category {
  margin-bottom: 0.75rem;
  border-radius: 3px;
}

.category-header {
  background: #ffeb3b;
  color: #000;
  padding: 0.5rem 0.75rem;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 3px 3px 0 0;
}

.category-header input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.competencies-checkboxes {
  background: #fffacd;
  padding: 0.5rem;
  border-radius: 0 0 3px 3px;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.competency-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem;
  cursor: pointer;
  font-size: 0.85rem;
  user-select: none;
}

.competency-item:hover {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 2px;
}

.competency-item input[type="checkbox"] {
  width: 14px;
  height: 14px;
  cursor: pointer;
}

.competency-text {
  font-weight: 600;
  color: #333;
}

/* Responsive */
@media (max-width: 1024px) {
  .registration-content {
    grid-template-columns: 1fr;
  }

  .right-column {
    display: grid;
    grid-template-columns: 180px 1fr;
    gap: 1rem;
  }

  .photo-panel {
    min-height: auto;
  }
}

@media (max-width: 768px) {
  .form-row-compact {
    grid-template-columns: 1fr;
  }

  .registration-content {
    grid-template-columns: 1fr;
  }

  .right-column {
    display: flex;
    flex-direction: column;
  }
}
</style>