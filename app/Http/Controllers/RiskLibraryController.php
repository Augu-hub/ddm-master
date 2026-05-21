<?php

namespace App\Http\Controllers;

use App\Models\RiskRegister;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RiskLibraryController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $tenantId = session('tenant_id') ?? 1;

        $risks = RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->bibliotheque()
            ->with([
                'activity.process.macroProcess',
                'nomenclature',
                'impactLevel',
                'frequencyLevel',
                'criticalityZone',
            ])
            ->orderBy('moved_to_library_at', 'desc')
            ->get()
            ->map(fn ($r) => $this->formatRisk($r));

        return Inertia::render('dashboards/Risk/RiskLibrary/Index', [
            'tree'  => $this->buildTree($risks),
            'stats' => $this->getStats($tenantId),
        ]);
    }

    // ── Retirer de la bibliothèque ────────────────────────────────────────────

    public function removeFromLibrary(int $id)
    {
        $tenantId = session('tenant_id') ?? 1;

        $risk = RiskRegister::on('tenant')
            ->tenant($tenantId)
            ->bibliotheque()
            ->where('id', $id)
            ->firstOrFail();

        $risk->update(['moved_to_library_at' => null]);

        return back()->with('success', "Risque {$risk->code_risk} retiré de la bibliothèque.");
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    /**
     * Construit l'arborescence :
     * Macro-processus → Processus → Activité → Risques
     */
    private function buildTree($risks): array
    {
        $tree = [];

        foreach ($risks as $risk) {
            $macroId   = $risk['macro_process_id']   ?? 0;
            $processId = $risk['process_id']          ?? 0;
            $actId     = $risk['activity_id']         ?? 0;

            // ── Macro-processus ────────────────────────────────────────────
            if (!isset($tree[$macroId])) {
                $tree[$macroId] = [
                    'id'        => $macroId,
                    'code'      => $risk['macro_process_code'] ?? '—',
                    'name'      => $risk['macro_process_name'] ?? 'Sans macro-processus',
                    'kind'      => $risk['macro_process_kind'] ?? null,
                    'processes' => [],
                ];
            }

            // ── Processus ──────────────────────────────────────────────────
            if (!isset($tree[$macroId]['processes'][$processId])) {
                $tree[$macroId]['processes'][$processId] = [
                    'id'         => $processId,
                    'code'       => $risk['process_code'] ?? '—',
                    'name'       => $risk['process_name'] ?? 'Sans processus',
                    'activities' => [],
                ];
            }

            // ── Activité ───────────────────────────────────────────────────
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

        // Réindexation des tableaux associatifs → tableaux séquentiels
        return array_values(array_map(function ($macro) {
            $macro['processes'] = array_values(array_map(function ($process) {
                $process['activities'] = array_values(array_map(function ($activity) {
                    return $activity;
                }, $process['activities']));
                return $process;
            }, $macro['processes']));
            return $macro;
        }, $tree));
    }

    private function getStats(int $tenantId): array
    {
        $base = RiskRegister::on('tenant')->tenant($tenantId);

        return [
            'total_registre'    => (clone $base)->registre()->count(),
            'total_bibliotheque'=> (clone $base)->bibliotheque()->count(),
            'total_actif'       => (clone $base)->bibliotheque()->actif()->count(),
            'total_draft'       => (clone $base)->bibliotheque()->draft()->count(),
        ];
    }

    private function formatRisk(RiskRegister $r): array
    {
        $activity  = $r->relationLoaded('activity')            ? $r->activity            : null;
        $process   = $activity?->relationLoaded('process')     ? $activity->process      : null;
        $macro     = $process?->relationLoaded('macroProcess') ? $process->macroProcess  : null;
        $zone      = $r->relationLoaded('criticalityZone')     ? $r->criticalityZone     : null;
        $nomen     = $r->relationLoaded('nomenclature')        ? $r->nomenclature        : null;
        $impact    = $r->relationLoaded('impactLevel')         ? $r->impactLevel         : null;
        $frequency = $r->relationLoaded('frequencyLevel')      ? $r->frequencyLevel      : null;

        return [
            'id'                   => $r->id,
            'code_risk'            => $r->code_risk,
            'libelle'              => $r->libelle,
            'description'          => $r->description,
            'entity_id'            => $r->entity_id,
            'activity_id'          => $r->activity_id,
            'process_id'           => $activity?->process_id,
            'macro_process_id'     => $process?->macro_process_id,
            'activity_code'        => $activity?->code,
            'activity_name'        => $activity?->name,
            'process_code'         => $process?->code,
            'process_name'         => $process?->name,
            'macro_process_code'   => $macro?->code,
            'macro_process_name'   => $macro?->name,
            'macro_process_kind'   => $macro?->kind,
            'nomenclature_label'   => $nomen?->label,
            'causes'               => $r->causes,
            'consequences'         => $r->consequences,
            'controles_existants'  => $r->controles_existants,
            'owner'                => $r->owner,
            'plan_traitement'      => $r->plan_traitement,
            'impact_label'         => $impact?->label,
            'impact_score'         => $impact?->score,
            'frequency_label'      => $frequency?->label,
            'frequency_score'      => $frequency?->score,
            'criticality_score'    => $r->criticality_score,
            'zone_label'           => $zone?->label,
            'zone_color'           => $zone?->color_hex ?? null,
            'statut'               => $r->statut,
            'statut_label'         => $r->statut_label,
            'statut_badge'         => $r->statut_badge,
            'moved_to_library_at'  => $r->moved_to_library_at?->format('d/m/Y'),
            'created_at'           => $r->created_at?->format('d/m/Y'),
        ];
    }
}
