<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\Param\{
    Entity, Factor, FactorScale, MissionSource, AuditFunction, Process,
    ProcessFunctionMapping, Risk, FactorEvaluation, ProcessEvaluationSummary,
    MissionRequest, MissionRequestEntity, MissionRequestFactor, AnnualPlan, Mission, MissionTeam
};
use App\Models\Param\Entite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;



class MissionPrioritizationController extends Controller
{
    public function index(Request $request)
    {
        $entity = $this->getEntity($request);
        $madSource = MissionSource::where('entity_id', $entity->id)
            ->where('code', 'MAD')->first();

        $missions = MissionRequest::where('entity_id', $entity->id);
        if ($madSource) {
            $missions = $missions->where('mission_source_id', $madSource->id);
        }

        $missions = $missions->with('factorScores.factor')->get();

        // Calculer coefficient pour chaque mission
        $missionsData = $missions->map(function ($mission) use ($entity) {
            $factors = Factor::where('entity_id', $entity->id)->count();
            $totalScore = $mission->factorScores->sum('score');
            $coefficient = $factors > 0 ? round($totalScore / $factors, 2) : 0;

            return [
                'id' => $mission->id,
                'code' => $mission->code,
                'mission_objective' => $mission->mission_objective,
                'coefficient' => $coefficient,
                'factorScores' => $mission->factorScores,
                'totalScore' => $totalScore
            ];
        })->sortByDesc('coefficient')->values();

        return Inertia::render('dashboards/Audit/MissionPrioritization', [
            'entity' => $entity->only(['id', 'name']),
            'missions' => $missionsData,
            'factors' => Factor::where('entity_id', $entity->id)
                ->orderBy('order_position')
                ->get(['id', 'order_position', 'label']),
            'scales' => FactorScale::where('entity_id', $entity->id)
                ->orderBy('value')
                ->get(['id', 'value', 'label']),
        ]);
    }

    public function updateMissionFactors(Request $request, $missionId)
    {
        $mission = MissionRequest::findOrFail($missionId);

        $validated = $request->validate([
            'factors' => 'required|array',
            'factors.*.factor_id' => 'required|exists:audit_factors,id',
            'factors.*.score' => 'required|integer|min:0|max:4',
        ]);

        foreach ($validated['factors'] as $factorData) {
            MissionRequestFactor::updateOrCreate(
                ['mission_request_id' => $missionId, 'factor_id' => $factorData['factor_id']],
                ['entity_id' => $mission->entity_id, 'score' => $factorData['score']]
            );
        }

        return back()->with('success', 'Facteurs et priorité calculés');
    }

    private function getEntity(Request $request): Entite
    {
        $entityId = $request->input('entity_id') ?? auth()->user()->entity_id;
        return Entite::findOrFail($entityId);
    }
}

