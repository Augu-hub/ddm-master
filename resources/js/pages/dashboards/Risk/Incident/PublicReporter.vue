<template>
  <div class="reporter-page">

    <!-- HEADER -->
    <div class="reporter-header">
      <div class="reporter-header-inner">
        <div class="reporter-logo">
          <i class="ti ti-shield-lock"></i>
        </div>
        <div>
          <h1 class="reporter-title">Signalement d'incident</h1>
          <p class="reporter-subtitle">
            {{ props.label || 'Signalez un incident confidentiel' }}
            <span v-if="props.entityName" class="reporter-entity">— {{ props.entityName }}</span>
          </p>
        </div>
      </div>
    </div>

    <div class="reporter-body">

      <!-- SUCCÈS -->
      <Transition name="slide-up">
        <div v-if="submitted" class="reporter-success">
          <i class="ti ti-circle-check reporter-success-icon"></i>
          <h3 class="fw-bold mb-2">Signalement enregistré</h3>
          <p class="mb-1">Votre signalement a été transmis avec succès.</p>
          <p class="mb-3">
            Référence : <strong class="font-monospace">{{ submittedCode }}</strong>
          </p>
          <p class="text-muted small mb-4">
            Conservez cette référence pour tout suivi ultérieur.
          </p>
          <button class="btn btn-outline-primary" @click="resetForm">
            <i class="ti ti-plus me-1"></i>Faire un autre signalement
          </button>
        </div>
      </Transition>

      <form v-if="!submitted" @submit.prevent="submitReport" novalidate>

        <!-- Confidentialité -->
        <div class="reporter-info mb-4">
          <i class="ti ti-lock me-2 flex-shrink-0"></i>
          Votre signalement est traité de manière confidentielle.
          Vos coordonnées personnelles sont entièrement optionnelles.
        </div>

        <!-- LIBELLÉ -->
        <div class="mb-3">
          <label class="reporter-lbl">
            Intitulé de l'incident <span class="text-danger">*</span>
          </label>
          <input v-model.trim="form.libelle" type="text"
                 :class="['form-control', { 'is-invalid': errors.libelle }]"
                 placeholder="Décrivez brièvement l'incident…"
                 maxlength="255" autocomplete="off"/>
          <div v-if="errors.libelle" class="invalid-feedback">{{ errors.libelle }}</div>
        </div>

        <!-- DESCRIPTION -->
        <div class="mb-3">
          <label class="reporter-lbl">Détails et contexte</label>
          <textarea v-model.trim="form.description" class="form-control" rows="4"
                    placeholder="Quand, où, comment cela s'est-il produit ? Qui est impliqué ?…"></textarea>
        </div>

        <!-- PROCESSUS (saisie libre) -->
        <div class="mb-3">
          <label class="reporter-lbl">Processus concerné</label>
          <input v-model.trim="form.processus_libelle" type="text"
                 class="form-control"
                 placeholder="Ex : Gestion des achats, Paie, Trésorerie…"
                 autocomplete="off"/>
          <div class="form-text text-muted">
            Saisissez le nom du processus ou de la fonction concernée
          </div>
        </div>

        <!-- ACTIVITÉ (saisie libre) -->
        <div class="mb-3">
          <label class="reporter-lbl">Activité ou tâche concernée</label>
          <input v-model.trim="form.activite_libelle" type="text"
                 class="form-control"
                 placeholder="Ex : Validation des factures, Saisie des virements…"
                 autocomplete="off"/>
          <div class="form-text text-muted">
            Précisez si possible l'activité spécifique où l'incident a été constaté
          </div>
        </div>

        <!-- DATE + MONTANT -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="reporter-lbl">Date approximative de l'incident</label>
            <input v-model="form.date_incident" type="date" class="form-control"/>
          </div>
          <div class="col-md-6">
            <label class="reporter-lbl">Estimation du montant en jeu</label>
            <div class="input-group">
              <input v-model="form.evaluation_monetaire" type="number"
                     class="form-control" placeholder="0.00" min="0" step="0.01"/>
              <select v-model="form.devise" class="form-select" style="max-width:110px">
                <option value="XOF">XOF</option>
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
                <option value="GBP">GBP</option>
              </select>
            </div>
            <div class="form-text text-muted">Laissez vide si inconnu</div>
          </div>
        </div>

        <!-- COORDONNÉES (optionnelles) -->
        <div class="reporter-optional-block mb-4">
          <div class="reporter-optional-title mb-2">
            <i class="ti ti-user me-1"></i>
            Vos coordonnées — <em>optionnel, pour un suivi de votre signalement</em>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="reporter-lbl">Nom / Prénom</label>
              <input v-model.trim="form.reporter_name" type="text" class="form-control"
                     placeholder="Laissez vide pour rester anonyme"
                     autocomplete="name"/>
            </div>
            <div class="col-md-6">
              <label class="reporter-lbl">Adresse e-mail</label>
              <input v-model.trim="form.reporter_email" type="email" class="form-control"
                     placeholder="Pour recevoir un accusé de réception"
                     autocomplete="email"/>
              <div v-if="errors.reporter_email" class="text-danger small mt-1">
                {{ errors.reporter_email }}
              </div>
            </div>
          </div>
        </div>

        <!-- ERREUR GLOBALE -->
        <div v-if="globalError" class="alert alert-danger py-2 mb-3">
          <i class="ti ti-alert-circle me-2"></i>{{ globalError }}
        </div>

        <!-- SUBMIT -->
        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary btn-lg px-5" :disabled="submitting">
            <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="ti ti-send me-2"></i>
            Envoyer mon signalement
          </button>
        </div>
      </form>
    </div>

    <!-- FOOTER -->
    <div class="reporter-footer">
      <i class="ti ti-lock-square me-1"></i>
      Formulaire sécurisé — vos données sont traitées de manière strictement confidentielle.
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  token:      { type: String, default: '' },
  entityName: { type: String, default: '' },
  label:      { type: String, default: '' },
  submitUrl:  { type: String, default: '' }, // URL complète passée par le contrôleur
})

const submitted     = ref(false)
const submittedCode = ref('')
const submitting    = ref(false)
const errors        = ref({})
const globalError   = ref('')

const emptyForm = () => ({
  libelle:              '',
  description:          '',
  processus_libelle:    '',
  activite_libelle:     '',
  date_incident:        '',
  evaluation_monetaire: null,
  devise:               'XOF',
  reporter_name:        '',
  reporter_email:       '',
})
const form = ref(emptyForm())

function resetForm() {
  form.value      = emptyForm()
  errors.value    = {}
  globalError.value = ''
  submitted.value   = false
}

// Utilise fetch natif — pas de router Inertia, pas de document.write
async function submitReport() {
  errors.value    = {}
  globalError.value = ''

  if (!form.value.libelle.trim()) {
    errors.value = { libelle: "Veuillez décrire l'incident." }
    return
  }

  submitting.value = true

  try {
    // URL de soumission : /m/risk.core/report/{token}
    const url = props.submitUrl || `/m/risk.core/report/${props.token}`

    // Récupérer le CSRF token depuis la meta tag (généré par Laravel)
    const csrfMeta  = document.querySelector('meta[name="csrf-token"]')
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : ''

    const response = await fetch(url, {
      method:  'POST',
      headers: {
        'Content-Type':     'application/json',
        'Accept':           'application/json',
        'X-CSRF-TOKEN':     csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(form.value),
    })

    const data = await response.json()

    if (response.ok) {
      submitting.value  = false
      submitted.value   = true
      submittedCode.value = data.code ?? data.message ?? 'INC-EXT-???'
    } else {
      submitting.value = false
      if (data.errors) {
        errors.value = data.errors
        globalError.value = Object.values(data.errors)[0]?.[0] ?? 'Erreur de validation.'
      } else {
        globalError.value = data.message ?? 'Une erreur est survenue.'
      }
    }
  } catch (e) {
    submitting.value  = false
    globalError.value = 'Impossible de joindre le serveur. Vérifiez votre connexion.'
  }
}
</script>

<style scoped>
.reporter-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  display: flex; flex-direction: column;
}
.reporter-header {
  background: linear-gradient(135deg, #1e293b, #1e3a5f);
  color: #fff; padding: 32px 24px;
}
.reporter-header-inner {
  max-width: 680px; margin: 0 auto;
  display: flex; align-items: center; gap: 20px;
}
.reporter-logo {
  width: 56px; height: 56px; border-radius: 14px; flex-shrink: 0;
  background: rgba(255,255,255,.12);
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; color: #93c5fd;
}
.reporter-title    { font-size: 1.5rem; font-weight: 800; margin: 0; }
.reporter-subtitle { font-size: .9rem; color: #94a3b8; margin: 4px 0 0; }
.reporter-entity   { color: #60a5fa; }
.reporter-body {
  max-width: 680px; width: 100%;
  margin: 32px auto; padding: 36px;
  background: #fff; border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,0,0,.08);
}
.reporter-info {
  background: #f0f9ff; border: 1px solid #bae6fd;
  border-radius: 8px; padding: 10px 14px;
  font-size: .82rem; color: #0369a1;
  display: flex; align-items: flex-start;
}
.reporter-lbl {
  font-size: .82rem; font-weight: 600;
  color: #374151; display: block; margin-bottom: 5px;
}
.reporter-optional-block {
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 10px; padding: 16px;
}
.reporter-optional-title { font-size: .8rem; font-weight: 600; color: #64748b; }
.reporter-success {
  text-align: center; padding: 40px 16px;
}
.reporter-success-icon {
  font-size: 4.5rem; color: #22c55e;
  display: block; margin-bottom: 16px;
}
.reporter-footer {
  text-align: center; padding: 20px;
  font-size: .75rem; color: #94a3b8;
}
.slide-up-enter-active { transition: all .35s ease-out; }
.slide-up-enter-from   { opacity: 0; transform: translateY(12px); }
@media (max-width: 640px) {
  .reporter-body         { margin: 16px; padding: 20px; }
  .reporter-header-inner { flex-direction: column; text-align: center; }
}
</style>