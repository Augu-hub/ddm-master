<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Master\Module;

class AuthorizeModule
{
    public function handle(Request $request, Closure $next, string $moduleCode)
    {
        $user = $request->user();
        if (! $user) abort(401);

        // ⬅️ Bypass total pour Super Admin / Global Admin
        $isGlobal = session('is_global_admin', false)
            || (method_exists($user, 'hasRole') && $user->hasRole('super-admin'));

        if ($isGlobal) {
            return $next($request);
        }

        $module = Module::where('code', $moduleCode)->where('is_active', true)->first();
        if (! $module) abort(404, 'Module introuvable');

        $hasPivot = $module->users()->where('user_id', $user->id)->exists();

        $hasPerm = false;
        if (method_exists($user, 'hasPermissionTo')) {
            try { $hasPerm = $user->hasPermissionTo($module->code.'.view'); } catch (\Throwable) {}
        }

        abort_unless($hasPivot || $hasPerm, 403, 'Accès refusé à ce module');

        return $next($request);
    }
}
