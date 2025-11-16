<?php
// app/Http/Controllers/Api/MenuController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\Menu;
use App\Models\Master\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    /** caches par requête (évite N+1) */
    private array $ctxModuleCodes = [];
    private array $ctxMenuIds     = [];
    private array $ctxPerms       = [];

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

    /** Pré-chauffe le contexte d’autorisations (sur mysql) */
    private function warmAuthContext($user): void
    {
        if (!$user) return;

        // modules du user via pivot module_user (connexion mysql via relation Eloquent)
        $this->ctxModuleCodes = $user->modules()
            ->pluck('modules.code')
            ->filter()
            ->values()
            ->all();

        // menus explicitement listés pour le user (menu_user) → connexion mysql
        $this->ctxMenuIds = DB::connection('mysql')
            ->table('menu_user')
            ->where('user_id', $user->id)
            ->pluck('menu_id')
            ->values()
            ->all();

        // permissions Spatie (config/permission.php => 'connection' => 'mysql')
        if (method_exists($user, 'getAllPermissions')) {
            $this->ctxPerms = $user->getAllPermissions()->pluck('name')->values()->all();
        } else {
            $this->ctxPerms = [];
        }
    }

    /** Construit l’arbre à partir des rows */
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
                    // méta contrôle
                    '_module_code'  => optional($n->module)->code,
                    '_service_code' => optional($n->service)->code,
                    '_has_perms'    => $n->permissions_count > 0,
                    '_has_users'    => $n->users_count > 0,
                    // enfants
                    'children'     => $make($n->id),
                ];
            }
            return $out;
        };

        return $make(null);
    }

    /** Filtre récursif : garder les noeuds du module ciblé (ou parents utiles) */
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

    /** (Optionnel) Filtrer par code service si présent */
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

    /** Vérifie la visibilité d’un nœud pour l’utilisateur courant (hors super-admin) */
    private function userCanSee(array $menu, $user, bool $isSuperAdmin): bool
    {
        if ($isSuperAdmin) return true;
        if (!$user) return false;

        // 1) contrainte module (soit il possède le module, soit permission {code}.view)
        if (!empty($menu['_module_code'])) {
            $code = $menu['_module_code'];

            $hasModule = in_array($code, $this->ctxModuleCodes, true);
            $hasPerm   = in_array($code . '.view', $this->ctxPerms, true);

            if (!$hasModule && !$hasPerm) {
                return false;
            }
        }

        // 2) contrainte ciblage users → l’id doit être dans menu_user
        if (!empty($menu['_has_users'])) {
            if (!in_array($menu['id'], $this->ctxMenuIds, true)) {
                return false;
            }
        }

        return true;
    }

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

    private function flattenKeys(array $nodes, array &$keys): void
    {
        foreach ($nodes as $n) {
            if (!empty($n['key'])) $keys[] = $n['key'];
            if (!empty($n['children'])) $this->flattenKeys($n['children'], $keys);
        }
    }

    // -------------------------------------------------------------
    // GET /api/menu/structure?module=...&service=...
    // -------------------------------------------------------------
    public function getMenuStructure(Request $request)
    {
        $user    = $request->user();
        $isSuper = $this->isSuperAdmin($user);
        $modCode = trim((string) $request->query('module', ''));
        $svcCode = trim((string) $request->query('service', ''));

        // warm caches (sur mysql)
        if (!$isSuper && $user) {
            $this->warmAuthContext($user);
        }

        // Arbre maître (mysql)
        $rows = Menu::query()
            ->with(['module','service'])
            ->withCount(['permissions','users'])
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

        $result = $isSuper ? $tree : $this->filterTreeForUser($tree, $user, false);

        return response()->json(['data' => $result]);
    }

    // -------------------------------------------------------------
    // GET /api/menu/visibility?module=...&service=...
    // -------------------------------------------------------------
    public function getMenuVisibility(Request $request)
    {
        $user    = $request->user();
        $isSuper = $this->isSuperAdmin($user);
        $modCode = trim((string) $request->query('module', ''));
        $svcCode = trim((string) $request->query('service', ''));

        if (!$isSuper && $user) {
            $this->warmAuthContext($user);
        }

        $rows = Menu::query()
            ->with(['module','service'])
            ->withCount(['permissions','users'])
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
            foreach ($node['children'] ?? [] as $child) $fill($child);
        };
        foreach ($tree as $n) $fill($n);

        if ($isSuper) {
            $visibility = array_fill_keys($keys, true);
        }

        return response()->json(['visibility' => $visibility]);
    }

    // -------------------------------------------------------------
    // GET /api/me/modules  (super admin = tout)
    // -------------------------------------------------------------
    public function myModules(Request $request)
    {
        $user    = $request->user();
        $isSuper = $this->isSuperAdmin($user);

        $q = Module::query()->where('is_active', true);

        if (!$isSuper && $user) {
            // filtre par pivot users (mysql)
            $q->whereHas('users', fn($uq) => $uq->where('user_id', $user->id));
        }

        $mods = $q->with('service:id,name')
                  ->orderBy('sort')
                  ->get(['id','code','name','entry_route_name as entry_route','service_id']);

        return response()->json([
            'modules' => $mods->map(fn($m) => [
                'code'        => $m->code,
                'name'        => $m->name,
                'entry_route' => $m->entry_route,
                'service'     => ['name' => optional($m->service)->name],
            ])->values(),
        ]);
    }

    // -------------------------------------------------------------
    // GET /api/menu/entities  (les entités sont côté TENANT)
    // -------------------------------------------------------------
    public function getEntities(Request $request)
    {
        try {
            $entities = DB::connection('tenant')
                ->table('entities')
                ->select('id','name')
                ->orderBy('name')
                ->get();

            return response()->json(['entities' => $entities]);
        } catch (\Throwable $e) {
            \Log::error('MenuController@getEntities tenant error', ['error'=>$e->getMessage()]);
            return response()->json(['entities' => []]);
        }
    }
}
