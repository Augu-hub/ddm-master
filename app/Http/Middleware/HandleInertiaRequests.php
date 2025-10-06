<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        // Quote (format habituel: "message - author")
        $rawQuote = Inspiring::quote();
        $message  = Str::of($rawQuote)->beforeLast(' - ')->trim();
        $author   = Str::of($rawQuote)->afterLast(' - ')->trim();

        // User + décorations (tenant courant & admin global)
        $user   = $request->user();
        $tenant = null;

        if ($user) {
            try {
                $t = $user->currentTenant; // utilise session('tenant_id') si défini
                if ($t) {
                    $tenant = [
                        'id'   => $t->id,
                        'name' => $t->name,
                    ];
                }
            } catch (\Throwable $e) {
                \Log::warning('HandleInertiaRequests: tenant courant indisponible', [
                    'user_id' => $user->id ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // Construction de la charge partagée
        return array_merge(parent::share($request), [
            'name'  => config('app.name'),

            'quote' => [
                'message' => (string) $message,
                'author'  => (string) $author,
            ],

            'auth' => [
                'user' => $user ? [
                    'id'             => $user->id,
                    'name'           => $user->name,
                    'email'          => $user->email,
                    // Optionnel: expose un avatar si tu as un accessor `$user->avatar_url`
                    'avatar_url'     => method_exists($user, 'getAvatarUrlAttribute') ? $user->avatar_url : null,
                    // Du contrôleur d’auth: session('is_global_admin')
                    'is_global_admin'=> (bool) session('is_global_admin'),
                    // Tenant courant minimal (id, name)
                    'tenant'         => $tenant,
                ] : null,
            ],

            'ziggy' => array_merge((new Ziggy)->toArray(), [
                'location' => $request->url(),
            ]),

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',

            // Laisse vide pour éviter des hits DB intempestifs ici
            'entities' => [],

            // Pratique si tu veux l’exposer globalement
            'csrf' => csrf_token(),
        ]);
    }
}
