<?php

use Illuminate\Support\Facades\Route;

/* ========= API publique minimale ========= */
Route::get('/health', fn () => response()->json(['ok' => true]))->name('api.health');

/* ========= API protégée par session (guard web via middleware 'auth') ========= */
//use App\Http\Controllers\Api\NavMenuController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\ModuleAssignmentController;
use App\Http\Controllers\Param\EntiteController;

Route::middleware(['auth'])->group(function () {
    // ---- NAV / MENUS ----
   // Route::get('/nav/entities', [NavMenuController::class, 'entities'])->name('api.nav.entities');

    // ---- ENTITÉS (Param) ----
    Route::get('/entities/menu',        [EntiteController::class, 'getMenuEntities'])->name('api.entities.menu');
    Route::get('/param/entities/list',  [EntiteController::class, 'apiList'])->name('api.param.entities.list');

    // ---- SERVICES / MODULES (REST) ----
    Route::apiResource('services', ServiceController::class)
        ->parameters(['services' => 'service:code'])
        ->names('api.services');

    Route::apiResource('modules', ModuleController::class)
        ->parameters(['modules' => 'module:code'])
        ->names('api.modules');

    // Si tu exposes aussi le CRUD des menus via ce controller
    Route::apiResource('menus', MenuController::class)->names('api.menus');

    // ---- Assignations de modules ----
    Route::post('module-assignments',   [ModuleAssignmentController::class, 'store'])->name('api.module-assignments.store');
    Route::delete('module-assignments', [ModuleAssignmentController::class, 'destroy'])->name('api.module-assignments.destroy');

    // ---- Mes modules ----
    Route::get('me/modules', [ModuleController::class, 'myModules'])->name('api.me.modules');
});

Route::middleware(['auth'])->group(function () {
    // Structure complète du menu (arbre)
    Route::get('/menu/structure', [MenuController::class, 'getMenuStructure']);  // alias
    Route::get('/menu/visibility', [MenuController::class, 'getMenuVisibility']); // alias

    // Si ton front appelle aussi ces endpoints :
    Route::get('/menu/entities', [MenuController::class, 'getEntities']); // facultatif, si tu veux le centraliser ici
    Route::get('/me/modules',    [MenuController::class, 'myModules']);   // renvoie les modules visibles (super admin = tout)
});
