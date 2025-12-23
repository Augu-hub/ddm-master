<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Process\Evaluations\ProcessEvaluationController;

// Page d'accueil du module
Route::view('/', 'modules.process.core.home')->name('home');

/* ÉVALUATIONS PROCESSUS – Maturité + Axes (DIADDEM) */
Route::prefix('evaluations')->name('evaluations.')->group(function () {

    Route::get('/', [ProcessEvaluationController::class, 'index'])->name('index');
    Route::get('/entry', [ProcessEvaluationController::class, 'index'])->name('entry.index');

    // API Sessions
    Route::get('/sessions', [ProcessEvaluationController::class, 'getSessions'])
        ->name('sessions'); // → process.evaluations.sessions

    Route::post('/sessions/create', [ProcessEvaluationController::class, 'createSession'])
        ->name('sessions.create');

    Route::post('/sessions/duplicate', [ProcessEvaluationController::class, 'duplicateSession'])
        ->name('sessions.duplicate');

    Route::post('/sessions/delete', [ProcessEvaluationController::class, 'deleteSession'])
        ->name('sessions.delete');

    Route::get('/load', [ProcessEvaluationController::class, 'loadEvaluations'])
        ->name('load');

    Route::post('/maturity/save', [ProcessEvaluationController::class, 'saveMaturity'])
        ->name('maturity.save');

    Route::post('/axis/save', [ProcessEvaluationController::class, 'saveAxis'])
        ->name('axis.save');
});