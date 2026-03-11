<template>
  <div class="mission-index-wrapper">
    <!-- Header avec bouton -->
    <div class="header-section">
      <div class="header-content">
        <div>
          <h1 class="page-title">📋 Demandes de Mission d'Audit</h1>
          <p class="page-subtitle">Gestion et suivi de toutes les demandes</p>
        </div>
        <button @click="generateAndShareLink" class="btn btn-primary btn-lg" :disabled="generating">
          <span v-if="!generating">➕ Générer Lien de Formulaire</span>
          <span v-else>⏳ Génération...</span>
        </button>
      </div>
    </div>

    <!-- Tableau des demandes -->
    <div class="table-container">
      <table class="missions-table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Objectif</th>
            <th>Entité</th>
            <th>Générée par</th>
            <th>Remplie par</th>
            <th>Statut</th>
            <th>Dates</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="mission in missionRequests.data" :key="mission.id" class="mission-row">
            <td class="code-cell">
              <span class="code-badge">{{ mission.code }}</span>
            </td>
            <td class="objective-cell">
              <span class="objective-text">{{ truncate(mission.mission_objective, 40) }}</span>
            </td>
            <td class="entity-cell">
              {{ mission.entity?.name || 'N/A' }}
            </td>
            <td class="requester-cell">
              <div class="user-info">
                <span class="user-avatar generateur">{{ mission.requester?.name.charAt(0) }}</span>
                <div>
                  <p class="user-name">{{ mission.requester?.name }}</p>
                  <p class="user-email">{{ mission.requester?.email }}</p>
                </div>
              </div>
            </td>
            <td class="filled-by-cell">
              <div v-if="mission.filled_by_name" class="user-info">
                <span class="user-avatar remplisseur">{{ mission.filled_by_name.charAt(0) }}</span>
                <div>
                  <p class="user-name">{{ mission.filled_by_name }}</p>
                  <p class="user-email">{{ mission.filled_by_email }}</p>
                </div>
              </div>
              <span v-else class="status-badge pending">⏳ En attente</span>
            </td>
            <td class="status-cell">
              <span class="status-badge" :class="mission.status">
                {{ formatStatus(mission.status) }}
              </span>
            </td>
            <td class="dates-cell">
              <div class="date-range">
                <span class="date-label">Demande:</span>
                <span class="date-value">{{ formatDate(mission.requested_date) }}</span>
              </div>
              <div class="date-range" v-if="mission.start_date">
                <span class="date-label">Audit:</span>
                <span class="date-value">{{ formatDate(mission.start_date) }} - {{ formatDate(mission.end_date) }}</span>
              </div>
            </td>
            <td class="actions-cell">
              <a :href="`/m/audit.core/api/audit/mission-request/${mission.code}`" class="btn-action btn-view" title="Voir">
                👁️
              </a>
              <button 
                v-if="mission.status === 'draft'" 
                @click="copyShareLink(mission.share_code)" 
                class="btn-action btn-copy" 
                title="Copier lien remplissage"
              >
                🔗
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="missionRequests.data.length === 0" class="empty-state">
        <p class="empty-icon">📋</p>
        <p class="empty-message">Aucune demande de mission</p>
        <button @click="generateAndShareLink" class="btn btn-primary">
          ➕ Créer la première demande
        </button>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="missionRequests.links && missionRequests.links.length > 3" class="pagination-section">
      <div class="pagination-info">
        Affichage {{ missionRequests.from }} à {{ missionRequests.to }} sur {{ missionRequests.total }}
      </div>
      <div class="pagination-links">
        <a 
          v-for="link in missionRequests.links" 
          :key="link.label"
          :href="link.url"
          :class="['page-link', { active: link.active, disabled: !link.url }]"
          v-html="link.label"
        ></a>
      </div>
    </div>

    <!-- Modal: Lien généré -->
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header success">
          <h2>✅ Lien de formulaire généré!</h2>
        </div>
        <div class="modal-body">
          <div class="success-box">
            <p class="success-message">
              Partagez ce lien avec la personne qui doit créer une nouvelle demande de mission.
            </p>
            <p class="sub-message">
              ✅ Vous êtes enregistré comme générateur du lien
            </p>
          </div>
          
          <div class="link-section">
            <h3 class="link-title">🔗 Lien à partager</h3>
            <p class="link-description">
              Copiez et partagez ce lien:
            </p>
            <div class="link-box">
              <input 
                type="text" 
                :value="generatedLink" 
                readonly 
                class="link-input"
              />
              <button @click="copyGeneratedLink" class="btn-copy">
                📋 Copier
              </button>
            </div>
            <p class="link-info">
              ⓘ Le lien est valable indéfiniment. Votre nom sera enregistré comme générateur.
            </p>
          </div>

          <div class="modal-actions">
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
import { ref } from 'vue'

defineProps({
  missionRequests: Object,
})

const showModal = ref(false)
const generating = ref(false)
const generatedLink = ref('')

const truncate = (text, length) => {
  return text?.length > length ? text.substring(0, length) + '...' : text
}

const formatStatus = (status) => {
  const statuses = {
    'draft': '📝 Brouillon',
    'submitted': '📤 Soumis',
    'approved': '✅ Approuvé',
    'rejected': '❌ Rejeté',
  }
  return statuses[status] || status
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const copyShareLink = async (shareCode) => {
  const link = `${window.location.origin}/m/audit.core/api/audit/mission-request/${shareCode}/fill`
  try {
    await navigator.clipboard.writeText(link)
    alert('✅ Lien de remplissage copié!')
  } catch {
    alert('Erreur lors de la copie')
  }
}

const generateAndShareLink = async () => {
  generating.value = true
  try {
    const response = await fetch('/m/audit.core/api/audit/mission-requests/generate-link', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
    })

    const data = await response.json()

    if (data.success) {
      generatedLink.value = data.form_link
      showModal.value = true
    } else {
      alert('Erreur: ' + (data.error || 'Erreur inconnue'))
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur réseau: ' + error.message)
  } finally {
    generating.value = false
  }
}

const copyGeneratedLink = async () => {
  try {
    await navigator.clipboard.writeText(generatedLink.value)
    alert('✅ Lien copié!')
  } catch {
    alert('Erreur lors de la copie')
  }
}

const closeModal = () => {
  showModal.value = false
}
</script>

<style scoped>
:root {
  --primary: #1a237e;
  --primary-light: #283593;
  --success: #2e7d32;
  --warning: #f57c00;
  --danger: #d32f2f;
  --secondary: #455a64;
  --border: #cfd8dc;
  --light: #f5f7fa;
  --text: #212121;
}

.mission-index-wrapper {
  min-height: 100vh;
  background: var(--light);
  padding: 2rem;
}

.header-section {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-title {
  font-size: 1.8rem;
  font-weight: 600;
  color: var(--primary);
  margin: 0;
}

.page-subtitle {
  color: var(--secondary);
  font-size: 0.95rem;
  margin: 0.5rem 0 0 0;
}

.btn-lg {
  padding: 0.8rem 1.5rem;
  white-space: nowrap;
}

.table-container {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  max-width: 1400px;
  margin: 0 auto 2rem;
}

.missions-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.missions-table thead {
  background: var(--primary);
  color: white;
}

.missions-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
}

.missions-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--light);
}

.mission-row:hover {
  background: #fafafa;
}

.code-badge {
  display: inline-block;
  background: var(--primary-light);
  color: white;
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.85rem;
  font-family: monospace;
}

.objective-text {
  color: var(--text);
  font-weight: 500;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.9rem;
  color: white;
}

.user-avatar.generateur {
  background: var(--primary);
}

.user-avatar.remplisseur {
  background: var(--success);
}

.user-name {
  margin: 0;
  font-weight: 500;
  color: var(--text);
  font-size: 0.9rem;
}

.user-email {
  margin: 0;
  color: var(--secondary);
  font-size: 0.8rem;
}

.status-badge {
  display: inline-block;
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-badge.draft {
  background: #e3f2fd;
  color: #1976d2;
}

.status-badge.submitted {
  background: #fff3e0;
  color: #f57c00;
}

.status-badge.approved {
  background: #e8f5e9;
  color: #388e3c;
}

.status-badge.rejected {
  background: #ffebee;
  color: #d32f2f;
}

.status-badge.pending {
  background: #eeeeee;
  color: #666;
}

.date-range {
  margin-bottom: 0.3rem;
  display: flex;
  gap: 0.5rem;
  font-size: 0.85rem;
}

.date-label {
  color: var(--secondary);
  font-weight: 500;
}

.date-value {
  color: var(--text);
  font-weight: 600;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.2s;
  text-decoration: none;
  background: var(--light);
}

.btn-view:hover {
  background: var(--primary);
  color: white;
}

.btn-copy:hover {
  background: var(--primary);
  color: white;
}

.empty-state {
  padding: 3rem 2rem;
  text-align: center;
}

.empty-icon {
  font-size: 3rem;
  margin: 0;
}

.empty-message {
  color: var(--secondary);
  font-size: 1.1rem;
  margin: 1rem 0;
}

.pagination-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pagination-info {
  color: var(--secondary);
  font-size: 0.9rem;
}

.pagination-links {
  display: flex;
  gap: 0.5rem;
}

.page-link {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: 4px;
  text-decoration: none;
  color: var(--primary);
  transition: all 0.2s;
  font-size: 0.85rem;
}

.page-link.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.page-link.disabled {
  color: var(--border);
  cursor: not-allowed;
}

.page-link:hover:not(.disabled) {
  background: var(--primary-light);
  color: white;
}

.btn {
  padding: 0.7rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s;
  font-size: 0.9rem;
}

.btn-primary {
  background: var(--primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-light);
  box-shadow: 0 2px 8px rgba(26, 35, 126, 0.3);
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
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  animation: slideUp 0.4s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 2rem;
  background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
  color: white;
  border-radius: 12px 12px 0 0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.3rem;
}

.modal-body {
  padding: 2rem;
}

.success-box {
  background: var(--light);
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  border-left: 4px solid var(--success);
}

.success-message {
  color: var(--secondary);
  margin: 0;
  font-size: 0.95rem;
}

.sub-message {
  color: var(--success);
  margin: 0.75rem 0 0 0;
  font-size: 0.9rem;
  font-weight: 600;
}

.link-section {
  margin-bottom: 1.5rem;
}

.link-title {
  margin: 0 0 0.5rem 0;
  color: var(--primary);
  font-size: 0.95rem;
}

.link-description {
  color: var(--secondary);
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
}

.link-info {
  color: var(--secondary);
  font-size: 0.8rem;
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: #f0f0f0;
  border-radius: 4px;
  margin-bottom: 0;
}

.link-box {
  display: flex;
  gap: 0.5rem;
}

.link-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 2px solid var(--border);
  border-radius: 6px;
  font-size: 0.8rem;
  font-family: monospace;
  background: var(--light);
}

.btn-copy {
  padding: 0.75rem 1.5rem;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-copy:hover {
  background: var(--primary-light);
  box-shadow: 0 2px 8px rgba(26, 35, 126, 0.3);
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .mission-index-wrapper {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .page-title {
    font-size: 1.4rem;
  }

  .missions-table {
    font-size: 0.8rem;
  }

  .missions-table th,
  .missions-table td {
    padding: 0.5rem;
  }

  .objective-text,
  .user-name {
    display: none;
  }

  .pagination-section {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>