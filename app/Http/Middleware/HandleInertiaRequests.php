<?php

namespace App\Http\Middleware;

use App\Services\Audit\UserMenuSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Session};
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Données partagées avec TOUTES les pages Inertia.
     * user_menus est chargé ici une seule fois par requête.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [

            // ── Auth ────────────────────────────────────────────────────
            'auth' => fn () => [
                'user' => Auth::check() ? [
                    'id'    => Auth::id(),
                    'name'  => Auth::user()->name,
                    'email' => Auth::user()->email,
                ] : null,
            ],

            // ── Flash messages ──────────────────────────────────────────
            'flash' => fn () => [
                'success' => Session::get('success'),
                'error'   => Session::get('error'),
                'warning' => Session::get('warning'),
            ],

            // ── Menus ddmparam ──────────────────────────────────────────
            // Chargé depuis la session (cache TTL 60 min).
            // UserMenuSessionService::getOrLoad() ne requête la DB
            // que si le cache est expiré ou absent.
            //
            // Structure : [ { mission_type: {...}, phases: [...] }, ... ]
            // Chaque form a 'available' = true|false selon url_path.
            'user_menus' => fn () => Auth::check()
                ? UserMenuSessionService::getOrLoad(
                    tenantDb: Session::get('tenant_db')
                        ?? Session::get('current_tenant_db')
                        ?? 'fruitiva',    // ← DB tenant par défaut
                )
                : [],

        ]);
    }
}