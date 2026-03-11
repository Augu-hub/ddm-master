<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\Param\{
    Entity, Factor, FactorScale, MissionSource, AuditFunction, Process,
    ProcessFunctionMapping, Risk, FactorEvaluation, ProcessEvaluationSummary,
    MissionRequest, MissionRequestEntity, MissionRequestFactor, AnnualPlan, Mission, MissionTeam
};
use App\Models\Param\Entite;
use App\Models\Param\Processus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;




class ProcessFactorEvaluationController extends Controller
{
    public function index(Request $request)
    {
        $entity = $this->getEntity($request);
        $year = $request->input('year', date('Y'));

        return Inertia::render('audit/ProcessFactorEvaluation', [
            'entity' => $entity->only(['id', 'name']),
            'processes' => Processus::where('entity_id', $entity->id)
                ->where('level', 0)
                ->with(['factorEvaluations' => function ($q) use ($year) {
                    $q->where('evaluation_year', $year);
                }])
                ->get(),
            'factors' => Factor::where('entity_id', $entity->id)
                ->orderBy('order_position')
                ->get(),
            'scales' => FactorScale::where('entity_id', $entity->id)
                ->orderBy('value')
                ->get(),
            'year' => (int)$year,
            'summaries' => ProcessEvaluationSummary::where('entity_id', $entity->id)
                ->where('evaluation_year', $year)
                ->get(),
        ]);
    }

    public function updateFactorEvaluation(Request $request, $processId)
    {
        $process = Processus::findOrFail($processId);
        $entity = $process->entity;

        $validated = $request->validate([
            'factor_id' => 'required|exists:audit_factors,id',
            'score' => 'required|integer|min:1|max:4',
            'justification' => 'nullable|string|max:1000',
            'year' => 'required|integer'
        ]);

        FactorEvaluation::updateOrCreate(
            [
                'process_id' => $processId,
                'factor_id' => $validated['factor_id'],
                'evaluation_year' => $validated['year']
            ],
            [
                'entity_id' => $entity->id,
                'score' => $validated['score'],
                'normalized_score' => round($validated['score'] / 4, 2),
                'justification' => $validated['justification']
            ]
        );

        $this->recalculateSummary($processId, $entity->id, $validated['year']);

        return back()->with('success', 'Évaluation sauvegardée');
    }

    private function recalculateSummary($processId, $entityId, $year)
    {
        $evals = FactorEvaluation::where('process_id', $processId)
            ->where('evaluation_year', $year)
            ->get();

        if ($evals->isEmpty()) return;

        $averageScore = $evals->avg('normalized_score');

        $rating = match (true) {
            $averageScore >= 0.75 => 'Critique',
            $averageScore >= 0.5 => 'Considérable',
            $averageScore >= 0.25 => 'Moyen',
            default => 'Mineur'
        };

        $frequency = match (true) {
            $averageScore >= 0.75 => 1,
            $averageScore >= 0.5 => 2,
            $averageScore >= 0.25 => 3,
            default => 4
        };

        ProcessEvaluationSummary::updateOrCreate(
            ['process_id' => $processId, 'evaluation_year' => $year],
            [
                'entity_id' => $entityId,
                'average_score' => round($averageScore, 2),
                'rating' => $rating,
                'audit_frequency' => $frequency
            ]
        );
    }

    private function getEntity(Request $request): Entite
    {
        $entityId = $request->input('entity_id') ?? auth()->user()->entity_id;
        return Entite::findOrFail($entityId);
    }
}
