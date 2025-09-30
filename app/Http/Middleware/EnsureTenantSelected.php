<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantSelected
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) return $next($request);

        if (!session()->has('tenant_id')) {
            // S'il a 1 seul tenant, on le pose automatiquement
            $tenantId = auth()->user()->tenants()->value('tenants.id');
            if ($tenantId) {
                session(['tenant_id' => $tenantId]);
            } else {
                // Aucun tenant -> page d’info / onboarding
                return redirect()->route('tenants.switch'); // page de choix/creation orga si tu l’as
            }
        }
        return $next($request);
    }
}
