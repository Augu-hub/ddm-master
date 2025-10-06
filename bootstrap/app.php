<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // ⚠️ si tu as un routes/api.php, ajoute: api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ alias (clé => classe) pour l’utiliser dans tes routes: ->middleware('menu.authz:permissions')
        $middleware->alias([
            'menu.authz' => \App\Http\Middleware\AuthorizeMenu::class,
        ]);

        // tes middlewares web (après StartSession)
        $middleware->web(append: [
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\UseTenantDatabase::class,
            \App\Http\Middleware\HandleInertiaRequests::class, // doit être ici
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
