<?php

use App\Http\Controllers\Audit\AuditProcessScoringController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Risk\RiskController;
use App\Http\Controllers\Risk\SettingsController;
use App\Http\Controllers\Risk\AuditUniverseController;
use App\Http\Controllers\Risk\ControlMeasureController;
use App\Http\Controllers\Risk\RiskAssessmentController;
use App\Http\Controllers\Risk\RiskMitigationController;
use App\Http\Controllers\Risk\MissionPhaseController;
use App\Http\Controllers\Risk\MissionController;

use App\Http\Controllers\Audit\FactorInputController;
use App\Http\Controllers\Audit\MissionPrioritizationController;
use App\Http\Controllers\Audit\ProcessFactorEvaluationController;
use App\Http\Controllers\Audit\MissionRequestController;
use App\Http\Controllers\Audit\AuditPlanningController;

use App\Http\Controllers\Audit\MissionProgrammingController;


$authMiddleware = ['auth', 'verified'];
$apiMiddleware = ['api'];


Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware($authMiddleware)->name('dashboard');


Route::middleware($authMiddleware)->prefix('api/risque')->name('api.risque.')->group(function () {

  
    Route::get('/', [RiskController::class, 'index'])->name('index');
    Route::post('/', [RiskController::class, 'store'])->name('store');
    Route::get('/{risk}', [RiskController::class, 'show'])->name('show');
    Route::put('/{risk}', [RiskController::class, 'update'])->name('update');
    Route::patch('/{risk}', [RiskController::class, 'updatePartial'])->name('patch');
    Route::delete('/{risk}', [RiskController::class, 'destroy'])->name('destroy');
    Route::post('/switch-session', [RiskController::class, 'switchSession'])->name('switch-session');

    // ───────────────────────────────────────────────────────────────────────────────────
    // RECHERCHE & FILTRAGE (6 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::get('/search', [RiskController::class, 'search'])->name('search');
    Route::get('/by-entity/{entityId}', [RiskController::class, 'getByEntity'])->name('by-entity');
    Route::get('/by-process/{processId}', [RiskController::class, 'getByProcess'])->name('by-process');
    Route::get('/by-activity/{activityId}', [RiskController::class, 'getByActivity'])->name('by-activity');
    Route::get('/by-severity/{severity}', [RiskController::class, 'getBySeverity'])->name('by-severity');
    Route::get('/high-priority', [RiskController::class, 'getHighPriority'])->name('high-priority');

    // ───────────────────────────────────────────────────────────────────────────────────
    // TYPES DE RISQUE (4 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::get('/types/all', [RiskController::class, 'getTypes'])->name('types.list');
    Route::post('/types', [RiskController::class, 'storeType'])->name('types.store');
    Route::put('/types/{typeId}', [RiskController::class, 'updateType'])->name('types.update');
    Route::delete('/types/{typeId}', [RiskController::class, 'deleteType'])->name('types.delete');

    // ───────────────────────────────────────────────────────────────────────────────────
    // FRÉQUENCES (4 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::get('/frequencies/all', [RiskController::class, 'getFrequencies'])->name('frequencies.list');
    Route::post('/frequencies', [RiskController::class, 'storeFrequency'])->name('frequencies.store');
    Route::put('/frequencies/{frequencyId}', [RiskController::class, 'updateFrequency'])->name('frequencies.update');
    Route::delete('/frequencies/{frequencyId}', [RiskController::class, 'deleteFrequency'])->name('frequencies.delete');

    // ───────────────────────────────────────────────────────────────────────────────────
    // IMPACTS (4 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::get('/impacts/all', [RiskController::class, 'getImpacts'])->name('impacts.list');
    Route::post('/impacts', [RiskController::class, 'storeImpact'])->name('impacts.store');
    Route::put('/impacts/{impactId}', [RiskController::class, 'updateImpact'])->name('impacts.update');
    Route::delete('/impacts/{impactId}', [RiskController::class, 'deleteImpact'])->name('impacts.delete');

    Route::get('/stats/summary', [RiskController::class, 'getStatsSummary'])->name('stats.summary');
    Route::get('/stats/by-entity', [RiskController::class, 'getStatsByEntity'])->name('stats.by-entity');
    Route::get('/stats/by-process', [RiskController::class, 'getStatsByProcess'])->name('stats.by-process');
    Route::get('/stats/by-severity', [RiskController::class, 'getStatsBySeverity'])->name('stats.by-severity');
    Route::get('/stats/heatmap', [RiskController::class, 'getHeatmap'])->name('stats.heatmap');

    // ───────────────────────────────────────────────────────────────────────────────────
    // EXPORT (3 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::get('/export/csv', [RiskController::class, 'exportCsv'])->name('export.csv');
    Route::get('/export/excel', [RiskController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [RiskController::class, 'exportPdf'])->name('export.pdf');

    // ───────────────────────────────────────────────────────────────────────────────────
    // INTÉGRATION IA (4 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::post('/ai/suggest-risks', [RiskController::class, 'suggestRisksAI'])->name('ai.suggest-risks');
    Route::post('/{risk}/ai/suggest-controls', [RiskController::class, 'suggestControlsAI'])->name('ai.suggest-controls');
    Route::post('/{risk}/ai/suggest-mitigations', [RiskController::class, 'suggestMitigationsAI'])->name('ai.suggest-mitigations');
    Route::post('/ai/analyze', [RiskController::class, 'analyzeRisksAI'])->name('ai.analyze');

    // ───────────────────────────────────────────────────────────────────────────────────
    // OPÉRATIONS EN MASSE (2 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::post('/bulk-update', [RiskController::class, 'bulkUpdate'])->name('bulk-update');
    Route::post('/bulk-delete', [RiskController::class, 'bulkDelete'])->name('bulk-delete');

}); // Fin api/risque


Route::middleware($authMiddleware)->prefix('api/settings')->name('api.settings.')->group(function () {

    // 📊 PAGE INDEX
    Route::get('/', [SettingsController::class, 'index'])->name('index');

    // 📊 STATISTIQUES
    Route::get('/stats', [SettingsController::class, 'getStats'])->name('stats');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 📋 TYPES RISQUE
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/risk-types', [SettingsController::class, 'getRiskTypes'])->name('risk-types.index');
    Route::post('/risk-types', [SettingsController::class, 'storeRiskType'])->name('risk-types.store');
    Route::put('/risk-types/{id}', [SettingsController::class, 'updateRiskType'])->name('risk-types.update');
    Route::delete('/risk-types/{id}', [SettingsController::class, 'deleteRiskType'])->name('risk-types.delete');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 📊 FRÉQUENCES
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/frequencies', [SettingsController::class, 'getFrequencies'])->name('frequencies.index');
    Route::post('/frequencies', [SettingsController::class, 'storeFrequency'])->name('frequencies.store');
    Route::put('/frequencies/{id}', [SettingsController::class, 'updateFrequency'])->name('frequencies.update');
    Route::delete('/frequencies/{id}', [SettingsController::class, 'deleteFrequency'])->name('frequencies.delete');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // ⚡ IMPACTS
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/impacts', [SettingsController::class, 'getImpacts'])->name('impacts.index');
    Route::post('/impacts', [SettingsController::class, 'storeImpact'])->name('impacts.store');
    Route::put('/impacts/{id}', [SettingsController::class, 'updateImpact'])->name('impacts.update');
    Route::delete('/impacts/{id}', [SettingsController::class, 'deleteImpact'])->name('impacts.delete');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 📊 MATRICE
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/matrix', [SettingsController::class, 'getMatrix'])->name('matrix.index');
    Route::post('/matrix', [SettingsController::class, 'storeMatrix'])->name('matrix.store');
    Route::delete('/matrix/{id}', [SettingsController::class, 'deleteMatrix'])->name('matrix.delete');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 🏛️ ENTITÉS
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/entities', [SettingsController::class, 'getEntities'])->name('entities.index');
    Route::post('/entities', [SettingsController::class, 'storeEntity'])->name('entities.store');
    Route::put('/entities/{id}', [SettingsController::class, 'updateEntity'])->name('entities.update');
    Route::delete('/entities/{id}', [SettingsController::class, 'deleteEntity'])->name('entities.delete');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // ⚙️ PROCESSUS
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/processes', [SettingsController::class, 'getProcesses'])->name('processes.index');
    Route::post('/processes', [SettingsController::class, 'storeProcess'])->name('processes.store');
    Route::put('/processes/{id}', [SettingsController::class, 'updateProcess'])->name('processes.update');
    Route::delete('/processes/{id}', [SettingsController::class, 'deleteProcess'])->name('processes.delete');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 📌 ACTIVITÉS
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/activities', [SettingsController::class, 'getActivities'])->name('activities.index');
    Route::post('/activities', [SettingsController::class, 'storeActivity'])->name('activities.store');
    Route::put('/activities/{id}', [SettingsController::class, 'updateActivity'])->name('activities.update');
    Route::delete('/activities/{id}', [SettingsController::class, 'deleteActivity'])->name('activities.delete');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 📚 EXERCICES
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/exercises', [SettingsController::class, 'getExercises'])->name('exercises.index');
    Route::post('/exercises', [SettingsController::class, 'storeExercise'])->name('exercises.store');
    Route::put('/exercises/{id}', [SettingsController::class, 'updateExercise'])->name('exercises.update');
    Route::delete('/exercises/{id}', [SettingsController::class, 'deleteExercise'])->name('exercises.delete');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 🎯 SESSIONS
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/sessions', [SettingsController::class, 'getSessions'])->name('sessions.index');
    Route::post('/sessions', [SettingsController::class, 'storeSession'])->name('sessions.store');
    Route::put('/sessions/{id}', [SettingsController::class, 'updateSession'])->name('sessions.update');
    Route::delete('/sessions/{id}', [SettingsController::class, 'deleteSession'])->name('sessions.delete');

}); // Fin api/settings

// ════════════════════════════════════════════════════════════════════════════════════════
// 🗂️ API REST - AUDIT UNIVERSE (ENDPOINTS EXISTANTS)
// ════════════════════════════════════════════════════════════════════════════════════════

Route::middleware($authMiddleware)->prefix('api/audit-universe')->name('api.audit-universe.')->group(function () {

    Route::get('/', [AuditUniverseController::class, 'index'])
        ->name('index');
    Route::get('/entities', [AuditUniverseController::class, 'getEntities'])->name('entities');
    Route::get('/entities/{entityId}', [AuditUniverseController::class, 'getEntityDetails'])->name('entity-details');
    Route::get('/entities/{entityId}/processes', [AuditUniverseController::class, 'getEntityProcesses'])->name('entity-processes');
    Route::get('/entities/{entityId}/risks', [AuditUniverseController::class, 'getEntityRisks'])->name('entity-risks');

    Route::get('/processes', [AuditUniverseController::class, 'getProcesses'])->name('processes');
    Route::get('/processes/{processId}', [AuditUniverseController::class, 'getProcessDetails'])->name('process-details');
    Route::get('/processes/{processId}/activities', [AuditUniverseController::class, 'getProcessActivities'])->name('process-activities');
    Route::get('/processes/{processId}/risks', [AuditUniverseController::class, 'getProcessRisks'])->name('process-risks');

    Route::get('/activities', [AuditUniverseController::class, 'getActivities'])->name('activities');
    Route::get('/activities/{activityId}', [AuditUniverseController::class, 'getActivityDetails'])->name('activity-details');

}); // Fin api/audit-universe

// ════════════════════════════════════════════════════════════════════════════════════════
// 📊 API REST - MISSION PHASES (ENDPOINTS EXISTANTS)
// ════════════════════════════════════════════════════════════════════════════════════════

Route::prefix('api/mission-phases')->name('api.mission-phases.')->group(function () {

    // ⚠️ ROUTES SPÉCIFIQUES D'ABORD (avant les routes génériques /{phase})
    
    // Types (spécifique)
    Route::get('/types/all', [MissionPhaseController::class, 'getAllTypes'])->name('types.list');

    // Hiérarchie (spécifique)
    Route::get('/hierarchy/{typeId}', [MissionPhaseController::class, 'getHierarchy'])->name('hierarchy')
        ->where('typeId', '[0-9]+');

    // Statistiques (spécifique)
    Route::get('/stats/summary', [MissionPhaseController::class, 'getStats'])->name('stats');

    // Export (spécifique)
    Route::get('/export', [MissionPhaseController::class, 'export'])->name('export');

    // Recherche (spécifique)
    Route::get('/search', [MissionPhaseController::class, 'search'])->name('search');

    // Type {typeId} (spécifique - avec constraint)
    Route::get('/type/{typeId}', [MissionPhaseController::class, 'getPhasesByType'])->name('by-type')
        ->where('typeId', '[0-9]+');
    Route::get('/type/{typeId}/roots', [MissionPhaseController::class, 'getRootPhases'])->name('roots')
        ->where('typeId', '[0-9]+');

    // Opérations en masse (spécifique)
    Route::post('/batch-update', [MissionPhaseController::class, 'batchUpdate'])->name('batch-update');
    Route::post('/batch-delete', [MissionPhaseController::class, 'batchDelete'])->name('batch-delete');

    // Import (spécifique)
    Route::post('/import', [MissionPhaseController::class, 'import'])->name('import');

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ROUTES GÉNÉRIQUES (CRUD - moins spécifiques, à la fin)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    // CRUD
    Route::post('/', [MissionPhaseController::class, 'store'])->name('store');
    Route::get('/', [MissionPhaseController::class, 'index'])->name('index');

    // Routes avec {phase} en paramètre (générique - à la fin)
    Route::get('/{phase}', [MissionPhaseController::class, 'show'])->name('show')
        ->where('phase', '[0-9]+');

    Route::put('/{phase}', [MissionPhaseController::class, 'update'])->name('update')
        ->where('phase', '[0-9]+');

    Route::patch('/{phase}', [MissionPhaseController::class, 'updatePartial'])->name('patch')
        ->where('phase', '[0-9]+');

    Route::delete('/{phase}', [MissionPhaseController::class, 'destroy'])->name('destroy')
        ->where('phase', '[0-9]+');

    // Phase avec sous-ressources (children, path, assignments, assign)
    Route::get('/{phase}/children', [MissionPhaseController::class, 'getChildren'])->name('children')
        ->where('phase', '[0-9]+');

    Route::get('/{phase}/path', [MissionPhaseController::class, 'getPath'])->name('path')
        ->where('phase', '[0-9]+');

    Route::get('/{phase}/assignments', [MissionPhaseController::class, 'getAssignments'])->name('assignments')
        ->where('phase', '[0-9]+');

    Route::post('/{phase}/assign', [MissionPhaseController::class, 'assignToMission'])->name('assign')
        ->where('phase', '[0-9]+');

}); // Fin api/mission-phases
Route::middleware(['auth'])->group(function () {

    // ── Dashboard Inertia ────────────────────────────────────────────────
    Route::get('/m/mission-phases', [MissionPhaseController::class, 'index'])
        ->name('mission-phases.index');

    // ── API JSON ─────────────────────────────────────────────────────────
    Route::prefix('/m/audit.core/api/mission-phases')->group(function () {

        // Hiérarchie complète d'un type d'audit
        Route::get('/hierarchy/{typeId}',   [MissionPhaseController::class, 'getHierarchy'])
            ->name('api.mission-phases.hierarchy');

        // Détails d'une phase
        Route::get('/{phase}/details',      [MissionPhaseController::class, 'getDetails'])
            ->name('api.mission-phases.details');

        // CRUD
        Route::post('/',                    [MissionPhaseController::class, 'store'])
            ->name('api.mission-phases.store');

        Route::put('/{phase}',              [MissionPhaseController::class, 'update'])
            ->name('api.mission-phases.update');

        Route::delete('/{phase}',           [MissionPhaseController::class, 'destroy'])
            ->name('api.mission-phases.destroy');

        // Assignation à une mission
        Route::post('/{phase}/assign',      [MissionPhaseController::class, 'assignToMission'])
            ->name('api.mission-phases.assign');

        // ✅ NOUVEAU — Import seed ASS-ADC
        Route::post('/seed-adc',            [MissionPhaseController::class, 'seedAdcPhases'])
            ->name('api.mission-phases.seed-adc');
    });
});
// ════════════════════════════════════════════════════════════════════════════════════════
// 🎫 ROUTES MISSIONS (EXISTANTES)
// ════════════════════════════════════════════════════════════════════════════════════════

Route::get('/creation-de-mission', [MissionController::class, 'create'])
    ->name('creation-de-mission');

Route::post('/missions', [MissionController::class, 'store'])
    ->name('missions.store');

Route::middleware(['auth'])->prefix('missions')->name('missions.')->group(function () {
    // Ajouter ici les autres routes missions si nécessaire
    Route::get('/', [MissionController::class, 'index'])->name('index');
    Route::get('/{mission}', [MissionController::class, 'show'])->name('show');
    Route::get('/{mission}/edit', [MissionController::class, 'edit'])->name('edit');
    Route::put('/{mission}', [MissionController::class, 'update'])->name('update');
    Route::delete('/{mission}', [MissionController::class, 'destroy'])->name('destroy');
    
});
Route::post('/ai/suggest-mission-title', [MissionController::class, 'suggestTitle'])
    ->middleware('auth');

// ════════════════════════════════════════════════════════════════════════════════════════
// 📊 ROUTES MENU NAVIGATION (LIENS DIRECTS)
// ════════════════════════════════════════════════════════════════════════════════════════

Route::middleware($authMiddleware)->group(function () {
    
    // Menu Audit System
    Route::get('/audit-menu', function () {
        return view('audit-menu');
    })->name('audit-menu');

    // Quick Links
    Route::get('/quick-audit-factors', [FactorInputController::class, 'index'])
        ->name('quick.factors');
    
    Route::get('/quick-audit-evaluation', [ProcessFactorEvaluationController::class, 'index'])
        ->name('quick.evaluation');
    
    Route::get('/quick-audit-planning', [AuditPlanningController::class, 'index'])
        ->name('quick.planning');

});

Route::prefix('api/audit')->name('api.audit.')->group(function () {

    
    Route::get('/', [FactorInputController::class, 'index'])
        ->name('index');
    Route::post('/factors', [FactorInputController::class, 'store'])
        ->name('factors.store');
    Route::get('/factors/{id}', [FactorInputController::class, 'show'])
        ->name('factors.show');
    Route::put('/factors/{id}', [FactorInputController::class, 'update'])
        ->name('factors.update');
    Route::delete('/factors/{id}', [FactorInputController::class, 'destroy'])
        ->name('factors.destroy');
    Route::get('/factors/search/{query}', [FactorInputController::class, 'search'])
        ->name('factors.search');


    Route::get('/prioritization', [MissionPrioritizationController::class, 'index'])
        ->name('prioritization.index');
    Route::get('/prioritization/{missionId}', [MissionPrioritizationController::class, 'show'])
        ->name('prioritization.show');
    Route::put('/prioritization/{missionId}', [MissionPrioritizationController::class, 'updateMissionFactors'])
        ->name('prioritization.update');
    Route::post('/prioritization/{missionId}/calculate', [MissionPrioritizationController::class, 'calculateCoefficient'])
        ->name('prioritization.calculate');
    Route::get('/prioritization/stats/summary', [MissionPrioritizationController::class, 'getStatsSummary'])
        ->name('prioritization.stats');
    Route::get('/prioritization/export/csv', [MissionPrioritizationController::class, 'exportCsv'])
        ->name('prioritization.export-csv');


    // ═══════════════════════════════════════════════════════════════════════════════════
    // 3️⃣ ÉVALUATION FACTEURS (Processus × Facteurs)
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/process-evaluation', [ProcessFactorEvaluationController::class, 'index'])
        ->name('process-evaluation.index');
    Route::get('/process-evaluation/{processId}', [ProcessFactorEvaluationController::class, 'show'])
        ->name('process-evaluation.show');
    Route::put('/process-evaluation/{processId}', [ProcessFactorEvaluationController::class, 'updateFactorEvaluation'])
        ->name('process-evaluation.update');
    Route::post('/process-evaluation/{processId}/recalculate', [ProcessFactorEvaluationController::class, 'recalculateSummary'])
        ->name('process-evaluation.recalculate');
    Route::get('/process-evaluation/year/{year}', [ProcessFactorEvaluationController::class, 'getByYear'])
        ->name('process-evaluation.by-year');
    Route::get('/process-evaluation/summary/{processId}', [ProcessFactorEvaluationController::class, 'getSummary'])
        ->name('process-evaluation.summary');
    Route::post('/process-evaluation/bulk-update', [ProcessFactorEvaluationController::class, 'bulkUpdate'])
        ->name('process-evaluation.bulk-update');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 4️⃣ DEMANDES DE MISSIONS (MAD)
    // ═══════════════════════════════════════════════════════════════════════════════════
   // ═══════════════════════════════════════════════════════════════════════════════════
// 4️⃣ DEMANDES DE MISSIONS (MAD) - FORMULAIRE UNIFIÉ
// ═══════════════════════════════════════════════════════════════════════════════════

// IMPORTANT: Placer la route CREATE AVANT la route générique {id}
Route::get('/mission-requests/create', [MissionRequestController::class, 'create'])
    ->name('mission-requests.create');  // ✅ AVEC LE "S"

Route::get('/mission-requests', [MissionRequestController::class, 'index'])
    ->name('mission-requests.index');

Route::post('/mission-requests', [MissionRequestController::class, 'store'])
    ->name('mission-requests.store');

Route::get('/mission-requests/{id}', [MissionRequestController::class, 'show'])
    ->name('mission-requests.show')
    ->where('id', '[0-9]+');

Route::put('/mission-requests/{id}', [MissionRequestController::class, 'update'])
    ->name('mission-requests.update')
    ->where('id', '[0-9]+');

Route::delete('/mission-requests/{id}', [MissionRequestController::class, 'destroy'])
    ->name('mission-requests.destroy')
    ->where('id', '[0-9]+');

Route::get('/mission-requests/status/{status}', [MissionRequestController::class, 'getByStatus'])
    ->name('mission-requests.by-status');

Route::patch('/mission-requests/{id}/status', [MissionRequestController::class, 'updateStatus'])
    ->name('mission-requests.update-status')
    ->where('id', '[0-9]+');

Route::post('/mission-requests/{id}/assign-entities', [MissionRequestController::class, 'assignEntities'])
    ->name('mission-requests.assign-entities')
    ->where('id', '[0-9]+');

Route::get('/mission-requests/{id}/entities', [MissionRequestController::class, 'getAuditedEntities'])
    ->name('mission-requests.entities')
    ->where('id', '[0-9]+');

Route::post('/mission-requests/bulk-export', [MissionRequestController::class, 'bulkExport'])
    ->name('mission-requests.bulk-export');

Route::get('/mission-requests/{shareCode}/fill', [MissionRequestController::class, 'fill'])
    ->name('mission-requests.fill')
    ->where('shareCode', '[A-Z0-9]+');

Route::post('/mission-requests/generate-link', [MissionRequestController::class, 'generateFormLink'])
    ->name('mission-requests.generate-link');

    Route::get('/planning', [AuditPlanningController::class, 'index'])
        ->name('planning.index');
    Route::get('/planning/year/{year}', [AuditPlanningController::class, 'getByYear'])
        ->name('planning.by-year');
    Route::post('/planning/generate-missions', [AuditPlanningController::class, 'generateMissions'])
        ->name('planning.generate-missions');
    Route::get('/planning/risks', [AuditPlanningController::class, 'getAllRisks'])
        ->name('planning.risks');
    Route::get('/planning/risks/criticality/{level}', [AuditPlanningController::class, 'getRisksByCriticality'])
        ->name('planning.risks-by-criticality');
    Route::post('/planning/missions/{missionId}/schedule', [AuditPlanningController::class, 'scheduleMission'])
        ->name('planning.schedule-mission');
    Route::post('/planning/missions/bulk-schedule', [AuditPlanningController::class, 'bulkScheduleMissions'])
        ->name('planning.bulk-schedule');
    Route::get('/planning/annual-plan/{year}', [AuditPlanningController::class, 'getAnnualPlan'])
        ->name('planning.annual-plan');
    Route::post('/planning/annual-plan', [AuditPlanningController::class, 'createAnnualPlan'])
        ->name('planning.create-annual-plan');
    Route::get('/planning/stats/heatmap', [AuditPlanningController::class, 'getHeatmap'])
        ->name('planning.heatmap');
    Route::get('/planning/export/pdf', [AuditPlanningController::class, 'exportPdf'])
        ->name('planning.export-pdf');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 🔀 UTILITAIRES & STATISTIQUES
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/stats/summary', [AuditPlanningController::class, 'getStatsSummary'])
        ->name('stats.summary');
    Route::get('/stats/by-entity', [AuditPlanningController::class, 'getStatsByEntity'])
        ->name('stats.by-entity');
    Route::get('/stats/by-process', [AuditPlanningController::class, 'getStatsByProcess'])
        ->name('stats.by-process');
    Route::get('/dashboard', [AuditPlanningController::class, 'getDashboard'])
        ->name('dashboard');

    Route::middleware(['auth:web', 'verified'])->group(function () {

  
        Route::get('/process-scoring', [AuditProcessScoringController::class, 'index'])
            ->name('process-scoring.index');

        // GET /audit/process-scoring/stats/entity/{entityId}
        // ✅ IMPORTANT: Cette route DOIT être AVANT la route show!
        // Sinon "stats" sera interprété comme processId
        Route::get('/process-scoring/stats/entity/{entityId}', 
            [AuditProcessScoringController::class, 'getEntityStats'])
            ->name('process-scoring.stats')
            ->where('entityId', '[0-9]+');

        // GET /audit/process-scoring/export/csv
        // ✅ IMPORTANT: Cette route DOIT être AVANT la route show!
        Route::get('/process-scoring/export/csv', 
            [AuditProcessScoringController::class, 'exportCsv'])
            ->name('process-scoring.export-csv');

        // POST /audit/process-scoring/recalculate-all
        // ✅ Recalculer tous les rangs d'une entité
        Route::post('/process-scoring/recalculate-all', 
            [AuditProcessScoringController::class, 'recalculateAll'])
            ->name('process-scoring.recalculate-all');

        // GET /audit/process-scoring/{processId}/{entityId}/history
        // ✅ IMPORTANT: Avant la route show pour éviter les conflits
        Route::get('/process-scoring/{processId}/{entityId}/history', 
            [AuditProcessScoringController::class, 'getHistory'])
            ->name('process-scoring.history')
            ->where(['processId' => '[0-9]+', 'entityId' => '[0-9]+']);

        // GET /audit/process-scoring/{processId}/{entityId}
        // ✅ Voir les détails d'un processus
        Route::get('/process-scoring/{processId}/{entityId}', 
            [AuditProcessScoringController::class, 'show'])
            ->name('process-scoring.show')
            ->where(['processId' => '[0-9]+', 'entityId' => '[0-9]+']);

        // PUT /audit/process-scoring/{processId}/{entityId}
        // ✅ Mettre à jour les scores d'un processus
        Route::put('/process-scoring/{processId}/{entityId}', 
            [AuditProcessScoringController::class, 'updateScore'])
            ->name('process-scoring.update')
            ->where(['processId' => '[0-9]+', 'entityId' => '[0-9]+']);

    });



 
});



Route::middleware(['web', 'auth'])->group(function () {
    
    Route::prefix('audit/factors')->name('audit.factors.')->controller(FactorInputController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'storeFactor')->name('store');
        Route::put('{id}', 'updateFactor')->name('update')->where('id', '[0-9]+');
        Route::delete('{id}', 'destroyFactor')->name('destroy')->where('id', '[0-9]+');
        Route::put('{id}/toggle', 'toggleFactor')->name('toggle')->where('id', '[0-9]+');
        Route::put('reorder', 'reorderFactors')->name('reorder');
        Route::post('{id}/improve', 'improveFactorName')->name('improve-name')->where('id', '[0-9]+');
        Route::get('/export', 'exportFactors')->name('export');
    });
    
    Route::prefix('audit/factor-scales')->name('audit.factor-scales.')->controller(FactorInputController::class)->group(function () {
        Route::post('/', 'storeScale')->name('store');
        Route::put('{id}', 'updateScale')->name('update')->where('id', '[0-9]+');
        Route::delete('{id}', 'destroyScale')->name('destroy')->where('id', '[0-9]+');
    });
});



//Route::post('/audit/risks/by-entities', [AuditProcessScoringController::class, 'getRisksByEntities']);

Route::middleware(['auth:web', 'verified'])->prefix('audit')->group(function () {
    Route::post('/risks/by-entities', [AuditProcessScoringController::class, 'getRisksByEntities']);
    // PAGE PRINCIPALE
    Route::get('/process-scoring', [AuditProcessScoringController::class, 'index'])
        ->name('audit.process-scoring.index');

    // PONDÉRATION
    Route::post('/process-ponderation', [AuditProcessScoringController::class, 'savePonderation'])
        ->name('audit.process-ponderation');

    // NOTATION
    Route::put('/process-scoring/{processId}/{entityId}', [AuditProcessScoringController::class, 'updateScore'])
        ->name('audit.process-scoring.update');

    // MISSIONS
    Route::post('/missions', [AuditProcessScoringController::class, 'storeMission'])
        ->name('audit.missions.store');
    
    Route::get('/missions', [AuditProcessScoringController::class, 'getMissions'])
        ->name('audit.missions.index');
    
    Route::delete('/missions/{id}', [AuditProcessScoringController::class, 'deleteMission'])
        ->name('audit.missions.destroy');

    // IA ROUTES
    Route::post('/ai/suggest-goals', [AuditProcessScoringController::class, 'aiSuggestGoals'])
        ->name('audit.ai.suggest-goals');
    
    Route::post('/ai/suggest-type', [AuditProcessScoringController::class, 'aiSuggestType'])
        ->name('audit.ai.suggest-type');
    
    Route::post('/ai/generate-fields', [AuditProcessScoringController::class, 'aiGenerateFields'])
        ->name('audit.ai.generate-fields');
    
    Route::post('/ai/full-proposal', [AuditProcessScoringController::class, 'aiFullProposal'])
        ->name('audit.ai.full-proposal');
    
    Route::post('/ai/revise-goal', [AuditProcessScoringController::class, 'aiReviseGoal'])
        ->name('audit.ai.revise-goal');
});




// Route pour la programmation des missions
Route::middleware(['auth'])->prefix('programmation-missions')->name('programmation-missions.')->group(function () {
   // Liste et création
    Route::get('/', [MissionProgrammingController::class, 'index'])->name('index');
    Route::get('/create', [MissionProgrammingController::class, 'create'])->name('create');
    Route::post('/', [MissionProgrammingController::class, 'store'])->name('store');
    
    // Détail d'une mission
    Route::get('/{id}', [MissionProgrammingController::class, 'show'])->name('show')
        ->where('id', '[0-9]+');
    
    // Mise à jour du statut
    Route::patch('/{id}/status', [MissionProgrammingController::class, 'updateStatus'])->name('update-status')
        ->where('id', '[0-9]+');
    
    // Gestion des auditeurs
    Route::post('/assign-auditor', [MissionProgrammingController::class, 'assignAuditor'])->name('assign-auditor');
    Route::delete('/remove-auditor/{id}', [MissionProgrammingController::class, 'removeAuditor'])->name('remove-auditor')
        ->where('id', '[0-9]+');
    
    // Gestion du budget
    Route::put('/{id}/budget', [MissionProgrammingController::class, 'updateBudget'])->name('update-budget')
        ->where('id', '[0-9]+');
    
    // Suppression
    Route::delete('/{id}', [MissionProgrammingController::class, 'destroy'])->name('destroy')
        ->where('id', '[0-9]+');
    
    // Export
    Route::get('/export/liste', [MissionProgrammingController::class, 'export'])->name('export');
    Route::get('/export/excel', [MissionProgrammingController::class, 'exportExcel'])
     ->name('export-excel');
    });
Route::get('/programmation-missions/{id}/entities', 
    [MissionProgrammingController::class, 'getMissionEntities'])
    ->name('programmation-missions.entities');
    
   
    Route::get('/audit/programmation-missions/export', [MissionProgrammingController::class, 'exportExcel'])
    ->name('programmation-missions.export');

    use App\Http\Controllers\Audit\MissionParametrageController;

// Paramétrage programmation missions
Route::prefix('programmation-missions/parametrage')->name('programmation-missions.parametrage')->middleware(['auth'])->group(function () {

    // Page principale (4 onglets)
    Route::get('/', [MissionParametrageController::class, 'index']);

    // Phases
    Route::post('/phases',          [MissionParametrageController::class, 'storePhase'])->name('.phases.store');
    Route::put('/phases/{id}',      [MissionParametrageController::class, 'updatePhase'])->name('.phases.update');
    Route::delete('/phases/{id}',   [MissionParametrageController::class, 'destroyPhase'])->name('.phases.destroy');

    // Rôles
    Route::post('/roles',           [MissionParametrageController::class, 'storeRole'])->name('.roles.store');
    Route::put('/roles/{id}',       [MissionParametrageController::class, 'updateRole'])->name('.roles.update');
    Route::delete('/roles/{id}',    [MissionParametrageController::class, 'destroyRole'])->name('.roles.destroy');

    // Types budget
    Route::post('/budget-types',        [MissionParametrageController::class, 'storeBudgetType'])->name('.budget-types.store');
    Route::put('/budget-types/{id}',    [MissionParametrageController::class, 'updateBudgetType'])->name('.budget-types.update');
    Route::delete('/budget-types/{id}', [MissionParametrageController::class, 'destroyBudgetType'])->name('.budget-types.destroy');

    // Catégories budget
    Route::post('/budget-categories',        [MissionParametrageController::class, 'storeBudgetCategory'])->name('.budget-categories.store');
    Route::put('/budget-categories/{id}',    [MissionParametrageController::class, 'updateBudgetCategory'])->name('.budget-categories.update');
    Route::delete('/budget-categories/{id}', [MissionParametrageController::class, 'destroyBudgetCategory'])->name('.budget-categories.destroy');
});

Route::post('/api/ai/suggest-mission-title',
    [\App\Http\Controllers\Audit\MissionAIController::class, 'suggestTitle']
)->name('api.ai.mission.title');



use App\Http\Controllers\Audit\OrdreMissionController;

// Groupe principal
Route::prefix('/ordres-mission')
    ->name('ordre-missions.')
    ->middleware(['auth', 'verified'])
    ->group(function () {

        // ─── CRUD Standard ───────────────────────────────────────────────────
        Route::get('/',                 [OrdreMissionController::class, 'index'])   ->name('index');
        Route::get('/create',           [OrdreMissionController::class, 'create'])  ->name('create');
        Route::post('/',                [OrdreMissionController::class, 'store'])   ->name('store');
        Route::get('/{id}',             [OrdreMissionController::class, 'show'])    ->name('show');
        Route::get('/{id}/edit',        [OrdreMissionController::class, 'edit'])    ->name('edit');
        Route::put('/{id}',             [OrdreMissionController::class, 'update'])  ->name('update');
        Route::delete('/{id}',          [OrdreMissionController::class, 'destroy']) ->name('destroy');

        // ─── PDF ─────────────────────────────────────────────────────────────
        // Générer & télécharger le PDF (toutes entités ou une seule)
        Route::get('/{id}/pdf',                    [OrdreMissionController::class, 'generatePdf'])
            ->name('pdf');
        Route::get('/{id}/pdf/entite/{entityId}',  [OrdreMissionController::class, 'generatePdf'])
            ->name('pdf.entite');

        // ─── ENVOI EMAIL ──────────────────────────────────────────────────────
        // Envoyer à toutes les entités (ou IDs précisés en POST)
        Route::post('/{id}/send-emails', [OrdreMissionController::class, 'sendEmails'])
            ->name('send-emails');

        // ─── AJAX – Charger entités + auditeurs d'une mission ─────────────────
        Route::get('/mission-entites/{missionProgId}', [OrdreMissionController::class, 'getMissionEntites'])
            ->name('mission-entites');
    });

use App\Http\Controllers\Audit\MissionPhaseAffectationController;

Route::middleware(['auth', 'verified'])
   
    ->group(function () {

    // ────────────────────────────────────────────────────────────────────────
    // PAGE PRINCIPALE (Inertia)
    // URL  : /m/audit.core/affectation-phases-aux-mission
    // Name : audit.core.home.programmation-missions.phases  ← exact DB nav
    // Accès direct depuis le menu ou avec ?mission_id=42
    // ────────────────────────────────────────────────────────────────────────
    Route::get(
        'affectation-phases-aux-mission',
        [MissionPhaseAffectationController::class, 'index']
    )->name('programmation-missions.phases');

    // ────────────────────────────────────────────────────────────────────────
    // APIs AJAX utilisées par la vue Vue.js
    // ────────────────────────────────────────────────────────────────────────

    // 1. Charger les phases d'un type de mission
    //    GET /m/audit.core/api/mission-phases/by-type/{typeId}
    Route::get(
        'api/mission-phases/by-type/{typeId}',
        [MissionPhaseAffectationController::class, 'getPhasesByTypeApi']
    )->name('api.mission-phases.by-type')
     ->where('typeId', '[0-9]+');

    // 2. Charger les affectations existantes d'une mission
    //    GET /m/audit.core/api/programmation-missions/{missionId}/phase-assignments
    Route::get(
        'api/programmation-missions/{missionId}/phase-assignments',
        [MissionPhaseAffectationController::class, 'getAssignedPhases']
    )->name('api.mission-phases.assigned')
     ->where('missionId', '[0-9]+');

    // 3. Charger les auditeurs d'une mission
    //    GET /m/audit.core/api/programmation-missions/{missionId}/auditeurs
    Route::get(
        'api/programmation-missions/{missionId}/auditeurs',
        [MissionPhaseAffectationController::class, 'getAuditeursApi']
    )->name('api.programmation-missions.auditeurs')
     ->where('missionId', '[0-9]+');

    // 4. Sauvegarder les affectations
    //    POST /m/audit.core/api/mission-phases/affectation/{missionId}
    Route::post(
        'api/mission-phases/affectation/{missionId}',
        [MissionPhaseAffectationController::class, 'saveAffectation']
    )->name('api.mission-phases.affectation')
     ->where('missionId', '[0-9]+');

    // 5. Toggle is_mandatory sur une phase
    //    PATCH /m/audit.core/api/mission-phases/{id}/toggle-mandatory
    Route::patch(
        'api/mission-phases/{id}/toggle-mandatory',
        [MissionPhaseAffectationController::class, 'toggleMandatory']
    )->name('api.mission-phases.toggle-mandatory')
     ->where('id', '[0-9]+');

});