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
use App\Http\Controllers\RiskEvaluationController;
use App\Http\Controllers\RiskIncidentController;
use App\Http\Controllers\RiskIncidentLibraryController;
use App\Http\Controllers\RiskLibraryController;
use App\Http\Controllers\RiskMatrixConfigController;
use App\Http\Controllers\RiskMatrixController;
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

    // Mistral AI ── risk.core.nomenclature.mistral.suggest
    Route::post('/mistral/suggest', [MistralNomenclatureController::class, 'suggest'])
        ->name('mistral.suggest');

    // Appétances CRUD
    Route::prefix('appetites')->name('appetites.')->group(function () {
        Route::post('/',       [NomenclatureController::class, 'storeAppetite'])  ->name('store');
        Route::put('/{id}',    [NomenclatureController::class, 'updateAppetite']) ->name('update') ->whereNumber('id');
        Route::delete('/{id}', [NomenclatureController::class, 'destroyAppetite'])->name('destroy')->whereNumber('id');
    });
});

// ── INCIDENTS ACTIFS ──────────────────────────────────────────────────────
Route::prefix('incidents')->name('incidents.')->group(function () {
    Route::get('/',                 [RiskIncidentController::class, 'index'])        ->name('index');
    Route::post('/',                [RiskIncidentController::class, 'store'])        ->name('store');
    Route::put('/{id}',             [RiskIncidentController::class, 'update'])       ->name('update') ->whereNumber('id');
    Route::delete('/{id}',          [RiskIncidentController::class, 'destroy'])      ->name('destroy')->whereNumber('id');
    Route::post('/{id}/to-library', [RiskIncidentController::class, 'moveToLibrary'])->name('move-to-library')->whereNumber('id');
});

// ── BIBLIOTHÈQUE INCIDENTS ────────────────────────────────────────────────
Route::prefix('incident-library')->name('incident-library.')->group(function () {
    Route::get('/',                   [RiskIncidentLibraryController::class, 'index'])        ->name('index');
    Route::post('/{id}/reactivate',   [RiskIncidentLibraryController::class, 'reactivate'])   ->name('reactivate')    ->whereNumber('id');
    Route::post('/{id}/convert-risk', [RiskIncidentLibraryController::class, 'convertToRisk'])->name('convert-to-risk')->whereNumber('id');
});

// ── REGISTRE DES RISQUES ──────────────────────────────────────────────────
Route::prefix('risks')->name('risks.')->group(function () {
    Route::post('/mistral/suggest',       [MistralRiskController::class,  'suggest'])      ->name('mistral.suggest');
    Route::get('/entity/{entityId}/tree', [RiskRegisterController::class, 'entityTree'])   ->name('entity-tree')   ->whereNumber('entityId');
    Route::get('/library-items',          [RiskRegisterController::class, 'libraryItems']) ->name('library-items');
    Route::get('/',                       [RiskRegisterController::class, 'index'])        ->name('index');
    Route::get('/create',                 [RiskRegisterController::class, 'create'])    ->name('create');
    Route::post('/',                      [RiskRegisterController::class, 'store'])     ->name('store');
    Route::get('/{id}/edit',              [RiskRegisterController::class, 'edit'])      ->name('edit')    ->whereNumber('id');
    Route::put('/{id}',                   [RiskRegisterController::class, 'update'])    ->name('update')  ->whereNumber('id');
    Route::delete('/{id}',               [RiskRegisterController::class, 'destroy'])   ->name('destroy') ->whereNumber('id');
    Route::post('/{id}/archive',          [RiskRegisterController::class, 'archive'])         ->name('archive')          ->whereNumber('id');
    Route::post('/{id}/activate',         [RiskRegisterController::class, 'activate'])        ->name('activate')         ->whereNumber('id');
    Route::post('/{id}/move-to-library',  [RiskRegisterController::class, 'moveToLibrary'])   ->name('move-to-library')  ->whereNumber('id');
    Route::post('/{id}/remove-from-library', [RiskRegisterController::class, 'removeFromLibrary'])->name('remove-from-library')->whereNumber('id');
});

// ── ÉVALUATION DES RISQUES ────────────────────────────────────────────────
Route::prefix('evaluation')->name('evaluation.')->group(function () {
    Route::get('/inherente',  [RiskEvaluationController::class, 'inherente']) ->name('inherente');
    Route::get('/residuelle', [RiskEvaluationController::class, 'residuelle'])->name('residuelle');
    Route::get('/cible',      [RiskEvaluationController::class, 'cible'])     ->name('cible');
    Route::post('/{id}/save', [RiskEvaluationController::class, 'save'])      ->name('save')->whereNumber('id');
});

// ── BIBLIOTHÈQUE DES RISQUES ──────────────────────────────────────────────
Route::prefix('risk-library')->name('risk-library.')->group(function () {
    Route::get('/',                          [RiskLibraryController::class, 'index'])            ->name('index');
    Route::post('/{id}/remove-from-library', [RiskLibraryController::class, 'removeFromLibrary'])->name('remove-from-library')->whereNumber('id');
});

// ── CONFIGURATION MATRICE ─────────────────────────────────────────────────
Route::prefix('matrix-config')->name('matrix-config.')->group(function () {
    Route::get('/',                             [RiskMatrixConfigController::class, 'index'])     ->name('index');
    Route::post('/',                            [RiskMatrixConfigController::class, 'store'])     ->name('store');
    Route::put('/{matrix_config}',              [RiskMatrixConfigController::class, 'update'])    ->name('update');
    Route::delete('/{matrix_config}',           [RiskMatrixConfigController::class, 'destroy'])   ->name('destroy');
    Route::post('/{matrix_config}/activate',    [RiskMatrixConfigController::class, 'activate'])  ->name('activate');
    Route::post('/{matrix_config}/reset-zones', [RiskMatrixConfigController::class, 'resetZones'])->name('reset-zones');
});

// ── MATRICE HEATMAP ───────────────────────────────────────────────────────
Route::prefix('matrix')->name('matrix.')->group(function () {
    Route::get('/', [RiskMatrixController::class, 'index'])->name('index');
});

// ── NIVEAUX D'IMPACT ──────────────────────────────────────────────────────
Route::prefix('impact')->name('impact.')->group(function () {
    Route::post('/reorder',         [ImpactLevelController::class,   'reorder'])->name('reorder');
    Route::post('/mistral/suggest', [MistralImpactController::class, 'suggest'])->name('mistral.suggest');
    Route::get('/',                 [ImpactLevelController::class,   'index'])  ->name('index');
    Route::post('/',                [ImpactLevelController::class,   'store'])  ->name('store');
    Route::put('/{impact_level}',   [ImpactLevelController::class,   'update']) ->name('update') ->whereNumber('impact_level');
    Route::delete('/{impact_level}',[ImpactLevelController::class,   'destroy'])->name('destroy')->whereNumber('impact_level');

    // ── Critères d'impact ─────────────────────────────────────────────────
    Route::prefix('{impact_level}/criteria')->name('criteria.')->whereNumber('impact_level')->group(function () {
        Route::post('/reorder', [ImpactCriterionController::class, 'reorder'])->name('reorder');
        Route::post('/',        [ImpactCriterionController::class, 'store'])  ->name('store');
        Route::put('/{criterion}',   [ImpactCriterionController::class, 'update']) ->name('update') ->whereNumber('criterion');
        Route::delete('/{criterion}',[ImpactCriterionController::class, 'destroy'])->name('destroy')->whereNumber('criterion');
    });

    // ── Mistral critères d'impact (global) ────────────────────────────────
    Route::post('/criteria/mistral/suggest', [MistralImpactCriteriaController::class, 'suggest'])
        ->name('criteria.mistral.suggest');
});

// ── NIVEAUX DE FRÉQUENCE ──────────────────────────────────────────────────
Route::prefix('frequency')->name('frequency.')->group(function () {
    Route::post('/reorder',             [FrequencyLevelController::class,   'reorder'])->name('reorder');
    Route::post('/mistral/suggest',     [MistralFrequencyController::class, 'suggest'])->name('mistral.suggest');
    Route::get('/',                     [FrequencyLevelController::class,   'index'])  ->name('index');
    Route::post('/',                    [FrequencyLevelController::class,   'store'])  ->name('store');
    Route::put('/{frequency_level}',    [FrequencyLevelController::class,   'update']) ->name('update') ->whereNumber('frequency_level');
    Route::delete('/{frequency_level}', [FrequencyLevelController::class,   'destroy'])->name('destroy')->whereNumber('frequency_level');

    // ── Critères de fréquence ─────────────────────────────────────────────
    Route::prefix('{frequency_level}/criteria')->name('criteria.')->whereNumber('frequency_level')->group(function () {
        Route::post('/reorder',      [FrequencyCriterionController::class, 'reorder'])->name('reorder');
        Route::post('/',             [FrequencyCriterionController::class, 'store'])  ->name('store');
        Route::put('/{criterion}',   [FrequencyCriterionController::class, 'update']) ->name('update') ->whereNumber('criterion');
        Route::delete('/{criterion}',[FrequencyCriterionController::class, 'destroy'])->name('destroy')->whereNumber('criterion');
    });

    // ── Mistral critères de fréquence (global) ────────────────────────────
    Route::post('/criteria/mistral/suggest', [MistralFrequencyCriteriaController::class, 'suggest'])
        ->name('criteria.mistral.suggest');
});
