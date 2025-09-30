<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureTenantSession
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && ! session()->has('tenant_id')) {
            // 1) d’abord, prendre le 1er tenant de l’utilisateur (pivot tenant_user en base maître)
            $firstTenantId = DB::connection('mysql')
                ->table('tenant_user')
                ->where('user_id', auth()->id())
                ->orderBy('tenant_id')
                ->value('tenant_id');

            // 2) fallback DEV: s’il n’a aucun tenant assigné, prendre le 1er de la table tenants
            if (! $firstTenantId) {
                $firstTenantId = DB::connection('mysql')->table('tenants')->min('id');
            }

            if ($firstTenantId) {
                session(['tenant_id' => (int) $firstTenantId]);
            }
        }

        return $next($request);
    }
}
