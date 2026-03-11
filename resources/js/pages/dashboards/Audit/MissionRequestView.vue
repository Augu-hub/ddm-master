<template>
  <div class="mission-view-wrapper">
    <!-- Header avec info utilisateur -->
    <div class="header-card">
      <div class="header-top">
        <div>
          <h1 class="title">📋 Demande de Mission d'Audit</h1>
          <p class="code">Code: <span class="badge-code">{{ missionRequest.code }}</span></p>
        </div>
        <div class="status-badge" :class="missionRequest.status">
          {{ formatStatus(missionRequest.status) }}
        </div>
      </div>

      <!-- Informations de l'utilisateur -->
      <div class="requester-section">
        <div class="requester-card">
          <div class="requester-avatar">{{ missionRequest.requester.name.charAt(0) }}</div>
          <div class="requester-info">
            <p class="requester-name">Créée par</p>
            <p class="requester-value">{{ missionRequest.requester.name }}</p>
          </div>
        </div>
        <div class="divider"></div>
        <div class="requester-card">
          <div class="icon">📧</div>
          <div class="requester-info">
            <p class="requester-name">Email</p>
            <p class="requester-value">{{ missionRequest.requester.email }}</p>
          </div>
        </div>
        <div class="divider"></div>
        <div class="requester-card">
          <div class="icon">📅</div>
          <div class="requester-info">
            <p class="requester-name">Date de Demande</p>
            <p class="requester-value">{{ formatDate(missionRequest.requested_date) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="content-grid">
      <!-- Détails de la Mission -->
      <div class="detail-card">
        <h2 class="card-title">📌 Informations Générales</h2>
        
        <div class="info-section">
          <div class="info-item">
            <label>Type de Mission</label>
            <p class="value">{{ missionRequest.mission_type }}</p>
          </div>
          <div class="info-item">
            <label>Source</label>
            <p class="value">{{ missionRequest.source.label }}</p>
          </div>
          <div class="info-item">
            <label>Fréquence</label>
            <p class="value">{{ missionRequest.frequency }}</p>
          </div>
        </div>
      </div>

      <!-- Objectif et Description -->
      <div class="detail-card">
        <h2 class="card-title">🎯 Objectif</h2>
        <p class="objective-text">{{ missionRequest.mission_objective }}</p>

        <div v-if="missionRequest.description" class="mt-4">
          <h3 class="subtitle">Description Détaillée</h3>
          <p class="description-text">{{ missionRequest.description }}</p>
        </div>

        <div v-if="missionRequest.concern" class="mt-4">
          <h3 class="subtitle">Préoccupations</h3>
          <p class="concern-text">{{ missionRequest.concern }}</p>
        </div>
      </div>

      <!-- Portée et Processus -->
      <div class="detail-card">
        <h2 class="card-title">📊 Portée et Contexte</h2>
        
        <div class="info-section">
          <div class="info-item">
            <label>Portée de l'Audit</label>
            <p class="value">{{ missionRequest.audit_scope || 'Non spécifiée' }}</p>
          </div>
          <div class="info-item">
            <label>Processus Concerné</label>
            <p class="value">{{ missionRequest.process?.name || 'Non spécifié' }}</p>
          </div>
          <div class="info-item">
            <label>Entité</label>
            <p class="value">{{ missionRequest.entity.name }}</p>
          </div>
        </div>
      </div>

      <!-- Planification -->
      <div class="detail-card">
        <h2 class="card-title">📅 Planification</h2>
        
        <div class="info-section">
          <div class="info-item">
            <label>Date de la Demande</label>
            <p class="value">{{ formatDate(missionRequest.requested_date) }}</p>
          </div>
          <div v-if="missionRequest.proposed_date" class="info-item">
            <label>Date Proposée</label>
            <p class="value">{{ formatDate(missionRequest.proposed_date) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="actions-section">
      <button class="btn btn-primary" @click="printPage">
        🖨️ Imprimer
      </button>
      <button class="btn btn-secondary" @click="goBack">
        ← Retour
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  missionRequest: Object,
})

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
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const printPage = () => {
  window.print()
}

const goBack = () => {
  window.history.back()
}
</script>

<style scoped>
:root {
  --primary: #2563eb;
  --secondary: #64748b;
  --success: #10b981;
  --danger: #ef4444;
  --light: #f8fafc;
  --border: #e2e8f0;
  --text: #1e293b;
}

.mission-view-wrapper {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
}

/* Header Card */
.header-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 2px solid var(--light);
}

.title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text);
  margin: 0;
}

.code {
  color: var(--secondary);
  margin: 0.5rem 0 0 0;
  font-size: 0.95rem;
}

.badge-code {
  background: var(--light);
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  font-family: monospace;
  font-weight: 600;
  color: var(--primary);
}

.status-badge {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
}

.status-badge.draft {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.submitted {
  background: #bfdbfe;
  color: #1e40af;
}

.status-badge.approved {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.rejected {
  background: #fee2e2;
  color: #7f1d1d;
}

/* Requester Section */
.requester-section {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.requester-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.requester-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.3rem;
}

.icon {
  font-size: 1.5rem;
  text-align: center;
  width: 48px;
}

.requester-name {
  color: var(--secondary);
  font-size: 0.85rem;
  margin: 0;
  font-weight: 500;
}

.requester-value {
  color: var(--text);
  font-weight: 600;
  margin: 0.3rem 0 0 0;
  font-size: 0.95rem;
}

.divider {
  width: 1px;
  height: 60px;
  background: var(--border);
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.detail-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text);
  margin: 0 0 1rem 0;
}

.subtitle {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text);
  margin: 1rem 0 0.5rem 0;
}

.info-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item label {
  color: var(--secondary);
  font-size: 0.85rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item .value {
  color: var(--text);
  font-weight: 600;
  margin-top: 0.3rem;
}

.objective-text,
.description-text,
.concern-text {
  color: var(--text);
  line-height: 1.6;
  margin: 0;
}

.objective-text {
  font-weight: 500;
  font-size: 1.05rem;
}

.description-text,
.concern-text {
  font-size: 0.95rem;
}

.mt-4 {
  margin-top: 1.5rem;
}

/* Actions */
.actions-section {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
}

.btn-primary {
  background: var(--primary);
  color: white;
}

.btn-primary:hover {
  background: #1d4ed8;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-secondary {
  background: var(--light);
  color: var(--text);
  border: 1px solid var(--border);
}

.btn-secondary:hover {
  background: #e2e8f0;
}

/* Print */
@media print {
  .mission-view-wrapper {
    background: white;
    padding: 0;
  }

  .actions-section {
    display: none;
  }

  .header-card,
  .detail-card {
    box-shadow: none;
    border: 1px solid var(--border);
    page-break-inside: avoid;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .header-top {
    flex-direction: column;
  }

  .requester-section {
    flex-direction: column;
  }

  .divider {
    display: none;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

  .actions-section {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>