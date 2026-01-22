<?php

namespace App\Http\Controllers\Risk;
use App\Http\Controllers\Controller;
use App\Models\Audit\Mission\{MissionType, MissionPhase, MissionPhaseAssignment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Storage};
use Inertia\Inertia;

class MissionPhaseController extends Controller
{
    /**
     * GET /m/mission-phases
     * Dashboard principal
     */
    public function index(Request $request)
    {
        try {
            $missionTypeId = $request->query('type_id');
            
            $missionTypes = MissionType::active()
                ->orderBy('sort_order')
                ->get();

            if (!$missionTypeId && $missionTypes->isNotEmpty()) {
                $missionTypeId = $missionTypes->first()->id;
            }

            $selectedType = MissionType::find($missionTypeId);
            $hierarchy = [];
            $statistics = [];

            if ($selectedType) {
                $hierarchy = $this->buildHierarchy($selectedType->phases()->whereNull('parent_id')->get());
                $statistics = [
                    'totalPhases' => $selectedType->phases()->count(),
                    'mainPhases' => $selectedType->phases()->whereNull('parent_id')->count(),
                    'totalWeight' => $selectedType->phases()->sum('weight'),
                ];
            }

            return Inertia::render('dashboards/Audit/Mission/Phases/index', [
                'missionTypes' => $missionTypes,
                'selectedTypeId' => $missionTypeId,
                'hierarchy' => $hierarchy,
                'statistics' => $statistics,
            ]);

        } catch (\Exception $e) {
            Log::error('MissionPhaseController@index: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * GET /m/audit.core/api/mission-phases/hierarchy/{typeId}
     * Récupère hiérarchie complète avec descriptions
     */
    public function getHierarchy($typeId)
    {
        try {
            $type = MissionType::find($typeId);
            if (!$type) return response()->json(['error' => 'Type not found'], 404);

            $hierarchy = $this->buildHierarchy($type->phases()->whereNull('parent_id')->get());

            return response()->json(['success' => true, 'data' => $hierarchy]);
        } catch (\Exception $e) {
            Log::error('getHierarchy: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /m/audit.core/api/mission-phases
     * Crée une phase
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:10',
                'label' => 'required|string|max:200',
                'description' => 'nullable|string|max:5000', // ✅ NOUVEAU: Description fiche
                'phase_type' => 'nullable|in:PREPARATION,VERIFICATION,CONCLUSION,SUIVI', // ✅ NOUVEAU
                'mission_type_id' => 'required|exists:mission_types,id',
                'parent_id' => 'nullable|exists:mission_phases,id',
                'weight' => 'nullable|integer|min:0|max:5',
                'is_decomposable' => 'nullable|boolean',
                'logo_preparation' => 'nullable|string', // ✅ NOUVEAU: Logo paths (stocké en DB)
                'logo_verification' => 'nullable|string',
                'logo_conclusion' => 'nullable|string',
                'logo_suivi' => 'nullable|string',
            ]);

            // Déterminer level et code_full
            $level = 1;
            $code_full = 'P' . $validated['code'];

            if ($validated['parent_id']) {
                $parent = MissionPhase::find($validated['parent_id']);
                if ($parent) {
                    $level = $parent->level + 1;
                    $code_full = $parent->code_full . $validated['code'];
                }
            }

            // Vérifier unicité code_full
            $exists = MissionPhase::where('code_full', $code_full)
                ->where('mission_type_id', $validated['mission_type_id'])
                ->exists();

            if ($exists) {
                return response()->json(['error' => "Code $code_full existe déjà"], 422);
            }

            $phase = MissionPhase::create([
                'code' => $validated['code'],
                'code_full' => $code_full,
                'label' => $validated['label'],
                'description' => $validated['description'] ?? null, // ✅ NOUVEAU
                'phase_type' => $validated['phase_type'] ?? null, // ✅ NOUVEAU
                'parent_id' => $validated['parent_id'] ?? null,
                'level' => $level,
                'mission_type_id' => $validated['mission_type_id'],
                'weight' => $validated['weight'] ?? 0,
                'is_decomposable' => $validated['is_decomposable'] ?? false,
                'logo_preparation' => $validated['logo_preparation'] ?? null, // ✅ NOUVEAU
                'logo_verification' => $validated['logo_verification'] ?? null,
                'logo_conclusion' => $validated['logo_conclusion'] ?? null,
                'logo_suivi' => $validated['logo_suivi'] ?? null,
                'status' => 'active',
            ]);

            Log::info("Phase créée: $code_full");

            return response()->json([
                'success' => true,
                'data' => $this->formatPhaseWithChildren($phase),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Store phase: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /m/audit.core/api/mission-phases/{id}
     * Modifie une phase
     */
    public function update(Request $request, MissionPhase $phase)
    {
        try {
            $validated = $request->validate([
                'code' => 'sometimes|string|max:10',
                'label' => 'sometimes|string|max:200',
                'description' => 'nullable|string|max:5000', // ✅ NOUVEAU
                'phase_type' => 'nullable|in:PREPARATION,VERIFICATION,CONCLUSION,SUIVI', // ✅ NOUVEAU
                'weight' => 'nullable|integer|min:0|max:5',
                'is_decomposable' => 'nullable|boolean',
                'status' => 'nullable|in:draft,active,archived',
                'logo_preparation' => 'nullable|string', // ✅ NOUVEAU
                'logo_verification' => 'nullable|string',
                'logo_conclusion' => 'nullable|string',
                'logo_suivi' => 'nullable|string',
            ]);

            $phase->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatPhaseWithChildren($phase),
            ]);

        } catch (\Exception $e) {
            Log::error('Update phase: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /m/audit.core/api/mission-phases/{id}
     * Supprime une phase + TOUS ses enfants en CASCADE
     */
    public function destroy(MissionPhase $phase)
    {
        try {
            $totalDescendants = $this->countAllDescendants($phase);
            $assignmentCount = $this->countAssignmentsInHierarchy($phase);
            
            if ($assignmentCount > 0) {
                return response()->json([
                    'error' => "$assignmentCount missions assignées à cette phase ou ses enfants. Impossible de supprimer!"
                ], 422);
            }

            $phaseLabel = $phase->code_full . ' - ' . $phase->label;
            
            Log::info("🗑️ Suppression phase: $phaseLabel");
            Log::info("   → Descendants en cascade: $totalDescendants");
            
            $phase->delete();

            return response()->json([
                'success' => true,
                'message' => "Phase '$phaseLabel' + $totalDescendants enfants supprimés en cascade"
            ]);

        } catch (\Exception $e) {
            Log::error('Delete phase: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /m/audit.core/api/mission-phases/{id}/assign
     * Assigne phase à mission
     */
    public function assignToMission(Request $request, MissionPhase $phase)
    {
        try {
            $validated = $request->validate([
                'mission_id' => 'required|exists:missions,id',
                'owner_id' => 'nullable|exists:users,id',
                'planned_start' => 'nullable|date',
                'planned_end' => 'nullable|date',
            ]);

            $assignment = MissionPhaseAssignment::create([
                'mission_id' => $validated['mission_id'],
                'mission_phase_id' => $phase->id,
                'owner_id' => $validated['owner_id'],
                'planned_start' => $validated['planned_start'],
                'planned_end' => $validated['planned_end'],
                'status' => 'pending',
            ]);

            return response()->json(['success' => true, 'data' => $assignment], 201);

        } catch (\Exception $e) {
            Log::error('Assign phase: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /m/audit.core/api/mission-phases/{id}/details
     * Retourne détails complets phase avec description
     */
    public function getDetails(MissionPhase $phase)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $phase->id,
                    'code' => $phase->code,
                    'code_full' => $phase->code_full,
                    'label' => $phase->label,
                    'description' => $phase->description, // ✅ Description complète
                    'phase_type' => $phase->phase_type,
                    'level' => $phase->level,
                    'weight' => $phase->weight,
                    'parent_id' => $phase->parent_id,
                    'status' => $phase->status,
                    'logo_preparation' => $phase->logo_preparation,
                    'logo_verification' => $phase->logo_verification,
                    'logo_conclusion' => $phase->logo_conclusion,
                    'logo_suivi' => $phase->logo_suivi,
                    'created_at' => $phase->created_at,
                    'updated_at' => $phase->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get phase details: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ════════════════════════════════════════════════════════════════════════════════════

    /**
     * Compte TOUS les descendants
     */
    private function countAllDescendants(MissionPhase $phase): int
    {
        $count = 0;
        foreach ($phase->children as $child) {
            $count++;
            $count += $this->countAllDescendants($child);
        }
        return $count;
    }

    /**
     * Compte assignments dans TOUTE la hiérarchie
     */
    private function countAssignmentsInHierarchy(MissionPhase $phase): int
    {
        $count = $phase->assignments()->count();
        foreach ($phase->children as $child) {
            $count += $this->countAssignmentsInHierarchy($child);
        }
        return $count;
    }

    /**
     * Formate une phase avec enfants et TOUS les champs V4
     */
    private function formatPhaseWithChildren(MissionPhase $phase): array
    {
        return [
            'id' => $phase->id,
            'code' => $phase->code,
            'code_full' => $phase->code_full,
            'label' => $phase->label,
            'description' => $phase->description, // ✅ V4
            'phase_type' => $phase->phase_type, // ✅ V4
            'level' => $phase->level,
            'weight' => $phase->weight,
            'parent_id' => $phase->parent_id,
            'is_decomposable' => $phase->is_decomposable,
            'logo_preparation' => $phase->logo_preparation, // ✅ V4
            'logo_verification' => $phase->logo_verification, // ✅ V4
            'logo_conclusion' => $phase->logo_conclusion, // ✅ V4
            'logo_suivi' => $phase->logo_suivi, // ✅ V4
            'status' => $phase->status,
            'children' => $phase->children->count() > 0
                ? $this->buildHierarchy($phase->children)
                : [],
        ];
    }

    /**
     * Construit hiérarchie récursive avec TOUS les champs V4
     */
    private function buildHierarchy($phases): array
    {
        return $phases->map(function ($phase) {
            return [
                'id' => $phase->id,
                'code' => $phase->code,
                'code_full' => $phase->code_full,
                'label' => $phase->label,
                'description' => $phase->description, // ✅ V4 - Description complète fiche
                'phase_type' => $phase->phase_type, // ✅ V4 - Type phase
                'level' => $phase->level,
                'weight' => $phase->weight,
                'parent_id' => $phase->parent_id,
                'is_decomposable' => $phase->is_decomposable,
                'logo_preparation' => $phase->logo_preparation, // ✅ V4 - 4 logos
                'logo_verification' => $phase->logo_verification,
                'logo_conclusion' => $phase->logo_conclusion,
                'logo_suivi' => $phase->logo_suivi,
                'status' => $phase->status,
                'children' => $phase->children->count() > 0
                    ? $this->buildHierarchy($phase->children)
                    : [],
            ];
        })->toArray();
    }
}