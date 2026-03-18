<?php
// ============================================================
// Fichier : routes du module risk.core
// Chemin   : routes/modules/risk.core.php (ou équivalent)
// ============================================================

use App\Http\Controllers\RiskIncidentController;
use App\Http\Controllers\RiskIncidentLibraryController;
use App\Http\Controllers\RiskMatrixConfigController;
use App\Http\Controllers\RiskMatrixController;
use App\Http\Controllers\FrequencyLevelController;
use App\Http\Controllers\MistralImpactController;
use App\Http\Controllers\ImpactLevelController;
use App\Http\Controllers\MistralFrequencyController;
use App\Http\Controllers\RiskRegisterController;
use App\Http\Controllers\MistralRiskController;
use App\Http\Controllers\Risk\NomenclatureController;
use Illuminate\Support\Facades\Route;

// ── Accueil ───────────────────────────────────────────────────────────────
Route::view('/', 'modules.risk.core.home')->name('home');

// ── NOMENCLATURE & APPÉTANCES ─────────────────────────────────────────────
Route::prefix('nomenclature')->name('nomenclature.')->group(function () {
    Route::get('/', [NomenclatureController::class, 'index'])->name('index');

    Route::prefix('appetites')->name('appetites.')->group(function () {
        Route::post('/',       [NomenclatureController::class, 'storeAppetite'])  ->name('store');
        Route::put('/{id}',    [NomenclatureController::class, 'updateAppetite']) ->name('update')->whereNumber('id');
        Route::delete('/{id}', [NomenclatureController::class, 'destroyAppetite'])->name('destroy')->whereNumber('id');
    });

    Route::prefix('nomenclatures')->name('nomenclatures.')->group(function () {
        Route::post('/',       [NomenclatureController::class, 'storeNomenclature'])  ->name('store');
        Route::put('/{id}',    [NomenclatureController::class, 'updateNomenclature']) ->name('update')->whereNumber('id');
        Route::delete('/{id}', [NomenclatureController::class, 'destroyNomenclature'])->name('destroy')->whereNumber('id');
    });

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::post('suggest-domains',  [NomenclatureController::class, 'aiSuggestDomains']) ->name('suggest-domains');
        Route::post('suggest-families', [NomenclatureController::class, 'aiSuggestFamilies'])->name('suggest-families');
        Route::post('suggest-types',    [NomenclatureController::class, 'aiSuggestTypes'])   ->name('suggest-types');
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

    // API Mistral AI
    Route::post('/mistral/suggest',
        [MistralRiskController::class, 'suggest'])->name('mistral.suggest');

    // API arbre entité (un seul appel)
    Route::get('/entity/{entityId}/tree',
        [RiskRegisterController::class, 'entityTree'])->name('entity-tree')->whereNumber('entityId');

    // CRUD
    Route::get('/',               [RiskRegisterController::class, 'index'])   ->name('index');
    Route::get('/create',         [RiskRegisterController::class, 'create'])  ->name('create');
    Route::post('/',              [RiskRegisterController::class, 'store'])   ->name('store');
    Route::get('/{id}/edit',      [RiskRegisterController::class, 'edit'])    ->name('edit')    ->whereNumber('id');
    Route::put('/{id}',           [RiskRegisterController::class, 'update'])  ->name('update')  ->whereNumber('id');
    Route::delete('/{id}',        [RiskRegisterController::class, 'destroy']) ->name('destroy') ->whereNumber('id');
    Route::post('/{id}/archive',  [RiskRegisterController::class, 'archive']) ->name('archive') ->whereNumber('id');
    Route::post('/{id}/activate', [RiskRegisterController::class, 'activate'])->name('activate')->whereNumber('id');
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
    Route::post('/reorder',         [ImpactLevelController::class,  'reorder'])->name('reorder');
    Route::post('/mistral/suggest', [MistralImpactController::class,'suggest'])->name('mistral.suggest');
    Route::get('/',                 [ImpactLevelController::class,  'index'])  ->name('index');
    Route::post('/',                [ImpactLevelController::class,  'store'])  ->name('store');
    Route::put('/{impact_level}',   [ImpactLevelController::class,  'update']) ->name('update');
    Route::delete('/{impact_level}',[ImpactLevelController::class,  'destroy'])->name('destroy');
});

// ── NIVEAUX DE FRÉQUENCE ──────────────────────────────────────────────────
Route::prefix('frequency')->name('frequency.')->group(function () {
    Route::post('/reorder',            [FrequencyLevelController::class,  'reorder'])->name('reorder');
    Route::post('/mistral/suggest',    [MistralFrequencyController::class,'suggest'])->name('mistral.suggest');
    Route::get('/',                    [FrequencyLevelController::class,  'index'])  ->name('index');
    Route::post('/',                   [FrequencyLevelController::class,  'store'])  ->name('store');
    Route::put('/{frequency_level}',   [FrequencyLevelController::class,  'update']) ->name('update');
    Route::delete('/{frequency_level}',[FrequencyLevelController::class,  'destroy'])->name('destroy');
});
