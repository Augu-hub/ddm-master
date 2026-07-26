<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Observers\Concerns\DispatchesTenantSync;
use App\Services\Audit\UserMenuSessionService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditTypeFormsController extends Controller
{
    /**
     * Les écritures de ce contrôleur passent par DB::table() (pas d'events
     * Eloquent) : on dispatche donc nous-mêmes la synchro vers les tenants
     * après chaque mutation du référentiel central.
     */
    use DispatchesTenantSync;

    private string $p = 'ddmparam';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()?->email !== 'admin@diaddem.local') {
                abort(403, 'Accès réservé au super administrateur système.');
            }
            return $next($request);
        });
    }

    // ══════════════════════════════════════════════════════════════
    //  VUE PRINCIPALE
    // ══════════════════════════════════════════════════════════════

    public function index()
    {
        return Inertia::render('dashboards/Param/admin/AuditTypeForms/index', [
            'auditTypes' => $this->buildAuditTypesList(),
            'stats'      => $this->buildStats(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  API JSON — lecture
    // ══════════════════════════════════════════════════════════════

    public function apiAuditTypes()
    {
        return response()->json([
            'success' => true,
            'data'    => $this->buildAuditTypesList(),
            'stats'   => $this->buildStats(),
        ]);
    }

    public function apiFormsByType(int $auditTypeId)
    {
        $type = DB::connection('mysql')
            ->table("{$this->p}.audit_types")
            ->where('id', $auditTypeId)
            ->first();

        if (!$type) {
            return response()->json(['success' => false, 'message' => 'Type introuvable.'], 404);
        }

        $flat = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms as f")
            ->leftJoin("{$this->p}.audit_type_forms as par", 'par.id', '=', 'f.parent_id')
            ->where('f.audit_type_id', $auditTypeId)
            ->orderBy('f.phase_num')
            ->orderBy('f.sort_order')
            ->orderBy('f.id')
            ->get([
                'f.id',
                'f.audit_type_id',
                'f.phase_num',
                'f.phase_label',
                'f.norme',
                'f.parent_id',
                'par.label as parent_label',
                'f.code',
                'f.label',
                'f.url_path',
                'f.icon',
                'f.sort_order',
                'f.is_active',
                'f.created_at',
                'f.updated_at',
            ]);

        $phases = $flat
            ->groupBy('phase_num')
            ->map(function ($items, $phaseNum) {
                $roots = $items->filter(function ($item) use ($items) {
                    return is_null($item->parent_id)
                        || !$items->contains('id', $item->parent_id);
                })->values();

                $buildTree = function ($nodes) use (&$buildTree, $items) {
                    return $nodes->map(function ($node) use (&$buildTree, $items) {
                        $node->children = $buildTree(
                            $items->where('parent_id', $node->id)->values()
                        );
                        return $node;
                    })->values();
                };

                return [
                    'phase_num'   => (int) $phaseNum,
                    'phase_label' => $items->first()->phase_label,
                    'norme'       => null, // plus de norme partagée
                    'forms'       => $buildTree($roots),
                ];
            })
            ->sortBy('phase_num')
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $phases,
            'flat'    => $flat,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  CRUD — AUDIT TYPES
    // ══════════════════════════════════════════════════════════════

    public function storeAuditType(Request $request)
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:20'],
            'label'     => ['required', 'string', 'max:255'],
            'color'     => ['nullable', 'string', 'max:20'],
            'icon'      => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $already = DB::connection('mysql')
            ->table("{$this->p}.audit_types")
            ->where('code', $data['code'])
            ->exists();

        if ($already) {
            return back()->withErrors(['code' => "Le code '{$data['code']}' existe déjà."]);
        }

        $data['is_active']  = $data['is_active'] ?? true;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::connection('mysql')
            ->table("{$this->p}.audit_types")
            ->insertGetId($data);

        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return back()->with('success', "Type d'audit '{$data['label']}' créé (ID {$id}).");
    }

    public function updateAuditType(Request $request, int $id)
    {
        $this->findAuditTypeOrFail($id);

        $data = $request->validate([
            'label'     => ['required', 'string', 'max:255'],
            'color'     => ['nullable', 'string', 'max:20'],
            'icon'      => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['updated_at'] = now();

        DB::connection('mysql')
            ->table("{$this->p}.audit_types")
            ->where('id', $id)
            ->update($data);

        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return back()->with('success', "Type d'audit mis à jour.");
    }

    public function destroyAuditType(int $id)
    {
        $type = $this->findAuditTypeOrFail($id);

        $nb = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->where('audit_type_id', $id)
            ->count();

        DB::connection('mysql')->transaction(function () use ($id) {
            DB::connection('mysql')
                ->table("{$this->p}.audit_type_forms")
                ->where('audit_type_id', $id)
                ->delete();

            DB::connection('mysql')
                ->table("{$this->p}.audit_types")
                ->where('id', $id)
                ->delete();
        });

        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return back()->with('success', "Type '{$type->label}' et {$nb} formulaire(s) supprimés.");
    }

    public function toggleAuditTypeActive(int $id)
    {
        $type = $this->findAuditTypeOrFail($id);

        DB::connection('mysql')
            ->table("{$this->p}.audit_types")
            ->where('id', $id)
            ->update([
                'is_active'  => !$type->is_active,
                'updated_at' => now(),
            ]);

        $etat = $type->is_active ? 'désactivé' : 'activé';
        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return back()->with('success', "Type '{$type->label}' {$etat}.");
    }

    // ══════════════════════════════════════════════════════════════
    //  CRUD — FORMULAIRES (norme individuelle)
    // ══════════════════════════════════════════════════════════════

    public function storeForm(Request $request)
    {
        $data = $request->validate([
            'audit_type_id' => ['required', 'integer'],
            'phase_num'     => ['required', 'integer', 'min:1'],
            'phase_label'   => ['required', 'string', 'max:100'],
            'norme'         => ['nullable', 'string'], // utilisateur la renseigne
            'parent_id'     => ['nullable', 'integer'],
            'code'          => ['required', 'string', 'max:50'],
            'label'         => ['required', 'string', 'max:255'],
            'url_path'      => ['nullable', 'string', 'max:500'],
            'icon'          => ['nullable', 'string', 'max:100'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $typeExists = DB::connection('mysql')
            ->table("{$this->p}.audit_types")
            ->where('id', $data['audit_type_id'])
            ->exists();

        if (!$typeExists) {
            return back()->withErrors(['audit_type_id' => "Type d'audit introuvable."]);
        }

        if (!empty($data['parent_id'])) {
            $parentExists = DB::connection('mysql')
                ->table("{$this->p}.audit_type_forms")
                ->where('id', $data['parent_id'])
                ->exists();

            if (!$parentExists) {
                return back()->withErrors(['parent_id' => 'Formulaire parent introuvable.']);
            }
        }

        // ✅ Suppression du bloc qui héritait automatiquement la norme de la phase
        // La norme est maintenant laissée telle quelle (nullable, définie par l'utilisateur)

        $data['is_active']  = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order']
            ?? $this->nextSortOrder($data['audit_type_id'], $data['phase_num'], $data['parent_id'] ?? null);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->insertGetId($data);

        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return back()->with('success', "Formulaire '{$data['label']}' créé (ID {$id}).");
    }

    public function updateForm(Request $request, int $id)
    {
        $form = $this->findFormOrFail($id);

        $data = $request->validate([
            'phase_num'   => ['required', 'integer', 'min:1'],
            'phase_label' => ['required', 'string', 'max:100'],
            'norme'       => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'integer'],
            'code'        => ['required', 'string', 'max:50'],
            'label'       => ['required', 'string', 'max:255'],
            'url_path'    => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        if (!empty($data['parent_id']) && (int) $data['parent_id'] === $id) {
            return back()->withErrors(['parent_id' => 'Un formulaire ne peut pas être son propre parent.']);
        }

        $data['updated_at'] = now();

        DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->where('id', $id)
            ->update($data);

        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return back()->with('success', "Formulaire '{$form->label}' mis à jour.");
    }

    public function destroyForm(int $id)
    {
        $form = $this->findFormOrFail($id);

        $nbEnfants = 0;
        DB::connection('mysql')->transaction(function () use ($id, &$nbEnfants) {
            $nbEnfants = $this->deleteFormRecursive($id);
        });

        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();

        $msg = $nbEnfants > 0
            ? "Formulaire '{$form->label}' et {$nbEnfants} enfant(s) supprimés."
            : "Formulaire '{$form->label}' supprimé.";

        return back()->with('success', $msg);
    }

    public function toggleFormActive(int $id)
    {
        $form = $this->findFormOrFail($id);

        DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->where('id', $id)
            ->update([
                'is_active'  => !$form->is_active,
                'updated_at' => now(),
            ]);

        $etat = $form->is_active ? 'désactivé' : 'activé';
        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return back()->with('success', "Formulaire '{$form->label}' {$etat}.");
    }

    public function reorderForms(Request $request)
    {
        $request->validate([
            'forms'             => ['required', 'array'],
            'forms.*.id'        => ['required', 'integer'],
            'forms.*.parent_id' => ['nullable', 'integer'],
            'forms.*.position'  => ['required', 'integer', 'min:0'],
        ]);

        DB::connection('mysql')->transaction(function () use ($request) {
            foreach ($request->forms as $item) {
                DB::connection('mysql')
                    ->table("{$this->p}.audit_type_forms")
                    ->where('id', $item['id'])
                    ->update([
                        'parent_id'  => $item['parent_id'],
                        'sort_order' => ($item['position'] + 1) * 10,
                        'updated_at' => now(),
                    ]);
            }
        });

        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return response()->json(['success' => true, 'message' => 'Ordre mis à jour.']);
    }

    // ══════════════════════════════════════════════════════════════
    //  GESTION DES PHASES (sans norme partagée)
    // ══════════════════════════════════════════════════════════════

    public function phaseRename(Request $request)
    {
        $data = $request->validate([
            'audit_type_id'   => ['required', 'integer'],
            'old_phase_num'   => ['required', 'integer'],
            'new_phase_num'   => ['required', 'integer', 'min:1'],
            'new_phase_label' => ['required', 'string', 'max:100'],
            // 'new_norme' => supprimé — la norme n'est plus gérée au niveau de la phase
        ]);

        if ((int) $data['new_phase_num'] !== (int) $data['old_phase_num']) {
            $conflict = DB::connection('mysql')
                ->table("{$this->p}.audit_type_forms")
                ->where('audit_type_id', $data['audit_type_id'])
                ->where('phase_num', $data['new_phase_num'])
                ->exists();

            if ($conflict) {
                return back()->withErrors([
                    'new_phase_num' => "La phase {$data['new_phase_num']} existe déjà pour ce type. Choisissez un autre numéro.",
                ]);
            }
        }

        $nb = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->where('audit_type_id', $data['audit_type_id'])
            ->where('phase_num', $data['old_phase_num'])
            ->update([
                'phase_num'   => $data['new_phase_num'],
                'phase_label' => $data['new_phase_label'],
                // norme non modifiée
                'updated_at'  => now(),
            ]);

        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return back()->with('success', "{$nb} formulaire(s) de la phase mis à jour.");
    }

    public function phaseDelete(int $auditTypeId, int $phaseNum)
    {
        $this->findAuditTypeOrFail($auditTypeId);

        $rootIds = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->where('audit_type_id', $auditTypeId)
            ->where('phase_num', $phaseNum)
            ->pluck('id');

        if ($rootIds->isEmpty()) {
            return back()->with('success', 'Phase déjà vide.');
        }

        $total = 0;
        DB::connection('mysql')->transaction(function () use ($rootIds, &$total) {
            foreach ($rootIds as $rootId) {
                $total += $this->deleteFormRecursive($rootId) + 1;
            }
        });

        UserMenuSessionService::clear();
        $this->dispatchSyncToAllTenants();
        return back()->with('success', "Phase {$phaseNum} supprimée ({$total} formulaire(s) effacés).");
    }

    // ══════════════════════════════════════════════════════════════
    //  HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════

    private function buildAuditTypesList(): array
    {
        $types = DB::connection('mysql')
            ->table("{$this->p}.audit_types")
            ->orderBy('label')
            ->get();

        $counts = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->selectRaw('audit_type_id, COUNT(*) as total, SUM(is_active) as actifs')
            ->groupBy('audit_type_id')
            ->get()
            ->keyBy('audit_type_id');

        return $types->map(function ($t) use ($counts) {
            $c = $counts[$t->id] ?? null;
            return [
                'id'           => $t->id,
                'code'         => $t->code,
                'label'        => $t->label,
                'color'        => $t->color  ?? '#6c757d',
                'icon'         => $t->icon   ?? 'ti ti-folder',
                'is_active'    => (bool) $t->is_active,
                'forms_total'  => $c ? (int) $c->total  : 0,
                'forms_actifs' => $c ? (int) $c->actifs : 0,
                'created_at'   => $t->created_at,
                'updated_at'   => $t->updated_at,
            ];
        })->toArray();
    }

    private function buildStats(): array
    {
        $t = DB::connection('mysql')
            ->table("{$this->p}.audit_types")
            ->selectRaw('COUNT(*) as total, SUM(is_active) as actifs')
            ->first();

        $f = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->selectRaw('COUNT(*) as total, SUM(is_active) as actifs,
                         COUNT(DISTINCT audit_type_id) as types_couverts,
                         MAX(phase_num) as max_phase')
            ->first();

        return [
            'types_total'    => (int) ($t->total          ?? 0),
            'types_actifs'   => (int) ($t->actifs          ?? 0),
            'forms_total'    => (int) ($f->total           ?? 0),
            'forms_actifs'   => (int) ($f->actifs          ?? 0),
            'types_couverts' => (int) ($f->types_couverts  ?? 0),
            'max_phase'      => (int) ($f->max_phase        ?? 0),
        ];
    }

    private function nextSortOrder(int $auditTypeId, int $phaseNum, ?int $parentId): int
    {
        $max = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->where('audit_type_id', $auditTypeId)
            ->where('phase_num', $phaseNum)
            ->where('parent_id', $parentId)
            ->max('sort_order');

        return ($max ?? 0) + 10;
    }

    private function deleteFormRecursive(int $id): int
    {
        $children = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->where('parent_id', $id)
            ->pluck('id');

        $nb = 0;
        foreach ($children as $childId) {
            $nb += $this->deleteFormRecursive($childId) + 1;
        }

        DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->where('id', $id)
            ->delete();

        return $nb;
    }

    private function findAuditTypeOrFail(int $id): object
    {
        $row = DB::connection('mysql')
            ->table("{$this->p}.audit_types")
            ->where('id', $id)
            ->first();

        if (!$row) abort(404, "Type d'audit introuvable (id={$id}).");
        return $row;
    }

    private function findFormOrFail(int $id): object
    {
        $row = DB::connection('mysql')
            ->table("{$this->p}.audit_type_forms")
            ->where('id', $id)
            ->first();

        if (!$row) abort(404, "Formulaire introuvable (id={$id}).");
        return $row;
    }
}