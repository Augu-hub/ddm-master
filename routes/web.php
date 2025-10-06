<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/* ====== Controllers ====== */
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorController;

use App\Http\Controllers\Param\ProjetController;
use App\Http\Controllers\Param\MacroProcessusController;
use App\Http\Controllers\Param\ProcessusController;
use App\Http\Controllers\Param\ActiviteController;
use App\Http\Controllers\Param\EntiteController;
use App\Http\Controllers\Param\FonctionController;                // ✅ manquait
use App\Http\Controllers\Param\DistributionController;
use App\Http\Controllers\Param\ChartsController;
use App\Http\Controllers\Param\FunctionAssignmentController;
use App\Http\Controllers\Param\EntityFunctionsChartController;
use App\Http\Controllers\Param\MpsRespController;                 // ✅ nom aligné
use App\Http\Controllers\Admin\TenantsController;               // ✅ nouveau
use App\Http\Controllers\NavMenuController;

use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\RolesController;

/* ====== Auth scaffolding ====== */
require __DIR__.'/auth.php';

/* ====== Routes publiques Auth (exemples) ====== */
Route::get('/auth/login',    fn () => Inertia::render('auth/login'));
Route::get('/auth/register', fn () => Inertia::render('auth/register'));

/* =======================================================================
|  ZONE AUTH + VERIFIED
|======================================================================= */
Route::middleware(['web','auth','verified'])->group(function () {

    /* ===== Dashboards ===== */
    Route::get('/',                      [DashboardController::class, 'sales'])->name('dashboard');
    Route::get('/dashboards/param',      [DashboardController::class, 'salesparam'])->name('dashboard-param');
    Route::get('/dashboards/processus',  [DashboardController::class, 'salesparam'])->name('dashboard-processus');
    Route::get('/dashboards/risque',     [DashboardController::class, 'salesparam'])->name('dashboard-risque');
    Route::get('/dashboards/clinic',     [DashboardController::class, 'clinic']);
    Route::get('/dashboards/e-wallet',   [DashboardController::class, 'wallet']);

    /* ===== Pages d’erreur (Inertia) ===== */
    // Route::get('/error/400',     [ErrorController::class, 'error400']);
    // Route::get('/error/401',     [ErrorController::class, 'error401']);
    // Route::get('/error/403',     [ErrorController::class, 'error403']);
    // Route::get('/error/404',     [ErrorController::class, 'error404']);
    // Route::get('/error/404-alt', [ErrorController::class, 'error404Alt']);
    // Route::get('/error/408',     [ErrorController::class, 'error408']);
    // Route::get('/error/500',     [ErrorController::class, 'error500']);
    // Route::get('/error/501',     [ErrorController::class, 'error501']);
    // Route::get('/error/502',     [ErrorController::class, 'error502']);
    // Route::get('/error/503',     [ErrorController::class, 'error503']);

    /* ===================================================================
    |  PARAM
    |=================================================================== */
    Route::prefix('param')->name('param.')->group(function () {

        // === Projets / Entités / MPA (macro-processus) ===
        Route::resource('projects', ProjetController::class)->middleware('menu.authz:project');         // key=project
        Route::resource('entities', EntiteController::class)->middleware('menu.authz:entity');          // key=entity

        // MPA + Processus + Activités (CRUD “unitaires”)
        Route::get('/mpa', [MacroProcessusController::class,'index'])->name('mpa.index')->middleware('menu.authz:process');
        Route::post('/macro', [MacroProcessusController::class,'store'])->name('macro.store')->middleware('menu.authz:process');
        Route::post('/macro/validate-defaults', [MacroProcessusController::class,'validateDefaults'])->name('macro.validate')->middleware('menu.authz:process');
        Route::put('/macro/{macro}', [MacroProcessusController::class,'update'])->name('macro.update')->middleware('menu.authz:process');

        Route::post('/process', [ProcessusController::class,'store'])->name('process.store')->middleware('menu.authz:process');
        Route::put('/process/{process}', [ProcessusController::class,'update'])->name('process.update')->middleware('menu.authz:process');
        Route::delete('/process/{process}', [ProcessusController::class,'destroy'])->name('process.destroy')->middleware('menu.authz:process');

        Route::post('/activity', [ActiviteController::class,'store'])->name('activity.store')->middleware('menu.authz:process');
        Route::put('/activity/{activity}', [ActiviteController::class,'update'])->name('activity.update')->middleware('menu.authz:process');
        Route::delete('/activity/{activity}', [ActiviteController::class,'destroy'])->name('activity.destroy')->middleware('menu.authz:process');

        // === Fonctions (CRUD) ===
        Route::get('function',                [FonctionController::class,'index'])->name('function.index')->middleware('menu.authz:function');
        Route::post('function',               [FonctionController::class,'store'])->name('function.store')->middleware('menu.authz:function');
        Route::put('function/{function}',     [FonctionController::class,'update'])->name('function.update')->middleware('menu.authz:function');
        Route::delete('function/{function}',  [FonctionController::class,'destroy'])->name('function.destroy')->middleware('menu.authz:function');

        // === Distribution (MPA → Entité) ===
        Route::get('/distribution',           [DistributionController::class, 'index'])->name('distribution.index')->middleware('menu.authz:distribution');
        Route::get('/distribution/tree',      [DistributionController::class, 'tree'])->name('distribution.tree')->middleware('menu.authz:distribution');
        Route::get('/distribution/current',   [DistributionController::class, 'current'])->name('distribution.current')->middleware('menu.authz:distribution');
        Route::post('/distribution/preview',  [DistributionController::class, 'preview'])->name('distribution.preview')->middleware('menu.authz:distribution');
        Route::post('/distribution/commit',   [DistributionController::class, 'commit'])->name('distribution.commit')->middleware('menu.authz:distribution');

        // === Affectation Fonctions → Entité ===
        Route::get('/functions/list',     [FunctionAssignmentController::class, 'list'])->name('functions.list')->middleware('menu.authz:function');
        Route::get('/functions/current',  [FunctionAssignmentController::class, 'current'])->name('functions.current')->middleware('menu.authz:function');
        Route::post('/functions/commit',  [FunctionAssignmentController::class, 'commit'])->name('functions.commit')->middleware('menu.authz:function');

        // === MPS / Responsable ===
        Route::get('/mpsresp/tree',       [MpsRespController::class,'tree'])->name('mpsresp.tree')->middleware('menu.authz:process');
        Route::get('/mpsresp/current',    [MpsRespController::class,'current'])->name('mpsresp.current')->middleware('menu.authz:process');
        Route::post('/mpsresp/assign',    [MpsRespController::class,'assign'])->name('mpsresp.assign')->middleware('menu.authz:process');
        Route::post('/mpsresp/unassign',  [MpsRespController::class,'unassign'])->name('mpsresp.unassign')->middleware('menu.authz:process');

        // === Charts projet ===
        Route::get('/charts/entity',       [ChartsController::class, 'entity'])->name('charts.entity')->middleware('menu.authz:entity_chart');
        Route::get('/charts/function',     [ChartsController::class, 'function'])->name('charts.function')->middleware('menu.authz:function_chart');
        Route::get('/charts/distribution',[ChartsController::class, 'distribution'])->name('charts.distribution'); // si mappé, ajoute menu.authz

        // === Charts entité (vue + API JSON) ===
        Route::prefix('charts')->name('charts.')->group(function () {
            // Page (sans ou avec entité pré-sélectionnée). Protégé par le menu key parent "charts_entity".
            Route::get('/entity-functions',                [EntityFunctionsChartController::class,'index'])->name('entity.functions.index')->middleware('menu.authz:charts_entity');
            Route::get('/entity-functions/{entity}',       [EntityFunctionsChartController::class,'index'])->name('entity.functions.show')->middleware('menu.authz:charts_entity');

            // API JSON
            Route::get('/entity-functions-data',           [EntityFunctionsChartController::class,'getChartData'])->name('entity.functions.data');
            Route::get('/entity-functions-data/{entity?}', [EntityFunctionsChartController::class,'getChartData'])->name('entity.functions.data.byEntity');
            Route::get('/entity-functions-stats',          [EntityFunctionsChartController::class,'getStats'])->name('entity.functions.stats');
        });

        // === Enfants pour remplir la section "Organigrammes Entité" dynamique dans le menu
        Route::get('/entities-menu-children', [EntiteController::class,'menuChildren'])->name('entities.menu-children');
    });

    /* ====== Nav dynamic (liste des entités pour le menu) ====== */
    Route::get('/api/menu/entities', [NavMenuController::class, 'getEntities'])->name('api.menu.entities');
    
    /* ====== Admin: Permissions/Roles ====== */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/permissions',            [PermissionsController::class,'index'])->name('permissions.index')->middleware('menu.authz:permissions');
        Route::post('/permissions',           [PermissionsController::class,'store'])->name('permissions.store')->middleware('menu.authz:permissions');
        Route::post('/permissions/sync-role', [PermissionsController::class,'syncRole'])->name('permissions.syncRole')->middleware('menu.authz:permissions');
        Route::post('/permissions/sync-user', [PermissionsController::class,'syncUser'])->name('permissions.syncUser')->middleware('menu.authz:permissions');

        Route::get('/roles',                  [RolesController::class,'index'])->name('roles.index')->middleware('menu.authz:roles');
        Route::post('/roles',                 [RolesController::class,'store'])->name('roles.store')->middleware('menu.authz:roles');
        Route::post('/roles/sync-user',       [RolesController::class,'syncUserRoles'])->name('roles.syncUserRoles')->middleware('menu.authz:roles');
    });
});

// routes/web.php
Route::get('/error/403', [ErrorController::class, 'error403'])->name('error403');
Route::get('/error/404', [ErrorController::class, 'error404'])->name('error404');
Route::get('/error/500', [ErrorController::class, 'error500'])->name('error500');
// routes/web.php - Ajouter dans le groupe admin
// routes/web.php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/tenants', [TenantsController::class, 'index'])->name('tenants.index');
    Route::post('/tenants', [TenantsController::class, 'store'])->name('tenants.store');
    Route::put('/tenants/{tenant}', [TenantsController::class, 'update'])->name('tenants.update');
    Route::delete('/tenants/{tenant}', [TenantsController::class, 'destroy'])->name('tenants.destroy');
    Route::post('/tenants/{tenant}/sync-users', [TenantsController::class, 'syncUsers'])->name('tenants.syncUsers');
    Route::post('/tenants/{tenant}/remove-user', [TenantsController::class, 'removeUser'])->name('tenants.removeUser');
});
// routes/web.php - Ajoutez cette route temporaire
Route::get('/debug/menu', function() {
    $user = auth()->user();
    
    if (!$user) {
        return response()->json(['error' => 'Non connecté']);
    }

    // Vérifier les menus dans la base
    $menus = DB::table('menus')->get();
    
    // Vérifier les permissions du super admin
    $isSuperAdmin = $user->email === 'ademo@user.com';
    $permissions = $user->getAllPermissions()->pluck('name');
    
    return response()->json([
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            
            'permissions' => $permissions
        ],
        'menus_in_database' => $menus->count(),
        'menus' => $menus->take(5), // Premiers 5 menus
        'all_routes' => Route::getRoutes()->getRoutesByName()
    ]);
})->middleware(['auth']);