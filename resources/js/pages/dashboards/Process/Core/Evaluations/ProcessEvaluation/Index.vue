<template>
  <VerticalLayout>
    <Head title="Évaluation des processus" />

    <!-- HEADER PRINCIPAL -->
    <b-card class="mb-4 border-0 shadow-lg p-4" style="background: linear-gradient(135deg,#10b981,#059669); border-radius:12px;">
      <h2 class="fw-bold mb-2 text-white">📊 Évaluation des Processus</h2>
      <p class="mb-0 text-white"><strong>{{ user?.name }}</strong> — <strong>{{ link?.function_name }}</strong></p>
    </b-card>

    <!-- SESSION ACTIVE -->
    <div v-if="activeSession" class="mb-4" :style="getSessionHeaderStyle()">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5 class="fw-bold mb-1">🟢 {{ activeSession.name }}</h5>
          <small class="text-muted d-block">Créée le {{ formatDate(activeSession.created_at) }}</small>
          <small class="text-muted d-block">{{ evaluatedCount }}/{{ processes.length }} processus évalués</small>
        </div>
        <div class="text-end">
          <div class="fw-bold" :style="getProgressStyle()">{{ (progressPercent).toFixed(0) }}%</div>
          <small class="text-muted d-block">Progression</small>
          <b-button size="sm" variant="info" class="mt-2" @click="showReportModal = true">
            <i class="ti ti-file-text me-1"></i>Rapport
          </b-button>
        </div>
      </div>
    </div>

    <b-alert v-else variant="warning" class="mb-4">
      <i class="ti ti-alert-triangle me-2"></i>
      <strong>Aucune session active</strong>
    </b-alert>

    <!-- MESSAGE SUCCÈS -->
    <b-alert v-if="successMessage" variant="success" dismissible @dismissed="successMessage = ''">
      <i class="ti ti-check me-2"></i>{{ successMessage }}
    </b-alert>

    <!-- TABLEAU PROCESSUS -->
    <b-card v-if="activeSession" class="mb-4 border-0 shadow-sm">
      <b-card-header class="bg-light" style="border-bottom:3px solid #10b981;">
        <h5 class="fw-bold mb-0"><i class="ti ti-list me-2"></i>{{ processes.length }} Processus à évaluer</h5>
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
                    size="sm"
                    :variant="getProcessEval(process.id, 'criticality_score') > 0 ? 'success' : 'outline-success'"
                    class="fw-bold"
                    @click="selectProcessForEvaluation(process)"
                  >
                    <i :class="getProcessEval(process.id, 'criticality_score') > 0 ? 'ti ti-pencil' : 'ti ti-plus'"></i>
                  </b-button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </b-card-body>
    </b-card>

    <!-- MODALE ÉVALUATION -->
    <b-modal
      v-model="showEvaluationModal"
      title="Évaluation du processus"
      size="xl"
      no-close-on-backdrop
      @hidden="closeEvaluationModal"
      scrollable
    >
      <div v-if="selectedProcess && activeSession">
        <div class="mb-4 p-3 bg-light rounded">
          <h6 class="fw-bold mb-1">{{ selectedProcess.code }} - {{ selectedProcess.name }}</h6>
          <small class="text-muted d-block">Session: {{ activeSession.name }}</small>
        </div>

        <!-- PROGRESSION -->
       

        <!-- NAVIGATION TABS -->
        <div class="mb-3 d-flex gap-2 flex-wrap border-bottom pb-3">
          <b-button
            v-for="step in evaluationSteps"
            :key="step.key"
            :variant="currentEvaluationStep === step.key ? 'success' : 'outline-success'"
            size="sm"
            class="fw-bold"
            @click="currentEvaluationStep = step.key"
          >
            {{ step.label }}
          </b-button>
        </div>

        <!-- ÉTAPE 1: MATURITÉ AVEC RADARS COMPARATIFS -->
        <div v-if="currentEvaluationStep === 'maturity'">
          <h6 class="fw-bold mb-3"><i class="ti ti-chart-radar-2 me-2"></i>📋 Matrice de Maturité (12 critères)</h6>
          
          <!-- CRITÈRES ET SÉLECTION + RADAR SESSION ACTIVE -->
          <b-row class="g-3 mb-4">
            <b-col lg="6">
              <h6 class="fw-bold mb-2 text-success">✏️ Sélectionner les niveaux</h6>
              <div class="criteria-list" style="max-height: 450px; overflow-y: auto;">
                <div v-for="scale in groupedMaturity" :key="scale.code" class="criteria-item p-2 mb-2 border rounded">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="text-success" style="font-size: 0.9rem;">{{ scale.code }}</strong>
                    <span v-if="maturityEval[scale.code]" class="badge" :style="{ background: getColorByLevel(maturityEval[scale.code]) }">
                      {{ maturityEval[scale.code] }}/5
                    </span>
                  </div>
                  
                  <div class="d-flex gap-1 flex-wrap">
                    <b-button 
                      v-for="lvl in scale.levels" 
                      :key="lvl.level_score"
                      size="sm"
                      :variant="maturityEval[scale.code] === lvl.level_score ? '' : 'outline-secondary'"
                      :style="maturityEval[scale.code] === lvl.level_score ? { background: getColorByLevel(lvl.level_score), color: 'white', border: 'none', padding: '0.2rem 0.4rem', fontSize: '0.7rem' } : { fontSize: '0.7rem' }"
                      @click="maturityEval[scale.code] = lvl.level_score; renderMaturityRadar()"
                      class="fw-bold"
                      style="min-width: 24px; height: 24px; padding: 0;"
                    >
                      {{ lvl.level_score }}
                    </b-button>
                  </div>
                </div>
              </div>

              <div class="alert alert-info small mt-2 mb-0">
                <strong>✓ Sélectionné:</strong> {{ Object.keys(maturityEval).length }}/{{ groupedMaturity.length }} critères
                <span v-if="maturityAverage" class="ms-2"><strong>Moyenne:</strong> {{ maturityAverage.toFixed(2) }}/5</span>
              </div>
            </b-col>

            <!-- RADAR SESSION ACTIVE -->
            <b-col lg="6">
              <h6 class="fw-bold mb-2" style="color: #10b981;">📊 Radar - {{ activeSession.name }}</h6>
              <div style="height: 450px; position: relative; background: #f9fafb; border-radius: 8px; border: 2px solid #10b981; padding: 1rem;">
                <canvas ref="maturityRadarRef" id="maturityRadar"></canvas>
              </div>
            </b-col>
          </b-row>

          <!-- RADARS COMPARATIFS DES AUTRES SESSIONS -->
          <h6 class="fw-bold mb-3">📊 Comparer avec autres sessions</h6>
          <div v-if="otherSessionsList.length > 0" class="row g-3">
            <div v-for="session in otherSessionsList" :key="session.id" class="col-md-6">
              <div class="p-2 border rounded" :style="{ borderLeft: '5px solid ' + session.color, background: session.color + '05' }">
                <h6 class="fw-bold mb-2 text-truncate" :style="{ color: session.color }">{{ session.name }}</h6>
                <div style="height: 300px; position: relative; background: #f9fafb; border-radius: 6px; border: 2px solid #e5e7eb; padding: 0.5rem;">
                  <canvas :ref="el => { if(el) radarComparison[`radar_${session.id}`] = el }" :id="`comparativeRadar_${session.id}`"></canvas>
                </div>
                <div class="mt-2 small">
                  <small class="d-block text-muted">Maturité: <span class="badge" :style="{ background: getColorByScore(getOtherSessionScore(session.id, 'maturity_score')) }">{{ getOtherSessionScore(session.id, 'maturity_score').toFixed(2) }}/5</span></small>
                  <small class="d-block text-muted">Global: <span class="badge" :style="{ background: getColorByScore(getOtherSessionScore(session.id, 'criticality_score')) }">{{ getOtherSessionScore(session.id, 'criticality_score').toFixed(2) }}/5</span></small>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="alert alert-info small mb-0">
            <i class="ti ti-info-circle me-2"></i>Aucune autre session pour comparaison
          </div>
        </div>

        <!-- ÉTAPE 2: MOTRICITÉ -->
        <div v-if="currentEvaluationStep === 'motricity'">
          <h6 class="fw-bold mb-3"><i class="ti ti-zap me-2"></i>⚡ Motricité</h6>
          
          <div v-if="Object.keys(maturityEval).length === 0" class="alert alert-warning">
            ⚠️ Évaluez d'abord la <strong>Maturité</strong>
          </div>
          <div v-else>
            <b-row class="g-3">
              <b-col lg="6">
                <div class="d-flex gap-2 flex-wrap justify-content-center mb-4">
                  <b-button v-for="level in [1,2,3,4,5]" :key="level" size="lg" :variant="motricityEval === level ? '' : 'outline-warning'" :style="motricityEval === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}" @click="setMotricity(level)" class="fw-bold">
                    {{ level }}
                  </b-button>
                </div>

                <div v-if="motricityEval" class="p-3 bg-light rounded mb-3">
                  <small class="fw-bold">Niveau {{ motricityEval }}:</small>
                  <p class="mb-0 text-muted">{{ getLevelDescription('motricity', motricityEval) }}</p>
                </div>

                <div style="height: 350px; position: relative; background: #f9fafb; border-radius: 8px; border: 2px solid #f59e0b; padding: 1rem;">
                  <canvas ref="motricityRadarRef" id="motricityRadar"></canvas>
                </div>
              </b-col>

              <b-col lg="6">
                <h6 class="fw-bold mb-3">📊 Comparer - Motricité</h6>
                <div v-if="otherSessionsList.length > 0" class="row g-3">
                  <div v-for="session in otherSessionsList" :key="session.id" class="col-6">
                    <div class="p-2 border rounded" :style="{ borderLeft: '5px solid ' + session.color, background: session.color + '05' }">
                      <small class="d-block fw-bold text-truncate" :style="{ color: session.color }">{{ session.name }}</small>
                      <div style="height: 200px; position: relative; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb; padding: 0.5rem;">
                        <canvas :ref="el => { if(el) radarMotricity[`radar_${session.id}`] = el }" :id="`motricityRadar_${session.id}`"></canvas>
                      </div>
                      <small class="d-block text-muted mt-1">Score: <span class="badge" :style="{ background: getColorByLevel(getOtherSessionScore(session.id, 'motricity_score')) }">{{ getOtherSessionScore(session.id, 'motricity_score') || '—' }}/5</span></small>
                    </div>
                  </div>
                </div>
              </b-col>
            </b-row>
          </div>
        </div>

        <!-- ÉTAPE 3: TRANSVERSALITÉ -->
        <div v-if="currentEvaluationStep === 'transversality'">
          <h6 class="fw-bold mb-3"><i class="ti ti-link me-2"></i>🔗 Transversalité</h6>
          
          <div v-if="motricityEval === null" class="alert alert-warning">
            ⚠️ Évaluez d'abord la <strong>Motricité</strong>
          </div>
          <div v-else>
            <b-row class="g-3">
              <b-col lg="6">
                <div class="d-flex gap-2 flex-wrap justify-content-center mb-4">
                  <b-button v-for="level in [1,2,3,4,5]" :key="level" size="lg" :variant="transversalityEval === level ? '' : 'outline-info'" :style="transversalityEval === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}" @click="setTransversality(level)" class="fw-bold">
                    {{ level }}
                  </b-button>
                </div>

                <div v-if="transversalityEval" class="p-3 bg-light rounded mb-3">
                  <small class="fw-bold">Niveau {{ transversalityEval }}:</small>
                  <p class="mb-0 text-muted">{{ getLevelDescription('transversality', transversalityEval) }}</p>
                </div>

                <div style="height: 350px; position: relative; background: #f9fafb; border-radius: 8px; border: 2px solid #8b5cf6; padding: 1rem;">
                  <canvas ref="transversalityRadarRef" id="transversalityRadar"></canvas>
                </div>
              </b-col>

              <b-col lg="6">
                <h6 class="fw-bold mb-3">📊 Comparer - Transversalité</h6>
                <div v-if="otherSessionsList.length > 0" class="row g-3">
                  <div v-for="session in otherSessionsList" :key="session.id" class="col-6">
                    <div class="p-2 border rounded" :style="{ borderLeft: '5px solid ' + session.color, background: session.color + '05' }">
                      <small class="d-block fw-bold text-truncate" :style="{ color: session.color }">{{ session.name }}</small>
                      <div style="height: 200px; position: relative; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb; padding: 0.5rem;">
                        <canvas :ref="el => { if(el) radarTransversality[`radar_${session.id}`] = el }" :id="`transversalityRadar_${session.id}`"></canvas>
                      </div>
                      <small class="d-block text-muted mt-1">Score: <span class="badge" :style="{ background: getColorByLevel(getOtherSessionScore(session.id, 'transversality_score')) }">{{ getOtherSessionScore(session.id, 'transversality_score') || '—' }}/5</span></small>
                    </div>
                  </div>
                </div>
              </b-col>
            </b-row>
          </div>
        </div>

        <!-- ÉTAPE 4: STRATÉGIQUE -->
        <div v-if="currentEvaluationStep === 'strategic'">
          <h6 class="fw-bold mb-3"><i class="ti ti-target me-2"></i>🎯 Stratégique</h6>
          
          <div v-if="transversalityEval === null" class="alert alert-warning">
            ⚠️ Évaluez d'abord la <strong>Transversalité</strong>
          </div>
          <div v-else>
            <b-row class="g-3">
              <b-col lg="6">
                <div class="d-flex gap-2 flex-wrap justify-content-center mb-4">
                  <b-button v-for="level in [1,2,3,4,5]" :key="level" size="lg" :variant="strategicEval === level ? '' : 'outline-danger'" :style="strategicEval === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}" @click="setStrategic(level)" class="fw-bold">
                    {{ level }}
                  </b-button>
                </div>

                <div v-if="strategicEval" class="p-3 bg-light rounded mb-3">
                  <small class="fw-bold">Niveau {{ strategicEval }}:</small>
                  <p class="mb-0 text-muted">{{ getLevelDescription('strategic', strategicEval) }}</p>
                </div>

                <div style="height: 350px; position: relative; background: #f9fafb; border-radius: 8px; border: 2px solid #ec4899; padding: 1rem;">
                  <canvas ref="strategicRadarRef" id="strategicRadar"></canvas>
                </div>
              </b-col>

              <b-col lg="6">
                <h6 class="fw-bold mb-3">📊 Comparer - Stratégique</h6>
                <div v-if="otherSessionsList.length > 0" class="row g-3">
                  <div v-for="session in otherSessionsList" :key="session.id" class="col-6">
                    <div class="p-2 border rounded" :style="{ borderLeft: '5px solid ' + session.color, background: session.color + '05' }">
                      <small class="d-block fw-bold text-truncate" :style="{ color: session.color }">{{ session.name }}</small>
                      <div style="height: 200px; position: relative; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb; padding: 0.5rem;">
                        <canvas :ref="el => { if(el) radarStrategic[`radar_${session.id}`] = el }" :id="`strategicRadar_${session.id}`"></canvas>
                      </div>
                      <small class="d-block text-muted mt-1">Score: <span class="badge" :style="{ background: getColorByLevel(getOtherSessionScore(session.id, 'strategic_score')) }">{{ getOtherSessionScore(session.id, 'strategic_score') || '—' }}/5</span></small>
                    </div>
                  </div>
                </div>
              </b-col>
            </b-row>
          </div>
        </div>

        <!-- ÉTAPE 5: RÉSUMÉ AVEC RADAR GLOBAL -->
        <div v-if="currentEvaluationStep === 'summary'">
          <h6 class="fw-bold mb-4"><i class="ti ti-radar me-2"></i>📊 Résumé Global</h6>
          
          <b-row class="g-3">
            <b-col lg="6">
              <div style="height: 400px; position: relative; background: #f9fafb; border-radius: 8px; border: 2px solid #10b981; padding: 1rem;">
                <canvas ref="summaryRadarRef" id="summaryRadar"></canvas>
              </div>
            </b-col>

            <b-col lg="6">
              <h6 class="fw-bold mb-3">Scores détaillés</h6>
              <div class="list-group">
                <div class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <strong>📋 Maturité</strong>
                    <span class="badge" :style="{ background: getColorByScore(maturityAverage) }">{{ maturityAverage.toFixed(2) }}/5</span>
                  </div>
                </div>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <strong>⚡ Motricité</strong>
                    <span class="badge" :style="{ background: getColorByScore(motricityEval) }">{{ motricityEval || '—' }}/5</span>
                  </div>
                </div>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <strong>🔗 Transversalité</strong>
                    <span class="badge" :style="{ background: getColorByScore(transversalityEval) }">{{ transversalityEval || '—' }}/5</span>
                  </div>
                </div>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <strong>🎯 Stratégique</strong>
                    <span class="badge" :style="{ background: getColorByScore(strategicEval) }">{{ strategicEval || '—' }}/5</span>
                  </div>
                </div>
                <div class="list-group-item bg-success bg-opacity-10">
                  <div class="d-flex justify-content-between">
                    <strong>📊 Score Global</strong>
                    <span class="badge bg-success">{{ globalEvalScore.toFixed(2) }}/5</span>
                  </div>
                </div>
              </div>
            </b-col>
          </b-row>
        </div>
      </div>

      <template #modal-footer>
        <b-button variant="secondary" @click="closeEvaluationModal">Annuler</b-button>
        <div v-if="currentEvaluationStep === 'summary'">
          <b-button variant="success" @click="finalizeEvaluation" :disabled="!canSaveEvaluation" class="fw-bold">
            <i class="ti ti-check me-1"></i>Terminer
          </b-button>
        </div>
        <div v-else>
          <b-button v-if="currentEvaluationStep !== 'maturity'" variant="outline-secondary" @click="previousStep" class="fw-bold">
            <i class="ti ti-arrow-left me-1"></i>Précédent
          </b-button>
          <b-button variant="success" @click="nextStep" :disabled="!canGoNext" class="fw-bold">
            Suivant <i class="ti ti-arrow-right ms-1"></i>
          </b-button>
        </div>
      </template>
    </b-modal>

    <!-- MODALE RAPPORT AVEC APERÇU -->
    <b-modal v-model="showReportModal" title="📊 Rapport d'évaluation" size="lg" centered scrollable>
      <div v-if="!reportGenerated" class="mb-3">
        <h6 class="fw-bold mb-3">Configuration du rapport</h6>
        
        <b-form-group label="Type de rapport:" label-class="fw-bold" class="mb-3">
          <b-form-radio v-model="reportConfig.type" value="single" class="mb-2">
            <i class="ti ti-file-text me-2"></i>Session active uniquement
          </b-form-radio>
          <b-form-radio v-model="reportConfig.type" value="all" class="mb-2">
            <i class="ti ti-files me-2"></i>Toutes les sessions
          </b-form-radio>
        </b-form-group>

        <b-form-group label="Format d'export:" label-class="fw-bold" class="mb-4">
          <b-form-checkbox v-model="reportConfig.exportPDF" class="mb-2">
            <i class="ti ti-file-pdf me-2"></i>PDF (professionnel)
          </b-form-checkbox>
          <b-form-checkbox v-model="reportConfig.exportExcel" class="mb-2">
            <i class="ti ti-file-spreadsheet me-2"></i>Excel (données brutes)
          </b-form-checkbox>
        </b-form-group>

        <!-- APERÇU RAPPORT -->
        <h6 class="fw-bold mb-2">📋 Aperçu du rapport</h6>
        <b-card class="bg-light mb-3">
          <b-card-body class="p-2">
            <small class="d-block"><strong>Sessions incluses:</strong> {{ reportConfig.type === 'single' ? '1' : allSessions.length }}</small>
            <small class="d-block"><strong>Processus:</strong> {{ processes.length }}</small>
            <small class="d-block"><strong>Données:</strong> {{ reportConfig.type === 'single' ? evaluatedCount : 'Toutes' }}/{{ processes.length }} processus évalués</small>
            <small class="d-block text-muted mt-2">
              <i class="ti ti-info-circle me-1"></i>
              Le rapport inclura tous les scores par axe (Maturité, Motricité, Transversalité, Stratégique) et le score de criticité global.
            </small>
          </b-card-body>
        </b-card>

        <div class="alert alert-info small mb-0">
          <i class="ti ti-info-circle me-2"></i>
          Fichiers générés: {{ reportConfig.exportPDF ? '📄 PDF' : '' }} {{ reportConfig.exportExcel ? '📊 Excel' : '' }}
        </div>
      </div>

      <div v-else class="text-center py-4">
        <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
        <h5>Rapport généré!</h5>
        <p class="text-muted mb-3">Fichiers téléchargés avec succès</p>
        <small class="d-block text-muted">
          <i class="ti ti-check me-1"></i>
          Vérifiez votre dossier de téléchargement
        </small>
      </div>

      <template #modal-footer>
        <b-button variant="secondary" @click="closeReportModal">Fermer</b-button>
        <b-button v-if="!reportGenerated" variant="success" @click="generateReport" :disabled="!reportConfig.exportPDF && !reportConfig.exportExcel">
          Générer les fichiers
        </b-button>
      </template>
    </b-modal>
  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layouts/VerticalLayout.vue"
import axios from "axios"
import { ref, reactive, computed, onMounted, watch, nextTick } from "vue"
import Chart from 'chart.js/auto'

const props = defineProps({
  user: Object,
  link: Object,
  processes: Array,
  sessions: Array,
  maturityLevels: Array,
})

// STATE
const activeSession = ref(null)
const selectedProcess = ref(null)
const showEvaluationModal = ref(false)
const showReportModal = ref(false)
const successMessage = ref("")

const maturityEval = reactive({})
const motricityEval = ref(null)
const transversalityEval = ref(null)
const strategicEval = ref(null)
const currentEvaluationStep = ref('maturity')

const sessionEvaluations = reactive({})
const allSessions = ref(props.sessions || [])

const reportGenerated = ref(false)
const reportConfig = reactive({
  type: 'single',
  exportPDF: true,
  exportExcel: false,
})

const evaluationSteps = [
  { key: 'maturity', label: '📋 Maturité', icon: 'ti ti-mountain' },
  { key: 'motricity', label: '⚡ Motricité', icon: 'ti ti-zap' },
  { key: 'transversality', label: '🔗 Transversalité', icon: 'ti ti-link' },
  { key: 'strategic', label: '🎯 Stratégique', icon: 'ti ti-target' },
  { key: 'summary', label: '📊 Résumé', icon: 'ti ti-radar' }
]

// CANVAS REFS
const maturityRadarRef = ref(null)
const motricityRadarRef = ref(null)
const transversalityRadarRef = ref(null)
const strategicRadarRef = ref(null)
const summaryRadarRef = ref(null)

const radarComparison = reactive({})
const radarMotricity = reactive({})
const radarTransversality = reactive({})
const radarStrategic = reactive({})

let radarCharts = {
  maturity: null,
  motricity: null,
  transversality: null,
  strategic: null,
  summary: null,
  comparisonMaturity: {},
  comparisonMotricity: {},
  comparisonTransversality: {},
  comparisonStrategic: {}
}

// COMPUTED
const groupedMaturity = computed(() => {
  const map = {}
  props.maturityLevels?.forEach(l => {
    if (!map[l.scale_code]) {
      map[l.scale_code] = { code: l.scale_code, levels: [] }
    }
    map[l.scale_code].levels.push(l)
  })
  return Object.values(map)
})

const maturityAverage = computed(() => {
  const vals = Object.values(maturityEval)
  return vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : 0
})

const globalEvalScore = computed(() => {
  const vals = [maturityAverage.value, motricityEval.value, transversalityEval.value, strategicEval.value].filter(v => v !== null)
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
  return Object.keys(maturityEval).length > 0 && motricityEval.value !== null && transversalityEval.value !== null && strategicEval.value !== null
})

const progressPercent = computed(() => {
  if (!activeSession.value) return 0
  const evals = sessionEvaluations[activeSession.value.id] || {}
  const evaluated = new Set()
  Object.values(evals).forEach(e => {
    if (e?.criticality_score) evaluated.add(e.process_id)
  })
  return props.processes?.length ? (evaluated.size / props.processes.length) * 100 : 0
})

const evaluatedCount = computed(() => {
  if (!activeSession.value) return 0
  const evals = sessionEvaluations[activeSession.value.id] || {}
  const evaluated = new Set()
  Object.values(evals).forEach(e => {
    if (e?.criticality_score) evaluated.add(e.process_id)
  })
  return evaluated.size
})

const otherSessionsList = computed(() => {
  if (!activeSession.value || !allSessions.value) return []
  return allSessions.value.filter(s => s.id !== activeSession.value.id && s.status !== 'archived')
})

// FUNCTIONS
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', { year: 'numeric', month: 'short', day: 'numeric' })
}

const getProgressStyle = () => ({
  fontSize: '2.5rem',
  color: activeSession.value?.color || '#10b981',
  fontWeight: 'bold'
})

const getSessionHeaderStyle = () => {
  if (!activeSession.value) return {}
  return {
    background: activeSession.value.color + '15',
    borderLeft: `5px solid ${activeSession.value.color}`,
    borderRadius: '8px',
    padding: '16px'
  }
}

const getColorByLevel = (level) => {
  const colors = { 1: '#ef4444', 2: '#f97316', 3: '#fbbf24', 4: '#84cc16', 5: '#10b981' }
  return colors[level] || '#d1d5db'
}

const getColorByScore = (score) => {
  if (!score || score === 0) return '#d1d5db'
  if (score >= 4) return '#10b981'
  if (score >= 3) return '#f59e0b'
  return '#ef4444'
}

const getLevelDescription = (axis, level) => {
  const descriptions = {
    maturity: { 1: 'Initial', 2: 'Reproductible', 3: 'Défini', 4: 'Mesuré', 5: 'Optimisé' },
    motricity: { 1: 'Rigide', 2: 'Peu Flexible', 3: 'Flexible', 4: 'Très Flexible', 5: 'Agile' },
    transversality: { 1: 'Isolé', 2: 'Peu Transversal', 3: 'Transversal', 4: 'Très Transversal', 5: 'Critique' },
    strategic: { 1: 'Support', 2: 'Important', 3: 'Significatif', 4: 'Clé', 5: 'Critique' }
  }
  return descriptions[axis]?.[level] || ''
}

const getProcessEval = (processId, field) => {
  if (!activeSession.value) return 0
  const evals = sessionEvaluations[activeSession.value.id] || {}
  const key = `${activeSession.value.id}_${processId}`
  return evals[key]?.[field] || 0
}

const getOtherSessionScore = (sessionId, field) => {
  if (!selectedProcess.value || !sessionEvaluations[sessionId]) return 0
  const evals = sessionEvaluations[sessionId]
  const key = `${sessionId}_${selectedProcess.value.id}`
  return evals[key]?.[field] || 0
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

  const labels = groupedMaturity.value.map(g => g.code)
  const data = groupedMaturity.value.map(g => maturityEval[g.code] || 0)

  const ctx = maturityRadarRef.value.getContext('2d')
  radarCharts.maturity = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Maturité',
        data: data,
        borderColor: activeSession.value.color || '#10b981',
        backgroundColor: (activeSession.value.color || '#10b981') + '30',
        borderWidth: 3,
        pointRadius: 5,
        pointBackgroundColor: activeSession.value.color || '#10b981',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        r: {
          beginAtZero: true,
          max: 5,
          ticks: { stepSize: 1 },
          grid: { color: '#e5e7eb' }
        }
      },
      plugins: {
        legend: { display: true, position: 'top' },
        tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.r + '/5' } }
      }
    }
  })

  // Render comparison radars
  for (let session of otherSessionsList.value) {
    renderComparisonMaturityRadar(session)
  }
}

const renderComparisonMaturityRadar = async (session) => {
  const canvasEl = radarComparison[`radar_${session.id}`]
  if (!canvasEl) return

  if (radarCharts.comparisonMaturity[session.id]) {
    radarCharts.comparisonMaturity[session.id].destroy()
  }

  const labels = groupedMaturity.value.map(g => g.code)
  const scores = []
  
  for (let scale of groupedMaturity.value) {
    const evals = sessionEvaluations[session.id] || {}
    const key = `${session.id}_${selectedProcess.value.id}_${scale.code}`
    scores.push(evals[key] || 0)
  }

  const ctx = canvasEl.getContext('2d')
  radarCharts.comparisonMaturity[session.id] = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: labels,
      datasets: [{
        label: session.name,
        data: scores,
        borderColor: session.color,
        backgroundColor: session.color + '30',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: session.color,
        pointBorderColor: '#fff',
        pointBorderWidth: 1,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 }, grid: { color: '#e5e7eb' } } },
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ctx.parsed.r + '/5' } } }
    }
  })
}

const renderMotricityRadar = async () => {
  await nextTick()
  if (!motricityRadarRef.value) return

  if (radarCharts.motricity) radarCharts.motricity.destroy()

  const ctx = motricityRadarRef.value.getContext('2d')
  radarCharts.motricity = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Motricité'],
      datasets: [{
        label: 'Motricité',
        data: [motricityEval.value || 0],
        borderColor: '#f59e0b',
        backgroundColor: '#f59e0b30',
        borderWidth: 3,
        pointRadius: 6,
        pointBackgroundColor: '#f59e0b',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 }, grid: { color: '#e5e7eb' } } },
      plugins: { legend: { display: true, position: 'top' }, tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.r + '/5' } } }
    }
  })

  // Render comparison radars
  for (let session of otherSessionsList.value) {
    renderComparisonMotricityRadar(session)
  }
}

const renderComparisonMotricityRadar = async (session) => {
  const canvasEl = radarMotricity[`radar_${session.id}`]
  if (!canvasEl) return

  if (radarCharts.comparisonMotricity[session.id]) {
    radarCharts.comparisonMotricity[session.id].destroy()
  }

  const score = getOtherSessionScore(session.id, 'motricity_score') || 0

  const ctx = canvasEl.getContext('2d')
  radarCharts.comparisonMotricity[session.id] = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Motricité'],
      datasets: [{
        label: session.name,
        data: [score],
        borderColor: session.color,
        backgroundColor: session.color + '30',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: session.color,
        pointBorderColor: '#fff',
        pointBorderWidth: 1,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 }, grid: { color: '#e5e7eb' } } },
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ctx.parsed.r + '/5' } } }
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
      labels: ['Transversalité'],
      datasets: [{
        label: 'Transversalité',
        data: [transversalityEval.value || 0],
        borderColor: '#8b5cf6',
        backgroundColor: '#8b5cf630',
        borderWidth: 3,
        pointRadius: 6,
        pointBackgroundColor: '#8b5cf6',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 }, grid: { color: '#e5e7eb' } } },
      plugins: { legend: { display: true, position: 'top' }, tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.r + '/5' } } }
    }
  })

  // Render comparison radars
  for (let session of otherSessionsList.value) {
    renderComparisonTransversalityRadar(session)
  }
}

const renderComparisonTransversalityRadar = async (session) => {
  const canvasEl = radarTransversality[`radar_${session.id}`]
  if (!canvasEl) return

  if (radarCharts.comparisonTransversality[session.id]) {
    radarCharts.comparisonTransversality[session.id].destroy()
  }

  const score = getOtherSessionScore(session.id, 'transversality_score') || 0

  const ctx = canvasEl.getContext('2d')
  radarCharts.comparisonTransversality[session.id] = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Transversalité'],
      datasets: [{
        label: session.name,
        data: [score],
        borderColor: session.color,
        backgroundColor: session.color + '30',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: session.color,
        pointBorderColor: '#fff',
        pointBorderWidth: 1,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 }, grid: { color: '#e5e7eb' } } },
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ctx.parsed.r + '/5' } } }
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
      labels: ['Stratégique'],
      datasets: [{
        label: 'Stratégique',
        data: [strategicEval.value || 0],
        borderColor: '#ec4899',
        backgroundColor: '#ec489930',
        borderWidth: 3,
        pointRadius: 6,
        pointBackgroundColor: '#ec4899',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 }, grid: { color: '#e5e7eb' } } },
      plugins: { legend: { display: true, position: 'top' }, tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.r + '/5' } } }
    }
  })

  // Render comparison radars
  for (let session of otherSessionsList.value) {
    renderComparisonStrategicRadar(session)
  }
}

const renderComparisonStrategicRadar = async (session) => {
  const canvasEl = radarStrategic[`radar_${session.id}`]
  if (!canvasEl) return

  if (radarCharts.comparisonStrategic[session.id]) {
    radarCharts.comparisonStrategic[session.id].destroy()
  }

  const score = getOtherSessionScore(session.id, 'strategic_score') || 0

  const ctx = canvasEl.getContext('2d')
  radarCharts.comparisonStrategic[session.id] = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Stratégique'],
      datasets: [{
        label: session.name,
        data: [score],
        borderColor: session.color,
        backgroundColor: session.color + '30',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: session.color,
        pointBorderColor: '#fff',
        pointBorderWidth: 1,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 }, grid: { color: '#e5e7eb' } } },
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ctx.parsed.r + '/5' } } }
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
        data: [maturityAverage.value || 0, motricityEval.value || 0, transversalityEval.value || 0, strategicEval.value || 0],
        borderColor: activeSession.value.color || '#10b981',
        backgroundColor: (activeSession.value.color || '#10b981') + '30',
        borderWidth: 3,
        pointRadius: 6,
        pointBackgroundColor: activeSession.value.color || '#10b981',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 }, grid: { color: '#e5e7eb' } } },
      plugins: { legend: { display: true, position: 'top' }, tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.r + '/5' } } }
    }
  })
}

// LOAD
const loadActiveSession = async () => {
  try {
    const res = await axios.get(route('process.core.sessions.active'))
    if (res.data.session) {
      activeSession.value = res.data.session
      await loadSessionEvaluations()
    }
  } catch (e) {
    console.error('Erreur', e)
  }
}

const loadSessionEvaluations = async () => {
  if (!activeSession.value) return
  try {
    for (let session of allSessions.value) {
      const evals = {}
      for (let process of props.processes) {
        const res = await axios.get(route('process.core.evaluations.criticality.load'), {
          params: { session_id: session.id, process_id: process.id }
        })
        const key = `${session.id}_${process.id}`
        evals[key] = res.data.axes || {}
        
        if (res.data.maturity) {
          Object.entries(res.data.maturity).forEach(([criterionCode, level]) => {
            const maturityKey = `${session.id}_${process.id}_${criterionCode}`
            evals[maturityKey] = level
          })
        }
      }
      sessionEvaluations[session.id] = evals
    }
  } catch (e) {
    console.error('Erreur', e)
  }
}

// EVAL
const selectProcessForEvaluation = async (process) => {
  selectedProcess.value = process

  const res = await axios.get(route('process.core.evaluations.criticality.load'), {
    params: { session_id: activeSession.value.id, process_id: process.id }
  })

  Object.keys(maturityEval).forEach(k => delete maturityEval[k])
  if (res.data.maturity) Object.assign(maturityEval, res.data.maturity)

  motricityEval.value = res.data.axes?.motricity_score || null
  transversalityEval.value = res.data.axes?.transversality_score || null
  strategicEval.value = res.data.axes?.strategic_score || null

  currentEvaluationStep.value = 'maturity'
  showEvaluationModal.value = true
}

const setMotricity = async (level) => {
  motricityEval.value = level
  await saveAxis('motricity')
}

const setTransversality = async (level) => {
  transversalityEval.value = level
  await saveAxis('transversality')
}

const setStrategic = async (level) => {
  strategicEval.value = level
  await saveAxis('strategic')
}

// SAVE
const saveMaturityStep = async () => {
  if (Object.keys(maturityEval).length === 0) return
  try {
    const evaluations = Object.entries(maturityEval).map(([code, level]) => ({
      process_id: selectedProcess.value.id,
      criterion_code: code,
      level_score: level
    }))
    await axios.post(route('process.core.evaluations.criticality.maturity.save'), {
      session_id: activeSession.value.id,
      evaluations
    })
  } catch (e) {
    console.error('Erreur', e)
  }
}

const saveAxis = async (axis) => {
  const scoreMap = { motricity: motricityEval.value, transversality: transversalityEval.value, strategic: strategicEval.value }
  if (!scoreMap[axis]) return
  try {
    await axios.post(route('process.core.evaluations.criticality.axis.save'), {
      session_id: activeSession.value.id,
      process_id: selectedProcess.value.id,
      axis: axis,
      score: scoreMap[axis]
    })
  } catch (e) {
    console.error('Erreur', e)
  }
}

// NAVIGATION
const nextStep = async () => {
  const stepKeys = ['maturity', 'motricity', 'transversality', 'strategic', 'summary']
  const currentIdx = stepKeys.indexOf(currentEvaluationStep.value)
  if (currentIdx < stepKeys.length - 1) {
    currentEvaluationStep.value = stepKeys[currentIdx + 1]
  }
}

const previousStep = () => {
  const stepKeys = ['maturity', 'motricity', 'transversality', 'strategic', 'summary']
  const currentIdx = stepKeys.indexOf(currentEvaluationStep.value)
  if (currentIdx > 0) {
    currentEvaluationStep.value = stepKeys[currentIdx - 1]
  }
}

// FINALIZE
const finalizeEvaluation = async () => {
  successMessage.value = '✓ Évaluation enregistrée'
  showEvaluationModal.value = false
  await loadSessionEvaluations()
  setTimeout(() => { successMessage.value = '' }, 2000)
}

const closeEvaluationModal = () => {
  showEvaluationModal.value = false
  selectedProcess.value = null
  Object.keys(maturityEval).forEach(k => delete maturityEval[k])
  motricityEval.value = null
  transversalityEval.value = null
  strategicEval.value = null
  currentEvaluationStep.value = 'maturity'
  
  Object.values(radarCharts).forEach(chart => {
    if (chart && typeof chart.destroy === 'function') chart.destroy()
  })
  Object.values(radarCharts.comparisonMaturity).forEach(chart => {
    if (chart && typeof chart.destroy === 'function') chart.destroy()
  })
  Object.values(radarCharts.comparisonMotricity).forEach(chart => {
    if (chart && typeof chart.destroy === 'function') chart.destroy()
  })
  Object.values(radarCharts.comparisonTransversality).forEach(chart => {
    if (chart && typeof chart.destroy === 'function') chart.destroy()
  })
  Object.values(radarCharts.comparisonStrategic).forEach(chart => {
    if (chart && typeof chart.destroy === 'function') chart.destroy()
  })
  radarCharts = {
    maturity: null,
    motricity: null,
    transversality: null,
    strategic: null,
    summary: null,
    comparisonMaturity: {},
    comparisonMotricity: {},
    comparisonTransversality: {},
    comparisonStrategic: {}
  }
}

// RAPPORT
const generateReport = async () => {
  try {
    const payload = {
      type: reportConfig.type,
      selected_session_id: reportConfig.type === 'single' ? activeSession.value.id : null,
      selected_processes: props.processes.map(p => p.id),
    }

    if (reportConfig.exportPDF) {
      const resPDF = await axios.post(route('process.core.report.export-pdf'), payload, { responseType: 'blob' })
      downloadFile(resPDF.data, `rapport-${activeSession.value.id}.pdf`)
    }

    if (reportConfig.exportExcel) {
      const resExcel = await axios.post(route('process.core.report.export-excel'), payload, { responseType: 'blob' })
      downloadFile(resExcel.data, `rapport-${activeSession.value.id}.xlsx`)
    }

    reportGenerated.value = true
  } catch (e) {
    console.error('Erreur rapport', e)
    alert('Erreur génération rapport')
  }
}

const downloadFile = (blob, filename) => {
  const url = window.URL.createObjectURL(new Blob([blob]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  link.parentNode.removeChild(link)
}

const closeReportModal = () => {
  showReportModal.value = false
  reportGenerated.value = false
}

// WATCHERS
watch(currentEvaluationStep, async (newVal) => {
  if (newVal === 'maturity') {
    await renderMaturityRadar()
  } else if (newVal === 'motricity') {
    await renderMotricityRadar()
  } else if (newVal === 'transversality') {
    await renderTransversalityRadar()
  } else if (newVal === 'strategic') {
    await renderStrategicRadar()
  } else if (newVal === 'summary') {
    await renderSummaryRadar()
  }
})

onMounted(async () => {
  allSessions.value.forEach(session => {
    if (!sessionEvaluations[session.id]) {
      sessionEvaluations[session.id] = {}
    }
  })
  await loadActiveSession()
})
</script>

<style scoped>
.table { margin-bottom: 0; font-size: 0.9rem; }
.table thead th { font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding: 0.6rem; }
.table tbody td { padding: 0.6rem; }
.process-row { transition: 0.2s; }
.process-row:hover { background: rgba(16,185,129,0.08); }
.score-badge { display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; border: 3px solid; border-radius: 50%; background: #f9fafb; font-weight: 700; }
.badge-score { padding: 0.4rem 0.6rem; font-size: 0.85rem; border-radius: 4px; display: inline-block; }
.list-group-item { border: 1px solid #e5e7eb; padding: 0.75rem; }
.progression-container { display: flex; justify-content: space-around; gap: 10px; padding: 20px 0; flex-wrap: wrap; }
.progression-step { flex: 1; text-align: center; min-width: 80px; }
.step-circle { width: 50px; height: 50px; border-radius: 50%; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; transition: 0.3s; }
.step-label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.85rem; }
.step-status { font-weight: 700; font-size: 1.2rem; }
.criteria-item { background: #f9fafb; transition: 0.2s; border-radius: 6px; }
.criteria-item:hover { background: #ecfdf5; }
</style>