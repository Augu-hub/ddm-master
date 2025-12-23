<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Process\{
    ProcessController, ContractsController, SettingsController, ReportsController,
    Evaluations\CriticalityController, Evaluations\AmdecController,
    Evaluations\RaciController, Evaluations\IdeaController,
    Evaluations\ProcessEvaluationController,
    ProcessInterfacesController
};

/*
|--------------------------------------------------------------------------
| MODULE PROCESSUS — DIADDEM
|--------------------------------------------------------------------------
| Le préfixe "/m/process.core" et le name "process.core." sont appliqués
| automatiquement par le chargeur modulaire.
|--------------------------------------------------------------------------
*/

Route::view('/', 'modules.process.core.home')->name('home');

/*
|--------------------------------------------------------------------------
| GROUPE PRINCIPAL: /process
|--------------------------------------------------------------------------
*/
Route::prefix('process')->name('process.')->group(function () {


    /* ════════════════════════════════════════════════
     *  ACCUEIL / REGISTRE
     * ════════════════════════════════════════════════ */

    /*
    |--------------------------------------------------------------------------
    | CONTRATS D’INTERFACES
    |--------------------------------------------------------------------------
    | URL réelles :
    |   /m/process.core/process/contracts
    |   /m/process.core/process/contracts/create
    |   /m/process.core/process/contracts/{id}
    |--------------------------------------------------------------------------
    */
   Route::prefix('contracts')->name('contracts.')->group(function () {

    Route::get('/', [ContractsController::class, 'index'])->name('index');
    Route::get('/create', [ContractsController::class, 'create'])->name('create');
    Route::post('/', [ContractsController::class, 'store'])->name('store');

    Route::get('/{contract}', [ContractsController::class, 'show'])->name('show');
    Route::delete('/{contract}', [ContractsController::class, 'destroy'])->name('destroy');
});


    /*
    |--------------------------------------------------------------------------
    | API Pour formulaires dynamiques NATALA
    |--------------------------------------------------------------------------
    | URL réelle :
    |   /m/process.core/process/api/process/{id}/interfaces
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/process/{id}/interfaces',
            [ProcessInterfacesController::class, 'get'])
            ->name('interfaces');

            
    });
Route::get('api/process/{processus}/interfaces', 
    [ContractsController::class, 'getInterfaces']
)->name('process.interfaces.api');

    /* ════════════════════════════════════════════════
     *  RÉFÉRENTIELS PROCESSUS
     * ════════════════════════════════════════════════ */
    Route::prefix('settings')->name('settings.')->group(function () {
        $ctrl = SettingsController::class;

        Route::get('/', [$ctrl, 'index'])->name('index');

        // Référentiels Criticité
        Route::get('/maturity-scales', [CriticalityController::class, 'maturity'])->name('maturity-scales');
        Route::get('/maturity-levels', [CriticalityController::class, 'maturity'])->name('maturity-levels');
        Route::get('/motricity-scales', [CriticalityController::class, 'motricity'])->name('motricity-scales');
        Route::get('/transversality-scales', [CriticalityController::class, 'transversality'])->name('transversality-scales');
        Route::get('/strategic-weight-scales', [CriticalityController::class, 'strategic'])->name('strategic-weight-scales');

        Route::get('/criticality-norms', [$ctrl, 'criticalityNorms'])->name('criticality-norms');
        Route::post('/criticality-norms/save', [$ctrl, 'saveCriticalityNorms'])->name('criticality-norms.save');

        Route::get('/criticality', [$ctrl, 'criticality'])->name('criticality');
        Route::get('/raci-roles', [$ctrl, 'raciRoles'])->name('raci-roles');
        Route::get('/idea-axes', [$ctrl, 'ideaAxes'])->name('idea-axes');
        Route::get('/kpi-categories', [$ctrl, 'kpiCategories'])->name('kpi-categories');
        Route::get('/link-types', [$ctrl, 'linkTypes'])->name('link-types');
        Route::get('/control-types', [$ctrl, 'controlTypes'])->name('control-types');
    });


    /* ════════════════════════════════════════════════
     *  ÉVALUATIONS
     * ════════════════════════════════════════════════ */
Route::prefix('evaluations')->name('evaluations.')->group(function () {

    // CRITICITÉ
        Route::prefix('criticality')->name('criticality.')->group(function () {
            $ctrl = CriticalityController::class;

            Route::get('/', [$ctrl, 'index'])->name('index');
            
            Route::get('/maturity', [$ctrl, 'maturity'])->name('maturity');
            Route::post('/maturity/save', [$ctrl, 'saveMaturity'])->name('maturity.save');
            Route::post('/maturity/{id}/save-levels', [$ctrl, 'saveMaturityLevels'])->name('maturity.save-levels');
            Route::delete('/maturity/{id}', [$ctrl, 'deleteMaturity'])->name('maturity.delete');

            Route::get('/motricity', [$ctrl, 'motricity'])->name('motricity');
            Route::post('/motricity/save', [$ctrl, 'saveMotricity'])->name('motricity.save');
            Route::post('/motricity/{id}/save-levels', [$ctrl, 'saveMotricityLevels'])->name('motricity.save-levels');
            Route::delete('/motricity/{id}', [$ctrl, 'deleteMotricity'])->name('motricity.delete');

            Route::get('/transversality', [$ctrl, 'transversality'])->name('transversality');
            Route::post('/transversality/save', [$ctrl, 'saveTransversality'])->name('transversality.save');
            Route::post('/transversality/{id}/save-levels', [$ctrl, 'saveTransversalityLevels'])->name('transversality.save-levels');
            Route::delete('/transversality/{id}', [$ctrl, 'deleteTransversality'])->name('transversality.delete');

            Route::get('/strategic', [$ctrl, 'strategic'])->name('strategic');
            Route::post('/strategic/save', [$ctrl, 'saveStrategic'])->name('strategic.save');
            Route::post('/strategic/{id}/save-levels', [$ctrl, 'saveStrategicLevels'])->name('strategic.save-levels');
            Route::delete('/strategic/{id}', [$ctrl, 'deleteStrategic'])->name('strategic.delete');

            Route::get('/norms', [$ctrl, 'norms'])->name('norms');
            Route::post('/norms/save', [$ctrl, 'saveNorms'])->name('norms.save');
            Route::delete('/norms/{id}', [$ctrl, 'deleteNorm'])->name('norms.delete');
        });

    Route::get('/entry', [ProcessEvaluationController::class, 'index'])->name('entry.index');
    Route::post('/entry/save', [ProcessEvaluationController::class, 'saveScores'])->name('entry.save');

    Route::get('/amdec', [AmdecController::class, 'index'])->name('amdec.index');
    Route::get('/raci', [RaciController::class, 'index'])->name('raci.index');
    Route::get('/idea', [IdeaController::class, 'index'])->name('idea.index');
});

Route::prefix('evaluations/sessions')->name('evaluations.sessions.')->group(function () {
 Route::post('/duplicate', [ProcessEvaluationController::class, 'duplicate'])->name('duplicate');

    Route::post('/maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])->name('maturity.save');
    Route::post('/axis/save', [ProcessEvaluationController::class, 'saveAxis'])->name('axis.save');
});

Route::prefix('evaluations/process')->name('process.evaluations.')->group(function () {

    // PAGE PRINCIPALE
    Route::get('/', [ProcessEvaluationController::class, 'index'])
        ->name('index');

    // CRÉATION SESSION
    Route::post('/sessions/create', [ProcessEvaluationController::class, 'createSession'])
        ->name('sessions.create');

    // CHARGER EVALUATIONS EXISTANTES
    Route::get('/load', [ProcessEvaluationController::class, 'loadProcessEvaluations'])
        ->name('load');

    // SAUVEGARDE MATURITÉ
    Route::post('/sessions/maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
        ->name('sessions.maturity.save');

    // SAUVEGARDE AXES
    Route::post('/sessions/axis/save', [ProcessEvaluationController::class, 'saveAxis'])
        ->name('sessions.axis.save');
});

// API - Récupérer Scores
Route::get('/evaluations/process/get', 
    [ProcessEvaluationController::class, 'loadProcessEvaluations']
)->name('process.evaluations.get');




    Route::prefix('reports')->name('reports.')->group(function () {
        $ctrl = ReportsController::class;

        Route::get('/list', [$ctrl, 'list'])->name('list');
        Route::get('/criticality', [$ctrl, 'criticality'])->name('criticality');
        Route::get('/maturity', [$ctrl, 'maturity'])->name('maturity');
        Route::get('/motricity', [$ctrl, 'motricity'])->name('motricity');
        Route::get('/raci', [$ctrl, 'raci'])->name('raci');
        Route::get('/idea', [$ctrl, 'idea'])->name('idea');
    });
});
