<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Master\Module;

class BindModuleMiddleware
{
    /**
     * Fixe le module courant en session à partir de :
     * - L'URL /m/{code}
     * - Le query param ?module=xxx
     * - La session existante
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        Log::info('═════════════════════════════════════════════');
        Log::info('🔵 BindModule MIDDLEWARE START', [
            'user_id' => $user?->id,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'route' => $request->route()?->getName(),
        ]);

        // 1. Tenter de récupérer le code module depuis l'URL ou query
        $code = $request->route('code') ?? $request->query('module');
        
        if ($code) {
            Log::info('📦 Code module détecté', ['code' => $code]);
            
            // Charger le module depuis la base maître
            $module = Module::on('mysql')
                ->where('code', $code)
                ->where('is_active', true)
                ->first();

            if (!$module) {
                Log::warning('⚠️ Module non trouvé ou inactif', ['code' => $code]);
                return redirect()->route('dashboard')
                    ->with('error', "Module {$code} introuvable ou inactif");
            }

            Log::info('✅ Module trouvé en base', [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
            ]);

            // Vérifier que l'utilisateur a accès à ce module
            if ($user && !$this->userHasAccessToModule($user, $module)) {
                Log::warning('🚫 Utilisateur sans accès au module', [
                    'user_id' => $user->id,
                    'module_code' => $code,
                ]);
                return redirect()->route('dashboard')
                    ->with('error', "Vous n'avez pas accès au module {$module->name}");
            }

            // Stocker en session
            session([
                'current_module_id' => $module->id,
                'current_module_code' => $module->code,
                'current_module_name' => $module->name,
                'current_service_id' => $module->service_id,
            ]);

            Log::info('✅ Module stocké en session', [
                'session_module_id' => session('current_module_id'),
                'session_module_code' => session('current_module_code'),
                'session_service_id' => session('current_service_id'),
            ]);
        } else {
            // Pas de code fourni : vérifier si on a déjà un module en session
            $sessionModuleCode = session('current_module_code');
            
            if ($sessionModuleCode) {
                Log::info('🔄 Module existant en session (pas de nouveau code)', [
                    'session_module_code' => $sessionModuleCode,
                    'session_module_id' => session('current_module_id'),
                ]);
            } else {
                Log::info('ℹ️ Aucun module en session ni dans URL/query');
            }
        }

        Log::info('🔵 BindModule MIDDLEWARE END');
        Log::info('═════════════════════════════════════════════');

        return $next($request);
    }

    /**
     * Vérifie si l'utilisateur a accès au module
     */
    private function userHasAccessToModule($user, Module $module): bool
    {
        // Admin global : accès à tout
        if (session('is_global_admin')) {
            Log::info('👑 Admin global : accès autorisé automatiquement');
            return true;
        }

        // Vérifier dans la table pivot module_user
        $hasAccess = $user->modules()
            ->where('modules.id', $module->id)
            ->exists();

        Log::info('🔍 Vérification accès module', [
            'user_id' => $user->id,
            'module_id' => $module->id,
            'module_code' => $module->code,
            'has_access' => $hasAccess ? 'OUI' : 'NON',
        ]);

        return $hasAccess;
    }
}