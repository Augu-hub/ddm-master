<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleEntryController;
use App\Http\Controllers\ErrorController;

// Admin
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\UserAssignmentController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\TenantsController;

// Menu API
use App\Http\Controllers\NavMenuController;

/* ====== Auth scaffolding ====== */
require __DIR__.'/auth.php';

/* ====== Pages Auth publiques (Blade/Inertia) ====== */
Route::get('/auth/login',    fn () => Inertia::render('auth/login'));
Route::get('/auth/register', fn () => Inertia::render('auth/register'));

/* ======================================================================
|  AUTH + VERIFIED
|====================================================================== */
Route::middleware(['web','auth','verified'])->group(function () {

    /* Accueil = grille de modules autorisés */
    Route::get('/', DashboardController::class)->name('dashboard');

    /* ====== ENTRÉE MODULE (CRITIQUE : doit être AVANT les routes API) ====== */
    Route::get('/m/{code}', [ModuleEntryController::class, 'show'])
        ->where('code', '[-_.a-z0-9]+')
        ->middleware(['bind.module', 'share.module'])
        ->name('modules.shell');

    /* Dashboards fallback si un module n'a pas d'entry_route */
    Route::prefix('dashboards')->group(function () {
        Route::get('/param',     [DashboardController::class, 'dashboardParam'])->name('param.projects.home');
        Route::get('/processus', [DashboardController::class, 'dashboardProcessus'])->name('process.core.home');
        Route::get('/risque',    [DashboardController::class, 'dashboardRisque'])->name('risk.core.home');
        Route::get('/audit',     [DashboardController::class, 'dashboardAudit'])->name('audit.core.home');
    });

    /* ====== API Menu (avec bind.module) ====== */
    Route::prefix('api')->middleware(['bind.module'])->group(function () {
        Route::get('/menu/structure',  [NavMenuController::class, 'structure']);
        Route::get('/menu/visibility', [NavMenuController::class, 'visibility']);
        Route::get('/menu/entities',   [NavMenuController::class, 'getEntities']);
        Route::get('/me/modules',      [NavMenuController::class, 'myModules']);
    });

    /* ====== Admin ====== */
    Route::prefix('admin')->name('admin.')->group(function () {
        // Services
        Route::get('/services',  [ServiceController::class, 'index'])->name('services.index');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');

        // Affectations
        Route::get('/assign/users-services', [UserAssignmentController::class, 'pageUsersServices'])->name('assign.users-services');
        Route::get('/assign/users-modules',  [UserAssignmentController::class, 'pageUsersModules'])->name('assign.users-modules');
        Route::post('/users/{user}/services/sync', [UserAssignmentController::class, 'syncServices'])->name('users.services.sync');
        Route::post('/users/{user}/modules/sync',  [UserAssignmentController::class, 'syncModules'])->name('users.modules.sync');

        // Permissions / Rôles
        Route::get('/permissions',            [PermissionsController::class,'index'])->name('permissions.index')->middleware('menu.authz:permissions');
        Route::post('/permissions',           [PermissionsController::class,'store'])->name('permissions.store')->middleware('menu.authz:permissions');
        Route::post('/permissions/sync-role', [PermissionsController::class,'syncRole'])->name('permissions.syncRole')->middleware('menu.authz:permissions');
        Route::post('/permissions/sync-user', [PermissionsController::class,'syncUser'])->name('permissions.syncUser')->middleware('menu.authz:permissions');

        Route::get('/roles',            [RolesController::class,'index'])->name('roles.index')->middleware('menu.authz:roles');
        Route::post('/roles',           [RolesController::class,'store'])->name('roles.store')->middleware('menu.authz:roles');
        Route::post('/roles/sync-user', [RolesController::class,'syncUserRoles'])->name('roles.syncUserRoles')->middleware('menu.authz:roles');

        // Tenants
        Route::get('/tenants',                      [TenantsController::class, 'index'])->name('tenants.index')->middleware('menu.authz:tenants');
        Route::post('/tenants',                     [TenantsController::class, 'store'])->name('tenants.store')->middleware('menu.authz:tenants');
        Route::put('/tenants/{tenant}',             [TenantsController::class, 'update'])->name('tenants.update')->middleware('menu.authz:tenants')->whereNumber('tenant');
        Route::delete('/tenants/{tenant}',          [TenantsController::class, 'destroy'])->name('tenants.destroy')->middleware('menu.authz:tenants')->whereNumber('tenant');
        Route::post('/tenants/{tenant}/sync-users', [TenantsController::class, 'syncUsers'])->name('tenants.syncUsers')->middleware('menu.authz:tenants')->whereNumber('tenant');
        Route::post('/tenants/{tenant}/remove-user',[TenantsController::class, 'removeUser'])->name('tenants.removeUser')->middleware('menu.authz:tenants')->whereNumber('tenant');
    });
});

/* ====== Erreurs publiques ====== */
Route::get('/error/403', [ErrorController::class, 'error403'])->name('error403');
Route::get('/error/404', [ErrorController::class, 'error404'])->name('error404');
Route::get('/error/500', [ErrorController::class, 'error500'])->name('error500');



Route::prefix('admin')->name('admin.')->middleware(['web', 'auth', 'verified'])->group(function () {
    
    // ========================================
    // GESTION DES MENUS
    // ========================================
    
    // Afficher la page de gestion des menus
    Route::get('/menus', [\App\Http\Controllers\Admin\MenusController::class, 'index'])
        ->name('menus.index');
    
    // Créer un nouveau menu (POST)
    Route::post('/menus', [\App\Http\Controllers\Admin\MenusController::class, 'store'])
        ->name('menus.store');
    
    // Mettre à jour un menu (PUT/PATCH)
    Route::put('/menus/{menu}', [\App\Http\Controllers\Admin\MenusController::class, 'update'])
        ->name('menus.update')
        ->whereNumber('menu');
    
    // Supprimer un menu (DELETE)
    Route::delete('/menus/{menu}', [\App\Http\Controllers\Admin\MenusController::class, 'destroy'])
        ->name('menus.destroy')
        ->whereNumber('menu');
    
    // Basculer la visibilité d'un menu
    Route::patch('/menus/{menu}/toggle-visibility', [\App\Http\Controllers\Admin\MenusController::class, 'toggleVisibility'])
        ->name('menus.toggleVisibility')
        ->whereNumber('menu');
    
    // Mise à jour en masse (ordre, visibilité)
    Route::post('/menus/batch-update', [\App\Http\Controllers\Admin\MenusController::class, 'batchUpdate'])
        ->name('menus.batchUpdate');
    
    // Réorganiser (drag-drop)
    Route::post('/menus/reorder', [\App\Http\Controllers\Admin\MenusController::class, 'reorder'])
        ->name('menus.reorder');
});


