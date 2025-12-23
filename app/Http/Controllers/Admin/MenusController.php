<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\Rule;
use App\Models\Master\Menu;
use App\Models\Master\Module;
use App\Models\Master\Service;

class MenusController extends Controller
{
    /**
     * Affiche la page de gestion des menus avec l'arborescence complète
     */
    public function index()
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        // Récupérer les menus racine uniquement (parent_id = null)
        $rootMenus = Menu::whereNull('parent_id')
            ->with('children:id,key,label,type,icon,url,route_name,module_id,service_id,visible,sort,parent_id')
            ->orderBy('sort')
            ->get()
            ->map(fn($menu) => $this->formatMenuForFrontend($menu));

        $modules = Module::select('id', 'code', 'name')->get();
        $services = Service::select('id', 'name')->get();

        return Inertia::render('dashboards/Param/admin/Menus/index', [
            'menus' => $rootMenus,
            'modules' => $modules,
            'services' => $services,
        ]);
    }

    /**
     * Crée un nouveau menu ou sous-menu
     */
    public function store(Request $request)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:menus,key'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:item,title,divider'],
            'icon' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:255'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'in:_blank,_self'],
            'sort' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'module_id' => ['nullable', 'exists:modules,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'visible' => ['nullable', 'boolean'],
            'badge_json' => ['nullable', 'string'],
            'tooltip_json' => ['nullable', 'string'],
            'meta_json' => ['nullable', 'string'],
        ]);

        try {
            // Valider les JSONs s'ils sont fournis
            if (!empty($data['badge_json'])) {
                json_decode($data['badge_json'], true, 512, JSON_THROW_ON_ERROR);
            }
            if (!empty($data['tooltip_json'])) {
                json_decode($data['tooltip_json'], true, 512, JSON_THROW_ON_ERROR);
            }
            if (!empty($data['meta_json'])) {
                json_decode($data['meta_json'], true, 512, JSON_THROW_ON_ERROR);
            }

            // Déterminer le sort automatiquement si non fourni
            if (empty($data['sort'])) {
                $parentId = $data['parent_id'] ?? null;
                $maxSort = Menu::where('parent_id', $parentId)->max('sort') ?? 0;
                $data['sort'] = $maxSort + 10;
            }

            $data['visible'] = $data['visible'] ?? true;

            $menu = Menu::create($data);

            return redirect()
                ->route('admin.menus.index')
                ->with('success', "Menu '{$menu->label}' créé avec succès");

        } catch (\JsonException $e) {
            return back()->withInput()->with('error', 'Erreur: JSON invalide — ' . $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('Erreur création menu: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Met à jour un menu existant
     */
    public function update(Request $request, Menu $menu)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', Rule::unique('menus')->ignore($menu->id)],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:item,title,divider'],
            'icon' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:255'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'in:_blank,_self'],
            'sort' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'module_id' => ['nullable', 'exists:modules,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'visible' => ['nullable', 'boolean'],
            'badge_json' => ['nullable', 'string'],
            'tooltip_json' => ['nullable', 'string'],
            'meta_json' => ['nullable', 'string'],
        ]);

        try {
            // Valider les JSONs
            if (!empty($data['badge_json'])) {
                json_decode($data['badge_json'], true, 512, JSON_THROW_ON_ERROR);
            }
            if (!empty($data['tooltip_json'])) {
                json_decode($data['tooltip_json'], true, 512, JSON_THROW_ON_ERROR);
            }
            if (!empty($data['meta_json'])) {
                json_decode($data['meta_json'], true, 512, JSON_THROW_ON_ERROR);
            }

            $data['visible'] = $data['visible'] ?? true;

            $menu->update($data);

            return redirect()
                ->route('admin.menus.index')
                ->with('success', "Menu '{$menu->label}' mis à jour avec succès");

        } catch (\JsonException $e) {
            return back()->withInput()->with('error', 'Erreur: JSON invalide — ' . $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('Erreur mise à jour menu: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Supprime un menu et ses enfants
     */
    public function destroy(Menu $menu)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        try {
            $label = $menu->label;
            $childCount = $menu->children()->count();

            // Supprimer les enfants d'abord
            $menu->children()->delete();
            // Supprimer les liaisons
            DB::table('menu_user')->where('menu_id', $menu->id)->delete();
            DB::table('menu_permission')->where('menu_id', $menu->id)->delete();
            // Supprimer le menu
            $menu->delete();

            $message = $childCount > 0 
                ? "Menu '{$label}' et ses {$childCount} sous-menu(s) supprimés avec succès"
                : "Menu '{$label}' supprimé avec succès";

            return redirect()
                ->route('admin.menus.index')
                ->with('success', $message);

        } catch (\Throwable $e) {
            \Log::error('Erreur suppression menu: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Mise à jour en masse des menus (ordre, visibilité, etc.)
     */
    public function batchUpdate(Request $request)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        $data = $request->validate([
            'menus' => ['required', 'array'],
            'menus.*.id' => ['required', 'exists:menus,id'],
            'menus.*.sort' => ['nullable', 'integer'],
            'menus.*.visible' => ['nullable', 'boolean'],
        ]);

        try {
            foreach ($data['menus'] as $menuData) {
                Menu::where('id', $menuData['id'])->update([
                    'sort' => $menuData['sort'] ?? null,
                    'visible' => $menuData['visible'] ?? true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Menus mis à jour avec succès'
            ]);

        } catch (\Throwable $e) {
            \Log::error('Erreur batch update menus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réorganiser les menus (drag-drop)
     */
    public function reorder(Request $request)
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403, 'Accès réservé aux administrateurs système');
        }

        $data = $request->validate([
            'menus' => ['required', 'array'],
            'menus.*.id' => ['required', 'exists:menus,id'],
            'menus.*.parent_id' => ['nullable', 'exists:menus,id'],
            'menus.*.position' => ['required', 'integer'],
        ]);

        try {
            DB::transaction(function () use ($data) {
                foreach ($data['menus'] as $menuData) {
                    Menu::where('id', $menuData['id'])->update([
                        'parent_id' => $menuData['parent_id'],
                        'sort' => ($menuData['position'] + 1) * 10,
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Arborescence mise à jour avec succès'
            ]);

        } catch (\Throwable $e) {
            \Log::error('Erreur réorganisation menus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les menus avec enfants (API)
     */
    public function getTreeStructure()
    {
        if (auth()->user()->email !== 'admin@diaddem.local') {
            abort(403);
        }

        $menus = Menu::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('sort');
            }])
            ->orderBy('sort')
            ->get()
            ->map(fn($menu) => $this->formatMenuForFrontend($menu));

        return response()->json(['data' => $menus]);
    }

    /**
     * Formate un menu pour le frontend (avec enfants)
     */
    private function formatMenuForFrontend($menu, $level = 0)
    {
        $formatted = [
            'id' => $menu->id,
            'key' => $menu->key,
            'label' => $menu->label,
            'type' => $menu->type,
            'icon' => $menu->icon,
            'url' => $menu->url,
            'route_name' => $menu->route_name,
            'target' => $menu->target,
            'sort' => $menu->sort,
            'visible' => (bool)$menu->visible,
            'parent_id' => $menu->parent_id,
            'module_id' => $menu->module_id,
            'service_id' => $menu->service_id,
            'badge_json' => $menu->badge_json,
            'tooltip_json' => $menu->tooltip_json,
            'meta_json' => $menu->meta_json,
        ];

        // Charger les enfants si nécessaire
        if ($menu->relationLoaded('children') && $menu->children->isNotEmpty()) {
            $formatted['children'] = $menu->children
                ->map(fn($child) => $this->formatMenuForFrontend($child, $level + 1))
                ->sortBy('sort')
                ->values()
                ->toArray();
        } else {
            $formatted['children'] = [];
        }

        return $formatted;
    }
}