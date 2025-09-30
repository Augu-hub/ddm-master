<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\Param\ProjetController;
use App\Http\Controllers\Param\MacroProcessusController;
use App\Http\Controllers\Param\ParamStaticController;
use App\Http\Controllers\Param\EntiteController;
use App\Http\Controllers\Param\FonctionController;
use App\Http\Controllers\Param\DistributionController;
use App\Http\Controllers\Param\ProcessusController;
use App\Http\Controllers\Param\ActiviteController;
use App\Http\Controllers\Param\ChartsController;
use App\Http\Controllers\Param\FunctionAssignmentController;
use App\Http\Controllers\NavMenuController;
use App\Http\Controllers\Param\EntityFunctionsChartController;
use App\Http\Controllers\Param\MenuPermissionController;

use App\Http\Controllers\Param\MpsRespController;

use Inertia\Inertia;

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboards
    Route::get('/',                    [DashboardController::class, 'sales'])->name('dashboard');
    Route::get('/dashboards/param',    [DashboardController::class, 'salesparam'])->name('dashboard-param');
    Route::get('/dashboards/paramr',  [DashboardController::class, 'salesparam'])->name('dashboard-processus');
    Route::get('/dashboards/risque',   [DashboardController::class, 'salesparam'])->name('dashboard-risque');
    Route::get('/dashboards/clinic',   [DashboardController::class, 'clinic']);
    Route::get('/dashboards/e-wallet', [DashboardController::class, 'wallet']);

    // Groupe PARAM -> /param/...
    Route::prefix('param')->name('param.')->group(function () {
        Route::resource('projects', ProjetController::class);
        Route::resource('process', MacroProcessusController::class);
        Route::resource('entities', EntiteController::class);
    });

    Route::prefix('param')->name('param.')->group(function () {
        Route::get('/mpa', [MacroProcessusController::class,'index'])->name('mpa.index');
        Route::post('/macro', [MacroProcessusController::class,'store'])->name('macro.store');
        Route::post('/macro/validate-defaults', [MacroProcessusController::class,'validateDefaults'])->name('macro.validate');
        Route::put('/macro/{macro}', [MacroProcessusController::class,'update'])->name('macro.update');

        Route::post('/process', [ProcessusController::class,'store'])->name('process.store');
        Route::put('/process/{process}', [ProcessusController::class,'update'])->name('process.update');
        Route::delete('/process/{process}', [ProcessusController::class,'destroy'])->name('process.destroy');

        Route::post('/activity', [ActiviteController::class,'store'])->name('activity.store');
        Route::put('/activity/{activity}', [ActiviteController::class,'update'])->name('activity.update');
        Route::delete('/activity/{activity}', [ActiviteController::class,'destroy'])->name('activity.destroy');
    });

    Route::get('/param/fonctions',    [ParamStaticController::class, 'fonctions'])->name('function.index');
    Route::get('/param/repartitions', [ParamStaticController::class, 'repartitions'])->name('distribution.index');

    // Pages d'erreur
    Route::get('/error/400',    [ErrorController::class, 'error400']);
    Route::get('/error/401',    [ErrorController::class, 'error401']);
    Route::get('/error/403',    [ErrorController::class, 'error403']);
    Route::get('/error/404',    [ErrorController::class, 'error404']);
    Route::get('/error/404-alt',[ErrorController::class, 'error404Alt']);
    Route::get('/error/408',    [ErrorController::class, 'error408']);
    Route::get('/error/500',    [ErrorController::class, 'error500']);
    Route::get('/error/501',    [ErrorController::class, 'error501']);
    Route::get('/error/502',    [ErrorController::class, 'error502']);
    Route::get('/error/503',    [ErrorController::class, 'error503']);
});

// Auth demo routes
Route::get('/auth/login',               fn () => Inertia::render('auth/login'));
Route::get('/auth/register',            fn () => Inertia::render('auth/register'));
// ... autres routes auth

// Routes Param principales avec noms corrects
Route::prefix('param')->name('param.')->middleware(['web','auth','verified'])->group(function () {
    
    // Fonctions
    Route::get('function',                 [FonctionController::class,'index'])->name('function.index');
    Route::post('function',                [FonctionController::class,'store'])->name('function.store');
    Route::put('function/{function}',      [FonctionController::class,'update'])->name('function.update');
    Route::delete('function/{function}',   [FonctionController::class,'destroy'])->name('function.destroy');

    // Charts
    Route::get('/charts/entity', [ChartsController::class, 'entity'])->name('charts.entity');
    Route::get('/charts/function', [ChartsController::class, 'function'])->name('charts.function');
    Route::get('/charts/distribution', [ChartsController::class, 'distribution'])->name('charts.distribution');

    // Entities menu
    Route::get('/entities-menu-children', [EntiteController::class,'menuChildren'])->name('entities.menu-children');
});
Route::middleware(['web','auth','verified'])->prefix('param')->name('param.')->group(function () {
    // Distribution (MPA → Entité)
    Route::get('/distribution',          [DistributionController::class, 'index'])->name('distribution.index');
    Route::get('/distribution/tree',     [DistributionController::class, 'tree'])->name('distribution.tree');
    Route::get('/distribution/current',  [DistributionController::class, 'current'])->name('distribution.current');
    Route::post('/distribution/preview', [DistributionController::class, 'preview'])->name('distribution.preview');
    Route::post('/distribution/commit',  [DistributionController::class, 'commit'])->name('distribution.commit');

    // Fonctions → Entité
    Route::get('/functions/list',        [FunctionAssignmentController::class, 'list'])->name('functions.list');
    Route::get('/functions/current',     [FunctionAssignmentController::class, 'current'])->name('functions.current');
    Route::post('/functions/commit',     [FunctionAssignmentController::class, 'commit'])->name('functions.commit');

    // MPS / Responsable
    Route::get('/mpsresp/tree',          [MpsResponsableController::class,'tree'])->name('mpsresp.tree');
    Route::get('/mpsresp/current',       [MpsResponsableController::class,'current'])->name('mpsresp.current');
    Route::post('/mpsresp/assign',       [MpsResponsableController::class,'assign'])->name('mpsresp.assign');
    Route::post('/mpsresp/unassign',     [MpsResponsableController::class,'unassign'])->name('mpsresp.unassign');

});
// routes/web.php
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/api/menu/entities', [NavMenuController::class, 'getEntities'])->name('api.menu.entities');
});
// routes/web.php
// routes/web.php

// routes/web.php


Route::prefix('param/charts')->name('param.charts.')->group(function () {
    // Page (sans ou avec entité pré-sélectionnée)
    Route::get('/entity-functions',                     [EntityFunctionsChartController::class,'index'])->name('entity.functions.index');
    Route::get('/entity-functions/{entity}',            [EntityFunctionsChartController::class,'index'])->name('entity.functions.show');

    // API JSON
    Route::get('/entity-functions-data',                [EntityFunctionsChartController::class,'getChartData'])->name('entity.functions.data');
    Route::get('/entity-functions-data/{entity?}',      [EntityFunctionsChartController::class,'getChartData'])->name('entity.functions.data.byEntity');
    Route::get('/entity-functions-stats',               [EntityFunctionsChartController::class,'getStats'])->name('entity.functions.stats');
});
// routes/web.php

Route::middleware(['web','auth'])->group(function () {
    Route::get('/api/menu/visibility', [MenuPermissionController::class,'visibility']);
});
