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

    /**
     * Formate un nœud — 2 niveaux seulement (racine + facteurs).
     * Le niveau 3 est supprimé volontairement.
     */
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
    // index — page Inertia (2 niveaux : racine + facteurs RF-XX-YY)
    // ---------------------------------------------------------------

    public function index(): Response
    {
        $tenantId = $this->tenantId();

        $appetites = RiskAppetiteLevel::where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'label', 'color', 'description', 'score_min', 'score_max', 'is_active'])
            ->toArray();

        $appetiteMap = collect($appetites)
            ->where('is_active', true)
            ->keyBy('id')
            ->toArray();

        // 2 niveaux seulement : racines + leurs enfants directs (facteurs)
        $roots = RiskNomenclature::forTenant($tenantId)
            ->roots()
            ->active()
            ->with(['children'])          // ← un seul niveau d'enfants
            ->orderBy('type_code')
            ->get()
            ->map(function (RiskNomenclature $root) use ($appetiteMap) {
                $data              = $this->formatNode($root, $appetiteMap);
                $data['type_meta'] = $this->buildTypeMeta($root);
                $data['children']  = $root->children
                    ->map(fn ($child) => $this->formatNode($child, $appetiteMap))
                    ->values()->toArray();
                return $data;
            });

        return Inertia::render('dashboards/Risk/Nomenclature/index', [
            'tree'      => $roots,
            'appetites' => array_values($appetites),
        ]);
    }

    // ---------------------------------------------------------------
    // tree — endpoint JSON pour NomenclatureTreePicker (2 niveaux)
    // ---------------------------------------------------------------

    public function tree(): JsonResponse
    {
        $tenantId = $this->tenantId();

        $roots = RiskNomenclature::forTenant($tenantId)
            ->roots()
            ->active()
            ->with(['children'])
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
                    'children'   => $root->children->map(fn ($child) => [
                        'id'        => $child->id,
                        'code'      => $child->code,
                        'label'     => $child->label,
                        'level'     => $child->level,
                        'type_code' => $child->type_code,
                        'children'  => [],   // plus de niveau 3
                    ])->values(),
                ];
            });

        return response()->json($roots);
    }

    // ---------------------------------------------------------------
    // store — nouvelle nomenclature (niveau 2 uniquement)
    // ---------------------------------------------------------------

    public function store(StoreNomenclatureRequest $request): JsonResponse
    {
        $tenantId = $this->tenantId();
        $data     = $request->validated();
        $parent   = $this->findForTenant((int) $data['parent_id']);

        // Seul le niveau 1 (racine) peut avoir des enfants — donc on bloque niveau 2+
        if ($parent->level >= 2) {
            return response()->json(
                ['message' => 'Structure à 2 niveaux uniquement — les facteurs ne peuvent pas avoir d\'enfants.'], 422
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
            'message'      => 'Facteur de risque créé avec succès',
        ], 201);
    }

    // ---------------------------------------------------------------
    // update — modifier nomenclature (inchangé)
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
            'message'      => 'Facteur de risque mis à jour',
        ]);
    }

    // ---------------------------------------------------------------
    // assignAppetite — assigne une appétence à un facteur (niveau 2)
    // ---------------------------------------------------------------

    public function assignAppetite(Request $request, int $id): JsonResponse
    {
        $nomenclature = $this->findForTenant($id);

        if ($nomenclature->isRoot()) {
            return response()->json(
                ['message' => 'Impossible d\'assigner une appétence à une racine'], 403
            );
        }

        $request->validate([
            'appetite_id'  => ['nullable', 'integer'],
            'description'  => ['nullable', 'string', 'max:1000'],  // description IA optionnelle
        ]);

        $appetiteId  = $request->input('appetite_id');
        $description = $request->input('description');   // contenu généré par l'IA

        if ($appetiteId !== null) {
            $exists = RiskAppetiteLevel::where('id', $appetiteId)
                ->where('tenant_id', $this->tenantId())
                ->where('is_active', true)
                ->exists();

            if (!$exists) {
                return response()->json(['message' => 'Appétence invalide'], 422);
            }
        }

        // Met à jour l'appétence + stocke la description IA dans la nomenclature
        $nomenclature->update([
            'appetite_id'  => $appetiteId,
            'description'  => $description ?? $nomenclature->description,
        ]);

        $appetite = $appetiteId
            ? RiskAppetiteLevel::find($appetiteId, ['id', 'code', 'label', 'color', 'description'])
            : null;

        return response()->json([
            'nomenclature_id' => $nomenclature->id,
            'appetite_id'     => $appetiteId,
            'appetite'        => $appetite,
            'description'     => $nomenclature->description,
            'message'         => $appetiteId ? 'Appétence assignée avec succès' : 'Appétence retirée',
        ]);
    }

    // ---------------------------------------------------------------
    // destroy — supprimer facteur (niveau 2 uniquement)
    // ---------------------------------------------------------------

    public function destroy(int $id): JsonResponse
    {
        $nomenclature = $this->findForTenant($id);

        if ($nomenclature->isRoot()) {
            return response()->json(
                ['message' => 'Les types racines ne peuvent pas être supprimés'], 403
            );
        }

        if ($nomenclature->riskRegisters()->exists()) {
            return response()->json(
                ['message' => 'Ce facteur est lié à des risques existants'], 422
            );
        }

        $nomenclature->delete();

        return response()->json(['message' => 'Facteur de risque supprimé']);
    }

    // ---------------------------------------------------------------
    // storeAppetite — créer un niveau d'appétence
    // ---------------------------------------------------------------

    public function storeAppetite(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'code'        => ['required', 'string', 'max:20'],
            'label'       => ['required', 'string', 'max:100'],
            'color'       => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'score_min'   => ['required', 'integer', 'min:0'],
            'score_max'   => ['required', 'integer', 'min:0', 'gte:score_min'],
        ]);

        $codeExists = RiskAppetiteLevel::where('tenant_id', $tenantId)
            ->where('code', $data['code'])
            ->exists();

        if ($codeExists) {
            return response()->json([
                'errors' => ['code' => ['Ce code existe déjà pour ce tenant']],
            ], 422);
        }

        $lastOrder = RiskAppetiteLevel::where('tenant_id', $tenantId)->max('sort_order') ?? -1;

        $appetite = RiskAppetiteLevel::create([
            'tenant_id'   => $tenantId,
            'code'        => strtoupper(trim($data['code'])),
            'label'       => $data['label'],
            'color'       => $data['color'],
            'description' => $data['description'] ?? null,
            'score_min'   => $data['score_min'],
            'score_max'   => $data['score_max'],
            'sort_order'  => $lastOrder + 1,
            'is_active'   => true,
        ]);

        return response()->json([
            'appetite' => $appetite,
            'message'  => 'Niveau d\'appétence créé avec succès',
        ], 201);
    }

    // ---------------------------------------------------------------
    // updateAppetite — modifier un niveau d'appétence
    // ---------------------------------------------------------------

    public function updateAppetite(Request $request, int $id): JsonResponse
    {
        $appetite = $this->findAppetiteForTenant($id);

        $data = $request->validate([
            'label'       => ['required', 'string', 'max:100'],
            'color'       => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'score_min'   => ['required', 'integer', 'min:0'],
            'score_max'   => ['required', 'integer', 'min:0', 'gte:score_min'],
        ]);

        $appetite->update([
            'label'       => $data['label'],
            'color'       => $data['color'],
            'description' => $data['description'] ?? $appetite->description,
            'score_min'   => $data['score_min'],
            'score_max'   => $data['score_max'],
        ]);

        return response()->json([
            'appetite' => $appetite->fresh(),
            'message'  => 'Niveau d\'appétence mis à jour',
        ]);
    }

    // ---------------------------------------------------------------
    // destroyAppetite — supprimer un niveau d'appétence
    // ---------------------------------------------------------------

    public function destroyAppetite(int $id): JsonResponse
    {
        $appetite = $this->findAppetiteForTenant($id);

        $inUse = RiskNomenclature::where('tenant_id', $this->tenantId())
            ->where('appetite_id', $id)
            ->exists();

        if ($inUse) {
            return response()->json([
                'message' => 'Ce niveau est assigné à des facteurs — retirez-le d\'abord',
            ], 422);
        }

        $appetite->delete();

        return response()->json(['message' => 'Niveau d\'appétence supprimé']);
    }

    // ---------------------------------------------------------------
    // generateCode — privé (RF-XX-YY pour niveau 2)
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

        // Niveau 2 toujours sur 2 chiffres : RF-01-01, RF-01-02…
        return $parent->code . '-' . str_pad($seq, 2, '0', STR_PAD_LEFT);
    }
}