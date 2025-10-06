<?php
namespace App\Http\Middleware;
use Closure;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionsTeam
{
    public function handle($request, Closure $next)
    {
        $tenantId = $request->session()->get('tenant_id'); // défini par ton admin quand il relie l'user à un tenant
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId ?: null);
        return $next($request);
    }
}
