<template>
  <div class="container-fluid py-4">
    <!-- 🟢 SESSION ACTIVE BANNER -->
    <div v-if="activeSession" class="alert alert-success mb-4 py-3 d-flex justify-content-between align-items-center border-start border-success border-4">
      <div>
        <h5 class="mb-0 fw-bold">
          ✅ Session Active: <span class="badge bg-success ms-2">{{ activeSession.code }}</span>
        </h5>
        <small class="text-muted">{{ activeSession.name }}</small>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-success" @click="switchSessionModal = true">
          🔄 Changer
        </button>
        <button class="btn btn-sm btn-outline-secondary" @click="reloadData">
          🔃 Recharger
        </button>
      </div>
    </div>

    <!-- ⚠️ AUCUNE SESSION -->
    <div v-else class="alert alert-danger mb-4">
      <h5 class="mb-2 fw-bold">❌ Aucune Session Active</h5>
      <p class="mb-0">Créez ou activez une session d'audit pour commencer.</p>
    </div>

    <!-- ✅ ALERT APRÈS ENREGISTREMENT -->
    <div v-if="showAlert" class="alert mb-4" :class="alertClass" role="alert" style="animation: slideDown 0.3s ease;">
      <div class="d-flex justify-content-between align-items-center">
        <span>{{ alertMessage }}</span>
        <button type="button" class="btn-close" @click="showAlert = false"></button>
      </div>
    </div>

    <!-- 📊 HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold text-danger mb-0">🔴 Gestion des Risques</h2>
        <small class="text-muted">Module Audit - Analyse Risques avec IA</small>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-danger" @click="openCreateModal" :disabled="loading">
          {{ loading ? '⏳' : '➕' }} Créer Risque
        </button>
        <button class="btn btn-secondary" @click="exportCsv" :disabled="filteredRisks.length === 0">
          📥 Export CSV
        </button>
      </div>
    </div>

    <!-- 🔍 FILTRES -->
    <div class="card mb-4 border-0 shadow-sm">
      <div class="card-header bg-danger text-white fw-bold">
        📋 Filtres Dynamiques
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label fw-bold small">🏛️ Entité</label>
            <select v-model.number="filters.entityId" class="form-select form-select-sm" @change="applyFilters">
              <option :value="null">-- Toutes --</option>
              <option v-for="e in entities" :key="e.id" :value="e.id">
                {{ e.code_base }} - {{ e.name }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold small">⚙️ Processus</label>
            <select v-model.number="filters.processId" class="form-select form-select-sm" @change="applyFilters">
              <option :value="null">-- Tous --</option>
              <option v-for="p in processes" :key="p.id" :value="p.id">
                {{ p.code }} - {{ p.name }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold small">📌 Activité</label>
            <select v-model.number="filters.activityId" class="form-select form-select-sm" @change="applyFilters">
              <option :value="null">-- Toutes --</option>
              <option v-for="a in availableActivities" :key="a.id" :value="a.id">
                {{ a.code }} - {{ a.name }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold small">🔍 Recherche</label>
            <input v-model="searchQuery" type="text" class="form-control form-control-sm" 
                   placeholder="Code, libellé..." @input="applyFilters">
          </div>
        </div>
      </div>
    </div>

    <!-- 📊 TABLEAU HIÉRARCHIQUE -->
    <div class="card border-0 shadow-sm" style="overflow-x: auto;">
      <div class="card-header bg-danger text-white py-2">
        <h6 class="fw-bold mb-0">📊 Tableau de Gestion des Risques</h6>
      </div>
      
      <table class="table table-sm table-hover mb-0">
        <thead class="table-danger text-white">
          <tr style="font-size: 0.8rem;">
            <th class="p-2" style="width: 15%;">PROCESSUS</th>
            <th class="p-2" style="width: 15%;">ACTIVITÉ</th>
            <th class="p-2" style="width: 25%;">RISQUES</th>
            <th class="p-2" style="width: 8%;">IMPACT</th>
            <th class="p-2" style="width: 8%;">FRÉQUENCE</th>
            <th class="p-2" style="width: 8%;">CRITICITÉ</th>
            <th class="p-2" style="width: 8%;">IMPACT NET</th>
            <th class="p-2" style="width: 8%;">FREQ NET</th>
            <th class="p-2" style="width: 5%;">ACTIONS</th>
          </tr>
        </thead>
        <tbody style="font-size: 0.75rem;">
          <template v-for="process in filteredProcessesWithActivities" :key="process.id">
            <tr class="process-header bg-light">
              <td class="p-2 fw-bold">
                <button 
                  @click="toggleProcess(process.id)" 
                  class="btn btn-sm btn-outline-danger p-0 me-2"
                  style="width: 20px; height: 20px; font-size: 0.7rem;"
                >
                  {{ expandedProcesses.includes(process.id) ? '▼' : '▶' }}
                </button>
                <strong>{{ process.code }}</strong>
                <br>
                <small class="text-muted">{{ process.name }}</small>
              </td>
              <td colspan="8"></td>
            </tr>

            <template v-if="expandedProcesses.includes(process.id)">
              <tr v-for="activity in process.activities" :key="activity.id" class="activity-row bg-white">
                <td class="p-2">
                  <button 
                    @click="toggleActivity(activity.id)" 
                    class="btn btn-sm btn-outline-secondary p-0 me-2"
                    style="width: 20px; height: 20px; font-size: 0.7rem;"
                  >
                    {{ expandedActivities.includes(activity.id) ? '▼' : '▶' }}
                  </button>
                  <strong>{{ activity.code }}</strong>
                  <br>
                  <small class="text-muted">{{ activity.name }}</small>
                </td>

                <td class="p-2">
                  <div v-if="getRisksForActivity(activity.id).length > 0" style="max-height: 100px; overflow-y: auto;">
                    <div 
                      v-for="risk in getRisksForActivity(activity.id)" 
                      :key="risk.id"
                      class="risk-item mb-2 p-2 rounded border-start border-3"
                      :style="{ borderLeftColor: getRiskTypeColor(risk.risk_type_id), backgroundColor: '#f8f9fa' }"
                    >
                      <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                          <small class="fw-bold d-block">{{ risk.code }}</small>
                          <small class="text-dark">{{ truncate(risk.label, 30) }}</small>
                          <div v-if="risk.owner" class="small text-muted mt-1">👤 {{ truncate(risk.owner, 15) }}</div>
                        </div>
                        <div class="d-flex gap-1 ms-2">
                          <button 
                            @click="editRisk(risk)" 
                            class="btn btn-sm btn-outline-primary p-0"
                            style="width: 24px; height: 24px; font-size: 0.6rem;"
                            title="Modifier"
                          >
                            ✏️
                          </button>
                          <button 
                            @click="deleteRisk(risk)" 
                            class="btn btn-sm btn-outline-danger p-0"
                            style="width: 24px; height: 24px; font-size: 0.6rem;"
                            title="Supprimer"
                          >
                            🗑️
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-muted small p-2"><em>Aucun risque</em></div>

                  <button 
                    v-if="expandedActivities.includes(activity.id)"
                    @click="openAddRiskToActivity(activity, process)"
                    class="btn btn-sm btn-outline-success mt-2 w-100"
                    style="font-size: 0.7rem;"
                  >
                    ➕ Ajouter un Risque
                  </button>
                </td>

                <!-- IMPACT -->
                <td class="p-2 text-center">
                  <span v-if="getRisksForActivity(activity.id).length > 0" 
                        class="badge fw-bold" 
                        :class="'bg-' + getAverageImpactColor(getRisksForActivity(activity.id))"
                        style="font-size: 0.65rem;">
                    {{ getAverageImpactLabel(getRisksForActivity(activity.id)) }}
                  </span>
                  <span v-else class="badge bg-secondary" style="font-size: 0.65rem;">-</span>
                </td>

                <!-- FRÉQUENCE -->
                <td class="p-2 text-center">
                  <span v-if="getRisksForActivity(activity.id).length > 0" 
                        class="badge fw-bold" 
                        :class="'bg-' + getAverageFrequencyColor(getRisksForActivity(activity.id))"
                        style="font-size: 0.65rem;">
                    {{ getAverageFrequencyLabel(getRisksForActivity(activity.id)) }}
                  </span>
                  <span v-else class="badge bg-secondary" style="font-size: 0.65rem;">-</span>
                </td>

                <!-- CRITICITÉ BRUTE -->
                <td class="p-2 text-center">
                  <span v-if="getRisksForActivity(activity.id).length > 0" 
                        class="badge fw-bold" 
                        :class="'bg-' + getCriticalityColor(getAverageCriticality(getRisksForActivity(activity.id)))"
                        style="font-size: 0.65rem;">
                    {{ getAverageCriticality(getRisksForActivity(activity.id)) }}
                  </span>
                  <span v-else class="badge bg-secondary" style="font-size: 0.65rem;">0</span>
                </td>

                <!-- IMPACT NET -->
                <td class="p-2 text-center">
                  <span v-if="getRisksForActivity(activity.id).length > 0" 
                        class="badge fw-bold bg-info"
                        style="font-size: 0.65rem;">
                    {{ getAverageImpactNet(getRisksForActivity(activity.id)) }}
                  </span>
                  <span v-else class="badge bg-secondary" style="font-size: 0.65rem;">-</span>
                </td>

                <!-- FRÉQUENCE NET -->
                <td class="p-2 text-center">
                  <span v-if="getRisksForActivity(activity.id).length > 0" 
                        class="badge fw-bold bg-info"
                        style="font-size: 0.65rem;">
                    {{ getAverageFrequencyNet(getRisksForActivity(activity.id)) }}
                  </span>
                  <span v-else class="badge bg-secondary" style="font-size: 0.65rem;">-</span>
                </td>

                <td class="p-2 text-center">
                  <button class="btn btn-sm btn-info px-1 py-0" style="font-size: 0.6rem;" title="Détails">
                    ℹ️
                  </button>
                </td>
              </tr>
            </template>
          </template>

          <tr v-if="filteredProcessesWithActivities.length === 0">
            <td colspan="9" class="p-4 text-center text-muted">
              <small>📭 Aucun processus trouvé</small>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 🎯 MODAL CRÉATION - 7 ÉTAPES FUSIONNÉES -->
    <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
      <div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-height: 90vh;">
        <div class="modal-content border-0 shadow-lg">
          
          <div class="modal-header bg-danger text-white border-0 py-3">
            <h5 class="modal-title fw-bold">
              {{ editingId ? '✏️ Modifier le Risque' : '➕ Créer un Nouveau Risque' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
          </div>

          <div class="modal-body" style="max-height: calc(90vh - 140px); overflow-y: auto; padding: 1rem;">
            
            <!-- ÉTAPE 1: CONTEXTE -->
            <div class="section-title mb-3">
              <h6 class="fw-bold text-danger mb-2">
                <span class="badge bg-danger me-2">1</span> Contexte
              </h6>
            </div>

            <div class="row g-2 mb-3 p-2 bg-light rounded" style="font-size: 0.85rem;">
              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1">Entité</label>
                <select v-model.number="form.entity_id" @change="triggerAISuggestions" 
                        class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.code_base }} - {{ e.name }}</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1">Processus</label>
                <select v-model.number="form.process_id" @change="triggerAISuggestions" 
                        class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="p in processes" :key="p.id" :value="p.id">{{ p.code }} - {{ p.name }}</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1">Activité</label>
                <select v-model.number="form.activity_id" @change="triggerAISuggestions" 
                        class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="a in availableActivities" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1">Type Risque *</label>
                <select v-model.number="form.risk_type_id" @change="triggerAISuggestions" 
                        class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="t in riskTypes" :key="t.id" :value="t.id">{{ t.code }} - {{ t.label }}</option>
                </select>
              </div>

              <div v-if="selectedRiskType" class="col-12">
                <div class="p-2 rounded text-white text-center small fw-bold" 
                     :style="{ backgroundColor: getRiskTypeColor(selectedRiskType.id) }">
                  {{ selectedRiskType.code }} - {{ selectedRiskType.label }}
                </div>
              </div>
            </div>

            <!-- ÉTAPE 2: SUGGESTIONS IA -->
            <div v-if="form.process_id && form.activity_id && form.risk_type_id" class="mb-3">
              <div class="section-title mb-2">
                <h6 class="fw-bold text-danger mb-2">
                  <span class="badge bg-danger me-2">2</span> 🤖 Suggestions IA - Sélectionnez un libellé
                </h6>
              </div>

              <div v-if="aiLoading" class="alert alert-info mb-2 py-2" style="font-size: 0.8rem;">
                <div class="spinner-border spinner-border-sm me-2" style="width: 1rem; height: 1rem;"></div>
                <small>⏳ Génération suggestions IA...</small>
              </div>

              <div v-else-if="aiSuggestions && aiSuggestions.length > 0" class="card mb-2 border-info shadow-sm">
                <div class="card-body p-2" style="max-height: 220px; overflow-y: auto;">
                  <div class="list-group list-group-flush">
                    <button 
                      v-for="(suggestion, idx) in aiSuggestions" 
                      :key="idx"
                      type="button"
                      @click="selectRiskSuggestion(suggestion)"
                      class="list-group-item list-group-item-action text-start p-2 border-bottom"
                      :class="{ 'active bg-danger text-white': form.label === suggestion }"
                      style="cursor: pointer; transition: all 0.2s; font-size: 0.75rem;"
                    >
                      <small>{{ suggestion }}</small>
                    </button>
                  </div>
                </div>
              </div>

              <div v-else class="alert alert-warning mb-2 py-2" style="font-size: 0.8rem;">
                <small>⚠️ Pas de suggestions disponibles</small>
              </div>
            </div>

            <!-- ÉTAPE 3: CODE AUTO -->
            <div class="section-title mb-3">
              <h6 class="fw-bold text-danger mb-2">
                <span class="badge bg-danger me-2">3</span> Code (Auto - Par Type)
              </h6>
            </div>

            <div class="row g-2 mb-3 p-2 bg-light rounded">
              <div class="col-12">
                <input 
                  v-model="form.code" 
                  type="text" 
                  class="form-control form-control-sm fw-bold" 
                  readonly
                  style="background-color: #e9ecef; font-size: 1rem; letter-spacing: 2px;"
                  placeholder="Auto"
                >
                <small class="text-muted d-block mt-1">Format: [TYPE]-[SEQ] (ex: RF-001, RC-001)</small>
              </div>
            </div>

            <!-- ÉTAPE 4: DÉTAILS -->
            <div class="section-title mb-3">
              <h6 class="fw-bold text-danger mb-2">
                <span class="badge bg-danger me-2">4</span> Détails
              </h6>
            </div>

            <div class="row g-2 mb-3 p-2 bg-light rounded" style="font-size: 0.85rem;">
              <div class="col-12">
                <label class="form-label fw-bold small mb-1">Libellé *</label>
                <input v-model="form.label" type="text" class="form-control form-control-sm" required>
              </div>

              <div class="col-12">
                <label class="form-label fw-bold small mb-1">Description</label>
                <textarea v-model="form.description" class="form-control form-control-sm" rows="2"></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1">Propriétaire</label>
                <input v-model="form.owner" type="text" class="form-control form-control-sm">
              </div>

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
            <div class="section-title mb-3">
              <h6 class="fw-bold text-danger mb-2">
                <span class="badge bg-danger me-2">5</span> Évaluation Brute
              </h6>
            </div>

            <div class="row g-2 mb-3 p-2 bg-light rounded" style="font-size: 0.85rem;">
              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1">Fréquence *</label>
                <select v-model.number="form.frequency_level_id" class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="f in frequencies" :key="f.id" :value="f.id">
                    {{ f.label }} ({{ f.level }}/5)
                  </option>
                </select>
                <div v-if="frequencyLevel" class="mt-2 p-2 rounded text-white text-center small fw-bold" 
                     :style="{ backgroundColor: getFrequencyLevelColor(frequencyLevel.id) }">
                  {{ frequencyLevel.label }}
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1">Impact *</label>
                <select v-model.number="form.impact_level_id" class="form-select form-select-sm">
                  <option value="">-- Sélectionner --</option>
                  <option v-for="i in impacts" :key="i.id" :value="i.id">
                    {{ i.label }} ({{ i.level }}/5)
                  </option>
                </select>
                <div v-if="impactLevel" class="mt-2 p-2 rounded text-white text-center small fw-bold" 
                     :style="{ backgroundColor: getImpactLevelColor(impactLevel.id) }">
                  {{ impactLevel.label }}
                </div>
              </div>

              <div v-if="criticityBrut" class="col-12">
                <div class="p-2 rounded text-white text-center small fw-bold" 
                     :style="{ backgroundColor: getCriticalityColor(criticityBrut) }">
                  Criticité Brute: <strong>{{ criticityBrut }}</strong> - {{ getCriticalityName(criticityBrut) }}
                </div>
              </div>
            </div>

            <!-- ÉTAPE 6: ÉVALUATION NETTE -->
            <div class="section-title mb-3">
              <h6 class="fw-bold text-danger mb-2">
                <span class="badge bg-danger me-2">6</span> Évaluation Nette
              </h6>
            </div>

            <div class="row g-2 mb-3 p-2 bg-light rounded" style="font-size: 0.85rem;">
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
                <div class="p-2 rounded text-white text-center small fw-bold" 
                     :style="{ backgroundColor: getCriticalityColor(criticityNet) }">
                  Criticité Nette: <strong>{{ criticityNet }}</strong> - {{ getCriticalityName(criticityNet) }}
                </div>
              </div>
            </div>

            <!-- ÉTAPE 7: PROCÉDURE CONTRÔLE -->
            <div class="section-title mb-3">
              <h6 class="fw-bold text-danger mb-2">
                <span class="badge bg-danger me-2">7</span> Procédure Contrôle
              </h6>
            </div>

            <div class="p-2 bg-light rounded mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold small mb-0">Procédure</label>
                <small v-if="!editingId && form.label" class="text-primary" 
                       @click="generateControlAI" style="cursor: pointer;">
                  ✨ Générer avec IA
                </small>
              </div>
              <textarea v-model="form.control_procedure" class="form-control form-control-sm" rows="2"></textarea>
            </div>
          </div>

          <div class="modal-footer bg-light border-top">
            <button @click="closeModal" type="button" class="btn btn-secondary btn-sm">
              ❌ Annuler
            </button>
            <button 
              @click="saveRisk" 
              type="button" 
              class="btn btn-danger btn-sm"
              :disabled="!form.label || !form.code || !form.risk_type_id || !form.frequency_level_id || !form.impact_level_id || loading"
            >
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              {{ loading ? '⏳' : '💾 ' + (editingId ? 'Modifier' : 'Créer') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 🔄 MODAL CHANGEMENT SESSION -->
    <div v-if="switchSessionModal" class="modal d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
      <div class="modal-dialog modal-md">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-info text-white border-0">
            <h5 class="modal-title fw-bold">🔄 Sélectionner Session</h5>
            <button type="button" class="btn-close btn-close-white" @click="switchSessionModal = false"></button>
          </div>

          <div class="modal-body">
            <div class="list-group">
              <button
                v-for="session in allSessions"
                :key="session.id"
                type="button"
                @click="switchSession(session.id)"
                class="list-group-item list-group-item-action text-start p-3"
                :class="{ 'active bg-info': session.is_active }"
              >
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1 fw-bold">{{ session.code }}</h6>
                    <small class="text-muted">{{ session.name }}</small>
                    <br>
                    <small class="badge" :class="'bg-' + (session.is_active ? 'info' : 'secondary')">
                      {{ session.is_active ? '✅ Active' : '⏸️ Inactive' }}
                    </small>
                  </div>
                  <div class="text-end">
                    <div class="badge bg-danger">{{ session.risks_count }} risques</div>
                  </div>
                </div>
              </button>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="switchSessionModal = false" type="button" class="btn btn-secondary btn-sm">
              Fermer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  activeSession: { type: Object, default: () => null },
  allSessions: { type: Array, default: () => [] },
  entities: { type: Array, default: () => [] },
  processes: { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
  riskTypes: { type: Array, default: () => [] },
  frequencies: { type: Array, default: () => [] },
  impacts: { type: Array, default: () => [] },
  initialRisks: { type: Array, default: () => [] },
})

// STATE
const allRisks = ref([])
const showModal = ref(false)
const switchSessionModal = ref(false)
const editingId = ref(null)
const loading = ref(false)
const aiLoading = ref(false)
const searchQuery = ref('')
const aiSuggestions = ref([])
const showAlert = ref(false)
const alertMessage = ref('')
const alertClass = ref('alert-success')

const filters = ref({ entityId: null, processId: null, activityId: null })

const form = ref({
  code: '', label: '', description: '', risk_type_id: null,
  frequency_level_id: null, frequency_net: null, impact_level_id: null, impact_net: null,
  entity_id: null, process_id: null, activity_id: null,
  owner: '', control_procedure: '', status: 'identified',
})

const expandedProcesses = ref([])
const expandedActivities = ref([])

// COMPUTED
const availableActivities = computed(() => {
  if (!filters.value.processId) return props.activities || []
  return (props.activities || []).filter(a => a.process_id === filters.value.processId)
})

const filteredRisks = computed(() => {
  return (allRisks.value || []).filter(r => {
    const matchEntity = !filters.value.entityId || r.entity_id === filters.value.entityId
    const matchProcess = !filters.value.processId || r.process_id === filters.value.processId
    const matchActivity = !filters.value.activityId || r.activity_id === filters.value.activityId
    const matchSearch = !searchQuery.value || 
      r.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      r.label.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchEntity && matchProcess && matchActivity && matchSearch
  })
})

const filteredProcessesWithActivities = computed(() => {
  let filtered = props.processes || []

  if (filters.value.entityId) {
    filtered = filtered.filter(p => p.entity_id === filters.value.entityId)
  }
  if (filters.value.processId) {
    filtered = filtered.filter(p => p.id === filters.value.processId)
  }

  return filtered
    .map(process => {
      let processActivities = (props.activities || []).filter(a => a.process_id === process.id)
      if (filters.value.activityId) {
        processActivities = processActivities.filter(a => a.id === filters.value.activityId)
      }
      return { ...process, activities: processActivities }
    })
    .filter(process => {
      const risksForProcess = filteredRisks.value.filter(r => 
        process.activities.some(a => a.id === r.activity_id)
      )
      return risksForProcess.length > 0
    })
})

const selectedRiskType = computed(() => {
  if (!form.value.risk_type_id) return null
  return props.riskTypes.find(t => t.id == form.value.risk_type_id)
})

const frequencyLevel = computed(() => {
  if (!form.value.frequency_level_id) return null
  return props.frequencies.find(f => f.id == form.value.frequency_level_id)
})

const impactLevel = computed(() => {
  if (!form.value.impact_level_id) return null
  return props.impacts.find(i => i.id == form.value.impact_level_id)
})

const criticityBrut = computed(() => {
  if (!frequencyLevel.value || !impactLevel.value) return null
  return frequencyLevel.value.level * impactLevel.value.level
})

const criticityNet = computed(() => {
  if (!form.value.frequency_net || !form.value.impact_net) return null
  return Math.round(form.value.frequency_net * form.value.impact_net)
})

// LIFECYCLE
onMounted(() => {
  allRisks.value = props.initialRisks || []
})

// ALERT
const showSuccessAlert = (msg) => {
  alertMessage.value = msg
  alertClass.value = 'alert-success'
  showAlert.value = true
  setTimeout(() => showAlert.value = false, 4000)
}

const showErrorAlert = (msg) => {
  alertMessage.value = msg
  alertClass.value = 'alert-danger'
  showAlert.value = true
  setTimeout(() => showAlert.value = false, 4000)
}

// METHODS
const reloadData = () => window.location.reload()

const switchSession = async (sessionId) => {
  try {
    const response = await fetch('/api/m/risk/switch-session', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({ session_id: sessionId })
    })
    if (response.ok) {
      switchSessionModal.value = false
      location.reload()
    }
  } catch (error) {
    console.error('Erreur:', error)
  }
}

const generateAutoCode = async () => {
  if (!selectedRiskType.value || !selectedRiskType.value.code) return ''
  const typeCode = selectedRiskType.value.code.substring(0, 2).toUpperCase()
  const sameTypeRisks = allRisks.value.filter(r => r.risk_type_id == form.value.risk_type_id)
  let maxSequence = 0
  if (sameTypeRisks.length > 0) {
    sameTypeRisks.forEach(r => {
      const match = r.code.match(/-(\d+)$/)
      if (match) {
        const num = parseInt(match[1])
        if (num > maxSequence) maxSequence = num
      }
    })
  }
  const nextSequence = maxSequence + 1
  return `${typeCode}-${String(nextSequence).padStart(3, '0')}`
}

const triggerAISuggestions = async () => {
  form.value.code = await generateAutoCode()
  if (!form.value.process_id || !form.value.activity_id || !form.value.risk_type_id) {
    aiSuggestions.value = []
    return
  }
  aiLoading.value = true
  try {
    const processName = props.processes?.find(p => p.id === form.value.process_id)?.name || ''
    const activityName = props.activities?.find(a => a.id === form.value.activity_id)?.name || ''
    const riskTypeName = props.riskTypes?.find(t => t.id === form.value.risk_type_id)?.label || ''

    const response = await fetch('/api/m/risk.core/suggest-ai', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({ 
        process_id: form.value.process_id,
        process_name: processName, 
        activity_id: form.value.activity_id,
        activity_name: activityName, 
        risk_type_id: form.value.risk_type_id,
        risk_type_name: riskTypeName 
      })
    })

    if (response.ok) {
      const data = await response.json()
      aiSuggestions.value = data.suggestions || []
    }
  } catch (error) {
    console.error('Erreur IA:', error)
    aiSuggestions.value = []
  } finally {
    aiLoading.value = false
  }
}

const selectRiskSuggestion = (suggestion) => {
  form.value.label = suggestion
  generateControlAI()
}

const generateControlAI = async () => {
  if (!form.value.label) return
  
  try {
    const processName = props.processes?.find(p => p.id === form.value.process_id)?.name || ''
    const activityName = props.activities?.find(a => a.id === form.value.activity_id)?.name || ''

    const response = await fetch('/api/m/risk.core/suggest-control', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({
        risk_label: form.value.label,
        activity_name: activityName,
        process_name: processName
      })
    })

    if (response.ok) {
      const data = await response.json()
      form.value.control_procedure = data.control_procedure || ''
    }
  } catch (error) {
    console.error('Erreur contrôle:', error)
  }
}

const saveRisk = async () => {
  try {
    loading.value = true
    const method = editingId.value ? 'PUT' : 'POST'
    const url = editingId.value ? `/api/m/risk.core/${editingId.value}` : '/api/m/risk.core'

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(form.value)
    })

    if (response.ok) {
      const data = await response.json()
      if (!editingId.value) {
        allRisks.value.push(data.risk || data)
        showSuccessAlert(`✅ Risque '${data.risk?.code || form.value.code}' créé avec succès`)
      } else {
        const idx = allRisks.value.findIndex(r => r.id === editingId.value)
        if (idx >= 0) allRisks.value[idx] = data.risk || data
        showSuccessAlert(`✅ Risque '${data.risk?.code || form.value.code}' modifié avec succès`)
      }
      closeModal()
    } else {
      const error = await response.json()
      showErrorAlert(`❌ ${error.error || 'Erreur lors de l\'enregistrement'}`)
    }
  } catch (error) {
    console.error('Erreur:', error)
    showErrorAlert('❌ Erreur serveur')
  } finally {
    loading.value = false
  }
}

const deleteRisk = async (risk) => {
  if (!confirm(`Supprimer ${risk.code}?`)) return
  try {
    const response = await fetch(`/api/m/risk.core/${risk.id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
    })
    if (response.ok) {
      allRisks.value = allRisks.value.filter(r => r.id !== risk.id)
      showSuccessAlert(`✅ Risque '${risk.code}' supprimé avec succès`)
    } else {
      showErrorAlert('❌ Erreur lors de la suppression')
    }
  } catch (error) {
    console.error('Erreur:', error)
    showErrorAlert('❌ Erreur suppression')
  }
}

const editRisk = (risk) => {
  editingId.value = risk.id
  Object.assign(form.value, risk)
  aiSuggestions.value = []
  showModal.value = true
}

const openAddRiskToActivity = (activity, process) => {
  resetForm()
  form.value.process_id = process.id
  form.value.activity_id = activity.id
  editingId.value = null
  aiSuggestions.value = []
  showModal.value = true
  triggerAISuggestions()
}

const openCreateModal = () => {
  resetForm()
  editingId.value = null
  aiSuggestions.value = []
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingId.value = null
  aiSuggestions.value = []
  loading.value = false
  aiLoading.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    code: '', label: '', description: '', risk_type_id: null, frequency_level_id: null,
    frequency_net: null, impact_level_id: null, impact_net: null,
    entity_id: null, process_id: null, activity_id: null,
    owner: '', control_procedure: '', status: 'identified'
  }
}

const applyFilters = () => {}

const toggleProcess = (processId) => {
  const idx = expandedProcesses.value.indexOf(processId)
  if (idx >= 0) expandedProcesses.value.splice(idx, 1)
  else expandedProcesses.value.push(processId)
}

const toggleActivity = (activityId) => {
  const idx = expandedActivities.value.indexOf(activityId)
  if (idx >= 0) expandedActivities.value.splice(idx, 1)
  else expandedActivities.value.push(activityId)
}

const getRisksForActivity = (activityId) => {
  return filteredRisks.value.filter(r => r.activity_id === activityId)
}

const exportCsv = () => {
  const csv = [['CODE', 'LIBELLÉ', 'PROCESSUS', 'ACTIVITÉ', 'TYPE', 'FRÉQUENCE', 'IMPACT', 'CRITICITÉ']]
  filteredRisks.value.forEach(r => {
    csv.push([
      r.code, r.label,
      props.processes?.find(p => p.id === r.process_id)?.name || '-',
      props.activities?.find(a => a.id === r.activity_id)?.name || '-',
      props.riskTypes?.find(t => t.id === r.risk_type_id)?.code || '-',
      props.frequencies?.find(f => f.id === r.frequency_level_id)?.label || '-',
      props.impacts?.find(i => i.id === r.impact_level_id)?.label || '-',
      (r.frequency_level_id && r.impact_level_id) ? r.frequency_level_id * r.impact_level_id : '-'
    ])
  })
  const text = csv.map(row => row.map(c => `"${c}"`).join(',')).join('\n')
  const blob = new Blob([text], { type: 'text/csv' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `risques_${new Date().toISOString().slice(0,10)}.csv`
  link.click()
}

const getRiskTypeColor = (id) => {
  const type = props.riskTypes?.find(t => t.id === id)
  const colors = { danger: '#dc3545', info: '#0dcaf0', warning: '#ffc107', secondary: '#6c757d', primary: '#0d6efd', success: '#198754' }
  return colors[type?.color] || '#6c757d'
}

const getFrequencyLevelColor = (id) => {
  const freq = props.frequencies?.find(f => f.id === id)
  const colors = { success: '#198754', warning: '#ffc107', danger: '#dc3545' }
  return colors[freq?.color] || '#6c757d'
}

const getImpactLevelColor = (id) => {
  const impact = props.impacts?.find(i => i.id === id)
  const colors = { success: '#198754', info: '#0dcaf0', warning: '#ffc107', danger: '#dc3545', secondary: '#6c757d' }
  return colors[impact?.color] || '#6c757d'
}

const getAverageImpactColor = (risks) => {
  if (!risks.length) return 'secondary'
  const levels = risks.map(r => props.impacts?.find(i => i.id === r.impact_level_id)?.level || 1)
  const avg = levels.reduce((a, b) => a + b, 0) / levels.length
  return avg >= 4 ? 'danger' : avg >= 3 ? 'warning' : avg >= 2 ? 'info' : 'success'
}

const getAverageImpactLabel = (risks) => {
  if (!risks.length) return '-'
  const levels = risks.map(r => props.impacts?.find(i => i.id === r.impact_level_id)?.level || 1)
  const avg = levels.reduce((a, b) => a + b, 0) / levels.length
  return avg >= 4.5 ? 'Très Élevé' : avg >= 3.5 ? 'Élevé' : avg >= 2.5 ? 'Important' : avg >= 1.5 ? 'Modéré' : 'Faible'
}

const getAverageImpactNet = (risks) => {
  if (!risks.length) return '-'
  const values = risks.filter(r => r.impact_net).map(r => r.impact_net)
  return values.length > 0 ? (values.reduce((a, b) => a + b, 0) / values.length).toFixed(1) : '-'
}

const getAverageFrequencyColor = (risks) => {
  if (!risks.length) return 'secondary'
  const levels = risks.map(r => props.frequencies?.find(f => f.id === r.frequency_level_id)?.level || 1)
  const avg = levels.reduce((a, b) => a + b, 0) / levels.length
  return avg >= 3.5 ? 'dark' : avg >= 2.5 ? 'danger' : avg >= 1.5 ? 'warning' : 'success'
}

const getAverageFrequencyLabel = (risks) => {
  if (!risks.length) return '-'
  const levels = risks.map(r => props.frequencies?.find(f => f.id === r.frequency_level_id)?.level || 1)
  const avg = levels.reduce((a, b) => a + b, 0) / levels.length
  return avg >= 3.5 ? 'Certain' : avg >= 2.5 ? 'Fréquent' : avg >= 1.5 ? 'Probable' : 'Rare'
}

const getAverageFrequencyNet = (risks) => {
  if (!risks.length) return '-'
  const values = risks.filter(r => r.frequency_net).map(r => r.frequency_net)
  return values.length > 0 ? (values.reduce((a, b) => a + b, 0) / values.length).toFixed(1) : '-'
}

const getAverageCriticality = (risks) => {
  if (!risks.length) return 0
  return Math.round(risks.reduce((sum, r) => sum + ((r.frequency_level_id && r.impact_level_id) ? r.frequency_level_id * r.impact_level_id : 0), 0) / risks.length)
}

const getCriticalityColor = (c) => c >= 12 ? 'danger' : c >= 8 ? 'warning' : c > 0 ? 'success' : 'secondary'

const getCriticalityName = (level) => {
  if (!level) return '-'
  if (level <= 4) return 'Faible'
  if (level <= 9) return 'Moyen'
  if (level <= 16) return 'Élevé'
  return 'Critique'
}

const getFrequencyNetName = (val) => {
  if (!val) return '-'
  if (val <= 1) return 'Très rare'
  if (val <= 2) return 'Rare'
  if (val <= 3) return 'Occasionnel'
  if (val <= 4) return 'Fréquent'
  return 'Très fréquent'
}

const getImpactNetName = (val) => {
  if (!val) return '-'
  if (val <= 1) return 'Négligeable'
  if (val <= 2) return 'Faible'
  if (val <= 3) return 'Modéré'
  if (val <= 4) return 'Majeur'
  return 'Critique'
}

const truncate = (text, len) => text?.length > len ? text.substring(0, len) + '...' : text || '-'
</script>

<style scoped>
.modal {
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.section-title {
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f0f0f0;
}

.risk-item {
  transition: all 0.2s ease;
}

.risk-item:hover {
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transform: translateX(2px);
}

.process-header {
  background-color: #f8f9fa !important;
}

.activity-row {
  border-left: 3px solid #e9ecef;
}

.activity-row:hover {
  background-color: #f9f9f9 !important;
}

.list-group-item.active {
  background-color: #dc3545 !important;
  border-color: #dc3545 !important;
}

@keyframes slideDown {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>