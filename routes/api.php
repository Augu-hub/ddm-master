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