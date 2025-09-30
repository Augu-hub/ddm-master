<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // ⚠️ ne mets pas "api" si tu n'as pas routes/api.php
    )
    ->withMiddleware(function (Middleware $middleware) {
        // tes middlewares web (après StartSession)
        $middleware->web(append: [
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\UseTenantDatabase::class,
             \App\Http\Middleware\HandleInertiaRequests::class, // Doit être ici
        ]);
    })
   
    ->withExceptions(function (Exceptions $exceptions) {
        // tu peux laisser vide
    })
    ->create();
