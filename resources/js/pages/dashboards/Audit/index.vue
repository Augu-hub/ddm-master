<template>
<div class="container-fluid py-4">
  <!-- Bannière session active -->
  <div v-if="activeSession" class="alert alert-success mb-4 py-3 d-flex justify-content-between align-items-center border-start border-success border-4">
    <div>
      <h5 class="mb-0 fw-bold">✅ Session Active: <span class="badge bg-success ms-2">{{ activeSession.code }}</span></h5>
      <small class="text-muted">{{ activeSession.name }} - {{ activeSession.entity_name }}</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-outline-success" @click="switchSessionModal = true">🔄 Changer</button>
      <button class="btn btn-sm btn-outline-secondary" @click="reloadData">🔃 Recharger</button>
    </div>
  </div>
  <div v-else class="alert alert-danger mb-4">
    <h5 class="mb-2 fw-bold">❌ Aucune Session Active</h5>
    <p class="mb-0">Créez ou activez une session d'audit pour commencer.</p>
  </div>

  <!-- Alertes -->
  <div v-if="showAlert" class="alert mb-4" :class="alertClass" role="alert" style="animation: slideDown 0.3s ease;">
    <div class="d-flex justify-content-between align-items-center">
      <span>{{ alertMessage }}</span>
      <button type="button" class="btn-close" @click="showAlert = false"></button>
    </div>
  </div>

  <!-- En-tête -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="fw-bold text-danger mb-0">🔴 Gestion des Risques</h2>
      <small class="text-muted">Module Audit - Analyse Risques avec IA</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-danger" @click="openCreateModal" :disabled="loading || !activeSession">{{ loading ? '⏳' : '➕' }} Créer Risque</button>
      <button class="btn btn-secondary" @click="exportCsv" :disabled="filteredRisks.length === 0">📥 Export CSV</button>
    </div>
  </div>

  <!-- Filtres -->
  <div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-danger text-white fw-bold">📋 Filtres Dynamiques</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label fw-bold small">🏛️ Entité</label>
          <select v-model.number="filters.entityId" class="form-select form-select-sm" @change="applyFilters">
            <option :value="null">-- Toutes --</option>
            <option v-for="e in (entities || [])" :key="e.id" :value="e.id">{{ e.code_base }} - {{ e.name }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold small">⚙️ Processus</label>
          <select v-model.number="filters.processId" class="form-select form-select-sm" @change="applyFilters">
            <option :value="null">-- Tous --</option>
            <option v-for="p in (processes || [])" :key="p.id" :value="p.id">{{ p.code }} - {{ p.name }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold small">📌 Activité</label>
          <select v-model.number="filters.activityId" class="form-select form-select-sm" @change="applyFilters">
            <option :value="null">-- Toutes --</option>
            <option v-for="a in availableActivitiesFilter" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold small">🔍 Recherche</label>
          <input v-model="searchQuery" type="text" class="form-control form-control-sm" placeholder="Code, libellé..." @input="applyFilters">
        </div>
      </div>
    </div>
  </div>

  <!-- Tableau hiérarchique -->
  <div class="card border-0 shadow-sm" style="overflow-x: auto;">
    <div class="card-header bg-danger text-white py-2">
      <h6 class="fw-bold mb-0">📊 Tableau de Gestion des Risques ({{ filteredRisks.length }} risques)</h6>
    </div>
    <table class="table table-sm table-hover mb-0">
      <thead class="table-danger">
        <tr style="font-size: 0.8rem;">
          <th class="p-2" style="width: 12%;">PROCESSUS</th>
          <th class="p-2" style="width: 12%;">ACTIVITÉ</th>
          <th class="p-2" style="width: 28%;">RISQUES</th>
          <th class="p-2" style="width: 8%;">IMPACT</th>
          <th class="p-2" style="width: 8%;">FRÉQUENCE</th>
          <th class="p-2" style="width: 8%;">CRITICITÉ</th>
          <th class="p-2" style="width: 8%;">IMPACT NET</th>
          <th class="p-2" style="width: 8%;">FREQ NET</th>
          <th class="p-2" style="width: 8%;">ACTIONS</th>
        </tr>
      </thead>
      <tbody style="font-size: 0.75rem;">
        <template v-for="process in filteredProcessesWithActivities" :key="process.id">
          <tr class="process-header bg-light">
            <td class="p-2 fw-bold">
              <button @click="toggleProcess(process.id)" class="btn btn-sm btn-outline-danger p-0 me-2" style="width:20px;height:20px;font-size:0.7rem;">{{ expandedProcesses.includes(process.id) ? '▼' : '▶' }}</button>
              <strong>{{ process.code }}</strong><br><small class="text-muted">{{ truncate(process.name, 25) }}</small>
            </td>
            <td colspan="8"></td>
          </tr>
          <template v-if="expandedProcesses.includes(process.id)">
            <tr v-for="activity in process.activities" :key="activity.id" class="activity-row bg-white">
              <td class="p-2" style="vertical-align: top;">
                <button @click="toggleActivity(activity.id)" class="btn btn-sm btn-outline-secondary p-0 me-2" style="width:20px;height:20px;font-size:0.7rem;">{{ expandedActivities.includes(activity.id) ? '▼' : '▶' }}</button>
                <strong>{{ activity.code }}</strong><br><small class="text-muted">{{ truncate(activity.name, 20) }}</small>
              </td>
              <td class="p-2" style="vertical-align: top;">
                <div v-if="getRisksForActivity(activity.id).length > 0 && expandedActivities.includes(activity.id)" style="max-height:150px;overflow-y:auto;">
                  <div v-for="risk in getRisksForActivity(activity.id)" :key="risk.id" class="risk-item mb-2 p-2 rounded border-start border-3" :style="{ borderLeftColor: getRiskTypeColor(risk.risk_type_id), backgroundColor: '#f8f9fa' }">
                    <div class="d-flex justify-content-between align-items-start gap-1">
                      <div style="flex:1;min-width:0;">
                        <div class="d-flex gap-1 align-items-center mb-1">
                          <strong class="text-primary" style="font-size:0.7rem;">{{ risk.code }}</strong>
                          <span class="badge bg-secondary" style="font-size:0.6rem;">{{ risk.status }}</span>
                        </div>
                        <small class="text-dark d-block" style="font-size:0.7rem;">{{ truncate(risk.label,35) }}</small>
                        <div v-if="risk.owner" class="small text-muted mt-1" style="font-size:0.65rem;">👤 {{ truncate(risk.owner,12) }}</div>
                      </div>
                      <div class="d-flex gap-1 ms-1" style="flex-shrink:0;">
                        <button @click="duplicateRisk(risk)" class="btn btn-sm btn-outline-info p-0" style="width:20px;height:20px;font-size:0.55rem;" title="Dupliquer">📋</button>
                        <button @click="editRisk(risk)" class="btn btn-sm btn-outline-primary p-0" style="width:20px;height:20px;font-size:0.55rem;" title="Modifier">✏️</button>
                        <button @click="deleteRisk(risk)" class="btn btn-sm btn-outline-danger p-0" style="width:20px;height:20px;font-size:0.55rem;" title="Supprimer">🗑️</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else-if="!expandedActivities.includes(activity.id)" class="text-muted small p-1 text-center"><em style="font-size:0.7rem;">{{ getRisksForActivity(activity.id).length }} risque(s)</em></div>
                <div v-else class="text-muted small p-1 text-center"><em style="font-size:0.7rem;">Aucun risque</em></div>
                <button v-if="expandedActivities.includes(activity.id) && activeSession" @click="openAddRiskToActivity(activity, process)" class="btn btn-sm btn-outline-success mt-2 w-100" style="font-size:0.7rem;">➕ Ajouter</button>
              </td>
              <td class="p-2 text-center" style="vertical-align: top;">
                <div v-if="getActivityAverageImpact(activity.id)" class="badge fw-bold p-1" :style="{ backgroundColor: getActivityAverageImpactColor(activity.id), color: getTextColor(getActivityAverageImpactColor(activity.id)) }" style="font-size:0.65rem;display:inline-block;">{{ getActivityAverageImpactLabel(activity.id) }}</div>
                <small v-else class="text-muted">-</small>
              </td>
              <td class="p-2 text-center" style="vertical-align: top;">
                <div v-if="getActivityAverageFrequency(activity.id)" class="badge fw-bold p-1" :style="{ backgroundColor: getActivityAverageFrequencyColor(activity.id), color: getTextColor(getActivityAverageFrequencyColor(activity.id)) }" style="font-size:0.65rem;display:inline-block;">{{ getActivityAverageFrequencyLabel(activity.id) }}</div>
                <small v-else class="text-muted">-</small>
              </td>
              <td class="p-2 text-center" style="vertical-align: top;">
                <div v-if="getActivityCriticityGross(activity.id)" class="badge fw-bold p-1" :class="'bg-' + getCriticalityColor(getActivityCriticityGross(activity.id))" style="font-size:0.65rem;display:inline-block;">{{ getActivityCriticityGross(activity.id) }}</div>
                <small v-else class="text-muted">-</small>
              </td>
              <td class="p-2 text-center" style="vertical-align: top;">
                <div v-if="getActivityAverageImpactNet(activity.id)" class="badge bg-info fw-bold p-1" style="font-size:0.65rem;display:inline-block;">{{ getActivityAverageImpactNet(activity.id) }}</div>
                <small v-else class="text-muted">-</small>
              </td>
              <td class="p-2 text-center" style="vertical-align: top;">
                <div v-if="getActivityAverageFrequencyNet(activity.id)" class="badge bg-info fw-bold p-1" style="font-size:0.65rem;display:inline-block;">{{ getActivityAverageFrequencyNet(activity.id) }}</div>
                <small v-else class="text-muted">-</small>
              </td>
              <td class="p-2 text-center" style="vertical-align: top;"><button class="btn btn-sm btn-info px-1 py-0" style="font-size:0.6rem;" title="Détails">ℹ️</button></td>
            </tr>
          </template>
        </template>
        <tr v-if="filteredProcessesWithActivities.length === 0"><td colspan="9" class="p-4 text-center text-muted"><small>📭 Aucun processus trouvé</small></td></tr>
      </tbody>
    </table>
  </div>

  <!-- 🧠 Suggestions IA en bas de la liste -->
  <div v-if="suggestedRisks.length > 0" class="card mt-4 border-0 shadow-sm">
    <div class="card-header bg-info text-white py-2">
      <h6 class="fw-bold mb-0">🤖 Risques suggérés pour l'entité sélectionnée</h6>
    </div>
    <div class="card-body p-2">
      <div class="d-flex flex-wrap gap-2">
        <div v-for="risk in suggestedRisks" :key="risk.id" class="p-2 border rounded" style="background:#f0f8ff; width:300px;">
          <div class="d-flex justify-content-between">
            <strong class="text-primary small">{{ risk.code }}</strong>
            <button @click="duplicateRisk(risk)" class="btn btn-sm btn-outline-info py-0 px-1" title="Dupliquer ce risque">📋 Dupliquer</button>
          </div>
          <div class="small">{{ truncate(risk.label, 50) }}</div>
          <div class="small text-muted">
            {{ getProcessName(risk.process_id) }} > {{ getActivityName(risk.activity_id) }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL CRÉATION / MODIFICATION / DUPLICATION RISQUE -->
  <div v-if="showModal" class="modal d-block" style="background:rgba(0,0,0,0.6);z-index:1050;">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-height:90vh;">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-danger text-white border-0 py-3">
          <h5 class="modal-title fw-bold">{{ isDuplicating ? '📋 Dupliquer le Risque' : (editingId ? '✏️ Modifier le Risque' : '➕ Créer un Nouveau Risque') }}</h5>
          <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
        </div>
        <div class="modal-body" style="max-height:calc(90vh - 140px);overflow-y:auto;padding:1rem;">

          <!-- ÉTAPE 1: CONTEXTE -->
          <div class="section-title mb-3"><h6 class="fw-bold text-danger mb-2"><span class="badge bg-danger me-2">1</span> Contexte</h6></div>
          <div class="row g-2 mb-3 p-2 bg-light rounded" style="font-size:0.85rem;">
            <div class="col-md-6">
              <label class="form-label fw-bold small mb-1">Entité</label>
              <select v-model.number="form.entity_id" class="form-select form-select-sm">
                <option :value="null">-- Sélectionner --</option>
                <option v-for="e in (entities || [])" :key="e.id" :value="e.id">{{ e.code_base }} - {{ e.name }}</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small mb-1">Processus</label>
              <select v-model.number="form.process_id" @change="onProcessChange" class="form-select form-select-sm">
                <option :value="null">-- Sélectionner --</option>
                <option v-for="p in (processes || [])" :key="p.id" :value="p.id">{{ p.code }} - {{ p.name }}</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small mb-1">Activité</label>
              <select v-model.number="form.activity_id" @change="onActivityChange" class="form-select form-select-sm">
                <option :value="null">-- Sélectionner --</option>
                <option v-for="a in availableActivitiesModal" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small mb-1">Type Risque *</label>
              <select v-model.number="form.risk_type_id" @change="onRiskTypeChange" class="form-select form-select-sm">
                <option :value="null">-- Sélectionner --</option>
                <option v-for="t in (riskTypes || [])" :key="t.id" :value="t.id">{{ t.code }} - {{ t.label }}</option>
              </select>
            </div>
            <div v-if="selectedRiskType" class="col-12">
              <div class="p-2 rounded text-center small fw-bold" :style="{ backgroundColor: getRiskTypeColor(selectedRiskType.id), color: getTextColor(getRiskTypeColor(selectedRiskType.id)) }">{{ selectedRiskType.code }} - {{ selectedRiskType.label }}</div>
            </div>
          </div>

          <!-- ÉTAPE 2: SUGGESTIONS IA -->
          <div v-if="form.process_id && form.activity_id && form.risk_type_id" class="mb-3">
            <div class="section-title mb-2"><h6 class="fw-bold text-danger mb-2"><span class="badge bg-danger me-2">2</span> 🤖 Suggestions IA</h6></div>
            <div v-if="aiLoading" class="alert alert-info mb-2 py-2" style="font-size:0.8rem;">
              <div class="spinner-border spinner-border-sm me-2" style="width:1rem;height:1rem;"></div>
              <small>⏳ Génération suggestions IA...</small>
            </div>
            <div v-else-if="aiSuggestions && aiSuggestions.length > 0" class="card mb-2 border-info shadow-sm">
              <div class="card-body p-2" style="max-height:220px;overflow-y:auto;">
                <div class="list-group list-group-flush">
                  <button
                    v-for="(suggestion, idx) in aiSuggestions"
                    :key="idx"
                    type="button"
                    @click="selectRiskSuggestion(suggestion)"
                    class="list-group-item list-group-item-action text-start p-2 border-bottom"
                    :class="{ 'active bg-danger text-white': form.label === (typeof suggestion === 'object' ? suggestion.label : suggestion) }"
                    style="cursor:pointer;transition:all 0.2s;font-size:0.75rem;"
                  >
                    <small>{{ typeof suggestion === 'object' ? suggestion.label : suggestion }}</small>
                  </button>
                </div>
              </div>
            </div>
            <div v-else-if="!aiLoading" class="text-muted small">
              <em>Aucune suggestion disponible</em>
            </div>
          </div>

          <!-- ÉTAPE 3: CODE AUTO -->
          <div class="section-title mb-3"><h6 class="fw-bold text-danger mb-2"><span class="badge bg-danger me-2">3</span> Code (Auto)</h6></div>
          <div class="row g-2 mb-3 p-2 bg-light rounded">
            <div class="col-12">
              <input v-model="form.code" type="text" class="form-control form-control-sm fw-bold" readonly style="background-color:#e9ecef;font-size:1rem;letter-spacing:2px;" placeholder="Auto">
              <small class="text-muted d-block mt-1">Format: [TYPE]-[SEQ] (ex: RF-001)</small>
            </div>
          </div>

          <!-- ÉTAPE 4: DÉTAILS -->
          <div class="section-title mb-3"><h6 class="fw-bold text-danger mb-2"><span class="badge bg-danger me-2">4</span> Détails</h6></div>
          <div class="row g-2 mb-3 p-2 bg-light rounded" style="font-size:0.85rem;">
            <div class="col-12"><label class="form-label fw-bold small mb-1">Libellé *</label><input v-model="form.label" type="text" class="form-control form-control-sm" required></div>
            <div class="col-12"><label class="form-label fw-bold small mb-1">Description</label><textarea v-model="form.description" class="form-control form-control-sm" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label fw-bold small mb-1">Propriétaire</label><input v-model="form.owner" type="text" class="form-control form-control-sm"></div>
            <div class="col-md-6">
              <label class="form-label fw-bold small mb-1">Statut</label>
              <select v-model="form.status" class="form-select form-select-sm">
                <option value="identified">🔵 Identifié</option>
                <option value="assessed">🟡 Évalué</option>
                <option value="mitigated">🟢 Atténué</option>
                <option value="monitored">📊 Suivi</option>
                <option value="closed">⚫ Fermé</option>
              </select>
            </div>
          </div>

          <!-- ÉTAPE 5: ÉVALUATION BRUTE -->
          <div class="section-title mb-3"><h6 class="fw-bold text-danger mb-2"><span class="badge bg-danger me-2">5</span> Évaluation Brute</h6></div>
          <div class="row g-2 mb-3 p-2 bg-light rounded" style="font-size:0.85rem;">
            <div class="col-md-6">
              <label class="form-label fw-bold small mb-1">Fréquence *</label>
              <select v-model.number="form.frequency_level_id" class="form-select form-select-sm">
                <option :value="null">-- Sélectionner --</option>
                <option v-for="f in (frequencies || [])" :key="f.id" :value="f.id">{{ f.label }} ({{ f.level }}/5)</option>
              </select>
              <div v-if="frequencyLevel" class="mt-2 p-2 rounded text-center small fw-bold" :style="{ backgroundColor: frequencyLevel.color, color: getTextColor(frequencyLevel.color) }">{{ frequencyLevel.label }} - {{ frequencyLevel.code }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small mb-1">Impact *</label>
              <select v-model.number="form.impact_level_id" class="form-select form-select-sm">
                <option :value="null">-- Sélectionner --</option>
                <option v-for="i in (impacts || [])" :key="i.id" :value="i.id">{{ i.label }} ({{ i.level }}/5)</option>
              </select>
              <div v-if="impactLevel" class="mt-2 p-2 rounded text-center small fw-bold" :style="{ backgroundColor: impactLevel.color, color: getTextColor(impactLevel.color) }">{{ impactLevel.label }} - {{ impactLevel.code }}</div>
            </div>
            <div v-if="criticityBrut" class="col-12">
              <div class="p-2 rounded text-white text-center small fw-bold" :style="{ backgroundColor: getMatrixCellColor(impactLevel?.level, frequencyLevel?.level) }">Criticité Brute: <strong>{{ criticityBrut }}</strong> - {{ getCriticalityName(criticityBrut) }}</div>
            </div>
          </div>

          <!-- ÉTAPE 6: ÉVALUATION NETTE -->
          <div class="section-title mb-3"><h6 class="fw-bold text-danger mb-2"><span class="badge bg-danger me-2">6</span> Évaluation Nette</h6></div>
          <div class="row g-2 mb-3 p-2 bg-light rounded" style="font-size:0.85rem;">
            <div class="col-md-6">
              <label class="form-label fw-bold small mb-1">Fréquence Nette (0-5)</label>
              <input v-model.number="form.frequency_net" type="number" min="0" max="5" step="0.5" class="form-control form-control-sm">
              <small v-if="form.frequency_net" class="text-muted d-block mt-1">{{ getFrequencyNetName(form.frequency_net) }}</small>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small mb-1">Impact Net (0-5)</label>
              <input v-model.number="form.impact_net" type="number" min="0" max="5" step="0.5" class="form-control form-control-sm">
              <small v-if="form.impact_net" class="text-muted d-block mt-1">{{ getImpactNetName(form.impact_net) }}</small>
            </div>
            <div v-if="criticityNet" class="col-12">
              <div class="p-2 rounded text-center small fw-bold" :style="{ backgroundColor: getCriticalityColorBg(criticityNet), color: 'white' }">Criticité Nette: <strong>{{ criticityNet }}</strong> - {{ getCriticalityName(criticityNet) }}</div>
            </div>
          </div>

          <!-- ÉTAPE 7: PROCÉDURE CONTRÔLE -->
          <div class="section-title mb-3"><h6 class="fw-bold text-danger mb-2"><span class="badge bg-danger me-2">7</span> Procédure Contrôle</h6></div>
          <div class="p-2 bg-light rounded mb-3">
            <div class="input-group">
              <textarea v-model="form.control_procedure" class="form-control form-control-sm" rows="2"></textarea>
              <button @click="generateControlProcedure" class="btn btn-sm btn-outline-info" type="button" title="Générer avec IA" :disabled="!form.label || !form.process_id || !form.activity_id">🤖</button>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button @click="closeModal" type="button" class="btn btn-secondary btn-sm">❌ Annuler</button>
          <button
            @click="saveRisk"
            type="button"
            class="btn btn-danger btn-sm"
            :disabled="!form.label || !form.risk_type_id || !form.frequency_level_id || !form.impact_level_id || loading"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            {{ loading ? '⏳ Enregistrement...' : '💾 ' + (editingId && !isDuplicating ? 'Modifier' : (isDuplicating ? 'Dupliquer' : 'Créer')) }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL CHANGEMENT SESSION -->
  <div v-if="switchSessionModal" class="modal d-block" style="background:rgba(0,0,0,0.6);z-index:1050;">
    <div class="modal-dialog modal-md">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-info text-white border-0">
          <h5 class="modal-title fw-bold">🔄 Sélectionner Session</h5>
          <button type="button" class="btn-close btn-close-white" @click="switchSessionModal = false"></button>
        </div>
        <div class="modal-body">
          <div class="list-group">
            <button v-for="session in (allSessions || [])" :key="session.id" type="button" @click="doSwitchSession(session.id)" class="list-group-item list-group-item-action text-start p-3" :class="{ 'active bg-info': session.is_active }">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="mb-1 fw-bold">{{ session.code }}</h6>
                  <small class="text-muted">{{ session.name }}</small><br>
                  <small class="badge" :class="'bg-' + (session.is_active ? 'info' : 'secondary')">{{ session.is_active ? '✅ Active' : '⏸️ Inactive' }}</small>
                </div>
                <div class="text-end">
                  <div class="badge bg-danger">{{ session.risks_count }} risques</div>
                  <div class="badge bg-warning text-dark" v-if="session.critical_count > 0">{{ session.critical_count }} critiques</div>
                </div>
              </div>
            </button>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="switchSessionModal = false" type="button" class="btn btn-secondary btn-sm">Fermer</button>
        </div>
      </div>
    </div>
  </div>
</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  activeSession:  { type: Object, default: () => null },
  allSessions:    { type: Array,  default: () => [] },
  entities:       { type: Array,  default: () => [] },
  processes:      { type: Array,  default: () => [] },
  activities:     { type: Array,  default: () => [] },
  riskTypes:      { type: Array,  default: () => [] },
  frequencies:    { type: Array,  default: () => [] },
  impacts:        { type: Array,  default: () => [] },
  matrix:         { type: Array,  default: () => [] },
  initialRisks:   { type: Array,  default: () => [] },
})

// ─── État ────────────────────────────────────────────────────────────────────
const allRisks          = ref([])
const showModal         = ref(false)
const switchSessionModal = ref(false)
const editingId         = ref(null)
const isDuplicating     = ref(false)
const loading           = ref(false)
const aiLoading         = ref(false)
const searchQuery       = ref('')
const aiSuggestions     = ref([])
const showAlert         = ref(false)
const alertMessage      = ref('')
const alertClass        = ref('alert-success')
const filters           = ref({ entityId: null, processId: null, activityId: null })

const form = ref({
  code: '', label: '', description: '',
  risk_type_id: null, frequency_level_id: null, frequency_net: null,
  impact_level_id: null, impact_net: null,
  entity_id: null, process_id: null, activity_id: null,
  owner: '', control_procedure: '', status: 'identified',
})

const expandedProcesses  = ref([])
const expandedActivities = ref([])

// ─── CSRF ─────────────────────────────────────────────────────────────────────
const getCsrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

// ─── Computed ─────────────────────────────────────────────────────────────────
const suggestedRisks = computed(() => {
  if (!filters.value.entityId) return []
  return allRisks.value.filter(r => r.entity_id === filters.value.entityId && r.id !== editingId.value)
})

// Activités disponibles dans les filtres (selon processus sélectionné)
const availableActivitiesFilter = computed(() => {
  if (!filters.value.processId) return props.activities || []
  return (props.activities || []).filter(a => a.process_id === filters.value.processId)
})

// Activités disponibles dans le modal (selon processus du formulaire)
const availableActivitiesModal = computed(() => {
  if (!form.value.process_id) return props.activities || []
  return (props.activities || []).filter(a => a.process_id === form.value.process_id)
})

const filteredRisks = computed(() => {
  return (allRisks.value || []).filter(r => {
    const matchEntity   = !filters.value.entityId   || r.entity_id   === filters.value.entityId
    const matchProcess  = !filters.value.processId  || r.process_id  === filters.value.processId
    const matchActivity = !filters.value.activityId || r.activity_id === filters.value.activityId
    const q = searchQuery.value.toLowerCase()
    const matchSearch   = !q || (r.code||'').toLowerCase().includes(q) || (r.label||'').toLowerCase().includes(q)
    return matchEntity && matchProcess && matchActivity && matchSearch
  })
})

const filteredProcessesWithActivities = computed(() => {
  let filtered = props.processes || []
  if (filters.value.entityId)  filtered = filtered.filter(p => p.entity_id === filters.value.entityId)
  if (filters.value.processId) filtered = filtered.filter(p => p.id === filters.value.processId)

  return filtered.map(process => {
    let acts = (props.activities || []).filter(a => a.process_id === process.id)
    if (filters.value.activityId) acts = acts.filter(a => a.id === filters.value.activityId)
    return { ...process, activities: acts }
  }).filter(process => {
    // Garder seulement les processus qui ont des activités avec des risques filtrés
    return process.activities.some(a => filteredRisks.value.some(r => r.activity_id === a.id))
      || process.activities.length > 0 // afficher aussi les processus vides quand on est en création
  })
})

const selectedRiskType = computed(() =>
  form.value.risk_type_id ? (props.riskTypes || []).find(t => t.id === form.value.risk_type_id) : null
)
const frequencyLevel = computed(() =>
  form.value.frequency_level_id ? (props.frequencies || []).find(f => f.id === form.value.frequency_level_id) : null
)
const impactLevel = computed(() =>
  form.value.impact_level_id ? (props.impacts || []).find(i => i.id === form.value.impact_level_id) : null
)
const criticityBrut = computed(() =>
  (frequencyLevel.value && impactLevel.value) ? frequencyLevel.value.level * impactLevel.value.level : null
)
const criticityNet = computed(() =>
  (form.value.frequency_net && form.value.impact_net)
    ? Math.round(form.value.frequency_net * form.value.impact_net) : null
)

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(() => { allRisks.value = [...(props.initialRisks || [])] })

// ─── Alertes ─────────────────────────────────────────────────────────────────
const showSuccessAlert = msg => { alertMessage.value = msg; alertClass.value = 'alert-success'; showAlert.value = true; setTimeout(() => showAlert.value = false, 4000) }
const showErrorAlert   = msg => { alertMessage.value = msg; alertClass.value = 'alert-danger';  showAlert.value = true; setTimeout(() => showAlert.value = false, 6000) }

// ─── Helpers fetch ────────────────────────────────────────────────────────────
const postJson = async (url, body) => {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
    body: JSON.stringify(body),
  })
  const text = await res.text()
  let data
  try { data = JSON.parse(text) } catch { throw new Error('Réponse non JSON: ' + text.substring(0, 150)) }
  if (!res.ok) throw new Error(data?.error || data?.message || `Erreur ${res.status}`)
  return data
}

const putJson = async (url, body) => {
  const res = await fetch(url, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
    body: JSON.stringify(body),
  })
  const text = await res.text()
  let data
  try { data = JSON.parse(text) } catch { throw new Error('Réponse non JSON: ' + text.substring(0, 150)) }
  if (!res.ok) throw new Error(data?.error || data?.message || `Erreur ${res.status}`)
  return data
}

const deleteReq = async (url) => {
  const res = await fetch(url, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
  })
  const text = await res.text()
  let data
  try { data = JSON.parse(text) } catch { throw new Error('Réponse non JSON: ' + text.substring(0, 150)) }
  if (!res.ok) throw new Error(data?.error || data?.message || `Erreur ${res.status}`)
  return data
}

// ─── Session ──────────────────────────────────────────────────────────────────
const reloadData = () => window.location.reload()

const doSwitchSession = async (sessionId) => {
  try {
    // ✅ Route correcte dans web.php: POST /api/settings/sessions/{id} -> updateSession
    // Ou utiliser la route switch-session si elle est ajoutée
    // Pour l'instant on recharge la page après avoir mis à jour le statut
    await putJson(`/api/settings/sessions/${sessionId}`, { status: 'active' })
    switchSessionModal.value = false
    location.reload()
  } catch (error) {
    console.error('Erreur switch session:', error)
    showErrorAlert('❌ Erreur lors du changement de session: ' + error.message)
  }
}

// ─── Code auto ───────────────────────────────────────────────────────────────
const generateAutoCode = () => {
  if (!selectedRiskType.value?.code) return ''
  const typeCode = selectedRiskType.value.code.substring(0, 2).toUpperCase()
  const sameTypeRisks = allRisks.value.filter(r => r.risk_type_id === form.value.risk_type_id)
  let maxSeq = 0
  sameTypeRisks.forEach(r => {
    const m = (r.code || '').match(/-(\d+)$/)
    if (m) { const n = parseInt(m[1]); if (n > maxSeq) maxSeq = n }
  })
  return `${typeCode}-${String(maxSeq + 1).padStart(3, '0')}`
}

// ─── Handlers changements formulaire ─────────────────────────────────────────
const onProcessChange = () => {
  form.value.activity_id = null
  onActivityChange()
}

const onActivityChange = () => {
  triggerAISuggestions()
}

const onRiskTypeChange = () => {
  form.value.code = generateAutoCode()
  triggerAISuggestions()
}

// ─── IA Suggestions ──────────────────────────────────────────────────────────
const triggerAISuggestions = async () => {
  form.value.code = generateAutoCode()
  if (!form.value.process_id || !form.value.activity_id || !form.value.risk_type_id) {
    aiSuggestions.value = []
    return
  }
  aiLoading.value = true
  try {
    const processName  = (props.processes  || []).find(p => p.id === form.value.process_id)?.name  || ''
    const activityName = (props.activities || []).find(a => a.id === form.value.activity_id)?.name || ''
    const riskTypeName = (props.riskTypes  || []).find(t => t.id === form.value.risk_type_id)?.label || ''

    // ✅ Route correcte: POST /api/risque/ai/suggest-risks -> suggestRisksAI
    const data = await postJson('/api/risque/ai/suggest-risks', {
      process_name:   processName,
      activity_name:  activityName,
      risk_type_name: riskTypeName,
    })
    // Le service retourne soit { suggestions: [...strings] } soit { suggestions: [...objects] }
    aiSuggestions.value = data.suggestions || []
  } catch (error) {
    console.error('Erreur IA suggestions:', error)
    aiSuggestions.value = []
  } finally {
    aiLoading.value = false
  }
}

const selectRiskSuggestion = (suggestion) => {
  form.value.label = typeof suggestion === 'object' ? suggestion.label : suggestion
}

// ─── Génération procédure contrôle ────────────────────────────────────────────
const generateControlProcedure = async () => {
  if (!form.value.label || !form.value.activity_id || !form.value.process_id) return
  try {
    const processName  = (props.processes  || []).find(p => p.id === form.value.process_id)?.name  || ''
    const activityName = (props.activities || []).find(a => a.id === form.value.activity_id)?.name || ''

    // ✅ Route correcte: POST /api/risque/{risk}/ai/suggest-controls
    // Mais comme on n'a pas toujours d'ID, utiliser une route sans ID
    // → On adapte: appel direct avec les paramètres nécessaires
    // Si editingId existe: /api/risque/{id}/ai/suggest-controls
    // Sinon: fallback local
    if (editingId.value && !isDuplicating.value) {
      const data = await postJson(`/api/risque/${editingId.value}/ai/suggest-controls`, {
        risk_label:    form.value.label,
        activity_name: activityName,
        process_name:  processName,
      })
      form.value.control_procedure = data.control_procedure || ''
    } else {
      // Génération locale pour les nouveaux risques
      form.value.control_procedure =
        `Vérifier et valider le processus "${processName}" - Activité "${activityName}": ` +
        `contrôle indépendant, documentation et archivage des preuves pour le risque "${form.value.label}".`
    }
  } catch (error) {
    console.error('Erreur génération procédure:', error)
    showErrorAlert('❌ Erreur génération procédure: ' + error.message)
  }
}

// ─── Sauvegarde risque ────────────────────────────────────────────────────────
const saveRisk = async () => {
  if (!form.value.label || !form.value.risk_type_id || !form.value.frequency_level_id || !form.value.impact_level_id) {
    showErrorAlert('❌ Champs obligatoires manquants: Libellé, Type, Fréquence, Impact')
    return
  }

  // Générer code si absent
  if (!form.value.code) {
    form.value.code = generateAutoCode()
  }

  try {
    loading.value = true
    const isUpdate = editingId.value && !isDuplicating.value
    let data

    if (isUpdate) {
      // ✅ Route: PUT /api/risque/{risk}
      data = await putJson(`/api/risque/${editingId.value}`, form.value)
      const idx = allRisks.value.findIndex(r => r.id === editingId.value)
      if (idx >= 0) allRisks.value[idx] = data.risk || data
      showSuccessAlert(`✅ Risque '${data.risk?.code || form.value.code}' modifié avec succès`)
    } else {
      // ✅ Route: POST /api/risque
      data = await postJson('/api/risque', form.value)
      allRisks.value.push(data.risk || data)
      showSuccessAlert(`✅ Risque '${data.risk?.code || form.value.code}' ${isDuplicating.value ? 'dupliqué' : 'créé'} avec succès`)
    }
    closeModal()
  } catch (error) {
    console.error('Erreur saveRisk:', error)
    showErrorAlert(`❌ ${error.message || 'Erreur lors de l\'enregistrement'}`)
  } finally {
    loading.value = false
  }
}

// ─── Suppression ──────────────────────────────────────────────────────────────
const deleteRisk = async (risk) => {
  if (!confirm(`Supprimer le risque ${risk.code} ?`)) return
  try {
    // ✅ Route: DELETE /api/risque/{risk}
    await deleteReq(`/api/risque/${risk.id}`)
    allRisks.value = allRisks.value.filter(r => r.id !== risk.id)
    showSuccessAlert(`✅ Risque '${risk.code}' supprimé`)
  } catch (error) {
    console.error('Erreur delete:', error)
    showErrorAlert('❌ Erreur suppression: ' + error.message)
  }
}

// ─── Duplication ──────────────────────────────────────────────────────────────
const duplicateRisk = (risk) => {
  resetForm()
  const { id, code, audit_session_id, created_at, updated_at, ...rest } = risk
  Object.assign(form.value, rest)
  form.value.code   = ''
  form.value.status = 'identified'
  editingId.value   = null
  isDuplicating.value = true
  aiSuggestions.value = []
  showModal.value   = true
  setTimeout(() => { form.value.code = generateAutoCode() }, 100)
}

// ─── Édition ──────────────────────────────────────────────────────────────────
const editRisk = (risk) => {
  resetForm()
  editingId.value     = risk.id
  isDuplicating.value = false
  Object.assign(form.value, risk)
  aiSuggestions.value = []
  showModal.value     = true
}

// ─── Ajout depuis activité ────────────────────────────────────────────────────
const openAddRiskToActivity = (activity, process) => {
  resetForm()
  form.value.process_id  = process.id
  form.value.activity_id = activity.id
  editingId.value        = null
  isDuplicating.value    = false
  aiSuggestions.value    = []
  showModal.value        = true
  triggerAISuggestions()
}

// ─── Création générale ────────────────────────────────────────────────────────
const openCreateModal = () => {
  resetForm()
  editingId.value     = null
  isDuplicating.value = false
  aiSuggestions.value = []
  showModal.value     = true
}

const closeModal = () => {
  showModal.value     = false
  editingId.value     = null
  isDuplicating.value = false
  aiSuggestions.value = []
  loading.value       = false
  aiLoading.value     = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    code: '', label: '', description: '',
    risk_type_id: null, frequency_level_id: null, frequency_net: null,
    impact_level_id: null, impact_net: null,
    entity_id: null, process_id: null, activity_id: null,
    owner: '', control_procedure: '', status: 'identified',
  }
}

// ─── Filtres ──────────────────────────────────────────────────────────────────
const applyFilters = () => {} // les computed font le travail

// ─── Tableau helpers ──────────────────────────────────────────────────────────
const toggleProcess  = id => { const i = expandedProcesses.value.indexOf(id);  i >= 0 ? expandedProcesses.value.splice(i, 1)  : expandedProcesses.value.push(id) }
const toggleActivity = id => { const i = expandedActivities.value.indexOf(id); i >= 0 ? expandedActivities.value.splice(i, 1) : expandedActivities.value.push(id) }
const getRisksForActivity = actId => filteredRisks.value.filter(r => r.activity_id === actId)

const getProcessName  = id => (props.processes  || []).find(p => p.id === id)?.name || '-'
const getActivityName = id => (props.activities || []).find(a => a.id === id)?.name || '-'

// ─── Calculs activité ─────────────────────────────────────────────────────────
const getActivityAverageImpact = actId => {
  const risks = getRisksForActivity(actId)
  if (!risks.length) return null
  const levels = risks.map(r => (props.impacts || []).find(i => i.id === r.impact_level_id)?.level || 0)
  return levels.reduce((a, b) => a + b, 0) / levels.length
}
const getActivityAverageImpactColor = actId => {
  const avg = getActivityAverageImpact(actId)
  if (!avg) return '#6c757d'
  return (props.impacts || []).find(i => i.level === Math.round(avg))?.color || '#6c757d'
}
const getActivityAverageImpactLabel = actId => {
  const avg = getActivityAverageImpact(actId)
  if (!avg) return '-'
  return avg >= 4.5 ? 'Très Élevé' : avg >= 3.5 ? 'Élevé' : avg >= 2.5 ? 'Important' : avg >= 1.5 ? 'Modéré' : 'Faible'
}
const getActivityAverageFrequency = actId => {
  const risks = getRisksForActivity(actId)
  if (!risks.length) return null
  const levels = risks.map(r => (props.frequencies || []).find(f => f.id === r.frequency_level_id)?.level || 0)
  return levels.reduce((a, b) => a + b, 0) / levels.length
}
const getActivityAverageFrequencyColor = actId => {
  const avg = getActivityAverageFrequency(actId)
  if (!avg) return '#6c757d'
  return (props.frequencies || []).find(f => f.level === Math.round(avg))?.color || '#6c757d'
}
const getActivityAverageFrequencyLabel = actId => {
  const avg = getActivityAverageFrequency(actId)
  if (!avg) return '-'
  return avg >= 3.5 ? 'Certain' : avg >= 2.5 ? 'Fréquent' : avg >= 1.5 ? 'Probable' : 'Rare'
}
const getActivityCriticityGross = actId => {
  const risks = getRisksForActivity(actId)
  if (!risks.length) return null
  const sum = risks.reduce((s, r) => {
    const f = (props.frequencies || []).find(f => f.id === r.frequency_level_id)?.level || 0
    const i = (props.impacts     || []).find(i => i.id === r.impact_level_id)?.level    || 0
    return s + f * i
  }, 0)
  return Math.round(sum / risks.length)
}
const getActivityAverageImpactNet = actId => {
  const risks  = getRisksForActivity(actId)
  const values = risks.filter(r => r.impact_net).map(r => r.impact_net)
  return values.length ? (values.reduce((a, b) => a + b, 0) / values.length).toFixed(1) : '-'
}
const getActivityAverageFrequencyNet = actId => {
  const risks  = getRisksForActivity(actId)
  const values = risks.filter(r => r.frequency_net).map(r => r.frequency_net)
  return values.length ? (values.reduce((a, b) => a + b, 0) / values.length).toFixed(1) : '-'
}

// ─── Export CSV ───────────────────────────────────────────────────────────────
const exportCsv = () => {
  const rows = [['CODE', 'LIBELLÉ', 'PROCESSUS', 'ACTIVITÉ', 'TYPE', 'FRÉQUENCE', 'IMPACT', 'CRITICITÉ', 'STATUT']]
  filteredRisks.value.forEach(r => {
    const f = (props.frequencies || []).find(f => f.id === r.frequency_level_id)
    const i = (props.impacts     || []).find(i => i.id === r.impact_level_id)
    rows.push([
      r.code, r.label,
      getProcessName(r.process_id), getActivityName(r.activity_id),
      (props.riskTypes || []).find(t => t.id === r.risk_type_id)?.code || '-',
      f?.label || '-', i?.label || '-',
      (f?.level && i?.level) ? f.level * i.level : '-',
      r.status || '-',
    ])
  })
  const text = rows.map(row => row.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n')
  const blob = new Blob(['\uFEFF' + text], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `risques_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
}

// ─── Couleurs & helpers UI ────────────────────────────────────────────────────
const getRiskTypeColor = id => (props.riskTypes || []).find(t => t.id === id)?.color || '#6c757d'

const getMatrixCellColor = (impLvl, freqLvl) => {
  if (!impLvl || !freqLvl) return '#6c757d'
  return (props.matrix || []).find(m => m.impact_level === impLvl && m.frequency_level === freqLvl)?.color || '#6c757d'
}

const getCriticalityColor   = c => c >= 12 ? 'danger' : c >= 8 ? 'warning' : c > 0 ? 'success' : 'secondary'
const getCriticalityColorBg = c => { if (c >= 16) return '#dc3545'; if (c >= 12) return '#dc3545'; if (c >= 8) return '#ffc107'; if (c > 0) return '#28a745'; return '#6c757d' }
const getCriticalityName    = c => { if (!c) return '-'; if (c <= 4) return 'Faible'; if (c <= 9) return 'Moyen'; if (c <= 16) return 'Élevé'; return 'Critique' }
const getFrequencyNetName   = v => { if (!v) return '-'; if (v <= 1) return 'Très rare'; if (v <= 2) return 'Rare'; if (v <= 3) return 'Occasionnel'; if (v <= 4) return 'Fréquent'; return 'Très fréquent' }
const getImpactNetName      = v => { if (!v) return '-'; if (v <= 1) return 'Négligeable'; if (v <= 2) return 'Faible'; if (v <= 3) return 'Modéré'; if (v <= 4) return 'Majeur'; return 'Critique' }

const getTextColor = bgColor => {
  if (!bgColor || !bgColor.startsWith('#')) return '#000'
  try {
    const r = parseInt(bgColor.substring(1, 3), 16)
    const g = parseInt(bgColor.substring(3, 5), 16)
    const b = parseInt(bgColor.substring(5, 7), 16)
    return (r * 299 + g * 587 + b * 114) / 1000 > 128 ? '#000' : '#fff'
  } catch { return '#000' }
}

const truncate = (text, len) => {
  if (!text) return '-'
  return text.length > len ? text.substring(0, len) + '...' : text
}
</script>

<style scoped>
.modal { position: fixed; left: 0; top: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
.section-title { padding-bottom: 0.5rem; border-bottom: 2px solid #f0f0f0; }
.risk-item { transition: all 0.2s ease; }
.risk-item:hover { box-shadow: 0 2px 4px rgba(0,0,0,0.1); transform: translateX(2px); }
.process-header { background-color: #f8f9fa !important; }
.activity-row { border-left: 3px solid #e9ecef; }
.activity-row:hover { background-color: #f9f9f9 !important; }
.list-group-item.active { background-color: #dc3545 !important; border-color: #dc3545 !important; color: white !important; }
@keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: #f1f1f1; }
::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: #555; }
</style>