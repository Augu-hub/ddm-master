<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Master\Menu;
use App\Models\Master\Module;

class NavMenuController extends Controller
{
    /**
     * Retourne la liste des modules accessibles par l'utilisateur connecté
     */
    public function myModules(Request $request)
    {
        Log::info('═════════════════════════════════════════════');
        Log::info('📋 NavMenu@myModules START', [
            'user_id' => auth()->id(),
            'is_global_admin' => session('is_global_admin'),
        ]);

        $user = auth()->user();

        if (!$user) {
            Log::warning('⚠️ Utilisateur non authentifié');
            return response()->json(['modules' => []]);
        }

        // Admin global : tous les modules actifs
        if (session('is_global_admin')) {
            $modules = Module::on('mysql')
                ->with('service:id,name')
                ->where('is_active', true)
                ->orderBy('sort')
                ->get(['id', 'code', 'name', 'icon', 'entry_route_name', 'service_id']);

            Log::info('👑 Admin global : tous les modules', [
                'count' => $modules->count(),
                'codes' => $modules->pluck('code')->toArray(),
            ]);
            
            return response()->json([
                'modules' => $modules->map(fn($m) => [
                    'code' => $m->code,
                    'name' => $m->name,
                    'icon' => $m->icon,
                    'entry_route' => $m->entry_route_name,
                    'service' => $m->service ? ['name' => $m->service->name] : null,
                ])
            ]);
        }

        // Utilisateur standard : modules assignés via module_user
        // 🔧 FIX : Qualifier la colonne 'id' avec le nom de la table
        $modules = $user->modules()
            ->with('service:id,name')
            ->where('modules.is_active', true)
            ->orderBy('modules.sort')
            ->get(['modules.id', 'modules.code', 'modules.name', 'modules.icon', 'modules.entry_route_name', 'modules.service_id']);

        Log::info('✅ Modules trouvés pour utilisateur', [
            'user_id' => $user->id,
            'count' => $modules->count(),
            'codes' => $modules->pluck('code')->toArray(),
        ]);
        Log::info('📋 NavMenu@myModules END');
        Log::info('═════════════════════════════════════════════');

        return response()->json([
            'modules' => $modules->map(fn($m) => [
                'code' => $m->code,
                'name' => $m->name,
                'icon' => $m->icon,
                'entry_route' => $m->entry_route_name,
                'service' => $m->service ? ['name' => $m->service->name] : null,
            ])
        ]);
    }

    /**
     * Retourne la structure du menu pour le module courant
     */
    public function structure(Request $request)
    {
        $moduleCode = $request->query('module') ?? session('current_module_code');

        Log::info('═════════════════════════════════════════════');
        Log::info('🏗️ NavMenu@structure START', [
            'query_module' => $request->query('module'),
            'session_module' => session('current_module_code'),
            'module_code_used' => $moduleCode,
        ]);

        if (!$moduleCode) {
            Log::warning('⚠️ Aucun module spécifié');
            Log::info('🏗️ NavMenu@structure END (vide)');
            Log::info('═════════════════════════════════════════════');
            return response()->json(['data' => []]);
        }

        // Charger le module
        $module = Module::on('mysql')
            ->where('code', $moduleCode)
            ->first();

        if (!$module) {
            Log::warning('⚠️ Module introuvable', ['code' => $moduleCode]);
            Log::info('🏗️ NavMenu@structure END (module non trouvé)');
            Log::info('═════════════════════════════════════════════');
            return response()->json(['data' => []]);
        }

        Log::info('✅ Module trouvé', [
            'id' => $module->id,
            'code' => $module->code,
            'name' => $module->name,
        ]);

        // Charger les menus racine du module
        $menus = Menu::on('mysql')
            ->whereNull('parent_id')
            ->where('module_id', $module->id)
            ->where('visible', true)
            ->orderBy('sort')
            ->with(['children' => function ($q) {
                $q->where('visible', true)->orderBy('sort');
            }])
            ->get();

        Log::info('✅ Menus chargés', [
            'module_code' => $moduleCode,
            'menu_count' => $menus->count(),
            'menu_keys' => $menus->pluck('key')->toArray(),
        ]);

        // Transformer en format attendu par le frontend
        $structure = $menus->map(function ($menu) {
            return $this->transformMenuNode($menu);
        });

        Log::info('✅ Structure transformée', [
            'items_count' => $structure->count(),
        ]);
        Log::info('🏗️ NavMenu@structure END');
        Log::info('═════════════════════════════════════════════');

        return response()->json(['data' => $structure]);
    }

    /**
     * Transforme un nœud de menu en format frontend
     */
    private function transformMenuNode(Menu $menu): array
    {
        $node = [
            'id' => $menu->id,
            'key' => $menu->key,
            'label' => $menu->label,
            'type' => $menu->type,
            'icon' => $menu->icon,
            'url' => $menu->url,
            'route_name' => $menu->route_name,
            'target' => $menu->target,
            'sort' => $menu->sort,
            'badge_json' => $menu->badge_json,
            'tooltip_json' => $menu->tooltip_json,
            'meta_json' => $menu->meta_json,
        ];

        if ($menu->children && $menu->children->isNotEmpty()) {
            $node['children'] = $menu->children->map(function ($child) {
                return $this->transformMenuNode($child);
            })->toArray();
        }

        return $node;
    }

    /**
     * Retourne la visibilité des éléments de menu (permissions)
     */
    public function visibility(Request $request)
    {
        Log::info('🔐 NavMenu@visibility', [
            'user_id' => auth()->id(),
            'module' => $request->query('module') ?? session('current_module_code'),
        ]);

        // Admin global ou pas de restrictions : tout visible
        return response()->json(['visibility' => []]);
    }

    /**
     * Retourne les entités du module courant
     */
    public function getEntities(Request $request)
    {
        Log::info('🏢 NavMenu@getEntities', [
            'module' => $request->query('module') ?? session('current_module_code'),
        ]);

        // Pour l'instant, pas d'entités
        return response()->json(['entities' => []]);
    }
}
