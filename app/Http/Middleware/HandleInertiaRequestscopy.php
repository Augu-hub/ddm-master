<?php

namespace App\Http\Middleware;

use App\Models\Master\Module;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequestscopy extends Middleware
{
    public function share(Request $request): array
    {
        // Quote
        $rawQuote = Inspiring::quote();
        $message  = Str::of($rawQuote)->beforeLast(' - ')->trim();
        $author   = Str::of($rawQuote)->afterLast(' - ')->trim();

        // User + décorations (tenant courant & admin global)
        $user   = $request->user();
        $tenant = null;

        if ($user) {
            try {
                $t = $user->currentTenant; // si tu utilises session('tenant_id')
                if ($t) {
                    $tenant = ['id' => $t->id, 'name' => $t->name];
                }
            } catch (\Throwable $e) {
                Log::warning('HandleInertiaRequests: tenant courant indisponible', [
                    'user_id' => $user->id ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // Super admin = session flag OU rôle Spatie
        $isGlobalAdmin =
            (bool) session('is_global_admin', false)
            || (method_exists($user ?? null, 'hasRole') && $user?->hasRole('super-admin'));

        return array_merge(parent::share($request), [
            'name'  => config('app.name'),

            'quote' => [
                'message' => (string) $message,
                'author'  => (string) $author,
            ],

            'auth' => [
                'user' => $user ? [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'avatar_url'      => method_exists($user, 'getAvatarUrlAttribute') ? $user->avatar_url : null,
                    'is_global_admin' => $isGlobalAdmin,
                    'tenant'          => $tenant,
                ] : null,
            ],

            'ziggy' => array_merge((new Ziggy)->toArray(), [
                'location' => $request->url(),
            ]),

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',

            'entities' => [],

            'csrf' => csrf_token(),

            // ⬇️ Modules visibles par l’utilisateur (lazy)
            // Inertia ne résout ce prop que si la page le demande (partial reload `only: ['modules']`)
            'modules' => Inertia::lazy(fn () => $this->modulesFor($request, $isGlobalAdmin)),
        ]);
    }

    /**
     * Retourne la liste des modules pour l’utilisateur courant.
     * - Super admin → tous les modules actifs
     * - Sinon → modules actifs liés via pivot module_user
     */
    private function modulesFor(Request $request, bool $isGlobalAdmin): array
    {
        $user = $request->user();
        if (! $user) {
            return [];
        }

        // pendant les premières migrations / bootstrap, la table peut ne pas exister
        try {
            if (! Schema::connection('mysql')->hasTable('modules')) {
                return [];
            }
        } catch (\Throwable $e) {
            Log::notice('modulesFor: DB not ready: '.$e->getMessage());
            return [];
        }

        $q = Module::with('service')->where('is_active', true)->orderBy('sort');

        if (! $isGlobalAdmin) {
            $q->whereHas('users', fn ($x) => $x->where('user_id', $user->id));
        }

        return $q->get(['id','code','name','entry_route_name','service_id'])
            ->map(fn ($m) => [
                'code'        => $m->code,
                'name'        => $m->name,
                'entry_route' => $m->entry_route_name,
                'service'     => ['name' => optional($m->service)->name],
            ])
            ->all();
    }
}

