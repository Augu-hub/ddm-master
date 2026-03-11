<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\Param\AuditFactor;
use App\Models\Audit\Param\FactorEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AuditFactorYearController extends Controller
{
    /**
     * GET /m/audit.core/audit/factors-by-year
     * ✅ AFFICHE LES FACTEURS LIÉS AUX ANNÉES D'EXERCICE
     * Récupère les années distinctes depuis audit_factor_evaluations
     */
    public function factorsByYear(Request $request)
    {
        try {
            // ✅ RÉCUPÉRER LES ANNÉES DISTINCTES DEPUIS LES ÉVALUATIONS
            $availableYears = FactorEvaluation::distinct('evaluation_year')
                ->where('evaluation_year', '!=', null)
                ->orderByDesc('evaluation_year')
                ->pluck('evaluation_year')
                ->toArray();

            // ✅ CHARGER LES FACTEURS ACTIFS (SANS entity_id, GLOBAUX)
            $allFactors = Factor::where('is_active', 1)
                ->orderBy('order_position', 'asc')
                ->get();

            // ✅ POUR CHAQUE ANNÉE, RÉCUPÉRER LES FACTEURS LIÉS
            $factorsByYear = [];
            foreach ($availableYears as $year) {
                $factorsForYear = FactorEvaluation::where('evaluation_year', $year)
                    ->distinct('factor_id')
                    ->pluck('factor_id')
                    ->toArray();

                $factorsByYear[$year] = [
                    'year' => $year,
                    'count' => count($factorsForYear),
                    'factors' => Factor::whereIn('id', $factorsForYear)
                        ->orderBy('order_position', 'asc')
                        ->get()
                        ->map(function ($factor) use ($year) {
                            return [
                                'id' => $factor->id,
                                'order_position' => $factor->order_position,
                                'label' => $factor->label,
                                'description' => $factor->description,
                                'importance' => $factor->importance,
                                'weight' => $factor->weight,
                                'evaluations_count' => FactorEvaluation::where('factor_id', $factor->id)
                                    ->where('evaluation_year', $year)
                                    ->count(),
                            ];
                        })
                        ->toArray()
                ];
            }

            Log::info("✅ Facteurs chargés pour " . count($availableYears) . " années");

            return Inertia::render('dashboards/Audit/FactorsByYear', [
                'availableYears' => $availableYears,
                'factorsByYear' => $factorsByYear,
                'allFactors' => $allFactors,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ AuditFactorYearController@factorsByYear: ' . $e->getMessage());
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * GET /m/audit.core/api/audit/factors-by-year/{year}
     * ✅ RETOURNE LES FACTEURS POUR UNE ANNÉE SPÉCIFIQUE (JSON)
     */
    public function getFactorsForYear($year)
    {
        try {
            // ✅ VÉRIFIER QUE L'ANNÉE EXISTE
            $evaluationsExists = FactorEvaluation::where('evaluation_year', $year)->exists();

            if (!$evaluationsExists) {
                return response()->json([
                    'success' => false,
                    'error' => "Aucune évaluation pour l'année $year"
                ], 404);
            }

            // ✅ RÉCUPÉRER LES FACTEURS DE CETTE ANNÉE
            $factors = FactorEvaluation::where('evaluation_year', $year)
                ->distinct('factor_id')
                ->pluck('factor_id')
                ->toArray();

            $factorsData = Factor::whereIn('id', $factors)
                ->orderBy('order_position', 'asc')
                ->get()
                ->map(function ($factor) use ($year) {
                    // ✅ RÉCUPÉRER LES ÉVALUATIONS POUR CE FACTEUR CETTE ANNÉE
                    $evaluations = FactorEvaluation::where('factor_id', $factor->id)
                        ->where('evaluation_year', $year)
                        ->get();

                    // ✅ CALCULER LES MOYENNES
                    $averageScore = $evaluations->avg('score') ?? 0;
                    $averageNormalizedScore = $evaluations->avg('normalized_score') ?? 0;

                    return [
                        'id' => $factor->id,
                        'order_position' => $factor->order_position,
                        'label' => $factor->label,
                        'description' => $factor->description,
                        'importance' => $factor->importance,
                        'weight' => (float)$factor->weight,
                        'evaluation_year' => $year,
                        'evaluations_count' => $evaluations->count(),
                        'average_score' => round($averageScore, 2),
                        'average_normalized_score' => round($averageNormalizedScore, 4),
                        'evaluations' => $evaluations->map(function ($eval) {
                            return [
                                'entity_id' => $eval->entity_id,
                                'process_id' => $eval->process_id,
                                'score' => $eval->score,
                                'normalized_score' => $eval->normalized_score,
                                'justification' => $eval->justification,
                            ];
                        })->toArray(),
                    ];
                })
                ->toArray();

            Log::info("✅ Facteurs récupérés pour l'année $year: " . count($factorsData) . " facteurs");

            return response()->json([
                'success' => true,
                'year' => $year,
                'count' => count($factorsData),
                'factors' => $factorsData,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ AuditFactorYearController@getFactorsForYear: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /m/audit.core/api/audit/factors/link-year
     * ✅ LIE UN FACTEUR À UNE ANNÉE (CRÉE UNE ÉVALUATION)
     */
    public function linkFactorToYear(Request $request)
    {
        try {
            $validated = $request->validate([
                'factor_id' => 'required|integer|exists:audit_factors,id',
                'evaluation_year' => 'required|integer|min:2000|max:' . date('Y'),
                'score' => 'nullable|integer|min:1|max:5',
                'justification' => 'nullable|string|max:500',
            ]);

            // ✅ VÉRIFIER SI LA LIAISON EXISTE DÉJÀ
            $existing = FactorEvaluation::where('factor_id', $validated['factor_id'])
                ->where('evaluation_year', $validated['evaluation_year'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce facteur est déjà lié à cette année'
                ], 409);
            }

            // ✅ CRÉER L'ÉVALUATION
            // NOTE: Sans entity_id et process_id pour la liaison globale
            $evaluation = FactorEvaluation::create([
                'factor_id' => $validated['factor_id'],
                'evaluation_year' => $validated['evaluation_year'],
                'score' => $validated['score'] ?? 1,
                'normalized_score' => ($validated['score'] ?? 1) * 0.25, // Score / 4
                'justification' => $validated['justification'] ?? null,
                'entity_id' => null, // Global
                'process_id' => null, // Global
            ]);

            Log::info("✅ Facteur {$validated['factor_id']} lié à l'année {$validated['evaluation_year']}");

            return response()->json([
                'success' => true,
                'message' => 'Facteur lié à l\'année avec succès',
                'data' => $evaluation
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ AuditFactorYearController@linkFactorToYear: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /m/audit.core/api/audit/factors/unlink-year/{evaluationId}
     * ✅ DÉLIE UN FACTEUR D'UNE ANNÉE
     */
    public function unlinkFactorFromYear($evaluationId)
    {
        try {
            $evaluation = FactorEvaluation::findOrFail($evaluationId);
            $year = $evaluation->evaluation_year;
            $factorId = $evaluation->factor_id;

            $evaluation->delete();

            Log::info("✅ Facteur $factorId délié de l'année $year");

            return response()->json([
                'success' => true,
                'message' => 'Facteur délié avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ AuditFactorYearController@unlinkFactorFromYear: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}