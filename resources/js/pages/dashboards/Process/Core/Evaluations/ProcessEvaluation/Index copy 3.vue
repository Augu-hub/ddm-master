<template>
  <VerticalLayout>
    <Head title="Évaluation des processus" />

    <!-- HEADER -->
    <b-card class="mb-4 border-0 shadow-lg p-4" style="background: linear-gradient(135deg,#10b981,#059669); border-radius:12px;">
      <h2 class="fw-bold mb-2 text-white">Évaluation des processus</h2>
      <p class="mb-0 text-white"><strong>{{ user?.name }}</strong> — <strong>{{ link?.function_name }}</strong></p>
    </b-card>

    <!-- PAGE 1: SÉLECTION DES SESSIONS -->
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
              <div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ processes.length }}</div>
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
                    <span :class="['badge', session.status === 'open' ? 'bg-success' : 'bg-warning']">
                      {{ session.status === 'open' ? '🟢 Ouverte' : '🔒 Fermée' }}
                    </span>
                  </div>

                  <div class="bg-white p-3 rounded mb-3" style="border:1px solid #e5e7eb;">
                    <div class="row g-2 text-center">
                      <div class="col-6">
                        <div class="fw-bold text-success">{{ session.evaluated_count }}/{{ processes.length }}</div>
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
                      :disabled="session.status === 'closed'"
                    >
                      <i class="ti ti-arrow-right me-1"></i>Évaluer
                    </b-button>
                    <b-button
                      v-if="session.status === 'open'"
                      variant="outline-warning"
                      size="sm"
                      class="fw-bold"
                      @click="showDuplicateModal = true; sessionToDuplicate = session"
                    >
                      <i class="ti ti-copy me-1"></i>Dupliquer
                    </b-button>
                    <b-button
                      v-if="session.status === 'open'"
                      variant="outline-info"
                      size="sm"
                      class="fw-bold"
                      @click="closeSession(session)"
                    >
                      <i class="ti ti-lock me-1"></i>Fermer
                    </b-button>
                    <b-button
                      variant="outline-secondary"
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
                        <div class="fw-bold text-muted">{{ session.evaluated_count }}/{{ processes.length }}</div>
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

    <!-- PAGE 2: ÉVALUATION DANS UNE SESSION -->
    <div v-if="selectedSession">
      <!-- Header session sélectionnée -->
      <b-card class="mb-4 border-0 shadow-sm" :style="{ background: selectedSession.color + '15', borderLeft: `5px solid ${selectedSession.color}` }">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="fw-bold mb-1">{{ selectedSession.name }}</h5>
            <small class="text-muted">{{ formatDate(selectedSession.created_at) }} — {{ selectedSession.status === 'open' ? '🟢 Ouverte' : '🔒 Fermée' }}</small>
          </div>
          <b-button variant="outline-secondary" size="sm" @click="selectedSession = null; selectedProcess = null" class="fw-bold">
            <i class="ti ti-arrow-left me-1"></i>Retour
          </b-button>
        </div>
      </b-card>

      <!-- STATS SESSION -->
      <b-row class="g-3 mb-4">
        <b-col md="3">
          <b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;">
            <div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ sessionEvaluatedCount }}/{{ processes.length }}</div>
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
                <tr v-for="process in processes" :key="process.id" class="process-row">
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
                      disabled
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
              @click="newSessionForm.color = color"
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
        <p class="text-muted small mb-0">
          ℹ️ Toutes les évaluations seront copiées.
        </p>
      </b-form>
    </b-modal>

    <!-- ========== MODALE: ÉVALUATION (5 ÉTAPES + RÉSUMÉ) ========== -->
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
        </div>

        <!-- NAVIGATION TABS -->
        <div class="mb-4 d-flex gap-2 flex-wrap border-bottom pb-3">
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
          <div class="table-responsive mb-3" style="max-height:350px;overflow-y:auto;">
            <table class="table table-sm">
              <thead style="background:#10b981;color:white;position:sticky;top:0;">
                <tr>
                  <th style="width:150px;">Critère</th>
                  <th v-for="lvl in [1,2,3,4,5]" :key="lvl" class="text-center" style="width:60px;">N{{ lvl }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="scale in groupedMaturity" :key="scale.code">
                  <td><strong class="text-success">{{ scale.code }}</strong></td>
                  <td v-for="lvl in scale.levels" :key="lvl.level_score" class="text-center p-2">
                    <div
                      class="maturity-box"
                      :style="getMaturityBoxStyle(scale.code, lvl.level_score)"
                      @click="toggleMaturityEval(scale.code, lvl.level_score)"
                      :title="`${scale.code} - Niveau ${lvl.level_score}: ${lvl.level_label}`"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="alert alert-info small" role="alert">
            <strong>Sélectionné:</strong> {{ Object.keys(maturityEval).length }}/{{ groupedMaturity.length }} critères
          </div>
        </div>

        <!-- ÉTAPE 2: MOTRICITÉ -->
        <div v-if="currentEvaluationStep === 'motricity'">
          <h6 class="fw-bold mb-3"><i class="ti ti-zap me-2"></i>Motricité (Capacité de changement)</h6>
          <div class="text-center">
            <p class="text-muted mb-3">Sélectionnez un niveau (1-5)</p>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
              <b-button
                v-for="level in [1,2,3,4,5]"
                :key="level"
                size="lg"
                :variant="motricityEval === level ? '' : 'outline-warning'"
                :style="motricityEval === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}"
                @click="motricityEval = level"
                class="fw-bold"
              >
                {{ level }}
              </b-button>
            </div>
            <div v-if="motricityEval" class="mt-3 p-3 bg-light rounded">
              <small class="text-muted">Niveau {{ motricityEval }}</small>
              <p class="fw-bold mb-0">{{ getLevelLabel(motricityEval) }}</p>
            </div>
          </div>
        </div>

        <!-- ÉTAPE 3: TRANSVERSALITÉ -->
        <div v-if="currentEvaluationStep === 'transversality'">
          <h6 class="fw-bold mb-3"><i class="ti ti-link me-2"></i>Transversalité (Impact cross-fonctionnel)</h6>
          <div class="text-center">
            <p class="text-muted mb-3">Sélectionnez un niveau (1-5)</p>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
              <b-button
                v-for="level in [1,2,3,4,5]"
                :key="level"
                size="lg"
                :variant="transversalityEval === level ? '' : 'outline-info'"
                :style="transversalityEval === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}"
                @click="transversalityEval = level"
                class="fw-bold"
              >
                {{ level }}
              </b-button>
            </div>
            <div v-if="transversalityEval" class="mt-3 p-3 bg-light rounded">
              <small class="text-muted">Niveau {{ transversalityEval }}</small>
              <p class="fw-bold mb-0">{{ getLevelLabel(transversalityEval) }}</p>
            </div>
          </div>
        </div>

        <!-- ÉTAPE 4: STRATÉGIQUE -->
        <div v-if="currentEvaluationStep === 'strategic'">
          <h6 class="fw-bold mb-3"><i class="ti ti-target me-2"></i>Poids Stratégique</h6>
          <div class="text-center">
            <p class="text-muted mb-3">Sélectionnez un niveau (1-5)</p>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
              <b-button
                v-for="level in [1,2,3,4,5]"
                :key="level"
                size="lg"
                :variant="strategicEval === level ? '' : 'outline-danger'"
                :style="strategicEval === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}"
                @click="strategicEval = level"
                class="fw-bold"
              >
                {{ level }}
              </b-button>
            </div>
            <div v-if="strategicEval" class="mt-3 p-3 bg-light rounded">
              <small class="text-muted">Niveau {{ strategicEval }}</small>
              <p class="fw-bold mb-0">{{ getLevelLabel(strategicEval) }}</p>
            </div>
          </div>
        </div>

        <!-- ÉTAPE 5: RÉSUMÉ ET RADAR -->
        <div v-if="currentEvaluationStep === 'summary'">
          <h6 class="fw-bold mb-4"><i class="ti ti-radar me-2"></i>Résumé de l'évaluation</h6>
          
          <!-- RADAR SIMPLE -->
          <b-row class="mb-4">
            <b-col md="6">
              <h6 class="fw-bold text-center mb-3" style="color:#10b981;">📊 Radar d'évaluation</h6>
              <div style="height:300px;">
                <canvas ref="radarCanvasRef" id="radarCanvas"></canvas>
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
                  <small class="text-muted d-block mt-1">{{ Object.keys(maturityEval).length }}/{{ groupedMaturity.length }} critères évalués</small>
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

          <div class="alert alert-info small" role="alert">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Légende:</strong> 
            <span class="badge bg-danger ms-1">1-2</span>
            <span class="badge bg-warning ms-1">2-3</span>
            <span class="badge bg-orange ms-1">3-4</span>
            <span class="badge bg-success ms-1">4-5</span>
          </div>
        </div>
      </div>

      <!-- BOUTONS D'ACTION -->
      <template #modal-footer>
        <b-button variant="secondary" @click="closeEvaluationModal">Annuler</b-button>
        <b-button
          variant="success"
          @click="saveEvaluation"
          :disabled="!canSaveEvaluation"
          class="fw-bold"
        >
          <i class="ti ti-check me-1"></i>Enregistrer
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

// STATE
const sessions = ref(props.sessions || [])
const selectedSession = ref(null)
const selectedProcess = ref(null)
const showCreateSessionModal = ref(false)
const showDuplicateModal = ref(false)
const showEvaluationModal = ref(false)
const successMessage = ref("")
const sessionToDuplicate = ref(null)
const radarCanvasRef = ref(null)
let radarChart = null

// Form states
const newSessionForm = reactive({
  name: '',
  color: '#10b981'
})

const duplicateForm = reactive({
  name: ''
})

// Évaluation state
const maturityEval = reactive({})
const motricityEval = ref(null)
const transversalityEval = ref(null)
const strategicEval = ref(null)
const currentEvaluationStep = ref('maturity')

// Session data
const sessionEvaluations = reactive({})

// Colors
const sessionColors = [
  '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'
]

const evaluationSteps = [
  { key: 'maturity', label: '📋 Maturité' },
  { key: 'motricity', label: '⚡ Motricité' },
  { key: 'transversality', label: '🔗 Transversalité' },
  { key: 'strategic', label: '🎯 Stratégique' },
  { key: 'summary', label: '📊 Résumé & Radar' }
]

// COMPUTED
const activeSessions = computed(() => sessions.value.filter(s => s.status !== 'archived'))
const archivedSessions = computed(() => sessions.value.filter(s => s.status === 'archived'))
const canCreateSession = computed(() => activeSessions.value.length < 5)

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

const canSaveEvaluation = computed(() => {
  return Object.keys(maturityEval).length > 0
})

// FUNCTIONS
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

const toggleMaturityEval = (code, level) => {
  if (maturityEval[code] === level) {
    delete maturityEval[code]
  } else {
    maturityEval[code] = level
  }
}

// RADAR CHART
const drawRadar = () => {
  nextTick(() => {
    if (!radarCanvasRef.value) return

    if (radarChart) {
      radarChart.destroy()
    }

    const ctx = radarCanvasRef.value.getContext('2d')
    radarChart = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: ['Maturité', 'Motricité', 'Transversalité', 'Stratégique'],
        datasets: [{
          label: selectedProcess.value.name,
          data: [
            maturityAverage.value,
            motricityEval.value || 0,
            transversalityEval.value || 0,
            strategicEval.value || 0
          ],
          borderColor: selectedSession.value.color || '#10b981',
          backgroundColor: (selectedSession.value.color || '#10b981') + '20',
          borderWidth: 2,
          fill: true,
          pointRadius: 5,
          pointBackgroundColor: selectedSession.value.color || '#10b981'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          r: {
            beginAtZero: true,
            max: 5,
            ticks: { stepSize: 1 }
          }
        },
        plugins: {
          legend: { display: true, position: 'top' }
        }
      }
    })
  })
}

// ACTIONS
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
  const existing = sessionEvaluations[selectedSession.value.id]?.[key]

  if (existing) {
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
  } else {
    Object.keys(maturityEval).forEach(k => delete maturityEval[k])
    motricityEval.value = null
    transversalityEval.value = null
    strategicEval.value = null
  }

  currentEvaluationStep.value = 'maturity'
  showEvaluationModal.value = true
}

const saveEvaluation = async () => {
  if (!selectedProcess.value || !selectedSession.value) return

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

    if (motricityEval.value) {
      await axios.post(route('process.core.evaluations.axis.save'), {
        session_id: selectedSession.value.id,
        process_id: selectedProcess.value.id,
        axis: 'motricity',
        score: motricityEval.value
      })
    }

    if (transversalityEval.value) {
      await axios.post(route('process.core.evaluations.axis.save'), {
        session_id: selectedSession.value.id,
        process_id: selectedProcess.value.id,
        axis: 'transversality',
        score: transversalityEval.value
      })
    }

    if (strategicEval.value) {
      await axios.post(route('process.core.evaluations.axis.save'), {
        session_id: selectedSession.value.id,
        process_id: selectedProcess.value.id,
        axis: 'strategic',
        score: strategicEval.value
      })
    }

    successMessage.value = '✓ Évaluation enregistrée'
    showEvaluationModal.value = false
    await loadSessionEvaluations()
    await nextTick()
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (e) {
    console.error('Erreur save evaluation', e)
    alert('Erreur: ' + (e.response?.data?.error || e.message))
  }
}

const closeEvaluationModal = () => {
  showEvaluationModal.value = false
  selectedProcess.value = null
  Object.keys(maturityEval).forEach(k => delete maturityEval[k])
  motricityEval.value = null
  transversalityEval.value = null
  strategicEval.value = null
  currentEvaluationStep.value = 'maturity'
  if (radarChart) {
    radarChart.destroy()
    radarChart = null
  }
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

    const newSession = {
      id: res.data.session_id,
      entity_id: props.link.entity_id,
      function_id: props.link.function_id,
      name: newSessionForm.name,
      color: newSessionForm.color,
      status: 'open',
      evaluated_count: 0,
      session_avg_score: 0,
      created_at: new Date()
    }
    sessions.value.push(newSession)
    successMessage.value = '✓ Session créée'
    setTimeout(() => { successMessage.value = '' }, 2000)
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

const closeSession = async (session) => {
  if (!confirm('Êtes-vous sûr?')) return

  try {
    await axios.post(route('process.core.evaluations.sessions.close'), {
      session_id: session.id
    })

    session.status = 'closed'
    successMessage.value = '✓ Session fermée'
    setTimeout(() => { successMessage.value = '' }, 2000)
  } catch (e) {
    console.error('Erreur close', e)
  }
}

const archiveSessionConfirm = async (session) => {
  if (!confirm('Archiver cette session? Elle ne comptera plus dans la limite de 5.')) return

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

// Watch pour afficher radar lors du changement d'étape
watch(() => currentEvaluationStep.value, (newVal) => {
  if (newVal === 'summary') {
    drawRadar()
  }
})

// MOUNT
onMounted(() => {
  sessions.value.forEach(session => {
    if (!sessionEvaluations[session.id]) {
      sessionEvaluations[session.id] = {}
    }
  })
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
.list-group-item .badge { font-size: 0.85rem; }
</style>