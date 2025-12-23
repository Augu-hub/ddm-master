<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Process\Evaluations\ProcessEvaluationController;

/*
|--------------------------------------------------------------------------
| ROUTES ÉVALUATION PROCESSUS
|--------------------------------------------------------------------------
| Préfixe: process/evaluations
| Middleware: auth:sanctum, verified
|--------------------------------------------------------------------------
*/

Route::prefix('process/evaluations')
    ->name('process.core.evaluations.')
    ->group(function () {

        // 📋 PAGE PRINCIPALE
        Route::get('/', [ProcessEvaluationController::class, 'index'])
            ->name('index');

        // 📊 CHARGER ÉVALUATIONS EXISTANTES
        Route::get('/load', [ProcessEvaluationController::class, 'loadEvaluations'])
            ->name('load');

        // ========== GESTION SESSIONS ==========

        // ➕ CRÉER SESSION
        Route::post('/sessions/create', [ProcessEvaluationController::class, 'createSession'])
            ->name('sessions.create');

        // 🔒 FERMER SESSION
        Route::post('/sessions/close', [ProcessEvaluationController::class, 'closeSession'])
            ->name('sessions.close');

        // 📁 ARCHIVER SESSION
        Route::post('/sessions/archive', [ProcessEvaluationController::class, 'archiveSession'])
            ->name('sessions.archive');

        // 📋 DUPLIQUER SESSION
        Route::post('/sessions/duplicate', [ProcessEvaluationController::class, 'duplicateSession'])
            ->name('sessions.duplicate');

        // 🗑️ SUPPRIMER SESSION
        Route::post('/sessions/delete', [ProcessEvaluationController::class, 'deleteSession'])
            ->name('sessions.delete');

        // ========== SAUVEGARDE ÉVALUATIONS ==========

        // 💾 SAUVEGARDER MATURITÉ (12 critères)
        Route::post('/maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
            ->name('maturity.save');

        // 📈 SAUVEGARDER UN AXE (motricity, transversality, strategic)
        Route::post('/axis/save', [ProcessEvaluationController::class, 'saveAxis'])
            ->name('axis.save');

        // ========== DONNÉES RADAR ==========

        // 📊 DONNÉES RADAR SESSION
        Route::get('/radar/session', [ProcessEvaluationController::class, 'getSessionRadarData'])
            ->name('radar.session');

        // 📊 COMPARAISON RADARS
        Route::get('/radar/compare', [ProcessEvaluationController::class, 'compareRadar'])
            ->name('radar.compare');
    });