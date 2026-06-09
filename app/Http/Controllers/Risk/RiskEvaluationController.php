<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Models\RiskRegister;
use App\Models\RiskMatrixConfig;
use App\Models\RiskImpactLevel;
use App\Models\RiskFrequencyLevel;
use App\Models\RiskCriticalityZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RiskEvaluationController extends Controller
{
    private function tenantId(): int
    {
        return (int)(session('tenant_id') ?? 1);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // INDEX — Évaluation inhérente
    // ═══════════════════════════════════════════════════════════════════════
    public function inherente(Request $request): Response
    {
        $tid = $this->tenantId();

        // ── Configurations matrice disponibles ────────────────────────────
        $matrixConfigs = RiskMatrixConfig::forTenant($tid)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'matrix_size'  => $c->matrix_size,
                'matrix_label' => $c->matrix_size . '×' . $c->matrix_size,
                'is_active'    => (bool)$c->is_active,
            ]);

        $selectedConfigId = $request->integer('config_id')
            ?: optional($matrixConfigs->firstWhere('is_active', true))['id']
            ?: optional($matrixConfigs->first())['id'];

        // ── Données de la matrice sélectionnée ────────────────────────────
        $matrixData = null;
        if ($selectedConfigId) {
            $config = RiskMatrixConfig::forTenant($tid)
                ->with([
                    'impactLevels'      => fn($q) => $q->ordered(),
                    'frequencyLevels'   => fn($q) => $q->ordered(),
                    'criticalityZones'  => fn($q) => $q->ordered(),
                ])
                ->find($selectedConfigId);

            if ($config) {
                $matrixData = $this->buildMatrixPayload($config);
            }
        }

        // ── Risques du registre (hors bibliothèque) ───────────────────────
        $risks = RiskRegister::on('tenant')
            ->tenant($tid)
            ->registre()
            ->with([
                'activity.process.macroProcess',
                'impactLevel',
                'frequencyLevel',
                'criticalityZone',
            ])
            ->whereNull('deleted_at')
            ->orderBy('activity_id')
            ->orderBy('id')
            ->get()
            ->map(fn($r) => $this->formatRisk($r));

        return Inertia::render('dashboards/Risk/Evaluation/Inherente', [
            'risks'                      => $risks,
            'tree'                       => $this->buildTree($risks),
            'stats'                      => $this->getStats($tid),
            'matrixConfigs'              => $matrixConfigs,
            'matrixData'                 => $matrixData,
            'selectedConfigId'           => $selectedConfigId,
            'frequencyCriteriaTemplates' => $this->getFrequencyCriteriaTemplates($tid, $selectedConfigId),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STORE — Enregistrer l'évaluation inhérente d'un risque
    // POST risk.core.evaluation.inherente.store
    // ═══════════════════════════════════════════════════════════════════════
    public function storeInherente(Request $request): JsonResponse
    {
        $tid = $this->tenantId();

        $v = $request->validate([
            'risk_id'         => 'required|integer',
            'impact_score'    => 'required|integer|min:1',
            'frequency_score' => 'required|integer|min:1',
        ]);

        $risk = RiskRegister::on('tenant')
            ->tenant($tid)
            ->findOrFail($v['risk_id']);

        // Trouver les niveaux correspondants
        $impactLevel = RiskImpactLevel::where('tenant_id', $tid)
            ->where('score', $v['impact_score'])
            ->first();

        $frequencyLevel = RiskFrequencyLevel::where('tenant_id', $tid)
            ->where('score', $v['frequency_score'])
            ->first();

        // Score de criticité inhérente
        $criticalityScore = $v['impact_score'] * $v['frequency_score'];

        // Zone de criticité correspondante
        $zone = RiskCriticalityZone::where('tenant_id', $tid)
            ->where('impact_score',    $v['impact_score'])
            ->where('frequency_score', $v['frequency_score'])
            ->first();

        // Mise à jour du risque — champs évaluation inhérente
        $risk->update([
            'impact_level_id'     => $impactLevel?->id,
            'frequency_level_id'  => $frequencyLevel?->id,
            'criticality_score'   => $criticalityScore,
            'criticality_zone_id' => $zone?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Évaluation inhérente de {$risk->code_risk} enregistrée.",
            'risk'    => [
                'id'               => $risk->id,
                'impact_score'     => $v['impact_score'],
                'frequency_score'  => $v['frequency_score'],
                'criticality_score'=> $criticalityScore,
                'impact_label'     => $impactLevel?->label,
                'frequency_label'  => $frequencyLevel?->label,
                'zone_label'       => $zone?->label,
                'zone_color'       => $zone?->color_code,
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE
    // ═══════════════════════════════════════════════════════════════════════

    private function buildMatrixPayload(RiskMatrixConfig $config): array
    {
        $impacts     = $config->impactLevels->sortByDesc('score')->values();
        $frequencies = $config->frequencyLevels->sortBy('score')->values();

        $zoneMap = [];
        foreach ($config->criticalityZones as $zone) {
            $key = $zone->impact_score . '_' . $zone->frequency_score;
            $zoneMap[$key] = $zone;
        }

        $cells = [];
        foreach ($impacts as $impact) {
            $row = [];
            foreach ($frequencies as $freq) {
                $key  = $impact->score . '_' . $freq->score;
                $zone = $zoneMap[$key] ?? null;
                $row[] = [
                    'score'            => $impact->score * $freq->score,
                    'impact_score'     => $impact->score,
                    'frequency_score'  => $freq->score,
                    'zone_id'          => $zone?->id,
                    'zone_label'       => $zone?->label,
                    'zone_color'       => $zone?->color_code ?? '#e2e8f0',
                    'zone_description' => $zone?->description ?? '',
                ];
            }
            $cells[] = $row;
        }

        return [
            'config' => [
                'id'           => $config->id,
                'name'         => $config->name,
                'matrix_size'  => $config->matrix_size,
                'matrix_label' => $config->matrix_size . '×' . $config->matrix_size,
                'max_score'    => $config->matrix_size * $config->matrix_size,
                'is_active'    => (bool)$config->is_active,
            ],
            'impacts' => $impacts->map(fn($l) => [
                'id'          => $l->id,
                'label'       => $l->label,
                'score'       => $l->score,
                'description' => $l->description ?? '',
                'color_code'  => $l->color_code,
            ])->values(),
            'frequencies' => $frequencies->map(fn($l) => [
                'id'          => $l->id,
                'label'       => $l->label,
                'score'       => $l->score,
                'description' => $l->description ?? '',
                'recurrence'  => $l->recurrence ?? '',
                'color_code'  => $l->color_code,
            ])->values(),
            'cells' => $cells,
            'zones' => $config->criticalityZones->sortBy('sort_order')->values()->map(fn($z) => [
                'id'              => $z->id,
                'label'           => $z->label,
                'impact_score'    => $z->impact_score,
                'frequency_score' => $z->frequency_score,
                'score'           => $z->score ?? ($z->impact_score * $z->frequency_score),
                'color_code'      => $z->color_code,
                'description'     => $z->description ?? '',
            ])->values(),
        ];
    }

    private function buildTree($risks): array
    {
        $tree = [];
        foreach ($risks as $risk) {
            $macroId   = $risk['macro_process_id'] ?? 0;
            $processId = $risk['process_id']        ?? 0;
            $actId     = $risk['activity_id']       ?? 0;

            if (!isset($tree[$macroId])) {
                $tree[$macroId] = [
                    'id'        => $macroId,
                    'code'      => $risk['macro_process_code'] ?? '—',
                    'name'      => $risk['macro_process_name'] ?? 'Sans macro-processus',
                    'kind'      => $risk['macro_process_kind'] ?? null,
                    'processes' => [],
                ];
            }
            if (!isset($tree[$macroId]['processes'][$processId])) {
                $tree[$macroId]['processes'][$processId] = [
                    'id'         => $processId,
                    'code'       => $risk['process_code'] ?? '—',
                    'name'       => $risk['process_name'] ?? 'Sans processus',
                    'activities' => [],
                ];
            }
            if (!isset($tree[$macroId]['processes'][$processId]['activities'][$actId])) {
                $tree[$macroId]['processes'][$processId]['activities'][$actId] = [
                    'id'    => $actId,
                    'code'  => $risk['activity_code'] ?? '—',
                    'name'  => $risk['activity_name'] ?? 'Sans activité',
                    'risks' => [],
                ];
            }
            $tree[$macroId]['processes'][$processId]['activities'][$actId]['risks'][] = $risk;
        }

        return array_values(array_map(function ($macro) {
            $macro['processes'] = array_values(array_map(function ($process) {
                $process['activities'] = array_values($process['activities']);
                return $process;
            }, $macro['processes']));
            return $macro;
        }, $tree));
    }

    private function getFrequencyCriteriaTemplates(int $tid, ?int $configId): array
    {
        if (!$configId) return [];

        // Templates de critères pour cette config
        $templates = \App\Models\RiskFrequencyCriteriaTemplate::where('tenant_id', $tid)
            ->where('matrix_config_id', $configId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        if ($templates->isEmpty()) return [];

        // Critères remplis (descriptions) groupés par template_id puis frequency_level_id
        $criteria = \App\Models\RiskFrequencyCriterion::where('tenant_id', $tid)
            ->whereIn('template_id', $templates->pluck('id'))
            ->whereNull('deleted_at')
            ->get();

        $criteriaByTemplate = $criteria->groupBy('template_id');

        return $templates->map(fn($tpl) => [
            'id'          => $tpl->id,
            'designation' => $tpl->designation,
            'hint'        => $tpl->hint,
            'sort_order'  => $tpl->sort_order,
            // levels : liste des descriptions par frequency_level_id
            'levels' => ($criteriaByTemplate->get($tpl->id) ?? collect())
                ->map(fn($c) => [
                    'frequency_level_id' => $c->frequency_level_id,
                    'description'        => $c->description,
                ])->values()->all(),
        ])->toArray();
    }

    private function getStats(int $tid): array
    {
        $base = RiskRegister::on('tenant')->tenant($tid)->registre();
        return [
            'total'         => (clone $base)->count(),
            'evaluated'     => (clone $base)->whereNotNull('criticality_score')->count(),
            'not_evaluated' => (clone $base)->whereNull('criticality_score')->count(),
            'critical'      => (clone $base)->whereHas('criticalityZone', fn($q) => $q->where('label', 'LIKE', '%critique%'))->count(),
        ];
    }

    private function formatRisk(RiskRegister $r): array
    {
        $activity  = $r->relationLoaded('activity')            ? $r->activity            : null;
        $process   = $activity?->relationLoaded('process')     ? $activity->process      : null;
        $macro     = $process?->relationLoaded('macroProcess') ? $process->macroProcess  : null;
        $impact    = $r->relationLoaded('impactLevel')         ? $r->impactLevel         : null;
        $frequency = $r->relationLoaded('frequencyLevel')      ? $r->frequencyLevel      : null;
        $zone      = $r->relationLoaded('criticalityZone')     ? $r->criticalityZone     : null;

        return [
            'id'                  => $r->id,
            'code_risk'           => $r->code_risk,
            'libelle'             => $r->libelle,
            'entity_id'           => $r->entity_id,
            'activity_id'         => $r->activity_id,
            'process_id'          => $activity?->process_id,
            'macro_process_id'    => $process?->macro_process_id,
            'activity_code'       => $activity?->code,
            'activity_name'       => $activity?->name,
            'process_code'        => $process?->code,
            'process_name'        => $process?->name,
            'macro_process_code'  => $macro?->code,
            'macro_process_name'  => $macro?->name,
            'macro_process_kind'  => $macro?->kind,
            // Évaluation inhérente
            'impact_score'        => $r->impactLevel?->score    ?? $impact?->score,
            'frequency_score'     => $r->frequencyLevel?->score ?? $frequency?->score,
            'criticality_score'   => $r->criticality_score,
            'impact_label'        => $impact?->label,
            'frequency_label'     => $frequency?->label,
            'zone_label'          => $zone?->label,
            'zone_color'          => $zone?->color_code,
        ];
    }
}