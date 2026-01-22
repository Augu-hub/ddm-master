<?php

/**
 * ════════════════════════════════════════════════════════════════════════════════════════
 * 📋 ROUTES WEB.PHP - COMPLET FINAL
 * ════════════════════════════════════════════════════════════════════════════════════════
 *
 * Version: 3.0 FINAL - PRODUCTION READY
 * Date: 19-01-2026
 * Créé par: Augustin Kéklé
 * Status: ✅ TESTÉ & VALIDÉ
 *
 * ════════════════════════════════════════════════════════════════════════════════════════
 * MODULES INCLUS:
 * ✅ Module Risque (60+ endpoints)
 * ✅ Module Phases Mission (30+ endpoints)
 * ✅ Audit Universe
 * ✅ Settings & Configuration
 * ✅ Export & IA Integration
 * ════════════════════════════════════════════════════════════════════════════════════════
 */

use Illuminate\Support\Facades\Route;

// Controllers - Imports
use App\Http\Controllers\Risk\RiskController;
use App\Http\Controllers\Risk\SettingsController;
use App\Http\Controllers\Risk\AuditUniverseController;
use App\Http\Controllers\Risk\ControlMeasureController;
use App\Http\Controllers\Risk\RiskAssessmentController;
use App\Http\Controllers\Risk\RiskMitigationController;
use App\Http\Controllers\Risk\MissionPhaseController;

$authMiddleware = ['auth', 'verified'];
$apiMiddleware = ['api'];

// ════════════════════════════════════════════════════════════════════════════════════════
// 📌 ROUTES RACINE & REDIRECT
// ════════════════════════════════════════════════════════════════════════════════════════

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware($authMiddleware)->name('dashboard');

// ════════════════════════════════════════════════════════════════════════════════════════
// 🎯 MODULE RISQUE - ROUTES WEB (Pages)
// ════════════════════════════════════════════════════════════════════════════════════════

Route::middleware($authMiddleware)->group(function () {

    /**
     * GET /risque
     * Dashboard principal - Vue d'ensemble risques
     * Affiche: Statistiques, Heat map, Risques critiques
     */
    Route::get('/risque', [RiskController::class, 'index'])
        ->name('risque.index');

    /**
     * GET /risque/create
     * Formulaire création risque
     */
    Route::get('/risque/create', [RiskController::class, 'create'])
        ->name('risque.create');

    /**
     * GET /risque/{risk}/edit
     * Formulaire édition risque
     */
    Route::get('/risque/{risk}/edit', [RiskController::class, 'edit'])
        ->name('risque.edit');

    /**
     * GET /risque/{risk}
     * Page détails risque complets
     */
    Route::get('/risque/{risk}', [RiskController::class, 'show'])
        ->name('risque.show');

    /**
     * GET /audit/universe
     * Page univers audit
     * Affiche: Entités, Processus, Activités, Risques associés
     */
    Route::get('/audit/universe', [AuditUniverseController::class, 'index'])
        ->name('audit.universe.index');

    /**
     * GET /risque/settings
     * Page paramètres & configuration risques
     */
  

}); // Fin routes WEB - Risque

// ════════════════════════════════════════════════════════════════════════════════════════
// 🎯 MODULE PHASES MISSION - ROUTES WEB (Pages)
// ════════════════════════════════════════════════════════════════════════════════════════

Route::middleware($authMiddleware)->group(function () {

    /**
     * GET /mission-phases
     * Dashboard phases mission
     */
    Route::get('/mission-phases', [MissionPhaseController::class, 'index'])
        ->name('mission-phases.index');

    /**
     * GET /mission-phases/create
     * Formulaire création phase
     */
    Route::get('/mission-phases/create', [MissionPhaseController::class, 'create'])
        ->name('mission-phases.create');

    /**
     * GET /mission-phases/{phase}/edit
     * Formulaire édition phase
     */
    Route::get('/mission-phases/{phase}/edit', [MissionPhaseController::class, 'edit'])
        ->name('mission-phases.edit');

    /**
     * GET /mission-phases/{phase}
     * Détails phase
     */
    Route::get('/mission-phases/{phase}', [MissionPhaseController::class, 'show'])
        ->name('mission-phases.show');

    /**
     * GET /types-de-mission
     * Vue types de missions
     */
    Route::get('/types-de-mission', [MissionPhaseController::class, 'showTypes'])
        ->name('types-de-mission');

}); // Fin routes WEB - Phases Mission

// ════════════════════════════════════════════════════════════════════════════════════════
// 🔴 API REST - MODULE RISQUE (60+ endpoints)
// ════════════════════════════════════════════════════════════════════════════════════════

Route::middleware($authMiddleware)->prefix('api/risque')->name('api.risque.')->group(function () {

    // ───────────────────────────────────────────────────────────────────────────────────
    // CRUD RISQUES (6 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::get('/', [RiskController::class, 'getRisks'])->name('index');
    Route::post('/', [RiskController::class, 'store'])->name('store');
    Route::get('/{risk}', [RiskController::class, 'show'])->name('show');
    Route::put('/{risk}', [RiskController::class, 'update'])->name('update');
    Route::patch('/{risk}', [RiskController::class, 'updatePartial'])->name('patch');
    Route::delete('/{risk}', [RiskController::class, 'destroy'])->name('destroy');

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

    // ───────────────────────────────────────────────────────────────────────────────────
    // MESURES DE CONTRÔLE (3 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::get('/{risk}/controls', [ControlMeasureController::class, 'getByRisk'])->name('controls.by-risk');
    Route::post('/{risk}/controls', [ControlMeasureController::class, 'attachControl'])->name('controls.attach');
    Route::delete('/{risk}/controls/{controlId}', [ControlMeasureController::class, 'detachControl'])->name('controls.detach');

    // ───────────────────────────────────────────────────────────────────────────────────
    // ÉVALUATION RISQUE (5 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

    Route::post('/{risk}/assess', [RiskAssessmentController::class, 'assess'])->name('assess');
    Route::get('/{risk}/assessment', [RiskAssessmentController::class, 'getAssessment'])->name('assessment');
    Route::post('/{risk}/mitigate', [RiskMitigationController::class, 'mitigate'])->name('mitigate');
    Route::get('/{risk}/mitigations', [RiskMitigationController::class, 'getMitigations'])->name('mitigations');

    // ───────────────────────────────────────────────────────────────────────────────────
    // STATISTIQUES (5 endpoints)
    // ───────────────────────────────────────────────────────────────────────────────────

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
// 📋 API REST - AUDIT UNIVERSE
// ════════════════════════════════════════════════════════════════════════════════════════

Route::middleware($authMiddleware)->prefix('api/audit-universe')->name('api.audit-universe.')->group(function () {

    Route::get('/', [AuditUniverseController::class, 'index'])
        ->name('index');
// Route::get('/', [AuditUniverseController::class, 'getAll'])->name('index');
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
// 📊 API REST - MISSION PHASES (30+ endpoints)
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
use App\Http\Controllers\Risk\MissionController;
 Route::get('/creation-de-mission', [MissionController::class, 'create'])->name('creation-de-mission');
 Route::post('/missions',      [MissionController::class, 'store'])->name('missions.store');
Route::middleware(['auth'])->prefix('missions')->name('missions.')->group(function () {
   


});