<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\RiskAppetiteLevel;
use App\Models\RiskNomenclature;
use App\Services\MistralNomenclatureAssistant;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * NOMENCLATURE CONTROLLER — Gestion des nomenclatures et appétances de risques
 *
 * Routes (montées sous prefix m/risk.core — risk.core.*) :
 *   GET  /nomenclature           → index        (page principale Inertia)
 *   POST /nomenclature/appetites → storeAppetite
 *   PUT  /nomenclature/appetites/{id} → updateAppetite
 *   DEL  /nomenclature/appetites/{id} → destroyAppetite
 *   POST /nomenclature/nomenclatures  → storeNomenclature
 *   PUT  /nomenclature/nomenclatures/{id} → updateNomenclature
 *   DEL  /nomenclature/nomenclatures/{id} → destroyNomenclature
 *   POST /nomenclature/ai/suggest-domains   → aiSuggestDomains
 *   POST /nomenclature/ai/suggest-families  → aiSuggestFamilies
 *   POST /nomenclature/ai/suggest-types     → aiSuggestTypes
 * ════════════════════════════════════════════════════════════════════════════
 */
class NomenclatureController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // PAGE PRINCIPALE
    // ────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? 1;

            $appetites = RiskAppetiteLevel::where('tenant_id', $tenantId)
                ->orderBy('sort_order')
                ->get();

            $nomenclatures = RiskNomenclature::where('tenant_id', $tenantId)
                ->with('appetite:id,code,label,color')
                ->orderBy('level')
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get();

            Log::info('✅ Nomenclature dashboard loaded', [
                'appetites_count'     => $appetites->count(),
                'nomenclatures_count' => $nomenclatures->count(),
                'tenant_id'           => $tenantId,
            ]);

            return Inertia::render('dashboards/Risk/Nomenclature/index', [
                'appetites'      => $appetites,
                'nomenclatures'  => $nomenclatures,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Nomenclature index error: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // CRUD APPÉTANCES
    // ────────────────────────────────────────────────────────────────────────

    public function storeAppetite(Request $request)
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? 1;

            $validated = $request->validate([
                'code'        => 'required|string|max:20',
                'label'       => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'score_min'   => 'required|integer|min:0|max:25',
                'score_max'   => 'required|integer|min:0|max:25|gte:score_min',
                'color'       => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'sort_order'  => 'nullable|integer|min:0',
            ]);

            // Vérifier unicité du code pour ce tenant
            if (RiskAppetiteLevel::where('tenant_id', $tenantId)->where('code', $validated['code'])->exists()) {
                return response()->json(['success' => false, 'error' => "Le code \"{$validated['code']}\" existe déjà."], 422);
            }

            $appetite = RiskAppetiteLevel::create([
                'tenant_id'   => $tenantId,
                'code'        => strtoupper($validated['code']),
                'label'       => $validated['label'],
                'description' => $validated['description'] ?? null,
                'score_min'   => $validated['score_min'],
                'score_max'   => $validated['score_max'],
                'color'       => $validated['color'],
                'sort_order'  => $validated['sort_order'] ?? 0,
                'is_active'   => true,
            ]);

            Log::info('✅ Appétance créée', ['id' => $appetite->id, 'code' => $appetite->code]);

            return response()->json(['success' => true, 'message' => "Appétance \"{$appetite->code}\" créée.", 'appetite' => $appetite]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('❌ storeAppetite: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateAppetite(Request $request, int $id)
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? 1;
            $appetite = RiskAppetiteLevel::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'label'       => 'sometimes|string|max:100',
                'description' => 'nullable|string|max:500',
                'score_min'   => 'sometimes|integer|min:0|max:25',
                'score_max'   => 'sometimes|integer|min:0|max:25',
                'color'       => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'sort_order'  => 'nullable|integer|min:0',
                'is_active'   => 'sometimes|boolean',
            ]);

            $appetite->update($validated);

            return response()->json(['success' => true, 'message' => "Appétance mise à jour.", 'appetite' => $appetite->fresh()]);

        } catch (\Exception $e) {
            Log::error('❌ updateAppetite: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyAppetite(int $id)
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? 1;
            $appetite = RiskAppetiteLevel::where('tenant_id', $tenantId)->findOrFail($id);

            // Vérifier si des nomenclatures utilisent cette appétance
            $usedCount = RiskNomenclature::where('tenant_id', $tenantId)->where('appetite_id', $id)->count();
            if ($usedCount > 0) {
                return response()->json([
                    'success' => false,
                    'error'   => "Impossible de supprimer : $usedCount nomenclature(s) utilisent cette appétance.",
                ], 422);
            }

            $code = $appetite->code;
            $appetite->delete();

            return response()->json(['success' => true, 'message' => "Appétance \"$code\" supprimée."]);

        } catch (\Exception $e) {
            Log::error('❌ destroyAppetite: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // CRUD NOMENCLATURES
    // ────────────────────────────────────────────────────────────────────────

    public function storeNomenclature(Request $request)
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? 1;

            $validated = $request->validate([
                'code'        => 'required|string|max:30',
                'label'       => 'required|string|max:150',
                'description' => 'nullable|string|max:500',
                'level'       => 'required|integer|in:1,2,3',
                'parent_id'   => 'nullable|integer|exists:risk_nomenclatures,id',
                'appetite_id' => 'nullable|integer|exists:risk_appetite_levels,id',
                'color'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'icon'        => 'nullable|string|max:50',
                'sort_order'  => 'nullable|integer|min:0',
            ]);

            // Vérifier unicité code pour ce tenant
            if (RiskNomenclature::where('tenant_id', $tenantId)->where('code', $validated['code'])->exists()) {
                return response()->json(['success' => false, 'error' => "Le code \"{$validated['code']}\" existe déjà."], 422);
            }

            // Vérifier cohérence parent / level
            if ($validated['level'] > 1 && !$validated['parent_id']) {
                return response()->json(['success' => false, 'error' => "Un parent est requis pour les niveaux 2 et 3."], 422);
            }

            $nomenclature = RiskNomenclature::create([
                'tenant_id'   => $tenantId,
                'parent_id'   => $validated['parent_id'] ?? null,
                'appetite_id' => $validated['appetite_id'] ?? null,
                'code'        => strtoupper($validated['code']),
                'label'       => $validated['label'],
                'description' => $validated['description'] ?? null,
                'level'       => $validated['level'],
                'color'       => $validated['color'] ?? null,
                'icon'        => $validated['icon'] ?? null,
                'sort_order'  => $validated['sort_order'] ?? 0,
                'is_active'   => true,
            ]);

            // Eager load appetite pour la réponse
            $nomenclature->load('appetite:id,code,label,color');

            Log::info('✅ Nomenclature créée', ['id' => $nomenclature->id, 'code' => $nomenclature->code, 'level' => $nomenclature->level]);

            return response()->json([
                'success'      => true,
                'message'      => "Nomenclature \"{$nomenclature->code}\" créée.",
                'nomenclature' => $nomenclature,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('❌ storeNomenclature: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateNomenclature(Request $request, int $id)
    {
        try {
            $tenantId     = auth()->user()->tenant_id ?? 1;
            $nomenclature = RiskNomenclature::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'label'       => 'sometimes|string|max:150',
                'description' => 'nullable|string|max:500',
                'appetite_id' => 'nullable|integer|exists:risk_appetite_levels,id',
                'color'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'icon'        => 'nullable|string|max:50',
                'sort_order'  => 'nullable|integer|min:0',
                'is_active'   => 'sometimes|boolean',
            ]);

            $nomenclature->update($validated);
            $nomenclature->load('appetite:id,code,label,color');

            return response()->json([
                'success'      => true,
                'message'      => "Nomenclature mise à jour.",
                'nomenclature' => $nomenclature,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ updateNomenclature: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyNomenclature(int $id)
    {
        try {
            $tenantId     = auth()->user()->tenant_id ?? 1;
            $nomenclature = RiskNomenclature::where('tenant_id', $tenantId)->findOrFail($id);

            // Vérifier s'il a des enfants
            $childrenCount = RiskNomenclature::where('tenant_id', $tenantId)->where('parent_id', $id)->count();
            if ($childrenCount > 0) {
                return response()->json([
                    'success' => false,
                    'error'   => "Impossible de supprimer : $childrenCount enfant(s) dépendent de cette entrée.",
                ], 422);
            }

            $code = $nomenclature->code;
            $nomenclature->delete();

            return response()->json(['success' => true, 'message' => "\"$code\" supprimé."]);

        } catch (\Exception $e) {
            Log::error('❌ destroyNomenclature: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // IA — SUGGESTIONS MISTRAL
    // ────────────────────────────────────────────────────────────────────────

    public function aiSuggestDomains(Request $request, MistralNomenclatureAssistant $mistral)
    {
        try {
            $data = $request->validate([
                'sector'           => 'required|string|min:3|max:100',
                'existing_domains' => 'nullable|array',
            ]);

            if (!MistralNomenclatureAssistant::validatePayloadSafety($data)) {
                return response()->json(['success' => false, 'error' => 'Données invalides.'], 400);
            }

            $result = $mistral->suggestDomains($data);

            return response()->json([
                'success' => true,
                'domains' => $result['domains'] ?? [],
                'source'  => 'Mistral',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ aiSuggestDomains: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function aiSuggestFamilies(Request $request, MistralNomenclatureAssistant $mistral)
    {
        try {
            $data = $request->validate([
                'domain_code'  => 'required|string|max:10',
                'domain_label' => 'required|string|min:3|max:100',
                'sector'       => 'nullable|string|max:100',
            ]);

            if (!MistralNomenclatureAssistant::validatePayloadSafety($data)) {
                return response()->json(['success' => false, 'error' => 'Données invalides.'], 400);
            }

            $result = $mistral->suggestFamilies($data);

            return response()->json([
                'success'  => true,
                'families' => $result['families'] ?? [],
                'source'   => 'Mistral',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ aiSuggestFamilies: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function aiSuggestTypes(Request $request, MistralNomenclatureAssistant $mistral)
    {
        try {
            $data = $request->validate([
                'family_code'  => 'required|string|max:15',
                'family_label' => 'required|string|min:3|max:100',
                'domain_label' => 'required|string|max:100',
                'sector'       => 'nullable|string|max:100',
            ]);

            if (!MistralNomenclatureAssistant::validatePayloadSafety($data)) {
                return response()->json(['success' => false, 'error' => 'Données invalides.'], 400);
            }

            $result = $mistral->suggestTypes($data);

            return response()->json([
                'success' => true,
                'types'   => $result['types'] ?? [],
                'source'  => 'Mistral',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ aiSuggestTypes: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
