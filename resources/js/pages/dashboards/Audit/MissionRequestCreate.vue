<template>
  <div class="mission-form-wrapper">
    <!-- Header Pro avec Gradient et Infos Générateur -->
    <div class="form-header">
      <div class="header-gradient"></div>
      <div class="header-content">
        <div class="header-title-section">
          <h1 class="form-title">📋 Demande de Mission d'Audit</h1>
          <p class="form-subtitle">Formulaire de soumission de demande d'audit interne</p>
        </div>
        <div class="header-info-box">
          <div class="info-item">
            <span class="info-label">Numéro Auto-Généré</span>
            <span class="info-value">{{ nextMissionNumber }}</span>
          </div>
          <div class="info-divider"></div>
          <div class="info-item">
            <span class="info-label">Générée par</span>
            <div class="user-info-header">
              <span class="user-avatar-header">{{ currentUser.name.charAt(0) }}</span>
              <div>
                <span class="info-name">{{ currentUser.name }}</span>
                <span class="info-email">{{ currentUser.email }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Formulaire -->
    <form @submit.prevent="submitForm" class="mission-form">
      <!-- Section 1: Informations de base -->
      <div class="form-section">
        <h2 class="section-title">📌 Informations générales</h2>
        
        <div class="form-row">
          <div class="form-group">
            <label for="mission_number">Numéro de mission (auto-généré) *</label>
            <input 
              :value="nextMissionNumber" 
              id="mission_number"
              type="text"
              class="form-control readonly"
              readonly
            />
            <input v-model="form.mission_number" type="hidden" />
          </div>

          <div class="form-group">
            <label for="entity_id">Entité audité *</label>
            <select v-model="form.entity_id" id="entity_id" class="form-control" required>
              <option value="">Sélectionner une entité</option>
              <option v-for="entity in entities" :key="entity.id" :value="entity.id">
                {{ entity.name }}
              </option>
            </select>
            <span v-if="errors.entity_id" class="error-text">{{ errors.entity_id[0] }}</span>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group full-width">
            <label for="mission_objective">Objectif de la mission *</label>
            <textarea 
              v-model="form.mission_objective" 
              id="mission_objective"
              class="form-control textarea"
              rows="3"
              placeholder="Définir l'objectif principal et les finalités de l'audit..."
              required
            ></textarea>
            <span v-if="errors.mission_objective" class="error-text">{{ errors.mission_objective[0] }}</span>
          </div>
        </div>
      </div>

      <!-- Section 2: Contexte et portée -->
      <div class="form-section">
        <h2 class="section-title">🎯 Portée de l'audit</h2>
        
        <div class="form-row">
          <div class="form-group full-width">
            <label for="audit_scope">Périmètre d'intervention *</label>
            <textarea 
              v-model="form.audit_scope" 
              id="audit_scope"
              class="form-control textarea"
              rows="3"
              placeholder="Délimiter précisément le champ d'application de l'audit..."
              required
            ></textarea>
            <span v-if="errors.audit_scope" class="error-text">{{ errors.audit_scope[0] }}</span>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="related_process_id">Processus concerné</label>
            <select v-model="form.related_process_id" id="related_process_id" class="form-control">
              <option value="">Sélectionner un processus</option>
              <option v-for="process in processes" :key="process.id" :value="process.id">
                {{ process.name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="risk">Niveau de risque identifié</label>
            <select v-model="form.risk" id="risk" class="form-control">
              <option value="">Sélectionner le niveau</option>
              <option value="Faible">Faible</option>
              <option value="Moyen">Moyen</option>
              <option value="Élevé">Élevé</option>
              <option value="Critique">Critique</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Section 3: Contexte opérationnel -->
      <div class="form-section">
        <h2 class="section-title">📊 Contexte opérationnel</h2>
        
        <div class="form-row">
          <div class="form-group">
            <label for="concern">Points de vigilance</label>
            <textarea 
              v-model="form.concern" 
              id="concern"
              class="form-control textarea"
              rows="3"
              placeholder="Éléments spécifiques nécessitant une attention particulière..."
            ></textarea>
          </div>

          <div class="form-group">
            <label for="result">Résultats attendus</label>
            <textarea 
              v-model="form.result" 
              id="result"
              class="form-control textarea"
              rows="3"
              placeholder="Livrables et bénéfices escomptés..."
            ></textarea>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group full-width">
            <label for="procedure">Méthodologie préconisée</label>
            <textarea 
              v-model="form.procedure" 
              id="procedure"
              class="form-control textarea"
              rows="2"
              placeholder="Approche et méthodes d'audit à mettre en œuvre..."
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Section 4: Planification -->
      <div class="form-section">
        <h2 class="section-title">📅 Planification</h2>
        
        <div class="form-row">
          <div class="form-group">
            <label for="start_date">Date de début souhaitée</label>
            <input 
              v-model="form.start_date" 
              id="start_date"
              type="date"
              class="form-control"
            />
          </div>

          <div class="form-group">
            <label for="end_date">Date de fin souhaitée</label>
            <input 
              v-model="form.end_date" 
              id="end_date"
              type="date"
              class="form-control"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group full-width">
            <label>Fréquence</label>
            <div class="radio-group">
              <label class="radio-label">
                <input type="radio" v-model="form.frequency" value="Ponctuelle" />
                Ponctuelle
              </label>
              <label class="radio-label">
                <input type="radio" v-model="form.frequency" value="Annuelle" />
                Annuelle
              </label>
              <label class="radio-label">
                <input type="radio" v-model="form.frequency" value="Biannuelle" />
                Biannuelle
              </label>
              <label class="radio-label">
                <input type="radio" v-model="form.frequency" value="Triennale" />
                Triennale
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 5: Informations du demandeur -->
      <div class="form-section">
        <h2 class="section-title">👤 Informations du demandeur</h2>
        
        <div class="form-row">
          <div class="form-group">
            <label for="requester_email">Email du demandeur *</label>
            <input 
              v-model="form.requester_email" 
              id="requester_email"
              type="email"
              class="form-control"
              placeholder="nom@entreprise.com"
              required
            />
            <span v-if="errors.requester_email" class="error-text">{{ errors.requester_email[0] }}</span>
          </div>

          <div class="form-group">
            <label for="requester_motif">Motif de la demande *</label>
            <select v-model="form.requester_motif" id="requester_motif" class="form-control" required>
              <option value="">Sélectionner un motif</option>
              <option value="Contrôle périodique">Contrôle périodique</option>
              <option value="Signalement d'anomalie">Signalement d'anomalie</option>
              <option value="Évolution réglementaire">Évolution réglementaire</option>
              <option value="Changement organisationnel">Changement organisationnel</option>
              <option value="Audit de conformité">Audit de conformité</option>
              <option value="Demande de la direction">Demande de la direction</option>
            </select>
            <span v-if="errors.requester_motif" class="error-text">{{ errors.requester_motif[0] }}</span>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group full-width">
            <label for="autre">Informations complémentaires</label>
            <textarea 
              v-model="form.autre" 
              id="autre"
              class="form-control textarea"
              rows="2"
              placeholder="Toute information utile pour la bonne compréhension de la demande..."
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Boutons d'action -->
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" @click="resetForm">
          ↻ Réinitialiser
        </button>
        <button type="submit" class="btn btn-primary" :disabled="loading">
          <span v-if="!loading">✓ Soumettre la demande</span>
          <span v-else>⏳ Envoi en cours...</span>
        </button>
      </div>
    </form>

    <!-- Modal de succès -->
    <div v-if="showSuccessModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header success">
          <h2>✅ Demande enregistrée</h2>
        </div>
        <div class="modal-body">
          <div class="success-info">
            <p class="success-code">
              <span class="label">Numéro:</span>
              <span class="value">{{ generatedCode }}</span>
            </p>
            <p class="success-generator">
              <span class="label">Générée par:</span>
              <span class="value">{{ currentUser.name }} ({{ currentUser.email }})</span>
            </p>
            <p class="success-message">Votre demande d'audit a été transmise avec succès.</p>
          </div>
          <div class="link-section">
            <p class="link-label">🔗 Lien de suivi :</p>
            <div class="link-box">
              <input 
                type="text" 
                :value="shareLink" 
                readonly 
                class="link-input"
              />
              <button @click="copyToClipboard" class="btn-copy">
                📋 Copier
              </button>
            </div>
          </div>
          <div class="modal-actions">
            <a :href="shareLink" target="_blank" class="btn btn-primary">
              👁️ Consulter la demande
            </a>
            <button @click="closeModal" class="btn btn-secondary">
              Fermer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  entities: Array,
  processes: Array,
  nextMissionNumber: String,
})

const page = usePage()
const currentUser = computed(() => page.props.auth.user)

const form = reactive({
  mission_objective: '',
  mission_number: '',
  entity_id: '',
  audit_scope: '',
  related_process_id: '',
  risk: '',
  concern: '',
  result: '',
  procedure: '',
  autre: '',
  frequency: 'Ponctuelle',
  start_date: '',
  end_date: '',
  requester_email: '',
  requester_motif: '',
})

const errors = ref({})
const loading = ref(false)
const showSuccessModal = ref(false)
const shareLink = ref('')
const generatedCode = ref('')

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  form.mission_number = props.nextMissionNumber

  try {
    // ✅ URL CORRECT - Utilise le préfixe de ton app: m/audit.core
    const response = await fetch('/m/audit.core/api/audit/mission-requests', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify(form),
    })

    const data = await response.json()

    if (!response.ok) {
      if (data.errors) {
        errors.value = data.errors
      } else {
        alert('Erreur lors de la soumission')
      }
      return
    }

    shareLink.value = data.share_link
    generatedCode.value = data.data.code
    showSuccessModal.value = true

  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur réseau')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  Object.assign(form, {
    mission_objective: '',
    mission_number: '',
    entity_id: '',
    audit_scope: '',
    related_process_id: '',
    risk: '',
    concern: '',
    result: '',
    procedure: '',
    autre: '',
    frequency: 'Ponctuelle',
    start_date: '',
    end_date: '',
    requester_email: '',
    requester_motif: '',
  })
  errors.value = {}
}

const closeModal = () => {
  showSuccessModal.value = false
}

const copyToClipboard = () => {
  navigator.clipboard.writeText(shareLink.value)
  alert('✅ Lien copié dans le presse-papier')
}
</script>

<style scoped>
:root {
  --primary: #1a237e;
  --primary-light: #283593;
  --secondary: #455a64;
  --success: #2e7d32;
  --border: #cfd8dc;
  --light: #f5f7fa;
  --background: #ffffff;
  --accent: #1565c0;
}

.mission-form-wrapper {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #e8eef7 100%);
  padding: 2rem 1rem;
  font-family: 'Segoe UI', 'Roboto', sans-serif;
}

/* Header Pro avec Gradient */
.form-header {
  position: relative;
  margin-bottom: 2rem;
  overflow: hidden;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(26, 35, 126, 0.15);
  max-width: 900px;
  margin-left: auto;
  margin-right: auto;
}

.header-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
}

.header-content {
  position: relative;
  z-index: 2;
  padding: 2rem;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-title-section {
  flex: 1;
}

.form-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin: 0;
}

.form-subtitle {
  font-size: 0.95rem;
  margin: 0.5rem 0 0 0;
  opacity: 0.9;
}

.header-info-box {
  background: rgba(255, 255, 255, 0.15);
  padding: 1.5rem;
  border-radius: 8px;
  backdrop-filter: blur(10px);
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.info-label {
  font-size: 0.75rem;
  opacity: 0.8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.info-value {
  font-size: 1.2rem;
  font-weight: 700;
  font-family: 'Courier New', monospace;
}

.user-info-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-avatar-header {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
}

.info-name {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
}

.info-email {
  display: block;
  font-size: 0.75rem;
  opacity: 0.8;
}

.info-divider {
  width: 1px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
}

/* Formulaire */
.mission-form {
  max-width: 900px;
  margin: 0 auto;
  background: var(--background);
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.form-section {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--light);
}

.form-section:last-of-type {
  border-bottom: none;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--primary);
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--light);
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-weight: 500;
  color: var(--secondary);
  margin-bottom: 0.3rem;
  font-size: 0.9rem;
}

.form-control {
  padding: 0.6rem 0.8rem;
  border: 2px solid var(--border);
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.9rem;
  transition: all 0.3s;
  background: var(--background);
}

.form-control:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
}

.form-control.readonly {
  background: var(--light);
  color: var(--primary);
  font-weight: 600;
  cursor: not-allowed;
}

.textarea {
  resize: vertical;
  min-height: 70px;
}

.error-text {
  color: #d32f2f;
  font-size: 0.8rem;
  margin-top: 0.2rem;
}

.radio-group {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 0.5rem;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 500;
}

.radio-label input[type="radio"] {
  cursor: pointer;
  accent-color: var(--primary);
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding: 1.5rem 2rem;
  background: var(--light);
  border-top: 1px solid var(--border);
}

.btn {
  padding: 0.7rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.3s;
}

.btn-primary {
  background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(26, 35, 126, 0.4);
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: var(--secondary);
  border: 1px solid var(--border);
}

.btn-secondary:hover {
  background: #f0f0f0;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 550px;
  width: 90%;
  animation: fadeIn 0.3s;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.modal-header {
  padding: 1.5rem 2rem;
  background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
  color: white;
  border-radius: 8px 8px 0 0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.3rem;
}

.modal-body {
  padding: 2rem;
}

.success-info {
  background: var(--light);
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  border-left: 4px solid var(--success);
}

.success-code,
.success-generator {
  display: flex;
  gap: 1rem;
  margin: 0.5rem 0;
  font-weight: 600;
}

.success-code .label,
.success-generator .label {
  color: var(--secondary);
  min-width: 80px;
}

.success-code .value,
.success-generator .value {
  color: var(--primary);
  font-family: 'Courier New', monospace;
}

.success-message {
  color: var(--secondary);
  margin-top: 1rem;
  font-size: 0.95rem;
}

.link-section {
  margin-bottom: 1.5rem;
}

.link-label {
  font-weight: 600;
  color: var(--secondary);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.link-box {
  display: flex;
  gap: 0.5rem;
}

.link-input {
  flex: 1;
  padding: 0.6rem 0.8rem;
  border: 1px solid var(--border);
  border-radius: 4px;
  font-size: 0.8rem;
  background: var(--light);
  font-family: 'Courier New', monospace;
}

.btn-copy {
  padding: 0.6rem 1rem;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-copy:hover {
  background: var(--primary-light);
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .mission-form-wrapper {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    text-align: center;
  }

  .header-info-box {
    flex-direction: column;
  }

  .info-divider {
    width: 100%;
    height: 1px;
  }

  .form-section {
    padding: 1rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }

  .modal-actions {
    flex-direction: column;
  }
}
</style>