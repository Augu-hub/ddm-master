<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\Param\MissionRequest;
use App\Models\Audit\Param\Factor;
use App\Models\Audit\Param\FactorScore;
use App\Models\Audit\Param\FactorScale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MissionPrioritizationController extends Controller
{
    /**
     * GET /m/audit.core/api/audit/prioritization
     * ✅ AFFICHE LE TABLEAU AVEC MISSIONS, FACTEURS ET SCALES + ANCIEN SCORES
     */
    public function index()
    {
        try {
            // ✅ CHARGER LES MISSIONS AVEC RELATIONS
            $missions = MissionRequest::with(['entity', 'process'])
                ->orderBy('requested_date', 'desc')
                ->orderBy('code', 'asc')
                ->get()
                ->map(function ($mission) {
                    return [
                        'id' => (int)$mission->id,
                        'code' => $mission->code,
                        'mission_objective' => $mission->mission_objective ?? '',
                        'description' => $mission->description,
                        'requested_date' => $mission->requested_date?->format('Y-m-d'),
                        'start_date' => $mission->start_date?->format('Y-m-d'),
                        'end_date' => $mission->end_date?->format('Y-m-d'),
                        'status' => $mission->status,
                        'entity' => [
                            'id' => (int)($mission->entity?->id ?? 0),
                            'name' => $mission->entity?->name ?? 'N/A',
                        ],
                        'process' => [
                            'id' => (int)($mission->process?->id ?? 0),
                            'name' => $mission->process?->name ?? 'N/A',
                        ],
                        'coefficient' => (float)($mission->coefficient ?? 0),
                        'level' => $mission->level,
                    ];
                })
                ->toArray();

            // ✅ CHARGER LES FACTEURS ACTIFS
            $factors = Factor::where('is_active', 1)
                ->orderBy('order_position', 'asc')
                ->get()
                ->map(function ($factor) {
                    return [
                        'id' => (int)$factor->id,
                        'order_position' => (int)$factor->order_position,
                        'label' => $factor->label,
                        'description' => $factor->description,
                        'weight' => (float)($factor->weight ?? 25),
                    ];
                })
                ->toArray();

            // ✅ CHARGER LES FACTOR SCALES (AVEC COULEURS)
            $factorScales = FactorScale::orderBy('value', 'asc')
                ->get()
                ->map(function ($scale) {
                    return [
                        'id' => (int)$scale->id,
                        'value' => (int)$scale->value,
                        'label' => $scale->label,
                        'description' => $scale->description,
                        'color' => $scale->color ?? '#808080',
                    ];
                })
                ->toArray();

            // ✅ CHARGER LES SCORES FACTEURS (ACTUELS)
            $currentScores = FactorScore::all()
                ->groupBy('mission_id')
                ->map(function ($items) {
                    $result = [];
                    foreach ($items as $item) {
                        $result[(int)$item->factor_id] = (int)$item->score;
                    }
                    return [
                        'scores' => $result,
                    ];
                })
                ->toArray();

            // ✅ CHARGER LES ANCIENS SCORES (HISTORIQUE)
            // Si vous avez une table d'historique, sinon garder les scores actuels
            $allScores = $this->getAllScoresWithHistory($currentScores);

            Log::info("✅ Prioritization index: " . count($missions) . " missions, " . count($factors) . " factors");

            return Inertia::render('dashboards/Audit/MissionPrioritization', [
                'missions' => $missions,
                'factors' => $factors,
                'factorScales' => $factorScales,  // ← Scales avec couleurs
                'factorScores' => $allScores,     // ← Scores actuels + historique
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Index error: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * GET /m/audit.core/api/audit/prioritization/{missionId}
     * ✅ AFFICHE UNE MISSION SPÉCIFIQUE AVEC HISTORIQUE
     */
    public function show($missionId)
    {
        try {
            $mission = MissionRequest::with(['entity', 'process'])
                ->findOrFail($missionId);

            $currentScores = FactorScore::where('mission_id', $missionId)
                ->get()
                ->pluck('score', 'factor_id')
                ->map(fn($score) => (int)$score)
                ->toArray();

            // ✅ Charger historique si disponible
            $allScores = $this->getScoresWithHistory($missionId, $currentScores);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $mission->id,
                    'code' => $mission->code,
                    'mission_objective' => $mission->mission_objective,
                    'entity' => $mission->entity?->name,
                    'coefficient' => $mission->coefficient,
                    'level' => $mission->level,
                    'factorScores' => $allScores,  // ← Incluant historique
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Show error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /m/audit.core/api/audit/prioritization/{missionId}
     * ✅ SAUVEGARDE LES SCORES FACTEURS
     */
    public function updateMissionFactors(Request $request, $missionId)
    {
        try {
            $mission = MissionRequest::findOrFail($missionId);

            $validated = $request->validate([
                'factor_scores' => 'required|array',
                'factor_scores.*' => 'nullable|integer|min:0|max:5',
            ]);

            // ✅ SAUVEGARDER LES SCORES DES FACTEURS
            if (is_array($validated['factor_scores'])) {
                foreach ($validated['factor_scores'] as $factorId => $score) {
                    if ($score !== null && $score > 0) {
                        // ✅ VÉRIFIER L'ANCIEN SCORE POUR HISTORIQUE
                        $oldScore = FactorScore::where('mission_id', (int)$missionId)
                            ->where('factor_id', (int)$factorId)
                            ->first();

                        // Log si changement
                        if ($oldScore && $oldScore->score != $score) {
                            Log::info("📊 Mission {$mission->code} Factor {$factorId}: {$oldScore->score} → {$score}");
                        }

                        // Créer ou mettre à jour
                        FactorScore::updateOrCreate(
                            [
                                'mission_id' => (int)$missionId,
                                'factor_id' => (int)$factorId,
                            ],
                            [
                                'score' => (int)$score,
                                'updated_at' => now(),  // ← Pour tracking historique
                            ]
                        );
                    }
                }
            }

            // ✅ CALCULER ET SAUVEGARDER COEFFICIENT + LEVEL
            $this->calculateAndSaveMissionMetrics($missionId);

            $mission->refresh();

            Log::info("✅ Mission {$mission->code} scores updated successfully");

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $mission->id,
                    'code' => $mission->code,
                    'coefficient' => (float)$mission->coefficient,
                    'level' => $mission->level,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /m/audit.core/api/audit/prioritization/{missionId}/calculate
     * ✅ CALCULE LE COEFFICIENT ET LE LEVEL
     */
    public function calculateCoefficient(Request $request, $missionId)
    {
        try {
            $mission = MissionRequest::findOrFail($missionId);

            // CALCULER
            $this->calculateAndSaveMissionMetrics($missionId);

            $mission->refresh();

            Log::info("✅ Mission {$mission->code} coefficient calculated: {$mission->coefficient}, level: {$mission->level}");

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $mission->id,
                    'code' => $mission->code,
                    'coefficient' => (float)$mission->coefficient,
                    'level' => $mission->level,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Calculate error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /m/audit.core/api/audit/prioritization/stats/summary
     * ✅ RETOURNE LES STATISTIQUES RÉSUMÉES
     */
    public function getStatsSummary()
    {
        try {
            $missions = MissionRequest::all();

            $stats = [
                'total' => $missions->count(),
                'evaluated' => $missions->where('coefficient', '>', 0)->count(),
                'critique' => $missions->where('level', 'Critique')->count(),
                'considerable' => $missions->where('level', 'Considérable')->count(),
                'important' => $missions->where('level', 'Important')->count(),
                'mineur' => $missions->where('level', 'Mineur')->count(),
                'avg_coefficient' => round($missions->avg('coefficient') ?? 0, 2),
            ];

            Log::info("📊 Stats summary retrieved: " . json_encode($stats));

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Stats error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /m/audit.core/api/audit/prioritization/export/csv
     * ✅ EXPORTE LES MISSIONS EN CSV (AVEC HISTORIQUE)
     */
    public function exportCsv()
    {
        try {
            $missions = MissionRequest::with(['entity'])
                ->orderBy('coefficient', 'desc')
                ->get();

            $factors = Factor::where('is_active', 1)
                ->orderBy('order_position', 'asc')
                ->get();

            // CRÉER LE CSV
            $csv = "Code,Mission,Entity,";
            
            // En-têtes des facteurs
            foreach ($factors as $factor) {
                $csv .= "{$factor->label},";
            }
            
            $csv .= "Total,Coefficient,Level,LastUpdated\n";

            // Données
            foreach ($missions as $mission) {
                $scores = FactorScore::where('mission_id', $mission->id)
                    ->pluck('score', 'factor_id')
                    ->toArray();

                $total = array_sum($scores);
                $coefficient = $factors->count() > 0 ? round($total / $factors->count(), 2) : 0;
                $lastUpdated = $mission->updated_at?->format('Y-m-d H:i:s') ?? '—';

                $csv .= "\"{$mission->code}\",\"{$mission->mission_objective}\",\"{$mission->entity?->name}\",";

                foreach ($factors as $factor) {
                    $score = $scores[$factor->id] ?? 0;
                    $csv .= "{$score},";
                }

                $csv .= "{$total},{$coefficient},{$mission->level},{$lastUpdated}\n";
            }

            Log::info("✅ CSV exported: " . count($missions) . " missions");

            return response()
                ->streamDownload(
                    fn() => print($csv),
                    'missions-prioritization-' . now()->format('Y-m-d-His') . '.csv',
                    ['Content-Type' => 'text/csv']
                );

        } catch (\Exception $e) {
            Log::error('❌ Export error: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * GET /m/audit.core/api/audit/prioritization/{missionId}/history
     * ✅ RETOURNE L'HISTORIQUE DES SCORES (NOUVEAU!)
     */
    public function getScoreHistory($missionId)
    {
        try {
            $mission = MissionRequest::findOrFail($missionId);

            // Si vous avez une table d'historique:
            // $history = ScoreHistory::where('mission_id', $missionId)->orderBy('created_at', 'desc')->get();
            
            // Pour maintenant, retourner les scores actuels
            $scores = FactorScore::where('mission_id', $missionId)
                ->get()
                ->map(function ($score) {
                    return [
                        'factor_id' => (int)$score->factor_id,
                        'score' => (int)$score->score,
                        'updated_at' => $score->updated_at?->format('Y-m-d H:i:s'),
                    ];
                })
                ->toArray();

            Log::info("✅ Score history retrieved for mission {$mission->code}");

            return response()->json([
                'success' => true,
                'data' => [
                    'mission_id' => $mission->id,
                    'mission_code' => $mission->code,
                    'scores' => $scores,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ History error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * HELPER: Obtenir tous les scores avec historique
     * ✅ Formatte les scores pour le frontend
     */
    private function getAllScoresWithHistory($currentScores)
    {
        $result = [];
        
        foreach ($currentScores as $missionId => $scoreData) {
            $result[(int)$missionId] = [
                'scores' => $scoreData['scores'] ?? $scoreData,
            ];
        }
        
        return $result;
    }

    /**
     * HELPER: Obtenir les scores d'une mission avec historique
     */
    private function getScoresWithHistory($missionId, $currentScores)
    {
        return [
            'scores' => $currentScores,
            // Plus tard: ajouter 'history' => [scores précédents]
        ];
    }

    /**
     * HELPER: Calculer et sauvegarder coefficient + level
     */
    private function calculateAndSaveMissionMetrics($missionId)
    {
        $mission = MissionRequest::findOrFail($missionId);
        $factors = Factor::where('is_active', 1)->get();

        // CALCULER COEFFICIENT
        $scores = FactorScore::where('mission_id', $missionId)
            ->pluck('score')
            ->toArray();

        $totalScore = array_sum($scores);
        $coefficient = $factors->count() > 0 ? round($totalScore / $factors->count(), 2) : 0;

        // DÉTERMINER LEVEL
        $level = $this->determineLevelFromCoefficient($coefficient);

        // SAUVEGARDER
        $mission->update([
            'coefficient' => $coefficient,
            'level' => $level,
        ]);

        Log::info("✅ Mission {$mission->code}: coeff={$coefficient}, level={$level}");
    }

    /**
     * HELPER: Déterminer le level basé sur le coefficient
     */
    private function determineLevelFromCoefficient($coefficient)
    {
        if ($coefficient >= 3.0) return 'Critique';
        if ($coefficient >= 2.0) return 'Considérable';
        if ($coefficient >= 1.0) return 'Important';
        return 'Mineur';
    }

    /**
     * HELPER: Calculer la moyenne d'un facteur
     * Utile pour les statistiques
     */
    public function getFactorAverage($factorId, $year = null)
    {
        try {
            $query = FactorScore::where('factor_id', $factorId);

            if ($year) {
                $query->whereYear('created_at', $year);
            }

            $avg = $query->avg('score') ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'factor_id' => (int)$factorId,
                    'average' => round((float)$avg, 2),
                    'year' => $year ?? 'all',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Factor average error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}