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
*/

Route::view('/', 'modules.process.core.home')->name('home');


/*
|--------------------------------------------------------------------------
| GROUPE PRINCIPAL /process
|--------------------------------------------------------------------------
*/
Route::prefix('process')->name('process.')->group(function () {


    /*
    |--------------------------------------------------------------------------
    | CONTRATS D’INTERFACES
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
    | API INTERFACES
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/process/{id}/interfaces',
            [ProcessInterfacesController::class, 'get']
        )->name('interfaces');
    });

    Route::get('api/process/{processus}/interfaces',
        [ContractsController::class, 'getInterfaces']
    )->name('process.interfaces.api');



    /*
    |--------------------------------------------------------------------------
    | SETTINGS / RÉFÉRENTIELS
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->group(function () {

        $ctrl = SettingsController::class;

        Route::get('/', [$ctrl, 'index'])->name('index');

        Route::get('/maturity-scales', [CriticalityController::class, 'maturity'])->name('maturity-scales');
        Route::get('/motricity-scales', [CriticalityController::class, 'motricity'])->name('motricity-scales');
        Route::get('/transversality-scales', [CriticalityController::class, 'transversality'])->name('transversality-scales');
        Route::get('/strategic-weight-scales', [CriticalityController::class, 'strategic'])->name('strategic-weight-scales');

        Route::get('/criticality-norms', [$ctrl, 'criticalityNorms'])->name('criticality-norms');
        Route::post('/criticality-norms/save', [$ctrl, 'saveCriticalityNorms'])->name('criticality-norms.save');
    });



    /*
    |--------------------------------------------------------------------------
    | EVALUATIONS — DIADDEM OFFICIEL
    |--------------------------------------------------------------------------
    |
    | PROCESSUS → SESSIONS → ÉVALUATIONS
    |
    |--------------------------------------------------------------------------
    */
    Route::prefix('evaluations/process')
        ->name('process.evaluations.')
        ->group(function () {

            // PAGE PRINCIPALE
            Route::get('/', [ProcessEvaluationController::class, 'index'])
                ->name('index');

            // CRÉATION SESSION
            Route::post('/sessions/create', [ProcessEvaluationController::class, 'createSession'])
                ->name('sessions.create');

            // CHARGER TOUTES LES ÉVALUATIONS D’UNE SESSION
            Route::get('/load', [ProcessEvaluationController::class, 'loadProcessEvaluations'])
                ->name('load');

            // MATURITÉ (CEM1 → CEM12)
            Route::post('/sessions/maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
                ->name('sessions.maturity.save');

            // AXES (motricité, transversalité, stratégique)
            Route::post('/sessions/axis/save', [ProcessEvaluationController::class, 'saveAxis'])
                ->name('sessions.axis.save');
        });


    /*
    |--------------------------------------------------------------------------
    | API : GET SCORES
    |--------------------------------------------------------------------------
    */
    Route::get('/evaluations/process/get',
        [ProcessEvaluationController::class, 'loadProcessEvaluations']
    )->name('process.evaluations.get');



    /*
    |--------------------------------------------------------------------------
    | AUTRES MODULES (AMDEC / RACI / IDEA / RAPPORTS)
    |--------------------------------------------------------------------------
    */

    Route::prefix('evaluations')->name('evaluations.')->group(function () {

        Route::get('/amdec', [AmdecController::class, 'index'])->name('amdec.index');
        Route::get('/raci', [RaciController::class, 'index'])->name('raci.index');
        Route::get('/idea', [IdeaController::class, 'index'])->name('idea.index');

    });

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

