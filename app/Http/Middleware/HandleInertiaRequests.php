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
        // Petite citation “safe”
        $raw   = Inspiring::quote();
        $msg   = Str::of($raw)->beforeLast(' - ')->trim();
        $authr = Str::of($raw)->afterLast(' - ')->trim();

        // Utilisateur & tenant “light”
        $user   = $request->user();
        $tenant = null;

        if ($user) {
            try {
                // ton accessor ou relation courante (optionnel)
                $t = $user->currentTenant ?? null;
                if ($t) $tenant = ['id' => $t->id, 'name' => $t->name];
            } catch (\Throwable $e) {
                \Log::warning('HandleInertiaRequests: tenant courant indisponible', [
                    'user_id' => $user->id ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // >>> Module courant (depuis la session alimentée par ModuleEntryController)
        $currentModuleCode = (string) ($request->session()->get('current_module_code', ''));
        $currentModuleName = (string) ($request->session()->get('current_module_name', ''));

        return array_merge(parent::share($request), [
            'name'  => config('app.name'),

            'quote' => [
                'message' => (string) $msg,
                'author'  => (string) $authr,
            ],

            'auth' => [
                'user' => $user ? [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'avatar_url'      => method_exists($user, 'getAvatarUrlAttribute') ? $user->avatar_url : null,
                    'is_global_admin' => (bool) $request->session()->get('is_global_admin', false),
                    'tenant'          => $tenant,
                ] : null,
            ],

            'ziggy' => array_merge((new Ziggy)->toArray(), [
                'location' => $request->url(),
            ]),

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',

            // Expose proprement le module courant au front (Shell.vue & helpers)
            'currentModuleCode' => fn () => $currentModuleCode ?: null,
            'currentModuleName' => fn () => $currentModuleName ?: null,

            // Évite les hits inutiles ici
            'entities' => [],

            'csrf' => csrf_token(),
        ]);
    }
}
