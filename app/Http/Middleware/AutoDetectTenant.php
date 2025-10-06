<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutoDetectTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return $next($request);
        }

        // Si c'est l'admin global, pas de tenant spécifique
        if ($this->isGlobalAdmin($user)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(null);
            return $next($request);
        }

        // Si un tenant est déjà en session, l'utiliser
        $tenantId = session('tenant_id');
        if ($tenantId) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
            return $next($request);
        }

        // Auto-sélection du tenant si un seul disponible
        $tenants = $user->tenants;
        if ($tenants->count() === 1) {
            $tenantId = $tenants->first()->id;
            session(['tenant_id' => $tenantId]);
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        }

        return $next($request);
    }

    private function isGlobalAdmin($user): bool
    {
        return $user->email === 'admin@diaddem.local' || 
               $user->hasRole('super-admin');
    }
}