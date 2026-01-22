<?php

/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ROUTES FINALES - MODULE RISQUE
 * ════════════════════════════════════════════════════════════════════════════════
 *
 * Fichier: routes/web.php
 *
 * À AJOUTER dans routes/web.php (après la partie auth existante)
 *
 * ❌ SANS middlewares permission
 * ✅ Chargement depuis la base
 * ✅ CRUD complet
 * ✅ Export CSV
 *
 * ════════════════════════════════════════════════════════════════════════════════
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Risk\RiskController;

// ════════════════════════════════════════════════════════════════════════════════
// ROUTES AUTHENTIFIÉES - MODULE RISQUE
// ════════════════════════════════════════════════════════════════════════════════

Route::middleware([ 'verified'])->group(function () {

    // ════════════════════════════════════════════════════════════════════════════
    // 🎯 DASHBOARD PRINCIPAL
    // ════════════════════════════════════════════════════════════════════════════

    Route::get('/risque', [RiskController::class, 'index'])->name('risque.index');

    // ════════════════════════════════════════════════════════════════════════════
    // 📊 API REST - DONNÉES & CRUD
    // ════════════════════════════════════════════════════════════════════════════

    Route::prefix('api/risque')->group(function () {

        // ─────────────────────────────────────────────────────────────────────
        // CRUD Risques
        // ─────────────────────────────────────────────────────────────────────
        
        // GET /api/risque?entity_id=5&process_id=3&search=RGPD
        Route::get('/', [RiskController::class, 'getRisks']);
        
        // POST /api/risque/
        Route::post('/', [RiskController::class, 'store']);
        
        // PUT /api/risque/{risk}
        Route::put('/{risk}', [RiskController::class, 'update']);
        
        // DELETE /api/risque/{risk}
        Route::delete('/{risk}', [RiskController::class, 'destroy']);
        
        // GET /api/risque/{risk}
        Route::get('/{risk}', [RiskController::class, 'show']);

        // ─────────────────────────────────────────────────────────────────────
        // Types
        // ─────────────────────────────────────────────────────────────────────
        
        Route::get('/types/all', [RiskController::class, 'getTypes']);
        Route::post('/types/create', [RiskController::class, 'storeType']);

        // ─────────────────────────────────────────────────────────────────────
        // Fréquences
        // ─────────────────────────────────────────────────────────────────────
        
        Route::get('/frequencies/all', [RiskController::class, 'getFrequencies']);
        Route::post('/frequencies/create', [RiskController::class, 'storeFrequency']);

        // ─────────────────────────────────────────────────────────────────────
        // Impacts
        // ─────────────────────────────────────────────────────────────────────
        
        Route::get('/impacts/all', [RiskController::class, 'getImpacts']);
        Route::post('/impacts/create', [RiskController::class, 'storeImpact']);

        // ─────────────────────────────────────────────────────────────────────
        // Export
        // ─────────────────────────────────────────────────────────────────────
        
        Route::get('/export/csv', [RiskController::class, 'exportCsv']);
        Route::get('/export/excel', [RiskController::class, 'exportExcel']);
        Route::get('/export/pdf', [RiskController::class, 'exportPdf']);

    }); // End: api/risque

}); 


use App\Http\Controllers\Risk\SettingsController;

Route::middleware('api')->prefix('settings')->group(function () {

    Route::get('/', [SettingsController::class, 'index']);

    Route::post('risk-types', [SettingsController::class, 'storeRiskType']);
    Route::put('risk-types/{id}', [SettingsController::class, 'updateRiskType']);
    Route::delete('risk-types/{id}', [SettingsController::class, 'deleteRiskType']);

    // 📊 FRÉQUENCES
    Route::post('frequencies', [SettingsController::class, 'storeFrequency']);
    Route::put('frequencies/{id}', [SettingsController::class, 'updateFrequency']);
    Route::delete('frequencies/{id}', [SettingsController::class, 'deleteFrequency']);

    // ⚡ IMPACTS
    Route::post('impacts', [SettingsController::class, 'storeImpact']);
    Route::put('impacts/{id}', [SettingsController::class, 'updateImpact']);
    Route::delete('impacts/{id}', [SettingsController::class, 'deleteImpact']);

    // 🏛️ ENTITÉS
    Route::post('entities', [SettingsController::class, 'storeEntity']);
    Route::put('entities/{id}', [SettingsController::class, 'updateEntity']);
    Route::delete('entities/{id}', [SettingsController::class, 'deleteEntity']);

    // ⚙️ PROCESSUS
    Route::post('processes', [SettingsController::class, 'storeProcess']);
    Route::put('processes/{id}', [SettingsController::class, 'updateProcess']);
    Route::delete('processes/{id}', [SettingsController::class, 'deleteProcess']);

    // 📌 ACTIVITÉS
    Route::post('activities', [SettingsController::class, 'storeActivity']);
    Route::put('activities/{id}', [SettingsController::class, 'updateActivity']);
    Route::delete('activities/{id}', [SettingsController::class, 'deleteActivity']);

    // 📊 STATISTIQUES
    Route::get('stats', [SettingsController::class, 'getStats']);
});


use App\Http\Controllers\Risk\AuditUniverseController;

// ════════════════════════════════════════════════════════════════════════════════
// 📋 AUDIT UNIVERSE - ROUTES WEB
// ════════════════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'verified'])->group(function () {

    /**
     * GET /audit/universe
     * Page principale - Chargement entités
     */
    Route::get('/audit/universe', [AuditUniverseController::class, 'index'])
        ->name('audit.universe.index');

});

Route::prefix('m/risk.core')->name('api.risk.')->group(function () {

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 🔴 RISK MANAGEMENT API
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    /**
     * GET /api/m/risk.core
     * List tous les risques
     */
    Route::get('/', [RiskController::class, 'index'])
        ->name('index');
    
    /**
     * POST /api/m/risk.core
     * Crée un nouveau risque
     */
    Route::post('/', [RiskController::class, 'store'])
        ->name('store');
    
    /**
     * GET /api/m/risk.core/{id}
     * Affiche un risque
     */
    Route::get('{risk}', [RiskController::class, 'show'])
        ->name('show');
    
    /**
     * PUT /api/m/risk.core/{id}
     * Update un risque complet
     */
    Route::put('{risk}', [RiskController::class, 'update'])
        ->name('update');
    
    /**
     * DELETE /api/m/risk.core/{id}
     * Supprime un risque
     */
    Route::delete('{risk}', [RiskController::class, 'destroy'])
        ->name('destroy');
    
    /**
     * POST /api/m/risk.core/suggest-ai
     * Génère suggestions IA pour risques
     */
    Route::post('/suggest-ai', [RiskController::class, 'suggestAI'])
        ->name('suggest-ai');
    
    /**
     * POST /api/m/risk.core/suggest-control
     * Génère procédure contrôle avec IA
     */
    Route::post('/suggest-control', [RiskController::class, 'suggestControl'])
        ->name('suggest-control');

});
use App\Http\Controllers\Risk\MissionPhaseController;

    Route::get('/types-de-mission', [MissionPhaseController::class, 'index'])
        ->name('types-de-mission');
Route::middleware(['auth'])->group(function () {
    Route::apiResource('mission-phases', MissionPhaseController::class);
   });