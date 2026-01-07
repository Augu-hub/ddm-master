<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Master\Menu;
use App\Models\Master\Module;
use App\Models\Master\Service;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MenusController extends Controller
{
    // Protection unique : seul le super admin accède à tout ce contrôleur
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()?->email !== 'admin@diaddem.local') {
                abort(403, 'Accès réservé au super administrateur système.');
            }
            return $next($request);
        });
    }

    /**
     * ✨ Vue SUPER PRO avec arborescence COMPLÈTE + stats
     */
    public function index()
    {
        // ✅ CORRIGÉ: Charger TOUS les menus une seule fois
        $allMenus = Menu::all()->keyBy('id');

        // Construire l'arborescence complète (tous les niveaux!)
        $rootMenus = Menu::whereNull('parent_id')
            ->orderBy('sort')
            ->get()
            ->map(fn($menu) => $this->buildCompleteTree($menu, $allMenus))
            ->values()
            ->toArray();

        // Charger les stats
        $stats = $this->calculateStats();

        return Inertia::render('dashboards/Param/admin/Menus/index', [
            'menus'    => $rootMenus,
            'modules'  => Module::select('id', 'code', 'name')->orderBy('name')->get(),
            'stats'    => $stats,
        ]);
    }

    /** Vue tableau complet */
    public function tableView()
    {
        return Inertia::render('dashboards/Param/admin/Menus/TableView', [
            'modules'  => Module::select('id', 'code', 'name')->orderBy('name')->get(),
            'services' => Service::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * 📊 API: Tous les menus pour DataTable
     */
    public function getAllMenusJson()
    {
        $menus = Menu::with(['parent', 'module', 'service'])
            ->orderBy('sort')
            ->orderBy('label')
            ->get()
            ->map(function ($menu) {
                return [
                    'id'              => $menu->id,
                    'key'             => $menu->key,
                    'label'           => $menu->label,
                    'type'            => $menu->type,
                    'icon'            => $menu->icon,
                    'url'             => $menu->url,
                    'route_name'      => $menu->route_name,
                    'sort'            => $menu->sort,
                    'visible'         => (bool) $menu->visible,
                    'parent_id'       => $menu->parent_id,
                    'parent_label'    => $menu->parent?->label,
                    'module_id'       => $menu->module_id,
                    'module_name'     => $menu->module?->name,
                    'module_code'     => $menu->module?->code,
                    'service_id'      => $menu->service_id,
                    'service_name'    => $menu->service?->name,
                    'children_count'  => $menu->children()->count(),
                    'created_at'      => $menu->created_at?->format('d/m/Y H:i'),
                    'updated_at'      => $menu->updated_at?->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $menus,
            'count'   => $menus->count(),
        ]);
    }

    /**
     * 📊 API: Stats calculées (pour refresh rapide)
     */
    public function getStatsJson()
    {
        return response()->json([
            'success' => true,
            'data'    => $this->calculateStats(),
        ]);
    }

    /**
     * API : Infos base de données
     */
    public function getDatabaseInfoJson()
    {
        $tables = $this->getDatabaseTablesInfo();

        return response()->json([
            'success' => true,
            'data'    => $tables,
            'count'   => count($tables),
            'stats'   => [
                'total_tables'  => count($tables),
                'total_rows'    => array_sum(array_column($tables, 'rows')),
                'total_size_mb' => round(array_sum(array_column($tables, 'size_mb')), 2),
                'empty_tables'  => count(array_filter($tables, fn($t) => $t['rows'] === 0)),
            ],
        ]);
    }

    private function getDatabaseTablesInfo(): array
    {
        $database = config('database.connections.mysql.database');
        $results  = DB::select("
            SELECT 
                TABLE_NAME,
                TABLE_ROWS,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb,
                TABLE_COLLATION,
                TABLE_COMMENT,
                CREATE_TIME AS created_time,
                UPDATE_TIME AS update_time
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_NAME
        ", [$database]);

        return collect($results)->map(fn($row) => [
            'name'      => $row->TABLE_NAME,
            'rows'      => (int) $row->TABLE_ROWS,
            'size_mb'   => (float) $row->size_mb,
            'collation' => $row->TABLE_COLLATION,
            'comment'   => $row->TABLE_COMMENT,
            'created'   => $row->created_time ? date('d/m/Y H:i', strtotime($row->created_time)) : null,
            'updated'   => $row->update_time ? date('d/m/Y H:i', strtotime($row->update_time)) : null,
        ])->toArray();
    }

    /** Création */
    public function store(Request $request)
    {
        $data = $this->validateMenu($request);

        $this->validateJsonFields($data);

        $data['sort']    ??= $this->getNextSort($data['parent_id'] ?? null);
        $data['visible'] = $data['visible'] ?? true;

        $menu = Menu::create($data);

        return back()->with('success', "Menu '{$menu->label}' créé avec succès.");
    }

    /** Mise à jour */
    public function update(Request $request, Menu $menu)
    {
        $data = $this->validateMenu($request, $menu->id);

        $this->validateJsonFields($data);

        $data['visible'] = $data['visible'] ?? true;
        $menu->update($data);

        return back()->with('success', "Menu '{$menu->label}' mis à jour.");
    }

    /** Suppression (récursive avec tous les enfants) */
    public function destroy(Menu $menu)
    {
        $label      = $menu->label;
        $childCount = $this->countAllChildrenRecursive($menu);

        DB::transaction(function () use ($menu) {
            $this->deleteMenuRecursive($menu);
        });

        $msg = $childCount > 0
            ? "Menu '{$label}' et ses {$childCount} enfant(s) supprimés."
            : "Menu '{$label}' supprimé.";

        return back()->with('success', $msg);
    }

    /** Toggle visibilité */
    public function toggleVisibility(Menu $menu)
    {
        $menu->update(['visible' => !$menu->visible]);
        return back()->with('success', 'Visibilité modifiée.');
    }

    /** Batch update (ordre + visibilité) */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'menus' => 'required|array',
            'menus.*.id'      => 'required|exists:menus,id',
            'menus.*.sort'    => 'nullable|integer|min:0',
            'menus.*.visible' => 'nullable|boolean',
        ]);

        foreach ($request->menus as $item) {
            Menu::where('id', $item['id'])->update([
                'sort'    => $item['sort'] ?? null,
                'visible' => $item['visible'] ?? true,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Mise à jour groupée effectuée.']);
    }

    /** Réorganisation drag & drop */
    public function reorder(Request $request)
    {
        $request->validate([
            'menus' => 'required|array',
            'menus.*.id'        => 'required|exists:menus,id',
            'menus.*.parent_id' => 'nullable|exists:menus,id',
            'menus.*.position'  => 'required|integer',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->menus as $item) {
                Menu::where('id', $item['id'])->update([
                    'parent_id' => $item['parent_id'],
                    'sort'      => ($item['position'] + 1) * 10,
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Arborescence réorganisée.']);
    }

    /** Structure arbre complète (pour refresh rapide) */
    public function getTreeStructure()
    {
        // ✅ CORRIGÉ: Charger TOUS les menus
        $allMenus = Menu::all()->keyBy('id');

        $menus = Menu::whereNull('parent_id')
            ->orderBy('sort')
            ->get()
            ->map(fn($menu) => $this->buildCompleteTree($menu, $allMenus))
            ->values()
            ->toArray();

        return response()->json(['data' => $menus]);
    }

    /**
     * ✅ NOUVEAU: Construire l'arborescence COMPLÈTE (tous les niveaux!)
     */
    private function buildCompleteTree(Menu $menu, $allMenus): array
    {
        $formatted = $this->formatMenuForFrontend($menu);
        
        // Chercher les enfants directs (par parent_id)
        $children = $allMenus
            ->filter(fn($m) => $m->parent_id === $menu->id)
            ->sortBy('sort')
            ->map(fn($child) => $this->buildCompleteTree($child, $allMenus))
            ->values()
            ->toArray();
        
        $formatted['children'] = $children;
        
        return $formatted;
    }

    /**
     * 📊 Calculer les stats complètes
     */
    private function calculateStats(): array
    {
        $allMenus = Menu::all();
        
        $total       = $allMenus->count();
        $visible     = $allMenus->filter(fn($m) => $m->visible)->count();
        $hidden      = $total - $visible;
        $withChildren = $allMenus->filter(fn($m) => $allMenus->where('parent_id', $m->id)->count() > 0)->count();
        $withModule  = $allMenus->filter(fn($m) => $m->module_id)->count();

        // Calculer la profondeur max
        $maxDepth = 1;
        foreach ($allMenus->whereNull('parent_id') as $menu) {
            $maxDepth = max($maxDepth, $this->getMaxDepth($menu, $allMenus));
        }

        return [
            'total'        => $total,
            'visible'      => $visible,
            'hidden'       => $hidden,
            'withChildren' => $withChildren,
            'withModule'   => $withModule,
            'maxDepth'     => $maxDepth,
        ];
    }

    /**
     * Obtenir la profondeur max d'un menu
     */
    private function getMaxDepth(Menu $menu, $allMenus): int
    {
        $children = $allMenus->where('parent_id', $menu->id);
        
        if ($children->isEmpty()) {
            return 1;
        }
        
        return 1 + max($children->map(fn($child) => $this->getMaxDepth($child, $allMenus))->toArray());
    }

    /** Validation commune */
    private function validateMenu(Request $request, ?int $ignoreId = null)
    {
        return $request->validate([
            'key'         => ['required', 'string', 'max:100', Rule::unique('menus', 'key')->ignore($ignoreId)],
            'label'       => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:item,title,divider'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'url'         => ['nullable', 'string', 'max:255'],
            'route_name'  => ['nullable', 'string', 'max:255'],
            'target'      => ['nullable', 'in:_blank,_self'],
            'sort'        => ['nullable', 'integer', 'min:0'],
            'parent_id'   => ['nullable', 'exists:menus,id'],
            'module_id'   => ['nullable', 'exists:modules,id'],
            'service_id'  => ['nullable', 'exists:services,id'],
            'visible'     => ['nullable', 'boolean'],
            'badge_json'  => ['nullable', 'string'],
            'tooltip_json'=> ['nullable', 'string'],
            'meta_json'   => ['nullable', 'string'],
        ]);
    }

    /** Validation JSON */
    private function validateJsonFields(array $data)
    {
        foreach (['badge_json', 'tooltip_json', 'meta_json'] as $field) {
            if (!empty($data[$field] ?? null)) {
                json_decode($data[$field], true, 512, JSON_THROW_ON_ERROR);
            }
        }
    }

    /** Prochain ordre automatique */
    private function getNextSort(?int $parentId): int
    {
        $max = Menu::where('parent_id', $parentId)->max('sort');
        return ($max ?? 0) + 10;
    }

    /** Supprimer récursivement */
    private function deleteMenuRecursive(Menu $menu)
    {
        // Supprimer tous les enfants d'abord
        foreach ($menu->children as $child) {
            $this->deleteMenuRecursive($child);
        }

        // Nettoyer les liaisons
        DB::table('menu_user')->where('menu_id', $menu->id)->delete();
        DB::table('menu_permission')->where('menu_id', $menu->id)->delete();

        // Supprimer le menu
        $menu->delete();
    }

    /** Compter tous les enfants récursivement */
    private function countAllChildrenRecursive(Menu $menu): int
    {
        $count = $menu->children()->count();
        foreach ($menu->children as $child) {
            $count += $this->countAllChildrenRecursive($child);
        }
        return $count;
    }

    /** Formatage pour le frontend */
    private function formatMenuForFrontend(Menu $menu): array
    {
        return [
            'id'           => $menu->id,
            'key'          => $menu->key,
            'label'        => $menu->label,
            'type'         => $menu->type,
            'icon'         => $menu->icon,
            'url'          => $menu->url,
            'route_name'   => $menu->route_name,
            'target'       => $menu->target,
            'sort'         => $menu->sort,
            'visible'      => (bool) $menu->visible,
            'parent_id'    => $menu->parent_id,
            'module_id'    => $menu->module_id,
            'service_id'   => $menu->service_id,
            'module_name'  => $menu->module?->name,
            'badge_json'   => $menu->badge_json,
            'tooltip_json' => $menu->tooltip_json,
            'meta_json'    => $menu->meta_json,
            'children'     => [], // ← Sera rempli par buildCompleteTree()
        ];
    }
}