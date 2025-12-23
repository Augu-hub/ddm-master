<template>
  <VerticalLayout>
    <Head title="Évaluation des processus" />

    <!-- HEADER PRINCIPAL -->
    <b-card class="mb-4 border-0 shadow-lg p-4" style="background: linear-gradient(135deg,#10b981,#059669); border-radius:12px;">
      <h2 class="fw-bold mb-2 text-white">📊 Évaluation des Processus</h2>
      <p class="mb-0 text-white"><strong>{{ user?.name }}</strong> — <strong>{{ link?.function_name }}</strong></p>
    </b-card>

    <!-- PAGE 1: SESSIONS -->
    <b-card v-if="!selectedSession" class="border-0 shadow-lg mb-4">
      <b-card-header class="bg-light" style="border-bottom:3px solid #10b981;">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0"><i class="ti ti-calendar me-2"></i>Sessions d'évaluation</h5>
          <b-button 
            v-if="canCreateSession" 
            variant="success" 
            size="sm" 
            @click="showCreateSessionModal = true" 
            class="fw-bold"
          >
            <i class="ti ti-plus me-1"></i>Créer
          </b-button>
          <span v-else class="badge bg-danger">Limite (5)</span>
        </div>
      </b-card-header>
      <b-card-body>
        <!-- STATS GLOBALES -->
        <b-row class="g-3 mb-4">
          <b-col md="3">
            <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;">
              <div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ activeSessions.length }}</div>
              <small class="text-muted d-block">Actives</small>
            </b-card>
          </b-col>
          <b-col md="3">
            <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;">
              <div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ archivedSessions.length }}</div>
              <small class="text-muted d-block">Archivées</small>
            </b-card>
          </b-col>
          <b-col md="3">
            <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;">
              <div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ props.processes?.length || 0 }}</div>
              <small class="text-muted d-block">Processus</small>
            </b-card>
          </b-col>
          <b-col md="3">
            <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;">
              <div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ globalAvg.toFixed(2) }}</div>
              <small class="text-muted d-block">Score moyen</small>
            </b-card>
          </b-col>
        </b-row>

        <!-- TABS: ACTIVES vs ARCHIVÉES -->
        <b-tabs class="mb-4">
          <b-tab title="🟢 Sessions actives" active>
            <div class="row g-3 mt-2">
              <div v-for="session in activeSessions" :key="session.id" class="col-md-6 col-lg-4">
                <b-card class="border-0 shadow-md h-100" :style="{ borderTop: `5px solid ${session.color}`, background: '#f9fafb' }">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <h6 class="fw-bold mb-1">{{ session.name }}</h6>
                      <small class="text-muted d-block">{{ formatDate(session.created_at) }}</small>
                    </div>
                    <span class="badge bg-success">🟢 Ouverte</span>
                  </div>

                  <div class="bg-white p-3 rounded mb-3" style="border:1px solid #e5e7eb;">
                    <div class="row g-2 text-center">
                      <div class="col-6">
                        <div class="fw-bold text-success">{{ session.evaluated_count }}/{{ props.processes?.length || 0 }}</div>
                        <small class="text-muted d-block">Évalués</small>
                      </div>
                      <div class="col-6">
                        <div class="fw-bold text-info">{{ session.session_avg_score }}</div>
                        <small class="text-muted d-block">Score</small>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex flex-column gap-2">
                    <b-button
                      variant="success"
                      size="sm"
                      class="fw-bold"
                      @click="selectSession(session)"
                    >
                      <i class="ti ti-arrow-right me-1"></i>Évaluer
                    </b-button>
                    <b-button
                      variant="outline-info"
                      size="sm"
                      class="fw-bold"
                      @click="showDuplicateModal = true; sessionToDuplicate = session"
                    >
                      <i class="ti ti-copy me-1"></i>Dupliquer
                    </b-button>
                    <b-button
                      variant="outline-warning"
                      size="sm"
                      class="fw-bold"
                      @click="archiveSessionConfirm(session)"
                    >
                      <i class="ti ti-archive me-1"></i>Archiver
                    </b-button>
                    <b-button
                      variant="outline-danger"
                      size="sm"
                      class="fw-bold"
                      @click="deleteSessionConfirm(session)"
                    >
                      <i class="ti ti-trash me-1"></i>Supprimer
                    </b-button>
                  </div>
                </b-card>
              </div>
            </div>

            <div v-if="activeSessions.length === 0" class="text-center py-5">
              <p class="text-muted mb-3">Aucune session active</p>
              <b-button v-if="canCreateSession" variant="success" @click="showCreateSessionModal = true" class="fw-bold">
                <i class="ti ti-plus me-1"></i>Créer
              </b-button>
            </div>
          </b-tab>

          <b-tab title="📁 Sessions archivées">
            <div class="row g-3 mt-2">
              <div v-for="session in archivedSessions" :key="session.id" class="col-md-6 col-lg-4">
                <b-card class="border-0 shadow-sm h-100" :style="{ borderTop: `5px solid ${session.color}`, background: '#f5f5f5', opacity: 0.8 }">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <h6 class="fw-bold mb-1">{{ session.name }}</h6>
                      <small class="text-muted d-block">{{ formatDate(session.created_at) }}</small>
                    </div>
                    <span class="badge bg-secondary">📁 Archivée</span>
                  </div>

                  <div class="bg-white p-3 rounded mb-3" style="border:1px solid #d1d5db;">
                    <div class="row g-2 text-center">
                      <div class="col-6">
                        <div class="fw-bold text-muted">{{ session.evaluated_count }}/{{ props.processes?.length || 0 }}</div>
                        <small class="text-muted d-block">Évalués</small>
                      </div>
                      <div class="col-6">
                        <div class="fw-bold text-muted">{{ session.session_avg_score }}</div>
                        <small class="text-muted d-block">Score</small>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex gap-2">
                    <b-button
                      variant="outline-info"
                      size="sm"
                      class="w-50 fw-bold"
                      @click="selectSession(session)"
                    >
                      <i class="ti ti-eye me-1"></i>Voir
                    </b-button>
                    <b-button
                      variant="outline-danger"
                      size="sm"
                      class="w-50 fw-bold"
                      @click="deleteSessionConfirm(session)"
                    >
                      <i class="ti ti-trash me-1"></i>Supprimer
                    </b-button>
                  </div>
                </b-card>
              </div>
            </div>

            <div v-if="archivedSessions.length === 0" class="text-center py-5">
              <p class="text-muted">Aucune session archivée</p>
            </div>
          </b-tab>
        </b-tabs>
      </b-card-body>
    </b-card>

    <!-- PAGE 2: ÉVALUATION -->
    <div v-if="selectedSession">
      <b-card class="mb-4 border-0 shadow-sm" :style="{ background: selectedSession.color + '15', borderLeft: `5px solid ${selectedSession.color}` }">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="fw-bold mb-1">{{ selectedSession.name }}</h5>
            <small class="text-muted">{{ formatDate(selectedSession.created_at) }} — {{ selectedSession.status === 'open' ? '🟢 Ouverte' : '🔒 Fermée' }}</small>
          </div>
          <div class="d-flex gap-2">
            <b-button variant="info" size="sm" @click="showReportModal = true" class="fw-bold">
              <i class="ti ti-file-text me-1"></i>Rapport
            </b-button>
            <b-button variant="outline-secondary" size="sm" @click="selectedSession = null; selectedProcess = null" class="fw-bold">
              <i class="ti ti-arrow-left me-1"></i>Retour
            </b-button>
          </div>
        </div>
      </b-card>

      <!-- STATS SESSION -->
      <b-row class="g-3 mb-4">
        <b-col md="3">
          <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;">
            <div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ sessionEvaluatedCount }}/{{ props.processes?.length || 0 }}</div>
            <small class="text-muted d-block">Évalués</small>
          </b-card>
        </b-col>
        <b-col md="3">
          <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;">
            <div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ sessionAvgScore.toFixed(2) }}</div>
            <small class="text-muted d-block">Score moyen</small>
          </b-card>
        </b-col>
        <b-col md="3">
          <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;">
            <div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ Math.round(progressPercent) }}%</div>
            <small class="text-muted d-block">Progression</small>
          </b-card>
        </b-col>
        <b-col md="3">
          <b-card 
            class="border-0 shadow-sm text-center" 
            :style="{ background: 'linear-gradient(135deg,' + (selectedSession.status === 'open' ? '#ecfdf5' : '#fee2e2') + ',#fff)', borderLeft: '5px solid ' + (selectedSession.status === 'open' ? '#10b981' : '#ef4444') }">
            <div class="fw-bold" :style="{ fontSize: '1.8rem', color: selectedSession.status === 'open' ? '#10b981' : '#ef4444' }">{{ selectedSession.status === 'open' ? '🟢' : '🔒' }}</div>
            <small class="text-muted d-block">Statut</small>
          </b-card>
        </b-col>
      </b-row>

      <!-- MESSAGE DE SUCCÈS -->
      <div v-if="successMessage" class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="ti ti-check me-2"></i>{{ successMessage }}
        <button type="button" class="btn-close" @click="successMessage = ''" ></button>
      </div>

      <!-- TABLEAU PROCESSUS -->
      <b-card class="mb-4 border-0 shadow-sm">
        <b-card-header class="bg-light" style="border-bottom:3px solid #10b981;">
          <h5 class="fw-bold mb-0"><i class="ti ti-list me-2"></i>Processus à évaluer</h5>
        </b-card-header>
        <b-card-body class="p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-sm">
              <thead style="background:#f3f4f6;border-bottom:2px solid #10b981;">
                <tr>
                  <th style="min-width:80px;">Code</th>
                  <th style="min-width:220px;">Processus</th>
                  <th class="text-center" style="min-width:100px;">Maturité</th>
                  <th class="text-center" style="min-width:100px;">Motricité</th>
                  <th class="text-center" style="min-width:100px;">Transversalité</th>
                  <th class="text-center" style="min-width:100px;">Stratégique</th>
                  <th class="text-center" style="min-width:100px;">Global</th>
                  <th class="text-center" style="min-width:100px;"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="process in props.processes" :key="process.id" class="process-row">
                  <td><span class="badge bg-success">{{ process.code }}</span></td>
                  <td><strong>{{ process.name }}</strong></td>
                  <td class="text-center">
                    <span v-if="getProcessEval(process.id, 'maturity_score') > 0" class="badge-score" :style="{ background: getColorByScore(getProcessEval(process.id, 'maturity_score')) }">
                      {{ getProcessEval(process.id, 'maturity_score').toFixed(1) }}
                    </span>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td class="text-center">
                    <span v-if="getProcessEval(process.id, 'motricity_score') > 0" class="badge-score" :style="{ background: getColorByScore(getProcessEval(process.id, 'motricity_score')) }">
                      {{ getProcessEval(process.id, 'motricity_score').toFixed(1) }}
                    </span>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td class="text-center">
                    <span v-if="getProcessEval(process.id, 'transversality_score') > 0" class="badge-score" :style="{ background: getColorByScore(getProcessEval(process.id, 'transversality_score')) }">
                      {{ getProcessEval(process.id, 'transversality_score').toFixed(1) }}
                    </span>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td class="text-center">
                    <span v-if="getProcessEval(process.id, 'strategic_score') > 0" class="badge-score" :style="{ background: getColorByScore(getProcessEval(process.id, 'strategic_score')) }">
                      {{ getProcessEval(process.id, 'strategic_score').toFixed(1) }}
                    </span>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td class="text-center">
                    <div class="score-badge" :style="{ borderColor: getProcessEval(process.id, 'criticality_score') > 0 ? getColorByScore(getProcessEval(process.id, 'criticality_score')) : '#d1d5db' }">
                      {{ getProcessEval(process.id, 'criticality_score') > 0 ? getProcessEval(process.id, 'criticality_score').toFixed(1) : '—' }}
                    </div>
                  </td>
                  <td class="text-center">
                    <b-button
                      v-if="selectedSession.status === 'open'"
                      size="sm"
                      variant="outline-success"
                      class="fw-bold"
                      @click="selectProcessForEvaluation(process)"
                      title="Évaluer ce processus"
                    >
                      <i class="ti ti-pencil"></i>
                    </b-button>
                    <b-button
                      v-else
                      size="sm"
                      variant="outline-info"
                      class="fw-bold"
                      @click="selectProcessForEvaluation(process)"
                      title="Voir l'évaluation"
                    >
                      <i class="ti ti-eye"></i>
                    </b-button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </b-card-body>
      </b-card>
    </div>

    <!-- ========== MODALE: CRÉER SESSION ========== -->
    <b-modal v-model="showCreateSessionModal" title="Créer une nouvelle session" @ok="createSession" centered size="md">
      <b-form>
        <b-form-group label="Nom de la session" label-for="session-name">
          <b-form-input
            id="session-name"
            v-model="newSessionForm.name"
            placeholder="ex: Audit Q1 2025"
            required
          />
        </b-form-group>
        <b-form-group label="Couleur" label-for="session-color">
          <div class="d-flex gap-2 flex-wrap">
            <button
              v-for="color in sessionColors"
              :key="color"
              type="button"
              class="color-picker"
              :style="{ background: color, border: newSessionForm.color === color ? '3px solid #000' : '2px solid #ccc' }"
              @click.prevent="newSessionForm.color = color"
            />
          </div>
        </b-form-group>
      </b-form>
    </b-modal>

    <!-- ========== MODALE: DUPLIQUER SESSION ========== -->
    <b-modal v-model="showDuplicateModal" title="Dupliquer la session" @ok="duplicateSession" centered size="md">
      <b-form>
        <b-form-group label="Nom de la nouvelle session" label-for="dup-name">
          <b-form-input
            id="dup-name"
            v-model="duplicateForm.name"
            :placeholder="`${sessionToDuplicate?.name} (copie)`"
            required
          />
        </b-form-group>
        <p class="text-muted small mb-0">ℹ️ Toutes les évaluations seront copiées.</p>
      </b-form>
    </b-modal>

    <!-- ========== MODALE: ÉVALUATION ========== -->
    <b-modal
      v-model="showEvaluationModal"
      title="Évaluation du processus"
      size="xl"
      no-close-on-backdrop
      @hidden="closeEvaluationModal"
    >
      <div v-if="selectedProcess">
        <!-- HEADER PROCESSUS -->
        <div class="mb-4 p-3 bg-light rounded">
          <h6 class="fw-bold mb-1">{{ selectedProcess.code }} - {{ selectedProcess.name }}</h6>
          <small class="text-muted">Session: {{ selectedSession.name }}</small>
          <div class="mt-2 small">
            <span v-if="isSavingMaturité" class="badge bg-info">💾 Sauvegarde en cours...</span>
            <span v-else-if="maturitéSauvegardée" class="badge bg-success">✓ Maturité sauvegardée</span>
          </div>
        </div>

        <!-- PROGRESSION -->
        <div class="mb-4 p-3 bg-info bg-opacity-10 rounded">
          <h6 class="fw-bold mb-3">Progression d'évaluation</h6>
          <div class="progression-container">
            <div v-for="step in evaluationSteps" :key="step.key" class="progression-step" :class="{ completed: isStepEvaluated(step.key) }">
              <div class="step-circle" :style="{ background: getStepColor(step.key) }">
                <i :class="step.icon"></i>
              </div>
              <small class="step-label">{{ step.label }}</small>
              <small class="step-status" :style="{ color: getStepColor(step.key) }">{{ isStepEvaluated(step.key) ? '✓' : '◯' }}</small>
            </div>
          </div>
        </div>

        <!-- NAVIGATION TABS -->
        <div class="mb-3 d-flex gap-2 flex-wrap border-bottom pb-3">
          <b-button
            v-for="(step, idx) in evaluationSteps"
            :key="step.key"
            :variant="currentEvaluationStep === step.key ? 'success' : 'outline-success'"
            size="sm"
            class="fw-bold"
            @click="currentEvaluationStep = step.key"
          >
            {{ step.label }}
          </b-button>
        </div>

        <!-- ÉTAPE 1: MATURITÉ -->
        <div v-if="currentEvaluationStep === 'maturity'">
          <h6 class="fw-bold mb-3"><i class="ti ti-chart-radar-2 me-2"></i>Matrice de Maturité (12 critères)</h6>
          <b-row class="g-4">
            <!-- MATRICE -->
            <b-col lg="6">
              <p class="text-muted small mb-2">Cliquez pour évaluer → Auto-save + Radars mis à jour</p>
              <div class="table-responsive mb-3" style="max-height:400px;overflow-y:auto;">
                <table class="table table-sm">
                  <thead style="background:#10b981;color:white;position:sticky;top:0;">
                    <tr>
                      <th style="width:120px;">Critère</th>
                      <th v-for="lvl in [1,2,3,4,5]" :key="lvl" class="text-center" style="width:50px;">N{{ lvl }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="scale in groupedMaturity" :key="scale.code">
                      <td><strong class="text-success" style="font-size:0.85rem;">{{ scale.code }}</strong></td>
                      <td v-for="lvl in scale.levels" :key="lvl.level_score" class="text-center p-2">
                        <div
                          class="maturity-box"
                          :style="getMaturityBoxStyle(scale.code, lvl.level_score)"
                          @click="toggleMaturityEval(scale.code, lvl.level_score)"
                          :title="`${scale.code} - Niveau ${lvl.level_score}`"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="alert alert-info small mb-0">
                <strong>Sélectionné:</strong> {{ Object.keys(maturityEval).length }}/{{ groupedMaturity.length }} critères
              </div>
            </b-col>

            <!-- RADAR MATURITÉ -->
            <b-col lg="6">
              <h6 class="fw-bold mb-3"><i class="ti ti-chart-radar me-2"></i>Radar Maturité</h6>
              <div style="height:350px;background:#f9fafb;border:2px solid #10b981;border-radius:8px;padding:1rem;margin-bottom:1rem;">
                <canvas ref="maturityRadarRef"></canvas>
              </div>
              
              <div v-if="Object.keys(maturityEval).length > 0" class="alert alert-success small">
                <strong>📊 Maturité moyenne:</strong> {{ maturityAverage.toFixed(2) }}/5
              </div>
              <div v-else class="alert alert-info small">
                <strong>ℹ️</strong> Cochez des critères pour voir le radar
              </div>

              <!-- COMPARAISON MULTI-SESSIONS -->
              <b-form-group v-if="otherSessions.length > 0" label="Comparer avec:" label-class="small fw-bold" class="mt-3">
                <b-form-checkbox
                  v-for="session in otherSessions"
                  :key="session.id"
                  v-model="comparisonSessions"
                  :value="session.id"
                  class="mb-2"
                >
                  {{ session.name }}
                </b-form-checkbox>
              </b-form-group>

              <!-- RADAR COMPARAISON -->
              <div v-if="comparisonSessions.length > 0" style="height:300px;background:#f9fafb;border:2px solid #8b5cf6;border-radius:8px;padding:1rem;margin-top:1rem;">
                <h6 class="fw-bold mb-2" style="font-size:0.85rem;color:#8b5cf6;">🔗 Comparaison {{ comparisonSessions.length }} session(s)</h6>
                <canvas ref="maturityComparisonRadarRef"></canvas>
              </div>
            </b-col>
          </b-row>
        </div>

        <!-- ÉTAPE 2: MOTRICITÉ -->
        <div v-if="currentEvaluationStep === 'motricity'">
          <h6 class="fw-bold mb-3"><i class="ti ti-zap me-2"></i>Motricité (Capacité de changement)</h6>
          <div v-if="Object.keys(maturityEval).length === 0" class="alert alert-warning">
            ⚠️ Vous devez d'abord évaluer la <strong>Maturité</strong> (Étape 1)
          </div>
          <div v-else class="text-center">
            <p class="text-muted mb-3">Sélectionnez un niveau (1-5)</p>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
              <b-button
                v-for="level in [1,2,3,4,5]"
                :key="level"
                size="lg"
                :variant="motricityEval === level ? '' : 'outline-warning'"
                :style="motricityEval === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}"
                @click="setMotricity(level)"
                class="fw-bold"
              >
                {{ level }}
              </b-button>
            </div>
            <div v-if="motricityEval" class="mt-3 p-3 bg-light rounded">
              <small class="text-muted">Niveau {{ motricityEval }}</small>
              <p class="fw-bold mb-0">{{ getLevelLabel(motricityEval) }}</p>
            </div>
            <div style="height:300px;background:#f9fafb;border:2px solid #f59e0b;border-radius:8px;padding:1rem;margin-top:2rem;">
              <h6 class="fw-bold text-warning mb-3" style="font-size:0.9rem;">📊 Radar Motricité</h6>
              <canvas ref="motricityRadarRef"></canvas>
            </div>
          </div>
        </div>

        <!-- ÉTAPE 3: TRANSVERSALITÉ -->
        <div v-if="currentEvaluationStep === 'transversality'">
          <h6 class="fw-bold mb-3"><i class="ti ti-link me-2"></i>Transversalité (Impact cross-fonctionnel)</h6>
          <div v-if="motricityEval === null" class="alert alert-warning">
            ⚠️ Vous devez d'abord évaluer la <strong>Motricité</strong> (Étape 2)
          </div>
          <div v-else class="text-center">
            <p class="text-muted mb-3">Sélectionnez un niveau (1-5)</p>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
              <b-button
                v-for="level in [1,2,3,4,5]"
                :key="level"
                size="lg"
                :variant="transversalityEval === level ? '' : 'outline-info'"
                :style="transversalityEval === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}"
                @click="setTransversality(level)"
                class="fw-bold"
              >
                {{ level }}
              </b-button>
            </div>
            <div v-if="transversalityEval" class="mt-3 p-3 bg-light rounded">
              <small class="text-muted">Niveau {{ transversalityEval }}</small>
              <p class="fw-bold mb-0">{{ getLevelLabel(transversalityEval) }}</p>
            </div>
            <div style="height:300px;background:#f9fafb;border:2px solid #8b5cf6;border-radius:8px;padding:1rem;margin-top:2rem;">
              <h6 class="fw-bold mb-3" style="color:#8b5cf6;font-size:0.9rem;">📊 Radar Transversalité</h6>
              <canvas ref="transversalityRadarRef"></canvas>
            </div>
          </div>
        </div>

        <!-- ÉTAPE 4: STRATÉGIQUE -->
        <div v-if="currentEvaluationStep === 'strategic'">
          <h6 class="fw-bold mb-3"><i class="ti ti-target me-2"></i>Poids Stratégique</h6>
          <div v-if="transversalityEval === null" class="alert alert-warning">
            ⚠️ Vous devez d'abord évaluer la <strong>Transversalité</strong> (Étape 3)
          </div>
          <div v-else class="text-center">
            <p class="text-muted mb-3">Sélectionnez un niveau (1-5)</p>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
              <b-button
                v-for="level in [1,2,3,4,5]"
                :key="level"
                size="lg"
                :variant="strategicEval === level ? '' : 'outline-danger'"
                :style="strategicEval === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}"
                @click="setStrategic(level)"
                class="fw-bold"
              >
                {{ level }}
              </b-button>
            </div>
            <div v-if="strategicEval" class="mt-3 p-3 bg-light rounded">
              <small class="text-muted">Niveau {{ strategicEval }}</small>
              <p class="fw-bold mb-0">{{ getLevelLabel(strategicEval) }}</p>
            </div>
            <div style="height:300px;background:#f9fafb;border:2px solid #ec4899;border-radius:8px;padding:1rem;margin-top:2rem;">
              <h6 class="fw-bold mb-3" style="color:#ec4899;font-size:0.9rem;">📊 Radar Stratégique</h6>
              <canvas ref="strategicRadarRef"></canvas>
            </div>
          </div>
        </div>

        <!-- ÉTAPE 5: RÉSUMÉ -->
        <div v-if="currentEvaluationStep === 'summary'">
          <h6 class="fw-bold mb-4"><i class="ti ti-radar me-2"></i>Résumé de l'évaluation</h6>
          
          <b-row class="g-4">
            <b-col md="6">
              <h6 class="fw-bold text-center mb-3 text-success">📊 Radar Global</h6>
              <div style="height:350px;">
                <canvas ref="summaryRadarRef"></canvas>
              </div>
            </b-col>
            <b-col md="6">
              <h6 class="fw-bold mb-3">Scores détaillés</h6>
              <div class="list-group">
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><strong>Maturité</strong></span>
                    <span class="badge" :style="{ background: getColorByScore(maturityAverage) }">{{ maturityAverage.toFixed(2) }}/5</span>
                  </div>
                  <small class="text-muted d-block mt-1">{{ Object.keys(maturityEval).length }}/{{ groupedMaturity.length }} critères</small>
                </div>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><strong>Motricité</strong></span>
                    <span v-if="motricityEval" class="badge" :style="{ background: getColorByLevel(motricityEval) }">{{ motricityEval }}/5</span>
                    <span v-else class="text-muted">—</span>
                  </div>
                </div>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><strong>Transversalité</strong></span>
                    <span v-if="transversalityEval" class="badge" :style="{ background: getColorByLevel(transversalityEval) }">{{ transversalityEval }}/5</span>
                    <span v-else class="text-muted">—</span>
                  </div>
                </div>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><strong>Stratégique</strong></span>
                    <span v-if="strategicEval" class="badge" :style="{ background: getColorByLevel(strategicEval) }">{{ strategicEval }}/5</span>
                    <span v-else class="text-muted">—</span>
                  </div>
                </div>
                <div class="list-group-item bg-success bg-opacity-10">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><strong>Score Global</strong></span>
                    <span class="badge bg-success">{{ globalEvalScore.toFixed(2) }}/5</span>
                  </div>
                </div>
              </div>
            </b-col>
          </b-row>

          <div class="alert alert-info small mt-4" role="alert">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Légende:</strong>
            <span class="ms-2">🔴 1-2 (Initial) | 🟠 2-3 (Reproductible) | 🟡 3-4 (Défini) | 🟢 4-5 (Optimisé)</span>
          </div>
        </div>
      </div>

      <!-- BOUTONS D'ACTION -->
      <template #modal-footer>
        <b-button variant="secondary" @click="closeEvaluationModal">Annuler</b-button>
        <div v-if="currentEvaluationStep === 'summary'">
          <b-button
            variant="success"
            @click="finalizeEvaluation"
            :disabled="!canSaveEvaluation"
            class="fw-bold"
          >
            <i class="ti ti-check me-1"></i>Terminer
          </b-button>
        </div>
        <div v-else>
          <b-button
            variant="outline-secondary"
            @click="previousStep"
            v-if="currentEvaluationStep !== 'maturity'"
            class="fw-bold"
          >
            <i class="ti ti-arrow-left me-1"></i>Précédent
          </b-button>
          <b-button
            variant="success"
            @click="nextStep"
            :disabled="!canGoNext"
            class="fw-bold"
          >
            Suivant <i class="ti ti-arrow-right ms-1"></i>
          </b-button>
        </div>
      </template>
    </b-modal>

    <!-- ========== MODALE: RAPPORT AMÉLIORÉ ========== -->
    <b-modal
      id="reportModal"
      v-model="showReportModal"
      title="📊 Rapport Professionnel"
      size="xl"
      no-close-on-backdrop
      @hidden="closeReportModal"
    >
      <div v-if="!reportGenerated">
        <!-- ÉTAPE 1: SÉLECTION -->
        <div v-if="reportStep === 'selection'">
          <h6 class="fw-bold mb-4">🎯 Configuration du rapport</h6>
          
          <!-- TYPE DE RAPPORT -->
          <b-form-group label="📋 Type:" label-class="fw-bold" class="mb-4">
            <b-form-radio-group v-model="reportConfig.type">
              <b-form-radio value="single">Une session</b-form-radio>
              <b-form-radio value="multi">Plusieurs sessions</b-form-radio>
              <b-form-radio value="all">Toutes les sessions</b-form-radio>
            </b-form-radio-group>
          </b-form-group>

          <!-- SÉLECTION SESSIONS -->
          <b-form-group label="🎪 Sessions:" label-class="fw-bold" class="mb-4">
            <div class="border rounded p-3 bg-light" style="max-height:250px;overflow-y:auto;">
              <div v-if="reportConfig.type === 'single'">
                <b-form-radio
                  v-for="session in activeSessions"
                  :key="session.id"
                  v-model="reportConfig.selectedSessionId"
                  :value="session.id"
                  class="mb-2 d-block"
                >
                  <strong>{{ session.name }}</strong> <span class="badge bg-info">{{ session.evaluated_count }}/{{ props.processes?.length || 0 }}</span>
                </b-form-radio>
              </div>
              <div v-else>
                <b-form-checkbox
                  v-for="session in activeSessions"
                  :key="session.id"
                  v-model="reportConfig.selectedSessionIds"
                  :value="session.id"
                  class="mb-2 d-block"
                >
                  <strong>{{ session.name }}</strong> <span class="badge bg-info">{{ session.evaluated_count }}/{{ props.processes?.length || 0 }}</span>
                </b-form-checkbox>
              </div>
            </div>
          </b-form-group>

          <!-- SÉLECTION PROCESSUS -->
          <b-form-group label="🔄 Processus:" label-class="fw-bold" class="mb-4">
            <div class="d-flex gap-2 mb-2">
              <b-button size="sm" variant="outline-primary" @click="selectAllProcesses">Tous</b-button>
              <b-button size="sm" variant="outline-secondary" @click="deselectAllProcesses">Aucun</b-button>
            </div>
            <div class="border rounded p-3 bg-light" style="max-height:250px;overflow-y:auto;">
              <b-form-checkbox
                v-for="process in props.processes"
                :key="process.id"
                v-model="reportConfig.selectedProcesses"
                :value="process.id"
                class="mb-2 d-block"
              >
                <strong>{{ process.code }}</strong> — {{ process.name }}
              </b-form-checkbox>
            </div>
          </b-form-group>

          <!-- OPTIONS -->
          <b-form-group label="⚙️ Options:" label-class="fw-bold">
            <b-form-checkbox v-model="reportConfig.includeCharts" class="mb-2">
              📈 Graphiques (radars, barres, courbes)
            </b-form-checkbox>
            <b-form-checkbox v-model="reportConfig.includeSummary" class="mb-2">
              📊 Statistiques (min, max, moyenne)
            </b-form-checkbox>
            <b-form-checkbox v-model="reportConfig.includeMaturityComparison" class="mb-2">
              🔗 Comparer matrices maturité
            </b-form-checkbox>
            <b-form-checkbox v-model="reportConfig.exportPDF" class="mb-2">
              📄 Exporter PDF
            </b-form-checkbox>
            <b-form-checkbox v-model="reportConfig.exportExcel" class="mb-2">
              📊 Exporter Excel
            </b-form-checkbox>
          </b-form-group>
        </div>

        <!-- ÉTAPE 2: APERÇU -->
        <div v-if="reportStep === 'preview'">
          <h6 class="fw-bold mb-3"><i class="ti ti-eye me-2"></i>Aperçu</h6>
          
          <div class="p-3 bg-light rounded mb-4 border-start border-4" style="border-color:#10b981;">
            <div class="row">
              <div class="col-6">
                <small><strong>Type:</strong> {{ reportConfig.type }}</small><br />
                <small><strong>Sessions:</strong> {{ reportConfig.type === 'single' ? 1 : reportConfig.selectedSessionIds.length }}</small>
              </div>
              <div class="col-6">
                <small><strong>Processus:</strong> {{ reportConfig.selectedProcesses.length }}</small><br />
                <small><strong>Formats:</strong> {{ reportConfig.exportPDF ? 'PDF ' : '' }}{{ reportConfig.exportExcel ? 'Excel' : '' }}</small>
              </div>
            </div>
          </div>

          <h6 class="fw-bold mb-3">Tableau résumé</h6>
          <div class="table-responsive border rounded" style="max-height:400px;overflow:auto;">
            <table class="table table-sm table-hover mb-0">
              <thead style="background:#10b981;color:white;position:sticky;top:0;">
                <tr>
                  <th>Processus</th>
                  <th v-for="session in reportSessions" :key="session.id" class="text-center" style="min-width:120px;">
                    {{ session.name }}
                  </th>
                  <th class="text-center" style="background:#059669;">Moy</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="process in getReportProcesses()" :key="process.id">
                  <td><strong>{{ process.code }}</strong></td>
                  <td v-for="session in reportSessions" :key="session.id" class="text-center">
                    <span class="badge" :style="{ background: getColorByScore(getReportScore(session.id, process.id)) }">
                      {{ getReportScore(session.id, process.id).toFixed(1) }}
                    </span>
                  </td>
                  <td class="text-center"><strong>{{ getProcessAvgScore(process.id).toFixed(1) }}</strong></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ÉTAPE 3: GÉNÉRATION -->
        <div v-if="reportStep === 'generating'" class="text-center py-4">
          <b-spinner class="mb-3"></b-spinner>
          <h6>Génération...</h6>
          <small class="text-muted d-block mb-3">{{ generationMessage }}</small>
          <div class="progress">
            <div class="progress-bar" :style="{ width: generationProgress + '%', background: '#10b981' }"></div>
          </div>
        </div>
      </div>

      <!-- SUCCÈS -->
      <div v-else class="text-center py-4">
        <div style="font-size:4rem;margin-bottom:1.5rem;">✅</div>
        <h5>Rapport généré!</h5>
        <p class="text-muted mb-3">{{ generatedReportInfo }}</p>
        
        <div class="d-flex flex-column gap-2">
          <b-alert v-if="reportConfig.exportPDF" variant="success" class="mb-0">
            <a href="#" @click.prevent="downloadPDF" class="alert-link fw-bold">
              <i class="ti ti-file-pdf me-2"></i>Télécharger PDF
            </a>
          </b-alert>
          <b-alert v-if="reportConfig.exportExcel" variant="success" class="mb-0">
            <a href="#" @click.prevent="downloadExcel" class="alert-link fw-bold">
              <i class="ti ti-file-spreadsheet me-2"></i>Télécharger Excel
            </a>
          </b-alert>
        </div>
      </div>

      <!-- BOUTONS -->
      <template #modal-footer>
        <b-button variant="secondary" @click="closeReportModal">Fermer</b-button>
        <b-button
          v-if="reportStep === 'selection'"
          variant="success"
          @click="reportStep = 'preview'"
          :disabled="reportConfig.selectedProcesses.length === 0"
        >
          Aperçu →
        </b-button>
        <b-button
          v-if="reportStep === 'preview'"
          variant="outline-secondary"
          @click="reportStep = 'selection'"
        >
          ← Retour
        </b-button>
        <b-button
          v-if="reportStep === 'preview'"
          variant="success"
          @click="generateReport"
        >
          Générer
        </b-button>
        <b-button v-if="reportGenerated" variant="success" @click="closeReportModal">
          Terminé
        </b-button>
      </template>
    </b-modal>
  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layouts/VerticalLayout.vue"
import axios from "axios"
import { ref, reactive, computed, onMounted, nextTick, watch } from "vue"
import Chart from 'chart.js/auto'

const props = defineProps({
  user: Object,
  link: Object,
  processes: Array,
  sessions: Array,
  maturityLevels: Array,
})

// STATE - SESSIONS
const sessions = ref(props.sessions || [])
const selectedSession = ref(null)
const selectedProcess = ref(null)
const showCreateSessionModal = ref(false)
const showDuplicateModal = ref(false)
const showEvaluationModal = ref(false)
const successMessage = ref("")
const sessionToDuplicate = ref(null)

// STATE - MATURITÉ
const isSavingMaturité = ref(false)
const maturitéSauvegardée = ref(false)
const maturityEval = reactive({})
const motricityEval = ref(null)
const transversalityEval = ref(null)
const strategicEval = ref(null)
const currentEvaluationStep = ref('maturity')
const comparisonSessions = ref([])

// STATE - SESSION ÉVALS
const sessionEvaluations = reactive({})

// STATE - RAPPORT
const showReportModal = ref(false)
const reportStep = ref('selection')
const reportGenerated = ref(false)
const generationMessage = ref('')
const generationProgress = ref(0)
const reportId = ref(null)
const generatedReportInfo = ref('')

const reportConfig = reactive({
  type: 'single',
  selectedSessionId: null,
  selectedSessionIds: [],
  selectedProcesses: [],
  includeCharts: true,
  includeSummary: true,
  includeMaturityComparison: true,
  exportPDF: true,
  exportExcel: true,
})

// COLORS & CONFIG
const sessionColors = [
  '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'
]

const evaluationSteps = [
  { key: 'maturity', label: '📋 Maturité', icon: 'ti ti-mountain' },
  { key: 'motricity', label: '⚡ Motricité', icon: 'ti ti-zap' },
  { key: 'transversality', label: '🔗 Transversalité', icon: 'ti ti-link' },
  { key: 'strategic', label: '🎯 Stratégique', icon: 'ti ti-target' },
  { key: 'summary', label: '📊 Résumé', icon: 'ti ti-radar' }
]

// FORMS
const newSessionForm = reactive({
  name: '',
  color: '#10b981'
})

const duplicateForm = reactive({
  name: ''
})

// CHART REFS
const maturityRadarRef = ref(null)
const maturityComparisonRadarRef = ref(null)
const motricityRadarRef = ref(null)
const transversalityRadarRef = ref(null)
const strategicRadarRef = ref(null)
const summaryRadarRef = ref(null)

let radarCharts = {}

// ========== COMPUTED ==========

const activeSessions = computed(() => sessions.value.filter(s => s.status !== 'archived'))
const archivedSessions = computed(() => sessions.value.filter(s => s.status === 'archived'))
const canCreateSession = computed(() => activeSessions.value.length < 5)

const otherSessions = computed(() => {
  return sessions.value.filter(s => s.id !== selectedSession.value?.id && s.status !== 'archived')
})

const groupedMaturity = computed(() => {
  const map = {}
  props.maturityLevels?.forEach(l => {
    if (!map[l.scale_code]) {
      map[l.scale_code] = { code: l.scale_code, label: l.scale_label, levels: [] }
    }
    map[l.scale_code].levels.push(l)
  })
  return Object.values(map)
})

const sessionEvaluatedCount = computed(() => {
  if (!selectedSession.value) return 0
  const evals = sessionEvaluations[selectedSession.value.id] || {}
  const evaluated = new Set()
  Object.values(evals).forEach(e => {
    if (e?.criticality_score) evaluated.add(e.process_id)
  })
  return evaluated.size
})

const sessionAvgScore = computed(() => {
  if (!selectedSession.value) return 0
  const evals = sessionEvaluations[selectedSession.value.id] || {}
  const scores = Object.values(evals)
    .filter(e => e?.criticality_score)
    .map(e => e.criticality_score)
  return scores.length ? scores.reduce((a, b) => a + b, 0) / scores.length : 0
})

const progressPercent = computed(() => {
  return props.processes?.length ? (sessionEvaluatedCount.value / props.processes.length) * 100 : 0
})

const globalAvg = computed(() => {
  const allScores = []
  Object.values(sessionEvaluations).forEach(evals => {
    Object.values(evals)
      .filter(e => e?.criticality_score)
      .forEach(e => allScores.push(e.criticality_score))
  })
  return allScores.length ? allScores.reduce((a, b) => a + b, 0) / allScores.length : 0
})

const maturityAverage = computed(() => {
  const vals = Object.values(maturityEval)
  return vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : 0
})

const globalEvalScore = computed(() => {
  const vals = [
    maturityAverage.value,
    motricityEval.value,
    transversalityEval.value,
    strategicEval.value
  ].filter(v => v !== null)
  return vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : 0
})

const canGoNext = computed(() => {
  if (currentEvaluationStep.value === 'maturity') return Object.keys(maturityEval).length > 0
  if (currentEvaluationStep.value === 'motricity') return motricityEval.value !== null
  if (currentEvaluationStep.value === 'transversality') return transversalityEval.value !== null
  if (currentEvaluationStep.value === 'strategic') return strategicEval.value !== null
  return false
})

const canSaveEvaluation = computed(() => {
  return Object.keys(maturityEval).length > 0 && motricityEval.value !== null && 
         transversalityEval.value !== null && strategicEval.value !== null
})

// RAPPORT COMPUTED
const reportSessions = computed(() => {
  if (reportConfig.type === 'single' && reportConfig.selectedSessionId) {
    return sessions.value.filter(s => s.id === reportConfig.selectedSessionId)
  } else if (reportConfig.type === 'multi') {
    return sessions.value.filter(s => reportConfig.selectedSessionIds.includes(s.id))
  } else {
    return sessions.value.filter(s => s.status !== 'archived')
  }
})

// ========== FUNCTIONS ==========

const getColorByLevel = (level) => {
  const colors = { 1: '#ef4444', 2: '#ef4444', 3: '#f59e0b', 4: '#10b981', 5: '#10b981' }
  return colors[level] || '#d1d5db'
}

const getColorByScore = (score) => {
  if (!score || score === 0) return '#d1d5db'
  if (score >= 4) return '#10b981'
  if (score >= 3) return '#f59e0b'
  if (score >= 2) return '#ef4444'
  return '#d1d5db'
}

const getLevelLabel = (level) => {
  const labels = {
    1: 'Initial',
    2: 'Reproductible',
    3: 'Défini',
    4: 'Mesuré',
    5: 'Optimisé'
  }
  return labels[level] || ''
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', { year: 'numeric', month: 'short', day: 'numeric' })
}

const getMaturityBoxStyle = (code, level) => {
  const selected = maturityEval[code] === level
  const color = getColorByLevel(level)
  if (selected) {
    return {
      background: color,
      color: 'white',
      border: `2px solid ${color}`,
      fontWeight: 'bold'
    }
  }
  return {
    background: '#f9fafb',
    border: '1px solid #d1d5db',
    color: '#374151'
  }
}

const getProcessEval = (processId, field) => {
  if (!selectedSession.value) return 0
  const evals = sessionEvaluations[selectedSession.value.id] || {}
  const key = `${selectedSession.value.id}_${processId}`
  return evals[key]?.[field] || 0
}

const toggleMaturityEval = async (code, level) => {
  if (maturityEval[code] === level) {
    delete maturityEval[code]
  } else {
    maturityEval[code] = level
  }

  await saveMaturityStep()
  await renderMaturityRadar()
  await renderMaturityComparisonRadar()
}

const isStepEvaluated = (step) => {
  if (step === 'maturity') return Object.keys(maturityEval).length > 0
  if (step === 'motricity') return motricityEval.value !== null
  if (step === 'transversality') return transversalityEval.value !== null
  if (step === 'strategic') return strategicEval.value !== null
  return false
}

const getStepColor = (step) => {
  return isStepEvaluated(step) ? '#10b981' : '#d1d5db'
}

// RADARS
const renderMaturityRadar = async () => {
  await nextTick()
  if (!maturityRadarRef.value) return

  if (radarCharts.maturity) radarCharts.maturity.destroy()

  const ctx = maturityRadarRef.value.getContext('2d')
  radarCharts.maturity = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: groupedMaturity.value.map(g => g.code),
      datasets: [{
        label: 'Maturité',
        data: groupedMaturity.value.map(g => maturityEval[g.code] || 0),
        borderColor: selectedSession.value.color || '#10b981',
        backgroundColor: (selectedSession.value.color || '#10b981') + '30',
        borderWidth: 2,
        pointRadius: 5,
        pointBackgroundColor: selectedSession.value.color || '#10b981',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } } },
      plugins: { legend: { display: false } }
    }
  })
}

const renderMaturityComparisonRadar = async () => {
  if (comparisonSessions.value.length === 0) {
    if (radarCharts.maturityComp) {
      radarCharts.maturityComp.destroy()
      radarCharts.maturityComp = null
    }
    return
  }

  await nextTick()
  if (!maturityComparisonRadarRef.value) return

  try {
    const datasets = []
    const currentSession = selectedSession.value

    // Session actuelle
    datasets.push({
      label: currentSession.name,
      data: groupedMaturity.value.map(g => maturityEval[g.code] || 0),
      borderColor: currentSession.color || '#10b981',
      backgroundColor: (currentSession.color || '#10b981') + '30',
      borderWidth: 2,
      pointRadius: 4,
      pointBackgroundColor: currentSession.color || '#10b981'
    })

    // Sessions comparées
    for (const sessionId of comparisonSessions.value) {
      const res = await axios.get(route('process.core.evaluations.load'), {
        params: {
          session_id: sessionId,
          process_id: selectedProcess.value.id
        }
      })

      const compSession = sessions.value.find(s => s.id === sessionId)
      datasets.push({
        label: compSession.name,
        data: groupedMaturity.value.map(g => res.data.maturity?.[g.code] || 0),
        borderColor: compSession.color || '#8b5cf6',
        backgroundColor: (compSession.color || '#8b5cf6') + '30',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: compSession.color || '#8b5cf6'
      })
    }

    if (radarCharts.maturityComp) radarCharts.maturityComp.destroy()

    const ctx = maturityComparisonRadarRef.value.getContext('2d')
    radarCharts.maturityComp = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: groupedMaturity.value.map(g => g.code),
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } } },
        plugins: { legend: { display: true, position: 'top' } }
      }
    })
  } catch (e) {
    console.error('Erreur comparaison', e)
  }
}

const renderMotricityRadar = async () => {
  await nextTick()
  if (!motricityRadarRef.value) return

  if (radarCharts.motricity) radarCharts.motricity.destroy()

  const ctx = motricityRadarRef.value.getContext('2d')
  radarCharts.motricity = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Motricité', 'Axe 2', 'Axe 3', 'Axe 4'],
      datasets: [{
        label: 'Motricité',
        data: [motricityEval.value || 0, 0, 0, 0],
        borderColor: selectedSession.value.color || '#f59e0b',
        backgroundColor: (selectedSession.value.color || '#f59e0b') + '30',
        borderWidth: 2,
        pointRadius: 5,
        pointBackgroundColor: selectedSession.value.color || '#f59e0b',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } } },
      plugins: { legend: { display: false } }
    }
  })
}

const renderTransversalityRadar = async () => {
  await nextTick()
  if (!transversalityRadarRef.value) return

  if (radarCharts.transversality) radarCharts.transversality.destroy()

  const ctx = transversalityRadarRef.value.getContext('2d')
  radarCharts.transversality = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Transversalité', 'Axe 2', 'Axe 3', 'Axe 4'],
      datasets: [{
        label: 'Transversalité',
        data: [transversalityEval.value || 0, 0, 0, 0],
        borderColor: selectedSession.value.color || '#8b5cf6',
        backgroundColor: (selectedSession.value.color || '#8b5cf6') + '30',
        borderWidth: 2,
        pointRadius: 5,
        pointBackgroundColor: selectedSession.value.color || '#8b5cf6',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } } },
      plugins: { legend: { display: false } }
    }
  })
}

const renderStrategicRadar = async () => {
  await nextTick()
  if (!strategicRadarRef.value) return

  if (radarCharts.strategic) radarCharts.strategic.destroy()

  const ctx = strategicRadarRef.value.getContext('2d')
  radarCharts.strategic = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Stratégique', 'Axe 2', 'Axe 3', 'Axe 4'],
      datasets: [{
        label: 'Stratégique',
        data: [strategicEval.value || 0, 0, 0, 0],
        borderColor: selectedSession.value.color || '#ec4899',
        backgroundColor: (selectedSession.value.color || '#ec4899') + '30',
        borderWidth: 2,
        pointRadius: 5,
        pointBackgroundColor: selectedSession.value.color || '#ec4899',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } } },
      plugins: { legend: { display: false } }
    }
  })
}

const renderSummaryRadar = async () => {
  await nextTick()
  if (!summaryRadarRef.value) return

  if (radarCharts.summary) radarCharts.summary.destroy()

  const ctx = summaryRadarRef.value.getContext('2d')
  radarCharts.summary = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Maturité', 'Motricité', 'Transversalité', 'Stratégique'],
      datasets: [{
        label: selectedProcess.value.name,
        data: [
          maturityAverage.value || 0,
          motricityEval.value || 0,
          transversalityEval.value || 0,
          strategicEval.value || 0
        ],
        borderColor: selectedSession.value.color || '#10b981',
        backgroundColor: (selectedSession.value.color || '#10b981') + '30',
        borderWidth: 2,
        pointRadius: 6,
        pointBackgroundColor: selectedSession.value.color || '#10b981',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } } },
      plugins: { legend: { display: true, position: 'top' } }
    }
  })
}

// SESSION ACTIONS
const selectSession = async (session) => {
  selectedSession.value = session
  await loadSessionEvaluations()
}

const loadSessionEvaluations = async () => {
  if (!selectedSession.value) return
  try {
    const evals = {}
    for (let process of props.processes) {
      const res = await axios.get(route('process.core.evaluations.load'), {
        params: {
          session_id: selectedSession.value.id,
          process_id: process.id
        }
      })
      const key = `${selectedSession.value.id}_${process.id}`
      evals[key] = res.data.axes || {}
    }
    sessionEvaluations[selectedSession.value.id] = evals
  } catch (e) {
    console.error('Erreur load evals', e)
  }
}

const selectProcessForEvaluation = async (process) => {
  selectedProcess.value = process

  const key = `${selectedSession.value.id}_${process.id}`
  const res = await axios.get(route('process.core.evaluations.load'), {
    params: {
      session_id: selectedSession.value.id,
      process_id: process.id
    }
  })

  Object.keys(maturityEval).forEach(k => delete maturityEval[k])
  if (res.data.maturity) {
    Object.assign(maturityEval, res.data.maturity)
  }

  motricityEval.value = res.data.axes?.motricity_score || null
  transversalityEval.value = res.data.axes?.transversality_score || null
  strategicEval.value = res.data.axes?.strategic_score || null

  currentEvaluationStep.value = 'maturity'
  comparisonSessions.value = []
  maturitéSauvegardée.value = false
  showEvaluationModal.value = true
}

const setMotricity = async (level) => {
  motricityEval.value = level
  await saveMotricityStep()
  renderMotricityRadar()
}

const setTransversality = async (level) => {
  transversalityEval.value = level
  await saveTransversalityStep()
  renderTransversalityRadar()
}

const setStrategic = async (level) => {
  strategicEval.value = level
  await saveStrategicStep()
  renderStrategicRadar()
}

const saveMaturityStep = async () => {
  if (Object.keys(maturityEval).length === 0) return

  isSavingMaturité.value = true
  maturitéSauvegardée.value = false

  try {
    const evaluations = Object.entries(maturityEval).map(([code, level]) => ({
      process_id: selectedProcess.value.id,
      criterion_code: code,
      level_score: level
    }))

    await axios.post(route('process.core.evaluations.maturity.save'), {
      session_id: selectedSession.value.id,
      evaluations
    })

    maturitéSauvegardée.value = true
  } catch (e) {
    console.error('Erreur maturité', e)
    alert('Erreur lors de la sauvegarde')
  } finally {
    isSavingMaturité.value = false
  }
}

const saveMotricityStep = async () => {
  if (!motricityEval.value) return

  try {
    await axios.post(route('process.core.evaluations.axis.save'), {
      session_id: selectedSession.value.id,
      process_id: selectedProcess.value.id,
      axis: 'motricity',
      score: motricityEval.value
    })
  } catch (e) {
    console.error('Erreur motricité', e)
  }
}

const saveTransversalityStep = async () => {
  if (!transversalityEval.value) return

  try {
    await axios.post(route('process.core.evaluations.axis.save'), {
      session_id: selectedSession.value.id,
      process_id: selectedProcess.value.id,
      axis: 'transversality',
      score: transversalityEval.value
    })
  } catch (e) {
    console.error('Erreur transversalité', e)
  }
}

const saveStrategicStep = async () => {
  if (!strategicEval.value) return

  try {
    await axios.post(route('process.core.evaluations.axis.save'), {
      session_id: selectedSession.value.id,
      process_id: selectedProcess.value.id,
      axis: 'strategic',
      score: strategicEval.value
    })
  } catch (e) {
    console.error('Erreur stratégique', e)
  }
}

const nextStep = async () => {
  const stepKeys = ['maturity', 'motricity', 'transversality', 'strategic', 'summary']
  const currentIdx = stepKeys.indexOf(currentEvaluationStep.value)
  if (currentIdx < stepKeys.length - 1) {
    currentEvaluationStep.value = stepKeys[currentIdx + 1]
    await nextTick()
    if (currentEvaluationStep.value === 'motricity') {
      await renderMotricityRadar()
    } else if (currentEvaluationStep.value === 'transversality') {
      await renderTransversalityRadar()
    } else if (currentEvaluationStep.value === 'strategic') {
      await renderStrategicRadar()
    } else if (currentEvaluationStep.value === 'summary') {
      await renderSummaryRadar()
    }
  }
}

const previousStep = () => {
  const stepKeys = ['maturity', 'motricity', 'transversality', 'strategic', 'summary']
  const currentIdx = stepKeys.indexOf(currentEvaluationStep.value)
  if (currentIdx > 0) {
    currentEvaluationStep.value = stepKeys[currentIdx - 1]
  }
}

const finalizeEvaluation = async () => {
  successMessage.value = '✓ Évaluation enregistrée'
  showEvaluationModal.value = false
  await loadSessionEvaluations()
  await nextTick()
  setTimeout(() => { successMessage.value = '' }, 3000)
}

const closeEvaluationModal = () => {
  showEvaluationModal.value = false
  selectedProcess.value = null
  Object.keys(maturityEval).forEach(k => delete maturityEval[k])
  motricityEval.value = null
  transversalityEval.value = null
  strategicEval.value = null
  currentEvaluationStep.value = 'maturity'
  comparisonSessions.value = []
  maturitéSauvegardée.value = false
  
  Object.values(radarCharts).forEach(chart => {
    if (chart) chart.destroy()
  })
  radarCharts = {}
}

const createSession = async () => {
  if (!newSessionForm.name) {
    alert('Veuillez entrer un nom')
    return
  }

  try {
    const res = await axios.post(route('process.core.evaluations.sessions.create'), {
      entity_id: props.link.entity_id,
      function_id: props.link.function_id,
      name: newSessionForm.name,
      color: newSessionForm.color
    })

    showCreateSessionModal.value = false
    newSessionForm.name = ''
    newSessionForm.color = '#10b981'

    await nextTick()
    location.reload()
  } catch (e) {
    console.error('Erreur create session', e)
    alert('Erreur: ' + (e.response?.data?.error || e.message))
  }
}

const duplicateSession = async () => {
  if (!duplicateForm.name || !sessionToDuplicate.value) return

  try {
    await axios.post(
      route('process.core.evaluations.sessions.duplicate'),
      {
        source_session_id: sessionToDuplicate.value.id,
        name: duplicateForm.name
      }
    )

    showDuplicateModal.value = false
    duplicateForm.name = ''
    sessionToDuplicate.value = null

    await nextTick()
    location.reload()
  } catch (e) {
    console.error('Erreur duplicate', e)
    alert('Erreur: ' + (e.response?.data?.error || e.message))
  }
}

const archiveSessionConfirm = async (session) => {
  if (!confirm('Archiver cette session?')) return

  try {
    await axios.post(route('process.core.evaluations.sessions.archive'), {
      session_id: session.id
    })

    const idx = sessions.value.findIndex(s => s.id === session.id)
    if (idx > -1) {
      sessions.value[idx].status = 'archived'
    }
    successMessage.value = '✓ Session archivée'
    setTimeout(() => { successMessage.value = '' }, 2000)
  } catch (e) {
    console.error('Erreur archive', e)
    alert('Erreur: ' + (e.response?.data?.error || e.message))
  }
}

const deleteSessionConfirm = async (session) => {
  if (!confirm('Supprimer cette session et toutes ses évaluations?')) return

  try {
    await axios.post(route('process.core.evaluations.sessions.delete'), {
      session_id: session.id
    })

    const index = sessions.value.findIndex(s => s.id === session.id)
    if (index > -1) sessions.value.splice(index, 1)

    if (selectedSession.value?.id === session.id) {
      selectedSession.value = null
    }
    successMessage.value = '✓ Session supprimée'
    setTimeout(() => { successMessage.value = '' }, 2000)
  } catch (e) {
    console.error('Erreur delete', e)
    alert('Erreur: ' + (e.response?.data?.error || e.message))
  }
}

// RAPPORT METHODS
const selectAllProcesses = () => {
  reportConfig.selectedProcesses = props.processes.map(p => p.id)
}

const deselectAllProcesses = () => {
  reportConfig.selectedProcesses = []
}

const getReportProcesses = () => {
  return props.processes.filter(p => reportConfig.selectedProcesses.includes(p.id))
}

const getReportScore = (sessionId, processId) => {
  const key = `${sessionId}_${processId}`
  const evals = sessionEvaluations[sessionId] || {}
  return evals[key]?.criticality_score || 0
}

const getProcessAvgScore = (processId) => {
  const scores = reportSessions.value
    .map(s => getReportScore(s.id, processId))
    .filter(s => s > 0)
  return scores.length ? scores.reduce((a, b) => a + b, 0) / scores.length : 0
}

const getSessionAvgScore = (sessionId) => {
  const scores = reportConfig.selectedProcesses
    .map(pId => getReportScore(sessionId, pId))
    .filter(s => s > 0)
  return scores.length ? scores.reduce((a, b) => a + b, 0) / scores.length : 0
}

const getSessionMaxScore = (sessionId) => {
  const scores = reportConfig.selectedProcesses
    .map(pId => getReportScore(sessionId, pId))
    .filter(s => s > 0)
  return scores.length ? Math.max(...scores) : 0
}

const getSessionMinScore = (sessionId) => {
  const scores = reportConfig.selectedProcesses
    .map(pId => getReportScore(sessionId, pId))
    .filter(s => s > 0)
  return scores.length ? Math.min(...scores) : 0
}

const generateReport = async () => {
  reportStep.value = 'generating'
  reportGenerated.value = false
  generationProgress.value = 0
  generationMessage.value = 'Compilation des données...'

  try {
    // ÉTAPE 1: Preview
    generationProgress.value = 20
    generationMessage.value = 'Génération aperçu...'

    const payload = {
      type: reportConfig.type,
      selected_session_id: reportConfig.selectedSessionId,
      session_ids: reportConfig.selectedSessionIds,
      selected_processes: reportConfig.selectedProcesses,
    }

    // ÉTAPE 2: PDF
    if (reportConfig.exportPDF) {
      generationProgress.value = 50
      generationMessage.value = 'Génération PDF...'

      await axios.post(
        route('process.core.evaluations.report.export-pdf'),
        payload,
        { responseType: 'blob' }
      ).then(response => {
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `rapport-${Date.now()}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.parentNode.removeChild(link)
      })
    }

    // ÉTAPE 3: Excel
    if (reportConfig.exportExcel) {
      generationProgress.value = 80
      generationMessage.value = 'Génération Excel...'

      await axios.post(
        route('process.core.evaluations.report.export-excel'),
        payload,
        { responseType: 'blob' }
      ).then(response => {
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `rapport-${Date.now()}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.parentNode.removeChild(link)
      })
    }

    generationProgress.value = 100
    generationMessage.value = 'Terminé!'
    reportGenerated.value = true
    generatedReportInfo.value = `✅ Rapport généré! (${reportConfig.exportPDF ? 'PDF + ' : ''}${reportConfig.exportExcel ? 'Excel' : ''})`

    successMessage.value = '✓ Rapport généré'
    setTimeout(() => { successMessage.value = '' }, 3000)

  } catch (error) {
    console.error('Erreur génération:', error)
    generationMessage.value = 'Erreur: ' + (error.response?.data?.error || error.message)
    setTimeout(() => {
      reportStep.value = 'selection'
      reportGenerated.value = false
    }, 2000)
  }
}

const downloadPDF = async () => {
  try {
    const res = await axios.post(
      route('process.core.evaluations.report.export-pdf'),
      {
        type: reportConfig.type,
        selected_session_id: reportConfig.selectedSessionId,
        session_ids: reportConfig.selectedSessionIds,
        selected_processes: reportConfig.selectedProcesses,
      },
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `rapport-${Date.now()}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
  } catch (e) {
    alert('Erreur téléchargement PDF')
  }
}

const downloadExcel = async () => {
  try {
    const res = await axios.post(
      route('process.core.evaluations.report.export-excel'),
      {
        type: reportConfig.type,
        selected_session_id: reportConfig.selectedSessionId,
        session_ids: reportConfig.selectedSessionIds,
        selected_processes: reportConfig.selectedProcesses,
      },
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `rapport-${Date.now()}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
  } catch (e) {
    alert('Erreur téléchargement Excel')
  }
}

const closeReportModal = () => {
  showReportModal.value = false
  reportStep.value = 'selection'
  reportGenerated.value = false
  reportConfig.selectedSessionId = null
  reportConfig.selectedSessionIds = []
  reportConfig.selectedProcesses = []
}

onMounted(() => {
  sessions.value.forEach(session => {
    if (!sessionEvaluations[session.id]) {
      sessionEvaluations[session.id] = {}
    }
  })
})

watch(currentEvaluationStep, async (newVal) => {
  if (newVal === 'maturity') {
    await renderMaturityRadar()
    if (comparisonSessions.value.length > 0) {
      await renderMaturityComparisonRadar()
    }
  }
})
</script>

<style scoped>
.table { margin-bottom: 0; font-size: 0.9rem; }
.table thead th { font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding: 0.6rem; }
.table tbody td { padding: 0.6rem; }
.process-row { transition: 0.2s; }
.process-row:hover { background: rgba(16,185,129,0.08); }
.score-badge { display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; border: 3px solid #d1d5db; border-radius: 50%; background: #f9fafb; font-weight: 700; font-size: 0.9rem; color: #374151; }
.badge-score { color: white; font-weight: 600; padding: 0.4rem 0.6rem; font-size: 0.85rem; border-radius: 4px; display: inline-block; }
.maturity-box { width: 40px; height: 40px; border-radius: 4px; cursor: pointer; transition: 0.2s; }
.maturity-box:hover { transform: scale(1.1); }
.color-picker { width: 40px; height: 40px; border-radius: 6px; cursor: pointer; transition: 0.2s; }
.color-picker:hover { transform: scale(1.1); }
.list-group-item { border: 1px solid #e5e7eb; padding: 0.75rem; }
.progression-container { display: flex; justify-content: space-around; gap: 10px; padding: 20px 0; flex-wrap: wrap; }
.progression-step { flex: 1; text-align: center; min-width: 80px; }
.step-circle { width: 50px; height: 50px; border-radius: 50%; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; transition: 0.3s; }
.step-circle:hover { transform: scale(1.05); }
.step-label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.85rem; }
.step-status { font-weight: 700; font-size: 1.2rem; }
.progression-step.completed .step-circle { transform: scale(1.1); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
</style>