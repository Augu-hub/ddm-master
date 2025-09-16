<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorController;
// === Param controllers ===
use App\Http\Controllers\Param\ProjectController;
use App\Http\Controllers\Param\MacroprocessController;
use App\Http\Controllers\Param\ParamStaticController;
use App\Http\Controllers\Param\EntityController;

use Inertia\Inertia;

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboards
    Route::get('/',                    [DashboardController::class, 'sales'])->name('dashboard');
    Route::get('/dashboards/param',    [DashboardController::class, 'salesparam'])->name('dashboard-param');
    Route::get('/dashboards/paramrr',  [DashboardController::class, 'salesparam'])->name('dashboard-processus');
    Route::get('/dashboards/risque',   [DashboardController::class, 'salesparam'])->name('dashboard-risque');
    Route::get('/dashboards/clinic',   [DashboardController::class, 'clinic']);
    Route::get('/dashboards/e-wallet', [DashboardController::class, 'wallet']);

    // Groupe PARAM -> /param/...
    Route::prefix('param')->name('param.')->group(function () {
        // Projects (une seule définition suffit)
        Route::resource('projects', ProjectController::class);
       
        // Entities (placé dans le même prefix pour obtenir /param/entities)
        Route::resource('process', MacroprocessController::class);
        Route::resource('entities', EntityController::class);
   
        // (Ajoute ici d'autres resources param si besoin: process, function, distribution, charts...)
        // ex: Route::resource('process', ProcessController::class);
    });
    Route::get('/param/fonctions',    [ParamStaticController::class, 'fonctions'])
    ->name('function.index');

Route::get('/param/repartitions', [ParamStaticController::class, 'repartitions'])
    ->name('distribution.index');

    // (Optionnel) Si tu veux que les pages d'erreur exigent auth, laisse-les ici.
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

// === Démos d’auth (hors middleware) ===
Route::get('/auth/login',               fn () => Inertia::render('auth/login'));
Route::get('/auth/register',            fn () => Inertia::render('auth/register'));
Route::get('/auth/logout',              fn () => Inertia::render('auth/logout'));
Route::get('/auth/forgot-password',     fn () => Inertia::render('auth/forgot-password'));
Route::get('/auth/reset-password',      fn () => Inertia::render('auth/reset-password'));
Route::get('/auth/verify-email',        fn () => Inertia::render('auth/verify-email'));
Route::get('/auth/confirm-password',    fn () => Inertia::render('auth/confirm-password'));
Route::get('/auth/lock-screen',         fn () => Inertia::render('auth/lock-screen'));
Route::get('/auth/confirm-mail',        fn () => Inertia::render('auth/confirm-mail'));
Route::get('/auth/login-pin',           fn () => Inertia::render('auth/login-pin'));
Route::get('/auth/2fa',                 fn () => Inertia::render('auth/2fa'));
Route::get('/auth/account-deactivation',fn () => Inertia::render('auth/account-deactivation'));
