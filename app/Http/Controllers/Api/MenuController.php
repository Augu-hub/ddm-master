<?php
// app/Http/Controllers/Api/MenuController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\Menu;
use App\Models\Master\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MenuController extends Controller
{
    /** Caches par requête (évite N+1) */
    private array $ctxModuleCodes = [];
    private array $ctxMenuIds     = [];
    private array $ctxPerms       = [];

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * HELPERS - AUTHENTIFICATION & CONTEXTE
     * ════════════════════════════════════════════════════════════════════════════
     */

    private function isSuperAdmin($user): bool
    {
        if (!$user) return false;

        if (session('is_global_admin', false)) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('Super Admin') || $user->hasRole('super-admin')) {
                return true;
            }
        }

        return false;
    }

    /** Pré-chauffe le contexte d'autorisations */
    private function warmAuthContext($user): void
    {
        if (!$user) return;

        // Modules du user via pivot module_user
        $this->ctxModuleCodes = $user->modules()
            ->pluck('modules.code')
            ->filter()
            ->values()
            ->all();

        // Menus explicitement listés pour le user (menu_user)
        $this->ctxMenuIds = DB::connection('mysql')
            ->table('menu_user')
            ->where('user_id', $user->id)
            ->pluck('menu_id')
            ->values()
            ->all();

        // Permissions Spatie
        if (method_exists($user, 'getAllPermissions')) {
            $this->ctxPerms = $user->getAllPermissions()->pluck('name')->values()->all();
        } else {
            $this->ctxPerms = [];
        }
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * TREE BUILDING
     * ════════════════════════════════════════════════════════════════════════════
     */

    /** Construit l'arbre hiérarchique à partir des rows */
    private function buildTree($rows)
    {
        $byParent = [];
        foreach ($rows as $row) {
            $byParent[$row->parent_id ?? 0][] = $row;
        }

        $make = function ($parentId) use (&$make, &$byParent) {
            $nodes = $byParent[$parentId ?? 0] ?? [];
            usort($nodes, fn ($a, $b) => ($a->sort ?? 0) <=> ($b->sort ?? 0));

            $out = [];
            foreach ($nodes as $n) {
                $out[] = [
                    'id'           => $n->id,
                    'key'          => $n->key,
                    'label'        => $n->label,
                    'type'         => $n->type,
                    'icon'         => $n->icon,
                    'url'          => $n->url,
                    'route_name'   => $n->route_name,
                    'target'       => $n->target,
                    'sort'         => $n->sort,
                    'badge_json'   => $n->badge_json,
                    'tooltip_json' => $n->tooltip_json,
                    'meta_json'    => $n->meta_json,
                    // Meta contrôle
                    '_module_code'  => optional($n->module)->code,
                    '_service_code' => optional($n->service)->code,
                    '_has_perms'    => $n->permissions_count > 0,
                    '_has_users'    => $n->users_count > 0,
                    // Enfants
                    'children'     => $make($n->id),
                ];
            }
            return $out;
        };

        return $make(null);
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * FILTERING - PAR MODULE, SERVICE, UTILISATEUR
     * ════════════════════════════════════════════════════════════════════════════
     */

    /** Filtre récursif : garder les nœuds du module ciblé (ou parents utiles) */
    private function filterTreeByModule(array $nodes, string $moduleCode): array
    {
        $out = [];
        foreach ($nodes as $n) {
            $keepSelf = ($n['_module_code'] ?? null) === $moduleCode;
            $children = $this->filterTreeByModule($n['children'] ?? [], $moduleCode);
            if ($keepSelf || count($children)) {
                $n['children'] = $children;
                $out[] = $n;
            }
        }
        return $out;
    }

    /** Filtre par code service */
    private function filterTreeByService(array $nodes, string $serviceCode): array
    {
        $out = [];
        foreach ($nodes as $n) {
            $keepSelf = ($n['_service_code'] ?? null) === $serviceCode;
            $children = $this->filterTreeByService($n['children'] ?? [], $serviceCode);
            if ($keepSelf || count($children)) {
                $n['children'] = $children;
                $out[] = $n;
            }
        }
        return $out;
    }

    /** Vérifie si l'utilisateur peut voir ce menu */
    private function userCanSee(array $menu, $user, bool $isSuperAdmin): bool
    {
        if ($isSuperAdmin) return true;
        if (!$user) return false;

        // 1) Contrainte module
        if (!empty($menu['_module_code'])) {
            $code = $menu['_module_code'];
            $hasModule = in_array($code, $this->ctxModuleCodes, true);
            $hasPerm   = in_array($code . '.view', $this->ctxPerms, true);

            if (!$hasModule && !$hasPerm) {
                return false;
            }
        }

        // 2) Contrainte ciblage users
        if (!empty($menu['_has_users'])) {
            if (!in_array($menu['id'], $this->ctxMenuIds, true)) {
                return false;
            }
        }

        return true;
    }

    /** Filtre l'arbre pour l'utilisateur actuel */
    private function filterTreeForUser(array $nodes, $user, bool $isSuperAdmin): array
    {
        $out = [];
        foreach ($nodes as $node) {
            $children = $this->filterTreeForUser($node['children'] ?? [], $user, $isSuperAdmin);
            $canSeeThis = $this->userCanSee($node, $user, $isSuperAdmin);

            if ($canSeeThis || count($children)) {
                $node['children'] = $children;
                $out[] = $node;
            }
        }
        return $out;
    }

    /** Récupère toutes les clés d'un arbre */
    private function flattenKeys(array $nodes, array &$keys): void
    {
        foreach ($nodes as $n) {
            if (!empty($n['key'])) $keys[] = $n['key'];
            if (!empty($n['children'])) $this->flattenKeys($n['children'], $keys);
        }
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════
     * PUBLIC API ENDPOINTS
     * ════════════════════════════════════════════════════════════════════════════
     */

    /**
     * GET /api/menu/structure?module=audit&service=...
     * 
     * Retourne la structure hiérarchique des menus filtrés
     */
    public function getMenuStructure(Request $request)
    {
        try {
            $user    = $request->user();
            $isSuper = $this->isSuperAdmin($user);
            $modCode = trim((string) $request->query('module', ''));
            $svcCode = trim((string) $request->query('service', ''));

            Log::info('📡 getMenuStructure', [
                'module'   => $modCode,
                'service'  => $svcCode,
                'user_id'  => optional($user)->id,
                'is_super' => $isSuper,
            ]);

            // Warm caches
            if (!$isSuper && $user) {
                $this->warmAuthContext($user);
            }

            // Charger l'arbre maître
            $rows = Menu::query()
                ->with(['module', 'service'])
                ->withCount(['permissions', 'users'])
                ->orderBy('parent_id')
                ->orderBy('sort')
                ->get();

            $tree = $this->buildTree($rows);

            // Filtrer par module
            if ($modCode !== '') {
                Log::info('🔍 Filtering by module', ['module' => $modCode]);
                $tree = $this->filterTreeByModule($tree, $modCode);
            }

            // Filtrer par service
            if ($svcCode !== '') {
                Log::info('🔍 Filtering by service', ['service' => $svcCode]);
                $tree = $this->filterTreeByService($tree, $svcCode);
            }

            // Filtrer par utilisateur
            $result = $isSuper ? $tree : $this->filterTreeForUser($tree, $user, false);

            Log::info('✅ getMenuStructure success', [
                'count' => count($result),
            ]);

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ getMenuStructure error', [
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/menu/visibility?module=audit&service=...
     * 
     * Retourne la visibilité des menus (key => boolean)
     */
    public function getMenuVisibility(Request $request)
    {
        try {
            $user    = $request->user();
            $isSuper = $this->isSuperAdmin($user);
            $modCode = trim((string) $request->query('module', ''));
            $svcCode = trim((string) $request->query('service', ''));

            Log::info('📡 getMenuVisibility', [
                'module'   => $modCode,
                'service'  => $svcCode,
            ]);

            if (!$isSuper && $user) {
                $this->warmAuthContext($user);
            }

            $rows = Menu::query()
                ->with(['module', 'service'])
                ->withCount(['permissions', 'users'])
                ->orderBy('parent_id')
                ->orderBy('sort')
                ->get();

            $tree = $this->buildTree($rows);

            if ($modCode !== '') {
                $tree = $this->filterTreeByModule($tree, $modCode);
            }
            if ($svcCode !== '') {
                $tree = $this->filterTreeByService($tree, $svcCode);
            }

            $keys = [];
            $this->flattenKeys($tree, $keys);

            $visibility = [];
            $fill = function(array $node) use (&$visibility, $user, $isSuper, &$fill) {
                $key = $node['key'] ?? null;
                if ($key) {
                    $visibility[$key] = $this->userCanSee($node, $user, $isSuper);
                }
                foreach ($node['children'] ?? [] as $child) {
                    $fill($child);
                }
            };

            foreach ($tree as $n) {
                $fill($n);
            }

            // Si super admin: tous les menus sont visibles
            if ($isSuper) {
                $visibility = array_fill_keys($keys, true);
            }

            Log::info('✅ getMenuVisibility success', [
                'count' => count($visibility),
            ]);

            return response()->json([
                'success'    => true,
                'visibility' => $visibility,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ getMenuVisibility error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/me/modules
     * 
     * Retourne les modules de l'utilisateur
     * (super admin = tous les modules)
     */
    public function myModules(Request $request)
    {
        try {
            $user    = $request->user();
            $isSuper = $this->isSuperAdmin($user);

            $q = Module::query()->where('is_active', true);

            if (!$isSuper && $user) {
                // Filtrer par pivot users
                $q->whereHas('users', fn($uq) => $uq->where('user_id', $user->id));
            }

            $mods = $q->with('service:id,name')
                      ->orderBy('sort')
                      ->get(['id', 'code', 'name', 'entry_route_name as entry_route', 'service_id']);

            return response()->json([
                'success' => true,
                'modules' => $mods->map(fn($m) => [
                    'code'        => $m->code,
                    'name'        => $m->name,
                    'entry_route' => $m->entry_route,
                    'service'     => ['name' => optional($m->service)->name],
                ])->values(),
            ]);
        } catch (\Exception $e) {
            Log::error('❌ myModules error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/menu/entities
     * 
     * Retourne les entités (tenant)
     */
    public function getEntities(Request $request)
    {
        try {
            $entities = DB::connection('tenant')
                ->table('entities')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success'  => true,
                'entities' => $entities,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ getEntities error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success'  => true,
                'entities' => [],
            ]);
        }
    }
}