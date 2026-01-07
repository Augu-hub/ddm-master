<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Risk\RiskController;
use App\Http\Controllers\Risk\RiskTypeController;
// Ajouter dans routes/web.php le contenu de routes_risk_complete.php
 Route::get('/risque', [RiskController::class, 'index'])->name('risque');
    Route::prefix('dashboards/risk')->name('risk.')->group(function () {
       
        Route::post('/', [RiskController::class, 'store'])->name('store');
        Route::put('/{id}', [RiskController::class, 'update'])->name('update');
        Route::delete('/{id}', [RiskController::class, 'destroy'])->name('destroy');
        Route::post('/frequency', [RiskController::class, 'storeFrequency'])->name('frequency.store');
        Route::post('/impact', [RiskController::class, 'storeImpact'])->name('impact.store');
        Route::get('/types', [RiskController::class, 'typesIndex'])->name('types.index');
        Route::post('/types', [RiskController::class, 'storeType'])->name('type.store');
        Route::post('/ai-suggestions', [RiskController::class, 'suggestionsAI'])->name('ai-suggestions');
        Route::get('/export', [RiskController::class, 'export'])->name('export');
    });
