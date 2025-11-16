<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Fallback: si Sanctum n'est pas installé, on utilise 'auth'
$apiAuth = data_get(config('auth.guards'), 'sanctum') ? 'auth:sanctum' : 'auth';

// Exemple: endpoint user (protégé)
Route::middleware([$apiAuth])->get('/user', function (Request $request) {
    return $request->user();
});

// Ajoute ici tes endpoints API...
// Route::middleware([$apiAuth])->get('/ping', fn() => ['ok' => true]);
