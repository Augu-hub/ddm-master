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
    | PROCESSUS → SESSIONS → ÉVALUATIONS
    |--------------------------------------------------------------------------
    */
    Route::prefix('evaluations/process')
        ->name('process.evaluations.')
        ->group(function () {

            // ⭐ PAGE PRINCIPALE — indispensable
            Route::get('/', [ProcessEvaluationController::class, 'index'])
                ->name('index');

            // ⭐ CRÉATION SESSION
            Route::post('/sessions/create', [ProcessEvaluationController::class, 'createSession'])
                ->name('sessions.create');

            // ⭐ CHARGER ÉVALUATIONS (maturité + axes)
            Route::get('/load', [ProcessEvaluationController::class, 'loadProcessEvaluations'])
                ->name('load');

            // ⭐ MATURITÉ (CEM1 → CEM12)
            Route::post('/sessions/maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
                ->name('sessions.maturity.save');

            // ⭐ AXES (motricité / transversalité / stratégique)
            Route::post('/sessions/axis/save', [ProcessEvaluationController::class, 'saveAxis'])
                ->name('sessions.axis.save');
        });


    /*
    |--------------------------------------------------------------------------
    | API : GET SCORES (utilisé par la vue)
    |--------------------------------------------------------------------------
    */
    Route::get('/evaluations/process/get',
        [ProcessEvaluationController::class, 'loadProcessEvaluations']
    )->name('process.evaluations.get');



    /*
    |--------------------------------------------------------------------------
    | AUTRES MODULES : AMDEC, RACI, IDEA
    |--------------------------------------------------------------------------
    */
    Route::prefix('evaluations')->name('evaluations.')->group(function () {

        Route::get('/entry', [ProcessEvaluationController::class, 'index'])->name('entry.index');
        Route::post('/entry/save', [ProcessEvaluationController::class, 'saveScores'])->name('entry.save');

        Route::get('/amdec', [AmdecController::class, 'index'])->name('amdec.index');
        Route::get('/raci', [RaciController::class, 'index'])->name('raci.index');
        Route::get('/idea', [IdeaController::class, 'index'])->name('idea.index');
    });



    /*
    |--------------------------------------------------------------------------
    | RAPPORTS
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')->group(function () {

        $ctrl = ReportsController::class;

        Route::get('/list', [$ctrl, 'list'])->name('list');
        Route::get('/criticality', [$ctrl, 'criticality'])->name('criticality');
        Route::get('/maturity', [$ctrl, 'maturity'])->name('maturity');
        Route::get('/motricity', [$ctrl, 'motricity'])->name('motricity');
        Route::get('/raci', [$ctrl, 'raci'])->name('raci');
        Route::get('/idea', [$ctrl, 'idea'])->name('idea');
    });

    Route::prefix('process')->name('process.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | ÉVALUATIONS PROCESSUS
    |--------------------------------------------------------------------------
    */
    Route::prefix('evaluations')->name('evaluations.')->group(function () {

        // INDEX PRINCIPAL
        Route::get('/process', 
            [ProcessEvaluationController::class, 'index']
        )->name('process.index');

        // CRÉER UNE SESSION
        Route::post('/process/sessions/create',
            [ProcessEvaluationController::class, 'createSession']
        )->name('process.sessions.create');

        // CHARGER SCORES (sessions + process)
        Route::get('/process/load',
            [ProcessEvaluationController::class, 'loadProcessEvaluations']
        )->name('process.load');

        // MATURITÉ CEM1 → CEM12
        Route::post('/process/sessions/maturity/save',
            [ProcessEvaluationController::class, 'saveMaturity']
        )->name('process.sessions.maturity.save');

        // AUTRES AXES
        Route::post('/process/sessions/axis/save',
            [ProcessEvaluationController::class, 'saveAxis']
        )->name('process.sessions.axis.save');
    });



Route::middleware(['auth', 'verified', 'tenant'])->prefix('process')->name('process.')->group(function () {

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 📊 PAGE PRINCIPALE
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/evaluations', [ProcessEvaluationController::class, 'index'])
        ->name('evaluations.index');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 📅 GESTION SESSIONS
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/evaluations/sessions', [ProcessEvaluationController::class, 'getSessions'])
        ->name('evaluations.sessions');

    Route::post('/evaluations/sessions/create', [ProcessEvaluationController::class, 'createSession'])
        ->name('evaluations.sessions.create');

    Route::post('/evaluations/sessions/duplicate', [ProcessEvaluationController::class, 'duplicateSession'])
        ->name('evaluations.sessions.duplicate');

    Route::post('/evaluations/sessions/delete', [ProcessEvaluationController::class, 'deleteSession'])
        ->name('evaluations.sessions.delete');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 📥 CHARGEMENT DES ÉVALUATIONS EXISTANTES
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::get('/evaluations/load', [ProcessEvaluationController::class, 'loadEvaluations'])
        ->name('evaluations.load');

    // ═══════════════════════════════════════════════════════════════════════════════════
    // 💾 SAUVEGARDE ÉVALUATIONS
    // ═══════════════════════════════════════════════════════════════════════════════════
    Route::post('/evaluations/maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
        ->name('evaluations.maturity.save');

    Route::post('/evaluations/axis/save', [ProcessEvaluationController::class, 'saveAxis'])
        ->name('evaluations.axis.save');

});

/*
|--------------------------------------------------------------------------
| ROUTES EXISTANTES — À CONSERVER
|--------------------------------------------------------------------------
| Ne rien modifier au-dessus - ceci c'est juste pour le module DIADDEM
| PROCESSUS - Les autres routes existantes restent intactes
*/

});
});
