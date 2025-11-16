<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Process\{
    ProcessController, ContractsController, SettingsController, ReportsController,
    Evaluations\CriticalityController, Evaluations\AmdecController,
    Evaluations\RaciController, Evaluations\IdeaController,
    Evaluations\ProcessEvaluationController
};

/*
|--------------------------------------------------------------------------
| MODULE PROCESSUS — DIADDEM
|--------------------------------------------------------------------------
| Ce module couvre :
| - Les Référentiels (paramètres du module)
| - Les Évaluations (Criticité, AMDEC, RACI, IDEA)
| - Les Rapports et Analyses
|
| Le préfixe "/m/process.core" et le nom "process.core." sont ajoutés
| automatiquement par le chargeur modulaire.
|
| Exemples complets :
|   /m/process.core/process/settings/maturity-scales
|   /m/process.core/process/evaluations/criticality/maturity
|   /m/process.core/process/reports/criticality
|--------------------------------------------------------------------------
*/

Route::view('/', 'modules.process.core.home')->name('home');

Route::prefix('process')->name('process.')->group(function () {

    /* ════════════════════════════════════════════════
     *  ACCUEIL / REGISTRE
     * ════════════════════════════════════════════════ */
    Route::get('/', [ProcessController::class, 'index'])->name('index');
    Route::get('/contracts', [ContractsController::class, 'index'])->name('contracts.index');

    /* ════════════════════════════════════════════════
     *  RÉFÉRENTIELS PROCESSUS
     * ════════════════════════════════════════════════ */
    Route::prefix('settings')->name('settings.')->group(function () {
        $ctrl = SettingsController::class;

        Route::get('/', [$ctrl, 'index'])->name('index');

        // Référentiels de criticité
        Route::get('/maturity-scales', [CriticalityController::class, 'maturity'])->name('maturity-scales');
        Route::get('/maturity-levels', [CriticalityController::class, 'maturity'])->name('maturity-levels');
        Route::get('/motricity-scales', [CriticalityController::class, 'motricity'])->name('motricity-scales');
        Route::get('/transversality-scales', [CriticalityController::class, 'transversality'])->name('transversality-scales');
        Route::get('/strategic-weight-scales', [CriticalityController::class, 'strategic'])->name('strategic-weight-scales');

        // Normes de criticité (nouvelle table)
        Route::get('/criticality-norms', [$ctrl, 'criticalityNorms'])->name('criticality-norms');
        Route::post('/criticality-norms/save', [$ctrl, 'saveCriticalityNorms'])->name('criticality-norms.save');

        // Autres référentiels
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

        // ── CRITICITÉ ────────────────────────────────
        Route::prefix('criticality')->name('criticality.')->group(function () {
            $ctrl = CriticalityController::class;
            Route::get('/', [$ctrl, 'index'])->name('index');
            Route::get('/maturity', [$ctrl, 'maturity'])->name('maturity');
            Route::get('/motricity', [$ctrl, 'motricity'])->name('motricity');
            Route::get('/transversality', [$ctrl, 'transversality'])->name('transversality');
            Route::get('/strategic', [$ctrl, 'strategic'])->name('strategic');

            // Mise à jour inline
            Route::post('/update/{table}', [$ctrl, 'update'])
                ->where('table', 'process_maturity_levels|process_motricity_scales|process_transversality_scales|process_strategic_weight_scales')
                ->name('update');
        });

        // ── SAISIE DES NOTES (Évaluations utilisateur)
        Route::get('/entry', [ProcessEvaluationController::class, 'index'])->name('entry.index');
        Route::post('/entry/save', [ProcessEvaluationController::class, 'saveScores'])->name('entry.save');

        // ── AMDEC / RACI / IDEA ─────────────────────
        Route::get('/amdec', [AmdecController::class, 'index'])->name('amdec.index');
        Route::get('/raci', [RaciController::class, 'index'])->name('raci.index');
        Route::get('/idea', [IdeaController::class, 'index'])->name('idea.index');
    });

    /* ════════════════════════════════════════════════
     *  RAPPORTS ET ANALYSES
     * ════════════════════════════════════════════════ */
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
