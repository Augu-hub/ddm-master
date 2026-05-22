<?php

namespace App\Http\Middleware;

use App\Services\Audit\UserMenuSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log, Session};

/**
 * ══════════════════════════════════════════════════════════════════
 *  Middleware LoadUserMenus
 * ══════════════════════════════════════════════════════════════════
 *  À enregistrer dans bootstrap/app.php ou Kernel.php.
 *  S'assure que la session user_menus est toujours peuplée
 *  pour les utilisateurs authentifiés.
 *
 *  Usage dans les routes :
 *     Route::middleware(['auth', 'load.user.menus'])->group(...)
 *
 *  Ou globalement dans le groupe web après auth.
 * ══════════════════════════════════════════════════════════════════
 */
class LoadUserMenus
{
    public function handle(Request $request, Closure $next)
    {
        // Uniquement pour les utilisateurs authentifiés
        if (!Auth::check()) {
            return $next($request);
        }

        // Ne recharger que si nécessaire (cache TTL géré dans le service)
        // On passe le nom de la DB tenant depuis la session si disponible
        $tenantDb = Session::get('tenant_db')
            ?? Session::get('current_tenant_db')
            ?? null;

        // Charger en session (cache-aware : ne recharge pas si encore valide)
        UserMenuSessionService::getOrLoad($tenantDb);

        return $next($request);
    }
}