<?php

namespace App\Http\Controllers\Risk;

use App\Enums\NomenclatureType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNomenclatureRequest;
use App\Http\Requests\UpdateNomenclatureRequest;
use App\Models\RiskAppetiteLevel;
use App\Models\RiskNomenclature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NomenclatureController extends Controller
{
    // ---------------------------------------------------------------
    // Helpers privés
    // ---------------------------------------------------------------

    private function tenantId(): int
    {
        return (int) (session('tenant_id') ?? 1);
    }

    private function findForTenant(int $id): RiskNomenclature
    {
        return RiskNomenclature::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    private function findAppetiteForTenant(int $id): RiskAppetiteLevel
    {
        return RiskAppetiteLevel::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    private function buildTypeMeta(RiskNomenclature $root): array
    {
        $enum = NomenclatureType::tryFrom($root->type_code ?? '');
        return [
            'label'       => $enum?->label()      ?? $root->label,
            'color'       => $root->resolvedColor(),
            'icon'        => $root->resolvedIcon(),
            'badge_class' => $enum?->badgeClass() ?? 'secondary',
        ];
    }

    private function formatNode(RiskNomenclature $node, array $appetiteMap): array
    {
        $appetiteId   = $node->appetite_id;
        $appetiteData = $appetiteId ? ($appetiteMap[$appetiteId] ?? null) : null;

        return [
            'id'          => $node->id,
            'code'        => $node->code,
            'label'       => $node->label,
            'description' => $node->description,
            'level'       => $node->level,
            'type_code'   => $node->type_code,
            'parent_id'   => $node->parent_id,
            'appetite_id' => $appetiteId,
            'appetite'    => $appetiteData,
        ];
    }

    // ---------------------------------------------------------------
    // index — page Inertia
    // ---------------------------------------------------------------

    public function index(): Response
    {
        $tenantId = $this->tenantId();

        $appetites = RiskAppetiteLevel::where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'label', 'color', 'score_min', 'score_max', 'is_active'])
            ->toArray();

        $appetiteMap = collect($appetites)
            ->where('is_active', true)
            ->keyBy('id')
            ->toArray();

        $roots = RiskNomenclature::forTenant($tenantId)
            ->roots()
            ->active()
            ->with(['children', 'children.children'])
            ->orderBy('type_code')
            ->get()
            ->map(function (RiskNomenclature $root) use ($appetiteMap) {
                $data              = $this->formatNode($root, $appetiteMap);
                $data['type_meta'] = $this->buildTypeMeta($root);
                $data['children']  = $root->children->map(function ($child) use ($appetiteMap) {
                    $c             = $this->formatNode($child, $appetiteMap);
                    $c['children'] = $child->children
                        ->map(fn($gc) => $this->formatNode($gc, $appetiteMap))
                        ->values()->toArray();
                    return $c;
                })->values()->toArray();
                return $data;
            });

        return Inertia::render('dashboards/Risk/Nomenclature/index', [
            'tree'      => $roots,
            'appetites' => array_values($appetites),
        ]);
    }

    // ---------------------------------------------------------------
    // tree — endpoint JSON pour NomenclatureTreePicker
    // ---------------------------------------------------------------

    public function tree(): JsonResponse
    {
        $tenantId = $this->tenantId();

        $roots = RiskNomenclature::forTenant($tenantId)
            ->roots()
            ->active()
            ->with(['children', 'children.children'])
            ->orderBy('type_code')
            ->get()
            ->map(function (RiskNomenclature $root) {
                $meta = $this->buildTypeMeta($root);
                return [
                    'id'         => $root->id,
                    'code'       => $root->code,
                    'label'      => $root->label,
                    'level'      => $root->level,
                    'type_code'  => $root->type_code,
                    'type_label' => $meta['label'],
                    'type_color' => $meta['color'],
                    'type_icon'  => $meta['icon'],
                    'children'   => $root->children->map(fn($child) => [
                        'id'        => $child->id,
                        'code'      => $child->code,
                        'label'     => $child->label,
                        'level'     => $child->level,
                        'type_code' => $child->type_code,
                        'children'  => $child->children->map(fn($gc) => [
                            'id'        => $gc->id,
                            'code'      => $gc->code,
                            'label'     => $gc->label,
                            'level'     => $gc->level,
                            'type_code' => $gc->type_code,
                            'children'  => [],
                        ])->values(),
                    ])->values(),
                ];
            });

        return response()->json($roots);
    }

    // ---------------------------------------------------------------
    // store — nouvelle nomenclature
    // ---------------------------------------------------------------

    public function store(StoreNomenclatureRequest $request): JsonResponse
    {
        $tenantId = $this->tenantId();
        $data     = $request->validated();
        $parent   = $this->findForTenant((int) $data['parent_id']);

        if (!$parent->canHaveChildren()) {
            return response()->json(
                ['message' => 'Niveau maximum atteint (3 niveaux)'], 422
            );
        }

        $nomenclature = RiskNomenclature::create([
            'tenant_id'   => $tenantId,
            'parent_id'   => $parent->id,
            'level'       => $parent->level + 1,
            'type_code'   => $parent->type_code,
            'code'        => $this->generateCode($parent),
            'label'       => $data['label'],
            'description' => $data['description'] ?? null,
            'is_active'   => true,
        ]);

        return response()->json([
            'nomenclature' => $nomenclature,
            'message'      => 'Nomenclature creee avec succes',
        ], 201);
    }

    // ---------------------------------------------------------------
    // update — modifier nomenclature
    // ---------------------------------------------------------------

    public function update(UpdateNomenclatureRequest $request, int $id): JsonResponse
    {
        $nomenclature = $this->findForTenant($id);

        if ($nomenclature->isRoot()) {
            return response()->json(
                ['message' => 'Les types racines ne sont pas modifiables'], 403
            );
        }

        $data = $request->validated();
        $nomenclature->update([
            'label'       => $data['label'],
            'description' => $data['description'] ?? $nomenclature->description,
        ]);

        return response()->json([
            'nomenclature' => $nomenclature->fresh(),
            'message'      => 'Nomenclature mise a jour',
        ]);
    }

    // ---------------------------------------------------------------
    // assignAppetite — assigne ou retire une appétance à une nomenclature
    // ---------------------------------------------------------------

    public function assignAppetite(Request $request, int $id): JsonResponse
    {
        $nomenclature = $this->findForTenant($id);

        if ($nomenclature->isRoot()) {
            return response()->json(
                ['message' => 'Impossible d\'assigner une appetance a une racine'], 403
            );
        }

        $request->validate([
            'appetite_id' => ['nullable', 'integer'],
        ]);

        $appetiteId = $request->input('appetite_id');

        if ($appetiteId !== null) {
            $exists = RiskAppetiteLevel::where('id', $appetiteId)
                ->where('tenant_id', $this->tenantId())
                ->where('is_active', true)
                ->exists();

            if (!$exists) {
                return response()->json(['message' => 'Appetance invalide'], 422);
            }
        }

        $nomenclature->update(['appetite_id' => $appetiteId]);

        $appetite = $appetiteId
            ? RiskAppetiteLevel::find($appetiteId, ['id', 'code', 'label', 'color'])
            : null;

        return response()->json([
            'nomenclature_id' => $nomenclature->id,
            'appetite_id'     => $appetiteId,
            'appetite'        => $appetite,
            'message'         => $appetiteId
                ? 'Appetance assignee avec succes'
                : 'Appetance retiree',
        ]);
    }

    // ---------------------------------------------------------------
    // destroy — supprimer nomenclature
    // ---------------------------------------------------------------

    public function destroy(int $id): JsonResponse
    {
        $nomenclature = $this->findForTenant($id);

        if ($nomenclature->isRoot()) {
            return response()->json(
                ['message' => 'Les types racines ne peuvent pas etre supprimes'], 403
            );
        }

        if ($nomenclature->children()->exists()) {
            return response()->json(
                ['message' => 'Impossible de supprimer : des sous-elements existent'], 422
            );
        }

        if ($nomenclature->riskRegisters()->exists()) {
            return response()->json(
                ['message' => 'Cette nomenclature est liee a des risques existants'], 422
            );
        }

        $nomenclature->delete();

        return response()->json(['message' => 'Nomenclature supprimee']);
    }

    // ---------------------------------------------------------------
    // storeAppetite — créer un niveau d'appétance
    // ---------------------------------------------------------------

    public function storeAppetite(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'code'      => ['required', 'string', 'max:20'],
            'label'     => ['required', 'string', 'max:100'],
            'color'     => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'score_min' => ['required', 'integer', 'min:0'],
            'score_max' => ['required', 'integer', 'min:0', 'gte:score_min'],
        ]);

        // Vérifier unicité du code pour ce tenant
        $codeExists = RiskAppetiteLevel::where('tenant_id', $tenantId)
            ->where('code', $data['code'])
            ->exists();

        if ($codeExists) {
            return response()->json([
                'errors' => ['code' => ['Ce code existe deja pour ce tenant']],
            ], 422);
        }

        // sort_order = dernier + 1
        $lastOrder = RiskAppetiteLevel::where('tenant_id', $tenantId)
            ->max('sort_order') ?? -1;

        $appetite = RiskAppetiteLevel::create([
            'tenant_id'  => $tenantId,
            'code'       => strtoupper(trim($data['code'])),
            'label'      => $data['label'],
            'color'      => $data['color'],
            'score_min'  => $data['score_min'],
            'score_max'  => $data['score_max'],
            'sort_order' => $lastOrder + 1,
            'is_active'  => true,
        ]);

        return response()->json([
            'appetite' => $appetite,
            'message'  => 'Niveau d\'appetance cree avec succes',
        ], 201);
    }

    // ---------------------------------------------------------------
    // updateAppetite — modifier un niveau d'appétance
    // ---------------------------------------------------------------

    public function updateAppetite(Request $request, int $id): JsonResponse
    {
        $appetite = $this->findAppetiteForTenant($id);

        $data = $request->validate([
            'label'     => ['required', 'string', 'max:100'],
            'color'     => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'score_min' => ['required', 'integer', 'min:0'],
            'score_max' => ['required', 'integer', 'min:0', 'gte:score_min'],
        ]);

        $appetite->update([
            'label'     => $data['label'],
            'color'     => $data['color'],
            'score_min' => $data['score_min'],
            'score_max' => $data['score_max'],
        ]);

        return response()->json([
            'appetite' => $appetite->fresh(),
            'message'  => 'Niveau d\'appetance mis a jour',
        ]);
    }

    // ---------------------------------------------------------------
    // destroyAppetite — supprimer un niveau d'appétance
    // ---------------------------------------------------------------

    public function destroyAppetite(int $id): JsonResponse
    {
        $appetite = $this->findAppetiteForTenant($id);

        // Vérifier qu'aucune nomenclature n'utilise ce niveau
        $inUse = RiskNomenclature::where('tenant_id', $this->tenantId())
            ->where('appetite_id', $id)
            ->exists();

        if ($inUse) {
            return response()->json([
                'message' => 'Ce niveau est assigne a des nomenclatures — retirez-le d\'abord',
            ], 422);
        }

        $appetite->delete();

        return response()->json(['message' => 'Niveau d\'appetance supprime']);
    }

    // ---------------------------------------------------------------
    // generateCode — privé
    // ---------------------------------------------------------------

    private function generateCode(RiskNomenclature $parent): string
    {
        $last = RiskNomenclature::withTrashed()
            ->where('parent_id', $parent->id)
            ->orderByRaw('CAST(SUBSTRING_INDEX(code, "-", -1) AS UNSIGNED) DESC')
            ->value('code');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq   = (int) end($parts) + 1;
        }

        $pad = ($parent->level === 1) ? 3 : 2;

        return $parent->code . '-' . str_pad($seq, $pad, '0', STR_PAD_LEFT);
    }
}
