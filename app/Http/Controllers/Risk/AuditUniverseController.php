<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\Audit\Risk;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════════════════════════
 * 📋 AUDIT UNIVERSE CONTROLLER - FINAL AVEC COULEURS
 * ════════════════════════════════════════════════════════════════════════════════════
 * 
 * ✅ Compatible avec colonnes existantes (SANS is_from_ddm)
 * ✅ Gère session entité + année
 * ✅ Auto-calcule qualification nette côté client
 * ✅ Affiche niveaux NUMÉRIQUES + couleurs pour impact/fréquence bruts
 * ✅ Sauvegarde asynchrone
 */
class AuditUniverseController extends Controller
{
    /**
     * GET /audit/universe
     * Page principale
     */
    public function index(Request $request)
    {
        try {
            // Charger entités
            $entities = DB::table('entities')
                ->select('id', 'code_base', 'name')
                ->orderBy('name')
                ->get();

            // Charger types risque
            $riskTypes = DB::table('risk_types')
                ->select('id', 'code', 'label', 'color')
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get();

            // Charger fréquences
            $frequencies = DB::table('audit_frequency_levels')
                ->select('id', 'level', 'label', 'color')
                ->whereNull('deleted_at')
                ->orderBy('level')
                ->get();

            // Charger impacts
            $impacts = DB::table('audit_impact_levels')
                ->select('id', 'level', 'label', 'color')
                ->whereNull('deleted_at')
                ->orderBy('level')
                ->get();

            // Charger matrice (COMPLÈTE pour calculs côté client)
            $matrix = DB::table('audit_matrix')
                ->select('id', 'frequency_level', 'impact_level', 'qualification')
                ->whereNull('deleted_at')
                ->get();

            // Charger processus
            $processes = DB::table('processes')
                ->select('id', 'code', 'name')
                ->orderBy('code')
                ->get();

            // Charger activités
            $activities = DB::table('activities')
                ->select('id', 'code', 'name')
                ->orderBy('code')
                ->get();

            // Années
            $years = array_reverse(range(date('Y') - 4, date('Y')));

            return Inertia::render('dashboards/Audit/universe', [
                'entities' => $entities,
                'processes' => $processes,
                'activities' => $activities,
                'riskTypes' => $riskTypes,
                'frequencies' => $frequencies,
                'impacts' => $impacts,
                'matrix' => $matrix,
                'initialRisks' => [],
                'years' => $years,
            ]);

        } catch (\Exception $e) {
            Log::error('Universe index error: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/audit/universe/set-session
     * Définir la session entité + année
     */
    public function setSession(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_id' => 'required|integer|exists:entities,id',
                'year' => 'required|integer|min:2000|max:2100',
            ]);

            session([
                'audit_entity_id' => $validated['entity_id'],
                'audit_year' => $validated['year'],
            ]);

            Log::info('Audit session set', [
                'entity_id' => $validated['entity_id'],
                'year' => $validated['year']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session définie',
            ]);

        } catch (\Exception $e) {
            Log::error('Set session error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/audit/universe/load-risks
     * Charger risques pour entité + année AVEC COULEURS ET NIVEAUX
     */
    public function loadRisks(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_id' => 'required|integer|exists:entities,id',
                'year' => 'required|integer|min:2000|max:2100',
            ]);

            $risks = Risk::select(
                'id', 'code', 'label', 'description',
                'risk_type_id', 'frequency_level_id', 'frequency_net',
                'impact_level_id', 'impact_net', 'criticality', 'owner',
                'control_procedure', 'status', 'entity_id', 'process_id',
                'activity_id', 'year'
            )
            ->where('entity_id', $validated['entity_id'])
           
            ->orderBy('code')
            ->get()
            ->map(function ($risk) {
                // ════════════════════════════════════════════════════════════════
                // ENRICHISSEMENT DES DONNÉES AVEC COULEURS & NIVEAUX NUMÉRIQUES
                // ════════════════════════════════════════════════════════════════

                // Récupérer FRÉQUENCE BRUTE avec couleur, label et niveau numérique
                $frequency = DB::table('risk_frequency_levels')
                    ->where('id', $risk->frequency_level_id)
                    ->whereNull('deleted_at')
                    ->first();
                
                $risk->frequency_color = $frequency?->color ?? 'secondary';
                $risk->frequency_label = $frequency?->label ?? '-';
                $risk->frequency_level = $frequency?->level ?? null;  // Niveau numérique 1-5
                
                // Récupérer IMPACT BRUT avec couleur, label et niveau numérique
                $impact = DB::table('risk_impact_levels')
                    ->where('id', $risk->impact_level_id)
                    ->whereNull('deleted_at')
                    ->first();
                
                $risk->impact_color = $impact?->color ?? 'secondary';
                $risk->impact_label = $impact?->label ?? '-';
                $risk->impact_level = $impact?->level ?? null;  // Niveau numérique 1-5
                
                return $risk;
            });

            return response()->json([
                'success' => true,
                'risks' => $risks,
                'colors' => $this->getColorPalette(), // ← Palette de couleurs Bootstrap
            ]);

        } catch (\Exception $e) {
            Log::error('Load risks error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/audit/universe/update-risk/{id}
     * Mettre à jour un champ du risque
     */
    public function updateRiskField(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'field' => 'required|string|in:impact_net,frequency_net,is_evaluated,control_procedure',
                'value' => 'nullable',
            ]);

            $risk = Risk::findOrFail($id);

            // Mise à jour simple
            $risk->update([
                $validated['field'] => $validated['value'],
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Risque mis à jour',
                'risk' => $risk,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Update risk field error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/audit/universe/create-risk
     * Créer un risque
     */
    public function createRisk(Request $request)
    {
        try {
            $entityId = session('audit_entity_id') ?? $request->input('entity_id');
            $year = session('audit_year') ?? $request->input('year');

            if (!$entityId || !$year) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session non définie ou données manquantes',
                ], 400);
            }

            $validated = $request->validate([
                'label' => 'required|string|max:500',
                'description' => 'nullable|string|max:2000',
                'risk_type_id' => 'nullable|integer|exists:risk_types,id',
                'frequency_level_id' => 'nullable|integer|exists:risk_frequency_levels,id',
                'impact_level_id' => 'nullable|integer|exists:risk_impact_levels,id',
                'activity_id' => 'nullable|integer|exists:activities,id',
                'process_id' => 'nullable|integer|exists:processes,id',
                'control_procedure' => 'nullable|string|max:5000',
            ]);

            // Générer code
            $code = $this->generateCode($validated['risk_type_id']);

            // Calculer criticité BRUTE
            $criticality = null;
            if ($validated['frequency_level_id'] && $validated['impact_level_id']) {
                $freq = DB::table('risk_frequency_levels')
                    ->where('id', $validated['frequency_level_id'])
                    ->whereNull('deleted_at')
                    ->first();
                    
                $impact = DB::table('risk_impact_levels')
                    ->where('id', $validated['impact_level_id'])
                    ->whereNull('deleted_at')
                    ->first();
                    
                if ($freq && $impact) {
                    $criticality = $freq->level * $impact->level;
                }
            }

            // Créer le risque
            $risk = Risk::create([
                'code' => $code,
                'label' => $validated['label'],
                'description' => $validated['description'],
                'risk_type_id' => $validated['risk_type_id'],
                'frequency_level_id' => $validated['frequency_level_id'],
                'impact_level_id' => $validated['impact_level_id'],
                'criticality' => $criticality,
                'entity_id' => $entityId,
                'process_id' => $validated['process_id'],
                'activity_id' => $validated['activity_id'],
                'control_procedure' => $validated['control_procedure'],
                'status' => 'identified',
                'year' => $year,
                'created_by' => auth()->id(),
                'tenant_id' => tenant('id') ?? 1,
            ]);

            Log::info('Risk created', [
                'risk_id' => $risk->id,
                'entity_id' => $entityId,
                'code' => $code
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Risque créé',
                'risk' => $risk
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Create risk error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * ════════════════════════════════════════════════════════════════════════════════════
     * HELPERS
     * ════════════════════════════════════════════════════════════════════════════════════
     */

    /**
     * Palette de couleurs Bootstrap (même que Settings)
     */
    private function getColorPalette(): array
    {
        return [
            'danger' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#0dcaf0',
            'success' => '#28a745',
            'secondary' => '#6c757d',
            'primary' => '#0d6efd'
        ];
    }

    /**
     * Génère un code unique pour un risque
     */
    private function generateCode(?int $riskTypeId): string
    {
        try {
            if (!$riskTypeId) return 'RX-001';

            $type = DB::table('risk_types')
                ->where('id', $riskTypeId)
                ->whereNull('deleted_at')
                ->first();
                
            if (!$type || !$type->code) return 'RX-001';

            $code = strtoupper(substr($type->code, 0, 2));
            
            $last = Risk::where('risk_type_id', $riskTypeId)
                ->orderBy('code', 'desc')
                ->first();

            $seq = 1;
            if ($last && preg_match('/-(\d+)$/', $last->code, $m)) {
                $seq = intval($m[1]) + 1;
            }

            return $code . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);

        } catch (\Exception $e) {
            Log::error('Code generation error: ' . $e->getMessage());
            return 'RX-' . rand(100, 999);
        }
    }
}