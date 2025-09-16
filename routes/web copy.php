<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppsController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\ECommerceController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ExtendedUIController;
use App\Http\Controllers\IconsController;
use App\Http\Controllers\ChartsController;
use App\Http\Controllers\FormsController;
use App\Http\Controllers\TablesController;
use App\Http\Controllers\MapsController;
use App\Http\Controllers\LayoutsController;
use Inertia\Inertia;

require __DIR__ . '/auth.php';

   Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [DashboardController::class, 'sales'])->name('dashboard');
    Route::get('/dashboards/param', [DashboardController::class, 'salesparam'])->name('dashboard-param');
    Route::get('/dashboards/paramrr', [DashboardController::class, 'salesparam'])->name('dashboard-processus');
    Route::get('/dashboards/risque', [DashboardController::class, 'salesparam'])->name('dashboard-risque');
   Route::get('/dashboards/clinic', [DashboardController::class, 'clinic']);
    Route::get('/dashboards/e-wallet', [DashboardController::class, 'wallet']);

Route::prefix('param')->name('param.')->group(function () {
    // Routes resource pour projects
    Route::resource('projects', ProjectController::class);
    
    // Routes supplémentaires explicites
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
});

   Route::get('entities',              [EntityController::class, 'index'])->name('entities.index');
    Route::get('entities/create',       [EntityController::class, 'create'])->name('entities.create');
    Route::post('entities',             [EntityController::class, 'store'])->name('entities.store');
    Route::get('entities/{entity}',     [EntityController::class, 'show'])->name('entities.show');
    Route::get('entities/{entity}/edit',[EntityController::class, 'edit'])->name('entities.edit');
    Route::put('entities/{entity}',     [EntityController::class, 'update'])->name('entities.update');
    Route::delete('entities/{entity}',  [EntityController::class, 'destroy'])->name('entities.destroy');









    Route::get('/error/400', [ErrorController::class, 'error400']);
    Route::get('/error/401', [ErrorController::class, 'error401']);
    Route::get('/error/403', [ErrorController::class, 'error403']);
    Route::get('/error/404', [ErrorController::class, 'error404']);
    Route::get('/error/404-alt', [ErrorController::class, 'error404Alt']);
    Route::get('/error/408', [ErrorController::class, 'error408']);
    Route::get('/error/500', [ErrorController::class, 'error500']);
    Route::get('/error/501', [ErrorController::class, 'error501']);
    Route::get('/error/502', [ErrorController::class, 'error502']);
    Route::get('/error/503', [ErrorController::class, 'error503']);











});


// These routes are only for demo purpose, remove them in production. For authentication, use routes define in auth.php

Route::get('/auth/login', function () {
    return Inertia::render('auth/login');
});

Route::get('/auth/register', function () {
    return Inertia::render('auth/register');
});

Route::get('/auth/logout', function () {
    return Inertia::render('auth/logout');
});

Route::get('/auth/forgot-password', function () {
    return Inertia::render('auth/forgot-password');
});

Route::get('/auth/reset-password', function () {
    return Inertia::render('auth/reset-password');
});

Route::get('/auth/verify-email', function () {
    return Inertia::render('auth/verify-email');
});

Route::get('/auth/confirm-password', function () {
    return Inertia::render('auth/confirm-password');
});

Route::get('/auth/lock-screen', function () {
    return Inertia::render('auth/lock-screen');
});

Route::get('/auth/confirm-mail', function () {
    return Inertia::render('auth/confirm-mail');
});

Route::get('/auth/login-pin', function () {
    return Inertia::render('auth/login-pin');
});

Route::get('/auth/2fa', function () {
    return Inertia::render('auth/2fa');
});

Route::get('/auth/account-deactivation', function () {
    return Inertia::render('auth/account-deactivation');
});
