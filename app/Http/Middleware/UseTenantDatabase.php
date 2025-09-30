<?php

namespace App\Http\Middleware;

use App\Support\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\DatabasePresenceVerifier;

class UseTenantDatabase
{
    public function __construct(private TenantManager $tenants) {}

public function handle($request, \Closure $next)
{
    app(\App\Support\TenantManager::class)->connectOrFail();

    if (app()->bound('currentTenantId')) {
        // 1) que tout ce qui ne précise pas de connexion (validation…) parte sur 'tenant'
        DB::setDefaultConnection('tenant');

        // 2) la règle "unique"/"exists" utilisera aussi 'tenant'
        $verifier = app(DatabasePresenceVerifier::class);
        $verifier->setConnection('tenant');
        app('validator')->setPresenceVerifier($verifier);
    }

    return $next($request);
}

}
