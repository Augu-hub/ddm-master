<?php

namespace App\Http\Controllers\Process\Evaluations;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessEvaluationController extends Controller
{
    protected function t()
    {
        return DB::connection('tenant');
    }

    /**
     * 📋 PAGE PRINCIPALE — Évaluation des processus
     */
    public function index(Request $request)
    {
        $t = $this->t();
        $user = Auth::user();
        $step = $request->get('step', 'maturity');

        // 🔹 1. Fonction / Entité de l'utilisateur
        $link = $t->table('function_assignments as fa')
            ->join('entities as e', 'e.id', '=', 'fa.entity_id')
            ->join('functions as f', 'f.id', '=', 'fa.function_id')
            ->where('fa.user_id', $user->id)
            ->select(
                'fa.id',
                'fa.entity_id',
                'fa.function_id',
                'e.name as entity_name',
                'f.name as function_name'
            )
            ->first();

        if (!$link) {
            return Inertia::render('dashboards/Process/Core/Evaluations/ProcessEvaluation/Index', [
                'user' => $user,
                'link' => null,
                'macroProcesses' => [],
                'processes' => [],
                'maturityLevels' => [],
                'motricityScales' => [],
                'transversalityScales' => [],
                'strategicScales' => [],
                'step' => $step,
            ]);
        }

        // 🔹 2. Processus rattachés
        $assignmentIds = $t->table('assignments as a')
            ->join('assignment_functions as af', 'af.assignment_id', '=', 'a.id')
            ->where('a.entity_id', $link->entity_id)
            ->where('af.function_id', $link->function_id)
            ->where('a.mpa_type', 'process')
            ->pluck('a.mpa_id')
            ->unique()
            ->toArray();

        if (empty($assignmentIds)) {
            return Inertia::render('dashboards/Process/Core/Evaluations/ProcessEvaluation/Index', [
                'user' => $user,
                'link' => $link,
                'macroProcesses' => [],
                'processes' => [],
                'maturityLevels' => [],
                'motricityScales' => [],
                'transversalityScales' => [],
                'strategicScales' => [],
                'step' => $step,
            ]);
        }

        // 🔹 3. Processus et macros
        $processes = $t->table('processes')
            ->whereIn('id', $assignmentIds)
            ->get();

        $macroIds = $processes->pluck('macro_process_id')->unique()->toArray();
        $macroProcesses = $t->table('macro_processes')
            ->whereIn('id', $macroIds)
            ->get();

        // 🔹 4. Référentiels (Échelles)
        $maturityLevels = $t->table('process_maturity_scales as s')
            ->join('process_maturity_scale_levels as l', 'l.scale_id', '=', 's.id')
            ->select(
                's.code as scale_code',
                's.label as scale_label',
                's.description as scale_description',
                's.id as scale_id',
                'l.level_score',
                'l.level_label',
                'l.level_description'
            )
            ->orderBy('s.id')
            ->orderBy('l.level_score')
            ->get();

        $motricityScales = $t->table('process_motricity_scales')
            ->select('id', 'code', 'label', 'description')
            ->orderBy('id')
            ->get();

        $transversalityScales = $t->table('process_transversality_scales')
            ->select('id', 'code', 'label', 'description')
            ->orderBy('id')
            ->get();

        $strategicScales = $t->table('process_strategic_weight_scales')
            ->select('id', 'code', 'label', 'description')
            ->orderBy('id')
            ->get();

        // 🔹 5. Scores existants
        $scores = $t->table('process_criticality_evaluations')
            ->whereIn('process_id', $assignmentIds)
            ->get()
            ->keyBy('process_id');

        // Convertir en array correctement (objets → arrays)
        $processesArray = [];
        foreach ($processes as $p) {
            $pArray = (array) $p;  // Convertir objet en array
            $s = $scores[$p->id] ?? null;  // Accéder comme objet
            
            if ($s) {
                $pArray['maturity_score'] = $s->maturity_score;
                $pArray['motricity_score'] = $s->motricity_score;
                $pArray['transversality_score'] = $s->transversality_score;
                $pArray['strategic_score'] = $s->strategic_score;
                $pArray['criticality_score'] = $s->criticality_score;
            } else {
                $pArray['maturity_score'] = null;
                $pArray['motricity_score'] = null;
                $pArray['transversality_score'] = null;
                $pArray['strategic_score'] = null;
                $pArray['criticality_score'] = null;
            }
            
            $processesArray[] = $pArray;
        }
        
        $processes = $processesArray;

        // 🔹 6. Render
        return Inertia::render('dashboards/Process/Core/Evaluations/ProcessEvaluation/Index', [
            'user' => $user,
            'link' => $link,
            'macroProcesses' => $macroProcesses,
            'processes' => $processes,
            'maturityLevels' => $maturityLevels,
            'motricityScales' => $motricityScales,
            'transversalityScales' => $transversalityScales,
            'strategicScales' => $strategicScales,
            'step' => $step,
        ]);
    }

    /**
     * 💾 SAUVEGARDE — Maturité (12 critères)
     */
    public function saveMaturity(Request $request)
    {
        $t = $this->t();

        $validated = $request->validate([
            'evaluations' => 'required|array',
            'evaluations.*.process_id' => 'required|integer',
            'evaluations.*.criterion_code' => 'required|string',
            'evaluations.*.level_score' => 'required|integer|min:1|max:5',
        ]);

        try {
            DB::transaction(function () use ($t, $validated) {
                // Sauvegarde les 12 critères
                foreach ($validated['evaluations'] as $eval) {
                    $t->table('process_maturity_evaluations')->updateOrInsert(
                        [
                            'process_id' => $eval['process_id'],
                            'criterion_code' => $eval['criterion_code'],
                        ],
                        [
                            'level_score' => $eval['level_score'],
                            'evaluated_by' => Auth::id(),
                            'evaluated_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                // Calcul et sauvegarde de la moyenne
                $this->calculateMaturityScore($t, $validated['evaluations'][0]['process_id']);
                $this->calculateCriticality($t, $validated['evaluations'][0]['process_id']);
            });

            return response()->json([
                'success' => true,
                'message' => '✅ Maturité enregistrée',
            ]);

        } catch (\Exception $e) {
            \Log::error('ProcessEvaluation@saveMaturity', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 💾 SAUVEGARDE — Autres axes (Motricité, Transversalité, Stratégique)
     */
    public function saveAxis(Request $request)
    {
        $t = $this->t();

        $validated = $request->validate([
            'process_id' => 'required|integer',
            'axis' => 'required|string|in:motricity,transversality,strategic',
            'score' => 'required|integer|min:0|max:5',
        ]);

        try {
            $field = $validated['axis'] . '_score';

            // Update ou insert
            $t->table('process_criticality_evaluations')->updateOrInsert(
                ['process_id' => $validated['process_id']],
                [
                    $field => $validated['score'] > 0 ? $validated['score'] : null,
                    'updated_at' => now(),
                    'evaluated_at' => now(),
                ]
            );

            // Recalcul criticité
            $this->calculateCriticality($t, $validated['process_id']);

            return response()->json([
                'success' => true,
                'message' => '✅ Score enregistré',
            ]);

        } catch (\Exception $e) {
            \Log::error('ProcessEvaluation@saveAxis', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 🧮 CALCUL — Score de maturité (moyenne des 12 critères)
     */
    private function calculateMaturityScore($t, $processId)
    {
        $scores = $t->table('process_maturity_evaluations')
            ->where('process_id', $processId)
            ->pluck('level_score')
            ->toArray();

        if (empty($scores)) return;

        $average = array_sum($scores) / count($scores);

        $t->table('process_criticality_evaluations')->updateOrInsert(
            ['process_id' => $processId],
            [
                'maturity_score' => round($average, 2),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * 🧮 CALCUL — Criticité globale (moyenne des 4 axes)
     */
    private function calculateCriticality($t, $processId)
    {
        $eval = $t->table('process_criticality_evaluations')
            ->where('process_id', $processId)
            ->first();

        if (!$eval) return;

        $scores = [
            $eval->maturity_score,
            $eval->motricity_score,
            $eval->transversality_score,
            $eval->strategic_score,
        ];

        $valid = array_filter($scores, fn($s) => $s !== null && $s > 0);
        if (empty($valid)) {
            $t->table('process_criticality_evaluations')
                ->where('process_id', $processId)
                ->update(['criticality_score' => null]);
            return;
        }

        $average = array_sum($valid) / count($valid);

        $t->table('process_criticality_evaluations')
            ->where('process_id', $processId)
            ->update([
                'criticality_score' => round($average, 2),
                'updated_at' => now(),
            ]);
    }
}