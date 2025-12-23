<template>
  <VerticalLayout>
    <Head title="Créer un Processus BPMN" />

    <!-- Header -->
    <div class="page-header-form">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <b-breadcrumb class="modern-breadcrumb">
            <b-breadcrumb-item @click="$inertia.visit(route('process.core.modeling.bpmn.index'))">
              <i class="ti ti-brain me-1"></i> Modélisation BPMN
            </b-breadcrumb-item>
            <b-breadcrumb-item active>Nouveau processus</b-breadcrumb-item>
          </b-breadcrumb>
          <h2 class="page-title mt-2">
            <i class="ti ti-plus me-2"></i>
            Créer un nouveau processus BPMN
          </h2>
          <p class="text-white opacity-75 mb-0">
            Saisissez les informations de base, puis passez à la modélisation graphique complète.
          </p>
        </div>
        <b-button variant="outline-secondary" @click="$inertia.visit(route('process.core.modeling.bpmn.index'))">
          <i class="ti ti-arrow-left me-2"></i> Retour
        </b-button>
      </div>
    </div>

    <!-- Formulaire -->
    <div class="form-container-modern">
      <b-form @submit.prevent="submit">
        <b-row class="g-4 mb-4">
          <b-col md="6">
            <label class="form-label-modern">Code <span class="text-danger">*</span></label>
            <b-form-input
              v-model="form.code"
              placeholder="Ex: PROC-001"
              class="input-modern"
              :class="{ 'is-invalid': errors.code }"
              required
            />
            <small class="form-hint">Code unique du processus</small>
            <div class="invalid-feedback" v-if="errors.code">{{ errors.code[0] }}</div>
          </b-col>

          <b-col md="6">
            <label class="form-label-modern">Nom <span class="text-danger">*</span></label>
            <b-form-input
              v-model="form.name"
              placeholder="Ex: Traitement des commandes clients"
              class="input-modern"
              :class="{ 'is-invalid': errors.name }"
              required
            />
            <div class="invalid-feedback" v-if="errors.name">{{ errors.name[0] }}</div>
          </b-col>
        </b-row>

        <b-alert show variant="info" class="modern-alert">
          <div class="d-flex align-items-start gap-3">
            <i class="ti ti-info-circle fs-4"></i>
            <div>
              <strong>Après création :</strong> vous serez automatiquement redirigé vers l'éditeur BPMN complet
              avec palette d'outils (à gauche), canvas et panel de propriétés (à droite).
            </div>
          </div>
        </b-alert>

        <div class="form-actions">
          <b-button variant="light" @click="$inertia.visit(route('process.core.modeling.bpmn.index'))" :disabled="loading">
            <i class="ti ti-x me-2"></i> Annuler
          </b-button>
          <b-button type="submit" variant="primary" :disabled="loading">
            <b-spinner v-if="loading" small class="me-2"></b-spinner>
            <i v-else class="ti ti-check me-2"></i>
            {{ loading ? 'Création...' : 'Créer et ouvrir l\'éditeur' }}
          </b-button>
        </div>
      </b-form>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const form = ref({
  code: '',
  name: '',
  macro_process_id: null
})

const loading = ref(false)
const errors = ref({})

function submit() {
  loading.value = true
  errors.value = {}

  router.post(route('process.core.modeling.bpmn.store'), form.value, {
    onSuccess: (page) => {
      // Récupère l'ID du nouveau processus depuis les props ou flash
      const newId = page.props.process?.id || page.props.flash?.process_id || page.props.process_id
      if (newId) {
        router.visit(route('process.core.modeling.bpmn.edit', newId))
      } else {
        alert('Processus créé avec succès, mais redirection impossible.')
      }
    },
    onError: (err) => {
      errors.value = err
      loading.value = false
    },
    onFinish: () => {
      loading.value = false
    }
  })
}
</script>

<style scoped>
/* Même styles que vos autres pages */
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
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
}

.form-container-modern {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.form-label-modern {
  font-weight: 600;
  font-size: 0.9375rem;
  color: #495057;
  margin-bottom: 0.5rem;
}

.input-modern {
  border: 2px solid #e9ecef;
  border-radius: 10px;
  padding: 0.75rem 1rem;
}

.input-modern:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.input-modern.is-invalid {
  border-color: #dc3545;
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

.modern-alert {
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  border-left: 4px solid #2196f3;
  padding: 1.5rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 2rem;
  margin-top: 2rem;
  border-top: 2px solid #f0f0f0;
}
</style>