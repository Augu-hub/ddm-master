<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true]));
// routes/api.php

use App\Http\Controllers\Api\NavMenuController;

Route::get('/nav/entities', [NavMenuController::class, 'entities']);
// routes/tenant.php
Route::get('/param/entities/api/list', [\App\Http\Controllers\Param\EntiteController::class, 'apiList']);

// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/entities/menu', [EntiteController::class, 'getMenuEntities']);
});
// routes/web.php
use App\Http\Controllers\MenuVisibilityController;
Route::middleware(['web','auth'])->get('/api/menu/visibility', MenuVisibilityController::class);
// routes/api.php

use App\Http\Controllers\Api\MenuController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/menu/structure', [MenuController::class, 'getMenuStructure']);
    Route::get('/menu/visibility', [MenuController::class, 'getMenuVisibility']);
});

// Pour Inertia (web routes)
// routes/web.php
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/api/menu/structure', [MenuController::class, 'getMenuStructure']);
    Route::get('/api/menu/visibility', [MenuController::class, 'getMenuVisibility']);
});