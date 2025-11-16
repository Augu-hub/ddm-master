<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Master\Module;

class ModuleEntryController extends Controller
{
    /**
     * Point d'entrée pour un module
     * Gère les redirections intelligentes et les layouts personnalisés
     */
    public function show(Request $request, string $code)
    {
        $user = auth()->user();

        Log::info('═════════════════════════════════════════════');
        Log::info('🚀 ModuleEntry@show START', [
            'user_id' => $user?->id,
            'module_code' => $code,
            'url' => $request->fullUrl(),
            'query_go' => $request->query('go'),
        ]);

        // Charger le module depuis la base maître
        $module = Module::on('mysql')
            ->with('service')
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$module) {
            Log::warning('⚠️ Module introuvable ou inactif', ['code' => $code]);
            return redirect()->route('dashboard')
                ->with('error', 'Module introuvable');
        }

        Log::info('✅ Module chargé', [
            'id' => $module->id,
            'code' => $module->code,
            'name' => $module->name,
            'entry_route' => $module->entry_route_name,
        ]);

        // 🔥 GESTION DES REDIRECTIONS
        
        // Cas 1 : ?go=1 → Redirection vers entry_route si défini
        if ($request->query('go') === '1' && $module->entry_route_name) {
            Log::info('🔀 Redirection vers entry_route (go=1)', [
                'route' => $module->entry_route_name,
            ]);
            return redirect()->route($module->entry_route_name);
        }

        // Cas 2 : ?redirect=home → Redirection vers entry_route
        if ($request->query('redirect') === 'home' && $module->entry_route_name) {
            Log::info('🔀 Redirection vers home du module', [
                'route' => $module->entry_route_name,
            ]);
            return redirect()->route($module->entry_route_name);
        }

        // Cas 3 : ?redirect={custom_route} → Redirection personnalisée
        $customRedirect = $request->query('redirect');
        if ($customRedirect && $customRedirect !== 'home') {
            // Vérifier que la route existe
            $fullRouteName = "{$code}.{$customRedirect}";
            if (\Route::has($fullRouteName)) {
                Log::info('🔀 Redirection personnalisée', [
                    'route' => $fullRouteName,
                ]);
                return redirect()->route($fullRouteName);
            }
        }

        // Cas 4 : Pas de redirection → Afficher le shell du module
        Log::info('🎨 Affichage du shell module (pas de redirection)');
        
        // Déterminer le layout à utiliser pour ce module
        $layout = $this->getLayoutForModule($module);
        
        Log::info('📐 Layout sélectionné', [
            'layout' => $layout,
            'module_code' => $module->code,
        ]);

        Log::info('🚀 ModuleEntry@show END');
        Log::info('═════════════════════════════════════════════');

        return Inertia::render('Modules/ModuleShell', [
            'module' => [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'icon' => $module->icon,
                'service' => $module->service ? [
                    'id' => $module->service->id,
                    'name' => $module->service->name,
                ] : null,
            ],
            'layout' => $layout, // Layout personnalisé
        ]);
    }

    /**
     * Détermine le layout à utiliser pour un module
     */
    private function getLayoutForModule(Module $module): string
    {
        // Configuration des layouts par module
        $layoutMap = [
            'param.projects' => 'Layouts/ParamLayout',
            'audit.core' => 'Layouts/AuditLayout',
            'risk.core' => 'Layouts/RiskLayout',
            'process.core' => 'Layouts/ProcessLayout',
        ];

        // Retourner le layout personnalisé ou le layout par défaut
        return $layoutMap[$module->code] ?? 'Layouts/ModuleLayout';
    }
}
