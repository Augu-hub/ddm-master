<?php

use App\Http\Controllers\FrequencyCriterionController;
use App\Http\Controllers\FrequencyLevelController;
use App\Http\Controllers\ImpactCriterionController;
use App\Http\Controllers\ImpactLevelController;
use App\Http\Controllers\MistralFrequencyCriteriaController;
use App\Http\Controllers\MistralFrequencyController;
use App\Http\Controllers\MistralImpactController;
use App\Http\Controllers\MistralImpactCriteriaController;
use App\Http\Controllers\Risk\MistralNomenclatureController;
use App\Http\Controllers\Risk\NomenclatureController;
use App\Http\Controllers\Risk\RiskActionPlanController;
use App\Http\Controllers\Risk\RiskEvaluationController;
use App\Http\Controllers\Risk\RiskEvaluationSessionController;
use App\Http\Controllers\Risk\RiskReportController;
use App\Http\Controllers\Risk\RiskMatrixController;
use App\Http\Controllers\Risk\SessionController;
use App\Http\Controllers\Risk\RiskMatrixConfigController;
use App\Http\Controllers\RiskAnalysesController;
use App\Http\Controllers\RiskIncidentController;
use App\Http\Controllers\RiskIncidentLibraryController;
use App\Http\Controllers\RiskLibraryController;
use App\Http\Controllers\RiskRegisterController;
use App\Http\Controllers\MistralRiskController;
use Illuminate\Support\Facades\Route;

// ── Accueil ───────────────────────────────────────────────────────────────
Route::view('/', 'modules.risk.core.home')->name('home');

// ── NOMENCLATURE & APPÉTANCES ─────────────────────────────────────────────
Route::prefix('nomenclature')->name('nomenclature.')->group(function () {
    Route::get('/',    [NomenclatureController::class, 'index'])->name('index');
    Route::get('/tree',[NomenclatureController::class, 'tree']) ->name('tree');
    Route::post('/',       [NomenclatureController::class, 'store'])  ->name('store');
    Route::put('/{id}',    [NomenclatureController::class, 'update']) ->name('update') ->whereNumber('id');
    Route::delete('/{id}', [NomenclatureController::class, 'destroy'])->name('destroy')->whereNumber('id');
    Route::put('/{id}/appetite', [NomenclatureController::class, 'assignAppetite'])
        ->name('assign-appetite')->whereNumber('id');
    Route::post('/mistral/suggest-appetite',       [MistralNomenclatureController::class, 'suggestAppetite'])     ->name('mistral.suggest-appetite');
    Route::post('/mistral/suggest-appetite-level', [MistralNomenclatureController::class, 'suggestAppetiteLevel'])->name('mistral.suggest-appetite-level');
    Route::post('/mistral/suggest',                [MistralNomenclatureController::class, 'suggest'])             ->name('mistral.suggest');
    Route::prefix('appetites')->name('appetites.')->group(function () {
        Route::post('/',       [NomenclatureController::class, 'storeAppetite'])  ->name('store');
        Route::put('/{id}',    [NomenclatureController::class, 'updateAppetite']) ->name('update') ->whereNumber('id');
        Route::delete('/{id}', [NomenclatureController::class, 'destroyAppetite'])->name('destroy')->whereNumber('id');
    });
});

// ── INCIDENTS ACTIFS ──────────────────────────────────────────────────────
Route::prefix('incidents')->name('incidents.')->group(function () {
    Route::get('/',                 [RiskIncidentController::class, 'index'])               ->name('index');
    Route::post('/',                [RiskIncidentController::class, 'store'])               ->name('store');
    Route::put('/{id}',             [RiskIncidentController::class, 'update'])              ->name('update')         ->whereNumber('id');
    Route::delete('/{id}',          [RiskIncidentController::class, 'destroy'])             ->name('destroy')        ->whereNumber('id');
    Route::post('/{id}/to-library', [RiskIncidentController::class, 'moveToLibrary'])       ->name('move-to-library')->whereNumber('id');
    Route::post('/reporter-link',   [RiskIncidentController::class, 'generateReporterLink'])->name('reporter-link');
});

// ── LIEN PUBLIC DE DÉNONCIATION ───────────────────────────────────────────
Route::get( '/report/{token}', [RiskIncidentController::class, 'reporterPage'])      ->name('incident.reporter.page');
Route::post('/report/{token}', [RiskIncidentController::class, 'storePublicReport']) ->name('incident.reporter.store');

// ── BIBLIOTHÈQUE INCIDENTS ────────────────────────────────────────────────
Route::prefix('incident-library')->name('incident-library.')->group(function () {
    Route::get('/',                   [RiskIncidentLibraryController::class, 'index'])        ->name('index');
    Route::post('/{id}/reactivate',   [RiskIncidentLibraryController::class, 'reactivate'])   ->name('reactivate')    ->whereNumber('id');
    Route::post('/{id}/convert-risk', [RiskIncidentLibraryController::class, 'convertToRisk'])->name('convert-to-risk')->whereNumber('id');
});

// ── REGISTRE DES RISQUES ──────────────────────────────────────────────────
Route::prefix('risks')->name('risks.')->group(function () {
    Route::post('/mistral/suggest',          [MistralRiskController::class,  'suggest'])         ->name('mistral.suggest');
    Route::get('/entity/{entityId}/tree',    [RiskRegisterController::class, 'entityTree'])       ->name('entity-tree')       ->whereNumber('entityId');
    Route::get('/library-items',             [RiskRegisterController::class, 'libraryItems'])     ->name('library-items');
    Route::get('/',                          [RiskRegisterController::class, 'index'])            ->name('index');
    Route::get('/create',                    [RiskRegisterController::class, 'create'])           ->name('create');
    Route::post('/',                         [RiskRegisterController::class, 'store'])            ->name('store');
    Route::get('/{id}/edit',                 [RiskRegisterController::class, 'edit'])             ->name('edit')    ->whereNumber('id');
    Route::put('/{id}',                      [RiskRegisterController::class, 'update'])           ->name('update')  ->whereNumber('id');
    Route::delete('/{id}',                   [RiskRegisterController::class, 'destroy'])          ->name('destroy') ->whereNumber('id');
    Route::post('/{id}/archive',             [RiskRegisterController::class, 'archive'])          ->name('archive')             ->whereNumber('id');
    Route::post('/{id}/activate',            [RiskRegisterController::class, 'activate'])         ->name('activate')            ->whereNumber('id');
    Route::post('/{id}/move-to-library',     [RiskRegisterController::class, 'moveToLibrary'])    ->name('move-to-library')     ->whereNumber('id');
    Route::post('/{id}/remove-from-library', [RiskRegisterController::class, 'removeFromLibrary'])->name('remove-from-library') ->whereNumber('id');
    Route::post('/factors-for-activities',   [RiskRegisterController::class, 'factorsForActivities'])->name('factors-for-activities');
    Route::post('/factors',                  [RiskRegisterController::class, 'storeFactor'])           ->name('factors.store');
});

// ── BIBLIOTHÈQUE DES RISQUES ──────────────────────────────────────────────
Route::prefix('risk-library')->name('risk-library.')->group(function () {
    Route::get('/',                          [RiskLibraryController::class, 'index'])            ->name('index');
    Route::post('/{id}/remove-from-library', [RiskLibraryController::class, 'removeFromLibrary'])->name('remove-from-library')->whereNumber('id');
});

// ── ANALYSES DES RISQUES ──────────────────────────────────────────────────
Route::prefix('risks-analyses')->name('risks-analyses.')->group(function () {
    Route::get('/',                 [RiskAnalysesController::class, 'index'])          ->name('index');
    Route::post('/mistral-suggest', [RiskAnalysesController::class, 'mistralSuggest']) ->name('mistral-suggest');
    Route::match(['PUT','POST'], '/{id}', [RiskAnalysesController::class, 'update'])   ->name('update')->whereNumber('id');
    Route::post('/{id}/remove-from-library', [RiskAnalysesController::class, 'removeFromLibrary'])->name('remove-from-library')->whereNumber('id');
});

// ═══════════════════════════════════════════════════════════════════════════
// ÉVALUATION DES RISQUES (groupe unique)
// ═══════════════════════════════════════════════════════════════════════════
Route::prefix('evaluation')->name('evaluation.')->group(function () {

    // Vues (toutes rendent la même page EvaluationGlobale.vue avec initialStep)
    Route::get('/inherente',    [RiskEvaluationController::class, 'inherente'])  ->name('inherente');
    Route::get('/controle',     [RiskEvaluationController::class, 'controle'])   ->name('controle');
    Route::get('/residuelle',   [RiskEvaluationController::class, 'residuelle']) ->name('residuelle');
    Route::get('/cible',        [RiskEvaluationController::class, 'cible'])      ->name('cible');
    Route::get('/cartographie', [RiskEvaluationController::class, 'cartographie'])->name('cartographie');

    // Stores JSON (fetch depuis Vue) — sauvegarde étape par étape
    Route::post('/inherente/store',  [RiskEvaluationController::class, 'storeInherente']) ->name('inherente.store');
    Route::post('/controle/store',   [RiskEvaluationController::class, 'storeControle'])  ->name('controle.store');
    Route::post('/residuelle/store', [RiskEvaluationController::class, 'storeResiduelle'])->name('residuelle.store');
    Route::post('/cible/store',      [RiskEvaluationController::class, 'storeCible'])     ->name('cible.store');
    Route::post('/decision/store',   [RiskEvaluationController::class, 'storeDecision'])  ->name('decision.store');

    // ── ACTIONS SUR RISQUE ─────────────────────────────────────────────────────
    Route::post('/risk-action', [RiskEvaluationController::class, 'addActionToRisk'])->name('risk-action.store');
    Route::get('/risk/{riskId}/actions', [RiskEvaluationController::class, 'getRiskActions'])->name('risk-actions.index');
});

// ═══════════════════════════════════════════════════════════════════════════
// SESSIONS D'ÉVALUATION — actualisation & comparaison d'évolution
// ═══════════════════════════════════════════════════════════════════════════
Route::prefix('eval-sessions')->name('eval-sessions.')->group(function () {
    Route::get('/',                 [RiskEvaluationSessionController::class, 'index'])      ->name('index');
    Route::post('/',                [RiskEvaluationSessionController::class, 'store'])      ->name('store');
    Route::get('/compare',          [RiskEvaluationSessionController::class, 'compare'])    ->name('compare');
    Route::get('/compare/data',     [RiskEvaluationSessionController::class, 'compareData'])->name('compare.data');
    Route::get('/{id}/report',      [RiskEvaluationSessionController::class, 'report'])     ->name('report')     ->whereNumber('id');
    Route::post('/{id}/report',     [RiskEvaluationSessionController::class, 'saveReport']) ->name('report.save')->whereNumber('id');
    Route::post('/{id}/actualiser', [RiskEvaluationSessionController::class, 'actualiser']) ->name('actualiser')->whereNumber('id');
    Route::post('/{id}/snapshot',   [RiskEvaluationSessionController::class, 'snapshot'])   ->name('snapshot')  ->whereNumber('id');
    Route::post('/{id}/activate',   [RiskEvaluationSessionController::class, 'activate'])   ->name('activate')  ->whereNumber('id');
    Route::post('/{id}/close',      [RiskEvaluationSessionController::class, 'close'])      ->name('close')     ->whereNumber('id');
    Route::delete('/{id}',          [RiskEvaluationSessionController::class, 'destroy'])    ->name('destroy')   ->whereNumber('id');
});

// ═══════════════════════════════════════════════════════════════════════════
// RAPPORTS & TABLEAU DE BORD (hub)
// ═══════════════════════════════════════════════════════════════════════════
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/',                    [RiskReportController::class, 'index'])         ->name('index');
    Route::get('/fiche/{riskId}',      [RiskReportController::class, 'ficheRisque'])   ->name('fiche')->whereNumber('riskId');
    Route::get('/plan-synthetique',    [RiskReportController::class, 'planSynthetique'])->name('plan-synthetique');
    Route::get('/gantt',               [RiskReportController::class, 'gantt'])          ->name('gantt');
    Route::get('/plan-recommandation/{riskId}', [RiskReportController::class, 'planParRecommandation'])->name('plan-recommandation')->whereNumber('riskId');
});

// ═══════════════════════════════════════════════════════════════════════════
// RECOMMANDATIONS (1 par risque, contient les plans d'action)
// ═══════════════════════════════════════════════════════════════════════════
// NOUVEAU — route top-level (comme /risks, /incidents…) pour matcher l'appel
// fetch du front : POST /m/risk.core/recommendation
Route::post('/recommendation', [RiskActionPlanController::class, 'saveRecommendation'])
    ->name('recommendation.store');

// ═══════════════════════════════════════════════════════════════════════════
// PLAN D'ACTION ET SUIVI
// ═══════════════════════════════════════════════════════════════════════════
Route::prefix('action-plan')->name('action-plan.')->group(function () {
    // Vue principale
    Route::get('/', [RiskActionPlanController::class, 'index'])->name('index');
    
    // Tableau de bord
    Route::get('/dashboard', [RiskActionPlanController::class, 'dashboard'])->name('dashboard');

    // Suivi opérationnel (ActionTracking.vue)
    Route::get('/tracking', [RiskActionPlanController::class, 'tracking'])->name('tracking');

    // CRUD Plan d'action
    Route::post('/', [RiskActionPlanController::class, 'store'])->name('store');
    Route::put('/{id}', [RiskActionPlanController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}', [RiskActionPlanController::class, 'destroy'])->name('destroy')->whereNumber('id');

    // Tâches / suivi d'un plan (c'est ce niveau qui porte la progression)
    Route::get('/{planId}/tasks', [RiskActionPlanController::class, 'getTasks'])->name('tasks')->whereNumber('planId');
    Route::post('/task', [RiskActionPlanController::class, 'storeTask'])->name('task.store');
    Route::put('/task/{id}', [RiskActionPlanController::class, 'updateTask'])->name('task.update')->whereNumber('id');
    Route::delete('/task/{id}', [RiskActionPlanController::class, 'deleteTask'])->name('task.destroy')->whereNumber('id');

    // Commentaires
    Route::get('/{planId}/comments', [RiskActionPlanController::class, 'getComments'])->name('comments')->whereNumber('planId');
    Route::post('/comment', [RiskActionPlanController::class, 'addComment'])->name('comment.store');

    // Historique
    Route::get('/{planId}/history', [RiskActionPlanController::class, 'getHistory'])->name('history')->whereNumber('planId');
});

// ═══════════════════════════════════════════════════════════════════════════
// CONFIGURATION MATRICE
// ═══════════════════════════════════════════════════════════════════════════
Route::prefix('matrix-config')->name('matrix-config.')->group(function () {
    Route::put('/impact-levels/{id}',    [RiskMatrixConfigController::class, 'updateImpact'])   ->name('impact.update')        ->whereNumber('id');
    Route::put('/frequency-levels/{id}', [RiskMatrixConfigController::class, 'updateFrequency'])->name('frequency.update')     ->whereNumber('id');
    Route::put('/zones/{id}',            [RiskMatrixConfigController::class, 'updateZone'])     ->name('zones.update')         ->whereNumber('id');
    Route::put('/mastery-levels/{id}',   [RiskMatrixConfigController::class, 'updateMastery'])  ->name('mastery-levels.update')->whereNumber('id');

    Route::get('/',                      [RiskMatrixConfigController::class, 'index'])       ->name('index');
    Route::post('/',                     [RiskMatrixConfigController::class, 'store'])       ->name('store');
    Route::put('/{id}',                  [RiskMatrixConfigController::class, 'update'])      ->name('update')       ->whereNumber('id');
    Route::delete('/{id}',               [RiskMatrixConfigController::class, 'destroy'])     ->name('destroy')      ->whereNumber('id');
    Route::post('/{id}/activate',        [RiskMatrixConfigController::class, 'activate'])    ->name('activate')     ->whereNumber('id');
    Route::post('/{id}/reset-zones',     [RiskMatrixConfigController::class, 'resetZones'])  ->name('reset-zones')  ->whereNumber('id');
    Route::post('/{id}/reset-mastery',   [RiskMatrixConfigController::class, 'resetMastery'])->name('reset-mastery')->whereNumber('id');
    Route::post('/{configId}/cell-zone', [RiskMatrixConfigController::class, 'assignCellZone'])->name('cell-zone.assign')->whereNumber('configId');
});

// ── MATRICE HEATMAP ───────────────────────────────────────────────────────
Route::prefix('matrix')->name('matrix.')->group(function () {
    Route::get('/',              [RiskMatrixController::class, 'index'])       ->name('index');
    Route::post('/risks',        [RiskMatrixController::class, 'storeRisk'])   ->name('risks.store');
    Route::put('/risks/{id}',    [RiskMatrixController::class, 'updateRisk'])  ->name('risks.update') ->whereNumber('id');
    Route::delete('/risks/{id}', [RiskMatrixController::class, 'destroyRisk'])->name('risks.destroy')->whereNumber('id');
});

// ── CRITÈRES IMPACT ───────────────────────────────────────────────────────
Route::post('/criteria/suggest', [ImpactLevelController::class, 'suggestCriteria'])->name('criteria.suggest');
Route::post('/mistral/suggest',  [ImpactLevelController::class, 'suggestLevels'])  ->name('mistral.suggest');

// ── NIVEAUX D'IMPACT ──────────────────────────────────────────────────────
Route::prefix('impact')->name('impact.')->group(function () {
    Route::post('/reorder',                 [ImpactLevelController::class, 'reorder'])              ->name('reorder');
    Route::post('/mistral/suggest',         [ImpactLevelController::class, 'suggestLevels'])        ->name('mistral.suggest');
    Route::post('/criteria/suggest-content',[ImpactLevelController::class, 'suggestCriteriaContent'])->name('criteria.suggest-content');
    Route::post('/criteria/apply-content',  [ImpactLevelController::class, 'applyCriteriaContent']) ->name('criteria.apply-content');
    Route::get('/',                         [ImpactLevelController::class, 'index'])                ->name('index');
    Route::post('/',                        [ImpactLevelController::class, 'store'])                ->name('store');
    Route::put('/{impact_level}',           [ImpactLevelController::class, 'update'])               ->name('update') ->whereNumber('impact_level');
    Route::delete('/{impact_level}',        [ImpactLevelController::class, 'destroy'])              ->name('destroy')->whereNumber('impact_level');

    Route::prefix('{impact_level}/criteria')->name('criteria.')->whereNumber('impact_level')->group(function () {
        Route::put('/{criterion}', [ImpactLevelController::class, 'updateCriterion'])->name('update')->whereNumber('criterion');
    });

    Route::prefix('templates')->name('templates.')->group(function () {
        Route::post('/',                          [ImpactLevelController::class, 'storeTemplate'])        ->name('store');
        Route::put('/{template}',                 [ImpactLevelController::class, 'updateTemplate'])       ->name('update') ->whereNumber('template');
        Route::delete('/{template}',              [ImpactLevelController::class, 'destroyTemplate'])      ->name('destroy')->whereNumber('template');
        Route::post('/reorder',                   [ImpactLevelController::class, 'reorderTemplates'])     ->name('reorder');
        Route::post('/{template}/appetite',       [ImpactLevelController::class, 'assignTemplateAppetite'])->name('appetite')->whereNumber('template');
    });
});

// ── NIVEAUX DE FRÉQUENCE ──────────────────────────────────────────────────
Route::prefix('frequency')->name('frequency.')->group(function () {
    Route::post('/reorder',             [FrequencyLevelController::class,   'reorder'])->name('reorder');
    Route::post('/mistral/suggest',     [MistralFrequencyController::class, 'suggest'])->name('mistral.suggest');
    Route::get('/',                     [FrequencyLevelController::class,   'index'])  ->name('index');
    Route::post('/',                    [FrequencyLevelController::class,   'store'])  ->name('store');
    Route::put('/{frequency_level}',    [FrequencyLevelController::class,   'update']) ->name('update') ->whereNumber('frequency_level');
    Route::delete('/{frequency_level}', [FrequencyLevelController::class,   'destroy'])->name('destroy')->whereNumber('frequency_level');
    
    Route::prefix('{frequency_level}/criteria')->name('criteria.')->whereNumber('frequency_level')->group(function () {
        Route::post('/reorder',      [FrequencyCriterionController::class, 'reorder'])->name('reorder');
        Route::post('/',             [FrequencyCriterionController::class, 'store'])  ->name('store');
        Route::put('/{criterion}',   [FrequencyCriterionController::class, 'update']) ->name('update') ->whereNumber('criterion');
        Route::delete('/{criterion}',[FrequencyCriterionController::class, 'destroy'])->name('destroy')->whereNumber('criterion');
    });
    
    Route::post('/templates/{template}/appetite', [FrequencyLevelController::class, 'assignTemplateAppetite'])
        ->name('templates.appetite')->whereNumber('template');
    Route::post('/criteria/mistral/suggest',  [MistralFrequencyCriteriaController::class, 'suggest'])    ->name('criteria.mistral.suggest');
    Route::post('/criteria/suggest-content',  [FrequencyLevelController::class, 'suggestCriteriaContent'])->name('criteria.suggest-content');
    Route::post('/criteria/apply-content',    [FrequencyLevelController::class, 'applyCriteriaContent'])  ->name('criteria.apply-content');
    
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::post('/',             [FrequencyLevelController::class, 'storeTemplate'])   ->name('store');
        Route::put('/{template}',    [FrequencyLevelController::class, 'updateTemplate'])  ->name('update') ->whereNumber('template');
        Route::delete('/{template}', [FrequencyLevelController::class, 'destroyTemplate']) ->name('destroy')->whereNumber('template');
        Route::post('/reorder',      [FrequencyLevelController::class, 'reorderTemplates'])->name('reorder');
    });
});

// ── SESSIONS RISK ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->name('risk.')->group(function () {
    Route::get('/sessions',                              [SessionController::class, 'index'])      ->name('sessions.index');
    Route::post('/sessions/assign-bulk',                 [SessionController::class, 'assignBulk']) ->name('sessions.assign-bulk');
    Route::patch('/sessions/assignments/{id}/user',      [SessionController::class, 'updateUser']) ->name('sessions.update-user')->where('id', '[0-9]+');
    Route::patch('/sessions/assignments/{id}/set-admin', [SessionController::class, 'setAdmin'])   ->name('sessions.set-admin')  ->where('id', '[0-9]+');
    Route::delete('/sessions/assignments/{id}',          [SessionController::class, 'unassign'])   ->name('sessions.unassign')   ->where('id', '[0-9]+');
});