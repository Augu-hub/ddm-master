<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Process\{
    ProcessController,
    ContractsController,
    SettingsController,
    ReportsController,
    Evaluations\CriticalityController,
    Evaluations\AmdecController,
    Evaluations\RaciController,
    Evaluations\IdeaController,
    Evaluations\ProcessEvaluationController,
    ProcessInterfacesController
};

/*
|--------------------------------------------------------------------------
| WEB ROUTES - DIADDEM PROCESSUS MODULE
|--------------------------------------------------------------------------
|
| ✅ MIDDLEWARE 'tenant' REQUIS
| ✅ ROUTE INDEX (/process/evaluations) REQUISE
| ✅ 8 ROUTES D'ÉVALUATION REQUISES
|
*/

Route::view('/', 'modules.process.core.home')->name('home');

Route::prefix('process')
    ->name('process.')
    ->group(function () {

        // ════════════════════════════════════════════════════════════════════════
        // 🎯 ÉVALUATIONS PROCESSUS — DIADDEM OFFICIEL
        // 
        // Routes:
        // - GET /process/evaluations → index()
        // - GET /process/evaluations/sessions → getSessions()
        // - POST /process/evaluations/sessions/create → createSession()
        // - POST /process/evaluations/sessions/duplicate → duplicateSession()
        // - POST /process/evaluations/sessions/delete → deleteSession()
        // - GET /process/evaluations/load → loadEvaluations()
        // - POST /process/evaluations/maturity/save → saveMaturity()
        // - POST /process/evaluations/axis/save → saveAxis()
        // ════════════════════════════════════════════════════════════════════════
        Route::prefix('evaluations')->name('evaluations.')->group(function () {

            // ✅ PAGE PRINCIPALE (REQUIS! Sinon Ziggy error)
            Route::get('/', [ProcessEvaluationController::class, 'index'])
                ->name('index');
  Route::get('/entry', [ProcessEvaluationController::class, 'index'])->name('entry.index');
        Route::post('/entry/save', [ProcessEvaluationController::class, 'saveScores'])->name('entry.save');

            // ✅ CHARGER SESSIONS D'UN PROCESSUS
            Route::get('/sessions', [ProcessEvaluationController::class, 'getSessions'])
                ->name('sessions');

            // ✅ CRÉER SESSION
            Route::post('/sessions/create', [ProcessEvaluationController::class, 'createSession'])
                ->name('sessions.create');

            // ✅ DUPLIQUER SESSION
            Route::post('/sessions/duplicate', [ProcessEvaluationController::class, 'duplicateSession'])
                ->name('sessions.duplicate');

            // ✅ SUPPRIMER SESSION
            Route::post('/sessions/delete', [ProcessEvaluationController::class, 'deleteSession'])
                ->name('sessions.delete');

            // ✅ CHARGER ÉVALUATIONS (maturité + axes)
            Route::get('/load', [ProcessEvaluationController::class, 'loadEvaluations'])
                ->name('load');

            // ✅ SAUVEGARDER MATURITÉ
            Route::post('/maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
                ->name('maturity.save');

            // ✅ SAUVEGARDER AXES (motricité, transversalité, stratégique)
            Route::post('/axis/save', [ProcessEvaluationController::class, 'saveAxis'])
                ->name('axis.save');

        }); // FIN ÉVALUATIONS


        // ════════════════════════════════════════════════════════════════════════
        // 🔗 CONTRATS D'INTERFACES
        // ════════════════════════════════════════════════════════════════════════
        Route::prefix('contracts')->name('contracts.')->group(function () {
            Route::get('/', [ContractsController::class, 'index'])->name('index');
            Route::get('/create', [ContractsController::class, 'create'])->name('create');
            Route::post('/', [ContractsController::class, 'store'])->name('store');
            Route::get('/{contract}', [ContractsController::class, 'show'])->name('show');
            Route::delete('/{contract}', [ContractsController::class, 'destroy'])->name('destroy');
        });


        // ════════════════════════════════════════════════════════════════════════
        // 🔌 API INTERFACES
        // ════════════════════════════════════════════════════════════════════════
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/process/{id}/interfaces',
                [ProcessInterfacesController::class, 'get']
            )->name('interfaces');
        });

        Route::get('api/process/{processus}/interfaces',
            [ContractsController::class, 'getInterfaces']
        )->name('process.interfaces.api');


        // ════════════════════════════════════════════════════════════════════════
        // ⚙️ SETTINGS / RÉFÉRENTIELS
        // ════════════════════════════════════════════════════════════════════════
        Route::prefix('settings')->name('settings.')->group(function () {

            $ctrl = SettingsController::class;

            Route::get('/', [$ctrl, 'index'])->name('index');

            Route::get('/maturity-scales', [CriticalityController::class, 'maturity'])
                ->name('maturity-scales');
            Route::get('/motricity-scales', [CriticalityController::class, 'motricity'])
                ->name('motricity-scales');
            Route::get('/transversality-scales', [CriticalityController::class, 'transversality'])
                ->name('transversality-scales');
            Route::get('/strategic-weight-scales', [CriticalityController::class, 'strategic'])
                ->name('strategic-weight-scales');

            Route::get('/criticality-norms', [$ctrl, 'criticalityNorms'])
                ->name('criticality-norms');
            Route::post('/criticality-norms/save', [$ctrl, 'saveCriticalityNorms'])
                ->name('criticality-norms.save');

        }); // FIN SETTINGS


        // ════════════════════════════════════════════════════════════════════════
        // 📊 AUTRES MODULES (AMDEC / RACI / IDEA)
        // ════════════════════════════════════════════════════════════════════════
        Route::prefix('modules')->name('modules.')->group(function () {

            Route::get('/amdec', [AmdecController::class, 'index'])
                ->name('amdec.index');
            Route::get('/raci', [RaciController::class, 'index'])
                ->name('raci.index');
            Route::get('/idea', [IdeaController::class, 'index'])
                ->name('idea.index');

        }); // FIN MODULES


        // ════════════════════════════════════════════════════════════════════════
        // 📈 RAPPORTS
        // ════════════════════════════════════════════════════════════════════════
        Route::prefix('reports')->name('reports.')->group(function () {

            $ctrl = ReportsController::class;

            Route::get('/list', [$ctrl, 'list'])->name('list');
            Route::get('/criticality', [$ctrl, 'criticality'])->name('criticality');
            Route::get('/maturity', [$ctrl, 'maturity'])->name('maturity');
            Route::get('/motricity', [$ctrl, 'motricity'])->name('motricity');
            Route::get('/raci', [$ctrl, 'raci'])->name('raci');
            Route::get('/idea', [$ctrl, 'idea'])->name('idea');

        }); // FIN REPORTS

    }); // FIN GROUPE PRINCIPAL PROCESS