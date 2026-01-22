<?php

/**
 * ════════════════════════════════════════════════════════════════════════════════════════
 * 📋 ROUTES API - APP/HTTP/CONTROLLERS/RISK/RISKCONTROLLER
 * ════════════════════════════════════════════════════════════════════════════════════════
 *
 * Fichier: routes/api.php
 * 
 * ✅ NAMED ROUTES pour matcher avec le Vue
 * ✅ Path: /api/m/risk.core/* (MODULE PATTERN)
 * ✅ Suggestions IA, CRUD, Métadonnées, Export
 *
 * ════════════════════════════════════════════════════════════════════════════════════════
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Risk\RiskController;

/**
 * ════════════════════════════════════════════════════════════════════════════════════════
 * 🔐 AUTHENTIFICATION
 * ════════════════════════════════════════════════════════════════════════════════════════
 */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * ════════════════════════════════════════════════════════════════════════════════════════
 * 📋 ROUTES MODULES - RISQUES AVEC SERVICE IA
 * ════════════════════════════════════════════════════════════════════════════════════════
 * 
 * Prefix: /api/m/risk.core
 * Match le pattern MODULE du Vue
 */

Route::middleware('api')->prefix('m/risk.core')->group(function () {

    // ════════════════════════════════════════════════════════════════════════════════════
    // 🤖 SUGGESTIONS IA - CLAUDE API
    // ════════════════════════════════════════════════════════════════════════════════════
    
    /**
     * POST /api/m/risk.core/suggest-ai
     * Génère 4 propositions de noms de risques via Claude IA
     * 
     * Body: {
     *   "process_name": "Paie",
     *   "activity_name": "Saisie données",
     *   "risk_type_name": "Erreur"
     * }
     * 
     * Response: {
     *   "success": true,
     *   "suggestions": [
     *     {"id": 1, "code": "RC-001", "label": "...", "control_procedure": ""},
     *     ...
     *   ],
     *   "mode": "ai" ou "fallback"
     * }
     */
    Route::post('suggest-ai', [RiskController::class, 'suggestAI'])
        ->name('risk.suggest-ai');

    /**
     * POST /api/m/risk.core/suggest-control
     * Génère procédure de contrôle pour un risque
     * 
     * Body: {
     *   "risk_label": "Erreurs de saisie",
     *   "activity_name": "Saisie données",
     *   "process_name": "Paie"
     * }
     * 
     * Response: {
     *   "success": true,
     *   "control_procedure": "Double vérification...",
     *   "mode": "ai" ou "fallback"
     * }
     */
    Route::post('suggest-control', [RiskController::class, 'suggestControl'])
        ->name('risk.suggest-control');

    // ════════════════════════════════════════════════════════════════════════════════════
    // 📋 CRUD RISQUES
    // ════════════════════════════════════════════════════════════════════════════════════
    
    /**
     * GET /api/m/risk.core
     * Récupère tous les risques avec métadonnées
     * 
     * Query params (optionnel):
     * - ?entity_id=5
     * - ?process_id=1
     * - ?activity_id=2
     * - ?search=keyword
     * 
     * Response: {
     *   "success": true,
     *   "risks": [...]
     * }
     */
    Route::get('/', [RiskController::class, 'index'])
        ->name('risk.index');

    /**
     * POST /api/m/risk.core
     * Crée un nouveau risque
     * 
     * Body: {
     *   "code": "",  // Auto si vide (RC-001, RC-002)
     *   "label": "Erreurs de saisie",
     *   "description": "...",
     *   "risk_type_id": 1,
     *   "frequency_level_id": 2,
     *   "frequency_net": 3,
     *   "impact_level_id": 3,
     *   "impact_net": 4,
     *   "entity_id": 5,
     *   "process_id": 1,
     *   "activity_id": 2,
     *   "owner": "John",
     *   "control_procedure": "Double vérification",
     *   "status": "identified"
     * }
     * 
     * Response: {
     *   "success": true,
     *   "message": "Risque créé avec succès",
     *   "risk": {...}
     * } (201 Created)
     */
    Route::post('/', [RiskController::class, 'store'])
        ->name('risk.store');

    /**
     * GET /api/m/risk.core/{id}
     * Récupère les détails d'un risque
     * 
     * Response: {
     *   "success": true,
     *   "risk": {...}
     * }
     */
    Route::get('{risk}', [RiskController::class, 'show'])
        ->name('risk.show');

    /**
     * PUT /api/m/risk.core/{id}
     * Modifie un risque
     * 
     * Body: {...} (champs à modifier)
     * 
     * Response: {
     *   "success": true,
     *   "message": "Risque modifié avec succès",
     *   "risk": {...}
     * }
     */
    Route::put('{risk}', [RiskController::class, 'update'])
        ->name('risk.update');

    /**
     * DELETE /api/m/risk.core/{id}
     * Supprime un risque
     * 
     * Response: {
     *   "success": true,
     *   "message": "Risque supprimé avec succès"
     * }
     */
    Route::delete('{risk}', [RiskController::class, 'destroy'])
        ->name('risk.destroy');

    // ════════════════════════════════════════════════════════════════════════════════════
    // 📚 MÉTADONNÉES
    // ════════════════════════════════════════════════════════════════════════════════════
    
    /**
     * GET /api/m/risk.core/metadata/types
     * Récupère tous les types de risques
     * 
     * Response: {
     *   "success": true,
     *   "types": [...]
     * }
     */
    Route::get('metadata/types', [RiskController::class, 'getTypes'])
        ->name('risk.types');

    /**
     * GET /api/m/risk.core/metadata/frequencies
     * Récupère tous les niveaux de fréquence
     * 
     * Response: {
     *   "success": true,
     *   "frequencies": [...]
     * }
     */
    Route::get('metadata/frequencies', [RiskController::class, 'getFrequencies'])
        ->name('risk.frequencies');

    /**
     * GET /api/m/risk.core/metadata/impacts
     * Récupère tous les niveaux d'impact
     * 
     * Response: {
     *   "success": true,
     *   "impacts": [...]
     * }
     */
    Route::get('metadata/impacts', [RiskController::class, 'getImpacts'])
        ->name('risk.impacts');

    // ════════════════════════════════════════════════════════════════════════════════════
    // 📤 EXPORT
    // ════════════════════════════════════════════════════════════════════════════════════
    
    /**
     * GET /api/m/risk.core/export/csv
     * Exporte les risques en CSV
     * 
     * Response: File (CSV)
     */
    Route::get('export/csv', [RiskController::class, 'exportCsv'])
        ->name('risk.export-csv');

});

use App\Http\Controllers\Risk\SettingsController;

Route::middleware(['auth', 'api'])->prefix('settings')->group(function () {

    // GET - Afficher le dashboard
    Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/stats', [SettingsController::class, 'getStats'])->name('settings.stats');

    // 📋 TYPES DE RISQUES
    Route::get('/risk-types', [SettingsController::class, 'getRiskTypes'])->name('settings.risk-types.get');
    Route::post('/risk-types', [SettingsController::class, 'storeRiskType'])->name('settings.risk-types.store');
    Route::put('/risk-types/{id}', [SettingsController::class, 'updateRiskType'])->name('settings.risk-types.update');
    Route::delete('/risk-types/{id}', [SettingsController::class, 'deleteRiskType'])->name('settings.risk-types.delete');

    // 📊 FRÉQUENCES
    Route::get('/frequencies', [SettingsController::class, 'getFrequencies'])->name('settings.frequencies.get');
    Route::post('/frequencies', [SettingsController::class, 'storeFrequency'])->name('settings.frequencies.store');
    Route::put('/frequencies/{id}', [SettingsController::class, 'updateFrequency'])->name('settings.frequencies.update');
    Route::delete('/frequencies/{id}', [SettingsController::class, 'deleteFrequency'])->name('settings.frequencies.delete');

    // ⚡ IMPACTS
    Route::get('/impacts', [SettingsController::class, 'getImpacts'])->name('settings.impacts.get');
    Route::post('/impacts', [SettingsController::class, 'storeImpact'])->name('settings.impacts.store');
    Route::put('/impacts/{id}', [SettingsController::class, 'updateImpact'])->name('settings.impacts.update');
    Route::delete('/impacts/{id}', [SettingsController::class, 'deleteImpact'])->name('settings.impacts.delete');

    // 📊 MATRICE
    Route::get('/matrix', [SettingsController::class, 'getMatrix'])->name('settings.matrix.get');
    Route::post('/matrix', [SettingsController::class, 'storeMatrix'])->name('settings.matrix.store');
    Route::delete('/matrix/{id}', [SettingsController::class, 'deleteMatrix'])->name('settings.matrix.delete');

    // 🏛️ ENTITÉS
    Route::get('/entities', [SettingsController::class, 'getEntities'])->name('settings.entities.get');
    Route::post('/entities', [SettingsController::class, 'storeEntity'])->name('settings.entities.store');
    Route::put('/entities/{id}', [SettingsController::class, 'updateEntity'])->name('settings.entities.update');
    Route::delete('/entities/{id}', [SettingsController::class, 'deleteEntity'])->name('settings.entities.delete');

    // ⚙️ PROCESSUS
    Route::get('/processes', [SettingsController::class, 'getProcesses'])->name('settings.processes.get');
    Route::post('/processes', [SettingsController::class, 'storeProcess'])->name('settings.processes.store');
    Route::put('/processes/{id}', [SettingsController::class, 'updateProcess'])->name('settings.processes.update');
    Route::delete('/processes/{id}', [SettingsController::class, 'deleteProcess'])->name('settings.processes.delete');

    // 📌 ACTIVITÉS
    Route::get('/activities', [SettingsController::class, 'getActivities'])->name('settings.activities.get');
    Route::post('/activities', [SettingsController::class, 'storeActivity'])->name('settings.activities.store');
    Route::put('/activities/{id}', [SettingsController::class, 'updateActivity'])->name('settings.activities.update');
    Route::delete('/activities/{id}', [SettingsController::class, 'deleteActivity'])->name('settings.activities.delete');

    // 📚 EXERCICES (si vous en avez besoin)
    Route::get('/exercises', [SettingsController::class, 'getExercises'])->name('settings.exercises.get');
    Route::post('/exercises', [SettingsController::class, 'storeExercise'])->name('settings.exercises.store');
    Route::put('/exercises/{id}', [SettingsController::class, 'updateExercise'])->name('settings.exercises.update');
    Route::delete('/exercises/{id}', [SettingsController::class, 'deleteExercise'])->name('settings.exercises.delete');

    // 🎯 SESSIONS (si vous en avez besoin)
    Route::get('/sessions', [SettingsController::class, 'getSessions'])->name('settings.sessions.get');
    Route::post('/sessions', [SettingsController::class, 'storeSession'])->name('settings.sessions.store');
    Route::put('/sessions/{id}', [SettingsController::class, 'updateSession'])->name('settings.sessions.update');
    Route::delete('/sessions/{id}', [SettingsController::class, 'deleteSession'])->name('settings.sessions.delete');
});
use App\Http\Controllers\Risk\AuditUniverseController;

// ════════════════════════════════════════════════════════════════════════════════════
// 📋 AUDIT UNIVERSE API ROUTES
// ════════════════════════════════════════════════════════════════════════════════════

Route::prefix('audit/universe')->group(function () {

    /**
     * POST /api/audit/universe/set-session
     * Définir la session entité + année
     */
    Route::post('/set-session', [AuditUniverseController::class, 'setSession']);

    /**
     * POST /api/audit/universe/load-risks
     * Charger les risques pour entité + année sélectionnées
     */
    Route::post('/load-risks', [AuditUniverseController::class, 'loadRisks']);

    /**
     * PUT /api/audit/universe/update-risk/{id}
     * Mettre à jour un champ du risque
     */
    Route::put('/update-risk/{id}', [AuditUniverseController::class, 'updateRiskField']);

    /**
     * POST /api/audit/universe/create-risk
     * Créer un nouveau risque
     */
    Route::post('/create-risk', [AuditUniverseController::class, 'createRisk']);

});