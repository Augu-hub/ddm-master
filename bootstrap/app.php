<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ===== Aliases de middlewares =====
        $middleware->alias([
            // Autorisations spécifiques (déjà en place)
            'module'     => \App\Http\Middleware\AuthorizeModule::class,
            'menu.authz' => \App\Http\Middleware\AuthorizeModule::class, // rétro-compat

            // ⬇️ Contexte “module courant” (sélection + partage ctx Inertia)
            'bind.module' => \App\Http\Middleware\BindModuleMiddleware::class,
            'share.module' => \App\Http\Middleware\ShareModuleContextMiddleware::class,
        
        ]);

        // ===== Groupe WEB (pile multi-tenant + Inertia) =====
        $middleware->web(append: [
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\UseTenantDatabase::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // ===== API stateful (sessions/cookies) + multi-tenant =====
        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,

            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\UseTenantDatabase::class,
        ]);

        // (On ne met pas VerifyCsrfToken ici pour l’API; ajoute-le si tu veux un /api CSRF-aware)
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
