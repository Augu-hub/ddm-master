<template>
  <VerticalLayout>
    <Head title="Évaluation des processus" />

    <!-- HEADER -->
    <b-card class="mb-4 border-0 shadow-lg p-4" style="background: linear-gradient(135deg,#10b981,#059669); border-radius:12px;">
      <h2 class="fw-bold mb-2 text-white">Évaluation des processus</h2>
      <p class="mb-0 text-white"><strong>{{ user?.name }}</strong> — <strong>{{ link?.function_name }}</strong></p>
    </b-card>

    <!-- STATS GLOBALES -->
    <b-row class="mb-4 g-3">
      <b-col md="3"><b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;"><div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ processes.length }}</div><small class="text-muted d-block">Processus</small></b-card></b-col>
      <b-col md="3"><b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;"><div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ countEvaluated }}</div><small class="text-muted d-block">Évalués</small></b-card></b-col>
      <b-col md="3"><b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;"><div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ processSessions.length }}</div><small class="text-muted d-block">Sessions</small></b-card></b-col>
      <b-col md="3"><b-card class="border-0 shadow-sm text-center" style="background:linear-gradient(135deg,#ecfdf5,#fff);border-left:5px solid #10b981;"><div class="fw-bold" style="font-size:1.8rem;color:#10b981;">{{ globalAvg.toFixed(2) }}</div><small class="text-muted d-block">Score moyen</small></b-card></b-col>
    </b-row>

    <!-- TABLEAU PROCESSUS -->
    <b-card class="border-0 shadow-sm mb-4">
      <h5 class="fw-bold mb-3"><i class="ti ti-list me-2"></i>Processus</h5>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-sm">
          <thead style="background:#f3f4f6;border-bottom:2px solid #10b981;">
            <tr>
              <th style="min-width:70px;">Code</th>
              <th style="min-width:200px;">Processus</th>
              <th class="text-center" style="min-width:80px;">Eval.</th>
              <th class="text-center" style="min-width:90px;">Score</th>
              <th class="text-center" style="min-width:80px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="process in processes" :key="process.id" class="process-row">
              <td><span class="badge bg-success">{{ process.code }}</span></td>
              <td><strong>{{ process.name }}</strong></td>
              <td class="text-center"><span class="badge" :style="{ background: getProcessScore(process.id) ? '#10b981' : '#d1d5db' }">{{ getProcessScore(process.id) ? '✓' : '—' }}</span></td>
              <td class="text-center"><div class="score-small" :style="{ borderColor: getColorByScore(getProcessScore(process.id)) }">{{ getProcessScore(process.id) ? getProcessScore(process.id).toFixed(1) : '—' }}</div></td>
              <td class="text-center"><b-button variant="success" size="sm" class="fw-bold" @click="selectProcess(process)"><i class="ti ti-arrow-right"></i></b-button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </b-card>

    <!-- PROCESSUS SÉLECTIONNÉ -->
    <div v-if="selectedProcess">
      <b-card class="mb-4 border-0 shadow-sm" style="background:#ecfdf5;border-left:5px solid #10b981;">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="fw-bold text-success mb-0">{{ selectedProcess.code }} — {{ selectedProcess.name }}</h5>
          <b-button variant="outline-success" size="sm" @click="selectedProcess = null" class="fw-bold"><i class="ti ti-x me-1"></i>Fermer</b-button>
        </div>
      </b-card>

      <!-- SESSIONS -->
      <b-card class="mb-4 border-0 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0"><i class="ti ti-calendar me-2"></i>Sessions</h5>
          <b-button variant="success" size="sm" @click="showCreateSessionModal = true" class="fw-bold"><i class="ti ti-plus"></i></b-button>
        </div>
        <select v-model="selectedSession" class="form-select form-select-sm mb-3" @change="loadSessionEvaluations" style="max-width:400px;">
          <option value="">Choisir session</option>
          <option v-for="s in processSessions" :value="s.id" :key="s.id">{{ s.name }} ({{ s.status === 'open' ? '🟢' : '🔴' }})</option>
        </select>
        <div v-if="selectedSession" class="d-flex gap-2 flex-wrap mb-3">
          <b-button size="sm" variant="outline-danger" @click="deleteSession" class="fw-bold"><i class="ti ti-trash"></i></b-button>
          <b-button size="sm" variant="outline-secondary" @click="closeSession" v-if="getCurrentSession()?.status === 'open'" class="fw-bold"><i class="ti ti-lock"></i></b-button>
        </div>
        <div v-if="successMessage" class="alert alert-success mb-0"><i class="ti ti-check me-1"></i>{{ successMessage }}</div>
      </b-card>

      <b-modal v-model="showCreateSessionModal" title="Créer session" @ok="createSession" centered>
        <input v-model="newSessionName" class="form-control mb-2" placeholder="Nom" />
        <input v-model="newSessionColor" type="color" class="form-control form-control-color" style="width:80px;height:35px;" />
      </b-modal>

      <!-- PROGRESSION EN HAUT -->
      <b-card v-if="selectedSession" class="mb-4 border-0 shadow-sm" style="background:#f0fdf4;border:2px solid #10b981;">
        <h5 class="fw-bold mb-3"><i class="ti ti-progress me-2"></i>Progression d'évaluation</h5>
        <div class="progression-container">
          <div v-for="step in evaluationSteps" :key="step.key" class="progression-step" :class="{ completed: isStepEvaluated(step.key) }">
            <div class="step-circle" :style="{ background: getStepColor(step.key) }">
              <i :class="step.icon"></i>
            </div>
            <small class="step-label">{{ step.label }}</small>
            <small class="step-status" :style="{ color: getStepColor(step.key) }">{{ isStepEvaluated(step.key) ? '✓' : '◯' }}</small>
          </div>
        </div>
      </b-card>

      <!-- TABLEAU ÉVALUATION -->
      <b-card v-if="selectedSession && currentEvaluation" class="mb-4 border-0 shadow-sm">
        <h5 class="fw-bold mb-3"><i class="ti ti-chart-line me-2"></i>Évaluation actuelle</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead style="background:#f3f4f6;border-bottom:2px solid #10b981;">
              <tr>
                <th>Critère</th>
                <th class="text-center">Maturité</th>
                <th class="text-center">Motricité</th>
                <th class="text-center">Transversalité</th>
                <th class="text-center">Stratégique</th>
                <th class="text-center">Global</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr style="background:#ecfdf5;">
                <td><strong>Scores actuels</strong></td>
                <td class="text-center">
                  <span class="badge-score" v-if="currentEvaluation.maturity_score" :style="{ background: getColorByLevel(currentEvaluation.maturity_score) }">{{ currentEvaluation.maturity_score.toFixed(1) }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-center">
                  <span class="badge-score" v-if="currentEvaluation.motricity_score" :style="{ background: getColorByLevel(currentEvaluation.motricity_score) }">{{ currentEvaluation.motricity_score.toFixed(1) }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-center">
                  <span class="badge-score" v-if="currentEvaluation.transversality_score" :style="{ background: getColorByLevel(currentEvaluation.transversality_score) }">{{ currentEvaluation.transversality_score.toFixed(1) }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-center">
                  <span class="badge-score" v-if="currentEvaluation.strategic_score" :style="{ background: getColorByLevel(currentEvaluation.strategic_score) }">{{ currentEvaluation.strategic_score.toFixed(1) }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-center">
                  <span class="score-badge-large" :style="{ borderColor: getColorByScore(currentEvaluation.criticality_score || 0) }">{{ (currentEvaluation.criticality_score || 0).toFixed(1) }}</span>
                </td>
                <td class="text-center">
                  <b-button variant="success" size="sm" @click="startEvaluation" :disabled="getCurrentSession()?.status !== 'open'" class="fw-bold"><i class="ti ti-pencil"></i></b-button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </b-card>

      <!-- NAVIGATION 5 ONGLETS (NOUVEAU) -->
      <div v-if="selectedSession" class="mb-4 d-flex gap-2 flex-wrap">
        <b-button v-for="step in steps" :key="step.key" class="fw-bold" size="sm" :variant="currentStep === step.key ? 'success' : 'outline-success'" @click="currentStep = step.key">
          {{ step.label }}
        </b-button>
      </div>

      <!-- ========== ÉTAPE 1 : MATURITÉ ========== -->
      <b-card v-if="currentStep === 'maturity' && selectedSession" class="shadow-lg border-0 mb-4">
        <b-card-header class="bg-light" style="border-bottom:3px solid #10b981;">
          <h5 class="fw-bold"><i class="ti ti-chart-radar-2 me-2"></i> Matrice de maturité</h5>
        </b-card-header>
        <b-card-body>
          <b-row class="g-4">
            <b-col lg="7">
              <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                <table class="table table-sm">
                  <thead style="background:#10b981;color:white;position:sticky;top:0;">
                    <tr>
                      <th>Critère</th>
                      <th v-for="lvl in [1,2,3,4,5]" :key="lvl" class="text-center">N{{ lvl }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="scale in groupedMaturity" :key="scale.code">
                      <td><strong class="text-success">{{ scale.code }}</strong><br><small class="text-muted">{{ scale.label }}</small></td>
                      <td v-for="lvl in scale.levels" :key="lvl.level_score" class="text-center p-2">
                        <div class="maturity-box" :style="maturityBoxColorPage(scale.code, lvl.level_score)" @click="toggleMaturityPage(scale.code, lvl.level_score); renderMaturityPageRadar()"></div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </b-col>
            <b-col lg="5">
              <h6 class="fw-bold mb-3">Aperçu Radar</h6>
              <div style="height:300px;background:#ecfdf5;border:2px solid #10b981;border-radius:12px;padding:1rem;">
                <canvas ref="maturityPageRadarRef"></canvas>
              </div>
            </b-col>
          </b-row>
          <div v-if="otherSessions.length > 0" class="mt-4 pt-4" style="border-top:2px solid #d1d5db;">
            <h6 class="fw-bold mb-3">Comparer avec une autre session</h6>
            <select v-model="comparisonSessionMaturity" class="form-select form-select-sm mb-3" style="max-width:300px;" @change="renderMaturityPageComparisonRadar">
              <option value="">— Aucune comparaison —</option>
              <option v-for="s in otherSessions" :value="s.id" :key="s.id">{{ s.name }}</option>
            </select>
            <div v-if="comparisonSessionMaturity" style="height:300px;background:#ecfdf5;border:2px solid #10b981;border-radius:12px;padding:1rem;">
              <canvas ref="maturityPageComparisonRadarRef"></canvas>
            </div>
          </div>
          <div class="mt-4 text-end">
            <b-button variant="secondary" class="me-2" @click="resetMaturityPage">Réinitialiser</b-button>
            <b-button variant="success" class="fw-bold" @click="saveMaturityPage">Enregistrer</b-button>
          </div>
        </b-card-body>
      </b-card>

      <!-- ========== ÉTAPE 2 : MOTRICITÉ ========== -->
      <b-card v-if="currentStep === 'motricity' && selectedSession" class="shadow-lg border-0 mb-4">
        <b-card-header class="bg-light" style="border-bottom:3px solid #f59e0b;">
          <h5 class="fw-bold"><i class="ti ti-zap me-2"></i> Motricité</h5>
        </b-card-header>
        <b-card-body>
          <b-row class="g-4">
            <b-col lg="6">
              <div class="text-center">
                <p class="fw-bold mb-4">Sélectionnez un niveau (1-5)</p>
                <div class="d-flex gap-3 flex-wrap justify-content-center mb-4">
                  <b-button v-for="level in [1,2,3,4,5]" :key="level" size="lg" :style="motricitySelectionPage === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}" :variant="motricitySelectionPage === level ? '' : 'outline-warning'" @click="motricitySelectionPage = level; renderMotricityPageRadar()" class="fw-bold">{{ level }}</b-button>
                </div>
                <div v-if="motricitySelectionPage" class="p-3" style="background:#f9fafb;border-radius:8px;border:2px solid #d1d5db;">
                  <small class="text-muted text-uppercase">Niveau {{ motricitySelectionPage }}</small>
                  <p class="fw-bold mb-2">{{ getLevelDesc(motricitySelectionPage) }}</p>
                </div>
              </div>
            </b-col>
            <b-col lg="6">
              <h6 class="fw-bold mb-3">Radar Motricité</h6>
              <div style="height:300px;background:#f9fafb;border:2px solid #f59e0b;border-radius:12px;padding:1rem;">
                <canvas ref="motricityPageRadarRef"></canvas>
              </div>
            </b-col>
          </b-row>
          <div v-if="otherSessions.length > 0" class="mt-4 pt-4" style="border-top:2px solid #d1d5db;">
            <h6 class="fw-bold mb-3">Comparer avec une autre session</h6>
            <select v-model="comparisonSessionMotricity" class="form-select form-select-sm mb-3" style="max-width:300px;" @change="renderMotricityPageComparisonRadar">
              <option value="">— Aucune comparaison —</option>
              <option v-for="s in otherSessions" :value="s.id" :key="s.id">{{ s.name }}</option>
            </select>
            <div v-if="comparisonSessionMotricity" style="height:300px;background:#f9fafb;border:2px solid #f59e0b;border-radius:12px;padding:1rem;">
              <canvas ref="motricityPageComparisonRadarRef"></canvas>
            </div>
          </div>
          <div class="mt-4 pt-3 d-flex gap-2 justify-content-end">
            <b-button variant="secondary" @click="resetMotricityPage" class="fw-bold">Réinitialiser</b-button>
            <b-button variant="success" @click="saveMotricityPage" :disabled="motricitySelectionPage === null" class="fw-bold">Enregistrer</b-button>
          </div>
        </b-card-body>
      </b-card>

      <!-- ========== ÉTAPE 3 : TRANSVERSALITÉ ========== -->
      <b-card v-if="currentStep === 'transversality' && selectedSession" class="shadow-lg border-0 mb-4">
        <b-card-header class="bg-light" style="border-bottom:3px solid #8b5cf6;">
          <h5 class="fw-bold"><i class="ti ti-link me-2"></i> Transversalité</h5>
        </b-card-header>
        <b-card-body>
          <b-row class="g-4">
            <b-col lg="6">
              <div class="text-center">
                <p class="fw-bold mb-4">Sélectionnez un niveau (1-5)</p>
                <div class="d-flex gap-3 flex-wrap justify-content-center mb-4">
                  <b-button v-for="level in [1,2,3,4,5]" :key="level" size="lg" :style="transversalitySelectionPage === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}" :variant="transversalitySelectionPage === level ? '' : 'outline-info'" @click="transversalitySelectionPage = level; renderTransversalityPageRadar()" class="fw-bold">{{ level }}</b-button>
                </div>
                <div v-if="transversalitySelectionPage" class="p-3" style="background:#f9fafb;border-radius:8px;border:2px solid #d1d5db;">
                  <small class="text-muted text-uppercase">Niveau {{ transversalitySelectionPage }}</small>
                  <p class="fw-bold mb-2">{{ getLevelDesc(transversalitySelectionPage) }}</p>
                </div>
              </div>
            </b-col>
            <b-col lg="6">
              <h6 class="fw-bold mb-3">Radar Transversalité</h6>
              <div style="height:300px;background:#f9fafb;border:2px solid #8b5cf6;border-radius:12px;padding:1rem;">
                <canvas ref="transversalityPageRadarRef"></canvas>
              </div>
            </b-col>
          </b-row>
          <div v-if="otherSessions.length > 0" class="mt-4 pt-4" style="border-top:2px solid #d1d5db;">
            <h6 class="fw-bold mb-3">Comparer avec une autre session</h6>
            <select v-model="comparisonSessionTransversality" class="form-select form-select-sm mb-3" style="max-width:300px;" @change="renderTransversalityPageComparisonRadar">
              <option value="">— Aucune comparaison —</option>
              <option v-for="s in otherSessions" :value="s.id" :key="s.id">{{ s.name }}</option>
            </select>
            <div v-if="comparisonSessionTransversality" style="height:300px;background:#f9fafb;border:2px solid #8b5cf6;border-radius:12px;padding:1rem;">
              <canvas ref="transversalityPageComparisonRadarRef"></canvas>
            </div>
          </div>
          <div class="mt-4 pt-3 d-flex gap-2 justify-content-end">
            <b-button variant="secondary" @click="resetTransversalityPage" class="fw-bold">Réinitialiser</b-button>
            <b-button variant="success" @click="saveTransversalityPage" :disabled="transversalitySelectionPage === null" class="fw-bold">Enregistrer</b-button>
          </div>
        </b-card-body>
      </b-card>

      <!-- ========== ÉTAPE 4 : STRATÉGIQUE ========== -->
      <b-card v-if="currentStep === 'strategic' && selectedSession" class="shadow-lg border-0 mb-4">
        <b-card-header class="bg-light" style="border-bottom:3px solid #ec4899;">
          <h5 class="fw-bold"><i class="ti ti-target me-2"></i> Poids Stratégique</h5>
        </b-card-header>
        <b-card-body>
          <b-row class="g-4">
            <b-col lg="6">
              <div class="text-center">
                <p class="fw-bold mb-4">Sélectionnez un niveau (1-5)</p>
                <div class="d-flex gap-3 flex-wrap justify-content-center mb-4">
                  <b-button v-for="level in [1,2,3,4,5]" :key="level" size="lg" :style="strategicSelectionPage === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}" :variant="strategicSelectionPage === level ? '' : 'outline-danger'" @click="strategicSelectionPage = level; renderStrategicPageRadar()" class="fw-bold">{{ level }}</b-button>
                </div>
                <div v-if="strategicSelectionPage" class="p-3" style="background:#f9fafb;border-radius:8px;border:2px solid #d1d5db;">
                  <small class="text-muted text-uppercase">Niveau {{ strategicSelectionPage }}</small>
                  <p class="fw-bold mb-2">{{ getLevelDesc(strategicSelectionPage) }}</p>
                </div>
              </div>
            </b-col>
            <b-col lg="6">
              <h6 class="fw-bold mb-3">Radar Stratégique</h6>
              <div style="height:300px;background:#f9fafb;border:2px solid #ec4899;border-radius:12px;padding:1rem;">
                <canvas ref="strategicPageRadarRef"></canvas>
              </div>
            </b-col>
          </b-row>
          <div v-if="otherSessions.length > 0" class="mt-4 pt-4" style="border-top:2px solid #d1d5db;">
            <h6 class="fw-bold mb-3">Comparer avec une autre session</h6>
            <select v-model="comparisonSessionStrategic" class="form-select form-select-sm mb-3" style="max-width:300px;" @change="renderStrategicPageComparisonRadar">
              <option value="">— Aucune comparaison —</option>
              <option v-for="s in otherSessions" :value="s.id" :key="s.id">{{ s.name }}</option>
            </select>
            <div v-if="comparisonSessionStrategic" style="height:300px;background:#f9fafb;border:2px solid #ec4899;border-radius:12px;padding:1rem;">
              <canvas ref="strategicPageComparisonRadarRef"></canvas>
            </div>
          </div>
          <div class="mt-4 pt-3 d-flex gap-2 justify-content-end">
            <b-button variant="secondary" @click="resetStrategicPage" class="fw-bold">Réinitialiser</b-button>
            <b-button variant="success" @click="saveStrategicPage" :disabled="strategicSelectionPage === null" class="fw-bold">Enregistrer</b-button>
          </div>
        </b-card-body>
      </b-card>

      <!-- ========== ÉTAPE 5 : RÉSUMÉ ========== -->
      <b-card v-if="currentStep === 'summary' && selectedSession" class="shadow-lg border-0 mb-4">
        <b-card-header class="bg-light" style="border-bottom:3px solid #10b981;">
          <h5 class="fw-bold"><i class="ti ti-radar me-2"></i> Vue d'ensemble globale</h5>
        </b-card-header>
        <b-card-body>
          <b-row class="g-4">
            <b-col lg="6">
              <h6 class="fw-bold mb-3">Radar complet (4 axes)</h6>
              <div style="height:350px;background:#f9fafb;border:2px solid #10b981;border-radius:12px;padding:1rem;">
                <canvas ref="generalPageRadarRef"></canvas>
              </div>
            </b-col>
            <b-col lg="6">
              <h6 class="fw-bold mb-3">Résumé des scores</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead style="background:#f3f4f6;border-bottom:2px solid #10b981;">
                    <tr>
                      <th>Critère</th>
                      <th class="text-center">Score</th>
                      <th class="text-center">Statut</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><strong>Maturité</strong></td>
                      <td class="text-center"><span class="badge" :style="{ background: getColorByLevel(maturityPageAverage) }">{{ maturityPageAverage.toFixed(1) }}/5</span></td>
                      <td class="text-center"><i :class="getStatusIcon(maturityPageAverage)" :style="{ color: getColorByLevel(maturityPageAverage) }"></i></td>
                    </tr>
                    <tr>
                      <td><strong>Motricité</strong></td>
                      <td class="text-center"><span class="badge" :style="{ background: getColorByLevel(motricitySelectionPage) }">{{ motricitySelectionPage ? motricitySelectionPage.toFixed(1) : '—' }}/5</span></td>
                      <td class="text-center"><i v-if="motricitySelectionPage" :class="getStatusIcon(motricitySelectionPage)" :style="{ color: getColorByLevel(motricitySelectionPage) }"></i></td>
                    </tr>
                    <tr>
                      <td><strong>Transversalité</strong></td>
                      <td class="text-center"><span class="badge" :style="{ background: getColorByLevel(transversalitySelectionPage) }">{{ transversalitySelectionPage ? transversalitySelectionPage.toFixed(1) : '—' }}/5</span></td>
                      <td class="text-center"><i v-if="transversalitySelectionPage" :class="getStatusIcon(transversalitySelectionPage)" :style="{ color: getColorByLevel(transversalitySelectionPage) }"></i></td>
                    </tr>
                    <tr>
                      <td><strong>Stratégique</strong></td>
                      <td class="text-center"><span class="badge" :style="{ background: getColorByLevel(strategicSelectionPage) }">{{ strategicSelectionPage ? strategicSelectionPage.toFixed(1) : '—' }}/5</span></td>
                      <td class="text-center"><i v-if="strategicSelectionPage" :class="getStatusIcon(strategicSelectionPage)" :style="{ color: getColorByLevel(strategicSelectionPage) }"></i></td>
                    </tr>
                    <tr style="background:#ecfdf5;font-weight:bold;">
                      <td>SCORE GLOBAL</td>
                      <td class="text-center"><span class="badge" :style="{ background: getColorByLevel(pageAverageScore) }">{{ pageAverageScore.toFixed(2) }}/5</span></td>
                      <td class="text-center"><i :class="getStatusIcon(pageAverageScore)" :style="{ color: getColorByLevel(pageAverageScore) }"></i></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </b-col>
          </b-row>
          <div v-if="otherSessions.length > 0" class="mt-4 pt-4" style="border-top:2px solid #d1d5db;">
            <h6 class="fw-bold mb-3">Comparer avec une autre session (tous les 4 axes)</h6>
            <select v-model="comparisonSessionGeneral" class="form-select form-select-sm mb-3" style="max-width:300px;" @change="renderGeneralPageComparisonRadar">
              <option value="">— Aucune comparaison —</option>
              <option v-for="s in otherSessions" :value="s.id" :key="s.id">{{ s.name }}</option>
            </select>
            <div v-if="comparisonSessionGeneral" style="height:350px;background:#f9fafb;border:2px solid #10b981;border-radius:12px;padding:1rem;">
              <canvas ref="generalPageComparisonRadarRef"></canvas>
            </div>
          </div>
          <div class="mt-4 p-3" style="background:#f0fdf4;border:2px solid #10b981;border-radius:8px;">
            <p class="mb-2"><strong>📊 Interprétation :</strong></p>
            <small class="text-muted d-block">
              • <strong>Rouge (1-2)</strong> : Processus non formalisé<br>
              • <strong>Orange (3)</strong> : Processus défini<br>
              • <strong>Vert (4-5)</strong> : Processus mesuré/optimisé
            </small>
          </div>
        </b-card-body>
      </b-card>

      <!-- RADAR MATURITÉ EN BAS (Original) -->
      <b-card v-if="selectedSession && currentStep === 'original'" class="border-0 shadow-sm">
        <h5 class="fw-bold mb-3"><i class="ti ti-radar me-2"></i>Radar Maturité</h5>
        <b-row>
          <b-col lg="6">
            <canvas ref="radarRef" style="max-height:350px;"></canvas>
          </b-col>
          <b-col lg="6">
            <h6 class="fw-bold mb-2">Comparaison</h6>
            <select v-model="selectedComparisonSession" class="form-select form-select-sm mb-3" @change="renderComparisonRadar">
              <option value="">Sélectionner session</option>
              <option v-for="s in otherSessions" :value="s.id" :key="s.id">{{ s.name }}</option>
            </select>
            <canvas ref="comparisonRadarRef" v-if="selectedComparisonSession" style="max-height:350px;"></canvas>
            <p v-else class="text-muted text-center py-5">Aucune session sélectionnée</p>
          </b-col>
        </b-row>
      </b-card>
    </div>

    <!-- MODALES MODIFIÉES POUR COMPATIBILITÉ -->
    <b-modal v-model="showMaturityModal" title="Matrice de Maturité" size="xl" @ok="saveMaturity" centered no-close-on-backdrop>
      <b-row>
        <b-col lg="7">
          <h6 class="fw-bold mb-3">Sélectionner les critères</h6>
          <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
            <table class="table table-sm">
              <thead style="background:#10b981;color:white;position:sticky;top:0;">
                <tr>
                  <th>Critère</th>
                  <th v-for="lvl in [1,2,3,4,5]" :key="lvl" class="text-center" style="width:50px;">N{{ lvl }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="scale in groupedMaturity" :key="scale.code">
                  <td><strong>{{ scale.code }}</strong></td>
                  <td v-for="lvl in scale.levels" :key="lvl.level_score" class="text-center p-2">
                    <div class="maturity-box" :style="maturityBoxColor(scale.code, lvl.level_score)" @click="toggleMaturity(scale.code, lvl.level_score)"></div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </b-col>
        <b-col lg="5">
          <h6 class="fw-bold mb-3">Aperçu Radar</h6>
          <canvas ref="maturityModalRadarRef" style="max-height:350px;"></canvas>
        </b-col>
      </b-row>
    </b-modal>

    <b-modal v-model="showMotricityModal" title="Motricité" @ok="saveMotricity" centered no-close-on-backdrop>
      <div class="text-center">
        <p class="fw-bold mb-3">Sélectionnez un niveau (1-5)</p>
        <div class="d-flex gap-2 flex-wrap justify-content-center">
          <b-button v-for="level in [1,2,3,4,5]" :key="level" size="lg" :style="motricitySelection === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}" :variant="motricitySelection === level ? '' : 'outline-warning'" @click="motricitySelection = level">{{ level }}</b-button>
        </div>
      </div>
    </b-modal>

    <b-modal v-model="showTransversalityModal" title="Transversalité" @ok="saveTransversality" centered no-close-on-backdrop>
      <div class="text-center">
        <p class="fw-bold mb-3">Sélectionnez un niveau (1-5)</p>
        <div class="d-flex gap-2 flex-wrap justify-content-center">
          <b-button v-for="level in [1,2,3,4,5]" :key="level" size="lg" :style="transversalitySelection === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}" :variant="transversalitySelection === level ? '' : 'outline-info'" @click="transversalitySelection = level">{{ level }}</b-button>
        </div>
      </div>
    </b-modal>

    <b-modal v-model="showStrategicModal" title="Poids Stratégique" @ok="saveStrategic" centered no-close-on-backdrop>
      <div class="text-center">
        <p class="fw-bold mb-3">Sélectionnez un niveau (1-5)</p>
        <div class="d-flex gap-2 flex-wrap justify-content-center">
          <b-button v-for="level in [1,2,3,4,5]" :key="level" size="lg" :style="strategicSelection === level ? { background: getColorByLevel(level), color: 'white', border: 'none' } : {}" :variant="strategicSelection === level ? '' : 'outline-danger'" @click="strategicSelection = level">{{ level }}</b-button>
        </div>
      </div>
    </b-modal>
  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"
import axios from "axios"
import { ref, reactive, computed, onMounted, watch, nextTick } from "vue"

const props = defineProps({
  user: Object,
  link: Object,
  processes: Array,
  maturityLevels: Array,
  motricityScales: Array,
  transversalityScales: Array,
  strategicScales: Array,
})

// STATE - ORIGINAL
const selectedProcess = ref(null)
const selectedSession = ref("")
const selectedComparisonSession = ref("")
const processSessions = ref([])
const allScores = reactive({})
const successMessage = ref("")
const showCreateSessionModal = ref(false)
const newSessionName = ref("")
const newSessionColor = ref("#10b981")
const showMaturityModal = ref(false)
const showMotricityModal = ref(false)
const showTransversalityModal = ref(false)
const showStrategicModal = ref(false)
const maturityData = reactive({})
const motricitySelection = ref(null)
const transversalitySelection = ref(null)
const strategicSelection = ref(null)
const radarRef = ref(null)
const comparisonRadarRef = ref(null)
const maturityModalRadarRef = ref(null)
let radarChart = null
let comparisonChart = null
let maturityModalChart = null

// STATE - NOUVEAU (5 ÉTAPES)
const currentStep = ref('maturity')
const maturityDataPage = reactive({})
const comparisonSessionMaturity = ref('')
const motricitySelectionPage = ref(null)
const comparisonSessionMotricity = ref('')
const transversalitySelectionPage = ref(null)
const comparisonSessionTransversality = ref('')
const strategicSelectionPage = ref(null)
const comparisonSessionStrategic = ref('')
const comparisonSessionGeneral = ref('')

const maturityPageRadarRef = ref(null)
const maturityPageComparisonRadarRef = ref(null)
const motricityPageRadarRef = ref(null)
const motricityPageComparisonRadarRef = ref(null)
const transversalityPageRadarRef = ref(null)
const transversalityPageComparisonRadarRef = ref(null)
const strategicPageRadarRef = ref(null)
const strategicPageComparisonRadarRef = ref(null)
const generalPageRadarRef = ref(null)
const generalPageComparisonRadarRef = ref(null)

let charts = {}
let Chart = null

const evaluationSteps = [
  { key: 'maturity', label: 'Maturité', icon: 'ti ti-mountain' },
  { key: 'motricity', label: 'Motricité', icon: 'ti ti-zap' },
  { key: 'transversality', label: 'Transversalité', icon: 'ti ti-link' },
  { key: 'strategic', label: 'Stratégique', icon: 'ti ti-target' }
]

const steps = [
  { key: "maturity", label: "🎯 Maturité" },
  { key: "motricity", label: "⚡ Motricité" },
  { key: "transversality", label: "🔗 Transversalité" },
  { key: "strategic", label: "📌 Stratégique" },
  { key: "summary", label: "📊 Résumé" },
]

// COMPUTED
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

const currentEvaluation = computed(() => {
  if (!selectedSession.value) return null
  return allScores[selectedSession.value] || null
})

const otherSessions = computed(() => processSessions.value.filter(s => s.id !== selectedSession.value))

const countEvaluated = computed(() => {
  let count = 0
  Object.values(allScores).forEach(s => {
    if (s?.criticality_score) count++
  })
  return count
})

const globalAvg = computed(() => {
  const scores = Object.values(allScores).filter(s => s?.criticality_score).map(s => s.criticality_score)
  return scores.length ? scores.reduce((a, b) => a + b, 0) / scores.length : 0
})

const maturityPageAverage = computed(() => {
  const vals = Object.values(maturityDataPage)
  return vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : 0
})

const pageAverageScore = computed(() => {
  const vals = [maturityPageAverage.value, motricitySelectionPage.value, transversalitySelectionPage.value, strategicSelectionPage.value].filter(v => v !== null)
  return vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : 0
})

// FUNCTIONS
const getColorByLevel = (level) => {
  const colors = { 1: "#ef4444", 2: "#ef4444", 3: "#f59e0b", 4: "#10b981", 5: "#10b981" }
  return colors[level] || "#d1d5db"
}

const getColorByScore = (score) => {
  if (!score || score === 0) return "#d1d5db"
  if (score >= 4) return "#10b981"
  if (score >= 3) return "#f59e0b"
  if (score >= 2) return "#ef4444"
  return "#d1d5db"
}

const getProcessScore = (processId) => {
  for (let key in allScores) {
    if (allScores[key].process_id === processId && allScores[key].criticality_score) {
      return allScores[key].criticality_score
    }
  }
  return 0
}

const isStepEvaluated = (step) => {
  if (!currentEvaluation.value) return false
  const key = step + '_score'
  return currentEvaluation.value[key] > 0
}

const getStepColor = (step) => {
  if (!isStepEvaluated(step)) return "#d1d5db"
  const score = currentEvaluation.value[step + '_score']
  return getColorByScore(score)
}

const maturityBoxColor = (code, level) => {
  const selected = maturityData[code] === level
  const color = getColorByLevel(level)
  if (selected) {
    return { background: color, color: "white", border: `2px solid ${color}`, fontWeight: 'bold' }
  }
  return { background: "#f9fafb", border: "1px solid #d1d5db", color: "#374151" }
}

const maturityBoxColorPage = (code, level) => {
  const selected = maturityDataPage[code] === level
  const color = getColorByLevel(level)
  if (selected) return { background: color, color: "white", border: `2px solid ${color}`, fontWeight: 'bold' }
  return { background: "#f9fafb", border: "1px solid #d1d5db", color: "#374151" }
}

const getLevelDesc = (level) => {
  const descs = { 1: "Niveau Initial", 2: "Niveau Reproductible", 3: "Niveau Défini", 4: "Niveau Mesuré", 5: "Niveau Optimisé" }
  return descs[level] || ""
}

const getStatusIcon = (score) => {
  if (score >= 4) return "ti ti-check text-success"
  if (score >= 3) return "ti ti-alert-circle text-warning"
  if (score >= 2) return "ti ti-x text-danger"
  return "ti ti-help text-muted"
}

// FONCTIONS ORIGINALES
const selectProcess = async (process) => {
  selectedProcess.value = process
  selectedSession.value = ""
  selectedComparisonSession.value = ""
  await loadProcessSessions()
}

const loadProcessSessions = async () => {
  try {
    const res = await axios.get(route("process.core.evaluations.sessions"), {
      params: { process_id: selectedProcess.value.id }
    })
    processSessions.value = res.data
  } catch (e) {
    console.error("Erreur sessions", e)
  }
}

const loadSessionEvaluations = async () => {
  if (!selectedSession.value) return
  try {
    const res = await axios.get(route("process.core.evaluations.load"), {
      params: {
        session_id: selectedSession.value,
        process_id: selectedProcess.value.id
      }
    })
    
    allScores[selectedSession.value] = {
      ...res.data.axes,
      process_id: selectedProcess.value.id
    }
    
    Object.keys(maturityData).forEach(k => delete maturityData[k])
    Object.keys(maturityDataPage).forEach(k => delete maturityDataPage[k])
    
    if (res.data.maturity) {
      Object.assign(maturityData, res.data.maturity)
      Object.assign(maturityDataPage, res.data.maturity)
    }
    
    if (res.data.axes) {
      motricitySelection.value = res.data.axes.motricity_score || null
      transversalitySelection.value = res.data.axes.transversality_score || null
      strategicSelection.value = res.data.axes.strategic_score || null
      motricitySelectionPage.value = res.data.axes.motricity_score || null
      transversalitySelectionPage.value = res.data.axes.transversality_score || null
      strategicSelectionPage.value = res.data.axes.strategic_score || null
    }
    
    currentStep.value = 'maturity'
    await nextTick()
    await renderRadar()
    renderMaturityPageRadar()
    renderMotricityPageRadar()
    renderTransversalityPageRadar()
    renderStrategicPageRadar()
    renderGeneralPageRadar()
  } catch (e) {
    console.error("Erreur load", e)
  }
}

const getCurrentSession = () => processSessions.value.find(s => s.id === selectedSession.value)

const createSession = async () => {
  try {
    const res = await axios.post(route("process.core.evaluations.sessions.create"), {
      process_id: selectedProcess.value.id,
      entity_id: props.link.entity_id,
      function_id: props.link.function_id,
      name: newSessionName.value,
      color: newSessionColor.value,
    })
    showCreateSessionModal.value = false
    newSessionName.value = ""
    newSessionColor.value = "#10b981"
    await loadProcessSessions()
    selectedSession.value = res.data.session_id
    successMessage.value = "Session créée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch (e) {
    console.error("Erreur create", e)
  }
}

const closeSession = async () => {
  try {
    await axios.post(route("process.core.evaluations.sessions.close"), {
      session_id: selectedSession.value
    })
    await loadProcessSessions()
    successMessage.value = "Session clôturée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch (e) {
    console.error("Erreur close", e)
  }
}

const deleteSession = async () => {
  if (!confirm("Supprimer ?")) return
  try {
    await axios.post(route("process.core.evaluations.sessions.delete"), {
      session_id: selectedSession.value
    })
    delete allScores[selectedSession.value]
    selectedSession.value = ""
    await loadProcessSessions()
    successMessage.value = "Session supprimée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch (e) {
    console.error("Erreur delete", e)
  }
}

const startEvaluation = async () => {
  try {
    const res = await axios.get(route("process.core.evaluations.load"), {
      params: {
        session_id: selectedSession.value,
        process_id: selectedProcess.value.id
      }
    })
    Object.keys(maturityData).forEach(k => delete maturityData[k])
    if (res.data.maturity) {
      Object.assign(maturityData, res.data.maturity)
    }
    await nextTick()
    showMaturityModal.value = true
    await nextTick()
    await new Promise(r => setTimeout(r, 500))
    await renderMaturityModalRadar()
  } catch (e) {
    console.error("Erreur start", e)
  }
}

const toggleMaturity = (code, level) => { maturityData[code] = level; renderMaturityModalRadar() }

const saveMaturity = async () => {
  if (Object.keys(maturityData).length === 0) return
  const evaluations = Object.entries(maturityData).map(([code, level]) => ({
    process_id: selectedProcess.value.id,
    criterion_code: code,
    level_score: level,
  }))
  try {
    await axios.post(route("process.core.evaluations.maturity.save"), {
      session_id: selectedSession.value,
      evaluations,
    })
    showMaturityModal.value = false
    await nextTick()
    await new Promise(r => setTimeout(r, 300))
    showMotricityModal.value = true
  } catch (e) {
    console.error("Erreur maturity", e)
  }
}

const saveMotricity = async () => {
  if (!motricitySelection.value) return
  try {
    await axios.post(route("process.core.evaluations.axis.save"), {
      session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      axis: 'motricity',
      score: motricitySelection.value,
    })
    showMotricityModal.value = false
    await nextTick()
    await new Promise(r => setTimeout(r, 300))
    showTransversalityModal.value = true
  } catch (e) {
    console.error("Erreur motricity", e)
  }
}

const saveTransversality = async () => {
  if (!transversalitySelection.value) return
  try {
    await axios.post(route("process.core.evaluations.axis.save"), {
      session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      axis: 'transversality',
      score: transversalitySelection.value,
    })
    showTransversalityModal.value = false
    await nextTick()
    await new Promise(r => setTimeout(r, 300))
    showStrategicModal.value = true
  } catch (e) {
    console.error("Erreur transversality", e)
  }
}

const saveStrategic = async () => {
  if (!strategicSelection.value) return
  try {
    await axios.post(route("process.core.evaluations.axis.save"), {
      session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      axis: 'strategic',
      score: strategicSelection.value,
    })
    showStrategicModal.value = false
    successMessage.value = "Évaluation terminée ✓"
    setTimeout(() => {
      successMessage.value = ""
      Object.keys(maturityData).forEach(k => delete maturityData[k])
      motricitySelection.value = null
      transversalitySelection.value = null
      strategicSelection.value = null
      loadSessionEvaluations()
      renderRadar()
    }, 1500)
  } catch (e) {
    console.error("Erreur strategic", e)
  }
}

const renderRadar = async () => {
  if (!radarRef.value || !currentEvaluation.value) return
  
  let Chart
  try {
    Chart = (await import("chart.js/auto")).default
  } catch {
    return
  }
  
  try {
    if (radarChart) radarChart.destroy()
    
    const session = getCurrentSession()
    radarChart = new Chart(radarRef.value, {
      type: 'radar',
      data: {
        labels: ['Critère 1', 'Critère 2', 'Critère 3', 'Critère 4'],
        datasets: [{
          label: 'Maturité',
          data: [currentEvaluation.value.maturity_score || 0, currentEvaluation.value.maturity_score || 0, currentEvaluation.value.maturity_score || 0, currentEvaluation.value.maturity_score || 0],
          borderColor: session?.color || '#10b981',
          backgroundColor: (session?.color || '#10b981') + '33',
          pointBackgroundColor: session?.color || '#10b981',
          borderWidth: 2,
        }]
      },
      options: { responsive: true, maintainAspectRatio: true, scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } } } }
    })
  } catch (err) {
    console.error("Erreur radar", err)
  }
}

const renderComparisonRadar = async () => {
  if (!comparisonRadarRef.value || !selectedComparisonSession.value) return
  
  let Chart
  try {
    Chart = (await import("chart.js/auto")).default
  } catch {
    return
  }
  
  try {
    const comp = allScores[selectedComparisonSession.value]
    if (!comp) return
    
    if (comparisonChart) comparisonChart.destroy()
    
    const session = processSessions.value.find(s => s.id === selectedComparisonSession.value)
    const current = getCurrentSession()
    
    comparisonChart = new Chart(comparisonRadarRef.value, {
      type: 'radar',
      data: {
        labels: ['Critère 1', 'Critère 2', 'Critère 3', 'Critère 4'],
        datasets: [
          {
            label: current?.name,
            data: [currentEvaluation.value?.maturity_score || 0, currentEvaluation.value?.maturity_score || 0, currentEvaluation.value?.maturity_score || 0, currentEvaluation.value?.maturity_score || 0],
            borderColor: current?.color || '#10b981',
            backgroundColor: (current?.color || '#10b981') + '33',
            pointBackgroundColor: current?.color || '#10b981',
            borderWidth: 2,
          },
          {
            label: session?.name,
            data: [comp.maturity_score || 0, comp.maturity_score || 0, comp.maturity_score || 0, comp.maturity_score || 0],
            borderColor: session?.color || '#f59e0b',
            backgroundColor: (session?.color || '#f59e0b') + '33',
            pointBackgroundColor: session?.color || '#f59e0b',
            borderWidth: 2,
          }
        ]
      },
      options: { responsive: true, maintainAspectRatio: true, scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } } } }
    })
  } catch (err) {
    console.error("Erreur comparison", err)
  }
}

const renderMaturityModalRadar = async () => {
  if (!maturityModalRadarRef.value) return
  
  let Chart
  try {
    Chart = (await import("chart.js/auto")).default
  } catch {
    return
  }
  
  try {
    if (maturityModalChart) maturityModalChart.destroy()
    
    const scores = Object.values(maturityData)
    const avg = scores.length ? scores.reduce((a, b) => a + b) / scores.length : 0
    
    maturityModalChart = new Chart(maturityModalRadarRef.value, {
      type: 'radar',
      data: {
        labels: ['Maturité', 'Motricité', 'Transversalité', 'Stratégique'],
        datasets: [{
          label: 'Sélection',
          data: [avg, 0, 0, 0],
          borderColor: '#10b981',
          backgroundColor: '#10b98133',
          pointBackgroundColor: '#10b981',
          borderWidth: 2,
        }]
      },
      options: { responsive: true, maintainAspectRatio: true, scales: { r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } } } }
    })
  } catch (err) {
    console.error("Erreur modal radar", err)
  }
}

// FONCTIONS NOUVELLES (5 ÉTAPES)
const toggleMaturityPage = (code, level) => { maturityDataPage[code] = level }

const resetMaturityPage = () => {
  Object.keys(maturityDataPage).forEach(k => delete maturityDataPage[k])
  comparisonSessionMaturity.value = ''
  renderMaturityPageRadar()
}

const saveMaturityPage = async () => {
  const evaluations = Object.entries(maturityDataPage).map(([code, level]) => ({
    process_id: selectedProcess.value.id,
    criterion_code: code,
    level_score: level,
  }))
  try {
    await axios.post(route("process.core.evaluations.maturity.save"), {
      session_id: selectedSession.value,
      evaluations,
    })
    Object.assign(allScores[selectedSession.value], { maturity_score: maturityPageAverage.value })
    successMessage.value = "Maturité enregistrée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch (e) {
    console.error("Erreur maturité", e)
  }
}

const renderMaturityPageRadar = async () => {
  if (!maturityPageRadarRef.value || !Chart) return
  if (charts.maturityPage) charts.maturityPage.destroy()
  charts.maturityPage = new Chart(maturityPageRadarRef.value, {
    type: 'radar',
    data: {
      labels: groupedMaturity.value.map(g => g.code),
      datasets: [{
        label: 'Maturité',
        data: groupedMaturity.value.map(g => maturityDataPage[g.code] || 0),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.2)',
        pointBackgroundColor: '#10b981',
        borderWidth: 2,
      }]
    },
    options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
  })
}

const renderMaturityPageComparisonRadar = async () => {
  if (!maturityPageComparisonRadarRef.value || !comparisonSessionMaturity.value) return
  try {
    const res = await axios.get(route("process.core.evaluations.load"), {
      params: { session_id: comparisonSessionMaturity.value, process_id: selectedProcess.value.id }
    })
    if (charts.maturityPageComp) charts.maturityPageComp.destroy()
    const compSession = processSessions.value.find(s => s.id === comparisonSessionMaturity.value)
    const currentSession = getCurrentSession()
    charts.maturityPageComp = new Chart(maturityPageComparisonRadarRef.value, {
      type: 'radar',
      data: {
        labels: groupedMaturity.value.map(g => g.code),
        datasets: [
          {
            label: currentSession?.name,
            data: groupedMaturity.value.map(g => maturityDataPage[g.code] || 0),
            borderColor: currentSession?.color || '#10b981',
            backgroundColor: (currentSession?.color || '#10b981') + '22',
            pointBackgroundColor: currentSession?.color || '#10b981',
            borderWidth: 2,
          },
          {
            label: compSession?.name,
            data: groupedMaturity.value.map(g => res.data.maturity?.[g.code] || 0),
            borderColor: compSession?.color || '#8b5cf6',
            backgroundColor: (compSession?.color || '#8b5cf6') + '22',
            pointBackgroundColor: compSession?.color || '#8b5cf6',
            borderWidth: 2,
          }
        ]
      },
      options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
    })
  } catch (e) {
    console.error("Erreur comparaison", e)
  }
}

// Motricité page
const resetMotricityPage = () => {
  motricitySelectionPage.value = null
  comparisonSessionMotricity.value = ''
  renderMotricityPageRadar()
}

const saveMotricityPage = async () => {
  try {
    await axios.post(route("process.core.evaluations.axis.save"), {
      session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      axis: 'motricity',
      score: motricitySelectionPage.value,
    })
    Object.assign(allScores[selectedSession.value], { motricity_score: motricitySelectionPage.value })
    successMessage.value = "Motricité enregistrée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch (e) {
    console.error("Erreur motricité", e)
  }
}

const renderMotricityPageRadar = async () => {
  if (!motricityPageRadarRef.value || !Chart) return
  if (charts.motricityPage) charts.motricityPage.destroy()
  const data = motricitySelectionPage.value || 0
  charts.motricityPage = new Chart(motricityPageRadarRef.value, {
    type: 'radar',
    data: {
      labels: ['Motricité', 'Axe 2', 'Axe 3', 'Axe 4'],
      datasets: [{
        label: 'Motricité',
        data: [data, 0, 0, 0],
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245,158,11,0.2)',
        pointBackgroundColor: '#f59e0b',
        borderWidth: 2,
      }]
    },
    options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
  })
}

const renderMotricityPageComparisonRadar = async () => {
  if (!motricityPageComparisonRadarRef.value || !comparisonSessionMotricity.value) return
  try {
    const res = await axios.get(route("process.core.evaluations.load"), {
      params: { session_id: comparisonSessionMotricity.value, process_id: selectedProcess.value.id }
    })
    if (charts.motricityPageComp) charts.motricityPageComp.destroy()
    const compSession = processSessions.value.find(s => s.id === comparisonSessionMotricity.value)
    const currentSession = getCurrentSession()
    charts.motricityPageComp = new Chart(motricityPageComparisonRadarRef.value, {
      type: 'radar',
      data: {
        labels: ['Motricité', 'Axe 2', 'Axe 3', 'Axe 4'],
        datasets: [
          {
            label: currentSession?.name,
            data: [motricitySelectionPage.value || 0, 0, 0, 0],
            borderColor: currentSession?.color || '#f59e0b',
            backgroundColor: (currentSession?.color || '#f59e0b') + '22',
            pointBackgroundColor: currentSession?.color || '#f59e0b',
            borderWidth: 2,
          },
          {
            label: compSession?.name,
            data: [res.data.axes?.motricity_score || 0, 0, 0, 0],
            borderColor: compSession?.color || '#8b5cf6',
            backgroundColor: (compSession?.color || '#8b5cf6') + '22',
            pointBackgroundColor: compSession?.color || '#8b5cf6',
            borderWidth: 2,
          }
        ]
      },
      options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
    })
  } catch (e) {
    console.error("Erreur comparaison", e)
  }
}

// Transversalité page
const resetTransversalityPage = () => {
  transversalitySelectionPage.value = null
  comparisonSessionTransversality.value = ''
  renderTransversalityPageRadar()
}

const saveTransversalityPage = async () => {
  try {
    await axios.post(route("process.core.evaluations.axis.save"), {
      session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      axis: 'transversality',
      score: transversalitySelectionPage.value,
    })
    Object.assign(allScores[selectedSession.value], { transversality_score: transversalitySelectionPage.value })
    successMessage.value = "Transversalité enregistrée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch (e) {
    console.error("Erreur transversalité", e)
  }
}

const renderTransversalityPageRadar = async () => {
  if (!transversalityPageRadarRef.value || !Chart) return
  if (charts.transversalityPage) charts.transversalityPage.destroy()
  const data = transversalitySelectionPage.value || 0
  charts.transversalityPage = new Chart(transversalityPageRadarRef.value, {
    type: 'radar',
    data: {
      labels: ['Transversalité', 'Axe 2', 'Axe 3', 'Axe 4'],
      datasets: [{
        label: 'Transversalité',
        data: [data, 0, 0, 0],
        borderColor: '#8b5cf6',
        backgroundColor: 'rgba(139,92,246,0.2)',
        pointBackgroundColor: '#8b5cf6',
        borderWidth: 2,
      }]
    },
    options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
  })
}

const renderTransversalityPageComparisonRadar = async () => {
  if (!transversalityPageComparisonRadarRef.value || !comparisonSessionTransversality.value) return
  try {
    const res = await axios.get(route("process.core.evaluations.load"), {
      params: { session_id: comparisonSessionTransversality.value, process_id: selectedProcess.value.id }
    })
    if (charts.transversalityPageComp) charts.transversalityPageComp.destroy()
    const compSession = processSessions.value.find(s => s.id === comparisonSessionTransversality.value)
    const currentSession = getCurrentSession()
    charts.transversalityPageComp = new Chart(transversalityPageComparisonRadarRef.value, {
      type: 'radar',
      data: {
        labels: ['Transversalité', 'Axe 2', 'Axe 3', 'Axe 4'],
        datasets: [
          {
            label: currentSession?.name,
            data: [transversalitySelectionPage.value || 0, 0, 0, 0],
            borderColor: currentSession?.color || '#8b5cf6',
            backgroundColor: (currentSession?.color || '#8b5cf6') + '22',
            pointBackgroundColor: currentSession?.color || '#8b5cf6',
            borderWidth: 2,
          },
          {
            label: compSession?.name,
            data: [res.data.axes?.transversality_score || 0, 0, 0, 0],
            borderColor: compSession?.color || '#8b5cf6',
            backgroundColor: (compSession?.color || '#8b5cf6') + '22',
            pointBackgroundColor: compSession?.color || '#8b5cf6',
            borderWidth: 2,
          }
        ]
      },
      options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
    })
  } catch (e) {
    console.error("Erreur comparaison", e)
  }
}

// Stratégique page
const resetStrategicPage = () => {
  strategicSelectionPage.value = null
  comparisonSessionStrategic.value = ''
  renderStrategicPageRadar()
}

const saveStrategicPage = async () => {
  try {
    await axios.post(route("process.core.evaluations.axis.save"), {
      session_id: selectedSession.value,
      process_id: selectedProcess.value.id,
      axis: 'strategic',
      score: strategicSelectionPage.value,
    })
    Object.assign(allScores[selectedSession.value], { strategic_score: strategicSelectionPage.value })
    successMessage.value = "Stratégique enregistrée ✓"
    setTimeout(() => { successMessage.value = "" }, 2000)
  } catch (e) {
    console.error("Erreur stratégique", e)
  }
}

const renderStrategicPageRadar = async () => {
  if (!strategicPageRadarRef.value || !Chart) return
  if (charts.strategicPage) charts.strategicPage.destroy()
  const data = strategicSelectionPage.value || 0
  charts.strategicPage = new Chart(strategicPageRadarRef.value, {
    type: 'radar',
    data: {
      labels: ['Stratégique', 'Axe 2', 'Axe 3', 'Axe 4'],
      datasets: [{
        label: 'Stratégique',
        data: [data, 0, 0, 0],
        borderColor: '#ec4899',
        backgroundColor: 'rgba(236,72,153,0.2)',
        pointBackgroundColor: '#ec4899',
        borderWidth: 2,
      }]
    },
    options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
  })
}

const renderStrategicPageComparisonRadar = async () => {
  if (!strategicPageComparisonRadarRef.value || !comparisonSessionStrategic.value) return
  try {
    const res = await axios.get(route("process.core.evaluations.load"), {
      params: { session_id: comparisonSessionStrategic.value, process_id: selectedProcess.value.id }
    })
    if (charts.strategicPageComp) charts.strategicPageComp.destroy()
    const compSession = processSessions.value.find(s => s.id === comparisonSessionStrategic.value)
    const currentSession = getCurrentSession()
    charts.strategicPageComp = new Chart(strategicPageComparisonRadarRef.value, {
      type: 'radar',
      data: {
        labels: ['Stratégique', 'Axe 2', 'Axe 3', 'Axe 4'],
        datasets: [
          {
            label: currentSession?.name,
            data: [strategicSelectionPage.value || 0, 0, 0, 0],
            borderColor: currentSession?.color || '#ec4899',
            backgroundColor: (currentSession?.color || '#ec4899') + '22',
            pointBackgroundColor: currentSession?.color || '#ec4899',
            borderWidth: 2,
          },
          {
            label: compSession?.name,
            data: [res.data.axes?.strategic_score || 0, 0, 0, 0],
            borderColor: compSession?.color || '#8b5cf6',
            backgroundColor: (compSession?.color || '#8b5cf6') + '22',
            pointBackgroundColor: compSession?.color || '#8b5cf6',
            borderWidth: 2,
          }
        ]
      },
      options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
    })
  } catch (e) {
    console.error("Erreur comparaison", e)
  }
}

// Résumé page
const renderGeneralPageRadar = async () => {
  if (!generalPageRadarRef.value || !Chart) return
  if (charts.generalPage) charts.generalPage.destroy()
  charts.generalPage = new Chart(generalPageRadarRef.value, {
    type: 'radar',
    data: {
      labels: ['Maturité', 'Motricité', 'Transversalité', 'Stratégique'],
      datasets: [{
        label: 'Scores',
        data: [maturityPageAverage.value || 0, motricitySelectionPage.value || 0, transversalitySelectionPage.value || 0, strategicSelectionPage.value || 0],
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.2)',
        pointBackgroundColor: '#10b981',
        borderWidth: 2,
      }]
    },
    options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
  })
}

const renderGeneralPageComparisonRadar = async () => {
  if (!generalPageComparisonRadarRef.value || !comparisonSessionGeneral.value) return
  try {
    const res = await axios.get(route("process.core.evaluations.load"), {
      params: { session_id: comparisonSessionGeneral.value, process_id: selectedProcess.value.id }
    })
    if (charts.generalPageComp) charts.generalPageComp.destroy()
    const compSession = processSessions.value.find(s => s.id === comparisonSessionGeneral.value)
    const currentSession = getCurrentSession()
    const compMaturityAvg = res.data.maturity ? Object.values(res.data.maturity).reduce((a,b)=>a+b,0)/Object.keys(res.data.maturity).length : 0
    charts.generalPageComp = new Chart(generalPageComparisonRadarRef.value, {
      type: 'radar',
      data: {
        labels: ['Maturité', 'Motricité', 'Transversalité', 'Stratégique'],
        datasets: [
          {
            label: currentSession?.name,
            data: [maturityPageAverage.value || 0, motricitySelectionPage.value || 0, transversalitySelectionPage.value || 0, strategicSelectionPage.value || 0],
            borderColor: currentSession?.color || '#10b981',
            backgroundColor: (currentSession?.color || '#10b981') + '22',
            pointBackgroundColor: currentSession?.color || '#10b981',
            borderWidth: 2,
          },
          {
            label: compSession?.name,
            data: [compMaturityAvg || 0, res.data.axes?.motricity_score || 0, res.data.axes?.transversality_score || 0, res.data.axes?.strategic_score || 0],
            borderColor: compSession?.color || '#8b5cf6',
            backgroundColor: (compSession?.color || '#8b5cf6') + '22',
            pointBackgroundColor: compSession?.color || '#8b5cf6',
            borderWidth: 2,
          }
        ]
      },
      options: { responsive: true, maintainAspectRatio: true, scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } } }
    })
  } catch (e) {
    console.error("Erreur comparaison", e)
  }
}

// WATCHERS
watch(selectedSession, async () => {
  if (selectedSession.value) {
    await nextTick()
    await renderRadar()
  }
})

watch(selectedComparisonSession, () => renderComparisonRadar())

watch(currentStep, async (newVal) => {
  if (newVal === 'maturity') await nextTick(), renderMaturityPageRadar()
  else if (newVal === 'motricity') await nextTick(), renderMotricityPageRadar()
  else if (newVal === 'transversality') await nextTick(), renderTransversalityPageRadar()
  else if (newVal === 'strategic') await nextTick(), renderStrategicPageRadar()
  else if (newVal === 'summary') await nextTick(), renderGeneralPageRadar()
})

// MOUNT
onMounted(async () => {
  Chart = (await import("chart.js/auto")).default
})
</script>

<style scoped>
.table-responsive { border-radius: 8px; }
.table { margin-bottom: 0; font-size: 0.9rem; }
.table thead th { font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding: 0.6rem; }
.table tbody td { padding: 0.6rem; }
.process-row { transition: 0.2s; }
.process-row:hover { background: rgba(16,185,129,0.08); }
.score-small { display: inline-flex; align-items: center; justify-content: center; width: 45px; height: 45px; border: 3px solid #d1d5db; border-radius: 50%; background: #f9fafb; font-weight: 700; font-size: 0.95rem; color: #374151; }
.score-badge-large { display: inline-flex; align-items: center; justify-content: center; width: 55px; height: 55px; border: 3px solid #d1d5db; border-radius: 50%; background: #f9fafb; font-weight: 800; font-size: 1.1rem; color: #10b981; }
.badge-score { color: white; font-weight: 600; padding: 0.4rem 0.6rem; font-size: 0.85rem; border-radius: 4px; display: inline-block; }
.maturity-box { width: 35px; height: 35px; border-radius: 4px; cursor: pointer; transition: 0.2s; }
.maturity-box:hover { transform: scale(1.1); }
.form-select-sm { font-size: 0.9rem; }
.progression-container { display: flex; justify-content: space-around; align-items: center; gap: 10px; padding: 20px 0; }
.progression-step { flex: 1; text-align: center; }
.step-circle { width: 50px; height: 50px; border-radius: 50%; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; transition: 0.3s; }
.step-circle:hover { transform: scale(1.05); }
.step-label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.85rem; }
.step-status { font-weight: 700; font-size: 1.2rem; }
.progression-step.completed .step-circle { transform: scale(1.1); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
</style>