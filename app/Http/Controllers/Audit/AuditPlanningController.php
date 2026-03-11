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


// ════════════════════════════════════════════════════════════════════════════════════
// 5️⃣ PLANIFICATION AUDIT (ABR)
// ════════════════════════════════════════════════════════════════════════════════════

class AuditPlanningController extends Controller
{
    public function index(Request $request)
    {
        $entity = $this->getEntity($request);
        $year = $request->input('year', date('Y'));

        $risks = Risk::where('entity_id', $entity->id)
            ->where('is_active', true)
            ->orderByDesc('criticality_raw')
            ->with('process')
            ->get()
            ->map(function ($risk, $index) {
                return [
                    ...$risk->toArray(),
                    'priority_rank' => $index + 1
                ];
            });

        return Inertia::render('dashboards/Audit//AuditPlanning', [
            'entity' => $entity->only(['id', 'name']),
            'risks' => $risks,
            'processes' => Processus::where('entity_id', $entity->id)->get(['id', 'code', 'name']),
            'entities' => Entite::where('id', $entity->id)
                ->orWhere('parent_id', $entity->id)
                ->get(['id', 'code', 'name']),
            'annualPlans' => AnnualPlan::where('entity_id', $entity->id)
                ->where('fiscal_year', $year)
                ->with('missions')
                ->get(),
            'year' => (int)$year,
        ]);
    }

    public function generateMissions(Request $request)
    {
        $entity = $this->getEntity($request);

        $validated = $request->validate([
            'year' => 'required|integer',
            'risk_ids' => 'required|array|exists:audit_risks,id'
        ]);

        $year = $validated['year'];

        $plan = AnnualPlan::firstOrCreate(
            ['entity_id' => $entity->id, 'fiscal_year' => $year],
            ['status' => 'draft']
        );

        $mbrSource = MissionSource::where('entity_id', $entity->id)
            ->where('code', 'MBR')->first();

        if (!$mbrSource) {
            return back()->with('error', 'Source MBR non trouvée');
        }

        $rank = 1;
        foreach ($validated['risk_ids'] as $riskId) {
            $risk = Risk::findOrFail($riskId);

            Mission::firstOrCreate(
                [
                    'annual_plan_id' => $plan->id,
                    'risk_id' => $riskId
                ],
                [
                    'entity_id' => $entity->id,
                    'code' => 'MIS-' . str_pad($rank, 4, '0', STR_PAD_LEFT) . '-' . $year,
                    'mission_source_id' => $mbrSource->id,
                    'process_id' => $risk->process_id,
                    'mission_type' => 'Audit basé sur les risques',
                    'title' => "Audit: {$risk->label}",
                    'objective' => $risk->description,
                    'priority_rank' => $rank,
                    'criticality' => $risk->criticality_raw,
                    'status' => 'scheduled'
                ]
            );

            $rank++;
        }

        return back()->with('success', count($validated['risk_ids']) . ' missions générées');
    }

    private function getEntity(Request $request): Entite
    {
        $entityId = $request->input('entity_id') ?? auth()->user()->entity_id;
        return Entite::findOrFail($entityId);
    }
}