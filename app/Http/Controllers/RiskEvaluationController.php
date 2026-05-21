<?php

namespace App\Http\Controllers;

use App\Models\RiskRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RiskEvaluationController extends Controller
{
    // ── Pages ─────────────────────────────────────────────────────────────────

    public function inherente(): Response
    {
        return $this->renderPage('inherente');
    }

    public function residuelle(): Response
    {
        return $this->renderPage('residuelle');
    }

    public function cible(): Response
    {
        return $this->renderPage('cible');
    }

    private function renderPage(string $type): Response
    {
        $tenantId    = session('tenant_id') ?? 1;
        $activeConfig = $this->getActiveMatrixConfig($tenantId);

        return Inertia::render('dashboards/Risk/RiskEvaluation/Index', [
            'type'             => $type,
            'tree'             => $this->buildTree($tenantId, $type),
            'impactLevels'     => $this->getImpactLevels($tenantId, $activeConfig?->id),
            'frequencyLevels'  => $this->getFrequencyLevels($tenantId, $activeConfig?->id),
            'criticalityZones' => $this->getCriticalityZones($tenantId, $activeConfig?->id),
            'evaluatedRisks'   => $this->getEvaluatedRisks($tenantId, $type),
            'activeMatrix'     => $activeConfig ? [
                'id'          => $activeConfig->id,
                'name'        => $activeConfig->name,
                'matrix_size' => $activeConfig->matrix_size,
            ] : null,
        ]);
    }

    // ── Sauvegarde AJAX ───────────────────────────────────────────────────────

    public function save(Request $request, int $id): JsonResponse
    {
        $tenantId  = session('tenant_id') ?? 1;
        $validated = $request->validate([
            'type'               => 'required|in:inherente,residuelle,cible',
            'impact_level_id'    => 'required|integer',
            'frequency_level_id' => 'required|integer',
        ]);

        $risk = RiskRegister::on('tenant')->tenant($tenantId)->findOrFail($id);

        [$impactField, $freqField] = $this->evalFields($validated['type']);

        $risk->update([
            $impactField => $validated['impact_level_id'],
            $freqField   => $validated['frequency_level_id'],
        ]);

        match ($validated['type']) {
            'inherente'  => $risk->computeCriticality(),
            'residuelle' => $risk->computeResidualCriticality(),
            'cible'      => $risk->computeTargetCriticality(),
        };

        $risk->load([
            'impactLevel', 'frequencyLevel', 'criticalityZone',
            'residualImpactLevel', 'residualFrequencyLevel', 'residualCriticalityZone',
            'targetImpactLevel',   'targetFrequencyLevel',   'targetCriticalityZone',
            'activity.process.macroProcess',
        ]);

        return response()->json([
            'success'      => true,
            'evaluatedRisk'=> $this->formatEvaluatedRisk($risk, $validated['type']),
            'treeRiskData' => $this->formatTreeRisk($risk, $validated['type']),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Retourne [impactField, frequencyField] selon le type. */
    private function evalFields(string $type): array
    {
        return match ($type) {
            'inherente'  => ['impact_level_id',          'frequency_level_id'],
            'residuelle' => ['residual_impact_level_id',  'residual_frequency_level_id'],
            'cible'      => ['target_impact_level_id',    'target_frequency_level_id'],
        };
    }

    private function scoreField(string $type): string
    {
        return match ($type) {
            'inherente'  => 'criticality_score',
            'residuelle' => 'residual_criticality_score',
            'cible'      => 'target_criticality_score',
        };
    }

    private function zoneRelation(string $type): string
    {
        return match ($type) {
            'inherente'  => 'criticalityZone',
            'residuelle' => 'residualCriticalityZone',
            'cible'      => 'targetCriticalityZone',
        };
    }

    // ── Construction de l'arborescence ───────────────────────────────────────

    private function buildTree(int $tenantId, string $type): array
    {
        [$impactField, $freqField] = $this->evalFields($type);
        $scoreField  = $this->scoreField($type);
        $zoneRelName = $this->zoneRelation($type);

        $query = RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->whereIn('statut', ['actif', 'draft'])
            ->with(['activity.process.macroProcess', $zoneRelName])
            ->orderBy('code_risk');

        // moved_to_library_at n'est présent que si la migration a été jouée
        if (DB::connection('tenant')->getSchemaBuilder()->hasColumn('risk_register', 'moved_to_library_at')) {
            $query->whereNull('moved_to_library_at');
        }

        $risks = $query->get();

        $tree = [];

        foreach ($risks as $risk) {
            $activity = $risk->activity;
            $process  = $activity?->process;
            $macro    = $process?->macroProcess;

            $macroId   = $macro?->id    ?? 0;
            $processId = $process?->id  ?? 0;
            $actId     = $activity?->id ?? 0;

            if (!isset($tree[$macroId])) {
                $tree[$macroId] = [
                    'id'        => $macroId,
                    'name'      => $macro?->name ?? '(Sans macro-processus)',
                    'code'      => $macro?->code ?? '',
                    'processes' => [],
                ];
            }
            if (!isset($tree[$macroId]['processes'][$processId])) {
                $tree[$macroId]['processes'][$processId] = [
                    'id'         => $processId,
                    'name'       => $process?->name ?? '(Sans processus)',
                    'code'       => $process?->code ?? '',
                    'activities' => [],
                ];
            }
            if (!isset($tree[$macroId]['processes'][$processId]['activities'][$actId])) {
                $tree[$macroId]['processes'][$processId]['activities'][$actId] = [
                    'id'    => $actId,
                    'name'  => $activity?->name ?? '(Sans activité)',
                    'code'  => $activity?->code ?? '',
                    'risks' => [],
                ];
            }

            $zone = $risk->relationLoaded($zoneRelName) ? $risk->{$zoneRelName} : null;

            $tree[$macroId]['processes'][$processId]['activities'][$actId]['risks'][] = [
                'id'                 => $risk->id,
                'code_risk'          => $risk->code_risk,
                'libelle'            => $risk->libelle,
                'statut'             => $risk->statut,
                'impact_level_id'    => $risk->{$impactField},
                'frequency_level_id' => $risk->{$freqField},
                'score'              => $risk->{$scoreField},
                'zone_color'         => $zone?->color_code,
                'zone_label'         => $zone?->label,
            ];
        }

        return array_values(array_map(function ($macro) {
            $macro['processes'] = array_values(array_map(function ($process) {
                $process['activities'] = array_values($process['activities']);
                return $process;
            }, $macro['processes']));
            return $macro;
        }, $tree));
    }

    // ── Risques déjà évalués (table du bas) ───────────────────────────────────

    private function getEvaluatedRisks(int $tenantId, string $type): array
    {
        [$impactField, $freqField] = $this->evalFields($type);

        $evalQuery = RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->whereNotNull($impactField)
            ->whereNotNull($freqField)
            ->with([
                'activity.process.macroProcess',
                'impactLevel', 'frequencyLevel', 'criticalityZone',
                'residualImpactLevel', 'residualFrequencyLevel', 'residualCriticalityZone',
                'targetImpactLevel',   'targetFrequencyLevel',   'targetCriticalityZone',
            ])
            ->orderBy('code_risk');

        if (DB::connection('tenant')->getSchemaBuilder()->hasColumn('risk_register', 'moved_to_library_at')) {
            $evalQuery->whereNull('moved_to_library_at');
        }

        $risks = $evalQuery->get();

        return $risks->map(fn ($r) => $this->formatEvaluatedRisk($r, $type))->values()->toArray();
    }

    // ── Formatage ─────────────────────────────────────────────────────────────

    private function formatEvaluatedRisk(RiskRegister $r, string $type): array
    {
        [$impactField, $freqField] = $this->evalFields($type);
        $scoreField  = $this->scoreField($type);
        $zoneRelName = $this->zoneRelation($type);

        $impact = match ($type) {
            'inherente'  => $r->impactLevel,
            'residuelle' => $r->residualImpactLevel,
            'cible'      => $r->targetImpactLevel,
        };
        $frequency = match ($type) {
            'inherente'  => $r->frequencyLevel,
            'residuelle' => $r->residualFrequencyLevel,
            'cible'      => $r->targetFrequencyLevel,
        };
        $zone = $r->{$zoneRelName} ?? null;

        $activity = $r->activity;
        $process  = $activity?->process;
        $macro    = $process?->macroProcess;

        return [
            'id'                 => $r->id,
            'code_risk'          => $r->code_risk,
            'libelle'            => $r->libelle,
            'statut'             => $r->statut,
            'macro_name'         => $macro?->name,
            'process_name'       => $process?->name,
            'activity_name'      => $activity?->name,
            'impact_level_id'    => $r->{$impactField},
            'impact_label'       => $impact?->label,
            'impact_score'       => $impact?->score,
            'impact_color'       => $impact?->color_code,
            'frequency_level_id' => $r->{$freqField},
            'frequency_label'    => $frequency?->label,
            'frequency_score'    => $frequency?->score,
            'frequency_color'    => $frequency?->color_code,
            'score'              => $r->{$scoreField},
            'zone_label'         => $zone?->label,
            'zone_color'         => $zone?->color_code,
        ];
    }

    private function formatTreeRisk(RiskRegister $r, string $type): array
    {
        [$impactField, $freqField] = $this->evalFields($type);
        $scoreField  = $this->scoreField($type);
        $zoneRelName = $this->zoneRelation($type);
        $zone        = $r->{$zoneRelName} ?? null;

        return [
            'id'                 => $r->id,
            'impact_level_id'    => $r->{$impactField},
            'frequency_level_id' => $r->{$freqField},
            'score'              => $r->{$scoreField},
            'zone_color'         => $zone?->color_code,
            'zone_label'         => $zone?->label,
        ];
    }

    // ── Data helpers ──────────────────────────────────────────────────────────

    /**
     * Retourne la configuration de matrice active pour le tenant.
     * Toutes les données de niveaux et zones sont filtrées sur cette config.
     */
    private function getActiveMatrixConfig(int $tenantId): ?object
    {
        return DB::connection('tenant')
            ->table('risk_matrix_configs')
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->first(['id', 'name', 'matrix_size']);
    }

    private function getImpactLevels(int $tenantId, ?int $configId): array
    {
        if (!$configId) return [];

        return DB::connection('tenant')
            ->table('risk_impact_levels')
            ->where('tenant_id', $tenantId)
            ->where('matrix_config_id', $configId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')->orderBy('score')
            ->get(['id', 'label', 'score', 'color_code', 'description'])
            ->map(fn ($r) => [
                'id'          => $r->id,
                'label'       => $r->label,
                'score'       => $r->score,
                'color'       => $r->color_code,
                'description' => $r->description,
            ])
            ->toArray();
    }

    private function getFrequencyLevels(int $tenantId, ?int $configId): array
    {
        if (!$configId) return [];

        return DB::connection('tenant')
            ->table('risk_frequency_levels')
            ->where('tenant_id', $tenantId)
            ->where('matrix_config_id', $configId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')->orderBy('score')
            ->get(['id', 'label', 'score', 'color_code', 'description'])
            ->map(fn ($r) => [
                'id'          => $r->id,
                'label'       => $r->label,
                'score'       => $r->score,
                'color'       => $r->color_code,
                'description' => $r->description,
            ])
            ->toArray();
    }

    private function getCriticalityZones(int $tenantId, ?int $configId): array
    {
        if (!$configId) return [];

        return DB::connection('tenant')
            ->table('risk_criticality_zones')
            ->where('tenant_id', $tenantId)
            ->where('matrix_config_id', $configId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')->orderBy('min_score')
            ->get(['id', 'label', 'min_score', 'max_score', 'color_code'])
            ->map(fn ($z) => [
                'id'        => $z->id,
                'label'     => $z->label,
                'min_score' => $z->min_score,
                'max_score' => $z->max_score,
                'color_code'=> $z->color_code,
            ])
            ->toArray();
    }
}
