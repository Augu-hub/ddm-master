<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Master\Module;

class ShareModuleContextMiddleware
{
    /**
     * Partage le contexte du module courant avec toutes les pages Inertia
     */
    public function handle(Request $request, Closure $next)
    {
        $moduleCode = session('current_module_code');
        $moduleId = session('current_module_id');

        Log::info('═════════════════════════════════════════════');
        Log::info('🎨 ShareModuleContext MIDDLEWARE START', [
            'session_module_code' => $moduleCode,
            'session_module_id' => $moduleId,
        ]);

        if ($moduleCode && $moduleId) {
            // Recharger le module complet depuis la base
            $module = Module::on('mysql')
                ->with('service')
                ->find($moduleId);

            if ($module) {
                // Partager avec Inertia
                Inertia::share('currentModule', [
                    'id' => $module->id,
                    'code' => $module->code,
                    'name' => $module->name,
                    'icon' => $module->icon,
                    'service' => $module->service ? [
                        'id' => $module->service->id,
                        'name' => $module->service->name,
                    ] : null,
                ]);

                Log::info('✅ Contexte module partagé avec Inertia', [
                    'module_id' => $module->id,
                    'module_code' => $module->code,
                    'module_name' => $module->name,
                    'service_name' => $module->service?->name,
                ]);
            } else {
                Log::warning('⚠️ Module introuvable en base', ['module_id' => $moduleId]);
                Inertia::share('currentModule', null);
            }
        } else {
            Log::info('ℹ️ Aucun module à partager (session vide)');
            Inertia::share('currentModule', null);
        }

        Log::info('🎨 ShareModuleContext MIDDLEWARE END');
        Log::info('═════════════════════════════════════════════');

        return $next($request);
    }
}