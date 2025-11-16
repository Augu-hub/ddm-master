<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Param\ProjetController;
use App\Http\Controllers\Param\MacroProcessusController;
use App\Http\Controllers\Param\ProcessusController;
use App\Http\Controllers\Param\ActiviteController;
use App\Http\Controllers\Param\EntiteController;
use App\Http\Controllers\Param\FonctionController;
use App\Http\Controllers\Param\DistributionController;
use App\Http\Controllers\Param\ChartsController;
use App\Http\Controllers\Param\MpsRespController;
use App\Http\Controllers\Param\FunctionAssignmentController;
use App\Http\Controllers\Param\EntityFunctionsChartController;
use App\Http\Controllers\Param\FunctionUsersController;


/* ====== Accueil du module ====== */
Route::get('/', fn () => Inertia::render('dashboards/Param/Entities/index'))
     ->name('home');

/* ====== Projets ====== */
Route::resource('projects', ProjetController::class)->names([
    'index'   => 'projects.index',
    'create'  => 'projects.create',
    'store'   => 'projects.store',
    'show'    => 'projects.show',
    'edit'    => 'projects.edit',
    'update'  => 'projects.update',
    'destroy' => 'projects.destroy',
]);

/* ====== Entités ====== */
Route::resource('entities', EntiteController::class)->names([
    'index'   => 'entities.index',
    'create'  => 'entities.create',
    'store'   => 'entities.store',
    'show'    => 'entities.show',
    'edit'    => 'entities.edit',
    'update'  => 'entities.update',
    'destroy' => 'entities.destroy',
]);

/* ====== MPA (Macro-Processus-Activités) ====== */
Route::resource('mpa', MacroProcessusController::class)->names([
    'index'   => 'mpa.index',
    'create'  => 'mpa.create',
    'store'   => 'mpa.store',
    'show'    => 'mpa.show',
    'edit'    => 'mpa.edit',
    'update'  => 'mpa.update',
    'destroy' => 'mpa.destroy',
]);

/* ✅ Validation des macros par défaut ====== */
Route::post('macro/validate-defaults', [MacroProcessusController::class, 'validateDefaults'])
     ->name('macro.validate');

/* ====== Processus ====== */
Route::resource('processus', ProcessusController::class)->names([
    'index'   => 'processus.index',
    'create'  => 'processus.create',
    'store'   => 'processus.store',
    'show'    => 'processus.show',
    'edit'    => 'processus.edit',
    'update'  => 'processus.update',
    'destroy' => 'processus.destroy',
]);

/* ====== Activités ====== */
Route::resource('activites', ActiviteController::class)->names([
    'index'   => 'activites.index',
    'create'  => 'activites.create',
    'store'   => 'activites.store',
    'show'    => 'activites.show',
    'edit'    => 'activites.edit',
    'update'  => 'activites.update',
    'destroy' => 'activites.destroy',
]);

/* ====== Fonctions ====== */
Route::resource('fonctions', FonctionController::class)->names([
    'index'   => 'fonctions.index',
    'create'  => 'fonctions.create',
    'store'   => 'fonctions.store',
    'show'    => 'fonctions.show',
    'edit'    => 'fonctions.edit',
    'update'  => 'fonctions.update',
    'destroy' => 'fonctions.destroy',
]);
// ====== Fonctions → Entité (catalogue + affectations de fonctions) ======
    Route::get('functions/list',    [FunctionAssignmentController::class, 'list'])   ->name('functions.list');
    Route::get('functions/current', [FunctionAssignmentController::class, 'current'])->name('functions.current');
    Route::post('functions/commit', [FunctionAssignmentController::class, 'commit'])->name('functions.commit');

    // ====== Fonctions ↔ Utilisateurs (PAGE + API dédiée) ======
    Route::prefix('functions/users')->name('functions.users.')->group(function () {
        Route::get('/',        [FunctionUsersController::class,'index'])->name('index');   // page Inertia
        Route::get('search',   [FunctionUsersController::class,'search'])->name('search'); // ?q=
        Route::get('map',      [FunctionUsersController::class,'map'])   ->name('map');    // ?entity_id=
        Route::post('set',     [FunctionUsersController::class,'set'])   ->name('set');    // {entity_id,function_id,user_id|null}
        Route::post('clear',   [FunctionUsersController::class,'clear']) ->name('clear');  // {entity_id,function_id}
    });

/* ====== Distribution ====== */
Route::get('distribution', [DistributionController::class, 'index'])
     ->name('distribution.index');

             

        Route::get('distribution/tree',     [DistributionController::class, 'tree'])
            ->name('distribution.tree');

        Route::get('distribution/current',  [DistributionController::class, 'current'])
            ->name('distribution.current');

        Route::post('distribution/preview', [DistributionController::class, 'preview'])
            ->name('distribution.preview');
            

        Route::post('distribution/commit',  [DistributionController::class, 'commit'])
            ->name('distribution.commit');
        Route::get('functions/list',    [FunctionAssignmentController::class, 'list'])
            ->name('functions.list');
                    Route::get('functions/current', [FunctionAssignmentController::class, 'current'])
            ->name('functions.current');

        Route::post('functions/commit', [FunctionAssignmentController::class, 'commit'])
            ->name('functions.commit');
            
                    Route::get('mpsresp/tree',     [MpsRespController::class,'tree'])
            ->name('mpsresp.tree');

        Route::get('mpsresp/current',  [MpsRespController::class,'current'])
            ->name('mpsresp.current');

        Route::post('mpsresp/assign',  [MpsRespController::class,'assign'])
            ->name('mpsresp.assign');

        Route::post('mpsresp/unassign',[MpsRespController::class,'unassign'])
            ->name('mpsresp.unassign');
             // nouveaux endpoints pour la table d'ajout utilisateur
    Route::get('mpsresp/options',    [MpsRespController::class,'options'])->name('mpsresp.options');     // ?entity_id=
    Route::get('mpsresp/table',      [MpsRespController::class,'table'])->name('mpsresp.table');         // ?entity_id=&function_id=
    Route::post('mpsresp/save-users',[MpsRespController::class,'saveUsers'])->name('mpsresp.saveUsers');
 Route::get('mpsresp/map', [MpsRespController::class,'map'])->name('mpsresp.map');
/* ====== Charts ====== */
Route::get('charts/entity', [ChartsController::class, 'entity'])
     ->name('charts.entity');
Route::get('charts/function', [ChartsController::class, 'function'])
     ->name('charts.function');
Route::get('charts/entity-functions', [EntityFunctionsChartController::class, 'index'])
     ->name('charts.entity-functions');

     Route::prefix('param/projects/mpsresp')->name('param.projects.mpsresp.')->group(function () {
   
    // (tes routes existantes, si tu les utilises ailleurs)
    // Route::get('tree', [MpsRespController::class,'tree'])->name('tree');
    // Route::get('current', [MpsRespController::class,'current'])->name('current');
    // Route::post('assign', [MpsRespController::class,'assign'])->name('assign');
    // Route::post('unassign',[MpsRespController::class,'unassign'])->name('unassign');
});

Route::prefix('param/projects/functions')->name('param.projects.functions.')->group(function(){
   
    // 🔽 nouveaux endpoints user
    Route::get('users/search', [FunctionAssignmentController::class,'users'])   ->name('users');
    Route::get('users/map',    [FunctionAssignmentController::class,'userMap'])->name('userMap');
    Route::post('users/set',   [FunctionAssignmentController::class,'setUser'])->name('setUser');
    Route::post('users/clear', [FunctionAssignmentController::class,'clearUser'])->name('clearUser');
});


